<?php
// Validar si quien inició sesión es el Super Admin
$es_super_admin = isset($_SESSION['cpanel_admin_id']);

// SOLUCIÓN DE ROLES E INFO DE USUARIO
if ($es_super_admin) {
    $usuario_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Super Admin';
    $rol_display = 'Super Administrador';
    $usuario_rol = 'super_admin';
} else {
    $usuario_nombre = $_SESSION['usuario_nombre'] ?? $_SESSION['nombre'] ?? 'Usuario';
    $usuario_rol = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? '';
    
    if ($usuario_rol === 'representante') {
        $rol_display = 'Representante Legal';
    } elseif ($usuario_rol === 'sst') {
        $rol_display = 'Responsable SST';
    } elseif ($usuario_rol === 'trabajador') {
        $rol_display = 'Trabajador';
    } else {
        $rol_display = 'Usuario';
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
$unread_count = 0;
$nivel_plan = 0; // 1 = Básico, 2 = Pro, 3 = Enterprise

// LÓGICA INTELIGENTE DE NOTIFICACIONES SEGÚN EL ROL
if ($es_super_admin) {
    // Si es Super Admin, contamos las solicitudes pendientes
    $stmt_notif = $conn->query("SELECT COUNT(*) FROM solicitudes_empresas WHERE estado = 'pendiente'");
    $unread_count = $stmt_notif->fetchColumn();
} elseif (isset($_SESSION['usuario_id']) && isset($conn)) {
    // Si es un usuario normal, contamos sus notificaciones no leídas
    $stmt_notif = $conn->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt_notif->execute([$_SESSION['usuario_id']]);
    $unread_count = $stmt_notif->fetchColumn();

    // Consultar el plan
    $stmt_plan = $conn->prepare("
        SELECT p.nombre 
        FROM usuarios u
        JOIN solicitudes_empresas se ON u.empresa_id = se.id
        LEFT JOIN planes p ON se.plan_id = p.id
        WHERE u.id = ?
    ");
    $stmt_plan->execute([$_SESSION['usuario_id']]);
    $plan_data = $stmt_plan->fetch(PDO::FETCH_ASSOC);

    if ($plan_data && !empty($plan_data['nombre'])) {
        $nombre_plan = strtolower($plan_data['nombre']);
        if (strpos($nombre_plan, 'enterprise') !== false) {
            $nivel_plan = 3;
        } elseif (strpos($nombre_plan, 'pro') !== false) {
            $nivel_plan = 2;
        } else {
            $nivel_plan = 1; 
        }
    } else {
        $nivel_plan = 0; 
    }
}
?>
<style>
    :root {
        --primary: #ff8a1f; --primary2: #ff7a00; --card: #ffffff;
        --text: #1f2d3d; --muted: #5f6f82; --border: #e2e8f0;
    }

    .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1040; backdrop-filter: blur(3px); opacity: 0; transition: opacity 0.3s ease; }
    .sidebar-overlay.active { display: block; opacity: 1; }

    .sidebar { width: 260px; background: #ffffff; border-right: 1px solid var(--border); display: flex; flex-direction: column; height: 100vh; position: fixed; left: 0; top: 0; font-family: 'Inter', sans-serif; z-index: 1050; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 4px 0 15px rgba(0, 0, 0, 0.02); }
    
    .sidebar-header { height: 55px; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
    .brand { display: flex; align-items: center; font-weight: 800; color: var(--blue-dark, #1e3a8a); font-size: 1.15rem; letter-spacing: -0.02em; }
    .btn-close-sidebar { display: none; background: none; border: none; color: var(--muted); cursor: pointer; padding: 4px; transition: color 0.2s; }
    .btn-close-sidebar:hover { color: #ef4444; }

    .sidebar-nav { padding: 12px 16px; flex: 1; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
    .sidebar-nav::-webkit-scrollbar { width: 4px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .nav-section { font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 0.05em; margin: 12px 0 6px 12px; }
    
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: var(--text); text-decoration: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; position: relative; }
    .nav-item:hover { background: #f8fafc; transform: translateX(3px); color: var(--blue-dark, #1e3a8a); }
    .nav-item.active { background: linear-gradient(135deg, rgba(255, 138, 31, 0.12), rgba(255, 122, 0, 0.05)); color: var(--primary2); font-weight: 700; }
    .nav-item.active::before { content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 3px; background: var(--primary); border-radius: 0 4px 4px 0; }
    .nav-item svg { opacity: 0.6; transition: opacity 0.2s, color 0.2s; }
    .nav-item:hover svg, .nav-item.active svg { opacity: 1; color: var(--primary); }

    .nav-item-locked { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; cursor: not-allowed; background: #f8fafc; opacity: 0.7; }
    .nav-item-locked .lock-left { display: flex; align-items: center; gap: 12px; }
    .nav-item-locked svg { opacity: 0.5; }

    /* ESTILO DEL PUNTICO DE NOTIFICACIÓN */
    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .dot-indicator {
        width: 8px; height: 8px; background: #ef4444; border-radius: 50%;
        margin-left: auto; animation: pulse-dot 2s infinite;
    }

    .sidebar-footer { padding: 16px; border-top: 1px solid rgba(0, 0, 0, 0.04); background: #ffffff; }
    .user-box { display: flex; flex-direction: column; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; transition: box-shadow 0.2s; gap: 12px; }
    .user-box:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04); }
    .user-mini { display: flex; align-items: center; gap: 10px; overflow: hidden; }
    .avatar-mini { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0; box-shadow: 0 2px 6px rgba(255, 138, 31, 0.3); }
    .avatar-admin { background: linear-gradient(135deg, #1e293b, #0f172a); box-shadow: 0 2px 6px rgba(30, 41, 59, 0.3); }
    .user-details { display: flex; flex-direction: column; }
    .user-details .name { font-size: 0.8rem; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
    .user-details .role { font-size: 0.7rem; color: var(--muted); font-weight: 500; }
    .user-box-divider { height: 1px; background: #e2e8f0; width: 100%; }
    .user-actions { display: flex; flex-direction: column; gap: 6px; }
    .action-btn { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text); text-decoration: none; transition: all 0.2s ease; }
    .action-btn svg { color: var(--muted); transition: color 0.2s ease; }
    .action-btn:hover { background: #e2e8f0; color: var(--primary); }
    .action-btn:hover svg { color: var(--primary); }
    .action-btn.exit-btn { color: #ef4444; }
    .action-btn.exit-btn svg { color: #ef4444; }
    .action-btn.exit-btn:hover { background: #fee2e2; color: #dc2626; }
    .action-btn.exit-btn:hover svg { color: #dc2626; }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
        .btn-close-sidebar { display: block; }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand">
            SG-SST <span style="color: var(--primary); margin-left: 4px;">Pro</span>
        </div>
        <button class="btn-close-sidebar" id="btnCloseSidebar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">

        <?php if ($es_super_admin): ?>
            <div class="nav-section">Super Administrador</div>
            <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg> Dashboard
            </a>
            <a href="solicitudes.php" class="nav-item <?php echo $current_page == 'solicitudes.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg> Solicitudes
            </a>
            <a href="accesos.php" class="nav-item <?php echo $current_page == 'accesos.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg> Accesos
            </a>
            <a href="planes.php" class="nav-item <?php echo $current_page == 'planes.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg> Planes
            </a>

        <?php else: ?>
            <div class="nav-section">Principal</div>
            <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg> Dashboard
            </a>

            <?php if ($usuario_rol === 'representante' || $usuario_rol === 'sst'): ?>
                <div class="nav-section">Administración</div>
                <a href="trabajadores.php" class="nav-item <?php echo $current_page == 'trabajadores.php' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg> Personal
                </a>
                <?php if ($nivel_plan >= 2): ?>
                    <a href="reportes.php" class="nav-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Generar Reportes
                    </a>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="alert('Requiere Plan Pro o Enterprise.')" class="nav-item-locked">
                        <div class="lock-left"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Generar Reportes</div>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($usuario_rol === 'trabajador'): ?>
                <div class="nav-section">Mis Tareas</div>
                <a href="mis_encuestas.php" class="nav-item <?php echo $current_page == 'mis_encuestas.php' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Mis Encuestas
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: auto;"></div>

        <div class="nav-section">Cuenta</div>
        <a href="notificaciones.php" class="nav-item <?php echo ($current_page == 'notificaciones.php') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Notificaciones
            <?php if ($unread_count > 0): ?>
                <span class="dot-indicator"></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-mini">
                <div class="avatar-mini <?php echo $es_super_admin ? 'avatar-admin' : ''; ?>">
                    <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="name"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                    <span class="role"><?php echo htmlspecialchars($rol_display); ?></span>
                </div>
            </div>
            <div class="user-box-divider"></div>
            <div class="user-actions">
                <a href="<?php echo $es_super_admin ? '#' : 'perfil.php'; ?>" class="action-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Configuración
                </a>
                
                <a href="#" onclick="showConfirmModal('Cerrar Sesión', '¿Estás seguro de que deseas salir de tu cuenta?', 'logout.php', 'danger', 'Sí, cerrar sesión'); return false;" class="action-btn exit-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnOpenSidebar = document.getElementById('btnOpenSidebar');
        const btnCloseSidebar = document.getElementById('btnCloseSidebar');
        const mainSidebar = document.getElementById('mainSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            mainSidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = mainSidebar.classList.contains('active') ? 'hidden' : '';
        }
        if (btnOpenSidebar) btnOpenSidebar.addEventListener('click', toggleMenu);
        if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', toggleMenu);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMenu);
    });
</script>

<?php include_once __DIR__ . '/modal_confirmacion.php'; ?>