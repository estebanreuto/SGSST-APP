<?php
// Asegurarnos de tener la página actual para el título
$current_page = basename($_SERVER['PHP_SELF']);

// Lógica para que el título del Header cambie según la página
$titulo_header = "Panel de Control";
if ($current_page == 'trabajadores.php') $titulo_header = "Gestión de Personal";
if ($current_page == 'reportes.php') $titulo_header = "Reportes y Estadísticas";
if ($current_page == 'empresa.php') $titulo_header = "Datos de la Empresa";
if ($current_page == 'evaluaciones.php') $titulo_header = "Evaluaciones SG-SST";
if ($current_page == 'mis_encuestas.php') $titulo_header = "Mis Encuestas";
if ($current_page == 'notificaciones.php') $titulo_header = "Centro de Notificaciones";
if ($current_page == 'configuracion.php') $titulo_header = "Configuración de Cuenta";
if ($current_page == 'planes.php') $titulo_header = "Gestión de Planes";
if ($current_page == 'accesos.php') $titulo_header = "Control de Accesos";
if ($current_page == 'solicitudes.php') $titulo_header = "Solicitudes de Registro";

// Consultas para la barra superior
$unread_count = 0;
$nombre_plan_header = null;
$clase_plan_header = 'plan-badge-gray'; 

$usuario_rol_header = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? '';

if (isset($_SESSION['cpanel_admin_id'])) {
    $rol_display = 'Super Administrador';
    
    // LÓGICA INTELIGENTE: Super Admin revisa solicitudes pendientes
    $stmt_notif = $conn->query("SELECT COUNT(*) FROM solicitudes_empresas WHERE estado = 'pendiente'");
    $unread_count = $stmt_notif->fetchColumn();

} else {
    if ($usuario_rol_header === 'representante') {
        $rol_display = 'Representante Legal';
    } elseif ($usuario_rol_header === 'sst') {
        $rol_display = 'Responsable SST';
    } elseif ($usuario_rol_header === 'trabajador') {
        $rol_display = 'Trabajador';
    } else {
        $rol_display = 'Usuario';
    }

    if (isset($_SESSION['usuario_id'])) {
        // LÓGICA INTELIGENTE: Usuario normal revisa sus notificaciones propias
        $stmt_notif = $conn->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
        $stmt_notif->execute([$_SESSION['usuario_id']]);
        $unread_count = $stmt_notif->fetchColumn();

        if ($usuario_rol_header === 'representante') {
            $stmt_plan_hdr = $conn->prepare("
                SELECT p.nombre 
                FROM usuarios u
                JOIN solicitudes_empresas se ON u.empresa_id = se.id
                LEFT JOIN planes p ON se.plan_id = p.id
                WHERE u.id = ?
            ");
            $stmt_plan_hdr->execute([$_SESSION['usuario_id']]);
            $plan_data_hdr = $stmt_plan_hdr->fetch(PDO::FETCH_ASSOC);
            
            if ($plan_data_hdr && !empty($plan_data_hdr['nombre'])) {
                $nombre_plan_header = $plan_data_hdr['nombre'];
                
                if (stripos(strtolower($nombre_plan_header), 'pro') !== false) {
                    $clase_plan_header = 'plan-badge-pro';
                } elseif (stripos(strtolower($nombre_plan_header), 'enterprise') !== false) {
                    $clase_plan_header = 'plan-badge-enterprise';
                } else {
                    $clase_plan_header = 'plan-badge-basic';
                }
            } else {
                $nombre_plan_header = "Sin Plan";
                $clase_plan_header = 'plan-badge-gray';
            }
        }
    }
}
?>
<style>
    .top-header {
        background: #ffffff; /* FONDO TOTALMENTE SÓLIDO */
        border: 1px solid #e2e8f0; /* Borde más definido para combinar con el blanco */
        border-radius: 20px; height: 55px; 
        padding: 0 24px; margin: 16px 30px; display: flex; justify-content: space-between;
        align-items: center; position: sticky; top: 16px; z-index: 100;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .header-left-group { display: flex; align-items: center; gap: 12px; }

    .btn-mobile-menu { display: none; background: none; border: none; color: var(--text); cursor: pointer; padding: 4px; border-radius: 6px; transition: background 0.2s; }
    .btn-mobile-menu:hover { background: rgba(0, 0, 0, 0.05); }

    .header-title { margin: 0; color: var(--blue-dark, #1e3a8a); font-size: 1.05rem; font-weight: 800; letter-spacing: -0.01em; }

    .top-header-actions { display: flex; gap: 12px; align-items: center; flex-direction: row !important; }

    .role-badge { background: rgba(255, 138, 31, 0.12); color: var(--primary2); padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.02em; text-transform: uppercase; border: 1px solid rgba(255, 138, 31, 0.2); }

    .plan-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.02em; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    
    .plan-badge-gray { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .plan-badge-basic { background: #e0f2fe; color: #2563eb; border: 1px solid #bfdbfe; }
    .plan-badge-pro { background: rgba(255, 138, 31, 0.12); color: var(--primary2); border: 1px solid rgba(255, 138, 31, 0.2); }
    .plan-badge-enterprise { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2); }

    /* Ajusté el botón para que combine mejor con el fondo sólido */
    .icon-btn { background: #f8fafc; border: 1px solid #e2e8f0; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; transition: all 0.3s ease; text-decoration: none; position: relative; }
    .icon-btn:hover { background: #ffffff; color: var(--primary); border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); }

    /* ESTILO DEL PUNTICO EN EL HEADER */
    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .dot-notif {
        position: absolute; top: -1px; right: -1px; width: 10px; height: 10px; 
        background: #ef4444; border-radius: 50%; border: 2px solid #ffffff; 
        animation: pulse-dot 2s infinite;
    }

    @media (max-width: 768px) {
        .top-header { margin: 12px 16px; top: 12px; padding: 0 16px; }
        .btn-mobile-menu { display: flex; }
        .role-badge, .plan-badge { display: none; }
        .header-title { font-size: 1rem; }
    }
</style>

<header class="top-header">
    <div class="header-left-group">
        <button class="btn-mobile-menu" id="btnOpenSidebar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <h2 class="header-title"><?php echo $titulo_header; ?></h2>
    </div>

    <div class="top-header-actions">
        <?php if ($nombre_plan_header !== null): ?>
            <div class="plan-badge <?php echo $clase_plan_header; ?>" title="Suscripción Actual">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Plan <?php echo htmlspecialchars($nombre_plan_header); ?>
            </div>
        <?php endif; ?>

        <div class="role-badge"><?php echo htmlspecialchars($rol_display); ?></div>

        <?php 
        // LÓGICA DEL BOTÓN DE NUBE (Solo visible para sst y representante)
        if ($usuario_rol_header === 'representante' || $usuario_rol_header === 'sst'): 
        ?>
        <a href="#" class="icon-btn" title="Almacenamiento en la Nube">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
            </svg>
        </a>
        <?php endif; ?>

        <a href="notificaciones.php" class="icon-btn" title="Notificaciones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            <?php if ($unread_count > 0): ?>
                <span class="dot-notif"></span>
            <?php endif; ?>
        </a>

        <a href="configuracion.php" class="icon-btn" title="Configuración">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        </a>
    </div>
</header>