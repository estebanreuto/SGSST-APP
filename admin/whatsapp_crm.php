<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once '../config/db.php';
require_once '../config/whatsapp_crm_schema.php';

if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_whatsapp_crm_schema($conn);

$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;
$admin_id = (int) $_SESSION['cpanel_admin_id'];
$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';
$current_page = 'whatsapp_crm.php';
$titulo_header = "WhatsApp CRM";
$rol_display = "Super Administrador";

if (empty($_SESSION['whatsapp_crm_token'])) {
    $_SESSION['whatsapp_crm_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['whatsapp_crm_token'];

$etapas = [
    'nuevo' => 'Nuevo',
    'datos_pendientes' => 'Datos pendientes',
    'diagnosticado' => 'Diagnosticado',
    'cotizado' => 'Cotizado',
    'llamada' => 'Llamada',
    'ganado' => 'Ganado',
    'perdido' => 'Perdido',
];

$intereses = [
    'diagnostico' => 'Diagnostico SG-SST',
    'contacto_saliente' => 'Contacto saliente',
    'precio' => 'Precio / planes',
    'llamada' => 'Agendar llamada',
    'soporte' => 'Soporte cliente',
    'propuesta' => 'Propuesta comercial',
];

$prioridades = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function post_value(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function clean_nullable_text($value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function normalize_phone_for_wa(?string $phone): string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 10) {
        return '57' . $digits;
    }
    return $digits;
}

function build_whatsapp_message(array $lead): string
{
    $contacto = trim((string) ($lead['contacto_nombre'] ?? ''));
    $empresa = trim((string) ($lead['empresa_nombre'] ?? ''));
    $etapa = $lead['etapa'] ?? 'nuevo';
    $interes = $lead['interes'] ?? 'diagnostico';
    $trabajadores = isset($lead['trabajadores']) ? (int) $lead['trabajadores'] : 0;
    $riesgo = trim((string) ($lead['nivel_riesgo'] ?? ''));

    $saludo = "Hola " . ($contacto ?: "buen dia");

    if (in_array($etapa, ['nuevo', 'datos_pendientes'], true) && $interes === 'contacto_saliente') {
        return $saludo . ", te saluda PreventWork.\n\nMuchas empresas no tienen problema por falta de voluntad, sino porque el SG-SST termina disperso entre carpetas, correos, firmas y pendientes.\n\nNosotros ayudamos a ordenar esa informacion y dejar seguimiento claro.\n\nQueria preguntarte: actualmente " . ($empresa ? $empresa : "tu empresa") . " ya tiene el SG-SST organizado o les seria util una llamada corta de diagnostico?";
    }

    if (in_array($etapa, ['nuevo', 'datos_pendientes'], true)) {
        return $saludo . ", gracias por escribir a PreventWork.\n\nTe apoyamos a digitalizar y organizar el SG-SST de " . ($empresa ?: "tu empresa") . " con diagnostico inicial, asesoria por WhatsApp y llamada comercial.\n\nPara orientarte bien, me compartes por favor:\n\n1. Numero de trabajadores\n2. Nivel de riesgo\n3. Ciudad\n4. Correo de contacto";
    }

    if ($etapa === 'diagnosticado') {
        $plan = 'plan recomendado';
        if ($trabajadores > 0 && $trabajadores <= 12) {
            $plan = 'plan Pequena Empresa';
        } elseif ($trabajadores > 0 && $trabajadores <= 52) {
            $plan = 'plan Mediana Empresa';
        } elseif ($trabajadores > 0 && $trabajadores <= 102) {
            $plan = 'plan Empresa Grande';
        }

        $detalleRiesgo = $riesgo !== '' ? " y nivel de riesgo " . $riesgo : "";
        return "Perfecto, gracias.\n\nCon " . ($trabajadores > 0 ? $trabajadores . " trabajadores" : "los datos compartidos") . $detalleRiesgo . ", " . ($empresa ?: "la empresa") . " encaja inicialmente en el " . $plan . ".\n\nEste plan permite organizar:\n\n- Diagnostico inicial\n- Gestion documental\n- Firmas digitales\n- Notificaciones\n- Planificacion y seguimiento\n\nPara confirmar alcance y dejar la propuesta bien ajustada, te parece si agendamos una llamada corta de diagnostico?";
    }

    if ($etapa === 'cotizado') {
        return $saludo . ", retomo la informacion enviada sobre PreventWork para " . ($empresa ?: "tu empresa") . ".\n\nQuedo atento para resolver dudas, revisar alcance y confirmar si agendamos la llamada de diagnostico.";
    }

    if ($etapa === 'llamada') {
        return "Perfecto, confirmamos la llamada.\n\nEn ese espacio revisamos:\n\n- Alcance de " . ($empresa ?: "tu empresa") . "\n- Numero de trabajadores\n- Nivel de riesgo\n- Puntos clave para ajustar la propuesta\n\nQuedo atento.";
    }

    return $saludo . ", retomo tu solicitud sobre SG-SST con PreventWork.\n\nQuedo atento para confirmar los datos de la empresa y ayudarte a definir el plan adecuado.";
}

$flash = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $flash = ['tipo' => 'error', 'mensaje' => 'La sesion expiro. Recarga la pagina e intenta de nuevo.'];
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete') {
            $id = (int) post_value('id', 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM whatsapp_prospectos WHERE id = ?");
                $stmt->execute([$id]);
                $flash = ['tipo' => 'success', 'mensaje' => 'Prospecto eliminado.'];
            }
        } else {
            $id = (int) post_value('id', 0);
            $contacto_nombre = clean_nullable_text(post_value('contacto_nombre'));
            $telefono = clean_nullable_text(post_value('telefono'));

            if (!$contacto_nombre || !$telefono) {
                $flash = ['tipo' => 'error', 'mensaje' => 'Contacto y telefono son obligatorios.'];
            } else {
                $data = [
                    'empresa_nombre' => clean_nullable_text(post_value('empresa_nombre')),
                    'contacto_nombre' => $contacto_nombre,
                    'cargo' => clean_nullable_text(post_value('cargo')),
                    'telefono' => $telefono,
                    'email' => clean_nullable_text(post_value('email')),
                    'ciudad' => clean_nullable_text(post_value('ciudad')),
                    'nivel_riesgo' => clean_nullable_text(post_value('nivel_riesgo')),
                    'trabajadores' => post_value('trabajadores') !== '' ? (int) post_value('trabajadores') : null,
                    'interes' => array_key_exists(post_value('interes'), $intereses) ? post_value('interes') : 'diagnostico',
                    'etapa' => array_key_exists(post_value('etapa'), $etapas) ? post_value('etapa') : 'nuevo',
                    'prioridad' => array_key_exists(post_value('prioridad'), $prioridades) ? post_value('prioridad') : 'media',
                    'origen' => clean_nullable_text(post_value('origen')) ?: 'WhatsApp Business',
                    'mensaje_inicial' => clean_nullable_text(post_value('mensaje_inicial')),
                    'notas' => clean_nullable_text(post_value('notas')),
                    'ultimo_contacto' => post_value('ultimo_contacto') ? str_replace('T', ' ', post_value('ultimo_contacto')) . ':00' : null,
                    'proximo_seguimiento' => clean_nullable_text(post_value('proximo_seguimiento')),
                ];

                if ($id > 0) {
                    $stmt = $conn->prepare("
                        UPDATE whatsapp_prospectos
                        SET empresa_nombre = :empresa_nombre,
                            contacto_nombre = :contacto_nombre,
                            cargo = :cargo,
                            telefono = :telefono,
                            email = :email,
                            ciudad = :ciudad,
                            nivel_riesgo = :nivel_riesgo,
                            trabajadores = :trabajadores,
                            interes = :interes,
                            etapa = :etapa,
                            prioridad = :prioridad,
                            origen = :origen,
                            mensaje_inicial = :mensaje_inicial,
                            notas = :notas,
                            ultimo_contacto = :ultimo_contacto,
                            proximo_seguimiento = :proximo_seguimiento
                        WHERE id = :id
                    ");
                    $data['id'] = $id;
                    $stmt->execute($data);
                    $flash = ['tipo' => 'success', 'mensaje' => 'Prospecto actualizado.'];
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO whatsapp_prospectos (
                            empresa_nombre, contacto_nombre, cargo, telefono, email, ciudad,
                            nivel_riesgo, trabajadores, interes, etapa, prioridad, origen,
                            mensaje_inicial, notas, ultimo_contacto, proximo_seguimiento, creado_por_admin_id
                        ) VALUES (
                            :empresa_nombre, :contacto_nombre, :cargo, :telefono, :email, :ciudad,
                            :nivel_riesgo, :trabajadores, :interes, :etapa, :prioridad, :origen,
                            :mensaje_inicial, :notas, :ultimo_contacto, :proximo_seguimiento, :creado_por_admin_id
                        )
                    ");
                    $data['creado_por_admin_id'] = $admin_id;
                    $stmt->execute($data);
                    $flash = ['tipo' => 'success', 'mensaje' => 'Prospecto registrado.'];
                }
            }
        }
    }
}

$stmt = $conn->query("SELECT * FROM whatsapp_prospectos ORDER BY fecha_actualizacion DESC, id DESC");
$prospectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => count($prospectos),
    'nuevos' => 0,
    'seguimientos' => 0,
    'ganados' => 0,
];

$today = date('Y-m-d');
foreach ($prospectos as $lead) {
    if (in_array($lead['etapa'], ['nuevo', 'datos_pendientes'], true)) {
        $stats['nuevos']++;
    }
    if (!empty($lead['proximo_seguimiento']) && $lead['proximo_seguimiento'] <= $today && !in_array($lead['etapa'], ['ganado', 'perdido'], true)) {
        $stats['seguimientos']++;
    }
    if ($lead['etapa'] === 'ganado') {
        $stats['ganados']++;
    }
}

$prospectos_js = [];
foreach ($prospectos as $lead) {
    $lead['telefono_wa'] = normalize_phone_for_wa($lead['telefono']);
    $lead['mensaje_wa'] = build_whatsapp_message($lead);
    $prospectos_js[] = $lead;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp CRM | SG-SST Pro</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #ff8a1f;
            --primary2: #ff7a00;
            --bg1: #edf4fb;
            --bg2: #f7f9fc;
            --card: #ffffff;
            --text: #1f2d3d;
            --muted: #5f6f82;
            --border: #dbe3ec;
            --blue-dark: #1e3a8a;
            --green: #16a34a;
            --red: #dc2626;
        }

        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 24px 32px; flex: 1; max-width: 1440px; margin: 0 auto; width: 100%; box-sizing: border-box; }

        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 16px; }
        .icon-box-std { width: 48px; height: 48px; background: rgba(37, 211, 102, 0.12); color: #128c4a; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(37, 211, 102, 0.24); flex-shrink: 0; }
        .estandar-title { margin: 0; font-size: 1.25rem; color: var(--blue-dark); font-weight: 800; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0; color: var(--muted); font-size: 0.85rem; font-weight: 500; line-height: 1.4; }

        .summary-cards-grid { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .summary-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; position: relative; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .summary-card i.bg { position: absolute; right: -10px; bottom: -20px; font-size: 92px; opacity: 0.06; transform: rotate(-15deg); pointer-events: none; }
        .summary-value { font-size: 1.8rem; font-weight: 800; margin: 0 0 4px; color: var(--blue-dark); }
        .summary-title { font-size: 0.9rem; font-weight: 700; margin: 0; color: var(--text); }
        .summary-desc { font-size: 0.74rem; color: var(--muted); margin: 2px 0 0; }

        .workspace-grid { display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: 18px; align-items: start; }
        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .panel-header { padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .panel-title { margin: 0; color: var(--blue-dark); font-size: 0.98rem; font-weight: 800; }
        .panel-body { padding: 16px; }

        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; }
        .field, .select, .textarea { width: 100%; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px; padding: 10px 12px; box-sizing: border-box; font-family: inherit; color: var(--text); font-size: 0.85rem; }
        .field:focus, .select:focus, .textarea:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.14); }
        .textarea { min-height: 84px; resize: vertical; }
        .toolbar .field { max-width: 280px; }
        .toolbar .select { max-width: 180px; }

        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .form-field.full { grid-column: 1 / -1; }
        .label { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.02em; }

        .btn { border: 0; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-weight: 800; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: all .2s ease; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary2); }
        .btn-secondary { background: #eef2f7; color: #334155; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-whatsapp { background: #25d366; color: white; }
        .btn-whatsapp:hover { background: #1fb356; }
        .btn-danger { background: #fee2e2; color: var(--red); }
        .btn-danger:hover { background: var(--red); color: white; }
        .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }

        .lead-list { display: grid; gap: 12px; }
        .lead-card { border: 1px solid var(--border); border-radius: 12px; padding: 14px; background: #fff; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; }
        .lead-card:hover { border-color: #cbd5e1; box-shadow: 0 6px 16px rgba(15,23,42,.04); }
        .lead-main { min-width: 0; }
        .lead-title { margin: 0 0 4px; font-size: 0.98rem; font-weight: 800; color: var(--blue-dark); }
        .lead-meta { margin: 0; color: var(--muted); font-size: 0.78rem; line-height: 1.5; }
        .lead-notes { margin-top: 10px; padding: 10px; background: #f8fafc; border-radius: 8px; color: #475569; font-size: 0.78rem; line-height: 1.45; white-space: pre-wrap; }
        .lead-actions { display: flex; flex-direction: column; gap: 8px; min-width: 150px; }

        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 6px; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge-stage { background: #eff6ff; color: #1d4ed8; }
        .badge-high { background: #fee2e2; color: #b91c1c; }
        .badge-medium { background: #fffbeb; color: #b45309; }
        .badge-low { background: #dcfce7; color: #166534; }
        .badge-follow { background: #fff7ed; color: #c2410c; }

        .flash { border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-weight: 700; }
        .flash.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .empty-state { padding: 36px 20px; text-align: center; color: #64748b; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; }
        .empty-state i { display: block; font-size: 2.2rem; color: #94a3b8; margin-bottom: 10px; }

        .prompt-box { margin-top: 14px; border: 1px solid #fed7aa; background: #fff7ed; border-radius: 10px; padding: 12px; color: #7c2d12; font-size: .78rem; line-height: 1.45; }
        .prompt-box strong { display: block; margin-bottom: 6px; color: #9a3412; }
        .prompt-output { margin-top: 14px; border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 10px; padding: 12px; color: #1e3a8a; font-size: .78rem; line-height: 1.45; }
        .prompt-output strong { display: block; margin-bottom: 6px; color: #1d4ed8; }
        .prompt-output .textarea { background: #ffffff; min-height: 170px; color: #1f2d3d; }
        .prompt-status { margin: 8px 0 0; color: #475569; font-weight: 700; }

        @media (max-width: 1100px) {
            .workspace-grid { grid-template-columns: 1fr; }
            .summary-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px; }
            .header-actions { flex-direction: column; }
            .summary-cards-grid, .form-grid { grid-template-columns: 1fr; }
            .lead-card { grid-template-columns: 1fr; }
            .lead-actions { flex-direction: row; flex-wrap: wrap; }
            .toolbar .field, .toolbar .select { max-width: none; }
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">
            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-brands fa-whatsapp" style="font-size: 1.6rem;"></i>
                    </div>
                    <div>
                        <h1 class="estandar-title">WhatsApp CRM</h1>
                        <p class="estandar-subtitle">Gestion manual de prospectos que llegan por WhatsApp Business gratis.</p>
                    </div>
                </div>
                <button class="btn btn-secondary" type="button" onclick="resetForm()">
                    <i class="fa-solid fa-plus"></i> Nuevo prospecto
                </button>
            </div>

            <?php if ($flash): ?>
                <div class="flash <?php echo h($flash['tipo']); ?>"><?php echo h($flash['mensaje']); ?></div>
            <?php endif; ?>

            <div class="summary-cards-grid">
                <div class="summary-card">
                    <i class="fa-solid fa-users bg"></i>
                    <p class="summary-value"><?php echo (int) $stats['total']; ?></p>
                    <p class="summary-title">Prospectos</p>
                    <p class="summary-desc">Contactos registrados desde WhatsApp.</p>
                </div>
                <div class="summary-card">
                    <i class="fa-regular fa-message bg"></i>
                    <p class="summary-value"><?php echo (int) $stats['nuevos']; ?></p>
                    <p class="summary-title">En apertura</p>
                    <p class="summary-desc">Nuevos o con datos pendientes.</p>
                </div>
                <div class="summary-card">
                    <i class="fa-regular fa-calendar-check bg"></i>
                    <p class="summary-value"><?php echo (int) $stats['seguimientos']; ?></p>
                    <p class="summary-title">Seguimientos</p>
                    <p class="summary-desc">Vencidos o programados para hoy.</p>
                </div>
                <div class="summary-card">
                    <i class="fa-solid fa-handshake bg"></i>
                    <p class="summary-value"><?php echo (int) $stats['ganados']; ?></p>
                    <p class="summary-title">Ganados</p>
                    <p class="summary-desc">Conversaciones convertidas.</p>
                </div>
            </div>

            <div class="workspace-grid">
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Bandeja comercial</h2>
                        <div class="toolbar">
                            <input class="field" id="searchInput" type="search" placeholder="Buscar empresa, contacto, telefono...">
                            <select class="select" id="stageFilter">
                                <option value="todos">Todas las etapas</option>
                                <?php foreach ($etapas as $key => $label): ?>
                                    <option value="<?php echo h($key); ?>"><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if (!$prospectos): ?>
                            <div class="empty-state">
                                <i class="fa-brands fa-whatsapp"></i>
                                Aun no hay prospectos registrados. Crea el primero desde el formulario.
                            </div>
                        <?php else: ?>
                            <div class="lead-list" id="leadList">
                                <?php foreach ($prospectos as $lead): ?>
                                    <?php
                                        $phoneWa = normalize_phone_for_wa($lead['telefono']);
                                        $waMessage = build_whatsapp_message($lead);
                                        $waUrl = $phoneWa ? 'https://web.whatsapp.com/send?phone=' . $phoneWa . '&text=' . rawurlencode($waMessage) : '#';
                                        $priorityClass = $lead['prioridad'] === 'alta' ? 'badge-high' : ($lead['prioridad'] === 'baja' ? 'badge-low' : 'badge-medium');
                                        $needsFollow = !empty($lead['proximo_seguimiento']) && $lead['proximo_seguimiento'] <= $today && !in_array($lead['etapa'], ['ganado', 'perdido'], true);
                                        $dataText = strtolower(trim(($lead['empresa_nombre'] ?? '') . ' ' . ($lead['contacto_nombre'] ?? '') . ' ' . ($lead['telefono'] ?? '') . ' ' . ($lead['email'] ?? '') . ' ' . ($lead['ciudad'] ?? '')));
                                    ?>
                                    <article class="lead-card filter-item" data-text="<?php echo h($dataText); ?>" data-stage="<?php echo h($lead['etapa']); ?>">
                                        <div class="lead-main">
                                            <div class="btn-row" style="margin-bottom: 8px;">
                                                <span class="badge badge-stage"><?php echo h($etapas[$lead['etapa']] ?? $lead['etapa']); ?></span>
                                                <span class="badge <?php echo h($priorityClass); ?>"><?php echo h($prioridades[$lead['prioridad']] ?? $lead['prioridad']); ?></span>
                                                <?php if ($needsFollow): ?>
                                                    <span class="badge badge-follow"><i class="fa-regular fa-bell"></i> Seguimiento</span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="lead-title"><?php echo h($lead['empresa_nombre'] ?: 'Empresa pendiente'); ?></h3>
                                            <p class="lead-meta">
                                                <strong><?php echo h($lead['contacto_nombre']); ?></strong>
                                                <?php if ($lead['cargo']): ?> · <?php echo h($lead['cargo']); ?><?php endif; ?>
                                                <br>
                                                <?php echo h($lead['telefono']); ?>
                                                <?php if ($lead['email']): ?> · <?php echo h($lead['email']); ?><?php endif; ?>
                                                <br>
                                                <?php echo h($intereses[$lead['interes']] ?? $lead['interes']); ?>
                                                <?php if ($lead['trabajadores']): ?> · <?php echo (int) $lead['trabajadores']; ?> trabajadores<?php endif; ?>
                                                <?php if ($lead['nivel_riesgo']): ?> · Riesgo <?php echo h($lead['nivel_riesgo']); ?><?php endif; ?>
                                                <?php if ($lead['ciudad']): ?> · <?php echo h($lead['ciudad']); ?><?php endif; ?>
                                                <?php if ($lead['proximo_seguimiento']): ?><br>Proximo seguimiento: <?php echo h($lead['proximo_seguimiento']); ?><?php endif; ?>
                                            </p>
                                            <?php if ($lead['notas']): ?>
                                                <div class="lead-notes"><?php echo h($lead['notas']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="lead-actions">
                                            <?php if ($phoneWa): ?>
                                                <a class="btn btn-whatsapp" target="_blank" href="<?php echo h($waUrl); ?>">
                                                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Web
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary" type="button" onclick="copyWhatsAppMessage(<?php echo (int) $lead['id']; ?>)">
                                                <i class="fa-regular fa-message"></i> Mensaje
                                            </button>
                                            <button class="btn btn-secondary" type="button" onclick="editLead(<?php echo (int) $lead['id']; ?>)">
                                                <i class="fa-regular fa-pen-to-square"></i> Editar
                                            </button>
                                            <button class="btn btn-secondary" type="button" onclick="copyPrompt(<?php echo (int) $lead['id']; ?>)">
                                                <i class="fa-regular fa-copy"></i> Prompt
                                            </button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <div class="empty-state" id="emptyState" style="display:none;">
                                <i class="fa-regular fa-folder-open"></i>
                                No hay prospectos con ese filtro.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title" id="formTitle">Nuevo prospecto</h2>
                    </div>
                    <div class="panel-body">
                        <form method="POST" id="leadForm">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" id="leadId" value="">

                            <div class="form-grid">
                                <div class="form-field full">
                                    <label class="label" for="empresa_nombre">Empresa</label>
                                    <input class="field" type="text" id="empresa_nombre" name="empresa_nombre" maxlength="180">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="contacto_nombre">Contacto *</label>
                                    <input class="field" type="text" id="contacto_nombre" name="contacto_nombre" maxlength="140" required>
                                </div>
                                <div class="form-field">
                                    <label class="label" for="cargo">Cargo</label>
                                    <input class="field" type="text" id="cargo" name="cargo" maxlength="120">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="telefono">Telefono *</label>
                                    <input class="field" type="text" id="telefono" name="telefono" maxlength="40" required>
                                </div>
                                <div class="form-field">
                                    <label class="label" for="email">Correo</label>
                                    <input class="field" type="email" id="email" name="email" maxlength="160">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="ciudad">Ciudad</label>
                                    <input class="field" type="text" id="ciudad" name="ciudad" maxlength="120">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="nivel_riesgo">Nivel de riesgo</label>
                                    <input class="field" type="text" id="nivel_riesgo" name="nivel_riesgo" maxlength="30" placeholder="I, II, III, IV o V">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="trabajadores">Trabajadores</label>
                                    <input class="field" type="number" id="trabajadores" name="trabajadores" min="0" step="1">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="interes">Interes</label>
                                    <select class="select" id="interes" name="interes">
                                        <?php foreach ($intereses as $key => $label): ?>
                                            <option value="<?php echo h($key); ?>"><?php echo h($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label class="label" for="etapa">Etapa</label>
                                    <select class="select" id="etapa" name="etapa">
                                        <?php foreach ($etapas as $key => $label): ?>
                                            <option value="<?php echo h($key); ?>"><?php echo h($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label class="label" for="prioridad">Prioridad</label>
                                    <select class="select" id="prioridad" name="prioridad">
                                        <?php foreach ($prioridades as $key => $label): ?>
                                            <option value="<?php echo h($key); ?>" <?php echo $key === 'media' ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label class="label" for="proximo_seguimiento">Proximo seguimiento</label>
                                    <input class="field" type="date" id="proximo_seguimiento" name="proximo_seguimiento">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="ultimo_contacto">Ultimo contacto</label>
                                    <input class="field" type="datetime-local" id="ultimo_contacto" name="ultimo_contacto">
                                </div>
                                <div class="form-field">
                                    <label class="label" for="origen">Origen</label>
                                    <input class="field" type="text" id="origen" name="origen" maxlength="80" value="WhatsApp Business">
                                </div>
                                <div class="form-field full">
                                    <label class="label" for="mensaje_inicial">Mensaje inicial</label>
                                    <textarea class="textarea" id="mensaje_inicial" name="mensaje_inicial"></textarea>
                                </div>
                                <div class="form-field full">
                                    <label class="label" for="notas">Notas comerciales</label>
                                    <textarea class="textarea" id="notas" name="notas"></textarea>
                                </div>
                            </div>

                            <div class="btn-row" style="margin-top: 14px;">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa-regular fa-floppy-disk"></i> Guardar
                                </button>
                                <button class="btn btn-secondary" type="button" onclick="resetForm()">Limpiar</button>
                                <button class="btn btn-danger" type="submit" id="deleteBtn" style="display:none;" onclick="return confirmDelete()" name="action" value="delete">
                                    <i class="fa-regular fa-trash-can"></i> Eliminar
                                </button>
                            </div>
                        </form>

                        <div class="prompt-box">
                            <strong>Uso operativo</strong>
                            Registra aqui lo que pase en el chat Arturo OO o en cualquier conversacion. WhatsApp Web abre el chat con texto sugerido, Mensaje muestra la respuesta lista y Prompt arma contexto para ChatGPT.
                        </div>
                        <div class="prompt-output" id="messageOutput" style="display:none;">
                            <strong>Mensaje sugerido para WhatsApp</strong>
                            <textarea class="textarea" id="messagePreview" readonly></textarea>
                            <p class="prompt-status" id="messageStatus"></p>
                        </div>
                        <div class="prompt-output" id="promptOutput" style="display:none;">
                            <strong>Prompt para ChatGPT</strong>
                            <textarea class="textarea" id="promptPreview" readonly></textarea>
                            <p class="prompt-status" id="promptStatus"></p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <script>
        const leads = <?php echo json_encode($prospectos_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const etapas = <?php echo json_encode($etapas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const intereses = <?php echo json_encode($intereses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        function setValue(id, value) {
            const el = document.getElementById(id);
            if (el) el.value = value ?? '';
        }

        function toDateTimeLocal(value) {
            if (!value) return '';
            return String(value).replace(' ', 'T').slice(0, 16);
        }

        function editLead(id) {
            const lead = leads.find(item => Number(item.id) === Number(id));
            if (!lead) return;

            document.getElementById('formTitle').innerText = 'Editar prospecto #' + String(lead.id).padStart(4, '0');
            setValue('leadId', lead.id);
            setValue('empresa_nombre', lead.empresa_nombre);
            setValue('contacto_nombre', lead.contacto_nombre);
            setValue('cargo', lead.cargo);
            setValue('telefono', lead.telefono);
            setValue('email', lead.email);
            setValue('ciudad', lead.ciudad);
            setValue('nivel_riesgo', lead.nivel_riesgo);
            setValue('trabajadores', lead.trabajadores);
            setValue('interes', lead.interes);
            setValue('etapa', lead.etapa);
            setValue('prioridad', lead.prioridad);
            setValue('proximo_seguimiento', lead.proximo_seguimiento);
            setValue('ultimo_contacto', toDateTimeLocal(lead.ultimo_contacto));
            setValue('origen', lead.origen || 'WhatsApp Business');
            setValue('mensaje_inicial', lead.mensaje_inicial);
            setValue('notas', lead.notas);
            document.getElementById('deleteBtn').style.display = 'inline-flex';
            document.getElementById('leadForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetForm() {
            document.getElementById('leadForm').reset();
            document.getElementById('formTitle').innerText = 'Nuevo prospecto';
            setValue('leadId', '');
            setValue('origen', 'WhatsApp Business');
            document.getElementById('deleteBtn').style.display = 'none';
            const promptOutput = document.getElementById('promptOutput');
            if (promptOutput) promptOutput.style.display = 'none';
            setValue('promptPreview', '');
            const messageOutput = document.getElementById('messageOutput');
            if (messageOutput) messageOutput.style.display = 'none';
            setValue('messagePreview', '');
        }

        function confirmDelete() {
            return confirm('Seguro que deseas eliminar este prospecto? Esta accion no se puede deshacer.');
        }

        async function copyWhatsAppMessage(id) {
            const lead = leads.find(item => Number(item.id) === Number(id));
            if (!lead) return;

            const message = lead.mensaje_wa || '';
            const output = document.getElementById('messageOutput');
            const preview = document.getElementById('messagePreview');
            const status = document.getElementById('messageStatus');
            if (output && preview) {
                preview.value = message;
                output.style.display = 'block';
                output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            try {
                await navigator.clipboard.writeText(message);
                if (status) status.textContent = 'Mensaje copiado. Revisalo antes de enviarlo por WhatsApp.';
            } catch (error) {
                if (status) status.textContent = 'No se pudo copiar automaticamente. Usa el texto visible para copiarlo.';
            }
        }

        async function copyPrompt(id) {
            const lead = leads.find(item => Number(item.id) === Number(id));
            if (!lead) return;
            const scenario = lead.interes === 'contacto_saliente'
                ? 'Escenario B: PreventWork inicia contacto con una empresa o contacto de base de datos.'
                : 'Escenario A: el cliente contacto primero a PreventWork o ya respondio con interes.';

            const prompt = `Actua como asesor comercial y tecnico inicial de PreventWork. Redacta una respuesta breve para WhatsApp segun este contexto:

Empresa: ${lead.empresa_nombre || 'Pendiente'}
Contacto: ${lead.contacto_nombre || 'Pendiente'}
Cargo: ${lead.cargo || 'Pendiente'}
Telefono: ${lead.telefono || 'Pendiente'}
Correo: ${lead.email || 'Pendiente'}
Ciudad: ${lead.ciudad || 'Pendiente'}
Trabajadores: ${lead.trabajadores || 'Pendiente'}
Nivel de riesgo: ${lead.nivel_riesgo || 'Pendiente'}
Interes: ${intereses[lead.interes] || lead.interes || 'Pendiente'}
Etapa: ${etapas[lead.etapa] || lead.etapa || 'Pendiente'}
Mensaje inicial: ${lead.mensaje_inicial || 'Sin mensaje inicial'}
Notas: ${lead.notas || 'Sin notas'}

${scenario}

Base PreventWork:
- Ayudamos a digitalizar y organizar el SG-SST con diagnostico inicial, gestion documental, firmas digitales, notificaciones, planificacion de eventos, seguimiento normativo, asesoria por WhatsApp y llamada comercial.
- Plan Pequena Empresa: hasta 12 trabajadores / usuarios.
- Plan Mediana Empresa: hasta 52 trabajadores / usuarios.
- Plan Empresa Grande: hasta 102 trabajadores / usuarios.
- Valores: Pequena $2.500.000 + IVA, Mediana $3.000.000 + IVA, Grande $3.500.000 + IVA.

Datos para mantener consistencia:
- Diagnostico inicial: empresa, numero de trabajadores, nivel de riesgo, ciudad y correo de contacto.
- Propuesta completa o registro CRM: empresa, numero de trabajadores, nivel de riesgo, ciudad, nombre de contacto, cargo, correo y telefono.
- No pidas los 8 datos completos si el cliente apenas esta preguntando; primero orienta con los 5 datos minimos.

Conexion humana antes de vender:
- Reconoce la realidad del cliente antes de hablar de precio o plan.
- Conecta con necesidades probables: orden documental, evidencias, firmas, fechas, responsables, auditorias, falta de tiempo o informacion dispersa.
- La idea no es llenar al cliente de formatos, sino ayudarle a tener orden, trazabilidad y seguimiento.
- Usa frases naturales como: "Te entiendo", "eso suele pasar", "la llamada nos ayuda a no recomendarte algo a ciegas", "primero revisamos el estado actual".
- Evita sonar como folleto o venta fria.

Formato para WhatsApp:
- Usa parrafos cortos.
- Usa listas numeradas cuando pidas datos.
- Usa vinietas cuando expliques beneficios o componentes.
- Puedes usar maximo 1 emoji funcional por bloque si aporta claridad: 📌 datos, ✅ confirmacion, 📋 documentos, 📞 llamada, 🕒 horario.
- Mantener seriedad profesional; no usar exceso de emojis, signos de admiracion ni mayusculas sostenidas.

Mensajes base:
- Si el cliente nos contacto: "Hola, gracias por escribir a PreventWork. Te apoyamos a digitalizar y organizar el SG-SST de tu empresa con diagnostico inicial, asesoria por WhatsApp y llamada comercial."
- Si nosotros iniciamos contacto: "Hola, te saluda PreventWork. Estamos contactando empresas que necesitan organizar o digitalizar su SG-SST de forma clara, practica y lista para seguimiento."
- Si nosotros iniciamos con conexion humana: "Muchas empresas no tienen problema por falta de voluntad, sino porque el SG-SST termina disperso entre carpetas, correos, firmas y pendientes. Nosotros ayudamos a ordenar esa informacion y dejar seguimiento claro."

Objetivo: responder de forma humana, tecnica y clara; avanzar la conversacion; pedir solo los datos faltantes; recomendar plan solo como orientacion preliminar; proponer llamada de diagnostico si ya hay informacion suficiente. Entrega el mensaje listo para WhatsApp con formato legible. No inventes precios, no prometas cumplimiento legal sin diagnostico y no suenes automatizado.`;

            const output = document.getElementById('promptOutput');
            const preview = document.getElementById('promptPreview');
            const status = document.getElementById('promptStatus');
            if (output && preview) {
                preview.value = prompt;
                output.style.display = 'block';
                output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            try {
                await navigator.clipboard.writeText(prompt);
                if (status) status.textContent = 'Prompt copiado. Tambien queda visible aqui para revisarlo.';
            } catch (error) {
                if (status) status.textContent = 'No se pudo copiar automaticamente. Usa el texto visible para copiarlo.';
            }
        }

        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const stage = document.getElementById('stageFilter').value;
            const items = document.querySelectorAll('.filter-item');
            let visible = 0;

            items.forEach(item => {
                const matchesText = item.dataset.text.includes(search);
                const matchesStage = stage === 'todos' || item.dataset.stage === stage;
                const show = matchesText && matchesStage;
                item.style.display = show ? 'grid' : 'none';
                if (show) visible++;
            });

            const empty = document.getElementById('emptyState');
            if (empty) empty.style.display = visible === 0 && items.length > 0 ? 'block' : 'none';
        }

        document.getElementById('searchInput')?.addEventListener('input', applyFilters);
        document.getElementById('stageFilter')?.addEventListener('change', applyFilters);
    </script>
</body>
</html>
