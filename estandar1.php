<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Obtener info del usuario actual (necesario para Estándar 1)
try {
    $sql = "SELECT nombre, apellido, cedula, email, telefono, ciudad, rol, 
                   licencia_sst, tipo_licencia, numero_licencia, firma,
                   fecha_licencia as fecha_licencia_raw,
                   DATE_FORMAT(fecha_licencia, '%d/%m/%Y') as fecha_licencia,
                   logo_empresa, nombre_empresa, tipo_persona, regimen_tributario, tipo_doc_empresa, num_doc_empresa
            FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_info) {
        $usuario_info = [];
    }
} catch (PDOException $e) {
    $usuario_info = [];
}

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

// 2. OBTENER EL HISTORIAL DE ACTAS (Para el Modal de Versiones)
$historial_actas = [];
if ($usuario_rol === 'sst' || $usuario_rol === 'representante') {
    try {
        $sst_target_id = 0;
        if ($usuario_rol === 'sst') {
            $sst_target_id = $_SESSION['usuario_id'];
        } elseif ($usuario_rol === 'representante') {
            $stmt_doc_t = $conn->prepare("SELECT sst_id FROM doc_asignacion_sst WHERE estado IN ('pendiente_firma', 'firmado') ORDER BY id DESC LIMIT 1");
            $stmt_doc_t->execute();
            $doc_temp = $stmt_doc_t->fetch(PDO::FETCH_ASSOC);
            if ($doc_temp) {
                $sst_target_id = $doc_temp['sst_id'];
            }
        }
        if ($sst_target_id > 0) {
            $stmt_hist = $conn->prepare("SELECT d.*, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_sst.nombre as sst_nombre, u_sst.apellido as sst_apellido FROM doc_asignacion_sst d LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id LEFT JOIN usuarios u_sst ON d.sst_id = u_sst.id WHERE d.sst_id = ? AND d.estado = 'firmado' ORDER BY d.id DESC");
            $stmt_hist->execute([$sst_target_id]);
            $historial_actas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { }
}

$current_page = 'estandar1.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 1 | SG-SST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }
        
        .card-box { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); margin-top: 20px; }
        
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
        .btn-versiones { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; text-decoration: none; }
        .btn-versiones:hover { background: #e2e8f0; }
        .btn-edit { background-color: var(--primary); color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background-color 0.2s, transform 0.1s; text-decoration: none; }
        .btn-edit:hover { background-color: var(--primary2); transform: translateY(-1px); }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .btn-back { order: -1; width: max-content; align-self: flex-start; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; margin: 0 0 4px 0; }
            .estandar-header-group { display: flex; flex-direction: row; align-items: flex-start; text-align: left; gap: 12px; width: 100%; }
            .icon-box-std { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; margin-top: 2px; }
            .estandar-title { font-size: 1.05rem; line-height: 1.3; margin: 0; display: block; }
            .estandar-subtitle { font-size: 0.75rem; line-height: 1.4; margin-top: 4px; }
            .document-format { padding: 20px 16px; }
            .signatures-grid { flex-direction: column; gap: 32px; margin-top: 40px; }
            .sig-box { width: 100%; }
            .toolbar-acta { flex-direction: column; gap: 12px; }
            .toolbar-acta button, .toolbar-acta a { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['doc']) && $_GET['doc'] == 'enviado'): ?>
                <div class="alert-status success" id="alertSuccess">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ¡Documento firmado y enviado al Representante Legal exitosamente!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['doc']) && $_GET['doc'] == 'firmado'): ?>
                <div class="alert-status success" id="alertSuccess">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ¡Acta firmada y legalizada exitosamente!
                </div>
            <?php endif; ?>

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title"><span class="std-num-marker">1.</span> Asignación de persona que diseña el SG-SST</h1>
                        <p class="estandar-subtitle">Generación, firma y legalización del acta de designación.</p>
                    </div>
                </div>
                <a href="dashboard.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>

            <div class="card-box">
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
    </main>

    <?php include 'components/modal_versiones_acta.php'; ?>
    <?php include 'components/modal_confirmacion.php'; ?>

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

        // GENERADOR DE PDF (Con html2pdf)
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

        // LÓGICA DE INTERFAZ Y CANVASES
        document.addEventListener('DOMContentLoaded', () => {
            // Eliminar alerta después de 4s
            const alertSuccess = document.getElementById('alertSuccess');
            if (alertSuccess) {
                setTimeout(() => {
                    alertSuccess.style.transition = 'opacity 0.5s ease';
                    alertSuccess.style.opacity = '0';
                    setTimeout(() => {
                        alertSuccess.remove();
                        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({path:newUrl}, '', newUrl);
                    }, 500);
                }, 4000); 
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
                
                // Redimensionar inmediatamente y al hacer resize
                redimensionar();
                setTimeout(redimensionar, 300);
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
</body>
</html>