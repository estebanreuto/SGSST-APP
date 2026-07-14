<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar7_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar7_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante', 'trabajador'], true)) {
    header('Location: dashboard.php');
    exit;
}

$submodulos = [
    'recursos-sg-sst' => [
        'codigo' => '1.1.3',
        'titulo' => 'Asignación de recursos para el Sistema de Gestión de Seguridad y Salud en el Trabajo - SG-SST',
        'resumen' => 'Planeación y seguimiento de recursos humanos, técnicos, financieros y físicos.',
        'icono' => 'fa-sack-dollar',
    ],
    'mantenimiento' => [
        'codigo' => '4.2.5',
        'titulo' => 'Mantenimiento periódico de instalaciones, equipos, máquinas, herramientas',
        'resumen' => 'Control de mantenimientos programados, soportes, responsables y vencimientos.',
        'icono' => 'fa-screwdriver-wrench',
    ],
    'epp' => [
        'codigo' => '4.2.6',
        'titulo' => 'Entrega de Elementos de Protección Personal EPP, se verifica con contratistas y subcontratistas',
        'resumen' => 'Registro de entregas, reposiciones, evidencias y verificación de uso.',
        'icono' => 'fa-helmet-safety',
    ],
    'plan-emergencias' => [
        'codigo' => '5.1.1',
        'titulo' => 'Plan de Prevención, Preparación y Respuesta ante emergencias',
        'resumen' => 'Documentación, actualización y control del plan de emergencias.',
        'icono' => 'fa-truck-medical',
    ],
    'brigada' => [
        'codigo' => '5.1.2',
        'titulo' => 'Brigada de prevención conformada, capacitada y dotada',
        'resumen' => 'Integrantes, formación, dotación y evidencias de la brigada.',
        'icono' => 'fa-user-shield',
    ],
    'mediciones-ambientales' => [
        'codigo' => '4.1.4',
        'titulo' => 'Realización mediciones ambientales, químicos, físicos y biológicos',
        'resumen' => 'Programación y resultados de mediciones por agente o área evaluada.',
        'icono' => 'fa-vial-circle-check',
    ],
    'verificacion-medidas' => [
        'codigo' => '4.2.2',
        'titulo' => 'Verificación de aplicación de medidas de prevención y control por parte de los trabajadores',
        'resumen' => 'Seguimiento a cumplimiento de controles definidos en campo.',
        'icono' => 'fa-clipboard-check',
    ],
    'procedimientos' => [
        'codigo' => '4.2.3',
        'titulo' => 'Elaboración de procedimientos, instructivos, fichas, protocolos',
        'resumen' => 'Biblioteca documental operativa para controles y actividades críticas.',
        'icono' => 'fa-file-lines',
    ],
    'inspecciones' => [
        'codigo' => '4.2.4',
        'titulo' => 'Realización de inspecciones sistemáticas a las instalaciones, maquinaria o equipos con la participación del COPASST',
        'resumen' => 'Inspecciones, hallazgos, acciones y participación del COPASST.',
        'icono' => 'fa-magnifying-glass-chart',
    ],
];

$modulo_actual = $_GET['modulo'] ?? '';
if ($modulo_actual !== '' && !isset($submodulos[$modulo_actual])) {
    $modulo_actual = '';
}
if ($usuario_rol === 'trabajador' && $modulo_actual !== 'epp') {
    header('Location: dashboard.php');
    exit;
}
$modulo = $modulo_actual !== '' ? $submodulos[$modulo_actual] : null;

$current_page = 'estandar7.php';
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmtEmpresa = $conn->prepare("SELECT empresa_id, nombre_empresa FROM usuarios WHERE id = ?");
$stmtEmpresa->execute([$usuario_id]);
$usuarioEmpresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_id = (int)($usuarioEmpresa['empresa_id'] ?? 0);
$puedeGestionarRecursos = $usuario_rol === 'sst';
$anio_recursos = (int)($_GET['anio'] ?? date('Y'));
if ($anio_recursos < 2020 || $anio_recursos > 2100) {
    $anio_recursos = (int)date('Y');
}
$mensaje = $_GET['msg'] ?? '';
$tipoMensaje = $_GET['tipo'] ?? 'ok';
$catalogo_recursos = estandar7_recursos_catalogo();
$meses_recursos = estandar7_meses();
$valores_recursos = [];
$analisis_consumos = [];
$catalogo_epp = estandar7_epp_catalogo();
$epp_trabajadores = [];
$epp_entregas = [];
$epp_entrega_actual = null;

if ($empresa_id > 0 && $modulo_actual === 'recursos-sg-sst') {
    $stmtRecursos = $conn->prepare("
        SELECT item_slug, periodo, presupuestado, ejecutado
        FROM estandar7_recursos_presupuesto
        WHERE empresa_id = ? AND anio = ?
    ");
    $stmtRecursos->execute([$empresa_id, $anio_recursos]);
    foreach ($stmtRecursos->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $itemSlug = (string)$row['item_slug'];
        $periodo = (int)$row['periodo'];
        $valores_recursos[$itemSlug][$periodo] = [
            'presupuestado' => (float)$row['presupuestado'],
            'ejecutado' => (float)$row['ejecutado'],
        ];
    }

    $stmtAnalisis = $conn->prepare("
        SELECT trimestre, seguimiento, accion
        FROM estandar7_recursos_analisis_consumo
        WHERE empresa_id = ? AND anio = ?
    ");
    $stmtAnalisis->execute([$empresa_id, $anio_recursos]);
    foreach ($stmtAnalisis->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $analisis_consumos[(int)$row['trimestre']] = [
            'seguimiento' => (string)($row['seguimiento'] ?? ''),
            'accion' => (string)($row['accion'] ?? ''),
        ];
    }
}

if ($empresa_id > 0 && $modulo_actual === 'epp') {
    if ($usuario_rol === 'sst') {
        $stmtTrabajadoresEpp = $conn->prepare("
            SELECT u.id, u.nombre, u.apellido, u.cedula, COALESCE(e.tipo_personal, '') AS cargo
            FROM usuarios u
            LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
            WHERE u.empresa_id = ? AND u.rol = 'trabajador' AND COALESCE(u.activo, 1) = 1
            ORDER BY u.nombre ASC, u.apellido ASC
        ");
        $stmtTrabajadoresEpp->execute([$empresa_id]);
        $epp_trabajadores = $stmtTrabajadoresEpp->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($usuario_rol === 'trabajador') {
        $stmtEntregasEpp = $conn->prepare("
            SELECT *
            FROM estandar7_epp_entregas
            WHERE empresa_id = ? AND trabajador_id = ?
            ORDER BY fecha_entrega DESC, id DESC
        ");
        $stmtEntregasEpp->execute([$empresa_id, $usuario_id]);
    } else {
        $stmtEntregasEpp = $conn->prepare("
            SELECT *
            FROM estandar7_epp_entregas
            WHERE empresa_id = ?
            ORDER BY fecha_entrega DESC, id DESC
            LIMIT 80
        ");
        $stmtEntregasEpp->execute([$empresa_id]);
    }
    $epp_entregas = $stmtEntregasEpp->fetchAll(PDO::FETCH_ASSOC);

    $entrega_id_actual = (int)($_GET['entrega_id'] ?? 0);
    foreach ($epp_entregas as $entrega) {
        if ((int)$entrega['id'] === $entrega_id_actual) {
            $epp_entrega_actual = $entrega;
            break;
        }
    }
    if (!$epp_entrega_actual && $usuario_rol === 'trabajador') {
        foreach ($epp_entregas as $entrega) {
            if (($entrega['estado'] ?? '') === 'pendiente_firma') {
                $epp_entrega_actual = $entrega;
                break;
            }
        }
    }
}

function e7h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function e7money($value): string
{
    return '$ ' . number_format((float)$value, 0, ',', '.');
}

function e7valor(array $valores, string $itemSlug, int $mes, string $campo): float
{
    return (float)($valores[$itemSlug][$mes][$campo] ?? 0);
}

function e7epp_items_text($json): string
{
    $items = estandar7_decode_items_json($json);
    $partes = [];
    foreach ($items as $item) {
        $nombre = (string)($item['item_nombre'] ?? '');
        $cantidad = (int)($item['cantidad'] ?? 0);
        if ($nombre !== '' && $cantidad > 0) {
            $partes[] = $nombre . ' x' . $cantidad;
        }
    }
    return $partes ? implode(', ', $partes) : 'Sin elementos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 7 | Medidas de prevención</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary:#ff8a1f;
            --primary2:#ff7a00;
            --bg1:#edf4fb;
            --bg2:#f7f9fc;
            --card:#ffffff;
            --text:#1f2d3d;
            --muted:#64748b;
            --border:#dbe3ec;
            --blue:#1e3a8a;
            --soft-orange:#fff7ed;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            display:flex;
            overflow-x:hidden;
            font-family:'Inter', sans-serif;
            color:var(--text);
            background:linear-gradient(180deg,var(--bg1),var(--bg2));
        }
        .main-wrapper {
            margin-left:260px;
            width:calc(100% - 260px);
            min-height:100vh;
            transition:all .3s ease;
        }
        .content-area {
            width:100%;
            padding:24px clamp(18px, 2.4vw, 42px) 46px;
        }
        .page-hero {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:18px;
            margin:14px 0 22px;
        }
        .title-group {
            display:flex;
            align-items:center;
            gap:16px;
            min-width:0;
        }
        .icon-hero {
            width:58px;
            height:58px;
            border-radius:14px;
            display:grid;
            place-items:center;
            color:var(--primary2);
            background:#fff3e8;
            border:1px solid #fed7aa;
            font-size:1.35rem;
            flex:0 0 auto;
        }
        h1 {
            margin:0;
            color:var(--blue);
            font-size:clamp(1.35rem, 2.25vw, 2rem);
            line-height:1.12;
            letter-spacing:0;
        }
        .subtitle {
            margin:7px 0 0;
            color:var(--muted);
            font-size:.94rem;
            line-height:1.45;
            max-width:780px;
        }
        .mode-badge,
        .back-link {
            display:inline-flex;
            align-items:center;
            gap:8px;
            border:1px solid #fed7aa;
            background:#fff7ed;
            color:#c2410c;
            border-radius:999px;
            padding:10px 14px;
            font-size:.8rem;
            font-weight:800;
            text-decoration:none;
            white-space:nowrap;
        }
        .section-card {
            position:relative;
            overflow:hidden;
            background:var(--card);
            border:1px solid var(--border);
            border-left:6px solid var(--primary2);
            border-radius:14px;
            box-shadow:0 12px 30px rgba(15,23,42,.05);
            padding:26px;
        }
        .section-card::after {
            content:'';
            position:absolute;
            top:-76px;
            right:-58px;
            width:180px;
            height:180px;
            border-radius:50%;
            background:#fff3e8;
            opacity:.82;
        }
        .section-kicker {
            margin:0 0 8px;
            color:var(--primary2);
            font-weight:800;
            font-size:.82rem;
            text-transform:uppercase;
            letter-spacing:.02em;
        }
        .section-card h2 {
            position:relative;
            z-index:1;
            margin:0;
            color:var(--blue);
            font-size:1.45rem;
            line-height:1.16;
            letter-spacing:0;
        }
        .section-copy {
            position:relative;
            z-index:1;
            margin:12px 0 0;
            color:var(--muted);
            max-width:820px;
            line-height:1.55;
            font-size:.95rem;
        }
        .module-grid {
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:12px;
            margin-top:26px;
        }
        .module-card {
            min-height:118px;
            display:flex;
            align-items:flex-start;
            gap:12px;
            padding:16px;
            border:1px solid var(--border);
            border-radius:10px;
            background:#f8fafc;
            color:#334155;
            text-decoration:none;
            transition:transform .2s ease, border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .module-card:hover,
        .module-card.active {
            transform:translateY(-1px);
            border-color:#fb923c;
            background:#fff7ed;
            box-shadow:0 10px 22px rgba(255,122,0,.1);
        }
        .module-card i {
            color:#334155;
            font-size:1rem;
            width:22px;
            margin-top:2px;
            flex:0 0 auto;
        }
        .module-card:hover i,
        .module-card.active i { color:var(--primary2); }
        .module-code {
            display:block;
            color:var(--blue);
            font-weight:800;
            font-size:.9rem;
            margin-bottom:4px;
        }
        .module-title {
            display:block;
            color:#334155;
            font-size:.82rem;
            font-weight:700;
            line-height:1.35;
        }
        .tabs-card {
            background:var(--card);
            border:1px solid var(--border);
            border-radius:14px;
            padding:14px;
            box-shadow:0 10px 24px rgba(15,23,42,.04);
            margin-bottom:18px;
        }
        .sub-tabs {
            display:flex;
            gap:8px;
            overflow-x:auto;
            padding-bottom:2px;
            scrollbar-width:thin;
        }
        .sub-tab {
            flex:0 0 auto;
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 12px;
            border-radius:10px;
            color:#475569;
            background:#f8fafc;
            border:1px solid var(--border);
            text-decoration:none;
            font-size:.78rem;
            font-weight:800;
            white-space:nowrap;
        }
        .sub-tab.active {
            color:#c2410c;
            background:#fff7ed;
            border-color:#fdba74;
        }
        .placeholder-panel {
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:1.15fr .85fr;
            gap:18px;
            margin-top:24px;
        }
        .info-box {
            border:1px solid var(--border);
            border-radius:12px;
            padding:18px;
            background:#f8fafc;
        }
        .info-box h3 {
            margin:0 0 10px;
            color:var(--blue);
            font-size:1rem;
        }
        .info-list {
            margin:0;
            padding:0;
            list-style:none;
            display:grid;
            gap:10px;
        }
        .info-list li {
            display:flex;
            gap:9px;
            color:#475569;
            font-size:.85rem;
            line-height:1.42;
        }
        .info-list i {
            color:var(--primary2);
            margin-top:2px;
        }
        .alert-message {
            position:relative;
            z-index:1;
            display:flex;
            align-items:center;
            gap:10px;
            border-radius:12px;
            padding:12px 14px;
            margin:0 0 16px;
            font-weight:800;
            font-size:.84rem;
        }
        .alert-message.ok {
            background:#ecfdf5;
            border:1px solid #bbf7d0;
            color:#047857;
        }
        .alert-message.error {
            background:#fef2f2;
            border:1px solid #fecaca;
            color:#b91c1c;
        }
        .resources-toolbar {
            position:relative;
            z-index:1;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            flex-wrap:wrap;
            margin:22px 0 14px;
            padding:14px;
            border:1px solid var(--border);
            border-radius:12px;
            background:#f8fafc;
        }
        .year-nav {
            display:flex;
            align-items:center;
            gap:10px;
        }
        .year-nav a {
            width:38px;
            height:38px;
            display:grid;
            place-items:center;
            color:var(--blue);
            background:#fff;
            border:1px solid var(--border);
            border-radius:10px;
            text-decoration:none;
            font-weight:900;
        }
        .year-nav strong {
            color:var(--blue);
            font-size:1.15rem;
            min-width:70px;
            text-align:center;
        }
        .summary-grid {
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:10px;
            margin:0 0 14px;
        }
        .summary-card {
            border:1px solid var(--border);
            background:#f8fafc;
            border-radius:10px;
            padding:11px 12px;
            min-height:72px;
        }
        .summary-card span {
            display:block;
            color:#64748b;
            text-transform:uppercase;
            font-size:.66rem;
            font-weight:800;
            letter-spacing:.03em;
            margin-bottom:7px;
        }
        .summary-card strong {
            color:var(--blue);
            font-size:1.08rem;
        }
        .category-budget-grid {
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
            gap:12px;
            margin:0 0 18px;
        }
        .category-budget-card {
            width:100%;
            text-align:left;
            border:1px solid var(--border);
            background:#fff;
            border-radius:12px;
            padding:15px;
            box-shadow:0 8px 20px rgba(15,23,42,.04);
            font-family:inherit;
            cursor:pointer;
            transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .category-budget-card:hover,
        .category-budget-card:focus-visible {
            transform:translateY(-2px);
            border-color:#fb923c;
            box-shadow:0 14px 26px rgba(255,122,0,.12);
            outline:none;
        }
        .category-budget-card h3 {
            margin:0 0 12px;
            color:var(--blue);
            font-size:.92rem;
            line-height:1.25;
        }
        .category-money-row {
            display:flex;
            justify-content:space-between;
            gap:10px;
            margin:8px 0;
            color:#475569;
            font-size:.78rem;
            font-weight:800;
        }
        .category-money-row strong {
            color:#0f172a;
            font-size:.8rem;
        }
        .category-progress {
            margin-top:12px;
        }
        .category-progress-track {
            height:10px;
            border-radius:999px;
            background:#e2e8f0;
            overflow:hidden;
        }
        .category-progress-fill {
            height:100%;
            border-radius:999px;
            background:linear-gradient(90deg, #22c55e, var(--primary2));
            min-width:0;
        }
        .category-progress-meta {
            display:flex;
            justify-content:space-between;
            gap:10px;
            margin-top:7px;
            color:#64748b;
            font-size:.72rem;
            font-weight:800;
        }
        .category-card-action {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            margin-top:12px;
            padding-top:10px;
            border-top:1px dashed var(--border);
            color:#c2410c;
            font-size:.72rem;
            font-weight:900;
        }
        .budget-modal {
            position:fixed;
            inset:0;
            z-index:9999;
            display:none;
            align-items:center;
            justify-content:center;
            padding:18px;
            background:rgba(15,23,42,.48);
        }
        .budget-modal.open { display:flex; }
        .budget-modal-panel {
            width:min(760px, 100%);
            max-height:min(82vh, 720px);
            overflow:auto;
            background:#fff;
            border-radius:16px;
            border:1px solid var(--border);
            box-shadow:0 24px 60px rgba(15,23,42,.24);
        }
        .budget-modal-head {
            position:sticky;
            top:0;
            z-index:2;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            padding:18px 20px;
            background:#fff;
            border-bottom:1px solid var(--border);
        }
        .budget-modal-head h3 {
            margin:0;
            color:var(--blue);
            font-size:1.12rem;
        }
        .budget-modal-head p {
            margin:5px 0 0;
            color:var(--muted);
            font-size:.84rem;
        }
        .budget-modal-close {
            width:38px;
            height:38px;
            border:0;
            border-radius:10px;
            background:#f1f5f9;
            color:#475569;
            display:grid;
            place-items:center;
            cursor:pointer;
            flex:0 0 auto;
        }
        .budget-modal-body {
            padding:18px 20px 20px;
        }
        .breakdown-list {
            display:grid;
            gap:12px;
        }
        .breakdown-item {
            border:1px solid var(--border);
            border-radius:12px;
            padding:14px;
            background:#f8fafc;
        }
        .breakdown-title {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            margin-bottom:10px;
        }
        .breakdown-title strong {
            color:#0f172a;
            font-size:.9rem;
            line-height:1.3;
        }
        .breakdown-title span {
            color:#c2410c;
            font-size:.78rem;
            font-weight:900;
            white-space:nowrap;
        }
        .breakdown-money {
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:10px;
            margin-bottom:10px;
        }
        .breakdown-money div {
            border:1px solid #e2e8f0;
            border-radius:10px;
            background:#fff;
            padding:10px;
        }
        .breakdown-money span {
            display:block;
            color:#64748b;
            text-transform:uppercase;
            font-size:.68rem;
            font-weight:900;
            margin-bottom:5px;
        }
        .breakdown-money strong {
            color:var(--blue);
            font-size:.92rem;
        }
        .breakdown-track {
            height:9px;
            border-radius:999px;
            background:#e2e8f0;
            overflow:hidden;
        }
        .breakdown-fill {
            height:100%;
            border-radius:999px;
            background:linear-gradient(90deg, #22c55e, var(--primary2));
        }
        .info-note {
            position:relative;
            z-index:1;
            display:flex;
            align-items:flex-start;
            gap:10px;
            border:1px solid #bfdbfe;
            background:#eff6ff;
            color:#1e3a8a;
            border-radius:12px;
            padding:12px 14px;
            margin-top:4px;
            font-size:.84rem;
            font-weight:700;
            line-height:1.4;
        }
        .consumption-card {
            position:relative;
            z-index:1;
            margin:18px 0 0;
            border:1px solid var(--border);
            border-radius:14px;
            background:#fff;
            overflow:hidden;
            box-shadow:0 10px 24px rgba(15,23,42,.04);
        }
        .consumption-head {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:14px;
            padding:16px 18px;
            background:#f8fafc;
            border-bottom:1px solid var(--border);
        }
        .consumption-head h3 {
            margin:0;
            color:var(--blue);
            font-size:1.04rem;
        }
        .consumption-head p {
            margin:5px 0 0;
            color:var(--muted);
            font-size:.82rem;
        }
        .consumption-table-wrap {
            width:100%;
            overflow:auto;
        }
        .consumption-table {
            width:100%;
            min-width:900px;
            border-collapse:separate;
            border-spacing:0;
            font-size:.82rem;
        }
        .consumption-table th,
        .consumption-table td {
            border-right:1px solid #cbd5e1;
            border-bottom:1px solid #cbd5e1;
            padding:10px;
            vertical-align:top;
        }
        .consumption-table th {
            background:#d9d9d9;
            color:#0f172a;
            text-align:center;
            font-weight:900;
        }
        .consumption-table td.period-cell {
            min-width:110px;
            background:#f1f5f9;
            color:#0f172a;
            font-weight:900;
            text-align:left;
            vertical-align:middle;
        }
        .consumption-table .money-cell,
        .consumption-table .percent-cell {
            min-width:110px;
            text-align:right;
            color:#0f172a;
            font-weight:800;
            vertical-align:middle;
        }
        .consumption-table .wide-cell {
            min-width:230px;
        }
        .consumption-table textarea {
            width:100%;
            min-height:64px;
            resize:vertical;
            border:1px solid #dbe3ec;
            border-radius:10px;
            background:#fff;
            color:#0f172a;
            padding:9px 10px;
            font:inherit;
            font-size:.8rem;
        }
        .consumption-table textarea:focus {
            outline:none;
            border-color:#fb923c;
            box-shadow:0 0 0 3px rgba(251,146,60,.12);
        }
        .consumption-readonly {
            min-height:46px;
            color:#334155;
            line-height:1.42;
            white-space:pre-wrap;
        }
        .consumption-empty {
            color:#94a3b8;
            font-style:italic;
        }
        .consumption-total td {
            background:#f8fafc;
            font-weight:900;
        }
        .resource-table-wrap {
            position:relative;
            z-index:1;
            width:100%;
            overflow:auto;
            border:1px solid var(--border);
            border-radius:12px;
            background:#fff;
            max-height:68vh;
        }
        .resource-table {
            width:100%;
            min-width:2300px;
            border-collapse:separate;
            border-spacing:0;
            font-size:.72rem;
        }
        .resource-table th,
        .resource-table td {
            border-right:1px solid #cbd5e1;
            border-bottom:1px solid #cbd5e1;
            padding:7px 6px;
            text-align:right;
            white-space:nowrap;
        }
        .resource-table thead th {
            position:sticky;
            top:0;
            z-index:3;
            text-align:center;
            color:#0f172a;
            text-transform:uppercase;
            letter-spacing:0;
            font-weight:900;
        }
        .resource-table .group-head {
            background:#6aa84f;
            color:#07140a;
            font-size:.75rem;
            border-top:1px solid #365f22;
        }
        .resource-table .item-head {
            background:#e2f0d9;
            color:#0f172a;
            vertical-align:middle;
            line-height:1.25;
            min-width:170px;
        }
        .resource-table .period-head {
            left:0;
            z-index:4;
            min-width:118px;
            background:#d9d9d9;
        }
        .resource-table .sub-presupuesto { background:#00b050; color:#052e16; }
        .resource-table .sub-ejecutado { background:#ffff00; color:#1f2937; }
        .resource-table .period-cell {
            position:sticky;
            left:0;
            z-index:2;
            text-align:left;
            background:#f1f5f9;
            color:#0f172a;
            font-weight:900;
            text-transform:uppercase;
        }
        .resource-table tbody tr:nth-child(even) td:not(.period-cell) { background:#fbfdff; }
        .resource-table tfoot td {
            font-weight:900;
            background:#f8fafc;
        }
        .resource-table tfoot .subtotal-presupuesto { background:#00b050; color:#052e16; }
        .resource-table tfoot .subtotal-ejecutado { background:#ffff00; color:#1f2937; }
        .resource-table input {
            width:96px;
            min-width:96px;
            border:0;
            border-radius:7px;
            background:#ffffff;
            padding:7px 8px;
            text-align:right;
            font-size:.76rem;
            color:#0f172a;
            box-shadow:inset 0 0 0 1px #dbe3ec;
        }
        .resource-table input:focus {
            outline:none;
            box-shadow:inset 0 0 0 2px #fb923c, 0 0 0 3px rgba(251,146,60,.12);
        }
        .readonly-money {
            color:#0f172a;
            font-weight:700;
        }
        .resources-actions {
            position:relative;
            z-index:1;
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:16px;
        }
        .btn-save {
            border:none;
            border-radius:10px;
            padding:12px 16px;
            background:linear-gradient(135deg,var(--primary),var(--primary2));
            color:#fff;
            font-weight:900;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            gap:8px;
            box-shadow:0 8px 18px rgba(255,122,0,.22);
        }
        .epp-layout {
            position:relative;
            z-index:1;
            display:grid;
            gap:18px;
            margin-top:22px;
        }
        .epp-panel {
            border:1px solid var(--border);
            border-radius:14px;
            background:#fff;
            padding:18px;
            box-shadow:0 10px 24px rgba(15,23,42,.04);
        }
        .epp-panel h3 {
            margin:0 0 12px;
            color:var(--blue);
            font-size:1.04rem;
        }
        .epp-form-grid {
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:12px;
            margin-bottom:14px;
        }
        .epp-field {
            display:flex;
            flex-direction:column;
            gap:6px;
            min-width:0;
        }
        .epp-field.wide { grid-column:span 2; }
        .epp-field.full { grid-column:1 / -1; }
        .epp-field label {
            color:#334155;
            font-size:.7rem;
            text-transform:uppercase;
            font-weight:900;
            letter-spacing:.03em;
        }
        .epp-field input,
        .epp-field select,
        .epp-field textarea {
            width:100%;
            border:1px solid #cbd5e1;
            background:#f8fafc;
            color:#0f172a;
            border-radius:10px;
            padding:10px 11px;
            font:inherit;
            font-size:.84rem;
            outline:none;
        }
        .epp-field textarea { min-height:74px; resize:vertical; }
        .worker-snapshot {
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:10px;
            padding:12px;
            border:1px dashed #bfdbfe;
            border-radius:12px;
            background:#eff6ff;
            margin-bottom:14px;
        }
        .worker-snapshot span {
            display:block;
            color:#64748b;
            font-size:.67rem;
            text-transform:uppercase;
            font-weight:900;
            margin-bottom:4px;
        }
        .worker-snapshot strong {
            color:#1e3a8a;
            font-size:.84rem;
        }
        .epp-category-grid {
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:12px;
        }
        .epp-category {
            border:1px solid var(--border);
            border-radius:12px;
            background:#f8fafc;
            padding:12px;
        }
        .epp-category h4 {
            margin:0 0 10px;
            color:#0f172a;
            font-size:.86rem;
        }
        .epp-item-row {
            display:grid;
            grid-template-columns:1fr 88px;
            gap:8px;
            align-items:center;
            margin:7px 0;
            color:#334155;
            font-size:.8rem;
            font-weight:700;
        }
        .epp-item-row input {
            width:88px;
            border:1px solid #cbd5e1;
            border-radius:9px;
            padding:8px;
            text-align:center;
            font:inherit;
            background:#fff;
        }
        .epp-other-row {
            display:grid;
            grid-template-columns:1fr 88px;
            gap:8px;
            margin:7px 0;
        }
        .epp-other-row input {
            border:1px solid #cbd5e1;
            border-radius:9px;
            padding:8px;
            font:inherit;
            background:#fff;
        }
        .epp-list-table-wrap {
            overflow:auto;
            border:1px solid var(--border);
            border-radius:12px;
        }
        .epp-list-table {
            width:100%;
            min-width:980px;
            border-collapse:separate;
            border-spacing:0;
            font-size:.78rem;
        }
        .epp-list-table th,
        .epp-list-table td {
            border-bottom:1px solid #e2e8f0;
            padding:10px;
            text-align:left;
            vertical-align:top;
        }
        .epp-list-table th {
            background:#f8fafc;
            color:#334155;
            text-transform:uppercase;
            font-size:.67rem;
            font-weight:900;
        }
        .status-pill {
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            padding:5px 8px;
            font-weight:900;
            font-size:.68rem;
            white-space:nowrap;
        }
        .status-pill.pending { background:#fff7ed; color:#c2410c; }
        .status-pill.signed { background:#ecfdf5; color:#047857; }
        .signature-pad {
            border:1px solid #cbd5e1;
            border-radius:12px;
            background:#fff;
            width:100%;
            height:220px;
            touch-action:none;
            display:block;
        }
        .signature-actions {
            display:flex;
            justify-content:space-between;
            gap:10px;
            margin-top:12px;
            flex-wrap:wrap;
        }
        .btn-light {
            border:1px solid #cbd5e1;
            border-radius:10px;
            padding:11px 14px;
            background:#fff;
            color:#334155;
            font-weight:900;
            cursor:pointer;
        }
        .btn-link-small {
            color:#1e3a8a;
            font-weight:900;
            text-decoration:none;
            font-size:.76rem;
        }
        @media (max-width:1100px) {
            .module-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .placeholder-panel { grid-template-columns:1fr; }
            .category-budget-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .epp-form-grid,
            .worker-snapshot,
            .epp-category-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width:768px) {
            body { display:block; }
            .main-wrapper { margin-left:0; width:100%; }
            .content-area { padding:18px 14px 34px; }
            .page-hero { flex-direction:column; }
            .title-group { align-items:flex-start; }
            .icon-hero { width:50px; height:50px; }
            .mode-badge, .back-link { width:100%; justify-content:center; }
            .section-card { padding:20px 16px; }
            .module-grid { grid-template-columns:1fr; }
            .summary-grid { grid-template-columns:1fr; }
            .category-budget-grid { grid-template-columns:1fr; }
            .epp-form-grid,
            .worker-snapshot,
            .epp-category-grid { grid-template-columns:1fr; }
            .epp-field.wide { grid-column:auto; }
        }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>

<main class="main-wrapper">
    <?php include 'components/header.php'; ?>

    <div class="content-area">
        <section class="page-hero">
            <div class="title-group">
                <div class="icon-hero">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h1>7. Medidas de prevención y control</h1>
                    <p class="subtitle">Subestándares para organizar recursos, mantenimientos, EPP, emergencias, mediciones, procedimientos e inspecciones.</p>
                </div>
            </div>
            <?php if ($modulo): ?>
                <a class="back-link" href="estandar7.php">
                    <i class="fa-solid fa-arrow-left"></i>
                    Vista principal
                </a>
            <?php else: ?>
                <span class="mode-badge">
                    <i class="fa-solid fa-layer-group"></i>
                    Vista modular
                </span>
            <?php endif; ?>
        </section>

        <?php if (!$modulo): ?>
            <section class="section-card">
                <p class="section-kicker">Estándar 7</p>
                <h2>Subestándares de trabajo</h2>
                <p class="section-copy">Esta vista deja preparada la navegación del estándar de medidas de prevención y control. Cada tarjeta abre una vista independiente para construir el flujo de gestión correspondiente.</p>

                <div class="module-grid">
                    <?php foreach ($submodulos as $slug => $item): ?>
                        <a class="module-card" href="estandar7.php?modulo=<?php echo urlencode($slug); ?>">
                            <i class="fa-solid <?php echo e7h($item['icono']); ?>"></i>
                            <span>
                                <span class="module-code"><?php echo e7h($item['codigo']); ?></span>
                                <span class="module-title"><?php echo e7h($item['titulo']); ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="tabs-card" aria-label="Subestándares">
                <div class="sub-tabs">
                    <?php foreach ($submodulos as $slug => $item): ?>
                        <a class="sub-tab <?php echo $slug === $modulo_actual ? 'active' : ''; ?>" href="estandar7.php?modulo=<?php echo urlencode($slug); ?>">
                            <i class="fa-solid <?php echo e7h($item['icono']); ?>"></i>
                            <?php echo e7h($item['codigo']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section-card">
                <p class="section-kicker"><?php echo e7h($modulo['codigo']); ?></p>
                <h2><?php echo e7h($modulo['titulo']); ?></h2>
                <p class="section-copy"><?php echo e7h($modulo['resumen']); ?></p>

                <?php if ($modulo_actual === 'recursos-sg-sst'): ?>
                    <?php
                        $totalPresupuestado = 0.0;
                        $totalEjecutado = 0.0;
                        $subtotalesItems = [];
                        $totalesCategorias = [];
                        foreach ($catalogo_recursos as $categoriaSlug => $categoria) {
                            $totalesCategorias[$categoriaSlug] = [
                                'nombre' => $categoria['nombre'],
                                'presupuestado' => 0.0,
                                'ejecutado' => 0.0,
                            ];
                            foreach ($categoria['items'] as $itemSlug => $itemNombre) {
                                $subtotalesItems[$itemSlug] = ['presupuestado' => 0.0, 'ejecutado' => 0.0];
                                foreach ($meses_recursos as $mesNumero => $mesNombre) {
                                    $pres = e7valor($valores_recursos, $itemSlug, $mesNumero, 'presupuestado');
                                    $eje = e7valor($valores_recursos, $itemSlug, $mesNumero, 'ejecutado');
                                    $subtotalesItems[$itemSlug]['presupuestado'] += $pres;
                                    $subtotalesItems[$itemSlug]['ejecutado'] += $eje;
                                    $totalesCategorias[$categoriaSlug]['presupuestado'] += $pres;
                                    $totalesCategorias[$categoriaSlug]['ejecutado'] += $eje;
                                    $totalPresupuestado += $pres;
                                    $totalEjecutado += $eje;
                                }
                            }
                        }
                        $avanceRecursos = $totalPresupuestado > 0 ? round(($totalEjecutado / $totalPresupuestado) * 100, 1) : 0;
                    ?>

                    <?php if ($mensaje !== ''): ?>
                        <div class="alert-message <?php echo $tipoMensaje === 'error' ? 'error' : 'ok'; ?>">
                            <i class="fa-solid <?php echo $tipoMensaje === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                            <span><?php echo e7h($mensaje); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="resources-toolbar">
                        <div>
                            <strong style="color:var(--blue);display:block;margin-bottom:4px;">Resumen anual de recursos</strong>
                            <span style="color:var(--muted);font-size:.84rem;">Presupuesto asignado y ejecutado por categoria.</span>
                        </div>
                        <div class="year-nav">
                            <a href="estandar7.php?modulo=recursos-sg-sst&anio=<?php echo $anio_recursos - 1; ?>" aria-label="Ano anterior"><i class="fa-solid fa-chevron-left"></i></a>
                            <strong><?php echo $anio_recursos; ?></strong>
                            <a href="estandar7.php?modulo=recursos-sg-sst&anio=<?php echo $anio_recursos + 1; ?>" aria-label="Ano siguiente"><i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-card">
                            <span>Presupuesto anual asignado</span>
                            <strong><?php echo e7money($totalPresupuestado); ?></strong>
                        </div>
                        <div class="summary-card">
                            <span>Presupuesto consumido</span>
                            <strong><?php echo e7money($totalEjecutado); ?></strong>
                        </div>
                        <div class="summary-card">
                            <span>Porcentaje ejecutado</span>
                            <strong><?php echo number_format($avanceRecursos, 1, ',', '.'); ?>%</strong>
                        </div>
                    </div>

                    <div class="category-budget-grid">
                        <?php foreach ($totalesCategorias as $categoriaSlug => $categoria): ?>
                            <?php
                                $catPres = (float)$categoria['presupuestado'];
                                $catEje = (float)$categoria['ejecutado'];
                                $catPct = $catPres > 0 ? min(100, round(($catEje / $catPres) * 100, 1)) : 0;
                            ?>
                            <button class="category-budget-card" type="button" data-budget-modal="modal-<?php echo e7h($categoriaSlug); ?>">
                                <h3><?php echo e7h($categoria['nombre']); ?></h3>
                                <div class="category-money-row">
                                    <span>Presupuestado</span>
                                    <strong><?php echo e7money($catPres); ?></strong>
                                </div>
                                <div class="category-money-row">
                                    <span>Ejecutado</span>
                                    <strong><?php echo e7money($catEje); ?></strong>
                                </div>
                                <div class="category-progress">
                                    <div class="category-progress-track">
                                        <div class="category-progress-fill" style="width:<?php echo $catPct; ?>%"></div>
                                    </div>
                                    <div class="category-progress-meta">
                                        <span>Avance</span>
                                        <strong><?php echo number_format($catPres > 0 ? ($catEje / $catPres) * 100 : 0, 1, ',', '.'); ?>%</strong>
                                    </div>
                                </div>
                                <div class="category-card-action">
                                    <span>Ver desglose</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($totalesCategorias as $categoriaSlug => $categoria): ?>
                        <div class="budget-modal" id="modal-<?php echo e7h($categoriaSlug); ?>" aria-hidden="true">
                            <div class="budget-modal-panel" role="dialog" aria-modal="true" aria-labelledby="modal-title-<?php echo e7h($categoriaSlug); ?>">
                                <div class="budget-modal-head">
                                    <div>
                                        <h3 id="modal-title-<?php echo e7h($categoriaSlug); ?>"><?php echo e7h($categoria['nombre']); ?></h3>
                                        <p>Distribucion interna del presupuesto asignado y ejecutado.</p>
                                    </div>
                                    <button type="button" class="budget-modal-close" aria-label="Cerrar desglose">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="budget-modal-body">
                                    <div class="breakdown-list">
                                        <?php foreach (($catalogo_recursos[$categoriaSlug]['items'] ?? []) as $itemSlug => $itemNombre): ?>
                                            <?php
                                                $itemPres = (float)($subtotalesItems[$itemSlug]['presupuestado'] ?? 0);
                                                $itemEje = (float)($subtotalesItems[$itemSlug]['ejecutado'] ?? 0);
                                                $itemPctValue = $itemPres > 0 ? ($itemEje / $itemPres) * 100 : 0;
                                                $itemPctBar = min(100, round($itemPctValue, 1));
                                            ?>
                                            <div class="breakdown-item">
                                                <div class="breakdown-title">
                                                    <strong><?php echo e7h($itemNombre); ?></strong>
                                                    <span><?php echo number_format($itemPctValue, 1, ',', '.'); ?>%</span>
                                                </div>
                                                <div class="breakdown-money">
                                                    <div>
                                                        <span>Presupuestado</span>
                                                        <strong><?php echo e7money($itemPres); ?></strong>
                                                    </div>
                                                    <div>
                                                        <span>Ejecutado</span>
                                                        <strong><?php echo e7money($itemEje); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="breakdown-track">
                                                    <div class="breakdown-fill" style="width:<?php echo $itemPctBar; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php
                        $trimestresRecursos = [
                            1 => ['label' => 'Trimestre I', 'meses' => [1, 2, 3]],
                            2 => ['label' => 'Trimestre II', 'meses' => [4, 5, 6]],
                            3 => ['label' => 'Trimestre III', 'meses' => [7, 8, 9]],
                            4 => ['label' => 'Trimestre IV', 'meses' => [10, 11, 12]],
                        ];
                        $totalesTrimestres = [];
                        $totalTrimestrePres = 0.0;
                        $totalTrimestreEje = 0.0;
                        foreach ($trimestresRecursos as $trimestre => $metaTrimestre) {
                            $totalesTrimestres[$trimestre] = ['presupuestado' => 0.0, 'ejecutado' => 0.0];
                            foreach ($catalogo_recursos as $categoria) {
                                foreach ($categoria['items'] as $itemSlug => $itemNombre) {
                                    foreach ($metaTrimestre['meses'] as $mesNumero) {
                                        $totalesTrimestres[$trimestre]['presupuestado'] += e7valor($valores_recursos, $itemSlug, $mesNumero, 'presupuestado');
                                        $totalesTrimestres[$trimestre]['ejecutado'] += e7valor($valores_recursos, $itemSlug, $mesNumero, 'ejecutado');
                                    }
                                }
                            }
                            $totalTrimestrePres += $totalesTrimestres[$trimestre]['presupuestado'];
                            $totalTrimestreEje += $totalesTrimestres[$trimestre]['ejecutado'];
                        }
                        $totalTrimestrePct = $totalTrimestrePres > 0 ? number_format(($totalTrimestreEje / $totalTrimestrePres) * 100, 1, ',', '.') . '%' : 'Sin presupuesto';
                    ?>

                    <div class="consumption-card">
                        <div class="consumption-head">
                            <div>
                                <h3>Análisis de consumos</h3>
                                <p>Seguimiento trimestral calculado desde el presupuesto anual asignado y ejecutado.</p>
                            </div>
                        </div>
                        <form method="post" action="procesar_estandar7.php">
                            <input type="hidden" name="accion" value="guardar_analisis_consumo">
                            <input type="hidden" name="anio" value="<?php echo $anio_recursos; ?>">
                            <div class="consumption-table-wrap">
                                <table class="consumption-table">
                                    <thead>
                                        <tr>
                                            <th>Periodo</th>
                                            <th>Presupuestado</th>
                                            <th>Ejecutado</th>
                                            <th>% Ejecución</th>
                                            <th>Seguimiento</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trimestresRecursos as $trimestre => $metaTrimestre): ?>
                                            <?php
                                                $triPres = $totalesTrimestres[$trimestre]['presupuestado'];
                                                $triEje = $totalesTrimestres[$trimestre]['ejecutado'];
                                                $triPct = $triPres > 0 ? number_format(($triEje / $triPres) * 100, 1, ',', '.') . '%' : 'Sin presupuesto';
                                                $seguimiento = $analisis_consumos[$trimestre]['seguimiento'] ?? '';
                                                $accionTri = $analisis_consumos[$trimestre]['accion'] ?? '';
                                            ?>
                                            <tr>
                                                <td class="period-cell"><?php echo e7h($metaTrimestre['label']); ?></td>
                                                <td class="money-cell"><?php echo e7money($triPres); ?></td>
                                                <td class="money-cell"><?php echo e7money($triEje); ?></td>
                                                <td class="percent-cell"><?php echo e7h($triPct); ?></td>
                                                <td class="wide-cell">
                                                    <?php if ($puedeGestionarRecursos): ?>
                                                        <textarea name="seguimiento[<?php echo $trimestre; ?>]" placeholder="Registra el seguimiento del trimestre"><?php echo e7h($seguimiento); ?></textarea>
                                                    <?php else: ?>
                                                        <div class="consumption-readonly <?php echo trim($seguimiento) === '' ? 'consumption-empty' : ''; ?>"><?php echo trim($seguimiento) === '' ? 'Sin seguimiento registrado' : e7h($seguimiento); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="wide-cell">
                                                    <?php if ($puedeGestionarRecursos): ?>
                                                        <textarea name="accion_trimestre[<?php echo $trimestre; ?>]" placeholder="Registra la acción o decisión"><?php echo e7h($accionTri); ?></textarea>
                                                    <?php else: ?>
                                                        <div class="consumption-readonly <?php echo trim($accionTri) === '' ? 'consumption-empty' : ''; ?>"><?php echo trim($accionTri) === '' ? 'Sin acción registrada' : e7h($accionTri); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="consumption-total">
                                            <td class="period-cell">Total <?php echo $anio_recursos; ?></td>
                                            <td class="money-cell"><?php echo e7money($totalTrimestrePres); ?></td>
                                            <td class="money-cell"><?php echo e7money($totalTrimestreEje); ?></td>
                                            <td class="percent-cell"><?php echo e7h($totalTrimestrePct); ?></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php if ($puedeGestionarRecursos): ?>
                                <div class="resources-actions">
                                    <button class="btn-save" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Guardar análisis
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (!$puedeGestionarRecursos): ?>
                        <div class="info-note">
                            <i class="fa-solid fa-eye"></i>
                            <span>Vista informativa para representante legal. La captura y edicion del presupuesto queda a cargo del Responsable SST.</span>
                        </div>
                    <?php else: ?>
                        <div class="resources-toolbar">
                            <div>
                                <strong style="color:var(--blue);display:block;margin-bottom:4px;">Matriz anual de recursos</strong>
                                <span style="color:var(--muted);font-size:.84rem;">Registra presupuesto asignado y ejecutado por mes y rubro.</span>
                            </div>
                        </div>

                        <form method="post" action="procesar_estandar7.php">
                            <input type="hidden" name="accion" value="guardar_recursos">
                            <input type="hidden" name="anio" value="<?php echo $anio_recursos; ?>">

                            <div class="resource-table-wrap">
                                <table class="resource-table">
                                    <thead>
                                        <tr>
                                            <th class="period-head" rowspan="3">Periodo</th>
                                            <?php foreach ($catalogo_recursos as $categoria): ?>
                                                <?php $colspan = count($categoria['items']) * 2; ?>
                                                <th class="group-head" colspan="<?php echo $colspan; ?>"><?php echo e7h($categoria['nombre']); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr>
                                            <?php foreach ($catalogo_recursos as $categoria): ?>
                                                <?php foreach ($categoria['items'] as $itemNombre): ?>
                                                    <th class="item-head" colspan="2"><?php echo e7h($itemNombre); ?></th>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr>
                                            <?php foreach ($catalogo_recursos as $categoria): ?>
                                                <?php foreach ($categoria['items'] as $itemSlug => $itemNombre): ?>
                                                    <th class="sub-presupuesto">Presupuestado</th>
                                                    <th class="sub-ejecutado">Ejecutado</th>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meses_recursos as $mesNumero => $mesNombre): ?>
                                            <tr>
                                                <td class="period-cell"><?php echo e7h($mesNombre); ?></td>
                                                <?php foreach ($catalogo_recursos as $categoria): ?>
                                                    <?php foreach ($categoria['items'] as $itemSlug => $itemNombre): ?>
                                                        <?php
                                                            $pres = e7valor($valores_recursos, $itemSlug, $mesNumero, 'presupuestado');
                                                            $eje = e7valor($valores_recursos, $itemSlug, $mesNumero, 'ejecutado');
                                                        ?>
                                                        <td>
                                                            <input type="number" min="0" step="1000" name="presupuesto[<?php echo e7h($itemSlug); ?>][<?php echo $mesNumero; ?>]" value="<?php echo e7h($pres); ?>" aria-label="Presupuestado <?php echo e7h($itemNombre . ' ' . $mesNombre); ?>">
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" step="1000" name="ejecutado[<?php echo e7h($itemSlug); ?>][<?php echo $mesNumero; ?>]" value="<?php echo e7h($eje); ?>" aria-label="Ejecutado <?php echo e7h($itemNombre . ' ' . $mesNombre); ?>">
                                                        </td>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="period-cell">Subtotal</td>
                                            <?php foreach ($catalogo_recursos as $categoria): ?>
                                                <?php foreach ($categoria['items'] as $itemSlug => $itemNombre): ?>
                                                    <td class="subtotal-presupuesto"><?php echo e7money($subtotalesItems[$itemSlug]['presupuestado'] ?? 0); ?></td>
                                                    <td class="subtotal-ejecutado"><?php echo e7money($subtotalesItems[$itemSlug]['ejecutado'] ?? 0); ?></td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr>
                                            <td class="period-cell">% Ejecutado</td>
                                            <?php foreach ($catalogo_recursos as $categoria): ?>
                                                <?php foreach ($categoria['items'] as $itemSlug => $itemNombre): ?>
                                                    <?php
                                                        $itemPres = $subtotalesItems[$itemSlug]['presupuestado'] ?? 0;
                                                        $itemEje = $subtotalesItems[$itemSlug]['ejecutado'] ?? 0;
                                                        $itemPct = $itemPres > 0 ? number_format(($itemEje / $itemPres) * 100, 1, ',', '.') . '%' : 'Sin presupuesto';
                                                    ?>
                                                    <td colspan="2"><?php echo e7h($itemPct); ?></td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="resources-actions">
                                <button class="btn-save" type="submit">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Guardar presupuesto
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php elseif ($modulo_actual === 'epp'): ?>
                    <div class="epp-layout">
                        <?php if ($mensaje !== ''): ?>
                            <div class="alert-message <?php echo $tipoMensaje === 'error' ? 'error' : 'ok'; ?>">
                                <i class="fa-solid <?php echo $tipoMensaje === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                                <span><?php echo e7h($mensaje); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($usuario_rol === 'sst'): ?>
                            <section class="epp-panel">
                                <h3>Nueva entrega de EPP</h3>
                                <form method="post" action="procesar_estandar7.php" id="eppDeliveryForm">
                                    <input type="hidden" name="accion" value="guardar_epp_entrega">
                                    <div class="epp-form-grid">
                                        <div class="epp-field wide">
                                            <label>Trabajador</label>
                                            <select name="trabajador_id" id="eppWorkerSelect" required>
                                                <option value="">Seleccione trabajador...</option>
                                                <?php foreach ($epp_trabajadores as $trabajador): ?>
                                                    <?php
                                                        $nombreTrabajador = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''));
                                                        $cargoTrabajador = trim((string)($trabajador['cargo'] ?? '')) ?: 'Sin cargo registrado';
                                                    ?>
                                                    <option value="<?php echo (int)$trabajador['id']; ?>"
                                                        data-nombre="<?php echo e7h($nombreTrabajador); ?>"
                                                        data-cedula="<?php echo e7h($trabajador['cedula'] ?? ''); ?>"
                                                        data-cargo="<?php echo e7h($cargoTrabajador); ?>">
                                                        <?php echo e7h($nombreTrabajador . ' - ' . ($trabajador['cedula'] ?? '')); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="epp-field">
                                            <label>Fecha</label>
                                            <input type="date" name="fecha_entrega" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="epp-field">
                                            <label>Tipo entrega</label>
                                            <select name="tipo_entrega" required>
                                                <option value="Ordinaria">Ordinaria</option>
                                                <option value="Desgaste">Desgaste</option>
                                                <option value="Perdida">Perdida</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="worker-snapshot">
                                        <div><span>Nombre</span><strong id="eppWorkerName">Sin seleccionar</strong></div>
                                        <div><span>Cédula</span><strong id="eppWorkerCedula">Sin dato</strong></div>
                                        <div><span>Cargo / tipo</span><strong id="eppWorkerCargo">Sin dato</strong></div>
                                        <div><span>Estado</span><strong>Pendiente de firma</strong></div>
                                    </div>

                                    <div class="epp-category-grid">
                                        <?php foreach ($catalogo_epp as $categoriaSlug => $categoria): ?>
                                            <div class="epp-category">
                                                <h4><?php echo e7h($categoria['nombre']); ?></h4>
                                                <?php if (!empty($categoria['items'])): ?>
                                                    <?php foreach ($categoria['items'] as $itemSlug => $itemNombre): ?>
                                                        <label class="epp-item-row">
                                                            <span><?php echo e7h($itemNombre); ?></span>
                                                            <input type="number" name="epp_qty[<?php echo e7h($itemSlug); ?>]" min="0" max="999" value="0">
                                                        </label>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <?php for ($otro = 0; $otro < 3; $otro++): ?>
                                                        <div class="epp-other-row">
                                                            <input type="text" name="otro_epp_nombre[<?php echo $otro; ?>]" placeholder="Nombre">
                                                            <input type="number" name="otro_epp_cantidad[<?php echo $otro; ?>]" min="0" max="999" value="0">
                                                        </div>
                                                    <?php endfor; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="epp-form-grid" style="margin-top:14px;">
                                        <div class="epp-field">
                                            <label>Entregado por</label>
                                            <select name="entregado_por_tipo" id="eppDeliveredBy">
                                                <option value="responsable_sst">Responsable SST</option>
                                                <option value="representante_legal">Representante legal</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="epp-field wide" id="eppDeliveredOtherWrap" style="display:none;">
                                            <label>Nombre de quien entrega</label>
                                            <input type="text" name="entregado_por_otro" placeholder="Nombre completo">
                                        </div>
                                        <div class="epp-field full">
                                            <label>Observaciones</label>
                                            <textarea name="observaciones" placeholder="Información adicional de la entrega"></textarea>
                                        </div>
                                    </div>

                                    <div class="resources-actions">
                                        <button class="btn-save" type="submit">
                                            <i class="fa-solid fa-paper-plane"></i>
                                            Guardar y enviar a firma
                                        </button>
                                    </div>
                                </form>
                            </section>
                        <?php endif; ?>

                        <?php if ($usuario_rol === 'trabajador' && $epp_entrega_actual && ($epp_entrega_actual['estado'] ?? '') === 'pendiente_firma'): ?>
                            <section class="epp-panel">
                                <h3>Firma de recibido pendiente</h3>
                                <p class="section-copy" style="margin-top:0;">Revisa los elementos entregados y firma el recibido en el recuadro.</p>
                                <div class="worker-snapshot">
                                    <div><span>Fecha</span><strong><?php echo date('d/m/Y', strtotime($epp_entrega_actual['fecha_entrega'])); ?></strong></div>
                                    <div><span>Tipo</span><strong><?php echo e7h($epp_entrega_actual['tipo_entrega']); ?></strong></div>
                                    <div><span>Entregado por</span><strong><?php echo e7h($epp_entrega_actual['entregado_por_nombre']); ?></strong></div>
                                    <div><span>Elementos</span><strong><?php echo e7h(e7epp_items_text($epp_entrega_actual['items_json'])); ?></strong></div>
                                </div>
                                <form method="post" action="procesar_estandar7.php" id="eppSignatureForm">
                                    <input type="hidden" name="accion" value="firmar_epp_entrega">
                                    <input type="hidden" name="entrega_id" value="<?php echo (int)$epp_entrega_actual['id']; ?>">
                                    <input type="hidden" name="firma_trabajador" id="eppSignatureInput">
                                    <canvas class="signature-pad" id="eppSignatureCanvas"></canvas>
                                    <div class="signature-actions">
                                        <button type="button" class="btn-light" id="eppClearSignature">Limpiar firma</button>
                                        <button class="btn-save" type="submit">
                                            <i class="fa-solid fa-signature"></i>
                                            Firmar recibido
                                        </button>
                                    </div>
                                </form>
                            </section>
                        <?php endif; ?>

                        <section class="epp-panel">
                            <h3>Historial de entregas</h3>
                            <div class="epp-list-table-wrap">
                                <table class="epp-list-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Trabajador</th>
                                            <th>Cédula</th>
                                            <th>Cargo</th>
                                            <th>Elementos</th>
                                            <th>Entregado por</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Firma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$epp_entregas): ?>
                                            <tr><td colspan="9">No hay entregas registradas.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($epp_entregas as $entrega): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($entrega['fecha_entrega'])); ?></td>
                                                    <td><?php echo e7h($entrega['nombre_trabajador']); ?></td>
                                                    <td><?php echo e7h($entrega['cedula']); ?></td>
                                                    <td><?php echo e7h($entrega['cargo'] ?: 'Sin cargo'); ?></td>
                                                    <td><?php echo e7h(e7epp_items_text($entrega['items_json'])); ?></td>
                                                    <td><?php echo e7h($entrega['entregado_por_nombre']); ?></td>
                                                    <td><?php echo e7h($entrega['tipo_entrega']); ?></td>
                                                    <td>
                                                        <span class="status-pill <?php echo ($entrega['estado'] ?? '') === 'firmado' ? 'signed' : 'pending'; ?>">
                                                            <?php echo ($entrega['estado'] ?? '') === 'firmado' ? 'Firmado' : 'Pendiente'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (($entrega['estado'] ?? '') === 'firmado'): ?>
                                                            <?php echo !empty($entrega['fecha_firma']) ? date('d/m/Y H:i', strtotime($entrega['fecha_firma'])) : 'Firmado'; ?>
                                                        <?php elseif ($usuario_rol === 'trabajador'): ?>
                                                            <a class="btn-link-small" href="estandar7.php?modulo=epp&entrega_id=<?php echo (int)$entrega['id']; ?>">Firmar</a>
                                                        <?php else: ?>
                                                            Enviado
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                <?php else: ?>
                <div class="placeholder-panel">
                    <div class="info-box">
                        <h3>Estructura inicial</h3>
                        <ul class="info-list">
                            <li><i class="fa-solid fa-check"></i><span>Vista independiente del subestándar seleccionada desde la barra lateral o desde la vista principal.</span></li>
                            <li><i class="fa-solid fa-check"></i><span>Pestañas superiores para moverse entre los subestándares sin perder el contexto del estándar 7.</span></li>
                            <li><i class="fa-solid fa-check"></i><span>Preparado para incorporar registros, soportes, responsables, alertas e indicadores en el siguiente paso.</span></li>
                        </ul>
                    </div>
                    <div class="info-box">
                        <h3>Estado</h3>
                        <ul class="info-list">
                            <li><i class="fa-solid fa-circle-info"></i><span>Módulo creado como base visual y de navegación.</span></li>
                            <li><i class="fa-solid fa-database"></i><span>Sin cambios de base de datos en esta fase.</span></li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
<script>
document.querySelectorAll('[data-budget-modal]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const modal = document.getElementById(trigger.dataset.budgetModal);
        if (!modal) return;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        modal.querySelector('.budget-modal-close')?.focus();
    });
});

document.querySelectorAll('.budget-modal').forEach((modal) => {
    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    };
    modal.querySelector('.budget-modal-close')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.budget-modal.open').forEach((modal) => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    });
});

const consumptionCard = document.querySelector('.consumption-card');
const resourcesFormInput = document.querySelector('input[name="accion"][value="guardar_recursos"]');
const resourcesForm = resourcesFormInput ? resourcesFormInput.closest('form') : null;
if (consumptionCard && resourcesForm) {
    resourcesForm.insertAdjacentElement('afterend', consumptionCard);
}

const eppWorkerSelect = document.getElementById('eppWorkerSelect');
if (eppWorkerSelect) {
    const fillWorkerSnapshot = () => {
        const opt = eppWorkerSelect.selectedOptions[0];
        document.getElementById('eppWorkerName').textContent = opt?.dataset.nombre || 'Sin seleccionar';
        document.getElementById('eppWorkerCedula').textContent = opt?.dataset.cedula || 'Sin dato';
        document.getElementById('eppWorkerCargo').textContent = opt?.dataset.cargo || 'Sin dato';
    };
    eppWorkerSelect.addEventListener('change', fillWorkerSnapshot);
    fillWorkerSnapshot();
}

const eppDeliveredBy = document.getElementById('eppDeliveredBy');
const eppDeliveredOtherWrap = document.getElementById('eppDeliveredOtherWrap');
if (eppDeliveredBy && eppDeliveredOtherWrap) {
    const toggleDeliveredOther = () => {
        eppDeliveredOtherWrap.style.display = eppDeliveredBy.value === 'otro' ? '' : 'none';
    };
    eppDeliveredBy.addEventListener('change', toggleDeliveredOther);
    toggleDeliveredOther();
}

const eppCanvas = document.getElementById('eppSignatureCanvas');
const eppSignatureForm = document.getElementById('eppSignatureForm');
if (eppCanvas && eppSignatureForm) {
    const ctx = eppCanvas.getContext('2d');
    let drawing = false;
    let hasSignature = false;

    const resizeCanvas = () => {
        const rect = eppCanvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        eppCanvas.width = Math.max(1, Math.floor(rect.width * ratio));
        eppCanvas.height = Math.max(1, Math.floor(rect.height * ratio));
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';
    };
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    const point = (event) => {
        const rect = eppCanvas.getBoundingClientRect();
        const source = event.touches ? event.touches[0] : event;
        return { x: source.clientX - rect.left, y: source.clientY - rect.top };
    };
    const start = (event) => {
        event.preventDefault();
        drawing = true;
        hasSignature = true;
        const p = point(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    };
    const move = (event) => {
        if (!drawing) return;
        event.preventDefault();
        const p = point(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    };
    const end = () => { drawing = false; };

    eppCanvas.addEventListener('mousedown', start);
    eppCanvas.addEventListener('mousemove', move);
    eppCanvas.addEventListener('mouseup', end);
    eppCanvas.addEventListener('mouseleave', end);
    eppCanvas.addEventListener('touchstart', start, { passive:false });
    eppCanvas.addEventListener('touchmove', move, { passive:false });
    eppCanvas.addEventListener('touchend', end);

    document.getElementById('eppClearSignature')?.addEventListener('click', () => {
        ctx.clearRect(0, 0, eppCanvas.width, eppCanvas.height);
        hasSignature = false;
    });

    eppSignatureForm.addEventListener('submit', (event) => {
        if (!hasSignature) {
            event.preventDefault();
            alert('Por favor firma el recibido antes de continuar.');
            return;
        }
        document.getElementById('eppSignatureInput').value = eppCanvas.toDataURL('image/png');
    });
}
</script>
</body>
</html>
