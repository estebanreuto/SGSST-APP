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

$current_page = 'planes.php';
$titulo_header = "Gestión de Planes";
$rol_display = "Super Administrador";

/* ================================================================
// TRAER LOS PLANES Y SUS CARACTERÍSTICAS DESDE LA BASE DE DATOS
================================================================ */
$stmt_planes = $conn->query("SELECT * FROM planes ORDER BY id ASC");
$planes_db = $stmt_planes->fetchAll(PDO::FETCH_ASSOC);

$planes_info = [];
foreach ($planes_db as $plan) {
    $stmt_feat = $conn->prepare("SELECT * FROM plan_caracteristicas WHERE plan_id = ? ORDER BY id ASC");
    $stmt_feat->execute([$plan['id']]);
    $features_db = $stmt_feat->fetchAll(PDO::FETCH_ASSOC);
    
    $features = [];
    foreach ($features_db as $f) {
        $features[] = [
            'texto' => $f['texto'],
            'incluido' => (bool)$f['incluido']
        ];
    }
    
    $plan['features'] = $features;
    $planes_info[] = $plan;
}

/* ================================================================
// TRAER LAS SUSCRIPCIONES REALES DESDE LA BASE DE DATOS
================================================================ */
// Hacemos JOIN entre empresas y planes
$stmt_sus = $conn->query("
    SELECT se.nombre, se.apellido, se.fecha_creacion, p.nombre as plan_nombre 
    FROM solicitudes_empresas se 
    LEFT JOIN planes p ON se.plan_id = p.id 
    WHERE se.estado = 'aprobada' 
    ORDER BY se.fecha_creacion DESC
");
$suscripciones_db = $stmt_sus->fetchAll(PDO::FETCH_ASSOC);

$suscripciones = [];
foreach ($suscripciones_db as $s) {
    // Calculamos 1 mes después de su creación como próximo pago
    $fecha_creacion = strtotime($s['fecha_creacion']);
    $proximo_pago = date('Y-m-d', strtotime('+30 days', $fecha_creacion));
    
    $suscripciones[] = [
        'empresa' => htmlspecialchars($s['nombre'] . ' ' . $s['apellido']),
        'plan' => $s['plan_nombre'] ?? 'Sin Plan',
        'estado' => empty($s['plan_nombre']) ? 'inactivo' : 'activo',
        'vencimiento' => empty($s['plan_nombre']) ? 'N/A' : $proximo_pago
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes y Suscripciones | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .welcome-title { margin: 0 0 6px 0; font-size: 1.25rem; color: var(--text); letter-spacing: -0.01em; font-weight: 800; }
        .welcome-text { color: var(--muted); margin: 0; font-size: 0.85rem; }
        
        .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; align-items: center; }
        .plan-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 32px 24px; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: transform 0.3s ease, box-shadow 0.3s ease; position: relative; }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.06); }
        .plan-card.popular { border: 2px solid var(--primary); box-shadow: 0 12px 30px rgba(255, 138, 31, 0.12); transform: scale(1.03); z-index: 1; }
        .plan-card.popular:hover { transform: scale(1.03) translateY(-5px); }
        .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; padding: 4px 16px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.3); }
        .plan-header { text-align: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .plan-title { font-size: 1.1rem; color: var(--text); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0; }
        .popular .plan-title { color: var(--primary); }
        .price-container { display: flex; flex-direction: column; align-items: center; gap: 2px; }
        .plan-price-old { font-size: 0.95rem; color: #94a3b8; text-decoration: line-through; font-weight: 600; margin: 0; height: 18px; }
        .plan-price { font-size: 2.5rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; display: flex; justify-content: center; align-items: baseline; gap: 4px;}
        .plan-price span { font-size: 0.9rem; color: var(--muted); font-weight: 600; }
        .plan-features { list-style: none; padding: 0; margin: 0 0 32px 0; flex: 1; display: flex; flex-direction: column; gap: 14px; }
        .plan-features li { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text); font-weight: 500; }
        .plan-features li svg.check { color: #10b981; flex-shrink: 0; }
        .plan-features li.disabled { color: #94a3b8; text-decoration: line-through; }
        .plan-features li.disabled svg.cross { color: #cbd5e1; }
        
        .btn-plan { width: 100%; padding: 12px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-align: center; cursor: pointer; transition: all 0.2s; border: none; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-outline { background: #f8fafc; color: var(--text); border: 1px solid #cbd5e1; }
        .btn-outline:hover { background: #f1f5f9; border-color: #94a3b8; }
        .btn-solid { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; box-shadow: 0 4px 12px rgba(255, 138, 31, 0.25); }
        .btn-solid:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.35); }
        
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 16px; }
        .section-header h2 { font-size: 1.1rem; color: var(--text); font-weight: 800; margin: 0; }
        
        /* ESTILOS DEL BUSCADOR Y FILTROS */
        .table-tools { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; background: #f8fafc; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        
        .filter-select { padding: 10px 32px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; color: var(--text); cursor: pointer; background-color: #f8fafc; appearance: none; background-image: url('data:image/svg+xml,%3Csvg fill=\'none\' stroke=\'%2394a3b8\' viewBox=\'0 0 24 24\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'%3E%3C/path%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px; transition: all 0.2s; }
        .filter-select:focus { outline: none; border-color: var(--primary); }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 14px 20px; font-size: 0.65rem; text-transform: uppercase; color: var(--muted); font-weight: 800; border-bottom: 1px solid var(--border); letter-spacing: 0.05em; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text); vertical-align: middle; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
        .badge-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-activo { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-activo::before { background: #16a34a; }
        .status-inactivo { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .status-inactivo::before { background: #94a3b8; }
        
        .plan-tag { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid #e2e8f0;}
        .plan-tag.basic { background: #e0f2fe; color: #2563eb; border-color: #bfdbfe; }
        .plan-tag.pro { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-color: rgba(255, 138, 31, 0.2);}
        .plan-tag.enterprise { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);}
        .plan-tag.none { background: #f8fafc; color: #94a3b8; border-style: dashed; }

        .no-results { text-align: center; padding: 40px; color: var(--muted); font-style: italic; display: none; }

        @media (max-width: 1024px) { .plans-grid { grid-template-columns: repeat(2, 1fr); } .plan-card.popular { transform: none; } .plan-card.popular:hover { transform: translateY(-5px); } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 20px 16px; }
            .plans-grid { grid-template-columns: 1fr; }
            .table-wrapper { overflow-x: auto; } table { min-width: 800px; }
            .table-tools { width: 100%; }
            .search-box { width: 100%; }
            .filter-select { width: 100%; }
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
                    <h1 class="welcome-title">Gestión de Planes</h1>
                    <p class="welcome-text">Administra los paquetes de suscripción y revisa el estado de las empresas.</p>
                </div>
            </div>

            <div class="plans-grid">
                
                <?php foreach ($planes_info as $plan): 
                    $tiene_descuento = ($plan['precio_descuento'] > 0 && $plan['precio_descuento'] < $plan['precio_normal']);
                    $precio_final = $tiene_descuento ? $plan['precio_descuento'] : $plan['precio_normal'];
                ?>
                    <div class="plan-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                        <?php if ($plan['popular']): ?>
                            <div class="popular-badge">Más Popular</div>
                        <?php endif; ?>
                        
                        <div class="plan-header">
                            <h3 class="plan-title"><?php echo htmlspecialchars($plan['nombre']); ?></h3>
                            <div class="price-container">
                                <?php if ($tiene_descuento): ?>
                                    <p class="plan-price-old">$<?php echo number_format($plan['precio_normal'], 0, ',', '.'); ?></p>
                                <?php else: ?>
                                    <p class="plan-price-old" style="visibility:hidden;">$0</p>
                                <?php endif; ?>
                                
                                <p class="plan-price">$<?php echo number_format($precio_final, 0, ',', '.'); ?><span>/mes</span></p>
                            </div>
                        </div>
                        
                        <ul class="plan-features">
                            <li>
                                <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> 
                                <?php echo $plan['trabajadores'] == 999 ? 'Trabajadores Ilimitados' : 'Hasta ' . $plan['trabajadores'] . ' Trabajadores'; ?>
                            </li>

                            <?php foreach ($plan['features'] as $f): ?>
                                <?php if ($f['incluido']): ?>
                                    <li><svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> <?php echo htmlspecialchars($f['texto']); ?></li>
                                <?php else: ?>
                                    <li class="disabled"><svg class="cross" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg> <?php echo htmlspecialchars($f['texto']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        
                        <button class="btn-plan <?php echo $plan['clase_btn']; ?>" 
                                onclick="abrirModalPlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['nombre'], ENT_QUOTES); ?>', <?php echo $plan['trabajadores']; ?>, <?php echo $plan['precio_normal']; ?>, <?php echo $plan['precio_descuento']; ?>, '<?php echo htmlspecialchars(json_encode($plan['features']), ENT_QUOTES, 'UTF-8'); ?>')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Editar Plan
                        </button>
                    </div>
                <?php endforeach; ?>

            </div>

            <div class="section-header">
                <h2>Suscripciones de Empresas</h2>
                
                <div class="table-tools">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="searchTable" placeholder="Buscar por nombre de empresa...">
                    </div>
                    
                    <select id="filterPlan" class="filter-select">
                        <option value="todos">Todos los Planes</option>
                        <?php foreach ($planes_db as $p): ?>
                            <option value="<?php echo strtolower($p['nombre']); ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                        <?php endforeach; ?>
                        <option value="sin plan">Sin Plan Asignado</option>
                    </select>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Plan Actual</th>
                            <th>Estado</th>
                            <th>Próximo Pago</th>
                        </tr>
                    </thead>
                    <tbody id="suscripcionesBody">
                        <?php if (empty($suscripciones)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--muted); font-style: italic;">No hay empresas aprobadas aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($suscripciones as $sus): 
                                $clase_plan = 'basic';
                                if (stripos(strtolower($sus['plan']), 'pro') !== false) {
                                    $clase_plan = 'pro';
                                } elseif (stripos(strtolower($sus['plan']), 'enterprise') !== false) {
                                    $clase_plan = 'enterprise';
                                } elseif (strtolower($sus['plan']) === 'sin plan') {
                                    $clase_plan = 'none';
                                }
                            ?>
                            <tr class="sus-row" data-empresa="<?php echo strtolower($sus['empresa']); ?>" data-plan="<?php echo strtolower($sus['plan']); ?>">
                                <td style="font-weight: 600; color: var(--blue-dark);"><?php echo $sus['empresa']; ?></td>
                                <td><span class="plan-tag <?php echo $clase_plan; ?>"><?php echo $sus['plan']; ?></span></td>
                                <td>
                                    <?php if($sus['estado'] === 'activo'): ?>
                                        <span class="badge-status status-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge-status status-inactivo">Incompleto</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--muted); font-size: 0.8rem; font-family: monospace;">
                                    <?php echo $sus['vencimiento'] !== 'N/A' ? date('d/m/Y', strtotime($sus['vencimiento'])) : 'N/A'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="no-results" id="noResultsMsg">No se encontraron suscripciones con esos filtros.</div>
            </div>

        </div>
    </main>

    <?php include '../components/modal_editar_plan.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchTable');
            const filterSelect = document.getElementById('filterPlan');
            const rows = document.querySelectorAll('.sus-row');
            const noResultsMsg = document.getElementById('noResultsMsg');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterPlan = filterSelect.value.toLowerCase();
                let visibleCount = 0;

                rows.forEach(row => {
                    const empresaName = row.getAttribute('data-empresa');
                    const planName = row.getAttribute('data-plan');

                    const matchesSearch = empresaName.includes(searchTerm);
                    const matchesPlan = (filterPlan === 'todos') || (planName.includes(filterPlan));

                    if (matchesSearch && matchesPlan) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Mostrar mensaje si no hay resultados
                if (visibleCount === 0 && rows.length > 0) {
                    noResultsMsg.style.display = 'block';
                } else {
                    noResultsMsg.style.display = 'none';
                }
            }

            if (searchInput) searchInput.addEventListener('input', filterTable);
            if (filterSelect) filterSelect.addEventListener('change', filterTable);
        });
    </script>

</body>
</html>