<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/calendar_integration.php';

// Exige sesión válida
$u = require_auth($conn);

// =======================================================
// LÓGICA PARA SUBIR LA FOTO DE PERFIL CON AJAX (BLINDADA)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    header('Content-Type: application/json');
    
    $uploadDir = 'uploads/perfiles/';
    // Crea la carpeta si no existe protegiendo permisos
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['foto_perfil'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Validar estrictamente que sea imagen por seguridad
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            // Nombre único y seguro
            $fileName = 'user_' . $_SESSION['usuario_id'] . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                
                // --- MAGIA: BORRAR LA IMAGEN ANTERIOR DEL SERVIDOR ---
                // 1. Buscamos la ruta de la foto actual en la base de datos
                $stmtOld = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                $stmtOld->execute([$_SESSION['usuario_id']]);
                $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
                
                // 2. Si tenía foto y el archivo físico existe, lo borramos (unlink)
                if ($oldData && !empty($oldData['foto_perfil'])) {
                    $rutaVieja = $oldData['foto_perfil'];
                    if (file_exists($rutaVieja)) {
                        unlink($rutaVieja); // Borrado físico del servidor
                    }
                }
                // -----------------------------------------------------

                // Actualizar BD con la foto nueva
                $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                $stmt->execute([$destination, $_SESSION['usuario_id']]);
                
                echo json_encode(['success' => true, 'ruta' => $destination]);
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'error' => 'Error de formato o seguridad al subir la imagen']);
    exit;
}
// =======================================================

// Variables de sesión básicas
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Mapear roles a nombres legibles
$roles_nombres = [
    'representante' => 'Representante Legal',
    'sst' => 'Responsable SG-SST',
    'trabajador' => 'Trabajador'
];
$rol_display = $roles_nombres[$usuario_rol] ?? 'Usuario';

// 1. Obtener TODA la información del usuario en sesión
try {
    $sql = "SELECT nombre, apellido, cedula, email, telefono, ciudad, rol, foto_perfil,
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

$dashboard_calendar = null;
if (in_array($usuario_rol, ['sst', 'representante'], true)) {
    try {
        $dashboard_calendar = calendar_connection($conn, (int)$_SESSION['usuario_id']);
    } catch (Throwable $e) {
        $dashboard_calendar = null;
    }
}

// LÓGICA DEL MODAL DE EMPRESA
$mostrar_modal_empresa = false;
if ($usuario_rol === 'representante') {
    if (empty($usuario_info['nombre_empresa']) || 
        empty($usuario_info['tipo_persona']) || 
        empty($usuario_info['regimen_tributario']) || 
        empty($usuario_info['tipo_doc_empresa']) || 
        empty($usuario_info['num_doc_empresa'])) {
        $mostrar_modal_empresa = true;
    }
}

// 2. OBTENER EL HISTORIAL DE ACTAS
$historial_actas = [];
if ($usuario_rol === 'sst' || $usuario_rol === 'representante') {
    try {
        $sst_target_id = 0;
        if ($usuario_rol === 'sst') {
            $sst_target_id = $_SESSION['usuario_id'];
        } elseif ($usuario_rol === 'representante') {
            $stmt_doc = $conn->prepare("SELECT sst_id FROM doc_asignacion_sst WHERE estado IN ('pendiente_firma', 'firmado') ORDER BY id DESC LIMIT 1");
            $stmt_doc->execute();
            $doc_temp = $stmt_doc->fetch(PDO::FETCH_ASSOC);
            if ($doc_temp) {
                $sst_target_id = $doc_temp['sst_id'];
            }
        }

        if ($sst_target_id > 0) {
            $stmt_hist = $conn->prepare("SELECT d.*, 
                                           u_rep.nombre as rep_nombre, 
                                           u_rep.apellido as rep_apellido, 
                                           u_sst.nombre as sst_nombre, 
                                           u_sst.apellido as sst_apellido
                                    FROM doc_asignacion_sst d 
                                    LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id 
                                    LEFT JOIN usuarios u_sst ON d.sst_id = u_sst.id 
                                    WHERE d.sst_id = ? AND d.estado = 'firmado' 
                                    ORDER BY d.id DESC");
            $stmt_hist->execute([$sst_target_id]);
            $historial_actas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { }
}

function mostrarDato($dato) {
    return !empty($dato) ? htmlspecialchars($dato) : '<span style="color: #94a3b8; font-style: italic; font-weight: 400;">No registrado</span>';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SG-SST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff8a1f;
            --primary2: #ff7a00;
            --bg1: #edf4fb;
            --bg2: #f7f9fc;
            --card: #ffffff;
            --text: #1f2d3d;
            --muted: #5f6f82;
            --border: #dbe3ec;
            --radius: 12px;
            --blue-main: #2b5a9e;
        }

        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid #bbf7d0; transition: opacity 0.5s ease, transform 0.5s ease; opacity: 1; transform: translateY(0); }
        .alert-success.hide { opacity: 0; transform: translateY(-10px); pointer-events: none; }
        
        /* Notificación Flotante para la Foto */
        .toast-noti { position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(100px); opacity: 0; transition: all 0.4s ease; z-index: 9999; display: flex; align-items: center; gap: 10px; }
        .toast-noti.show { transform: translateY(0); opacity: 1; }

        .btn-edit { background-color: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background-color 0.2s, transform 0.1s; }
        .btn-edit:hover { background-color: var(--primary2); transform: translateY(-1px); }
        
        /* =========================================
           TARJETA DE PERFIL HERO
           ========================================= */
        .profile-hero-card {
            background: #ffffff;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            display: grid;
            grid-template-columns: 128px minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;
            min-height: 190px;
            margin-bottom: 22px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.055);
            position: relative;
            overflow: hidden; 
        }

        .card-blob { position: absolute; border-radius: 50%; filter: blur(70px); z-index: 0; opacity: 0.11; animation: floatCardAnim 12s infinite alternate ease-in-out; }
        .card-blob-1 { width: 310px; height: 310px; background: var(--primary); top: -190px; left: -90px; }
        .card-blob-2 { width: 280px; height: 280px; background: #3b82f6; bottom: -190px; right: 2%; animation-delay: -6s; }

        @keyframes floatCardAnim {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 20px) scale(1.1); }
            100% { transform: translate(-20px, 40px) scale(0.9); }
        }

        /* ICONOS FLOTANTES */
        .float-icon { position: absolute; z-index: 0; animation: spinFloat 20s infinite linear; pointer-events: none; }
        .fi-x1 { top: 10%; right: 7%; font-size: 54px; animation-duration: 25s; color: rgba(255, 122, 0, 0.065); }
        .fi-c1 { bottom: 4%; right: 26%; font-size: 88px; animation-direction: reverse; color: rgba(37, 99, 235, 0.055); }
        .fi-x2 { top: 49%; right: 14%; font-size: 36px; animation-duration: 15s; color: rgba(59, 130, 246, 0.055); }
        .fi-c2 { top: 10%; right: 36%; font-size: 64px; animation-duration: 30s; animation-direction: reverse; color: rgba(255, 122, 0, 0.05); }
        .fi-x3 { bottom: 12%; right: 3%; font-size: 46px; animation-duration: 20s; color: rgba(30, 58, 138, 0.04); }

        @keyframes spinFloat {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }

        .profile-hero-photo, .profile-hero-info, .profile-btn-edit { position: relative; z-index: 2; }

        .profile-hero-photo {
            width: 128px;
            height: 128px;
            background: #f8fafc;
            border-radius: 14px;
            border: 1px dashed #aebed0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(15,23,42,.07);
        }

        .profile-hero-photo:hover { background: #fff7ed; border-color: var(--primary); transform: translateY(-2px); }
        .profile-hero-photo img { width: 100%; height: 100%; object-fit: cover; }

        .loading-spinner { display: none; color: var(--primary); font-size: 2rem; animation: spin 1s infinite linear;}
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .profile-photo-placeholder { text-align: center; color: #64748b; font-size: 0.72rem; display: flex; flex-direction: column; gap: 7px; align-items: center; transition: color 0.3s ease; }
        .profile-hero-photo:hover .profile-photo-placeholder { color: var(--primary); }

        .profile-hero-info { min-width: 0; display: flex; flex-direction: column; gap: 7px; }
        .profile-hero-kicker { display:flex; align-items:center; gap:6px; color:#64748b; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .profile-hero-kicker i { color:var(--primary2); }
        .profile-hero-name { max-width: 780px; font-size: clamp(1.25rem, 2.2vw, 1.85rem); font-weight: 800; margin: 0; line-height: 1.15; color: #1e3a8a; letter-spacing: -0.025em; overflow-wrap:anywhere; }
        .profile-hero-role { width:max-content; max-width:100%; font-size: .68rem; color: #c2410c; background:#fff7ed; border:1px solid #fed7aa; border-radius:999px; padding:4px 9px; font-weight: 800; margin: 0 0 5px; text-transform: uppercase; letter-spacing: 0.04em; }

        .profile-hero-details { display: flex; flex-wrap: wrap; gap: 7px; min-width:0; }
        .profile-hero-detail-item { min-width:0; max-width:100%; display: flex; align-items: center; gap: 7px; font-size: .68rem; font-weight:650; color: #526177; background: #f8fafc; padding: 7px 9px; border-radius: 8px; border: 1px solid #e2e8f0; overflow-wrap:anywhere; }
        .profile-hero-detail-item i { color:#2563eb; flex:none; }

        .profile-btn-edit { position: relative; top: auto; right: auto; align-self:flex-start; background: #ffffff; color: #c2410c; border: 1px solid #fdba74; box-shadow: 0 5px 14px rgba(234,88,12,.07); padding:9px 12px; font-size:.68rem; white-space:nowrap; }
        .profile-btn-edit:hover { background: var(--primary); border-color: var(--primary); color: white; }

        /* ACCESO ÚNICO A CALENDARIO */
        .calendar-shortcut { --calendar-color:#2563eb; position:relative; overflow:hidden; width:min(100%,560px); min-height:0; margin:0 0 13px auto; padding:7px 9px; display:grid; grid-template-columns:29px minmax(0,1fr) auto; gap:8px; align-items:center; box-sizing:border-box; border:1px solid #dfe7f0; border-radius:9px; background:rgba(255,255,255,.72); box-shadow:none; text-decoration:none; transition:background .2s,border-color .2s,transform .2s; }
        .calendar-shortcut.connected { --calendar-color:#059669; }
        .calendar-shortcut:hover { transform:translateY(-1px); border-color:#b9cff2; background:#fff; }
        .calendar-shortcut-icon { position:relative; z-index:2; width:29px; height:29px; display:grid; place-items:center; border-radius:7px; background:color-mix(in srgb,var(--calendar-color) 9%,white); color:var(--calendar-color); font-size:.66rem; }
        .calendar-shortcut-copy { position:relative; z-index:2; min-width:0; }
        .calendar-shortcut-copy strong { display:block; color:#294a7e; font-size:.67rem; }
        .calendar-shortcut-copy span { display:block; margin-top:2px; color:#7c8a9e; font-size:.57rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .calendar-shortcut-state { position:relative; z-index:2; display:inline-flex; align-items:center; gap:5px; padding:4px 7px; border-radius:999px; background:transparent; color:var(--calendar-color); font-size:.56rem; font-weight:850; white-space:nowrap; }
        .calendar-shortcut-watermark { position:absolute; right:58px; bottom:-21px; color:var(--calendar-color); opacity:.03; font-size:3.6rem; transform:rotate(-10deg); pointer-events:none; }

        /* DETALLES ABAJO */
        .header-section { margin:13px 0 8px; padding-bottom:7px; border-bottom:1px solid var(--border); }
        .section-title { font-size:.78rem; font-weight:800; color:#1e3a8a; margin:0; display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:.035em; }
        .title-icon-wrapper { width:28px; height:28px; background:rgba(255,138,31,.1); border-radius:7px; display:flex; align-items:center; justify-content:center; color:var(--primary2); }
        
        .section-desc { color: var(--muted); font-size: 0.85rem; margin: 10px 0 0 0; display: flex; align-items: flex-start; gap: 8px; text-align: left; line-height: 1.4; }
        .section-desc svg { flex-shrink: 0; margin-top: 2px; }

        .info-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .info-card { --info-color:#ea580c; --info-soft:#fff3e8; position:relative; min-width:0; min-height:64px; overflow:hidden; display:grid; grid-template-columns:minmax(0,1fr) 28px; align-items:center; gap:7px; padding:8px 9px; box-sizing:border-box; border:1px solid var(--border); border-radius:9px; background:#fff; box-shadow:0 5px 16px rgba(15,23,42,.035); isolation:isolate; transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease; }
        .info-card:nth-child(2) { --info-color:#2563eb; --info-soft:#eff6ff; }
        .info-card:nth-child(3) { --info-color:#7c3aed; --info-soft:#f5f3ff; }
        .info-card:nth-child(4) { --info-color:#059669; --info-soft:#ecfdf5; }
        .info-card:nth-child(5) { --info-color:#d97706; --info-soft:#fffbeb; }
        .info-card:nth-child(6) { --info-color:#dc2626; --info-soft:#fef2f2; }
        .info-card.certification-card { --info-color:#2563eb; --info-soft:#eff6ff; }
        .info-card:hover { transform:translateY(-2px); border-color:var(--info-color); box-shadow:0 9px 22px rgba(15,23,42,.07); }
        .icon-box { position:relative; z-index:2; grid-column:2; grid-row:1; width:28px; height:28px; display:grid; place-items:center; border-radius:7px; background:var(--info-soft) !important; color:var(--info-color) !important; font-size:.62rem; transition:transform .22s ease; }
        .info-card:hover .icon-box { transform:scale(1.05); }
        .info-content { position:relative; z-index:2; grid-column:1; grid-row:1; min-width:0; align-self:center; display:flex; flex-direction:column; align-items:flex-start; gap:4px; overflow:hidden; }
        .info-label { width:max-content; max-width:100%; min-height:17px; display:inline-flex; align-items:center; margin:0; padding:0 6px; border-radius:999px; background:var(--info-soft); color:var(--info-color); font-size:.46rem; line-height:1; text-transform:uppercase; letter-spacing:.03em; font-weight:900; }
        .info-value { max-width:100%; margin:0; color:#172554; font-size:.66rem; line-height:1.25; font-weight:750; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .info-watermark { position:absolute; z-index:1; right:-8px; bottom:-17px; color:var(--info-color); font-size:3.65rem; opacity:.04; transform:rotate(-10deg); pointer-events:none; transition:opacity .22s ease,transform .22s ease; }
        .info-card:hover .info-watermark { opacity:.08; transform:rotate(-6deg) scale(1.03); }
        .badge-rol { display:inline-block; padding:2px 7px; border-radius:6px; background:var(--info-soft); color:var(--info-color); font-size:.57rem; line-height:1.2; font-weight:800; }

        /* =========================================
           AJUSTES MÓVILES
           ========================================= */
        @media (max-width: 1050px) {
            .content-area { padding: 26px 24px; }
            .profile-hero-card { grid-template-columns:112px minmax(0,1fr); }
            .profile-hero-photo { width:112px; height:112px; }
            .profile-btn-edit { grid-column:2; }
            .info-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px 38px; }
            
            .profile-hero-card { 
                grid-template-columns: 88px minmax(0,1fr);
                align-items: flex-start; 
                text-align: left; 
                padding: 16px;
                gap: 14px;
            }
            .profile-hero-photo { width:88px; height:88px; border-radius:11px; }
            .profile-hero-info { align-self:center; }
            .profile-hero-kicker { display:none; }
            .profile-hero-details { grid-column:1/-1; display:grid; grid-template-columns:1fr; width:100%; }
            .profile-hero-detail-item { width:100%; }
            .profile-btn-edit { 
                grid-column:1/-1;
                width: 100%; 
                justify-content: center; 
            }
            .calendar-shortcut { width:100%; grid-template-columns:29px minmax(0,1fr) auto; }
            .calendar-shortcut-state { grid-column:auto; width:max-content; }
            .calendar-shortcut-watermark { right:-8px; }
            
            /* Ajuste de íconos para que se vean bien en móvil sin estorbar */
            .float-icon { opacity: 1; } /* Les devuelvo la opacidad base */
            .fi-x1 { top: 5%; right: 5%; font-size: 40px; }
            .fi-c1 { bottom: 25%; right: 10%; font-size: 60px; }
            .fi-x2 { top: 40%; right: 5%; font-size: 30px; }
            .fi-c2 { top: 10%; right: 20%; font-size: 50px; }
            .fi-x3 { bottom: 10%; right: 2%; font-size: 35px; }
        }

        @media (max-width: 520px) {
            .content-area { padding-left:12px; padding-right:12px; }
            .profile-hero-card { grid-template-columns:72px minmax(0,1fr); padding:14px; gap:12px; }
            .profile-hero-photo { width:72px; height:72px; }
            .profile-hero-name { font-size:1.05rem; }
            .profile-hero-role { font-size:.56rem; }
            .calendar-shortcut-copy span { display:none; }
            .info-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; }
            .info-card { min-height:62px; padding:8px; }
            .info-watermark { right:-7px; bottom:-15px; font-size:3.4rem; }
            .header-section { margin-top:13px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .card-blob, .float-icon { animation:none; }
        }
    </style>
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                <div class="alert-success" id="alertSuccess">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ¡Tu información ha sido actualizada correctamente!
                </div>
            <?php endif; ?>

            <div id="toastNoti" class="toast-noti">
                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i>
                <span id="toastMsg">Foto actualizada</span>
            </div>

            <?php if (!empty($usuario_info)): ?>
                <div class="profile-hero-card">
                    <div class="card-blob card-blob-1"></div>
                    <div class="card-blob card-blob-2"></div>
                    
                    <i class="fa-solid fa-plus float-icon fi-x1"></i>
                    <i class="fa-regular fa-circle float-icon fi-c1"></i>
                    <i class="fa-solid fa-plus float-icon fi-x2"></i>
                    <i class="fa-regular fa-circle float-icon fi-c2"></i>
                    <i class="fa-solid fa-plus float-icon fi-x3"></i>

                    <div class="profile-hero-photo" id="photoContainer" title="Subir o cambiar foto">
                        <i class="fa-solid fa-spinner loading-spinner" id="photoSpinner"></i>
                        
                        <div id="photoContent" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($usuario_info['foto_perfil']) && file_exists($usuario_info['foto_perfil'])): ?>
                                <img src="<?php echo htmlspecialchars($usuario_info['foto_perfil']); ?>?v=<?php echo time(); ?>" alt="Foto de perfil" id="profileImagePreview">
                            <?php else: ?>
                                <div class="profile-photo-placeholder" id="profilePlaceholder">
                                    <i class="fa-solid fa-camera" style="font-size: 1.8rem;"></i>
                                    <span>Subir foto</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <input type="file" id="foto-upload" style="display: none;" accept="image/png, image/jpeg, image/jpg, image/webp">
                    </div>

                    <div class="profile-hero-info">
                        <div class="profile-hero-kicker"><i class="fa-solid fa-shield-heart"></i> Perfil de usuario PreventWork</div>
                        <h1 class="profile-hero-name"><?php echo mostrarDato($usuario_info['nombre'] . ' ' . $usuario_info['apellido']); ?></h1>
                        <p class="profile-hero-role"><?php echo htmlspecialchars($rol_display); ?></p>
                        
                        <div class="profile-hero-details">
                            <div class="profile-hero-detail-item">
                                <i class="fa-regular fa-id-card"></i>
                                CC. <?php echo mostrarDato($usuario_info['cedula']); ?>
                            </div>
                            <div class="profile-hero-detail-item">
                                <i class="fa-regular fa-envelope"></i>
                                <?php echo mostrarDato($usuario_info['email']); ?>
                            </div>
                            <div class="profile-hero-detail-item">
                                <i class="fa-solid fa-phone"></i>
                                <?php echo mostrarDato($usuario_info['telefono']); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                        <button class="btn-edit profile-btn-edit" id="btnOpenModal">
                            <i class="fa-solid fa-pen-to-square"></i> Editar Perfil
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (in_array($usuario_rol, ['sst', 'representante'], true)): ?>
                    <a href="configuracion.php?tab=calendar" class="calendar-shortcut <?php echo $dashboard_calendar ? 'connected' : ''; ?>">
                        <div class="calendar-shortcut-icon">
                            <i class="fa-solid <?php echo $dashboard_calendar ? 'fa-calendar-check' : 'fa-calendar-plus'; ?>"></i>
                        </div>
                        <div class="calendar-shortcut-copy">
                            <strong>Agenda y reuniones</strong>
                            <span>
                                <?php if ($dashboard_calendar): ?>
                                    <?php echo htmlspecialchars(calendar_provider_label($dashboard_calendar['provider'])); ?> conectado · administra la sincronización desde Configuración
                                <?php else: ?>
                                    Conecta Google Calendar o Microsoft Outlook para programar reuniones
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="calendar-shortcut-state">
                            <i class="fa-solid <?php echo $dashboard_calendar ? 'fa-circle-check' : 'fa-arrow-right'; ?>"></i>
                            <?php echo $dashboard_calendar ? 'Conectado' : 'Configurar'; ?>
                        </span>
                        <i class="fa-regular fa-calendar-check calendar-shortcut-watermark" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>

                <div class="header-section">
                    <h2 class="section-title">
                        <div class="title-icon-wrapper">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        Detalle de Información
                    </h2>
                </div>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-regular fa-user"></i></div>
                        <div class="info-content">
                            <p class="info-label">Nombre Completo</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['nombre'] . ' ' . $usuario_info['apellido']); ?></p>
                        </div>
                        <i class="fa-regular fa-user info-watermark" aria-hidden="true"></i>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-regular fa-id-card"></i></div>
                        <div class="info-content">
                            <p class="info-label">Documento</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['cedula']); ?></p>
                        </div>
                        <i class="fa-regular fa-id-card info-watermark" aria-hidden="true"></i>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-regular fa-envelope"></i></div>
                        <div class="info-content">
                            <p class="info-label">Correo Electrónico</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['email']); ?></p>
                        </div>
                        <i class="fa-regular fa-envelope info-watermark" aria-hidden="true"></i>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-content">
                            <p class="info-label">Teléfono</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['telefono']); ?></p>
                        </div>
                        <i class="fa-solid fa-phone info-watermark" aria-hidden="true"></i>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-content">
                            <p class="info-label">Ciudad</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['ciudad']); ?></p>
                        </div>
                        <i class="fa-solid fa-location-dot info-watermark" aria-hidden="true"></i>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-briefcase"></i></div>
                        <div class="info-content">
                            <p class="info-label">Rol Asignado</p>
                            <p class="info-value"><span class="badge-rol"><?php echo htmlspecialchars($rol_display); ?></span></p>
                        </div>
                        <i class="fa-solid fa-briefcase info-watermark" aria-hidden="true"></i>
                    </div>
                </div>

                <?php if ($usuario_info['licencia_sst'] === 'si' || !empty($usuario_info['numero_licencia']) || $usuario_rol === 'sst'): ?>
                    <div class="header-section" style="margin-top: 20px;">
                        <h2 class="section-title">
                            <div class="title-icon-wrapper" style="background: rgba(74, 127, 191, 0.12); color: #4a7fbf;">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            Certificación SG-SST
                        </h2>
                    </div>

                    <div class="info-grid">
                        <div class="info-card certification-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-solid fa-id-badge"></i></div>
                            <div class="info-content"><p class="info-label">Tipo de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['tipo_licencia']); ?></p></div>
                            <i class="fa-solid fa-id-badge info-watermark" aria-hidden="true"></i>
                        </div>
                        <div class="info-card certification-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-solid fa-hashtag"></i></div>
                            <div class="info-content"><p class="info-label">Número de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['numero_licencia']); ?></p></div>
                            <i class="fa-solid fa-hashtag info-watermark" aria-hidden="true"></i>
                        </div>
                        <div class="info-card certification-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-regular fa-calendar-check"></i></div>
                            <div class="info-content"><p class="info-label">Fecha de Expedición</p><p class="info-value"><?php echo mostrarDato($usuario_info['fecha_licencia']); ?></p></div>
                            <i class="fa-regular fa-calendar-check info-watermark" aria-hidden="true"></i>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 12px; font-weight: 600; text-align: center; border: 1px solid #fca5a5;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                    Hubo un problema al consultar tus datos. Por favor, asegúrate de haber creado la columna 'foto_perfil' en la base de datos.
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                <?php include 'components/estandares_minimos.php'; ?>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'components/modal_editar_perfil.php'; ?>
    <?php include 'components/modal_confirmacion.php'; ?>
    <?php include 'components/modal_versiones_acta.php'; ?>
    <?php include 'components/modal_empresa.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alertSuccess = document.getElementById('alertSuccess');
            if (alertSuccess) {
                setTimeout(() => {
                    alertSuccess.classList.add('hide');
                    setTimeout(() => {
                        alertSuccess.remove();
                        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({path:newUrl}, '', newUrl);
                    }, 500);
                }, 4000); 
            }
        });

        // ==========================================
        // LÓGICA AJAX PARA SUBIR FOTO EN VIVO
        // ==========================================
        const photoContainer = document.getElementById('photoContainer');
        const fileInput = document.getElementById('foto-upload');
        const photoContent = document.getElementById('photoContent');
        const photoSpinner = document.getElementById('photoSpinner');
        const toastNoti = document.getElementById('toastNoti');
        const toastMsg = document.getElementById('toastMsg');

        if(photoContainer){
            photoContainer.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                photoContent.style.display = 'none';
                photoSpinner.style.display = 'block';

                const formData = new FormData();
                formData.append('foto_perfil', file);

                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    photoSpinner.style.display = 'none';
                    photoContent.style.display = 'flex';

                    if(data.success) {
                        photoContent.innerHTML = `<img src="${data.ruta}?v=${new Date().getTime()}" alt="Foto de perfil" style="width: 100%; height: 100%; object-fit: cover;">`;
                        toastMsg.innerText = "Foto actualizada con éxito";
                        toastNoti.style.background = "#1e293b";
                        toastNoti.innerHTML = `<i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> <span id="toastMsg">Foto actualizada con éxito</span>`;
                        showToast();
                    } else {
                        toastNoti.style.background = "#7f1d1d";
                        toastNoti.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="color: #fca5a5;"></i> <span id="toastMsg">${data.error}</span>`;
                        showToast();
                    }
                })
                .catch(error => {
                    photoSpinner.style.display = 'none';
                    photoContent.style.display = 'flex';
                    toastNoti.style.background = "#7f1d1d";
                    toastNoti.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color: #fca5a5;"></i> <span id="toastMsg">Error de conexión</span>`;
                    showToast();
                });
            });
        }

        function showToast() {
            toastNoti.classList.add('show');
            setTimeout(() => {
                toastNoti.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
