<?php
require_once "config/db.php";
header('Content-Type: application/json');

$nit = trim($_GET['nit'] ?? '');

if ($nit === '') {
    echo json_encode(['success' => false]);
    exit;
}

// Para empresas nuevas se usa empresa_nit. La cedula queda como respaldo para
// registros antiguos creados antes de separar persona y empresa.
$stmt = $conn->prepare("
    SELECT nombre, apellido, empresa_nombre
    FROM solicitudes_empresas
    WHERE (empresa_nit = ? OR cedula = ?)
      AND estado = 'aprobada'
    LIMIT 1
");
$stmt->execute([$nit, $nit]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empresa) {
    $nombreEmpresa = $empresa['empresa_nombre'] ?: trim($empresa['nombre'] . ' ' . $empresa['apellido']);

    echo json_encode([
        'success' => true,
        'nombre' => htmlspecialchars($nombreEmpresa)
    ]);
} else {
    echo json_encode(['success' => false]);
}
