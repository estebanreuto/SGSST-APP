<?php
// Lógica para que el título del Header cambie según la página
$titulo_header = "Panel de Control";
if ($current_page == 'trabajadores.php') $titulo_header = "Gestión de Personal";
if ($current_page == 'reportes.php') $titulo_header = "Reportes y Estadísticas";
if ($current_page == 'empresa.php') $titulo_header = "Datos de la Empresa";
if ($current_page == 'evaluaciones.php') $titulo_header = "Evaluaciones SG-SST";
if ($current_page == 'mis_encuestas.php') $titulo_header = "Mis Encuestas";
if ($current_page == 'notificaciones.php') $titulo_header = "Centro de Notificaciones";
if ($current_page == 'perfil.php') $titulo_header = "Configuración del Perfil";
?>
<?php
// Consulta para contar notificaciones no leídas
$unread_count = 0;
if (isset($_SESSION['usuario_id'])) {
    $stmt_notif = $conn->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt_notif->execute([$_SESSION['usuario_id']]);
    $unread_count = $stmt_notif->fetchColumn();
}
?>
<style>
    .top-header {
        background: var(--bg1);
        border-bottom: none;
        padding: 24px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .header-left-group {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .btn-mobile-menu {
        display: none;
        /* Oculto en PC */
        background: none;
        border: none;
        color: var(--text);
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
    }

    .btn-mobile-menu:hover {
        background: rgba(0, 0, 0, 0.05);
    }

    .header-title {
        margin: 0;
        color: var(--text);
        font-size: 1.1rem;
        font-weight: 600;
        letter-spacing: -0.01em;
    }

    .top-header-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-direction: row !important;
        /* Forza a que estén uno al lado del otro */
    }

    .role-badge {
        background: rgba(255, 138, 31, 0.12);
        color: var(--primary2);
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    /* ESTILO UNIFICADO PARA LOS BOTONES DEL HEADER */
    .icon-btn {
        background: var(--card);
        border: 1px solid var(--border);
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none; /* Asegura que el enlace no tenga subrayado */
        position: relative; /* Clave para que el globito rojo se posicione bien */
    }

    .icon-btn:hover {
        color: var(--primary);
        border-color: var(--primary);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(255, 138, 31, 0.1);
    }

    /* Globito de notificación rojo perfectamente alineado */
    .badge-notif {
        position: absolute; 
        top: -6px; 
        right: -6px; 
        background: #ef4444; 
        color: white; 
        border-radius: 50%; 
        font-size: 0.65rem; 
        font-weight: bold; 
        width: 18px; 
        height: 18px; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        border: 2px solid var(--bg1); /* Borde del color del fondo para que resalte */
        line-height: 1;
    }

    /* Ajustes Responsive para el Header */
    @media (max-width: 768px) {
        .top-header {
            padding: 16px 20px;
        }

        .btn-mobile-menu {
            display: flex;
            /* Visible en celular */
        }

        .role-badge {
            display: none;
            /* Ocultamos el rol en el header para ahorrar espacio en cel */
        }
    }
</style>

<header class="top-header">
    <div class="header-left-group">
        <button class="btn-mobile-menu" id="btnOpenSidebar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h2 class="header-title"><?php echo $titulo_header; ?></h2>
    </div>

    <div class="top-header-actions">
        <div class="role-badge"><?php echo htmlspecialchars($rol_display ?? 'Usuario'); ?></div>

        <a href="notificaciones.php" class="icon-btn" title="Notificaciones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <?php if ($unread_count > 0): ?>
                <span class="badge-notif">
                    <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="perfil.php" class="icon-btn" title="Mi Perfil">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </a>
    </div>
</header>