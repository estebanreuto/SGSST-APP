<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar2_schema.php';

// Exige sesión válida
$u = require_auth($conn);
ensure_estandar2_schema($conn);
if (empty($_SESSION['estandar2_csrf'])) {
    $_SESSION['estandar2_csrf'] = bin2hex(random_bytes(24));
}

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
$es_representante = $usuario_rol === 'representante';
$es_sst = $usuario_rol === 'sst';

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
        WHERE p.empresa_id = ? AND p.anio = ?
        ORDER BY p.mes
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

$planillas_cargadas = count($planillas_mes);
$planillas_pendientes = 12 - $planillas_cargadas;
$valor_total_anual = 0;
$personas_ultimo_soporte = null;
$riesgos_anuales = [];
$ultimo_soporte = null;

foreach ($planillas_mes as $planilla_resumen) {
    $valor_total_anual += (float)($planilla_resumen['valor_total'] ?? 0);
    if (!$ultimo_soporte || strtotime($planilla_resumen['fecha_subida']) > strtotime($ultimo_soporte['fecha_subida'])) {
        $ultimo_soporte = $planilla_resumen;
    }

    if (!empty($planilla_resumen['riesgos_detectados'])) {
        foreach (explode(',', $planilla_resumen['riesgos_detectados']) as $riesgo) {
            $riesgo = trim($riesgo);
            if ($riesgo !== '') {
                $riesgos_anuales[$riesgo] = true;
            }
        }
    }
}

if ($ultimo_soporte) {
    $personas_ultimo_soporte = $ultimo_soporte['cedulas_detectadas'] ?? null;
}

$riesgos_anuales_texto = $riesgos_anuales ? implode(', ', array_keys($riesgos_anuales)) : 'Sin dato';
$coberturas_detectadas = [];
foreach ($planillas_mes as $planilla_cobertura) {
    $detected = (int)($planilla_cobertura['cedulas_detectadas'] ?? 0);
    $expected = (int)($planilla_cobertura['trabajadores_esperados'] ?? 0);
    if ($expected > 0) {
        $coberturas_detectadas[] = min(100, round(($detected / $expected) * 100));
    }
}
$cobertura_promedio = $coberturas_detectadas ? (int)round(array_sum($coberturas_detectadas) / count($coberturas_detectadas)) : null;
$anio_actual = (int)date('Y');
$mes_actual_numero = (int)date('n');
$periodos_exigibles = $anio_seleccionado < $anio_actual ? 12 : ($anio_seleccionado > $anio_actual ? 0 : $mes_actual_numero);
$cargadas_exigibles = 0;
foreach (array_keys($planillas_mes) as $loadedMonth) {
    if ((int)$loadedMonth <= $periodos_exigibles) {
        $cargadas_exigibles++;
    }
}
$pendientes_a_la_fecha = max(0, $periodos_exigibles - $cargadas_exigibles);

$current_page = 'estandar2.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 2 | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 16px; --blue-dark: #1e3a8a; }
        html { min-height: 100%; background: linear-gradient(180deg, var(--bg1), var(--bg2)); }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; width: 100%; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden;}
        .main-wrapper { margin-left: 260px; width: calc(100dvw - 260px); max-width: calc(100dvw - 260px); min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; overflow: visible; }
        .content-area { padding: 32px 40px; flex: 1; max-width: none; margin: 0; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO Y ALERTAS */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; width: 100%; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; }

        .alert-status { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .alert-status.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-left: 4px solid #22c55e;}
        .alert-status.danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-left: 4px solid #ef4444;}

        /* ESTRUCTURA PRINCIPAL */
        .panel-container { display: flex; flex-direction: column; gap: 14px; align-items: stretch; }
        .panel-container.rep-view { gap: 14px; }
        .card-box { min-width: 0; background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
        body.sidebar-collapsed .main-wrapper { max-width: calc(100dvw - 76px) !important; }
        
        .card-title { margin: 0 0 20px 0; font-size: 1.1rem; color: var(--blue-dark); font-weight: 800; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed var(--border); padding-bottom: 16px; }
        .title-icon-wrapper { background: rgba(255, 138, 31, 0.12); padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary2); width: 32px; height: 32px; box-sizing: border-box;}

        /* FORMULARIOS */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .custom-select, .custom-input { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; font-weight: 600; color: var(--text); box-sizing: border-box; background-color: #f8fafc; transition: all 0.2s; }
        .custom-select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; }
        .custom-select:focus, .custom-input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        
        .info-pila-box { background: var(--bg2); border: 1px dashed #cbd5e1; border-radius: 12px; padding: 18px; margin-bottom: 24px; }
        .info-pila-box p { margin: 0 0 8px 0; font-size: 0.85rem; color: #334155; }
        .info-pila-box .highlight { color: var(--primary2); font-weight: 800; font-size: 0.9rem; margin-left: 4px; }
        .info-pila-box .fecha-roja { color: #dc2626; font-weight: 800; font-size: 0.85rem; display: block; margin-top: 4px; }
        
        .dropzone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
        .dropzone:hover, .dropzone.dragover { border-color: var(--primary); background: rgba(255, 138, 31, 0.05); }
        .dropzone p { margin: 0; font-size: 0.85rem; font-weight: 700; color: var(--text); }
        .dropzone span { font-size: 0.7rem; color: var(--muted); font-weight: 500; }
        
        body.preview-modal-open { overflow:hidden; }
        .preview-container { position:fixed !important; top:0; left:0; z-index:1000000; width:100vw; height:100dvh; display:none; align-items:center; justify-content:center; box-sizing:border-box; padding:14px; overflow:hidden; isolation:isolate; background:rgba(15,23,42,.58); backdrop-filter:blur(5px); }
        .preview-modal-shell { width:min(1240px,calc(100vw - 28px)); max-height:calc(100dvh - 28px); display:flex; flex-direction:column; overflow:hidden; border:1px solid #dbe3ec; border-radius:14px; background:#fff; box-shadow:0 28px 80px rgba(15,23,42,.26); }
        .preview-modal-head { min-height:58px; padding:10px 14px; display:flex; align-items:center; justify-content:space-between; gap:14px; border-bottom:1px solid #e2e8f0; background:#fff; box-sizing:border-box; }
        .preview-modal-title { display:flex; align-items:center; gap:9px; min-width:0; }
        .preview-modal-title > i { width:34px; height:34px; flex:0 0 34px; display:grid; place-items:center; border-radius:9px; color:#ea580c; background:#fff7ed; }
        .preview-modal-title strong { display:block; color:#1e3a8a; font-size:.8rem; line-height:1.2; }
        .preview-modal-title small { display:block; max-width:620px; margin-top:2px; color:#64748b; font-size:.53rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .preview-modal-close { width:32px; height:32px; flex:0 0 32px; display:grid; place-items:center; border:1px solid #dbe3ec; border-radius:8px; outline:none; color:#64748b; background:#fff; cursor:pointer; transition:.18s ease; }
        .preview-modal-close:hover { border-color:#fecaca; color:#dc2626; background:#fff7f7; }
        .preview-modal-close:focus-visible { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(37,99,235,.14); }
        .preview-modal-body { min-height:0; padding:12px; display:grid; grid-template-columns:minmax(340px,.7fr) minmax(0,1.3fr); gap:12px; overflow:auto; background:#f7f9fc; }
        .preview-document-panel { min-width:0; overflow:hidden; border:1px solid #dbe3ec; border-radius:10px; background:#fff; }
        .preview-header { background:#fff; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; }
        .preview-header span { font-size:.68rem; font-weight:800; color:#1e3a8a; display:flex; align-items:center; gap:6px; }
        .btn-change-file { background: #fee2e2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-change-file:hover { background: #fca5a5; }
        .preview-modal-footer { min-height:62px; padding:10px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; border-top:1px solid #e2e8f0; background:#fff; box-sizing:border-box; }
        .preview-modal-note { color:#64748b; font-size:.54rem; line-height:1.35; }
        .preview-modal-actions { display:flex; align-items:center; gap:8px; margin-left:auto; }
        .preview-secondary { min-height:38px; padding:0 12px; border:1px solid #dbe3ec; border-radius:8px; color:#475569; background:#fff; font:inherit; font-size:.6rem; font-weight:800; cursor:pointer; }
        
        /* PANEL DE ANÁLISIS */
        .analysis-panel { min-width:0; padding:14px; border:1px solid #dbe3ec; border-radius:10px; background:#fff; color:#172554; font-size:.72rem; display:none; flex-direction:column; gap:10px; align-self:start; box-shadow:0 8px 24px rgba(15,23,42,.035); }
        .analysis-title { font-weight:850; color:#1e3a8a; display:flex; align-items:center; gap:7px; font-size:.78rem; margin-bottom:2px; }
        .analysis-title i { color:#ea580c; }
        .analysis-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .analysis-item { background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0; }
        .analysis-label { color:#64748b; font-size:.58rem; text-transform:uppercase; letter-spacing:.045em; font-weight:800; margin-bottom:4px; display:block; }
        .analysis-val { color:#172554; font-weight:750; font-size:.74rem; display:flex; flex-direction:column; gap:4px; }
        .status-ok { color: #059669; }
        .status-warn { color: #f59e0b; }
        .status-err { color: #dc2626; }
        
        .pdf-viewer { width:100%; height:clamp(420px,58dvh,620px); border:none; display:block; background:#f8fafc; }
        
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; width: 100%; cursor: pointer; transition: transform 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; font-family: inherit; margin-top: 24px; box-shadow: 0 4px 12px rgba(255, 138, 31, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.3); }
        
        /* SELECTOR DE AÑO Y TARJETAS MESES */
        .year-selector { display: flex; align-items: center; gap: 12px; }
        .year-btn { background: #f1f5f9; color: #475569; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 0.85rem;}
        .year-btn:hover { background: #e2e8f0; color: #1e293b; }
        .year-display { font-size: 1.05rem; font-weight: 800; color: var(--blue-dark); }
        
        .months-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }
        .month-card { border: 1px solid var(--border); border-radius: 12px; padding: 16px; background: #ffffff; display: flex; flex-direction: column; gap: 12px; transition: all 0.2s; position: relative; }
        .month-card:hover { box-shadow: 0 6px 15px rgba(0, 0, 0, 0.04); border-color: #cbd5e1; transform: translateY(-2px);}
        .month-card.loaded { border-top: 4px solid #10b981; }
        .month-card.missing { border-top: 4px solid #cbd5e1; }
        
        .month-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .month-name { font-weight: 800; color: var(--blue-dark); font-size: 1rem; display: block; }
        .status-badge { font-size: 0.6rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; height: fit-content; }
        .status-loaded { background: #dcfce7; color: #166534; }
        .status-missing { background: #f1f5f9; color: #64748b; }
        .month-info { font-size: 0.75rem; color: var(--muted); line-height: 1.5; flex: 1; }
        
        .month-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn-view { background: #e0f2fe; color: #0284c7; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; flex: 1; }
        .btn-view:hover { background: #bae6fd; }
        .btn-delete { background: #fee2e2; color: #dc2626; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; flex: 1; }
        .btn-delete:hover { background: #ef4444; color: white; }
        .btn-outline-upload { background: #fff8f3; border: 1px dashed var(--primary); color: var(--primary2); padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; width: 100%; font-family: inherit; }
        .btn-outline-upload:hover { background: var(--primary); color: white; border-style: solid; }

        .rep-pila-strip { display: grid; grid-template-columns: minmax(min(260px, 100%), 0.85fr) minmax(0, 2fr); gap: clamp(10px, 1.2vw, 16px); align-items: stretch; background: #fff; border: 1px solid var(--border); border-left: 4px solid var(--primary2); border-radius: 14px; padding: clamp(12px, 1.3vw, 16px); margin: -8px 0 clamp(14px, 1.6vw, 22px); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .rep-pila-title { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .rep-pila-title i { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 10px; background: #fff7ed; color: var(--primary2); flex: 0 0 auto; }
        .rep-pila-title strong { display: block; color: var(--blue-dark); font-size: 0.95rem; line-height: 1.2; }
        .rep-pila-title span { display: block; color: var(--muted); font-size: 0.72rem; line-height: 1.35; margin-top: 3px; }
        .rep-pila-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(145px, 100%), 1fr)); gap: 10px; min-width: 0; }
        .rep-pila-fact { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; min-width: 0; }
        .rep-pila-fact span { display: block; color: #64748b; font-size: 0.58rem; font-weight: 850; text-transform: uppercase; letter-spacing: .035em; line-height: 1.2; margin-bottom: 5px; }
        .rep-pila-fact strong { color: var(--blue-dark); font-size: 0.88rem; line-height: 1.2; overflow-wrap: anywhere; }
        .rep-pila-fact.due strong { color: #dc2626; }
        .executive-note { grid-column: 1 / -1; display: flex; align-items: flex-start; gap: 8px; color: #7c2d12; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 8px 10px; font-size: 0.72rem; line-height: 1.4; }
        .executive-note i { color: var(--primary2); margin-top: 2px; }
        .executive-note strong { color: var(--blue-dark); margin-right: 4px; }
        .rep-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(190px, 100%), 1fr)); gap: clamp(10px, 1.1vw, 12px); margin-bottom: clamp(12px, 1.3vw, 18px); }
        .rep-metric { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: clamp(10px, 1vw, 13px); min-height: clamp(64px, 8vh, 78px); display: flex; flex-direction: column; justify-content: space-between; }
        .rep-metric span { color: #64748b; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.035em; line-height: 1.25; }
        .rep-metric strong { color: var(--blue-dark); font-size: 1.12rem; font-weight: 850; margin-top: 8px; line-height: 1.15; }
        .rep-metric.ok strong { color: #15803d; }
        .rep-metric.warn strong { color: #b45309; }
        .rep-table-wrap { width: 100%; max-width: 100%; max-height: none; overflow-x: auto; overflow-y: visible; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; scrollbar-gutter: auto; }
        .rep-table-wrap::-webkit-scrollbar { height: 8px; }
        .rep-table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .rep-table-wrap::-webkit-scrollbar-track { background: #f8fafc; border-radius: 999px; }
        .rep-summary-table { width: 100%; border-collapse: collapse; min-width: 0; table-layout: fixed; }
        .rep-summary-table th { background: #f8fafc; color: #475569; font-size: clamp(0.54rem, 0.58vw, 0.62rem); text-transform: uppercase; letter-spacing: 0.035em; text-align: left; padding: clamp(7px, 0.8vh, 9px) 7px; border-bottom: 1px solid #e2e8f0; line-height: 1.15; }
        .rep-summary-table td { padding: clamp(7px, 0.85vh, 10px) 7px; border-bottom: 1px solid #eef2f7; color: #334155; font-size: clamp(0.64rem, 0.68vw, 0.72rem); vertical-align: top; line-height: 1.32; overflow-wrap: anywhere; }
        .rep-summary-table tr:last-child td { border-bottom: none; }
        .summary-status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border-radius: 999px; font-size: 0.58rem; font-weight: 850; text-transform: uppercase; line-height: 1.1; white-space: nowrap; max-width: 100%; }
        .summary-status.ok { background: #dcfce7; color: #166534; }
        .summary-status.pending { background: #f1f5f9; color: #64748b; }
        .summary-status.late { background: #fef2f2; color: #b91c1c; }
        .summary-muted { color: #94a3b8; font-style: italic; }
        .risk-pill { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #eef2ff; color: #1e3a8a; font-size: 0.62rem; font-weight: 800; margin: 2px 4px 2px 0; }
        .rep-executive-panel { display: grid; grid-template-columns: minmax(260px, 1.4fr) repeat(3, minmax(160px, 1fr)); gap: 12px; margin-top: 4px; }
        .rep-exec-chart, .rep-exec-card, .rep-risk-summary { border: 1px solid #dbe3ec; border-radius: 14px; background: #ffffff; padding: 16px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035); }
        .rep-exec-chart-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .rep-exec-chart-head span, .rep-exec-card span { color: #64748b; font-size: 0.7rem; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em; }
        .rep-exec-chart-head strong, .rep-exec-card strong { color: var(--blue-dark); font-size: clamp(1.15rem, 2vw, 1.65rem); font-weight: 900; line-height: 1.1; }
        .rep-exec-track { height: 12px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .rep-exec-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #ff8a1f, #16a34a); }
        .rep-exec-foot { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; color: #64748b; font-size: 0.76rem; font-weight: 700; }
        .rep-exec-card { min-height: 128px; display: flex; flex-direction: column; justify-content: space-between; }
        .rep-exec-card small { color: #64748b; line-height: 1.35; }
        .rep-risk-summary { margin-top: 12px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .rep-risk-summary h4 { margin: 0 0 4px; color: var(--blue-dark); font-size: 0.98rem; }
        .rep-risk-summary p { margin: 0; color: #64748b; font-size: 0.82rem; }
        .rep-risk-list { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 4px; min-width: 220px; }
        
        /* Escala y componentes compartidos con el Estándar 5 */
        body { font-size:.82rem; }
        .content-area { padding:30px clamp(24px,2.5vw,44px) 60px; }
        .header-actions { margin-bottom:14px; }
        .estandar-header-group { gap:11px; }
        .icon-box-std { width:40px; height:40px; border-radius:9px; }
        .icon-box-std i { font-size:.95rem !important; }
        .estandar-title { font-size:1rem; line-height:1.22; }
        .estandar-subtitle { margin-top:3px; font-size:.7rem; line-height:1.32; }
        .alert-status { padding:11px 13px; margin-bottom:14px; border-radius:9px; font-size:.72rem; }
        .panel-container { gap:14px; }
        .card-box { padding:16px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .card-title { margin-bottom:14px; padding-bottom:11px; font-size:.9rem; }
        .title-icon-wrapper { width:34px; height:34px; padding:7px; border-radius:9px; }
        .form-group { margin-bottom:12px; }
        .form-group label { margin-bottom:6px; font-size:.62rem; }
        .custom-select,.custom-input { min-height:38px; padding:8px 11px; font-size:.72rem; }
        .info-pila-box { margin-bottom:14px; padding:13px; border-radius:9px; }
        .info-pila-box p { font-size:.7rem; }
        .dropzone { padding:20px 14px; border-width:1px; border-radius:9px; }
        .dropzone p { font-size:.72rem; }
        .btn-primary { padding:10px 14px; margin-top:14px; font-size:.72rem; }
        .rep-pila-strip { margin:0 0 14px; padding:13px 14px; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .rep-summary-grid,.months-grid { display:none; }
        .rep-executive-panel { grid-template-columns:minmax(220px,1.4fr) repeat(3,minmax(130px,1fr)); gap:8px; margin-bottom:12px; }
        .rep-exec-chart,.rep-exec-card,.rep-risk-summary { border-radius:9px; padding:11px; box-shadow:none; }
        .rep-exec-card { min-height:92px; }
        .rep-exec-chart-head span,.rep-exec-card span { font-size:.55rem; }
        .rep-exec-chart-head strong,.rep-exec-card strong { font-size:1rem; }
        .rep-risk-summary { margin:0 0 12px; }
        .rep-risk-summary h4 { font-size:.82rem; }
        .rep-risk-summary p { font-size:.68rem; }

        .pila-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin:0; }
        .pila-kpi { --pila-color:#ea580c; --pila-soft:#fff3e8; position:relative; overflow:hidden; min-height:104px; padding:13px; border:1px solid var(--border); border-radius:10px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .pila-kpi.blue { --pila-color:#2563eb; --pila-soft:#eff6ff; }
        .pila-kpi.green { --pila-color:#059669; --pila-soft:#ecfdf5; }
        .pila-kpi.violet { --pila-color:#7c3aed; --pila-soft:#f5f3ff; }
        .pila-kpi > i { position:absolute; right:-8px; bottom:-17px; color:var(--pila-color); font-size:4.5rem; opacity:.055; transform:rotate(-10deg); pointer-events:none; }
        .pila-kpi span { position:relative; z-index:1; display:block; color:#64748b; font-size:.6rem; font-weight:850; text-transform:uppercase; }
        .pila-kpi strong { position:relative; z-index:1; display:block; margin-top:8px; color:var(--pila-color); font-size:1.28rem; line-height:1; }
        .pila-kpi small { position:relative; z-index:1; display:block; max-width:84%; margin-top:6px; color:#64748b; font-size:.62rem; line-height:1.35; }
        .pila-detail-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:14px 0 10px; }
        .pila-detail-head h4 { margin:0; color:var(--blue-dark); font-size:.88rem; }
        .pila-detail-head p { margin:3px 0 0; color:#64748b; font-size:.64rem; }
        .pila-tools { display:flex; align-items:center; gap:7px; flex-wrap:wrap; justify-content:flex-end; }
        .pila-search,.pila-filter { height:36px; box-sizing:border-box; border:1px solid var(--border); border-radius:8px; background:#fff; color:#475569; padding:0 10px; font:inherit; font-size:.68rem; outline:none; }
        .pila-search { width:min(220px,100%); }
        .pila-files-link { height:36px; padding:0 10px; border:1px solid #fed7aa; border-radius:8px; display:inline-flex; align-items:center; gap:6px; background:#fff7ed; color:#c2410c; text-decoration:none; font-size:.64rem; font-weight:850; }
        .pila-period-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .pila-period-card { --period-color:#94a3b8; --period-soft:#f1f5f9; position:relative; overflow:hidden; min-width:0; padding:12px; border:1px solid var(--border); border-radius:10px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.035); }
        .pila-period-card.loaded { --period-color:#059669; --period-soft:#ecfdf5; }
        .pila-period-card::after { content:'\f571'; font-family:'Font Awesome 6 Free'; font-weight:900; position:absolute; right:-9px; top:18px; color:var(--period-color); font-size:4.7rem; opacity:.045; transform:rotate(-10deg); pointer-events:none; }
        .pila-period-top { position:relative; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
        .pila-period-title { display:flex; align-items:center; gap:8px; min-width:0; }
        .pila-period-icon { width:32px; height:32px; border-radius:8px; display:grid; place-items:center; color:var(--period-color); background:var(--period-soft); flex:none; }
        .pila-period-title strong { display:block; color:var(--blue-dark); font-size:.8rem; }
        .pila-period-title small { display:block; margin-top:2px; color:#64748b; font-size:.56rem; }
        .pila-status { display:inline-flex; min-height:22px; align-items:center; padding:0 7px; border-radius:999px; color:var(--period-color); background:var(--period-soft); font-size:.52rem; font-weight:900; text-transform:uppercase; }
        .pila-period-metrics { position:relative; z-index:1; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; margin-top:10px; }
        .pila-period-metric { padding:7px 8px; border:1px solid #eef2f7; border-radius:7px; background:#f8fafc; min-width:0; }
        .pila-period-metric span { display:block; color:#94a3b8; font-size:.48rem; font-weight:900; text-transform:uppercase; }
        .pila-period-metric strong { display:block; margin-top:3px; color:#334155; font-size:.68rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pila-period-note { position:relative; z-index:1; margin:9px 0 0; min-height:34px; color:#64748b; font-size:.59rem; line-height:1.4; }
        .pila-period-details { position:relative; z-index:1; margin-top:8px; border-top:1px solid #eef2f7; padding-top:8px; }
        .pila-period-details summary { color:#1d4ed8; font-size:.58rem; font-weight:850; cursor:pointer; list-style:none; }
        .pila-period-details summary::-webkit-details-marker { display:none; }
        .pila-period-details summary i { margin-right:4px; transition:transform .2s ease; }
        .pila-period-details[open] summary i { transform:rotate(180deg); }
        .pila-detail-copy { margin-top:7px; padding:8px; border-radius:7px; background:#f8fafc; color:#64748b; font-size:.56rem; line-height:1.45; overflow-wrap:anywhere; }
        .pila-card-actions { position:relative; z-index:1; display:flex; gap:6px; margin-top:9px; }
        .pila-action { min-height:31px; padding:0 9px; border:1px solid var(--border); border-radius:7px; display:inline-flex; align-items:center; justify-content:center; gap:5px; background:#fff; color:#334155; text-decoration:none; font:inherit; font-size:.58rem; font-weight:850; cursor:pointer; flex:1; }
        .pila-action.primary { border-color:#bfdbfe; color:#1d4ed8; background:#eff6ff; }
        .pila-action.danger { border-color:#fecaca; color:#b91c1c; background:#fff7f7; }
        .pila-action.upload { border-color:#fed7aa; color:#c2410c; background:#fff7ed; }

        .upload-planilla-card { display:block; padding:14px 16px 16px; overflow:hidden; }
        .upload-planilla-card .card-title { margin:0; padding:0 0 10px; }
        .upload-title-copy { display:flex; flex-direction:column; gap:2px; min-width:0; }
        .upload-title-copy strong { color:var(--blue-dark); font-size:.86rem; line-height:1.2; }
        .upload-title-copy small { color:#64748b; font-size:.58rem; font-weight:550; line-height:1.4; }
        .pila-payment-context { display:grid; grid-template-columns:minmax(190px,.62fr) minmax(0,2fr); gap:12px; align-items:center; min-width:0; padding:9px 10px; border:1px solid #fed7aa; border-radius:10px; background:linear-gradient(135deg,#fffaf5 0%,#fff 72%); box-sizing:border-box; }
        .pila-context-heading { display:flex; align-items:center; gap:8px; min-width:0; padding-right:12px; border-right:1px solid #ffedd5; }
        .pila-context-icon { width:30px; height:30px; flex:0 0 30px; display:grid; place-items:center; border-radius:8px; color:#ea580c; background:#ffedd5; font-size:.68rem; }
        .pila-context-heading > div { display:flex; flex-direction:column; gap:1px; min-width:0; }
        .pila-context-heading strong { color:#9a3412; font-size:.65rem; line-height:1.2; }
        .pila-context-heading small { display:block; color:#78716c; font-size:.48rem; line-height:1.3; }
        .pila-context-grid { display:grid; grid-template-columns:minmax(0,1.55fr) repeat(2,minmax(62px,.58fr)) minmax(130px,1fr); gap:7px; }
        .pila-context-item { min-width:0; padding:7px 8px; border:1px solid #f1f5f9; border-radius:7px; background:rgba(255,255,255,.92); }
        .pila-context-item.company,.pila-context-item.due { grid-column:auto; }
        .pila-context-item span { display:block; margin-bottom:2px; color:#94a3b8; font-size:.4rem; font-weight:850; letter-spacing:.04em; line-height:1.1; text-transform:uppercase; }
        .pila-context-item strong { display:block; color:#0f2f67; font-size:.56rem; line-height:1.2; overflow-wrap:anywhere; }
        .pila-context-item.due { border-color:#ffedd5; background:#fff7ed; }
        .pila-context-item.due strong { color:#dc2626; }
        .pila-upload-form { display:flex; flex-direction:column; gap:9px; margin-top:9px; min-width:0; }
        .pila-flow-row { display:grid; grid-template-columns:minmax(165px,.42fr) minmax(0,2fr); gap:12px; align-items:center; min-width:0; padding:9px 10px; border:1px solid #e6edf5; border-radius:10px; background:#fbfdff; }
        .pila-flow-copy { display:flex; align-items:center; gap:8px; min-width:0; }
        .pila-flow-number { width:27px; height:27px; flex:0 0 27px; display:grid; place-items:center; border-radius:8px; color:#fff; background:#ff7a00; font-size:.62rem; font-weight:900; box-shadow:0 5px 12px rgba(255,122,0,.18); }
        .pila-flow-copy > div { display:flex; flex-direction:column; gap:1px; min-width:0; }
        .pila-flow-copy strong { color:#0f2f67; font-size:.64rem; line-height:1.2; }
        .pila-flow-copy small { color:#64748b; font-size:.47rem; line-height:1.3; }
        .pila-period-fields { display:grid; grid-template-columns:minmax(170px,1fr) minmax(130px,.55fr); gap:10px; max-width:560px; }
        .pila-upload-form .form-group { margin:0; min-width:0; }
        .pila-document-row { align-items:start; }
        .pila-document-row .pila-flow-copy { align-self:start; padding-top:18px; }
        .pila-upload-form .dropzone { min-height:82px; box-sizing:border-box; padding:12px 14px; flex-direction:row; justify-content:flex-start; gap:11px; border-width:1px; border-color:#fdba74; border-radius:9px; text-align:left; background:#fff; }
        .pila-upload-form .dropzone:hover,.pila-upload-form .dropzone.dragover { border-color:#f97316; background:#fffaf5; box-shadow:0 0 0 3px rgba(249,115,22,.08); }
        .pila-file-symbol { width:44px; height:44px; flex:0 0 44px; display:grid; place-items:center; border-radius:10px; color:#ea580c; background:#fff7ed; font-size:1.05rem; }
        .pila-file-copy { display:flex; flex:1; flex-direction:column; gap:1px; min-width:0; }
        .pila-file-copy strong { color:#0f2f67; font-size:.7rem; line-height:1.25; }
        .pila-file-copy small { color:#64748b; font-size:.52rem; line-height:1.3; }
        .pila-file-action { min-height:34px; margin-left:auto; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:8px; color:#c2410c; background:#ffedd5; font-size:.58rem; font-weight:850; white-space:nowrap; }
        .pila-selected-file { min-height:82px; box-sizing:border-box; padding:10px 12px; display:none; align-items:center; gap:10px; border:1px solid #bbf7d0; border-radius:9px; background:#f0fdf4; }
        .pila-selected-file > i { width:38px; height:38px; flex:0 0 38px; display:grid; place-items:center; border-radius:9px; color:#047857; background:#dcfce7; font-size:.9rem; }
        .pila-selected-copy { min-width:0; flex:1; }
        .pila-selected-copy strong { display:block; overflow:hidden; color:#065f46; font-size:.65rem; text-overflow:ellipsis; white-space:nowrap; }
        .pila-selected-copy small { display:block; margin-top:3px; color:#64748b; font-size:.49rem; }
        .pila-review-button { min-height:34px; padding:0 11px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border:1px solid #bfdbfe; border-radius:8px; color:#1d4ed8; background:#eff6ff; font:inherit; font-size:.56rem; font-weight:850; cursor:pointer; white-space:nowrap; }
        .pila-upload-form .btn-primary { min-height:38px; margin:0; padding:8px 10px; }
        .preview-modal-actions .btn-primary { width:auto; min-width:150px; }
        .pila-report-link { height:36px; padding:0 11px; border:1px solid #bfdbfe; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; gap:6px; color:#1d4ed8; background:#eff6ff; text-decoration:none; font-size:.62rem; font-weight:850; }
        .year-report-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .planillas-card { width:100%; box-sizing:border-box; }

        #loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 999999; display: none; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        #loader-overlay.active { display: flex; }

        @media (max-width: 900px) {
            .rep-pila-strip { grid-template-columns: 1fr; }
            .rep-pila-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .pila-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .pila-period-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .pila-payment-context { grid-template-columns:1fr; }
            .pila-context-heading { padding:0 0 8px; border-right:0; border-bottom:1px solid #ffedd5; }
            .pila-context-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
            .pila-flow-row { grid-template-columns:minmax(145px,.42fr) minmax(0,2fr); }
            .preview-modal-body { grid-template-columns:1fr; }
            .pdf-viewer { height:46vh; }
        }
        @media (min-width: 769px) and (max-height: 820px) {
            .content-area { padding-top: 20px; padding-bottom: 20px; }
            .header-actions { margin-bottom: 16px; }
            .card-box { padding: 18px 22px; }
            .card-title { margin-bottom: 14px; padding-bottom: 12px; }
            .rep-pila-strip { padding: 12px 14px; margin-bottom: 16px; }
            .rep-pila-fact { padding: 8px 10px; }
            .rep-metric { min-height: 62px; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; max-width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .estandar-header-group { flex-direction: row; align-items: flex-start; width: 100%; }
            
            .card-box { padding: 14px; }
            .months-grid { grid-template-columns: 1fr; }
            .analysis-grid { grid-template-columns: 1fr; }
            .rep-pila-strip { margin-top: -4px; padding: 12px; gap: 12px; }
            .rep-pila-title i { width: 34px; height: 34px; }
            .rep-summary-grid { grid-template-columns: 1fr 1fr; }
            .rep-executive-panel { grid-template-columns: 1fr 1fr; }
            .rep-exec-chart { grid-column: 1 / -1; }
            .rep-risk-summary { align-items: flex-start; flex-direction: column; }
            .rep-risk-list { justify-content: flex-start; min-width: 0; }
            .rep-table-wrap { max-height: none; border: none; overflow: visible; background: transparent; scrollbar-gutter: auto; }
            .rep-summary-table { min-width: 0; table-layout: auto; }
            .rep-summary-table, .rep-summary-table tbody, .rep-summary-table tr, .rep-summary-table td { display: block; width: 100%; box-sizing: border-box; }
            .rep-summary-table thead { display: none; }
            .rep-summary-table tr { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(15,23,42,.025); }
            .rep-summary-table td { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 9px 0; border-bottom: 1px solid #eef2f7; text-align: right; }
            .rep-summary-table td:last-child { border-bottom: none; }
            .rep-summary-table td::before { content: attr(data-label); flex: 0 0 42%; color: #64748b; font-size: 0.62rem; font-weight: 850; text-transform: uppercase; letter-spacing: .035em; text-align: left; }
            .pila-detail-head { align-items:flex-start; flex-direction:column; }
            .pila-tools { justify-content:flex-start; width:100%; }
            .pila-search { flex:1; }
            .pila-period-grid { grid-template-columns:1fr; }
            .pila-tools,.pila-search,.pila-filter,.pila-files-link { width:100%; }
            .pila-files-link { justify-content:center; }
            .pila-context-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .pila-context-item.company,.pila-context-item.due { grid-column:span 2; }
            .pila-flow-row { grid-template-columns:1fr; }
            .pila-period-fields { grid-template-columns:1fr; max-width:none; }
            .pila-upload-form .dropzone { min-height:92px; }
            .pila-file-action { padding:0 9px; }
            .pila-document-row .pila-flow-copy { padding-top:0; }
            .preview-container { padding:8px; }
            .preview-modal-shell { width:calc(100vw - 16px); max-height:calc(100vh - 16px); border-radius:11px; }
            .preview-modal-head { min-height:54px; padding:9px 10px; }
            .preview-modal-title small { max-width:240px; }
            .preview-modal-body { padding:8px; }
            .analysis-grid { grid-template-columns:1fr; }
            .analysis-item[style*="grid-column"] { grid-column:1 !important; }
            .pdf-viewer { height:42vh; }
            .preview-modal-footer { align-items:stretch; flex-direction:column; padding:9px 10px; }
            .preview-modal-note { display:none; }
            .preview-modal-actions { width:100%; margin:0; }
            .preview-modal-actions > * { flex:1; }
            .pila-selected-file { align-items:flex-start; flex-wrap:wrap; }
            .pila-review-button { width:100%; }
        }
        @media (max-width: 480px) {
            .rep-summary-grid { grid-template-columns: 1fr; }
            .rep-executive-panel { grid-template-columns: 1fr; }
            .rep-exec-chart { grid-column: auto; }
            .rep-pila-facts { grid-template-columns: 1fr; }
            .rep-pila-title { align-items: flex-start; }
        }
    </style>
</head>

<body>

    <div id="loader-overlay">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 3rem; color: var(--primary);"></i>
        <h3 style="margin-top:20px; font-weight: 800; color: var(--blue-dark);">Guardando documento...</h3>
        <p style="color: var(--muted); margin: 5px 0 0 0;">Por favor no cierres la ventana.</p>
    </div>

    <?php include 'components/sidebar.php'; ?>

    <style>
        @media (min-width: 769px) {
            body .main-wrapper {
                flex: 1 1 auto !important;
                width: auto !important;
                max-width: none !important;
                min-width: 0 !important;
                margin-left: var(--sidebar-expanded, 260px) !important;
            }

            body.sidebar-collapsed .main-wrapper {
                margin-left: var(--sidebar-compact, 76px) !important;
            }

            body .main-wrapper > .content-area {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding-inline: clamp(16px, 2.1vw, 36px) !important;
                box-sizing: border-box !important;
            }

            body .top-header {
                margin-inline: clamp(16px, 2.1vw, 36px) !important;
            }
        }
    </style>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'subido'): ?>
                <div class="alert-status success" id="alertBox">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                    Planilla guardada y asignada correctamente.
                </div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
                <div class="alert-status danger" id="alertBox">
                    <i class="fa-solid fa-trash-can" style="font-size: 1.2rem;"></i>
                    La planilla ha sido eliminada del historial.
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <?php
                    $erroresPlanilla = [
                        'sesion' => 'La sesión del formulario venció. Recarga la página e intenta nuevamente.',
                        'periodo' => 'Selecciona un mes y un año válidos para la planilla.',
                        'archivo' => 'No se recibió el soporte. Selecciona nuevamente el PDF.',
                        'formato' => 'El soporte debe ser un PDF válido de máximo 15 MB.',
                        'almacenamiento' => 'No hay espacio disponible en el plan para guardar este soporte.',
                        'no_permission' => 'Solo el Responsable SST puede administrar las planillas.',
                        'registro' => 'No fue posible guardar la planilla. Intenta nuevamente.',
                    ];
                    $errorPlanilla = $erroresPlanilla[(string)$_GET['error']] ?? 'No fue posible completar la operación.';
                ?>
                <div class="alert-status danger" id="alertBox">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.05rem;"></i>
                    <?php echo htmlspecialchars($errorPlanilla); ?>
                </div>
            <?php endif; ?>

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-file-invoice-dollar" style="font-size: 1.2rem;"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title"><span class="std-num-marker">2.</span> Afiliación al Sistema de Seguridad Social</h1>
                        <p class="estandar-subtitle">Control y carga mensual de la planilla de pago de Seguridad Social (PILA).</p>
                    </div>
                </div>
            </div>

            <?php if ($es_representante): ?>
                <?php if ($bloquear_subida): ?>
                    <div class="alert-status danger" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                        <div style="display:flex; gap: 8px; align-items:center;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i>
                            <strong style="font-size: 0.9rem;">Falta el NIT de la Empresa</strong>
                        </div>
                        <p style="margin:0; font-size: 0.8rem; font-weight: 500; line-height: 1.5;">
                            No podemos calcular la fecha limite de pago PILA porque la empresa no tiene NIT configurado.
                        </p>
                        <a href="configuracion.php" class="btn-primary" style="margin-top: 8px; width: auto; font-size: 0.8rem; padding: 10px 14px; background: #dc2626;">Completar Perfil de Empresa</a>
                    </div>
                <?php else: ?>
                    <section class="rep-pila-strip">
                        <div class="rep-pila-title">
                            <i class="fa-solid fa-calculator"></i>
                            <div>
                                <strong>Calculo automatico PILA</strong>
                                <span>Referencia mensual segun NIT y calendario de pago.</span>
                            </div>
                        </div>

                        <div class="rep-pila-facts">
                            <div class="rep-pila-fact">
                                <span>Empresa</span>
                                <strong><?php echo htmlspecialchars($nombre_empresa); ?></strong>
                            </div>
                            <div class="rep-pila-fact">
                                <span>NIT finaliza en</span>
                                <strong><?php echo $ultimos_digitos; ?></strong>
                            </div>
                            <div class="rep-pila-fact">
                                <span>Dia habil limite</span>
                                <strong><?php echo $dia_habil; ?>&deg;</strong>
                            </div>
                            <div class="rep-pila-fact due">
                                <span>Limite sugerido</span>
                                <strong><?php echo $fecha_limite_actual; ?></strong>
                            </div>
                        </div>

                        <div class="executive-note">
                            <i class="fa-solid fa-eye"></i>
                            <div><strong>Vista ejecutiva:</strong> el Responsable SST carga y valida los soportes; aqui se resume estado, valor, personas cubiertas y riesgos por periodo.</div>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <div class="panel-container <?php echo $es_representante ? 'rep-view' : ''; ?>">
                
                <?php if ($es_sst): ?>
                    <div class="card-box upload-planilla-card">
                        <h3 class="card-title">
                            <div class="title-icon-wrapper"><i class="fa-solid <?php echo $es_sst ? 'fa-cloud-arrow-up' : 'fa-circle-info'; ?>"></i></div>
                            <span class="upload-title-copy">
                                <strong>Subir nueva planilla</strong>
                                <small>Registra el periodo y adjunta el soporte PILA en formato PDF.</small>
                            </span>
                        </h3>

                        <?php if ($bloquear_subida): ?>
                            <div class="alert-status danger" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                                <div style="display:flex; gap: 8px; align-items:center;">
                                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i>
                                    <strong style="font-size: 0.9rem;">¡Falta el NIT de la Empresa!</strong>
                                </div>
                                <p style="margin:0; font-size: 0.8rem; font-weight: 500; line-height: 1.5;">
                                    No podemos calcular tu fecha límite de pago (Decreto 1990 de 2016) porque no tienes el <strong>NIT</strong> configurado.
                                </p>
                                <?php if ($usuario_rol === 'representante'): ?>
                                    <a href="configuracion.php" class="btn-primary" style="margin-top: 8px; width: 100%; font-size: 0.8rem; padding: 10px; background: #dc2626;">Completar Perfil de Empresa</a>
                                <?php else: ?>
                                    <p style="margin:8px 0 0 0; font-size: 0.8rem; font-weight: 700; color: #991b1b; padding: 8px; background: rgba(220,38,38,0.1); border-radius: 6px; width: 100%;">El Representante Legal debe actualizar el "Perfil Corporativo" en Configuración.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>

                            <div class="pila-payment-context">
                                <div class="pila-context-heading">
                                    <span class="pila-flow-number">1</span>
                                    <span class="pila-context-icon"><i class="fa-solid fa-calculator"></i></span>
                                    <div><strong>Contexto de pago PILA</strong><small>Cálculo automático según el NIT registrado.</small></div>
                                </div>
                                <div class="pila-context-grid">
                                    <div class="pila-context-item company"><span>Empresa</span><strong><?php echo htmlspecialchars($nombre_empresa); ?></strong></div>
                                    <div class="pila-context-item"><span>Terminación NIT</span><strong><?php echo $ultimos_digitos; ?></strong></div>
                                    <div class="pila-context-item"><span>Día hábil</span><strong><?php echo $dia_habil; ?>°</strong></div>
                                    <div class="pila-context-item due"><span>Próximo límite</span><strong><?php echo $fecha_limite_actual; ?></strong></div>
                                </div>
                            </div>

                            <form class="pila-upload-form" id="pilaUploadForm" action="procesar_estandar2.php" method="POST" enctype="multipart/form-data" onsubmit="mostrarLoader()">
                                <input type="hidden" name="accion" value="subir_planilla">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['estandar2_csrf']); ?>">

                                <div class="pila-flow-row pila-period-row">
                                    <div class="pila-flow-copy">
                                        <span class="pila-flow-number">2</span>
                                        <div><strong>Periodo de la planilla</strong><small>Selecciona el mes y la vigencia que vas a registrar.</small></div>
                                    </div>

                                    <div class="pila-period-fields">
                                        <div class="form-group">
                                            <label>Mes correspondiente</label>
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
                                    </div>
                                </div>

                                <div class="pila-flow-row pila-document-row">
                                    <div class="pila-flow-copy">
                                        <span class="pila-flow-number">3</span>
                                        <div><strong>Soporte documental</strong><small>Adjunta la planilla PILA para validarla y guardarla.</small></div>
                                    </div>

                                    <div class="form-group file-group">
                                        <label>Documento PDF (PILA)</label>

                                        <div class="dropzone" id="dropzone">
                                            <input type="file" name="archivo" id="archivoPdf" accept="application/pdf" required hidden>
                                            <span class="pila-file-symbol"><i class="fa-solid fa-file-pdf"></i></span>
                                            <span class="pila-file-copy"><strong>Haz clic o arrastra el soporte PILA</strong><small>Solo PDF · Máximo 15 MB · El sistema revisará NIT, trabajadores y novedades</small></span>
                                            <span class="pila-file-action"><i class="fa-solid fa-folder-open"></i> Elegir archivo</span>
                                        </div>

                                        <div class="pila-selected-file" id="selectedFileCard">
                                            <i class="fa-solid fa-file-circle-check"></i>
                                            <div class="pila-selected-copy">
                                                <strong id="selectedFileName">Soporte PILA seleccionado</strong>
                                                <small>Archivo listo para revisar y guardar.</small>
                                            </div>
                                            <button type="button" class="pila-review-button" id="btnOpenPreview"><i class="fa-solid fa-magnifying-glass"></i> Revisar documento</button>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="pila-summary-grid">
                    <article class="pila-kpi green"><i class="fa-solid fa-file-circle-check"></i><span>Planillas cargadas</span><strong><?php echo $planillas_cargadas; ?>/12</strong><small>Soportes registrados para la vigencia <?php echo $anio_seleccionado; ?>.</small></article>
                    <article class="pila-kpi"><i class="fa-solid fa-calendar-xmark"></i><span>Pendientes a la fecha</span><strong><?php echo $pendientes_a_la_fecha; ?></strong><small>Periodos exigibles que todavía no tienen soporte PILA.</small></article>
                    <article class="pila-kpi blue"><i class="fa-solid fa-users-shield"></i><span>Cobertura detectada</span><strong><?php echo $cobertura_promedio !== null ? $cobertura_promedio . '%' : '—'; ?></strong><small>Promedio de trabajadores encontrados frente a los esperados.</small></article>
                    <article class="pila-kpi violet"><i class="fa-solid fa-sack-dollar"></i><span>Valor anual reportado</span><strong><?php echo $valor_total_anual > 0 ? '$' . number_format($valor_total_anual, 0, ',', '.') : '—'; ?></strong><small>Suma de los valores detectados en las planillas analizadas.</small></article>
                </div>

                <div class="card-box planillas-card">

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px dashed var(--border); padding-bottom: 12px;">
                        <h3 class="card-title" style="border: none; padding: 0; margin: 0;">
                            <div class="title-icon-wrapper"><i class="fa-solid fa-calendar-check"></i></div>
                            Planillas del Año
                        </h3>

                        <div class="year-report-actions">
                            <a class="pila-report-link" href="estandar2_reporte_pdf?anio=<?php echo $anio_seleccionado; ?>" target="_blank">
                                <i class="fa-solid fa-file-pdf"></i> Reporte completo PDF
                            </a>
                            <div class="year-selector">
                                <a href="?anio=<?php echo $anio_seleccionado - 1; ?>" class="year-btn" title="Año Anterior">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                                <span class="year-display"><?php echo $anio_seleccionado; ?></span>
                                <a href="?anio=<?php echo $anio_seleccionado + 1; ?>" class="year-btn" title="Año Siguiente">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($es_representante): ?>
                        <div class="rep-summary-grid">
                            <div class="rep-metric ok">
                                <span>Planillas cargadas</span>
                                <strong><?php echo $planillas_cargadas; ?>/12</strong>
                            </div>
                            <div class="rep-metric <?php echo $planillas_pendientes === 0 ? 'ok' : 'warn'; ?>">
                                <span>Periodos pendientes</span>
                                <strong><?php echo $planillas_pendientes; ?></strong>
                            </div>
                            <div class="rep-metric">
                                <span>Valor anual reportado</span>
                                <strong><?php echo $valor_total_anual > 0 ? '$' . number_format($valor_total_anual, 0, ',', '.') : 'Sin dato'; ?></strong>
                            </div>
                            <div class="rep-metric">
                                <span>Personas ultimo soporte</span>
                                <strong><?php echo $personas_ultimo_soporte !== null ? (int)$personas_ultimo_soporte : 'Sin dato'; ?></strong>
                            </div>
                        </div>

                        <?php
                            $ultimo_mes_nombre = $ultimo_soporte ? ($meses[(int)$ultimo_soporte['mes']] ?? 'Periodo') : 'Sin soporte';
                            $ultimo_valor = $ultimo_soporte && !empty($ultimo_soporte['valor_total']) ? '$' . number_format((float)$ultimo_soporte['valor_total'], 0, ',', '.') : 'Sin dato';
                            $ultimo_personas = $personas_ultimo_soporte !== null ? (int)$personas_ultimo_soporte : 'Sin dato';
                            $porcentaje_cargado = round(($planillas_cargadas / 12) * 100);
                            $riesgos_resumen_lista = array_keys($riesgos_anuales);
                        ?>
                        <div class="rep-executive-panel">
                            <article class="rep-exec-chart">
                                <div class="rep-exec-chart-head">
                                    <span>Estado anual de planillas</span>
                                    <strong><?php echo $porcentaje_cargado; ?>%</strong>
                                </div>
                                <div class="rep-exec-track">
                                    <div class="rep-exec-fill" style="width: <?php echo $porcentaje_cargado; ?>%;"></div>
                                </div>
                                <div class="rep-exec-foot">
                                    <span><i class="fa-solid fa-circle-check"></i> <?php echo $planillas_cargadas; ?> cargadas</span>
                                    <span><i class="fa-regular fa-clock"></i> <?php echo $planillas_pendientes; ?> pendientes</span>
                                </div>
                            </article>
                            <article class="rep-exec-card">
                                <span>Ultima planilla cargada</span>
                                <strong><?php echo htmlspecialchars($ultimo_mes_nombre); ?></strong>
                                <small><?php echo $ultimo_soporte ? htmlspecialchars(date('d/m/Y', strtotime($ultimo_soporte['fecha_subida']))) : 'El Responsable SST aun no ha cargado soportes.'; ?></small>
                            </article>
                            <article class="rep-exec-card">
                                <span>Trabajadores pagados</span>
                                <strong><?php echo htmlspecialchars((string)$ultimo_personas); ?></strong>
                                <small>Conteo detectado en el ultimo soporte PILA.</small>
                            </article>
                            <article class="rep-exec-card">
                                <span>Valor pagado</span>
                                <strong><?php echo htmlspecialchars($ultimo_valor); ?></strong>
                                <small>Valor reportado en la ultima planilla analizada.</small>
                            </article>
                        </div>

                        <div class="rep-risk-summary">
                            <div>
                                <h4>Riesgos pagados identificados</h4>
                                <p>Resumen de los niveles reportados en las planillas cargadas durante la vigencia.</p>
                            </div>
                            <div class="rep-risk-list">
                                <?php if (!empty($riesgos_resumen_lista)): ?>
                                    <?php foreach ($riesgos_resumen_lista as $riesgo): ?>
                                        <span class="risk-pill"><?php echo htmlspecialchars($riesgo); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="summary-muted">Sin riesgos detectados en soportes cargados.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
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
                                            <i class="fa-solid fa-user-check" style="width: 14px; text-align: center; color: #94a3b8;"></i>
                                            <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap: 6px;">
                                            <i class="fa-solid fa-clock-rotate-left" style="width: 14px; text-align: center; color: #94a3b8;"></i>
                                            Subido el <?php echo date('d/m/Y', strtotime($p['fecha_subida'])); ?>
                                        </div>
                                    </div>
                                    <div class="month-actions">
                                        <a href="<?php echo htmlspecialchars($p['archivo_url']); ?>" target="_blank" class="btn-view" title="Ver Documento">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>
                                        <?php if ($es_sst): ?>
                                            <a href="#" onclick="showConfirmModal('Eliminar Planilla', '¿Estás seguro de eliminar la planilla de <?php echo $mes_nombre; ?>? Esta acción no se puede deshacer.', 'procesar_estandar2.php?accion=eliminar_planilla&id=<?php echo $p['id']; ?>', 'danger', 'Sí, eliminar'); return false;" class="btn-delete" title="Eliminar">
                                                <i class="fa-regular fa-trash-can"></i> Borrar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="month-info" style="color: #94a3b8; font-style: italic; display: flex; align-items: center; margin-top: 6px;">
                                        No se ha subido el soporte para este periodo.
                                    </div>
                                    <?php if (!$bloquear_subida && $es_sst): ?>
                                        <div class="month-actions">
                                            <button type="button" class="btn-outline-upload" onclick="prepararSubida(<?php echo $m; ?>, <?php echo $anio_seleccionado; ?>)">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Cargar Planilla
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>

                    <div class="pila-detail-head">
                        <div>
                            <h4>Detalle mensual de planillas</h4>
                            <p>Consulta cobertura, valor, validación, novedades y versiones de cada periodo.</p>
                        </div>
                        <div class="pila-tools">
                            <input class="pila-search" id="pilaSearch" type="search" placeholder="Buscar mes o estado...">
                            <select class="pila-filter" id="pilaStatusFilter"><option value="all">Todos</option><option value="loaded">Cargadas</option><option value="pending">Pendientes</option></select>
                            <a class="pila-files-link" href="almacenamiento?estandar=2"><i class="fa-solid fa-folder-open"></i> Archivos</a>
                        </div>
                    </div>

                    <div class="pila-period-grid" id="pilaPeriodGrid">
                        <?php for ($periodMonth = 1; $periodMonth <= 12; $periodMonth++): ?>
                            <?php
                                $periodLoaded = isset($planillas_mes[$periodMonth]);
                                $periodData = $periodLoaded ? $planillas_mes[$periodMonth] : null;
                                $periodName = $meses[$periodMonth];
                                $detected = $periodLoaded ? (int)($periodData['cedulas_detectadas'] ?? 0) : 0;
                                $expected = $periodLoaded ? (int)($periodData['trabajadores_esperados'] ?? 0) : 0;
                                $coverage = $expected > 0 ? min(100, round(($detected / $expected) * 100)) : null;
                                $downloadUrl = $periodLoaded && !empty($periodData['almacenamiento_archivo_id']) ? 'descargar_archivo?id=' . (int)$periodData['almacenamiento_archivo_id'] : ($periodData['archivo_url'] ?? '#');
                                $searchText = strtolower($periodName . ' ' . ($periodLoaded ? 'cargada validada' : 'pendiente'));
                            ?>
                            <article class="pila-period-card <?php echo $periodLoaded ? 'loaded' : 'pending'; ?>" data-state="<?php echo $periodLoaded ? 'loaded' : 'pending'; ?>" data-search="<?php echo htmlspecialchars($searchText); ?>">
                                <div class="pila-period-top">
                                    <div class="pila-period-title">
                                        <span class="pila-period-icon"><i class="fa-solid <?php echo $periodLoaded ? 'fa-file-circle-check' : 'fa-calendar-day'; ?>"></i></span>
                                        <div><strong><?php echo $periodName; ?></strong><small><?php echo $anio_seleccionado; ?> · Límite <?php echo htmlspecialchars($fechas_limites_meses[$periodMonth] ?? 'por definir'); ?></small></div>
                                    </div>
                                    <span class="pila-status"><?php echo $periodLoaded ? 'Cargada' : 'Pendiente'; ?></span>
                                </div>

                                <?php if ($periodLoaded): ?>
                                    <div class="pila-period-metrics">
                                        <div class="pila-period-metric"><span>Cobertura</span><strong><?php echo $coverage !== null ? $coverage . '%' : 'Sin dato'; ?></strong></div>
                                        <div class="pila-period-metric"><span>Valor pagado</span><strong><?php echo !empty($periodData['valor_total']) ? '$' . number_format((float)$periodData['valor_total'], 0, ',', '.') : 'Sin dato'; ?></strong></div>
                                        <div class="pila-period-metric"><span>Validación NIT</span><strong><?php echo ($periodData['nit_coincide'] ?? 'NO') === 'SI' ? 'Coincide' : 'Por revisar'; ?></strong></div>
                                        <div class="pila-period-metric"><span>Versión</span><strong>V<?php echo (int)($periodData['version_actual'] ?? 1); ?>.0</strong></div>
                                    </div>
                                    <p class="pila-period-note"><i class="fa-solid fa-user-check"></i> <?php echo htmlspecialchars(trim(($periodData['nombre'] ?? '') . ' ' . ($periodData['apellido'] ?? ''))); ?> · <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($periodData['fecha_subida']))); ?></p>
                                    <details class="pila-period-details"><summary><i class="fa-solid fa-chevron-down"></i> Ver análisis consolidado</summary><div class="pila-detail-copy"><strong>Trabajadores:</strong> <?php echo $detected ?: 'Sin dato'; ?> de <?php echo $expected ?: 'Sin dato'; ?><br><strong>Riesgos:</strong> <?php echo htmlspecialchars($periodData['riesgos_detectados'] ?: 'No detectados'); ?><br><strong>Novedades:</strong> <?php echo htmlspecialchars($periodData['novedades_resumen'] ?: 'Sin novedades reportadas.'); ?></div></details>
                                    <div class="pila-card-actions">
                                        <a class="pila-action primary" href="<?php echo htmlspecialchars($downloadUrl); ?>" target="_blank"><i class="fa-solid fa-download"></i> Soporte</a>
                                        <?php if ($es_sst): ?><button class="pila-action upload" type="button" onclick="prepararSubida(<?php echo $periodMonth; ?>,<?php echo $anio_seleccionado; ?>)"><i class="fa-solid fa-rotate"></i> Reemplazar</button><a class="pila-action danger" href="#" onclick="showConfirmModal('Eliminar planilla','¿Deseas eliminar todas las versiones de <?php echo $periodName; ?>?','procesar_estandar2?accion=eliminar_planilla&id=<?php echo (int)$periodData['id']; ?>&csrf_token=<?php echo urlencode($_SESSION['estandar2_csrf']); ?>','danger','Sí, eliminar');return false;"><i class="fa-regular fa-trash-can"></i></a><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="pila-period-metrics"><div class="pila-period-metric"><span>Estado</span><strong>Sin soporte</strong></div><div class="pila-period-metric"><span>Fecha límite</span><strong><?php echo htmlspecialchars($fechas_limites_meses[$periodMonth] ?? 'Por definir'); ?></strong></div></div>
                                    <p class="pila-period-note">Todavía no se ha registrado la planilla PILA correspondiente a este periodo.</p>
                                    <?php if ($es_sst && !$bloquear_subida): ?><div class="pila-card-actions"><button class="pila-action upload" type="button" onclick="prepararSubida(<?php echo $periodMonth; ?>,<?php echo $anio_seleccionado; ?>)"><i class="fa-solid fa-cloud-arrow-up"></i> Cargar planilla</button></div><?php endif; ?>
                                <?php endif; ?>
                            </article>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include_once 'components/modal_confirmacion.php'; ?>

    <?php if ($es_sst && !$bloquear_subida): ?>
        <div class="preview-container" id="previewContainer" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="previewModalTitle">
            <div class="preview-modal-shell">
                <div class="preview-modal-head">
                    <div class="preview-modal-title">
                        <i class="fa-solid fa-file-shield"></i>
                        <div><strong id="previewModalTitle">Revisión del soporte PILA</strong><small id="previewFileName">Documento seleccionado</small></div>
                    </div>
                    <button type="button" class="preview-modal-close" id="btnClosePreview" aria-label="Cerrar revisión"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="preview-modal-body">
                    <div class="analysis-panel" id="analysisPanel">
                        <div class="analysis-title">
                            <i class="fa-solid fa-robot"></i> Análisis rápido del documento
                        </div>
                        <div class="analysis-grid">
                            <div class="analysis-item">
                                <span class="analysis-label">Validación NIT</span>
                                <span class="analysis-val" id="resNit">Cargando...</span>
                            </div>
                            <div class="analysis-item">
                                <span class="analysis-label">Cruce de trabajadores</span>
                                <span class="analysis-val" id="resTrabajadores">Cargando...</span>
                            </div>
                            <div class="analysis-item" style="grid-column:1 / -1;">
                                <span class="analysis-label">Novedades detectadas (aprox.)</span>
                                <span class="analysis-val" id="resNovedades" style="font-weight:500;">Cargando...</span>
                            </div>
                        </div>
                        <div style="font-size:.56rem;color:#64748b;line-height:1.45;margin-top:2px;">
                            <i class="fa-solid fa-circle-info" style="color:#2563eb;"></i> La cédula debe aparecer como un número exacto en la tabla de la PILA para completar el cruce automático.
                        </div>
                    </div>

                    <div class="preview-document-panel">
                        <div class="preview-header">
                            <span><i class="fa-solid fa-eye" style="color:#2563eb;"></i> Vista previa del PDF</span>
                            <button type="button" class="btn-change-file" id="btnRemovePdf">Cambiar archivo</button>
                        </div>
                        <iframe id="pdfViewer" class="pdf-viewer" title="Vista previa del soporte PILA"></iframe>
                    </div>
                </div>

                <div class="preview-modal-footer">
                    <div class="preview-modal-note"><i class="fa-solid fa-lock"></i> Confirma que el periodo y el documento sean correctos antes de guardar.</div>
                    <div class="preview-modal-actions">
                        <button type="button" class="preview-secondary" id="btnClosePreviewBottom">Volver al formulario</button>
                        <button type="submit" class="btn-primary" id="btnSubmitForm" form="pilaUploadForm" style="display:none;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar planilla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

        function extraerValorTotalPila(texto) {
            const normalizado = texto.replace(/\s+/g, ' ');
            const candidatos = [];
            const regexMoneda = /(?:\$|COP)?\s*([0-9]{1,3}(?:[.,][0-9]{3})+(?:[.,][0-9]{2})?|[0-9]{5,})(?:\s*COP)?/gi;
            let match;

            while ((match = regexMoneda.exec(normalizado)) !== null) {
                const raw = match[1];
                const numeric = Number(raw.replace(/\./g, '').replace(/,/g, '').replace(/[^\d]/g, ''));
                if (Number.isFinite(numeric) && numeric >= 10000) {
                    candidatos.push(numeric);
                }
            }

            if (candidatos.length === 0) return '';
            return String(Math.max(...candidatos));
        }

        function detectarRiesgosPila(texto) {
            const upper = texto.toUpperCase().replace(/\s+/g, ' ');
            const riesgos = new Set();
            const patrones = [
                ['Riesgo I', /(?:RIESGO|CLASE)\s*(?:I|1)\b/],
                ['Riesgo II', /(?:RIESGO|CLASE)\s*(?:II|2)\b/],
                ['Riesgo III', /(?:RIESGO|CLASE)\s*(?:III|3)\b/],
                ['Riesgo IV', /(?:RIESGO|CLASE)\s*(?:IV|4)\b/],
                ['Riesgo V', /(?:RIESGO|CLASE)\s*(?:V|5)\b/],
            ];

            patrones.forEach(([label, regex]) => {
                if (regex.test(upper)) riesgos.add(label);
            });

            return Array.from(riesgos).join(', ');
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
            resNit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Analizando...';
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
                    resNit.innerHTML = `<span class="status-ok"><i class="fa-solid fa-circle-check"></i> Coincide (${nitBase})</span>`;
                } else {
                    resNit.innerHTML = `<span class="status-err" title="No se detectó el NIT de la empresa en el documento"><i class="fa-solid fa-circle-xmark"></i> No Coincide / No legible</span>`;
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
                            htmlTrab += `<span style="display:block; color:#dc2626;">- ${f.nombre} ${f.apellido} (C.C. ${f.cedula})</span>`;
                        });
                    } else {
                        htmlTrab += `<span style="color:#059669;"><b>¡Todos los trabajadores están al día!</b></span>`;
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
                let valorTotalPila = extraerValorTotalPila(textoCompleto);
                let riesgosPila = detectarRiesgosPila(textoCompleto);
                
                guardarDatoOculto('nit_coincide', nitCoincide ? 'SI' : 'NO');
                guardarDatoOculto('trab_found', `${empEncontradosGlobal.length}/${dbTrabajadores.length}`);
                guardarDatoOculto('trab_faltantes', nombresFaltantes);
                guardarDatoOculto('incapacidades_json', JSON.stringify(incapacidades));
                guardarDatoOculto('valor_total', valorTotalPila);
                guardarDatoOculto('cedulas_detectadas', empEncontradosGlobal.length);
                guardarDatoOculto('trabajadores_esperados', dbTrabajadores.length);
                guardarDatoOculto('riesgos_detectados', riesgosPila);

            } catch (error) {
                console.error("Error analizando PDF: ", error);
                resNit.innerHTML = '<span class="status-err"><i class="fa-solid fa-circle-xmark"></i> Error al leer PDF</span>';
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
            const uploadForm = document.querySelector('.pila-upload-form');
            const selectedFileCard = document.getElementById('selectedFileCard');
            const selectedFileName = document.getElementById('selectedFileName');
            const previewFileName = document.getElementById('previewFileName');
            const btnOpenPreview = document.getElementById('btnOpenPreview');
            const btnClosePreview = document.getElementById('btnClosePreview');
            const btnClosePreviewBottom = document.getElementById('btnClosePreviewBottom');
            const pilaSearch = document.getElementById('pilaSearch');
            const pilaStatusFilter = document.getElementById('pilaStatusFilter');
            const periodCards = Array.from(document.querySelectorAll('.pila-period-card'));
            let currentPdfUrl = '';

            const filtrarPeriodos = () => {
                const term = (pilaSearch?.value || '').trim().toLocaleLowerCase('es');
                const state = pilaStatusFilter?.value || 'all';
                periodCards.forEach(card => {
                    const matchesText = !term || (card.dataset.search || '').toLocaleLowerCase('es').includes(term);
                    const matchesState = state === 'all' || card.dataset.state === state;
                    card.hidden = !(matchesText && matchesState);
                });
            };

            pilaSearch?.addEventListener('input', filtrarPeriodos);
            pilaStatusFilter?.addEventListener('change', filtrarPeriodos);

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

                function abrirRevision() {
                    previewContainer.style.display = 'flex';
                    previewContainer.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('preview-modal-open');
                    btnClosePreview?.focus();
                }

                function cerrarRevision() {
                    previewContainer.style.display = 'none';
                    previewContainer.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('preview-modal-open');
                    btnOpenPreview?.focus();
                }

                function manejarArchivo() {
                    const file = fileInput.files[0];
                    if (file && file.type === 'application/pdf') {

                        // 1. Preparar la revisión visual en ventana modal
                        if (currentPdfUrl) URL.revokeObjectURL(currentPdfUrl);
                        currentPdfUrl = URL.createObjectURL(file);
                        pdfViewer.src = currentPdfUrl;
                        dropzone.style.display = 'none';
                        selectedFileCard.style.display = 'flex';
                        selectedFileName.textContent = file.name;
                        previewFileName.textContent = file.name;
                        btnSubmitForm.style.display = 'flex';
                        uploadForm?.classList.add('has-file');
                        abrirRevision();

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

                btnOpenPreview?.addEventListener('click', abrirRevision);
                btnClosePreview?.addEventListener('click', cerrarRevision);
                btnClosePreviewBottom?.addEventListener('click', cerrarRevision);
                previewContainer.addEventListener('click', (event) => {
                    if (event.target === previewContainer) cerrarRevision();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && previewContainer.getAttribute('aria-hidden') === 'false') cerrarRevision();
                });

                function restaurarFormulario() {
                    cerrarRevision();
                    btnSubmitForm.style.display = 'none';
                    analysisPanel.style.display = 'none';
                    dropzone.style.display = 'flex';
                    selectedFileCard.style.display = 'none';
                    selectedFileName.textContent = 'Soporte PILA seleccionado';
                    previewFileName.textContent = 'Documento seleccionado';
                    if (currentPdfUrl) {
                        URL.revokeObjectURL(currentPdfUrl);
                        currentPdfUrl = '';
                    }
                    uploadForm?.classList.remove('has-file');
                }
            }

            const alerts = document.querySelectorAll('.alert-status');
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
