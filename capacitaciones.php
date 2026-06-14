<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/capacitaciones_schema.php';
require_once 'config/capacitaciones_helpers.php';

$u = require_auth($conn);
ensure_capacitaciones_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'trabajador') {
    header('Location: estandar3.php');
    exit;
}

$stmt_usuario = $conn->prepare("SELECT empresa_id, grupo_id FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT c.*, a.nombre_actividad, a.tipo_capacitacion, a.categoria, a.dirigido_a,
           a.descripcion, a.fecha_inicio, a.fecha_fin, a.modalidad,
           i.puntaje_escala, i.aprobado, i.finalizado_en,
           ac.id AS acta_id,
           EXISTS(
               SELECT 1 FROM actividades_trabajadores at
               WHERE at.actividad_id = a.id AND at.usuario_id = ?
           ) AS asignado_directo,
           (
               SELECT g.id FROM grupos_personal g
               WHERE g.empresa_id = a.empresa_id
                 AND CONCAT('Grupo: ', g.nombre) = a.dirigido_a
               LIMIT 1
           ) AS grupo_asignado_id
    FROM capacitaciones_cursos c
    JOIN actividades_capacitacion a ON a.id = c.actividad_id
    LEFT JOIN capacitaciones_intentos i ON i.curso_id = c.id AND i.usuario_id = ?
    LEFT JOIN capacitaciones_actas ac ON ac.curso_id = c.id AND ac.usuario_id = ?
    WHERE a.empresa_id = ?
    ORDER BY a.fecha_fin ASC, a.fecha_inicio DESC
");
$stmt->execute([$usuario_id, $usuario_id, $usuario_id, $usuario['empresa_id']]);
$cursos = array_values(array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), static function ($curso) use ($usuario_id, $usuario) {
    return trabajador_tiene_curso($curso, $usuario_id, $usuario['grupo_id'] ? (int)$usuario['grupo_id'] : null);
}));

$stmt_materiales = $conn->prepare("SELECT * FROM capacitaciones_materiales WHERE curso_id = ? ORDER BY orden, id");
foreach ($cursos as &$curso) {
    $stmt_materiales->execute([$curso['id']]);
    $curso['materiales'] = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);
    if (!$curso['materiales'] && $curso['modalidad'] === 'Sistema') {
        $legacy_type = !empty($curso['video_archivo']) ? 'video' : (!empty($curso['contenido_url']) ? 'enlace' : 'texto');
        $curso['materiales'][] = [
            'titulo' => 'Contenido del curso',
            'tipo' => $legacy_type,
        ];
    }
}
unset($curso);

$current_page = 'capacitaciones.php';
$ahora = new DateTime();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Capacitaciones | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#ff8a1f;--primary2:#ff7a00;--blue:#1e3a8a;--text:#1f2d3d;--muted:#64748b;--border:#dbe3ec}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;background:linear-gradient(180deg,#edf4fb,#f7f9fc);color:var(--text);min-height:100vh}
        .main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}.content-area{padding:32px 40px;max-width:1240px;margin:auto}
        .page-title{display:flex;gap:14px;align-items:center;margin-bottom:28px}.page-icon{width:46px;height:46px;border-radius:10px;background:#fff3e8;color:var(--primary);display:grid;place-items:center;font-size:1.2rem;border:1px solid #fed7aa}.page-title h1{font-size:1.2rem;color:var(--blue);margin:0}.page-title p{margin:4px 0 0;color:var(--muted);font-size:.82rem}
        .course-filters{display:grid;grid-template-columns:minmax(220px,1fr) 210px;gap:10px;margin-bottom:18px}.filter-field{position:relative;height:42px}.filter-field>i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.78rem;pointer-events:none}.filter-field input,.filter-field select{width:100%;height:42px;border:1px solid var(--border);border-radius:8px;background:#fff;padding:0 36px;font:inherit;font-size:.76rem;color:#334155;outline:0}.filter-field select{appearance:none;cursor:pointer}.filter-field .select-arrow{left:auto;right:13px}.filter-field input:focus,.filter-field select:focus{border-color:#fdba74;box-shadow:0 0 0 3px rgba(251,146,60,.12)}.filter-result{display:none;padding:38px 20px;text-align:center;background:#fff;border:1px dashed #cbd5e1;border-radius:8px;color:#64748b;font-size:.8rem}.filter-result i{display:block;color:#94a3b8;font-size:1.4rem;margin-bottom:9px}
        .course-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.course-card{background:#fff;border:1px solid var(--border);border-radius:8px;overflow:hidden;display:flex;flex-direction:column;min-width:0;box-shadow:0 5px 16px rgba(15,23,42,.04);transition:transform .25s ease,box-shadow .25s ease}.course-card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(15,23,42,.09)}
        .cover{--course-accent:#38bdf8;aspect-ratio:16/8.2;position:relative;overflow:hidden;padding:14px;display:flex;flex-direction:column;justify-content:space-between;color:#fff}.cover.theme-blue{background:#1e3a8a;--course-accent:#38bdf8}.cover.theme-teal{background:#0f766e;--course-accent:#2dd4bf}.cover.theme-violet{background:#5b21b6;--course-accent:#c4b5fd}.cover.theme-rose{background:#9f1239;--course-accent:#fda4af}.cover.theme-amber{background:#a24608;--course-accent:#fbbf24}.cover::before{content:"";position:absolute;width:46%;height:100%;right:-12%;top:0;background:rgba(255,255,255,.09);transform:skewX(-12deg)}.cover::after{content:"";position:absolute;width:6px;height:58%;left:14px;bottom:14px;background:var(--course-accent);border-radius:4px}.cover-top,.cover-main,.cover-foot{position:relative;z-index:1}.cover-top{display:flex;justify-content:space-between;align-items:center;gap:8px}.cover-icon{width:29px;height:29px;border-radius:7px;display:grid;place-items:center;background:rgba(255,255,255,.14);font-size:.75rem}.course-kind{background:#fff;padding:5px 8px;border-radius:6px;font-size:.61rem;font-weight:800;color:var(--blue);max-width:72%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.cover-main{padding-left:14px}.cover-main small{display:block;font-size:.58rem;color:rgba(255,255,255,.72);text-transform:uppercase;margin-bottom:4px}.cover-main strong{display:block;font-size:.94rem;line-height:1.25;max-width:86%;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.cover-foot{padding-left:14px;font-size:.57rem;color:rgba(255,255,255,.72);display:flex;align-items:center;gap:5px}
        .course-body{padding:13px;display:flex;flex-direction:column;gap:9px;flex:1}.course-body h2{font-size:.9rem;line-height:1.32;margin:0;color:#172554}.course-meta{display:flex;gap:9px;flex-wrap:wrap;color:var(--muted);font-size:.66rem}.course-meta span{display:flex;align-items:center;gap:4px}.status{font-size:.64rem;font-weight:800;border-radius:6px;padding:4px 7px;width:max-content;max-width:100%}.pending{background:#fff7ed;color:#c2410c}.approved{background:#dcfce7;color:#15803d}.signed{background:#e0f2fe;color:#0369a1}.closed{background:#fee2e2;color:#b91c1c}
        .course-files{display:inline-flex;align-items:center;gap:7px;width:max-content;color:#64748b;text-decoration:none;font-size:.7rem;font-weight:700;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;transition:color .2s ease,border-color .2s ease,background .2s ease}.course-files i{font-size:.76rem;color:#d97706}.course-files:hover{color:#1e3a8a;border-color:#cbd5e1;background:#fff}.course-files small{font-size:.62rem;color:#94a3b8;font-weight:700}
        .btn-course{margin-top:auto;text-decoration:none;background:var(--primary2);color:#fff;border-radius:7px;padding:9px 11px;text-align:center;font-size:.73rem;font-weight:750}.btn-course.disabled{background:#cbd5e1;pointer-events:none}.empty{padding:60px 20px;text-align:center;background:#fff;border:1px dashed #cbd5e1;border-radius:10px;color:var(--muted)}.empty i{font-size:2rem;color:#94a3b8;margin-bottom:12px}
        @media(max-width:1399px){.course-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:1050px){.course-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}.content-area{padding:18px 14px}.course-filters{grid-template-columns:1fr}.course-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <div class="page-title">
            <div class="page-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <div><h1>Mis capacitaciones</h1><p>Revisa el contenido, presenta la evaluación y firma el acta de finalización.</p></div>
        </div>
        <?php if (!$cursos): ?>
            <div class="empty"><i class="fa-solid fa-book-open"></i><h2>No tienes cursos asignados</h2><p>Las nuevas inducciones y reinducciones aparecerán aquí.</p></div>
        <?php else: ?>
            <div class="course-filters">
                <label class="filter-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="courseSearch" placeholder="Buscar capacitación...">
                </label>
                <label class="filter-field">
                    <i class="fa-solid fa-filter"></i>
                    <select id="courseStatus" aria-label="Filtrar por estado">
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobados</option>
                        <option value="signed">Finalizados</option>
                        <option value="closed">Plazo finalizado</option>
                    </select>
                    <i class="fa-solid fa-chevron-down select-arrow"></i>
                </label>
            </div>
            <div class="course-grid">
                <?php foreach ($cursos as $curso):
                    $inicio = new DateTime($curso['fecha_inicio']);
                    $fin = new DateTime($curso['fecha_fin']);
                    $disponible = $ahora >= $inicio && $ahora <= $fin;
                    $pendiente_inicio = $ahora < $inicio;
                    $status_class = 'pending';
                    $status_text = 'Pendiente';
                    if ($curso['acta_id']) { $status_class = 'signed'; $status_text = 'Finalizado y firmado'; }
                    elseif ($curso['aprobado']) { $status_class = 'approved'; $status_text = 'Aprobado, falta firma'; }
                    elseif ($ahora > $fin) { $status_class = 'closed'; $status_text = 'Plazo finalizado'; }
                ?>
                    <article class="course-card" data-status="<?php echo $status_class; ?>" data-search="<?php echo htmlspecialchars(mb_strtolower($curso['nombre_actividad'] . ' ' . $curso['tipo_capacitacion'] . ' ' . $curso['categoria'])); ?>">
                        <div class="cover theme-<?php echo capacitacion_tema($curso['tipo_capacitacion']); ?>">
                            <div class="cover-top">
                                <span class="cover-icon"><i class="fa-solid <?php echo capacitacion_icono($curso['tipo_capacitacion']); ?>"></i></span>
                                <span class="course-kind"><?php echo htmlspecialchars($curso['tipo_capacitacion']); ?></span>
                            </div>
                            <div class="cover-main">
                                <small><?php echo htmlspecialchars($curso['categoria']); ?></small>
                                <strong><?php echo htmlspecialchars($curso['nombre_actividad']); ?></strong>
                            </div>
                            <div class="cover-foot"><i class="fa-solid fa-layer-group"></i> <?php echo count($curso['materiales']); ?> materiales · <?php echo htmlspecialchars($curso['modalidad']); ?></div>
                        </div>
                        <div class="course-body">
                            <span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            <h2><?php echo htmlspecialchars($curso['nombre_actividad']); ?></h2>
                            <div class="course-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('d M', strtotime($curso['fecha_inicio'])); ?> - <?php echo date('d M Y', strtotime($curso['fecha_fin'])); ?></span>
                                <span><i class="fa-solid fa-chart-simple"></i> Escala <?php echo $curso['escala_calificacion'] === '100' ? '100%' : $curso['escala_calificacion']; ?></span>
                            </div>
                            <a class="course-files" href="recursos_curso.php?id=<?php echo $curso['id']; ?>">
                                <i class="fa-regular fa-folder-open"></i>
                                Archivos del curso
                                <small><?php echo count($curso['materiales']); ?></small>
                            </a>
                            <a class="btn-course <?php echo (!$disponible && !$curso['aprobado']) ? 'disabled' : ''; ?>" href="curso.php?id=<?php echo $curso['id']; ?>">
                                <?php echo $curso['acta_id'] ? 'Ver finalización' : ($curso['aprobado'] ? 'Firmar acta' : ($pendiente_inicio ? 'Próximamente' : 'Continuar curso')); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="filter-result" id="courseFilterEmpty"><i class="fa-solid fa-magnifying-glass"></i>No encontramos capacitaciones con esos filtros.</div>
        <?php endif; ?>
    </div>
</main>
<?php if ($cursos): ?>
<script>
    const courseCards = [...document.querySelectorAll('.course-card')];
    const courseSearch = document.getElementById('courseSearch');
    const courseStatus = document.getElementById('courseStatus');
    const courseFilterEmpty = document.getElementById('courseFilterEmpty');

    function filterCourses() {
        const term = courseSearch.value.trim().toLocaleLowerCase('es');
        const status = courseStatus.value;
        let visible = 0;
        courseCards.forEach(card => {
            const matches = card.dataset.search.includes(term) && (status === 'all' || card.dataset.status === status);
            card.hidden = !matches;
            if (matches) visible++;
        });
        courseFilterEmpty.style.display = visible ? 'none' : 'block';
    }
    courseSearch.addEventListener('input', filterCourses);
    courseStatus.addEventListener('change', filterCourses);
</script>
<?php endif; ?>
</body>
</html>
