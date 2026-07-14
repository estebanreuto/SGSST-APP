<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar7_schema.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar7_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$catalogo = estandar7_programas_catalogo();
$programa_slug = trim((string)($_GET['programa'] ?? ''));
if (!isset($catalogo[$programa_slug])) {
    header('Location: estandar7.php?modulo=procedimientos&categoria=programas&msg=' . urlencode('Programa no valido para generar PDF.') . '&tipo=error');
    exit;
}

$stmtUsuario = $conn->prepare("SELECT empresa_id, nombre_empresa, num_doc_empresa FROM usuarios WHERE id=? LIMIT 1");
$stmtUsuario->execute([$usuario_id]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_id = (int)($usuario['empresa_id'] ?? 0);
if ($empresa_id <= 0) {
    header('Location: estandar7.php?modulo=procedimientos&categoria=programas&msg=' . urlencode('No se encontro empresa asociada.') . '&tipo=error');
    exit;
}

$stmtEmpresa = $conn->prepare("
    SELECT nombre_empresa, num_doc_empresa
    FROM usuarios
    WHERE empresa_id=? AND COALESCE(nombre_empresa, '') <> ''
    ORDER BY FIELD(rol, 'representante', 'sst') DESC, id ASC
    LIMIT 1
");
$stmtEmpresa->execute([$empresa_id]);
$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_nombre = trim((string)($empresa['nombre_empresa'] ?? '')) ?: (trim((string)($usuario['nombre_empresa'] ?? '')) ?: 'Empresa');
$empresa_nit = trim((string)($empresa['num_doc_empresa'] ?? '')) ?: trim((string)($usuario['num_doc_empresa'] ?? ''));

$stmtDocumento = $conn->prepare("
    SELECT contenido_json, actualizado_en
    FROM estandar7_programas_documentales
    WHERE empresa_id=? AND programa_slug=?
    LIMIT 1
");
$stmtDocumento->execute([$empresa_id, $programa_slug]);
$documento = $stmtDocumento->fetch(PDO::FETCH_ASSOC) ?: [];
$contenido = estandar7_programa_contenido($documento['contenido_json'] ?? '');
$programa = $catalogo[$programa_slug];

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page{margin:28px 34px}
    body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:10px;margin:0;line-height:1.45}
    .header{border-bottom:3px solid #ff8a1f;padding-bottom:12px;margin-bottom:16px}
    .brand{color:#ff7a00;font-weight:bold;font-size:13px;margin-bottom:5px}
    h1{margin:0;color:#1e3a8a;font-size:18px;line-height:1.25}
    .meta{float:right;text-align:right;color:#64748b;font-size:8px;line-height:1.5}
    .company{color:#475569;margin-top:5px}
    .section{page-break-inside:avoid;border:1px solid #dbe3ec;border-radius:6px;margin-bottom:9px}
    .section-title{background:#edf4fb;color:#1e3a8a;font-weight:bold;padding:7px 9px;border-bottom:1px solid #dbe3ec}
    .section-body{padding:8px 9px;min-height:28px;white-space:pre-line}
    .empty{color:#94a3b8;font-style:italic}
    .footer{position:fixed;bottom:-14px;left:0;right:0;text-align:center;color:#94a3b8;font-size:7px}
</style>
</head>
<body>
    <div class="header">
        <div class="meta">
            Generado: <?php echo date('d/m/Y H:i'); ?><br>
            <?php if (!empty($documento['actualizado_en'])): ?>
                Actualizado: <?php echo date('d/m/Y H:i', strtotime($documento['actualizado_en'])); ?><br>
            <?php endif; ?>
            Estandar 4.2.3
        </div>
        <div class="brand">PREVENTWORK</div>
        <h1><?php echo htmlspecialchars($programa['nombre']); ?></h1>
        <div class="company">
            <?php echo htmlspecialchars($empresa_nombre); ?>
            <?php echo $empresa_nit !== '' ? ' - NIT ' . htmlspecialchars($empresa_nit) : ''; ?>
        </div>
    </div>

    <?php foreach ($programa['items'] as $itemSlug => $itemNombre): ?>
        <?php $texto = trim((string)($contenido[$itemSlug] ?? '')); ?>
        <div class="section">
            <div class="section-title"><?php echo htmlspecialchars($itemNombre); ?></div>
            <div class="section-body <?php echo $texto === '' ? 'empty' : ''; ?>">
                <?php echo $texto !== '' ? nl2br(htmlspecialchars($texto)) : 'Seccion pendiente de diligenciar.'; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="footer">Documento generado en PreventWork - Modulo Medidas de prevencion y control</div>
</body>
</html>
<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $programa_slug) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
