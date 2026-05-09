<?php
require_once 'config/db.php';
require_once 'config/auth.php';

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
// ESTADÍSTICAS PARA LAS TARJETAS RESUMEN
// ==========================================
$total_actividades = 0;
$actividades_programadas = 0;
$actividades_completadas = 0;

if ($empresa_id) {
    try {
        $stmt_stat1 = $conn->prepare("SELECT COUNT(*) FROM actividades_capacitacion WHERE empresa_id = ?");
        $stmt_stat1->execute([$empresa_id]);
        $total_actividades = $stmt_stat1->fetchColumn();

        $stmt_stat2 = $conn->prepare("SELECT COUNT(*) FROM actividades_capacitacion WHERE empresa_id = ? AND estado = 'programada'");
        $stmt_stat2->execute([$empresa_id]);
        $actividades_programadas = $stmt_stat2->fetchColumn();

        $stmt_stat3 = $conn->prepare("SELECT COUNT(*) FROM actividades_capacitacion WHERE empresa_id = ? AND estado = 'completada'");
        $stmt_stat3->execute([$empresa_id]);
        $actividades_completadas = $stmt_stat3->fetchColumn();
    } catch (PDOException $e) { }
}

// ==========================================
// OBTENER DATOS PARA LA AGENDA (TODOS LOS DÍAS DEL MES)
// ==========================================
$agenda = [];

// Variables del mes actual
$mes_actual_num = date('m');
$anio_actual = date('Y');
$dias_en_mes = date('t'); // Cuántos días tiene el mes actual
$hoy = date('Y-m-d');

$meses_cortos = ['01'=>'ENE', '02'=>'FEB', '03'=>'MAR', '04'=>'ABR', '05'=>'MAY', '06'=>'JUN', '07'=>'JUL', '08'=>'AGO', '09'=>'SEP', '10'=>'OCT', '11'=>'NOV', '12'=>'DIC'];
$dias_semana = ['0'=>'DOM', '1'=>'LUN', '2'=>'MAR', '3'=>'MIÉ', '4'=>'JUE', '5'=>'VIE', '6'=>'SÁB'];
$meses_completos = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio', '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
$nombre_mes_actual = $meses_completos[$mes_actual_num] . ' de ' . $anio_actual;

// 1. PRE-LLENAMOS LA AGENDA CON TODOS LOS DÍAS DEL MES
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

// 2. BUSCAMOS LOS EVENTOS EN LA BASE DE DATOS
if ($empresa_id) {
    try {
        $stmt_act = $conn->prepare("SELECT id, nombre_actividad, tipo_capacitacion, categoria, fecha_inicio, fecha_fin, enlace_reunion, estado 
                                    FROM actividades_capacitacion 
                                    WHERE empresa_id = ? 
                                    AND fecha_inicio IS NOT NULL 
                                    AND MONTH(fecha_inicio) = ? 
                                    AND YEAR(fecha_inicio) = ?
                                    ORDER BY DATE(fecha_inicio) ASC, fecha_inicio ASC");
        $stmt_act->execute([$empresa_id, $mes_actual_num, $anio_actual]);
        $actividades_raw = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

        $google_colors = ['#039be5', '#33b679', '#8e24aa', '#e67c73', '#f6bf26', '#f4511e', '#3f51b5', '#0b8043'];

        // 3. ASIGNAMOS LOS EVENTOS A SU DÍA CORRESPONDIENTE
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO PREMIUM */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; display: block; }
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }

        /* TARJETAS DE RESUMEN */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .summary-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .summary-bg-icon { position: absolute; right: -15px; bottom: -15px; width: 110px; height: 110px; color: var(--primary); opacity: 0.04; transform: rotate(-15deg); transition: all 0.4s ease; pointer-events: none; }
        .summary-card:hover .summary-bg-icon { transform: rotate(0deg) scale(1.1); opacity: 0.08; }
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .summary-icon-box { width: 44px; height: 44px; border-radius: 12px; background: rgba(255, 138, 31, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; }
        .summary-icon-box svg { width: 22px; height: 22px; }
        .summary-value { font-size: 2rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; letter-spacing: -0.03em; }
        .summary-title { font-size: 0.95rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 4px 0; }
        .summary-desc { font-size: 0.8rem; color: var(--muted); margin: 0; }

        /* BARRA DE HERRAMIENTAS */
        .workspace-toolbar { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
        .search-box { position: relative; flex: 1; max-width: 350px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.85rem; color: var(--text); background: #f8fafc; transition: all 0.3s ease; box-sizing: border-box; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; justify-content: center; align-items: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.15); white-space: nowrap; text-decoration: none; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.25); color: white;}

        /* =========================================
           AGENDA MINIMALISTA (ESTILO GOOGLE)
           ========================================= */
        .agenda-container { width: 100%; background: transparent; border: none; box-shadow: none; padding: 0; margin-top: 20px; }
        
        .agenda-day-block { display: flex; padding: 16px 0; border-bottom: 1px solid rgba(0,0,0,0.06); scroll-margin-top: 100px; }
        
        .agenda-date-col { width: 80px; display: flex; flex-direction: column; align-items: flex-start; flex-shrink: 0; padding-top: 6px; }
        
        .date-number { font-size: 1.6rem; font-weight: 400; color: #70757a; line-height: 1; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; }
        .date-number.is-today { background: #1a73e8; color: #fff; border-radius: 50%; font-weight: 500; }
        
        .date-text { font-size: 0.7rem; font-weight: 600; color: #70757a; text-transform: uppercase; letter-spacing: 0.05em; padding-left: 2px; }
        .date-text.is-today { color: #1a73e8; }

        .agenda-events-col { flex: 1; display: flex; flex-direction: column; gap: 4px; position: relative; min-height: 40px;}
        
        .agenda-event-row { display: flex; flex-direction: column; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
        .agenda-event-row:hover { background: rgba(0,0,0,0.04); }

        .event-main-info { display: flex; align-items: center; width: 100%; }
        .event-dot { width: 10px; height: 10px; border-radius: 50%; margin-right: 18px; flex-shrink: 0; }
        .event-time { font-size: 0.85rem; color: #3c4043; width: 100px; flex-shrink: 0; font-weight: 400; }
        .event-title { font-size: 0.9rem; font-weight: 600; color: #3c4043; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px; }
        .event-category-badge { font-weight: 500; color: #70757a; font-size: 0.8rem; }

        /* Contenedor de Indicadores */
        .event-indicators { display: flex; align-items: center; gap: 12px; margin-left: auto; padding-right: 10px; }
        .badge-status { font-size: 0.65rem; padding: 3px 8px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge-status.programada { background: #e0f2fe; color: #2563eb; }
        .badge-status.completada { background: #dcfce7; color: #16a34a; }
        .meet-indicator { color: #1a73e8; font-size: 0.9rem; }
        .no-meet-indicator { color: #cbd5e1; font-size: 0.9rem; }

        /* Icono de acordeón */
        .btn-toggle-info { background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px 8px; font-size: 0.8rem; transition: all 0.2s; flex-shrink: 0; }
        .btn-toggle-info:hover { color: #1e293b; }

        /* Detalles Ocultos (Acordeón) */
        .event-details { display: none; margin-top: 10px; padding-left: 28px; padding-bottom: 8px; animation: fadeIn 0.2s ease; }
        .detail-item { font-size: 0.85rem; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        
        .meet-link-box { display: flex; align-items: center; gap: 8px; background: #ffffff; padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: fit-content; }
        .meet-link-box a { color: #1a73e8; text-decoration: none; font-weight: 600; font-size: 0.85rem;}
        .meet-link-box a:hover { text-decoration: underline; }
        
        .btn-copy { background: #f1f5f9; border: none; color: #475569; border-radius: 6px; padding: 4px 10px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 600; }
        .btn-copy:hover { background: #e2e8f0; color: #0f172a; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* Mensaje final de agenda Premium */
        .agenda-end-message {
            margin-top: 30px;
            padding: 20px 24px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }
        
        .agenda-end-message:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .end-message-icon {
            width: 48px;
            height: 48px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #64748b;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            flex-shrink: 0;
        }

        .end-message-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .end-message-content h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #334155;
        }

        .end-message-content p {
            margin: 0;
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.4;
        }

        /* Marca de agua animada para HOY sin eventos */
        .today-empty-watermark {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 100%;
            min-height: 44px;
            padding-left: 12px;
            overflow: hidden;
            pointer-events: none;
        }
        .watermark-bg-animated {
            font-size: 1.8rem;
            color: #1a73e8;
            opacity: 0.15;
            animation: pulseIcon 3s infinite ease-in-out;
        }
        .today-empty-text {
            font-size: 0.85rem;
            color: #94a3b8;
            font-style: italic;
            font-weight: 500;
            opacity: 0.8;
        }
        @keyframes pulseIcon {
            0% { transform: scale(1) rotate(-5deg); opacity: 0.1; }
            50% { transform: scale(1.15) rotate(5deg); opacity: 0.2; }
            100% { transform: scale(1) rotate(-5deg); opacity: 0.1; }
        }

        /* NUEVO: Botón Flotante "Volver a Hoy" */
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
        .btn-floating-today.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .btn-floating-today:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            color: #174ea6;
            border-color: #cbd5e1;
        }

        /* RESPONSIVE AGENDA */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .estandar-header-group { display: flex; flex-direction: row; align-items: flex-start; text-align: left; gap: 12px; width: 100%; }
            .icon-box-std { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; margin-top: 2px; }
            .icon-box-std svg { width: 20px; height: 20px; }
            .workspace-toolbar { flex-direction: column; align-items: stretch; padding: 16px; }
            .search-box { max-width: 100%; }
            .btn-primary { width: 100%; justify-content: center; }
            
            .agenda-day-block { flex-direction: column; padding: 16px 0; }
            .agenda-date-col { width: 100%; flex-direction: row; align-items: center; padding-top: 0; margin-bottom: 12px; }
            .date-number { margin-bottom: 0; margin-right: 12px; }
            .date-text { padding-left: 0; }
            .event-time { width: 80px; }
            .event-indicators { flex-direction: row; margin-left: 0; margin-top: 4px; width: 100%; justify-content: flex-start; padding-left: 110px; }
            .event-main-info { flex-wrap: wrap; }
            .btn-toggle-info { position: absolute; right: 10px; top: 12px; }
            .today-empty-watermark { padding-left: 0; }
            
            .agenda-end-message { flex-direction: column; text-align: center; padding: 24px 16px; gap: 12px; }
            
            /* Botón flotante móvil */
            .btn-floating-today { bottom: 20px; right: 20px; padding: 10px 16px; font-size: 0.85rem; }
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

            <div class="summary-cards-grid">
                
                <div class="summary-card">
                    <svg class="summary-bg-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253"></path></svg>
                            </div>
                            <h2 class="summary-value" style="color: var(--primary);"><?php echo $total_actividades; ?></h2>
                        </div>
                        <h3 class="summary-title">Total Actividades</h3>
                        <p class="summary-desc">Capacitaciones registradas.</p>
                    </div>
                </div>

                <div class="summary-card">
                    <svg class="summary-bg-icon" style="color: #0284c7;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box" style="background: rgba(14, 165, 233, 0.1); color: #0284c7;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h2 class="summary-value" style="color: #0284c7;"><?php echo $actividades_programadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Programadas</h3>
                        <p class="summary-desc">Actividades por realizar.</p>
                    </div>
                </div>

                <div class="summary-card">
                    <svg class="summary-bg-icon" style="color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box" style="background: rgba(34, 197, 94, 0.1); color: #16a34a;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h2 class="summary-value" style="color: #16a34a;"><?php echo $actividades_completadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Completadas</h3>
                        <p class="summary-desc">Capacitaciones finalizadas.</p>
                    </div>
                </div>

            </div>

            <div id="workspace-estandar3">
                <div class="workspace-toolbar">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" placeholder="Buscar actividad o tema...">
                    </div>
                    
                    <?php if ($usuario_rol === 'sst'): ?>
                        <a href="nueva_actividad.php" class="btn-primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nueva Actividad
                        </a>
                    <?php endif; ?>
                </div>

                <div class="agenda-container">
                    <?php foreach ($agenda as $fecha => $dia_data): ?>
                        
                        <?php 
                        // Ocultar días del PASADO si no tienen eventos.
                        // Mostrar TODOS los días a partir de hoy (tengan o no eventos).
                        if ($fecha < $hoy && empty($dia_data['eventos'])) {
                            continue;
                        }
                        ?>
                        
                        <div class="agenda-day-block" <?php echo $dia_data['es_hoy'] ? 'id="agenda-dia-hoy"' : ''; ?>>
                            
                            <div class="agenda-date-col">
                                <div class="date-number <?php echo $dia_data['es_hoy'] ? 'is-today' : ''; ?>">
                                    <?php echo $dia_data['dia_num']; ?>
                                </div>
                                <div class="date-text <?php echo $dia_data['es_hoy'] ? 'is-today' : ''; ?>">
                                    <?php echo $dia_data['mes_dia']; ?>
                                </div>
                            </div>
                            
                            <div class="agenda-events-col">
                                <?php if (!empty($dia_data['eventos'])): ?>
                                    <?php foreach ($dia_data['eventos'] as $evento): ?>
                                        <div class="agenda-event-row" onclick="toggleDetails(<?php echo $evento['id']; ?>)">
                                            
                                            <div class="event-main-info">
                                                <div class="event-dot" style="background-color: <?php echo $evento['color']; ?>;"></div>
                                                <div class="event-time"><?php echo $evento['time_str']; ?></div>
                                                <div class="event-title">
                                                    <?php echo htmlspecialchars($evento['nombre_actividad']); ?>
                                                    <span class="event-category-badge">- <?php echo htmlspecialchars($evento['categoria']); ?></span>
                                                </div>

                                                <div class="event-indicators">
                                                    <?php if($evento['estado'] === 'programada'): ?>
                                                        <span class="badge-status programada">Programada</span>
                                                    <?php elseif($evento['estado'] === 'completada'): ?>
                                                        <span class="badge-status completada">Completada</span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($evento['enlace_reunion'])): ?>
                                                        <span class="meet-indicator" title="Tiene enlace de Meet"><i class="fa-solid fa-video"></i></span>
                                                    <?php else: ?>
                                                        <span class="no-meet-indicator" title="Sin enlace virtual"><i class="fa-solid fa-video-slash"></i></span>
                                                    <?php endif; ?>
                                                </div>

                                                <button class="btn-toggle-info">
                                                    <i class="fa-solid fa-chevron-down" id="icon-<?php echo $evento['id']; ?>" style="transition: transform 0.2s;"></i>
                                                </button>
                                            </div>

                                            <div class="event-details" id="details-<?php echo $evento['id']; ?>" onclick="event.stopPropagation();">
                                                <div class="detail-item">
                                                    <i class="fa-solid fa-tag" style="color: #94a3b8; width: 16px;"></i> Categoría: <?php echo htmlspecialchars($evento['categoria']); ?>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fa-solid fa-video" style="color: #94a3b8; width: 16px;"></i> Enlace:
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
                                                        <span style="font-style: italic; color: #94a3b8; font-weight: 500;">No hay enlace para esta actividad.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($dia_data['es_hoy']): ?>
                                    <div class="today-empty-watermark">
                                        <i class="fa-solid fa-calendar-check watermark-bg-animated"></i>
                                        <span class="today-empty-text">Día libre de actividades programadas</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>

                    <div class="agenda-end-message">
                        <div class="end-message-icon">
                            <i class="fa-solid fa-flag-checkered"></i>
                        </div>
                        <div class="end-message-content">
                            <h4>Fin del registro de <?php echo $nombre_mes_actual; ?></h4>
                            <p>Los días pasados sin actividades se ocultan automáticamente para mantener tu historial limpio y optimizado.</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </main>

    <button id="btn-back-today" class="btn-floating-today">
        <i class="fa-solid fa-calendar-day"></i> Hoy
    </button>

    <script>
        // Lógica del botón flotante "Volver a Hoy"
        const btnToday = document.getElementById('btn-back-today');
        
        window.addEventListener('scroll', () => {
            // Mostrar botón si el usuario baja más de 200px
            if (window.scrollY > 200) {
                btnToday.classList.add('visible');
            } else {
                btnToday.classList.remove('visible');
            }
        });

        btnToday.addEventListener('click', () => {
            const hoyBloque = document.getElementById('agenda-dia-hoy');
            if (hoyBloque) {
                hoyBloque.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // Por si acaso, si no encuentra el ID, simplemente sube al top
                window.scrollTo({ top: 0, behavior: 'smooth' }); 
            }
        });

        // Lógica de Autoscroll Suave hacia el día de hoy al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            const hoyBloque = document.getElementById('agenda-dia-hoy');
            if (hoyBloque) {
                setTimeout(() => {
                    hoyBloque.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 400);
            }
        });

        // Lógica de Acordeón
        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('icon-' + id);
            
            if (el.style.display === 'block') {
                el.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            } else {
                el.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            }
        }

        // Lógica para Copiar Enlace
        function copyLink(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado';
                btn.style.color = '#16a34a'; 
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.color = '';
                }, 2000);
            });
        }
    </script>

</body>
</html>