<?php
require_once "config/db.php";
header('Content-Type: application/json');

$nit = $_GET['nit'] ?? '';

if (empty($nit)) {
    echo json_encode(['success' => false]);
    exit;
}

// Buscamos si existe la empresa y está aprobada
$stmt = $conn->prepare("SELECT nombre, apellido FROM solicitudes_empresas WHERE cedula = ? AND estado = 'aprobada'");
$stmt->execute([$nit]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empresa) {
    // Si existe, mandamos éxito y el nombre de la empresa
    echo json_encode([
        'success' => true,
        'nombre' => htmlspecialchars($empresa['nombre'] . ' ' . $empresa['apellido'])
    ]);
} else {
    // Si no existe, mandamos false
    echo json_encode(['success' => false]);
}
?>