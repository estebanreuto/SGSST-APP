<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);
$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'];

$accion = $_REQUEST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. SST envía a firma
    if ($accion === 'enviar_firma' && $usuario_rol === 'sst') {
        $firma_sst = $_POST['firma_sst'] ?? '';
        if (!empty($firma_sst)) {
            $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado, firma_sst) VALUES (?, 'pendiente_firma', ?) 
                    ON DUPLICATE KEY UPDATE estado = 'pendiente_firma', firma_sst = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_sst, $firma_sst]);
            header("Location: dashboard.php?doc=enviado");
            exit;
        }
    } 
    // 2. Representante firma el documento
    elseif ($accion === 'firmar_doc' && $usuario_rol === 'representante') {
        $firma_base64 = $_POST['firma_rep'] ?? '';
        $doc_id = $_POST['doc_id'] ?? 0;
        if (!empty($firma_base64) && $doc_id > 0) {
            $sql = "UPDATE doc_asignacion_sst 
                    SET estado = 'firmado', representante_id = ?, firma_representante = ?, fecha_firma = NOW() 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_base64, $doc_id]);
            header("Location: dashboard.php?doc=firmado");
            exit;
        }
    }
    // 3. Guardar el PDF generado (AJAX)
    elseif ($accion === 'guardar_pdf') {
        $pdf_base64 = $_POST['pdf_base64'] ?? '';
        $doc_id = $_POST['doc_id'] ?? 0;
        if (!empty($pdf_base64) && $doc_id > 0) {
            $sql = "UPDATE doc_asignacion_sst SET archivo_pdf = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pdf_base64, $doc_id]);
            echo "OK";
            exit;
        }
    }
} 
// 4. Crear una NUEVA VERSIÓN del Acta (Por GET desde el modal de confirmación)
elseif ($accion === 'nueva_version' && $usuario_rol === 'sst') {
    // Simplemente insertamos un nuevo borrador. Como el dashboard busca el último (ORDER BY id DESC), este será el nuevo actual.
    $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado) VALUES (?, 'borrador')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario_id]);
    header("Location: dashboard.php?doc=nueva_version");
    exit;
}

header("Location: dashboard.php");
exit;