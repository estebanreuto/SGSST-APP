<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

// Variables de sesión necesarias para los componentes
$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;
$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';

$current_page = 'solicitudes.php';
$titulo_header = "Gestión de Solicitudes";
$rol_display = "Super Administrador";

/* ================================================================
// CONSULTA REAL A LA BASE DE DATOS
================================================================ */
$stmt = $conn->query("SELECT * FROM solicitudes_empresas ORDER BY fecha_creacion DESC");
$solicitudes_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$solicitudes = [];
$total_pendientes = 0;
$total_aprobadas = 0;
$total_rechazadas = 0;

foreach ($solicitudes_db as $s) {
    if ($s['estado'] === 'pendiente') $total_pendientes++;
    if ($s['estado'] === 'aprobada') $total_aprobadas++;
    if ($s['estado'] === 'rechazada') $total_rechazadas++;

    $solicitudes[] = [
        'id' => $s['id'],
        'fecha' => $s['fecha_creacion'],
        'empresa' => htmlspecialchars($s['nombre'] . ' ' . $s['apellido']), 
        'tipo' => 'Registro de Representante',
        'estado' => $s['estado'],
        'cedula' => htmlspecialchars($s['cedula'] ?? ''),
        'email' => htmlspecialchars($s['email'] ?? ''),
        'telefono' => htmlspecialchars($s['telefono'] ?? ''),
        'direccion' => htmlspecialchars($s['direccion'] ?? ''),
        'ciudad' => htmlspecialchars($s['ciudad'] ?? ''),
        'barrio' => htmlspecialchars($s['barrio'] ?? ''),
        'localidad' => htmlspecialchars($s['localidad'] ?? ''),
        'firma' => $s['firma'] 
    ];
}
$total_solicitudes = count($solicitudes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ESTILOS BASE Y GRID */
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .welcome-title { margin: 0 0 6px 0; font-size: 1.25rem; color: var(--text); letter-spacing: -0.01em; font-weight: 800; }
        .welcome-text { color: var(--muted); margin: 0; font-size: 0.85rem; }
        
        .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .info-card { background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: flex-start; gap: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        
        .icon-box { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .box-blue { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .box-orange { background: rgba(255, 138, 31, 0.08); color: var(--primary2); }
        .box-green { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .box-red { background: rgba(239, 68, 68, 0.08); color: #ef4444; }
        
        .info-content { display: flex; flex-direction: column; gap: 3px; padding-top: 2px; }
        .info-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin: 0; }
        .info-value { font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; }
        
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; background: var(--card); padding: 12px 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); gap: 16px; flex-wrap: wrap; }
        .toolbar-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;}
        .search-box { position: relative; flex: 1; max-width: 350px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; background: #f8fafc; }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        
        .filter-box select { padding: 10px 32px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; background: #f8fafc; color: var(--text); cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2394a3b8' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 16px; }
        .filter-box select:focus { outline: none; border-color: var(--primary); background-color: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        
        .toolbar-right { display: flex; align-items: center; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .view-btn { border: none; background: transparent; padding: 6px 12px; border-radius: 6px; cursor: pointer; color: #64748b; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; }
        .view-btn.active { background: #ffffff; color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .view-btn:hover:not(.active) { color: var(--text); }
        
        /* VISTA TABLA */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: block; }
        .table-wrapper.hidden { display: none; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 12px 16px; font-size: 0.65rem; text-transform: uppercase; color: var(--muted); font-weight: 800; border-bottom: 1px solid var(--border); letter-spacing: 0.05em; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text); vertical-align: middle; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        /* VISTA TARJETAS */
        .cards-wrapper { display: none; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .cards-wrapper.active { display: grid; }
        .req-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .req-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .req-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .req-card-id { color: var(--primary); font-weight: 800; font-family: monospace; font-size: 0.9rem; }
        .req-card-date { font-size: 0.75rem; color: var(--muted); font-weight: 500; display: flex; align-items: center; gap: 4px; }
        .req-card-body { flex: 1; margin-bottom: 20px; }
        .req-card-empresa { font-weight: 700; font-size: 0.95rem; color: var(--text); margin: 0 0 6px 0; }
        .req-card-tipo { font-size: 0.8rem; color: var(--muted); margin: 0; display: flex; align-items: center; gap: 6px; }
        .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #f1f5f9; }
        
        /* BADGES Y BOTONES */
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
        .badge-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-pendiente { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-pendiente::before { background: #ea580c; }
        .status-aprobada { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-aprobada::before { background: #16a34a; }
        .status-rechazada { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-rechazada::before { background: #dc2626; }
        
        .action-btns { display: flex; gap: 6px; }
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-approve { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .btn-approve:hover { background: #22c55e; color: white; }
        .btn-reject { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .btn-reject:hover { background: #ef4444; color: white; }
        .btn-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .btn-view:hover { background: #3b82f6; color: white; }
        
        .empty-state { text-align: center; padding: 40px; color: var(--muted); font-style: italic; display: none; width: 100%; grid-column: 1 / -1; }
        .empty-state.active { display: block; }

        /* ======================================================== */
        /* MODAL DE DETALLES DE SOLICITUD (MÁS COMPACTO Y FINO)     */
        /* ======================================================== */
        .modal-detalles-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); 
            display: none; justify-content: center; align-items: center; 
            z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 20px; box-sizing: border-box; 
        }
        .modal-detalles-overlay.active { display: flex; opacity: 1; }
        
        .modal-detalles-box { 
            background: #ffffff; border-radius: 20px; width: 100%; 
            max-width: 900px; /* Ancho ajustado para que no se vea desproporcionado */
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); 
            transform: translateY(-30px) scale(0.95); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            display: flex; flex-direction: column; overflow: hidden; 
            max-height: 90vh; 
        }
        .modal-detalles-overlay.active .modal-detalles-box { transform: translateY(0) scale(1); }
        
        /* CABECERA REDUCIDA */
        .modal-detalles-header { 
            background: linear-gradient(to right, #f8fafc, #ffffff); padding: 18px 30px; 
            border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 14px; position: relative; 
        }
        .modal-detalles-icon-top { 
            width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; 
            justify-content: center; flex-shrink: 0; background: rgba(59, 130, 246, 0.1); color: #3b82f6; 
        }
        .modal-detalles-icon-top svg { width: 18px; height: 18px; }
        .modal-detalles-header-text h3 { margin: 0 0 2px 0; font-size: 1rem; color: #1e293b; font-weight: 800; }
        .modal-detalles-header-text p { margin: 0; color: var(--muted); font-size: 0.72rem; }
        
        .btn-close-detalles { 
            position: absolute; top: 18px; right: 24px; background: #f1f5f9; border: none; width: 30px; height: 30px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            color: var(--muted); cursor: pointer; transition: all 0.2s; 
        }
        .btn-close-detalles:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
        
        .modal-detalles-body { padding: 24px 30px; overflow-y: auto; }
        .modal-detalles-body::-webkit-scrollbar { width: 6px; }
        .modal-detalles-body::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
        .modal-detalles-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        
        .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        
        .detail-item-styled {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; 
            background: #ffffff; transition: all 0.2s ease;
        }
        .detail-item-styled:hover {
            border-color: #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            transform: translateY(-2px);
        }
        
        .detail-icon {
            width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc; color: #64748b;
        }
        .detail-icon svg { width: 14px; height: 14px; }
        
        .ic-orange { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .ic-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .ic-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .ic-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .ic-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .ic-pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
        .ic-yellow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .ic-teal { background: rgba(20, 184, 166, 0.1); color: #14b8a6; }

        .detail-text { display: flex; flex-direction: column; gap: 2px; overflow: hidden; width: 100%; }
        /* LETRA MÁS PEQUEÑA Y ELEGANTE */
        .detail-label { font-size: 0.62rem; text-transform: uppercase; color: var(--muted); font-weight: 700; letter-spacing: 0.05em; margin: 0; }
        .detail-value { font-size: 0.78rem; color: var(--text); font-weight: 600; margin: 0; word-break: break-word; line-height: 1.3; }
        
        /* BOTON WHATSAPP MEJORADO */
        .btn-whatsapp {
            display: inline-flex; align-items: center; gap: 6px;
            background: #25D366; color: #ffffff !important; 
            padding: 5px 10px; border-radius: 6px; font-size: 0.7rem; 
            font-weight: 700; text-decoration: none; margin-top: 6px; 
            transition: all 0.2s ease; width: max-content;
            box-shadow: 0 2px 6px rgba(37, 211, 102, 0.25);
            letter-spacing: 0.02em;
        }
        .btn-whatsapp:hover { 
            background: #1ebe57; 
            transform: translateY(-1px); 
            box-shadow: 0 4px 8px rgba(37, 211, 102, 0.35); 
        }
        .btn-whatsapp svg { width: 14px; height: 14px; }

        .firma-box { border: 1px dashed var(--border); border-radius: 10px; padding: 16px; background: #f8fafc; text-align: center; margin-top: 16px; }
        .firma-box img { max-height: 100px; max-width: 100%; object-fit: contain; }
        
        .modal-detalles-footer { 
            padding: 16px 30px; border-top: 1px solid var(--border); background: #f8fafc; 
            display: flex; justify-content: flex-end; gap: 12px; 
        }
        .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 8px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
        .btn-secondary:hover { background: #f1f5f9; color: #1e293b; }
        .btn-primary-action { color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: transform 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-primary-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        @media (max-width: 1024px) { .info-grid { grid-template-columns: repeat(2, 1fr); } .detail-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 20px 16px; }
            .info-grid { grid-template-columns: 1fr; } .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar-left { flex-direction: column; } .search-box, .filter-box select { width: 100%; max-width: 100%; }
            .toolbar-right { justify-content: center; } .table-wrapper { overflow-x: auto; } table { min-width: 800px; }
            .detail-grid { grid-template-columns: 1fr; gap: 12px; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div>
                    <h1 class="welcome-title">Gestión de Solicitudes</h1>
                    <p class="welcome-text">Administra y aprueba registros y peticiones del sistema.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="icon-box box-blue">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <div class="info-content"><p class="info-label">Total Solicitudes</p><p class="info-value"><?php echo $total_solicitudes; ?></p></div>
                </div>
                <div class="info-card">
                    <div class="icon-box box-orange">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="info-content"><p class="info-label">Pendientes</p><p class="info-value"><?php echo $total_pendientes; ?></p></div>
                </div>
                <div class="info-card">
                    <div class="icon-box box-green">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="info-content"><p class="info-label">Aprobadas</p><p class="info-value"><?php echo $total_aprobadas; ?></p></div>
                </div>
                <div class="info-card">
                    <div class="icon-box box-red">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="info-content"><p class="info-label">Rechazadas</p><p class="info-value"><?php echo $total_rechazadas; ?></p></div>
                </div>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="searchInput" placeholder="Buscar por ID o remitente...">
                    </div>
                    <div class="filter-box">
                        <select id="statusFilter">
                            <option value="todos">Todos los estados</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                        </select>
                    </div>
                </div>
                <div class="toolbar-right">
                    <button class="view-btn" id="btnViewTable" title="Vista de Tabla">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Tabla
                    </button>
                    <button class="view-btn active" id="btnViewCards" title="Vista de Tarjetas">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Tarjetas
                    </button>
                </div>
            </div>

            <div class="empty-state" id="emptyStateMsg">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40" style="margin-bottom:10px; opacity:0.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <br>No se encontraron solicitudes que coincidan con tu búsqueda.
            </div>

            <div class="table-wrapper hidden" id="viewTable">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Remitente</th>
                            <th>Tipo de Solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($solicitudes)): ?>
                            <tr class="no-data-row"><td colspan="6" style="text-align: center; padding: 30px; color: var(--muted); font-style: italic;">No hay solicitudes en la base de datos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $req): ?>
                                <tr class="filter-item" data-status="<?php echo $req['estado']; ?>" data-text="<?php echo strtolower($req['id'] . ' ' . $req['empresa'] . ' ' . $req['tipo']); ?>">
                                    <td style="color: var(--primary); font-weight: 700; font-family: monospace;">#REQ-<?php echo str_pad($req['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td style="color: var(--muted); font-size: 0.8rem;"><?php echo date('d/m/Y', strtotime($req['fecha'])); ?></td>
                                    <td style="font-weight: 600;"><?php echo $req['empresa']; ?></td>
                                    <td><?php echo $req['tipo']; ?></td>
                                    <td><span class="badge-status status-<?php echo $req['estado']; ?>"><?php echo $req['estado']; ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="btn-icon btn-view" title="Ver Detalles" onclick="verDetalles(<?php echo $req['id']; ?>)">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            
                                            <?php if ($req['estado'] === 'pendiente'): ?>
                                                <button type="button" class="btn-icon btn-approve" title="Aprobar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'aprobar')">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                                <button type="button" class="btn-icon btn-reject" title="Rechazar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'rechazar')">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($req['estado'] === 'rechazada'): ?>
                                                <button type="button" class="btn-icon btn-reject" title="Eliminar" onclick="borrarSolicitud(<?php echo $req['id']; ?>)">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="cards-wrapper active" id="viewCards">
                <?php foreach ($solicitudes as $req): ?>
                    <div class="req-card filter-item" data-status="<?php echo $req['estado']; ?>" data-text="<?php echo strtolower($req['id'] . ' ' . $req['empresa'] . ' ' . $req['tipo']); ?>">
                        
                        <div class="req-card-header">
                            <span class="req-card-id">#REQ-<?php echo str_pad($req['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="req-card-date">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <?php echo date('d/m/Y', strtotime($req['fecha'])); ?>
                            </span>
                        </div>

                        <div class="req-card-body">
                            <h4 class="req-card-empresa"><?php echo $req['empresa']; ?></h4>
                            <p class="req-card-tipo">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <?php echo $req['tipo']; ?>
                            </p>
                        </div>

                        <div class="req-card-footer">
                            <span class="badge-status status-<?php echo $req['estado']; ?>">
                                <?php echo $req['estado']; ?>
                            </span>
                            
                            <div class="action-btns">
                                <button type="button" class="btn-icon btn-view" title="Ver Detalles" onclick="verDetalles(<?php echo $req['id']; ?>)">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                
                                <?php if ($req['estado'] === 'pendiente'): ?>
                                    <button type="button" class="btn-icon btn-approve" title="Aprobar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'aprobar')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon btn-reject" title="Rechazar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'rechazar')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                <?php endif; ?>

                                <?php if ($req['estado'] === 'rechazada'): ?>
                                    <button type="button" class="btn-icon btn-reject" title="Eliminar" onclick="borrarSolicitud(<?php echo $req['id']; ?>)">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <div class="modal-detalles-overlay" id="modalDetalles">
        <div class="modal-detalles-box">
            <div class="modal-detalles-header">
                <div class="modal-detalles-icon-top">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div class="modal-detalles-header-text">
                    <h3>Detalles de la Solicitud</h3>
                    <p>Información de registro enviada por el representante.</p>
                </div>
                <button type="button" class="btn-close-detalles" onclick="cerrarDetalles()" title="Cerrar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="modal-detalles-body" id="detallesBody">
                </div>
            
            <div class="modal-detalles-footer" id="detallesFooter">
                </div>
        </div>
    </div>
    
    <?php include '../components/modal_confirmacion.php'; ?>

    <script>
        // PASO 1: Pasamos todo el arreglo de PHP a una variable JSON de Javascript
        const datosSolicitudes = <?php echo json_encode($solicitudes); ?>;

        // PASO 2: Función para buscar la info y abrir el modal
        function verDetalles(id) {
            const data = datosSolicitudes.find(s => s.id === id);
            if (!data) return;

            let badgeClass = 'status-pendiente';
            if (data.estado === 'aprobada') badgeClass = 'status-aprobada';
            if (data.estado === 'rechazada') badgeClass = 'status-rechazada';

            let formattedId = String(data.id).padStart(4, '0');

            // Limpieza del teléfono y botón de WhatsApp MEJORADO
            let cleanPhone = data.telefono ? data.telefono.replace(/\D/g, '') : '';
            let waButton = cleanPhone ? `<a href="https://wa.me/57${cleanPhone}" target="_blank" class="btn-whatsapp" title="Contactar por WhatsApp">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.711.848 3.146.848 3.182 0 5.767-2.585 5.769-5.766.001-3.181-2.586-5.766-5.766-5.766m3.167 8.272c-.135.378-.795.731-1.092.744-.297.013-.672.046-2.146-.566-1.782-.74-2.923-2.56-3.008-2.671-.085-.111-.718-.958-.718-1.838 0-.88.455-1.312.617-1.488.163-.176.353-.221.472-.221.119 0 .238.001.343.006.111.005.259-.043.405.311.15.362.515 1.258.562 1.353.047.095.078.206.015.337-.062.131-.095.213-.19.324-.095.112-.2.247-.284.331-.095.101-.194.21-.082.391.112.182.497.813 1.034 1.285.69.61 1.306.797 1.485.892.18.095.284.078.39-.033.106-.112.46-.537.584-.722.124-.184.248-.153.414-.091.166.061 1.047.495 1.226.584.18.089.299.135.343.21.045.075.045.437-.09.815"/>
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 1.764.453 3.42 1.282 4.887L2 22l5.253-1.253A9.954 9.954 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2m0 18.25c-1.503 0-2.97-.384-4.26-1.112l-.306-.181-3.17.756.768-3.088-.198-.315A8.252 8.252 0 0 1 3.75 12c0-4.55 3.7-8.25 8.25-8.25s8.25 3.7 8.25 8.25-3.7 8.25-8.25 8.25"/>
                </svg>
                Escribir
            </a>` : '';


            const bodyHtml = `
                <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-family: monospace; font-weight: 800; font-size: 1.1rem; color: var(--blue-dark);">#REQ-${formattedId}</span>
                    </div>
                    <span class="badge-status ${badgeClass}">${data.estado}</span>
                </div>
                
                <div class="detail-grid">
                    
                    <div class="detail-item-styled">
                        <div class="detail-icon ic-orange">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Empresa</span>
                            <p class="detail-value">${data.empresa}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item-styled">
                        <div class="detail-icon ic-blue">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Cédula o NIT</span>
                            <p class="detail-value">${data.cedula}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-green">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Correo</span>
                            <p class="detail-value">${data.email}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-purple">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Teléfono</span>
                            <div style="display:flex; flex-direction: column; align-items:flex-start;">
                                <p class="detail-value">${data.telefono || 'N/A'}</p>
                                ${waButton}
                            </div>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-red">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Dirección</span>
                            <p class="detail-value">${data.direccion || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-pink">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Ciudad</span>
                            <p class="detail-value">${data.ciudad || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-yellow">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Barrio</span>
                            <p class="detail-value">${data.barrio || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-teal">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        </div>
                        <div class="detail-text">
                            <span class="detail-label">Localidad</span>
                            <p class="detail-value">${data.localidad || 'N/A'}</p>
                        </div>
                    </div>

                </div>
                
                <div class="firma-box">
                    <span class="detail-label" style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:12px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Firma Digital Capturada
                    </span>
                    ${data.firma ? `<img src="${data.firma}" alt="Firma de la solicitud">` : '<span style="color:#94a3b8; font-style:italic;">No se adjuntó firma digital.</span>'}
                </div>
            `;

            document.getElementById('detallesBody').innerHTML = bodyHtml;

            let footerHtml = `<button type="button" class="btn-secondary" onclick="cerrarDetalles()">Cerrar Detalles</button>`;
            
            if (data.estado === 'pendiente') {
                footerHtml += `
                    <button type="button" class="btn-primary-action" style="background: #ef4444;" onclick="cerrarDetalles(); procesarSolicitud(${data.id}, 'rechazar')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Rechazar
                    </button>
                    <button type="button" class="btn-primary-action" style="background: #10b981;" onclick="cerrarDetalles(); procesarSolicitud(${data.id}, 'aprobar')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Aprobar y Crear Usuario
                    </button>
                `;
            } else if (data.estado === 'rechazada') {
                footerHtml += `
                    <button type="button" class="btn-primary-action" style="background: #ef4444;" onclick="cerrarDetalles(); borrarSolicitud(${data.id})">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Eliminar Definitivamente
                    </button>
                `;
            }
            document.getElementById('detallesFooter').innerHTML = footerHtml;

            document.getElementById('modalDetalles').classList.add('active');
        }

        function cerrarDetalles() {
            document.getElementById('modalDetalles').classList.remove('active');
        }

        document.getElementById('modalDetalles').addEventListener('click', function(e) {
            if (e.target === this) cerrarDetalles();
        });

        function procesarSolicitud(id, accion) {
            let titulo = accion === 'aprobar' ? 'Aprobar Registro' : 'Rechazar Registro';
            let mensaje = accion === 'aprobar' 
                ? '¿Seguro que deseas aprobar a este representante? Se creará su usuario automáticamente en el sistema.' 
                : '¿Seguro que deseas rechazar esta solicitud?';
            let url = 'procesar_solicitud.php?id=' + id + '&accion=' + accion;
            let tipo = accion === 'aprobar' ? 'success' : 'danger';
            let btnText = accion === 'aprobar' ? 'Sí, Aprobar' : 'Sí, Rechazar';
            
            showConfirmModal(titulo, mensaje, url, tipo, btnText);
        }

        function borrarSolicitud(id) {
            let titulo = 'Eliminar Solicitud';
            let mensaje = '¿Estás seguro de que deseas eliminar permanentemente esta solicitud rechazada? Esta acción no se puede deshacer.';
            let url = 'procesar_solicitud.php?id=' + id + '&accion=borrar';
            let tipo = 'danger';
            let btnText = 'Sí, Eliminar';
            
            showConfirmModal(titulo, mensaje, url, tipo, btnText);
        }

        document.addEventListener('DOMContentLoaded', function() {
            
            const btnTable = document.getElementById('btnViewTable');
            const btnCards = document.getElementById('btnViewCards');
            const viewTable = document.getElementById('viewTable');
            const viewCards = document.getElementById('viewCards');

            btnTable.addEventListener('click', () => {
                btnTable.classList.add('active');
                btnCards.classList.remove('active');
                viewTable.classList.remove('hidden');
                viewCards.classList.remove('active');
            });

            btnCards.addEventListener('click', () => {
                btnCards.classList.add('active');
                btnTable.classList.remove('active');
                viewTable.classList.add('hidden');
                viewCards.classList.add('active');
            });

            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const items = document.querySelectorAll('.filter-item'); 
            const emptyStateMsg = document.getElementById('emptyStateMsg');

            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const statusTerm = statusFilter.value;
                let visibleCount = 0;

                items.forEach(item => {
                    const itemText = item.getAttribute('data-text');
                    const itemStatus = item.getAttribute('data-status');
                    
                    const matchesSearch = itemText.includes(searchTerm);
                    const matchesStatus = (statusTerm === 'todos' || itemStatus === statusTerm);

                    if (matchesSearch && matchesStatus) {
                        item.style.display = ''; 
                        visibleCount++;
                    } else {
                        item.style.display = 'none'; 
                    }
                });

                if (visibleCount === 0 && items.length > 0) {
                    emptyStateMsg.classList.add('active');
                    viewTable.style.display = 'none';
                    viewCards.style.display = 'none';
                } else {
                    emptyStateMsg.classList.remove('active');
                    if(btnTable.classList.contains('active')) {
                        viewTable.style.display = 'block';
                    } else {
                        viewCards.style.display = 'grid';
                    }
                }
            }

            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
        });
    </script>
</body>
</html>