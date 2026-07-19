<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/document_control_schema.php';

// Exige sesión válida
$u = require_auth($conn);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$empresa_id = storage_user_company_id($conn, $usuario_id);
ensure_document_control_schema($conn);
document_control_backfill_legalized_pdfs($conn, $empresa_id, $usuario_id);
if (empty($_SESSION['estandar1_csrf'])) {
    $_SESSION['estandar1_csrf'] = bin2hex(random_bytes(24));
}

// Obtener info del usuario actual (necesario para Estándar 1)
try {
    $sql = "SELECT nombre, apellido, cedula, email, telefono, ciudad, rol, 
                   licencia_sst, tipo_licencia, numero_licencia, firma,
                   fecha_licencia as fecha_licencia_raw,
                   DATE_FORMAT(fecha_licencia, '%d/%m/%Y') as fecha_licencia,
                   logo_empresa, nombre_empresa, tipo_persona, regimen_tributario, tipo_doc_empresa, num_doc_empresa
            FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
        $stmt->execute([$usuario_id]);
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
        $stmt_doc->execute([$usuario_id]);
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    } elseif ($usuario_rol === 'representante') {
        $stmt_doc = $conn->prepare("SELECT d.*, u_sst.nombre as sst_nombre, u_sst.apellido as sst_apellido, u_sst.cedula as sst_cedula, u_sst.licencia_sst, u_sst.tipo_licencia as sst_tipo_licencia, u_sst.numero_licencia as sst_num_licencia, DATE_FORMAT(u_sst.fecha_licencia, '%d/%m/%Y') as sst_fecha_licencia, u_sst.ciudad as sst_ciudad, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula FROM doc_asignacion_sst d JOIN usuarios u_sst ON d.sst_id = u_sst.id LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id WHERE u_sst.empresa_id=? AND d.estado IN ('pendiente_firma', 'firmado') ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute([$empresa_id]);
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
$document_records = [];
$document_config = document_control_config($conn, $empresa_id, 1);
$document_code_example = document_control_code_example($document_config, 1, 'ACT');
$document_errors = $_SESSION['estandar1_document_error'] ?? [];
$document_old = $_SESSION['estandar1_document_old'] ?? [];
unset($_SESSION['estandar1_document_error'], $_SESSION['estandar1_document_old']);

if (($usuario_rol === 'sst' || $usuario_rol === 'representante') && $empresa_id > 0) {
    try {
        $stmt_hist = $conn->prepare("SELECT d.*, u_rep.nombre AS rep_nombre, u_rep.apellido AS rep_apellido, u_sst.nombre AS sst_nombre, u_sst.apellido AS sst_apellido FROM doc_asignacion_sst d LEFT JOIN usuarios u_rep ON d.representante_id=u_rep.id JOIN usuarios u_sst ON d.sst_id=u_sst.id WHERE u_sst.empresa_id=? AND d.estado='firmado' ORDER BY d.id DESC");
        $stmt_hist->execute([$empresa_id]);
        $historial_actas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

        $stmt_records = $conn->prepare("SELECT r.*, a.nombre_original, a.tamano_bytes, a.id AS archivo_id, CONCAT_WS(' ', u.nombre, u.apellido) AS cargado_por FROM control_documental_registros r LEFT JOIN almacenamiento_archivos a ON a.id=r.almacenamiento_archivo_id LEFT JOIN usuarios u ON u.id=r.usuario_id WHERE r.empresa_id=? AND r.estandar_numero=1 ORDER BY r.creado_en DESC LIMIT 30");
        $stmt_records->execute([$empresa_id]);
        $document_records = $stmt_records->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $document_records = [];
    }
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

        .rep-review-card, .document-control-card, .document-history-card { background:#fff; border:1px solid var(--border); border-radius:16px; margin-bottom:18px; box-shadow:0 8px 24px rgba(30,58,138,.045); overflow:hidden; position:relative; }
        .rep-review-card { padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:18px; background:linear-gradient(135deg,#fff 0%,#fffaf5 100%); }
        .rep-review-main { display:flex; align-items:center; gap:14px; min-width:0; }
        .rep-review-icon { color:#ff7a00; font-size:1.65rem; flex:0 0 auto; }
        .rep-review-card h2, .document-card-head h2 { margin:0; color:var(--blue-dark); font-size:1rem; font-weight:800; }
        .rep-review-card p, .document-card-head p { margin:4px 0 0; color:var(--muted); font-size:.78rem; line-height:1.5; }
        .state-chip { display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px; font-size:.7rem; font-weight:800; white-space:nowrap; }
        .state-chip.pending { color:#b45309; background:#fff7ed; }
        .state-chip.approved, .state-chip.validated { color:#047857; background:#ecfdf5; }
        .state-chip.obsolete { color:#64748b; background:#f1f5f9; }
        .document-card-head { padding:18px 20px; border-bottom:1px solid #e8eef5; display:flex; justify-content:space-between; align-items:center; gap:16px; }
        .document-head-title { display:flex; align-items:flex-start; gap:12px; }
        .document-head-title > i { color:#ff7a00; font-size:1.35rem; margin-top:2px; }
        .doc-link { color:#1d4ed8; text-decoration:none; font-size:.76rem; font-weight:750; white-space:nowrap; }
        .document-control-grid { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(290px,.85fr); gap:0; }
        .document-upload { padding:20px; }
        .upload-fields { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(0,1fr); gap:12px; }
        .doc-field { display:flex; flex-direction:column; gap:6px; }
        .doc-field.full { grid-column:1/-1; }
        .doc-field label { color:#34445a; font-size:.68rem; text-transform:uppercase; letter-spacing:.035em; font-weight:800; }
        .doc-field input { width:100%; box-sizing:border-box; min-height:43px; border:1px solid #cad6e4; border-radius:10px; padding:10px 12px; background:#fbfdff; color:#172554; font:inherit; font-size:.8rem; }
        .doc-field input:focus { outline:none; border-color:#ff8a1f; box-shadow:0 0 0 3px rgba(255,138,31,.12); background:#fff; }
        .file-drop { border:1px dashed #9db7d5; border-radius:12px; padding:14px; background:#f8fbff; display:flex; align-items:center; gap:12px; }
        .file-drop i { color:#2563eb; font-size:1.35rem; }
        .file-drop input { border:0; padding:0; min-height:0; background:transparent; }
        .document-submit { margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .document-submit small { color:#64748b; font-size:.7rem; line-height:1.45; }
        .doc-primary { border:0; border-radius:9px; background:#ff7a00; color:#fff; padding:11px 16px; font:inherit; font-size:.78rem; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
        .upload-guide { padding:20px; border-left:1px solid #e8eef5; background:#fbfdff; }
        .upload-guide h3 { margin:0 0 5px; color:#172554; font-size:.88rem; }
        .upload-guide p { margin:0 0 12px; color:#64748b; font-size:.72rem; line-height:1.5; }
        .guide-list { display:grid; gap:7px; margin-bottom:12px; }
        .guide-item { display:flex; gap:8px; align-items:flex-start; color:#42536a; font-size:.7rem; line-height:1.45; }
        .guide-item i { color:#16a34a; margin-top:2px; }
        .copy-template { width:100%; border:1px solid #cdd9e8; background:#fff; color:#1e3a8a; border-radius:9px; padding:9px 12px; font:inherit; font-size:.72rem; font-weight:750; cursor:pointer; }
        .document-errors { margin:0 20px 16px; padding:12px 14px; border:1px solid #fecaca; border-radius:10px; color:#b91c1c; background:#fff7f7; font-size:.74rem; }
        .document-errors ul { margin:5px 0 0; padding-left:18px; }
        .history-list { padding:8px 16px 16px; display:grid; gap:8px; }
        .history-row { border:1px solid #e3eaf3; border-radius:12px; padding:12px 14px; display:grid; grid-template-columns:minmax(210px,1.45fr) repeat(3,minmax(95px,.55fr)) minmax(130px,.75fr) auto; gap:12px; align-items:center; position:relative; overflow:hidden; }
        .history-row::after { content:'\f15c'; font-family:'Font Awesome 6 Free'; font-weight:900; position:absolute; right:-2px; bottom:-16px; font-size:64px; color:#2563eb; opacity:.025; pointer-events:none; }
        .history-main { min-width:0; }
        .history-main strong { display:block; color:#172554; font-size:.78rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .history-main span, .history-meta span { display:block; color:#8290a4; font-size:.62rem; text-transform:uppercase; font-weight:750; margin-bottom:3px; }
        .history-main small, .history-meta strong { color:#4b5d73; font-size:.68rem; font-weight:650; }
        .history-download { width:34px; height:34px; border:1px solid #d8e2ef; border-radius:9px; color:#1d4ed8; display:grid; place-items:center; text-decoration:none; background:#fff; z-index:2; }
        .empty-history { padding:28px 18px; text-align:center; color:#64748b; font-size:.76rem; }
        /* Escala visual compartida con el Estándar 5 */
        .content-area { max-width:none; padding:30px clamp(24px,2.5vw,44px) 60px; }
        .header-actions { margin-bottom:14px; }
        .estandar-header-group { gap:11px; }
        .icon-box-std { width:40px; height:40px; border-radius:9px; }
        .icon-box-std svg { width:19px; height:19px; }
        .estandar-title { font-size:1rem; line-height:1.22; }
        .std-num-marker { margin-right:3px; }
        .estandar-subtitle { margin-top:3px; font-size:.7rem; line-height:1.32; }
        .alert-status { margin-bottom:14px; padding:11px 13px; border-radius:9px; font-size:.72rem; }
        .rep-review-card { min-height:64px; padding:13px 15px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .rep-review-card::after { content:'\f573'; font-family:'Font Awesome 6 Free'; font-weight:900; position:absolute; right:34px; bottom:-30px; color:#ff7a00; opacity:.035; font-size:7rem; transform:rotate(-10deg); pointer-events:none; }
        .rep-review-icon { font-size:1.2rem; }
        .rep-review-card h2, .document-card-head h2 { font-size:.92rem; }
        .rep-review-card p, .document-card-head p { font-size:.68rem; }
        .state-chip { padding:6px 9px; font-size:.58rem; }
        .document-control-card, .document-history-card { border-radius:10px; margin-bottom:14px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .document-card-head { padding:14px 16px; }
        .document-head-title > i { font-size:1rem; }
        .document-upload, .upload-guide { padding:16px; }
        .doc-field input { min-height:38px; border-radius:8px; padding:8px 10px; font-size:.72rem; }
        .file-drop { border-radius:9px; padding:11px; }
        .info-cards-grid { grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .info-card, body.role-representante .info-card { min-height:116px; padding:13px 14px; gap:9px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); transform:none; }
        .info-card:hover { transform:translateY(-2px); box-shadow:0 13px 28px rgba(15,23,42,.075); }
        .info-bg-icon { right:-8px; font-size:72px; }
        .info-icon-box, body.role-representante .info-icon-box { width:35px; height:35px; border-radius:9px; font-size:.9rem; }
        .info-title { font-size:.84rem; }
        .info-label { font-size:.56rem; }
        .info-value { font-size:.72rem; }
        .e1-acta-details, body.role-representante .e1-acta-details { padding:0 !important; margin-bottom:14px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); overflow:hidden; }
        .e1-acta-summary { min-height:66px; box-sizing:border-box; padding:13px 16px; display:grid; grid-template-columns:36px minmax(0,1fr) auto 18px; gap:11px; align-items:center; cursor:pointer; list-style:none; background:#fff; position:relative; }
        .e1-acta-summary::-webkit-details-marker { display:none; }
        .e1-acta-summary-icon { width:35px; height:35px; border-radius:9px; display:grid; place-items:center; background:#fff3e8; color:#ea580c; font-size:.9rem; }
        .e1-acta-summary-copy { min-width:0; }
        .e1-acta-summary-copy strong { display:block; color:var(--blue-dark); font-size:.84rem; line-height:1.25; }
        .e1-acta-summary-copy small { display:block; margin-top:3px; color:#64748b; font-size:.64rem; line-height:1.35; }
        .e1-acta-chevron { color:#94a3b8; font-size:.68rem; transition:transform .2s ease; }
        .e1-acta-details[open] .e1-acta-chevron { transform:rotate(180deg); }
        .e1-acta-body { padding:20px 22px 22px; border-top:1px solid #e7edf4; background:#fff; }
        .acta-content, body.role-representante .acta-content { margin-bottom:22px; color:#475569; font-size:.76rem; line-height:1.62; }
        .acta-content h4 { margin-bottom:16px; font-size:.94rem; letter-spacing:.025em; }
        .acta-content p { margin-bottom:11px; }
        .signatures-title { margin-bottom:13px; font-size:.88rem; letter-spacing:.025em; }
        .signatures-wrapper-dashed { padding:20px; border-width:1px; border-radius:10px; }
        .signatures-grid { gap:22px; }
        .sig-box .img-placeholder { height:72px; margin-bottom:8px; }
        .sig-box img { height:72px; }
        .sig-box .line { margin-bottom:8px; }
        .sig-box p { font-size:.76rem; }
        .sig-box span { font-size:.68rem; }
        .firma-box canvas { height:110px; }
        .toolbar-acta { margin-bottom:14px; padding:11px 14px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .btn-pdf, .btn-versiones { padding:9px 12px; border-radius:8px; font-size:.68rem; }
        .history-list { padding:8px 12px 12px; }
        .history-row { border-radius:9px; padding:10px 12px; }
        
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
            .rep-review-card, .document-card-head, .document-submit { align-items:flex-start; flex-direction:column; }
            .document-control-grid { grid-template-columns:1fr; }
            .upload-fields { grid-template-columns:1fr; }
            .upload-guide { border-left:0; border-top:1px solid #e8eef5; }
            .history-row { grid-template-columns:1fr 1fr; }
            .history-main { grid-column:1/-1; }
            .history-download { position:absolute; top:10px; right:10px; }
        }
    </style>
</head>
<body class="role-<?php echo htmlspecialchars($usuario_rol); ?>">

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

            <?php if ($usuario_rol === 'representante'): ?>
                <section class="rep-review-card" aria-label="Estado de revisión del representante">
                    <div class="rep-review-main">
                        <i class="fa-solid fa-file-signature rep-review-icon"></i>
                        <div>
                            <h2>Revisión y aprobación del Representante Legal</h2>
                            <p>Comprueba la designación, la licencia del responsable y las firmas antes de legalizar el acta.</p>
                        </div>
                    </div>
                    <?php $rep_state = $doc_asignacion['estado'] ?? 'sin_documento'; ?>
                    <span class="state-chip <?php echo $rep_state === 'firmado' ? 'approved' : 'pending'; ?>">
                        <i class="fa-solid <?php echo $rep_state === 'firmado' ? 'fa-circle-check' : 'fa-clock'; ?>"></i>
                        <?php echo $rep_state === 'firmado' ? 'ACTA LEGALIZADA' : ($rep_state === 'pendiente_firma' ? 'REQUIERE TU FIRMA' : 'SIN ACTA PENDIENTE'); ?>
                    </span>
                </section>
            <?php endif; ?>

            <?php if ($usuario_rol === 'sst'): ?>
                <section class="document-control-card" id="control-documental">
                    <div class="document-card-head">
                        <div class="document-head-title">
                            <i class="fa-solid fa-file-shield"></i>
                            <div>
                                <h2>Formato documental del Estándar 1</h2>
                                <p>El sistema revisa estructura, peso, código, versión, fecha y nombre antes de archivarlo.</p>
                            </div>
                        </div>
                        <a class="doc-link" href="almacenamiento?estandar=1"><i class="fa-solid fa-folder-open"></i> Ver en Archivos</a>
                    </div>

                    <?php if (isset($_GET['formato']) && $_GET['formato'] === 'validado'): ?>
                        <div class="alert-status success" style="margin:16px 20px 0;"><i class="fa-solid fa-circle-check"></i> Formato validado y guardado en Archivos → Estándar 1.</div>
                    <?php endif; ?>
                    <?php if ($document_errors): ?>
                        <div class="document-errors"><strong>El formato fue rechazado. Corrige lo siguiente:</strong><ul><?php foreach ($document_errors as $error): ?><li><?php echo htmlspecialchars((string)$error); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>

                    <div class="document-control-grid">
                        <form class="document-upload" action="procesar_estandar1.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="cargar_formato">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['estandar1_csrf']); ?>">
                            <div class="upload-fields">
                                <div class="doc-field">
                                    <label for="nombre_documento">Nombre documental</label>
                                    <input id="nombre_documento" name="nombre_documento" required value="<?php echo htmlspecialchars($document_old['nombre_documento'] ?? 'Acta de designación del Responsable SG-SST'); ?>">
                                </div>
                                <div class="doc-field">
                                    <label for="codigo_documento">Código de control</label>
                                    <input id="codigo_documento" name="codigo_documento" required value="<?php echo htmlspecialchars($document_old['codigo_documento'] ?? $document_code_example); ?>">
                                </div>
                                <div class="doc-field">
                                    <label for="version_documento">Versión</label>
                                    <input id="version_documento" name="version_documento" required value="<?php echo htmlspecialchars($document_old['version_documento'] ?? $document_config['version_inicial']); ?>">
                                </div>
                                <div class="doc-field">
                                    <label for="fecha_documento">Fecha de emisión</label>
                                    <input id="fecha_documento" type="date" name="fecha_documento" required value="<?php echo htmlspecialchars($document_old['fecha_documento'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div class="doc-field full">
                                    <label for="archivo_formato">Archivo a analizar</label>
                                    <div class="file-drop">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        <input id="archivo_formato" type="file" name="archivo_formato" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                                    </div>
                                </div>
                            </div>
                            <div class="document-submit">
                                <small>PDF o DOCX · máximo 15 MB · nombre: <strong><?php echo htmlspecialchars($document_code_example . '_' . $document_config['version_inicial']); ?>.pdf</strong></small>
                                <button class="doc-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Analizar y archivar</button>
                            </div>
                        </form>

                        <aside class="upload-guide">
                            <h3>Cómo preparar el formato</h3>
                            <p>Incluye este bloque dentro del documento y usa los mismos datos en el formulario.</p>
                            <div class="guide-list">
                                <div class="guide-item"><i class="fa-solid fa-check"></i><span>Título, código <strong><?php echo htmlspecialchars($document_code_example); ?></strong>, versión y fecha de emisión.</span></div>
                                <div class="guide-item"><i class="fa-solid fa-check"></i><span>Campos Elaboró, Revisó, Aprobó y una sección de control de cambios.</span></div>
                                <div class="guide-item"><i class="fa-solid fa-check"></i><span>El nombre del archivo debe contener el código y la versión configurados.</span></div>
                                <div class="guide-item"><i class="fa-solid fa-folder-tree"></i><span>Cuando cumple, se almacena automáticamente en Archivos → Estándar 1.</span></div>
                            </div>
                            <button class="copy-template" type="button" onclick="copiarPlantillaDocumental(this)"><i class="fa-regular fa-copy"></i> Copiar bloque de control</button>
                        </aside>
                    </div>
                </section>
            <?php endif; ?>

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

            <?php $acta_expandida = !$doc_asignacion || ($doc_asignacion['estado'] ?? 'borrador') !== 'firmado'; ?>
            <details class="acta-preview-card e1-acta-details" <?php echo $acta_expandida ? 'open' : ''; ?>>
                <summary class="e1-acta-summary">
                    <span class="e1-acta-summary-icon"><i class="fa-solid fa-file-signature"></i></span>
                    <span class="e1-acta-summary-copy">
                        <strong>Acta de designación del Responsable SG-SST</strong>
                        <small><?php echo $acta_expandida ? 'Revisa el contenido y completa las firmas requeridas.' : 'Documento legalizado. Expande para consultar el contenido y las firmas.'; ?></small>
                    </span>
                    <span class="state-chip <?php echo !$acta_expandida ? 'approved' : 'pending'; ?>"><?php echo !$acta_expandida ? 'LEGALIZADA' : 'EN GESTIÓN'; ?></span>
                    <i class="fa-solid fa-chevron-down e1-acta-chevron"></i>
                </summary>
                <div class="e1-acta-body">
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
            </details>

            <?php if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado'): ?>
                <div class="toolbar-acta">
                    <button id="btnDescargarPDF" onclick="generarYGuardarPDF()" class="btn-pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        Descargar PDF Legalizado
                    </button>
                    
                    <div class="toolbar-acta-group">
                        <a href="#historial-documental" class="btn-versiones">
                            <i class="fa-solid fa-clock-rotate-left"></i> Historial
                        </a>
                        
                        <?php if ($usuario_rol === 'sst'): ?>
                            <a href="#" onclick="showConfirmModal('Actualizar Acta', 'Al actualizar el acta, se creará una nueva versión en borrador y se requerirán nuevas firmas. ¿Deseas continuar?', 'procesar_estandar1.php?accion=nueva_version', 'warning', 'Sí, actualizar'); return false;" class="btn-versiones" style="color: var(--primary2);">
                                <i class="fa-solid fa-arrows-rotate"></i> Actualizar Acta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <section class="document-history-card" id="historial-documental">
                <div class="document-card-head">
                    <div class="document-head-title">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <div>
                            <h2>Historial y trazabilidad documental</h2>
                            <p>Versiones, responsables, fechas y estado de los formatos y actas legalizadas del Estándar 1.</p>
                        </div>
                    </div>
                    <a class="doc-link" href="almacenamiento?estandar=1"><i class="fa-solid fa-cloud"></i> Abrir todos los archivos</a>
                </div>
                <?php if ($document_records): ?>
                    <div class="history-list">
                        <?php foreach ($document_records as $record): ?>
                            <?php
                                $record_state = (string)($record['estado'] ?? 'validado');
                                $state_label = ['validado' => 'VALIDADO', 'aprobado' => 'APROBADO', 'rechazado' => 'RECHAZADO', 'obsoleto' => 'OBSOLETO'][$record_state] ?? strtoupper($record_state);
                                $type_label = ['formato' => 'Formato base', 'soporte' => 'Soporte', 'pdf_legalizado' => 'Acta legalizada'][$record['tipo_documento']] ?? 'Documento';
                            ?>
                            <article class="history-row">
                                <div class="history-main">
                                    <span><?php echo htmlspecialchars($type_label); ?></span>
                                    <strong title="<?php echo htmlspecialchars($record['nombre_documento']); ?>"><?php echo htmlspecialchars($record['nombre_documento']); ?></strong>
                                    <small><?php echo htmlspecialchars($record['nombre_original'] ?: $record['archivo_original'] ?: 'Archivo documental'); ?></small>
                                </div>
                                <div class="history-meta"><span>Código</span><strong><?php echo htmlspecialchars($record['codigo_documento']); ?></strong></div>
                                <div class="history-meta"><span>Versión</span><strong><?php echo htmlspecialchars($record['version_documento']); ?></strong></div>
                                <div class="history-meta"><span>Emisión</span><strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($record['fecha_documento']))); ?></strong></div>
                                <div class="history-meta"><span>Responsable</span><strong><?php echo htmlspecialchars($record['cargado_por'] ?: 'Sistema'); ?></strong></div>
                                <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;z-index:2;">
                                    <span class="state-chip <?php echo $record_state === 'aprobado' ? 'approved' : ($record_state === 'validado' ? 'validated' : 'obsolete'); ?>"><?php echo $state_label; ?></span>
                                    <?php if (!empty($record['archivo_id'])): ?><a class="history-download" href="descargar_archivo?id=<?php echo (int)$record['archivo_id']; ?>" title="Descargar"><i class="fa-solid fa-download"></i></a><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($historial_actas): ?>
                    <div class="history-list">
                        <?php foreach ($historial_actas as $index => $legacy): ?>
                            <article class="history-row">
                                <div class="history-main"><span>Acta legalizada anterior</span><strong>Acta de designación del Responsable SG-SST</strong><small>Registro histórico de firmas</small></div>
                                <div class="history-meta"><span>Código</span><strong>ACTA-<?php echo (int)$legacy['id']; ?></strong></div>
                                <div class="history-meta"><span>Versión</span><strong>Hist. <?php echo count($historial_actas) - $index; ?></strong></div>
                                <div class="history-meta"><span>Firma</span><strong><?php echo !empty($legacy['fecha_firma']) ? htmlspecialchars(date('d/m/Y', strtotime($legacy['fecha_firma']))) : 'Sin fecha'; ?></strong></div>
                                <div class="history-meta"><span>Representante</span><strong><?php echo htmlspecialchars(trim(($legacy['rep_nombre'] ?? '') . ' ' . ($legacy['rep_apellido'] ?? '')) ?: 'Registrado'); ?></strong></div>
                                <span class="state-chip approved">LEGALIZADA</span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-history"><i class="fa-regular fa-folder-open" style="font-size:1.5rem;display:block;margin-bottom:8px;color:#9fb0c5;"></i>Aún no hay versiones documentales. El primer formato validado aparecerá aquí y en Archivos.</div>
                <?php endif; ?>
            </section>

        </div>
    </main>

    <?php include 'components/modal_versiones_acta.php'; ?>
    <?php include 'components/modal_confirmacion.php'; ?>

    <script>
        window.copiarPlantillaDocumental = async function(button) {
            const template = `TÍTULO: Acta de designación del Responsable SG-SST
CÓDIGO: <?php echo addslashes($document_code_example); ?>
VERSIÓN: <?php echo addslashes((string)$document_config['version_inicial']); ?>
FECHA DE EMISIÓN: AAAA-MM-DD
ELABORÓ: Nombre / Cargo
REVISÓ: Nombre / Cargo
APROBÓ: Nombre / Cargo
CONTROL DE CAMBIOS: Descripción breve del ajuste realizado`;
            try {
                await navigator.clipboard.writeText(template);
                const original = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-check"></i> Bloque copiado';
                setTimeout(() => { button.innerHTML = original; }, 1800);
            } catch (error) {
                window.prompt('Copia este bloque de control documental:', template);
            }
        };

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
