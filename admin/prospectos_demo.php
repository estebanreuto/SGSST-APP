<?php
session_start();
require_once '../config/db.php';
require_once '../config/demo_schema.php';

if (!isset($_SESSION['cpanel_admin_id'])) {
    header('Location: login.php');
    exit;
}

$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;
ensure_demo_prospectos_schema($conn);

if (empty($_SESSION['demo_admin_csrf'])) {
    $_SESSION['demo_admin_csrf'] = bin2hex(random_bytes(24));
}

$allowedStates = ['nuevo', 'contactado', 'calificado', 'convertido', 'descartado'];
$generatedLink = is_array($_SESSION['demo_generated_link'] ?? null) ? $_SESSION['demo_generated_link'] : [];
unset($_SESSION['demo_generated_link']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (hash_equals((string)$_SESSION['demo_admin_csrf'], $csrf)) {
        $id = (int)($_POST['id'] ?? 0);
        $action = (string)($_POST['action'] ?? 'update_followup');
        if ($id > 0 && in_array($action, ['approve_access', 'regenerate_access'], true)) {
            $days = (int)($_POST['access_days'] ?? 7);
            if (!in_array($days, [1, 3, 7, 15, 30], true)) {
                $days = 7;
            }
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));
            $stmtUpdate = $conn->prepare(<<<'SQL'
                UPDATE demo_prospectos
                SET acceso_estado = 'aprobado', acceso_token_hash = ?, acceso_token_sufijo = ?,
                    acceso_generado_en = NOW(), acceso_expira_en = ?, acceso_revocado_en = NULL,
                    acceso_decidido_en = NOW(), acceso_decidido_por = ?
                WHERE id = ?
            SQL);
            $stmtUpdate->execute([$tokenHash, substr($plainToken, -10), $expiresAt, (int)$_SESSION['cpanel_admin_id'], $id]);
            $leadStmt = $conn->prepare('SELECT nombre_completo, empresa FROM demo_prospectos WHERE id = ?');
            $leadStmt->execute([$id]);
            $leadInfo = $leadStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $_SESSION['demo_generated_link'] = [
                'url' => demo_app_url('demo_pem?access=' . $plainToken),
                'nombre' => (string)($leadInfo['nombre_completo'] ?? 'Prospecto'),
                'empresa' => (string)($leadInfo['empresa'] ?? ''),
                'expira' => $expiresAt,
            ];
            header('Location: prospectos_demo.php?enlace=generado#prospecto-' . $id);
            exit;
        }
        if ($id > 0 && $action === 'reject_access') {
            $stmtUpdate = $conn->prepare("UPDATE demo_prospectos SET acceso_estado='rechazado', acceso_token_hash=NULL, acceso_token_sufijo=NULL, acceso_expira_en=NULL, acceso_revocado_en=NOW(), acceso_decidido_en=NOW(), acceso_decidido_por=? WHERE id=?");
            $stmtUpdate->execute([(int)$_SESSION['cpanel_admin_id'], $id]);
            header('Location: prospectos_demo.php?acceso=rechazado#prospecto-' . $id);
            exit;
        }
        if ($id > 0 && $action === 'revoke_access') {
            $stmtUpdate = $conn->prepare("UPDATE demo_prospectos SET acceso_estado='revocado', acceso_token_hash=NULL, acceso_token_sufijo=NULL, acceso_expira_en=NULL, acceso_revocado_en=NOW(), acceso_decidido_en=NOW(), acceso_decidido_por=? WHERE id=?");
            $stmtUpdate->execute([(int)$_SESSION['cpanel_admin_id'], $id]);
            header('Location: prospectos_demo.php?acceso=revocado#prospecto-' . $id);
            exit;
        }
        if ($id > 0 && $action === 'update_followup') {
            $state = (string)($_POST['estado'] ?? 'nuevo');
            $notes = trim((string)($_POST['notas'] ?? ''));
            if (in_array($state, $allowedStates, true)) {
                $stmtUpdate = $conn->prepare('UPDATE demo_prospectos SET estado = ?, notas = ? WHERE id = ?');
                $stmtUpdate->execute([$state, $notes !== '' ? $notes : null, $id]);
                header('Location: prospectos_demo.php?actualizado=1#prospecto-' . $id);
                exit;
            }
        }
    }
}

$stateFilter = (string)($_GET['estado'] ?? 'todos');
$search = trim((string)($_GET['q'] ?? ''));
$where = [];
$params = [];
if (in_array($stateFilter, $allowedStates, true)) {
    $where[] = 'estado = ?';
    $params[] = $stateFilter;
}
if ($search !== '') {
    $where[] = '(nombre_completo LIKE ? OR empresa LIKE ? OR email LIKE ? OR telefono LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term, $term);
}

$sql = 'SELECT * FROM demo_prospectos';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY FIELD(estado,'nuevo','calificado','contactado','convertido','descartado'), ultima_visita DESC LIMIT 150";
$stmtLeads = $conn->prepare($sql);
$stmtLeads->execute($params);
$leads = $stmtLeads->fetchAll(PDO::FETCH_ASSOC);

$counts = array_fill_keys($allowedStates, 0);
foreach ($conn->query('SELECT estado, COUNT(*) total FROM demo_prospectos GROUP BY estado')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($counts[$row['estado']])) {
        $counts[$row['estado']] = (int)$row['total'];
    }
}
$total = array_sum($counts);
$accessCounts = array_fill_keys(['pendiente', 'aprobado', 'rechazado', 'revocado'], 0);
foreach ($conn->query('SELECT acceso_estado, COUNT(*) total FROM demo_prospectos GROUP BY acceso_estado')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($accessCounts[$row['acceso_estado']])) {
        $accessCounts[$row['acceso_estado']] = (int)$row['total'];
    }
}

function prospect_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function prospect_state_label(string $state): string
{
    return match ($state) {
        'contactado' => 'Contactado',
        'calificado' => 'Calificado',
        'convertido' => 'Convertido',
        'descartado' => 'Descartado',
        default => 'Nuevo',
    };
}

function prospect_access_label(string $state): string
{
    return match ($state) {
        'aprobado' => 'Acceso aprobado',
        'rechazado' => 'Acceso rechazado',
        'revocado' => 'Acceso revocado',
        default => 'Pendiente de aprobación',
    };
}

$current_page = 'prospectos_demo.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prospectos Demo PEM | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--primary:#ff8a1f;--primary2:#ff7a00;--bg1:#edf4fb;--bg2:#f7f9fc;--card:#fff;--text:#1f2d3d;--muted:#64748b;--border:#dbe3ec;--blue-dark:#1e3a8a}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;overflow-x:hidden;background:linear-gradient(180deg,var(--bg1),var(--bg2));color:var(--text);font-family:Inter,sans-serif}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh;transition:all .3s ease}.content-area{width:min(1450px,100%);margin:auto;padding:18px 28px 40px}.prospect-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:8px 0 14px}.prospect-title{display:flex;align-items:center;gap:11px}.prospect-title>i{width:42px;height:42px;display:grid;place-items:center;border:1px solid #fed7aa;border-radius:10px;background:#fff3e8;color:var(--primary2)}.prospect-title h1{margin:0;color:var(--blue-dark);font-size:1.12rem}.prospect-title p{margin:4px 0 0;color:var(--muted);font-size:.68rem}.prospect-head>a{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border:1px solid #fed7aa;border-radius:8px;background:#fff7ed;color:#c2410c;text-decoration:none;font-size:.62rem;font-weight:800}.flash{margin-bottom:11px;padding:9px 11px;border:1px solid #bbf7d0;border-radius:9px;background:#ecfdf5;color:#047857;font-size:.66rem;font-weight:700}.prospect-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;margin-bottom:12px}.prospect-metric{--metric:#ff7a00;position:relative;overflow:hidden;min-height:94px;padding:11px;border:1px solid var(--border);border-radius:10px;background:#fff}.prospect-metric.blue{--metric:#2563eb}.prospect-metric.green{--metric:#059669}.prospect-metric.violet{--metric:#7c3aed}.prospect-metric.gray{--metric:#64748b}.prospect-metric span,.prospect-metric strong,.prospect-metric small{position:relative;z-index:1;display:block}.prospect-metric span{color:#64748b;font-size:.56rem;font-weight:800;text-transform:uppercase}.prospect-metric strong{margin-top:7px;color:var(--blue-dark);font-size:1.3rem}.prospect-metric small{margin-top:4px;color:#64748b;font-size:.54rem}.prospect-metric i{position:absolute;right:5px;bottom:-8px;color:var(--metric);opacity:.08;font-size:3.1rem;transform:rotate(-8deg)}.prospect-tools{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;padding:9px;border:1px solid var(--border);border-radius:10px;background:#fff}.prospect-tools form{display:flex;align-items:center;gap:7px;width:100%}.prospect-search{width:min(430px,100%);height:36px;display:flex;align-items:center;gap:7px;padding:0 9px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#94a3b8}.prospect-search input{width:100%;border:0;outline:0;background:transparent;font:inherit;font-size:.63rem}.prospect-tools select{height:36px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;padding:0 28px 0 9px;color:#334155;font:inherit;font-size:.61rem}.prospect-tools button{height:36px;border:0;border-radius:8px;background:#102a67;color:#fff;padding:0 11px;font:inherit;font-size:.61rem;font-weight:800}.prospect-result{color:#64748b;font-size:.58rem;white-space:nowrap}.prospect-list{display:grid;gap:8px}.prospect-card{position:relative;overflow:hidden;border:1px solid var(--border);border-radius:10px;background:#fff;box-shadow:0 7px 18px rgba(15,23,42,.03)}.prospect-card summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:38px minmax(180px,1.25fr) minmax(160px,1fr) minmax(135px,.75fr) auto 18px;gap:11px;align-items:center;min-height:76px;padding:10px 12px}.prospect-card summary::-webkit-details-marker{display:none}.prospect-avatar{width:38px;height:38px;display:grid;place-items:center;border-radius:9px;background:#fff3e8;color:#c2410c;font-size:.75rem;font-weight:800}.prospect-main,.prospect-data{min-width:0}.prospect-main strong,.prospect-main span,.prospect-data strong,.prospect-data span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.prospect-main strong{color:#102a67;font-size:.68rem}.prospect-main span,.prospect-data span{margin-top:3px;color:#64748b;font-size:.55rem}.prospect-data strong{color:#334155;font-size:.61rem}.prospect-state{padding:5px 8px;border-radius:99px;background:#fff7ed;color:#c2410c;font-size:.5rem;font-weight:850;text-transform:uppercase}.prospect-state.contactado{background:#eff6ff;color:#1d4ed8}.prospect-state.calificado{background:#f5f3ff;color:#6d28d9}.prospect-state.convertido{background:#ecfdf5;color:#047857}.prospect-state.descartado{background:#f1f5f9;color:#64748b}.prospect-card summary>i{color:#94a3b8;font-size:.55rem;transition:transform .2s}.prospect-card[open] summary>i{transform:rotate(180deg)}.prospect-detail{display:grid;grid-template-columns:1fr 1fr minmax(280px,.8fr);gap:9px;padding:0 12px 12px 61px}.prospect-box{padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}.prospect-box span{display:block;color:#94a3b8;font-size:.5rem;font-weight:800;text-transform:uppercase}.prospect-box p{margin:5px 0 0;color:#334155;font-size:.59rem;line-height:1.45}.prospect-contact{display:flex;gap:6px;margin-top:8px}.prospect-contact a{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border:1px solid #dbe3ec;border-radius:7px;background:#fff;color:#2563eb;text-decoration:none;font-size:.55rem;font-weight:800}.prospect-form{display:grid;gap:7px}.prospect-form select,.prospect-form textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:8px;color:#334155;font:inherit;font-size:.58rem}.prospect-form textarea{min-height:58px;resize:vertical}.prospect-form button{min-height:33px;border:0;border-radius:7px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;font:inherit;font-size:.58rem;font-weight:800}.prospect-empty{padding:50px 20px;border:1px dashed #cbd5e1;border-radius:10px;background:#fff;text-align:center;color:#64748b;font-size:.7rem}@media(max-width:1180px){.prospect-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.prospect-card summary{grid-template-columns:38px 1fr 1fr auto 18px}.prospect-card summary .prospect-data:nth-of-type(3){grid-column:2/4}.prospect-detail{grid-template-columns:1fr 1fr;padding-left:12px}.prospect-detail .prospect-form{grid-column:1/-1}}@media(max-width:768px){body{display:block}.main-wrapper{margin-left:0;width:100%}.content-area{padding:14px 12px 32px}.prospect-head{align-items:flex-start;flex-direction:column}.prospect-head>a{width:100%;justify-content:center}.prospect-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.prospect-tools,.prospect-tools form{align-items:stretch;flex-direction:column}.prospect-search{width:100%}.prospect-result{white-space:normal}.prospect-card summary{grid-template-columns:38px 1fr 18px}.prospect-card summary .prospect-data,.prospect-card summary .prospect-state{grid-column:2}.prospect-card summary>i{grid-column:3;grid-row:1}.prospect-detail{grid-template-columns:1fr}}@media(max-width:440px){.prospect-metrics{grid-template-columns:1fr}}
    </style>
    <link rel="stylesheet" href="../assets/admin-demo-access.css?v=20260716-1">
</head>
<body>
<?php include '../components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include '../components/header.php'; ?>
    <div class="content-area">
        <section class="prospect-head">
            <div class="prospect-title"><i class="fa-solid fa-rocket"></i><div><h1>Prospectos de la demo PEM</h1><p>Personas que solicitaron el recorrido comercial desde la página pública.</p></div></div>
            <a href="../demo" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir demo pública</a>
        </section>

        <?php if (isset($_GET['actualizado'])): ?><div class="flash"><i class="fa-solid fa-circle-check"></i> El seguimiento del prospecto fue actualizado.</div><?php endif; ?>
        <?php if (isset($_GET['acceso'])): ?><div class="flash"><i class="fa-solid fa-shield-halved"></i> El estado de acceso fue actualizado correctamente.</div><?php endif; ?>
        <?php if ($generatedLink): ?>
            <section class="demo-link-flash">
                <div>
                    <strong><i class="fa-solid fa-link"></i> Enlace privado listo para <?php echo prospect_h($generatedLink['nombre'] ?? 'el prospecto'); ?></strong>
                    <small><?php echo prospect_h($generatedLink['empresa'] ?? ''); ?> · Vence el <?php echo prospect_h(date('d/m/Y H:i', strtotime((string)($generatedLink['expira'] ?? 'now')))); ?>. Este enlace solo se muestra ahora; puedes regenerarlo cuando lo necesites.</small>
                    <div class="demo-link-copy"><input id="generatedDemoLink" readonly value="<?php echo prospect_h($generatedLink['url'] ?? ''); ?>"><button class="demo-copy-btn" type="button" data-copy-demo-link><i class="fa-regular fa-copy"></i> Copiar enlace</button></div>
                </div>
                <i class="fa-solid fa-circle-check" style="color:#16a34a;font-size:1.35rem"></i>
            </section>
        <?php endif; ?>

        <section class="prospect-metrics">
            <article class="prospect-metric"><span>Total prospectos</span><strong><?php echo $total; ?></strong><small>Registros acumulados</small><i class="fa-solid fa-users"></i></article>
            <article class="prospect-metric blue"><span>Esperando decisión</span><strong><?php echo $accessCounts['pendiente']; ?></strong><small>Solicitudes sin resolver</small><i class="fa-solid fa-hourglass-half"></i></article>
            <article class="prospect-metric violet"><span>Accesos aprobados</span><strong><?php echo $accessCounts['aprobado']; ?></strong><small>Enlaces privados vigentes</small><i class="fa-solid fa-key"></i></article>
            <article class="prospect-metric green"><span>Convertidos</span><strong><?php echo $counts['convertido']; ?></strong><small>Clientes logrados</small><i class="fa-solid fa-circle-check"></i></article>
            <article class="prospect-metric gray"><span>Contactados</span><strong><?php echo $counts['contactado']; ?></strong><small>Gestión realizada</small><i class="fa-solid fa-headset"></i></article>
        </section>

        <section class="prospect-tools">
            <form method="get">
                <label class="prospect-search"><i class="fa-solid fa-magnifying-glass"></i><input name="q" value="<?php echo prospect_h($search); ?>" placeholder="Buscar empresa, nombre, correo o teléfono"></label>
                <select name="estado"><option value="todos">Todos los estados</option><?php foreach ($allowedStates as $state): ?><option value="<?php echo prospect_h($state); ?>" <?php echo $stateFilter === $state ? 'selected' : ''; ?>><?php echo prospect_h(prospect_state_label($state)); ?></option><?php endforeach; ?></select>
                <button type="submit">Filtrar</button>
            </form>
            <span class="prospect-result"><?php echo count($leads); ?> resultado(s)</span>
        </section>

        <?php if (!$leads): ?>
            <div class="prospect-empty"><i class="fa-solid fa-inbox"></i><p>No hay prospectos que coincidan con el filtro.</p></div>
        <?php else: ?>
            <section class="prospect-list">
                <?php foreach ($leads as $lead): ?>
                    <?php
                        $phoneDigits = preg_replace('/\D+/', '', (string)$lead['telefono']);
                        $whatsappDigits = str_starts_with($phoneDigits, '57') ? $phoneDigits : '57' . ltrim($phoneDigits, '0');
                    ?>
                    <?php $accessState = (string)($lead['acceso_estado'] ?? 'pendiente'); ?>
                    <details id="prospecto-<?php echo (int)$lead['id']; ?>" class="prospect-card" <?php echo ($lead['estado'] === 'nuevo' || $accessState === 'pendiente') ? 'open' : ''; ?>>
                        <summary>
                            <span class="prospect-avatar"><?php echo prospect_h(mb_strtoupper(mb_substr((string)$lead['nombre_completo'], 0, 1))); ?></span>
                            <div class="prospect-main"><strong><?php echo prospect_h($lead['nombre_completo']); ?></strong><span><?php echo prospect_h($lead['empresa']); ?> · <?php echo prospect_h($lead['cargo'] ?: 'Cargo no indicado'); ?></span></div>
                            <div class="prospect-data"><strong><?php echo prospect_h($lead['email']); ?></strong><span><?php echo prospect_h($lead['telefono']); ?> · <?php echo prospect_h($lead['ciudad'] ?: 'Sin ciudad'); ?></span></div>
                            <div class="prospect-data"><strong><?php echo (int)$lead['cantidad_trabajadores']; ?> trabajadores</strong><span><?php echo (int)$lead['paginas_vistas']; ?> páginas vistas · <?php echo prospect_h(date('d/m/Y H:i', strtotime((string)$lead['ultima_visita']))); ?></span></div>
                            <span class="prospect-state <?php echo prospect_h($lead['estado']); ?>"><?php echo prospect_h(prospect_state_label((string)$lead['estado'])); ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </summary>
                        <div class="prospect-detail">
                            <div class="prospect-box"><span>Interés comercial</span><p><?php echo prospect_h($lead['interes']); ?> · Solicitó acceso el <?php echo prospect_h(date('d/m/Y \a \l\a\s H:i', strtotime((string)$lead['primera_visita']))); ?>.</p><div class="prospect-contact"><a href="mailto:<?php echo prospect_h($lead['email']); ?>?subject=Demo%20PreventWork%20PEM"><i class="fa-solid fa-envelope"></i> Correo</a><?php if ($phoneDigits !== ''): ?><a href="https://wa.me/<?php echo prospect_h($whatsappDigits); ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a><?php endif; ?></div></div>
                            <div class="prospect-box"><span>Solicitud y notificación</span><p>Origen: formulario público. <?php if (!empty($lead['notificacion_enviada_en'])): ?>Aviso interno enviado el <?php echo prospect_h(date('d/m/Y H:i', strtotime((string)$lead['notificacion_enviada_en']))); ?>.<?php elseif (!empty($lead['notificacion_error'])): ?>La solicitud fue guardada, pero falló el aviso interno: <?php echo prospect_h($lead['notificacion_error']); ?><?php else: ?>Notificación interna pendiente.<?php endif; ?></p></div>

                            <section class="prospect-access">
                                <div class="prospect-access-head">
                                    <div>
                                        <strong><i class="fa-solid fa-shield-halved"></i> Control de acceso a la demo</strong>
                                        <p>
                                            <?php if ($accessState === 'aprobado'): ?>
                                                Enlace privado activo<?php echo !empty($lead['acceso_token_sufijo']) ? ' · termina en ' . prospect_h($lead['acceso_token_sufijo']) : ''; ?><?php echo !empty($lead['acceso_expira_en']) ? ' · vence el ' . prospect_h(date('d/m/Y H:i', strtotime((string)$lead['acceso_expira_en']))) : ''; ?>.
                                            <?php elseif ($accessState === 'rechazado'): ?>
                                                La solicitud fue rechazada. Puedes aprobarla más adelante si cambia la decisión comercial.
                                            <?php elseif ($accessState === 'revocado'): ?>
                                                El enlace anterior fue revocado y dejó de funcionar inmediatamente.
                                            <?php else: ?>
                                                El prospecto todavía no puede entrar. Aprueba la solicitud para crear un enlace privado.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="access-state <?php echo prospect_h($accessState); ?>"><?php echo prospect_h(prospect_access_label($accessState)); ?></span>
                                </div>
                                <form class="prospect-access-form" method="post">
                                    <input type="hidden" name="csrf" value="<?php echo prospect_h($_SESSION['demo_admin_csrf']); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$lead['id']; ?>">
                                    <label>Vigencia del nuevo enlace
                                        <select name="access_days">
                                            <option value="1">1 día</option>
                                            <option value="3">3 días</option>
                                            <option value="7" selected>7 días</option>
                                            <option value="15">15 días</option>
                                            <option value="30">30 días</option>
                                        </select>
                                    </label>
                                    <?php if ($accessState === 'aprobado'): ?>
                                        <button class="access-primary" type="submit" name="action" value="regenerate_access"><i class="fa-solid fa-rotate"></i> Regenerar enlace</button>
                                        <button class="access-danger" type="submit" name="action" value="revoke_access" onclick="return confirm('¿Revocar este acceso privado? El enlace dejará de funcionar de inmediato.')"><i class="fa-solid fa-ban"></i> Revocar</button>
                                    <?php else: ?>
                                        <button class="access-primary" type="submit" name="action" value="approve_access"><i class="fa-solid fa-link"></i> Aprobar y generar enlace</button>
                                        <?php if ($accessState === 'pendiente'): ?>
                                            <button class="access-danger" type="submit" name="action" value="reject_access" onclick="return confirm('¿Rechazar esta solicitud de demo?')"><i class="fa-solid fa-xmark"></i> Rechazar</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </form>
                            </section>

                            <form class="prospect-form" method="post"><input type="hidden" name="csrf" value="<?php echo prospect_h($_SESSION['demo_admin_csrf']); ?>"><input type="hidden" name="id" value="<?php echo (int)$lead['id']; ?>"><input type="hidden" name="action" value="update_followup"><select name="estado"><?php foreach ($allowedStates as $state): ?><option value="<?php echo prospect_h($state); ?>" <?php echo $lead['estado'] === $state ? 'selected' : ''; ?>><?php echo prospect_h(prospect_state_label($state)); ?></option><?php endforeach; ?></select><textarea name="notas" placeholder="Notas del seguimiento comercial"><?php echo prospect_h($lead['notas']); ?></textarea><button type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar seguimiento</button></form>
                        </div>
                    </details>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
<script>
document.querySelector('[data-copy-demo-link]')?.addEventListener('click', async function () {
    const input = document.getElementById('generatedDemoLink');
    if (!input || !input.value) return;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(input.value);
        } else {
            input.focus();
            input.select();
            document.execCommand('copy');
        }
        this.innerHTML = '<i class="fa-solid fa-check"></i> Enlace copiado';
    } catch (error) {
        input.focus();
        input.select();
        this.textContent = 'Seleccionado: presiona Ctrl + C';
    }
});
</script>
</body>
</html>
