<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

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
        $conn->rollBack();
        header('Location: estandar3.php?error=db_error');
        exit;
    }
}