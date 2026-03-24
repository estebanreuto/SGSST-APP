<?php
session_start();
require_once '../config/db.php';

// Le decimos al navegador que vamos a responder con JSON
header('Content-Type: application/json');

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa_id = $_POST['empresa_id'] ?? 0;
    $plan_id = !empty($_POST['plan_id']) ? $_POST['plan_id'] : null;
    $trabajadores_extra = $_POST['trabajadores_extra'] ?? 0;

    if ($empresa_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE solicitudes_empresas SET plan_id = ?, trabajadores_extra = ? WHERE id = ?");
            $stmt->execute([$plan_id, $trabajadores_extra, $empresa_id]);
            
            // Respondemos éxito sin recargar la página
            echo json_encode(['success' => true, 'message' => '¡Suscripción actualizada con éxito!']);
            exit;

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
exit;