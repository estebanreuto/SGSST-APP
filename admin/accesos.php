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

$default_plan = count($planes_disponibles) > 0 ? $planes_disponibles[0] : ['id'=>1, 'nombre'=>'Básico'];

/* ================================================================
// CONSULTA: TRAER SOLO LAS SOLICITUDES APROBADAS
================================================================ */
$stmt = $conn->query("SELECT * FROM solicitudes_empresas WHERE estado = 'aprobada' ORDER BY fecha_creacion DESC");
$aprobadas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$accesos = [];
$contador = 0;
foreach ($aprobadas_db as $s) {
    // SIMULACIÓN: Hacemos que algunas tengan plan y otras no para probar el diseño
    $tiene_plan = ($contador % 2 == 0); 

    $accesos[] = [
        'id' => $s['id'],
        'fecha' => $s['fecha_creacion'],
        'empresa' => htmlspecialchars($s['nombre'] . ' ' . $s['apellido']),
        'cedula' => htmlspecialchars($s['cedula'] ?? ''),
        'email' => htmlspecialchars($s['email'] ?? ''),
        'telefono' => htmlspecialchars($s['telefono'] ?? ''),
        'ciudad' => htmlspecialchars($s['ciudad'] ?? 'N/A'),
        'plan_id' => $tiene_plan ? $default_plan['id'] : null,
        'plan_nombre' => $tiene_plan ? $default_plan['nombre'] : 'Sin Plan',
        'trabajadores_extra' => 0, 
        'precio_trabajador_extra' => 10000 // Valor por defecto editable
    ];
    $contador++;
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

    <style>
        /* ESTILOS BASE Y GRID */
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .welcome-title { margin: 0 0 6px 0; font-size: 1.25rem; color: var(--text); letter-spacing: -0.01em; font-weight: 800; }
        .welcome-text { color: var(--muted); margin: 0; font-size: 0.85rem; }
        
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .info-card { background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: flex-start; gap: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        
        .icon-box { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .box-green { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .box-blue { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .box-purple { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
        
        .info-content { display: flex; flex-direction: column; gap: 3px; padding-top: 2px; }
        .info-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin: 0; }
        .info-value { font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; }
        
        /* TOOLBAR Y BUSCADOR */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; background: var(--card); padding: 12px 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); gap: 16px; flex-wrap: wrap; }
        .toolbar-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;}
        .search-box { position: relative; flex: 1; max-width: 400px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; background: #f8fafc; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        
        .toolbar-right { display: flex; align-items: center; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .view-btn { border: none; background: transparent; padding: 6px 12px; border-radius: 6px; cursor: pointer; color: #64748b; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; }
        .view-btn.active { background: #ffffff; color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .view-btn:hover:not(.active) { color: var(--text); }

        /* VISTA TABLA */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: block; }
        .table-wrapper.hidden { display: none; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 14px 20px; font-size: 0.65rem; text-transform: uppercase; color: var(--muted); font-weight: 800; border-bottom: 1px solid var(--border); letter-spacing: 0.05em; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text); vertical-align: middle; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        /* VISTA TARJETAS */
        .cards-wrapper { display: none; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .cards-wrapper.active { display: grid; }
        .req-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; overflow: hidden; }
        .req-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .req-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .req-card-id { color: var(--primary); font-weight: 800; font-family: monospace; font-size: 0.9rem; }
        .req-card-date { font-size: 0.75rem; color: var(--muted); font-weight: 500; display: flex; align-items: center; gap: 4px; }
        .req-card-body { flex: 1; margin-bottom: 20px; overflow: hidden; }
        .req-card-empresa { font-weight: 700; font-size: 0.95rem; color: var(--text); margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px; }
        
        .req-card-tipo { font-size: 0.8rem; color: var(--muted); margin: 0; display: flex; align-items: center; gap: 6px; width: 100%; overflow: hidden; }
        .req-card-tipo svg { flex-shrink: 0; }
        .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; width: 100%; }

        .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #f1f5f9; }

        .client-avatar { width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0;}

        /* BADGES Y ETIQUETAS */
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
        .badge-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-aprobada { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-aprobada::before { background: #16a34a; }
        .status-sin-plan { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        /* Estilo del Plan asignado */
        .plan-tag { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #e2e8f0;}
        .plan-tag.pro { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-color: rgba(255, 138, 31, 0.2);}
        .plan-tag.enterprise { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);}

        /* BOTONES DE ACCIÓN */
        .action-btns { display: flex; gap: 8px; }
        
        .btn-key { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 700; transition: all 0.2s; }
        .btn-key:hover { background: #8b5cf6; color: white; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.25); transform: translateY(-1px); }

        .btn-upgrade { background: rgba(255, 138, 31, 0.1); color: var(--primary); border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 700; transition: all 0.2s; }
        .btn-upgrade:hover { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.25); transform: translateY(-1px); }

        .empty-state { text-align: center; padding: 50px; color: var(--muted); font-style: italic; display: none; width: 100%; grid-column: 1 / -1;}
        .empty-state.active { display: block; }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 20px 16px; }
            .info-grid { grid-template-columns: 1fr; }
            .table-wrapper { overflow-x: auto; } table { min-width: 850px; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar-left { flex-direction: column; } .search-box { width: 100%; max-width: 100%; }
            .toolbar-right { justify-content: center; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div>
                    <h1 class="welcome-title">Control de Accesos</h1>
                    <p class="welcome-text">Gestiona las credenciales y planes de las empresas que ya han sido aprobadas.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="icon-box box-green">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Empresas Aprobadas</p>
                        <p class="info-value"><?php echo $total_aprobadas; ?></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="icon-box box-purple">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Accesos Generados</p>
                        <p class="info-value"><?php echo $total_aprobadas; ?></p> 
                    </div>
                </div>

                <div class="info-card">
                    <div class="icon-box box-blue">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Cuentas Activas</p>
                        <p class="info-value"><?php echo $total_aprobadas; ?></p>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="searchInput" placeholder="Buscar empresa, cédula o correo...">
                    </div>
                </div>
                <div class="toolbar-right">
                    <button class="view-btn" id="btnViewTable" title="Vista de Tabla">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Tabla
                    </button>
                    <button class="view-btn active" id="btnViewCards" title="Vista de Tarjetas">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Tarjetas
                    </button>
                </div>
            </div>

            <div class="empty-state" id="emptyStateMsg">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40" style="margin-bottom:10px; opacity:0.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <br>No se encontraron empresas aprobadas en el sistema.
            </div>

            <div class="table-wrapper hidden" id="viewTable">
                <table>
                    <thead>
                        <tr>
                            <th>Empresa / Representante</th>
                            <th>NIT / Cédula</th>
                            <th>Contacto</th>
                            <th>Plan Asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accesos)): ?>
                            <tr class="no-data-row"><td colspan="5" style="text-align: center; padding: 40px; color: var(--muted); font-style: italic;">Aún no hay solicitudes aprobadas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($accesos as $acc): 
                                $clase_plan = strpos(strtolower($acc['plan_nombre']), 'pro') !== false ? 'pro' : (strtolower($acc['plan_nombre']) === 'enterprise' ? 'enterprise' : '');
                            ?>
                                <tr class="filter-item" data-text="<?php echo strtolower($acc['empresa'] . ' ' . $acc['cedula'] . ' ' . $acc['email']); ?>">
                                    
                                    <td style="font-weight: 600;">
                                        <div class="client-avatar">
                                            <?php echo strtoupper(substr($acc['empresa'], 0, 1)); ?>
                                        </div>
                                        <?php echo $acc['empresa']; ?>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.9rem;">
                                        <?php echo $acc['cedula']; ?>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <div style="display:flex; flex-direction:column; gap:2px;">
                                            <span style="font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo $acc['email']; ?>"><?php echo $acc['email']; ?></span>
                                            <span style="color: var(--muted); font-size: 0.75rem;"><?php echo $acc['telefono']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($acc['plan_id']): ?>
                                            <span class="plan-tag <?php echo $clase_plan; ?>"><?php echo $acc['plan_nombre']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-status status-sin-plan">Sin Plan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="btn-upgrade" title="Gestionar Plan y Trabajadores" onclick="abrirModalGestionPlan(<?php echo $acc['id']; ?>, '<?php echo addslashes($acc['empresa']); ?>', <?php echo $acc['plan_id'] ?? 'null'; ?>, <?php echo $acc['trabajadores_extra']; ?>, <?php echo $acc['precio_trabajador_extra']; ?>)">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                Plan
                                            </button>
                                            <button type="button" class="btn-key" title="Gestionar Acceso" onclick="abrirModalAcceso(<?php echo $acc['id']; ?>)">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                                Accesos
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="cards-wrapper active" id="viewCards">
                <?php foreach ($accesos as $acc): 
                    $clase_plan = strpos(strtolower($acc['plan_nombre']), 'pro') !== false ? 'pro' : (strtolower($acc['plan_nombre']) === 'enterprise' ? 'enterprise' : '');
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
                                <div class="client-avatar" style="width: 28px; height: 28px; font-size: 0.75rem; margin:0;">
                                    <?php echo strtoupper(substr($acc['empresa'], 0, 1)); ?>
                                </div>
                                <span class="text-truncate" title="<?php echo $acc['empresa']; ?>"><?php echo $acc['empresa']; ?></span>
                            </div>
                            
                            <p class="req-card-tipo" style="margin-top: 10px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                NIT: <?php echo $acc['cedula']; ?>
                            </p>
                            <p class="req-card-tipo" style="margin-top: 6px;" title="<?php echo $acc['email']; ?>">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span class="text-truncate"><?php echo $acc['email']; ?></span>
                            </p>
                        </div>

                        <div class="req-card-footer">
                            <span class="req-card-date" style="margin:0;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <?php echo date('d/m/Y', strtotime($acc['fecha'])); ?>
                            </span>
                            
                            <div class="action-btns">
                                <button type="button" class="btn-upgrade" title="Gestionar Plan y Trabajadores" onclick="abrirModalGestionPlan(<?php echo $acc['id']; ?>, '<?php echo addslashes($acc['empresa']); ?>', <?php echo $acc['plan_id'] ?? 'null'; ?>, <?php echo $acc['trabajadores_extra']; ?>, <?php echo $acc['precio_trabajador_extra']; ?>)">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Plan
                                </button>
                                
                                <button type="button" class="btn-key" title="Gestionar Acceso" onclick="abrirModalAcceso(<?php echo $acc['id']; ?>)">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    Accesos
                                </button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <style>
        .modal-detalles-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); 
            display: none; justify-content: center; align-items: center; 
            z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 20px; box-sizing: border-box; 
        }
        .modal-detalles-overlay.active { display: flex; opacity: 1; }
        
        .modal-detalles-box { 
            background: #ffffff; border-radius: 20px; width: 100%; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); 
            transform: translateY(-30px) scale(0.95); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            display: flex; flex-direction: column; overflow: hidden; 
            max-height: 90vh; 
        }
        .modal-detalles-overlay.active .modal-detalles-box { transform: translateY(0) scale(1); }
        
        .modal-detalles-header { 
            background: linear-gradient(to right, #f8fafc, #ffffff); padding: 20px 30px; 
            border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 14px; position: relative; 
        }
        .modal-detalles-icon-top { 
            width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; 
            justify-content: center; flex-shrink: 0; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; 
        }
        .modal-detalles-header-text h3 { margin: 0 0 2px 0; font-size: 1.05rem; color: #1e293b; font-weight: 800; }
        .modal-detalles-header-text p { margin: 0; color: var(--muted); font-size: 0.75rem; }
        
        .btn-close-detalles { 
            position: absolute; top: 24px; right: 24px; background: #f1f5f9; border: none; width: 32px; height: 32px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            color: var(--muted); cursor: pointer; transition: all 0.2s; 
        }
        .btn-close-detalles:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
        
        .modal-detalles-body { padding: 24px 30px; overflow-y: auto; }
        .modal-detalles-body::-webkit-scrollbar { width: 6px; }
        .modal-detalles-body::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
        .modal-detalles-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    </style>

    <div class="modal-detalles-overlay" id="modalAccesos">
        <div class="modal-detalles-box" style="max-width: 600px;">
            <div class="modal-detalles-header">
                <div class="modal-detalles-icon-top">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <div class="modal-detalles-header-text">
                    <h3>Gestión de Accesos</h3>
                    <p>Genera y envía las credenciales a la empresa.</p>
                </div>
                <button type="button" class="btn-close-detalles" onclick="cerrarModalAcceso()" title="Cerrar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="modal-detalles-body" id="accesosBody">
                </div>
            
            <div class="modal-detalles-footer" id="accesosFooter" style="padding: 20px 30px; border-top: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                </div>
        </div>
    </div>

    <?php include '../components/modal_gestionar_plan.php'; ?>

    <script>
        const datosAccesos = <?php echo json_encode($accesos); ?>;
        window_planes = <?php echo json_encode($planes_disponibles); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const btnTable = document.getElementById('btnViewTable');
            const btnCards = document.getElementById('btnViewCards');
            const viewTable = document.getElementById('viewTable');
            const viewCards = document.getElementById('viewCards');

            btnTable.addEventListener('click', () => {
                btnTable.classList.add('active');
                btnCards.classList.remove('active');
                viewTable.classList.remove('hidden');
                viewCards.classList.remove('active');
            });

            btnCards.addEventListener('click', () => {
                btnCards.classList.add('active');
                btnTable.classList.remove('active');
                viewTable.classList.add('hidden');
                viewCards.classList.add('active');
            });

            const searchInput = document.getElementById('searchInput');
            const items = document.querySelectorAll('.filter-item'); 
            const emptyStateMsg = document.getElementById('emptyStateMsg');

            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase().trim();
                    let visibleCount = 0;

                    items.forEach(item => {
                        const text = item.getAttribute('data-text') || item.innerText.toLowerCase();
                        if (text.includes(filter)) {
                            item.style.display = ''; 
                            visibleCount++;
                        } else {
                            item.style.display = 'none'; 
                        }
                    });

                    if (visibleCount === 0 && items.length > 0) {
                        emptyStateMsg.classList.add('active');
                        viewTable.style.display = 'none';
                        viewCards.style.display = 'none';
                    } else {
                        emptyStateMsg.classList.remove('active');
                        if(btnTable.classList.contains('active')) {
                            viewTable.style.display = 'block';
                        } else {
                            viewCards.style.display = 'grid';
                        }
                    }
                });
            }
        });

        function abrirModalAcceso(id) {
            const data = datosAccesos.find(s => s.id === id);
            if (!data) return;

            let waMessage = encodeURIComponent(`Hola, somos el equipo de Vertix. Tu solicitud para la empresa *${data.empresa}* ha sido aprobada. Aquí tienes tus credenciales de acceso a SG-SST Pro:\n\nUsuario: ${data.cedula}\nContraseña: (Tu contraseña aquí)\n\nEnlace: https://tusistema.com/login`);
            let cleanPhone = data.telefono ? data.telefono.replace(/\D/g, '') : '';
            let waLink = `https://wa.me/57${cleanPhone}?text=${waMessage}`;

            const bodyHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 500; width: 60px; font-size: 0.85rem;">Empresa:</span>
                        <span style="flex: 1; word-break: break-all; font-size: 0.85rem; color: var(--text); font-weight: 600;">${data.empresa}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 500; width: 60px; font-size: 0.85rem;">Usuario:</span>
                        <span style="font-family: monospace; color: var(--primary); font-size: 0.85rem; font-weight: 600;">${data.cedula}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 500; width: 60px; font-size: 0.85rem;">Correo:</span>
                        <span style="flex: 1; word-break: break-all; font-size: 0.85rem; color: var(--text); font-weight: 600;">${data.email}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--muted); font-weight: 500; width: 60px; font-size: 0.85rem;">Teléfono:</span>
                        <span style="font-size: 0.85rem; color: var(--text); font-weight: 600;">${data.telefono || 'N/A'}</span>
                    </div>
                </div>
                <p style="font-size: 0.8rem; color: var(--muted); margin: 0; line-height: 1.5;">
                    Al enviar las credenciales, el sistema habilitará automáticamente la contraseña inicial para que el representante pueda iniciar sesión.
                </p>
            `;

            document.getElementById('accesosBody').innerHTML = bodyHtml;

            let footerHtml = `<button type="button" style="background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.8rem;" onclick="cerrarModalAcceso()">Cancelar</button>`;
            
            footerHtml += `
                <a href="mailto:${data.email}?subject=Acceso%20SG-SST%20Pro&body=Hola%20${data.empresa},%0A%0ATu%20cuenta%20ha%20sido%20aprobada." style="background: #3b82f6; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    Email
                </a>
            `;

            if (cleanPhone) {
                footerHtml += `
                    <a href="${waLink}" target="_blank" style="background: #25D366; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;" onclick="cerrarModalAcceso()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.711.848 3.146.848 3.182 0 5.767-2.585 5.769-5.766.001-3.181-2.586-5.766-5.766-5.766m3.167 8.272c-.135.378-.795.731-1.092.744-.297.013-.672.046-2.146-.566-1.782-.74-2.923-2.56-3.008-2.671-.085-.111-.718-.958-.718-1.838 0-.88.455-1.312.617-1.488.163-.176.353-.221.472-.221.119 0 .238.001.343.006.111.005.259-.043.405.311.15.362.515 1.258.562 1.353.047.095.078.206.015.337-.062.131-.095.213-.19.324-.095.112-.2.247-.284.331-.095.101-.194.21-.082.391.112.182.497.813 1.034 1.285.69.61 1.306.797 1.485.892.18.095.284.078.39-.033.106-.112.46-.537.584-.722.124-.184.248-.153.414-.091.166.061 1.047.495 1.226.584.18.089.299.135.343.21.045.075.045.437-.09.815"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.764.453 3.42 1.282 4.887L2 22l5.253-1.253A9.954 9.954 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2m0 18.25c-1.503 0-2.97-.384-4.26-1.112l-.306-.181-3.17.756.768-3.088-.198-.315A8.252 8.252 0 0 1 3.75 12c0-4.55 3.7-8.25 8.25-8.25s8.25 3.7 8.25 8.25-3.7 8.25-8.25 8.25"/></svg>
                        WhatsApp
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