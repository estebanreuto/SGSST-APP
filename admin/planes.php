<?php
session_start();
require_once '../config/db.php';
require_once '../config/storage_schema.php';

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
ensure_storage_schema($conn);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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
           TARJETAS DE PLANES (CON MARCA DE AGUA)
           ========================================= */
        .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; align-items: stretch; }
        
        .plan-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 16px; 
            padding: 32px 24px 24px 24px; display: flex; flex-direction: column; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: transform 0.3s ease, box-shadow 0.3s ease; 
            position: relative; overflow: hidden; z-index: 1;
        }
        .plan-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #cbd5e1;}
        
        /* Estilos específicos para el plan Popular */
        .plan-card.popular { 
            border: 1px solid var(--primary); 
            box-shadow: 0 8px 25px rgba(255, 138, 31, 0.08); 
            transform: translateY(-2px); z-index: 2; 
        }
        .plan-card.popular:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(255, 138, 31, 0.12); }
        
        .popular-badge { 
            position: absolute; top: -1px; left: 50%; transform: translateX(-50%); 
            background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; 
            padding: 4px 16px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; 
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; 
            box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); z-index: 3;
        }

        /* Marca de agua FontAwesome */
        .plan-watermark { 
            position: absolute; right: -20px; bottom: -30px; font-size: 160px; 
            line-height: 1; opacity: 0.03; transform: rotate(-15deg); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; z-index: 0;
        }
        .plan-card:hover .plan-watermark { transform: rotate(0deg) scale(1.1); opacity: 0.06; }
        
        /* Colores de marca de agua por tipo de plan (Asignados dinámicamente) */
        .watermark-blue { color: #3b82f6; }
        .watermark-orange { color: var(--primary); }
        .watermark-purple { color: #8b5cf6; }

        /* Contenido de la tarjeta */
        .plan-content-wrapper { position: relative; z-index: 2; display: flex; flex-direction: column; height: 100%;}
        
        .plan-header { text-align: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .plan-title { font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0; }
        .popular .plan-title { color: var(--primary); }
        
        .price-container { display: flex; flex-direction: column; align-items: center; gap: 2px; }
        .plan-price-old { font-size: 0.85rem; color: #94a3b8; text-decoration: line-through; font-weight: 600; margin: 0; height: 16px; }
        .plan-price { font-size: 2.2rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; display: flex; justify-content: center; align-items: baseline; gap: 4px;}
        .plan-price span { font-size: 0.85rem; color: var(--muted); font-weight: 600; }
        
        .plan-features { list-style: none; padding: 0; margin: 0 0 24px 0; flex: 1; display: flex; flex-direction: column; gap: 12px; }
        .plan-features li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.8rem; color: #334155; font-weight: 500; line-height: 1.4;}
        .plan-features li i.fa-check { color: #10b981; flex-shrink: 0; margin-top: 2px; font-size: 0.9rem;}
        .plan-features li.disabled { color: #94a3b8; text-decoration: line-through; }
        .plan-features li.disabled i.fa-xmark { color: #cbd5e1; flex-shrink: 0; margin-top: 2px; font-size: 0.9rem;}
        
        .btn-plan { 
            width: 100%; padding: 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; 
            text-align: center; cursor: pointer; transition: all 0.2s; border: none; 
            display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: auto;
        }
        .btn-outline { background: #f8fafc; color: var(--text); border: 1px solid #cbd5e1; }
        .btn-outline:hover { background: #f1f5f9; border-color: #94a3b8; }
        .btn-solid { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); }
        .btn-solid:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.3); }
        
        /* =========================================
           BARRA DE HERRAMIENTAS Y TABLA
           ========================================= */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 16px; }
        .section-header h2 { font-size: 1rem; color: var(--blue-dark); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px;}
        
        .table-tools { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; background: #f8fafc; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        
        .filter-select { 
            padding: 10px 32px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; color: var(--text); 
            cursor: pointer; background-color: #f8fafc; appearance: none; 
            background-image: url('data:image/svg+xml,%3Csvg fill=\'none\' stroke=\'%2394a3b8\' viewBox=\'0 0 24 24\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'%3E%3C/path%3E%3C/svg%3E'); 
            background-repeat: no-repeat; background-position: right 12px center; background-size: 16px; transition: all 0.2s; 
        }
        .filter-select:focus { outline: none; border-color: var(--primary); }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        
        .modern-table { width: 100%; border-collapse: collapse; text-align: left; }
        .modern-table th { background: #f8fafc; padding: 12px 16px; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.05em; }
        .modern-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; color: #334155; vertical-align: middle;}
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tr:hover td { background: #f8fafc; }
        
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.03em;}
        .status-activo { background: #dcfce7; color: #166534; }
        .status-inactivo { background: #f1f5f9; color: #64748b; }
        
        .plan-tag { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #e2e8f0; display: inline-block; text-transform: uppercase; letter-spacing: 0.03em;}
        .plan-tag.basic { background: #e0f2fe; color: #2563eb; border-color: #bfdbfe; }
        .plan-tag.pro { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-color: rgba(255, 138, 31, 0.2);}
        .plan-tag.enterprise { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);}
        .plan-tag.none { background: #f8fafc; color: #94a3b8; border-style: dashed; }

        .client-avatar { 
            width: 28px; height: 28px; border-radius: 6px; 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.05)); 
            color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.15);
            display: inline-flex; align-items: center; justify-content: center; 
            font-weight: 800; font-size: 0.8rem; margin-right: 10px; vertical-align: middle; 
        }

        .no-results { text-align: center; padding: 40px; color: var(--muted); font-style: italic; display: none; }

        @media (max-width: 1024px) { .plans-grid { grid-template-columns: repeat(2, 1fr); } .plan-card.popular { transform: none; } .plan-card.popular:hover { transform: translateY(-4px); } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 16px; }
            .estandar-header-group { flex-direction: column; align-items: flex-start; gap: 12px; }
            .plans-grid { grid-template-columns: 1fr; }
            .table-wrapper { overflow-x: auto; } .modern-table { min-width: 700px; }
            .table-tools { width: 100%; } .search-box { width: 100%; } .filter-select { width: 100%; }
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
                        <i class="fa-solid fa-tags" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Gestión de Planes</h1>
                        <p class="estandar-subtitle">Administra los paquetes de suscripción y revisa el estado de las empresas.</p>
                    </div>
                </div>
            </div>

            <div class="plans-grid">
                
                <?php foreach ($planes_info as $plan): 
                    $tiene_descuento = ($plan['precio_descuento'] > 0 && $plan['precio_descuento'] < $plan['precio_normal']);
                    $precio_final = $tiene_descuento ? $plan['precio_descuento'] : $plan['precio_normal'];
                    
                    // Asignación de icono y color dinámico según nombre del plan
                    $watermark_icon = 'fa-paper-plane';
                    $watermark_color = 'watermark-blue';
                    if (stripos(strtolower($plan['nombre']), 'mem') !== false || stripos(strtolower($plan['nombre']), 'pro') !== false) {
                        $watermark_icon = 'fa-rocket';
                        $watermark_color = 'watermark-orange';
                    } elseif (stripos(strtolower($plan['nombre']), 'gem') !== false || stripos(strtolower($plan['nombre']), 'enterprise') !== false) {
                        $watermark_icon = 'fa-crown';
                        $watermark_color = 'watermark-purple';
                    }
                ?>
                    <div class="plan-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                        
                        <i class="fa-solid <?php echo $watermark_icon; ?> plan-watermark <?php echo $watermark_color; ?>"></i>

                        <?php if ($plan['popular']): ?>
                            <div class="popular-badge">Más Popular</div>
                        <?php endif; ?>
                        
                        <div class="plan-content-wrapper">
                            <div class="plan-header">
                                <h3 class="plan-title"><?php echo htmlspecialchars($plan['nombre']); ?></h3>
                                <div class="price-container">
                                    <?php if ($tiene_descuento): ?>
                                        <p class="plan-price-old">$<?php echo number_format($plan['precio_normal'], 0, ',', '.'); ?></p>
                                    <?php else: ?>
                                        <p class="plan-price-old" style="visibility:hidden;">$0</p>
                                    <?php endif; ?>
                                    
                                    <p class="plan-price">$<?php echo number_format($precio_final, 0, ',', '.'); ?><span>/anual</span></p>
                                </div>
                            </div>
                            
                            <ul class="plan-features">
                                <li>
                                    <i class="fa-solid fa-check"></i>
                                    <?php echo $plan['trabajadores'] == 999 ? 'Trabajadores Ilimitados' : 'Hasta ' . $plan['trabajadores'] . ' Trabajadores'; ?>
                                </li>
                                <li>
                                    <i class="fa-solid fa-cloud"></i>
                                    <?php echo number_format((float)$plan['almacenamiento_gb'], 0, ',', '.'); ?> GB de almacenamiento documental
                                </li>

                                <?php foreach ($plan['features'] as $f): ?>
                                    <?php if ($f['incluido']): ?>
                                        <li><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($f['texto']); ?></li>
                                    <?php else: ?>
                                        <li class="disabled"><i class="fa-solid fa-xmark"></i> <?php echo htmlspecialchars($f['texto']); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            
                            <button class="btn-plan <?php echo $plan['clase_btn'] === 'btn-solid' ? 'btn-solid' : 'btn-outline'; ?>" 
                                    onclick="abrirModalPlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['nombre'], ENT_QUOTES); ?>', <?php echo $plan['trabajadores']; ?>, <?php echo (float)$plan['almacenamiento_gb']; ?>, <?php echo $plan['precio_normal']; ?>, <?php echo $plan['precio_descuento']; ?>, '<?php echo htmlspecialchars(json_encode($plan['features']), ENT_QUOTES, 'UTF-8'); ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Editar Plan
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <div class="section-header">
                <h2><i class="fa-solid fa-building-user" style="color: var(--primary); margin-right: 8px;"></i> Suscripciones de Empresas</h2>
                
                <div class="table-tools">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
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
                <table class="modern-table">
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
                            <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8; font-style: italic;">No hay empresas aprobadas aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($suscripciones as $sus): 
                                $clase_plan = 'basic';
                                if (stripos(strtolower($sus['plan']), 'mem') !== false || stripos(strtolower($sus['plan']), 'pro') !== false) {
                                    $clase_plan = 'pro';
                                } elseif (stripos(strtolower($sus['plan']), 'gem') !== false || stripos(strtolower($sus['plan']), 'enterprise') !== false) {
                                    $clase_plan = 'enterprise';
                                } elseif (strtolower($sus['plan']) === 'sin plan') {
                                    $clase_plan = 'none';
                                }
                            ?>
                            <tr class="sus-row" data-empresa="<?php echo strtolower($sus['empresa']); ?>" data-plan="<?php echo strtolower($sus['plan']); ?>">
                                <td style="font-weight: 600;">
                                    <div class="client-avatar">
                                        <?php echo strtoupper(substr($sus['empresa'], 0, 1)); ?>
                                    </div>
                                    <?php echo $sus['empresa']; ?>
                                </td>
                                <td><span class="plan-tag <?php echo $clase_plan; ?>"><?php echo $sus['plan']; ?></span></td>
                                <td>
                                    <?php if($sus['estado'] === 'activo'): ?>
                                        <span class="badge-status status-activo"><i class="fa-solid fa-circle-check" style="margin-right:2px;"></i> Activo</span>
                                    <?php else: ?>
                                        <span class="badge-status status-inactivo"><i class="fa-solid fa-circle-exclamation" style="margin-right:2px;"></i> Incompleto</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #64748b; font-size: 0.8rem; font-family: monospace; font-weight: 600;">
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
