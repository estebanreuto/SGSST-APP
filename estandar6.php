<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar6_schema.php';

date_default_timezone_set('America/Bogota');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
$u = require_auth($conn);
ensure_estandar6_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmtEmpresa = $conn->prepare("SELECT empresa_id, nombre_empresa FROM usuarios WHERE id = ?");
$stmtEmpresa->execute([$usuario_id]);
$usuarioEmpresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_id = (int)($usuarioEmpresa['empresa_id'] ?? 0);
$empresa_nombre = $usuarioEmpresa['nombre_empresa'] ?? 'Empresa';

$catalogos = estandar6_catalogos();
$registros = [];
if ($empresa_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM estandar6_ipvr_registros WHERE empresa_id = ? ORDER BY numero ASC, id ASC");
    $stmt->execute([$empresa_id]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function e6h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function e6_count_by(array $rows, string $field): array
{
    $out = [];
    foreach ($rows as $row) {
        $key = trim((string)($row[$field] ?? ''));
        $key = $key === '' ? 'Sin dato' : $key;
        $out[$key] = ($out[$key] ?? 0) + 1;
    }
    arsort($out);
    return $out;
}

function e6_pct(int $value, int $total): string
{
    return $total > 0 ? number_format(($value / $total) * 100, 1, ',', '.') . '%' : '0%';
}

function e6_risk_level(int $riesgo): array
{
    if ($riesgo >= 600) {
        return ['class' => 'risk-i', 'level' => 'Nivel I', 'label' => 'Crítico'];
    }
    if ($riesgo >= 150) {
        return ['class' => 'risk-ii', 'level' => 'Nivel II', 'label' => 'Corregir'];
    }
    if ($riesgo >= 40) {
        return ['class' => 'risk-iii', 'level' => 'Nivel III', 'label' => 'Mejorable'];
    }
    return ['class' => 'risk-iv', 'level' => 'Nivel IV', 'label' => 'Aceptable'];
}

$totalRegistros = count($registros);
$actividades = count(array_unique(array_filter(array_map(fn($r) => trim((string)$r['actividad']), $registros))));
$procesos = count(array_unique(array_filter(array_map(fn($r) => trim((string)$r['proceso']), $registros))));
$riesgosAltos = 0;
$riesgosResidualesAltos = 0;
foreach ($registros as $row) {
    if ((int)$row['nivel_riesgo'] >= 150) {
        $riesgosAltos++;
    }
    if ((int)$row['nivel_riesgo_residual'] >= 150) {
        $riesgosResidualesAltos++;
    }
}
$porPeligro = e6_count_by($registros, 'peligro');
$porAceptabilidad = e6_count_by($registros, 'aceptabilidad_residual');
$maxPeligro = max($porPeligro ?: [1]);
$maxAceptabilidad = max($porAceptabilidad ?: [1]);
$msg = $_GET['msg'] ?? '';
$tipoMsg = $_GET['tipo'] ?? 'ok';
$puedeEditar = $usuario_rol === 'sst';
$registrosVista = $registros;
$totalVista = count($registrosVista);
$actividadesVista = count(array_unique(array_filter(array_map(fn($r) => trim((string)$r['actividad']), $registrosVista))));
$procesosVista = count(array_unique(array_filter(array_map(fn($r) => trim((string)$r['proceso']), $registrosVista))));
$riesgosResidualesAltosVista = count(array_filter($registrosVista, fn($r) => (int)$r['nivel_riesgo_residual'] >= 150));
$riesgosAltosVista = count(array_filter($registrosVista, fn($r) => (int)$r['nivel_riesgo'] >= 150));
$porPeligroVista = e6_count_by($registrosVista, 'peligro');
$porAceptabilidadVista = e6_count_by($registrosVista, 'aceptabilidad_residual');
$maxPeligroVista = max($porPeligroVista ?: [1]);
$maxAceptabilidadVista = max($porAceptabilidadVista ?: [1]);

$current_page = 'estandar6.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Est&aacute;ndar 6 | IPVR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#ff8a1f; --primary2:#ff7a00; --bg1:#edf4fb; --bg2:#f7f9fc; --card:#fff; --text:#1f2d3d; --muted:#64748b; --border:#dbe3ec; --blue:#1e3a8a; --green:#047857; --red:#b91c1c; --yellow:#b45309; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:'Inter',sans-serif; color:var(--text); background:linear-gradient(180deg,var(--bg1),var(--bg2)); min-height:100vh; display:flex; overflow-x:hidden; }
        .main-wrapper { margin-left:260px; width:calc(100% - 260px); min-height:100vh; transition:all .3s ease; }
        .content-area { width:100%; max-width:none; padding:24px clamp(18px, 2.4vw, 42px) 46px; }
        .page-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; margin:14px 0 22px; }
        .title-group { display:flex; gap:16px; align-items:center; min-width:0; }
        .icon-hero { width:58px; height:58px; border-radius:14px; display:grid; place-items:center; color:var(--primary2); background:#fff3e8; border:1px solid #fed7aa; font-size:1.35rem; flex:0 0 auto; }
        h1 { margin:0; color:var(--blue); font-size:clamp(1.35rem, 2.2vw, 2rem); line-height:1.08; letter-spacing:0; }
        .subtitle { margin:7px 0 0; color:var(--muted); font-size:.92rem; line-height:1.45; }
        .quick-tabs { display:flex; gap:9px; flex-wrap:wrap; justify-content:flex-end; }
        .quick-tabs a { color:var(--blue); text-decoration:none; border:1px solid #bfdbfe; background:#eff6ff; border-radius:10px; padding:10px 12px; font-size:.8rem; font-weight:800; display:inline-flex; align-items:center; gap:7px; white-space:nowrap; }
        .notice { margin:0 0 16px; padding:12px 14px; border-radius:12px; border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; font-size:.84rem; font-weight:700; display:flex; gap:9px; align-items:center; }
        .notice.error { border-color:#fecaca; background:#fef2f2; color:#991b1b; }
        .section-card { background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:0 12px 30px rgba(15,23,42,.04); padding:22px; margin-bottom:22px; overflow:hidden; }
        .section-head { display:flex; align-items:center; justify-content:space-between; gap:16px; border-bottom:1px dashed var(--border); padding-bottom:14px; margin-bottom:18px; }
        .section-title { display:flex; align-items:center; gap:12px; min-width:0; }
        .section-title i { width:38px; height:38px; display:grid; place-items:center; border-radius:10px; background:#fff3e8; color:var(--primary2); }
        h2 { margin:0; color:var(--blue); font-size:1.18rem; letter-spacing:0; }
        .section-kicker { margin:4px 0 0; color:var(--muted); font-size:.82rem; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .metric { border:1px solid var(--border); background:#f8fafc; border-radius:12px; padding:15px; min-height:94px; display:flex; flex-direction:column; justify-content:space-between; }
        .metric span { color:#64748b; font-size:.72rem; text-transform:uppercase; font-weight:800; letter-spacing:.03em; }
        .metric strong { color:var(--blue); font-size:1.55rem; letter-spacing:0; }
        .metric.danger strong { color:var(--red); }
        .metric.ok strong { color:var(--green); }
        .dash-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:16px; }
        .chart-box { border:1px solid var(--border); border-radius:14px; padding:16px; background:#fff; min-height:235px; }
        .chart-title { margin:0 0 13px; color:#334155; font-size:.86rem; font-weight:800; }
        .bar-row { display:grid; grid-template-columns:minmax(120px,170px) 1fr auto; gap:10px; align-items:center; margin:10px 0; font-size:.78rem; color:#334155; }
        .bar-track { height:10px; border-radius:99px; background:#e2e8f0; overflow:hidden; }
        .bar-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#ff8a1f,#2563eb); min-width:5px; }
        .empty-state { border:1px dashed #cbd5e1; background:#f8fafc; border-radius:14px; padding:22px; text-align:center; color:#64748b; font-weight:700; }
        .form-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .field { display:flex; flex-direction:column; gap:6px; min-width:0; }
        .field.wide { grid-column:span 2; }
        .field.full { grid-column:1 / -1; }
        label { color:#334155; font-size:.72rem; text-transform:uppercase; font-weight:800; letter-spacing:.03em; }
        input, select, textarea { width:100%; border:1px solid #cbd5e1; background:#f8fafc; color:#0f172a; border-radius:10px; padding:11px 12px; font:inherit; font-size:.84rem; outline:none; transition:border-color .2s, box-shadow .2s, background .2s; }
        textarea { min-height:76px; resize:vertical; }
        input:focus, select:focus, textarea:focus { border-color:#fb923c; box-shadow:0 0 0 3px rgba(251,146,60,.15); background:#fff; }
        .calc-panel { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:16px; }
        .calc-pill { border:1px solid #dbe3ec; border-radius:12px; background:#f8fafc; padding:12px; }
        .calc-pill span { display:block; color:#64748b; font-size:.68rem; text-transform:uppercase; font-weight:800; margin-bottom:5px; }
        .calc-pill strong { color:var(--blue); font-size:1rem; }
        .actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
        .btn-primary { border:none; border-radius:10px; padding:12px 16px; background:linear-gradient(135deg,var(--primary),var(--primary2)); color:white; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 8px 18px rgba(255,122,0,.22); }
        .btn-secondary { border:1px solid #cbd5e1; border-radius:10px; padding:12px 16px; background:#fff; color:#334155; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .matrix-toolbar { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
        .matrix-toolbar input { max-width:360px; background:#fff; }
        .table-wrap { width:100%; overflow:auto; border:1px solid var(--border); border-radius:14px; background:#fff; max-height:620px; }
        table { width:100%; min-width:1900px; border-collapse:separate; border-spacing:0; font-size:.76rem; }
        th { position:sticky; top:0; z-index:2; background:#f8fafc; color:#334155; text-transform:uppercase; font-size:.66rem; letter-spacing:.03em; border-bottom:1px solid var(--border); padding:11px 10px; text-align:left; white-space:nowrap; }
        td { padding:10px; border-bottom:1px solid #edf2f7; color:#1f2d3d; vertical-align:top; }
        tr:hover td { background:#fff7ed; }
        .badge { display:inline-flex; align-items:center; border-radius:99px; padding:5px 8px; font-weight:800; font-size:.68rem; white-space:nowrap; }
        .badge.ok { background:#dcfce7; color:#166534; }
        .badge.mid { background:#ffedd5; color:#9a3412; }
        .badge.bad { background:#fee2e2; color:#991b1b; }
        .badge.warn { background:#fef9c3; color:#854d0e; }
        .muted { color:#64748b; font-style:italic; }
        @media (max-width:1180px) { .metric-grid,.calc-panel { grid-template-columns:repeat(2,minmax(0,1fr)); } .dash-grid { grid-template-columns:1fr; } .form-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:768px) { .main-wrapper { margin-left:0; width:100%; } .content-area { padding:14px 14px 36px; } .page-hero { flex-direction:column; } .quick-tabs { justify-content:flex-start; } .section-card { padding:16px; border-radius:14px; } .section-head { align-items:flex-start; flex-direction:column; } .metric-grid,.calc-panel,.form-grid { grid-template-columns:1fr; } .field.wide { grid-column:auto; } .bar-row { grid-template-columns:1fr; gap:6px; } .actions { flex-direction:column; } .btn-primary,.btn-secondary { width:100%; justify-content:center; } }

        /* Lenguaje visual compacto compartido con los módulos anteriores */
        .content-area { padding:18px clamp(16px,2vw,30px) 42px; }
        .page-hero { align-items:center; margin:8px 0 14px; }
        .title-group { gap:11px; }
        .icon-hero { width:42px; height:42px; border-radius:10px; font-size:1rem; }
        h1 { font-size:clamp(1rem,1.6vw,1.24rem); line-height:1.18; }
        .subtitle { margin-top:4px; font-size:.67rem; line-height:1.4; }
        .quick-tabs { gap:6px; }
        .quick-tabs a { padding:7px 9px; border-radius:8px; font-size:.62rem; }
        .section-card { padding:0; margin-bottom:14px; border-radius:11px; box-shadow:0 10px 24px rgba(15,23,42,.045); }
        .section-head { margin:0; padding:13px 15px; border-bottom:1px solid var(--border); }
        .section-title { gap:9px; }
        .section-title i { width:auto; height:auto; border-radius:0; background:none; font-size:.88rem; }
        h2 { font-size:.93rem; }
        .section-kicker { margin-top:3px; font-size:.64rem; line-height:1.35; }
        .metric-grid { gap:10px; margin:13px 14px; }
        .metric { position:relative; overflow:hidden; min-height:102px; padding:13px; background:#fff; border-radius:10px; }
        .metric::before { content:""; position:absolute; inset:0 auto 0 0; width:3px; background:var(--metric-accent,#ff8a1f); }
        .metric.blue { --metric-accent:#2563eb; }
        .metric.green { --metric-accent:#10b981; }
        .metric.violet { --metric-accent:#7c3aed; }
        .metric.danger { --metric-accent:#ef4444; }
        .metric span { position:relative; z-index:1; font-size:.63rem; }
        .metric strong { position:relative; z-index:1; margin-top:7px; font-size:1.38rem; }
        .metric small { position:relative; z-index:1; margin-top:4px; color:#64748b; font-size:.61rem; line-height:1.35; max-width:85%; }
        .metric-watermark { position:absolute; right:9px; bottom:2px; color:var(--metric-accent,#ff8a1f); opacity:.08; font-size:3rem; transform:rotate(-7deg); }
        .dash-grid { gap:12px; margin:0 14px 14px; }
        .chart-box { min-height:0; padding:13px; border-radius:10px; }
        .chart-title { margin-bottom:10px; font-size:.72rem; }
        .bar-row { grid-template-columns:minmax(110px,150px) 1fr auto; margin:8px 0; font-size:.66rem; }
        .bar-track { height:7px; }
        .calc-panel { gap:9px; }
        .calc-pill { border-radius:9px; padding:9px; }
        .calc-pill span { font-size:.59rem; }
        .calc-pill strong { font-size:.82rem; }
        .e6-count-pill { display:inline-flex; align-items:center; gap:5px; flex:0 0 auto; border-radius:99px; padding:5px 8px; background:#fff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:.59rem; font-weight:800; text-transform:uppercase; }
        .e6-count-pill { color:#c2410c; border-color:#fed7aa; background:#fff7ed; }
        .e6-new-link { display:inline-flex; align-items:center; gap:6px; min-height:32px; padding:0 10px; border-radius:8px; background:linear-gradient(135deg,#ff8a1f,#ff7a00); color:#fff; text-decoration:none; font-size:.62rem; font-weight:850; box-shadow:0 7px 15px rgba(255,122,0,.18); }
        .e6-new-link:hover { box-shadow:0 9px 20px rgba(255,122,0,.25); transform:translateY(-1px); }
        .e6-create-panel { margin:13px 14px; border:1px solid #fed7aa; border-radius:10px; background:#fffaf5; overflow:hidden; }
        .e6-create-panel > summary { list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 13px; color:#9a3412; font-size:.68rem; font-weight:800; }
        .e6-create-panel > summary::-webkit-details-marker,.e6-risk-summary::-webkit-details-marker { display:none; }
        .e6-create-panel > summary i:last-child { transition:transform .2s ease; }
        .e6-create-panel[open] > summary i:last-child { transform:rotate(180deg); }
        .e6-create-body { padding:14px; border-top:1px solid #ffedd5; background:#fff; }
        .form-grid { gap:11px; }
        label { font-size:.63rem; }
        input,select,textarea { border-radius:9px; padding:9px 10px; font-size:.72rem; }
        textarea { min-height:68px; }
        .actions { margin-top:14px; }
        .btn-primary,.btn-secondary { padding:9px 12px; border-radius:8px; font-size:.68rem; }
        .matrix-toolbar { margin:0; padding:13px 14px 10px; }
        .matrix-toolbar-title { display:flex; align-items:center; gap:8px; }
        .matrix-toolbar input { max-width:390px; }
        .e6-risk-list { display:grid; gap:8px; padding:0 14px 14px; }
        .e6-risk-card { position:relative; overflow:hidden; border:1px solid #dbe3ec; border-radius:10px; background:#fff; transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease; }
        .e6-risk-card:hover { border-color:#bfdbfe; box-shadow:0 7px 18px rgba(30,58,138,.07); transform:translateY(-1px); }
        .e6-risk-card[open] { border-color:#93c5fd; box-shadow:0 8px 20px rgba(37,99,235,.08); }
        .e6-risk-summary { position:relative; z-index:1; list-style:none; cursor:pointer; display:grid; grid-template-columns:34px minmax(180px,1.25fr) minmax(150px,1fr) 92px 92px minmax(145px,.8fr) 22px; gap:11px; align-items:center; min-height:69px; padding:10px 12px; }
        .e6-risk-icon { width:34px; height:34px; display:grid; place-items:center; color:#ff7a00; background:#fff3e8; border-radius:9px; font-size:.8rem; }
        .e6-risk-main,.e6-risk-meta { min-width:0; }
        .e6-risk-eyebrow { display:block; margin-bottom:3px; color:#94a3b8; font-size:.55rem; font-weight:800; text-transform:uppercase; }
        .e6-risk-main strong,.e6-risk-meta strong { display:block; overflow:hidden; text-overflow:ellipsis; color:#102a67; font-size:.7rem; white-space:nowrap; }
        .e6-risk-main small,.e6-risk-meta small { display:block; overflow:hidden; margin-top:3px; color:#64748b; font-size:.6rem; text-overflow:ellipsis; white-space:nowrap; }
        .e6-risk-score span { display:block; color:#64748b; font-size:.56rem; text-transform:uppercase; font-weight:800; }
        .e6-risk-score strong { display:block; margin-top:3px; color:#102a67; font-size:.88rem; }
        .e6-risk-score.residual strong { color:#047857; }
        .e6-risk-score { --risk-color:#64748b; --risk-soft:#f8fafc; --risk-border:#e2e8f0; padding:7px 8px; border:1px solid var(--risk-border); border-radius:8px; background:var(--risk-soft); }
        .e6-risk-score strong,.e6-risk-score.residual strong { color:var(--risk-color); }
        .risk-i { --risk-color:#b91c1c!important; --risk-soft:#fee2e2!important; --risk-border:#fca5a5!important; }
        .risk-ii { --risk-color:#c2410c!important; --risk-soft:#ffedd5!important; --risk-border:#fdba74!important; }
        .risk-iii { --risk-color:#854d0e!important; --risk-soft:#fef9c3!important; --risk-border:#fde047!important; }
        .risk-iv { --risk-color:#166534!important; --risk-soft:#dcfce7!important; --risk-border:#86efac!important; }
        .badge.risk-i,.badge.risk-ii,.badge.risk-iii,.badge.risk-iv { color:var(--risk-color); background:var(--risk-soft); border:1px solid var(--risk-border); }
        .e6-risk-card.risk-i,.e6-risk-card.risk-ii,.e6-risk-card.risk-iii,.e6-risk-card.risk-iv { border-left:4px solid var(--risk-color); }
        .e6-risk-card.risk-i .e6-risk-icon,.e6-risk-card.risk-ii .e6-risk-icon,.e6-risk-card.risk-iii .e6-risk-icon,.e6-risk-card.risk-iv .e6-risk-icon { color:var(--risk-color); background:var(--risk-soft); }
        .e6-risk-card.risk-i[open],.e6-risk-card.risk-ii[open],.e6-risk-card.risk-iii[open],.e6-risk-card.risk-iv[open] { border-color:var(--risk-border); border-left-color:var(--risk-color); }
        .e6-risk-toggle { color:#94a3b8; font-size:.65rem; transition:transform .2s ease; }
        .e6-risk-card[open] .e6-risk-toggle { transform:rotate(180deg); }
        .e6-risk-watermark { position:absolute; right:48px; bottom:-18px; color:#2563eb; opacity:.035; font-size:4.6rem; pointer-events:none; }
        .e6-risk-detail { position:relative; z-index:1; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; padding:0 12px 12px 57px; }
        .e6-detail-box { min-height:68px; padding:9px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
        .e6-detail-box.wide { grid-column:span 2; }
        .e6-detail-box.full { grid-column:1/-1; }
        .e6-detail-box span { display:block; margin-bottom:5px; color:#94a3b8; font-size:.54rem; font-weight:800; text-transform:uppercase; }
        .e6-detail-box strong,.e6-detail-box p { margin:0; color:#334155; font-size:.62rem; line-height:1.45; }
        .e6-detail-box strong { color:#102a67; }
        .e6-detail-box.risk-i,.e6-detail-box.risk-ii,.e6-detail-box.risk-iii,.e6-detail-box.risk-iv { border-color:var(--risk-border); border-left:3px solid var(--risk-color); background:linear-gradient(135deg,var(--risk-soft),#fff 68%); }
        .e6-detail-box.risk-i strong,.e6-detail-box.risk-ii strong,.e6-detail-box.risk-iii strong,.e6-detail-box.risk-iv strong { color:var(--risk-color); }
        .e6-context-summary { border-color:#b8cde3; border-left:3px solid #24496f; background:linear-gradient(135deg,#eff6ff,#fff); }
        .e6-context-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .e6-context-item { min-width:0; padding:7px 8px; border-left:1px solid #dbeafe; }
        .e6-context-item:first-child { border-left:0; }
        .e6-context-item small { display:block; color:#64748b; font-size:.5rem; font-weight:800; text-transform:uppercase; }
        .e6-context-item strong { display:block; margin-top:3px; color:#173b65; font-size:.59rem; line-height:1.35; }
        .e6-empty-search { display:none; margin:0 14px 14px; padding:18px; border:1px dashed #cbd5e1; border-radius:9px; color:#64748b; text-align:center; font-size:.68rem; }
        .e6-risk-legend { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
        .e6-risk-legend span { --risk-color:#64748b; --risk-soft:#f8fafc; --risk-border:#e2e8f0; display:inline-flex; align-items:center; gap:5px; min-height:24px; padding:0 7px; border:1px solid var(--risk-border); border-radius:99px; background:var(--risk-soft); color:var(--risk-color); font-size:.51rem; font-weight:850; }
        .e6-risk-legend i { font-size:.44rem; }
        @media (max-width:1100px) { .e6-risk-summary { grid-template-columns:34px minmax(180px,1.3fr) minmax(140px,1fr) 78px 78px 22px; } .e6-risk-summary .badge { grid-column:2 / 6; width:max-content; } .e6-risk-detail { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:768px) { .content-area { padding:14px 12px 34px; } .page-hero { align-items:flex-start; } .section-card { padding:0; border-radius:11px; } .section-head { flex-direction:row; align-items:center; } .metric-grid { grid-template-columns:repeat(2,minmax(0,1fr)); margin:11px; } .metric { min-height:96px; } .dash-grid { margin:0 11px 11px; } .e6-demo-note,.e6-create-panel { margin-left:11px; margin-right:11px; } .matrix-toolbar { align-items:stretch; } .matrix-toolbar input { max-width:none; } .e6-risk-list { padding:0 11px 11px; } .e6-risk-summary { grid-template-columns:34px 1fr auto; gap:9px; } .e6-risk-summary > :not(.e6-risk-icon):not(.e6-risk-main):not(.e6-risk-toggle) { grid-column:2 / 3; } .e6-risk-summary .badge { grid-column:2 / 3; } .e6-risk-toggle { grid-column:3; grid-row:1; } .e6-risk-detail { grid-template-columns:1fr; padding:0 10px 10px; } .e6-detail-box.wide,.e6-detail-box.full { grid-column:auto; } .e6-context-grid { grid-template-columns:1fr 1fr; } .e6-context-item:nth-child(odd) { border-left:0; } }
        @media (max-width:480px) { .metric-grid { grid-template-columns:1fr; } .e6-demo-note { align-items:flex-start; flex-direction:column; } }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<div class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <main class="content-area">
        <div class="page-hero">
            <div class="title-group">
                <div class="icon-hero"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <h1>6. Identificaci&oacute;n de peligros y valoraci&oacute;n de riesgos</h1>
                    <p class="subtitle">Matriz IPVR por actividad con resumen ejecutivo, distribuci&oacute;n de riesgos y seguimiento detallado de controles.</p>
                </div>
            </div>
            <div class="quick-tabs">
                <a href="#presentacion"><i class="fa-solid fa-chart-pie"></i> Presentaci&oacute;n</a>
                <a href="#matriz"><i class="fa-solid fa-table-list"></i> Peligros por actividad</a>
            </div>
        </div>

        <?php if ($msg !== ''): ?>
            <div class="notice <?php echo $tipoMsg === 'error' ? 'error' : ''; ?>">
                <i class="fa-solid <?php echo $tipoMsg === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                <?php echo e6h($msg); ?>
            </div>
        <?php endif; ?>

        <section class="section-card" id="presentacion">
            <div class="section-head">
                <div class="section-title">
                    <i class="fa-solid fa-chart-simple"></i>
                    <div>
                        <h2>Presentaci&oacute;n y gr&aacute;ficos</h2>
                        <p class="section-kicker">Resumen inicial para revisar el estado de la matriz IPVR de <?php echo e6h($empresa_nombre); ?>.</p>
                    </div>
                </div>
                <span class="e6-count-pill"><i class="fa-solid fa-shield-halved"></i> <?php echo $totalVista; ?> peligros</span>
            </div>

            <div class="metric-grid">
                <div class="metric"><span>Registros en matriz</span><strong><?php echo $totalVista; ?></strong><small>Peligros identificados y valorados.</small><i class="fa-solid fa-list-check metric-watermark"></i></div>
                <div class="metric blue"><span>Actividades analizadas</span><strong><?php echo $actividadesVista; ?></strong><small>Actividades con evaluaci&oacute;n IPVR.</small><i class="fa-solid fa-person-digging metric-watermark"></i></div>
                <div class="metric violet"><span>Procesos cubiertos</span><strong><?php echo $procesosVista; ?></strong><small>Procesos incluidos en la matriz.</small><i class="fa-solid fa-diagram-project metric-watermark"></i></div>
                <div class="metric <?php echo $riesgosResidualesAltosVista > 0 ? 'danger' : 'green'; ?>"><span>Riesgos residuales altos</span><strong><?php echo $riesgosResidualesAltosVista; ?></strong><small>Requieren intervenci&oacute;n prioritaria.</small><i class="fa-solid fa-triangle-exclamation metric-watermark"></i></div>
            </div>

            <div class="dash-grid">
                    <div class="chart-box">
                        <p class="chart-title">Distribuci&oacute;n por tipo de peligro</p>
                        <?php foreach (array_slice($porPeligroVista, 0, 8, true) as $label => $count): ?>
                            <div class="bar-row">
                                <strong><?php echo e6h($label); ?></strong>
                                <div class="bar-track"><div class="bar-fill" style="width: <?php echo max(7, round(($count / $maxPeligroVista) * 100)); ?>%"></div></div>
                                <span><?php echo $count; ?> &middot; <?php echo e6_pct($count, $totalVista); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-box">
                        <p class="chart-title">Aceptabilidad residual de riesgos</p>
                        <?php foreach ($porAceptabilidadVista as $label => $count): ?>
                            <div class="bar-row">
                                <strong><?php echo e6h($label); ?></strong>
                                <div class="bar-track"><div class="bar-fill" style="width: <?php echo max(7, round(($count / $maxAceptabilidadVista) * 100)); ?>%"></div></div>
                                <span><?php echo $count; ?> &middot; <?php echo e6_pct($count, $totalVista); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="calc-panel" style="grid-template-columns:repeat(2,minmax(0,1fr)); margin-top:14px;">
                            <div class="calc-pill"><span>Altos antes del control</span><strong><?php echo $riesgosAltosVista; ?></strong></div>
                            <div class="calc-pill"><span>Altos despu&eacute;s del control</span><strong><?php echo $riesgosResidualesAltosVista; ?></strong></div>
                        </div>
                    </div>
            </div>
        </section>

        <section class="section-card" id="matriz">
            <div class="section-head">
                <div class="section-title">
                    <i class="fa-solid fa-list-check"></i>
                    <div>
                        <h2>Peligros por actividad</h2>
                        <p class="section-kicker">Consulta compacta por proceso, actividad, tarea, peligro, valoraci&oacute;n y controles.</p>
                    </div>
                </div>
                <?php if ($puedeEditar): ?>
                    <a class="e6-new-link" href="nuevo_peligro_ipvr"><i class="fa-solid fa-plus"></i> Registrar peligro</a>
                <?php endif; ?>
            </div>

            <?php if (!$puedeEditar): ?>
                <div class="empty-state">Vista de consulta para representante legal. El responsable SST registra y actualiza la matriz IPVR.</div>
            <?php endif; ?>

            <div class="matrix-toolbar">
                <div class="matrix-toolbar-title">
                    <h2>Seguimiento de peligros identificados</h2>
                    <span class="e6-count-pill"><?php echo $totalVista; ?> registros</span>
                </div>
                <div class="e6-risk-legend" aria-label="Colores por nivel de riesgo">
                    <span class="risk-i"><i class="fa-solid fa-circle"></i> I Cr&iacute;tico</span>
                    <span class="risk-ii"><i class="fa-solid fa-circle"></i> II Corregir</span>
                    <span class="risk-iii"><i class="fa-solid fa-circle"></i> III Mejorable</span>
                    <span class="risk-iv"><i class="fa-solid fa-circle"></i> IV Aceptable</span>
                </div>
                <input type="search" id="matrixSearch" placeholder="Buscar proceso, actividad, cargo o peligro..." autocomplete="off">
            </div>

            <div class="e6-risk-list" id="ipvrList">
                <?php foreach ($registrosVista as $index => $row): ?>
                    <?php
                        $acept = trim((string)($row['aceptabilidad'] ?? 'Sin dato'));
                        $aceptR = trim((string)($row['aceptabilidad_residual'] ?? 'Sin dato'));
                        $riesgoInicial = (int)($row['nivel_riesgo'] ?? 0);
                        $riesgoResidual = (int)($row['nivel_riesgo_residual'] ?? 0);
                        $nivelInicial = e6_risk_level($riesgoInicial);
                        $nivelResidual = e6_risk_level($riesgoResidual);
                        $categoriaVista = trim((string)($row['categoria'] ?? 'Seguridad')) ?: 'Seguridad';
                        $nivelDanioVista = trim((string)($row['nivel_danio'] ?? ''));
                        if ($nivelDanioVista === '') {
                            $nivelDanioVista = $riesgoInicial >= 600 ? 'Mortal' : ($riesgoInicial >= 150 ? 'Grave' : ($riesgoInicial >= 40 ? 'Moderado' : 'Leve'));
                        }
                        $controles = array_filter([
                            trim((string)($row['control_fuente'] ?? '')),
                            trim((string)($row['control_medio'] ?? '')),
                            trim((string)($row['control_persona'] ?? '')),
                        ]);
                        $plan = array_filter([
                            trim((string)($row['eliminacion'] ?? '')),
                            trim((string)($row['sustitucion'] ?? '')),
                            trim((string)($row['controles_ingenieria'] ?? '')),
                            trim((string)($row['administrativos'] ?? '')),
                            trim((string)($row['epp'] ?? '')),
                        ]);
                        $searchText = implode(' ', [
                            $row['proceso'] ?? '', $row['actividad'] ?? '', $row['tarea'] ?? '', $row['cargos'] ?? '',
                            $row['peligro'] ?? '', $row['clasificacion_peligro'] ?? '', $row['sitio_trabajo'] ?? '',
                        ]);
                    ?>
                    <details class="e6-risk-card <?php echo $nivelResidual['class']; ?>" data-ipvr-card data-search="<?php echo e6h($searchText); ?>"<?php echo $index === 0 ? ' open' : ''; ?>>
                        <summary class="e6-risk-summary">
                            <span class="e6-risk-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                            <div class="e6-risk-main">
                                <span class="e6-risk-eyebrow">#<?php echo (int)($row['numero'] ?? ($index + 1)); ?> &middot; <?php echo e6h($row['proceso'] ?? 'Sin proceso'); ?></span>
                                <strong><?php echo e6h($row['actividad'] ?? 'Sin actividad'); ?></strong>
                                <small><?php echo e6h($row['tarea'] ?? 'Sin tarea especificada'); ?></small>
                            </div>
                            <div class="e6-risk-meta">
                                <span class="e6-risk-eyebrow">Peligro</span>
                                <strong><?php echo e6h($row['peligro'] ?? 'Sin dato'); ?></strong>
                                <small><?php echo e6h($row['clasificacion_peligro'] ?? 'Sin clasificación'); ?></small>
                            </div>
                            <div class="e6-risk-score <?php echo $nivelInicial['class']; ?>">
                                <span><?php echo $nivelInicial['level']; ?> inicial</span><strong><?php echo $riesgoInicial; ?></strong>
                            </div>
                            <div class="e6-risk-score residual <?php echo $nivelResidual['class']; ?>">
                                <span><?php echo $nivelResidual['level']; ?> residual</span><strong><?php echo $riesgoResidual; ?></strong>
                            </div>
                            <span class="badge <?php echo $nivelResidual['class']; ?>"><?php echo $nivelResidual['level'] . ' · ' . $nivelResidual['label']; ?></span>
                            <i class="fa-solid fa-chevron-down e6-risk-toggle"></i>
                        </summary>
                        <i class="fa-solid fa-shield-halved e6-risk-watermark"></i>
                        <div class="e6-risk-detail">
                            <div class="e6-detail-box full e6-context-summary">
                                <span>Contexto inicial de la organizaci&oacute;n y peligro identificado</span>
                                <div class="e6-context-grid">
                                    <div class="e6-context-item"><small>Sitio y zona</small><strong><?php echo e6h($row['sitio_trabajo'] ?? 'Sin sede'); ?> &middot; <?php echo e6h($row['zona_lugar'] ?? 'Sin zona'); ?></strong></div>
                                    <div class="e6-context-item"><small>Actividad desarrollada</small><strong><?php echo e6h($row['proceso'] ?? 'Sin proceso'); ?> &middot; <?php echo e6h($row['actividad'] ?? 'Sin actividad'); ?></strong></div>
                                    <div class="e6-context-item"><small>Expuestos</small><strong><?php echo (int)($row['total_expuestos'] ?? 0); ?> persona(s) &middot; <?php echo e6h($row['cargos'] ?? 'Sin cargos'); ?></strong></div>
                                    <div class="e6-context-item"><small>Peligro y efecto posible</small><strong><?php echo e6h($row['peligro'] ?? 'Sin peligro'); ?> &middot; <?php echo e6h($row['clasificacion_peligro'] ?? 'Sin clasificación'); ?> &middot; <?php echo e6h($categoriaVista); ?> / <?php echo e6h($nivelDanioVista); ?></strong></div>
                                </div>
                            </div>
                            <div class="e6-detail-box <?php echo $nivelInicial['class']; ?>"><span>Valoraci&oacute;n inicial</span><strong><?php echo $nivelInicial['level']; ?> &middot; <?php echo e6h($acept); ?></strong><p>Nivel de riesgo: <?php echo $riesgoInicial; ?>.</p></div>
                            <div class="e6-detail-box <?php echo $nivelResidual['class']; ?>"><span>Resultado del control</span><strong><?php echo $nivelResidual['level']; ?> &middot; <?php echo e6h($aceptR); ?></strong><p>Reducci&oacute;n estimada: <?php echo number_format((float)($row['factor_reduccion'] ?? 0), 1, ',', '.'); ?>%.</p></div>
                            <div class="e6-detail-box wide"><span>Descripci&oacute;n del peligro</span><p><?php echo e6h($row['descripcion_peligro'] ?? 'Sin descripci&oacute;n registrada.'); ?></p></div>
                            <div class="e6-detail-box wide"><span>Controles existentes</span><p><?php echo e6h($controles ? implode(' ', $controles) : 'Sin controles existentes registrados.'); ?></p></div>
                            <div class="e6-detail-box wide"><span>Plan de intervenci&oacute;n</span><p><?php echo e6h($plan ? implode(' ', $plan) : 'Sin acciones adicionales registradas.'); ?></p></div>
                            <div class="e6-detail-box wide"><span>Lectura de seguimiento</span><p><?php echo $riesgoResidual >= 150 ? 'Mantener la actividad como prioridad de intervenci&oacute;n y verificar la eficacia de los controles.' : 'Continuar el seguimiento y conservar la evidencia de los controles implementados.'; ?></p></div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
            <div class="e6-empty-search" id="matrixEmptySearch">No encontramos peligros con ese criterio de b&uacute;squeda.</div>
        </section>
    </main>
</div>
<script>
document.getElementById('matrixSearch')?.addEventListener('input', function() {
    const q = this.value.trim().toLocaleLowerCase('es');
    let visibles = 0;
    document.querySelectorAll('[data-ipvr-card]').forEach(card => {
        const haystack = (card.dataset.search || card.textContent).toLocaleLowerCase('es');
        const coincide = haystack.includes(q);
        card.hidden = !coincide;
        if (coincide) visibles++;
    });
    const empty = document.getElementById('matrixEmptySearch');
    if (empty) empty.style.display = visibles === 0 ? 'block' : 'none';
});
</script>
</body>
</html>
