<?php
require_once 'config/db.php';
require_once 'config/auth.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$submodulos = [
    'sociodemografica' => [
        'codigo' => '3.1.1',
        'titulo' => 'Descripción sociodemográfica - Diagnóstico de condiciones de salud',
        'descripcion' => 'Base para consolidar información poblacional y hallazgos generales de salud.',
        'icono' => 'fa-users-viewfinder',
    ],
    'promocion-prevencion' => [
        'codigo' => '3.1.2',
        'titulo' => 'Actividades de Promoción y Prevención en Salud',
        'descripcion' => 'Planificación y seguimiento de acciones preventivas derivadas del diagnóstico.',
        'icono' => 'fa-heart-pulse',
    ],
    'perfiles-cargo' => [
        'codigo' => '3.1.3',
        'titulo' => 'Información al médico de los perfiles de cargo',
        'descripcion' => 'Insumos del cargo, peligros y funciones para orientar los exámenes médicos.',
        'icono' => 'fa-user-doctor',
    ],
    'evaluaciones-medicas' => [
        'codigo' => '3.1.4',
        'titulo' => 'Realización de Evaluaciones Médicas Ocupacionales - Peligros - Periodicidad',
        'descripcion' => 'Control de ingreso, periódicos, egreso y periodicidad según exposición.',
        'icono' => 'fa-notes-medical',
    ],
    'historias-clinicas' => [
        'codigo' => '3.1.5',
        'titulo' => 'Custodia de Historias Clínicas',
        'descripcion' => 'Gestión de soporte y evidencia de custodia por entidad competente.',
        'icono' => 'fa-folder-lock',
    ],
    'restricciones' => [
        'codigo' => '3.1.6',
        'titulo' => 'Restricciones y recomendaciones médico/laborales',
        'descripcion' => 'Seguimiento ejecutivo de restricciones, recomendaciones y cierre de acciones.',
        'icono' => 'fa-clipboard-list',
    ],
];

$modulo_actual = $_GET['modulo'] ?? 'sociodemografica';
if (!isset($submodulos[$modulo_actual])) {
    $modulo_actual = 'sociodemografica';
}
$modulo = $submodulos[$modulo_actual];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 5 | Evaluaciones médicas ocupacionales</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#ff8a1f; --primary2:#ff7a00; --blue-dark:#1e3a8a; --bg1:#edf4fb; --bg2:#f7f9fc; --card:#fff; --text:#1f2d3d; --muted:#64748b; --border:#dbe3ec; --green:#16a34a; --amber:#d97706; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:Inter,sans-serif; background:linear-gradient(180deg,var(--bg1),var(--bg2)); color:var(--text); font-size:.82rem; }
        .main-wrapper { margin-left:260px; width:calc(100% - 260px); min-height:100vh; }
        .content-area { width:100%; padding:30px clamp(24px,2.5vw,44px) 60px; }
        .page-head { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:18px; }
        .head-copy { display:flex; align-items:center; gap:13px; min-width:0; }
        .head-icon { width:46px; height:46px; border-radius:10px; display:grid; place-items:center; color:var(--primary); background:#fff3e8; border:1px solid #fed7aa; font-size:1.05rem; flex:none; }
        .head-copy h1 { margin:0; color:var(--blue-dark); font-size:1.18rem; line-height:1.2; }
        .head-copy p { margin:5px 0 0; color:var(--muted); font-size:.76rem; line-height:1.35; }
        .status-pill { display:inline-flex; align-items:center; gap:7px; height:34px; padding:0 12px; border-radius:999px; border:1px solid #fed7aa; background:#fff7ed; color:#c2410c; font-weight:800; font-size:.68rem; text-transform:uppercase; white-space:nowrap; }
        .intro-panel { position:relative; overflow:hidden; background:#fff; border:1px solid var(--border); border-left:4px solid var(--primary2); border-radius:10px; padding:18px; margin-bottom:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .intro-panel::after { content:""; position:absolute; right:-52px; top:-74px; width:180px; height:180px; border-radius:50%; background:#fff3e8; opacity:.75; }
        .intro-content { position:relative; z-index:1; display:grid; grid-template-columns:minmax(250px,.85fr) minmax(300px,1.15fr); gap:18px; align-items:center; }
        .intro-kicker { color:var(--primary2); font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.02em; }
        .intro-content h2 { margin:5px 0 8px; color:var(--blue-dark); font-size:1.06rem; }
        .intro-content p { margin:0; color:var(--muted); line-height:1.45; font-size:.76rem; }
        .module-tabs { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .module-tab { display:flex; align-items:flex-start; gap:8px; min-width:0; padding:10px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; text-decoration:none; transition:border-color .2s ease, background .2s ease, transform .2s ease; }
        .module-tab:hover { border-color:#fdba74; background:#fff8f3; transform:translateY(-1px); }
        .module-tab.active { border-color:#fdba74; background:#fff3e8; color:#c2410c; }
        .module-tab strong { display:block; color:var(--blue-dark); font-size:.67rem; margin-bottom:2px; }
        .module-tab span { display:block; color:inherit; font-size:.64rem; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .workspace-grid { display:grid; grid-template-columns:minmax(280px,.78fr) minmax(360px,1.22fr); gap:14px; align-items:start; }
        .module-card, .detail-card { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .module-card { overflow:hidden; }
        .module-card-head { background:linear-gradient(135deg,#172554,#1e3a8a); color:#fff; padding:16px; min-height:145px; display:flex; flex-direction:column; justify-content:space-between; gap:18px; }
        .module-code { align-self:flex-start; display:inline-flex; align-items:center; gap:7px; padding:6px 9px; border-radius:7px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.16); font-size:.68rem; font-weight:850; }
        .module-card-head h2 { margin:0; font-size:1.02rem; line-height:1.25; }
        .module-card-body { padding:15px; display:grid; gap:12px; }
        .module-card-body p { margin:0; color:var(--muted); line-height:1.45; }
        .state-row { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .state-box { border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:10px; }
        .state-box span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .state-box strong { display:block; margin-top:4px; color:var(--blue-dark); font-size:.92rem; }
        .detail-card { padding:16px; }
        .detail-card h3 { margin:0; color:var(--blue-dark); font-size:.95rem; }
        .detail-card p { margin:5px 0 14px; color:var(--muted); line-height:1.45; }
        .detail-list { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
        .detail-list li { display:flex; align-items:flex-start; gap:9px; padding:11px; border:1px solid #eef2f7; border-radius:8px; background:#fbfdff; color:#475569; line-height:1.35; }
        .detail-list i { color:var(--primary2); margin-top:2px; }
        .notice { margin-top:14px; display:flex; align-items:flex-start; gap:9px; border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; border-radius:8px; padding:11px; font-size:.72rem; line-height:1.4; }
        @media (max-width: 980px) {
            .intro-content, .workspace-grid { grid-template-columns:1fr; }
            .module-tabs { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left:0; width:100%; }
            .content-area { padding:18px 14px 45px; }
            .page-head { align-items:flex-start; flex-direction:column; }
            .status-pill { align-self:flex-start; }
        }
        @media (max-width: 560px) {
            .module-tabs, .state-row { grid-template-columns:1fr; }
            .head-copy { align-items:flex-start; }
        }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <div class="page-head">
            <div class="head-copy">
                <div class="head-icon"><i class="fa-solid fa-briefcase-medical"></i></div>
                <div>
                    <h1>5. Evaluaciones médicas ocupacionales</h1>
                    <p>Estructura inicial de submódulos para organizar la gestión médica ocupacional.</p>
                </div>
            </div>
            <span class="status-pill"><i class="fa-solid fa-layer-group"></i> Vista modular</span>
        </div>

        <section class="intro-panel">
            <div class="intro-content">
                <div>
                    <div class="intro-kicker">Estándar 5</div>
                    <h2>Submódulos de trabajo</h2>
                    <p>Esta primera vista deja preparada la navegación. En el siguiente paso definimos qué datos arrastra cada módulo y qué registros debe mostrar según el rol.</p>
                </div>
                <div class="module-tabs" aria-label="Submódulos del estándar 5">
                    <?php foreach ($submodulos as $slug => $item): ?>
                        <a class="module-tab <?php echo $slug === $modulo_actual ? 'active' : ''; ?>" href="estandar5.php?modulo=<?php echo urlencode($slug); ?>">
                            <i class="fa-solid <?php echo htmlspecialchars($item['icono']); ?>"></i>
                            <span><strong><?php echo htmlspecialchars($item['codigo']); ?></strong><?php echo htmlspecialchars($item['titulo']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="workspace-grid">
            <article class="module-card">
                <div class="module-card-head">
                    <span class="module-code"><i class="fa-solid <?php echo htmlspecialchars($modulo['icono']); ?>"></i> <?php echo htmlspecialchars($modulo['codigo']); ?></span>
                    <h2><?php echo htmlspecialchars($modulo['titulo']); ?></h2>
                </div>
                <div class="module-card-body">
                    <p><?php echo htmlspecialchars($modulo['descripcion']); ?></p>
                    <div class="state-row">
                        <div class="state-box"><span>Estado</span><strong>Diseño</strong></div>
                        <div class="state-box"><span>Datos</span><strong>Pendiente</strong></div>
                        <div class="state-box"><span>Rol</span><strong><?php echo $usuario_rol === 'sst' ? 'Gestión SST' : 'Resumen'; ?></strong></div>
                    </div>
                </div>
            </article>

            <article class="detail-card">
                <h3>Base funcional del submódulo</h3>
                <p>Por ahora dejamos el contenedor visual listo para conectar datos y flujos cuando definas cómo debe operar cada sección.</p>
                <ul class="detail-list">
                    <li><i class="fa-solid fa-check"></i><span>Acceso desde el menú lateral y desde los botones internos del estándar 5.</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Selección del submódulo actual sin perder la estructura general de la página.</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Diseño responsive para escritorio, portátil y celular.</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Preparado para separar vista de gestión del Responsable SST y vista resumen del Representante Legal.</span></li>
                </ul>
                <div class="notice">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>No se creó ni modificó ninguna tabla todavía. Cuando conectemos datos, dejaré el SQL correspondiente en un archivo de migración.</span>
                </div>
            </article>
        </section>
    </div>
</main>
</body>
</html>
