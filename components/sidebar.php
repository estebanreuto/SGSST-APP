<?php
// Validar si quien inició sesión es el Super Admin
$es_super_admin = isset($_SESSION['cpanel_admin_id']);

// SOLUCIÓN DE ROLES E INFO DE USUARIO
if ($es_super_admin) {
    $usuario_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Super Admin';
    $rol_display = 'Super Administrador';
    $usuario_rol = 'super_admin';
    $nivel_plan = 3; // Super admin tiene todo activo
    $foto_perfil_sidebar = '';
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
$foto_perfil_sidebar = ''; // Variable para guardar la foto

// LÓGICA INTELIGENTE DE NOTIFICACIONES SEGÚN EL ROL Y PLANES
if ($es_super_admin) {
    $stmt_notif = $conn->query("SELECT COUNT(*) FROM solicitudes_empresas WHERE estado = 'pendiente'");
    $unread_count = $stmt_notif->fetchColumn();
    $nivel_plan = 3; // Super admin tiene nivel máximo
} elseif (isset($_SESSION['usuario_id']) && isset($conn)) {
    $stmt_notif = $conn->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt_notif->execute([$_SESSION['usuario_id']]);
    $unread_count = $stmt_notif->fetchColumn();

    // Consultar el plan y la foto de perfil
    $stmt_plan = $conn->prepare("
        SELECT p.nombre, u.foto_perfil 
        FROM usuarios u
        LEFT JOIN solicitudes_empresas se ON u.empresa_id = se.id
        LEFT JOIN planes p ON se.plan_id = p.id
        WHERE u.id = ?
    ");
    $stmt_plan->execute([$_SESSION['usuario_id']]);
    $plan_data = $stmt_plan->fetch(PDO::FETCH_ASSOC);

    if ($plan_data) {
        $foto_perfil_sidebar = $plan_data['foto_perfil'] ?? ''; 

        if (!empty($plan_data['nombre'])) {
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
}

// ==========================================
// DICCIONARIO DE NOMBRES DE ESTÁNDARES
// ==========================================
$nombres_estandares = [
    1 => "Asignación de persona que diseña el Sistema de Gestión de SST",
    2 => "Afiliación al Sistema de Seguridad Social Integral",
    3 => "Capacitación en SST",
    4 => "Plan Anual de Trabajo",
    5 => "Evaluaciones médicas ocupacionales",
    6 => "Identificación de peligros; evaluación y valoración de riesgos",
    7 => "Medidas de prevención y control frente a peligros/riesgos identificados",
    8 => "Asignación de recursos para el Sistema de Gestión de SST",
    9 => "Conformación y funcionamiento del COPASST",
    10 => "Conformación y funcionamiento del Comité de Convivencia Laboral",
    11 => "Política de Seguridad y Salud en el Trabajo",
    12 => "Archivo y retención documental del Sistema de Gestión de SST",
    13 => "Descripción socio demográfica y Diagnóstico de condiciones de salud",
    14 => "Actividades de medicina del trabajo y de prevención y promoción de la salud",
    15 => "Restricciones y recomendaciones médicas laborales",
    16 => "Reporte de accidentes de trabajo y enfermedades laborales",
    17 => "Investigación de incidentes, accidentes de trabajo y enfermedades cuando sean diagnosticadas como laborales",
    18 => "Mantenimiento periódico de instalaciones, equipos, máquinas y herramientas",
    19 => "Entrega de los elementos de protección personal – EPP y capacitación en uso adecuado",
    20 => "Plan de prevención, preparación y respuesta ante emergencias",
    21 => "Brigada de prevención, preparación y respuesta ante emergencias",
    22 => "Revisión por la alta dirección",
    23 => "Asignación de responsabilidades en SST",
    24 => "Identificación de trabajadores que se dediquen en forma permanente a actividades de alto riesgo y cotización de pensión especial",
    25 => "Capacitación de los integrantes del COPASST",
    26 => "Inducción y reinducción en SST",
    27 => "Curso Virtual de capacitación de cincuenta (50) horas en SST",
    28 => "Objetivos de SST",
    29 => "Evaluación Inicial del Sistema de Gestión",
    30 => "Rendición de cuentas",
    31 => "Matriz legal",
    32 => "Mecanismos de comunicación",
    33 => "Identificación y evaluación para la adquisición de bienes y servicios",
    34 => "Evaluación y selección de proveedores y contratistas",
    35 => "Gestión del cambio",
    36 => "Perfiles de cargos",
    37 => "Custodia de las historias clínicas",
    38 => "Estilos de vida y entorno saludable",
    39 => "Servicios de higiene",
    40 => "Manejo de Residuos",
    41 => "Registro y análisis estadístico de accidentes de trabajo y enfermedades laborales",
    42 => "Frecuencia de accidentalidad",
    43 => "Severidad de accidentalidad",
    44 => "Proporción de accidentes de trabajo mortales",
    45 => "Prevalencia de la enfermedad laboral",
    46 => "Incidencia de la enfermedad laboral",
    47 => "Ausentismo por causa médica",
    48 => "Metodología para identificación de peligros, evaluación y valoración de riesgos",
    49 => "Identificación de sustancias catalogadas como carcinógenas o con toxicidad aguda",
    50 => "Mediciones ambientales",
    51 => "Aplicación de medidas de prevención y control por parte de los trabajadores",
    52 => "Procedimientos e instructivos internos de seguridad y salud en el trabajo",
    53 => "Inspecciones a instalaciones, maquinaria o equipos",
    54 => "Definición de indicadores del Sistema de Gestión de Seguridad y Salud en el Trabajo",
    55 => "Auditoría anual",
    56 => "Planificación de la auditoría con el COPASST",
    57 => "Acciones preventivas y/o correctivas",
    58 => "Acciones de mejora conforme a revisión de la Alta Dirección",
    59 => "Acciones de mejora con base en investigaciones de accidentes de trabajo y enfermedades laborales",
    60 => "Plan de mejoramiento"
];
?>
<style>
    :root {
        --primary: #ff8a1f; --primary2: #ff7a00; --card: #ffffff;
        --text: #1f2d3d; --muted: #5f6f82; --border: #e2e8f0;
    }

    .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1040; backdrop-filter: blur(3px); opacity: 0; transition: opacity 0.3s ease; }
    .sidebar-overlay.active { display: block; opacity: 1; }

    .sidebar { width: 260px; background: #ffffff; border-right: 1px solid var(--border); display: flex; flex-direction: column; height: 100dvh; position: fixed; left: 0; top: 0; font-family: 'Inter', sans-serif; z-index: 1050; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 4px 0 15px rgba(0, 0, 0, 0.02); }
    
    .sidebar-header { height: 68px; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); box-sizing: border-box; }
    .brand { display: flex; align-items: center; flex: 1; min-width: 0; margin-right: 12px; }
    .brand img { max-height: 28px; max-width: 100%; width: auto; object-fit: contain; object-position: left center; display: block; }
    
    .btn-close-sidebar { display: none; background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 50%; transition: all 0.3s ease; flex-shrink: 0; }
    .btn-close-sidebar:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
    .btn-close-sidebar svg { width: 24px; height: 24px; }

    .sidebar-nav { padding: 12px 16px; flex: 1; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; overflow-x: hidden; padding-bottom: 20px;}
    .sidebar-nav::-webkit-scrollbar { width: 4px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .nav-section { font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 0.05em; margin: 12px 0 6px 12px; }
    
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: var(--text); text-decoration: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; position: relative; }
    .nav-item:hover { background: #f8fafc; transform: translateX(3px); color: var(--blue-dark, #1e3a8a); }
    .nav-item.active { background: linear-gradient(135deg, rgba(255, 138, 31, 0.12), rgba(255, 122, 0, 0.05)); color: var(--primary2); font-weight: 700; }
    .nav-item.active::before { content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 3px; background: var(--primary); border-radius: 0 4px 4px 0; }
    .nav-item > svg { opacity: 0.6; transition: opacity 0.2s, color 0.2s; flex-shrink: 0;}
    .nav-item:hover > svg, .nav-item.active > svg { opacity: 1; color: var(--primary); }

    .nav-item-locked { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; cursor: not-allowed; background: #f8fafc; opacity: 0.7; margin-bottom: 2px;}
    .nav-item-locked .lock-left { display: flex; align-items: center; gap: 12px; }
    .nav-item-locked .lock-left svg { width: 18px; height: 18px; opacity: 0.6; }
    .nav-item-locked > svg { opacity: 0.5; }

    .sidebar-search-box { position: relative; margin: 4px 0 12px 0; }
    .sidebar-search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.8rem; background: #f8fafc; color: var(--text); box-sizing: border-box; transition: all 0.2s; }
    .sidebar-search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
    .sidebar-search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 16px; height: 16px; }

    .nav-dropdown { display: flex; flex-direction: column; }
    .nav-dropdown-toggle { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; color: var(--text); text-decoration: none; font-size: 0.85rem; font-weight: 500; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; user-select: none; }
    .nav-dropdown-toggle:hover { background: #f8fafc; color: var(--blue-dark, #1e3a8a); }
    .nav-dropdown-toggle .dropdown-left { display: flex; align-items: center; gap: 12px; }
    .nav-dropdown-toggle .dropdown-left svg { opacity: 0.6; width: 18px; height: 18px; transition: 0.2s; }
    .nav-dropdown-toggle:hover .dropdown-left svg { opacity: 1; color: var(--primary); }
    .chevron-icon { width: 16px; height: 16px; color: #94a3b8; transition: transform 0.3s ease; }
    
    .nav-dropdown.active .nav-dropdown-toggle { background: #f8fafc; color: var(--primary2); font-weight: 600; } 
    .nav-dropdown.active .nav-dropdown-toggle .dropdown-left svg { color: var(--primary); opacity: 1; }
    .nav-dropdown.active .chevron-icon { transform: rotate(180deg); color: var(--primary); }

    .nav-dropdown-menu { display: none; padding-left: 36px; padding-top: 4px; padding-bottom: 4px; flex-direction: column; gap: 2px; border-left: 2px solid #e2e8f0; margin-left: 22px; margin-bottom: 4px;}
    .nav-dropdown.active .nav-dropdown-menu { display: flex; }

    .sub-item { display: flex; align-items: flex-start; justify-content: space-between; padding: 8px 12px; color: var(--muted); text-decoration: none; font-size: 0.75rem; font-weight: 500; border-radius: 6px; transition: all 0.2s; position: relative; line-height: 1.35; gap: 8px; }
    .sub-item span { flex: 1; } 
    .sub-item::before { content: ''; position: absolute; left: -14px; top: 12px; width: 10px; height: 2px; background: #e2e8f0; }
    .sub-item:hover { color: var(--primary2); background: #fff8f3; }
    .sub-item.active { color: var(--primary2); background: #fff8f3; font-weight: 700; }
    
    .sub-item.locked { color: #94a3b8; background: transparent; cursor: not-allowed; opacity: 0.7;}
    .sub-item.locked:hover { background: #f8fafc; }
    .sub-item.locked svg { width: 14px; height: 14px; opacity: 0.6; flex-shrink: 0; margin-top: 1px;}

    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .dot-indicator { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; margin-left: auto; animation: pulse-dot 2s infinite; }

    /* FOOTER Y CUENTA */
    .sidebar-footer { padding: 16px; padding-bottom: max(16px, env(safe-area-inset-bottom, 20px)); border-top: 1px solid rgba(0, 0, 0, 0.04); background: #ffffff; }
    .user-box { display: flex; flex-direction: column; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; transition: box-shadow 0.2s; gap: 12px; }
    .user-box:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04); }
    .user-mini { display: flex; align-items: center; gap: 10px; overflow: hidden; }
    .avatar-mini { padding: 0; overflow: hidden; width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0; box-shadow: 0 2px 6px rgba(255, 138, 31, 0.3); }
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
    
    .action-btn.active { background: rgba(255, 138, 31, 0.12); color: var(--primary2); font-weight: 700; }
    .action-btn.active svg { color: var(--primary); }

    .action-btn.exit-btn { color: #ef4444; }
    .action-btn.exit-btn svg { color: #ef4444; }
    .action-btn.exit-btn:hover { background: #fee2e2; color: #dc2626; }
    .action-btn.exit-btn:hover svg { color: #dc2626; }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
        .btn-close-sidebar { display: block; }
        .sidebar-header { padding: 0 20px; }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand">
            <img src="assets/logo_preventwork.jpeg" alt="PrevenWork">
        </div>
        <button class="btn-close-sidebar" id="btnCloseSidebar" title="Cerrar Menú">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                
                <a href="trabajadores.php" class="nav-item <?php echo in_array($current_page, ['trabajadores.php', 'grupos.php']) ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg> Personal
                </a>

                <div class="nav-section" style="margin-top: 20px;">Estándares SG-SST</div>
                
                <div class="sidebar-search-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInputSidebar" placeholder="Buscar estándar (Ej. 15)">
                </div>

                <div class="nav-dropdown" id="dropdownPEM">
                    <div class="nav-dropdown-toggle">
                        <div class="dropdown-left">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Estándares PEM
                        </div>
                        <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    <div class="nav-dropdown-menu">
                        <?php for($i = 1; $i <= 7; $i++): ?>
                            <a href="estandar<?php echo $i; ?>.php" class="sub-item std-item <?php echo $current_page == 'estandar'.$i.'.php' ? 'active' : ''; ?>" data-search="<?php echo $i . ' ' . strtolower(htmlspecialchars($nombres_estandares[$i])); ?>">
                                <span><?php echo htmlspecialchars($nombres_estandares[$i]); ?></span>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>

                <?php if ($nivel_plan >= 2): ?>
                    <div class="nav-dropdown" id="dropdownMEM">
                        <div class="nav-dropdown-toggle">
                            <div class="dropdown-left">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Estándares MEM
                            </div>
                            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="nav-dropdown-menu">
                            <?php for($i = 8; $i <= 21; $i++): ?>
                                <a href="estandar<?php echo $i; ?>.php" class="sub-item std-item <?php echo $current_page == 'estandar'.$i.'.php' ? 'active' : ''; ?>" data-search="<?php echo $i . ' ' . strtolower(htmlspecialchars($nombres_estandares[$i])); ?>">
                                    <span><?php echo htmlspecialchars($nombres_estandares[$i]); ?></span>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="alert('La categoría de Estándares MEM requiere el Plan Pro o Enterprise.')" class="nav-item-locked">
                        <div class="lock-left">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Estándares MEM
                        </div>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </a>
                <?php endif; ?>

                <?php if ($nivel_plan >= 3): ?>
                    <div class="nav-dropdown" id="dropdownGEM">
                        <div class="nav-dropdown-toggle">
                            <div class="dropdown-left">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Estándares GEM
                            </div>
                            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="nav-dropdown-menu">
                            <?php for($i = 22; $i <= 60; $i++): ?>
                                <a href="estandar<?php echo $i; ?>.php" class="sub-item std-item <?php echo $current_page == 'estandar'.$i.'.php' ? 'active' : ''; ?>" data-search="<?php echo $i . ' ' . strtolower(htmlspecialchars($nombres_estandares[$i])); ?>">
                                    <span><?php echo htmlspecialchars($nombres_estandares[$i]); ?></span>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="alert('La categoría de Estándares GEM requiere el Plan Enterprise.')" class="nav-item-locked">
                        <div class="lock-left">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Estándares GEM
                        </div>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </a>
                <?php endif; ?>

                <div class="nav-section" style="margin-top: 20px;">Herramientas</div>
                <?php if ($nivel_plan >= 2): ?>
                    <a href="reportes.php" class="nav-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Generar Reportes
                    </a>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="alert('Requiere Plan Pro o Enterprise.')" class="nav-item-locked">
                        <div class="lock-left">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Generar Reportes
                        </div>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($usuario_rol === 'trabajador'): ?>
                <div class="nav-section" style="margin-top: 20px;">Mis Tareas</div>
                <a href="mis_encuestas.php" class="nav-item <?php echo $current_page == 'mis_encuestas.php' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Mis Encuestas
                </a>
            <?php endif; ?>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-mini">
                <div class="avatar-mini <?php echo $es_super_admin ? 'avatar-admin' : ''; ?>">
                    <?php if (!$es_super_admin && !empty($foto_perfil_sidebar) && file_exists($foto_perfil_sidebar)): ?>
                        <img src="<?php echo htmlspecialchars($foto_perfil_sidebar); ?>?v=<?php echo time(); ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <span class="name"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                    <span class="role"><?php echo htmlspecialchars($rol_display); ?></span>
                </div>
            </div>
            <div class="user-box-divider"></div>
            <div class="user-actions">
                
                <a href="notificaciones.php" class="action-btn <?php echo ($current_page == 'notificaciones.php') ? 'active' : ''; ?>">
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Notificaciones
                    </div>
                    <?php if ($unread_count > 0): ?>
                        <span class="dot-indicator" style="margin-left: auto;"></span>
                    <?php endif; ?>
                </a>

                <a href="configuracion.php" class="action-btn <?php echo ($current_page == 'configuracion.php') ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Configuración
                </a>
                
                <a href="#" onclick="cerrarSesionSegura(); return false;" class="action-btn exit-btn">
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
    // LÓGICA DE ACORDEONES
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const parent = this.parentElement;
            parent.classList.toggle('active');
        });
    });

    // LÓGICA DEL BUSCADOR HÍBRIDO DE ESTÁNDARES EN TIEMPO REAL
    const searchInput = document.getElementById('searchInputSidebar');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            let filter = this.value.toLowerCase().trim();
            let dropdowns = document.querySelectorAll('.nav-dropdown');

            dropdowns.forEach(dropdown => {
                let items = dropdown.querySelectorAll('.std-item');
                let hasVisibleItem = false;

                items.forEach(item => {
                    let searchData = item.getAttribute('data-search') || '';
                    if (searchData.includes(filter)) {
                        item.style.display = 'flex';
                        hasVisibleItem = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (filter !== '') {
                    if (hasVisibleItem) {
                        dropdown.style.display = 'flex';
                        dropdown.classList.add('active');
                    } else {
                        dropdown.style.display = 'none';
                        dropdown.classList.remove('active');
                    }
                } else {
                    dropdown.style.display = 'flex';
                    item.style.display = 'flex';
                    if (!dropdown.querySelector('.std-item.active')) {
                        dropdown.classList.remove('active');
                    }
                }
            });
        });
    }

    // AUTO-ABRIR EL DESPLEGABLE SI EL ESTÁNDAR ESTÁ ACTIVO
    document.addEventListener('DOMContentLoaded', () => {
        const activeSubItem = document.querySelector('.sub-item.active');
        if (activeSubItem) {
            const parentDropdown = activeSubItem.closest('.nav-dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('active');
                setTimeout(() => {
                    activeSubItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 150);
            }
        }
    });

    // REEMPLAZAMOS EL CONFIRM NATIVO POR EL MODAL PREMIUM
    function cerrarSesionSegura() {
        if (typeof showConfirmModal === 'function') {
            showConfirmModal(
                'Cerrar Sesión', 
                '¿Estás seguro de que deseas salir de tu cuenta?', 
                'logout.php', 
                'danger', 
                'Sí, salir'
            );
        } else {
            if (confirm('¿Estás seguro de que deseas salir de tu cuenta?')) {
                window.location.href = 'logout.php';
            }
        }
    }

    // LÓGICA RESPONSIVE MÓVIL
    document.addEventListener('DOMContentLoaded', () => {
        const btnOpenSidebar = document.getElementById('btnOpenSidebar');
        const btnCloseSidebar = document.getElementById('btnCloseSidebar');
        const mainSidebar = document.getElementById('mainSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            if (mainSidebar && sidebarOverlay) {
                mainSidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = mainSidebar.classList.contains('active') ? 'hidden' : '';
            }
        }
        if (btnOpenSidebar) btnOpenSidebar.addEventListener('click', toggleMenu);
        if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', toggleMenu);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMenu);
    });
</script>

<?php include_once __DIR__ . '/modal_confirmacion.php'; ?>