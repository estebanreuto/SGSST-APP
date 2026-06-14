<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar4_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar4_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);
$empresa_id = (int)($usuario_info['empresa_id'] ?? 0);
$anio = max(2020, min(2100, (int)($_GET['anio'] ?? date('Y'))));
$plan = estandar4_get_or_create_plan($conn, $empresa_id, $anio);

$stmt = $conn->prepare("SELECT * FROM estandar4_actividades WHERE plan_id=? ORDER BY orden, id");
$stmt->execute([$plan['id']]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM estandar4_seguimientos WHERE plan_id=? ORDER BY fecha_max_ejecucion, id");
$stmt->execute([$plan['id']]);
$seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT id, nombre, apellido, cedula, rol FROM usuarios
    WHERE empresa_id=? AND rol IN ('sst','representante') AND activo=1
    ORDER BY rol DESC, nombre
");
$stmt->execute([$empresa_id]);
$responsables = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM actividades_capacitacion a
    WHERE a.empresa_id=? AND YEAR(a.fecha_inicio)=?
      AND NOT EXISTS (
          SELECT 1 FROM estandar4_actividades e
          WHERE e.plan_id=? AND e.actividad_capacitacion_id=a.id
      )
");
$stmt->execute([$empresa_id, $anio, $plan['id']]);
$capacitaciones_pendientes = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT nombre, apellido, cedula FROM usuarios WHERE id=?");
$stmt->execute([$plan['sst_id'] ?: 0]);
$firmante_sst = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$stmt->execute([$plan['representante_id'] ?: 0]);
$firmante_representante = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$metricas = estandar4_metricas($actividades);
$meta = (int)$plan['meta_cumplimiento'];
$cumple_meta = $metricas['cumplimiento'] >= $meta;
$editable = $usuario_rol === 'sst' && $plan['estado'] !== 'firmado';
$meses = [
    1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR', 5 => 'MAY', 6 => 'JUN',
    7 => 'JUL', 8 => 'AGO', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'
];
$estado_labels = [
    'borrador' => ['Borrador', 'draft'],
    'pendiente_firma' => ['Pendiente de gerencia', 'pending'],
    'firmado' => ['Aprobado y firmado', 'signed'],
];
$estado_plan = $estado_labels[$plan['estado']] ?? $estado_labels['borrador'];
$current_page = 'estandar4.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 4 | Plan Anual de Trabajo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#ff8a1f;--primary2:#ff7a00;--blue-dark:#1e3a8a;--bg1:#edf4fb;--bg2:#f7f9fc;--card:#fff;--text:#1f2d3d;--muted:#64748b;--border:#dbe3ec;--green:#16a34a;--red:#dc2626;--violet:#7c3aed}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,sans-serif;background:linear-gradient(180deg,var(--bg1),var(--bg2));color:var(--text);font-size:.82rem}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}.content-area{width:100%;padding:30px clamp(24px,2.5vw,44px) 60px}
        button,input,textarea,select{font:inherit}.page-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.head-copy{display:flex;align-items:center;gap:13px}.head-icon{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;color:var(--primary);background:#fff3e8;border:1px solid #fed7aa;font-size:1.05rem}.head-copy h1{margin:0;color:var(--blue-dark);font-size:1.15rem}.head-copy p{margin:4px 0 0;color:var(--muted);font-size:.76rem}.head-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
        .year-select,.btn{height:38px;border-radius:7px;border:1px solid var(--border);background:#fff;padding:0 12px;color:#334155;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;cursor:pointer;white-space:nowrap}.btn:hover{border-color:#fdba74;color:var(--primary2)}.btn-primary{background:var(--primary2);border-color:var(--primary2);color:#fff}.btn-primary:hover{background:#ea6b00;color:#fff}.btn-blue{background:var(--blue-dark);border-color:var(--blue-dark);color:#fff}.btn-blue:hover{color:#fff;background:#172d6b}.btn-danger{color:var(--red)}.btn[disabled]{opacity:.5;cursor:not-allowed}.status-plan{padding:6px 9px;border-radius:6px;font-size:.66rem;font-weight:800;text-transform:uppercase}.status-plan.draft{background:#f1f5f9;color:#475569}.status-plan.pending{background:#fff7ed;color:#c2410c}.status-plan.signed{background:#dcfce7;color:#15803d}
        .alert{padding:11px 14px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:9px;font-weight:650}.alert.ok{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0}.alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.pending-banner{padding:12px 15px;border-radius:8px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:12px}
        .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}.summary-card{background:#fff;border:1px solid var(--border);border-radius:8px;padding:13px;display:flex;align-items:center;gap:11px;min-width:0}.summary-icon{width:34px;height:34px;border-radius:8px;display:grid;place-items:center;flex:none}.summary-icon.orange{color:#c2410c;background:#fff7ed}.summary-icon.green{color:#15803d;background:#dcfce7}.summary-icon.violet{color:#6d28d9;background:#ede9fe}.summary-icon.blue{color:#1d4ed8;background:#dbeafe}.summary-value{font-size:1.3rem;font-weight:800;line-height:1;color:var(--blue-dark)}.summary-label{font-size:.68rem;color:var(--muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .control-panel{background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px}.control-top{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:10px}.control-top h2{margin:0;font-size:.86rem;color:var(--blue-dark)}.meta-form{display:flex;align-items:center;gap:7px}.meta-form input{width:62px;height:34px;border:1px solid var(--border);border-radius:6px;text-align:center;font-weight:800}.progress-track{height:8px;background:#e2e8f0;border-radius:99px;overflow:hidden}.progress-fill{height:100%;background:linear-gradient(90deg,#38bdf8,#22c55e);border-radius:inherit;transition:width .3s}.legend-months{display:flex;gap:8px 14px;flex-wrap:wrap;margin-top:9px;color:var(--muted);font-size:.65rem}.legend-months strong{color:#334155}.legend-dot{width:7px;height:7px;border-radius:2px;display:inline-block;margin-right:4px}.dot-p{background:#818cf8}.dot-e{background:#22c55e}.dot-r{background:#f59e0b}
        .section{background:#fff;border:1px solid var(--border);border-radius:8px;margin-top:12px;overflow:hidden}.section-head{padding:12px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}.section-head h2{margin:0;color:var(--blue-dark);font-size:.88rem}.section-head p{margin:3px 0 0;color:var(--muted);font-size:.68rem}.matrix-scroll{overflow-x:auto}.matrix{width:100%;min-width:1500px;border-collapse:separate;border-spacing:0}.matrix th{background:#f8fafc;color:#64748b;font-size:.6rem;text-transform:uppercase;padding:9px 7px;border-bottom:1px solid var(--border);text-align:center}.matrix td{padding:9px 7px;border-bottom:1px solid #eef2f7;vertical-align:middle}.matrix tr:last-child td{border-bottom:0}.matrix .sticky{position:sticky;z-index:2;background:#fff;text-align:left}.matrix th.sticky{background:#f8fafc;z-index:3}.matrix .col-topic{left:0;width:145px;min-width:145px}.matrix .col-activity{left:145px;width:220px;min-width:220px}.matrix .col-responsible{left:365px;width:165px;min-width:165px;box-shadow:5px 0 10px rgba(15,23,42,.04)}.cell-title{font-weight:700;color:#1e293b}.cell-muted{color:var(--muted);font-size:.65rem;margin-top:3px}.month-cell{text-align:center;width:78px;min-width:78px}.month-toggle{display:grid;grid-template-columns:repeat(3,20px);gap:2px;justify-content:center}.month-state{width:20px;height:20px;padding:0;border:0;border-radius:5px;background:#f1f5f9;color:#94a3b8;font-size:.58rem;font-weight:800;cursor:pointer}.month-state.p.active{background:#e0e7ff;color:#4338ca}.month-state.e.active{background:#dcfce7;color:#15803d}.month-state.r.active{background:#fef3c7;color:#b45309}.month-state:disabled{cursor:default}.month-date{font-size:.51rem;color:#94a3b8;margin-top:4px;min-height:9px}.row-actions{display:flex;gap:4px}.icon-btn{width:29px;height:29px;border:1px solid var(--border);background:#fff;border-radius:6px;color:#64748b;cursor:pointer;display:grid;place-items:center}.icon-btn:hover{color:var(--primary2);border-color:#fdba74}.icon-btn.delete:hover{color:var(--red);border-color:#fca5a5}.empty-state{padding:42px 20px;text-align:center;color:var(--muted)}.empty-state i{font-size:1.7rem;color:#cbd5e1;margin-bottom:10px}
        .tracking-scroll{overflow-x:auto}.tracking{width:100%;min-width:1180px;border-collapse:collapse}.tracking th{padding:9px;background:#f8fafc;color:#64748b;font-size:.58rem;text-transform:uppercase;border-bottom:1px solid var(--border)}.tracking td{padding:9px;border-bottom:1px solid #eef2f7;vertical-align:top}.tracking tr:last-child td{border-bottom:0}.tracking-main{font-weight:700;color:#334155}.tracking-text{color:#64748b;line-height:1.4;max-width:180px}.tracking-date{white-space:nowrap;color:#475569}.tracking-actions{display:flex;gap:5px}
        .signatures{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px}.signature-card{border:1px solid var(--border);border-radius:8px;padding:13px;text-align:center;background:#fbfdff}.signature-card h3{margin:0 0 4px;font-size:.8rem;color:var(--blue-dark)}.signature-card p{margin:0 0 10px;color:var(--muted);font-size:.65rem}.signature-pad{height:120px;border:1px dashed #cbd5e1;border-radius:7px;background:#fff;overflow:hidden;margin-bottom:9px}.signature-pad canvas{display:block;width:100%;height:100%;touch-action:none;cursor:crosshair}.signature-image{width:100%;height:120px;object-fit:contain;background:#fff;border:1px dashed #cbd5e1;border-radius:7px}.signature-name{margin-top:7px;font-weight:700;color:#334155}.signature-actions{display:flex;justify-content:center;gap:7px;margin-top:9px}.signature-actions .btn{height:33px;font-size:.68rem}.signature-placeholder{height:120px;border:1px dashed #cbd5e1;border-radius:7px;display:grid;place-items:center;color:#94a3b8;background:#fff}.pdf-bar{padding:12px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc}
        .modal{position:fixed;inset:0;background:rgba(15,23,42,.62);z-index:12000;display:none;align-items:center;justify-content:center;padding:20px}.modal.open{display:flex}.modal-card{width:min(680px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:10px;box-shadow:0 24px 70px rgba(15,23,42,.25)}.modal-head{padding:15px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}.modal-head h2{margin:0;font-size:.95rem;color:var(--blue-dark)}.modal-close{border:0;background:#f1f5f9;width:30px;height:30px;border-radius:6px;color:#64748b;cursor:pointer}.modal-body{padding:18px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:.67rem;font-weight:800;color:#475569;text-transform:uppercase}.field input,.field textarea,.field select{width:100%;border:1px solid #cbd5e1;border-radius:7px;padding:10px;color:#334155;outline:0}.field textarea{min-height:80px;resize:vertical}.field input:focus,.field textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(255,138,31,.1)}.months-selector{display:grid;grid-template-columns:repeat(6,1fr);gap:6px}.month-check input{display:none}.month-check span{display:flex;height:32px;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:6px;font-size:.64rem;font-weight:800;color:#64748b;cursor:pointer}.month-check input:checked+span{background:#e0e7ff;border-color:#a5b4fc;color:#3730a3}.modal-foot{padding:13px 18px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;background:#f8fafc}
        @media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}.content-area{padding:18px 14px 45px}.page-head{align-items:flex-start;flex-direction:column}.head-actions{width:100%;justify-content:flex-start}.summary-grid{grid-template-columns:1fr 1fr}.control-top{align-items:flex-start;flex-direction:column}.signatures{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.months-selector{grid-template-columns:repeat(4,1fr)}.pending-banner{align-items:flex-start;flex-direction:column}}@media(max-width:420px){.summary-grid{grid-template-columns:1fr}.head-actions .btn{flex:1}.year-select{width:100%}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <div class="page-head">
            <div class="head-copy">
                <div class="head-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                <div>
                    <h1>4. Plan Anual de Trabajo</h1>
                    <p>Programa, ejecuta y documenta las actividades del SG-SST durante la vigencia.</p>
                </div>
            </div>
            <div class="head-actions">
                <span class="status-plan <?php echo $estado_plan[1]; ?>"><?php echo $estado_plan[0]; ?></span>
                <select class="year-select" onchange="location.href='estandar4.php?anio='+this.value" aria-label="Seleccionar vigencia">
                    <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 4; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $anio ? 'selected' : ''; ?>>Vigencia <?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <?php if ($editable): ?>
                    <form action="procesar_estandar4.php" method="post">
                        <input type="hidden" name="accion" value="importar_capacitaciones">
                        <input type="hidden" name="anio" value="<?php echo $anio; ?>">
                        <button class="btn" type="submit" title="Incorporar actividades del Estándar 3">
                            <i class="fa-solid fa-link"></i> Importar capacitaciones
                            <?php if ($capacitaciones_pendientes): ?><strong><?php echo $capacitaciones_pendientes; ?></strong><?php endif; ?>
                        </button>
                    </form>
                    <button class="btn btn-primary" type="button" onclick="openActivityModal()"><i class="fa-solid fa-plus"></i> Nueva actividad</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert <?php echo ($_GET['tipo'] ?? '') === 'error' ? 'error' : 'ok'; ?>">
                <i class="fa-solid <?php echo ($_GET['tipo'] ?? '') === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if ($usuario_rol === 'representante' && $plan['estado'] === 'pendiente_firma'): ?>
            <div class="pending-banner">
                <div><strong>El plan anual está listo para tu revisión.</strong><br>Verifica la programación y firma al final del documento.</div>
                <i class="fa-solid fa-file-signature"></i>
            </div>
        <?php endif; ?>

        <div class="summary-grid">
            <div class="summary-card"><div class="summary-icon blue"><i class="fa-solid fa-list-check"></i></div><div><div class="summary-value"><?php echo count($actividades); ?></div><div class="summary-label">Actividades del plan</div></div></div>
            <div class="summary-card"><div class="summary-icon orange"><i class="fa-regular fa-calendar"></i></div><div><div class="summary-value"><?php echo $metricas['programadas']; ?></div><div class="summary-label">Programaciones pendientes</div></div></div>
            <div class="summary-card"><div class="summary-icon green"><i class="fa-solid fa-check"></i></div><div><div class="summary-value"><?php echo $metricas['ejecutadas']; ?></div><div class="summary-label">Ejecuciones registradas</div></div></div>
            <div class="summary-card"><div class="summary-icon violet"><i class="fa-solid fa-chart-line"></i></div><div><div class="summary-value"><?php echo $metricas['cumplimiento']; ?>%</div><div class="summary-label"><?php echo $cumple_meta ? 'Meta anual alcanzada' : 'Avance frente a la meta'; ?></div></div></div>
        </div>

        <section class="control-panel">
            <div class="control-top">
                <div><h2>Indicadores automáticos</h2><div class="cell-muted">Medición anual entre actividades programadas, ejecutadas y reprogramadas.</div></div>
                <form class="meta-form" action="procesar_estandar4.php" method="post">
                    <input type="hidden" name="accion" value="guardar_meta"><input type="hidden" name="anio" value="<?php echo $anio; ?>">
                    <label for="meta">Meta anual</label>
                    <input id="meta" name="meta_cumplimiento" type="number" min="1" max="100" value="<?php echo $meta; ?>" <?php echo !$editable ? 'disabled' : ''; ?>>
                    <span>%</span>
                    <?php if ($editable): ?><button class="icon-btn" title="Guardar meta"><i class="fa-solid fa-check"></i></button><?php endif; ?>
                </form>
            </div>
            <div class="progress-track"><div class="progress-fill" style="width:<?php echo min(100, $metricas['cumplimiento']); ?>%"></div></div>
            <div class="legend-months">
                <span><i class="legend-dot dot-p"></i>Programado</span><span><i class="legend-dot dot-e"></i>Ejecutado</span><span><i class="legend-dot dot-r"></i>Reprogramado</span>
                <?php foreach ($meses as $numero => $mes): $m = $metricas['por_mes'][$numero]; ?>
                    <span><strong><?php echo $mes; ?>:</strong> P<?php echo $m['P']; ?> · E<?php echo $m['E']; ?> · R<?php echo $m['R']; ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <div><h2>Plan de Trabajo Anual SG-SST · <?php echo $anio; ?></h2><p>Usa P para programado, E para ejecutado y R para reprogramado.</p></div>
                <?php if ($editable): ?><button class="btn" type="button" onclick="openActivityModal()"><i class="fa-solid fa-plus"></i> Actividad</button><?php endif; ?>
            </div>
            <?php if (!$actividades): ?>
                <div class="empty-state"><i class="fa-regular fa-calendar-plus"></i><h3>El plan aún no tiene actividades</h3><p>Agrega una actividad o importa las capacitaciones programadas en el Estándar 3.</p></div>
            <?php else: ?>
                <div class="matrix-scroll">
                    <table class="matrix">
                        <thead><tr>
                            <th class="sticky col-topic">Tema</th><th class="sticky col-activity">Actividad</th><th class="sticky col-responsible">Responsable</th>
                            <?php foreach ($meses as $mes): ?><th><?php echo $mes; ?></th><?php endforeach; ?>
                            <th>Acciones</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($actividades as $actividad):
                            $programacion = estandar4_decode_programacion($actividad['programacion_json']);
                            $activity_payload = htmlspecialchars(json_encode([
                                'id' => (int)$actividad['id'],
                                'tema' => $actividad['tema'],
                                'actividad' => $actividad['actividad'],
                                'responsable' => $actividad['responsable'],
                                'observaciones' => $actividad['observaciones'],
                                'meses' => array_map('intval', array_keys($programacion)),
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td class="sticky col-topic"><div class="cell-title"><?php echo htmlspecialchars($actividad['tema']); ?></div><?php if ($actividad['actividad_capacitacion_id']): ?><div class="cell-muted"><i class="fa-solid fa-link"></i> Estándar 3</div><?php endif; ?></td>
                                <td class="sticky col-activity"><div class="cell-title"><?php echo htmlspecialchars($actividad['actividad']); ?></div><?php if ($actividad['observaciones']): ?><div class="cell-muted"><?php echo htmlspecialchars($actividad['observaciones']); ?></div><?php endif; ?></td>
                                <td class="sticky col-responsible"><div class="cell-title"><?php echo htmlspecialchars($actividad['responsable']); ?></div></td>
                                <?php foreach ($meses as $numero => $mes):
                                    $dato = $programacion[(string)$numero] ?? [];
                                    $estado = is_array($dato) ? ($dato['estado'] ?? '') : $dato;
                                    $fecha = is_array($dato) ? ($dato['fecha'] ?? '') : '';
                                ?>
                                    <td class="month-cell">
                                        <div class="month-toggle">
                                            <?php foreach (['P' => 'p', 'E' => 'e', 'R' => 'r'] as $letra => $clase): ?>
                                                <form action="procesar_estandar4.php" method="post">
                                                    <input type="hidden" name="accion" value="actualizar_mes"><input type="hidden" name="anio" value="<?php echo $anio; ?>">
                                                    <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>"><input type="hidden" name="mes" value="<?php echo $numero; ?>">
                                                    <input type="hidden" name="estado" value="<?php echo $estado === $letra ? '' : $letra; ?>">
                                                    <button class="month-state <?php echo $clase . ($estado === $letra ? ' active' : ''); ?>" title="<?php echo $estado === $letra ? 'Quitar estado' : ['P'=>'Programado','E'=>'Ejecutado','R'=>'Reprogramado'][$letra]; ?>" <?php echo !$editable ? 'disabled' : ''; ?>><?php echo $letra; ?></button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="month-date"><?php echo $fecha ? date('d/m/y', strtotime($fecha)) : ''; ?></div>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <div class="row-actions">
                                        <?php if ($editable): ?>
                                            <button class="icon-btn" type="button" title="Editar actividad" data-activity="<?php echo $activity_payload; ?>" onclick="editActivity(this)"><i class="fa-solid fa-pen"></i></button>
                                            <form action="procesar_estandar4.php" method="post" onsubmit="return confirm('¿Eliminar esta actividad del plan?')">
                                                <input type="hidden" name="accion" value="eliminar_actividad"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                                                <button class="icon-btn delete" title="Eliminar actividad"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php else: ?><span class="cell-muted">Solo lectura</span><?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="section">
            <div class="section-head">
                <div><h2>Seguimiento del Responsable SST</h2><p>Registra análisis, acciones propuestas, responsables y vencimientos.</p></div>
                <?php if ($editable): ?><button class="btn btn-blue" type="button" onclick="openTrackingModal()"><i class="fa-solid fa-plus"></i> Nuevo seguimiento</button><?php endif; ?>
            </div>
            <?php if (!$seguimientos): ?>
                <div class="empty-state"><i class="fa-solid fa-chart-simple"></i><h3>Sin seguimientos registrados</h3><p>Las acciones de mejora y sus resultados aparecerán en esta sección.</p></div>
            <?php else: ?>
                <div class="tracking-scroll"><table class="tracking"><thead><tr><th>Periodo</th><th>Análisis del resultado</th><th>Acción propuesta</th><th>Responsable</th><th>Fecha máxima</th><th>Fecha seguimiento</th><th>Responsable seguimiento</th><th>Resultado</th><th>Acciones</th></tr></thead><tbody>
                <?php foreach ($seguimientos as $seguimiento):
                    $tracking_payload = htmlspecialchars(json_encode($seguimiento, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td class="tracking-main"><?php echo htmlspecialchars($seguimiento['periodo']); ?></td>
                        <td class="tracking-text"><?php echo nl2br(htmlspecialchars($seguimiento['analisis_resultado'])); ?></td>
                        <td class="tracking-text"><?php echo nl2br(htmlspecialchars($seguimiento['accion_propuesta'])); ?></td>
                        <td class="tracking-main"><?php echo htmlspecialchars($seguimiento['responsable']); ?></td>
                        <td class="tracking-date"><?php echo $seguimiento['fecha_max_ejecucion'] ? date('d/m/Y', strtotime($seguimiento['fecha_max_ejecucion'])) : '—'; ?></td>
                        <td class="tracking-date"><?php echo $seguimiento['fecha_seguimiento'] ? date('d/m/Y', strtotime($seguimiento['fecha_seguimiento'])) : '—'; ?></td>
                        <td><?php echo htmlspecialchars($seguimiento['responsable_seguimiento'] ?: '—'); ?></td>
                        <td class="tracking-text"><?php echo $seguimiento['resultado_seguimiento'] ? nl2br(htmlspecialchars($seguimiento['resultado_seguimiento'])) : 'Pendiente'; ?></td>
                        <td><div class="tracking-actions"><?php if ($editable): ?>
                            <button class="icon-btn" type="button" data-tracking="<?php echo $tracking_payload; ?>" onclick="editTracking(this)" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <form action="procesar_estandar4.php" method="post" onsubmit="return confirm('¿Eliminar este seguimiento?')"><input type="hidden" name="accion" value="eliminar_seguimiento"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="seguimiento_id" value="<?php echo $seguimiento['id']; ?>"><button class="icon-btn delete" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
                        <?php endif; ?></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>

        <section class="section">
            <div class="section-head"><div><h2>Firmas del documento</h2><p>Primero firma el Responsable SST. Luego el Representante Legal revisa y aprueba el plan.</p></div></div>
            <div class="signatures">
                <div class="signature-card">
                    <h3>Responsable del SG-SST</h3><p>Elabora y presenta el Plan Anual de Trabajo.</p>
                    <?php if ($plan['firma_sst']): ?>
                        <img class="signature-image" src="<?php echo htmlspecialchars($plan['firma_sst']); ?>" alt="Firma Responsable SST">
                        <div class="signature-name"><?php echo htmlspecialchars(trim(($firmante_sst['nombre'] ?? '') . ' ' . ($firmante_sst['apellido'] ?? ''))); ?></div>
                    <?php elseif ($usuario_rol === 'sst' && $plan['estado'] !== 'firmado'): ?>
                        <form action="procesar_estandar4.php" method="post" class="signature-form" data-canvas="sstSignature">
                            <input type="hidden" name="accion" value="firmar_sst"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="firma" class="signature-value">
                            <div class="signature-pad"><canvas id="sstSignature"></canvas></div>
                            <div class="signature-actions"><button type="button" class="btn clear-signature"><i class="fa-solid fa-eraser"></i> Limpiar</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Firmar y enviar</button></div>
                        </form>
                    <?php else: ?><div class="signature-placeholder"><span><i class="fa-regular fa-clock"></i><br>Pendiente de firma SST</span></div><?php endif; ?>
                </div>
                <div class="signature-card">
                    <h3>Representante Legal</h3><p>Revisa, aprueba y avala la ejecución del plan.</p>
                    <?php if ($plan['firma_representante']): ?>
                        <img class="signature-image" src="<?php echo htmlspecialchars($plan['firma_representante']); ?>" alt="Firma Representante Legal">
                        <div class="signature-name"><?php echo htmlspecialchars(trim(($firmante_representante['nombre'] ?? '') . ' ' . ($firmante_representante['apellido'] ?? ''))); ?></div>
                    <?php elseif ($usuario_rol === 'representante' && $plan['estado'] === 'pendiente_firma'): ?>
                        <form action="procesar_estandar4.php" method="post" class="signature-form" data-canvas="repSignature">
                            <input type="hidden" name="accion" value="firmar_representante"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="firma" class="signature-value">
                            <div class="signature-pad"><canvas id="repSignature"></canvas></div>
                            <div class="signature-actions"><button type="button" class="btn clear-signature"><i class="fa-solid fa-eraser"></i> Limpiar</button><button type="submit" class="btn btn-blue"><i class="fa-solid fa-signature"></i> Aprobar y firmar</button></div>
                        </form>
                    <?php else: ?><div class="signature-placeholder"><span><i class="fa-regular fa-clock"></i><br><?php echo $plan['firma_sst'] ? 'Pendiente de gerencia' : 'Disponible después de la firma SST'; ?></span></div><?php endif; ?>
                </div>
            </div>
            <div class="pdf-bar">
                <span><?php echo $plan['estado'] === 'firmado' ? 'Documento legalizado el ' . date('d/m/Y H:i', strtotime($plan['fecha_firma'])) : 'El PDF se habilita cuando estén registradas ambas firmas.'; ?></span>
                <div class="head-actions">
                    <?php if ($plan['estado'] === 'firmado' && $usuario_rol === 'sst'): ?>
                        <form action="procesar_estandar4.php" method="post" onsubmit="return confirm('Al reabrir el plan se solicitarán nuevamente las firmas. ¿Continuar?')"><input type="hidden" name="accion" value="reabrir_plan"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><button class="btn" type="submit"><i class="fa-solid fa-pen-to-square"></i> Reabrir plan</button></form>
                    <?php endif; ?>
                    <a class="btn btn-blue" href="<?php echo $plan['estado'] === 'firmado' ? 'estandar4_pdf.php?anio=' . $anio : '#'; ?>" <?php echo $plan['estado'] !== 'firmado' ? 'aria-disabled="true" style="opacity:.5;pointer-events:none"' : ''; ?>><i class="fa-solid fa-file-pdf"></i> Descargar PDF firmado</a>
                </div>
            </div>
        </section>
    </div>
</main>

<div class="modal" id="activityModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-head"><h2 id="activityModalTitle">Nueva actividad del plan</h2><button class="modal-close" type="button" onclick="closeModal('activityModal')"><i class="fa-solid fa-xmark"></i></button></div>
        <form action="procesar_estandar4.php" method="post" id="activityForm">
            <input type="hidden" name="accion" value="guardar_actividad"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="actividad_id" id="activityId">
            <div class="modal-body"><div class="form-grid">
                <div class="field"><label for="activityTopic">Tema</label><input id="activityTopic" name="tema" required placeholder="Ej. Capacitación SST"></div>
                <div class="field"><label for="activityResponsible">Responsable</label><input id="activityResponsible" name="responsable" required list="responsibleOptions" placeholder="Nombre o cargo"></div>
                <div class="field full"><label for="activityName">Actividad</label><input id="activityName" name="actividad" required placeholder="Describe la actividad a ejecutar"></div>
                <div class="field full"><label>Meses programados</label><div class="months-selector"><?php foreach ($meses as $numero => $mes): ?><label class="month-check"><input type="checkbox" name="meses[]" value="<?php echo $numero; ?>"><span><?php echo $mes; ?></span></label><?php endforeach; ?></div></div>
                <div class="field full"><label for="activityNotes">Observaciones</label><textarea id="activityNotes" name="observaciones" placeholder="Recursos, alcance o información adicional"></textarea></div>
            </div></div>
            <div class="modal-foot"><button class="btn" type="button" onclick="closeModal('activityModal')">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar actividad</button></div>
        </form>
    </div>
</div>

<div class="modal" id="trackingModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-head"><h2 id="trackingModalTitle">Nuevo seguimiento</h2><button class="modal-close" type="button" onclick="closeModal('trackingModal')"><i class="fa-solid fa-xmark"></i></button></div>
        <form action="procesar_estandar4.php" method="post" id="trackingForm">
            <input type="hidden" name="accion" value="guardar_seguimiento"><input type="hidden" name="anio" value="<?php echo $anio; ?>"><input type="hidden" name="seguimiento_id" id="trackingId">
            <div class="modal-body"><div class="form-grid">
                <div class="field"><label for="trackingPeriod">Periodo</label><input id="trackingPeriod" name="periodo" required placeholder="Ej. Enero - Marzo"></div>
                <div class="field"><label for="trackingResponsible">Responsable</label><input id="trackingResponsible" name="responsable" required list="responsibleOptions"></div>
                <div class="field full"><label for="trackingAnalysis">Análisis del resultado</label><textarea id="trackingAnalysis" name="analisis_resultado" required></textarea></div>
                <div class="field full"><label for="trackingAction">Acción propuesta</label><textarea id="trackingAction" name="accion_propuesta" required></textarea></div>
                <div class="field"><label for="trackingDeadline">Fecha máxima de ejecución</label><input id="trackingDeadline" name="fecha_max_ejecucion" type="date"></div>
                <div class="field"><label for="trackingDate">Fecha de seguimiento</label><input id="trackingDate" name="fecha_seguimiento" type="date"></div>
                <div class="field"><label for="trackingReviewer">Responsable del seguimiento</label><input id="trackingReviewer" name="responsable_seguimiento" list="responsibleOptions"></div>
                <div class="field"><label for="trackingResult">Resultado del seguimiento</label><textarea id="trackingResult" name="resultado_seguimiento"></textarea></div>
            </div></div>
            <div class="modal-foot"><button class="btn" type="button" onclick="closeModal('trackingModal')">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar seguimiento</button></div>
        </form>
    </div>
</div>

<datalist id="responsibleOptions">
    <?php foreach ($responsables as $responsable): ?><option value="<?php echo htmlspecialchars(trim($responsable['nombre'] . ' ' . $responsable['apellido'])); ?>"><?php echo htmlspecialchars(ucfirst($responsable['rol'])); ?></option><?php endforeach; ?>
    <option value="Responsable SG-SST"><option value="Representante Legal"><option value="COPASST"><option value="Comité de Convivencia">
</datalist>

<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    function openActivityModal() {
        document.getElementById('activityForm').reset();
        document.getElementById('activityId').value = '';
        document.getElementById('activityModalTitle').textContent = 'Nueva actividad del plan';
        openModal('activityModal');
    }
    function editActivity(button) {
        const activity = JSON.parse(button.dataset.activity);
        openActivityModal();
        document.getElementById('activityModalTitle').textContent = 'Editar actividad';
        document.getElementById('activityId').value = activity.id;
        document.getElementById('activityTopic').value = activity.tema || '';
        document.getElementById('activityName').value = activity.actividad || '';
        document.getElementById('activityResponsible').value = activity.responsable || '';
        document.getElementById('activityNotes').value = activity.observaciones || '';
        document.querySelectorAll('#activityForm input[name="meses[]"]').forEach(input => input.checked = activity.meses.includes(Number(input.value)));
    }
    function openTrackingModal() {
        document.getElementById('trackingForm').reset();
        document.getElementById('trackingId').value = '';
        document.getElementById('trackingModalTitle').textContent = 'Nuevo seguimiento';
        openModal('trackingModal');
    }
    function editTracking(button) {
        const data = JSON.parse(button.dataset.tracking);
        openTrackingModal();
        document.getElementById('trackingModalTitle').textContent = 'Editar seguimiento';
        const fields = {
            trackingId:'id', trackingPeriod:'periodo', trackingResponsible:'responsable',
            trackingAnalysis:'analisis_resultado', trackingAction:'accion_propuesta',
            trackingDeadline:'fecha_max_ejecucion', trackingDate:'fecha_seguimiento',
            trackingReviewer:'responsable_seguimiento', trackingResult:'resultado_seguimiento'
        };
        Object.entries(fields).forEach(([id, key]) => document.getElementById(id).value = data[key] || '');
    }
    document.querySelectorAll('.modal').forEach(modal => modal.addEventListener('click', event => {
        if (event.target === modal) closeModal(modal.id);
    }));

    function initializeSignature(form) {
        const canvas = document.getElementById(form.dataset.canvas);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let hasInk = false;
        function resize() {
            const ratio = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width = Math.max(1, Math.round(rect.width * ratio));
            canvas.height = Math.max(1, Math.round(rect.height * ratio));
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#172554';
        }
        function point(event) {
            const source = event.touches ? event.touches[0] : event;
            const rect = canvas.getBoundingClientRect();
            return {x: source.clientX - rect.left, y: source.clientY - rect.top};
        }
        function start(event) {
            event.preventDefault();
            drawing = true;
            hasInk = true;
            const p = point(event);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        }
        function move(event) {
            if (!drawing) return;
            event.preventDefault();
            const p = point(event);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        }
        function stop() { drawing = false; }
        resize();
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mouseleave', stop);
        canvas.addEventListener('touchstart', start, {passive:false});
        canvas.addEventListener('touchmove', move, {passive:false});
        canvas.addEventListener('touchend', stop);
        form.querySelector('.clear-signature').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
        });
        form.addEventListener('submit', event => {
            if (!hasInk) {
                event.preventDefault();
                alert('Dibuja tu firma antes de continuar.');
                return;
            }
            form.querySelector('.signature-value').value = canvas.toDataURL('image/png');
        });
    }
    document.querySelectorAll('.signature-form').forEach(initializeSignature);
</script>
</body>
</html>
