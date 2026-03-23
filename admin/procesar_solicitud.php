<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$accion = $_GET['accion'] ?? '';

if ($id && in_array($accion, ['aprobar', 'rechazar'])) {
    
    $nuevo_estado = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';
    
    // 1. Cambiamos el estado en la tabla de solicitudes
    $stmt = $conn->prepare("UPDATE solicitudes_empresas SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    // 2. Si se aprobó, creamos el usuario oficial en el sistema
    if ($accion === 'aprobar') {
        
        $stmt_sel = $conn->prepare("SELECT * FROM solicitudes_empresas WHERE id = ?");
        $stmt_sel->execute([$id]);
        $solicitud = $stmt_sel->fetch(PDO::FETCH_ASSOC);

        if ($solicitud) {
            // Verificamos por seguridad que no exista ya con esa cédula o correo
            $check = $conn->prepare("SELECT id FROM usuarios WHERE cedula = ? OR email = ?");
            $check->execute([$solicitud['cedula'], $solicitud['email']]);
            
            if (!$check->fetch()) {
                // Hacemos el INSERT en usuarios
                $sql_ins = "INSERT INTO usuarios (
                    nombre, apellido, cedula, email, telefono, rol, 
                    direccion, ciudad, barrio, localidad, firma
                ) VALUES (?,?,?,?,?, 'representante', ?,?,?,?,?)";
                
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->execute([
                    $solicitud['nombre'], $solicitud['apellido'], $solicitud['cedula'], 
                    $solicitud['email'], $solicitud['telefono'], $solicitud['direccion'], 
                    $solicitud['ciudad'], $solicitud['barrio'], $solicitud['localidad'], 
                    $solicitud['firma']
                ]);
            }
        }
    }
}

// Lo devolvemos al panel de solicitudes de forma transparente
header("Location: solicitudes.php");
exit;