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

$stmt_materiales = $conn->prepare("SELECT * FROM capacitaciones_materiales WHERE curso_id = ? ORDER BY orden, id");
$stmt_materiales->execute([$curso_id]);
$materiales = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);
if (!$materiales && $curso['modalidad'] === 'Sistema') {
    $legacy_type = !empty($curso['video_archivo']) ? 'video' : (!empty($curso['contenido_url']) ? 'enlace' : 'texto');
    $materiales[] = [
        'titulo' => 'Contenido del curso',
        'tipo' => $legacy_type,
        'contenido' => $legacy_type === 'enlace' ? $curso['contenido_url'] : ($curso['instrucciones'] ?: 'Revisa el contenido asignado.'),
        'archivo' => $legacy_type === 'video' ? $curso['video_archivo'] : null,
    ];
}

$stmt_preguntas = $conn->prepare("SELECT * FROM capacitaciones_preguntas WHERE curso_id = ? ORDER BY orden, id");
$stmt_preguntas->execute([$curso_id]);
$preguntas = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
foreach ($preguntas as &$pregunta) {
    $stmt_opciones = $conn->prepare("SELECT id, texto FROM capacitaciones_opciones WHERE pregunta_id = ? ORDER BY orden, id");
    $stmt_opciones->execute([$pregunta['id']]);
    $pregunta['opciones'] = $stmt_opciones->fetchAll(PDO::FETCH_ASSOC);
}
unset($pregunta);

$stmt_intento = $conn->prepare("SELECT * FROM capacitaciones_intentos WHERE curso_id = ? AND usuario_id = ?");
$stmt_intento->execute([$curso_id, $usuario_id]);
$intento = $stmt_intento->fetch(PDO::FETCH_ASSOC);

$stmt_acta = $conn->prepare("SELECT * FROM capacitaciones_actas WHERE curso_id = ? AND usuario_id = ?");
$stmt_acta->execute([$curso_id, $usuario_id]);
$acta = $stmt_acta->fetch(PDO::FETCH_ASSOC);

$stmt_progreso = $conn->prepare("SELECT * FROM capacitaciones_progreso WHERE curso_id = ? AND usuario_id = ?");
$stmt_progreso->execute([$curso_id, $usuario_id]);
$progreso_db = $stmt_progreso->fetch(PDO::FETCH_ASSOC);

$ahora = time();
$disponible = $ahora >= strtotime($curso['fecha_inicio']) && $ahora <= strtotime($curso['fecha_fin']);
$requested_section = isset($_GET['seccion']) ? max(0, min((int)$_GET['seccion'], max(0, count($materiales) - 1))) : null;
$current_page = 'capacitaciones.php';

function render_material(array $material): void
{
    $tipo = $material['tipo'];
    $contenido = $material['contenido'] ?? '';
    $archivo = $material['archivo'] ?? '';
    if ($tipo === 'texto') {
        echo '<div class="reading-content">' . nl2br(htmlspecialchars($contenido)) . '</div>';
    } elseif ($tipo === 'video' && $archivo) {
        echo '<div class="media-frame"><video controls controlsList="nodownload"><source src="' . htmlspecialchars($archivo) . '"></video></div>';
    } elseif ($tipo === 'imagen' && $archivo) {
        echo '<div class="image-frame"><img src="' . htmlspecialchars($archivo) . '" alt=""></div>';
    } elseif ($tipo === 'documento' && $archivo) {
        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            echo '<iframe class="document-frame" src="' . htmlspecialchars($archivo) . '" title="Documento"></iframe>';
        } else {
            echo '<a class="resource-link" href="' . htmlspecialchars($archivo) . '" target="_blank"><i class="fa-solid fa-file-arrow-down"></i><span>Abrir documento completo</span></a>';
        }
    } elseif ($tipo === 'enlace' && $contenido) {
        $embed = youtube_embed_url($contenido);
        if ($embed) {
            echo '<div class="media-frame"><iframe src="' . htmlspecialchars($embed) . '" allowfullscreen title="Video"></iframe></div>';
        } else {
            echo '<a class="resource-link" href="' . htmlspecialchars($contenido) . '" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Abrir recurso externo</span></a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($curso['nombre_actividad']); ?> | PreventWork</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#ff8a1f;--primary2:#ff7a00;--blue:#1e3a8a;--text:#1f2d3d;--muted:#64748b;--border:#dbe3ec;--green:#16a34a}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;background:linear-gradient(180deg,#edf4fb,#f7f9fc);color:var(--text);min-height:100vh}.main-wrapper{margin-left:260px;width:calc(100% - 260px);min-height:100vh}.content{padding:26px 40px 44px;max-width:1240px;margin:auto}
        .back{display:inline-flex;align-items:center;gap:7px;color:#475569;text-decoration:none;font-size:.78rem;font-weight:700;margin-bottom:16px}.course-cover-hero{--course-accent:#38bdf8;position:relative;min-height:205px;border-radius:8px;overflow:hidden;margin-bottom:18px;padding:24px;color:#fff;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 10px 26px rgba(30,58,138,.13)}.course-cover-hero.theme-blue{background:#1e3a8a;--course-accent:#38bdf8}.course-cover-hero.theme-teal{background:#0f766e;--course-accent:#2dd4bf}.course-cover-hero.theme-violet{background:#5b21b6;--course-accent:#c4b5fd}.course-cover-hero.theme-rose{background:#9f1239;--course-accent:#fda4af}.course-cover-hero.theme-amber{background:#a24608;--course-accent:#fbbf24}.course-cover-hero::before{content:"";position:absolute;width:42%;height:150%;right:-8%;top:-24%;background:rgba(255,255,255,.08);transform:rotate(14deg)}.course-cover-hero::after{content:"";position:absolute;width:8px;height:58%;left:24px;bottom:24px;background:var(--course-accent);border-radius:5px}.course-hero-top,.course-hero-main,.course-hero-meta{position:relative;z-index:1}.course-hero-top{display:flex;justify-content:space-between;align-items:center;gap:14px}.hero-kind{display:inline-flex;align-items:center;gap:7px;background:#fff;color:var(--blue);padding:6px 9px;border-radius:6px;font-size:.66rem;font-weight:800}.deadline{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:7px;padding:8px 10px;font-size:.68rem;color:#fff;white-space:nowrap}.course-hero-main{padding-left:22px}.course-hero-main small{display:block;color:rgba(255,255,255,.72);font-size:.67rem;text-transform:uppercase;margin-bottom:6px}.course-hero-main h1{font-size:1.65rem;line-height:1.15;margin:0;max-width:720px}.course-hero-meta{padding-left:22px;display:flex;gap:16px;flex-wrap:wrap;color:rgba(255,255,255,.78);font-size:.68rem}.course-hero-meta span{display:inline-flex;align-items:center;gap:6px}
        .progress-shell{background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-bottom:18px;box-shadow:0 5px 18px rgba(15,23,42,.04)}.progress-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:9px}.progress-head strong{font-size:.8rem;color:var(--blue)}.progress-value{font-size:.9rem;font-weight:800;color:var(--primary2)}.progress-track{height:9px;border-radius:20px;background:#e2e8f0;overflow:hidden}.progress-fill{height:100%;width:1%;border-radius:inherit;background:linear-gradient(90deg,var(--primary),#22c55e);transition:width .55s cubic-bezier(.22,1,.36,1)}
        .course-layout{display:grid;grid-template-columns:230px minmax(0,1fr);gap:20px;align-items:start}.section-nav{position:sticky;top:20px;background:#fff;border:1px solid var(--border);border-radius:8px;padding:10px}.nav-step{display:flex;align-items:center;gap:9px;width:100%;border:0;background:transparent;padding:10px;border-radius:7px;text-align:left;color:#64748b;font:inherit;font-size:.74rem;cursor:pointer}.nav-step.active{background:#fff3e8;color:#c2410c;font-weight:750}.nav-step.completed{color:#15803d}.step-dot{width:25px;height:25px;border-radius:50%;display:grid;place-items:center;background:#f1f5f9;font-size:.67rem;flex-shrink:0}.active .step-dot{background:var(--primary);color:#fff}.completed .step-dot{background:#dcfce7;color:#15803d}
        .learning-stage{min-width:0}.learning-step{display:none;background:#fff;border:1px solid var(--border);border-radius:8px;padding:24px;box-shadow:0 8px 26px rgba(15,23,42,.045);animation:stepIn .35s ease}.learning-step.active{display:block}@keyframes stepIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}.step-kicker{display:flex;align-items:center;gap:8px;color:var(--primary2);font-size:.7rem;font-weight:800;text-transform:uppercase}.learning-step h2{font-size:1.15rem;color:var(--blue);margin:8px 0 18px}.reading-content{font-size:.9rem;line-height:1.75;color:#334155;white-space:normal}.media-frame{aspect-ratio:16/9;background:#0f172a;border-radius:8px;overflow:hidden}.media-frame video,.media-frame iframe{width:100%;height:100%;border:0}.image-frame{text-align:center}.image-frame img{max-width:100%;max-height:620px;border-radius:8px}.document-frame{width:100%;height:680px;border:1px solid #cbd5e1;border-radius:8px}.resource-link{min-height:220px;border:1px dashed #94a3b8;border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;text-decoration:none;color:var(--blue);font-weight:750;background:#f8fafc}.resource-link i{font-size:2.2rem;color:var(--primary)}
        .step-actions{display:flex;justify-content:space-between;gap:12px;margin-top:22px;padding-top:16px;border-top:1px solid #e2e8f0}.btn{border:0;border-radius:7px;padding:11px 16px;font:inherit;font-size:.8rem;font-weight:750;cursor:pointer;display:inline-flex;align-items:center;gap:7px}.btn-secondary{background:#f1f5f9;color:#475569}.btn-primary{background:var(--primary2);color:#fff}.btn:disabled{opacity:.45;cursor:not-allowed}
        .question{padding:16px 0;border-bottom:1px solid #e2e8f0}.question:last-of-type{border-bottom:0}.question h3{font-size:.88rem;margin:0 0 12px;color:#1e293b}.points{color:#7c3aed;font-size:.68rem;margin-left:6px}.answers{display:flex;flex-direction:column;gap:8px}.answer{display:flex;gap:9px;align-items:center;border:1px solid #dbe3ec;border-radius:8px;padding:11px 12px;cursor:pointer;font-size:.8rem;transition:.2s}.answer:hover{border-color:#a78bfa;transform:translateX(2px)}.answer:has(input:checked){border-color:#8b5cf6;background:#f5f3ff}.answer input{accent-color:#7c3aed}
        .result,.alert{padding:14px;border-radius:8px;margin-bottom:14px;font-size:.82rem;font-weight:700}.result.pass{background:#dcfce7;color:#166534}.result.fail,.alert{background:#fee2e2;color:#991b1b}.completion-panel{margin-top:18px;background:#fff;border:1px solid var(--border);border-radius:8px;padding:20px}.signature-box{border:2px dashed #cbd5e1;border-radius:8px;background:#fff}.signature-box canvas{display:block;width:100%;height:145px;touch-action:none;cursor:crosshair}.signature-actions{display:flex;justify-content:space-between;gap:10px;margin-top:10px}.signed-box{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:18px;border-radius:8px;text-align:center}.signed-box img{max-width:220px;max-height:90px;display:block;margin:10px auto}
        .confirm-dialog{width:min(430px,calc(100% - 28px));border:0;border-radius:8px;padding:0;overflow:hidden;box-shadow:0 26px 70px rgba(15,23,42,.32)}.confirm-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.confirm-content{padding:24px;text-align:center}.confirm-icon{width:48px;height:48px;margin:0 auto 13px;border-radius:50%;display:grid;place-items:center;background:#fff7ed;color:#ea580c;font-size:1rem}.confirm-content h2{font-size:1rem;color:var(--blue);margin:0 0 8px}.confirm-content p{font-size:.78rem;line-height:1.55;color:#64748b;margin:0}.confirm-warning{display:none;margin-top:12px;padding:9px;border-radius:7px;background:#fff7ed;color:#c2410c;font-size:.72rem;font-weight:700}.confirm-actions{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:20px}.confirm-actions .btn{justify-content:center}
        @media(max-width:950px){.course-layout{grid-template-columns:1fr}.section-nav{position:static;display:flex;overflow-x:auto}.nav-step{min-width:180px}}@media(max-width:768px){.main-wrapper{margin-left:0;width:100%}.content{padding:18px 14px}.course-cover-hero{min-height:190px;padding:18px}.course-cover-hero::after{left:18px;bottom:18px}.course-hero-main,.course-hero-meta{padding-left:18px}.course-hero-main h1{font-size:1.28rem}.course-hero-top{align-items:flex-start}.deadline{white-space:normal;text-align:right;max-width:52%}.course-hero-meta{gap:9px}.learning-step{padding:17px}.document-frame{height:520px}.step-actions{position:sticky;bottom:8px;background:#fff;padding:12px 0 0}.section-nav{display:none}}
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content">
        <a class="back" href="capacitaciones.php"><i class="fa-solid fa-arrow-left"></i> Mis capacitaciones</a>
        <header class="course-cover-hero theme-<?php echo capacitacion_tema($curso['tipo_capacitacion']); ?>">
            <div class="course-hero-top">
                <span class="hero-kind"><i class="fa-solid <?php echo capacitacion_icono($curso['tipo_capacitacion']); ?>"></i> <?php echo htmlspecialchars($curso['tipo_capacitacion']); ?></span>
                <span class="deadline"><i class="fa-regular fa-clock"></i> Hasta <?php echo date('d/m/Y h:i A', strtotime($curso['fecha_fin'])); ?></span>
            </div>
            <div class="course-hero-main">
                <small><?php echo htmlspecialchars($curso['categoria']); ?></small>
                <h1><?php echo htmlspecialchars($curso['nombre_actividad']); ?></h1>
            </div>
            <div class="course-hero-meta">
                <span><i class="fa-solid fa-layer-group"></i> <?php echo count($materiales); ?> materiales</span>
                <span><i class="fa-solid fa-display"></i> <?php echo htmlspecialchars($curso['modalidad']); ?></span>
                <span><i class="fa-solid fa-chart-simple"></i> Escala <?php echo $curso['escala_calificacion'] === '100' ? '100%' : htmlspecialchars($curso['escala_calificacion']); ?></span>
            </div>
        </header>

        <div class="progress-shell">
            <div class="progress-head"><strong><i class="fa-solid fa-chart-line"></i> Progreso del curso</strong><span class="progress-value" id="progressValue">1%</span></div>
            <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
        </div>

        <?php if (isset($_GET['error'])): ?><div class="alert"><?php echo htmlspecialchars($_GET['error']); ?></div><?php endif; ?>

        <?php if ($intento): ?>
            <div class="result <?php echo $intento['aprobado'] ? 'pass' : 'fail'; ?>">
                <?php echo $intento['aprobado'] ? 'Evaluación aprobada' : 'Aún no alcanzas la nota mínima'; ?>:
                <?php echo rtrim(rtrim(number_format($intento['puntaje_escala'], 2), '0'), '.'); ?><?php echo $curso['escala_calificacion'] === '100' ? '%' : ' / ' . $curso['escala_calificacion']; ?>
            </div>
        <?php endif; ?>

        <div class="course-layout">
            <nav class="section-nav" id="sectionNav">
                <?php foreach ($materiales as $index => $material): ?>
                    <button type="button" class="nav-step" data-index="<?php echo $index; ?>"><span class="step-dot"><?php echo $index + 1; ?></span><span><?php echo htmlspecialchars($material['titulo']); ?></span></button>
                <?php endforeach; ?>
                <button type="button" class="nav-step" data-index="<?php echo count($materiales); ?>"><span class="step-dot"><i class="fa-solid fa-list-check"></i></span><span>Evaluación</span></button>
            </nav>

            <div class="learning-stage">
                <?php foreach ($materiales as $index => $material): ?>
                    <section class="learning-step" data-step="<?php echo $index; ?>">
                        <span class="step-kicker"><i class="fa-solid fa-book-open"></i> Sección <?php echo $index + 1; ?> de <?php echo count($materiales); ?></span>
                        <h2><?php echo htmlspecialchars($material['titulo']); ?></h2>
                        <?php render_material($material); ?>
                        <div class="step-actions">
                            <button type="button" class="btn btn-secondary prev-step" <?php echo $index === 0 ? 'disabled' : ''; ?>><i class="fa-solid fa-arrow-left"></i> Anterior</button>
                            <button type="button" class="btn btn-primary next-step">Siguiente <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </section>
                <?php endforeach; ?>

                <section class="learning-step" data-step="<?php echo count($materiales); ?>" id="evaluationStep">
                    <span class="step-kicker"><i class="fa-solid fa-award"></i> Paso final</span>
                    <h2>Evaluación</h2>
                    <?php if (!$disponible && !$intento): ?>
                        <div class="alert">La evaluación no está disponible en este momento.</div>
                    <?php elseif ($acta): ?>
                        <p>El examen ya fue aprobado y el acta fue enviada.</p>
                    <?php elseif ($intento && $intento['aprobado']): ?>
                        <p>Ya aprobaste. Continúa con la firma del acta.</p>
                    <?php elseif (!$preguntas): ?>
                        <p>Esta actividad todavía no tiene preguntas configuradas.</p>
                    <?php else: ?>
                        <form action="procesar_curso.php" method="POST" id="examForm">
                            <input type="hidden" name="accion" value="presentar_examen">
                            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                            <input type="hidden" name="progreso" value="100">
                            <?php foreach ($preguntas as $index => $pregunta): ?>
                                <div class="question">
                                    <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($pregunta['enunciado']); ?><span class="points"><?php echo rtrim(rtrim(number_format($pregunta['puntos'], 2), '0'), '.'); ?> pts</span></h3>
                                    <div class="answers">
                                        <?php foreach ($pregunta['opciones'] as $opcion): $multiple = $pregunta['tipo'] === 'multiple'; ?>
                                            <label class="answer"><input type="<?php echo $multiple ? 'checkbox' : 'radio'; ?>" name="respuestas[<?php echo $pregunta['id']; ?>][]" value="<?php echo $opcion['id']; ?>"><?php echo htmlspecialchars($opcion['texto']); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="step-actions">
                                <button type="button" class="btn btn-secondary prev-step"><i class="fa-solid fa-arrow-left"></i> Anterior</button>
                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar evaluación</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <?php if ($intento && $intento['aprobado']): ?>
            <section class="completion-panel" id="acta">
                <h2 style="color:var(--blue);font-size:1rem;">Acta de finalización</h2>
                <p style="font-size:.82rem;color:#475569;">Declaro que completé el material y aprobé la evaluación correspondiente.</p>
                <?php if ($acta): ?>
                    <div class="signed-box"><i class="fa-solid fa-circle-check"></i><strong> Acta firmada y enviada</strong><img src="<?php echo htmlspecialchars($acta['firma']); ?>" alt="Firma"><small><?php echo date('d/m/Y h:i A', strtotime($acta['enviada_en'])); ?></small></div>
                <?php else: ?>
                    <form action="procesar_curso.php" method="POST" id="signatureForm">
                        <input type="hidden" name="accion" value="firmar_acta"><input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>"><input type="hidden" name="firma" id="firmaInput">
                        <div class="signature-box"><canvas id="signatureCanvas"></canvas></div>
                        <div class="signature-actions"><button class="btn btn-secondary" type="button" id="clearSignature">Limpiar</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-file-signature"></i> Firmar y enviar acta</button></div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
<dialog class="confirm-dialog" id="examConfirmDialog">
    <div class="confirm-content">
        <div class="confirm-icon"><i class="fa-solid fa-paper-plane"></i></div>
        <h2>¿Enviar la evaluación?</h2>
        <p>Después de enviarla se calculará tu resultado con las respuestas seleccionadas.</p>
        <div class="confirm-warning" id="examWarning"></div>
        <div class="confirm-actions">
            <button class="btn btn-secondary" type="button" id="cancelExamSubmit">Volver a revisar</button>
            <button class="btn btn-primary" type="button" id="confirmExamSubmit"><i class="fa-solid fa-check"></i> Sí, enviar</button>
        </div>
    </div>
</dialog>
<script>
    const courseId = <?php echo $curso_id; ?>;
    const userId = <?php echo $usuario_id; ?>;
    const storageKey = `preventwork_course_${courseId}_${userId}`;
    const serverProgress = <?php echo (int)($progreso_db['porcentaje'] ?? 0); ?>;
    const requestedSection = <?php echo $requested_section === null ? 'null' : $requested_section; ?>;
    const steps = [...document.querySelectorAll('.learning-step')];
    const navSteps = [...document.querySelectorAll('.nav-step')];
    const progressFill = document.getElementById('progressFill');
    const progressValue = document.getElementById('progressValue');
    let stored = {};
    try { stored = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) {}
    let currentStep = Math.min(Number(stored.step || 0), Math.max(0, steps.length - 1));
    if (requestedSection !== null) currentStep = requestedSection;
    if (serverProgress >= 100) currentStep = Math.max(0, steps.length - 1);

    function percentageFor(index) {
        if (steps.length <= 1) return 100;
        return Math.max(1, Math.round((index / (steps.length - 1)) * 100));
    }
    function showStep(index, save = true) {
        currentStep = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, i) => step.classList.toggle('active', i === currentStep));
        navSteps.forEach((step, i) => {
            step.classList.toggle('active', i === currentStep);
            step.classList.toggle('completed', i < currentStep);
        });
        const progress = Math.max(serverProgress, percentageFor(currentStep));
        progressFill.style.width = `${progress}%`;
        progressValue.textContent = `${progress}%`;
        if (save) localStorage.setItem(storageKey, JSON.stringify({step: currentStep, progress}));
        if (save) {
            window.scrollTo({top: document.querySelector('.progress-shell').offsetTop - 80, behavior: 'smooth'});
        }
    }
    document.addEventListener('click', event => {
        if (event.target.closest('.next-step')) showStep(currentStep + 1);
        if (event.target.closest('.prev-step')) showStep(currentStep - 1);
        const nav = event.target.closest('.nav-step');
        if (nav && Number(nav.dataset.index) <= currentStep) showStep(Number(nav.dataset.index));
    });
    const examForm = document.getElementById('examForm');
    const examConfirmDialog = document.getElementById('examConfirmDialog');
    let examSubmitConfirmed = false;
    examForm?.addEventListener('submit', event => {
        if (!examSubmitConfirmed) {
            event.preventDefault();
            const questions = [...examForm.querySelectorAll('.question')];
            const unanswered = questions.filter(question => !question.querySelector('input:checked')).length;
            const warning = document.getElementById('examWarning');
            warning.textContent = unanswered ? `Tienes ${unanswered} pregunta${unanswered === 1 ? '' : 's'} sin responder.` : '';
            warning.style.display = unanswered ? 'block' : 'none';
            examConfirmDialog.showModal();
            return;
        }
        localStorage.setItem(storageKey, JSON.stringify({step: steps.length - 1, progress: 100, submitting: true}));
    });
    document.getElementById('cancelExamSubmit')?.addEventListener('click', () => examConfirmDialog.close());
    document.getElementById('confirmExamSubmit')?.addEventListener('click', () => {
        examSubmitConfirmed = true;
        examConfirmDialog.close();
        examForm.requestSubmit();
    });
    <?php if (isset($_GET['resultado']) || $acta): ?>
    localStorage.removeItem(storageKey);
    currentStep = steps.length - 1;
    <?php endif; ?>
    showStep(currentStep, false);
</script>
<?php if ($intento && $intento['aprobado'] && !$acta): ?>
<script>
    const canvas = document.getElementById('signatureCanvas'), ctx = canvas.getContext('2d');
    let drawing = false, signed = false;
    function resizeCanvas(){const ratio=devicePixelRatio||1,rect=canvas.getBoundingClientRect();canvas.width=rect.width*ratio;canvas.height=rect.height*ratio;ctx.setTransform(ratio,0,0,ratio,0,0);ctx.lineWidth=2;ctx.lineCap='round';ctx.strokeStyle='#1e293b'}
    function position(e){const r=canvas.getBoundingClientRect(),p=e.touches?e.touches[0]:e;return{x:p.clientX-r.left,y:p.clientY-r.top}}
    function start(e){e.preventDefault();drawing=true;signed=true;const p=position(e);ctx.beginPath();ctx.moveTo(p.x,p.y)}
    function draw(e){if(!drawing)return;e.preventDefault();const p=position(e);ctx.lineTo(p.x,p.y);ctx.stroke()}
    ['mousedown','touchstart'].forEach(n=>canvas.addEventListener(n,start,{passive:false}));['mousemove','touchmove'].forEach(n=>canvas.addEventListener(n,draw,{passive:false}));['mouseup','mouseleave','touchend'].forEach(n=>canvas.addEventListener(n,()=>drawing=false));
    document.getElementById('clearSignature').addEventListener('click',()=>{ctx.clearRect(0,0,canvas.width,canvas.height);signed=false});
    document.getElementById('signatureForm').addEventListener('submit',e=>{if(!signed){e.preventDefault();alert('Dibuja tu firma antes de enviar el acta.');return}document.getElementById('firmaInput').value=canvas.toDataURL('image/png')});
    resizeCanvas();
</script>
<?php endif; ?>
</body>
</html>
