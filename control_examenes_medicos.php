<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar5_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar5_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'sst') {
    header('Location: estandar5?modulo=evaluaciones-medicas&msg=' . urlencode('Solo el responsable SST puede cargar resultados médicos.') . '&tipo=error');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();

function em_cargo(array $row): string
{
    return trim((string)($row['grupo_nombre'] ?? '')) ?: (trim((string)($row['tipo_personal'] ?? '')) ?: 'Sin cargo registrado');
}

function em_rows(PDO $conn, string $sql, array $params): array
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$trabajadores = em_rows($conn, "SELECT u.id,u.nombre,u.apellido,u.cedula,u.email,g.nombre AS grupo_nombre,e.tipo_personal FROM usuarios u LEFT JOIN grupos_personal g ON g.id=u.grupo_id LEFT JOIN encuesta_sociodemografica e ON e.usuario_id=u.id WHERE u.empresa_id=? AND u.rol='trabajador' ORDER BY u.nombre,u.apellido", [$empresa_id]);
$perfiles = em_rows($conn, "SELECT * FROM estandar5_perfiles_cargo WHERE empresa_id=? AND estado='activo' ORDER BY nombre_cargo", [$empresa_id]);
$centros = em_rows($conn, "SELECT * FROM estandar5_centros_medicos WHERE empresa_id=? AND estado='activo' ORDER BY nombre", [$empresa_id]);
$solicitudes = em_rows($conn, "SELECT em.*,u.nombre,u.apellido,u.cedula,p.nombre_cargo,c.nombre AS centro_nombre FROM estandar5_evaluaciones_medicas em INNER JOIN usuarios u ON u.id=em.trabajador_id INNER JOIN estandar5_perfiles_cargo p ON p.id=em.perfil_cargo_id INNER JOIN estandar5_centros_medicos c ON c.id=em.centro_medico_id WHERE em.empresa_id=? ORDER BY em.creado_en DESC LIMIT 80", [$empresa_id]);
$soportes = em_rows($conn, "SELECT s.*,p.nombre_cargo,c.nombre AS centro_registrado FROM estandar5_evaluaciones_medicas_soportes s LEFT JOIN estandar5_perfiles_cargo p ON p.id=s.perfil_cargo_id LEFT JOIN estandar5_centros_medicos c ON c.id=s.centro_medico_id WHERE s.empresa_id=? ORDER BY s.creado_en DESC LIMIT 40", [$empresa_id]);
$pendientes = count(array_filter($solicitudes, fn($row) => ($row['estado'] ?? '') !== 'realizada'));
$alertas = count(array_filter($soportes, function ($row) {
    if (empty($row['fecha_vencimiento'])) return false;
    $fecha = strtotime($row['fecha_vencimiento']);
    return $fecha !== false && $fecha <= strtotime('+90 days');
}));
$msg = trim((string)($_GET['msg'] ?? ''));
$tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de exámenes médicos | Estándar 5</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar5-evaluaciones.css">
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="em-page">
        <header class="em-head">
            <div class="em-head-copy"><i class="fa-solid fa-file-waveform"></i><div><h1>Control de exámenes médicos realizados</h1><p>Carga el concepto médico, confirma la aptitud y controla las próximas fechas de seguimiento.</p></div></div>
            <a class="em-back" href="estandar5?modulo=evaluaciones-medicas"><i class="fa-solid fa-arrow-left"></i> Volver a evaluaciones</a>
        </header>

        <?php if ($msg !== ''): ?><div class="em-alert <?php echo htmlspecialchars($tipo_msg); ?>"><i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i><span><?php echo htmlspecialchars($msg); ?></span></div><?php endif; ?>

        <section class="em-summary">
            <article class="em-kpi"><i class="fa-solid fa-users"></i><span>Trabajadores</span><strong><?php echo count($trabajadores); ?></strong><small>Personal disponible para asociar al soporte.</small></article>
            <article class="em-kpi blue"><i class="fa-solid fa-paper-plane"></i><span>Solicitudes</span><strong><?php echo count($solicitudes); ?></strong><small>Programaciones médicas registradas.</small></article>
            <article class="em-kpi green"><i class="fa-solid fa-file-circle-check"></i><span>Soportes cargados</span><strong><?php echo count($soportes); ?></strong><small>Conceptos médicos disponibles.</small></article>
            <article class="em-kpi violet"><i class="fa-solid fa-triangle-exclamation"></i><span>Alertas</span><strong><?php echo $alertas; ?></strong><small><?php echo $pendientes; ?> solicitudes aún pendientes de resultado.</small></article>
        </section>

        <div class="em-shell">
            <section class="em-card">
                <div class="em-card-head"><h2>Registrar resultado y soporte</h2><p>Organizamos el formulario por etapas para que sea más fácil confirmar la información antes de guardar.</p><i class="fa-solid fa-notes-medical"></i></div>
                <form class="em-form" action="procesar_estandar5.php" method="POST" enctype="multipart/form-data" id="medicalSupportForm">
                    <input type="hidden" name="accion" value="guardar_soporte_evaluacion_medica">
                    <input type="hidden" name="origen_formulario" value="control-examenes">
                    <input type="hidden" name="texto_extraido" id="textoExtraidoInput">

                    <section class="em-section">
                        <div class="em-section-title"><i class="fa-solid fa-user-check"></i> 1. Trabajador y programación</div>
                        <div class="em-worker-filter"><div class="em-field"><label for="workerFilter">Buscar por nombre o identificación</label><input type="search" id="workerFilter" placeholder="Escribe nombre, cédula o correo"></div><span class="em-filter-status" id="workerFilterStatus">Selecciona el trabajador del resultado.</span></div>
                        <div class="em-grid">
                            <div class="em-field wide"><label for="supportTrabajador">Trabajador *</label><select name="trabajador_id" id="supportTrabajador" required><option value="">Selecciona...</option><?php foreach ($trabajadores as $trabajador): $cargo=em_cargo($trabajador); ?><option value="<?php echo (int)$trabajador['id']; ?>" data-nombre="<?php echo htmlspecialchars(trim($trabajador['nombre'].' '.$trabajador['apellido'])); ?>" data-cedula="<?php echo htmlspecialchars($trabajador['cedula']); ?>" data-cargo="<?php echo htmlspecialchars($cargo); ?>"><?php echo htmlspecialchars(trim($trabajador['nombre'].' '.$trabajador['apellido']).' · '.$trabajador['cedula'].' · '.$cargo); ?></option><?php endforeach; ?></select></div>
                            <div class="em-field"><label for="supportSolicitud">Solicitud previa</label><select name="evaluacion_id" id="supportSolicitud"><option value="">Sin solicitud vinculada</option><?php foreach ($solicitudes as $sol): ?><option value="<?php echo (int)$sol['id']; ?>" data-trabajador="<?php echo (int)$sol['trabajador_id']; ?>" data-perfil="<?php echo (int)$sol['perfil_cargo_id']; ?>" data-centro="<?php echo (int)$sol['centro_medico_id']; ?>" data-tipo="<?php echo htmlspecialchars($sol['tipo_examen']); ?>"><?php echo htmlspecialchars(date('d/m/Y',strtotime($sol['creado_en'])).' · '.$sol['cedula'].' · '.$sol['tipo_examen']); ?></option><?php endforeach; ?></select></div>
                            <div class="em-field"><label for="supportPerfil">Perfil de cargo</label><select name="perfil_cargo_id" id="supportPerfil"><option value="">Sin perfil</option><?php foreach ($perfiles as $perfil): ?><option value="<?php echo (int)$perfil['id']; ?>" data-operacion="<?php echo htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto'); ?>" data-cargo="<?php echo htmlspecialchars($perfil['nombre_cargo']); ?>"><?php echo htmlspecialchars($perfil['nombre_cargo'].' · '.($perfil['tipo_operacion'] ?? 'Mixto')); ?></option><?php endforeach; ?></select></div>
                            <div class="em-field wide"><label for="supportCentro">Centro médico</label><select name="centro_medico_id" id="supportCentro"><option value="">Sin centro vinculado</option><?php foreach ($centros as $centro): ?><option value="<?php echo (int)$centro['id']; ?>" data-nombre="<?php echo htmlspecialchars($centro['nombre']); ?>"><?php echo htmlspecialchars($centro['nombre'].' · '.$centro['direccion_principal']); ?></option><?php endforeach; ?></select></div>
                        </div>
                    </section>

                    <section class="em-section">
                        <div class="em-section-title"><i class="fa-solid fa-file-arrow-up"></i> 2. Soporte médico</div>
                        <label class="em-file" id="medicalFileBox"><input type="file" name="archivo_pdf" id="archivoPdfMedico" accept="application/pdf,.pdf" required><i class="fa-solid fa-file-pdf em-file-icon"></i><span class="em-file-copy"><strong>Selecciona el concepto o certificado en PDF</strong><span id="medicalFileName">El sistema intentará completar algunos campos automáticamente.</span></span><span class="em-file-action">Seleccionar archivo</span></label>
                    </section>

                    <section class="em-section">
                        <div class="em-section-title"><i class="fa-solid fa-stethoscope"></i> 3. Resultado de la evaluación</div>
                        <div class="em-grid">
                            <div class="em-field wide"><label for="nombreTrabajadorMedico">Nombres y apellidos *</label><input name="nombre_trabajador" id="nombreTrabajadorMedico" required></div>
                            <div class="em-field"><label for="cedulaTrabajadorMedico">No. identificación *</label><input name="cedula" id="cedulaTrabajadorMedico" required></div>
                            <div class="em-field"><label for="cargoTrabajadorMedico">Cargo</label><input name="cargo" id="cargoTrabajadorMedico"></div>
                            <div class="em-field"><label for="tipoExamenMedico">Tipo de examen</label><select name="tipo_examen" id="tipoExamenMedico"><option value="">Selecciona...</option><option value="Ingreso">Ingreso</option><option value="Periodico">Periódico</option><option value="Levantamiento Restricciones">Levantamiento restricciones</option><option value="Retiro">Retiro</option><option value="Post-Incapacidad">Post incapacidad</option></select></div>
                            <div class="em-field"><label for="resultadoMedico">Resultado</label><select name="resultado" id="resultadoMedico"><option value="">Selecciona...</option><option value="Restriccion">Restricción</option><option value="Apto">Apto</option><option value="No Apto">No apto</option></select></div>
                            <div class="em-field"><label for="aptitudMedico">Tipo de aptitud</label><select name="tipo_aptitud" id="aptitudMedico"><option value="">Selecciona...</option><option value="Sin restricciones">Sin restricciones</option><option value="Con recomendaciones">Con recomendaciones</option><option value="Con restricciones">Con restricciones</option></select></div>
                            <div class="em-field"><label for="centroMedicoTexto">Centro que expide</label><input name="centro_medico" id="centroMedicoTexto"></div>
                        </div>
                    </section>

                    <section class="em-section">
                        <div class="em-section-title"><i class="fa-solid fa-calendar-check"></i> 4. Vigencia y seguimiento</div>
                        <div class="em-grid">
                            <div class="em-field"><label for="fechaExpedicionMedico">Fecha de expedición</label><input type="date" name="fecha_expedicion" id="fechaExpedicionMedico"></div>
                            <div class="em-field"><label for="fechaVencimientoMedico">Fecha de vencimiento</label><input type="date" name="fecha_vencimiento" id="fechaVencimientoMedico"></div>
                            <div class="em-field"><label for="tiempoProgramarMedico">Momento para programar</label><input name="tiempo_para_programar" id="tiempoProgramarMedico" placeholder="Ej. Programar renovación"></div>
                            <div class="em-field"><label for="diasAccionMedico">Días para la acción</label><input type="number" name="dias_accion" id="diasAccionMedico"></div>
                            <div class="em-field full"><label for="observacionesSoporte">Observaciones internas</label><textarea name="observaciones" id="observacionesSoporte" placeholder="Restricciones, recomendaciones o notas para seguimiento SST."></textarea></div>
                        </div>
                    </section>
                    <div class="em-submit-row"><span class="em-note" id="extractStatus">Periodicidad sugerida: operativo/mixto 18 meses, administrativo 36 meses.</span><button class="em-submit" type="submit"><i class="fa-solid fa-cloud-arrow-up"></i> Guardar examen realizado</button></div>
                </form>
            </section>

            <aside class="em-side">
                <article class="em-side-card"><i class="fa-solid fa-shield-heart watermark"></i><h3>Antes de guardar</h3><p>Confirma que el soporte corresponda al trabajador seleccionado.</p><div class="em-check-list"><div class="em-check"><i class="fa-solid fa-circle-check"></i><span>Vincula la solicitud previa cuando exista.</span></div><div class="em-check"><i class="fa-solid fa-circle-check"></i><span>Revisa resultado, aptitud y fechas extraídas.</span></div><div class="em-check"><i class="fa-solid fa-circle-check"></i><span>Registra únicamente información de seguimiento SST.</span></div></div></article>
                <article class="em-side-card"><i class="fa-solid fa-chart-line watermark"></i><h3>Estado documental</h3><p><?php echo count($soportes); ?> soporte(s) cargado(s) y <?php echo $pendientes; ?> solicitud(es) pendiente(s) de resultado.</p><div class="em-check-list"><div class="em-check"><i class="fa-solid fa-bell"></i><span><?php echo $alertas; ?> alerta(s) requieren revisión por fecha.</span></div></div></article>
            </aside>
        </div>

        <section class="em-history">
            <div class="em-history-head"><div><h2>Soportes cargados recientemente</h2><p>Consulta rápida de los últimos conceptos médicos registrados.</p></div><span class="em-badge"><i class="fa-solid fa-file-medical"></i> <?php echo count($soportes); ?> registros</span></div>
            <?php if (empty($soportes)): ?><div class="em-empty"><i class="fa-solid fa-folder-open"></i> Aún no hay soportes médicos cargados.</div><?php else: ?><div class="em-history-list"><?php foreach (array_slice($soportes,0,8) as $soporte): ?><article class="em-history-item"><div><strong><?php echo htmlspecialchars($soporte['nombre_trabajador']); ?></strong><span>C.C. <?php echo htmlspecialchars($soporte['cedula']); ?> · <?php echo htmlspecialchars($soporte['cargo'] ?: ($soporte['nombre_cargo'] ?? 'Sin cargo')); ?></span></div><div><strong><?php echo htmlspecialchars($soporte['tipo_examen'] ?: 'Sin tipo'); ?></strong><span><?php echo htmlspecialchars($soporte['resultado'] ?: 'Sin resultado'); ?> · <?php echo htmlspecialchars($soporte['tipo_aptitud'] ?: 'Sin aptitud'); ?></span></div><div><strong><?php echo htmlspecialchars($soporte['centro_medico'] ?: ($soporte['centro_registrado'] ?? 'Sin centro')); ?></strong><span>Vence: <?php echo $soporte['fecha_vencimiento'] ? htmlspecialchars(date('d/m/Y',strtotime($soporte['fecha_vencimiento']))) : 'Sin fecha'; ?></span></div><a class="em-pdf" href="<?php echo htmlspecialchars($soporte['archivo_pdf']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ver soporte</a></article><?php endforeach; ?></div><?php endif; ?>
        </section>
    </div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const trabajador=document.getElementById('supportTrabajador'),solicitud=document.getElementById('supportSolicitud'),perfil=document.getElementById('supportPerfil'),centro=document.getElementById('supportCentro'),archivo=document.getElementById('archivoPdfMedico'),filter=document.getElementById('workerFilter'),filterStatus=document.getElementById('workerFilterStatus');
    const fields={nombre:document.getElementById('nombreTrabajadorMedico'),cedula:document.getElementById('cedulaTrabajadorMedico'),cargo:document.getElementById('cargoTrabajadorMedico'),tipo:document.getElementById('tipoExamenMedico'),centroTexto:document.getElementById('centroMedicoTexto'),fechaExp:document.getElementById('fechaExpedicionMedico'),fechaVen:document.getElementById('fechaVencimientoMedico'),dias:document.getElementById('diasAccionMedico'),programar:document.getElementById('tiempoProgramarMedico')};
    const options=[...(trabajador?.options||[])].map(option=>({option,text:option.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')}));
    filter?.addEventListener('input',function(){const q=filter.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');let n=0;options.forEach(({option,text})=>{const show=!option.value||text.includes(q);option.hidden=!show;if(option.value&&show)n++;});filterStatus.textContent=q?`${n} trabajador(es) encontrado(s).`:'Selecciona el trabajador del resultado.';});
    trabajador?.addEventListener('change',function(){const o=trabajador.selectedOptions[0];fields.nombre.value=o?.dataset.nombre||'';fields.cedula.value=o?.dataset.cedula||'';fields.cargo.value=o?.dataset.cargo||'';});
    solicitud?.addEventListener('change',function(){const o=solicitud.selectedOptions[0];if(o?.dataset.trabajador){trabajador.value=o.dataset.trabajador;trabajador.dispatchEvent(new Event('change'));}if(o?.dataset.perfil){perfil.value=o.dataset.perfil;perfil.dispatchEvent(new Event('change'));}if(o?.dataset.centro){centro.value=o.dataset.centro;centro.dispatchEvent(new Event('change'));}if(o?.dataset.tipo){const map={'Post incapacidad':'Post-Incapacidad','Egreso':'Retiro'};fields.tipo.value=map[o.dataset.tipo]||o.dataset.tipo;}});
    centro?.addEventListener('change',function(){fields.centroTexto.value=centro.selectedOptions[0]?.dataset.nombre||'';});
    function iso(date){return date.toISOString().slice(0,10)}function addMonths(value,months){const d=new Date(value+'T00:00:00');d.setMonth(d.getMonth()+months);return iso(d)}function days(value){return Math.ceil((new Date(value+'T00:00:00')-new Date())/86400000)}
    function refreshDates(){if(fields.fechaExp.value&&!fields.fechaVen.value){const op=perfil.selectedOptions[0]?.dataset.operacion||'Mixto';fields.fechaVen.value=addMonths(fields.fechaExp.value,op==='Administrativo'?36:18);}if(fields.fechaVen.value){fields.dias.value=days(fields.fechaVen.value);fields.programar.value=days(fields.fechaVen.value)<=90?'Programar renovación':'Seguimiento';}}
    perfil?.addEventListener('change',function(){const cargo=perfil.selectedOptions[0]?.dataset.cargo;if(cargo&&(!fields.cargo.value||fields.cargo.value==='Sin cargo registrado'))fields.cargo.value=cargo;refreshDates();});fields.fechaExp?.addEventListener('change',refreshDates);fields.fechaVen?.addEventListener('change',refreshDates);
    archivo?.addEventListener('change',async function(){const file=archivo.files?.[0],box=document.getElementById('medicalFileBox'),name=document.getElementById('medicalFileName'),status=document.getElementById('extractStatus');box.classList.toggle('has-file',!!file);name.textContent=file?file.name:'El sistema intentará completar algunos campos automáticamente.';if(!file||!window.pdfjsLib)return;try{status.textContent='Leyendo PDF para apoyar el diligenciamiento...';pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';const pdf=await pdfjsLib.getDocument({data:await file.arrayBuffer()}).promise,pages=[];for(let i=1;i<=pdf.numPages;i++){const p=await pdf.getPage(i),c=await p.getTextContent();pages.push(c.items.map(x=>x.str).join(' '));}document.getElementById('textoExtraidoInput').value=pages.join('\n').slice(0,60000);status.textContent='PDF leído. Confirma manualmente los datos antes de guardar.';}catch(e){status.textContent='PDF cargado. Confirma los campos manualmente antes de guardar.';}});
});
</script>
</body>
</html>
