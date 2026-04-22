<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// ========================================================
// 1. OBTENER EL ID DE LA EMPRESA DEL USUARIO ACTUAL
// ========================================================
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$_SESSION['usuario_id']]);
$empresa_id = $stmt_emp->fetchColumn();

$nit_empresa = '';
$nombre_empresa = '';

if ($empresa_id) {
    // Buscar primero si el Representante Legal ya configuró el NIT oficial
    $stmt_rep = $conn->prepare("SELECT num_doc_empresa, nombre_empresa FROM usuarios WHERE empresa_id = ? AND rol = 'representante' LIMIT 1");
    $stmt_rep->execute([$empresa_id]);
    $rep_data = $stmt_rep->fetch(PDO::FETCH_ASSOC);

    if ($rep_data && !empty($rep_data['num_doc_empresa'])) {
        $nit_empresa = $rep_data['num_doc_empresa'];
        $nombre_empresa = $rep_data['nombre_empresa'];
    } else {
        // Fallback: Tomar datos de la tabla de solicitudes originales
        $stmt_sol = $conn->prepare("SELECT cedula, nombre FROM solicitudes_empresas WHERE id = ?");
        $stmt_sol->execute([$empresa_id]);
        $sol_data = $stmt_sol->fetch(PDO::FETCH_ASSOC);
        if ($sol_data) {
            $nit_empresa = $sol_data['cedula'];
            $nombre_empresa = $sol_data['nombre'];
        }
    }
}

// ========================================================
// 1.5 OBTENER TRABAJADORES DE LA EMPRESA (PARA ANÁLISIS)
// ========================================================
$trabajadores_empresa = [];
if ($empresa_id) {
    $stmt_trab = $conn->prepare("SELECT nombre, apellido, cedula FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador' AND activo = 1");
    $stmt_trab->execute([$empresa_id]);
    $trabajadores_empresa = $stmt_trab->fetchAll(PDO::FETCH_ASSOC);
}

// ========================================================
// 2. CÁLCULO DE DÍAS HÁBILES Y FECHAS LÍMITE (DECRETO 1990/2016)
// ========================================================
$anio_seleccionado = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

$bloquear_subida = empty($nit_empresa);
$dia_habil = 0;
$ultimos_digitos = '';
$fecha_limite_actual = '';
$fechas_limites_meses = []; 

if (!$bloquear_subida) {
    $doc_parts = explode('-', $nit_empresa);
    $doc_base = $doc_parts[0];
    $doc_clean = preg_replace('/[^0-9]/', '', $doc_base);

    $last_two = substr($doc_clean, -2);
    if (strlen($last_two) < 2) $last_two = str_pad($last_two, 2, '0', STR_PAD_LEFT);

    $last_two_num = (int) $last_two;
    $ultimos_digitos = $last_two;

    if ($last_two_num >= 0 && $last_two_num <= 7) $dia_habil = 2;
    elseif ($last_two_num >= 8 && $last_two_num <= 14) $dia_habil = 3;
    elseif ($last_two_num >= 15 && $last_two_num <= 21) $dia_habil = 4;
    elseif ($last_two_num >= 22 && $last_two_num <= 28) $dia_habil = 5;
    elseif ($last_two_num >= 29 && $last_two_num <= 35) $dia_habil = 6;
    elseif ($last_two_num >= 36 && $last_two_num <= 42) $dia_habil = 7;
    elseif ($last_two_num >= 43 && $last_two_num <= 49) $dia_habil = 8;
    elseif ($last_two_num >= 50 && $last_two_num <= 56) $dia_habil = 9;
    elseif ($last_two_num >= 57 && $last_two_num <= 63) $dia_habil = 10;
    elseif ($last_two_num >= 64 && $last_two_num <= 69) $dia_habil = 11;
    elseif ($last_two_num >= 70 && $last_two_num <= 75) $dia_habil = 12;
    elseif ($last_two_num >= 76 && $last_two_num <= 81) $dia_habil = 13;
    elseif ($last_two_num >= 82 && $last_two_num <= 87) $dia_habil = 14;
    elseif ($last_two_num >= 88 && $last_two_num <= 93) $dia_habil = 15;
    elseif ($last_two_num >= 94 && $last_two_num <= 99) $dia_habil = 16;

    $mes_actual = date('n');
    $anio_act = date('Y');

    $count = 0;
    $dia = 1;
    $dias_del_mes = date('t', mktime(0, 0, 0, $mes_actual, 1, $anio_act));

    while ($dia <= $dias_del_mes) {
        $timestamp = mktime(0, 0, 0, $mes_actual, $dia, $anio_act);
        $w = date('w', $timestamp);
        if ($w != 0 && $w != 6) { $count++; }

        if ($count == $dia_habil) {
            $meses_nom = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $fecha_limite_actual = date('d', $timestamp) . ' de ' . $meses_nom[$mes_actual] . ' de ' . $anio_act;
            break;
        }
        $dia++;
    }

    for ($m = 1; $m <= 12; $m++) {
        $count_mes = 0;
        $dia_mes = 1;
        $dias_totales_mes = date('t', mktime(0, 0, 0, $m, 1, $anio_seleccionado));

        while ($dia_mes <= $dias_totales_mes) {
            $ts = mktime(0, 0, 0, $m, $dia_mes, $anio_seleccionado);
            $w_dia = date('w', $ts);
            if ($w_dia != 0 && $w_dia != 6) { $count_mes++; }

            if ($count_mes == $dia_habil) {
                $fechas_limites_meses[$m] = date('d/m/Y', $ts);
                break;
            }
            $dia_mes++;
        }
    }
}

// ========================================================
// 3. OBTENER PLANILLAS POR AÑO SELECCIONADO
// ========================================================
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.nombre, u.apellido 
        FROM estandar2_planillas p
        JOIN usuarios u ON p.subido_por = u.id
        WHERE u.empresa_id = ? AND p.anio = ?
    ");
    $stmt->execute([$empresa_id, $anio_seleccionado]);
    $planillas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $planillas_mes = [];
    foreach ($planillas_raw as $p) {
        $planillas_mes[$p['mes']] = $p;
    }
} catch (PDOException $e) {
    $planillas_mes = [];
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$current_page = 'estandar2.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 2 | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }
        .panel-container { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
        .card-box { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        .card-title { margin: 0 0 20px 0; font-size: 1rem; color: var(--text); font-weight: 800; display: flex; align-items: center; gap: 8px; border-bottom: 1px dashed var(--border); padding-bottom: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .custom-select { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; font-weight: 600; color: var(--text); box-sizing: border-box; background-color: #f8fafc; cursor: pointer; appearance: none; transition: all 0.2s; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; }
        .custom-select:focus, .custom-input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .custom-input { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; font-weight: 600; box-sizing: border-box; background: #f8fafc; transition: all 0.2s; }
        .info-pila-box { background: rgba(255, 138, 31, 0.05); border: 1px solid rgba(255, 138, 31, 0.2); border-radius: 12px; padding: 18px; margin-bottom: 24px; }
        .info-pila-box p { margin: 0 0 8px 0; font-size: 0.85rem; color: #334155; }
        .info-pila-box .highlight { color: var(--primary2); font-weight: 800; font-size: 0.9rem; margin-left: 4px; }
        .info-pila-box .fecha-roja { color: #dc2626; font-weight: 800; font-size: 0.85rem; display: block; margin-top: 4px; }
        .dropzone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
        .dropzone:hover, .dropzone.dragover { border-color: var(--primary); background: rgba(255, 138, 31, 0.05); }
        .dropzone svg { color: var(--primary); margin-bottom: 4px; }
        .dropzone p { margin: 0; font-size: 0.85rem; font-weight: 700; color: var(--text); }
        .dropzone span { font-size: 0.7rem; color: var(--muted); font-weight: 500; }
        .preview-container { display: none; margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        .preview-header { background: #f8fafc; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .preview-header span { font-size: 0.75rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 6px; }
        .btn-change-file { background: #fee2e2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-change-file:hover { background: #fca5a5; }
        
        /* PANEL DE ANÁLISIS */
        .analysis-panel { background: #1e293b; color: #f8fafc; padding: 16px; border-bottom: 1px solid #334155; font-size: 0.8rem; display: none; flex-direction: column; gap: 12px; }
        .analysis-title { font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 6px; font-size: 0.85rem; margin-bottom: 4px; }
        .analysis-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .analysis-item { background: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .analysis-label { color: #94a3b8; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 4px; display: block; }
        .analysis-val { font-weight: 700; font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px; }
        .status-ok { color: #10b981; }
        .status-warn { color: #f59e0b; }
        .status-err { color: #ef4444; }
        .pdf-viewer { width: 100%; height: 280px; border: none; display: block; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; width: 100%; cursor: pointer; transition: transform 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; font-family: inherit; margin-top: 24px; box-shadow: 0 4px 12px rgba(255, 138, 31, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.3); }
        .year-selector { display: flex; align-items: center; gap: 12px; }
        .year-btn { background: #f1f5f9; color: #475569; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .year-btn:hover { background: #e2e8f0; color: #1e293b; }
        .year-display { font-size: 1.05rem; font-weight: 800; color: var(--blue-dark); }
        .months-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }
        .month-card { border: 1px solid var(--border); border-radius: 12px; padding: 16px; background: #ffffff; display: flex; flex-direction: column; gap: 12px; transition: all 0.2s; position: relative; }
        .month-card:hover { box-shadow: 0 6px 15px rgba(0, 0, 0, 0.04); border-color: #cbd5e1; }
        .month-card.loaded { border-top: 4px solid #10b981; }
        .month-card.missing { border-top: 4px solid #cbd5e1; }
        .month-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .month-name { font-weight: 800; color: var(--blue-dark); font-size: 1rem; display: block; }
        .status-badge { font-size: 0.6rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; height: fit-content; }
        .status-loaded { background: #dcfce7; color: #166534; }
        .status-missing { background: #f1f5f9; color: #64748b; }
        .month-info { font-size: 0.75rem; color: var(--muted); line-height: 1.5; flex: 1; }
        .month-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn-view { background: #e0f2fe; color: #0284c7; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; transition: 0.2s; flex: 1; }
        .btn-view:hover { background: #bae6fd; }
        .btn-delete { background: #fee2e2; color: #dc2626; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; transition: 0.2s; flex: 1; }
        .btn-delete:hover { background: #ef4444; color: white; }
        .btn-outline-upload { background: #fff8f3; border: 1px dashed var(--primary); color: var(--primary2); padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; width: 100%; font-family: inherit; }
        .btn-outline-upload:hover { background: var(--primary); color: white; border-style: solid; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        #loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 999999; display: none; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        #loader-overlay.active { display: flex; }
        .spinner { animation: spin 1s linear infinite; color: var(--primary); }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .panel-container { grid-template-columns: 1fr; gap: 16px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .btn-back { order: -1; width: max-content; align-self: flex-start; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; margin: 0 0 4px 0; }
            .estandar-header-group { display: flex; flex-direction: row; align-items: flex-start; text-align: left; gap: 12px; width: 100%; }
            .icon-box-std { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; margin-top: 2px; }
            .icon-box-std svg { width: 20px; height: 20px; }
            .estandar-header-text { display: flex; flex-direction: column; }
            .estandar-title { font-size: 1.05rem; line-height: 1.3; margin: 0; display: block; }
            .estandar-subtitle { font-size: 0.75rem; line-height: 1.4; margin-top: 4px; }
            .card-box { padding: 20px 16px; }
            .info-pila-box { padding: 14px; }
            .info-pila-box p { font-size: 0.8rem; }
            .dropzone { padding: 20px 12px; }
            .dropzone p { font-size: 0.8rem; }
            .months-grid { grid-template-columns: 1fr; gap: 12px; }
            .analysis-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <div id="loader-overlay">
        <svg class="spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="60" height="60">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <h3 style="margin-top:20px; font-weight: 800; color: var(--blue-dark);">Guardando documento...</h3>
        <p style="color: var(--muted); margin: 5px 0 0 0;">Por favor no cierres la ventana.</p>
    </div>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'subido'): ?>
                <div class="alert alert-success">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Planilla guardada y asignada correctamente.
                </div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
                <div class="alert alert-danger">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    La planilla ha sido eliminada del historial.
                </div>
            <?php endif; ?>

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title"><span class="std-num-marker">2.</span> Afiliación al Sistema de Seguridad Social</h1>
                        <p class="estandar-subtitle">Control y carga mensual de la planilla de pago de Seguridad Social (PILA).</p>
                    </div>
                </div>
                <a href="dashboard.php?std=2" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>

            <div class="panel-container">
                <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                    <div class="card-box" style="align-self: start;">
                        <h3 class="card-title">Subir Nueva Planilla</h3>

                        <?php if ($bloquear_subida): ?>
                            <div class="alert alert-danger" style="flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 0;">
                                <div style="display:flex; gap: 8px; align-items:center;">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <strong style="font-size: 0.9rem;">¡Falta el NIT de la Empresa!</strong>
                                </div>
                                <p style="margin:0; font-size: 0.8rem; font-weight: 500; line-height: 1.5; color: #7f1d1d;">
                                    No podemos calcular tu fecha límite de pago (Decreto 1990 de 2016) porque no tienes el <strong>NIT</strong> configurado.
                                </p>
                                <?php if ($usuario_rol === 'representante'): ?>
                                    <a href="configuracion.php" class="btn-primary" style="margin-top: 4px; width: auto; font-size: 0.8rem; padding: 10px 16px; background: #dc2626; color: white; text-decoration: none;">Completar Perfil de Empresa</a>
                                <?php else: ?>
                                    <p style="margin:0; font-size: 0.8rem; font-weight: 700; color: #991b1b; padding: 8px; background: rgba(220,38,38,0.1); border-radius: 6px;">El Representante Legal debe actualizar el "Perfil Corporativo" en Configuración.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>

                            <div class="info-pila-box">
                                <p style="margin-bottom: 12px; font-weight: 800; font-size: 0.75rem; color: var(--primary2); display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Cálculo Automático PILA
                                </p>
                                <p>Empresa: <strong style="color: var(--blue-dark);"><?php echo htmlspecialchars($nombre_empresa); ?></strong></p>
                                <p>NIT finaliza en: <span class="highlight"><?php echo $ultimos_digitos; ?></span></p>
                                <p>Día hábil límite: <span class="highlight"><?php echo $dia_habil; ?>°</span></p>

                                <div style="margin-top: 16px; background: #ffffff; border-radius: 8px; padding: 12px; border: 1px solid rgba(255,138,31,0.2);">
                                    <span style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--muted); margin-bottom: 4px; text-transform: uppercase;">Límite sugerido para este mes:</span>
                                    <span class="fecha-roja"><?php echo $fecha_limite_actual; ?></span>
                                </div>
                            </div>

                            <form action="procesar_estandar2.php" method="POST" enctype="multipart/form-data" onsubmit="mostrarLoader()">
                                <input type="hidden" name="accion" value="subir_planilla">

                                <div class="form-group">
                                    <label>Mes Correspondiente</label>
                                    <select name="mes" id="select_mes_upload" class="custom-select" required>
                                        <?php foreach ($meses as $num => $nombre): ?>
                                            <option value="<?php echo $num; ?>" <?php echo date('n') == $num ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Año</label>
                                    <input type="number" name="anio" id="input_anio_upload" class="custom-input" value="<?php echo $anio_seleccionado; ?>" min="2020" max="2050" required>
                                </div>

                                <div class="form-group">
                                    <label>Documento PDF (PILA)</label>

                                    <div class="dropzone" id="dropzone">
                                        <input type="file" name="archivo" id="archivoPdf" accept="application/pdf" required hidden>
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p>Haz clic o arrastra tu PDF</p>
                                        <span>Solo se aceptan archivos .pdf</span>
                                    </div>

                                    <div class="preview-container" id="previewContainer">

                                        <div class="analysis-panel" id="analysisPanel">
                                            <div class="analysis-title">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                                </svg>
                                                Análisis Rápido del Documento
                                            </div>
                                            <div class="analysis-grid">
                                                <div class="analysis-item">
                                                    <span class="analysis-label">Validación NIT</span>
                                                    <span class="analysis-val" id="resNit">Cargando...</span>
                                                </div>
                                                <div class="analysis-item">
                                                    <span class="analysis-label">Cruce de Trabajadores</span>
                                                    <span class="analysis-val" id="resTrabajadores">Cargando...</span>
                                                </div>
                                                <div class="analysis-item" style="grid-column: 1 / -1;">
                                                    <span class="analysis-label">Novedades Detectadas (Aprox)</span>
                                                    <span class="analysis-val" id="resNovedades" style="font-weight: 500;">Cargando...</span>
                                                </div>
                                            </div>
                                            <div style="font-size: 0.65rem; color: #64748b; font-style: italic; margin-top: 4px;">
                                                *Nota: Este análisis automatizado exige que la cédula aparezca como un número exacto en la tabla de la PILA.
                                            </div>
                                        </div>

                                        <div class="preview-header">
                                            <span>
                                                <svg fill="none" stroke="#ef4444" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                Vista previa
                                            </span>
                                            <button type="button" class="btn-change-file" id="btnRemovePdf">Cambiar</button>
                                        </div>
                                        <iframe id="pdfViewer" class="pdf-viewer"></iframe>
                                    </div>
                                </div>

                                <button type="submit" class="btn-primary" id="btnSubmitForm" style="display: none;">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Guardar Planilla
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card-box" <?php echo ($usuario_rol === 'trabajador') ? 'style="grid-column: span 2;"' : ''; ?>>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px dashed var(--border); padding-bottom: 12px;">
                        <h3 class="card-title" style="border: none; padding: 0; margin: 0;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" style="color: var(--primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            Planillas del Año
                        </h3>

                        <div class="year-selector">
                            <a href="?anio=<?php echo $anio_seleccionado - 1; ?>" class="year-btn" title="Año Anterior">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            <span class="year-display"><?php echo $anio_seleccionado; ?></span>
                            <a href="?anio=<?php echo $anio_seleccionado + 1; ?>" class="year-btn" title="Año Siguiente">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="months-grid">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <?php
                            $is_loaded = isset($planillas_mes[$m]);
                            $mes_nombre = $meses[$m];
                            ?>
                            <div class="month-card <?php echo $is_loaded ? 'loaded' : 'missing'; ?>">
                                <div class="month-header">
                                    <div>
                                        <span class="month-name"><?php echo $mes_nombre; ?></span>
                                        <?php if (!$bloquear_subida && isset($fechas_limites_meses[$m])): ?>
                                            <div style="font-size: 0.65rem; color: #64748b; margin-top: 4px;">
                                                Límite: <strong style="color: #dc2626; font-weight: 800;"><?php echo $fechas_limites_meses[$m]; ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-badge <?php echo $is_loaded ? 'status-loaded' : 'status-missing'; ?>">
                                        <?php echo $is_loaded ? 'Cargado' : 'Pendiente'; ?>
                                    </span>
                                </div>

                                <?php if ($is_loaded): ?>
                                    <?php $p = $planillas_mes[$m]; ?>
                                    <div class="month-info" style="margin-top: 6px;">
                                        <div style="display:flex; align-items:center; gap: 6px; margin-bottom: 6px;">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap: 6px;">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Subido el <?php echo date('d/m/Y', strtotime($p['fecha_subida'])); ?>
                                        </div>
                                    </div>
                                    <div class="month-actions">
                                        <a href="<?php echo htmlspecialchars($p['archivo_url']); ?>" target="_blank" class="btn-view" title="Ver Documento">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Ver
                                        </a>
                                        <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                                            <a href="#" onclick="showConfirmModal('Eliminar Planilla', '¿Estás seguro de eliminar la planilla de <?php echo $mes_nombre; ?>?', 'procesar_estandar2.php?accion=eliminar_planilla&id=<?php echo $p['id']; ?>', 'danger', 'Sí, eliminar'); return false;" class="btn-delete" title="Eliminar">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Borrar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="month-info" style="color: #94a3b8; font-style: italic; display: flex; align-items: center; margin-top: 6px;">
                                        No se ha subido el soporte para este periodo.
                                    </div>
                                    <?php if (!$bloquear_subida && ($usuario_rol === 'sst' || $usuario_rol === 'representante')): ?>
                                        <div class="month-actions">
                                            <button type="button" class="btn-outline-upload" onclick="prepararSubida(<?php echo $m; ?>, <?php echo $anio_seleccionado; ?>)">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                                Cargar Planilla
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include_once 'components/modal_confirmacion.php'; ?>

    <script>
        // ==========================================
        // VARIABLES PHP PARA EL MOTOR JS
        // ==========================================
        const dbNitEmpresa = "<?php echo htmlspecialchars($nit_empresa); ?>";
        const dbTrabajadores = <?php echo json_encode($trabajadores_empresa); ?>;

        // Cargar worker de PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        function mostrarLoader() {
            document.getElementById('loader-overlay').classList.add('active');
        }

        function prepararSubida(mes, anio) {
            document.getElementById('select_mes_upload').value = mes;
            document.getElementById('input_anio_upload').value = anio;
            if (window.innerWidth <= 768) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
            setTimeout(() => {
                document.getElementById('archivoPdf').click();
            }, 300);
        }

        // Función para inyectar datos al PHP
        function guardarDatoOculto(name, value) {
            const form = document.querySelector('form[action="procesar_estandar2.php"]');
            if (!form) return;
            let input = document.getElementById('hidden_' + name);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.id = 'hidden_' + name;
                form.appendChild(input);
            }
            input.value = value;
        }

        // =======================================================
        // MOTOR INTELIGENTE DE ANÁLISIS DE PDF (REGEX CON LÍMITES)
        // =======================================================
        async function analizarPDF(file) {
            const panel = document.getElementById('analysisPanel');
            const resNit = document.getElementById('resNit');
            const resTrabajadores = document.getElementById('resTrabajadores');
            const resNovedades = document.getElementById('resNovedades');

            panel.style.display = 'flex';
            resNit.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="14" height="14"><path fill="none" stroke="currentColor" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Analizando...';
            resTrabajadores.innerHTML = '...';
            resNovedades.innerHTML = '...';

            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;

                let textoCompleto = '';
                let headers = { ige: null, irl: null };
                let itemsGlobal = [];

                // 1. Extraer texto y buscar coordenadas de columnas
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();

                    content.items.forEach(item => {
                        itemsGlobal.push(item);
                        textoCompleto += item.str + ' ';

                        let text = item.str.trim().toUpperCase();
                        if (text === 'IGE') headers.ige = item.transform[4]; 
                        if (text === 'IRL') headers.irl = item.transform[4]; 
                    });
                }

                // 2. VALIDAR NIT (Usando Regex Segura)
                const nitBase = dbNitEmpresa.split('-')[0].replace(/\D/g, '');
                let nitCoincide = false;
                
                if (nitBase) {
                    // Crea un regex que permite espacios entre los números pero exige que sean aislados
                    let regexNitStr = "(?:^|\\D)" + nitBase.split('').join('\\s*') + "(?:\\D|$)";
                    let regexNit = new RegExp(regexNitStr);
                    if(regexNit.test(textoCompleto)) {
                        nitCoincide = true;
                    }
                }

                if (nitCoincide) {
                    resNit.innerHTML = `<span class="status-ok"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="vertical-align:text-bottom"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Coincide (${nitBase})</span>`;
                } else {
                    resNit.innerHTML = `<span class="status-err" title="No se detectó el NIT de la empresa en el documento"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="vertical-align:text-bottom"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> No Coincide / No legible</span>`;
                }

                // 3. VALIDACIÓN EXACTA DE TRABAJADORES (CERO FALSOS POSITIVOS)
                let empEncontradosGlobal = [];
                let empFaltantesGlobal = [];
                
                dbTrabajadores.forEach(t => {
                    const cedulaLimpia = t.cedula.replace(/\D/g, '');
                    if (cedulaLimpia) {
                        // Regex perfecta: Busca la cédula como una "palabra" aislada
                        let regexCedStr = "(?:^|\\D)" + cedulaLimpia.split('').join('\\s*') + "(?:\\D|$)";
                        let regexCed = new RegExp(regexCedStr);
                        
                        if (regexCed.test(textoCompleto)) {
                            empEncontradosGlobal.push(t);
                        } else {
                            empFaltantesGlobal.push(t);
                        }
                    }
                });

                // 4. CRUCE DE FILAS PARA NOVEDADES ("X")
                let lines = {};
                itemsGlobal.forEach(item => {
                    let y = Math.round(item.transform[5]);
                    let foundY = Object.keys(lines).find(ky => Math.abs(ky - y) <= 3);
                    if (foundY) y = foundY;
                    if (!lines[y]) lines[y] = [];
                    lines[y].push(item);
                });

                let incapacidades = [];

                for (let y in lines) {
                    let lineItems = lines[y];
                    // Mantener el texto original de la línea para usar el mismo Regex
                    let lineText = lineItems.map(i => i.str).join(' ');

                    dbTrabajadores.forEach(t => {
                        const cedulaLimpia = t.cedula.replace(/\D/g, '');
                        if (cedulaLimpia) {
                            let regexCedStr = "(?:^|\\D)" + cedulaLimpia.split('').join('\\s*') + "(?:\\D|$)";
                            let regexCed = new RegExp(regexCedStr);
                            
                            if (regexCed.test(lineText)) {
                                // Encontramos al trabajador en esta línea, buscamos si hay una "X"
                                lineItems.forEach(i => {
                                    if (i.str.trim().toUpperCase() === 'X') {
                                        let xCoord = i.transform[4];
                                        if (headers.ige !== null && Math.abs(xCoord - headers.ige) < 20) {
                                            incapacidades.push({ tipo: 'IGE', nombre: `${t.nombre} ${t.apellido}`, cedula: t.cedula });
                                        }
                                        if (headers.irl !== null && Math.abs(xCoord - headers.irl) < 20) {
                                            incapacidades.push({ tipo: 'IRL', nombre: `${t.nombre} ${t.apellido}`, cedula: t.cedula });
                                        }
                                    }
                                });
                            }
                        }
                    });
                }

                // 5. RESULTADOS TRABAJADORES (PANTALLA)
                let htmlTrab = '';
                if (dbTrabajadores.length === 0) {
                    resTrabajadores.innerHTML = `<span class="status-warn">No tienes trabajadores registrados</span>`;
                } else {
                    let countOk = empEncontradosGlobal.length;
                    let countTot = dbTrabajadores.length;
                    
                    htmlTrab += `<span>${countOk} de ${countTot} encontrados</span>`;
                    htmlTrab += `<div style="font-size:0.75rem; margin-top:6px; font-weight: normal; color: #94a3b8;">`;
                    
                    if (empFaltantesGlobal.length > 0) {
                        htmlTrab += `<span style="color:#ef4444; display:block; margin-bottom:2px;"><b>Faltan en PILA:</b></span>`;
                        empFaltantesGlobal.forEach(f => {
                            htmlTrab += `<span style="display:block; color:#fca5a5;">- ${f.nombre} ${f.apellido} (C.C. ${f.cedula})</span>`;
                        });
                    } else {
                        htmlTrab += `<span style="color:#10b981;"><b>¡Todos los trabajadores están al día!</b></span>`;
                    }
                    htmlTrab += `</div>`;
                    
                    resTrabajadores.innerHTML = htmlTrab;
                }

                // 6. RESULTADOS NOVEDADES (PANTALLA)
                if (incapacidades.length > 0) {
                    let htmlNov = incapacidades.map(inc => {
                        let color = inc.tipo === 'IGE' ? '#f59e0b' : '#ef4444';
                        return `<span style="color:${color}; display:block; margin-bottom:4px;">&#8226; <b>1 ${inc.tipo}</b> - ${inc.nombre} (C.C. ${inc.cedula})</span>`;
                    }).join('');
                    resNovedades.innerHTML = htmlNov;
                } else {
                    resNovedades.innerHTML = `<span style="color:#94a3b8; font-weight: normal;">No se encontraron cruces con "X" en IGE / IRL para tus trabajadores.</span>`;
                }

                // 7. INYECTAR DATOS PARA QUE PHP LOS GUARDE (OCULTOS)
                let nombresFaltantes = empFaltantesGlobal.map(f => f.nombre + " " + f.apellido).join(', ');
                
                guardarDatoOculto('nit_coincide', nitCoincide ? 'SI' : 'NO');
                guardarDatoOculto('trab_found', `${empEncontradosGlobal.length}/${dbTrabajadores.length}`);
                guardarDatoOculto('trab_faltantes', nombresFaltantes);
                guardarDatoOculto('incapacidades_json', JSON.stringify(incapacidades));

            } catch (error) {
                console.error("Error analizando PDF: ", error);
                resNit.innerHTML = '<span class="status-err">Error al leer PDF</span>';
                resTrabajadores.innerHTML = '-';
                resNovedades.innerHTML = '-';
            }
        }

        // EVENTOS DOM
        document.addEventListener('DOMContentLoaded', () => {
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('archivoPdf');
            const previewContainer = document.getElementById('previewContainer');
            const pdfViewer = document.getElementById('pdfViewer');
            const btnRemovePdf = document.getElementById('btnRemovePdf');
            const btnSubmitForm = document.getElementById('btnSubmitForm');
            const analysisPanel = document.getElementById('analysisPanel');

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
                dropzone.addEventListener('dragleave', () => {
                    dropzone.classList.remove('dragover');
                });
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    if (e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        manejarArchivo();
                    }
                });

                fileInput.addEventListener('change', manejarArchivo);

                function manejarArchivo() {
                    const file = fileInput.files[0];
                    if (file && file.type === 'application/pdf') {

                        // 1. Mostrar Preview Visual
                        const fileURL = URL.createObjectURL(file);
                        pdfViewer.src = fileURL;
                        dropzone.style.display = 'none';
                        previewContainer.style.display = 'block';
                        btnSubmitForm.style.display = 'flex';

                        // 2. Disparar Motor de Análisis IA
                        analizarPDF(file);

                    } else {
                        alert('Error: Por favor selecciona un archivo PDF válido.');
                        fileInput.value = '';
                        restaurarFormulario();
                    }
                }

                btnRemovePdf.addEventListener('click', () => {
                    fileInput.value = '';
                    pdfViewer.src = '';
                    restaurarFormulario();
                });

                function restaurarFormulario() {
                    previewContainer.style.display = 'none';
                    btnSubmitForm.style.display = 'none';
                    analysisPanel.style.display = 'none';
                    dropzone.style.display = 'flex';
                }
            }

            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(() => {
                    alerts.forEach(a => {
                        a.style.transition = 'opacity 0.5s ease';
                        a.style.opacity = '0';
                        setTimeout(() => a.remove(), 500);
                    });
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({
                        path: newUrl
                    }, '', newUrl);
                }, 4000);
            }
        });
    </script>
</body>
</html>