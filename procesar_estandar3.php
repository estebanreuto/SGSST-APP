<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/capacitaciones_schema.php';

// Exige sesión válida
$u = require_auth($conn);
ensure_capacitaciones_schema($conn);

if (($_SESSION['usuario_rol'] ?? '') !== 'sst') {
    header('Location: dashboard.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($accion === 'crear_actividad' || $accion === 'editar_actividad')) {
    
    // Obtener el ID de la empresa por seguridad
    $stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
    $stmt_emp->execute([$_SESSION['usuario_id']]);
    $empresa_id = $stmt_emp->fetchColumn();

    $nombre = trim($_POST['nombre_actividad']);
    $tipo = $_POST['tipo_capacitacion'];
    $categoria = $_POST['categoria'];
    $dirigido_a = $_POST['dirigido_a'];
    
    // CAMPOS NUEVOS
    $modalidad = $_POST['modalidad'] ?? 'Virtual';
    $lugar_exacto = trim($_POST['lugar_exacto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $es_curso_virtual = $modalidad === 'Sistema';
    $requiere_evaluacion = $es_curso_virtual || ($_POST['requiere_evaluacion'] ?? '0') === '1';
    
    // Unificar fecha y hora en formato SQL (YYYY-MM-DD HH:MM:00)
    $inicio = $_POST['fecha_inicio'] . ' ' . $_POST['hora_inicio'] . ':00';
    $fin = $_POST['fecha_fin'] . ' ' . $_POST['hora_fin'] . ':00';
    
    try {
        $conn->beginTransaction();

        if ($accion === 'crear_actividad') {
            // 1. Guardar la actividad nueva
            $sql = "INSERT INTO actividades_capacitacion (empresa_id, nombre_actividad, tipo_capacitacion, categoria, dirigido_a, fecha_inicio, fecha_fin, estado, modalidad, lugar_exacto, descripcion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'programada', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$empresa_id, $nombre, $tipo, $categoria, $dirigido_a, $inicio, $fin, $modalidad, $lugar_exacto, $descripcion]);
            $actividad_id = $conn->lastInsertId();
        } else {
            // 1B. Actualizar la actividad existente (Reprogramar)
            $actividad_id = intval($_POST['edit_id']);
            $sql = "UPDATE actividades_capacitacion 
                    SET nombre_actividad=?, tipo_capacitacion=?, categoria=?, dirigido_a=?, fecha_inicio=?, fecha_fin=?, estado='reprogramada', modalidad=?, lugar_exacto=?, descripcion=? 
                    WHERE id=? AND empresa_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $tipo, $categoria, $dirigido_a, $inicio, $fin, $modalidad, $lugar_exacto, $descripcion, $actividad_id, $empresa_id]);
            
            // Borramos los trabajadores asignados antes, para volverlos a guardar si cambió la selección
            $stmt_del = $conn->prepare("DELETE FROM actividades_trabajadores WHERE actividad_id = ?");
            $stmt_del->execute([$actividad_id]);
        }

        // 2. Si es trabajador específico, guardar la relación en la tabla pivote
        if ($dirigido_a === 'Trabajador Específico' && isset($_POST['trabajadores_seleccionados'])) {
            $stmt_rel = $conn->prepare("INSERT INTO actividades_trabajadores (actividad_id, usuario_id) VALUES (?, ?)");
            foreach ($_POST['trabajadores_seleccionados'] as $id_trab) {
                $stmt_rel->execute([$actividad_id, $id_trab]);
            }
        }

        if ($requiere_evaluacion) {
            $tipo_contenido = ($_POST['tipo_contenido'] ?? 'enlace') === 'video' ? 'video' : 'enlace';
            $contenido_url = trim($_POST['contenido_url'] ?? '');
            $instrucciones = trim($_POST['instrucciones_curso'] ?? '');
            $escala = in_array($_POST['escala_calificacion'] ?? '100', ['5', '10', '100'], true)
                ? $_POST['escala_calificacion']
                : '100';
            $puntaje_aprobacion = (float)($_POST['puntaje_aprobacion'] ?? ($escala === '5' ? 3 : ($escala === '10' ? 6 : 60)));
            $preguntas = json_decode($_POST['preguntas_json'] ?? '[]', true);
            $materiales = json_decode($_POST['materiales_json'] ?? '[]', true);

            if (!is_array($preguntas) || count($preguntas) === 0) {
                throw new RuntimeException('El curso debe contener al menos una pregunta.');
            }
            if ($es_curso_virtual && (!is_array($materiales) || count($materiales) === 0)) {
                throw new RuntimeException('El curso debe contener al menos una sección de material.');
            }

            $upload_dir = __DIR__ . '/uploads/capacitaciones';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }

            $video_archivo = null;

            $stmt_curso_actual = $conn->prepare("SELECT * FROM capacitaciones_cursos WHERE actividad_id = ?");
            $stmt_curso_actual->execute([$actividad_id]);
            $curso_actual = $stmt_curso_actual->fetch(PDO::FETCH_ASSOC);

            if ($curso_actual) {
                $video_archivo = $video_archivo ?: $curso_actual['video_archivo'];
                $stmt_curso = $conn->prepare("
                    UPDATE capacitaciones_cursos
                    SET tipo_contenido=?, contenido_url=?, video_archivo=?, imagen_portada=NULL,
                        instrucciones=?, escala_calificacion=?, puntaje_aprobacion=?
                    WHERE actividad_id=?
                ");
                $stmt_curso->execute([
                    $tipo_contenido, $contenido_url, $video_archivo,
                    $instrucciones, $escala, $puntaje_aprobacion, $actividad_id
                ]);
                $curso_id = (int)$curso_actual['id'];
                $conn->prepare("DELETE FROM capacitaciones_intentos WHERE curso_id = ?")->execute([$curso_id]);
                $conn->prepare("DELETE FROM capacitaciones_preguntas WHERE curso_id = ?")->execute([$curso_id]);
                $conn->prepare("DELETE FROM capacitaciones_materiales WHERE curso_id = ?")->execute([$curso_id]);
            } else {
                $stmt_curso = $conn->prepare("
                    INSERT INTO capacitaciones_cursos
                    (actividad_id, tipo_contenido, contenido_url, video_archivo, imagen_portada, instrucciones, escala_calificacion, puntaje_aprobacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_curso->execute([
                    $actividad_id, $tipo_contenido, $contenido_url, $video_archivo,
                    null, $instrucciones, $escala, $puntaje_aprobacion
                ]);
                $curso_id = (int)$conn->lastInsertId();
            }

            if ($es_curso_virtual) {
                $stmt_material = $conn->prepare("
                    INSERT INTO capacitaciones_materiales (curso_id, titulo, tipo, contenido, archivo, orden)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $allowed_types = ['texto', 'video', 'enlace', 'documento', 'imagen'];
                $allowed_extensions = [
                    'video' => ['mp4', 'webm', 'mov'],
                    'imagen' => ['jpg', 'jpeg', 'png', 'webp'],
                    'documento' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'],
                ];

                foreach ($materiales as $material_orden => $material) {
                    $key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($material['key'] ?? ''));
                    $titulo_material = trim($material['titulo'] ?? '');
                    $tipo_material = in_array($material['tipo'] ?? '', $allowed_types, true) ? $material['tipo'] : 'texto';
                    $contenido_material = trim($material['contenido'] ?? '');
                    $archivo_material = trim($material['archivo_actual'] ?? '');

                    if ($titulo_material === '') {
                        throw new RuntimeException('Cada material necesita un título.');
                    }
                    if ($tipo_material === 'enlace') {
                        $url_valida = filter_var($contenido_material, FILTER_VALIDATE_URL);
                        $scheme = strtolower((string)parse_url($contenido_material, PHP_URL_SCHEME));
                        if (!$url_valida || !in_array($scheme, ['http', 'https'], true)) {
                            throw new RuntimeException('Los enlaces de materiales deben comenzar por http:// o https://.');
                        }
                    }

                    $file_field = 'material_file_' . $key;
                    if (in_array($tipo_material, ['video', 'documento', 'imagen'], true)
                        && isset($_FILES[$file_field])
                        && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
                        $extension = strtolower(pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION));
                        if (!in_array($extension, $allowed_extensions[$tipo_material], true)) {
                            throw new RuntimeException('Formato de archivo no permitido en "' . $titulo_material . '".');
                        }
                        $file_name = 'material_' . $actividad_id . '_' . $material_orden . '_' . time() . '.' . $extension;
                        if (!move_uploaded_file($_FILES[$file_field]['tmp_name'], $upload_dir . '/' . $file_name)) {
                            throw new RuntimeException('No fue posible guardar el material "' . $titulo_material . '".');
                        }
                        $archivo_material = 'uploads/capacitaciones/' . $file_name;
                    }

                    if ($tipo_material === 'texto' && $contenido_material === '') {
                        throw new RuntimeException('La sección "' . $titulo_material . '" necesita contenido.');
                    }
                    if (in_array($tipo_material, ['video', 'documento', 'imagen'], true) && $archivo_material === '') {
                        throw new RuntimeException('Debes cargar un archivo para "' . $titulo_material . '".');
                    }

                    $stmt_material->execute([
                        $curso_id, $titulo_material, $tipo_material, $contenido_material,
                        $archivo_material, $material_orden
                    ]);
                }
            }

            $stmt_pregunta = $conn->prepare("
                INSERT INTO capacitaciones_preguntas (curso_id, enunciado, tipo, puntos, orden)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_opcion = $conn->prepare("
                INSERT INTO capacitaciones_opciones (pregunta_id, texto, es_correcta, orden)
                VALUES (?, ?, ?, ?)
            ");

            $total_preguntas = count($preguntas);
            $base_puntos = floor(((float)$escala / $total_preguntas) * 100) / 100;
            $puntos_usados = 0.0;
            foreach ($preguntas as $orden => $pregunta) {
                $enunciado = trim($pregunta['enunciado'] ?? '');
                $tipo_pregunta = in_array($pregunta['tipo'] ?? '', ['unica', 'multiple', 'verdadero_falso'], true)
                    ? $pregunta['tipo']
                    : 'unica';
                $puntos = $orden === $total_preguntas - 1
                    ? round((float)$escala - $puntos_usados, 2)
                    : $base_puntos;
                $puntos_usados += $puntos;
                $opciones = $pregunta['opciones'] ?? [];
                if ($enunciado === '' || !is_array($opciones) || count($opciones) < 2) {
                    throw new RuntimeException('Cada pregunta necesita un enunciado y al menos dos opciones.');
                }

                $stmt_pregunta->execute([$curso_id, $enunciado, $tipo_pregunta, $puntos, $orden]);
                $pregunta_id = (int)$conn->lastInsertId();
                $correctas = 0;
                foreach ($opciones as $opcion_orden => $opcion) {
                    $texto_opcion = trim($opcion['texto'] ?? '');
                    $es_correcta = !empty($opcion['correcta']) ? 1 : 0;
                    if ($texto_opcion === '') {
                        continue;
                    }
                    $correctas += $es_correcta;
                    $stmt_opcion->execute([$pregunta_id, $texto_opcion, $es_correcta, $opcion_orden]);
                }
                if ($correctas === 0) {
                    throw new RuntimeException('Cada pregunta necesita una respuesta correcta.');
                }
            }
        } else {
            $conn->prepare("DELETE FROM capacitaciones_cursos WHERE actividad_id = ?")->execute([$actividad_id]);
        }

        // ==========================================================
        // LA MAGIA: INTEGRACIÓN CON GOOGLE CALENDAR API Y MEET
        // ==========================================================
        $usar_api_google = false;

        if ($usar_api_google && file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
            
            $client = new Google\Client();
            $client->setAuthConfig('credentials.json'); 
            $client->addScope(Google\Service\Calendar::CALENDAR);
            
            if (isset($_SESSION['google_access_token'])) {
                $client->setAccessToken($_SESSION['google_access_token']);
                $service = new Google\Service\Calendar($client);
                
                $event = new Google\Service\Calendar\Event([
                  'summary' => 'SG-SST: ' . $nombre,
                  'description' => "Modalidad: $modalidad\nLugar: $lugar_exacto\nDescripción: $descripcion",
                  'start' => [
                    'dateTime' => date('c', strtotime($inicio)),
                    'timeZone' => 'America/Bogota', 
                  ],
                  'end' => [
                    'dateTime' => date('c', strtotime($fin)),
                    'timeZone' => 'America/Bogota',
                  ],
                  'conferenceData' => [
                    'createRequest' => [
                      'requestId' => 'sg-sst-meet-' . $actividad_id . '-' . time(),
                      'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                    ]
                  ]
                ]);
                
                // (Para simplificar en modo edición, aquí crearíamos un evento nuevo. 
                // Lo ideal a futuro es guardar el ID de Google Calendar en BD y hacer un $service->events->update())
                $createdEvent = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);
                $enlace_meet = $createdEvent->getHangoutLink(); 
                
                if ($enlace_meet) {
                    $stmt_upd = $conn->prepare("UPDATE actividades_capacitacion SET enlace_reunion = ? WHERE id = ?");
                    $stmt_upd->execute([$enlace_meet, $actividad_id]);
                }
                
                $conn->commit();
                header('Location: estandar3.php?save=success');
                exit;
            }
        }
        
        // ==========================================================
        // MÉTODO TRADICIONAL (Sin API directa)
        // ==========================================================
        $conn->commit(); 

        if ($es_curso_virtual) {
            header('Location: estandar3.php?save=curso_success');
            exit;
        }

        $google_start = date('Ymd\THis', strtotime($inicio));
        $google_end = date('Ymd\THis', strtotime($fin));
        $details = "Capacitación SG-SST: " . $tipo . "\nModalidad: " . $modalidad . "\n\n" . $descripcion;
        $location = $modalidad === 'Físico' ? $lugar_exacto : '';
        
        $google_url = "https://www.google.com/calendar/render?action=TEMPLATE" .
                      "&text=" . urlencode("SG-SST: " . $nombre) .
                      "&dates=" . $google_start . "/" . $google_end .
                      "&details=" . urlencode($details) .
                      "&location=" . urlencode($location) .
                      "&sf=true&output=xml";

        echo "<script>
                window.open('$google_url', '_blank');
                window.location.href = 'estandar3.php?save=success';
              </script>";
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header('Location: nueva_actividad.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}
