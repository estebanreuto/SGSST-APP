<?php
$usuario_nombre = $usuario_nombre ?? 'Usuario';
$rol_display = $rol_display ?? 'Rol no definido';
$usuario_rol = $usuario_rol ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --primary: #ff8a1f;
        --primary2: #ff7a00;
        --card: #ffffff;
        --text: #1f2d3d;
        --muted: #5f6f82;
        --border: #dbe3ec;
    }

    /* Fondo oscuro para móvil al abrir menú */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.5);
        z-index: 1040;
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    .sidebar {
        width: 260px;
        background: var(--card);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        font-family: 'Inter', sans-serif;
        z-index: 1050;
        transition: transform 0.3s ease;
    }

    .sidebar-header {
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: var(--text);
        font-size: 0.95rem;
        letter-spacing: -0.01em;
    }

    .brand svg {
        color: var(--primary);
    }

    /* Botón cerrar menú móvil */
    .btn-close-sidebar {
        display: none;
        background: none;
        border: none;
        color: var(--muted);
        cursor: pointer;
        padding: 4px;
    }

    .sidebar-nav {
        padding: 0 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
        overflow-y: auto;
    }

    .nav-section {
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.05em;
        margin: 16px 0 8px 12px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        color: var(--muted);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .nav-item:hover {
        background: var(--bg1);
        color: var(--text);
    }

    .nav-item.active {
        background: rgba(255, 138, 31, 0.1);
        color: var(--primary2);
        font-weight: 600;
    }

    .nav-item svg {
        opacity: 0.7;
    }

    .nav-item.active svg {
        opacity: 1;
        color: var(--primary);
    }

    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .user-mini {
        display: flex;
        align-items: center;
        gap: 10px;
        overflow: hidden;
    }

    .avatar-mini {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-details .name {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text);
    }

    .user-details .role {
        font-size: 0.7rem;
        color: var(--muted);
    }

    .logout-item {
        color: #ef4444;
        padding: 8px;
        margin: 0;
    }

    .logout-item:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    /* Ajustes Responsive para el Sidebar */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            /* Oculto por defecto */
        }

        .sidebar.active {
            transform: translateX(0);
            /* Visible al activarse */
        }

        .btn-close-sidebar {
            display: block;
            /* Muestra el botón de la X */
        }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            SG-SST Pro
        </div>
        <button class="btn-close-sidebar" id="btnCloseSidebar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Dashboard
        </a>

        <?php if ($usuario_rol === 'representante' || $usuario_rol === 'sst'): ?>
            <div class="nav-section">Administración</div>
            <a href="trabajadores.php" class="nav-item <?php echo $current_page == 'trabajadores.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Personal
            </a>
            <a href="reportes.php" class="nav-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Generar Reportes
            </a>
        <?php endif; ?>

        <?php if ($usuario_rol === 'trabajador'): ?>
            <div class="nav-section">Mis Tareas</div>
            <a href="mis_encuestas.php" class="nav-item <?php echo $current_page == 'mis_encuestas.php' ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Mis Encuestas
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-mini">
                <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
            </div>
            <div class="user-details">
                <span class="name"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="role"><?php echo htmlspecialchars($rol_display); ?></span>
            </div>
        </div>
        <a href="#" onclick="showConfirmModal('Cerrar Sesión', '¿Estás seguro de que deseas salir de tu cuenta?', 'logout.php', 'danger', 'Sí, cerrar sesión'); return false;" class="nav-item logout-item" title="Cerrar Sesión">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </a>
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
            if (mainSidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden'; // Evita scroll atrás
            } else {
                document.body.style.overflow = '';
            }
        }

        if (btnOpenSidebar) btnOpenSidebar.addEventListener('click', toggleMenu);
        if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', toggleMenu);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMenu);
    });
</script>