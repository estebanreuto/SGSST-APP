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

$current_page = 'accesos.php';
$titulo_header = "Control de Accesos";
$rol_display = "Super Administrador";

/* ================================================================
// TRAER LOS PLANES DISPONIBLES PARA EL MODAL DE GESTIÓN
================================================================ */
$stmt_planes = $conn->query("SELECT * FROM planes ORDER BY precio_normal ASC");
$planes_disponibles = $stmt_planes->fetchAll(PDO::FETCH_ASSOC);

/* ================================================================
// CONSULTA: TRAER LAS SOLICITUDES APROBADAS + SU PLAN REAL
================================================================ */
// Hacemos un LEFT JOIN para traer el nombre del plan real desde la BD
$stmt = $conn->query("
    SELECT se.*, p.nombre as plan_nombre 
    FROM solicitudes_empresas se 
    LEFT JOIN planes p ON se.plan_id = p.id 
    WHERE se.estado = 'aprobada' 
    ORDER BY se.fecha_creacion DESC
");
$aprobadas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$accesos = [];
foreach ($aprobadas_db as $s) {
    // AHORA SÍ LEEMOS LOS DATOS REALES DE LA BASE DE DATOS
    $accesos[] = [
        'id' => $s['id'],
        'fecha' => $s['fecha_creacion'],
        'empresa' => htmlspecialchars($s['nombre'] . ' ' . $s['apellido']),
        'cedula' => htmlspecialchars($s['cedula'] ?? ''),
        'email' => htmlspecialchars($s['email'] ?? ''),
        'telefono' => htmlspecialchars($s['telefono'] ?? ''),
        'ciudad' => htmlspecialchars($s['ciudad'] ?? 'N/A'),
        'plan_id' => $s['plan_id'], // Trae el ID real de la BD
        'plan_nombre' => $s['plan_nombre'] ?? 'Sin Plan', // Trae el nombre real
        'trabajadores_extra' => $s['trabajadores_extra'] ?? 0, // Trae los extra reales
        'precio_trabajador_extra' => 10000 // Valor base fijo por ahora
    ];
}
$total_aprobadas = count($accesos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesos y Credenciales | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ESTILOS BASE Y GRID */
        :root { 
            --primary: #ff8a1f; --primary2: #ff7a00; 
            --bg1: #edf4fb; --bg2: #f7f9fc; 
            --card: #ffffff; --text: #1f2d3d; 
            --muted: #5f6f82; --border: #dbe3ec; 
            --radius: 12px; --blue-dark: #1e3a8a;
        }
        
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 24px 32px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO PREMIUM ESTANDARIZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 16px; }
        
        .icon-box-std { 
            width: 48px; height: 48px; 
            background: rgba(59, 130, 246, 0.1); color: #3b82f6; 
            border-radius: 12px; display: flex; align-items: center; justify-content: center; 
            flex-shrink: 0; border: 1px solid rgba(59, 130, 246, 0.2); 
        }
        
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.25rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.85rem; font-weight: 500; line-height: 1.4; }

        /* =========================================
           TARJETAS RESUMEN (ESTILO COMPACTO PREMIUM)
           ========================================= */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 16px; position: relative; overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        /* Marca de agua FontAwesome */
        .summary-bg-icon { 
            position: absolute; right: -10px; bottom: -20px; font-size: 100px; 
            line-height: 1; opacity: 0.06; transform: rotate(-15deg); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; z-index: 0;
        }
        .summary-card:hover .summary-bg-icon { transform: rotate(0deg) scale(1.1); opacity: 0.12; }
        
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        
        .summary-icon-box { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .summary-value { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; }
        .summary-title { font-size: 0.9rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 2px 0; }
        .summary-desc { font-size: 0.75rem; color: var(--muted); margin: 0; }

        /* Colores Tarjetas */
        .card-green .summary-bg-icon { color: #10b981; }
        .card-green .summary-icon-box { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .card-green .summary-value { color: #10b981; }

        .card-purple .summary-bg-icon { color: #8b5cf6; }
        .card-purple .summary-icon-box { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
        .card-purple .summary-value { color: #8b5cf6; }

        .card-blue .summary-bg-icon { color: #3b82f6; }
        .card-blue .summary-icon-box { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .card-blue .summary-value { color: #3b82f6; }

        /* =========================================
           BARRA DE HERRAMIENTAS Y BUSCADOR
           ========================================= */
        .toolbar { 
            display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; 
            background: var(--card); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); 
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); gap: 16px; flex-wrap: wrap; 
        }
        
        .search-box { position: relative; flex: 1; max-width: 400px; }
        .search-box input { 
            width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.85rem; color: var(--text); 
            background: #f8fafc; transition: all 0.2s; box-sizing: border-box; 
        }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        
        /* CORRECCIÓN LUPA */
        .search-box i, .search-box svg { 
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%); 
            color: #94a3b8; pointer-events: none; 
        }

        /* ESTADO VACÍO (EMPTY STATE) */
        .empty-state { 
            text-align: center; padding: 40px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; 
            border-radius: 16px; color: #94a3b8; font-size: 0.9rem; font-weight: 500; display: none; 
            flex-direction: column; align-items: center; gap: 12px; grid-column: 1 / -1; 
        }
        .empty-state i { font-size: 2.5rem; color: #cbd5e1; }

        /* =========================================
           VISTA TARJETAS (ESTILO COMPACTO Y ÚNICO)
           ========================================= */
        .cards-wrapper { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        
        .req-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); display: flex; flex-direction: column; 
            transition: transform 0.2s ease, box-shadow 0.2s ease; overflow: hidden;
        }
        .req-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        .req-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .req-card-id { color: #64748b; font-weight: 700; font-family: monospace; font-size: 0.85rem; }
        
        .req-card-body { flex: 1; margin-bottom: 16px; overflow: hidden; }
        .req-card-empresa { font-weight: 700; font-size: 0.95rem; color: var(--blue-dark); margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px; }
        
        .client-avatar { 
            width: 28px; height: 28px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); 
            color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; 
            font-weight: 800; font-size: 0.75rem; flex-shrink: 0;
        }

        .req-card-tipo { font-size: 0.8rem; color: var(--muted); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px; width: 100%; }
        .req-card-tipo i { flex-shrink: 0; color: #94a3b8; width: 14px; text-align: center; }
        .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; width: 100%; }
        
        .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f1f5f9; }
        .req-card-date { font-size: 0.75rem; color: var(--muted); font-weight: 500; display: flex; align-items: center; gap: 4px; margin: 0; }

        /* BADGES Y PLANES */
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.03em; }
        .status-sin-plan { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        .plan-tag { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #e2e8f0; display: inline-block; text-transform: uppercase; letter-spacing: 0.03em;}
        .plan-tag.basic { background: #e0f2fe; color: #2563eb; border-color: #bfdbfe; }
        .plan-tag.pro { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-color: rgba(255, 138, 31, 0.2);}
        .plan-tag.enterprise { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);}

        /* BOTONES DE ACCIÓN */
        .action-btns { display: flex; gap: 8px; }
        
        .btn-upgrade { 
            background: rgba(255, 138, 31, 0.1); color: var(--primary); border: none; padding: 6px 12px; 
            border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; 
            font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 700; transition: all 0.2s; 
        }
        .btn-upgrade:hover { background: var(--primary); color: white; transform: translateY(-1px); }

        .btn-key { 
            background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border: none; padding: 6px 12px; 
            border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; 
            font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 700; transition: all 0.2s; 
        }
        .btn-key:hover { background: #8b5cf6; color: white; transform: translateY(-1px); }

        /* ======================================================== */
        /* MODAL DE ACCESOS (DISEÑO FINO Y COMPACTO)                */
        /* ======================================================== */
        .modal-detalles-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); 
            display: none; justify-content: center; align-items: center; 
            z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 20px; box-sizing: border-box; 
        }
        .modal-detalles-overlay.active { display: flex; opacity: 1; }
        
        .modal-detalles-box { 
            background: #ffffff; border-radius: 16px; width: 100%; max-width: 550px; 
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); 
            transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; 
        }
        .modal-detalles-overlay.active .modal-detalles-box { transform: translateY(0) scale(1); }
        
        .modal-detalles-header { 
            background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid var(--border); 
            display: flex; align-items: center; gap: 14px; position: relative; 
        }
        
        .modal-detalles-icon-top { 
            width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; 
            justify-content: center; flex-shrink: 0; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; font-size: 1rem;
        }
        .modal-detalles-header-text h3 { margin: 0 0 2px 0; font-size: 1rem; color: #1e293b; font-weight: 800; }
        .modal-detalles-header-text p { margin: 0; color: var(--muted); font-size: 0.75rem; }
        
        .btn-close-detalles { 
            position: absolute; top: 16px; right: 20px; background: transparent; border: none; 
            width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; 
            color: #94a3b8; cursor: pointer; transition: all 0.2s; font-size: 1rem;
        }
        .btn-close-detalles:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
        
        .modal-detalles-body { padding: 20px 24px; overflow-y: auto; }

        .modal-detalles-footer { 
            padding: 16px 24px; border-top: 1px solid var(--border); background: #ffffff; 
            display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 16px; }
            .summary-cards-grid { grid-template-columns: 1fr; } 
            .estandar-header-group { flex-direction: column; align-items: flex-start; gap: 12px; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-box { width: 100%; max-width: 100%; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-key" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Control de Accesos</h1>
                        <p class="estandar-subtitle">Gestiona las credenciales y planes de las empresas aprobadas.</p>
                    </div>
                </div>
            </div>

            <div class="summary-cards-grid">
                <div class="summary-card card-green">
                    <i class="fa-solid fa-building-circle-check summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-building-circle-check"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_aprobadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Empresas Aprobadas</h3>
                        <p class="summary-desc">Aceptadas en el sistema.</p>
                    </div>
                </div>

                <div class="summary-card card-purple">
                    <i class="fa-solid fa-id-card-clip summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_aprobadas; ?></h2> 
                        </div>
                        <h3 class="summary-title">Accesos Generados</h3>
                        <p class="summary-desc">Credenciales creadas.</p>
                    </div>
                </div>

                <div class="summary-card card-blue">
                    <i class="fa-solid fa-users-gear summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-users-gear"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_aprobadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Cuentas Activas</h3>
                        <p class="summary-desc">Operando actualmente.</p>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Buscar empresa, cédula o correo...">
                </div>
            </div>

            <div class="empty-state" id="emptyStateMsg" style="<?php echo empty($accesos) ? 'display: flex;' : ''; ?>">
                <i class="fa-regular fa-folder-open"></i>
                <span>No se encontraron empresas aprobadas en el sistema.</span>
            </div>

            <div class="cards-wrapper" id="viewCards" style="<?php echo empty($accesos) ? 'display: none;' : 'display: grid;'; ?>">
                <?php foreach ($accesos as $acc): 
                    $clase_plan = 'basic';
                    if (stripos(strtolower($acc['plan_nombre']), 'pro') !== false) {
                        $clase_plan = 'pro';
                    } elseif (stripos(strtolower($acc['plan_nombre']), 'enterprise') !== false) {
                        $clase_plan = 'enterprise';
                    }
                ?>
                    <div class="req-card filter-item" data-text="<?php echo strtolower($acc['empresa'] . ' ' . $acc['cedula'] . ' ' . $acc['email']); ?>">
                        
                        <div class="req-card-header">
                            <span class="req-card-id">#REQ-<?php echo str_pad($acc['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            
                            <div style="display:flex; gap:8px;">
                                <?php if ($acc['plan_id']): ?>
                                    <span class="plan-tag <?php echo $clase_plan; ?>"><?php echo $acc['plan_nombre']; ?></span>
                                <?php else: ?>
                                    <span class="badge-status status-sin-plan">Sin Plan</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="req-card-body">
                            <div class="req-card-empresa">
                                <div class="client-avatar">
                                    <?php echo strtoupper(substr($acc['empresa'], 0, 1)); ?>
                                </div>
                                <span class="text-truncate" title="<?php echo $acc['empresa']; ?>"><?php echo $acc['empresa']; ?></span>
                            </div>
                            
                            <p class="req-card-tipo" style="margin-top: 10px;">
                                <i class="fa-regular fa-id-card"></i>
                                NIT: <?php echo $acc['cedula']; ?>
                            </p>
                            <p class="req-card-tipo" title="<?php echo $acc['email']; ?>">
                                <i class="fa-regular fa-envelope"></i>
                                <span class="text-truncate"><?php echo $acc['email']; ?></span>
                            </p>
                            <?php if ($acc['trabajadores_extra'] > 0): ?>
                                <p class="req-card-tipo">
                                    <i class="fa-solid fa-users" style="color: var(--primary);"></i>
                                    <span style="color: var(--primary); font-weight: 600;">+<?php echo $acc['trabajadores_extra']; ?> cupos extra</span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="req-card-footer">
                            <span class="req-card-date">
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($acc['fecha'])); ?>
                            </span>
                            
                            <div class="action-btns">
                                <button type="button" class="btn-upgrade" title="Gestionar Plan y Trabajadores" onclick="abrirModalGestionPlan(<?php echo $acc['id']; ?>, '<?php echo addslashes($acc['empresa']); ?>', <?php echo $acc['plan_id'] ?? 'null'; ?>, <?php echo $acc['trabajadores_extra']; ?>, <?php echo $acc['precio_trabajador_extra']; ?>)">
                                    <i class="fa-solid fa-arrow-up-right-dots"></i> Plan
                                </button>
                                
                                <button type="button" class="btn-key" title="Gestionar Acceso" onclick="abrirModalAcceso(<?php echo $acc['id']; ?>)">
                                    <i class="fa-solid fa-key"></i> Accesos
                                </button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <div class="modal-detalles-overlay" id="modalAccesos">
        <div class="modal-detalles-box" style="max-width: 500px;">
            <div class="modal-detalles-header">
                <div class="modal-detalles-icon-top">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="modal-detalles-header-text">
                    <h3>Credenciales de Acceso</h3>
                    <p>Genera y envía las credenciales a la empresa.</p>
                </div>
                <button type="button" class="btn-close-detalles" onclick="cerrarModalAcceso()" title="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="modal-detalles-body" id="accesosBody">
                </div>
            
            <div class="modal-detalles-footer" id="accesosFooter">
                </div>
        </div>
    </div>

    <?php include '../components/modal_gestionar_plan.php'; ?>

    <script>
        const datosAccesos = <?php echo json_encode($accesos); ?>;
        window_planes = <?php echo json_encode($planes_disponibles); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            
            // Detectar mensaje de éxito en la URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('upgrade') === 'success') {
                alert("¡Suscripción actualizada exitosamente!");
                window.history.replaceState(null, null, window.location.pathname);
            } else if (urlParams.get('upgrade') === 'error') {
                alert("Hubo un error al actualizar el plan.");
                window.history.replaceState(null, null, window.location.pathname);
            }

            // Buscador automático de tarjetas
            const searchInput = document.getElementById('searchInput');
            const items = document.querySelectorAll('.filter-item'); 
            const emptyStateMsg = document.getElementById('emptyStateMsg');
            const viewCards = document.getElementById('viewCards');

            if(searchInput) {
                searchInput.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    let visibleCount = 0;

                    items.forEach(item => {
                        const text = item.getAttribute('data-text') || '';
                        if (text.includes(filter)) {
                            item.style.display = 'flex'; // Mantener el display original de la tarjeta
                            visibleCount++;
                        } else {
                            item.style.display = 'none'; 
                        }
                    });

                    if (visibleCount === 0 && items.length > 0) {
                        emptyStateMsg.style.display = 'flex';
                        viewCards.style.display = 'none';
                    } else if (items.length > 0) {
                        emptyStateMsg.style.display = 'none';
                        viewCards.style.display = 'grid';
                    }
                });
            }
        });

        // Lógica Modal de Accesos
        function abrirModalAcceso(id) {
            const data = datosAccesos.find(s => s.id === id);
            if (!data) return;

            let waMessage = encodeURIComponent(`Hola, somos el equipo de Prevención. Tu solicitud para la empresa *${data.empresa}* ha sido aprobada. Aquí tienes tus credenciales de acceso a SG-SST Pro:\n\nUsuario: ${data.cedula}\nContraseña: (Tu contraseña aquí)\n\nEnlace: https://tusistema.com/login`);
            let cleanPhone = data.telefono ? data.telefono.replace(/\D/g, '') : '';
            let waLink = `https://wa.me/57${cleanPhone}?text=${waMessage}`;

            const bodyHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 700; width: 65px; font-size: 0.75rem; text-transform: uppercase;">Empresa</span>
                        <span style="flex: 1; word-break: break-all; font-size: 0.85rem; color: #1e293b; font-weight: 600;">${data.empresa}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 700; width: 65px; font-size: 0.75rem; text-transform: uppercase;">Usuario</span>
                        <span style="font-family: monospace; color: var(--primary); font-size: 0.95rem; font-weight: 700;">${data.cedula}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 700; width: 65px; font-size: 0.75rem; text-transform: uppercase;">Correo</span>
                        <span style="flex: 1; word-break: break-all; font-size: 0.85rem; color: #1e293b; font-weight: 600;">${data.email}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 700; width: 65px; font-size: 0.75rem; text-transform: uppercase;">Teléfono</span>
                        <span style="font-size: 0.85rem; color: #1e293b; font-weight: 600;">${data.telefono || 'N/A'}</span>
                    </div>
                </div>
                <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.15); padding: 12px; border-radius: 8px; display: flex; gap: 10px; align-items: flex-start;">
                    <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-top: 2px;"></i>
                    <p style="font-size: 0.75rem; color: #475569; margin: 0; line-height: 1.4;">
                        Al enviar las credenciales, el representante podrá iniciar sesión con su número de documento. La contraseña inicial será solicitada en su primer ingreso o la que definas en el correo.
                    </p>
                </div>
            `;

            document.getElementById('accesosBody').innerHTML = bodyHtml;

            let footerHtml = `<button type="button" style="background: #ffffff; color: #64748b; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'" onclick="cerrarModalAcceso()">Cancelar</button>`;
            
            footerHtml += `
                <a href="mailto:${data.email}?subject=Acceso%20SG-SST%20Pro&body=Hola%20${data.empresa},%0A%0ATu%20cuenta%20ha%20sido%20aprobada." style="background: #3b82f6; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fa-regular fa-envelope"></i> Correo
                </a>
            `;

            if (cleanPhone) {
                footerHtml += `
                    <a href="${waLink}" target="_blank" style="background: #25D366; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'" onclick="cerrarModalAcceso()">
                        <i class="fa-brands fa-whatsapp" style="font-size: 0.9rem;"></i> WhatsApp
                    </a>
                `;
            }

            document.getElementById('accesosFooter').innerHTML = footerHtml;
            document.getElementById('modalAccesos').classList.add('active');
        }

        function cerrarModalAcceso() {
            document.getElementById('modalAccesos').classList.remove('active');
        }

        document.getElementById('modalAccesos').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalAcceso();
        });
    </script>
</body>
</html>