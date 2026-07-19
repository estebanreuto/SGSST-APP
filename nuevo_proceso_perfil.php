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
    header('Location: estandar5?modulo=perfiles-cargo&msg=' . urlencode('Solo el responsable SST puede registrar procesos.') . '&tipo=error');
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
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Nuevo proceso | Estándar 5</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="assets/estandar5-perfiles.css">
<style>*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,sans-serif;background:linear-gradient(180deg,#edf4fb,#f7f9fc);color:#1f2d3d;font-size:.82rem}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}}</style></head>
<body><?php include 'components/sidebar.php'; ?><main class="main-wrapper"><?php include 'components/header.php'; ?><div class="pc-page">
<header class="pc-head"><div class="pc-head-copy"><div class="pc-head-icon"><i class="fa-solid fa-diagram-project"></i></div><div><h1>Crear proceso del perfil</h1><p>Organiza los cargos por áreas o procesos reutilizables antes de construir el perfil.</p></div></div><a class="pc-back" href="estandar5?modulo=perfiles-cargo"><i class="fa-solid fa-arrow-left"></i> Volver al módulo</a></header>
<?php if ($msg !== ''): ?><div class="pc-alert <?php echo htmlspecialchars($tipo_msg); ?>"><i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i><span><?php echo htmlspecialchars($msg); ?></span></div><?php endif; ?>
<nav class="pc-flow" aria-label="Flujo de creación de perfiles"><a class="pc-flow-step <?php echo !empty($centros) ? 'done' : ''; ?>" href="nuevo_centro_medico"><span class="pc-flow-number">1</span><span><strong>Centro médico</strong><small><?php echo count($centros); ?> registrado(s)</small></span><i class="fa-solid fa-hospital pc-flow-watermark" aria-hidden="true"></i></a><a class="pc-flow-step active" href="nuevo_proceso_perfil"><span class="pc-flow-number">2</span><span><strong>Proceso</strong><small>Área o proceso del cargo</small></span><i class="fa-solid fa-diagram-project pc-flow-watermark" aria-hidden="true"></i></a><a class="pc-flow-step" href="nuevo_perfil_cargo"><span class="pc-flow-number">3</span><span><strong>Perfil de cargo</strong><small>Tareas, riesgos y herramientas</small></span><i class="fa-solid fa-user-gear pc-flow-watermark" aria-hidden="true"></i></a></nav>
<div class="pc-form-shell"><section class="pc-form-card"><div class="pc-form-head"><h2>Nuevo proceso</h2><p>Usa un nombre claro y corto. El proceso quedará disponible en todos los perfiles futuros.</p><i class="fa-solid fa-diagram-project pc-form-watermark" aria-hidden="true"></i></div><form class="pc-form" action="procesar_estandar5.php" method="POST"><input type="hidden" name="accion" value="guardar_proceso_perfil"><input type="hidden" name="origen_formulario" value="proceso"><div class="pc-form-grid"><div class="pc-field full"><label>Nombre del proceso *</label><input name="nombre" required maxlength="160" placeholder="Ej. Gestión administrativa, Producción o Mantenimiento"><div class="pc-hint">No necesitas repetirlo si ya aparece en la lista inferior.</div></div></div><div class="pc-submit-row"><a class="pc-cancel" href="estandar5?modulo=perfiles-cargo">Cancelar</a><button class="pc-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar proceso</button></div></form></section>
<aside class="pc-guide"><div class="pc-guide-icon"><i class="fa-solid fa-sitemap"></i></div><h3>Procesos disponibles</h3><p>Ya hay <?php echo count($procesos); ?> proceso(s) para reutilizar.</p><div class="pc-guide-list"><?php if (empty($procesos)): ?><div class="pc-guide-item"><i class="fa-solid fa-circle-info"></i><span>Aún no hay procesos. Crea el primero para continuar.</span></div><?php else: ?><?php foreach (array_slice($procesos, 0, 8) as $proceso): ?><div class="pc-guide-item"><i class="fa-solid fa-circle-check"></i><span><?php echo htmlspecialchars($proceso); ?></span></div><?php endforeach; ?><?php endif; ?></div><?php if (!empty($procesos)): ?><div class="pc-submit-row"><a class="pc-submit" href="nuevo_perfil_cargo"><i class="fa-solid fa-arrow-right"></i> Crear perfil</a></div><?php endif; ?></aside></div>
</div></main></body></html>
