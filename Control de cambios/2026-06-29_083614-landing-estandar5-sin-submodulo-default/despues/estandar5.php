<?php
require_once 'config/db.php';
require_once 'config/auth.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$submodulos = [
    'sociodemografica' => [
        'codigo' => '3.1.1',
        'titulo' => 'Descripción sociodemográfica - Diagnóstico de condiciones de salud',
        'descripcion' => 'Base para consolidar información poblacional y hallazgos generales de salud.',
        'icono' => 'fa-users-viewfinder',
    ],
    'promocion-prevencion' => [
        'codigo' => '3.1.2',
        'titulo' => 'Actividades de Promoción y Prevención en Salud',
        'descripcion' => 'Planificación y seguimiento de acciones preventivas derivadas del diagnóstico.',
        'icono' => 'fa-heart-pulse',
    ],
    'perfiles-cargo' => [
        'codigo' => '3.1.3',
        'titulo' => 'Información al médico de los perfiles de cargo',
        'descripcion' => 'Insumos del cargo, peligros y funciones para orientar los exámenes médicos.',
        'icono' => 'fa-user-doctor',
    ],
    'evaluaciones-medicas' => [
        'codigo' => '3.1.4',
        'titulo' => 'Realización de Evaluaciones Médicas Ocupacionales - Peligros - Periodicidad',
        'descripcion' => 'Control de ingreso, periódicos, egreso y periodicidad según exposición.',
        'icono' => 'fa-notes-medical',
    ],
    'historias-clinicas' => [
        'codigo' => '3.1.5',
        'titulo' => 'Custodia de Historias Clínicas',
        'descripcion' => 'Gestión de soporte y evidencia de custodia por entidad competente.',
        'icono' => 'fa-folder-lock',
    ],
    'restricciones' => [
        'codigo' => '3.1.6',
        'titulo' => 'Restricciones y recomendaciones médico/laborales',
        'descripcion' => 'Seguimiento ejecutivo de restricciones, recomendaciones y cierre de acciones.',
        'icono' => 'fa-clipboard-list',
    ],
];

$modulo_actual = $_GET['modulo'] ?? '';
if ($modulo_actual !== '' && !isset($submodulos[$modulo_actual])) {
    $modulo_actual = '';
}
$modulo = $modulo_actual !== '' ? $submodulos[$modulo_actual] : null;

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt_empresa = $conn->prepare("SELECT empresa_id, nombre_empresa FROM usuarios WHERE id = ?");
$stmt_empresa->execute([$usuario_id]);
$usuario_empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_id = (int)($usuario_empresa['empresa_id'] ?? 0);
$empresa_nombre = $usuario_empresa['nombre_empresa'] ?? 'Empresa';

function estandar5_socio_preguntas(): array
{
    return [
        'edad' => ['label' => 'Edad', 'icon' => 'fa-hourglass-half'],
        'estado_civil' => ['label' => 'Estado civil', 'icon' => 'fa-heart'],
        'genero' => ['label' => 'Género', 'icon' => 'fa-person-half-dress'],
        'personas_cargo' => ['label' => 'Personas a cargo', 'icon' => 'fa-people-roof'],
        'escolaridad' => ['label' => 'Escolaridad', 'icon' => 'fa-graduation-cap'],
        'vivienda' => ['label' => 'Vivienda', 'icon' => 'fa-house'],
        'tiempo_libre' => ['label' => 'Tiempo libre', 'icon' => 'fa-clock'],
        'experiencia' => ['label' => 'Experiencia', 'icon' => 'fa-briefcase'],
        'estrato' => ['label' => 'Estrato', 'icon' => 'fa-layer-group'],
        'convive_con' => ['label' => 'Convive con', 'icon' => 'fa-users'],
        'raza' => ['label' => 'Raza', 'icon' => 'fa-earth-americas'],
        'tipo_contrato' => ['label' => 'Tipo de contrato', 'icon' => 'fa-file-signature'],
        'turno' => ['label' => 'Turno', 'icon' => 'fa-calendar-days'],
        'antiguedad' => ['label' => 'Antigüedad', 'icon' => 'fa-chart-line'],
        'enfermedad' => ['label' => 'Enfermedad diagnosticada', 'icon' => 'fa-notes-medical'],
        'fuma' => ['label' => 'Fuma', 'icon' => 'fa-smoking'],
        'alcohol' => ['label' => 'Consumo de alcohol', 'icon' => 'fa-wine-glass'],
        'deporte' => ['label' => 'Actividad física/deporte', 'icon' => 'fa-person-running'],
        'tipo_personal' => ['label' => 'Tipo de personal', 'icon' => 'fa-id-badge'],
    ];
}

function estandar5_socio_respuesta($valor): string
{
    $texto = trim((string)$valor);
    return $texto === '' ? 'Sin respuesta' : $texto;
}

function estandar5_socio_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }

    $stmt = $conn->prepare("
        SELECT e.*, u.nombre, u.apellido, u.cedula, u.email, u.activo
        FROM encuesta_sociodemografica e
        INNER JOIN usuarios u ON u.id = e.usuario_id
        WHERE u.empresa_id = ? AND u.rol = 'trabajador'
        ORDER BY u.nombre ASC, u.apellido ASC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_socio_stats(array $rows, array $preguntas): array
{
    $stats = [];
    $total = count($rows);
    foreach ($preguntas as $campo => $meta) {
        $conteos = [];
        foreach ($rows as $row) {
            $respuesta = estandar5_socio_respuesta($row[$campo] ?? '');
            $conteos[$respuesta] = ($conteos[$respuesta] ?? 0) + 1;
        }
        arsort($conteos);
        $items = [];
        foreach ($conteos as $respuesta => $cantidad) {
            $items[] = [
                'respuesta' => $respuesta,
                'cantidad' => $cantidad,
                'porcentaje' => $total > 0 ? round(($cantidad / $total) * 100, 1) : 0,
            ];
        }
        $stats[$campo] = [
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'total' => $total,
            'items' => $items,
            'principal' => $items[0] ?? ['respuesta' => 'Sin datos', 'cantidad' => 0, 'porcentaje' => 0],
        ];
    }
    return $stats;
}

function estandar5_socio_export_html(array $stats, array $preguntas, string $empresa_nombre, ?string $pregunta_filtro = null, string $buscar_filtro = ''): string
{
    $fecha = date('d/m/Y H:i');
    $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2d3d;font-size:12px}
        h1{color:#1e3a8a;font-size:20px;margin:0 0 5px}
        h2{color:#1e3a8a;font-size:15px;margin:22px 0 8px;border-bottom:1px solid #dbe3ec;padding-bottom:6px}
        .meta{color:#64748b;margin-bottom:16px}.question{page-break-inside:avoid;margin-bottom:16px}
        table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #dbe3ec;padding:7px;text-align:left}
        th{background:#f8fafc;color:#334155}.bar{height:9px;background:#e2e8f0;border-radius:20px;overflow:hidden}
        .fill{height:9px;background:#ff7a00}.summary{background:#fff7ed;border:1px solid #fed7aa;padding:8px;border-radius:6px;color:#9a3412;margin:8px 0}
    </style></head><body>';
    $html .= '<h1>Informe perfil sociodemográfico</h1>';
    $html .= '<div class="meta">Empresa: ' . htmlspecialchars($empresa_nombre) . ' | Generado: ' . htmlspecialchars($fecha) . '</div>';

    foreach ($preguntas as $campo => $meta) {
        if ($pregunta_filtro && $pregunta_filtro !== $campo) {
            continue;
        }
        $dato = $stats[$campo] ?? null;
        if (!$dato) {
            continue;
        }
        if ($buscar_filtro !== '' && stripos($meta['label'], $buscar_filtro) === false) {
            $coincide = false;
            foreach ($dato['items'] as $item_busqueda) {
                if (stripos($item_busqueda['respuesta'], $buscar_filtro) !== false) {
                    $coincide = true;
                    break;
                }
            }
            if (!$coincide) {
                continue;
            }
        }
        $html .= '<section class="question">';
        $html .= '<h2>' . htmlspecialchars($meta['label']) . '</h2>';
        $html .= '<div class="summary">Respuesta predominante: <strong>' . htmlspecialchars($dato['principal']['respuesta']) . '</strong> (' . htmlspecialchars((string)$dato['principal']['porcentaje']) . '%)</div>';
        $html .= '<table><thead><tr><th>Respuesta</th><th>Cantidad</th><th>Porcentaje</th><th>Gráfica</th></tr></thead><tbody>';
        foreach ($dato['items'] as $item) {
            if ($buscar_filtro !== '' && stripos($meta['label'], $buscar_filtro) === false && stripos($item['respuesta'], $buscar_filtro) === false) {
                continue;
            }
            $html .= '<tr><td>' . htmlspecialchars($item['respuesta']) . '</td><td>' . (int)$item['cantidad'] . '</td><td>' . htmlspecialchars((string)$item['porcentaje']) . '%</td><td><div class="bar"><div class="fill" style="width:' . htmlspecialchars((string)$item['porcentaje']) . '%"></div></div></td></tr>';
        }
        if (empty($dato['items'])) {
            $html .= '<tr><td colspan="4">No hay respuestas registradas.</td></tr>';
        }
        $html .= '</tbody></table></section>';
    }
    return $html . '</body></html>';
}

$preguntas_socio = estandar5_socio_preguntas();
$pregunta_filtro = $_GET['pregunta'] ?? '';
$pregunta_filtro = isset($preguntas_socio[$pregunta_filtro]) ? $pregunta_filtro : '';
$buscar_filtro = trim((string)($_GET['buscar'] ?? ''));
$socio_rows = $modulo_actual === 'sociodemografica' ? estandar5_socio_rows($conn, $empresa_id) : [];
$socio_stats = $modulo_actual === 'sociodemografica' ? estandar5_socio_stats($socio_rows, $preguntas_socio) : [];

if ($modulo_actual === 'sociodemografica' && isset($_GET['export'])) {
    $export = $_GET['export'];
    if ($export === 'excel') {
        $filename = 'perfil_sociodemografico_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo estandar5_socio_export_html($socio_stats, $preguntas_socio, $empresa_nombre, $pregunta_filtro ?: null, $buscar_filtro);
        exit;
    }
    if ($export === 'pdf') {
        require_once 'vendor/autoload.php';
        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml(estandar5_socio_export_html($socio_stats, $preguntas_socio, $empresa_nombre, $pregunta_filtro ?: null, $buscar_filtro), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('perfil_sociodemografico_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 5 | Evaluaciones médicas ocupacionales</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#ff8a1f; --primary2:#ff7a00; --blue-dark:#1e3a8a; --bg1:#edf4fb; --bg2:#f7f9fc; --card:#fff; --text:#1f2d3d; --muted:#64748b; --border:#dbe3ec; --green:#16a34a; --amber:#d97706; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:Inter,sans-serif; background:linear-gradient(180deg,var(--bg1),var(--bg2)); color:var(--text); font-size:.82rem; }
        .main-wrapper { margin-left:260px; width:calc(100% - 260px); min-height:100vh; }
        .content-area { width:100%; padding:30px clamp(24px,2.5vw,44px) 60px; }
        .page-head { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:18px; }
        .head-copy { display:flex; align-items:center; gap:13px; min-width:0; }
        .head-icon { width:46px; height:46px; border-radius:10px; display:grid; place-items:center; color:var(--primary); background:#fff3e8; border:1px solid #fed7aa; font-size:1.05rem; flex:none; }
        .head-copy h1 { margin:0; color:var(--blue-dark); font-size:1.18rem; line-height:1.2; }
        .head-copy p { margin:5px 0 0; color:var(--muted); font-size:.76rem; line-height:1.35; }
        .status-pill { display:inline-flex; align-items:center; gap:7px; height:34px; padding:0 12px; border-radius:999px; border:1px solid #fed7aa; background:#fff7ed; color:#c2410c; font-weight:800; font-size:.68rem; text-transform:uppercase; white-space:nowrap; }
        .intro-panel { position:relative; overflow:hidden; background:#fff; border:1px solid var(--border); border-left:4px solid var(--primary2); border-radius:10px; padding:18px; margin-bottom:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .intro-panel::after { content:""; position:absolute; right:-52px; top:-74px; width:180px; height:180px; border-radius:50%; background:#fff3e8; opacity:.75; }
        .intro-content { position:relative; z-index:1; display:grid; grid-template-columns:minmax(250px,.85fr) minmax(300px,1.15fr); gap:18px; align-items:center; }
        .intro-kicker { color:var(--primary2); font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.02em; }
        .intro-content h2 { margin:5px 0 8px; color:var(--blue-dark); font-size:1.06rem; }
        .intro-content p { margin:0; color:var(--muted); line-height:1.45; font-size:.76rem; }
        .module-tabs { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .module-tab { display:flex; align-items:flex-start; gap:8px; min-width:0; padding:10px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; text-decoration:none; transition:border-color .2s ease, background .2s ease, transform .2s ease; }
        .module-tab:hover { border-color:#fdba74; background:#fff8f3; transform:translateY(-1px); }
        .module-tab.active { border-color:#fdba74; background:#fff3e8; color:#c2410c; }
        .module-tab strong { display:block; color:var(--blue-dark); font-size:.67rem; margin-bottom:2px; }
        .module-tab span { display:block; color:inherit; font-size:.64rem; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .workspace-grid { display:grid; grid-template-columns:minmax(280px,.78fr) minmax(360px,1.22fr); gap:14px; align-items:start; }
        .module-card, .detail-card { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .module-card { overflow:hidden; }
        .module-card-head { background:linear-gradient(135deg,#172554,#1e3a8a); color:#fff; padding:16px; min-height:145px; display:flex; flex-direction:column; justify-content:space-between; gap:18px; }
        .module-code { align-self:flex-start; display:inline-flex; align-items:center; gap:7px; padding:6px 9px; border-radius:7px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.16); font-size:.68rem; font-weight:850; }
        .module-card-head h2 { margin:0; font-size:1.02rem; line-height:1.25; }
        .module-card-body { padding:15px; display:grid; gap:12px; }
        .module-card-body p { margin:0; color:var(--muted); line-height:1.45; }
        .state-row { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .state-box { border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:10px; }
        .state-box span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .state-box strong { display:block; margin-top:4px; color:var(--blue-dark); font-size:.92rem; }
        .detail-card { padding:16px; }
        .detail-card h3 { margin:0; color:var(--blue-dark); font-size:.95rem; }
        .detail-card p { margin:5px 0 14px; color:var(--muted); line-height:1.45; }
        .detail-list { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
        .detail-list li { display:flex; align-items:flex-start; gap:9px; padding:11px; border:1px solid #eef2f7; border-radius:8px; background:#fbfdff; color:#475569; line-height:1.35; }
        .detail-list i { color:var(--primary2); margin-top:2px; }
        .notice { margin-top:14px; display:flex; align-items:flex-start; gap:9px; border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; border-radius:8px; padding:11px; font-size:.72rem; line-height:1.4; }
        .socio-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .socio-filters { display:flex; align-items:center; gap:8px; flex:1; min-width:0; }
        .socio-field { height:38px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; padding:0 11px; font:inherit; font-size:.76rem; outline:0; min-width:180px; }
        .socio-field:focus { border-color:#fdba74; background:#fff; box-shadow:0 0 0 3px rgba(255,138,31,.12); }
        .socio-search { flex:1; min-width:180px; }
        .socio-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .socio-btn { height:38px; border:1px solid var(--border); background:#fff; color:#334155; border-radius:8px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; text-decoration:none; font-weight:800; font-size:.72rem; white-space:nowrap; cursor:pointer; }
        .socio-btn:hover { border-color:#fdba74; color:var(--primary2); background:#fff8f3; }
        .socio-btn.primary { background:var(--primary2); border-color:var(--primary2); color:#fff; }
        .socio-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .socio-kpi { background:#fff; border:1px solid var(--border); border-radius:10px; padding:13px; min-height:100px; box-shadow:0 8px 24px rgba(15,23,42,.04); position:relative; overflow:hidden; }
        .socio-kpi::after { content:""; position:absolute; right:-28px; bottom:-32px; width:92px; height:92px; border-radius:50%; background:#fff3e8; }
        .socio-kpi span { display:block; color:#64748b; font-size:.62rem; font-weight:850; text-transform:uppercase; position:relative; z-index:1; }
        .socio-kpi strong { display:block; margin-top:9px; color:var(--blue-dark); font-size:1.45rem; line-height:1; position:relative; z-index:1; }
        .socio-kpi small { display:block; margin-top:6px; color:#64748b; line-height:1.35; position:relative; z-index:1; }
        .socio-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .socio-question { background:#fff; border:1px solid var(--border); border-radius:10px; padding:14px; box-shadow:0 8px 24px rgba(15,23,42,.04); min-width:0; }
        .socio-question-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px; }
        .socio-question-title { display:flex; align-items:flex-start; gap:9px; min-width:0; }
        .socio-question-title i { width:32px; height:32px; border-radius:8px; display:grid; place-items:center; background:#fff3e8; color:var(--primary2); flex:none; }
        .socio-question-title h3 { margin:0; color:var(--blue-dark); font-size:.86rem; line-height:1.25; }
        .socio-question-title p { margin:3px 0 0; color:#64748b; font-size:.65rem; }
        .socio-main-percent { color:var(--primary2); font-size:1.3rem; font-weight:850; line-height:1; white-space:nowrap; }
        .answer-bars { display:grid; gap:9px; }
        .answer-row { display:grid; grid-template-columns:minmax(120px,.8fr) minmax(160px,1fr) 54px; gap:9px; align-items:center; color:#475569; font-size:.68rem; }
        .answer-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:700; }
        .answer-track { height:9px; background:#e2e8f0; border-radius:99px; overflow:hidden; }
        .answer-fill { height:100%; background:linear-gradient(90deg,#ff8a1f,#1e3a8a); border-radius:inherit; min-width:2px; }
        .answer-percent { text-align:right; color:#172554; font-weight:850; }
        .responses-section { background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .responses-head { padding:14px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .responses-head h2 { margin:0; color:var(--blue-dark); font-size:.95rem; }
        .responses-head p { margin:3px 0 0; color:#64748b; font-size:.68rem; }
        .responses-list { display:grid; }
        .response-question { padding:14px; border-bottom:1px solid #eef2f7; }
        .response-question:last-child { border-bottom:0; }
        .response-question h3 { margin:0 0 10px; color:#172554; font-size:.82rem; display:flex; align-items:center; gap:8px; }
        .response-table-wrap { overflow-x:auto; }
        .response-table { width:100%; border-collapse:collapse; min-width:560px; }
        .response-table th { text-align:left; padding:8px; background:#f8fafc; color:#64748b; font-size:.6rem; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .response-table td { padding:8px; border-bottom:1px solid #eef2f7; color:#334155; font-size:.7rem; }
        .response-table tr:last-child td { border-bottom:0; }
        .empty-socio { background:#fff; border:1px dashed #cbd5e1; border-radius:10px; padding:34px 18px; text-align:center; color:#64748b; }
        .empty-socio i { display:block; color:#cbd5e1; font-size:1.8rem; margin-bottom:10px; }
        @media (max-width: 980px) {
            .intro-content, .workspace-grid { grid-template-columns:1fr; }
            .module-tabs { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .socio-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .socio-grid { grid-template-columns:1fr; }
            .socio-toolbar { align-items:stretch; flex-direction:column; }
            .socio-actions { justify-content:flex-start; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left:0; width:100%; }
            .content-area { padding:18px 14px 45px; }
            .page-head { align-items:flex-start; flex-direction:column; }
            .status-pill { align-self:flex-start; }
            .socio-filters { flex-direction:column; align-items:stretch; }
            .socio-field { width:100%; min-width:0; }
            .socio-btn { flex:1; justify-content:center; }
            .answer-row { grid-template-columns:1fr; gap:5px; }
            .answer-percent { text-align:left; }
        }
        @media (max-width: 560px) {
            .module-tabs, .state-row { grid-template-columns:1fr; }
            .head-copy { align-items:flex-start; }
            .socio-summary { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <div class="page-head">
            <div class="head-copy">
                <div class="head-icon"><i class="fa-solid <?php echo $modulo ? htmlspecialchars($modulo['icono']) : 'fa-briefcase-medical'; ?>"></i></div>
                <div>
                    <?php if ($modulo): ?>
                        <h1><?php echo htmlspecialchars($modulo['codigo'] . ' ' . $modulo['titulo']); ?></h1>
                        <p>Vista independiente del submódulo seleccionado.</p>
                    <?php else: ?>
                        <h1>5. Evaluaciones médicas ocupacionales</h1>
                        <p>Selecciona el submódulo que deseas consultar o gestionar.</p>
                    <?php endif; ?>
                </div>
            </div>
            <span class="status-pill"><i class="fa-solid <?php echo $modulo ? 'fa-window-maximize' : 'fa-layer-group'; ?>"></i> <?php echo $modulo ? 'Submódulo' : 'Vista modular'; ?></span>
        </div>

        <?php if (!$modulo): ?>
            <section class="intro-panel">
                <div class="intro-content">
                    <div>
                        <div class="intro-kicker">Estándar 5</div>
                        <h2>Submódulos de trabajo</h2>
                        <p>Esta pantalla funciona como punto de entrada. El contenido de cada submódulo se abre en una vista independiente al seleccionarlo.</p>
                    </div>
                    <div class="module-tabs" aria-label="Submódulos del estándar 5">
                        <?php foreach ($submodulos as $slug => $item): ?>
                            <a class="module-tab" href="estandar5.php?modulo=<?php echo urlencode($slug); ?>">
                                <i class="fa-solid <?php echo htmlspecialchars($item['icono']); ?>"></i>
                                <span><strong><?php echo htmlspecialchars($item['codigo']); ?></strong><?php echo htmlspecialchars($item['titulo']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($modulo_actual === 'sociodemografica'): ?>
            <?php
                $total_trabajadores = count($socio_rows);
                $preguntas_con_datos = 0;
                foreach ($socio_stats as $stat) {
                    if (!empty($stat['items'])) {
                        $preguntas_con_datos++;
                    }
                }
                $query_export = http_build_query([
                    'modulo' => 'sociodemografica',
                    'pregunta' => $pregunta_filtro,
                    'buscar' => $buscar_filtro,
                ]);
            ?>

            <form class="socio-toolbar" method="GET" action="estandar5.php">
                <input type="hidden" name="modulo" value="sociodemografica">
                <div class="socio-filters">
                    <select class="socio-field" name="pregunta" aria-label="Filtrar por pregunta">
                        <option value="">Todas las preguntas</option>
                        <?php foreach ($preguntas_socio as $campo => $meta): ?>
                            <option value="<?php echo htmlspecialchars($campo); ?>" <?php echo $pregunta_filtro === $campo ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($meta['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input class="socio-field socio-search" type="search" name="buscar" value="<?php echo htmlspecialchars($buscar_filtro); ?>" placeholder="Buscar respuesta o trabajador...">
                    <button class="socio-btn primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                </div>
                <div class="socio-actions">
                    <a class="socio-btn" href="estandar5.php?modulo=sociodemografica"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
                    <a class="socio-btn" href="estandar5.php?<?php echo htmlspecialchars($query_export . '&export=excel'); ?>"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a class="socio-btn" href="estandar5.php?<?php echo htmlspecialchars($query_export . '&export=pdf'); ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                </div>
            </form>

            <section class="socio-summary">
                <article class="socio-kpi">
                    <span>Trabajadores encuestados</span>
                    <strong><?php echo $total_trabajadores; ?></strong>
                    <small>Registros vinculados a la empresa actual.</small>
                </article>
                <article class="socio-kpi">
                    <span>Preguntas del perfil</span>
                    <strong><?php echo count($preguntas_socio); ?></strong>
                    <small>Campos del formulario de registro del trabajador.</small>
                </article>
                <article class="socio-kpi">
                    <span>Preguntas con datos</span>
                    <strong><?php echo $preguntas_con_datos; ?></strong>
                    <small>Se calculan porcentajes sobre trabajadores encuestados.</small>
                </article>
                <article class="socio-kpi">
                    <span>Vista del rol</span>
                    <strong><?php echo $usuario_rol === 'sst' ? 'Gestión' : 'Resumen'; ?></strong>
                    <small>Información lista para análisis e informe.</small>
                </article>
            </section>

            <?php if ($total_trabajadores === 0): ?>
                <div class="empty-socio">
                    <i class="fa-solid fa-chart-pie"></i>
                    <strong>No hay encuestas sociodemográficas registradas.</strong>
                    <p>Cuando los trabajadores completen el registro, aquí aparecerán los porcentajes por pregunta.</p>
                </div>
            <?php else: ?>
                <section class="socio-grid" aria-label="Gráficos del perfil sociodemográfico">
                    <?php foreach ($socio_stats as $campo => $stat): ?>
                        <?php
                            if ($pregunta_filtro && $pregunta_filtro !== $campo) {
                                continue;
                            }
                            if ($buscar_filtro !== '') {
                                $hay_busqueda = stripos($stat['label'], $buscar_filtro) !== false;
                                foreach ($stat['items'] as $item) {
                                    if (stripos($item['respuesta'], $buscar_filtro) !== false) {
                                        $hay_busqueda = true;
                                        break;
                                    }
                                }
                                if (!$hay_busqueda) {
                                    continue;
                                }
                            }
                            $items_grafica = array_slice($stat['items'], 0, 5);
                        ?>
                        <article class="socio-question" data-question="<?php echo htmlspecialchars($campo); ?>">
                            <div class="socio-question-head">
                                <div class="socio-question-title">
                                    <i class="fa-solid <?php echo htmlspecialchars($stat['icon']); ?>"></i>
                                    <div>
                                        <h3><?php echo htmlspecialchars($stat['label']); ?></h3>
                                        <p><?php echo $stat['total']; ?> respuesta(s) analizadas</p>
                                    </div>
                                </div>
                                <div class="socio-main-percent"><?php echo htmlspecialchars((string)$stat['principal']['porcentaje']); ?>%</div>
                            </div>
                            <div class="answer-bars">
                                <?php foreach ($items_grafica as $item): ?>
                                    <div class="answer-row">
                                        <div class="answer-name" title="<?php echo htmlspecialchars($item['respuesta']); ?>"><?php echo htmlspecialchars($item['respuesta']); ?></div>
                                        <div class="answer-track"><div class="answer-fill" style="width: <?php echo htmlspecialchars((string)$item['porcentaje']); ?>%;"></div></div>
                                        <div class="answer-percent"><?php echo htmlspecialchars((string)$item['porcentaje']); ?>%</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="responses-section">
                    <div class="responses-head">
                        <div>
                            <h2>Listado de respuestas por pregunta</h2>
                            <p>Ordenado según el formulario de registro del trabajador.</p>
                        </div>
                        <i class="fa-solid fa-table-list" style="color:var(--primary2);"></i>
                    </div>
                    <div class="responses-list">
                        <?php foreach ($socio_stats as $campo => $stat): ?>
                            <?php
                                if ($pregunta_filtro && $pregunta_filtro !== $campo) {
                                    continue;
                                }
                                $items_tabla = $stat['items'];
                                if ($buscar_filtro !== '') {
                                    $items_tabla = array_values(array_filter($items_tabla, function ($item) use ($buscar_filtro, $stat) {
                                        return stripos($item['respuesta'], $buscar_filtro) !== false || stripos($stat['label'], $buscar_filtro) !== false;
                                    }));
                                    if (empty($items_tabla)) {
                                        continue;
                                    }
                                }
                            ?>
                            <article class="response-question">
                                <h3><i class="fa-solid <?php echo htmlspecialchars($stat['icon']); ?>"></i> <?php echo htmlspecialchars($stat['label']); ?></h3>
                                <div class="response-table-wrap">
                                    <table class="response-table">
                                        <thead>
                                            <tr>
                                                <th>Respuesta</th>
                                                <th>Cantidad</th>
                                                <th>Porcentaje</th>
                                                <th>Lectura rápida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items_tabla as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['respuesta']); ?></td>
                                                    <td><?php echo (int)$item['cantidad']; ?></td>
                                                    <td><?php echo htmlspecialchars((string)$item['porcentaje']); ?>%</td>
                                                    <td><?php echo (int)$item['cantidad']; ?> de <?php echo $stat['total']; ?> trabajador(es)</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php elseif ($modulo): ?>
            <section class="workspace-grid">
                <article class="module-card">
                    <div class="module-card-head">
                        <span class="module-code"><i class="fa-solid <?php echo htmlspecialchars($modulo['icono']); ?>"></i> <?php echo htmlspecialchars($modulo['codigo']); ?></span>
                        <h2><?php echo htmlspecialchars($modulo['titulo']); ?></h2>
                    </div>
                    <div class="module-card-body">
                        <p><?php echo htmlspecialchars($modulo['descripcion']); ?></p>
                        <div class="state-row">
                            <div class="state-box"><span>Estado</span><strong>Diseño</strong></div>
                            <div class="state-box"><span>Datos</span><strong>Pendiente</strong></div>
                            <div class="state-box"><span>Rol</span><strong><?php echo $usuario_rol === 'sst' ? 'Gestión SST' : 'Resumen'; ?></strong></div>
                        </div>
                    </div>
                </article>

                <article class="detail-card">
                    <h3>Base funcional del submódulo</h3>
                    <p>Por ahora dejamos el contenedor visual listo para conectar datos y flujos cuando definas cómo debe operar cada sección.</p>
                    <ul class="detail-list">
                        <li><i class="fa-solid fa-check"></i><span>Acceso desde el menú lateral y desde los botones internos del estándar 5.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Selección del submódulo actual sin perder la estructura general de la página.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Diseño responsive para escritorio, portátil y celular.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Preparado para separar vista de gestión del Responsable SST y vista resumen del Representante Legal.</span></li>
                    </ul>
                    <div class="notice">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>No se creó ni modificó ninguna tabla todavía. Cuando conectemos datos, dejaré el SQL correspondiente en un archivo de migración.</span>
                    </div>
                </article>
            </section>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
