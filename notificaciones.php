<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// 1. Exige sesión válida
$u = require_auth($conn);

// 2. EXTRAER VARIABLES DE SESIÓN (ESTO ARREGLA EL ROL Y NOMBRE EN EL SIDEBAR Y HEADER)
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// 3. Formatear el nombre del rol para que se vea bonito
$roles_nombres = [
    'representante' => 'Representante Legal',
    'sst' => 'Responsable SG-SST',
    'trabajador' => 'Trabajador'
];
$rol_display = $roles_nombres[$usuario_rol] ?? 'Usuario';

// 4. Declarar la página actual para que el Sidebar sepa dónde estamos
$current_page = 'notificaciones.php';

// ==========================================
// LÓGICA DE NOTIFICACIONES
// ==========================================

// Marcar todas como leídas si se solicita
if (isset($_GET['marcar_leidas'])) {
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    header("Location: notificaciones.php");
    exit;
}

// Marcar una específica como leída
if (isset($_GET['leer_id'])) {
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['leer_id'], $usuario_id]);
    $redir = $_GET['enlace'] ?? 'notificaciones.php';
    header("Location: " . $redir);
    exit;
}

// Obtener las notificaciones del usuario
$stmt = $conn->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT 50");
$stmt->execute([$usuario_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones | SG-SST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #ff8a1f; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .welcome-title { margin: 0; font-size: 1.25rem; }
        .btn-mark-read { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.2s; }
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
        .empty-state { text-align: center; padding: 40px; color: var(--muted); }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
        }
    </style>
</head>
<body>
    
    <?php include 'components/sidebar.php'; ?>
    
    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>
        
        <div class="content-area">
            <div class="header-actions">
                <h1 class="welcome-title">Notificaciones</h1>
                <a href="?marcar_leidas=1" class="btn-mark-read">Marcar todas como leídas</a>
            </div>

            <div class="notif-list">
                <?php if (empty($notificaciones)): ?>
                    <div class="empty-state">No tienes notificaciones en este momento.</div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $n): ?>
                        <a href="?leer_id=<?php echo $n['id']; ?>&enlace=<?php echo urlencode($n['enlace']); ?>" class="notif-card <?php echo $n['leida'] == 0 ? 'unread' : ''; ?>">
                            <div class="notif-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            </div>
                            <div class="notif-content">
                                <p class="notif-title"><?php echo htmlspecialchars($n['titulo']); ?></p>
                                <p class="notif-msg"><?php echo htmlspecialchars($n['mensaje']); ?></p>
                                <span class="notif-date"><?php echo date('d M Y, h:i A', strtotime($n['fecha_creacion'])); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>