<?php
// ========================================================
// LÓGICA ESTÁNDAR 1 (DOCUMENTO DE ASIGNACIÓN)
// ========================================================
$doc_asignacion = null;
try {
    if ($usuario_rol === 'sst') {
        $stmt_doc = $conn->prepare("SELECT d.*, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula FROM doc_asignacion_sst d LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id WHERE d.sst_id = ? ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute([$_SESSION['usuario_id']]);
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    } elseif ($usuario_rol === 'representante') {
        $stmt_doc = $conn->prepare("SELECT d.*, u_sst.nombre as sst_nombre, u_sst.apellido as sst_apellido, u_sst.cedula as sst_cedula, u_sst.licencia_sst, u_sst.tipo_licencia as sst_tipo_licencia, u_sst.numero_licencia as sst_num_licencia, DATE_FORMAT(u_sst.fecha_licencia, '%d/%m/%Y') as sst_fecha_licencia, u_sst.ciudad as sst_ciudad, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula FROM doc_asignacion_sst d JOIN usuarios u_sst ON d.sst_id = u_sst.id LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id WHERE d.estado IN ('pendiente_firma', 'firmado') ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute();
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $doc_asignacion = null; }

$doc_empresa = "Sistemas P";  $doc_rol = "Responsable"; $doc_nombre = ""; $doc_cedula = ""; $doc_tipo_lic = ""; $doc_num_lic = ""; $doc_fecha_lic = ""; $doc_ciudad = ""; $doc_fecha_firma = "____/____/________"; $doc_firma_sst = ""; $doc_rep_nombre = ""; $doc_rep_cedula = ""; $doc_firma_rep = "";

if ($usuario_rol === 'sst') {
    $doc_nombre = ($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? ''); $doc_cedula = $usuario_info['cedula'] ?? ''; $doc_tipo_lic = $usuario_info['tipo_licencia'] ?? ''; $doc_num_lic = $usuario_info['numero_licencia'] ?? ''; $doc_fecha_lic = $usuario_info['fecha_licencia'] ?? ''; $doc_ciudad = $usuario_info['ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; $doc_rep_nombre = trim(($doc_asignacion['rep_nombre'] ?? '') . ' ' . ($doc_asignacion['rep_apellido'] ?? '')); $doc_rep_cedula = $doc_asignacion['rep_cedula'] ?? ''; $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
} elseif ($doc_asignacion) {
    $doc_nombre = ($doc_asignacion['sst_nombre'] ?? '') . ' ' . ($doc_asignacion['sst_apellido'] ?? ''); $doc_cedula = $doc_asignacion['sst_cedula'] ?? ''; $doc_tipo_lic = $doc_asignacion['sst_tipo_licencia'] ?? ''; $doc_num_lic = $doc_asignacion['sst_num_licencia'] ?? ''; $doc_fecha_lic = $doc_asignacion['sst_fecha_licencia'] ?? ''; $doc_ciudad = $doc_asignacion['sst_ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    if (!empty($doc_asignacion['rep_nombre'])) { $doc_rep_nombre = trim($doc_asignacion['rep_nombre'] . ' ' . $doc_asignacion['rep_apellido']); $doc_rep_cedula = $doc_asignacion['rep_cedula'];
    } else { $doc_rep_nombre = trim(($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? '')); $doc_rep_cedula = $usuario_info['cedula'] ?? ''; }
    if ($doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
}

// ========================================================
// LÓGICA ESTÁNDAR 2 (PLANILLAS DE SEGURIDAD SOCIAL)
// ========================================================
$meses_nombres = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$anio_seleccionado = isset($_GET['anio_planillas']) ? (int)$_GET['anio_planillas'] : (int)date('Y');

// Obtener planillas subidas para este año
$planillas_db = [];
try {
    $stmt_p = $conn->prepare("SELECT id, mes, archivo_url, fecha_subida FROM estandar2_planillas WHERE anio = ?");
    $stmt_p->execute([$anio_seleccionado]);
    while ($row = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
        $planillas_db[$row['mes']] = $row;
    }
} catch (PDOException $e) {}

$hoy = new DateTime();
$hoy->setTime(0, 0, 0);

// ========================================================
// ESTADOS DE LOS ESTÁNDARES (PARA LOS BADGES VISUALES)
// ========================================================

// Estado Estándar 1
$std1_estado = ($doc_asignacion && $doc_asignacion['estado'] === 'firmado') ? 'Completado' : 'Pendiente';
$std1_class = ($std1_estado === 'Completado') ? 'badge-std-success' : 'badge-std-pending';

// Estado Estándar 2 (Revisar si hay vencidos)
$std2_tiene_vencidos = false;
for ($m = 1; $m <= 12; $m++) {
    if (!isset($planillas_db[$m])) {
        $fecha_vencimiento = new DateTime("$anio_seleccionado-" . sprintf('%02d', $m) . "-10");
        $fecha_vencimiento->modify('+1 month'); 
        $fecha_vencimiento->setTime(0, 0, 0);
        $intervalo = $hoy->diff($fecha_vencimiento);
        $dias_restantes = (int)$intervalo->format('%R%a');
        if ($dias_restantes < 0) {
            $std2_tiene_vencidos = true;
            break;
        }
    }
}
$std2_estado = $std2_tiene_vencidos ? 'Pendiente' : 'Al Día';
$std2_class = $std2_tiene_vencidos ? 'badge-std-pending' : 'badge-std-success';

// Estado Estándares 3 al 7 (Por defecto en Pendiente hasta que se programen)
$std3_estado = 'Pendiente'; $std3_class = 'badge-std-pending';
$std4_estado = 'Pendiente'; $std4_class = 'badge-std-pending';
$std5_estado = 'Pendiente'; $std5_class = 'badge-std-pending';
$std6_estado = 'Pendiente'; $std6_class = 'badge-std-pending';
$std7_estado = 'Pendiente'; $std7_class = 'badge-std-pending';

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<style>
    /* Estilos base Acordeón y Documento */
    .accordion-container { display: flex; flex-direction: column; gap: 12px; margin-top: 16px; font-family: 'Inter', sans-serif; }
    .accordion-item { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; transition: all 0.3s ease; }
    .accordion-header { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: none; border: none; cursor: pointer; text-align: left; transition: background 0.3s ease; font-family: inherit; }
    .accordion-header.active { background: #fff8f3; border-bottom: 1px solid var(--border); }
    .header-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .accordion-header.active .icon-box { background: var(--primary); color: #fff; }
    .accordion-title { font-size: 0.9rem; font-weight: 600; color: var(--text); margin: 0; }
    .accordion-header.active .accordion-title { color: var(--primary2); }
    .chevron-icon { color: var(--muted); transition: transform 0.3s ease; }
    .accordion-header.active .chevron-icon { transform: rotate(180deg); color: var(--primary); }
    .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background: #fafbfc; }
    .content-inner { padding: 20px; color: var(--muted); font-size: 0.85rem; line-height: 1.6; }
    
    /* ========================================================
       ESTILOS PARA LOS BADGES DE ESTADO (Completado / Pendiente)
       ======================================================== */
    .badge-std {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }
    .badge-std-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-std-pending { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* VISTA ESTÁNDAR 1 */
    .document-format { background: #fff; border: 1px solid #e2e8f0; padding: 40px 50px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-top: 16px; color: #1f2d3d; font-size: 0.9rem; line-height: 1.6; text-align: justify; font-family: 'Inter', Arial, sans-serif; }
    .document-format p { margin-bottom: 16px; }
    .signatures-grid { display: flex; justify-content: space-between; gap: 40px; margin-top: 60px; }
    .sig-box { display: flex; flex-direction: column; align-items: center; text-align: center; width: 45%; }
    .sig-box .img-placeholder { height: 80px; width: 100%; display: flex; align-items: center; justify-content: center; }
    .sig-box img { max-width: 100%; height: 80px; object-fit: contain; }
    .sig-box .line { width: 100%; height: 1px; background: #94a3b8; margin-bottom: 10px; margin-top: 8px; }
    .sig-box p { margin: 0 0 4px 0 !important; font-size: 0.85rem; font-weight: 700; color: #1f2d3d; }
    .sig-box span { font-size: 0.8rem; color: #475569; font-weight: 500; margin-bottom: 2px; }
    .firma-box { border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; position: relative; margin: 0 auto 12px auto; width: 100%; max-width: 300px; overflow: hidden; }
    .firma-box canvas { width: 100%; height: 120px; cursor: crosshair; display: block; touch-action: none; background: white; }
    .btn-limpiar { position: absolute; top: 8px; right: 8px; background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); padding: 4px 8px; border-radius: 4px; font-size: .7rem; cursor: pointer; z-index: 10; font-family: inherit; }
    .alert-status { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
    .alert-status.pending { background: #fef08a; color: #854d0e; border: 1px solid #fde047; }
    .alert-status.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .toolbar-acta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; border-top: 1px dashed var(--border); padding-top: 20px; }
    .btn-pdf { background: #0f172a; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; font-family: inherit; }
    .btn-pdf:hover:not(:disabled) { background: #334155; }
    .btn-pdf:disabled { background: #94a3b8; cursor: wait; opacity: 0.8; }
    .btn-versiones { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; }
    .btn-versiones:hover { background: #e2e8f0; }

    /* VISTA ESTÁNDAR 2 (TARJETAS DE MESES) */
    .year-selector { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 24px; font-size: 1.2rem; font-weight: 700; color: var(--text); }
    .year-btn { background: #fff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 8px; color: var(--text); text-decoration: none; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .year-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
    
    .grid-meses { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
    .mes-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: 0.2s; position: relative; overflow: hidden; }
    .mes-card:hover { border-color: #cbd5e1; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); transform: translateY(-2px); }
    .mes-titulo { font-size: 1.1rem; font-weight: 700; color: var(--text); margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.05em; }
    
    .badge-estado { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; margin-bottom: 16px; }
    .bg-subido { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .bg-pendiente { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .bg-alerta { background: #fef08a; color: #854d0e; border: 1px solid #fde047; animation: pulse-border 2s infinite; }
    .bg-vencido { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    @keyframes pulse-border { 0% { box-shadow: 0 0 0 0 rgba(253, 224, 71, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(253, 224, 71, 0); } 100% { box-shadow: 0 0 0 0 rgba(253, 224, 71, 0); } }

    .acciones-mes { display: flex; justify-content: center; gap: 8px; }
    .btn-action { width: 100%; background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
    .btn-action.primary { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border: 1px solid transparent; }
    .btn-action.primary:hover { background: var(--primary); color: white; }
    .btn-action.danger { width: auto; background: #fee2e2; color: #dc2626; border-color: transparent; }
    .btn-action.danger:hover { background: #ef4444; color: white; }
    
    @media (max-width: 768px) {
        .document-format { padding: 20px; }
        .signatures-grid { flex-direction: column; gap: 40px; }
        .sig-box { width: 100%; }
        .toolbar-acta { flex-direction: column; }
        .toolbar-acta button, .toolbar-acta a { width: 100%; justify-content: center; }
        .grid-meses { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .grid-meses { grid-template-columns: 1fr; }
    }
</style>

<h2 class="section-title" style="margin-top: 40px; border-bottom: none; padding-bottom: 0;">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" style="color: var(--primary2);">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
    </svg>
    Estándares Mínimos (10 o menos trabajadores)
</h2>
<p style="color: var(--muted); font-size: 0.85rem; margin-top: 0; margin-bottom: 16px;">Resolución 0312 de 2019: Requisitos aplicables para empresas, empleadores y contratantes clasificados en riesgo I, II o III.</p>

<div class="accordion-container">
    
    <div class="accordion-item">
        <button class="accordion-header" id="btnAccordionEst1">
            <div class="header-left">
                <div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg></div>
                <span class="accordion-title">1. Asignación de persona que diseña el SG-SST</span>
                <span class="badge-std <?php echo $std1_class; ?>"><?php echo $std1_estado; ?></span>
            </div>
            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div class="accordion-content">
            <div class="content-inner" style="background: #f1f5f9;">
                <?php if ($usuario_rol === 'sst'): ?>
                    <?php if (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador'): ?>
                        <div class="alert-status pending">Genera el documento, dibuja tu firma al final de la hoja y envíalo al Representante Legal.</div>
                    <?php elseif ($doc_asignacion['estado'] === 'pendiente_firma'): ?>
                        <div class="alert-status pending"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Documento firmado por ti y enviado. Esperando la firma del Representante Legal...</div>
                    <?php elseif ($doc_asignacion['estado'] === 'firmado'): ?>
                        <div class="alert-status success"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Documento firmado por ambas partes y legalizado exitosamente.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                    <div class="alert-status pending" style="border-left: 4px solid #ca8a04;">El Responsable SG-SST solicita tu firma para legalizar su asignación. Por favor, revisa el documento y firma al final.</div>
                <?php endif; ?>

                <div class="document-format" id="acta-pantalla">
                    <h4 style="text-align: center; font-weight: bold; margin-bottom: 24px; color: #111; font-size: 1.15rem;">ACTA DE DESIGNACIÓN DEL RESPONSABLE DEL SG-SST</h4>
                    <p>En cumplimiento de lo establecido en la normatividad vigente en Seguridad y Salud en el Trabajo, la empresa <strong><?php echo htmlspecialchars($doc_empresa); ?></strong> designa como <strong><?php echo htmlspecialchars($doc_rol); ?></strong> del Sistema de Gestión de Seguridad y Salud en el Trabajo – SG-SST a:</p>
                    <p><strong><?php echo htmlspecialchars($doc_nombre); ?></strong>, identificado(a) con cédula No. <strong><?php echo htmlspecialchars($doc_cedula); ?></strong>, con licencia en Seguridad y Salud en el Trabajo de tipo <strong><?php echo htmlspecialchars($doc_tipo_lic); ?></strong>, No. <strong><?php echo htmlspecialchars($doc_num_lic); ?></strong>, expedida el <strong><?php echo htmlspecialchars($doc_fecha_lic); ?></strong>.</p>
                    <p>El responsable del SG-SST se compromete a liderar, coordinar y hacer seguimiento a las actividades del Sistema de Gestión de acuerdo con el Decreto 1072 de 2015, la Resolución 0312 de 2019 y demás normas aplicables, garantizando la mejora continua y la protección de la seguridad y salud de todos los trabajadores.</p>
                    <p>Para constancia se firma la presente en la ciudad de <strong><?php echo htmlspecialchars($doc_ciudad); ?></strong>, a los <strong><?php echo htmlspecialchars($doc_fecha_firma); ?></strong>.</p>

                    <div class="signatures-grid">
                        <div class="sig-box">
                            <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_firma_rep)): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_firma_rep; ?>"></div><div class="line"></div><p>Representante Legal</p><span><?php echo htmlspecialchars($doc_rep_nombre); ?></span><span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            <?php elseif ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaRep" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                                    <input type="hidden" name="accion" value="firmar_doc"><input type="hidden" name="doc_id" value="<?php echo $doc_asignacion['id']; ?>"><input type="hidden" name="firma_rep" id="firmaRepBase64">
                                    <div class="firma-box"><canvas id="canvasFirmaRep"></canvas><button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaRep()">Limpiar</button></div>
                                    <button type="button" onclick="confirmarEnvioRep(event)" class="btn-edit" style="width: 100%; max-width: 300px; justify-content: center; background: #16a34a; margin-top: 4px; margin-bottom: 16px;">Firmar Documento</button>
                                </form>
                                <div class="line"></div><p>Representante Legal</p><span><?php echo htmlspecialchars($doc_rep_nombre); ?></span><span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            <?php else: ?>
                                <div class="img-placeholder"></div><div class="line"></div><p>Representante Legal</p><span><?php echo htmlspecialchars($doc_rep_nombre); ?></span><span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="sig-box">
                            <?php if (!empty($doc_firma_sst)): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_firma_sst; ?>"></div><div class="line"></div>
                            <?php elseif ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaSST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                                    <input type="hidden" name="accion" value="enviar_firma"><input type="hidden" name="firma_sst" id="firmaSSTBase64">
                                    <div class="firma-box"><canvas id="canvasFirmaSST"></canvas><button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaSST()">Limpiar</button></div>
                            <?php else: ?>
                                <div class="img-placeholder"></div><div class="line"></div>
                            <?php endif; ?>
                            
                            <p <?php if($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')) echo 'style="margin-top:12px !important;"'; ?>>Responsable SG-SST</p>
                            <span><?php echo htmlspecialchars($doc_nombre); ?></span><span>C.C. <?php echo htmlspecialchars($doc_cedula); ?></span>

                            <?php if ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                    <button type="button" onclick="confirmarEnvioSST(event)" class="btn-edit" style="width: 100%; max-width: 300px; justify-content: center; background: var(--primary); margin-top: 16px;">Firmar y Enviar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado'): ?>
                    <div class="toolbar-acta">
                        <button id="btnDescargarPDF" onclick="generarYGuardarPDF()" class="btn-pdf">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Descargar y Guardar PDF
                        </button>
                        <button onclick="openVersionesModal()" class="btn-versiones">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Ver Versiones
                        </button>
                        <?php if ($usuario_rol === 'sst'): ?>
                            <a href="#" onclick="showConfirmModal('Actualizar Acta', 'Al actualizar el acta, se creará una nueva versión en borrador y se requerirán nuevas firmas. ¿Deseas continuar?', 'procesar_estandar1.php?accion=nueva_version', 'warning', 'Sí, actualizar'); return false;" class="btn-versiones" style="text-decoration: none;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Actualizar Acta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="accordion-item" <?php if(isset($_GET['std']) && $_GET['std'] == 2) echo 'id="estandar2_open"'; ?>>
        <button class="accordion-header">
            <div class="header-left">
                <div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg></div>
                <span class="accordion-title">2. Afiliación al Sistema de Seguridad Social</span>
                <span class="badge-std <?php echo $std2_class; ?>"><?php echo $std2_estado; ?></span>
            </div>
            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div class="accordion-content">
            <div class="content-inner" style="background: #f8fafc;">
                <p style="margin-top:0;">Control y carga mensual de la planilla de pago de Seguridad Social (PILA). El vencimiento estándar es el día 10 del mes siguiente.</p>
                
                <div class="year-selector">
                    <a href="dashboard.php?anio_planillas=<?php echo $anio_seleccionado - 1; ?>&std=2" class="year-btn" title="Año Anterior">&laquo;</a>
                    <span>Año <?php echo $anio_seleccionado; ?></span>
                    <a href="dashboard.php?anio_planillas=<?php echo $anio_seleccionado + 1; ?>&std=2" class="year-btn" title="Año Siguiente">&raquo;</a>
                </div>

                <div class="grid-meses">
                    <?php 
                    for ($m = 1; $m <= 12; $m++): 
                        // Lógica de tarjetas
                        $estado_texto = "Pendiente";
                        $clase_estado = "bg-pendiente";
                        $esta_subido = isset($planillas_db[$m]);
                        
                        if ($esta_subido) {
                            $estado_texto = "Subido";
                            $clase_estado = "bg-subido";
                        } else {
                            $fecha_vencimiento = new DateTime("$anio_seleccionado-" . sprintf('%02d', $m) . "-10");
                            $fecha_vencimiento->modify('+1 month'); // Pasa al siguiente mes
                            $fecha_vencimiento->setTime(0, 0, 0);

                            $intervalo = $hoy->diff($fecha_vencimiento);
                            $dias_restantes = (int)$intervalo->format('%R%a');

                            if ($dias_restantes < 0) {
                                $estado_texto = "Vencido";
                                $clase_estado = "bg-vencido";
                            } elseif ($dias_restantes >= 0 && $dias_restantes <= 3) {
                                $estado_texto = "¡Vence en " . ($dias_restantes == 0 ? 'hoy' : "$dias_restantes días!") . "!";
                                $clase_estado = "bg-alerta";
                            }
                        }
                    ?>
                        <div class="mes-card">
                            <h4 class="mes-titulo"><?php echo $meses_nombres[$m]; ?></h4>
                            <span class="badge-estado <?php echo $clase_estado; ?>"><?php echo $estado_texto; ?></span>
                            
                            <div class="acciones-mes">
                                <?php if ($esta_subido): ?>
                                    <a href="<?php echo htmlspecialchars($planillas_db[$m]['archivo_url']); ?>" target="_blank" class="btn-action">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> Ver
                                    </a>
                                    <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                                        <a href="#" onclick="showConfirmModal('Eliminar Planilla', '¿Eliminar la planilla de <?php echo $meses_nombres[$m]; ?>?', 'procesar_estandar2.php?accion=eliminar_planilla&id=<?php echo $planillas_db[$m]['id']; ?>', 'danger', 'Sí, eliminar'); return false;" class="btn-action danger" title="Eliminar archivo">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                                        <form action="procesar_estandar2.php" method="POST" enctype="multipart/form-data" id="formPlanilla_<?php echo $m; ?>" style="width:100%;">
                                            <input type="hidden" name="accion" value="subir_planilla">
                                            <input type="hidden" name="mes" value="<?php echo $m; ?>">
                                            <input type="hidden" name="anio" value="<?php echo $anio_seleccionado; ?>">
                                            <input type="file" name="archivo" id="filePlanilla_<?php echo $m; ?>" style="display:none;" accept=".pdf,.png,.jpg,.jpeg" onchange="mostrarCargandoCorreos('Subiendo planilla de seguridad social...'); this.form.submit();">
                                            <button type="button" class="btn-action primary" onclick="document.getElementById('filePlanilla_<?php echo $m; ?>').click();">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                Cargar Planilla
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: var(--muted);">No disponible</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253" /></svg></div>
    <span class="accordion-title">3. Capacitación en SST</span>
    <span class="badge-std <?php echo $std3_class; ?>"><?php echo $std3_estado; ?></span>
    </div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Elaborar y ejecutar programa de capacitación en promoción y prevención.</p></div></div></div>
    
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg></div>
    <span class="accordion-title">4. Plan Anual de Trabajo</span>
    <span class="badge-std <?php echo $std4_class; ?>"><?php echo $std4_estado; ?></span>
    </div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Elaborar el Plan Anual de Trabajo firmado por el empleador o contratante.</p></div></div></div>
    
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg></div>
    <span class="accordion-title">5. Evaluaciones médicas ocupacionales</span>
    <span class="badge-std <?php echo $std5_class; ?>"><?php echo $std5_estado; ?></span>
    </div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Realizar las evaluaciones médicas ocupacionales (ingreso, periódicas, retiro).</p></div></div></div>
    
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div>
    <span class="accordion-title">6. Identificación de peligros, evaluación de riesgos</span>
    <span class="badge-std <?php echo $std6_class; ?>"><?php echo $std6_estado; ?></span>
    </div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Realizar la identificación de peligros y la evaluación y valoración de los riesgos.</p></div></div></div>
    
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" /></svg></div>
    <span class="accordion-title">7. Medidas de prevención y control</span>
    <span class="badge-std <?php echo $std7_class; ?>"><?php echo $std7_estado; ?></span>
    </div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Ejecutar medidas de prevención y control con base en los resultados de la identificación de peligros.</p></div></div></div>
</div>

<script>
    // PANTALLA DE CARGA GLOBAL (BLOQUEA LA PANTALLA MIENTRAS PROCESA)
    window.mostrarCargandoCorreos = function(mensaje) {
        let overlay = document.createElement('div');
        overlay.id = 'loader-correo-global';
        overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.92); z-index:999999; display:flex; flex-direction:column; align-items:center; justify-content:center; backdrop-filter: blur(5px);';
        overlay.innerHTML = `
            <svg fill="none" stroke="#ff8a1f" viewBox="0 0 24 24" width="60" height="60" style="animation: spin 1s linear infinite;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <h3 style="margin-top:24px; font-family: 'Inter', sans-serif; color: #1f2d3d; font-size: 1.25rem; font-weight: 700;">${mensaje}</h3>
            <p style="color: #64748b; font-family: 'Inter', sans-serif; margin-top: 8px; font-size: 0.9rem;">Por favor, no cierres la ventana.</p>
        `;
        document.body.appendChild(overlay);
    };

    // GENERADOR DE PDF (Con mPDF)
    async function generarYGuardarPDF() {
        const btn = document.getElementById('btnDescargarPDF');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="animation: spin 1s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Procesando y Descargando...';
        btn.disabled = true;
        btn.style.cursor = 'wait';

        try {
            let formData = new FormData();
            formData.append('accion', 'generar_pdf');
            formData.append('doc_id', <?php echo $doc_asignacion['id'] ?? 0; ?>);

            const response = await fetch('procesar_estandar1.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                const link = document.createElement('a');
                link.href = result.pdf;
                link.download = 'Acta_Designacion_SST_v<?php echo $doc_asignacion['id'] ?? 0; ?>.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('Error al generar PDF: ' + result.message);
            }
        } catch (error) {
            alert('Error de conexión al generar el PDF.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.cursor = 'pointer';
        }
    }

    // LÓGICA DE INTERFAZ Y ACORDEONES
    document.addEventListener('DOMContentLoaded', () => {
        const accordions = document.querySelectorAll('.accordion-header');
        accordions.forEach(acc => {
            acc.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                if (content.style.maxHeight) { content.style.maxHeight = null; } 
                else { content.style.maxHeight = content.scrollHeight + "px"; }
            });
        });

        if(document.getElementById('estandar2_open')) {
            const acc = document.getElementById('estandar2_open').querySelector('.accordion-header');
            if(acc) {
                acc.click();
                setTimeout(() => acc.scrollIntoView({ behavior: 'smooth', block: 'center' }), 300);
            }
        }

        // Inicializar Canvases
        function initCanvas(canvasId, inputId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const ctx = canvas.getContext("2d");
            let dibujando = false;

            function redimensionar() {
                const parentWidth = canvas.parentElement.clientWidth;
                canvas.width = parentWidth > 0 ? parentWidth : 300; 
                canvas.height = 120; 
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.lineWidth = 2; 
                ctx.lineCap = "round"; 
                ctx.strokeStyle = "#111";
            }
            
            const btnEst1 = document.getElementById('btnAccordionEst1');
            if(btnEst1) btnEst1.addEventListener('click', () => setTimeout(redimensionar, 300));
            window.addEventListener('resize', () => { if(canvas.offsetParent !== null) redimensionar(); });

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const evt = e.touches ? e.touches[0] : e;
                return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
            }

            function iniciar(e) {
                if (e.cancelable) e.preventDefault(); dibujando = true;
                const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y);
            }

            function trazar(e) {
                if (!dibujando) return;
                if (e.cancelable) e.preventDefault();
                const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke();
            }

            function terminar() { dibujando = false; }

            canvas.addEventListener("mousedown", iniciar); canvas.addEventListener("mousemove", trazar);
            canvas.addEventListener("mouseup", terminar); canvas.addEventListener("mouseleave", terminar);
            canvas.addEventListener("touchstart", iniciar, { passive: false }); canvas.addEventListener("touchmove", trazar, { passive: false }); canvas.addEventListener("touchend", terminar);

            window['limpiar' + canvasId] = function() { 
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height); 
            };
        }

        initCanvas('canvasFirmaSST', 'firmaSSTBase64');
        initCanvas('canvasFirmaRep', 'firmaRepBase64');
    });

    window.confirmarEnvioSST = function(e) {
        e.preventDefault();
        const canvas = document.getElementById("canvasFirmaSST");
        document.getElementById("firmaSSTBase64").value = canvas.toDataURL("image/png");
        showConfirmModal('Firmar y Enviar Documento', '¿Estás seguro de firmar esta acta y enviarla al Representante Legal? Se le notificará por correo.', 'javascript:mostrarCargandoCorreos("Enviando...");document.getElementById("formFirmaSST").submit();', 'warning', 'Sí, firmar y enviar');
    }

    window.confirmarEnvioRep = function(e) {
        e.preventDefault();
        const canvas = document.getElementById("canvasFirmaRep");
        document.getElementById("firmaRepBase64").value = canvas.toDataURL("image/png");
        showConfirmModal('Aprobar Documento', '¿Estás seguro de firmar y legalizar esta acta definitivamente? Se notificará al Responsable.', 'javascript:mostrarCargandoCorreos("Enviando...");document.getElementById("formFirmaRep").submit();', 'warning', 'Sí, firmar documento');
    }
</script>
<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>