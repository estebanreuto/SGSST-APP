<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

$current_page = 'contabilidad.php';
$success_msg = "";
$error_msg = "";
$error_columnas_mov = false;

// =======================================================
// VALIDAR SI LA TABLA TIENE LAS COLUMNAS CONTABLES NUEVAS
// =======================================================
try {
    $conn->query("SELECT subtotal, iva, metodo_pago, comprobante FROM movimientos_financieros LIMIT 1");
} catch (PDOException $e) {
    $error_columnas_mov = true;
}

// =======================================================
// GUARDAR NUEVO MOVIMIENTO MANUAL (INGRESO O EGRESO)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_movimiento'])) {
    if ($error_columnas_mov) {
        $error_msg = "No se puede guardar. Primero debes actualizar la base de datos (mira el aviso amarillo).";
    } else {
        $tipo = $_POST['tipo'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $subtotal = floatval($_POST['subtotal'] ?? 0);
        $iva_pct = floatval($_POST['iva_pct'] ?? 0); // Ahora puede ser cualquier número
        
        $iva = $subtotal * ($iva_pct / 100);
        $monto_total = $subtotal + $iva;
        
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        $comprobante = $_POST['comprobante'] ?? '';
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $descripcion = $_POST['descripcion'] ?? '';

        if (!empty($tipo) && !empty($categoria) && $subtotal > 0) {
            try {
                $stmt = $conn->prepare("INSERT INTO movimientos_financieros (tipo, categoria, subtotal, iva, monto, metodo_pago, comprobante, fecha, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tipo, $categoria, $subtotal, $iva, $monto_total, $metodo_pago, $comprobante, $fecha, $descripcion]);
                $success_msg = "Movimiento contable registrado exitosamente.";
            } catch (PDOException $e) {
                $error_msg = "Error al registrar el movimiento: " . $e->getMessage();
            }
        } else {
            $error_msg = "Por favor completa los campos obligatorios (Tipo, Categoría y Subtotal).";
        }
    }
}

// =======================================================
// LÓGICA CONTABLE Y FINANCIERA (SAAS)
// =======================================================
$ingresos_mes = 0;
$egresos_mes = 0;
$ingresos_totales = 0;
$egresos_totales = 0;

$chart_labels = [];
$chart_ingresos = [];
$chart_egresos = [];

try {
    // 1. Ingresos Mes Actual (Wompi + Manuales)
    $stmt_ingreso_wompi_mes = $conn->query("SELECT SUM(monto) FROM pagos_suscripciones WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pago) = YEAR(CURRENT_DATE()) AND estado = 'APPROVED'");
    $ingreso_wompi_mes = $stmt_ingreso_wompi_mes->fetchColumn() ?: 0;
    
    $stmt_ingreso_manual_mes = $conn->query("SELECT SUM(monto) FROM movimientos_financieros WHERE tipo = 'ingreso' AND MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
    $ingreso_manual_mes = $stmt_ingreso_manual_mes->fetchColumn() ?: 0;
    
    $ingresos_mes = $ingreso_wompi_mes + $ingreso_manual_mes;

    // 2. Egresos Mes Actual
    $stmt_egreso_mes = $conn->query("SELECT SUM(monto) FROM movimientos_financieros WHERE tipo = 'egreso' AND MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
    $egresos_mes = $stmt_egreso_mes->fetchColumn() ?: 0;

    // 3. Totales Históricos
    $ingreso_wompi_total = $conn->query("SELECT SUM(monto) FROM pagos_suscripciones WHERE estado = 'APPROVED'")->fetchColumn() ?: 0;
    $ingreso_manual_total = $conn->query("SELECT SUM(monto) FROM movimientos_financieros WHERE tipo = 'ingreso'")->fetchColumn() ?: 0;
    $ingresos_totales = $ingreso_wompi_total + $ingreso_manual_total;

    $egresos_totales = $conn->query("SELECT SUM(monto) FROM movimientos_financieros WHERE tipo = 'egreso'")->fetchColumn() ?: 0;

    // 4. Datos para la Gráfica (Últimos 6 meses)
    $meses_espanol = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
    
    for ($i = 5; $i >= 0; $i--) {
        $mes_str = date('Y-m', strtotime("-$i month"));
        $mes_num = date('m', strtotime("-$i month"));
        
        $chart_labels[] = $meses_espanol[$mes_num];

        // Ingresos Wompi del mes
        $stmt_w = $conn->prepare("SELECT SUM(monto) FROM pagos_suscripciones WHERE DATE_FORMAT(fecha_pago, '%Y-%m') = ? AND estado = 'APPROVED'");
        $stmt_w->execute([$mes_str]);
        $wompi_m = $stmt_w->fetchColumn() ?: 0;

        // Ingresos manuales del mes
        $stmt_im = $conn->prepare("SELECT SUM(monto) FROM movimientos_financieros WHERE DATE_FORMAT(fecha, '%Y-%m') = ? AND tipo = 'ingreso'");
        $stmt_im->execute([$mes_str]);
        $manual_m = $stmt_im->fetchColumn() ?: 0;

        $chart_ingresos[] = $wompi_m + $manual_m;

        // Egresos manuales del mes
        $stmt_eg = $conn->prepare("SELECT SUM(monto) FROM movimientos_financieros WHERE DATE_FORMAT(fecha, '%Y-%m') = ? AND tipo = 'egreso'");
        $stmt_eg->execute([$mes_str]);
        $chart_egresos[] = $stmt_eg->fetchColumn() ?: 0;
    }

    // 5. Traer las transacciones de Wompi
    $stmt_pagos = $conn->query("SELECT p.*, se.nombre, se.apellido, se.cedula FROM pagos_suscripciones p LEFT JOIN solicitudes_empresas se ON p.empresa_id = se.id ORDER BY p.fecha_pago DESC LIMIT 30");
    $pagos_wompi = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

    // 6. Traer los movimientos manuales
    $stmt_mov = $conn->query("SELECT * FROM movimientos_financieros ORDER BY fecha DESC, id DESC LIMIT 30");
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_bd = true;
    $pagos_wompi = [];
    $movimientos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad Integral | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { 
            --primary: #ff8a1f; --primary2: #ff7a00; 
            --bg-body: #f8fafc; --card: #ffffff; 
            --text-main: #0f172a; --text-muted: #64748b; 
            --border: #e2e8f0; --blue-main: #2b5a9e; 
            --radius-lg: 16px; --radius-md: 12px;
            --green: #10b981; --red: #ef4444;
        }
        
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); margin: 0; padding: 0; min-height: 100vh; color: var(--text-main); display: flex; font-size: 0.85rem; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* HEADER & BOTONES */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .header-group { display: flex; align-items: center; gap: 16px; }
        .icon-box-std { width: 48px; height: 48px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05)); color: var(--green); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid rgba(16, 185, 129, 0.2);}
        .header-text h1 { margin: 0; font-size: 1.5rem; color: var(--text-main); font-weight: 800; letter-spacing: -0.02em;}
        .header-text p { margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.9rem; }

        .btn-group { display: flex; gap: 10px; flex-wrap: wrap;}
        .btn-export, .btn-new { padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: none;}
        .btn-export { background: #ffffff; border: 1px solid var(--border); color: var(--text-main); }
        .btn-export:hover { border-color: var(--blue-main); color: var(--blue-main); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
        .btn-new { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; box-shadow: 0 4px 12px rgba(255, 138, 31, 0.25);}
        .btn-new:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.35);}

        /* ALERTAS */
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: fadeDown 0.3s ease;}
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0;}
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}
        .alert-dev { background: #fffbeb; border: 1px solid #fde68a; padding: 16px; border-radius: 12px; color: #d97706; margin-bottom: 24px; display: flex; gap: 12px; align-items: center; }
        @keyframes fadeDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        /* GRID DE MÉTRICAS (KPIs) */
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .kpi-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; display: flex; flex-direction: column; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; transition: transform 0.2s;}
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.04); }
        
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .kpi-title { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;}
        .kpi-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        
        .icon-green { background: rgba(16, 185, 129, 0.1); color: var(--green); }
        .icon-red { background: rgba(239, 68, 68, 0.1); color: var(--red); }
        .icon-blue { background: rgba(43, 90, 158, 0.1); color: var(--blue-main); }
        .icon-orange { background: rgba(255, 138, 31, 0.1); color: var(--primary); }

        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin: 0 0 8px 0; line-height: 1;}
        .kpi-desc { font-size: 0.8rem; color: var(--text-muted); font-weight: 500;}

        /* SECCIÓN DEL GRÁFICO */
        .chart-section { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .section-title { font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0 0 20px 0; display: flex; justify-content: space-between; align-items: center; }
        .chart-container { position: relative; height: 300px; width: 100%; }

        /* TABLAS DE TRANSACCIONES */
        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .table-section { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .table-header { padding: 16px 24px; border-bottom: 1px solid var(--border); background: #ffffff; display: flex; justify-content: space-between; align-items: center;}
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 12px 24px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); background: #f8fafc; }
        td { padding: 14px 24px; font-size: 0.85rem; color: var(--text-main); border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .badge-ingreso { background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #bbf7d0;}
        .badge-egreso { background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #fecaca;}
        .tx-id { font-family: 'Courier New', Courier, monospace; color: var(--text-muted); font-size: 0.75rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;}
        .monto-col { font-weight: 800; font-size: 0.95rem;}
        .monto-pos { color: var(--green); }
        .monto-neg { color: var(--red); }

        /* =========================================
           MODAL NUEVO MOVIMIENTO CONTABLE (Avanzado)
           ========================================= */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: #fff; padding: 32px; border-radius: 20px; width: 100%; max-width: 650px; transform: translateY(20px); transition: transform 0.3s ease; box-shadow: 0 20px 40px rgba(0,0,0,0.15); border: 1px solid var(--border);}
        .modal-overlay.active .modal-box { transform: translateY(0); }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h3 { margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--blue-dark); }
        .btn-close { background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer; transition: 0.2s;}
        .btn-close:hover { color: var(--red); transform: rotate(90deg);}

        /* GRID DEL FORMULARIO Y INPUTS CON ICONOS */
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px; }
        .form-group.full-width { grid-column: 1 / -1; }
        
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; }
        .opcional-text { text-transform: none; font-weight: 400; font-size: 0.7rem; color: #94a3b8; letter-spacing: 0; }
        
        .input-control { position: relative; }
        .input-control i, .input-control svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.95rem; pointer-events: none; transition: color 0.2s; }
        
        .input-control input, .input-control select, .input-control textarea { 
            width: 100%; padding: 12px 14px 12px 42px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.9rem; background: #f8fafc; color: var(--text-main); 
            transition: 0.2s; box-sizing: border-box; 
        }
        
        .input-control input:focus, .input-control select:focus, .input-control textarea:focus { 
            outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); 
        }
        .input-control input:focus ~ i, .input-control select:focus ~ i, .input-control textarea:focus ~ i { color: var(--primary); }

        .input-total { background: #e0f2fe !important; border-color: var(--blue-main) !important; color: var(--blue-dark) !important; font-weight: 800 !important; font-size: 1.1rem !important; cursor: not-allowed;}
        
        @media (max-width: 1200px) { 
            .metrics-grid { grid-template-columns: repeat(2, 1fr); } 
            .tables-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .metrics-grid { grid-template-columns: 1fr; }
            .header-actions { flex-direction: column; align-items: flex-start; }
            .form-grid { grid-template-columns: 1fr; } /* Colapsa a 1 columna en celulares */
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">
            
            <div class="header-actions">
                <div class="header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="header-text">
                        <h1>Contabilidad y Finanzas</h1>
                        <p>Control de ingresos Wompi, gastos de la empresa y balance neto.</p>
                    </div>
                </div>
                <div class="btn-group">
                    <button class="btn-export" onclick="exportTableToCSV('reporte_financiero.csv')">
                        <i class="fa-solid fa-file-csv" style="color: #10b981;"></i> Reporte
                    </button>
                    <button class="btn-new" onclick="document.getElementById('modalMovimiento').classList.add('active')">
                        <i class="fa-solid fa-plus"></i> Registrar Movimiento
                    </button>
                </div>
            </div>

            <?php if ($error_columnas_mov): ?>
                <div class="alert-dev">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">Atención desarrollador: Actualiza tu Base de Datos</strong>
                        Hemos mejorado la contabilidad con IVA y métodos de pago. Ejecuta esto en tu phpMyAdmin (pestaña SQL):<br>
                        <code style="background: rgba(0,0,0,0.05); padding: 6px 10px; border-radius: 6px; color: #b45309; display: inline-block; margin-top: 6px; font-weight: 700; word-break: break-all;">
                            ALTER TABLE movimientos_financieros ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0 AFTER categoria, ADD COLUMN iva DECIMAL(10,2) DEFAULT 0 AFTER subtotal, ADD COLUMN metodo_pago VARCHAR(50) NULL AFTER monto, ADD COLUMN comprobante VARCHAR(100) NULL AFTER metodo_pago;
                        </code>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="metrics-grid">
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <h3 class="kpi-title">Ingresos (Mes Actual)</h3>
                        <div class="kpi-icon icon-green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                    <p class="kpi-value">$<?php echo number_format($ingresos_mes, 0, ',', '.'); ?></p>
                    <p class="kpi-desc">Total histórico: $<?php echo number_format($ingresos_totales, 0, ',', '.'); ?></p>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <h3 class="kpi-title">Gastos (Mes Actual)</h3>
                        <div class="kpi-icon icon-red"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    </div>
                    <p class="kpi-value" style="color: var(--red);">$<?php echo number_format($egresos_mes, 0, ',', '.'); ?></p>
                    <p class="kpi-desc">Total histórico: $<?php echo number_format($egresos_totales, 0, ',', '.'); ?></p>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <h3 class="kpi-title">Utilidad Neta (Mes)</h3>
                        <div class="kpi-icon icon-blue"><i class="fa-solid fa-scale-balanced"></i></div>
                    </div>
                    <?php $balance_mes = $ingresos_mes - $egresos_mes; ?>
                    <p class="kpi-value" style="color: <?php echo $balance_mes >= 0 ? 'var(--blue-main)' : 'var(--red)'; ?>;">
                        $<?php echo number_format($balance_mes, 0, ',', '.'); ?>
                    </p>
                    <p class="kpi-desc">Histórico: $<?php echo number_format($ingresos_totales - $egresos_totales, 0, ',', '.'); ?></p>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <h3 class="kpi-title">Ventas Wompi (Histórico)</h3>
                        <div class="kpi-icon icon-orange"><i class="fa-solid fa-credit-card"></i></div>
                    </div>
                    <p class="kpi-value">$<?php echo number_format($ingreso_wompi_total, 0, ',', '.'); ?></p>
                    <p class="kpi-desc">Membresías procesadas automáticamente</p>
                </div>

            </div>

            <div class="chart-section">
                <div class="section-title">Evolución: Ingresos vs Egresos (Últimos 6 meses)</div>
                <div class="chart-container">
                    <canvas id="balanceChart"></canvas>
                </div>
            </div>

            <div class="tables-grid">
                
                <div class="table-section">
                    <div class="table-header">
                        <h2 class="section-title" style="margin:0; font-size: 1rem;">Caja Menor y Gastos</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="tablaManuales">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Detalle</th>
                                    <th>Pago</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimientos)): ?>
                                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No hay registros manuales aún.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?php echo date('d M Y', strtotime($mov['fecha'])); ?></td>
                                            <td>
                                                <?php if($mov['tipo'] == 'ingreso'): ?>
                                                    <span class="badge-ingreso"><i class="fa-solid fa-arrow-up"></i> Ingreso</span>
                                                <?php else: ?>
                                                    <span class="badge-egreso"><i class="fa-solid fa-arrow-down"></i> Gasto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($mov['categoria']); ?></div>
                                                <?php if(!empty($mov['comprobante'])): ?>
                                                    <div class="tx-id" style="display:inline-block; margin-top:4px;">Ref: <?php echo htmlspecialchars($mov['comprobante']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($mov['metodo_pago'] ?? 'N/D'); ?>
                                            </td>
                                            <td class="monto-col <?php echo $mov['tipo'] == 'ingreso' ? 'monto-pos' : 'monto-neg'; ?>">
                                                <?php echo $mov['tipo'] == 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($mov['monto'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-section">
                    <div class="table-header">
                        <h2 class="section-title" style="margin:0; font-size: 1rem;">Membresías Wompi (Automático)</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="tablaWompi">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Plan / TX ID</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagos_wompi)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding: 20px;">No hay ventas de membresías aún.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pagos_wompi as $pago): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?php echo date('d M Y', strtotime($pago['fecha_pago'])); ?></td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']); ?></div>
                                                <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo htmlspecialchars($pago['cedula']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($pago['plan_nombre']); ?></div>
                                                <div class="tx-id"><?php echo htmlspecialchars($pago['transaccion_wompi']); ?></div>
                                            </td>
                                            <td class="monto-col monto-pos">
                                                +$<?php echo number_format($pago['monto'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <div class="modal-overlay" id="modalMovimiento">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Registrar Movimiento Contable</h3>
                <button class="btn-close" onclick="document.getElementById('modalMovimiento').classList.remove('active')" type="button"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="registrar_movimiento" value="1">
                
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>Tipo de Movimiento *</label>
                        <div class="input-control">
                            <select name="tipo" required onchange="cambiarCategorias(this.value)">
                                <option value="">Seleccione...</option>
                                <option value="ingreso">Ingreso (Consignación, Servicios)</option>
                                <option value="egreso">Egreso (Nómina, Impuestos, etc.)</option>
                            </select>
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Categoría *</label>
                        <div class="input-control">
                            <select name="categoria" id="selectCategoria" required>
                                <option value="">Seleccione un tipo primero...</option>
                            </select>
                            <i class="fa-solid fa-tags"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Método de Pago <span class="opcional-text">(Opcional)</span></label>
                        <div class="input-control">
                            <select name="metodo_pago">
                                <option value="">No especificado...</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                <option value="Tarjeta de Crédito/Débito">Tarjeta de Crédito/Débito</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <i class="fa-regular fa-credit-card"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>N° Comprobante / Factura <span class="opcional-text">(Opcional)</span></label>
                        <div class="input-control">
                            <input type="text" name="comprobante" placeholder="Ej. FAC-00123">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Monto Subtotal (COP) *</label>
                        <div class="input-control">
                            <input type="number" id="inputSubtotal" name="subtotal" required min="1" step="any" placeholder="Ej. 150000" oninput="calcularTotal()">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Impuesto (IVA %) <span class="opcional-text">(Opcional)</span></label>
                        <div class="input-control">
                            <input type="number" name="iva_pct" id="inputIva" value="0" min="0" step="any" placeholder="Ej. 19" oninput="calcularTotal()">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Fecha *</label>
                        <div class="input-control">
                            <input type="date" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="color: var(--blue-dark);">Valor Total Calculado</label>
                        <div class="input-control">
                            <input type="text" id="inputTotalVista" class="input-total" value="$0" readonly tabindex="-1">
                            <i class="fa-solid fa-sack-dollar" style="color: var(--blue-main);"></i>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Descripción / Observación <span class="opcional-text">(Opcional)</span></label>
                        <div class="input-control">
                            <textarea name="descripcion" rows="2" placeholder="Detalles de la compra, proveedor, o justificación..."></textarea>
                            <i class="fa-solid fa-align-left" style="top: 24px;"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-new" style="width: 100%; justify-content: center; padding: 14px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Registro Contable
                </button>
            </form>
        </div>
    </div>

    <script>
        // === CÁLCULO DE IVA EN VIVO (CON INPUT LIBRE) ===
        function calcularTotal() {
            const subtotal = parseFloat(document.getElementById('inputSubtotal').value) || 0;
            const iva_pct = parseFloat(document.getElementById('inputIva').value) || 0;
            
            const total = subtotal + (subtotal * (iva_pct / 100));
            
            // Formatear a moneda colombiana visualmente
            document.getElementById('inputTotalVista').value = '$' + total.toLocaleString('es-CO', {maximumFractionDigits: 2});
        }

        // === CATEGORÍAS DINÁMICAS ===
        function cambiarCategorias(tipo) {
            const select = document.getElementById('selectCategoria');
            select.innerHTML = '';
            
            if (tipo === 'ingreso') {
                const opciones = ['Aporte a Capital', 'Servicio Adicional', 'Reembolso', 'Otro Ingreso'];
                opciones.forEach(op => select.add(new Option(op, op)));
            } else if (tipo === 'egreso') {
                const opciones = ['Nómina', 'Pago de Servidores (Hosting)', 'Publicidad / Marketing', 'Papelería y Oficina', 'Impuestos', 'Soporte / Proveedores', 'Otro Gasto'];
                opciones.forEach(op => select.add(new Option(op, op)));
            } else {
                select.add(new Option('Seleccione un tipo primero...', ''));
            }
        }

        // === GRÁFICA DOBLE (INGRESOS VS EGRESOS) ===
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('balanceChart');
            if (ctx) {
                const labels = <?php echo json_encode($chart_labels); ?>;
                const dataIngresos = <?php echo json_encode($chart_ingresos); ?>;
                const dataEgresos = <?php echo json_encode($chart_egresos); ?>;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Ingresos Totales',
                                data: dataIngresos,
                                backgroundColor: 'rgba(16, 185, 129, 0.8)', // Verde
                                borderRadius: 4
                            },
                            {
                                label: 'Gastos (Egresos)',
                                data: dataEgresos,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)', // Rojo
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 13 },
                                bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                                callbacks: { label: function(context) { return '$ ' + context.parsed.y.toLocaleString('es-CO'); } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9', drawBorder: false },
                                ticks: { font: { family: 'Inter', size: 12 }, color: '#64748b', callback: function(value) { return '$' + value.toLocaleString('es-CO'); } }
                            },
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { font: { family: 'Inter', size: 12 }, color: '#64748b' }
                            }
                        }
                    }
                });
            }
        });

        // === EXPORTAR A CSV ===
        function exportTableToCSV(filename) {
            alert('Para exportar el reporte completo, se deben unir ambas tablas. Esta función puede ser expandida según requieras en la base de datos.');
        }
    </script>
</body>
</html>