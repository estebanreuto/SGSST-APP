<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Establecer zona horaria de Colombia para que el "Hoy" sea exacto
date_default_timezone_set('America/Bogota');

// Exige sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Validar permisos
if ($usuario_rol === 'trabajador') {
    header('Location: dashboard.php');
    exit;
}

// Obtener el ID de la empresa del usuario actual
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

// ==========================================
// VALIDACIÓN DE COLUMNAS NUEVAS (ALERTA DEV)
// ==========================================
$mostrar_alerta_db = false;
$alerta_tipo = "";
try {
    $stmt_check = $conn->query("SHOW COLUMNS FROM actividades_capacitacion LIKE 'descripcion'");
    if (!$stmt_check->fetch()) {
        $mostrar_alerta_db = true;
        $alerta_tipo = "columnas_faltantes";
    }
} catch (Exception $e) {}

// ==========================================
// CAMBIAR ESTADO DE LA ACTIVIDAD (Rápido)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'cambiar_estado' && isset($_GET['id']) && isset($_GET['estado'])) {
    $nuevo_estado = $_GET['estado'];
    $act_id = intval($_GET['id']);
    
    try {
        $stmt = $conn->prepare("UPDATE actividades_capacitacion SET estado = ? WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$nuevo_estado, $act_id, $empresa_id]);
        
        // Redirigir conservando los filtros de mes y año
        $mes_redir = isset($_GET['mes']) ? "&mes=".$_GET['mes'] : "";
        $anio_redir = isset($_GET['anio']) ? "&anio=".$_GET['anio'] : "";
        header("Location: estandar3.php?msg=estado_actualizado" . $mes_redir . $anio_redir);
        exit;
    } catch (PDOException $e) {}
}

// ==========================================
// ELIMINAR ACTIVIDAD DEFINITIVAMENTE
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'eliminar_actividad' && isset($_GET['id'])) {
    $act_id = intval($_GET['id']);
    
    try {
        // Eliminar posibles relaciones con trabajadores primero
        $stmt_rel = $conn->prepare("DELETE FROM actividades_trabajadores WHERE actividad_id = ?");
        $stmt_rel->execute([$act_id]);

        // Eliminar la actividad de la tabla principal
        $stmt_del = $conn->prepare("DELETE FROM actividades_capacitacion WHERE id = ? AND empresa_id = ?");
        $stmt_del->execute([$act_id, $empresa_id]);
        
        $mes_redir = isset($_GET['mes']) ? "&mes=".$_GET['mes'] : "";
        $anio_redir = isset($_GET['anio']) ? "&anio=".$_GET['anio'] : "";
        header("Location: estandar3.php?msg=actividad_eliminada" . $mes_redir . $anio_redir);
        exit;
    } catch (PDOException $e) {}
}

// ==========================================
// VARIABLES DE MES Y AÑO PARA FILTROS
// ==========================================
$mes_actual_num = isset($_GET['mes']) ? str_pad(intval($_GET['mes']), 2, '0', STR_PAD_LEFT) : date('m');
$anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$actividad_enfocada = max(0, (int)($_GET['actividad'] ?? 0));

if (!preg_match('/^(0[1-9]|1[0-2])$/', $mes_actual_num)) $mes_actual_num = date('m');
if ($anio_actual < 2020 || $anio_actual > 2100) $anio_actual = date('Y');

$meses_completos = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio', '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
$nombre_mes_actual = $meses_completos[$mes_actual_num] . ' ' . $anio_actual;

// ==========================================
// ESTADÍSTICAS GLOBALES Y MENSUALES PARA TARJETAS
// ==========================================
$total_actividades = 0; $actividades_programadas = 0; $actividades_ejecutadas = 0;
$actividades_reprogramadas = 0; $actividades_no_ejecutadas = 0; $cumplimiento_global = 0;

$total_mes = 0; $prog_mes = 0; $exec_mes = 0; 
$reprog_mes = 0; $fail_mes = 0; $cumplimiento_mes = 0;

if ($empresa_id && !$mostrar_alerta_db) {
    try {
        // --- MÉTRICAS GLOBALES ---
        $stmt_g = $conn->prepare("SELECT estado, COUNT(*) as cant FROM actividades_capacitacion WHERE empresa_id = ? GROUP BY estado");
        $stmt_g->execute([$empresa_id]);
        $res_g = $stmt_g->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $actividades_programadas = ($res_g['programada'] ?? 0);
        $actividades_reprogramadas = ($res_g['reprogramada'] ?? 0);
        $actividades_ejecutadas = ($res_g['ejecutada'] ?? 0) + ($res_g['completada'] ?? 0);
        $actividades_no_ejecutadas = ($res_g['no_ejecutada'] ?? 0) + ($res_g['cancelada'] ?? 0);
        
        $total_actividades = $actividades_programadas + $actividades_reprogramadas + $actividades_ejecutadas + $actividades_no_ejecutadas;
        $cumplimiento_global = $total_actividades > 0 ? round(($actividades_ejecutadas / $total_actividades) * 100) : 0;

        // --- MÉTRICAS DEL MES SELECCIONADO ---
        $stmt_m = $conn->prepare("SELECT estado, COUNT(*) as cant FROM actividades_capacitacion WHERE empresa_id = ? AND MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ? GROUP BY estado");
        $stmt_m->execute([$empresa_id, $mes_actual_num, $anio_actual]);
        $res_m = $stmt_m->fetchAll(PDO::FETCH_KEY_PAIR);

        $prog_mes = ($res_m['programada'] ?? 0);
        $reprog_mes = ($res_m['reprogramada'] ?? 0);
        $exec_mes = ($res_m['ejecutada'] ?? 0) + ($res_m['completada'] ?? 0);
        $fail_mes = ($res_m['no_ejecutada'] ?? 0) + ($res_m['cancelada'] ?? 0);
        
        $total_mes = $prog_mes + $reprog_mes + $exec_mes + $fail_mes;
        $cumplimiento_mes = $total_mes > 0 ? round(($exec_mes / $total_mes) * 100) : 0;

    } catch (PDOException $e) { }
}

// Porcentajes del mes para las barras
$pct_prog = $total_mes > 0 ? round(($prog_mes / $total_mes) * 100) : 0;
$pct_exec = $total_mes > 0 ? round(($exec_mes / $total_mes) * 100) : 0;
$pct_reprog = $total_mes > 0 ? round(($reprog_mes / $total_mes) * 100) : 0;
$pct_fail = $total_mes > 0 ? round(($fail_mes / $total_mes) * 100) : 0;

// ==========================================
// OBTENER DATOS PARA LA AGENDA
// ==========================================
$agenda = [];
$dias_en_mes = date('t', strtotime("$anio_actual-$mes_actual_num-01")); 
$hoy = date('Y-m-d');
$meses_cortos = ['01'=>'ENE', '02'=>'FEB', '03'=>'MAR', '04'=>'ABR', '05'=>'MAY', '06'=>'JUN', '07'=>'JUL', '08'=>'AGO', '09'=>'SEP', '10'=>'OCT', '11'=>'NOV', '12'=>'DIC'];
$dias_semana = ['0'=>'DOM', '1'=>'LUN', '2'=>'MAR', '3'=>'MIÉ', '4'=>'JUE', '5'=>'VIE', '6'=>'SÁB'];

for ($i = 1; $i <= $dias_en_mes; $i++) {
    $fecha_iter = sprintf('%04d-%02d-%02d', $anio_actual, $mes_actual_num, $i);
    $ts_iter = strtotime($fecha_iter);
    $agenda[$fecha_iter] = [
        'dia_num' => $i,
        'mes_dia' => $meses_cortos[$mes_actual_num] . ', ' . $dias_semana[date('w', $ts_iter)],
        'es_hoy' => ($fecha_iter === $hoy),
        'eventos' => []
    ];
}

if ($empresa_id && !$mostrar_alerta_db) {
    try {
        $stmt_act = $conn->prepare("SELECT id, nombre_actividad, tipo_capacitacion, categoria, fecha_inicio, fecha_fin, enlace_reunion, estado, modalidad, lugar_exacto, descripcion 
                                    FROM actividades_capacitacion 
                                    WHERE empresa_id = ? 
                                    AND fecha_inicio IS NOT NULL 
                                    AND MONTH(fecha_inicio) = ? 
                                    AND YEAR(fecha_inicio) = ?
                                    ORDER BY DATE(fecha_inicio) ASC, fecha_inicio ASC");
        $stmt_act->execute([$empresa_id, $mes_actual_num, $anio_actual]);
        $actividades_raw = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

        $google_colors = ['#039be5', '#33b679', '#8e24aa', '#e67c73', '#f6bf26', '#f4511e', '#3f51b5', '#0b8043'];

        foreach ($actividades_raw as $act) {
            $ts_inicio = strtotime($act['fecha_inicio']);
            $ts_fin = strtotime($act['fecha_fin']);
            $fecha_key = date('Y-m-d', $ts_inicio);
            
            if (isset($agenda[$fecha_key])) {
                $start_time = date('g:ia', $ts_inicio);
                $end_time = date('g:ia', $ts_fin);
                $start_time = str_replace([':00', 'am', 'pm'], ['', 'am', 'pm'], $start_time);
                $end_time = str_replace([':00', 'am', 'pm'], ['', 'am', 'pm'], $end_time);
                
                $start_ampm = substr($start_time, -2);
                $end_ampm = substr($end_time, -2);
                
                if ($start_ampm === $end_ampm) {
                    $start_time = str_replace($start_ampm, '', $start_time);
                }
                $act['time_str'] = $start_time . ' - ' . $end_time;
                $hash = crc32($act['categoria'] . $act['tipo_capacitacion']);
                $act['color'] = $google_colors[$hash % count($google_colors)];
                
                $agenda[$fecha_key]['eventos'][] = $act;
            }
        }
    } catch (PDOException $e) { }
}

$current_page = 'estandar3.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 3 | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ALERTA DB / ELIMINACIÓN */
        .alert-dev { background: #fffbeb; border: 1px solid #fde68a; padding: 16px 24px; border-radius: 12px; color: #d97706; margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.05); }
        .alert-dev svg { flex-shrink: 0; margin-top: 2px; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; gap: 12px; font-weight: 600; align-items: center; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; gap: 12px; font-weight: 600; align-items: center; }
        
        /* ENCABEZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid rgba(148, 163, 184, 0.22); }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; }

        /* =========================================
           INDICADORES OPERATIVOS
           ========================================= */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
        .summary-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 14px; min-width: 0; position: relative; overflow: hidden !important; box-shadow: 0 3px 10px rgba(15, 23, 42, 0.025); transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; z-index: 1;}
        .summary-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06); border-color: #cbd5e1; }
        
        .summary-bg-icon { position: absolute; right: -12px; bottom: -16px; font-size: 72px; color: var(--primary); opacity: 0.035; transform: rotate(-15deg); transition: all 0.3s ease; pointer-events: none; z-index: 0; }
        .summary-card:hover .summary-bg-icon { transform: rotate(0deg) scale(1.08); opacity: 0.07; }
        
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .summary-icon-box { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink:0;}
        .summary-value { font-size: 1.45rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; text-align: right;}
        .summary-title { font-size: 0.82rem; font-weight: 750; color: var(--blue-dark); margin: 0 0 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .summary-desc { font-size: 0.68rem; color: var(--muted); margin: 0; line-height: 1.35; min-height: 1.8em;}

        /* Colores Específicos */
        .card-total .summary-icon-box { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .card-total .summary-value { color: var(--primary); }
        .card-total .summary-bg-icon { color: var(--primary); }

        .card-prog .summary-icon-box { background: rgba(14, 165, 233, 0.1); color: #0284c7; }
        .card-prog .summary-value { color: #0284c7; }
        .card-prog .summary-bg-icon { color: #0284c7; }

        .card-exec .summary-icon-box { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .card-exec .summary-value { color: #16a34a; }
        .card-exec .summary-bg-icon { color: #16a34a; }

        .card-reprog .summary-icon-box { background: rgba(217, 119, 6, 0.1); color: #d97706; }
        .card-reprog .summary-value { color: #d97706; }
        .card-reprog .summary-bg-icon { color: #d97706; }

        .card-fail .summary-icon-box { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
        .card-fail .summary-value { color: #dc2626; }
        .card-fail .summary-bg-icon { color: #dc2626; }

        .card-kpi .summary-icon-box { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .card-kpi .summary-value { color: #6366f1; }
        .card-kpi .summary-bg-icon { color: #6366f1; }

        /* ACORDEÓN MOSTRAR MÁS EN TARJETAS */
        .btn-show-more { background: transparent; border: none; font-size: 0.68rem; font-weight: 700; color: #94a3b8; cursor: pointer; padding: 0; margin-top: 9px; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s; }
        .btn-show-more:hover { color: var(--primary); }
        .btn-show-more i { transition: transform 0.3s; }
        .btn-show-more.active i { transform: rotate(180deg); color: var(--primary);}
        
        .card-extra-info { display: none; margin-top: 10px; padding-top: 12px; border-top: 1px dashed var(--border); animation: fadeIn 0.3s ease; }
        .month-stat-text { font-size: 0.75rem; color: #475569; display: flex; justify-content: space-between; margin-bottom: 6px; font-weight: 600; }
        .progress-bar-bg { width: 100%; height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }


        /* =========================================
           BARRA DE HERRAMIENTAS Y FILTROS PREMIUM
           ========================================= */
        .workspace-toolbar { background: var(--card); border: 1px solid var(--border); border-top: 3px solid var(--primary); border-radius: var(--radius); padding: 14px 18px; display: flex; flex-direction: column; gap: 14px; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.035); margin-bottom: 16px; }
        .rep-training-dashboard { display: grid; grid-template-columns: minmax(260px, .95fr) minmax(0, 1.05fr); gap: 16px; margin-bottom: 20px; }
        .rep-training-panel { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.035); }
        .rep-training-panel h3 { margin: 0 0 6px; color: var(--blue-dark); font-size: 1rem; }
        .rep-training-panel p { margin: 0 0 16px; color: var(--muted); line-height: 1.45; }
        .rep-ring-wrap { display: grid; grid-template-columns: 150px 1fr; align-items: center; gap: 18px; }
        .rep-ring { --value: 0; width: 136px; aspect-ratio: 1; border-radius: 50%; background: conic-gradient(#16a34a calc(var(--value) * 1%), #e2e8f0 0); position: relative; display: grid; place-items: center; }
        .rep-ring::after { content: ''; position: absolute; inset: 14px; border-radius: 50%; background: #fff; }
        .rep-ring strong { position: relative; z-index: 1; color: var(--blue-dark); font-size: 1.65rem; }
        .rep-training-bars { display: flex; flex-direction: column; gap: 10px; }
        .rep-training-row { display: grid; grid-template-columns: 145px 1fr 56px; gap: 10px; align-items: center; font-size: .78rem; color: #334155; font-weight: 750; }
        .rep-training-row.is-total, .rep-training-row.is-kpi { color: var(--blue-dark); font-weight: 900; }
        .rep-training-track { height: 10px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .rep-training-fill { height: 100%; border-radius: 999px; background: var(--bar, #ff8a1f); }
        .rep-training-note { margin-top: 16px; border: 1px solid #fed7aa; background: #fff7ed; color: #9a3412; border-radius: 12px; padding: 12px 14px; font-size: .82rem; font-weight: 700; display: flex; gap: 9px; align-items: flex-start; }
        
        .toolbar-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; width: 100%; }
        
        .search-box { position: relative; flex: 1 1 200px; max-width: 400px; box-sizing: border-box; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; color: var(--text); background: #f8fafc; outline: none; transition: 0.2s; box-sizing: border-box;}
        .search-box input:focus { border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        .toolbar-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; flex-shrink: 0; }
        
        .date-selectors { display: flex; gap: 8px; flex-shrink: 0;}
        .date-selectors select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; color: var(--text); background: #f8fafc; outline: none; cursor: pointer; font-weight: 500; appearance: none; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; padding-right: 30px;}
        .date-selectors select:hover { border-color: #94a3b8; }
        .date-selectors select:focus { border-color: var(--primary); }

        .btn-clear-filters { background: transparent; color: #ef4444; border: 1px solid transparent; padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; display: none; align-items: center; gap: 6px; white-space: nowrap;}
        .btn-clear-filters:hover { background: #fef2f2; color: #dc2626; }

        .btn-filter-toggle { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; flex-shrink: 0;}
        .btn-filter-toggle:hover, .btn-filter-toggle.active { background: #f1f5f9; color: var(--blue-dark); border-color: #94a3b8; }
        
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.15); white-space: nowrap; flex-shrink: 0; box-sizing: border-box;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.25); color: white;}

        /* Panel de Filtros Avanzados (Desplegable) */
        .advanced-filters { width: 100%; display: none; grid-template-columns: repeat(3, 1fr); gap: 16px; padding-top: 16px; border-top: 1px dashed #cbd5e1; animation: fadeIn 0.3s ease; }
        .advanced-filters.active { display: grid; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .filter-group select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; color: var(--text); outline: none; cursor: pointer; box-sizing: border-box;}
        .filter-group select:focus { border-color: var(--primary); }

        /* AGENDA */
        .agenda-container { width: 100%; margin-top: 8px; background: rgba(255,255,255,0.7); border: 1px solid rgba(219,227,236,0.85); border-radius: 12px; padding: 4px 20px 18px; box-sizing: border-box; }
        .agenda-day-block { display: flex; padding: 16px 0; border-bottom: 1px solid rgba(0,0,0,0.06); scroll-margin-top: 100px; }
        .agenda-date-col { width: 80px; display: flex; flex-direction: column; align-items: flex-start; flex-shrink: 0; padding-top: 6px; }
        
        .date-number { font-size: 1.6rem; font-weight: 400; color: #70757a; line-height: 1; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; }
        .date-number.is-today { background: #1a73e8; color: #fff; border-radius: 50%; font-weight: 500; }
        .date-text { font-size: 0.7rem; font-weight: 600; color: #70757a; text-transform: uppercase; letter-spacing: 0.05em; padding-left: 2px; }
        .date-text.is-today { color: #1a73e8; }

        .agenda-events-col { flex: 1; display: flex; flex-direction: column; gap: 4px; position: relative; min-height: 48px; justify-content: center;}
        .agenda-event-row { display: flex; flex-direction: column; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; border: 1px solid transparent;}
        .agenda-event-row:hover { background: #f8fafc; border-color: #e2e8f0;}

        .event-main-info { display: flex; align-items: center; width: 100%; }
        .event-dot { width: 10px; height: 10px; border-radius: 50%; margin-right: 18px; flex-shrink: 0; }
        .event-time { font-size: 0.85rem; color: #3c4043; width: 100px; flex-shrink: 0; font-weight: 400; }
        
        /* TÍTULO CON PUNTOS SUSPENSIVOS Y CATEGORÍA PROTEGIDA */
        .event-title-wrapper { flex: 1; display: flex; align-items: center; gap: 8px; min-width: 0; /* Crucial para el ellipsis */ }
        .event-name-text { font-size: 0.9rem; font-weight: 600; color: #3c4043; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-category-badge { font-weight: 500; color: #70757a; font-size: 0.8rem; flex-shrink: 0; white-space: nowrap; }

        .event-indicators { display: flex; align-items: center; gap: 12px; margin-left: auto; padding-right: 10px; }
        .badge-status { font-size: 0.65rem; padding: 3px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid transparent; white-space: nowrap;}
        .badge-status.programada { background: #e0f2fe; color: #2563eb; border-color: #bae6fd;}
        .badge-status.reprogramada { background: #fefce8; color: #d97706; border-color: #fde047;}
        .badge-status.ejecutada { background: #dcfce7; color: #16a34a; border-color: #bbf7d0;}
        .badge-status.no_ejecutada { background: #fee2e2; color: #dc2626; border-color: #fecaca;}
        
        .meet-indicator { color: #1a73e8; font-size: 0.9rem; }
        .no-meet-indicator { color: #cbd5e1; font-size: 0.9rem; }
        
        /* BOTÓN DE BORRAR EVENTO */
        .btn-delete-event { background: transparent; border: none; color: #cbd5e1; cursor: pointer; padding: 6px; font-size: 0.95rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; }
        .btn-delete-event:hover { color: #dc2626; background: #fee2e2; }

        .btn-toggle-info { background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px 8px; font-size: 0.8rem; transition: all 0.2s; flex-shrink: 0; }
        .btn-toggle-info:hover { color: #1e293b; }

        /* ACORDEÓN MEJORADO */
        .event-details { display: none; margin-top: 12px; padding-left: 28px; padding-bottom: 8px; animation: fadeIn 0.2s ease; }
        .detail-item { font-size: 0.85rem; color: #475569; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        
        .meet-link-box { display: flex; align-items: center; gap: 8px; background: transparent; width: fit-content; }
        .meet-link-box a { color: #1a73e8; text-decoration: none; font-weight: 500; font-size: 0.85rem;}
        .meet-link-box a:hover { text-decoration: underline; }
        .btn-copy { background: transparent; border: none; color: #64748b; padding: 4px; cursor: pointer; transition: color 0.2s; display: flex; align-items: center; font-size: 0.8rem; }
        .btn-copy:hover { color: #1e293b; }

        /* BOTONES DE GESTIÓN */
        .action-buttons-group { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.04); }
        .btn-state { padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid transparent; }
        .btn-state:hover { transform: translateY(-1px); }
        .btn-ejecutado { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .btn-ejecutado:hover { background: #dcfce7; }
        .btn-reprogramado { background: #fefce8; color: #d97706; border-color: #fde047; }
        .btn-reprogramado:hover { background: #fef9c3; }
        .btn-no-ejecutado { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .btn-no-ejecutado:hover { background: #fee2e2; }

        /* BOTONES DE EJECUTADO (Limpios) */
        .executed-actions { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.04);}
        .btn-action { padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; background: transparent; cursor: pointer;}
        .btn-informe { color: #f97316; border: 1px solid #fdba74; }
        .btn-informe:hover { background: #fff7ed; border-color: #f97316; transform: translateY(-1px); }
        .btn-asistencia { color: #3b82f6; border: 1px solid #93c5fd; }
        .btn-asistencia:hover { background: #eff6ff; border-color: #3b82f6; transform: translateY(-1px); }
        
        .not-executed-msg { font-size: 0.8rem; color: #dc2626; font-style: italic; margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.04); display: flex; align-items: center; gap: 6px;}

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* MÁS UTILIDADES */
        .agenda-end-message { margin-top: 30px; padding: 20px 24px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px; display: flex; align-items: center; gap: 16px; transition: all 0.3s ease; }
        .end-message-icon { width: 48px; height: 48px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #64748b; box-shadow: 0 4px 10px rgba(0,0,0,0.03); flex-shrink: 0; }
        .end-message-content h4 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #334155; }
        .end-message-content p { margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4; }

        /* MARCA DE AGUA (Solo para el día de Hoy vacío) */
        .today-empty-watermark { display: flex; align-items: center; gap: 12px; padding-left: 12px; overflow: hidden; pointer-events: none; }
        .watermark-bg-animated { font-size: 1.8rem; color: #1a73e8; opacity: 0.15; animation: pulseIcon 3s infinite ease-in-out; }
        .today-empty-text { font-size: 0.85rem; color: #94a3b8; font-style: italic; font-weight: 500; opacity: 0.8; }
        @keyframes pulseIcon { 0% { transform: scale(1) rotate(-5deg); opacity: 0.1; } 50% { transform: scale(1.15) rotate(5deg); opacity: 0.2; } 100% { transform: scale(1) rotate(-5deg); opacity: 0.1; } }

        /* =========================================
           BOTONES FLOTANTES (HOY & INFORMES) INTELIGENTES
           ========================================= */
        .btn-floating-informes { 
            position: fixed; 
            bottom: 30px; /* Posición por defecto (abajo) */
            right: 30px; 
            background: linear-gradient(135deg, var(--primary), var(--primary2)); 
            color: #fff; 
            border: none; 
            border-radius: 50px; 
            padding: 12px 20px; 
            font-size: 0.9rem; 
            font-weight: 700; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: 0 4px 15px rgba(255, 138, 31, 0.3); 
            cursor: pointer; 
            z-index: 999; 
            text-decoration: none; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .btn-floating-informes:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(255, 138, 31, 0.4); color: white; }
        
        /* MAGIA: Esta clase se activa por JS cuando aparece el botón "Hoy" */
        .btn-floating-informes.shifted { bottom: 85px; }

        .btn-floating-today { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            background: #ffffff; 
            color: #1a73e8; 
            border: 1px solid #e2e8f0; 
            border-radius: 50px; 
            padding: 12px 20px; 
            font-size: 0.9rem; 
            font-weight: 700; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
            cursor: pointer; 
            opacity: 0; 
            visibility: hidden; 
            transform: translateY(20px); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            z-index: 1000; 
        }
        .btn-floating-today.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .btn-floating-today:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); color: #174ea6; border-color: #cbd5e1; }

        /* RESPONSIVE */
        @media (max-width: 1180px) {
            .summary-cards-grid { grid-template-columns: repeat(3, 1fr); }
            .rep-training-dashboard { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .advanced-filters { grid-template-columns: 1fr 1fr; }
            .btn-floating-informes { bottom: 20px; right: 20px; padding: 10px 16px; font-size: 0.85rem; }
            .btn-floating-informes.shifted { bottom: 74px; }
            .btn-floating-today { bottom: 20px; right: 20px; padding: 10px 16px; font-size: 0.85rem; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .estandar-header-group { flex-direction: row; align-items: flex-start; gap: 12px; width: 100%; }
            .summary-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .rep-ring-wrap { grid-template-columns: 1fr; justify-items: center; text-align: center; }
            .rep-training-row { grid-template-columns: 1fr; gap: 5px; }
            .workspace-toolbar { padding: 16px; }
            .summary-card { padding: 12px; }
            .summary-desc { display: none; }
            .summary-title { font-size: 0.78rem; }
            .agenda-container { padding: 2px 12px 14px; }
            
            .toolbar-top { flex-direction: column; align-items: stretch; gap: 12px;}
            .search-box { max-width: 100%; flex: 1 1 100%; }
            .toolbar-actions { width: 100%; justify-content: space-between; }
            .btn-primary { width: 100%; justify-content: center; margin-top: 10px; }
            .advanced-filters { grid-template-columns: 1fr; }
            
            .agenda-day-block { flex-direction: column; padding: 16px 0; }
            .agenda-date-col { width: 100%; flex-direction: row; align-items: center; padding-top: 0; margin-bottom: 12px; }
            .date-number { margin-bottom: 0; margin-right: 12px; }
            .date-text { padding-left: 0; }
            .event-time { width: 80px; }
            .event-indicators { flex-direction: row; margin-left: 0; margin-top: 4px; width: 100%; justify-content: flex-start; padding-left: 110px; flex-wrap: wrap;}
            .event-main-info { flex-wrap: wrap; }
            .btn-toggle-info { position: absolute; right: 10px; top: 12px; }
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
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title"><span class="std-num-marker">3.</span> Capacitación en SST</h1>
                        <p class="estandar-subtitle">Elaborar, ejecutar y hacer seguimiento al programa de capacitación.</p>
                    </div>
                </div>
            </div>

            <?php if ($mostrar_alerta_db): ?>
                <div class="alert-dev">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">¡Atención Desarrollador! Actualiza la Base de Datos</strong>
                        Para poder guardar la Modalidad y la Descripción de la reunión, ejecuta esto en phpMyAdmin (pestaña SQL):
                        <code style="background: rgba(0,0,0,0.05); padding: 6px 10px; border-radius: 6px; color: #b45309; display: inline-block; margin-top: 6px; font-weight: 700; word-break: break-all; font-family: monospace; font-size: 13px;">
                            ALTER TABLE actividades_capacitacion ADD COLUMN modalidad VARCHAR(50) DEFAULT 'Virtual', ADD COLUMN lugar_exacto VARCHAR(255) NULL, ADD COLUMN descripcion TEXT NULL;
                        </code>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'actividad_eliminada'): ?>
                <div class="alert-danger">
                    <i class="fa-solid fa-trash-can"></i> Actividad eliminada correctamente de la agenda.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['save']) && $_GET['save'] === 'success'): ?>
                <?php if (($_GET['calendar'] ?? '') === 'error'): ?>
                    <div class="alert-dev">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <div>
                            <strong style="display:block;margin-bottom:3px;">Actividad guardada; calendario pendiente</strong>
                            <?php echo htmlspecialchars((string)($_GET['calendar_message'] ?? 'Revisa la conexión desde Configuración e intenta nuevamente.')); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert-success">
                        <i class="fa-solid fa-calendar-check"></i>
                        <?php echo (($_GET['calendar'] ?? '') === 'synced')
                            ? 'Actividad guardada y reunión sincronizada con tu calendario.'
                            : 'Actividad guardada en PreventWork. Puedes conectar un calendario desde Configuración.'; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['save']) && $_GET['save'] === 'curso_success'): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-graduation-cap"></i> Curso, evaluación y asignación guardados correctamente.
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol !== 'representante'): ?>
            <div class="summary-cards-grid">
                
                <div class="summary-card card-total">
                    <i class="fa-solid fa-list-check summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-list-check"></i></div>
                            <h2 class="summary-value"><?php echo $total_actividades; ?></h2>
                        </div>
                        <h3 class="summary-title">Total Actividades</h3>
                        <p class="summary-desc">Histórico global de capacitaciones.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>Total mes:</span><span><?php echo $total_mes; ?></span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $total_mes > 0 ? 100 : 0; ?>%; background: var(--primary);"></div></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card card-prog">
                    <i class="fa-solid fa-calendar-days summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-calendar-days"></i></div>
                            <h2 class="summary-value"><?php echo $actividades_programadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Programadas</h3>
                        <p class="summary-desc">Pendientes por realizar globalmente.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>En el mes:</span><span><?php echo $prog_mes; ?> (<?php echo $pct_prog; ?>%)</span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $pct_prog; ?>%; background: #0284c7;"></div></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card card-exec">
                    <i class="fa-solid fa-check-double summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-check-double"></i></div>
                            <h2 class="summary-value"><?php echo $actividades_ejecutadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Ejecutadas</h3>
                        <p class="summary-desc">Finalizadas con éxito globalmente.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>En el mes:</span><span><?php echo $exec_mes; ?> (<?php echo $pct_exec; ?>%)</span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $pct_exec; ?>%; background: #16a34a;"></div></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card card-reprog">
                    <i class="fa-solid fa-clock-rotate-left summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <h2 class="summary-value"><?php echo $actividades_reprogramadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Reprogramadas</h3>
                        <p class="summary-desc">Modificadas de fecha globalmente.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>En el mes:</span><span><?php echo $reprog_mes; ?> (<?php echo $pct_reprog; ?>%)</span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $pct_reprog; ?>%; background: #d97706;"></div></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card card-fail">
                    <i class="fa-solid fa-calendar-xmark summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-calendar-xmark"></i></div>
                            <h2 class="summary-value"><?php echo $actividades_no_ejecutadas; ?></h2>
                        </div>
                        <h3 class="summary-title">No Ejecutadas</h3>
                        <p class="summary-desc">Canceladas o perdidas globalmente.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>En el mes:</span><span><?php echo $fail_mes; ?> (<?php echo $pct_fail; ?>%)</span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $pct_fail; ?>%; background: #dc2626;"></div></div>
                        </div>
                    </div>
                </div>

                <div class="summary-card card-kpi">
                    <i class="fa-solid fa-chart-line summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-chart-line"></i></div>
                            <h2 class="summary-value"><?php echo $cumplimiento_global; ?>%</h2>
                        </div>
                        <h3 class="summary-title">Efectividad Global</h3>
                        <p class="summary-desc">Tasa histórica de éxito del SG-SST.</p>
                        
                        <button class="btn-show-more" onclick="toggleCardInfo(this)">
                            Resumen <?php echo $nombre_mes_actual; ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="card-extra-info">
                            <div class="month-stat-text"><span>Efectividad mes:</span><span><?php echo $cumplimiento_mes; ?>%</span></div>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?php echo $cumplimiento_mes; ?>%; background: #6366f1;"></div></div>
                        </div>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'representante'): ?>
                <section class="rep-training-dashboard">
                    <article class="rep-training-panel">
                        <h3>Mes actual: <?php echo htmlspecialchars($nombre_mes_actual); ?></h3>
                        <p>Estado de las actividades de capacitaciÃ³n programadas para este periodo.</p>
                        <div class="rep-ring-wrap">
                            <div class="rep-ring" style="--value: <?php echo $cumplimiento_mes; ?>;"><strong><?php echo $cumplimiento_mes; ?>%</strong></div>
                            <div class="rep-training-bars">
                                <div class="rep-training-row is-total"><span>Total actividades</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#ff8a1f;width:<?php echo $total_mes > 0 ? 100 : 0; ?>%"></div></div><strong><?php echo $total_mes; ?></strong></div>
                                <div class="rep-training-row"><span>Programadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#0284c7;width:<?php echo $pct_prog; ?>%"></div></div><strong><?php echo $prog_mes; ?></strong></div>
                                <div class="rep-training-row"><span>Ejecutadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#16a34a;width:<?php echo $pct_exec; ?>%"></div></div><strong><?php echo $exec_mes; ?></strong></div>
                                <div class="rep-training-row"><span>Reprogramadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#d97706;width:<?php echo $pct_reprog; ?>%"></div></div><strong><?php echo $reprog_mes; ?></strong></div>
                                <div class="rep-training-row"><span>No ejecutadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#dc2626;width:<?php echo $pct_fail; ?>%"></div></div><strong><?php echo $fail_mes; ?></strong></div>
                                <div class="rep-training-row is-kpi"><span>Efectividad del mes</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#6366f1;width:<?php echo $cumplimiento_mes; ?>%"></div></div><strong><?php echo $cumplimiento_mes; ?>%</strong></div>
                            </div>
                        </div>
                    </article>
                    <article class="rep-training-panel">
                        <h3>Avance global del plan de capacitaciÃ³n</h3>
                        <p>Lectura acumulada de actividades creadas por el Responsable SST.</p>
                        <div class="rep-ring-wrap">
                            <div class="rep-ring" style="--value: <?php echo $cumplimiento_global; ?>;"><strong><?php echo $cumplimiento_global; ?>%</strong></div>
                            <div class="rep-training-bars">
                                <div class="rep-training-row is-total"><span>Total actividades</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#ff8a1f;width:<?php echo $total_actividades > 0 ? 100 : 0; ?>%"></div></div><strong><?php echo $total_actividades; ?></strong></div>
                                <div class="rep-training-row"><span>Programadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#0284c7;width:<?php echo $total_actividades ? round(($actividades_programadas / $total_actividades) * 100) : 0; ?>%"></div></div><strong><?php echo $actividades_programadas; ?></strong></div>
                                <div class="rep-training-row"><span>Ejecutadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#16a34a;width:<?php echo $total_actividades ? round(($actividades_ejecutadas / $total_actividades) * 100) : 0; ?>%"></div></div><strong><?php echo $actividades_ejecutadas; ?></strong></div>
                                <div class="rep-training-row"><span>Reprogramadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#d97706;width:<?php echo $total_actividades ? round(($actividades_reprogramadas / $total_actividades) * 100) : 0; ?>%"></div></div><strong><?php echo $actividades_reprogramadas; ?></strong></div>
                                <div class="rep-training-row"><span>No ejecutadas</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#dc2626;width:<?php echo $total_actividades ? round(($actividades_no_ejecutadas / $total_actividades) * 100) : 0; ?>%"></div></div><strong><?php echo $actividades_no_ejecutadas; ?></strong></div>
                                <div class="rep-training-row is-kpi"><span>Efectividad global</span><div class="rep-training-track"><div class="rep-training-fill" style="--bar:#6366f1;width:<?php echo $cumplimiento_global; ?>%"></div></div><strong><?php echo $cumplimiento_global; ?>%</strong></div>
                            </div>
                        </div>
                        <div class="rep-training-note"><i class="fa-solid fa-circle-info"></i><span>Esta vista es informativa. La programaciÃ³n, ejecuciÃ³n y reprogramaciÃ³n la gestiona el Responsable SST.</span></div>
                    </article>
                </section>
            <?php endif; ?>

            <?php if ($usuario_rol === 'sst'): ?>
            <div id="workspace-estandar3">
                <div class="workspace-toolbar">
                    <div class="toolbar-top">
                        
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="filtroTexto" placeholder="Buscar actividad o tema...">
                        </div>
                        
                        <div class="toolbar-actions">
                            
                            <div class="date-selectors">
                                <select id="filtroMes" onchange="cambiarMesAnio()">
                                    <?php foreach($meses_completos as $num => $nombre): ?>
                                        <option value="<?php echo $num; ?>" <?php echo $num === $mes_actual_num ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filtroAnio" onchange="cambiarMesAnio()">
                                    <?php 
                                    $anio_base = date('Y') - 2; 
                                    for($i = 0; $i < 5; $i++): 
                                        $a = $anio_base + $i;
                                    ?>
                                        <option value="<?php echo $a; ?>" <?php echo $a == $anio_actual ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <button class="btn-clear-filters" id="btnLimpiarFiltros" onclick="limpiarFiltros()" title="Borrar filtros">
                                <i class="fa-solid fa-xmark"></i> Limpiar
                            </button>

                            <button class="btn-filter-toggle" id="btnFiltrosToggle" onclick="toggleFiltrosPanel()">
                                <i class="fa-solid fa-filter"></i> Filtros
                            </button>

                            <?php if ($usuario_rol === 'sst'): ?>
                                <a href="nueva_actividad.php" class="btn-primary">
                                    <i class="fa-solid fa-plus"></i>
                                    Nueva Actividad
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="advanced-filters" id="panelFiltros">
                        <div class="filter-group">
                            <label>Estado de la Reunión</label>
                            <select id="filtroEstado" onchange="aplicarFiltrosJS()">
                                <option value="">Todos los estados</option>
                                <option value="programada">Programada</option>
                                <option value="reprogramada">Reprogramada</option>
                                <option value="ejecutada">Ejecutada / Completada</option>
                                <option value="no_ejecutada">No Ejecutada / Cancelada</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Categoría SG-SST</label>
                            <select id="filtroCategoria" onchange="aplicarFiltrosJS()">
                                <option value="">Todas las categorías</option>
                                <option value="Biológico">Biológico</option>
                                <option value="Físico">Físico</option>
                                <option value="Químico">Químico</option>
                                <option value="Psicosocial">Psicosocial</option>
                                <option value="Biomecánicos">Biomecánicos</option>
                                <option value="Mecánico">Mecánico</option>
                                <option value="Eléctrico">Eléctrico</option>
                                <option value="Locativo">Locativo</option>
                                <option value="Seguridad Vial">Seguridad Vial</option>
                                <option value="Públicos">Públicos</option>
                                <option value="Trabajo en alturas">Trabajo en alturas</option>
                                <option value="Legal">Legal</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Modalidad</label>
                            <select id="filtroModalidad" onchange="aplicarFiltrosJS()">
                                <option value="">Cualquier modalidad</option>
                                <option value="Virtual">Virtual</option>
                                <option value="Físico">Presencial (Físico)</option>
                                <option value="Mixto">Mixto</option>
                                <option value="Sistema">Sistema (Curso)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="agenda-container">
                    <?php foreach ($agenda as $fecha => $dia_data): ?>
                        
                        <?php 
                        // LÓGICA DE DÍAS: 
                        // 1. Ocultar los días pasados vacíos.
                        $es_pasado = ($fecha < $hoy);
                        if ($es_pasado && empty($dia_data['eventos'])) { continue; }
                        
                        // 2. Definir clases temporales
                        $clase_tiempo = $dia_data['es_hoy'] ? 'dia-hoy' : ($es_pasado ? 'dia-pasado' : 'dia-futuro');
                        ?>
                        
                        <div class="agenda-day-block dia-agenda <?php echo $clase_tiempo; ?>" <?php echo $dia_data['es_hoy'] ? 'id="agenda-dia-hoy"' : ''; ?>>
                            
                            <div class="agenda-date-col">
                                <div class="date-number <?php echo $dia_data['es_hoy'] ? 'is-today' : ''; ?>">
                                    <?php echo $dia_data['dia_num']; ?>
                                </div>
                                <div class="date-text <?php echo $dia_data['es_hoy'] ? 'is-today' : ''; ?>">
                                    <?php echo $dia_data['mes_dia']; ?>
                                </div>
                            </div>
                            
                            <div class="agenda-events-col contenedor-eventos">
                                <?php if (!empty($dia_data['eventos'])): ?>
                                    <?php foreach ($dia_data['eventos'] as $evento): ?>
                                        
                                        <div class="agenda-event-row evento-item" id="actividad-<?php echo (int)$evento['id']; ?>"
                                             data-texto="<?php echo strtolower(htmlspecialchars($evento['nombre_actividad'].' '.$evento['categoria'])); ?>"
                                             data-estado="<?php echo $evento['estado']; ?>"
                                             data-categoria="<?php echo htmlspecialchars($evento['categoria']); ?>"
                                             data-modalidad="<?php echo htmlspecialchars($evento['modalidad'] ?? 'Virtual'); ?>"
                                             onclick="toggleDetails(<?php echo $evento['id']; ?>)">
                                            
                                            <div class="event-main-info">
                                                <div class="event-dot" style="background-color: <?php echo $evento['color']; ?>;"></div>
                                                <div class="event-time"><?php echo $evento['time_str']; ?></div>
                                                
                                                <div class="event-title-wrapper">
                                                    <span class="event-name-text"><?php echo htmlspecialchars($evento['nombre_actividad']); ?></span>
                                                    <span class="event-category-badge">- <?php echo htmlspecialchars($evento['categoria']); ?></span>
                                                </div>

                                                <div class="event-indicators">
                                                    <?php if($evento['estado'] === 'programada'): ?>
                                                        <span class="badge-status programada">Programada</span>
                                                    <?php elseif($evento['estado'] === 'reprogramada'): ?>
                                                        <span class="badge-status reprogramada">Reprogramada</span>
                                                    <?php elseif($evento['estado'] === 'ejecutada' || $evento['estado'] === 'completada'): ?>
                                                        <span class="badge-status ejecutada">Ejecutada</span>
                                                    <?php elseif($evento['estado'] === 'no_ejecutada' || $evento['estado'] === 'cancelada'): ?>
                                                        <span class="badge-status no_ejecutada">No Ejecutada</span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($evento['enlace_reunion'])): ?>
                                                        <span class="meet-indicator" title="Tiene enlace de Meet"><i class="fa-solid fa-video"></i></span>
                                                    <?php else: ?>
                                                        <span class="no-meet-indicator" title="Sin enlace virtual"><i class="fa-solid fa-video-slash"></i></span>
                                                    <?php endif; ?>

                                                    <button class="btn-delete-event" onclick="confirmarEliminacion(event, <?php echo $evento['id']; ?>, '<?php echo $mes_actual_num; ?>', '<?php echo $anio_actual; ?>')" title="Eliminar Actividad">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </div>

                                                <button class="btn-toggle-info">
                                                    <i class="fa-solid fa-chevron-down" id="icon-<?php echo $evento['id']; ?>" style="transition: transform 0.2s;"></i>
                                                </button>
                                            </div>

                                            <div class="event-details" id="details-<?php echo $evento['id']; ?>" onclick="event.stopPropagation();">
                                                <div class="detail-item">
                                                    <i class="fa-solid fa-tag" style="color: #94a3b8; width: 16px;"></i> 
                                                    <strong>Categoría:</strong> <?php echo htmlspecialchars($evento['categoria']); ?>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <i class="fa-solid fa-location-dot" style="color: #94a3b8; width: 16px;"></i> 
                                                    <strong>Modalidad:</strong> <?php echo htmlspecialchars($evento['modalidad'] ?? 'Virtual'); ?> 
                                                    <?php echo !empty($evento['lugar_exacto']) ? ' - <span style="color:#64748b; font-style:italic;">' . htmlspecialchars($evento['lugar_exacto']) . '</span>' : ''; ?>
                                                </div>

                                                <?php if(!empty($evento['descripcion'])): ?>
                                                <div class="detail-item" style="align-items: flex-start;">
                                                    <i class="fa-solid fa-align-left" style="color: #94a3b8; width: 16px; margin-top:3px;"></i> 
                                                    <span style="flex:1; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?></span>
                                                </div>
                                                <?php endif; ?>

                                                <div class="detail-item">
                                                    <i class="fa-solid fa-video" style="color: #94a3b8; width: 16px;"></i> <strong>Enlace:</strong>
                                                    <?php if (!empty($evento['enlace_reunion'])): ?>
                                                        <div class="meet-link-box">
                                                            <a href="<?php echo htmlspecialchars($evento['enlace_reunion']); ?>" target="_blank">
                                                                Unirse a la videollamada
                                                            </a>
                                                            <button type="button" class="btn-copy" onclick="copyLink('<?php echo htmlspecialchars($evento['enlace_reunion']); ?>', this)">
                                                                <i class="fa-regular fa-copy"></i> Copiar
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="font-style: italic; color: #94a3b8; font-weight: 500;">No hay enlace virtual para esta actividad.</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if($evento['estado'] === 'programada' || $evento['estado'] === 'reprogramada'): ?>
                                                    <div class="action-buttons-group">
                                                        <a href="?action=cambiar_estado&estado=ejecutada&id=<?php echo $evento['id']; ?>&mes=<?php echo $mes_actual_num; ?>&anio=<?php echo $anio_actual; ?>" class="btn-state btn-ejecutado">
                                                            <i class="fa-solid fa-check"></i> Ejecutado
                                                        </a>
                                                        <a href="nueva_actividad.php?edit_id=<?php echo $evento['id']; ?>" class="btn-state btn-reprogramado">
                                                            <i class="fa-solid fa-pen-to-square"></i> Reprogramar o Editar
                                                        </a>
                                                        <a href="?action=cambiar_estado&estado=no_ejecutada&id=<?php echo $evento['id']; ?>&mes=<?php echo $mes_actual_num; ?>&anio=<?php echo $anio_actual; ?>" class="btn-state btn-no-ejecutado">
                                                            <i class="fa-solid fa-xmark"></i> No Ejecutada
                                                        </a>
                                                    </div>
                                                
                                                <?php elseif($evento['estado'] === 'ejecutada' || $evento['estado'] === 'completada'): ?>
                                                    <div class="executed-actions">
                                                        <a href="#" class="btn-action btn-informe" onclick="alert('Generador de Informes en construcción.'); return false;">
                                                            <i class="fa-regular fa-file-pdf"></i> Generar Informe
                                                        </a>
                                                        <button type="button" class="btn-action btn-asistencia" onclick="copyLink('http://<?php echo $_SERVER['HTTP_HOST']; ?>/asistencia.php?id=<?php echo $evento['id']; ?>', this)">
                                                            <i class="fa-solid fa-link"></i> Copiar Link de Asistencia
                                                        </button>
                                                    </div>

                                                <?php elseif($evento['estado'] === 'no_ejecutada' || $evento['estado'] === 'cancelada'): ?>
                                                    <div class="not-executed-msg">
                                                        <i class="fa-solid fa-circle-info"></i> Esta actividad no fue ejecutada.
                                                    </div>
                                                <?php endif; ?>

                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if ($dia_data['es_hoy']): ?>
                                    <div class="today-empty-watermark marca-agua-vacio" style="<?php echo empty($dia_data['eventos']) ? 'display:flex;' : 'display:none;'; ?>">
                                        <i class="fa-solid fa-calendar-check watermark-bg-animated"></i>
                                        <span class="today-empty-text">Día libre de actividades programadas</span>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                    <?php endforeach; ?>

                    <div id="mensaje-sin-resultados" style="display: none; text-align: center; padding: 40px 20px;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <h3 style="color: #475569; margin: 0 0 8px 0;">No se encontraron resultados</h3>
                        <p style="color: #94a3b8; font-size: 0.9rem;">Prueba limpiando los filtros o cambiando la palabra clave.</p>
                    </div>

                    <div class="agenda-end-message" id="mensaje-fin-agenda">
                        <div class="end-message-icon">
                            <i class="fa-solid fa-flag-checkered"></i>
                        </div>
                        <div class="end-message-content">
                            <h4>Fin del registro de <?php echo $nombre_mes_actual; ?></h4>
                            <p>Los días pasados sin actividades se ocultan automáticamente para mantener tu historial limpio.</p>
                        </div>
                    </div>

                </div>

            </div>
            <?php endif; ?>

        </div>
    </main>

    <?php if ($usuario_rol === 'sst'): ?>
        <a href="reportes.php" class="btn-floating-informes" id="btn-informes-floating">
            <i class="fa-solid fa-chart-pie"></i> Informes
        </a>

        <button id="btn-back-today" class="btn-floating-today">
            <i class="fa-solid fa-calendar-day"></i> Hoy
        </button>
    <?php endif; ?>

    <script>
        // === LÓGICA DE ELIMINAR ACTIVIDAD CON MODAL PREMIUM ===
        function confirmarEliminacion(e, id, mes, anio) {
            e.stopPropagation(); // Evita que se abra el acordeón
            const urlBorrar = `estandar3.php?action=eliminar_actividad&id=${id}&mes=${mes}&anio=${anio}`;
            
            // Validamos si la función global del modal premium existe
            if (typeof showConfirmModal === 'function') {
                showConfirmModal(
                    'Eliminar Actividad', 
                    '¿Estás seguro de que deseas eliminar esta actividad definitivamente? Esta acción borrará todo el registro y no se puede deshacer.', 
                    urlBorrar, 
                    'danger', 
                    'Sí, eliminar'
                );
            } else {
                // Respaldo por si el JS del modal no carga
                if(confirm('¿Estás seguro de que deseas eliminar esta actividad definitivamente?')) {
                    window.location.href = urlBorrar;
                }
            }
        }

        // === LÓGICA DE MOSTRAR MÁS EN TARJETAS ===
        function toggleCardInfo(btn) {
            const extraInfo = btn.nextElementSibling;
            if (extraInfo.style.display === 'block') {
                extraInfo.style.display = 'none';
                btn.classList.remove('active');
            } else {
                extraInfo.style.display = 'block';
                btn.classList.add('active');
            }
        }

        // === LÓGICA DE FILTRADO (FRONTEND) ===
        function aplicarFiltrosJS() {
            if (!document.getElementById('filtroTexto')) return;
            const texto = document.getElementById('filtroTexto').value.toLowerCase().trim();
            const estadoFiltro = document.getElementById('filtroEstado').value;
            const categoriaFiltro = document.getElementById('filtroCategoria').value;
            const modalidadFiltro = document.getElementById('filtroModalidad').value;
            
            const filtrosActivos = (texto !== '' || estadoFiltro !== '' || categoriaFiltro !== '' || modalidadFiltro !== '');

            // Mostrar/Ocultar el botón fantasma de "Limpiar Filtros"
            const btnLimpiar = document.getElementById('btnLimpiarFiltros');
            if (!btnLimpiar) return;
            if (filtrosActivos) {
                btnLimpiar.style.display = 'inline-flex';
            } else {
                btnLimpiar.style.display = 'none';
            }

            let diasVisibles = 0;

            document.querySelectorAll('.dia-agenda').forEach(bloqueDia => {
                let eventosMostrados = 0;

                bloqueDia.querySelectorAll('.evento-item').forEach(evento => {
                    const t = evento.getAttribute('data-texto');
                    const e = evento.getAttribute('data-estado');
                    const c = evento.getAttribute('data-categoria');
                    const m = evento.getAttribute('data-modalidad');

                    let matchEstado = (estadoFiltro === '');
                    if (!matchEstado) {
                        if (estadoFiltro === 'ejecutada' && (e === 'ejecutada' || e === 'completada')) matchEstado = true;
                        else if (estadoFiltro === 'no_ejecutada' && (e === 'no_ejecutada' || e === 'cancelada')) matchEstado = true;
                        else if (e === estadoFiltro) matchEstado = true;
                    }

                    const matchTexto = t.includes(texto);
                    const matchCat = categoriaFiltro === '' || c === categoriaFiltro;
                    const matchMod = modalidadFiltro === '' || m === modalidadFiltro;

                    if (matchTexto && matchEstado && matchCat && matchMod) {
                        evento.style.display = 'flex';
                        eventosMostrados++;
                    } else {
                        evento.style.display = 'none';
                    }
                });

                const marcaAgua = bloqueDia.querySelector('.marca-agua-vacio');
                const esHoy = bloqueDia.id === 'agenda-dia-hoy';
                const esFuturoOHoy = bloqueDia.classList.contains('dia-futuro') || esHoy;

                if (eventosMostrados > 0) {
                    bloqueDia.style.display = 'flex';
                    if(marcaAgua) marcaAgua.style.display = 'none';
                    diasVisibles++;
                } else {
                    if (!filtrosActivos) {
                        // Comportamiento normal: Mostrar futuros/hoy limpios. Pasados se ocultan.
                        if (esFuturoOHoy) {
                            bloqueDia.style.display = 'flex';
                            if(esHoy && marcaAgua) marcaAgua.style.display = 'flex';
                            diasVisibles++;
                        } else {
                            bloqueDia.style.display = 'none'; 
                        }
                    } else {
                        // Si hay filtros activos y no hay eventos, ocultar todo
                        bloqueDia.style.display = 'none';
                    }
                }
            });

            const msgSinResultados = document.getElementById('mensaje-sin-resultados');
            const msgFinAgenda = document.getElementById('mensaje-fin-agenda');
            if (!msgSinResultados || !msgFinAgenda) return;
            
            if (diasVisibles === 0) {
                msgSinResultados.style.display = 'block';
                msgFinAgenda.style.display = 'none';
            } else {
                msgSinResultados.style.display = 'none';
                msgFinAgenda.style.display = 'flex';
            }
        }

        function limpiarFiltros() {
            if (!document.getElementById('filtroTexto')) return;
            document.getElementById('filtroTexto').value = '';
            document.getElementById('filtroEstado').value = '';
            document.getElementById('filtroCategoria').value = '';
            document.getElementById('filtroModalidad').value = '';
            aplicarFiltrosJS();
        }

        // === EVENT LISTENERS ===
        const filtroTextoInput = document.getElementById('filtroTexto');
        if (filtroTextoInput) {
            filtroTextoInput.addEventListener('input', aplicarFiltrosJS);
        }

        function cambiarMesAnio() {
            if (!document.getElementById('filtroMes') || !document.getElementById('filtroAnio')) return;
            const mes = document.getElementById('filtroMes').value;
            const anio = document.getElementById('filtroAnio').value;
            window.location.href = `estandar3.php?mes=${mes}&anio=${anio}`;
        }

        function toggleFiltrosPanel() {
            const panel = document.getElementById('panelFiltros');
            const btn = document.getElementById('btnFiltrosToggle');
            if (!panel || !btn) return;
            
            if (panel.style.display === 'none' || panel.style.display === '') {
                panel.style.display = 'grid';
                btn.classList.add('active');
            } else {
                panel.style.display = 'none';
                btn.classList.remove('active');
                // Si oculta el panel, limpiar los filtros avanzados
                document.getElementById('filtroEstado').value = '';
                document.getElementById('filtroCategoria').value = '';
                document.getElementById('filtroModalidad').value = '';
                aplicarFiltrosJS();
            }
        }

        // === BOTONES FLOTANTES Y ACORDEÓN ===
        const btnToday = document.getElementById('btn-back-today');
        const btnInformes = document.getElementById('btn-informes-floating');

        if (btnToday) {
            window.addEventListener('scroll', () => {
            if (window.scrollY > 200) {
                btnToday.classList.add('visible');
                if(btnInformes) btnInformes.classList.add('shifted');
            } else {
                btnToday.classList.remove('visible');
                if(btnInformes) btnInformes.classList.remove('shifted');
            }
            });

            btnToday.addEventListener('click', () => {
            const f = new Date();
            const mesHoy = (f.getMonth() + 1).toString().padStart(2, '0');
            const anioHoy = f.getFullYear().toString();
            
            const filtroMes = document.getElementById('filtroMes');
            const filtroAnio = document.getElementById('filtroAnio');
            const mesSelect = filtroMes ? filtroMes.value : mesHoy;
            const anioSelect = filtroAnio ? filtroAnio.value : anioHoy;

            if (mesHoy !== mesSelect || anioHoy !== anioSelect) {
                window.location.href = `estandar3.php`; 
            } else {
                const hoyBloque = document.getElementById('agenda-dia-hoy');
                if (hoyBloque) {
                    hoyBloque.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' }); 
                }
            }
            });
        }

        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('icon-' + id);
            if (el.style.display === 'block') {
                el.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            } else {
                document.querySelectorAll('.event-details').forEach(detail => {
                    if(detail.id !== 'details-' + id) {
                        detail.style.display = 'none';
                        const otherIcon = document.getElementById('icon-' + detail.id.replace('details-', ''));
                        if(otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                    }
                });
                el.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            }
        }

        function copyLink(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado';
                btn.style.color = '#16a34a'; 
                btn.style.borderColor = '#16a34a'; 
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.color = '';
                    btn.style.borderColor = '';
                }, 2000);
            });
        }

        const actividadEnfocada = <?php echo (int)$actividad_enfocada; ?>;
        if (actividadEnfocada > 0) {
            window.addEventListener('load', () => {
                const actividad = document.getElementById('actividad-' + actividadEnfocada);
                const detalle = document.getElementById('details-' + actividadEnfocada);
                const icono = document.getElementById('icon-' + actividadEnfocada);
                if (!actividad || !detalle) return;
                detalle.style.display = 'block';
                if (icono) icono.style.transform = 'rotate(180deg)';
                actividad.style.borderColor = '#fb923c';
                actividad.style.background = '#fff7ed';
                setTimeout(() => actividad.scrollIntoView({ behavior:'smooth', block:'center' }), 120);
            });
        }
    </script>
</body>
</html>
