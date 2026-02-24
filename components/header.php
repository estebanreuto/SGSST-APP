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
        /* EFECTO FLOTANTE CON BORDES REDONDOS (Floating Pill) */
        background: rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.8); /* Borde blanco sutil en todos lados */
        border-radius: 20px; /* Bordes bien redondeados */
        height: 55px; 
        padding: 0 24px; 
        margin: 16px 30px; /* Margen para despegarlo del techo y del sidebar */
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 16px; /* Al hacer scroll, respeta 16px de espacio arriba */
        z-index: 100;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02); /* Sombra 3D suave */
    }

    .header-left-group {
        display: flex;
        align-items: center;
        gap: 12px; 
    }

    .btn-mobile-menu {
        display: none;
        background: none;
        border: none;
        color: var(--text);
        cursor: pointer;
        padding: 4px; 
        border-radius: 6px;
        transition: background 0.2s;
    }

    .btn-mobile-menu:hover {
        background: rgba(0, 0, 0, 0.05);
    }

    .header-title {
        margin: 0;
        color: var(--blue-dark, #1e3a8a);
        font-size: 1.05rem; 
        font-weight: 800; 
        letter-spacing: -0.01em;
    }

    .top-header-actions {
        display: flex;
        gap: 12px; 
        align-items: center;
        flex-direction: row !important;
    }

    .role-badge {
        background: rgba(255, 138, 31, 0.12);
        color: var(--primary2);
        padding: 4px 10px; 
        border-radius: 12px;
        font-size: 0.7rem; 
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        border: 1px solid rgba(255, 138, 31, 0.2);
    }

    .icon-btn {
        background: rgba(255, 255, 255, 0.7); 
        border: 1px solid rgba(0,0,0,0.05);
        width: 34px; 
        height: 34px; 
        border-radius: 8px; 
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }

    .icon-btn:hover {
        background: #ffffff;
        color: var(--primary);
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .badge-notif {
        position: absolute; 
        top: -4px; 
        right: -4px; 
        background: #ef4444; 
        color: white; 
        border-radius: 50%; 
        font-size: 0.6rem; 
        font-weight: bold; 
        width: 16px; 
        height: 16px; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        border: 2px solid #ffffff;
        line-height: 1;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4);
    }

    @media (max-width: 768px) {
        .top-header {
            margin: 12px 16px; /* Más pegado en celulares para ahorrar espacio */
            top: 12px;
            padding: 0 16px;
        }
        .btn-mobile-menu {
            display: flex;
        }
        .role-badge {
            display: none;
        }
        .header-title {
            font-size: 1rem;
        }
    }
</style>

<header class="top-header">
    <div class="header-left-group">
        <button class="btn-mobile-menu" id="btnOpenSidebar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
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