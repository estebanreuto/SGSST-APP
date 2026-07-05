<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/capacitaciones_schema.php';

// Exige sesión válida
$u = require_auth($conn);
ensure_capacitaciones_schema($conn);

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Validar permisos
if ($usuario_rol === 'trabajador' || $usuario_rol !== 'sst') {
    header('Location: dashboard.php');
    exit;
}

// Obtener el ID de la empresa del usuario actual
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

// Verificar si ya estamos conectados con Google Calendar
$google_connected = isset($_SESSION['google_access_token']) && !empty($_SESSION['google_access_token']);

// ==========================================
// MODO EDICIÓN / REPROGRAMACIÓN (MAGIA)
// ==========================================
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$act_edit = null;
$trabajadores_edit = [];
$curso_edit = null;
$preguntas_edit = [];
$materiales_edit = [];

if ($edit_id > 0) {
    try {
        $stmt_edit = $conn->prepare("SELECT * FROM actividades_capacitacion WHERE id = ? AND empresa_id = ?");
        $stmt_edit->execute([$edit_id, $empresa_id]);
        $act_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        
        if ($act_edit && $act_edit['dirigido_a'] === 'Trabajador Específico') {
            $stmt_te = $conn->prepare("SELECT usuario_id FROM actividades_trabajadores WHERE actividad_id = ?");
            $stmt_te->execute([$edit_id]);
            $trabajadores_edit = $stmt_te->fetchAll(PDO::FETCH_COLUMN);
        }

        if ($act_edit) {
            $stmt_curso = $conn->prepare("SELECT * FROM capacitaciones_cursos WHERE actividad_id = ?");
            $stmt_curso->execute([$edit_id]);
            $curso_edit = $stmt_curso->fetch(PDO::FETCH_ASSOC);
            if ($curso_edit) {
                $stmt_materiales = $conn->prepare("SELECT * FROM capacitaciones_materiales WHERE curso_id = ? ORDER BY orden, id");
                $stmt_materiales->execute([$curso_edit['id']]);
                $materiales_edit = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);

                $stmt_preguntas = $conn->prepare("SELECT * FROM capacitaciones_preguntas WHERE curso_id = ? ORDER BY orden, id");
                $stmt_preguntas->execute([$curso_edit['id']]);
                foreach ($stmt_preguntas->fetchAll(PDO::FETCH_ASSOC) as $pregunta) {
                    $stmt_opciones = $conn->prepare("SELECT texto, es_correcta FROM capacitaciones_opciones WHERE pregunta_id = ? ORDER BY orden, id");
                    $stmt_opciones->execute([$pregunta['id']]);
                    $pregunta['opciones'] = array_map(static function ($opcion) {
                        return ['texto' => $opcion['texto'], 'correcta' => (bool)$opcion['es_correcta']];
                    }, $stmt_opciones->fetchAll(PDO::FETCH_ASSOC));
                    $preguntas_edit[] = $pregunta;
                }
            }
        }
    } catch (PDOException $e) {}
}

// Extraer fechas para prellenar
$fecha_inicio_val = ''; $hora_inicio_val = '';
$fecha_fin_val = ''; $hora_fin_val = '';

if ($act_edit) {
    $fecha_inicio_val = date('Y-m-d', strtotime($act_edit['fecha_inicio']));
    $hora_inicio_val = date('H:i', strtotime($act_edit['fecha_inicio']));
    $fecha_fin_val = date('Y-m-d', strtotime($act_edit['fecha_fin']));
    $hora_fin_val = date('H:i', strtotime($act_edit['fecha_fin']));
}

// 1. Obtener los grupos de la empresa para el formulario
$grupos = [];
if ($empresa_id) {
    try {
        $stmt_g = $conn->prepare("
            SELECT g.id, g.nombre, COUNT(u.id) as total_trabajadores 
            FROM grupos_personal g 
            LEFT JOIN usuarios u ON g.id = u.grupo_id AND u.activo = 1 
            WHERE g.empresa_id = ? 
            GROUP BY g.id 
            ORDER BY g.nombre ASC
        ");
        $stmt_g->execute([$empresa_id]);
        $grupos = $stmt_g->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

// 2. Obtener los trabajadores activos
$trabajadores_activos = [];
if ($empresa_id) {
    try {
        $stmt_ta = $conn->prepare("SELECT id, nombre, apellido, cedula, foto_perfil FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador' AND activo = 1 ORDER BY nombre ASC, apellido ASC");
        $stmt_ta->execute([$empresa_id]);
        $trabajadores_activos = $stmt_ta->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

$categorias_capacitacion_base = [
    'Biológico',
    'Físico',
    'Químico',
    'Psicosocial',
    'Biomecánicos',
    'Mecánico',
    'Eléctrico',
    'Locativo',
    'Seguridad Vial',
    'Públicos',
    'Trabajo en alturas',
    'Legal',
];
$categorias_entrenamiento = ['Trabajo en Alturas', 'Trabajo en Espacio Confinados', 'Trabajo en Caliente', 'Trabajo con Energías Peligrosas', 'Trabajo con Productos Químicos'];
$categorias_pyp_salud = [
    'Estilos de Vida Saludable (Alimentación, Ejercicio, Lavado de Manos)',
    'Prevención Consumo de Sustancias (Alcohol, Drogas, Fármacos, Tabaco)',
    'Bienestar Emocional y Mental',
    'Controles Médicos Periódicos (Autocuidado)',
];
$categorias_personalizadas = [];
if ($empresa_id) {
    try {
        $stmt_cat_custom = $conn->prepare("
            SELECT tipo_capacitacion, categoria
            FROM capacitaciones_categorias_personalizadas
            WHERE empresa_id = ? AND activo = 1
            ORDER BY tipo_capacitacion ASC, categoria ASC
        ");
        $stmt_cat_custom->execute([$empresa_id]);
        foreach ($stmt_cat_custom->fetchAll(PDO::FETCH_ASSOC) as $cat_custom) {
            $tipo_custom = $cat_custom['tipo_capacitacion'] ?? '';
            $categorias_personalizadas[$tipo_custom][] = $cat_custom['categoria'] ?? '';
        }
    } catch (PDOException $e) {
        $categorias_personalizadas = [];
    }
}

$current_page = 'estandar3.php'; 
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $act_edit ? 'Reprogramar' : 'Nueva'; ?> Actividad | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* HEADER CONSTANTE */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid rgba(148, 163, 184, 0.22); }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        
        .icon-box-std { 
            width: 44px; height: 44px; 
            background: rgba(255, 138, 31, 0.08); color: var(--primary); 
            border-radius: 10px; display: flex; align-items: center; justify-content: center; 
            flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); 
        }
        .icon-box-std svg { width: 22px; height: 22px; }
        
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; }
        .estandar-subtitle { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 500; }
        
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }

        /* ESTADO DE GOOGLE CALENDAR */
        .google-sync-banner { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: center; padding: 16px 18px; border-radius: 10px; margin-bottom: 24px; font-family: 'Inter', sans-serif; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);}
        .google-sync-banner.disconnected { background: #ffffff; border: 1px solid #dbe3ec; border-left: 4px solid #4285f4; }
        .google-sync-banner.connected { background: #ffffff; border: 1px solid #dbe3ec; border-left: 4px solid #16a34a; }
        .g-sync-info { display: flex; align-items: center; gap: 14px; }
        .g-sync-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
        .disconnected .g-sync-icon { background: rgba(66, 133, 244, 0.1); color: #4285f4; }
        .connected .g-sync-icon { background: rgba(22, 163, 74, 0.1); color: #16a34a; }
        .g-sync-text h4 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .g-sync-text p { margin: 4px 0 0 0; font-size: 0.78rem; color: #64748b; line-height: 1.4; }
        .sync-status { color: #16a34a; font-weight: 700; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn-google { background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); box-sizing: border-box;}
        .btn-google:hover { background: #f8fafc; border-color: #94a3b8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transform: translateY(-1px); }
        .btn-google svg { width: 16px; height: 16px; }

        /* LAYOUT 2 COLUMNAS */
        .layout-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 28px; align-items: start; }
        .left-column { display: flex; flex-direction: column; gap: 24px; }
        .form-section-heading { display: flex; align-items: center; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
        .form-section-heading .section-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; color: var(--primary); background: rgba(255,138,31,0.1); flex-shrink: 0; }
        .form-section-heading h2 { margin: 0; font-size: 0.95rem; color: var(--blue-dark); }
        .form-section-heading p { margin: 2px 0 0; font-size: 0.75rem; color: #64748b; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .form-group { text-align: left; }
        .form-group label.title-label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.75rem; color: #475569; text-transform: uppercase; letter-spacing: 0.03em; }
        
        .input-icon-wrapper { position: relative; width: 100%; }
        .input-icon-wrapper > i.icon-form { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; z-index: 10; font-size: 0.95rem; }
        .input-icon-wrapper > input.actividad-input, .input-icon-wrapper > select.actividad-input, .input-icon-wrapper > textarea.actividad-input { width: 100%; padding: 10px 14px 10px 40px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b; transition: all 0.2s ease; box-sizing: border-box; background: #ffffff; }
        .input-icon-wrapper > input.actividad-input, .input-icon-wrapper > select.actividad-input { height: 42px; }
        .input-icon-wrapper > select.actividad-input { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; }
        .input-icon-wrapper > input.actividad-input:focus, .input-icon-wrapper > select.actividad-input:focus, .input-icon-wrapper > textarea.actividad-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .custom-category-panel { display: none; margin-top: 10px; padding: 12px; border: 1px solid #fed7aa; border-radius: 10px; background: #fff7ed; }
        .custom-category-panel.active { display: block; }
        .custom-category-panel input[type="text"] { width: 100%; height: 38px; border: 1px solid #fdba74; border-radius: 8px; padding: 0 11px; font: inherit; box-sizing: border-box; color: #1e293b; background: #fff; outline: none; }
        .custom-category-panel input[type="text"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.13); }
        .custom-category-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 9px; }
        .custom-category-actions label { display: flex; align-items: center; gap: 8px; border: 1px solid #fed7aa; background: #fff; border-radius: 8px; padding: 9px 10px; color: #9a3412; font-size: .72rem; font-weight: 750; cursor: pointer; }
        .custom-category-actions input { accent-color: var(--primary2); }

        .course-builder { display: none; flex-direction: column; gap: 18px; background: #ffffff; border: 1px solid #dbe3ec; border-left: 4px solid #8b5cf6; border-radius: 12px; padding: 20px; }
        .course-builder.active { display: flex; }
        .course-note { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 9px; background: #f5f3ff; color: #5b21b6; font-size: 0.78rem; line-height: 1.45; }
        .content-mode { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .content-mode label { display: flex; align-items: center; gap: 9px; border: 1px solid #cbd5e1; border-radius: 9px; padding: 11px 12px; cursor: pointer; font-weight: 650; color: #475569; }
        .content-mode label:has(input:checked) { border-color: #8b5cf6; background: #f5f3ff; color: #5b21b6; }
        .evaluation-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .evaluation-choice label { display: flex; gap: 10px; align-items: center; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 9px; background: #ffffff; cursor: pointer; font-weight: 700; color: #475569; }
        .evaluation-choice label:has(input:checked) { border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; }
        .evaluation-choice input { accent-color: #7c3aed; }
        .evaluation-hint { margin: 7px 0 0; color: #64748b; font-size: 0.72rem; line-height: 1.4; }
        .upload-field { border: 1px dashed #94a3b8; border-radius: 10px; padding: 14px; background: #f8fafc; }
        .upload-field input { width: 100%; font-family: inherit; font-size: 0.78rem; }
        .materials-builder { display: flex; flex-direction: column; gap: 12px; }
        .materials-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .materials-toolbar h3 { margin: 0; color: var(--blue-dark); font-size: 0.92rem; }
        .material-list { display: flex; flex-direction: column; gap: 12px; }
        .material-editor { border: 1px solid #dbe3ec; border-radius: 10px; padding: 15px; background: #fff; box-shadow: 0 3px 12px rgba(15,23,42,.035); }
        .material-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .material-number { color: #0369a1; font-size: .76rem; font-weight: 800; display: flex; align-items: center; gap: 7px; }
        .material-grid { display: grid; grid-template-columns: minmax(0,1fr) 190px; gap: 10px; }
        .material-grid input, .material-body textarea, .material-body input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font: inherit; box-sizing: border-box; background: #fff; }
        .material-type-wrap { position: relative; min-width: 0; }
        .material-type-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 25px; height: 25px; border-radius: 6px; display: grid; place-items: center; color: #0369a1; background: #e0f2fe; font-size: .72rem; pointer-events: none; z-index: 1; }
        .material-type { appearance: none; width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 34px 0 45px; font: inherit; font-size: .78rem; font-weight: 700; color: #334155; background: #f8fafc; cursor: pointer; outline: none; }
        .material-type:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(14,165,233,.12); background: #fff; }
        .material-type-arrow { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .68rem; pointer-events: none; }
        .material-body { margin-top: 10px; }
        .material-body textarea { min-height: 88px; resize: vertical; }
        .material-existing { margin-top: 8px; padding: 8px 10px; border-radius: 7px; background: #eff6ff; color: #1d4ed8; font-size: .72rem; }
        .exam-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .exam-toolbar h3 { margin: 0; color: var(--blue-dark); font-size: 0.92rem; }
        .btn-add-question { border: 1px solid #c4b5fd; background: #f5f3ff; color: #6d28d9; border-radius: 8px; padding: 9px 12px; font: inherit; font-weight: 700; cursor: pointer; }
        .question-list { display: flex; flex-direction: column; gap: 12px; }
        .question-editor { border: 1px solid #dbe3ec; border-radius: 10px; padding: 16px; background: #ffffff; box-shadow: 0 3px 12px rgba(15,23,42,0.035); }
        .question-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .question-number { display: inline-flex; align-items: center; gap: 7px; color: #5b21b6; font-size: 0.76rem; font-weight: 800; }
        .question-top { display: grid; grid-template-columns: minmax(0, 1fr) 90px; gap: 10px; align-items: end; }
        .question-field label, .points-field label { display: block; margin-bottom: 5px; color: #64748b; font-size: 0.67rem; font-weight: 800; text-transform: uppercase; }
        .question-editor input, .question-editor select { width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; box-sizing: border-box; font: inherit; background: #ffffff; }
        .btn-remove-question { width: 34px; height: 34px; border: 0; border-radius: 8px; color: #dc2626; background: #fee2e2; cursor: pointer; }
        .question-type-switch { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin: 12px 0; }
        .type-choice { border: 1px solid #dbe3ec; background: #f8fafc; color: #475569; border-radius: 8px; padding: 9px 8px; cursor: pointer; font: inherit; font-size: 0.72rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .type-choice.active { border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; box-shadow: 0 0 0 2px rgba(139,92,246,.08); }
        .answers-label { color: #64748b; font-size: 0.67rem; font-weight: 800; text-transform: uppercase; margin-top: 12px; }
        .option-list { margin-top: 10px; display: flex; flex-direction: column; gap: 7px; }
        .option-row { display: grid; grid-template-columns: 28px minmax(0, 1fr) 30px; gap: 7px; align-items: center; padding: 5px 7px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .option-row:has(input[type="radio"]:checked), .option-row:has(input[type="checkbox"]:checked) { border-color: #86efac; background: #f0fdf4; }
        .option-row input[type="checkbox"], .option-row input[type="radio"] { width: 17px; height: 17px; accent-color: #8b5cf6; }
        .btn-option { border: 0; background: transparent; color: #64748b; cursor: pointer; font-weight: 700; padding: 5px 0; }
        .btn-option.add-option { margin-top: 12px; padding: 8px 10px; border: 1px dashed #cbd5e1; border-radius: 8px; width: 100%; background: #f8fafc; }
        .course-hidden { display: none !important; }

        /* TARJETAS FEATURE COMPACTAS */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .feature-card { background: #ffffff; border-radius: 14px; padding: 16px; border: 1px solid #cbd5e1; position: relative; overflow: hidden; transition: all 0.2s ease; cursor: pointer; display: flex; flex-direction: column; gap: 12px; z-index: 1; margin: 0 !important; font-weight: 400 !important; }
        .feature-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.04); border-color: #94a3b8; }
        .feature-card:has(input:checked) { background: #fff8f3; border-color: var(--primary); box-shadow: 0 4px 12px rgba(255, 138, 31, 0.08); }
        .watermark-icon { position: absolute; right: -8px; bottom: -10px; font-size: 75px; color: var(--primary); opacity: 0.06; transform: rotate(-15deg); z-index: 0; transition: all 0.3s ease; pointer-events: none; }
        .feature-card:hover .watermark-icon { transform: rotate(0deg) scale(1.05); opacity: 0.12; }
        .feature-card:has(input:checked) .watermark-icon { opacity: 0.20; transform: rotate(0deg) scale(1.05); }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; width: 100%; position: relative; z-index: 2; }
        .feature-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .feature-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .feature-icon.orange { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .feature-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .feature-card h3 { font-size: 0.9rem; font-weight: 800; color: #1e293b; margin: 0; position: relative; z-index: 2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .feature-card p { font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.3; position: relative; z-index: 2; }

        .radio-custom-dir { appearance: none; -webkit-appearance: none; width: 20px; height: 20px; border: 2px solid #cbd5e1; border-radius: 50%; cursor: pointer; position: relative; margin: 0; flex-shrink: 0; transition: all 0.2s ease; background: white; }
        .radio-custom-dir:checked { border-color: var(--primary); background: var(--primary); }
        .radio-custom-dir:checked::after { content: ''; position: absolute; top: 5px; left: 5px; width: 6px; height: 6px; background: white; border-radius: 50%; }
        
        .custom-chk { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; border: 2px solid #cbd5e1; border-radius: 5px; background-color: white; cursor: pointer; position: relative; flex-shrink: 0; transition: all 0.1s ease; margin: 0; }
        .custom-chk:checked { background-color: var(--primary); border-color: var(--primary); }
        .custom-chk:checked::after { content: ''; position: absolute; left: 5px; top: 1.5px; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }

        /* LISTA DE TRABAJADORES */
        .search-trabajadores { margin-bottom: 12px; position: relative; }
        .search-trabajadores input { width: 100%; padding: 12px 14px 12px 42px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.85rem; outline: none; background: #ffffff; transition: 0.2s;}
        .search-trabajadores input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255,138,31,0.1); }
        .search-icon-abs { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 0.95rem;}
        
        .trabajadores-list { max-height: 400px; overflow-y: auto; padding-right: 4px; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;}
        .trabajadores-list::-webkit-scrollbar { width: 5px; }
        .trabajadores-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .t-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; overflow: hidden; }
        .t-avatar img { width: 100%; height: 100%; object-fit: cover; }

        @keyframes slideInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-card { opacity: 0; animation: slideInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* COLUMNA DERECHA (FIJA) */
        .right-column { position: sticky; top: 24px; display: flex; flex-direction: column; gap: 20px; background: #ffffff; border: 1px solid #dbe3ec; border-top: 3px solid var(--primary); border-radius: 12px; padding: 20px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05); box-sizing: border-box; }
        .schedule-heading { display: flex; align-items: center; gap: 10px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
        .schedule-heading i { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; color: #0284c7; background: rgba(14,165,233,0.1); }
        .schedule-heading h2 { margin: 0; font-size: 0.95rem; color: var(--blue-dark); }
        .schedule-heading p { margin: 2px 0 0; font-size: 0.72rem; color: #64748b; }
        .schedule-group { display: flex; flex-direction: column; gap: 8px; }
        .datetime-row { display: grid; grid-template-columns: 3fr 2fr; gap: 12px; }

        .btn-primary-act { width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #fff; border: none; padding: 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; box-sizing: border-box; }
        .btn-primary-act:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.25); }
        
        .btn-cancel { width: 100%; background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 14px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-size: 0.9rem; display: flex; justify-content: center; align-items: center; gap: 8px; box-sizing: border-box; }
        .btn-cancel:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        .flatpickr-calendar { font-family: 'Inter', sans-serif !important; border-radius: 12px !important; box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border: 1px solid #e2e8f0 !important;}
        .flatpickr-day.selected { background: var(--primary) !important; border-color: var(--primary) !important; }
        input.datepicker[readonly], input.timepicker[readonly] { background-color: #ffffff; cursor: pointer; }

        @media (max-width: 1024px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
            .layout-grid { grid-template-columns: 1fr; gap: 28px;}
            .right-column { position: static; order: initial; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
            .btn-back { order: -1; width: max-content; padding: 6px 12px; }
            .form-grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .cards-grid { grid-template-columns: 1fr; }
            .datetime-row { grid-template-columns: 1fr; gap: 12px; } 
            .google-sync-banner { grid-template-columns: 1fr; gap: 14px; }
            .btn-google { width: 100%; justify-content: center; }
            .sync-status { padding-left: 56px; }
            .question-type-switch { grid-template-columns: 1fr; }
            .material-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title"><?php echo $act_edit ? 'Reprogramar Actividad' : 'Programar Nueva Capacitación'; ?></h1>
                        <p class="estandar-subtitle">
                            <?php echo $act_edit ? 'Actualiza los datos y fechas de la reunión.' : 'Define la actividad, convoca al equipo y organiza su programación.'; ?>
                        </p>
                    </div>
                </div>
                <a href="estandar3.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver al Estándar 3
                </a>
            </div>

            <?php if (!$act_edit): ?>
                <?php if ($google_connected): ?>
                    <div class="google-sync-banner connected">
                        <div class="g-sync-info">
                            <div class="g-sync-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <div class="g-sync-text">
                                <h4>Conectado a Google Calendar</h4>
                                <p>Al guardar podrás continuar el evento en Google Calendar con los datos de la actividad.</p>
                            </div>
                        </div>
                        <span class="sync-status">
                            <i class="fa-solid fa-check-circle"></i> Sincronizado
                        </span>
                    </div>
                <?php else: ?>
                    <div class="google-sync-banner disconnected">
                        <div class="g-sync-info">
                            <div class="g-sync-icon"><i class="fa-brands fa-google"></i></div>
                            <div class="g-sync-text">
                                <h4>Organiza también en Google Calendar</h4>
                                <p>La conexión es opcional y te permite llevar los datos de la actividad a tu calendario.</p>
                            </div>
                        </div>
                        <a href="google_auth.php" class="btn-google">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                <path d="M1 1h22v22H1z" fill="none"/>
                            </svg>
                            Conectar con Google
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="google-sync-banner disconnected" style="border-left-color:#dc2626;">
                    <div class="g-sync-info">
                        <div class="g-sync-icon" style="color:#dc2626;background:#fee2e2;"><i class="fa-solid fa-circle-exclamation"></i></div>
                        <div class="g-sync-text"><h4>No pudimos guardar la actividad</h4><p><?php echo htmlspecialchars($_GET['error']); ?></p></div>
                    </div>
                </div>
            <?php endif; ?>

            <form id="formRegistroActividad" action="procesar_estandar3.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?php echo $edit_id ? 'editar_actividad' : 'crear_actividad'; ?>">
                <?php if($edit_id): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>
                
                <div class="layout-grid">
                    
                    <div class="left-column">
                        <div class="form-group full" style="margin-bottom: 0;">
                            <label class="title-label" for="nombre_actividad">Nombre de la Actividad *</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-pen-to-square icon-form"></i>
                                <input type="text" name="nombre_actividad" id="nombre_actividad" class="actividad-input" value="<?php echo htmlspecialchars($act_edit['nombre_actividad'] ?? ''); ?>" required placeholder="Ej. Uso y manejo de extintores">
                            </div>
                        </div>

                        <section class="course-builder" id="courseBuilder">
                            <div class="form-section-heading">
                                <div class="section-icon" style="color:#7c3aed;background:#f5f3ff;"><i class="fa-solid fa-graduation-cap"></i></div>
                                <div>
                                    <h2 id="builderTitle">Contenido y evaluación</h2>
                                    <p id="builderSubtitle">Configura el material autogestionado y el examen de aprobación.</p>
                                </div>
                            </div>

                            <div class="course-note" id="systemCourseNote">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>La modalidad Sistema crea un curso autogestionado dentro de PreventWork. No abrirá Google Calendar y el trabajador deberá completar el contenido, el examen y el acta.</span>
                            </div>

                            <div class="materials-builder system-content-field">
                                <div class="materials-toolbar">
                                    <div><h3>Secciones y materiales</h3><span style="font-size:.72rem;color:#64748b;">Agrega texto, videos, enlaces, documentos o imágenes sin límite.</span></div>
                                    <button type="button" class="btn-add-question" id="btnAddMaterial"><i class="fa-solid fa-plus"></i> Material</button>
                                </div>
                                <div class="material-list" id="materialList"></div>
                                <input type="hidden" name="materiales_json" id="materialesJson">
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="title-label" for="escala_calificacion">Escala de calificación *</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fa-solid fa-chart-simple icon-form"></i>
                                        <select name="escala_calificacion" id="escala_calificacion" class="actividad-input">
                                            <?php foreach (['5' => 'De 1 a 5', '10' => 'De 1 a 10', '100' => 'Porcentaje (0 a 100%)'] as $valor => $texto): ?>
                                                <option value="<?php echo $valor; ?>" <?php echo (($curso_edit['escala_calificacion'] ?? '100') === (string)$valor) ? 'selected' : ''; ?>><?php echo $texto; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="title-label" for="puntaje_aprobacion">Nota mínima para aprobar *</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fa-solid fa-bullseye icon-form"></i>
                                        <input type="number" name="puntaje_aprobacion" id="puntaje_aprobacion" class="actividad-input" min="0" step="0.1" value="<?php echo htmlspecialchars($curso_edit['puntaje_aprobacion'] ?? '60'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="exam-toolbar">
                                <div><h3>Preguntas del examen</h3><span style="font-size:.72rem;color:#64748b;">Los puntos se distribuyen automáticamente según la escala.</span></div>
                                <button type="button" class="btn-add-question" id="btnAddQuestion"><i class="fa-solid fa-plus"></i> Pregunta</button>
                            </div>
                            <div class="question-list" id="questionList"></div>
                            <input type="hidden" name="preguntas_json" id="preguntasJson">
                        </section>

                        <div class="form-grid-2">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="title-label" for="tipo_capacitacion">Tipo de Capacitación *</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-layer-group icon-form"></i>
                                    <select name="tipo_capacitacion" id="tipo_capacitacion" class="actividad-input" required>
                                        <option value="">Selecciona...</option>
                                        <?php 
                                        $tipos = ['Inducción', 'Re Inducción', 'Charla de Seguridad', 'Capacitación', 'Entrenamiento', 'Campaña PyP en Salud'];
                                        foreach($tipos as $t) {
                                            $sel = ($act_edit['tipo_capacitacion'] ?? '') == $t ? 'selected' : '';
                                            echo "<option value='$t' $sel>$t</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="title-label" for="categoria">Categoría *</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-tag icon-form"></i>
                                    <select name="categoria" id="categoria" class="actividad-input" required>
                                        <option value="">Selecciona...</option>
                                        <?php 
                                        foreach($categorias_capacitacion_base as $c) {
                                            $sel = ($act_edit['categoria'] ?? '') == $c ? 'selected' : '';
                                            echo "<option value='$c' $sel>$c</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="custom-category-panel" id="customCategoryPanel">
                                    <input type="text" name="categoria_personalizada" id="categoria_personalizada" placeholder="Escribe la nueva categoría">
                                    <div class="custom-category-actions">
                                        <label>
                                            <input type="radio" name="guardar_categoria_personalizada" value="0" checked>
                                            Solo esta actividad
                                        </label>
                                        <label>
                                            <input type="radio" name="guardar_categoria_personalizada" value="1">
                                            Dejar fija
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid-2" id="modalidadGrid">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="title-label" for="modalidad">Modalidad *</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-globe icon-form"></i>
                                    <select name="modalidad" id="modalidad" class="actividad-input" required>
                                        <option value="Virtual" <?php echo ($act_edit['modalidad']??'') == 'Virtual' ? 'selected' : ''; ?>>Virtual (Meet/Zoom/Teams)</option>
                                        <option value="Físico" <?php echo ($act_edit['modalidad']??'') == 'Físico' ? 'selected' : ''; ?>>Presencial (Físico)</option>
                                        <option value="Mixto" <?php echo ($act_edit['modalidad']??'') == 'Mixto' ? 'selected' : ''; ?>>Mixto (Ambas)</option>
                                        <option value="Sistema" <?php echo ($act_edit['modalidad']??'') == 'Sistema' ? 'selected' : ''; ?>>Sistema (Curso autogestionado)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" id="lugarGroup" style="margin-bottom: 0;">
                                <label class="title-label" for="lugar_exacto">Lugar / Enlace Alterno <span style="text-transform:none; font-weight:400; color:#94a3b8;">(Opcional)</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-location-dot icon-form"></i>
                                    <input type="text" name="lugar_exacto" id="lugar_exacto" class="actividad-input" placeholder="Sala 1, Oficina, o URL" value="<?php echo htmlspecialchars($act_edit['lugar_exacto']??''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="evaluationChoiceGroup">
                            <label class="title-label">¿Esta actividad lleva evaluación? *</label>
                            <div class="evaluation-choice">
                                <label>
                                    <input type="radio" name="requiere_evaluacion" value="1" <?php echo $curso_edit ? 'checked' : ''; ?>>
                                    <i class="fa-solid fa-list-check"></i> Sí, agregar evaluación
                                </label>
                                <label>
                                    <input type="radio" name="requiere_evaluacion" value="0" <?php echo !$curso_edit ? 'checked' : ''; ?>>
                                    <i class="fa-solid fa-calendar-check"></i> No, solo programar
                                </label>
                            </div>
                            <p class="evaluation-hint" id="evaluationHint">Puedes programar la reunión en Google Calendar y decidir si el trabajador deberá presentar una evaluación.</p>
                        </div>

                        <div class="form-group full" style="margin-bottom: 0;">
                            <label class="title-label" for="descripcion">Descripción de la Actividad <span style="text-transform:none; font-weight:400; color:#94a3b8;">(Opcional)</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-align-left icon-form" style="top: 20px;"></i>
                                <textarea name="descripcion" id="descripcion" class="actividad-input" style="height: 80px; padding-top:12px; resize:none;" placeholder="Detalles, temas a tratar, o instrucciones especiales..."><?php echo htmlspecialchars($act_edit['descripcion']??''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group full" style="margin-bottom: 0;">
                            <label class="title-label">Dirigido a *</label>
                            
                            <div class="cards-grid">
                                <label class="feature-card">
                                    <i class="fa-solid fa-building-user watermark-icon"></i>
                                    <div class="card-header">
                                        <div class="feature-icon blue"><i class="fa-solid fa-building-user"></i></div>
                                        <input type="radio" name="dirigido_a" value="Toda la empresa" class="radio-custom-dir" required <?php echo ($act_edit['dirigido_a']??'') == 'Toda la empresa' ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="card-body">
                                        <h3>Toda la Empresa</h3>
                                        <p>Todos los activos</p>
                                    </div>
                                </label>

                                <label class="feature-card">
                                    <i class="fa-solid fa-user-check watermark-icon"></i>
                                    <div class="card-header">
                                        <div class="feature-icon orange"><i class="fa-solid fa-user-check"></i></div>
                                        <input type="radio" name="dirigido_a" value="Trabajador Específico" class="radio-custom-dir" required <?php echo ($act_edit['dirigido_a']??'') == 'Trabajador Específico' ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="card-body">
                                        <h3>Trabajador Específico</h3>
                                        <p>Selección manual</p>
                                    </div>
                                </label>

                                <?php if (isset($grupos) && !empty($grupos)): ?>
                                    <?php foreach ($grupos as $g): ?>
                                        <label class="feature-card">
                                            <i class="fa-solid fa-users-viewfinder watermark-icon"></i>
                                            <div class="card-header">
                                                <div class="feature-icon purple"><i class="fa-solid fa-users-viewfinder"></i></div>
                                                <input type="radio" name="dirigido_a" value="Grupo: <?php echo htmlspecialchars($g['nombre']); ?>" class="radio-custom-dir" required <?php echo ($act_edit['dirigido_a']??'') == 'Grupo: '.$g['nombre'] ? 'checked' : ''; ?>>
                                            </div>
                                            <div class="card-body">
                                                <h3><?php echo htmlspecialchars($g['nombre']); ?></h3>
                                                <p><?php echo htmlspecialchars($g['total_trabajadores'] ?? '0'); ?> trabajadores</p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group full" id="contenedor_trabajadores_especificos" style="display: <?php echo ($act_edit['dirigido_a']??'') == 'Trabajador Específico' ? 'block' : 'none'; ?>; animation: slideInUp 0.2s ease; margin-bottom: 0;">
                            <label class="title-label">Selecciona los Trabajadores *</label>
                            
                            <div class="search-trabajadores">
                                <i class="fa-solid fa-magnifying-glass search-icon-abs"></i>
                                <input type="text" id="buscarTrabajadorInput" placeholder="Buscar por nombre o cédula...">
                            </div>
                                
                            <div class="cards-grid trabajadores-list" id="listaTrabajadoresContainer">
                                <?php if (isset($trabajadores_activos) && !empty($trabajadores_activos)): ?>
                                    <?php $delay = 0; ?>
                                    <?php foreach ($trabajadores_activos as $ta): ?>
                                        
                                        <label class="feature-card trabajador-item fade-in-card" style="animation-delay: <?php echo $delay * 0.03; ?>s;">
                                            <i class="fa-solid fa-id-badge watermark-icon"></i>
                                            <div class="card-header">
                                                <div class="t-avatar">
                                                    <?php if (!empty($ta['foto_perfil']) && file_exists($ta['foto_perfil'])): ?>
                                                        <img src="<?php echo htmlspecialchars($ta['foto_perfil']); ?>" alt="Foto">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($ta['nombre'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="checkbox" name="trabajadores_seleccionados[]" value="<?php echo $ta['id']; ?>" class="chk-trabajador custom-chk" <?php echo in_array($ta['id'], $trabajadores_edit) ? 'checked' : ''; ?>>
                                            </div>
                                            <div class="card-body">
                                                <h3><?php echo htmlspecialchars($ta['nombre'] . ' ' . $ta['apellido']); ?></h3>
                                                <p>C.C. <?php echo htmlspecialchars($ta['cedula']); ?></p>
                                            </div>
                                        </label>
                                        
                                        <?php $delay++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="padding: 16px; text-align: center; color: #94a3b8; font-size: 0.8rem; grid-column: 1 / -1;">
                                        No hay trabajadores activos en la empresa.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <div class="right-column">
                        <div class="schedule-heading">
                            <i class="fa-regular fa-calendar-check"></i>
                            <div>
                                <h2>Programación</h2>
                                <p>Indica cuándo inicia y finaliza.</p>
                            </div>
                        </div>
                        
                        <div class="schedule-group">
                            <label class="title-label">Inicio *</label>
                            <div class="datetime-row">
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-calendar icon-form"></i>
                                    <input type="text" name="fecha_inicio" id="fecha_inicio" class="actividad-input datepicker" required placeholder="Añadir fecha" value="<?php echo $fecha_inicio_val; ?>">
                                </div>
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-clock icon-form"></i>
                                    <input type="text" name="hora_inicio" id="hora_inicio" class="actividad-input timepicker" required placeholder="00:00" value="<?php echo $hora_inicio_val; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="schedule-group" style="margin-top: 6px;">
                            <label class="title-label">Finalización *</label>
                            <div class="datetime-row">
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-calendar icon-form"></i>
                                    <input type="text" name="fecha_fin" id="fecha_fin" class="actividad-input datepicker" required placeholder="Añadir fecha" value="<?php echo $fecha_fin_val; ?>">
                                </div>
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-clock icon-form"></i>
                                    <input type="text" name="hora_fin" id="hora_fin" class="actividad-input timepicker" required placeholder="00:00" value="<?php echo $hora_fin_val; ?>">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                            <button type="submit" class="btn-primary-act">
                                <i class="fa-solid <?php echo $act_edit ? 'fa-calendar-day' : 'fa-check'; ?>"></i>
                                <?php echo $act_edit ? 'Guardar Cambios' : 'Guardar Actividad'; ?>
                            </button>
                            <button type="button" class="btn-cancel" onclick="window.location.href='estandar3.php'">
                                <i class="fa-solid fa-xmark"></i>
                                Cancelar
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            flatpickr(".datepicker", {
                locale: "es",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j M Y", 
                minDate: "today",
                disableMobile: "true" 
            });

            flatpickr(".timepicker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                altInput: true,
                altFormat: "h:i K",
                disableMobile: "true"
            });

            const formActividad = document.getElementById('formRegistroActividad');
            const tipoCapacitacion = document.getElementById('tipo_capacitacion');
            const categoriaCapacitacion = document.getElementById('categoria');
            const courseBuilder = document.getElementById('courseBuilder');
            const googleBanner = document.querySelector('.google-sync-banner.connected, .google-sync-banner.disconnected');
            const contentUrlField = document.getElementById('contentUrlField');
            const contentVideoField = document.getElementById('contentVideoField');
            const contenidoUrl = document.getElementById('contenido_url');
            const videoCurso = document.getElementById('video_curso');
            const questionList = document.getElementById('questionList');
            const preguntasJson = document.getElementById('preguntasJson');
            const escalaCalificacion = document.getElementById('escala_calificacion');
            const puntajeAprobacion = document.getElementById('puntaje_aprobacion');
            const typeCategoryGrid = tipoCapacitacion?.closest('.form-grid-2');
            const modalidadActividad = document.getElementById('modalidad');
            const modalidadGrid = document.getElementById('modalidadGrid');
            const lugarGroup = document.getElementById('lugarGroup');
            const evaluationChoiceGroup = document.getElementById('evaluationChoiceGroup');
            const evaluationRadios = document.querySelectorAll('input[name="requiere_evaluacion"]');
            const evaluationHint = document.getElementById('evaluationHint');
            const systemContentFields = document.querySelectorAll('.system-content-field');
            const systemCourseNote = document.getElementById('systemCourseNote');
            const builderTitle = document.getElementById('builderTitle');
            const builderSubtitle = document.getElementById('builderSubtitle');
            const initialQuestions = <?php echo json_encode($preguntas_edit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const materialList = document.getElementById('materialList');
            const materialesJson = document.getElementById('materialesJson');
            const initialMaterials = <?php echo json_encode($materiales_edit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const categoriasBase = <?php echo json_encode($categorias_capacitacion_base, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const categoriasEntrenamiento = <?php echo json_encode($categorias_entrenamiento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const categoriasPypSalud = <?php echo json_encode($categorias_pyp_salud, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const categoriasPersonalizadas = <?php echo json_encode($categorias_personalizadas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const categoriaInicial = <?php echo json_encode($act_edit['categoria'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const otraCategoriaValue = '__otra_categoria__';
            const customCategoryPanel = document.getElementById('customCategoryPanel');
            const categoriaPersonalizada = document.getElementById('categoria_personalizada');
            let questionCounter = 0;
            let materialCounter = 0;

            if (modalidadGrid && typeCategoryGrid) {
                typeCategoryGrid.after(modalidadGrid);
            }
            if (evaluationChoiceGroup && modalidadGrid) {
                modalidadGrid.after(evaluationChoiceGroup);
            }
            if (courseBuilder && evaluationChoiceGroup) {
                evaluationChoiceGroup.after(courseBuilder);
            }

            function isSelfPacedCourse() {
                return modalidadActividad?.value === 'Sistema';
            }

            function requiresEvaluation() {
                return isSelfPacedCourse() || document.querySelector('input[name="requiere_evaluacion"]:checked')?.value === '1';
            }

            function updateContentMode() {
                return;
            }

            function categoryOptionsForType(type) {
                let options = categoriasBase;
                if (type === 'Inducción' || type === 'Re Inducción') {
                    options = ['Legal'];
                } else if (type === 'Entrenamiento') {
                    options = categoriasEntrenamiento;
                } else if (type === 'Campaña PyP en Salud') {
                    options = categoriasPypSalud;
                }
                const customOptions = Array.isArray(categoriasPersonalizadas[type]) ? categoriasPersonalizadas[type] : [];
                return [...new Set([...options, ...customOptions].filter(Boolean))];
            }

            function toggleCustomCategoryPanel() {
                const active = categoriaCapacitacion?.value === otraCategoriaValue;
                customCategoryPanel?.classList.toggle('active', active);
                if (categoriaPersonalizada) {
                    categoriaPersonalizada.required = active;
                    if (active) categoriaPersonalizada.focus();
                    if (!active) categoriaPersonalizada.value = '';
                }
            }

            function updateCategoryOptions() {
                if (!categoriaCapacitacion || !tipoCapacitacion) return;
                const currentValue = categoriaCapacitacion.value;
                const previous = currentValue && currentValue !== otraCategoriaValue ? currentValue : categoriaInicial;
                const options = categoryOptionsForType(tipoCapacitacion.value);
                categoriaCapacitacion.innerHTML = '<option value="">Selecciona...</option>';
                options.forEach(optionText => {
                    const option = document.createElement('option');
                    option.value = optionText;
                    option.textContent = optionText;
                    if (optionText === previous) option.selected = true;
                    categoriaCapacitacion.appendChild(option);
                });
                if (previous && previous !== otraCategoriaValue && !options.includes(previous)) {
                    const currentOption = document.createElement('option');
                    currentOption.value = previous;
                    currentOption.textContent = previous;
                    currentOption.selected = true;
                    categoriaCapacitacion.appendChild(currentOption);
                }
                const otherOption = document.createElement('option');
                otherOption.value = otraCategoriaValue;
                otherOption.textContent = 'Otra categoría...';
                if (previous === otraCategoriaValue) otherOption.selected = true;
                categoriaCapacitacion.appendChild(otherOption);
                if (options.length === 1 && !categoriaCapacitacion.value && tipoCapacitacion.value !== '') {
                    categoriaCapacitacion.value = options[0];
                }
                toggleCustomCategoryPanel();
            }

            function updateCourseMode() {
                updateCategoryOptions();
                const systemMode = isSelfPacedCourse();
                if (systemMode) {
                    const yesRadio = document.querySelector('input[name="requiere_evaluacion"][value="1"]');
                    if (yesRadio) yesRadio.checked = true;
                }
                evaluationRadios.forEach(radio => {
                    radio.disabled = systemMode && radio.value === '0';
                });
                const evaluationActive = requiresEvaluation();
                courseBuilder?.classList.toggle('active', evaluationActive);
                systemContentFields.forEach(field => field.classList.toggle('course-hidden', !systemMode));
                if (systemCourseNote) systemCourseNote.classList.toggle('course-hidden', !systemMode);
                if (builderTitle) builderTitle.textContent = systemMode ? 'Contenido y evaluación' : 'Evaluación de la actividad';
                if (builderSubtitle) builderSubtitle.textContent = systemMode
                    ? 'Configura el material autogestionado y el examen de aprobación.'
                    : 'Crea el examen que el trabajador presentará después de la actividad.';
                if (googleBanner) googleBanner.style.display = systemMode ? 'none' : '';
                if (lugarGroup) lugarGroup.style.display = systemMode ? 'none' : '';
                if (modalidadGrid) modalidadGrid.style.gridTemplateColumns = systemMode ? '1fr' : '';
                if (evaluationHint) evaluationHint.textContent = systemMode
                    ? 'En modalidad Sistema la evaluación es obligatoria para completar el curso y firmar el acta.'
                    : 'Google Calendar seguirá creando la reunión. La evaluación se realizará después desde PreventWork.';
                document.querySelector('.schedule-heading h2').textContent = systemMode ? 'Disponibilidad' : 'Programación';
                document.querySelector('.schedule-heading p').textContent = systemMode
                    ? 'Define desde cuándo y hasta cuándo estará disponible.'
                    : 'Indica cuándo inicia y finaliza.';
                updateContentMode();
            }

            function updateScoreScale(event) {
                if (!escalaCalificacion || !puntajeAprobacion) return;
                const scale = Number(escalaCalificacion.value);
                const defaults = {5: 3, 10: 6, 100: 60};
                puntajeAprobacion.max = String(scale);
                if (event || Number(puntajeAprobacion.value) > scale || Number(puntajeAprobacion.value) <= 0) {
                    puntajeAprobacion.value = defaults[scale];
                }
                distributeQuestionPoints();
            }

            function distributeQuestionPoints() {
                const editors = [...questionList.querySelectorAll('.question-editor')];
                if (!editors.length) return;
                const scale = Number(escalaCalificacion?.value || 100);
                const base = Math.floor((scale / editors.length) * 100) / 100;
                let used = 0;
                editors.forEach((editor, index) => {
                    const value = index === editors.length - 1 ? Math.round((scale - used) * 100) / 100 : base;
                    editor.querySelector('.question-points').value = value;
                    used += value;
                    const label = editor.querySelector('.question-number');
                    if (label) label.innerHTML = `<i class="fa-solid fa-circle-question"></i> Pregunta ${index + 1}`;
                });
            }

            function materialBodyTemplate(key, type, content = '', file = '') {
                if (type === 'texto') {
                    return `<textarea class="material-content" placeholder="Escribe el contenido completo de esta sección...">${escapeHtml(content)}</textarea>`;
                }
                if (type === 'enlace') {
                    return `<input type="url" class="material-content" value="${escapeHtml(content)}" placeholder="https://...">`;
                }
                const accepts = type === 'video' ? '.mp4,.webm,.mov' : (type === 'imagen' ? '.jpg,.jpeg,.png,.webp' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt');
                return `<div class="upload-field"><input type="file" name="material_file_${key}" accept="${accepts}"></div>
                    ${file ? `<div class="material-existing"><i class="fa-solid fa-paperclip"></i> Archivo actual: ${escapeHtml(file.split('/').pop())}</div>` : ''}`;
            }

            function addMaterial(data = {}) {
                const key = `m${Date.now()}_${++materialCounter}`;
                const type = data.tipo || 'texto';
                const editor = document.createElement('div');
                editor.className = 'material-editor';
                editor.dataset.materialKey = key;
                editor.dataset.existingFile = data.archivo || '';
                editor.innerHTML = `
                    <div class="material-head">
                        <span class="material-number"><i class="fa-solid fa-layer-group"></i> Sección ${materialList.children.length + 1}</span>
                        <button type="button" class="btn-remove-question remove-material" title="Eliminar material"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="material-grid">
                        <input type="text" class="material-title" value="${escapeHtml(data.titulo || '')}" placeholder="Título de la sección">
                        <div class="material-type-wrap">
                            <span class="material-type-icon"><i class="fa-solid ${materialTypeIcon(type)}"></i></span>
                            <select class="material-type" aria-label="Tipo de material">
                                <option value="texto" ${type === 'texto' ? 'selected' : ''}>Lectura o texto</option>
                                <option value="video" ${type === 'video' ? 'selected' : ''}>Archivo de video</option>
                                <option value="enlace" ${type === 'enlace' ? 'selected' : ''}>Enlace externo</option>
                                <option value="documento" ${type === 'documento' ? 'selected' : ''}>Documento o presentación</option>
                                <option value="imagen" ${type === 'imagen' ? 'selected' : ''}>Imagen</option>
                            </select>
                            <i class="fa-solid fa-chevron-down material-type-arrow"></i>
                        </div>
                    </div>
                    <div class="material-body">${materialBodyTemplate(key, type, data.contenido || '', data.archivo || '')}</div>`;
                materialList.appendChild(editor);
                renumberMaterials();
            }

            function renumberMaterials() {
                [...materialList.querySelectorAll('.material-editor')].forEach((editor, index) => {
                    editor.querySelector('.material-number').innerHTML = `<i class="fa-solid fa-layer-group"></i> Sección ${index + 1}`;
                });
            }

            function materialTypeIcon(type) {
                return {
                    texto: 'fa-align-left',
                    video: 'fa-circle-play',
                    enlace: 'fa-link',
                    documento: 'fa-file-lines',
                    imagen: 'fa-image'
                }[type] || 'fa-layer-group';
            }

            function serializeMaterials() {
                return [...materialList.querySelectorAll('.material-editor')].map(editor => ({
                    key: editor.dataset.materialKey,
                    titulo: editor.querySelector('.material-title').value.trim(),
                    tipo: editor.querySelector('.material-type').value,
                    contenido: editor.querySelector('.material-content')?.value.trim() || '',
                    archivo_actual: editor.dataset.existingFile || ''
                }));
            }

            function optionTemplate(questionId, option = {}, optionIndex = 0, inputType = 'radio') {
                return `
                    <div class="option-row">
                        <input type="${inputType}" name="correct_${questionId}" ${option.correcta ? 'checked' : ''} aria-label="Respuesta correcta">
                        <input type="text" class="option-text" value="${escapeHtml(option.texto || '')}" placeholder="Opción ${optionIndex + 1}">
                        <button type="button" class="btn-option remove-option" title="Eliminar opción"><i class="fa-solid fa-xmark"></i></button>
                    </div>`;
            }

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[char]);
            }

            function addQuestion(data = {}) {
                const id = ++questionCounter;
                const type = data.tipo || 'unica';
                const options = data.opciones?.length ? data.opciones : [
                    { texto: type === 'verdadero_falso' ? 'Verdadero' : '', correcta: true },
                    { texto: type === 'verdadero_falso' ? 'Falso' : '', correcta: false }
                ];
                const inputType = type === 'multiple' ? 'checkbox' : 'radio';
                const editor = document.createElement('div');
                editor.className = 'question-editor';
                editor.dataset.questionId = id;
                editor.innerHTML = `
                    <div class="question-heading">
                        <span class="question-number"><i class="fa-solid fa-circle-question"></i> Pregunta ${questionList.children.length + 1}</span>
                        <button type="button" class="btn-remove-question" title="Eliminar pregunta"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="question-top">
                        <div class="question-field">
                            <label>Enunciado</label>
                            <input type="text" class="question-text" value="${escapeHtml(data.enunciado || '')}" placeholder="¿Qué deseas evaluar?">
                        </div>
                        <div class="points-field">
                            <label>Puntos</label>
                            <input type="number" class="question-points" min="0.01" step="0.01" value="${data.puntos || 1}" readonly>
                        </div>
                    </div>
                    <input type="hidden" class="question-type" value="${type}">
                    <div class="question-type-switch">
                        <button type="button" class="type-choice ${type === 'unica' ? 'active' : ''}" data-type="unica"><i class="fa-regular fa-circle-dot"></i> Una respuesta</button>
                        <button type="button" class="type-choice ${type === 'multiple' ? 'active' : ''}" data-type="multiple"><i class="fa-regular fa-square-check"></i> Varias respuestas</button>
                        <button type="button" class="type-choice ${type === 'verdadero_falso' ? 'active' : ''}" data-type="verdadero_falso"><i class="fa-solid fa-scale-balanced"></i> Verdadero / Falso</button>
                    </div>
                    <div class="answers-label">Opciones · marca la respuesta correcta</div>
                    <div class="option-list">${options.map((option, index) => optionTemplate(id, option, index, inputType)).join('')}</div>
                    <button type="button" class="btn-option add-option" style="${type === 'verdadero_falso' ? 'display:none' : ''}"><i class="fa-solid fa-plus"></i> Agregar opción</button>`;
                questionList.appendChild(editor);
                distributeQuestionPoints();
            }

            function resetOptionsForType(editor, type) {
                editor.querySelector('.question-type').value = type;
                editor.querySelectorAll('.type-choice').forEach(button => button.classList.toggle('active', button.dataset.type === type));
                const options = type === 'verdadero_falso'
                    ? [{ texto: 'Verdadero', correcta: true }, { texto: 'Falso', correcta: false }]
                    : [{ texto: '', correcta: true }, { texto: '', correcta: false }];
                const inputType = type === 'multiple' ? 'checkbox' : 'radio';
                const id = editor.dataset.questionId;
                editor.querySelector('.option-list').innerHTML = options.map((option, index) => optionTemplate(id, option, index, inputType)).join('');
                editor.querySelector('.add-option').style.display = type === 'verdadero_falso' ? 'none' : '';
            }

            function serializeQuestions() {
                return [...questionList.querySelectorAll('.question-editor')].map(editor => ({
                    enunciado: editor.querySelector('.question-text').value.trim(),
                    tipo: editor.querySelector('.question-type').value,
                    puntos: parseFloat(editor.querySelector('.question-points').value) || 1,
                    opciones: [...editor.querySelectorAll('.option-row')].map(row => ({
                        texto: row.querySelector('.option-text').value.trim(),
                        correcta: row.querySelector('input[type="radio"], input[type="checkbox"]').checked
                    })).filter(option => option.texto)
                }));
            }

            tipoCapacitacion?.addEventListener('change', updateCourseMode);
            categoriaCapacitacion?.addEventListener('change', toggleCustomCategoryPanel);
            modalidadActividad?.addEventListener('change', updateCourseMode);
            evaluationRadios.forEach(radio => radio.addEventListener('change', updateCourseMode));
            escalaCalificacion?.addEventListener('change', updateScoreScale);
            document.querySelectorAll('input[name="tipo_contenido"]').forEach(input => input.addEventListener('change', updateContentMode));
            document.getElementById('btnAddQuestion')?.addEventListener('click', () => addQuestion());
            document.getElementById('btnAddMaterial')?.addEventListener('click', () => addMaterial());
            materialList?.addEventListener('click', event => {
                if (event.target.closest('.remove-material')) {
                    event.target.closest('.material-editor')?.remove();
                    renumberMaterials();
                }
            });
            materialList?.addEventListener('change', event => {
                if (!event.target.classList.contains('material-type')) return;
                const editor = event.target.closest('.material-editor');
                editor.dataset.existingFile = '';
                editor.querySelector('.material-type-icon').innerHTML = `<i class="fa-solid ${materialTypeIcon(event.target.value)}"></i>`;
                editor.querySelector('.material-body').innerHTML = materialBodyTemplate(
                    editor.dataset.materialKey, event.target.value
                );
            });
            questionList?.addEventListener('click', event => {
                const editor = event.target.closest('.question-editor');
                if (event.target.closest('.btn-remove-question')) {
                    editor?.remove();
                    distributeQuestionPoints();
                }
                const typeButton = event.target.closest('.type-choice');
                if (typeButton && editor) resetOptionsForType(editor, typeButton.dataset.type);
                if (event.target.closest('.remove-option')) event.target.closest('.option-row')?.remove();
                if (event.target.closest('.add-option') && editor) {
                    const type = editor.querySelector('.question-type').value;
                    const list = editor.querySelector('.option-list');
                    list.insertAdjacentHTML('beforeend', optionTemplate(
                        editor.dataset.questionId, {}, list.children.length, type === 'multiple' ? 'checkbox' : 'radio'
                    ));
                }
            });
            (initialMaterials.length ? initialMaterials : [{titulo: 'Bienvenida', tipo: 'texto', contenido: ''}]).forEach(addMaterial);
            (initialQuestions.length ? initialQuestions : [{}]).forEach(addQuestion);
            updateScoreScale();
            updateCourseMode();

            const radiosDirigido = document.querySelectorAll('input[name="dirigido_a"]');
            const contenedorTrabajadores = document.getElementById('contenedor_trabajadores_especificos');
            const checkboxes = document.querySelectorAll('.chk-trabajador');
            
            radiosDirigido.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'Trabajador Específico') {
                        contenedorTrabajadores.style.display = 'block';
                        const cards = document.querySelectorAll('.fade-in-card');
                        cards.forEach(card => {
                            card.style.animation = 'none';
                            card.offsetHeight; 
                            card.style.animation = null; 
                        });
                    } else {
                        contenedorTrabajadores.style.display = 'none';
                        // No desmarcamos checkboxes por si se arrepiente, a menos que guarde.
                    }
                });
            });

            const buscarInput = document.getElementById('buscarTrabajadorInput');
            const listaTrabajadores = document.getElementById('listaTrabajadoresContainer');

            if (buscarInput && listaTrabajadores) {
                buscarInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    const items = listaTrabajadores.querySelectorAll('.trabajador-item');
                    
                    items.forEach(item => {
                        const text = item.innerText.toLowerCase();
                        if (text.includes(term)) {
                            item.style.display = 'flex'; 
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            if (formActividad) {
                formActividad.addEventListener('submit', function(e) {
                    const radioSeleccionado = document.querySelector('input[name="dirigido_a"]:checked');
                    if (radioSeleccionado && radioSeleccionado.value === 'Trabajador Específico') {
                        const seleccionados = document.querySelectorAll('.chk-trabajador:checked');
                        if (seleccionados.length === 0) {
                            e.preventDefault(); 
                            alert('Debes seleccionar al menos un trabajador de la lista.');
                            return;
                        }
                    }

                    if (requiresEvaluation()) {
                        const questions = serializeQuestions();
                        const invalid = questions.length === 0 || questions.some(question =>
                            !question.enunciado || question.opciones.length < 2 || !question.opciones.some(option => option.correcta)
                        );
                        if (invalid) {
                            e.preventDefault();
                            alert('Revisa el examen: cada pregunta necesita enunciado, dos opciones y al menos una respuesta correcta.');
                            return;
                        }
                        preguntasJson.value = JSON.stringify(questions);
                        if (isSelfPacedCourse()) {
                            const materials = serializeMaterials();
                            const invalidMaterials = materials.length === 0 || materials.some(material =>
                                !material.titulo || (material.tipo === 'texto' && !material.contenido) ||
                                (material.tipo === 'enlace' && !material.contenido)
                            );
                            if (invalidMaterials) {
                                e.preventDefault();
                                alert('Revisa los materiales: cada sección necesita título y contenido.');
                                return;
                            }
                            materialesJson.value = JSON.stringify(materials);
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>
