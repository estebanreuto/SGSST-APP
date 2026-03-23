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

/* DATOS DE SUSCRIPCIONES (Aún simulados) */
$suscripciones = [
    ['empresa' => 'Constructora Vertix S.A.S', 'plan' => 'Pro SG-SST', 'estado' => 'activo', 'vencimiento' => '2026-10-15'],
    ['empresa' => 'Logística Global Ltda', 'plan' => 'Enterprise', 'estado' => 'activo', 'vencimiento' => '2025-12-01'],
];
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
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-header h2 { font-size: 1.1rem; color: var(--text); font-weight: 800; margin: 0; }
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
        .status-inactivo { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-inactivo::before { background: #dc2626; }
        .plan-tag { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid #e2e8f0;}
        .plan-tag.pro { background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-color: rgba(255, 138, 31, 0.2);}
        .plan-tag.enterprise { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);}
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .btn-icon:hover { background: #3b82f6; color: white; }
        @media (max-width: 1024px) { .plans-grid { grid-template-columns: repeat(2, 1fr); } .plan-card.popular { transform: none; } .plan-card.popular:hover { transform: translateY(-5px); } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 20px 16px; }
            .plans-grid { grid-template-columns: 1fr; }
            .table-wrapper { overflow-x: auto; } table { min-width: 800px; }
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
                    <p class="welcome-text">Administra los paquetes de suscripción y sus características dinámicas.</p>
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
                    <tbody>
                        <?php foreach ($suscripciones as $sus): 
                            $clase_plan = strpos(strtolower($sus['plan']), 'pro') !== false ? 'pro' : (strtolower($sus['plan']) === 'enterprise' ? 'enterprise' : '');
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--blue-dark);"><?php echo $sus['empresa']; ?></td>
                            <td><span class="plan-tag <?php echo $clase_plan; ?>"><?php echo $sus['plan']; ?></span></td>
                            <td><span class="badge-status status-<?php echo $sus['estado']; ?>"><?php echo $sus['estado']; ?></span></td>
                            <td style="color: var(--muted); font-size: 0.8rem;"><?php echo date('d/m/Y', strtotime($sus['vencimiento'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?php include '../components/modal_editar_plan.php'; ?>

</body>
</html>