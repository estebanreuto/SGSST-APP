<?php
require_once 'config/db.php';
require_once 'config/auth.php';

$u = require_auth($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

$roles_nombres = [
    'representante' => 'Representante Legal',
    'sst' => 'Responsable SG-SST',
    'trabajador' => 'Trabajador',
];
$rol_display = $roles_nombres[$usuario_rol] ?? 'Usuario';
$current_page = 'notificaciones.php';

function notif_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function notif_safe_link(?string $link): string
{
    $link = trim((string)$link);
    if ($link === '' || preg_match('/[\x00-\x1F\x7F]/', $link)) {
        return 'notificaciones';
    }

    // Las notificaciones solo pueden llevar a rutas internas de la aplicación.
    if (preg_match('~^(?:https?:)?//~i', $link) || preg_match('~^[a-z][a-z0-9+.-]*:~i', $link)) {
        return 'notificaciones';
    }

    return $link;
}

function notif_presentation(array $notification): array
{
    $text = mb_strtolower(($notification['titulo'] ?? '') . ' ' . ($notification['mensaje'] ?? ''), 'UTF-8');

    if (str_contains($text, 'firma') || str_contains($text, 'aprobada') || str_contains($text, 'epp')) {
        return ['signature', 'Documento y firma', '#2563eb', '#eff6ff'];
    }
    if (str_contains($text, 'tarea') || str_contains($text, 'plan de trabajo') || str_contains($text, 'actividad')) {
        return ['tasks', 'Plan de trabajo', '#f97316', '#fff7ed'];
    }
    if (str_contains($text, 'venc') || str_contains($text, 'alerta') || str_contains($text, 'pendiente')) {
        return ['alert', 'Requiere atención', '#dc2626', '#fef2f2'];
    }
    if (str_contains($text, 'capacit') || str_contains($text, 'curso')) {
        return ['training', 'Capacitación', '#7c3aed', '#f5f3ff'];
    }

    return ['bell', 'Actualización', '#0f766e', '#f0fdfa'];
}

function notif_icon(string $type, int $size = 20): string
{
    $paths = [
        'signature' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/><path d="m15.5 19.5 1.5 1.5 3-3"/>',
        'tasks' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 3v3h6V3M8 11h8M8 15h6"/>',
        'alert' => '<path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'training' => '<path d="m2 10 10-5 10 5-10 5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5M22 10v6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'inbox' => '<path d="M4 4h16l2 12H2L4 4Z"/><path d="M2 16h6a4 4 0 0 0 8 0h6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'read' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    ];

    $path = $paths[$type] ?? $paths['bell'];
    return '<svg aria-hidden="true" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

if (empty($_SESSION['notif_csrf'])) {
    $_SESSION['notif_csrf'] = bin2hex(random_bytes(24));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'marcar_todas') {
    if (!hash_equals($_SESSION['notif_csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('La solicitud no es válida. Actualiza la página e inténtalo de nuevo.');
    }

    $stmt = $conn->prepare('UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0');
    $stmt->execute([$usuario_id]);
    header('Location: notificaciones');
    exit;
}

if (isset($_GET['leer_id'])) {
    $notificationId = filter_var($_GET['leer_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $redirect = 'notificaciones';

    if ($notificationId) {
        $stmt = $conn->prepare('SELECT enlace FROM notificaciones WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$notificationId, $usuario_id]);
        $notificationLink = $stmt->fetchColumn();

        if ($notificationLink !== false) {
            $stmt = $conn->prepare('UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$notificationId, $usuario_id]);
            $redirect = notif_safe_link((string)$notificationLink);
        }
    }

    header('Location: ' . $redirect);
    exit;
}

$summaryStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN leida = 0 THEN 1 ELSE 0 END) AS no_leidas,
        SUM(CASE WHEN leida = 1 THEN 1 ELSE 0 END) AS leidas,
        SUM(CASE WHEN DATE(fecha_creacion) = CURDATE() THEN 1 ELSE 0 END) AS hoy
    FROM notificaciones
    WHERE usuario_id = ?
");
$summaryStmt->execute([$usuario_id]);
$resumen = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$total_notificaciones = (int)($resumen['total'] ?? 0);
$total_no_leidas = (int)($resumen['no_leidas'] ?? 0);
$total_leidas = (int)($resumen['leidas'] ?? 0);
$total_hoy = (int)($resumen['hoy'] ?? 0);
$porcentaje_leido = $total_notificaciones > 0 ? (int)round(($total_leidas / $total_notificaciones) * 100) : 100;

$stmt = $conn->prepare('SELECT id, titulo, mensaje, enlace, leida, fecha_creacion FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT 50');
$stmt->execute([$usuario_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones | PreventWork</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff7500;
            --primary-soft: #fff4e9;
            --blue-dark: #173b8f;
            --text: #10234a;
            --muted: #60718d;
            --border: #dbe4ef;
            --surface: #ffffff;
            --page: #f3f7fc;
            --success: #059669;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            color: var(--text);
            background: linear-gradient(180deg, #edf4fb 0%, #f7f9fc 100%);
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
        }

        button, input { font: inherit; }
        .main-wrapper {
            width: calc(100% - 260px);
            min-height: 100vh;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            transition: margin-left .28s ease, width .28s ease;
        }
        .content-area {
            width: 100%;
            max-width: 1480px;
            margin: 0 auto;
            padding: 18px clamp(16px, 2vw, 30px) 42px;
        }

        .page-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: 8px 0 14px;
        }
        .hero-copy { display: flex; align-items: center; gap: 13px; min-width: 0; }
        .hero-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: var(--primary);
            background: var(--primary-soft);
            border: 1px solid #fed7aa;
            border-radius: 10px;
        }
        .page-hero h1 { margin: 0 0 4px; color: var(--blue-dark); font-size: 1.24rem; letter-spacing: -.025em; }
        .page-hero p { margin: 0; color: var(--muted); line-height: 1.45; }
        .hero-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex: 0 0 auto;
            padding: 8px 12px;
            border: 1px solid #fed7aa;
            border-radius: 999px;
            color: #c2410c;
            background: #fff7ed;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .hero-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 0 4px rgba(255,117,0,.12); }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 11px;
            margin-bottom: 14px;
        }
        .metric-card {
            --metric-accent: var(--primary);
            position: relative;
            min-height: 96px;
            overflow: hidden;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            box-shadow: 0 8px 22px rgba(15, 35, 70, .035);
        }
        .metric-card.blue { --metric-accent: #2563eb; }
        .metric-card.green { --metric-accent: #059669; }
        .metric-card.violet { --metric-accent: #7c3aed; }
        .metric-label { position: relative; z-index: 1; display: block; margin-bottom: 7px; color: #5d6e89; font-size: .64rem; font-weight: 800; text-transform: uppercase; }
        .metric-value { position: relative; z-index: 1; display: block; color: var(--metric-accent); font-size: 1.22rem; line-height: 1; }
        .metric-detail { position: relative; z-index: 1; display: block; margin-top: 6px; color: #70809a; font-size: .68rem; }
        .metric-watermark {
            position: absolute;
            right: 8px;
            bottom: 0;
            color: var(--metric-accent);
            opacity: .075;
            transform: rotate(-8deg) scale(2.3);
            transform-origin: bottom right;
            transition: opacity .2s ease, transform .2s ease;
        }
        .metric-card:hover .metric-watermark { opacity: .12; transform: rotate(-3deg) scale(2.45); }

        .notification-panel {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: var(--surface);
            box-shadow: 0 12px 30px rgba(23, 59, 143, .045);
        }
        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
        }
        .panel-head h2 { margin: 0 0 4px; color: var(--blue-dark); font-size: 1rem; letter-spacing: -.015em; }
        .panel-head p { margin: 0; color: var(--muted); font-size: .73rem; }
        .mark-all-form { flex: 0 0 auto; }
        .mark-all-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            padding: 8px 12px;
            border: 1px solid #cbd8e8;
            border-radius: 8px;
            color: #31527c;
            background: #fff;
            font-size: .7rem;
            font-weight: 800;
            cursor: pointer;
            transition: .2s ease;
        }
        .mark-all-btn:hover:not(:disabled) { color: var(--primary); border-color: #fdba74; background: #fffaf5; transform: translateY(-1px); }
        .mark-all-btn:disabled { color: #9aabc0; background: #f7f9fc; cursor: not-allowed; }

        .filter-bar {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            background: #fbfdff;
            border-bottom: 1px solid var(--border);
        }
        .search-box { position: relative; max-width: 520px; }
        .search-box > svg { position: absolute; left: 12px; top: 50%; color: #8da0b8; transform: translateY(-50%); }
        .search-box input {
            width: 100%;
            height: 38px;
            padding: 0 38px;
            border: 1px solid #d5e0ed;
            border-radius: 8px;
            outline: none;
            color: #243b61;
            background: #fff;
            font-size: .73rem;
            transition: .2s ease;
        }
        .search-box input:focus { border-color: #93b4eb; box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
        .search-clear {
            position: absolute;
            right: 8px;
            top: 50%;
            display: none;
            width: 25px;
            height: 25px;
            padding: 0;
            border: 0;
            border-radius: 6px;
            color: #64748b;
            background: #eef3f8;
            cursor: pointer;
            transform: translateY(-50%);
        }
        .search-clear.visible { display: grid; place-items: center; }
        .filter-pills { display: flex; align-items: center; gap: 6px; }
        .filter-pill {
            min-height: 34px;
            padding: 7px 10px;
            border: 1px solid #d7e1ed;
            border-radius: 8px;
            color: #60718d;
            background: #fff;
            font-size: .68rem;
            font-weight: 800;
            cursor: pointer;
        }
        .filter-pill:hover { border-color: #fdba74; color: #c2410c; }
        .filter-pill.active { border-color: #fdba74; color: #c2410c; background: #fff4e9; }
        .filter-pill span { margin-left: 4px; color: inherit; opacity: .72; }

        .results-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 18px;
            color: #76869d;
            background: #fff;
            border-bottom: 1px solid #e8eef5;
            font-size: .66rem;
            font-weight: 600;
        }
        .read-progress { display: flex; align-items: center; gap: 8px; }
        .progress-track { width: 84px; height: 4px; overflow: hidden; border-radius: 99px; background: #e2e8f0; }
        .progress-track span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #ff8a1f, #22c55e); }

        .notification-list { display: flex; flex-direction: column; }
        .notification-card {
            --notification-accent: #0f766e;
            --notification-soft: #f0fdfa;
            position: relative;
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: center;
            gap: 13px;
            min-height: 90px;
            padding: 13px 18px;
            border-bottom: 1px solid #e6edf5;
            background: #fff;
            transition: background .18s ease, transform .18s ease;
        }
        .notification-card:last-child { border-bottom: 0; }
        .notification-card:hover { z-index: 1; background: #fbfdff; }
        .notification-card.unread { background: linear-gradient(90deg, var(--notification-soft) 0, #fff 35%); }
        .notification-card.unread::before { content: ''; position: absolute; inset: 13px auto 13px 0; width: 3px; border-radius: 0 5px 5px 0; background: var(--notification-accent); }
        .notification-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            color: var(--notification-accent);
            background: var(--notification-soft);
            border: 1px solid color-mix(in srgb, var(--notification-accent) 20%, white);
            border-radius: 9px;
        }
        .notification-body { min-width: 0; }
        .notification-title-row { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .notification-title { margin: 0; overflow: hidden; color: #112b5b; font-size: .79rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .unread-dot { width: 7px; height: 7px; flex: 0 0 auto; border-radius: 50%; background: var(--notification-accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--notification-accent) 12%, white); }
        .notification-message { margin: 4px 0 7px; overflow: hidden; color: #5e708d; font-size: .71rem; line-height: 1.45; text-overflow: ellipsis; white-space: nowrap; }
        .notification-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; color: #8190a6; font-size: .63rem; font-weight: 600; }
        .notification-category { color: var(--notification-accent); font-weight: 800; }
        .meta-separator { width: 3px; height: 3px; border-radius: 50%; background: #c5d0dd; }
        .notification-actions { display: flex; align-items: center; gap: 10px; padding-left: 12px; }
        .status-badge { padding: 5px 8px; border-radius: 999px; color: #64748b; background: #f1f5f9; font-size: .58rem; font-weight: 800; text-transform: uppercase; }
        .notification-card.unread .status-badge { color: var(--notification-accent); background: var(--notification-soft); }
        .open-notification {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 34px;
            padding: 7px 9px;
            border: 1px solid #d7e2ef;
            border-radius: 8px;
            color: #31527c;
            background: #fff;
            text-decoration: none;
            font-size: .67rem;
            font-weight: 800;
            transition: .18s ease;
        }
        .open-notification:hover { color: var(--notification-accent); border-color: color-mix(in srgb, var(--notification-accent) 35%, white); transform: translateX(2px); }

        .empty-state, .no-results {
            display: grid;
            place-items: center;
            min-height: 260px;
            padding: 34px 20px;
            text-align: center;
        }
        .empty-visual {
            position: relative;
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin-bottom: 13px;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 13px;
        }
        .empty-state h3, .no-results h3 { margin: 0 0 6px; color: var(--blue-dark); font-size: .9rem; }
        .empty-state p, .no-results p { max-width: 430px; margin: 0; color: var(--muted); font-size: .72rem; line-height: 1.5; }
        .no-results { display: none; }
        .no-results.visible { display: grid; }

        @media (max-width: 1024px) {
            .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .notification-card { grid-template-columns: 42px minmax(0, 1fr); }
            .notification-actions { grid-column: 2; padding-left: 0; justify-content: space-between; }
        }
        @media (max-width: 768px) {
            body { display: block; }
            .main-wrapper { width: 100%; margin-left: 0; }
            .content-area { padding: 14px 12px 34px; }
            .page-hero { align-items: flex-start; }
            .hero-status { margin-top: 4px; }
            .filter-bar { grid-template-columns: 1fr; }
            .search-box { max-width: none; }
            .filter-pills { overflow-x: auto; padding-bottom: 2px; }
            .filter-pill { flex: 0 0 auto; }
        }
        @media (max-width: 560px) {
            .page-hero { flex-direction: column; gap: 10px; }
            .hero-status { margin-left: 56px; }
            .metrics-grid { gap: 8px; }
            .metric-card { min-height: 90px; padding: 11px; }
            .panel-head { align-items: flex-start; flex-direction: column; padding: 14px; }
            .mark-all-form, .mark-all-btn { width: 100%; }
            .filter-bar, .results-meta { padding-left: 14px; padding-right: 14px; }
            .results-meta { align-items: flex-start; flex-direction: column; }
            .notification-card { grid-template-columns: 36px minmax(0, 1fr); gap: 10px; padding: 13px 14px; }
            .notification-icon { width: 36px; height: 36px; }
            .notification-message { display: -webkit-box; white-space: normal; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
            .status-badge { display: none; }
            .open-notification { flex: 1; justify-content: space-between; }
        }
        @media (max-width: 390px) {
            .metrics-grid { grid-template-columns: 1fr; }
            .hero-status { margin-left: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; animation: none !important; }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">
            <section class="page-hero" aria-labelledby="notifications-title">
                <div class="hero-copy">
                    <span class="hero-icon"><?php echo notif_icon('bell', 22); ?></span>
                    <div>
                        <h1 id="notifications-title">Centro de notificaciones</h1>
                        <p>Revisa tareas, firmas, alertas y novedades de tu gestión SG-SST en un solo lugar.</p>
                    </div>
                </div>
                <span class="hero-status"><?php echo $total_no_leidas; ?> pendiente<?php echo $total_no_leidas === 1 ? '' : 's'; ?></span>
            </section>

            <section class="metrics-grid" aria-label="Resumen de notificaciones">
                <article class="metric-card">
                    <span class="metric-label">Total recibidas</span>
                    <strong class="metric-value"><?php echo $total_notificaciones; ?></strong>
                    <span class="metric-detail">Historial completo registrado.</span>
                    <span class="metric-watermark"><?php echo notif_icon('inbox', 22); ?></span>
                </article>
                <article class="metric-card blue">
                    <span class="metric-label">Sin revisar</span>
                    <strong class="metric-value"><?php echo $total_no_leidas; ?></strong>
                    <span class="metric-detail">Novedades que requieren lectura.</span>
                    <span class="metric-watermark"><?php echo notif_icon('bell', 22); ?></span>
                </article>
                <article class="metric-card green">
                    <span class="metric-label">Revisadas</span>
                    <strong class="metric-value"><?php echo $total_leidas; ?></strong>
                    <span class="metric-detail"><?php echo $porcentaje_leido; ?>% de lectura acumulada.</span>
                    <span class="metric-watermark"><?php echo notif_icon('read', 22); ?></span>
                </article>
                <article class="metric-card violet">
                    <span class="metric-label">Recibidas hoy</span>
                    <strong class="metric-value"><?php echo $total_hoy; ?></strong>
                    <span class="metric-detail">Movimientos del día actual.</span>
                    <span class="metric-watermark"><?php echo notif_icon('clock', 22); ?></span>
                </article>
            </section>

            <section class="notification-panel" aria-labelledby="notification-list-title">
                <header class="panel-head">
                    <div>
                        <h2 id="notification-list-title">Bandeja de actividad</h2>
                        <p>Se muestran primero las novedades más recientes. La bandeja carga hasta 50 registros.</p>
                    </div>
                    <form class="mark-all-form" method="post" action="notificaciones">
                        <input type="hidden" name="accion" value="marcar_todas">
                        <input type="hidden" name="csrf" value="<?php echo notif_h($_SESSION['notif_csrf']); ?>">
                        <button class="mark-all-btn" type="submit" <?php echo $total_no_leidas === 0 ? 'disabled' : ''; ?>>
                            <?php echo notif_icon('check', 16); ?>
                            Marcar todo como leído
                        </button>
                    </form>
                </header>

                <?php if (!empty($notificaciones)): ?>
                    <div class="filter-bar">
                        <label class="search-box" for="notification-search">
                            <?php echo notif_icon('search', 17); ?>
                            <input id="notification-search" type="search" autocomplete="off" placeholder="Buscar por título, mensaje o categoría...">
                            <button class="search-clear" id="search-clear" type="button" aria-label="Limpiar búsqueda">×</button>
                        </label>
                        <div class="filter-pills" role="group" aria-label="Filtrar notificaciones">
                            <button class="filter-pill active" type="button" data-filter="all">Todas <span><?php echo count($notificaciones); ?></span></button>
                            <button class="filter-pill" type="button" data-filter="unread">Sin leer <span><?php echo $total_no_leidas; ?></span></button>
                            <button class="filter-pill" type="button" data-filter="read">Leídas <span><?php echo $total_leidas; ?></span></button>
                        </div>
                    </div>

                    <div class="results-meta">
                        <span id="results-count">Mostrando <?php echo count($notificaciones); ?> notificación<?php echo count($notificaciones) === 1 ? '' : 'es'; ?></span>
                        <span class="read-progress">
                            Progreso de lectura
                            <span class="progress-track" aria-hidden="true"><span style="width: <?php echo $porcentaje_leido; ?>%"></span></span>
                            <?php echo $porcentaje_leido; ?>%
                        </span>
                    </div>

                    <div class="notification-list" id="notification-list">
                        <?php foreach ($notificaciones as $n): ?>
                            <?php
                            [$type, $category, $accent, $soft] = notif_presentation($n);
                            $isUnread = (int)$n['leida'] === 0;
                            $searchText = mb_strtolower(($n['titulo'] ?? '') . ' ' . ($n['mensaje'] ?? '') . ' ' . $category, 'UTF-8');
                            ?>
                            <article
                                class="notification-card <?php echo $isUnread ? 'unread' : 'read'; ?>"
                                data-status="<?php echo $isUnread ? 'unread' : 'read'; ?>"
                                data-search="<?php echo notif_h($searchText); ?>"
                                style="--notification-accent: <?php echo notif_h($accent); ?>; --notification-soft: <?php echo notif_h($soft); ?>;"
                            >
                                <span class="notification-icon"><?php echo notif_icon($type, 19); ?></span>
                                <div class="notification-body">
                                    <div class="notification-title-row">
                                        <h3 class="notification-title" title="<?php echo notif_h($n['titulo']); ?>"><?php echo notif_h($n['titulo']); ?></h3>
                                        <?php if ($isUnread): ?><span class="unread-dot" title="Sin leer"></span><?php endif; ?>
                                    </div>
                                    <p class="notification-message" title="<?php echo notif_h($n['mensaje']); ?>"><?php echo notif_h($n['mensaje']); ?></p>
                                    <div class="notification-meta">
                                        <span class="notification-category"><?php echo notif_h($category); ?></span>
                                        <span class="meta-separator"></span>
                                        <time datetime="<?php echo notif_h(date('c', strtotime($n['fecha_creacion']))); ?>">
                                            <?php echo notif_h(date('d/m/Y · h:i a', strtotime($n['fecha_creacion']))); ?>
                                        </time>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <span class="status-badge"><?php echo $isUnread ? 'Nueva' : 'Leída'; ?></span>
                                    <a class="open-notification" href="notificaciones?leer_id=<?php echo (int)$n['id']; ?>">
                                        <?php echo $isUnread ? 'Revisar' : 'Abrir'; ?>
                                        <?php echo notif_icon('arrow', 15); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="no-results" id="no-results">
                        <div>
                            <span class="empty-visual"><?php echo notif_icon('search', 25); ?></span>
                            <h3>No encontramos coincidencias</h3>
                            <p>Prueba con otra palabra o cambia el filtro para volver a ver tus notificaciones.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div>
                            <span class="empty-visual"><?php echo notif_icon('inbox', 27); ?></span>
                            <h3>Tu bandeja está al día</h3>
                            <p>Cuando tengas tareas, firmas, alertas o novedades del SG-SST aparecerán organizadas en este espacio.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php if (!empty($notificaciones)): ?>
    <script>
    (function () {
        var searchInput = document.getElementById('notification-search');
        var clearButton = document.getElementById('search-clear');
        var filterButtons = Array.prototype.slice.call(document.querySelectorAll('.filter-pill'));
        var cards = Array.prototype.slice.call(document.querySelectorAll('.notification-card'));
        var resultsCount = document.getElementById('results-count');
        var noResults = document.getElementById('no-results');
        var list = document.getElementById('notification-list');
        var currentFilter = 'all';

        function normalize(value) {
            return (value || '').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function applyFilters() {
            var query = normalize(searchInput.value.trim());
            var visible = 0;

            cards.forEach(function (card) {
                var matchesStatus = currentFilter === 'all' || card.dataset.status === currentFilter;
                var matchesSearch = !query || normalize(card.dataset.search).indexOf(query) !== -1;
                var show = matchesStatus && matchesSearch;
                card.hidden = !show;
                if (show) visible += 1;
            });

            resultsCount.textContent = 'Mostrando ' + visible + (visible === 1 ? ' notificación' : ' notificaciones');
            noResults.classList.toggle('visible', visible === 0);
            list.hidden = visible === 0;
            clearButton.classList.toggle('visible', searchInput.value.length > 0);
        }

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                currentFilter = button.dataset.filter;
                filterButtons.forEach(function (item) { item.classList.toggle('active', item === button); });
                applyFilters();
            });
        });

        searchInput.addEventListener('input', applyFilters);
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.focus();
            applyFilters();
        });
    }());
    </script>
    <?php endif; ?>
</body>
</html>
