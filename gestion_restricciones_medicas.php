<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar5_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar5_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'sst') {
    header('Location: estandar5?modulo=restricciones&msg=' . urlencode('Solo el responsable SST puede gestionar restricciones médicas.') . '&tipo=error');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT u.id,u.nombre,u.apellido,u.cedula,u.email,u.telefono,g.nombre AS grupo_nombre,e.tipo_personal,prev.cargo AS cargo_guardado,prev.fecha_ingreso AS fecha_ingreso_guardada FROM usuarios u LEFT JOIN grupos_personal g ON g.id=u.grupo_id LEFT JOIN encuesta_sociodemografica e ON e.usuario_id=u.id LEFT JOIN estandar5_restricciones_recomendaciones prev ON prev.id=(SELECT MAX(prev2.id) FROM estandar5_restricciones_recomendaciones prev2 WHERE prev2.empresa_id=u.empresa_id AND prev2.trabajador_id=u.id) WHERE u.empresa_id=? AND u.rol='trabajador' ORDER BY u.nombre,u.apellido");
$stmt->execute([$empresa_id]);
$trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT r.*,u.nombre,u.apellido,u.cedula FROM estandar5_restricciones_recomendaciones r INNER JOIN usuarios u ON u.id=r.trabajador_id WHERE r.empresa_id=? ORDER BY r.actualizado_en DESC,r.id DESC LIMIT 100");
$stmt->execute([$empresa_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pve_programas = ['Prevención DME','Prevención hipoacusia neurosensorial','Cuidado respiratorio','Estilos de vida saludable','Factores de riesgo psicosocial'];
$tipos_restriccion = ['No tiene Restricción','Física (Levantamiento, Movimientos Repetitivos)','Trabajo en Alturas','Espacios Confinados','Emocionales o Psicológicos'];
$estados = ['No presenta Gestión - Se reprograma','Cita Programada','Ya tuvo Cita y se remite a Especialista','Cita programada con Especialista','Ya tuvo Cita con Especialista','Se encuentra en Tratamiento','Cerrado'];
$vista = ($_GET['vista'] ?? '') === 'seguimiento' ? 'seguimiento' : 'nueva';
$registro_preseleccionado = (int)($_GET['registro_id'] ?? 0);
$msg = trim((string)($_GET['msg'] ?? ''));
$tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
$pendientes = count(array_filter($registros, fn($row) => trim((string)($row['sst_estado'] ?? '')) !== 'Cerrado'));
$firmadas = count(array_filter($registros, fn($row) => ($row['carta_firmada'] ?? 'No') === 'Si'));
$registros_formulario = [];
foreach ($registros as $registro) {
    $registros_formulario[(int)$registro['id']] = [
        'carta_firmada' => $registro['carta_firmada'] ?? 'No',
        'fecha_entrega_carta' => $registro['fecha_entrega_carta'] ?? '',
        'sst_fecha_programada' => $registro['sst_fecha_programada'] ?? '',
        'sst_fecha_real' => $registro['sst_fecha_real'] ?? '',
        'sst_responsable' => $registro['sst_responsable'] ?? '',
        'sst_estado' => $registro['sst_estado'] ?? '',
        'sst_historial' => $registro['sst_historial'] ?? '',
        'arl_fecha_real' => $registro['arl_fecha_real'] ?? '',
        'arl_responsable' => $registro['arl_responsable'] ?? '',
        'arl_historial' => $registro['arl_historial'] ?? '',
    ];
}

function rr_cargo(array $trabajador): string
{
    $cargo = trim((string)($trabajador['grupo_nombre'] ?? '')) ?: trim((string)($trabajador['tipo_personal'] ?? ''));
    $cargo = $cargo ?: trim((string)($trabajador['cargo_guardado'] ?? ''));
    return $cargo ?: 'Sin cargo registrado';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de restricciones médicas | Estándar 5</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar5-restricciones.css?v=20260715-2">
    <link rel="stylesheet" href="assets/worker-selector.css?v=20260715-1">
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="rr-page">
        <header class="rr-head">
            <div class="rr-head-copy"><div class="rr-head-icon"><i class="fa-solid fa-notes-medical"></i></div><div><h1>Gestión de restricciones médicas</h1><p>Crea la carta inicial o actualiza el seguimiento sin perder el contexto del trabajador.</p></div></div>
            <a class="rr-back" href="estandar5?modulo=restricciones"><i class="fa-solid fa-arrow-left"></i> Volver al tablero</a>
        </header>

        <?php if ($msg !== ''): ?><div class="rr-alert <?php echo htmlspecialchars($tipo_msg); ?>"><i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i><span><?php echo htmlspecialchars($msg); ?></span></div><?php endif; ?>

        <nav class="rr-flow" aria-label="Flujo de restricciones médicas">
            <a class="rr-flow-step <?php echo $vista === 'nueva' ? 'active' : ''; ?>" href="gestion_restricciones_medicas?vista=nueva"><span class="rr-flow-icon"><i class="fa-solid fa-file-circle-plus"></i></span><span><strong>1. Crear nueva carta</strong><small>Trabajador, concepto, restricciones y recomendaciones</small></span><i class="fa-solid fa-file-signature rr-flow-watermark" aria-hidden="true"></i></a>
            <a class="rr-flow-step <?php echo $vista === 'seguimiento' ? 'active' : ''; ?>" href="gestion_restricciones_medicas?vista=seguimiento"><span class="rr-flow-icon"><i class="fa-solid fa-route"></i></span><span><strong>2. Actualizar seguimiento</strong><small>Entrega, firma, gestión SST y acompañamiento ARL</small></span><i class="fa-solid fa-clipboard-check rr-flow-watermark" aria-hidden="true"></i></a>
        </nav>

        <div class="rr-shell">
            <section class="rr-card">
                <?php if ($vista === 'nueva'): ?>
                    <div class="rr-card-head"><h2>Nueva carta de recomendaciones médicas</h2><p>La carta se genera en PDF y crea automáticamente el registro de seguimiento.</p><i class="fa-solid fa-file-signature rr-card-watermark" aria-hidden="true"></i></div>
                    <form class="rr-form" action="procesar_estandar5.php" method="POST" id="restrictionForm">
                        <input type="hidden" name="accion" value="crear_carta_recomendacion_medica"><input type="hidden" name="origen_formulario" value="gestion-restricciones"><input type="hidden" name="vista_restricciones" value="nueva">
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-user"></i> 1. Trabajador y origen médico</div><div class="rr-grid">
                            <div class="rr-field full"><label for="restrTrabajador">Trabajador *</label><select name="trabajador_id" id="restrTrabajador" data-worker-search data-worker-search-placeholder="Buscar por nombre, c&eacute;dula, correo o cargo" required><option value="">Selecciona trabajador...</option><?php foreach ($trabajadores as $trabajador): $nombre_trabajador=trim($trabajador['nombre'].' '.$trabajador['apellido']); ?><option value="<?php echo (int)$trabajador['id']; ?>" data-nombre="<?php echo htmlspecialchars($nombre_trabajador); ?>" data-cedula="<?php echo htmlspecialchars($trabajador['cedula']); ?>" data-email="<?php echo htmlspecialchars($trabajador['email'] ?? ''); ?>" data-telefono="<?php echo htmlspecialchars($trabajador['telefono'] ?? ''); ?>" data-cargo="<?php echo htmlspecialchars(rr_cargo($trabajador)); ?>" data-fecha-ingreso="<?php echo htmlspecialchars($trabajador['fecha_ingreso_guardada'] ?? ''); ?>"><?php echo htmlspecialchars($nombre_trabajador.' · C.C. '.$trabajador['cedula'].' · '.rr_cargo($trabajador)); ?></option><?php endforeach; ?></select></div>
                            <div class="rr-field"><label for="restrCargo">Cargo</label><input id="restrCargo" name="cargo" placeholder="Se completa al seleccionar trabajador"></div><div class="rr-field"><label for="restrFechaIngreso">Fecha de ingreso</label><input id="restrFechaIngreso" type="date" name="fecha_ingreso"></div>
                            <div class="rr-field"><label for="restrFechaExamen">Fecha del examen</label><input id="restrFechaExamen" type="date" name="fecha_examen"></div><div class="rr-field"><label for="restrIps">IPS / centro médico</label><input id="restrIps" name="ips_nombre" placeholder="IPS que emite el concepto"></div>
                        </div></section>
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-file-medical"></i> 2. Carta y concepto</div><div class="rr-grid">
                            <div class="rr-field"><label for="restrCartaFecha">Fecha de carta *</label><input id="restrCartaFecha" type="date" name="carta_fecha" value="<?php echo date('Y-m-d'); ?>" required></div><div class="rr-field"><label for="restrProyecto">Proyecto / sede</label><input id="restrProyecto" name="proyecto" placeholder="Proyecto o sede"></div>
                            <div class="rr-field full"><label for="restrConcepto">Concepto médico</label><input id="restrConcepto" name="concepto_medico" placeholder="Ej. Apto con restricciones"></div>
                            <div class="rr-field full"><label for="restrTipo">Tipo de restricción *</label><select id="restrTipo" name="tipo_restriccion" required><option value="">Selecciona...</option><?php foreach ($tipos_restriccion as $tipo): ?><option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option><?php endforeach; ?></select></div>
                            <div class="rr-field full"><label for="restrRestriccion">Detalle de la restricción</label><textarea id="restrRestriccion" name="restriccion" placeholder="Describe la restricción indicada por el médico"></textarea></div>
                        </div></section>
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-list-check"></i> 3. Recomendaciones y programas</div><div class="rr-grid">
                            <div class="rr-field"><label for="restrLaborales">Recomendaciones médico laborales</label><textarea id="restrLaborales" name="recomendaciones_laborales"></textarea></div><div class="rr-field"><label for="restrGenerales">Recomendaciones médico generales</label><textarea id="restrGenerales" name="recomendaciones_generales"></textarea></div>
                            <div class="rr-field full"><label>PVE / programas de gestión</label><div class="rr-checks"><?php foreach ($pve_programas as $programa): ?><label class="rr-check"><input type="checkbox" name="pve[]" value="<?php echo htmlspecialchars($programa); ?>"><span><?php echo htmlspecialchars($programa); ?></span></label><?php endforeach; ?></div></div>
                        </div></section>
                        <div class="rr-submit-row"><span class="rr-note">Al guardar se genera el PDF y el caso aparecerá en el tablero principal.</span><button class="rr-submit" type="submit"><i class="fa-solid fa-file-pdf"></i> Crear carta y registro</button></div>
                    </form>
                <?php else: ?>
                    <div class="rr-card-head"><h2>Actualizar seguimiento de una carta</h2><p>Selecciona el registro y completa entrega, firma, gestión SST y acompañamiento ARL.</p><i class="fa-solid fa-route rr-card-watermark" aria-hidden="true"></i></div>
                    <?php if (empty($registros)): ?><div class="rr-form"><div class="rr-alert error"><i class="fa-solid fa-circle-info"></i><span>Primero debes crear una carta para habilitar el seguimiento.</span></div></div><?php else: ?>
                    <form class="rr-form" action="procesar_estandar5.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar_seguimiento_restriccion"><input type="hidden" name="origen_formulario" value="gestion-restricciones"><input type="hidden" name="vista_restricciones" value="seguimiento">
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-file-signature"></i> 1. Carta y entrega</div><div class="rr-grid">
                            <div class="rr-field full"><label for="restrRegistro">Carta / trabajador *</label><select id="restrRegistro" name="registro_id" required><option value="">Selecciona carta creada...</option><?php foreach ($registros as $row): ?><option value="<?php echo (int)$row['id']; ?>" <?php echo $registro_preseleccionado === (int)$row['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(trim($row['nombre'].' '.$row['apellido']).' · C.C. '.$row['cedula'].' · '.($row['carta_fecha'] ? date('d/m/Y',strtotime($row['carta_fecha'])) : 'Sin fecha')); ?></option><?php endforeach; ?></select></div>
                            <div class="rr-field"><label for="restrCarta">Carta firmada</label><select id="restrCarta" name="carta_firmada"><option value="No">No</option><option value="Si">Sí</option></select></div><div class="rr-field"><label for="restrFechaCarta">Fecha de entrega</label><input id="restrFechaCarta" type="date" name="fecha_entrega_carta"></div>
                        </div></section>
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-shield-heart"></i> 2. Seguimiento SST</div><div class="rr-grid">
                            <div class="rr-field"><label for="restrSstProg">Fecha programada</label><input id="restrSstProg" type="date" name="sst_fecha_programada"></div><div class="rr-field"><label for="restrSstReal">Fecha real</label><input id="restrSstReal" type="date" name="sst_fecha_real"></div>
                            <div class="rr-field"><label for="restrSstResp">Responsable SST</label><input id="restrSstResp" name="sst_responsable" placeholder="Nombre del responsable"></div><div class="rr-field"><label for="restrSstEstado">Estado SST</label><select id="restrSstEstado" name="sst_estado"><option value="">Selecciona...</option><?php foreach ($estados as $estado): ?><option value="<?php echo htmlspecialchars($estado); ?>"><?php echo htmlspecialchars($estado); ?></option><?php endforeach; ?></select></div>
                            <div class="rr-field full"><label for="restrSstHist">Historial de seguimiento SST</label><textarea id="restrSstHist" name="sst_historial"></textarea></div>
                        </div></section>
                        <section class="rr-section"><div class="rr-section-title"><i class="fa-solid fa-people-group"></i> 3. Acompañamiento ARL</div><div class="rr-grid">
                            <div class="rr-field"><label for="restrArlReal">Fecha real ARL</label><input id="restrArlReal" type="date" name="arl_fecha_real"></div><div class="rr-field"><label for="restrArlResp">Responsable ARL</label><input id="restrArlResp" name="arl_responsable"></div><div class="rr-field full"><label for="restrArlHist">Historial de seguimiento ARL</label><textarea id="restrArlHist" name="arl_historial"></textarea></div>
                        </div></section>
                        <div class="rr-submit-row"><span class="rr-note">La actualización se aplicará únicamente sobre la carta seleccionada.</span><button class="rr-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar seguimiento</button></div>
                    </form><?php endif; ?>
                <?php endif; ?>
            </section>

            <aside class="rr-side">
                <article class="rr-side-card"><div class="rr-side-icon"><i class="fa-solid fa-shield-heart"></i></div><h3>Control del flujo</h3><p>La carta inicial alimenta automáticamente el tablero y evita volver a registrar la información.</p><div class="rr-side-list"><div class="rr-side-item"><i class="fa-solid fa-circle-check"></i><span>Selecciona el trabajador y confirma el concepto emitido por la IPS.</span></div><div class="rr-side-item"><i class="fa-solid fa-circle-check"></i><span>Registra únicamente recomendaciones presentes en el concepto médico.</span></div><div class="rr-side-item"><i class="fa-solid fa-circle-check"></i><span>Actualiza el mismo caso para conservar la trazabilidad SST y ARL.</span></div></div></article>
                <article class="rr-side-card"><div class="rr-side-icon"><i class="fa-solid fa-chart-simple"></i></div><h3>Estado actual</h3><p>Resumen de la gestión registrada para la empresa.</p><div class="rr-stat"><div><span>Trabajadores</span><strong><?php echo count($trabajadores); ?></strong></div><div><span>Cartas</span><strong><?php echo count($registros); ?></strong></div><div><span>Firmadas</span><strong><?php echo $firmadas; ?></strong></div><div><span>Pendientes</span><strong><?php echo $pendientes; ?></strong></div></div></article>
            </aside>
        </div>
    </div>
</main>
<script src="assets/worker-selector.js?v=20260715-1"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var worker=document.getElementById('restrTrabajador'),cargo=document.getElementById('restrCargo'),fechaIngreso=document.getElementById('restrFechaIngreso');
    function hydrateWorker(){var selected=worker?.selectedOptions?.[0];if(cargo)cargo.value=selected?.value?(selected.dataset.cargo||''):'';if(fechaIngreso)fechaIngreso.value=selected?.value?(selected.dataset.fechaIngreso||''):'';}
    worker?.addEventListener('change',hydrateWorker);if(worker?.value)hydrateWorker();
    var records=<?php echo json_encode($registros_formulario, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var recordSelect=document.getElementById('restrRegistro');
    var fields={carta_firmada:'restrCarta',fecha_entrega_carta:'restrFechaCarta',sst_fecha_programada:'restrSstProg',sst_fecha_real:'restrSstReal',sst_responsable:'restrSstResp',sst_estado:'restrSstEstado',sst_historial:'restrSstHist',arl_fecha_real:'restrArlReal',arl_responsable:'restrArlResp',arl_historial:'restrArlHist'};
    function hydrateRecord(){var data=records[recordSelect?.value]||{};Object.entries(fields).forEach(function(entry){var input=document.getElementById(entry[1]);if(input)input.value=data[entry[0]]||'';});}
    recordSelect?.addEventListener('change',hydrateRecord);if(recordSelect?.value)hydrateRecord();
});
</script>
</body>
</html>
