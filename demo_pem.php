<?php
session_start();

require_once 'config/db.php';
require_once 'config/demo_schema.php';

ensure_demo_prospectos_schema($conn);
$plainAccessToken = trim((string)($_GET['access'] ?? ''));
if ($plainAccessToken !== '') {
    if (!preg_match('/^[a-f0-9]{64}$/i', $plainAccessToken)) {
        $_SESSION['demo_error'] = 'El enlace de acceso no es válido.';
        header('Location: demo?acceso=invalido');
        exit;
    }
    $tokenHash = hash('sha256', $plainAccessToken);
    $stmtAccess = $conn->prepare(<<<'SQL'
        SELECT id, nombre_completo, empresa
        FROM demo_prospectos
        WHERE acceso_token_hash = ?
          AND acceso_estado = 'aprobado'
          AND acceso_revocado_en IS NULL
          AND acceso_expira_en IS NOT NULL
          AND acceso_expira_en > NOW()
        LIMIT 1
    SQL);
    $stmtAccess->execute([$tokenHash]);
    $approvedLead = $stmtAccess->fetch(PDO::FETCH_ASSOC);
    if (!$approvedLead) {
        $_SESSION['demo_error'] = 'Este enlace venció, fue revocado o todavía no está aprobado.';
        header('Location: demo?acceso=no-disponible');
        exit;
    }
    session_regenerate_id(true);
    $_SESSION['demo_pem_prospecto_id'] = (int)$approvedLead['id'];
    $_SESSION['demo_pem_access_hash'] = $tokenHash;
    $_SESSION['demo_pem_started_at'] = time();
    $_SESSION['demo_pem_role'] = 'sst';
    header('Location: demo_pem');
    exit;
}

$leadId = (int)($_SESSION['demo_pem_prospecto_id'] ?? 0);
$startedAt = (int)($_SESSION['demo_pem_started_at'] ?? 0);
$sessionAccessHash = (string)($_SESSION['demo_pem_access_hash'] ?? '');
$stmtSession = $conn->prepare(<<<'SQL'
    SELECT id, nombre_completo, empresa
    FROM demo_prospectos
    WHERE id = ? AND acceso_token_hash = ? AND acceso_estado = 'aprobado'
      AND acceso_revocado_en IS NULL AND acceso_expira_en > NOW()
    LIMIT 1
SQL);
$stmtSession->execute([$leadId, $sessionAccessHash]);
$activeLead = $stmtSession->fetch(PDO::FETCH_ASSOC);
if (!$activeLead || $leadId < 1 || $startedAt < 1 || (time() - $startedAt) >= 43200) {
    unset(
        $_SESSION['demo_pem_prospecto_id'], $_SESSION['demo_pem_started_at'],
        $_SESSION['demo_pem_access_hash'], $_SESSION['demo_pem_role']
    );
    $_SESSION['demo_error'] = 'Necesitas un enlace aprobado por PreventWork para abrir la demostración.';
    header('Location: demo?acceso=requerido');
    exit;
}

$stmtVisit = $conn->prepare('UPDATE demo_prospectos SET paginas_vistas = paginas_vistas + 1, ultima_visita = NOW() WHERE id = ?');
$stmtVisit->execute([$leadId]);
$visitorName = trim((string)$activeLead['nombre_completo']);
$visitorCompany = trim((string)$activeLead['empresa']);

$standards = [
    'estandar1' => [
        'number' => 1, 'code' => '1.1.1', 'icon' => 'fa-user-shield',
        'title' => 'Asignación de la persona que diseña el SG-SST',
        'summary' => 'Responsable, formación, licencia y evidencia de designación.', 'progress' => 100,
        'metrics' => [['Responsables','1','Designación vigente'],['Documentos','4','Soportes completos'],['Vencimientos','0','Sin alertas'],['Estado','Cumple','Validado']],
        'items' => [
            ['Designación del responsable SST','Laura Marcela Gómez · Profesional SST','Vigente',100,'Carta firmada el 12/01/2026 · Licencia SST vigente hasta 2028.'],
            ['Curso obligatorio de 50 horas','Certificado registrado y verificado','Completo',100,'Certificado emitido por entidad autorizada · 50 horas aprobadas.'],
            ['Responsabilidades asignadas','Manual y alcance del cargo','Completo',100,'Diseño, implementación, seguimiento e informes a la alta dirección.'],
        ],
    ],
    'estandar2' => [
        'number' => 2, 'code' => '1.1.4', 'icon' => 'fa-shield-heart',
        'title' => 'Afiliación al Sistema de Seguridad Social Integral',
        'summary' => 'Control de afiliaciones a EPS, pensión, ARL y caja de compensación.', 'progress' => 96,
        'metrics' => [['Trabajadores','24','Personal activo'],['Afiliados','23','Cobertura completa'],['Pendientes','1','Requiere gestión'],['Cobertura','96%','Resultado actual']],
        'items' => [
            ['Personal con afiliación completa','23 de 24 trabajadores','Al día',96,'EPS, AFP, ARL y caja de compensación verificadas.'],
            ['Novedad pendiente','Carlos Ramírez · Operaciones','Revisar',45,'Pendiente soporte de traslado de EPS. Responsable: Gestión Humana.'],
            ['Clasificación del riesgo ARL','Centro de trabajo principal','Actualizado',100,'Clases de riesgo configuradas según cargos y actividades.'],
        ],
    ],
    'estandar3' => [
        'number' => 3, 'code' => '1.2.1', 'icon' => 'fa-graduation-cap',
        'title' => 'Capacitación en Seguridad y Salud en el Trabajo',
        'summary' => 'Plan de formación, asistencia, evaluaciones y evidencias.', 'progress' => 82,
        'metrics' => [['Actividades','12','Plan anual'],['Ejecutadas','9','Con evidencia'],['Programadas','3','Próximas'],['Participación','88%','Promedio anual']],
        'items' => [
            ['Inducción y reinducción SG-SST','24 trabajadores convocados','Ejecutada',100,'23 asistentes · Evaluación promedio 92% · Acta y registro fotográfico.'],
            ['Prevención del riesgo biomecánico','Área administrativa','Ejecutada',100,'Taller práctico, pausas activas y lista de asistencia digital.'],
            ['Manejo seguro de herramientas','Área de mantenimiento','Programada',35,'Fecha: 22/08/2026 · Responsable: Coordinador operativo.'],
        ],
    ],
    'estandar4' => [
        'number' => 4, 'code' => '2.4.1', 'icon' => 'fa-calendar-check',
        'title' => 'Plan Anual de Trabajo',
        'summary' => 'Programación, responsables, recursos, seguimiento y firmas.', 'progress' => 74,
        'metrics' => [['Actividades','32','Planeadas'],['Terminadas','21','Cerradas'],['En proceso','7','Con seguimiento'],['Avance','74%','Ejecución anual']],
        'items' => [
            ['Inspecciones planeadas','8 inspecciones durante el año','En proceso',75,'6 ejecutadas · 2 programadas · Responsable COPASST.'],
            ['Programa de capacitación','12 actividades de formación','En proceso',82,'9 ejecutadas · 3 programadas · Presupuesto controlado.'],
            ['Revisión por la dirección','Informe y acta anual','Programada',20,'Programada para diciembre · Requiere cierre de indicadores.'],
        ],
    ],
    'estandar5' => [
        'number' => 5, 'code' => '3.1', 'icon' => 'fa-notes-medical',
        'title' => 'Evaluaciones médicas ocupacionales',
        'summary' => 'Perfiles de cargo, exámenes, historias clínicas y restricciones.', 'progress' => 79,
        'submodules' => ['3.1.1 Sociodemográfica','3.1.2 Promoción y prevención','3.1.3 Perfiles de cargo','3.1.4 Evaluaciones médicas','3.1.5 Historias clínicas','3.1.6 Restricciones'],
        'metrics' => [['Trabajadores','24','Controlados'],['Exámenes vigentes','19','Con soporte'],['Programados','4','Solicitudes activas'],['Alertas','1','Próximo a vencer']],
        'items' => [
            ['Programación de evaluaciones','Ingreso, periódicos y retiro','En proceso',79,'19 vigentes · 4 solicitudes enviadas · 1 pendiente de soporte.'],
            ['Perfiles de cargo médicos','8 perfiles documentados','Completo',100,'Peligros, tareas críticas, herramientas y exámenes requeridos.'],
            ['Restricciones y recomendaciones','2 casos con seguimiento activo','Seguimiento',65,'Cartas generadas, compromisos y fechas de revisión registradas.'],
        ],
    ],
    'estandar6' => [
        'number' => 6, 'code' => '4.1', 'icon' => 'fa-triangle-exclamation',
        'title' => 'Identificación de peligros y valoración de riesgos',
        'summary' => 'Matriz IPVR, riesgo inicial, controles y valoración residual.', 'progress' => 86,
        'metrics' => [['Peligros','18','Identificados'],['Actividades','7','Analizadas'],['Riesgos altos','2','Antes del control'],['Residuales altos','0','Después del control']],
        'items' => [
            ['Trabajo en alturas','Caída a diferente nivel','Controlado',86,'Riesgo inicial II · Controles de ingeniería, administrativos y EPP · Residual III.'],
            ['Manipulación manual de cargas','Biomecánico · Sobreesfuerzo','Mejorable',72,'Ayudas mecánicas, capacitación y rotación de tareas implementadas.'],
            ['Ruido ocupacional','Físico · Exposición en mantenimiento','Controlado',90,'Medición higiénica, mantenimiento preventivo y protección auditiva.'],
        ],
    ],
    'estandar7' => [
        'number' => 7, 'code' => '4.2 / 5.1', 'icon' => 'fa-shield-halved',
        'title' => 'Medidas de prevención y control',
        'summary' => 'Recursos, mantenimiento, EPP, emergencias e inspecciones.', 'progress' => 77,
        'submodules' => ['Recursos SG-SST','Mantenimiento','Entrega de EPP','Plan de emergencias','Brigada','Mediciones ambientales','Verificación','Procedimientos','Inspecciones'],
        'metrics' => [['Frentes','9','Subestándares'],['Controles activos','14','En seguimiento'],['Evidencias','28','Documentos'],['Avance','77%','Ejecución general']],
        'items' => [
            ['Entrega y control de EPP','24 trabajadores con trazabilidad','Al día',92,'Entregas, reposiciones, firma digital y verificación de uso.'],
            ['Mantenimiento preventivo','11 equipos registrados','En proceso',78,'8 mantenimientos ejecutados · 3 próximos · Sin equipos vencidos.'],
            ['Plan de emergencias y brigada','Documentos, recursos y formación','En proceso',70,'Plan actualizado · 8 brigadistas · Simulacro programado para octubre.'],
        ],
    ],
];

$allowedViews = array_merge(['dashboard', 'personal'], array_keys($standards));
$view = (string)($_GET['vista'] ?? 'dashboard');
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}
$demoRoles = [
    'sst' => [
        'label' => 'Responsable SST',
        'title' => 'Panel ejecutivo de la organización',
        'description' => 'Prioriza el trabajo operativo, gestiona evidencias y mantiene la trazabilidad de los estándares mínimos.',
        'icon' => 'fa-user-shield',
    ],
    'representante' => [
        'label' => 'Representante legal',
        'title' => 'Resumen gerencial del SG-SST',
        'description' => 'Consulta indicadores, alertas, avances y decisiones que requieren seguimiento de la alta dirección.',
        'icon' => 'fa-building-shield',
    ],
    'trabajador' => [
        'label' => 'Trabajador',
        'title' => 'Mi espacio de Seguridad y Salud',
        'description' => 'Revisa tus capacitaciones, evaluaciones médicas, entregas, compromisos y documentos asignados.',
        'icon' => 'fa-user',
    ],
];
$requestedRole = (string)($_GET['rol'] ?? ($_SESSION['demo_pem_role'] ?? 'sst'));
if (!isset($demoRoles[$requestedRole])) {
    $requestedRole = 'sst';
}
$_SESSION['demo_pem_role'] = $requestedRole;
$demoRole = $requestedRole;
$demoRoleInfo = $demoRoles[$demoRole];

$workers = [
    ['Esteban Reuto','Tecnología','Operativo','Activo','98%'],
    ['Andrea Rodríguez','Coordinación logística','Administrativo','Activo','92%'],
    ['Juan Carlos Pérez','Técnico de mantenimiento','Operativo','Activo','86%'],
    ['María Fernanda López','Gestión humana','Administrativo','Activo','100%'],
    ['Carlos Ramírez','Auxiliar de operaciones','Operativo','Seguimiento','71%'],
    ['Laura Martínez','Servicios generales','Operativo','Activo','90%'],
];

function demo_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function demo_link(string $view, string $role): string
{
    return 'demo_pem?vista=' . rawurlencode($view) . '&rol=' . rawurlencode($role);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo PEM | PreventWork</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/demo-pem.css?v=20260716-1">
    <link rel="stylesheet" href="assets/demo-pem-project.css?v=20260716-1">
</head>
<body>
<?php include_once __DIR__ . '/components/public_page_loader.php'; ?>
<div class="demo-overlay" id="demoOverlay"></div>
<aside class="demo-sidebar" id="demoSidebar">
    <div class="demo-brand"><img src="assets/logo_preventwork.png" alt="PreventWork"><button type="button" id="closeDemoMenu" aria-label="Cerrar menú"><i class="fa-solid fa-xmark"></i></button></div>
    <nav class="demo-nav">
        <span class="demo-nav-label">Vista <?php echo demo_h($demoRoleInfo['label']); ?></span>
        <a href="<?php echo demo_h(demo_link('dashboard', $demoRole)); ?>" class="demo-nav-link <?php echo $view === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-table-cells-large"></i><span><?php echo $demoRole === 'trabajador' ? 'Mi panel' : 'Panel ejecutivo'; ?></span></a>
        <a href="<?php echo demo_h(demo_link('personal', $demoRole)); ?>" class="demo-nav-link <?php echo $view === 'personal' ? 'active' : ''; ?>"><i class="fa-solid fa-user-group"></i><span><?php echo $demoRole === 'trabajador' ? 'Mi perfil' : 'Personal'; ?></span></a>
        <span class="demo-nav-label">Estándares PEM</span>
        <?php foreach ($standards as $slug => $standard): ?>
            <a href="<?php echo demo_h(demo_link($slug, $demoRole)); ?>" class="demo-nav-link <?php echo $view === $slug ? 'active' : ''; ?>"><i class="fa-solid <?php echo demo_h($standard['icon']); ?>"></i><span><small>Estándar <?php echo (int)$standard['number']; ?></small><?php echo demo_h($standard['title']); ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="demo-session-card">
        <div class="demo-session-user"><span><?php echo demo_h(mb_strtoupper(mb_substr($visitorName, 0, 1))); ?></span><div><strong><?php echo demo_h($visitorName); ?></strong><small><?php echo demo_h($visitorCompany); ?></small></div></div>
        <div class="demo-storage"><div><span>Almacenamiento demo</span><strong>8,4 GB de 30 GB</strong></div><div class="demo-storage-track"><i style="width:28%"></i></div></div>
        <a href="index.php#planes"><i class="fa-solid fa-crown"></i> Ver membresías</a>
        <a href="cerrar_demo.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Salir de la demo</a>
    </div>
</aside>

<main class="demo-main">
    <header class="demo-topbar">
        <button type="button" class="demo-menu-btn" id="openDemoMenu" aria-label="Abrir menú"><i class="fa-solid fa-bars"></i></button>
        <div><strong>Panel de Control</strong><span>Demo privada · <?php echo demo_h($visitorCompany); ?></span></div>
        <div class="demo-top-actions">
            <label class="demo-role-switch"><i class="fa-solid <?php echo demo_h($demoRoleInfo['icon']); ?>"></i><span>Vista</span><select id="demoRoleSwitch" aria-label="Cambiar vista de rol"><?php foreach ($demoRoles as $roleKey => $roleInfo): ?><option value="<?php echo demo_h($roleKey); ?>" <?php echo $demoRole === $roleKey ? 'selected' : ''; ?>><?php echo demo_h($roleInfo['label']); ?></option><?php endforeach; ?></select></label>
            <a href="contacto.php"><i class="fa-solid fa-headset"></i><span>Hablar con un asesor</span></a><a class="primary" href="index.php#planes">Quiero PreventWork <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </header>

    <div class="demo-notice"><i class="fa-solid fa-flask"></i><span>Estás explorando información ficticia. Los botones de consulta funcionan, pero ningún cambio se guarda.</span><b>DEMO PEM</b></div>

    <div class="demo-content">
        <?php if ($view === 'dashboard'): ?>
            <section class="demo-page-head"><div><span>Vista interactiva · <?php echo demo_h($demoRoleInfo['label']); ?></span><h1><?php echo demo_h($demoRoleInfo['title']); ?></h1><p><?php echo demo_h($demoRoleInfo['description']); ?></p></div><div class="demo-plan-pill"><i class="fa-solid <?php echo demo_h($demoRoleInfo['icon']); ?>"></i> Cambia de rol arriba</div></section>

            <section class="demo-summary-grid">
                <article class="demo-summary"><span>Cumplimiento general</span><strong>84%</strong><small>+6% frente al trimestre anterior</small><i class="fa-solid fa-chart-line"></i></article>
                <article class="demo-summary blue"><span>Trabajadores activos</span><strong>24</strong><small>6 grupos de personal</small><i class="fa-solid fa-users"></i></article>
                <article class="demo-summary green"><span>Evidencias cargadas</span><strong>126</strong><small>Documentos organizados</small><i class="fa-solid fa-folder-open"></i></article>
                <article class="demo-summary violet"><span>Acciones prioritarias</span><strong>5</strong><small>2 próximas a vencer</small><i class="fa-solid fa-bell"></i></article>
            </section>

            <div class="demo-dashboard-grid">
                <section class="demo-panel">
                    <div class="demo-panel-head"><div><span>Avance normativo</span><h2>Estado de los estándares PEM</h2></div><small>Actualizado hoy</small></div>
                    <div class="demo-standard-list">
                        <?php foreach ($standards as $slug => $standard): ?>
                            <a href="<?php echo demo_h(demo_link($slug, $demoRole)); ?>"><span class="demo-standard-icon"><i class="fa-solid <?php echo demo_h($standard['icon']); ?>"></i></span><div><strong><?php echo (int)$standard['number']; ?>. <?php echo demo_h($standard['title']); ?></strong><small><?php echo demo_h($standard['summary']); ?></small><div class="demo-progress"><i style="width:<?php echo (int)$standard['progress']; ?>%"></i></div></div><b><?php echo (int)$standard['progress']; ?>%</b><i class="fa-solid fa-chevron-right"></i></a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <aside class="demo-panel demo-priority">
                    <div class="demo-panel-head"><div><span>Agenda SST</span><h2>Próximas acciones</h2></div></div>
                    <div class="demo-task"><i class="fa-solid fa-stethoscope"></i><div><strong>Evaluaciones periódicas</strong><span>4 trabajadores · 18 de julio</span></div><b>HOY</b></div>
                    <div class="demo-task"><i class="fa-solid fa-screwdriver-wrench"></i><div><strong>Mantenimiento preventivo</strong><span>Compresor principal · 22 de julio</span></div><b>6 DÍAS</b></div>
                    <div class="demo-task"><i class="fa-solid fa-graduation-cap"></i><div><strong>Capacitación de alturas</strong><span>8 participantes · 28 de julio</span></div><b>12 DÍAS</b></div>
                    <div class="demo-storage-panel"><div><i class="fa-solid fa-cloud"></i><span><strong>8,4 GB utilizados</strong><small>de 30 GB incluidos en PEM</small></span><b>28%</b></div><div class="demo-progress"><i style="width:28%"></i></div></div>
                </aside>
            </div>
        <?php elseif ($view === 'personal'): ?>
            <section class="demo-page-head"><div><span>Gestión de personal</span><h1>Trabajadores de la organización</h1><p>Información centralizada para conectar cargos, exámenes, capacitaciones y evidencias.</p></div><div class="demo-plan-pill"><i class="fa-solid fa-users"></i> 24 trabajadores</div></section>
            <section class="demo-panel">
                <div class="demo-worker-toolbar"><label><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="demoWorkerSearch" placeholder="Buscar por nombre, cargo o grupo"></label><div><span>6 visibles</span><button type="button" data-demo-message="En una cuenta activa podrás registrar y administrar todo tu personal."><i class="fa-solid fa-plus"></i> Agregar trabajador</button></div></div>
                <div class="demo-worker-grid" id="demoWorkerGrid">
                    <?php foreach ($workers as $index => $worker): ?>
                        <article class="demo-worker-card" data-worker-card data-search="<?php echo demo_h(strtolower(implode(' ', $worker))); ?>"><span class="demo-worker-avatar"><?php echo demo_h(mb_substr($worker[0],0,1)); ?></span><div class="demo-worker-main"><small><?php echo demo_h($worker[2]); ?></small><strong><?php echo demo_h($worker[0]); ?></strong><span><?php echo demo_h($worker[1]); ?></span></div><div class="demo-worker-state"><b class="<?php echo $worker[3] === 'Activo' ? 'ok' : 'warn'; ?>"><?php echo demo_h($worker[3]); ?></b><span>Perfil completo <?php echo demo_h($worker[4]); ?></span></div><button type="button" data-demo-message="Ficha demostrativa de <?php echo demo_h($worker[0]); ?>: exámenes, capacitaciones, restricciones y documentos conectados."><i class="fa-solid fa-arrow-up-right-from-square"></i></button></article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <?php $standard = $standards[$view]; ?>
            <section class="demo-page-head"><div><span><?php echo demo_h($standard['code']); ?> · Estándar <?php echo (int)$standard['number']; ?></span><h1><?php echo demo_h($standard['title']); ?></h1><p><?php echo demo_h($standard['summary']); ?></p></div><div class="demo-plan-pill"><i class="fa-solid <?php echo demo_h($standard['icon']); ?>"></i> <?php echo (int)$standard['progress']; ?>% de avance</div></section>

            <?php if (!empty($standard['submodules'])): ?><div class="demo-submodules"><?php foreach ($standard['submodules'] as $submodule): ?><span><i class="fa-solid fa-circle-check"></i><?php echo demo_h($submodule); ?></span><?php endforeach; ?></div><?php endif; ?>

            <section class="demo-summary-grid">
                <?php foreach ($standard['metrics'] as $metricIndex => $metric): ?><article class="demo-summary <?php echo ['','blue','green','violet'][$metricIndex]; ?>"><span><?php echo demo_h($metric[0]); ?></span><strong><?php echo demo_h($metric[1]); ?></strong><small><?php echo demo_h($metric[2]); ?></small><i class="fa-solid <?php echo demo_h($standard['icon']); ?>"></i></article><?php endforeach; ?>
            </section>

            <section class="demo-panel">
                <div class="demo-panel-head"><div><span>Vista operativa</span><h2>Seguimiento y evidencias</h2></div><small><?php echo count($standard['items']); ?> registros destacados</small></div>
                <div class="demo-record-list">
                    <?php foreach ($standard['items'] as $record): ?>
                        <details class="demo-record"><summary><span class="demo-record-icon"><i class="fa-solid <?php echo demo_h($standard['icon']); ?>"></i></span><div><small><?php echo demo_h($record[1]); ?></small><strong><?php echo demo_h($record[0]); ?></strong><div class="demo-progress"><i style="width:<?php echo (int)$record[3]; ?>%"></i></div></div><b><?php echo demo_h($record[2]); ?></b><span><?php echo (int)$record[3]; ?>%</span><i class="fa-solid fa-chevron-down"></i></summary><div class="demo-record-detail"><div><span>Detalle consolidado</span><p><?php echo demo_h($record[4]); ?></p></div><div><span>Trazabilidad disponible</span><p>Responsable asignado · Fechas · Evidencias · Observaciones · Historial de cambios.</p></div><button type="button" data-demo-message="En la membresía activa esta acción abre la gestión completa y permite guardar evidencias."><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir gestión completa</button></div></details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<div class="demo-toast" id="demoToast"><i class="fa-solid fa-circle-info"></i><span></span><button type="button" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button></div>
<script>
const sidebar=document.getElementById('demoSidebar'),overlay=document.getElementById('demoOverlay');
document.getElementById('openDemoMenu')?.addEventListener('click',()=>{sidebar.classList.add('open');overlay.classList.add('show')});
const closeMenu=()=>{sidebar.classList.remove('open');overlay.classList.remove('show')};
document.getElementById('closeDemoMenu')?.addEventListener('click',closeMenu);overlay?.addEventListener('click',closeMenu);
const toast=document.getElementById('demoToast'),toastText=toast?.querySelector('span');let toastTimer;
document.querySelectorAll('[data-demo-message]').forEach(button=>button.addEventListener('click',()=>{if(!toast)return;toastText.textContent=button.dataset.demoMessage;toast.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.classList.remove('show'),5200)}));
toast?.querySelector('button')?.addEventListener('click',()=>toast.classList.remove('show'));
document.getElementById('demoWorkerSearch')?.addEventListener('input',event=>{const term=event.target.value.trim().toLowerCase();document.querySelectorAll('[data-worker-card]').forEach(card=>card.hidden=term!==''&&!card.dataset.search.includes(term))});
document.getElementById('demoRoleSwitch')?.addEventListener('change',event=>{
    const nextRole=event.target.value;
    window.location.href='demo_pem?vista=<?php echo rawurlencode($view); ?>&rol='+encodeURIComponent(nextRole);
});
</script>
</body>
</html>
