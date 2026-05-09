<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

// Para que el header.php no falle intentando buscar notificaciones del admin
$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;

$admin_id = $_SESSION['cpanel_admin_id'];

// =======================================================
// LÓGICA PARA SUBIR LA FOTO DE PERFIL DEL ADMIN CON AJAX (BLINDADA)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    header('Content-Type: application/json');
    
    // Carpeta específica para fotos de super admins
    $uploadDir = '../uploads/perfiles_admin/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['foto_perfil'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            // Nombre único: admin_id_time
            $fileName = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                try {
                    // Borrar foto anterior
                    $stmtOld = $conn->prepare("SELECT foto_perfil FROM cpanel_admins WHERE id = ?");
                    $stmtOld->execute([$admin_id]);
                    $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
                    
                    if ($oldData && !empty($oldData['foto_perfil'])) {
                        if (file_exists($oldData['foto_perfil'])) {
                            unlink($oldData['foto_perfil']); 
                        }
                    }

                    // Actualizar BD (la ruta relativa para usar desde admin/)
                    $rutaBD = '../uploads/perfiles_admin/' . $fileName;
                    $stmt = $conn->prepare("UPDATE cpanel_admins SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$rutaBD, $admin_id]);
                    
                    echo json_encode(['success' => true, 'ruta' => $rutaBD]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'error' => 'Crea la columna foto_perfil en tu BD primero']);
                    exit;
                }
            }
        }
    }
    echo json_encode(['success' => false, 'error' => 'Error al procesar la imagen']);
    exit;
}
// =======================================================

// 1. Obtener info completa del Super Admin (BLINDADO CONTRA ERROR 500)
$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';
$admin_email = 'Configura tu email';
$admin_foto = '';
$error_columnas_db = false;

try {
    // Asumimos que tu tabla se llama cpanel_admins. Si se llama distinto, cámbialo aquí.
    $stmt_admin = $conn->prepare("SELECT nombre, email, foto_perfil FROM cpanel_admins WHERE id = ?");
    $stmt_admin->execute([$admin_id]);
    
    if ($admin_info = $stmt_admin->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($admin_info['nombre'])) $admin_nombre = $admin_info['nombre'];
        $admin_email = $admin_info['email'] ?? 'Configura tu email';
        $admin_foto = $admin_info['foto_perfil'] ?? '';
    }
} catch (PDOException $e) {
    // Si la tabla no tiene 'foto_perfil' o 'email', evitamos el Error 500 y mostramos alerta visual
    $error_columnas_db = true;
}

// 2. Obtener contadores globales
$stmt = $conn->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
$conteos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total_empresas = $conteos['representante'] ?? 0;
$total_sst = $conteos['sst'] ?? 0;
$total_trabajadores = $conteos['trabajador'] ?? 0;
$total_usuarios = array_sum($conteos);

// 3. Obtener lista de empresas (Representantes Legales)
$stmt_empresas = $conn->query("SELECT id, nombre, apellido, cedula, email, ciudad, fecha_registro FROM usuarios WHERE rol = 'representante' ORDER BY id DESC LIMIT 10");
$empresas = $stmt_empresas->fetchAll(PDO::FETCH_ASSOC);

// Variables para igualar el header.php
$current_page = 'index.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Master | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            --blue-dark: #1e3a8a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, var(--bg1), var(--bg2));
            margin: 0; padding: 0; min-height: 100vh;
            color: var(--text); display: flex; font-size: 0.85rem;
            overflow-x: hidden;
        }

        .main-wrapper {
            margin-left: 260px; width: calc(100% - 260px);
            display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease;
        }

        .content-area {
            padding: 24px 32px; flex: 1; max-width: 1400px;
            margin: 0 auto; width: 100%; box-sizing: border-box;
        }

        /* Notificación Flotante para la Foto */
        .toast-noti { position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(100px); opacity: 0; transition: all 0.4s ease; z-index: 9999; display: flex; align-items: center; gap: 10px; }
        .toast-noti.show { transform: translateY(0); opacity: 1; }

        /* =========================================
           TARJETA GRANDE DE PERFIL (ESTILO DASHBOARD)
           ========================================= */
        .admin-hero-card {
            background: linear-gradient(135deg, #1e293b, #0f172a); 
            color: white; border-radius: 16px; padding: 24px;
            display: flex; gap: 24px; align-items: center; margin-bottom: 24px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08); position: relative; overflow: hidden; 
        }

        .card-blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: 1; opacity: 0.15; animation: floatCardAnim 12s infinite alternate ease-in-out; }
        .card-blob-1 { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -80px; }
        .card-blob-2 { width: 250px; height: 250px; background: #3b82f6; bottom: -100px; right: 5%; animation-delay: -6s; }

        @keyframes floatCardAnim {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 15px) scale(1.05); }
            100% { transform: translate(-15px, 30px) scale(0.95); }
        }

        .float-icon { position: absolute; z-index: 1; animation: spinFloat 20s infinite linear; pointer-events: none; opacity: 0.6; }
        .fi-x1 { top: 10%; right: 8%; font-size: 50px; animation-duration: 25s; color: rgba(255, 138, 31, 0.06); }
        .fi-c1 { bottom: 10%; right: 20%; font-size: 80px; animation-direction: reverse; color: rgba(255, 255, 255, 0.05); }
        .fi-x2 { top: 50%; right: 12%; font-size: 35px; animation-duration: 15s; color: rgba(59, 130, 246, 0.06); }
        .fi-c2 { top: 20%; right: 30%; font-size: 60px; animation-duration: 30s; animation-direction: reverse; color: rgba(255, 138, 31, 0.04); }

        @keyframes spinFloat {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }

        .admin-photo-col, .admin-info-col { position: relative; z-index: 2; }

        .admin-hero-photo {
            width: 120px; height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 2px dashed rgba(255, 255, 255, 0.2);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            cursor: pointer; transition: all 0.3s ease; flex-shrink: 0; overflow: hidden; backdrop-filter: blur(5px);
        }
        .admin-hero-photo:hover { background: rgba(255, 255, 255, 0.1); border-color: var(--primary); }
        .admin-hero-photo img { width: 100%; height: 100%; object-fit: cover; }
        
        .loading-spinner { display: none; color: var(--primary); font-size: 1.5rem; animation: spin 1s infinite linear;}
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .photo-placeholder { text-align: center; color: rgba(255, 255, 255, 0.6); font-size: 0.8rem; display: flex; flex-direction: column; gap: 6px; align-items: center; }
        .admin-hero-photo:hover .photo-placeholder { color: var(--primary); }

        .admin-info-col { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .admin-hero-label { font-size: 0.7rem; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0; }
        .admin-hero-name { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1.1; color: #ffffff; letter-spacing: -0.01em; }
        .admin-hero-email { font-size: 0.9rem; color: #94a3b8; margin: 0; }

        /* =========================================
           TARJETAS RESUMEN 
           ========================================= */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 16px; position: relative; overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        /* SOLUCIÓN AL ÍCONO DE MARCA DE AGUA (FUENTE EN VEZ DE CAJA) */
        .summary-bg-icon { 
            position: absolute; 
            right: -10px; 
            bottom: -20px; 
            font-size: 100px; /* Aquí controlamos el tamaño del icono de FontAwesome */
            line-height: 1;
            opacity: 0.06; 
            transform: rotate(-15deg); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            pointer-events: none; 
            z-index: 0;
        }
        .summary-card:hover .summary-bg-icon { 
            transform: rotate(0deg) scale(1.1); 
            opacity: 0.12; 
        }
        
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        
        .summary-icon-box { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        
        .summary-value { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; }
        .summary-title { font-size: 0.9rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 2px 0; }
        .summary-desc { font-size: 0.75rem; color: var(--muted); margin: 0; }

        /* COLORES POR TARJETA */
        .card-blue .summary-bg-icon { color: #3b82f6; }
        .card-blue .summary-icon-box { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .card-blue .summary-value { color: #3b82f6; }

        .card-orange .summary-bg-icon { color: var(--primary); }
        .card-orange .summary-icon-box { background: rgba(255, 138, 31, 0.08); color: var(--primary); }
        .card-orange .summary-value { color: var(--primary); }

        .card-green .summary-bg-icon { color: #10b981; }
        .card-green .summary-icon-box { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .card-green .summary-value { color: #10b981; }

        .card-purple .summary-bg-icon { color: #8b5cf6; }
        .card-purple .summary-icon-box { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
        .card-purple .summary-value { color: #8b5cf6; }

        /* GRÁFICAS */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .chart-container {
            background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); display: flex; flex-direction: column;
        }
        .chart-header-title { font-size: 0.9rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 16px 0; display: flex; align-items: center; gap: 6px; }
        .chart-wrapper { position: relative; width: 100%; height: 240px; }

        /* TABLA */
        .table-section { margin-top: 30px; }
        .section-title {
            font-size: 0.95rem; font-weight: 800; color: var(--blue-dark); margin: 0 0 12px 0; 
            display: flex; align-items: center; gap: 8px;
        }
        
        .table-wrapper { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); 
        }
        
        .modern-table { width: 100%; border-collapse: collapse; text-align: left; }
        .modern-table th { 
            background: #f8fafc; padding: 12px 16px; font-size: 0.7rem; 
            text-transform: uppercase; color: #64748b; font-weight: 700; 
            border-bottom: 1px solid #e2e8f0; letter-spacing: 0.05em; 
        }
        .modern-table td { 
            padding: 12px 16px; border-bottom: 1px solid #f1f5f9; 
            font-size: 0.8rem; color: #334155; 
        }
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tr:hover td { background: #f8fafc; }
        
        .badge-status { 
            background: #dcfce7; color: #166534; padding: 4px 8px; 
            border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-block; 
        }
        
        .client-avatar { 
            width: 28px; height: 28px; border-radius: 6px; 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.05)); 
            color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.15);
            display: inline-flex; align-items: center; justify-content: center; 
            font-weight: 800; font-size: 0.8rem; margin-right: 10px; vertical-align: middle; 
        }

        .client-name-group { display: flex; align-items: center; font-weight: 600; color: #1e293b; }

        @media (max-width: 1024px) { .charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px; }
            .admin-hero-card { flex-direction: column; align-items: flex-start; text-align: left; padding: 20px; gap: 16px; }
            .modern-table th, .modern-table td { padding: 10px 12px; }
            .table-wrapper { overflow-x: auto; }
            .modern-table { min-width: 700px; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">

        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <?php if ($error_columnas_db): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 16px; border-radius: 12px; color: #d97706; margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">Atención desarrollador:</strong>
                        Para que funcione la foto y el email del Super Admin, ve a phpMyAdmin y corre este código SQL:<br>
                        <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; color: #b45309; display: inline-block; margin-top: 4px;">
                            ALTER TABLE cpanel_admins ADD COLUMN email VARCHAR(100) NULL AFTER nombre, ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER email;
                        </code>
                    </div>
                </div>
            <?php endif; ?>

            <div class="admin-hero-card">
                <div class="card-blob card-blob-1"></div>
                <div class="card-blob card-blob-2"></div>
                
                <i class="fa-solid fa-plus float-icon fi-x1"></i>
                <i class="fa-regular fa-circle float-icon fi-c1"></i>
                <i class="fa-solid fa-plus float-icon fi-x2"></i>
                <i class="fa-regular fa-circle float-icon fi-c2"></i>

                <div class="admin-photo-col">
                    <div class="admin-hero-photo" id="photoContainer" title="Click para cambiar foto">
                        <i class="fa-solid fa-spinner loading-spinner" id="photoSpinner"></i>
                        
                        <div id="photoContent" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($admin_foto) && file_exists($admin_foto)): ?>
                                <img src="<?php echo htmlspecialchars($admin_foto); ?>?v=<?php echo time(); ?>" alt="Foto Admin" id="profileImagePreview">
                            <?php else: ?>
                                <div class="photo-placeholder">
                                    <i class="fa-solid fa-camera" style="font-size: 1.5rem;"></i>
                                    <span>Subir</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="foto-upload" style="display: none;" accept="image/png, image/jpeg, image/jpg, image/webp">
                    </div>
                </div>

                <div class="admin-info-col">
                    <p class="admin-hero-label">Super Administrador</p>
                    <h1 class="admin-hero-name"><?php echo htmlspecialchars($admin_nombre); ?></h1>
                    <p class="admin-hero-email"><?php echo htmlspecialchars($admin_email); ?></p>
                </div>
            </div>

            <div class="summary-cards-grid">
                
                <div class="summary-card card-blue">
                    <i class="fa-solid fa-users summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_usuarios; ?></h2>
                        </div>
                        <h3 class="summary-title">Total Usuarios</h3>
                        <p class="summary-desc">Registrados en el sistema.</p>
                    </div>
                </div>

                <div class="summary-card card-orange">
                    <i class="fa-solid fa-building summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_empresas; ?></h2>
                        </div>
                        <h3 class="summary-title">Empresas Activas</h3>
                        <p class="summary-desc">Clientes operando (Representantes).</p>
                    </div>
                </div>

                <div class="summary-card card-green">
                    <i class="fa-solid fa-user-shield summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_sst; ?></h2>
                        </div>
                        <h3 class="summary-title">Responsables SST</h3>
                        <p class="summary-desc">Administradores de seguridad.</p>
                    </div>
                </div>

                <div class="summary-card card-purple">
                    <i class="fa-solid fa-helmet-safety summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-helmet-safety"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_trabajadores; ?></h2>
                        </div>
                        <h3 class="summary-title">Trabajadores</h3>
                        <p class="summary-desc">Personal operativo registrado.</p>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-header-title">
                        <i class="fa-solid fa-chart-pie" style="color: #94a3b8;"></i> 
                        Distribución de Roles
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="rolesChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-header-title">
                        <i class="fa-solid fa-chart-column" style="color: #94a3b8;"></i> 
                        Volumen de Plataforma
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="table-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-address-book" style="color: var(--primary);"></i>
                    Últimas Empresas Registradas (Clientes)
                </h2>
                
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa / Representante</th>
                                <th>NIT / Cédula</th>
                                <th>Email de Contacto</th>
                                <th>Ciudad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($empresas)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8; font-style: italic;">Sin registros aún.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($empresas as $emp): ?>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 700;">#<?php echo str_pad($emp['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div class="client-name-group">
                                                <div class="client-avatar">
                                                    <?php echo strtoupper(substr($emp['nombre'], 0, 1)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>
                                            </div>
                                        </td>
                                        <td style="font-family: monospace; color: #475569;"><?php echo htmlspecialchars($emp['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['ciudad'] ?: '-'); ?></td>
                                        <td><span class="badge-status"><i class="fa-solid fa-check" style="margin-right: 4px;"></i>Activa</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="toastNoti" class="toast-noti">
        <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i>
        <span id="toastMsg">Foto actualizada</span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica AJAX para subir foto de Admin
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

                    if(!file.type.match('image.*')){
                        toastMsg.innerText = "Solo se permiten imágenes";
                        toastNoti.style.background = "#7f1d1d";
                        toastNoti.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="color: #fca5a5;"></i> Solo imágenes`;
                        showToast();
                        return;
                    }

                    photoContent.style.display = 'none';
                    photoSpinner.style.display = 'block';

                    const formData = new FormData();
                    formData.append('foto_perfil', file);

                    fetch('index.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        photoSpinner.style.display = 'none';
                        photoContent.style.display = 'flex';

                        if(data.success) {
                            photoContent.innerHTML = `<img src="${data.ruta}?v=${new Date().getTime()}" alt="Foto de perfil">`;
                            toastNoti.style.background = "#1e293b";
                            toastNoti.innerHTML = `<i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> Foto actualizada con éxito`;
                            showToast();
                        } else {
                            toastNoti.style.background = "#7f1d1d";
                            toastNoti.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="color: #fca5a5;"></i> ${data.error}`;
                            showToast();
                        }
                    })
                    .catch(error => {
                        photoSpinner.style.display = 'none';
                        photoContent.style.display = 'flex';
                        toastNoti.style.background = "#7f1d1d";
                        toastNoti.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color: #fca5a5;"></i> Error de conexión`;
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

            // --- CONFIGURACIÓN DE GRÁFICOS (COMPACTOS) ---
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#64748b';
            const commonTooltip = {
                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                titleFont: { family: 'Inter', size: 13, weight: '700' },
                bodyFont: { family: 'Inter', size: 12 },
                padding: 10, cornerRadius: 8, boxPadding: 6
            };

            const dataRoles = [ <?php echo $total_empresas; ?>, <?php echo $total_sst; ?>, <?php echo $total_trabajadores; ?> ];

            new Chart(document.getElementById('rolesChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Empresas', 'Resp. SST', 'Trabajadores'],
                    datasets: [{
                        data: dataRoles,
                        backgroundColor: ['#ff8a1f', '#10b981', '#8b5cf6'],
                        borderWidth: 0, hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { bottom: 10 } },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11, weight: '500' } } },
                        tooltip: commonTooltip
                    },
                    cutout: '72%' 
                }
            });

            new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Empresas', 'Resp. SST', 'Trabajadores'],
                    datasets: [{
                        data: dataRoles,
                        backgroundColor: ['rgba(255,138,31,0.8)', 'rgba(16,185,129,0.8)', 'rgba(139,92,246,0.8)'],
                        borderRadius: 6, maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { top: 10 } },
                    plugins: { legend: { display: false }, tooltip: commonTooltip },
                    scales: {
                        y: {
                            beginAtZero: true, border: { display: false },
                            ticks: { font: { size: 11 }, padding: 8, color: '#94a3b8' },
                            grid: { color: '#f1f5f9', drawTicks: false }
                        },
                        x: {
                            border: { display: false },
                            ticks: { font: { size: 11, weight: '600' }, padding: 8, color: '#64748b' },
                            grid: { display: false, drawTicks: false }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>