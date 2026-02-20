<?php
// --- LÓGICA DEL ESTÁNDAR 1 (DOCUMENTO DE ASIGNACIÓN) ---
$doc_asignacion = null;
try {
    if ($usuario_rol === 'sst') {
        $stmt_doc = $conn->prepare("SELECT d.*, 
                                           u_rep.nombre as rep_nombre, 
                                           u_rep.apellido as rep_apellido, 
                                           u_rep.cedula as rep_cedula 
                                    FROM doc_asignacion_sst d 
                                    LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id 
                                    WHERE d.sst_id = ? ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute([$_SESSION['usuario_id']]);
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    } elseif ($usuario_rol === 'representante') {
        $stmt_doc = $conn->prepare("SELECT d.*, 
                                           u_sst.nombre as sst_nombre, 
                                           u_sst.apellido as sst_apellido, 
                                           u_sst.cedula as sst_cedula, 
                                           u_sst.licencia_sst, 
                                           u_sst.tipo_licencia as sst_tipo_licencia,
                                           u_sst.numero_licencia as sst_num_licencia,
                                           DATE_FORMAT(u_sst.fecha_licencia, '%d/%m/%Y') as sst_fecha_licencia,
                                           u_sst.ciudad as sst_ciudad,
                                           u_rep.nombre as rep_nombre, 
                                           u_rep.apellido as rep_apellido, 
                                           u_rep.cedula as rep_cedula
                                    FROM doc_asignacion_sst d 
                                    JOIN usuarios u_sst ON d.sst_id = u_sst.id 
                                    LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id
                                    WHERE d.estado IN ('pendiente_firma', 'firmado') 
                                    ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute();
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $doc_asignacion = null; }

// Variables dinámicas para el Documento
$doc_empresa = "Sistemas P"; 
$doc_rol = "Responsable";
$doc_nombre = ""; $doc_cedula = ""; $doc_tipo_lic = ""; $doc_num_lic = ""; $doc_fecha_lic = ""; $doc_ciudad = ""; $doc_fecha_firma = "____/____/________"; $doc_firma_sst = "";
$doc_rep_nombre = ""; $doc_rep_cedula = "";

if ($usuario_rol === 'sst') {
    $doc_nombre = ($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? '');
    $doc_cedula = $usuario_info['cedula'] ?? ''; $doc_tipo_lic = $usuario_info['tipo_licencia'] ?? ''; $doc_num_lic = $usuario_info['numero_licencia'] ?? ''; $doc_fecha_lic = $usuario_info['fecha_licencia'] ?? ''; $doc_ciudad = $usuario_info['ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; 
    $doc_rep_nombre = trim(($doc_asignacion['rep_nombre'] ?? '') . ' ' . ($doc_asignacion['rep_apellido'] ?? ''));
    $doc_rep_cedula = $doc_asignacion['rep_cedula'] ?? '';
    if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
} elseif ($doc_asignacion) {
    $doc_nombre = ($doc_asignacion['sst_nombre'] ?? '') . ' ' . ($doc_asignacion['sst_apellido'] ?? '');
    $doc_cedula = $doc_asignacion['sst_cedula'] ?? ''; $doc_tipo_lic = $doc_asignacion['sst_tipo_licencia'] ?? ''; $doc_num_lic = $doc_asignacion['sst_num_licencia'] ?? ''; $doc_fecha_lic = $doc_asignacion['sst_fecha_licencia'] ?? ''; $doc_ciudad = $doc_asignacion['sst_ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? '';
    if (!empty($doc_asignacion['rep_nombre'])) {
        $doc_rep_nombre = trim($doc_asignacion['rep_nombre'] . ' ' . $doc_asignacion['rep_apellido']);
        $doc_rep_cedula = $doc_asignacion['rep_cedula'];
    } else {
        $doc_rep_nombre = trim(($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? ''));
        $doc_rep_cedula = $usuario_info['cedula'] ?? '';
    }
    if ($doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<style>
    /* --- ESTILOS DEL ACORDEÓN --- */
    .accordion-container { display: flex; flex-direction: column; gap: 12px; margin-top: 16px; font-family: 'Inter', sans-serif; }
    .accordion-item { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; transition: all 0.3s ease; }
    .accordion-header { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: none; border: none; cursor: pointer; text-align: left; transition: background 0.3s ease; font-family: inherit; }
    .accordion-header.active { background: #fff8f3; border-bottom: 1px solid var(--border); }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .accordion-header.active .icon-box { background: var(--primary); color: #fff; }
    .accordion-title { font-size: 0.9rem; font-weight: 600; color: var(--text); margin: 0; }
    .accordion-header.active .accordion-title { color: var(--primary2); }
    .chevron-icon { color: var(--muted); transition: transform 0.3s ease; }
    .accordion-header.active .chevron-icon { transform: rotate(180deg); color: var(--primary); }
    .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background: #fafbfc; }
    .content-inner { padding: 20px; color: var(--muted); font-size: 0.85rem; line-height: 1.6; }
    .content-inner h4 { margin: 0 0 8px 0; color: var(--text); font-size: 0.85rem; }
    .content-inner ul { margin: 0; padding-left: 20px; }

    /* --- FORMATO DEL DOCUMENTO (VISTA PANTALLA) --- */
    .document-format { background: #fff; border: 1px solid #e2e8f0; padding: 40px 50px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-top: 16px; color: #1f2d3d; font-size: 0.9rem; line-height: 1.6; text-align: justify; font-family: 'Inter', Arial, sans-serif; }
    .document-format p { margin-bottom: 16px; }
    
    .signatures-grid { display: flex; justify-content: space-between; gap: 40px; margin-top: 60px; }
    .sig-box { display: flex; flex-direction: column; align-items: center; text-align: center; width: 45%; }
    .sig-box .img-placeholder { height: 80px; width: 100%; display: flex; align-items: center; justify-content: center; }
    .sig-box img { max-width: 100%; height: 80px; object-fit: contain; }
    .sig-box .line { width: 100%; height: 1px; background: #94a3b8; margin-bottom: 10px; margin-top: 8px; }
    .sig-box p { margin: 0 0 4px 0 !important; font-size: 0.85rem; font-weight: 700; color: #1f2d3d; }
    .sig-box span { font-size: 0.8rem; color: #475569; font-weight: 500; margin-bottom: 2px; }
    
    /* Panel de firma (Canvas) */
    .firma-box { border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; position: relative; margin: 0 auto 12px auto; width: 100%; max-width: 300px; overflow: hidden; }
    .firma-box canvas { width: 100%; height: 120px; cursor: crosshair; display: block; touch-action: none; }
    .btn-limpiar { position: absolute; top: 8px; right: 8px; background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); padding: 4px 8px; border-radius: 4px; font-size: .7rem; cursor: pointer; z-index: 10; font-family: inherit; }

    /* Alertas */
    .alert-status { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
    .alert-status.pending { background: #fef08a; color: #854d0e; border: 1px solid #fde047; }
    .alert-status.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

    /* Botones de acción del PDF */
    .toolbar-acta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; border-top: 1px dashed var(--border); padding-top: 20px; }
    .btn-pdf { background: #0f172a; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; font-family: inherit; }
    .btn-pdf:hover { background: #334155; }
    .btn-pdf:disabled { background: #94a3b8; cursor: not-allowed; }
    .btn-versiones { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; }
    .btn-versiones:hover { background: #e2e8f0; }

    /* ==================================================================== */
    /* CLASE MÁGICA: GARANTIZA QUE EL PDF NO SE CORTE EN MODO HORIZONTAL    */
    /* ==================================================================== */
    .pdf-mode {
        position: fixed !important;    /* Lo fija en la esquina 0,0 para que html2canvas no se confunda con los márgenes */
        top: 0 !important;
        left: 0 !important;
        width: 1000px !important;      /* Ancho perfecto para formato Carta Horizontal */
        max-width: none !important;
        padding: 50px !important;      /* Espacio interno para que respire */
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: #ffffff !important;
        z-index: 9999 !important;      /* Lo pone encima de todo por un segundo */
    }
    .pdf-mode p { font-size: 14px !important; color: #000 !important; text-align: justify !important; line-height: 1.6 !important; }
    .pdf-mode h4 { font-size: 18px !important; color: #000 !important; margin-bottom: 30px !important; }
    .pdf-mode .signatures-grid { 
        display: flex !important; 
        flex-direction: row !important; /* Mantiene las firmas una al lado de la otra */
        justify-content: space-around !important; /* Espaciado simétrico para horizontal */
        gap: 50px !important; 
        margin-top: 60px !important; 
    }
    .pdf-mode .sig-box { width: 40% !important; display: flex !important; flex-direction: column !important; align-items: center !important; }
    .pdf-mode .sig-box p { font-size: 14px !important; margin: 0 0 4px 0 !important; }
    .pdf-mode .sig-box span { font-size: 12px !important; margin: 0 !important; color: #333 !important; }
    /* ==================================================================== */

    @media (max-width: 768px) {
        .document-format { padding: 20px; }
        .signatures-grid { flex-direction: column; gap: 40px; }
        .sig-box { width: 100%; }
        .toolbar-acta { flex-direction: column; }
        .toolbar-acta button, .toolbar-acta a { width: 100%; justify-content: center; }
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
                <div class="icon-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span class="accordion-title">1. Asignación de persona que diseña el SG-SST</span>
            </div>
            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div class="accordion-content">
            <div class="content-inner" style="background: #f1f5f9;">

                <?php if ($usuario_rol === 'sst'): ?>
                    <?php if (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador'): ?>
                        <div class="alert-status pending">Genera el documento, dibuja tu firma al final de la hoja y envíalo al Representante Legal.</div>
                    <?php elseif ($doc_asignacion['estado'] === 'pendiente_firma'): ?>
                        <div class="alert-status pending">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Documento firmado por ti y enviado. Esperando la firma del Representante Legal...
                        </div>
                    <?php elseif ($doc_asignacion['estado'] === 'firmado'): ?>
                        <div class="alert-status success">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Documento firmado por ambas partes y legalizado exitosamente.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                    <div class="alert-status pending" style="border-left: 4px solid #ca8a04;">
                        El Responsable SG-SST solicita tu firma para legalizar su asignación. Por favor, revisa el documento y firma al final.
                    </div>
                <?php endif; ?>

                <div class="document-format" id="acta-imprimir">
                    <h4 style="text-align: center; font-weight: bold; margin-bottom: 24px; color: #111; font-size: 1.15rem;">ACTA DE DESIGNACIÓN DEL RESPONSABLE DEL SG-SST</h4>
                    
                    <p>En cumplimiento de lo establecido en la normatividad vigente en Seguridad y Salud en el Trabajo, la empresa <strong><?php echo htmlspecialchars($doc_empresa); ?></strong> designa como <strong><?php echo htmlspecialchars($doc_rol); ?></strong> del Sistema de Gestión de Seguridad y Salud en el Trabajo – SG-SST a:</p>
                    
                    <p><strong><?php echo htmlspecialchars($doc_nombre); ?></strong>, identificado(a) con cédula No. <strong><?php echo htmlspecialchars($doc_cedula); ?></strong>, con licencia en Seguridad y Salud en el Trabajo de tipo <strong><?php echo htmlspecialchars($doc_tipo_lic); ?></strong>, No. <strong><?php echo htmlspecialchars($doc_num_lic); ?></strong>, expedida el <strong><?php echo htmlspecialchars($doc_fecha_lic); ?></strong>.</p>
                    
                    <p>El responsable del SG-SST se compromete a liderar, coordinar y hacer seguimiento a las actividades del Sistema de Gestión de acuerdo con el Decreto 1072 de 2015, la Resolución 0312 de 2019 y demás normas aplicables, garantizando la mejora continua y la protección de la seguridad y salud de todos los trabajadores.</p>
                    
                    <p>Para constancia se firma la presente en la ciudad de <strong><?php echo htmlspecialchars($doc_ciudad); ?></strong>, a los <strong><?php echo htmlspecialchars($doc_fecha_firma); ?></strong>.</p>

                    <div class="signatures-grid">
                        
                        <div class="sig-box">
                            <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['firma_representante'])): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_asignacion['firma_representante']; ?>" alt="Firma Representante"></div>
                                <div class="line"></div>
                                <p>Representante Legal</p>
                                <span><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <?php if (!empty($doc_rep_cedula)): ?>
                                    <span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                                <?php endif; ?>

                            <?php elseif ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaRep" style="width: 100%; display: flex; flex-direction: column; align-items: center;" data-html2canvas-ignore="true">
                                    <input type="hidden" name="accion" value="firmar_doc">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc_asignacion['id']; ?>">
                                    <input type="hidden" name="firma_rep" id="firmaRepBase64">
                                    <div class="firma-box">
                                        <canvas id="canvasFirmaRep"></canvas>
                                        <button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaRep()">Limpiar</button>
                                    </div>
                                    <button type="button" onclick="confirmarEnvioRep(event)" class="btn-edit" style="width: 100%; max-width: 300px; justify-content: center; background: #16a34a; margin-top: 4px; margin-bottom: 16px;">Firmar Documento</button>
                                </form>
                                <div class="line" data-html2canvas-ignore="true"></div>
                                <p data-html2canvas-ignore="true">Representante Legal</p>
                                <span data-html2canvas-ignore="true"><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <?php if (!empty($doc_rep_cedula)): ?>
                                    <span data-html2canvas-ignore="true">C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="img-placeholder"></div>
                                <div class="line"></div>
                                <p>Representante Legal</p>
                                <span><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <?php if (!empty($doc_rep_cedula)): ?>
                                    <span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="sig-box">
                            <?php if (!empty($doc_firma_sst)): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_firma_sst; ?>" alt="Firma SST"></div>
                                <div class="line"></div>
                            <?php elseif ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaSST" style="width: 100%; display: flex; flex-direction: column; align-items: center;" data-html2canvas-ignore="true">
                                    <input type="hidden" name="accion" value="enviar_firma">
                                    <input type="hidden" name="firma_sst" id="firmaSSTBase64">
                                    <div class="firma-box">
                                        <canvas id="canvasFirmaSST"></canvas>
                                        <button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaSST()">Limpiar</button>
                                    </div>
                            <?php else: ?>
                                <div class="img-placeholder"></div>
                                <div class="line"></div>
                            <?php endif; ?>
                            
                            <p <?php if($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')) echo 'data-html2canvas-ignore="true" style="margin-top:12px !important;"'; ?>>Responsable SG-SST</p>
                            <span <?php if($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')) echo 'data-html2canvas-ignore="true"'; ?>><?php echo htmlspecialchars($doc_nombre); ?></span>
                            <span <?php if($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')) echo 'data-html2canvas-ignore="true"'; ?>>C.C. <?php echo htmlspecialchars($doc_cedula); ?></span>

                            <?php if ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                    <button type="button" onclick="confirmarEnvioSST(event)" class="btn-edit" style="width: 100%; max-width: 300px; justify-content: center; background: var(--primary); margin-top: 16px;">Firmar y Enviar</button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado'): ?>
                    <div class="toolbar-acta">
                        <button id="btnDescargarPDF" onclick="generarYGuardarPDF(<?php echo $doc_asignacion['id']; ?>)" class="btn-pdf">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Descargar y Guardar PDF
                        </button>
                        
                        <button onclick="openVersionesModal()" class="btn-versiones">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Ver Versiones
                        </button>

                        <?php if ($usuario_rol === 'sst'): ?>
                            <a href="#" onclick="showConfirmModal('Actualizar Acta', 'Al actualizar el acta, se creará una nueva versión en borrador y se requerirán nuevas firmas. La versión actual pasará al historial. ¿Deseas continuar?', 'procesar_estandar1.php?accion=nueva_version', 'warning', 'Sí, actualizar'); return false;" class="btn-versiones" style="text-decoration: none;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Actualizar Acta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg></div><span class="accordion-title">2. Afiliación al Sistema de Seguridad Social</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Garantizar que todos los trabajadores estén afiliados al Sistema General de Seguridad Social.</p></div></div></div>
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253" /></svg></div><span class="accordion-title">3. Capacitación en SST</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Elaborar y ejecutar programa de capacitación en promoción y prevención.</p></div></div></div>
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg></div><span class="accordion-title">4. Plan Anual de Trabajo</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Elaborar el Plan Anual de Trabajo firmado por el empleador o contratante.</p></div></div></div>
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg></div><span class="accordion-title">5. Evaluaciones médicas ocupacionales</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Realizar las evaluaciones médicas ocupacionales (ingreso, periódicas, retiro).</p></div></div></div>
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div><span class="accordion-title">6. Identificación de peligros, evaluación de riesgos</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Realizar la identificación de peligros y la evaluación y valoración de los riesgos.</p></div></div></div>
    <div class="accordion-item"><button class="accordion-header"><div class="header-left"><div class="icon-box"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" /></svg></div><span class="accordion-title">7. Medidas de prevención y control</span></div><svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button><div class="accordion-content"><div class="content-inner"><h4>Modo de Verificación:</h4><p>Ejecutar medidas de prevención y control con base en los resultados de la identificación de peligros.</p></div></div></div>
</div>

<script>
    // ==========================================
    // 1. GENERADOR DE PDF PERFECTO (HORIZONTAL)
    // ==========================================
    async function generarYGuardarPDF(docId) {
        const element = document.getElementById('acta-imprimir');
        const btn = document.getElementById('btnDescargarPDF');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = 'Generando... <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="animation: spin 1s linear infinite;"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
        btn.disabled = true;

        // Inyecta la clase mágica para que NO se recorte
        element.classList.add('pdf-mode');

        var opt = {
          margin:       [15, 15, 15, 15], 
          filename:     'Acta_Designacion_SST_v' + docId + '.pdf',
          image:        { type: 'jpeg', quality: 1 },
          html2canvas:  { 
              scale: 2, 
              useCORS: true, 
              scrollX: 0, // Fija el inicio exactamente al borde izquierdo
              scrollY: 0
          }, 
          jsPDF:        { unit: 'mm', format: 'letter', orientation: 'landscape' } // Formato Horizontal (Landscape)
        };

        try {
            const pdfBase64 = await html2pdf().set(opt).from(element).outputPdf('datauristring');
            
            let formData = new FormData();
            formData.append('accion', 'guardar_pdf');
            formData.append('doc_id', docId);
            formData.append('pdf_base64', pdfBase64);

            await fetch('procesar_estandar1.php', { method: 'POST', body: formData });

            await html2pdf().set(opt).from(element).save();
        } catch (error) {
            alert('Error al generar el PDF.');
        } finally {
            // Remueve la clase mágica
            element.classList.remove('pdf-mode');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // ==========================================
    // 2. LÓGICA DE INTERFAZ Y CANVAS
    // ==========================================
    document.addEventListener('DOMContentLoaded', () => {
        // Acordeón
        const accordions = document.querySelectorAll('.accordion-header');
        accordions.forEach(acc => {
            acc.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                if (content.style.maxHeight) { content.style.maxHeight = null; } 
                else { content.style.maxHeight = content.scrollHeight + "px"; }
            });
        });

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
                ctx.lineWidth = 2; ctx.lineCap = "round"; ctx.strokeStyle = "#111";
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

            window['limpiar' + canvasId] = function() { ctx.clearRect(0, 0, canvas.width, canvas.height); };
        }

        initCanvas('canvasFirmaSST', 'firmaSSTBase64');
        initCanvas('canvasFirmaRep', 'firmaRepBase64');
    });

    window.confirmarEnvioSST = function(e) {
        e.preventDefault();
        const canvas = document.getElementById("canvasFirmaSST");
        const blank = document.createElement('canvas'); blank.width = canvas.width; blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) { alert("Debe dibujar su firma."); return; }
        document.getElementById("firmaSSTBase64").value = canvas.toDataURL("image/png");
        showConfirmModal('Firmar y Enviar Documento', '¿Estás seguro de firmar esta acta y enviarla al Representante Legal?', 'javascript:document.getElementById("formFirmaSST").submit();', 'warning', 'Sí, firmar y enviar');
    }

    window.confirmarEnvioRep = function(e) {
        e.preventDefault();
        const canvas = document.getElementById("canvasFirmaRep");
        const blank = document.createElement('canvas'); blank.width = canvas.width; blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) { alert("Debe dibujar su firma."); return; }
        document.getElementById("firmaRepBase64").value = canvas.toDataURL("image/png");
        showConfirmModal('Aprobar Documento', '¿Estás seguro de firmar y legalizar esta acta definitivamente?', 'javascript:document.getElementById("formFirmaRep").submit();', 'warning', 'Sí, firmar documento');
    }
</script>
<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>