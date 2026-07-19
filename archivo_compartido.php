<?php
require_once 'config/db.php';
require_once 'config/storage_schema.php';

ensure_storage_schema($conn);
header('X-Robots-Tag: noindex, nofollow', true);
header('Referrer-Policy: no-referrer');
header('Cache-Control: private, no-store, max-age=0');

function shared_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shared_file_icon(string $extension): string
{
    return match (strtolower($extension)) {
        'pdf' => 'fa-file-pdf',
        'doc', 'docx' => 'fa-file-word',
        'xls', 'xlsx', 'csv' => 'fa-file-excel',
        'ppt', 'pptx' => 'fa-file-powerpoint',
        'jpg', 'jpeg', 'png', 'webp' => 'fa-file-image',
        'zip' => 'fa-file-zipper',
        default => 'fa-file-lines',
    };
}

function shared_serve_file(array $file, int $companyId): never
{
    $root = realpath(storage_company_root($companyId));
    $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file['ruta_relativa']);
    $realFile = is_file($absolute) ? realpath($absolute) : false;
    if (!$root || !$realFile || !storage_path_is_within($realFile, $root)) {
        http_response_code(404);
        exit('El archivo ya no esta disponible.');
    }

    $downloadName = str_replace(["\r", "\n", '"'], '', (string)$file['nombre_original']);
    header('Content-Type: ' . ((string)$file['tipo_mime'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($realFile));
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($downloadName));
    readfile($realFile);
    exit;
}

$plainToken = strtolower(trim((string)($_GET['token'] ?? '')));
if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
    http_response_code(404);
    exit('El enlace compartido no es valido.');
}

$stmt = $conn->prepare("
    SELECT * FROM almacenamiento_compartidos
    WHERE token_hash = ? AND activo = 1 AND vence_en > NOW()
    LIMIT 1
");
$stmt->execute([hash('sha256', $plainToken)]);
$share = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$share) {
    http_response_code(410);
    exit('Este enlace vencio o fue desactivado.');
}

$companyId = (int)$share['empresa_id'];
$storage = storage_company_context($conn, $companyId);
$type = (string)$share['tipo_objeto'];
$objectId = (int)$share['objeto_id'];
$sharedFolder = null;
$files = [];

if ($type === 'archivo') {
    $stmt = $conn->prepare('SELECT * FROM almacenamiento_archivos WHERE id = ? AND empresa_id = ? LIMIT 1');
    $stmt->execute([$objectId, $companyId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) {
        http_response_code(404);
        exit('El archivo compartido ya no existe.');
    }
    $files = [$file];
    if (($_GET['accion'] ?? '') === 'descargar') {
        shared_serve_file($file, $companyId);
    }
} elseif ($type === 'carpeta') {
    $stmt = $conn->prepare('SELECT * FROM almacenamiento_carpetas WHERE id = ? AND empresa_id = ? LIMIT 1');
    $stmt->execute([$objectId, $companyId]);
    $sharedFolder = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sharedFolder) {
        http_response_code(404);
        exit('La carpeta compartida ya no existe.');
    }
    $folderIds = storage_folder_descendant_ids($conn, $companyId, $objectId);
    $placeholders = implode(',', array_fill(0, count($folderIds), '?'));
    $stmt = $conn->prepare("
        SELECT a.*, c.nombre AS carpeta_nombre
        FROM almacenamiento_archivos a
        LEFT JOIN almacenamiento_carpetas c ON c.id = a.carpeta_id
        WHERE a.empresa_id = ? AND a.carpeta_id IN ($placeholders)
        ORDER BY c.nombre ASC, a.nombre_original ASC
    ");
    $stmt->execute([$companyId, ...$folderIds]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (($_GET['accion'] ?? '') === 'descargar') {
        $fileId = (int)($_GET['archivo'] ?? 0);
        foreach ($files as $allowedFile) {
            if ((int)$allowedFile['id'] === $fileId) {
                shared_serve_file($allowedFile, $companyId);
            }
        }
        http_response_code(404);
        exit('El archivo no pertenece a esta carpeta compartida.');
    }
} else {
    http_response_code(404);
    exit('El elemento compartido no es valido.');
}

$expires = date('d/m/Y · h:i a', strtotime((string)$share['vence_en']));
$title = $type === 'carpeta' ? (string)$sharedFolder['nombre'] : (string)$files[0]['nombre_original'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo shared_h($title); ?> | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--blue:#173b8f;--orange:#ff7500;--muted:#64748b;--border:#dbe4ef}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:linear-gradient(180deg,#edf4fb,#f8fafc);font-family:Inter,sans-serif;color:#17233c;font-size:.82rem}.share-header{height:68px;display:flex;align-items:center;justify-content:space-between;padding:0 clamp(18px,4vw,64px);border-bottom:1px solid var(--border);background:#fff}.brand{font-size:1.05rem;font-weight:800;color:var(--blue)}.brand span{color:var(--orange)}.secure{display:flex;align-items:center;gap:7px;color:#047857;font-size:.66rem;font-weight:800}.page{width:min(1060px,calc(100% - 28px));margin:36px auto}.hero{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.hero-copy{display:flex;align-items:center;gap:13px}.hero-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:11px;background:#eaf2ff;color:#2563eb;font-size:1.25rem}.hero h1{margin:0 0 4px;color:var(--blue);font-size:1.18rem}.hero p{margin:0;color:var(--muted);font-size:.7rem}.expiry{padding:7px 10px;border-radius:999px;background:#fff7ed;color:#c2410c;font-size:.62rem;font-weight:800}.panel{overflow:hidden;border:1px solid var(--border);border-radius:13px;background:#fff;box-shadow:0 14px 35px rgba(15,23,42,.06)}.panel-head{padding:13px 16px;border-bottom:1px solid var(--border);color:#475569;font-size:.67rem;font-weight:700}.file-row{display:grid;grid-template-columns:36px minmax(0,1fr) auto auto;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #e8edf3}.file-row:last-child{border-bottom:0}.file-row>i{color:#2563eb;font-size:1.1rem;text-align:center}.file-info{min-width:0}.file-info strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#203967;font-size:.72rem}.file-info span{display:block;margin-top:3px;color:#7a8aa1;font-size:.6rem}.file-size{color:#64748b;font-size:.64rem}.download{display:inline-flex;align-items:center;gap:7px;padding:8px 10px;border:1px solid #d7e1ed;border-radius:8px;color:#1d4ed8;text-decoration:none;font-size:.65rem;font-weight:800}.download:hover{border-color:#93b4eb;background:#eff6ff}.empty{padding:48px 20px;text-align:center;color:#64748b}.empty i{display:block;margin-bottom:10px;color:#b6c5d8;font-size:2rem}.foot{margin-top:14px;color:#8190a6;text-align:center;font-size:.6rem}@media(max-width:620px){.share-header{padding:0 16px}.secure span{display:none}.page{margin-top:22px}.hero{align-items:flex-start;flex-direction:column}.file-row{grid-template-columns:30px minmax(0,1fr) auto}.file-size{display:none}.download{width:34px;height:34px;padding:0;justify-content:center}.download span{display:none}}
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/components/public_page_loader.php'; ?>
    <header class="share-header"><div class="brand">PREVENT<span>WORK</span></div><div class="secure"><i class="fa-solid fa-shield-halved"></i><span>Enlace seguro de solo lectura</span></div></header>
    <main class="page">
        <section class="hero">
            <div class="hero-copy"><span class="hero-icon"><i class="fa-solid <?php echo $type === 'carpeta' ? 'fa-folder-open' : shared_file_icon((string)$files[0]['extension']); ?>"></i></span><div><h1><?php echo shared_h($title); ?></h1><p>Compartido por <?php echo shared_h($storage['empresa_nombre'] ?? 'una empresa PreventWork'); ?></p></div></div>
            <span class="expiry"><i class="fa-regular fa-clock"></i> Disponible hasta <?php echo shared_h($expires); ?></span>
        </section>
        <section class="panel">
            <div class="panel-head"><?php echo count($files); ?> archivo<?php echo count($files) === 1 ? '' : 's'; ?> disponible<?php echo count($files) === 1 ? '' : 's'; ?></div>
            <?php if (!$files): ?><div class="empty"><i class="fa-regular fa-folder-open"></i>Esta carpeta no contiene archivos.</div><?php endif; ?>
            <?php foreach ($files as $file): ?>
                <article class="file-row"><i class="fa-solid <?php echo shared_file_icon((string)$file['extension']); ?>"></i><div class="file-info"><strong><?php echo shared_h($file['nombre_original']); ?></strong><span><?php echo shared_h($file['carpeta_nombre'] ?? strtoupper((string)$file['extension'])); ?> · <?php echo date('d/m/Y', strtotime((string)$file['creado_en'])); ?></span></div><span class="file-size"><?php echo storage_format_bytes((int)$file['tamano_bytes']); ?></span><a class="download" href="archivo_compartido?token=<?php echo rawurlencode($plainToken); ?>&accion=descargar<?php echo $type === 'carpeta' ? '&archivo=' . (int)$file['id'] : ''; ?>"><i class="fa-solid fa-download"></i><span>Descargar</span></a></article>
            <?php endforeach; ?>
        </section>
        <p class="foot">Este enlace es personal. No lo compartas con personas no autorizadas.</p>
    </main>
</body>
</html>
