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
    header('Location: estandar5?modulo=perfiles-cargo&msg=' . urlencode('Solo el responsable SST puede registrar centros médicos.') . '&tipo=error');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();
$centros = perfil5_centros($conn, $empresa_id);
$procesos = perfil5_procesos($conn, $empresa_id);
$msg = trim((string)($_GET['msg'] ?? ''));
$tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo centro médico | Estándar 5</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar5-perfiles.css">
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,sans-serif;background:linear-gradient(180deg,#edf4fb,#f7f9fc);color:#1f2d3d;font-size:.82rem}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="pc-page">
        <header class="pc-head">
            <div class="pc-head-copy">
                <div class="pc-head-icon"><i class="fa-solid fa-hospital"></i></div>
                <div><h1>Registrar centro médico autorizado</h1><p>Crea el proveedor y deja listas sus licencias para asociarlo a los perfiles de cargo.</p></div>
            </div>
            <a class="pc-back" href="estandar5?modulo=perfiles-cargo"><i class="fa-solid fa-arrow-left"></i> Volver al módulo</a>
        </header>

        <?php if ($msg !== ''): ?>
            <div class="pc-alert <?php echo htmlspecialchars($tipo_msg); ?>"><i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i><span><?php echo htmlspecialchars($msg); ?></span></div>
        <?php endif; ?>

        <nav class="pc-flow" aria-label="Flujo de creación de perfiles">
            <a class="pc-flow-step active" href="nuevo_centro_medico"><span class="pc-flow-number">1</span><span><strong>Centro médico</strong><small>Proveedor y licencias</small></span><i class="fa-solid fa-hospital pc-flow-watermark" aria-hidden="true"></i></a>
            <a class="pc-flow-step <?php echo !empty($procesos) ? 'done' : ''; ?>" href="nuevo_proceso_perfil"><span class="pc-flow-number">2</span><span><strong>Proceso</strong><small>Área o proceso del cargo</small></span><i class="fa-solid fa-diagram-project pc-flow-watermark" aria-hidden="true"></i></a>
            <a class="pc-flow-step" href="nuevo_perfil_cargo"><span class="pc-flow-number">3</span><span><strong>Perfil de cargo</strong><small>Tareas, riesgos y herramientas</small></span><i class="fa-solid fa-user-gear pc-flow-watermark" aria-hidden="true"></i></a>
        </nav>

        <div class="pc-form-shell">
            <section class="pc-form-card">
                <div class="pc-form-head"><h2>Datos del centro</h2><p>Los campos marcados con * son obligatorios. Las licencias pueden cargarse en PDF o imagen.</p><i class="fa-solid fa-hospital-user pc-form-watermark" aria-hidden="true"></i></div>
                <form class="pc-form" action="procesar_estandar5.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="guardar_centro_medico">
                    <input type="hidden" name="origen_formulario" value="centro">
                    <div class="pc-form-grid">
                        <div class="pc-field"><label>Nombre del centro *</label><input name="nombre" required maxlength="180" placeholder="Ej. IPS Salud Ocupacional"></div>
                        <div class="pc-field"><label>NIT *</label><input name="nit" required maxlength="40" placeholder="900123456-7"></div>
                        <div class="pc-field full"><label>Dirección principal *</label><input name="direccion_principal" required maxlength="220" placeholder="Dirección principal del centro"></div>
                        <div class="pc-field"><label>Teléfono *</label><input name="telefono" required maxlength="50" placeholder="Teléfono o celular"></div>
                        <div class="pc-field"><label>Correo *</label><input type="email" name="correo" required maxlength="180" placeholder="contacto@centro.com"></div>
                        <div class="pc-field full">
                            <label>Sedes adicionales</label>
                            <div class="pc-dynamic" data-dynamic-list="sedes">
                                <div class="pc-dynamic-row"><input name="sedes[]" maxlength="220" placeholder="Dirección de sede opcional"><button class="pc-icon-btn" type="button" data-remove-row aria-label="Quitar sede"><i class="fa-solid fa-xmark"></i></button></div>
                            </div>
                            <button class="pc-add" type="button" data-add-row="sedes"><i class="fa-solid fa-plus"></i> Agregar otra sede</button>
                        </div>
                        <div class="pc-field"><label>Licencia de funcionamiento</label><div class="pc-file pc-file-drop"><input type="file" name="licencia_funcionamiento" accept=".pdf,.png,.jpg,.jpeg,.webp" data-file-input><span class="pc-file-icon"><i class="fa-solid fa-file-shield"></i></span><span class="pc-file-copy"><strong>Adjuntar licencia vigente</strong><small data-file-name>PDF o imagen · máximo permitido por el sistema</small></span><span class="pc-file-action">Seleccionar</span></div><div class="pc-hint">Documento vigente del centro.</div></div>
                        <div class="pc-field"><label>Licencia SST</label><div class="pc-file pc-file-drop"><input type="file" name="licencia_sst" accept=".pdf,.png,.jpg,.jpeg,.webp" data-file-input><span class="pc-file-icon"><i class="fa-solid fa-id-card-clip"></i></span><span class="pc-file-copy"><strong>Adjuntar licencia SST</strong><small data-file-name>PDF o imagen del médico o prestador</small></span><span class="pc-file-action">Seleccionar</span></div><div class="pc-hint">Licencia del médico o del prestador.</div></div>
                    </div>
                    <div class="pc-submit-row"><a class="pc-cancel" href="estandar5?modulo=perfiles-cargo">Cancelar</a><button class="pc-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar centro médico</button></div>
                </form>
            </section>
            <aside class="pc-guide">
                <div class="pc-guide-icon"><i class="fa-solid fa-shield-heart"></i></div><h3>Antes de guardar</h3><p>Verifica que el prestador esté autorizado para evaluaciones médicas ocupacionales.</p>
                <div class="pc-guide-list">
                    <div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Ya tienes <?php echo count($centros); ?> centro(s) activo(s).</span></div>
                    <div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Registra datos de contacto que pueda usar el responsable SST.</span></div>
                    <div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span>Después podrás asociarlo directamente a cada perfil.</span></div>
                </div>
            </aside>
        </div>
    </div>
</main>
<script>
document.querySelectorAll('[data-add-row]').forEach(function(button){button.addEventListener('click',function(){var list=document.querySelector('[data-dynamic-list="'+button.dataset.addRow+'"]');var row=list.querySelector('.pc-dynamic-row').cloneNode(true);row.querySelector('input').value='';list.appendChild(row);syncRows(list);});});
document.addEventListener('click',function(event){var button=event.target.closest('[data-remove-row]');if(!button)return;var list=button.closest('.pc-dynamic');if(list.children.length>1){button.closest('.pc-dynamic-row').remove();}else{button.closest('.pc-dynamic-row').querySelector('input').value='';}syncRows(list);});
function syncRows(list){list.querySelectorAll('[data-remove-row]').forEach(function(button){button.disabled=list.children.length===1;});}document.querySelectorAll('.pc-dynamic').forEach(syncRows);
document.querySelectorAll('[data-file-input]').forEach(function(input){input.addEventListener('change',function(){var box=input.closest('.pc-file-drop');var name=box.querySelector('[data-file-name]');var selected=input.files&&input.files[0];box.classList.toggle('has-file',!!selected);name.textContent=selected?selected.name:(input.name==='licencia_sst'?'PDF o imagen del médico o prestador':'PDF o imagen · máximo permitido por el sistema');box.querySelector('.pc-file-action').textContent=selected?'Cambiar':'Seleccionar';});});
</script>
</body>
</html>
