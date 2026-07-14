<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar6_schema.php';

date_default_timezone_set('America/Bogota');
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
                    <p class="subtitle">Estructura IPVR basada en matriz de peligros por actividad: presentaci&oacute;n ejecutiva, gr&aacute;ficos y registro horizontal por actividad, tarea y control.</p>
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
            </div>

            <div class="metric-grid">
                <div class="metric"><span>Registros en matriz</span><strong><?php echo $totalRegistros; ?></strong></div>
                <div class="metric"><span>Actividades analizadas</span><strong><?php echo $actividades; ?></strong></div>
                <div class="metric"><span>Procesos cubiertos</span><strong><?php echo $procesos; ?></strong></div>
                <div class="metric <?php echo $riesgosResidualesAltos > 0 ? 'danger' : 'ok'; ?>"><span>Riesgos residuales altos</span><strong><?php echo $riesgosResidualesAltos; ?></strong></div>
            </div>

            <?php if ($totalRegistros === 0): ?>
                <div class="empty-state">A&uacute;n no hay peligros registrados. Inicia con la secci&oacute;n de peligros por actividad para alimentar estos gr&aacute;ficos.</div>
            <?php else: ?>
                <div class="dash-grid">
                    <div class="chart-box">
                        <p class="chart-title">Distribuci&oacute;n por tipo de peligro</p>
                        <?php foreach (array_slice($porPeligro, 0, 8, true) as $label => $count): ?>
                            <div class="bar-row">
                                <strong><?php echo e6h($label); ?></strong>
                                <div class="bar-track"><div class="bar-fill" style="width: <?php echo max(7, round(($count / $maxPeligro) * 100)); ?>%"></div></div>
                                <span><?php echo $count; ?> · <?php echo e6_pct($count, $totalRegistros); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-box">
                        <p class="chart-title">Aceptabilidad residual de riesgos</p>
                        <?php foreach ($porAceptabilidad as $label => $count): ?>
                            <div class="bar-row">
                                <strong><?php echo e6h($label); ?></strong>
                                <div class="bar-track"><div class="bar-fill" style="width: <?php echo max(7, round(($count / $maxAceptabilidad) * 100)); ?>%"></div></div>
                                <span><?php echo $count; ?> · <?php echo e6_pct($count, $totalRegistros); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="calc-panel" style="grid-template-columns:repeat(2,minmax(0,1fr)); margin-top:18px;">
                            <div class="calc-pill"><span>Altos antes del control</span><strong><?php echo $riesgosAltos; ?></strong></div>
                            <div class="calc-pill"><span>Altos despues del control</span><strong><?php echo $riesgosResidualesAltos; ?></strong></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="section-card" id="matriz">
            <div class="section-head">
                <div class="section-title">
                    <i class="fa-solid fa-list-check"></i>
                    <div>
                        <h2>Peligros por actividad</h2>
                        <p class="section-kicker">Registro horizontal por proceso, actividad, tarea, peligro, valoraci&oacute;n y controles.</p>
                    </div>
                </div>
            </div>

            <?php if ($puedeEditar): ?>
                <form action="procesar_estandar6.php" method="POST" id="ipvrForm">
                    <input type="hidden" name="accion" value="guardar_ipvr">
                    <div class="form-grid">
                        <div class="field"><label>No.</label><input type="number" name="numero" min="1" placeholder="Auto"></div>
                        <div class="field"><label>Sitio de trabajo</label><input name="sitio_trabajo" placeholder="Centro o sede"></div>
                        <div class="field"><label>Cuadro b&aacute;sico</label><input name="cuadro_basico" placeholder="Area o grupo"></div>
                        <div class="field"><label>Proceso *</label><input name="proceso" required placeholder="Ej. Produccion"></div>
                        <div class="field wide"><label>Actividad *</label><input name="actividad" required placeholder="Actividad evaluada"></div>
                        <div class="field wide"><label>Tareas dentro de la actividad</label><input name="tarea" placeholder="Tarea especifica o secuencia"></div>
                        <div class="field"><label>Zona / lugar</label><input name="zona_lugar" placeholder="Lugar de ejecucion"></div>
                        <div class="field"><label>Clase de actividad</label><select name="clase_actividad"><option>Rutinaria</option><option>No Rutinaria</option></select></div>
                        <div class="field"><label>Origen</label><select name="origen_actividad"><option>Interna</option><option>Externa</option></select></div>
                        <div class="field"><label>Cargos</label><input name="cargos" placeholder="Cargos expuestos"></div>
                        <div class="field"><label>Directos</label><input type="number" min="0" name="directos" id="directos" value="0"></div>
                        <div class="field"><label>Contratistas</label><input type="number" min="0" name="contratistas" id="contratistas" value="0"></div>
                        <div class="field"><label>Visitantes</label><input type="number" min="0" name="visitantes" id="visitantes" value="0"></div>
                        <div class="field"><label>Peligro *</label><select name="peligro" id="peligro" required><option value="">Selecciona...</option><?php foreach ($catalogos['peligros'] as $peligro => $items): ?><option value="<?php echo e6h($peligro); ?>"><?php echo e6h($peligro); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>Clasificaci&oacute;n *</label><select name="clasificacion_peligro" id="clasificacion_peligro" required><option value="">Selecciona peligro primero</option></select></div>
                        <div class="field wide"><label>Descripci&oacute;n del peligro</label><input name="descripcion_peligro" placeholder="Describe la fuente o condicion peligrosa"></div>
                        <div class="field"><label>Categor&iacute;a *</label><select name="categoria" id="categoria" required><option>Salud</option><option selected>Seguridad</option><option>Propiedad_Proceso</option></select></div>
                        <div class="field"><label>Nivel de da&ntilde;o *</label><select name="nivel_danio" id="nivel_danio" required></select></div>
                        <div class="field"><label>ND antes</label><select name="nivel_deficiencia" id="nivel_deficiencia"><?php foreach ($catalogos['nivel_deficiencia'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . e6h($label); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>NE antes</label><select name="nivel_exposicion" id="nivel_exposicion"><?php foreach ($catalogos['nivel_exposicion'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . e6h($label); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>Requisito legal</label><select name="requisito_legal"><option>NO</option><option>SI</option></select></div>
                        <div class="field wide"><label>Control fuente</label><textarea name="control_fuente" placeholder="Controles existentes en la fuente"></textarea></div>
                        <div class="field wide"><label>Control medio</label><textarea name="control_medio" placeholder="Controles existentes en el medio"></textarea></div>
                        <div class="field wide"><label>Control persona</label><textarea name="control_persona" placeholder="Controles existentes sobre la persona"></textarea></div>
                        <div class="field wide"><label>Instrumento</label><textarea name="instrumento" placeholder="Procedimiento, formato, inspeccion, medicion o soporte"></textarea></div>
                        <div class="field"><label>ND despues</label><select name="nivel_deficiencia_residual" id="nivel_deficiencia_residual"><?php foreach ($catalogos['nivel_deficiencia'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>" <?php echo (int)$valor === 2 ? 'selected' : ''; ?>><?php echo (int)$valor . ' - ' . e6h($label); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>NE despues</label><select name="nivel_exposicion_residual" id="nivel_exposicion_residual"><?php foreach ($catalogos['nivel_exposicion'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . e6h($label); ?></option><?php endforeach; ?></select></div>
                        <div class="field"><label>NC despues</label><input type="number" min="0" name="nivel_consecuencia_residual" id="nivel_consecuencia_residual" value="10"></div>
                        <div class="field"><label>Accidentes a&ntilde;o anterior</label><input type="number" min="0" name="accidentes_anterior" placeholder="Opcional"></div>
                        <div class="field"><label>Accidentes a&ntilde;o actual</label><input type="number" min="0" name="accidentes_actual" placeholder="Opcional"></div>
                        <div class="field wide"><label>Eliminaci&oacute;n</label><textarea name="eliminacion"></textarea></div>
                        <div class="field wide"><label>Sustituci&oacute;n</label><textarea name="sustitucion"></textarea></div>
                        <div class="field wide"><label>Controles de ingenier&iacute;a</label><textarea name="controles_ingenieria"></textarea></div>
                        <div class="field wide"><label>Se&ntilde;alizaci&oacute;n / advertencia</label><textarea name="senalizacion_advertencia"></textarea></div>
                        <div class="field wide"><label>Administrativos</label><textarea name="administrativos"></textarea></div>
                        <div class="field wide"><label>EPP</label><textarea name="epp"></textarea></div>
                        <div class="field full"><label>Observaciones</label><textarea name="observaciones" placeholder="Notas internas para el seguimiento"></textarea></div>
                    </div>

                    <div class="calc-panel">
                        <div class="calc-pill"><span>Total expuestos</span><strong id="calcTotal">0</strong></div>
                        <div class="calc-pill"><span>Riesgo antes</span><strong id="calcRiesgoAntes">0</strong></div>
                        <div class="calc-pill"><span>Aceptabilidad antes</span><strong id="calcAceptAntes">ACEPTABLE</strong></div>
                        <div class="calc-pill"><span>Riesgo residual</span><strong id="calcRiesgoDespues">0</strong></div>
                    </div>
                    <div class="actions">
                        <button type="reset" class="btn-secondary"><i class="fa-solid fa-rotate-left"></i> Limpiar</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar peligro</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">Vista de consulta para representante legal. El responsable SST registra y actualiza la matriz IPVR.</div>
            <?php endif; ?>

            <div class="matrix-toolbar" style="margin-top:24px;">
                <h2 style="font-size:1rem;">Matriz IPVR registrada</h2>
                <input type="search" id="matrixSearch" placeholder="Filtrar por proceso, actividad, cargo, peligro o clasificacion...">
            </div>
            <div class="table-wrap">
                <table id="ipvrTable">
                    <thead>
                        <tr>
                            <th>No.</th><th>Sitio</th><th>Proceso</th><th>Actividad</th><th>Tarea</th><th>Zona</th><th>Clase</th><th>Origen</th><th>Cargos</th><th>Total</th><th>Peligro</th><th>Clasificaci&oacute;n</th><th>Descripci&oacute;n</th><th>ND</th><th>NE</th><th>NP</th><th>NC</th><th>Riesgo</th><th>Aceptabilidad</th><th>Controles existentes</th><th>Riesgo residual</th><th>Aceptabilidad residual</th><th>Plan de intervenci&oacute;n</th><th>Reducci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalRegistros === 0): ?>
                            <tr><td colspan="24" class="muted">No hay registros en la matriz.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($registros as $row): ?>
                            <?php
                                $acept = (string)$row['aceptabilidad'];
                                $aceptClass = $acept === 'ACEPTABLE' ? 'ok' : ($acept === 'MEJORABLE' ? 'mid' : ($acept === 'NO ACEPTABLE' ? 'bad' : 'warn'));
                                $aceptR = (string)$row['aceptabilidad_residual'];
                                $aceptRClass = $aceptR === 'ACEPTABLE' ? 'ok' : ($aceptR === 'MEJORABLE' ? 'mid' : ($aceptR === 'NO ACEPTABLE' ? 'bad' : 'warn'));
                            ?>
                            <tr>
                                <td><?php echo (int)$row['numero']; ?></td>
                                <td><?php echo e6h($row['sitio_trabajo']); ?></td>
                                <td><strong><?php echo e6h($row['proceso']); ?></strong></td>
                                <td><?php echo e6h($row['actividad']); ?></td>
                                <td><?php echo e6h($row['tarea']); ?></td>
                                <td><?php echo e6h($row['zona_lugar']); ?></td>
                                <td><?php echo e6h($row['clase_actividad']); ?></td>
                                <td><?php echo e6h($row['origen_actividad']); ?></td>
                                <td><?php echo e6h($row['cargos']); ?></td>
                                <td><?php echo (int)$row['total_expuestos']; ?></td>
                                <td><?php echo e6h($row['peligro']); ?></td>
                                <td><?php echo e6h($row['clasificacion_peligro']); ?></td>
                                <td><?php echo e6h($row['descripcion_peligro']); ?></td>
                                <td><?php echo (int)$row['nivel_deficiencia']; ?></td>
                                <td><?php echo (int)$row['nivel_exposicion']; ?></td>
                                <td><?php echo (int)$row['nivel_probabilidad']; ?> / <?php echo e6h($row['interpretacion_probabilidad']); ?></td>
                                <td><?php echo (int)$row['nivel_consecuencia']; ?></td>
                                <td><strong><?php echo (int)$row['nivel_riesgo']; ?></strong></td>
                                <td><span class="badge <?php echo $aceptClass; ?>"><?php echo e6h($acept); ?></span></td>
                                <td><?php echo e6h(trim(($row['control_fuente'] ?? '') . ' ' . ($row['control_medio'] ?? '') . ' ' . ($row['control_persona'] ?? ''))); ?></td>
                                <td><strong><?php echo (int)$row['nivel_riesgo_residual']; ?></strong></td>
                                <td><span class="badge <?php echo $aceptRClass; ?>"><?php echo e6h($aceptR); ?></span></td>
                                <td><?php echo e6h(trim(($row['eliminacion'] ?? '') . ' ' . ($row['sustitucion'] ?? '') . ' ' . ($row['controles_ingenieria'] ?? '') . ' ' . ($row['administrativos'] ?? '') . ' ' . ($row['epp'] ?? ''))); ?></td>
                                <td><?php echo number_format((float)$row['factor_reduccion'], 2, ',', '.'); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script>
const catalogos = <?php echo json_encode($catalogos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function ncPorNivel(nivel) {
    const value = (nivel || '').toLowerCase();
    if (value.includes('mortal') || value.includes('catastrofico')) return 100;
    if (value.includes('grave') || value.includes('mayor')) return 60;
    if (value.includes('moderado')) return 25;
    return 10;
}
function interpretacion(np) {
    if (np >= 24) return 'MUY ALTO';
    if (np >= 10) return 'ALTO';
    if (np >= 6) return 'MEDIO';
    return 'BAJO';
}
function aceptabilidad(riesgo) {
    if (riesgo >= 600) return 'NO ACEPTABLE';
    if (riesgo >= 150) return 'NO ACEPTABLE O ACEPTABLE CON CONTROL ESPECIFICO';
    if (riesgo >= 40) return 'MEJORABLE';
    return 'ACEPTABLE';
}
function fillClasificaciones() {
    const peligro = document.getElementById('peligro');
    const clasificacion = document.getElementById('clasificacion_peligro');
    if (!peligro || !clasificacion) return;
    const selected = peligro.value;
    clasificacion.innerHTML = '<option value="">Selecciona...</option>';
    (catalogos.peligros[selected] || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item;
        opt.textContent = item;
        clasificacion.appendChild(opt);
    });
}
function fillNivelesDanio() {
    const categoria = document.getElementById('categoria');
    const nivel = document.getElementById('nivel_danio');
    if (!categoria || !nivel) return;
    nivel.innerHTML = '';
    (catalogos.niveles_danio[categoria.value] || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item;
        opt.textContent = item;
        nivel.appendChild(opt);
    });
    updateCalc();
}
function updateCalc() {
    const get = id => document.getElementById(id);
    if (!get('calcTotal')) return;
    const directos = parseInt(get('directos')?.value || '0', 10);
    const contratistas = parseInt(get('contratistas')?.value || '0', 10);
    const visitantes = parseInt(get('visitantes')?.value || '0', 10);
    const nd = parseInt(get('nivel_deficiencia')?.value || '2', 10);
    const ne = parseInt(get('nivel_exposicion')?.value || '1', 10);
    const nc = ncPorNivel(get('nivel_danio')?.value || 'Leve');
    const np = nd * ne;
    const riesgo = np * nc;
    const ndR = parseInt(get('nivel_deficiencia_residual')?.value || '2', 10);
    const neR = parseInt(get('nivel_exposicion_residual')?.value || '1', 10);
    const ncR = parseInt(get('nivel_consecuencia_residual')?.value || String(nc), 10);
    const riesgoR = ndR * neR * ncR;
    get('calcTotal').textContent = directos + contratistas + visitantes;
    get('calcRiesgoAntes').textContent = `${riesgo} (${interpretacion(np)})`;
    get('calcAceptAntes').textContent = aceptabilidad(riesgo);
    get('calcRiesgoDespues').textContent = `${riesgoR} (${aceptabilidad(riesgoR)})`;
}
document.getElementById('peligro')?.addEventListener('change', fillClasificaciones);
document.getElementById('categoria')?.addEventListener('change', fillNivelesDanio);
['directos','contratistas','visitantes','nivel_deficiencia','nivel_exposicion','nivel_danio','nivel_deficiencia_residual','nivel_exposicion_residual','nivel_consecuencia_residual'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateCalc);
    document.getElementById(id)?.addEventListener('change', updateCalc);
});
document.getElementById('matrixSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#ipvrTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
fillClasificaciones();
fillNivelesDanio();
updateCalc();
</script>
</body>
</html>
