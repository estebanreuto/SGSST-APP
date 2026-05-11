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

$doc_empresa = $usuario_info['nombre_empresa'] ?? "La Empresa";  
$doc_rol = "Responsable"; 
$doc_nombre = ""; $doc_cedula = ""; $doc_tipo_lic = ""; $doc_num_lic = ""; $doc_fecha_lic = ""; $doc_ciudad = ""; 
$doc_fecha_firma = "____/____/________"; $doc_firma_sst = ""; $doc_rep_nombre = ""; $doc_rep_cedula = ""; $doc_firma_rep = "";

if ($usuario_rol === 'sst') {
    $doc_nombre = ($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? ''); 
    $doc_cedula = $usuario_info['cedula'] ?? ''; 
    $doc_tipo_lic = $usuario_info['tipo_licencia'] ?? ''; 
    $doc_num_lic = $usuario_info['numero_licencia'] ?? ''; 
    $doc_fecha_lic = $usuario_info['fecha_licencia'] ?? ''; 
    $doc_ciudad = $usuario_info['ciudad'] ?? ''; 
    $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; 
    $doc_rep_nombre = trim(($doc_asignacion['rep_nombre'] ?? '') . ' ' . ($doc_asignacion['rep_apellido'] ?? '')); 
    $doc_rep_cedula = $doc_asignacion['rep_cedula'] ?? ''; 
    $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    
    if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { 
        $doc_fecha_firma = date('d M Y', strtotime($doc_asignacion['fecha_firma'])); 
    }
} elseif ($doc_asignacion) {
    $doc_nombre = ($doc_asignacion['sst_nombre'] ?? '') . ' ' . ($doc_asignacion['sst_apellido'] ?? ''); 
    $doc_cedula = $doc_asignacion['sst_cedula'] ?? ''; 
    $doc_tipo_lic = $doc_asignacion['sst_tipo_licencia'] ?? ''; 
    $doc_num_lic = $doc_asignacion['sst_num_licencia'] ?? ''; 
    $doc_fecha_lic = $doc_asignacion['sst_fecha_licencia'] ?? ''; 
    $doc_ciudad = $doc_asignacion['sst_ciudad'] ?? ''; 
    $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; 
    $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    
    if (!empty($doc_asignacion['rep_nombre'])) { 
        $doc_rep_nombre = trim($doc_asignacion['rep_nombre'] . ' ' . $doc_asignacion['rep_apellido']); 
        $doc_rep_cedula = $doc_asignacion['rep_cedula'];
    } else { 
        $doc_rep_nombre = trim(($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? '')); 
        $doc_rep_cedula = $usuario_info['cedula'] ?? ''; 
    }
    
    if ($doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { 
        $doc_fecha_firma = date('d M Y', strtotime($doc_asignacion['fecha_firma'])); 
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; }

        /* ALERTAS */
        .alert-status { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .alert-status.pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; }
        .alert-status.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-left: 4px solid #22c55e;}

        /* =========================================
           TARJETAS DE INFORMACIÓN (ESTILO DASHBOARD)
           ========================================= */
        .info-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px; }
        
        .info-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; position: relative; overflow: hidden !important; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.3s ease; z-index: 1; display: flex; flex-direction: column; gap: 12px;}
        .info-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        
        .info-bg-icon { position: absolute; right: -15px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 100px; color: var(--primary); opacity: 0.04; transition: all 0.4s ease; pointer-events: none; z-index: 0; }
        .info-card:hover .info-bg-icon { transform: translateY(-50%) rotate(0deg) scale(1.15); opacity: 0.08; }
        
        .info-header { display: flex; align-items: center; gap: 12px; position: relative; z-index: 2;}
        .info-icon-box { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink:0;}
        .info-title { font-size: 1rem; font-weight: 800; color: var(--blue-dark); margin: 0; }
        
        .info-body { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 8px;}
        .info-row { display: flex; flex-direction: column; gap: 2px;}
        .info-label { font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
        .info-value { font-size: 0.9rem; color: #334155; font-weight: 600; margin: 0; word-break: break-word;}

        /* Colores Específicos */
        .card-company .info-icon-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .card-company .info-bg-icon { color: #3b82f6; }
        
        .card-user .info-icon-box { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .card-user .info-bg-icon { color: var(--primary); }
        
        .card-details .info-icon-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .card-details .info-bg-icon { color: #10b981; }


        /* =========================================
           PREVISUALIZACIÓN DE ACTA Y FIRMAS (NUEVO DISEÑO)
           ========================================= */
        .acta-preview-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 40px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        
        .acta-content { font-size: 0.95rem; color: #334155; line-height: 1.8; text-align: justify; margin-bottom: 40px; }
        .acta-content h4 { text-align: center; color: var(--blue-dark); font-size: 1.15rem; font-weight: 800; margin: 0 0 24px 0; text-transform: uppercase; letter-spacing: 0.05em; }
        .acta-content p { margin: 0 0 16px 0; }
        .acta-content strong { color: #1e293b; font-weight: 700; }

        .signatures-title { text-align: center; color: var(--blue-dark); font-size: 1.1rem; font-weight: 800; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.05em; }
        
        /* Contenedor principal de firmas con borde dashed */
        .signatures-wrapper-dashed { border: 2px dashed #cbd5e1; border-radius: 16px; padding: 32px; background: #f8fafc; position: relative; }
        
        .signatures-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; position: relative; }
        
        /* Línea divisoria en medio de las firmas */
        .signatures-grid::after { content: ''; position: absolute; top: 0; bottom: 0; left: 50%; width: 1px; background: #cbd5e1; transform: translateX(-50%); }

        .sig-box { display: flex; flex-direction: column; align-items: center; text-align: center; width: 100%; position: relative; z-index: 2; }
        
        .sig-box .img-placeholder { height: 100px; width: 100%; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .sig-box img { max-width: 100%; height: 100px; object-fit: contain; }
        
        .sig-box .line { width: 80%; height: 1px; background: #94a3b8; margin-bottom: 12px; }
        .sig-box p { margin: 0 0 4px 0 !important; font-size: 0.95rem; font-weight: 800; color: #1e293b; }
        .sig-box span { font-size: 0.85rem; color: #64748b; font-weight: 500; margin-bottom: 2px; }
        
        .firma-canvas-container { position: relative; width: 100%; max-width: 320px; margin: 0 auto 16px auto; }
        .firma-box { border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: border-color 0.3s;}
        .firma-box:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .firma-box canvas { width: 100%; height: 130px; cursor: crosshair; display: block; touch-action: none; background: transparent; }
        
        .btn-limpiar { position: absolute; top: 8px; right: 8px; background: rgba(241, 245, 249, 0.8); color: #475569; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; cursor: pointer; z-index: 10; font-family: inherit; font-weight: 600; transition: all 0.2s; backdrop-filter: blur(4px);}
        .btn-limpiar:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5;}

        .btn-action-firma { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); width: 100%; max-width: 320px; }
        .btn-action-firma:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.3); }

        /* =========================================
           BARRA DE HERRAMIENTAS INFERIOR
           ========================================= */
        .toolbar-acta { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 24px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        
        .toolbar-acta-group { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn-pdf { background: #0f172a; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
        .btn-pdf:hover:not(:disabled) { background: #1e293b; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15);}
        .btn-pdf:disabled { background: #94a3b8; cursor: wait; opacity: 0.8; box-shadow: none; transform: none;}
        
        .btn-versiones { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; text-decoration: none; }
        .btn-versiones:hover { background: #f8fafc; color: var(--blue-dark); border-color: #94a3b8; }
        
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .estandar-header-group { flex-direction: row; align-items: flex-start; width: 100%; }
            
            .info-cards-grid { grid-template-columns: 1fr; }
            .acta-preview-card { padding: 24px 20px; }
            
            .signatures-grid { grid-template-columns: 1fr; gap: 40px; }
            .signatures-grid::after { display: none; } /* Ocultar línea vertical en móvil */
            .signatures-wrapper-dashed { padding: 24px 16px; }
            
            .toolbar-acta { flex-direction: column; align-items: stretch; }
            .toolbar-acta-group { flex-direction: column; }
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
                    <i class="fa-solid fa-check-circle" style="font-size: 1.2rem;"></i>
                    ¡Documento firmado y enviado al Representante Legal exitosamente!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['doc']) && $_GET['doc'] == 'firmado'): ?>
                <div class="alert-status success" id="alertSuccess">
                    <i class="fa-solid fa-check-circle" style="font-size: 1.2rem;"></i>
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
                </div>

            <?php if ($usuario_rol === 'sst'): ?>
                <?php if (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador'): ?>
                    <div class="alert-status pending">
                        <i class="fa-solid fa-file-signature" style="font-size: 1.2rem;"></i>
                        Revisa la información de la carta, dibuja tu firma al final y envía el documento al Representante Legal.
                    </div>
                <?php elseif ($doc_asignacion['estado'] === 'pendiente_firma'): ?>
                    <div class="alert-status pending">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 1.2rem;"></i> 
                        Documento enviado. Esperando la firma electrónica del Representante Legal...
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                <div class="alert-status pending">
                    <i class="fa-solid fa-pen-nib" style="font-size: 1.2rem;"></i>
                    El Responsable SG-SST solicita tu firma. Por favor, revisa el documento y firma al final de la página.
                </div>
            <?php endif; ?>

            <div class="info-cards-grid">
                <div class="info-card card-company">
                    <i class="fa-solid fa-building info-bg-icon"></i>
                    <div class="info-header">
                        <div class="info-icon-box"><i class="fa-solid fa-building"></i></div>
                        <h3 class="info-title">Datos de la Empresa</h3>
                    </div>
                    <div class="info-body">
                        <div class="info-row">
                            <span class="info-label">Razón Social</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc_empresa); ?></span>
                        </div>
                        <div class="info-row" style="flex-direction: row; justify-content: space-between;">
                            <div style="display:flex; flex-direction:column; gap:2px;">
                                <span class="info-label">Sede / Ciudad</span>
                                <span class="info-value"><?php echo htmlspecialchars($doc_ciudad ?: 'Principal'); ?></span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:2px; text-align:right;">
                                <span class="info-label">Fecha</span>
                                <span class="info-value"><?php echo htmlspecialchars($doc_fecha_firma); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card card-user">
                    <i class="fa-solid fa-user-shield info-bg-icon"></i>
                    <div class="info-header">
                        <div class="info-icon-box"><i class="fa-solid fa-user-shield"></i></div>
                        <h3 class="info-title">Responsable Asignado</h3>
                    </div>
                    <div class="info-body">
                        <div class="info-row">
                            <span class="info-label">Nombre Completo</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc_nombre); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Documento de Identidad</span>
                            <span class="info-value">C.C. <?php echo htmlspecialchars($doc_cedula); ?></span>
                        </div>
                    </div>
                </div>

                <div class="info-card card-details">
                    <i class="fa-solid fa-certificate info-bg-icon"></i>
                    <div class="info-header">
                        <div class="info-icon-box"><i class="fa-solid fa-certificate"></i></div>
                        <h3 class="info-title">Licencia SST</h3>
                    </div>
                    <div class="info-body">
                        <div class="info-row">
                            <span class="info-label">Tipo de Licencia</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc_tipo_lic ?: 'No registrada'); ?></span>
                        </div>
                        <div class="info-row" style="flex-direction: row; justify-content: space-between;">
                            <div style="display:flex; flex-direction:column; gap:2px;">
                                <span class="info-label">Número</span>
                                <span class="info-value"><?php echo htmlspecialchars($doc_num_lic ?: 'N/A'); ?></span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:2px; text-align:right;">
                                <span class="info-label">Expedición</span>
                                <span class="info-value"><?php echo htmlspecialchars($doc_fecha_lic ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="acta-preview-card">
                
                <div class="acta-content">
                    <h4>ACTA DE DESIGNACIÓN DEL RESPONSABLE DEL SG-SST</h4>
                    <p>En cumplimiento de lo establecido en la normatividad vigente en Seguridad y Salud en el Trabajo, la empresa <strong><?php echo htmlspecialchars($doc_empresa); ?></strong> designa como <strong><?php echo htmlspecialchars($doc_rol); ?></strong> del Sistema de Gestión de Seguridad y Salud en el Trabajo – SG-SST a:</p>
                    <p><strong><?php echo htmlspecialchars($doc_nombre); ?></strong>, identificado(a) con cédula No. <strong><?php echo htmlspecialchars($doc_cedula); ?></strong>, con licencia en Seguridad y Salud en el Trabajo de tipo <strong><?php echo htmlspecialchars($doc_tipo_lic); ?></strong>, No. <strong><?php echo htmlspecialchars($doc_num_lic); ?></strong>, expedida el <strong><?php echo htmlspecialchars($doc_fecha_lic); ?></strong>.</p>
                    <p>El responsable del SG-SST se compromete a liderar, coordinar y hacer seguimiento a las actividades del Sistema de Gestión de acuerdo con el Decreto 1072 de 2015, la Resolución 0312 de 2019 y demás normas aplicables, garantizando la mejora continua y la protección de la seguridad y salud de todos los trabajadores.</p>
                    <p>Para constancia se firma la presente en la ciudad de <strong><?php echo htmlspecialchars($doc_ciudad); ?></strong>, a los <strong><?php echo htmlspecialchars($doc_fecha_firma); ?></strong>.</p>
                </div>

                <h3 class="signatures-title">Firmas de Aceptación y Compromiso</h3>
                
                <div class="signatures-wrapper-dashed">
                    <div class="signatures-grid">
                        
                        <div class="sig-box">
                            <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_firma_rep)): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_firma_rep; ?>"></div>
                                <div class="line"></div>
                                <p>Representante Legal</p>
                                <span><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            
                            <?php elseif ($usuario_rol === 'representante' && $doc_asignacion && $doc_asignacion['estado'] === 'pendiente_firma'): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaRep" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                                    <input type="hidden" name="accion" value="firmar_doc">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc_asignacion['id']; ?>">
                                    <input type="hidden" name="firma_rep" id="firmaRepBase64">
                                    
                                    <div class="firma-canvas-container">
                                        <div class="firma-box">
                                            <canvas id="canvasFirmaRep"></canvas>
                                        </div>
                                        <button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaRep()"><i class="fa-solid fa-eraser"></i> Limpiar</button>
                                    </div>
                                    
                                    <button type="button" onclick="confirmarEnvioRep(event)" class="btn-action-firma" style="background: linear-gradient(135deg, #16a34a, #15803d); margin-bottom: 16px;">
                                        <i class="fa-solid fa-signature"></i> Aprobar y Firmar
                                    </button>
                                </form>
                                <div class="line"></div>
                                <p>Representante Legal</p>
                                <span><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            
                            <?php else: ?>
                                <div class="img-placeholder" style="color: #cbd5e1; font-style: italic; font-size: 0.8rem;">
                                    <i class="fa-solid fa-clock" style="margin-right: 6px; font-size: 1.2rem; display:block; margin-bottom:6px;"></i> Pendiente de firma
                                </div>
                                <div class="line"></div>
                                <p>Representante Legal</p>
                                <span><?php echo htmlspecialchars($doc_rep_nombre); ?></span>
                                <span>C.C. <?php echo htmlspecialchars($doc_rep_cedula); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="sig-box">
                            <?php if (!empty($doc_firma_sst)): ?>
                                <div class="img-placeholder"><img src="<?php echo $doc_firma_sst; ?>"></div>
                                <div class="line"></div>
                            
                            <?php elseif ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                <form action="procesar_estandar1.php" method="POST" id="formFirmaSST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                                    <input type="hidden" name="accion" value="enviar_firma">
                                    <input type="hidden" name="firma_sst" id="firmaSSTBase64">
                                    
                                    <div class="firma-canvas-container">
                                        <div class="firma-box">
                                            <canvas id="canvasFirmaSST"></canvas>
                                        </div>
                                        <button type="button" class="btn-limpiar" onclick="limpiarcanvasFirmaSST()"><i class="fa-solid fa-eraser"></i> Limpiar</button>
                                    </div>
                            <?php else: ?>
                                <div class="img-placeholder" style="color: #cbd5e1; font-style: italic; font-size: 0.8rem;">
                                    <i class="fa-solid fa-clock" style="margin-right: 6px; font-size: 1.2rem; display:block; margin-bottom:6px;"></i> Pendiente de firma
                                </div>
                                <div class="line"></div>
                            <?php endif; ?>
                            
                            <p <?php if($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')) echo 'style="margin-top:0 !important;"'; ?>>Responsable SG-SST</p>
                            <span><?php echo htmlspecialchars($doc_nombre); ?></span>
                            <span>C.C. <?php echo htmlspecialchars($doc_cedula); ?></span>

                            <?php if ($usuario_rol === 'sst' && (!$doc_asignacion || $doc_asignacion['estado'] === 'borrador')): ?>
                                    <button type="button" onclick="confirmarEnvioSST(event)" class="btn-action-firma" style="margin-top: 16px;">
                                        <i class="fa-solid fa-paper-plane"></i> Firmar y Enviar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado'): ?>
                <div class="toolbar-acta">
                    <button id="btnDescargarPDF" onclick="generarYGuardarPDF()" class="btn-pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        Descargar PDF Legalizado
                    </button>
                    
                    <div class="toolbar-acta-group">
                        <button onclick="openVersionesModal()" class="btn-versiones">
                            <i class="fa-solid fa-clock-rotate-left"></i> Historial
                        </button>
                        
                        <?php if ($usuario_rol === 'sst'): ?>
                            <a href="#" onclick="showConfirmModal('Actualizar Acta', 'Al actualizar el acta, se creará una nueva versión en borrador y se requerirán nuevas firmas. ¿Deseas continuar?', 'procesar_estandar1.php?accion=nueva_version', 'warning', 'Sí, actualizar'); return false;" class="btn-versiones" style="color: var(--primary2);">
                                <i class="fa-solid fa-arrows-rotate"></i> Actualizar Acta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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

        // GENERADOR DE PDF (Conectado al Backend mPDF)
        async function generarYGuardarPDF() {
            const btn = document.getElementById('btnDescargarPDF');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando y Guardando...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('accion', 'generar_pdf');
                formData.append('doc_id', '<?php echo $doc_asignacion["id"] ?? 0; ?>');

                const response = await fetch('procesar_estandar1.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    const link = document.createElement('a');
                    link.href = data.pdf;
                    link.download = 'Acta_Designacion_SST_v<?php echo $doc_asignacion["id"] ?? 0; ?>.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    window.location.reload();
                } else {
                    alert("Error del servidor: " + data.message);
                }
            } catch (error) {
                console.error("Error al conectar con el servidor:", error);
                alert("Hubo un error de conexión al intentar generar el documento.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
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

            // Inicializar Canvases de Firma
            function initCanvas(canvasId, inputId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                const ctx = canvas.getContext("2d");
                let dibujando = false;

                function redimensionar() {
                    const parentWidth = canvas.parentElement.clientWidth;
                    canvas.width = parentWidth > 0 ? parentWidth : 300; 
                    canvas.height = 130; 
                    ctx.fillStyle = "transparent"; // Fondo transparente para combinar con el diseño
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.lineWidth = 2; 
                    ctx.lineCap = "round"; 
                    ctx.strokeStyle = "#0f172a"; // Tinta oscura azul marino
                }
                
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
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                };
            }

            initCanvas('canvasFirmaSST', 'firmaSSTBase64');
            initCanvas('canvasFirmaRep', 'firmaRepBase64');
        });

        window.confirmarEnvioSST = function(e) {
            e.preventDefault();
            const canvas = document.getElementById("canvasFirmaSST");
            document.getElementById("firmaSSTBase64").value = canvas.toDataURL("image/png");
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal(
                    'Firmar y Enviar Documento', 
                    '¿Estás seguro de firmar esta acta y enviarla al Representante Legal? Se le notificará por correo.', 
                    'javascript:mostrarCargandoCorreos("Enviando...");document.getElementById("formFirmaSST").submit();', 
                    'warning', 
                    'Sí, firmar y enviar'
                );
            } else {
                if(confirm('¿Estás seguro de firmar esta acta y enviarla al Representante Legal? Se le notificará por correo.')) {
                    mostrarCargandoCorreos("Enviando...");
                    document.getElementById("formFirmaSST").submit();
                }
            }
        }

        window.confirmarEnvioRep = function(e) {
            e.preventDefault();
            const canvas = document.getElementById("canvasFirmaRep");
            document.getElementById("firmaRepBase64").value = canvas.toDataURL("image/png");
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal(
                    'Aprobar Documento', 
                    '¿Estás seguro de firmar y legalizar esta acta definitivamente? Se notificará al Responsable.', 
                    'javascript:mostrarCargandoCorreos("Enviando...");document.getElementById("formFirmaRep").submit();', 
                    'warning', 
                    'Sí, firmar documento'
                );
            } else {
                if(confirm('¿Estás seguro de firmar y legalizar esta acta definitivamente? Se notificará al Responsable.')) {
                    mostrarCargandoCorreos("Enviando...");
                    document.getElementById("formFirmaRep").submit();
                }
            }
        }
    </script>
</body>
</html>