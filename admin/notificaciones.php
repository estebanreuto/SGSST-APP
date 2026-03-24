<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;
$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';

$current_page = 'notificaciones.php';
$titulo_header = "Centro de Notificaciones";
$rol_display = "Super Administrador";

// ==========================================
// FUNCIÓN PARA TIEMPO RELATIVO
// ==========================================
function tiempo_hace($fecha) {
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;

    if ($diferencia < 60) return "Hace un momento";
    $minutos = round($diferencia / 60);
    if ($minutos < 60) return "Hace $minutos minuto" . ($minutos > 1 ? 's' : '');
    $horas = round($diferencia / 3600);
    if ($horas < 24) return "Hace $horas hora" . ($horas > 1 ? 's' : '');
    $dias = round($diferencia / 86400);
    if ($dias < 7) return "Hace $dias día" . ($dias > 1 ? 's' : '');
    return date("d M Y", $timestamp);
}

/* ================================================================
// CONSULTA: TRAER SOLICITUDES PENDIENTES COMO NOTIFICACIONES
================================================================ */
$stmt = $conn->query("
    SELECT id, nombre, apellido, email, fecha_creacion 
    FROM solicitudes_empresas 
    WHERE estado = 'pendiente' 
    ORDER BY fecha_creacion DESC
");
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_notificaciones = count($notificaciones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #ff8a1f; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; }
        
        /* AQUI SE ARREGLÓ EL ANCHO (Pasó de 1000px a 1400px) */
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .welcome-title { margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; font-weight: 800; color: var(--blue-dark, #1e3a8a); }
        .badge-count { background: #ef4444; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }
        
        .btn-mark-read { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-mark-read:hover { background: #e2e8f0; color: #0f172a; }

        .notif-list { display: flex; flex-direction: column; gap: 12px; }
        .notif-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; display: flex; gap: 16px; transition: transform 0.2s; text-decoration: none; color: inherit; }
        .notif-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .notif-card.unread { background: #fff8f3; border-left: 4px solid var(--primary); }
        
        .notif-icon { width: 40px; height: 40px; background: rgba(255, 138, 31, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notif-content { flex: 1; }
        .notif-title { margin: 0 0 4px 0; font-weight: 700; font-size: 0.95rem; color: #0f172a; }
        .notif-msg { margin: 0 0 8px 0; color: #475569; line-height: 1.5; }
        .notif-date { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        
        .empty-state { text-align: center; padding: 40px; color: var(--muted); background: var(--card); border: 1px dashed var(--border); border-radius: 12px; }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px;}
        }
    </style>
</head>
<body>
    
    <?php include '../components/sidebar.php'; ?>
    
    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>
        
        <div class="content-area">
            <div class="header-actions">
                <h1 class="welcome-title">
                    Notificaciones
                    <?php if ($total_notificaciones > 0): ?>
                        <span class="badge-count"><?php echo $total_notificaciones; ?> nuevas</span>
                    <?php endif; ?>
                </h1>
                <a href="solicitudes.php" class="btn-mark-read">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Revisar Panel de Solicitudes
                </a>
            </div>

            <div class="notif-list">
                <?php if (empty($notificaciones)): ?>
                    <div class="empty-state">No tienes solicitudes de registro pendientes en este momento.</div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $n): ?>
                        <a href="solicitudes.php" class="notif-card unread">
                            <div class="notif-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div class="notif-content">
                                <p class="notif-title">Nueva Solicitud: <?php echo htmlspecialchars($n['nombre']); ?></p>
                                <p class="notif-msg">La empresa completó su registro y está esperando aprobación. Email: <?php echo htmlspecialchars($n['email']); ?></p>
                                <span class="notif-date"><?php echo tiempo_hace($n['fecha_creacion']); ?> • <?php echo date('d M Y, h:i A', strtotime($n['fecha_creacion'])); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include_once '../components/modal_confirmacion.php'; ?>
</body>
</html>