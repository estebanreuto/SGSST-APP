<?php
require_once 'config/db.php';
require_once 'config/auth.php';

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
            background: linear-gradient(135deg, #1e293b, #0f172a); 
            color: white;
            border-radius: 16px;
            padding: 32px;
            display: flex;
            gap: 32px;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden; 
        }

        .card-blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: 1; opacity: 0.25; animation: floatCardAnim 12s infinite alternate ease-in-out; }
        .card-blob-1 { width: 350px; height: 350px; background: var(--primary); top: -150px; left: -100px; }
        .card-blob-2 { width: 300px; height: 300px; background: #3b82f6; bottom: -150px; right: 0%; animation-delay: -6s; }

        @keyframes floatCardAnim {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 20px) scale(1.1); }
            100% { transform: translate(-20px, 40px) scale(0.9); }
        }

        /* ICONOS FLOTANTES */
        .float-icon { position: absolute; z-index: 1; animation: spinFloat 20s infinite linear; pointer-events: none; }
        .fi-x1 { top: 15%; right: 8%; font-size: 60px; animation-duration: 25s; color: rgba(255, 138, 31, 0.08); }
        .fi-c1 { bottom: 10%; right: 25%; font-size: 100px; animation-direction: reverse; color: rgba(255, 255, 255, 0.06); }
        .fi-x2 { top: 50%; right: 15%; font-size: 40px; animation-duration: 15s; color: rgba(59, 130, 246, 0.08); }
        .fi-c2 { top: 20%; right: 35%; font-size: 70px; animation-duration: 30s; animation-direction: reverse; color: rgba(255, 138, 31, 0.05); }
        .fi-x3 { bottom: 20%; right: 5%; font-size: 50px; animation-duration: 20s; color: rgba(255, 255, 255, 0.04); }

        @keyframes spinFloat {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }

        .profile-hero-photo, .profile-hero-info, .profile-btn-edit { position: relative; z-index: 2; }

        .profile-hero-photo {
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .profile-hero-photo:hover { background: rgba(255, 255, 255, 0.1); border-color: var(--primary); }
        .profile-hero-photo img { width: 100%; height: 100%; object-fit: cover; }

        .loading-spinner { display: none; color: var(--primary); font-size: 2rem; animation: spin 1s infinite linear;}
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .profile-photo-placeholder { text-align: center; color: rgba(255, 255, 255, 0.8); font-size: 0.85rem; display: flex; flex-direction: column; gap: 8px; align-items: center; transition: color 0.3s ease; }
        .profile-hero-photo:hover .profile-photo-placeholder { color: var(--primary); }

        .profile-hero-info { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .profile-hero-name { font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1.1; color: #ffffff; letter-spacing: -0.02em; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .profile-hero-role { font-size: 1.1rem; color: var(--primary); font-weight: 700; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 0.05em; }

        .profile-hero-details { display: flex; flex-wrap: wrap; gap: 16px; }
        .profile-hero-detail-item { display: flex; align-items: center; gap: 6px; font-size: 0.95rem; color: #e2e8f0; background: rgba(255, 255, 255, 0.08); padding: 6px 12px; border-radius: 8px; backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.05); }

        .profile-btn-edit { position: absolute; top: 32px; right: 32px; background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: none; backdrop-filter: blur(10px); }
        .profile-btn-edit:hover { background: var(--primary); border-color: var(--primary); color: white; }

        /* DETALLES ABAJO */
        .header-section { margin: 24px 0 20px 0; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .section-title { font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        .title-icon-wrapper { background: rgba(255, 138, 31, 0.12); padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary2); }
        
        .section-desc { color: var(--muted); font-size: 0.85rem; margin: 10px 0 0 0; display: flex; align-items: flex-start; gap: 8px; text-align: left; line-height: 1.4; }
        .section-desc svg { flex-shrink: 0; margin-top: 2px; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .info-card { background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: flex-start; gap: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        .icon-box { width: 38px; height: 38px; background: rgba(255, 138, 31, 0.08); color: var(--primary2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.3s ease; }
        .info-content { display: flex; flex-direction: column; gap: 4px; overflow: hidden; padding-top: 2px; }
        .info-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin: 0; }
        .info-value { font-size: 0.9rem; font-weight: 600; color: var(--text); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge-rol { display: inline-block; background: rgba(255, 138, 31, 0.12); color: var(--primary2); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }

        /* =========================================
           AJUSTES MÓVILES
           ========================================= */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .info-grid { grid-template-columns: 1fr; }
            
            .profile-hero-card { 
                flex-direction: column; 
                align-items: flex-start; 
                text-align: left; 
                padding: 24px; 
                gap: 20px; 
            }
            .profile-hero-details { 
                justify-content: flex-start; 
            }
            .profile-btn-edit { 
                position: relative; 
                top: 0; 
                right: 0; 
                width: 100%; 
                margin-top: 10px; 
                justify-content: center; 
            }
            
            /* Ajuste de íconos para que se vean bien en móvil sin estorbar */
            .float-icon { opacity: 1; } /* Les devuelvo la opacidad base */
            .fi-x1 { top: 5%; right: 5%; font-size: 40px; }
            .fi-c1 { bottom: 25%; right: 10%; font-size: 60px; }
            .fi-x2 { top: 40%; right: 5%; font-size: 30px; }
            .fi-c2 { top: 10%; right: 20%; font-size: 50px; }
            .fi-x3 { bottom: 10%; right: 2%; font-size: 35px; }
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
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-regular fa-id-card"></i></div>
                        <div class="info-content">
                            <p class="info-label">Documento</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['cedula']); ?></p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-regular fa-envelope"></i></div>
                        <div class="info-content">
                            <p class="info-label">Correo Electrónico</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['email']); ?></p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-content">
                            <p class="info-label">Teléfono</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['telefono']); ?></p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-content">
                            <p class="info-label">Ciudad</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['ciudad']); ?></p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="icon-box"><i class="fa-solid fa-briefcase"></i></div>
                        <div class="info-content">
                            <p class="info-label">Rol Asignado</p>
                            <p class="info-value"><span class="badge-rol"><?php echo htmlspecialchars($rol_display); ?></span></p>
                        </div>
                    </div>
                </div>

                <?php if ($usuario_info['licencia_sst'] === 'si' || !empty($usuario_info['numero_licencia']) || $usuario_rol === 'sst'): ?>
                    <div class="header-section" style="margin-top: 30px;">
                        <h2 class="section-title">
                            <div class="title-icon-wrapper" style="background: rgba(74, 127, 191, 0.12); color: #4a7fbf;">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            Certificación SG-SST
                        </h2>
                    </div>

                    <div class="info-grid">
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-solid fa-id-badge"></i></div>
                            <div class="info-content"><p class="info-label">Tipo de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['tipo_licencia']); ?></p></div>
                        </div>
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-solid fa-hashtag"></i></div>
                            <div class="info-content"><p class="info-label">Número de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['numero_licencia']); ?></p></div>
                        </div>
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><i class="fa-regular fa-calendar-check"></i></div>
                            <div class="info-content"><p class="info-label">Fecha de Expedición</p><p class="info-value"><?php echo mostrarDato($usuario_info['fecha_licencia']); ?></p></div>
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