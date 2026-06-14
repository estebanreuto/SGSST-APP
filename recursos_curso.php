<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/capacitaciones_schema.php';
require_once 'config/capacitaciones_helpers.php';

$u = require_auth($conn);
ensure_capacitaciones_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (($_SESSION['usuario_rol'] ?? '') !== 'trabajador') {
    header('Location: dashboard.php');
    exit;
}

$curso_id = (int)($_GET['id'] ?? 0);
$curso = obtener_curso_trabajador($conn, $curso_id, $usuario_id);
if (!$curso) {
    header('Location: capacitaciones.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM capacitaciones_materiales WHERE curso_id = ? ORDER BY orden, id");
$stmt->execute([$curso_id]);
$materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$materiales && $curso['modalidad'] === 'Sistema') {
    $legacy_type = !empty($curso['video_archivo']) ? 'video' : (!empty($curso['contenido_url']) ? 'enlace' : 'texto');
    $materiales[] = [
        'titulo' => 'Contenido del curso',
        'tipo' => $legacy_type,
        'contenido' => $legacy_type === 'enlace' ? $curso['contenido_url'] : ($curso['instrucciones'] ?: 'Revisa el contenido asignado.'),
        'archivo' => $legacy_type === 'video' ? $curso['video_archivo'] : null,
    ];
}

function resource_icon(string $tipo): string
{
    return match ($tipo) {
        'video' => 'fa-circle-play',
        'documento' => 'fa-file-lines',
        'imagen' => 'fa-image',
        'enlace' => 'fa-link',
        default => 'fa-align-left',
    };
}

function resource_label(string $tipo): string
{
    return match ($tipo) {
        'video' => 'Video',
        'documento' => 'Documento',
        'imagen' => 'Imagen',
        'enlace' => 'Enlace',
        default => 'Lectura',
    };
}

$current_page = 'capacitaciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos de <?php echo htmlspecialchars($curso['nombre_actividad']); ?> | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#ff7a00;--blue:#1e3a8a;--text:#1f2d3d;--muted:#64748b;--border:#dbe3ec}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;background:#f3f7fb;color:var(--text);min-height:100vh}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}.content{padding:24px 40px 44px;max-width:1280px;margin:auto}
        .back{display:inline-flex;align-items:center;gap:7px;color:#64748b;text-decoration:none;font-size:.75rem;font-weight:700;margin-bottom:14px}.library-header{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:18px}.library-title{display:flex;align-items:center;gap:12px;min-width:0}.library-title-icon{width:38px;height:38px;border-radius:7px;background:#fff7ed;border:1px solid #fed7aa;color:#d97706;display:grid;place-items:center;font-size:.9rem}.library-title h1{font-size:1.18rem;color:var(--blue);margin:0}.library-title p{font-size:.75rem;color:var(--muted);margin:4px 0 0}.course-link{display:inline-flex;align-items:center;gap:7px;background:var(--primary);color:#fff;text-decoration:none;padding:9px 12px;border-radius:7px;font-size:.75rem;font-weight:750}
        .toolbar{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:12px;margin-bottom:16px}.resource-search{position:relative;display:block;height:40px;align-self:start}.resource-search>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.78rem;pointer-events:none}.resource-search>input{width:100%;height:40px;border:1px solid var(--border);border-radius:7px;padding:0 12px 0 36px;font:inherit;font-size:.76rem;background:#fff;outline:0}.resource-search>input:focus{border-color:#fdba74;box-shadow:0 0 0 3px rgba(251,146,60,.12)}.filters{display:flex;gap:6px;flex-wrap:wrap}.filter-btn{height:40px;border:1px solid var(--border);border-radius:7px;padding:0 11px;background:#fff;color:#64748b;font:inherit;font-size:.7rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.filter-btn.active{background:#fff7ed;border-color:#fdba74;color:#c2410c}
        .file-workspace{display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px;align-items:start}.file-panel,.preview-panel{background:#fff;border:1px solid var(--border);border-radius:8px;overflow:hidden}.panel-caption{padding:11px 13px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:.68rem;font-weight:750;text-transform:uppercase}.file-list{padding:8px;display:flex;flex-direction:column;gap:4px;max-height:680px;overflow:auto}.file-item{width:100%;display:grid;grid-template-columns:32px minmax(0,1fr);gap:9px;align-items:center;border:0;border-radius:6px;background:transparent;padding:9px;text-align:left;cursor:pointer;color:inherit;font:inherit}.file-item:hover{background:#f8fafc}.file-item.active{background:#fff7ed}.file-icon{width:32px;height:32px;border-radius:6px;background:#f1f5f9;color:#64748b;display:grid;place-items:center;font-size:.75rem}.file-item.active .file-icon{background:#ffedd5;color:#c2410c}.file-copy{min-width:0}.file-copy strong{display:block;font-size:.73rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.file-copy span{display:block;font-size:.62rem;color:#94a3b8;margin-top:3px}.no-results{display:none;padding:28px 12px;text-align:center;color:#94a3b8;font-size:.74rem}
        .preview-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid #e2e8f0}.preview-head h2{font-size:.9rem;color:#1e293b;margin:0}.preview-head span{font-size:.65rem;color:#64748b}.preview-body{padding:18px;min-height:480px}.resource-view{display:none;animation:fadeIn .25s ease}.resource-view.active{display:block}@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}.reading{font-size:.84rem;line-height:1.75;color:#334155}.media{aspect-ratio:16/9;background:#0f172a;border-radius:7px;overflow:hidden}.media video,.media iframe{width:100%;height:100%;border:0}.image{text-align:center}.image img{max-width:100%;max-height:590px;border-radius:7px}.document{width:100%;height:620px;border:1px solid #cbd5e1;border-radius:7px}.open-resource{min-height:300px;border:1px dashed #cbd5e1;border-radius:7px;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:#64748b;text-align:center}.open-resource i{font-size:1.45rem;color:#94a3b8}.open-resource a{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #cbd5e1;border-radius:7px;padding:9px 12px;color:#1e3a8a;text-decoration:none;font-size:.75rem;font-weight:750}.empty{padding:70px 20px;text-align:center;color:#94a3b8}.empty i{font-size:1.8rem;margin-bottom:10px}
        @media(max-width:980px){.file-workspace{grid-template-columns:1fr}.file-list{max-height:270px}.preview-body{min-height:360px}}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}.content{padding:18px 14px}.library-header{align-items:flex-start;flex-direction:column}.toolbar{grid-template-columns:1fr}.filters{overflow-x:auto;flex-wrap:nowrap;padding-bottom:3px}.filter-btn{flex:none}.preview-body{padding:12px}.document{height:520px}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content">
        <a class="back" href="capacitaciones.php"><i class="fa-solid fa-arrow-left"></i> Mis capacitaciones</a>
        <header class="library-header">
            <div class="library-title">
                <span class="library-title-icon"><i class="fa-regular fa-folder-open"></i></span>
                <div><h1>Archivos del curso</h1><p><?php echo htmlspecialchars($curso['nombre_actividad']); ?> · <?php echo count($materiales); ?> recursos</p></div>
            </div>
            <a class="course-link" href="curso.php?id=<?php echo $curso_id; ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ir al curso</a>
        </header>

        <?php if (!$materiales): ?>
            <div class="file-panel empty"><i class="fa-regular fa-folder-open"></i><div>No hay archivos publicados todavía.</div></div>
        <?php else: ?>
            <div class="toolbar">
                <label class="resource-search"><i class="fa-solid fa-magnifying-glass"></i><input id="resourceSearch" type="search" placeholder="Buscar archivo o material..."></label>
                <div class="filters" aria-label="Filtrar materiales">
                    <button class="filter-btn active" type="button" data-filter="all">Todos</button>
                    <button class="filter-btn" type="button" data-filter="documento"><i class="fa-regular fa-file-lines"></i> Documentos</button>
                    <button class="filter-btn" type="button" data-filter="video"><i class="fa-regular fa-circle-play"></i> Videos</button>
                    <button class="filter-btn" type="button" data-filter="imagen"><i class="fa-regular fa-image"></i> Imágenes</button>
                    <button class="filter-btn" type="button" data-filter="enlace"><i class="fa-solid fa-link"></i> Enlaces</button>
                    <button class="filter-btn" type="button" data-filter="texto"><i class="fa-solid fa-align-left"></i> Lecturas</button>
                </div>
            </div>

            <div class="file-workspace">
                <aside class="file-panel">
                    <div class="panel-caption">Contenido</div>
                    <div class="file-list" id="fileList">
                        <?php foreach ($materiales as $index => $material): ?>
                            <button class="file-item <?php echo $index === 0 ? 'active' : ''; ?>" type="button" data-index="<?php echo $index; ?>" data-type="<?php echo htmlspecialchars($material['tipo']); ?>" data-name="<?php echo htmlspecialchars(mb_strtolower($material['titulo'])); ?>">
                                <span class="file-icon"><i class="fa-solid <?php echo resource_icon($material['tipo']); ?>"></i></span>
                                <span class="file-copy"><strong><?php echo htmlspecialchars($material['titulo']); ?></strong><span><?php echo resource_label($material['tipo']); ?></span></span>
                            </button>
                        <?php endforeach; ?>
                        <div class="no-results" id="noResults">No encontramos archivos con ese filtro.</div>
                    </div>
                </aside>

                <section class="preview-panel">
                    <div class="preview-head"><div><h2 id="previewTitle"><?php echo htmlspecialchars($materiales[0]['titulo']); ?></h2><span id="previewType"><?php echo resource_label($materiales[0]['tipo']); ?></span></div></div>
                    <div class="preview-body">
                        <?php foreach ($materiales as $index => $material):
                            $tipo = $material['tipo'];
                            $contenido = $material['contenido'] ?? '';
                            $archivo = $material['archivo'] ?? '';
                        ?>
                            <div class="resource-view <?php echo $index === 0 ? 'active' : ''; ?>" data-view="<?php echo $index; ?>" data-title="<?php echo htmlspecialchars($material['titulo']); ?>" data-label="<?php echo resource_label($tipo); ?>">
                                <?php if ($tipo === 'texto'): ?>
                                    <div class="reading"><?php echo nl2br(htmlspecialchars($contenido)); ?></div>
                                <?php elseif ($tipo === 'video' && $archivo): ?>
                                    <div class="media"><video controls controlsList="nodownload"><source src="<?php echo htmlspecialchars($archivo); ?>"></video></div>
                                <?php elseif ($tipo === 'imagen' && $archivo): ?>
                                    <div class="image"><img src="<?php echo htmlspecialchars($archivo); ?>" alt="<?php echo htmlspecialchars($material['titulo']); ?>"></div>
                                <?php elseif ($tipo === 'documento' && $archivo && strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) === 'pdf'): ?>
                                    <iframe class="document" src="<?php echo htmlspecialchars($archivo); ?>" title="<?php echo htmlspecialchars($material['titulo']); ?>"></iframe>
                                <?php elseif ($tipo === 'enlace' && ($embed = youtube_embed_url($contenido))): ?>
                                    <div class="media"><iframe src="<?php echo htmlspecialchars($embed); ?>" allowfullscreen title="<?php echo htmlspecialchars($material['titulo']); ?>"></iframe></div>
                                <?php else: ?>
                                    <?php $target = $archivo ?: $contenido; ?>
                                    <div class="open-resource"><i class="fa-solid <?php echo resource_icon($tipo); ?>"></i><span>Este recurso se abre en su formato original.</span><?php if ($target): ?><a href="<?php echo htmlspecialchars($target); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir archivo</a><?php endif; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php if ($materiales): ?>
<script>
    const items = [...document.querySelectorAll('.file-item')];
    const views = [...document.querySelectorAll('.resource-view')];
    const search = document.getElementById('resourceSearch');
    const filters = [...document.querySelectorAll('.filter-btn')];
    const noResults = document.getElementById('noResults');
    let activeFilter = 'all';

    function openResource(index) {
        items.forEach(item => item.classList.toggle('active', Number(item.dataset.index) === index));
        views.forEach(view => view.classList.toggle('active', Number(view.dataset.view) === index));
        const view = views.find(view => Number(view.dataset.view) === index);
        if (view) {
            document.getElementById('previewTitle').textContent = view.dataset.title;
            document.getElementById('previewType').textContent = view.dataset.label;
        }
    }

    function applyFilters() {
        const term = search.value.trim().toLocaleLowerCase('es');
        const visible = items.filter(item => {
            const matches = (activeFilter === 'all' || item.dataset.type === activeFilter) && item.dataset.name.includes(term);
            item.hidden = !matches;
            return matches;
        });
        noResults.style.display = visible.length ? 'none' : 'block';
        const selected = items.find(item => item.classList.contains('active') && !item.hidden);
        if (!selected && visible.length) openResource(Number(visible[0].dataset.index));
    }

    document.addEventListener('click', event => {
        const item = event.target.closest('.file-item');
        if (item) openResource(Number(item.dataset.index));

        const filter = event.target.closest('.filter-btn');
        if (filter) {
            activeFilter = filter.dataset.filter;
            filters.forEach(button => button.classList.toggle('active', button === filter));
            applyFilters();
        }
    });
    search.addEventListener('input', applyFilters);
</script>
<?php endif; ?>
</body>
</html>
