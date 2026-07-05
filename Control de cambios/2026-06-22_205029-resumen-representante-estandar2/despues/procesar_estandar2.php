<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// 1. Exigir sesión válida
$u = require_auth($conn);
$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// 2. Solo el Responsable SST gestiona planillas. El Representante Legal solo consulta resumen.
if ($usuario_rol !== 'sst') {
    header('Location: estandar2.php?error=no_permission');
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

function estandar2_planilla_summary_columns_exist(PDO $conn): bool
{
    $required = ['valor_total', 'cedulas_detectadas', 'trabajadores_esperados', 'riesgos_detectados', 'nit_coincide', 'novedades_resumen'];
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'estandar2_planillas'
          AND COLUMN_NAME IN ('valor_total','cedulas_detectadas','trabajadores_esperados','riesgos_detectados','nit_coincide','novedades_resumen')
    ");
    $stmt->execute();
    $found = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return count(array_intersect($required, $found)) === count($required);
}

// 3. Obtener el ID de la empresa del usuario
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

if (!$empresa_id) {
    die("Error crítico: El usuario no está asociado a ninguna empresa.");
}

// ========================================================
// LÓGICA PARA SUBIR PLANILLA (PILA) Y REGISTRAR ANÁLISIS
// ========================================================
if ($accion === 'subir_planilla') {
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];
    $has_summary_columns = estandar2_planilla_summary_columns_exist($conn);
    $valor_total = isset($_POST['valor_total']) && $_POST['valor_total'] !== '' ? (float)$_POST['valor_total'] : null;
    $cedulas_detectadas = isset($_POST['cedulas_detectadas']) && $_POST['cedulas_detectadas'] !== '' ? (int)$_POST['cedulas_detectadas'] : null;
    $trabajadores_esperados = isset($_POST['trabajadores_esperados']) && $_POST['trabajadores_esperados'] !== '' ? (int)$_POST['trabajadores_esperados'] : null;
    $riesgos_detectados = trim($_POST['riesgos_detectados'] ?? '');
    $nit_c = $_POST['nit_coincide'] ?? 'NO';
    
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo'];
        
        // Seguridad: Validar estrictamente que sea PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime !== 'application/pdf') {
            die("Error de seguridad: Solo se permiten archivos en formato PDF.");
        }
        
        // 4. RUTAS BLINDADAS PARA GUARDAR EL ARCHIVO
        $ruta_relativa = "uploads/empresas/empresa_{$empresa_id}/estandar2/planillas/";
        $ruta_absoluta = __DIR__ . "/" . $ruta_relativa;
        
        // Crear carpeta si no existe
        if (!file_exists($ruta_absoluta)) {
            if (!mkdir($ruta_absoluta, 0777, true)) {
                die("Error de permisos: El servidor no deja crear la carpeta 'uploads/'. Revisa los permisos.");
            }
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        // Nombre inteligente: PILA_2026_3_60a1b2c.pdf
        $filename = "PILA_{$anio}_{$mes}_" . uniqid() . "." . $ext;
        
        $destino_fisico = $ruta_absoluta . $filename;
        $ruta_bd = $ruta_relativa . $filename; 
        
        // 5. Mover el archivo a la carpeta final
        if (move_uploaded_file($file['tmp_name'], $destino_fisico)) {
            
            // A. Verificar si ya existe una planilla para ese mes y año para reemplazarla
            $stmt_check = $conn->prepare("
                SELECT id, archivo_url 
                FROM estandar2_planillas 
                WHERE mes = ? AND anio = ? AND subido_por IN (SELECT id FROM usuarios WHERE empresa_id = ?)
            ");
            $stmt_check->execute([$mes, $anio, $empresa_id]);
            $existe = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($existe) {
                // Borrar archivo viejo físico del servidor
                $archivo_viejo = __DIR__ . "/" . $existe['archivo_url'];
                if (file_exists($archivo_viejo) && is_file($archivo_viejo)) {
                    unlink($archivo_viejo);
                }
                
                // Actualizar registro en BD
                if ($has_summary_columns) {
                    $stmt_upd = $conn->prepare("
                        UPDATE estandar2_planillas
                        SET archivo_url = ?, valor_total = ?, cedulas_detectadas = ?, trabajadores_esperados = ?,
                            riesgos_detectados = ?, nit_coincide = ?, subido_por = ?, fecha_subida = NOW()
                        WHERE id = ?
                    ");
                    $stmt_upd->execute([$ruta_bd, $valor_total, $cedulas_detectadas, $trabajadores_esperados, $riesgos_detectados, $nit_c, $usuario_id, $existe['id']]);
                } else {
                    $stmt_upd = $conn->prepare("UPDATE estandar2_planillas SET archivo_url = ?, subido_por = ?, fecha_subida = NOW() WHERE id = ?");
                    $stmt_upd->execute([$ruta_bd, $usuario_id, $existe['id']]);
                }
            } else {
                // Insertar nueva planilla en BD
                if ($has_summary_columns) {
                    $stmt_ins = $conn->prepare("
                        INSERT INTO estandar2_planillas (
                            mes, anio, archivo_url, valor_total, cedulas_detectadas, trabajadores_esperados,
                            riesgos_detectados, nit_coincide, subido_por, fecha_subida
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt_ins->execute([$mes, $anio, $ruta_bd, $valor_total, $cedulas_detectadas, $trabajadores_esperados, $riesgos_detectados, $nit_c, $usuario_id]);
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO estandar2_planillas (mes, anio, archivo_url, subido_por, fecha_subida) VALUES (?, ?, ?, ?, NOW())");
                    $stmt_ins->execute([$mes, $anio, $ruta_bd, $usuario_id]);
                }
            }

            // ========================================================
            // B. GUARDAR LOS RESULTADOS DETALLADOS DEL ANÁLISIS
            // ========================================================
            $trab_f = $_POST['trab_found'] ?? '0/0';
            $trab_faltantes = $_POST['trab_faltantes'] ?? '';
            $inc_json = $_POST['incapacidades_json'] ?? '[]';
            
            // Texto de trabajadores faltantes
            $detalle_trab = $trab_f;
            if (!empty($trab_faltantes)) {
                $detalle_trab .= " (Faltan en PILA: $trab_faltantes)";
            }

            // Texto de incapacidades
            $inc_array = json_decode($inc_json, true) ?? [];
            $resumen_inc = "Sin novedades IGE/IRL.";
            
            if (count($inc_array) > 0) {
                $resumen_inc = "Novedades encontradas:";
                foreach($inc_array as $inc) {
                    $resumen_inc .= " [{$inc['tipo']}: {$inc['nombre']} CC. {$inc['cedula']}]";
                }
            }

            if ($has_summary_columns) {
                $stmt_summary = $conn->prepare("
                    UPDATE estandar2_planillas
                    SET novedades_resumen = ?
                    WHERE mes = ? AND anio = ? AND subido_por IN (SELECT id FROM usuarios WHERE empresa_id = ?)
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $stmt_summary->execute([$resumen_inc, $mes, $anio, $empresa_id]);
            }

            // Construir el reporte final para la base de datos
            $descripcion_log = "Análisis PILA ($mes/$anio). NIT Coincide: $nit_c. Trabajadores detectados: $detalle_trab. $resumen_inc";

            // Guardar el reporte en la tabla general de logs_actividad
            $stmt_log = $conn->prepare("INSERT INTO logs_actividad (usuario_id, accion, descripcion, ip_address, fecha) VALUES (?, 'ANALISIS_PILA', ?, ?, NOW())");
            $stmt_log->execute([$usuario_id, $descripcion_log, $_SERVER['REMOTE_ADDR'] ?? '']);
            
            // Redirigir de vuelta al panel con mensaje de éxito
            header('Location: estandar2.php?msg=subido&anio=' . $anio);
            exit;
        } else {
            die("Error del servidor: No se pudo guardar el archivo físico. Verifica los permisos de escritura en la carpeta 'uploads'.");
        }
    } else {
        die("Error: No se recibió el archivo o es demasiado grande para tu servidor.");
    }
} 

// ========================================================
// LÓGICA PARA ELIMINAR PLANILLA
// ========================================================
elseif ($accion === 'eliminar_planilla') {
    $id = (int)$_GET['id'];
    
    // Verificar por seguridad que la planilla pertenece a esta empresa
    $stmt_check = $conn->prepare("
        SELECT p.id, p.archivo_url, p.anio, p.mes 
        FROM estandar2_planillas p 
        JOIN usuarios u ON p.subido_por = u.id 
        WHERE p.id = ? AND u.empresa_id = ?
    ");
    $stmt_check->execute([$id, $empresa_id]);
    $planilla = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($planilla) {
        $archivo_fisico = __DIR__ . "/" . $planilla['archivo_url'];
        
        // Borrar el PDF físico del servidor
        if (file_exists($archivo_fisico) && is_file($archivo_fisico)) {
            unlink($archivo_fisico);
        }
        
        // Borrar de la base de datos
        $stmt_del = $conn->prepare("DELETE FROM estandar2_planillas WHERE id = ?");
        $stmt_del->execute([$id]);
        
        // Registrar en el log de actividad que fue eliminada
        $stmt_log = $conn->prepare("INSERT INTO logs_actividad (usuario_id, accion, descripcion, ip_address, fecha) VALUES (?, 'ELIMINAR_PILA', ?, ?, NOW())");
        $stmt_log->execute([$usuario_id, "Eliminó planilla del periodo {$planilla['mes']}/{$planilla['anio']}.", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        header('Location: estandar2.php?msg=eliminado&anio=' . $planilla['anio']);
        exit;
    } else {
        die("Error de permisos: No tienes autorización para eliminar esta planilla.");
    }
} else {
    // Si entran directamente al archivo sin POST/GET válidos, los devolvemos
    header('Location: estandar2.php');
    exit;
}
?>
