<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar5_schema.php';
require_once 'config/estandar5_perfiles_helpers.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar5_schema($conn);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'sst') {
    header('Location: estandar5?modulo=perfiles-cargo&msg=' . urlencode('Solo el responsable SST puede crear perfiles de cargo.') . '&tipo=error');
    exit;
}
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();
$centros = perfil5_centros($conn, $empresa_id);
$procesos = perfil5_procesos($conn, $empresa_id);
$herramientas = perfil5_herramientas();
$riesgos = perfil5_tareas_alto_riesgo();
$msg = trim((string)($_GET['msg'] ?? ''));
$tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Nuevo perfil de cargo | Estándar 5</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar5-perfiles.css">
    <style>*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,sans-serif;background:linear-gradient(180deg,#edf4fb,#f7f9fc);color:#1f2d3d;font-size:.82rem}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}.pc-submit{text-decoration:none}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}}</style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper"><?php include 'components/header.php'; ?><div class="pc-page">
    <header class="pc-head"><div class="pc-head-copy"><div class="pc-head-icon"><i class="fa-solid fa-user-gear"></i></div><div><h1>Crear perfil de cargo</h1><p>Consolida el proceso, las funciones, herramientas y exposiciones que recibirá el médico ocupacional.</p></div></div><a class="pc-back" href="estandar5?modulo=perfiles-cargo"><i class="fa-solid fa-arrow-left"></i> Volver al módulo</a></header>
    <?php if ($msg !== ''): ?><div class="pc-alert <?php echo htmlspecialchars($tipo_msg); ?>"><i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i><span><?php echo htmlspecialchars($msg); ?></span></div><?php endif; ?>
    <nav class="pc-flow" aria-label="Flujo de creación de perfiles"><a class="pc-flow-step <?php echo !empty($centros) ? 'done' : ''; ?>" href="nuevo_centro_medico"><span class="pc-flow-number">1</span><span><strong>Centro médico</strong><small><?php echo count($centros); ?> registrado(s)</small></span><i class="fa-solid fa-hospital pc-flow-watermark" aria-hidden="true"></i></a><a class="pc-flow-step <?php echo !empty($procesos) ? 'done' : ''; ?>" href="nuevo_proceso_perfil"><span class="pc-flow-number">2</span><span><strong>Proceso</strong><small><?php echo count($procesos); ?> disponible(s)</small></span><i class="fa-solid fa-diagram-project pc-flow-watermark" aria-hidden="true"></i></a><a class="pc-flow-step active" href="nuevo_perfil_cargo"><span class="pc-flow-number">3</span><span><strong>Perfil de cargo</strong><small>Tareas, riesgos y herramientas</small></span><i class="fa-solid fa-user-gear pc-flow-watermark" aria-hidden="true"></i></a></nav>

    <div class="pc-form-shell">
        <section class="pc-form-card"><div class="pc-form-head"><h2>Información ocupacional del cargo</h2><p>Este perfil quedará como plantilla reutilizable y podrá descargarse en PDF desde el módulo principal.</p><i class="fa-solid fa-user-gear pc-form-watermark" aria-hidden="true"></i></div>
            <form class="pc-form" action="procesar_estandar5.php" method="POST">
                <input type="hidden" name="accion" value="guardar_perfil_cargo"><input type="hidden" name="origen_formulario" value="perfil">
                <div class="pc-form-grid">
                    <div class="pc-field"><label>Nombre del cargo *</label><input name="nombre_cargo" required maxlength="180" placeholder="Ej. Operario de producción"></div>
                    <div class="pc-field"><label>Tipo de operación *</label><select name="tipo_operacion" id="tipoOperacion" required><option value="">Selecciona...</option><option value="Administrativo">Administrativo</option><option value="Operativo">Operativo</option><option value="Mixto">Mixto</option></select></div>
                    <div class="pc-field"><label>Centro médico asociado</label><select name="centro_medico_id"><option value="0">Sin asignar por ahora</option><?php foreach ($centros as $centro): ?><option value="<?php echo (int)$centro['id']; ?>"><?php echo htmlspecialchars($centro['nombre']); ?></option><?php endforeach; ?></select><div class="pc-hint"><?php echo empty($centros) ? 'Puedes crear el centro primero o asignarlo después.' : 'Selecciona el prestador que recibirá el perfil.'; ?></div></div>
                    <div class="pc-field"><label>Jefe inmediato *</label><input name="jefe_inmediato" required maxlength="180" placeholder="Ej. Coordinador de planta"></div>
                    <div class="pc-field full"><label>Proceso *</label><select name="tipo_proceso_select" id="tipoProcesoSelect" required><option value="">Selecciona...</option><?php foreach ($procesos as $proceso): ?><option value="<?php echo htmlspecialchars($proceso); ?>"><?php echo htmlspecialchars($proceso); ?></option><?php endforeach; ?><option value="__nuevo_proceso__" <?php echo empty($procesos) ? 'selected' : ''; ?>>Crear otro proceso...</option></select>
                        <div class="pc-process-new" id="newProcessPanel"><input name="tipo_proceso_nuevo" id="tipoProcesoNuevo" maxlength="160" placeholder="Escribe el nuevo proceso"><div class="pc-save-process"><span class="pc-save-question">¿Cómo quieres usar este proceso?</span><div class="pc-save-options"><label class="pc-radio-card"><input type="radio" name="guardar_proceso" value="1" checked><span class="pc-radio-card-icon"><i class="fa-solid fa-bookmark"></i></span><span><strong>Guardar para futuros perfiles</strong><small>Quedará disponible en las próximas creaciones.</small></span></label><label class="pc-radio-card"><input type="radio" name="guardar_proceso" value="0"><span class="pc-radio-card-icon"><i class="fa-solid fa-file-circle-check"></i></span><span><strong>Usar solo en este perfil</strong><small>No se agregará a la lista de procesos.</small></span></label></div></div></div>
                    </div>
                    <div class="pc-field full"><label>Tareas del cargo *</label><div class="pc-dynamic" data-dynamic-list="tareas"><div class="pc-dynamic-row"><input name="tareas[]" required maxlength="300" placeholder="Describe una tarea concreta"><button class="pc-icon-btn" type="button" data-remove-row aria-label="Quitar tarea"><i class="fa-solid fa-xmark"></i></button></div></div><button class="pc-add" type="button" data-add-row="tareas"><i class="fa-solid fa-plus"></i> Agregar otra tarea</button></div>
                    <div class="pc-field full pc-choice-section"><div class="pc-choice-head"><span class="pc-choice-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><div><label>Herramientas autorizadas según la operación</label><p>Selecciona las herramientas que realmente puede utilizar el cargo. Las opciones cambian según el tipo de operación.</p></div></div><div class="pc-tools" id="toolsGroups"><?php foreach ($herramientas as $grupo_key => $grupo): ?><section class="pc-tool-group" data-tool-group="<?php echo htmlspecialchars($grupo_key); ?>"><h3><i class="fa-solid <?php echo htmlspecialchars($grupo['icon']); ?>"></i> <?php echo htmlspecialchars($grupo['titulo']); ?></h3><div class="pc-tool-checks"><?php foreach ($grupo['items'] as $item): ?><label class="pc-check"><input type="checkbox" name="herramientas_<?php echo htmlspecialchars($grupo_key); ?>[]" value="<?php echo htmlspecialchars($item); ?>"><span><?php echo htmlspecialchars($item); ?></span></label><?php endforeach; ?></div><input class="pc-tool-other" name="herramienta_otra_<?php echo htmlspecialchars($grupo_key); ?>" maxlength="180" placeholder="Otra herramienta"></section><?php endforeach; ?></div></div>
                    <div class="pc-field full pc-choice-section"><div class="pc-choice-head"><span class="pc-choice-icon"><i class="fa-solid fa-triangle-exclamation"></i></span><div><label>Tareas de alto riesgo</label><p>Marca todas las exposiciones que apliquen; las opciones seleccionadas quedarán resaltadas.</p></div></div><div class="pc-risk-grid"><?php foreach ($riesgos as $riesgo): ?><label class="pc-check"><input type="checkbox" name="tareas_alto_riesgo[]" value="<?php echo htmlspecialchars($riesgo); ?>"><span><?php echo htmlspecialchars($riesgo); ?></span></label><?php endforeach; ?></div></div>
                </div>
                <div class="pc-submit-row"><a class="pc-cancel" href="estandar5?modulo=perfiles-cargo">Cancelar</a><button class="pc-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar perfil de cargo</button></div>
            </form>
        </section>
        <aside class="pc-guide"><div class="pc-guide-icon"><i class="fa-solid fa-clipboard-check"></i></div><h3>Perfil listo para el médico</h3><p>Describe el trabajo real para que la evaluación ocupacional considere sus exposiciones.</p><div class="pc-guide-list"><div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Usa tareas específicas, no funciones genéricas.</span></div><div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Marca herramientas y actividades de alto riesgo aplicables.</span></div><div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Podrás reutilizar y descargar el perfil desde las cards.</span></div></div><?php if (empty($procesos)): ?><div class="pc-submit-row"><a class="pc-submit" href="nuevo_proceso_perfil"><i class="fa-solid fa-plus"></i> Crear proceso primero</a></div><?php endif; ?></aside>
    </div>
</div></main>
<script>
var operation=document.getElementById('tipoOperacion');var groups=document.querySelectorAll('[data-tool-group]');function syncTools(){var value=(operation.value||'').toLowerCase();groups.forEach(function(group){var key=group.dataset.toolGroup;var show=value==='mixto'||(value==='administrativo'&&key==='administrativo')||(value==='operativo'&&key!=='administrativo');group.classList.toggle('active',show);if(!show){group.querySelectorAll('input[type="checkbox"]').forEach(function(input){input.checked=false;});}});}operation.addEventListener('change',syncTools);syncTools();
var processSelect=document.getElementById('tipoProcesoSelect');var processPanel=document.getElementById('newProcessPanel');var processInput=document.getElementById('tipoProcesoNuevo');function syncProcess(){var isNew=processSelect.value==='__nuevo_proceso__';processPanel.classList.toggle('active',isNew);processInput.required=isNew;}processSelect.addEventListener('change',syncProcess);syncProcess();
document.querySelectorAll('[data-add-row]').forEach(function(button){button.addEventListener('click',function(){var list=document.querySelector('[data-dynamic-list="'+button.dataset.addRow+'"]');var row=list.querySelector('.pc-dynamic-row').cloneNode(true);row.querySelector('input').value='';list.appendChild(row);syncRows(list);});});document.addEventListener('click',function(event){var button=event.target.closest('[data-remove-row]');if(!button)return;var list=button.closest('.pc-dynamic');if(list.children.length>1){button.closest('.pc-dynamic-row').remove();}else{button.closest('.pc-dynamic-row').querySelector('input').value='';}syncRows(list);});function syncRows(list){list.querySelectorAll('[data-remove-row]').forEach(function(button){button.disabled=list.children.length===1;});}document.querySelectorAll('.pc-dynamic').forEach(syncRows);
</script>
</body></html>
