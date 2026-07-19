<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/storage_schema.php';

$u = require_auth($conn);
$role = $_SESSION['usuario_rol'] ?? '';
if (!in_array($role, ['sst', 'representante'], true)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

ensure_storage_schema($conn);
$userId = (int)($_SESSION['usuario_id'] ?? 0);
$companyId = storage_user_company_id($conn, $userId);
$fileId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM almacenamiento_archivos WHERE id = ? AND empresa_id = ? LIMIT 1");
$stmt->execute([$fileId, $companyId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file['ruta_relativa']);
$root = realpath(storage_company_root($companyId));
$real = realpath($absolute);
if (!$root || !$real || !str_starts_with(strtolower($real), strtolower($root . DIRECTORY_SEPARATOR)) || !is_file($real)) {
    http_response_code(404);
    exit('El archivo fisico no esta disponible.');
}

$downloadName = str_replace(["\r", "\n", '"'], '', (string)$file['nombre_original']);
header('Content-Type: ' . ($file['tipo_mime'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($real));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
readfile($real);
exit;
