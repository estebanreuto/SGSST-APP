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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ESTILOS BASE Y GRID */
        :root { 
            --primary: #ff8a1f; --primary2: #ff7a00; 
            --bg1: #edf4fb; --bg2: #f7f9fc; 
            --card: #ffffff; --text: #1f2d3d; 
            --muted: #5f6f82; --border: #dbe3ec; 
            --radius: 12px; --blue-dark: #1e3a8a;
        }
        
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 24px 32px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO PREMIUM ESTANDARIZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 16px; }
        
        .icon-box-std { 
            width: 48px; height: 48px; 
            background: rgba(59, 130, 246, 0.1); color: #3b82f6; 
            border-radius: 12px; display: flex; align-items: center; justify-content: center; 
            flex-shrink: 0; border: 1px solid rgba(59, 130, 246, 0.2); 
        }
        
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.25rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.85rem; font-weight: 500; line-height: 1.4; }

        /* =========================================
           TARJETAS RESUMEN (ESTILO COMPACTO PREMIUM)
           ========================================= */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(4, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 16px; position: relative; overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        /* Marca de agua FontAwesome */
        .summary-bg-icon { 
            position: absolute; right: -10px; bottom: -20px; font-size: 100px; 
            line-height: 1; opacity: 0.06; transform: rotate(-15deg); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; z-index: 0;
        }
        .summary-card:hover .summary-bg-icon { transform: rotate(0deg) scale(1.1); opacity: 0.12; }
        
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        
        .summary-icon-box { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .summary-value { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; }
        .summary-title { font-size: 0.9rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 2px 0; }
        .summary-desc { font-size: 0.75rem; color: var(--muted); margin: 0; }

        /* Colores Tarjetas */
        .card-blue .summary-bg-icon { color: #3b82f6; }
        .card-blue .summary-icon-box { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .card-blue .summary-value { color: #3b82f6; }

        .card-orange .summary-bg-icon { color: var(--primary); }
        .card-orange .summary-icon-box { background: rgba(255, 138, 31, 0.08); color: var(--primary); }
        .card-orange .summary-value { color: var(--primary); }

        .card-green .summary-bg-icon { color: #10b981; }
        .card-green .summary-icon-box { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .card-green .summary-value { color: #10b981; }

        .card-red .summary-bg-icon { color: #ef4444; }
        .card-red .summary-icon-box { background: rgba(239, 68, 68, 0.08); color: #ef4444; }
        .card-red .summary-value { color: #ef4444; }

        /* =========================================
           BARRA DE HERRAMIENTAS Y VISTAS
           ========================================= */
        .toolbar { 
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; 
            background: var(--card); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); 
            box-shadow: 0 2px 4px rgba(0,0,0,0.01); gap: 16px; flex-wrap: wrap; 
        }
        
        .toolbar-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;}
        .search-box { position: relative; flex: 1; max-width: 350px; }
        .search-box input { 
            width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.85rem; color: var(--text); 
            background: #f8fafc; transition: all 0.2s; box-sizing: border-box; 
        }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        
        /* CORRECCIÓN LUPA: Aplica a i y svg para que no se salga */
        .search-box i, .search-box svg { 
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%); 
            color: #94a3b8; pointer-events: none; 
        }
        
        .filter-box select { 
            padding: 10px 32px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.85rem; background: #f8fafc; color: var(--text); 
            cursor: pointer; appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2394a3b8' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); 
            background-repeat: no-repeat; background-position: right 10px center; background-size: 16px; 
        }
        .filter-box select:focus { outline: none; border-color: var(--primary); background-color: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }

        /* ESTADO VACÍO (EMPTY STATE) */
        .empty-state { 
            text-align: center; padding: 40px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; 
            border-radius: 16px; color: #94a3b8; font-size: 0.9rem; font-weight: 500; display: none; 
            flex-direction: column; align-items: center; gap: 12px; grid-column: 1 / -1; 
        }
        .empty-state i { font-size: 2.5rem; color: #cbd5e1; }

        /* =========================================
           VISTA TARJETAS (ESTILO COMPACTO Y PREDETERMINADO)
           ========================================= */
        .cards-wrapper { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        
        .req-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); display: flex; flex-direction: column; 
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        .req-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        .req-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .req-card-id { color: #64748b; font-weight: 700; font-family: monospace; font-size: 0.85rem; }
        .req-card-date { font-size: 0.75rem; color: var(--muted); font-weight: 500; display: flex; align-items: center; gap: 4px; }
        
        .req-card-body { flex: 1; margin-bottom: 16px; }
        .req-card-empresa { font-weight: 700; font-size: 0.95rem; color: var(--blue-dark); margin: 0 0 4px 0; }
        .req-card-tipo { font-size: 0.8rem; color: var(--muted); margin: 0; display: flex; align-items: center; gap: 6px; }
        
        .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f1f5f9; }

        /* =========================================
           BADGES Y BOTONES DE ACCIÓN SUTILES
           ========================================= */
        .badge-status { 
            padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; 
            display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.03em;
        }
        .status-pendiente { background: #fffbeb; color: #d97706; } 
        .status-aprobada { background: #dcfce7; color: #166534; }  
        .status-rechazada { background: #fee2e2; color: #991b1b; } 
        
        .action-btns { display: flex; gap: 6px; }
        .btn-icon { 
            width: 30px; height: 30px; border-radius: 8px; border: none; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-size: 0.9rem;
        }
        .btn-approve { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .btn-approve:hover { background: #22c55e; color: white; }
        .btn-reject { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .btn-reject:hover { background: #ef4444; color: white; }
        .btn-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .btn-view:hover { background: #3b82f6; color: white; }

        /* ======================================================== */
        /* MODAL DE DETALLES DE SOLICITUD (DISEÑO FINO Y COMPACTO)  */
        /* ======================================================== */
        .modal-detalles-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); 
            display: none; justify-content: center; align-items: center; 
            z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 20px; box-sizing: border-box; 
        }
        .modal-detalles-overlay.active { display: flex; opacity: 1; }
        
        .modal-detalles-box { 
            background: #ffffff; border-radius: 16px; width: 100%; max-width: 850px; 
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); 
            transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; 
        }
        .modal-detalles-overlay.active .modal-detalles-box { transform: translateY(0) scale(1); }
        
        .modal-detalles-header { 
            background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid var(--border); 
            display: flex; align-items: center; gap: 14px; position: relative; 
        }
        
        .modal-detalles-icon-top { 
            width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; 
            justify-content: center; flex-shrink: 0; background: rgba(59, 130, 246, 0.1); color: #3b82f6; font-size: 1rem;
        }
        .modal-detalles-header-text h3 { margin: 0 0 2px 0; font-size: 1rem; color: #1e293b; font-weight: 800; }
        .modal-detalles-header-text p { margin: 0; color: var(--muted); font-size: 0.75rem; }
        
        .btn-close-detalles { 
            position: absolute; top: 16px; right: 20px; background: transparent; border: none; 
            width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; 
            color: #94a3b8; cursor: pointer; transition: all 0.2s; font-size: 1rem;
        }
        .btn-close-detalles:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
        
        .modal-detalles-body { padding: 20px 24px; overflow-y: auto; }
        
        .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        
        .detail-item-styled {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px; border-radius: 10px; border: 1px solid #f1f5f9; 
            background: #ffffff; transition: all 0.2s ease;
        }
        .detail-item-styled:hover { border-color: #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        
        .detail-icon {
            width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc; color: #64748b; font-size: 0.8rem;
        }
        
        .ic-orange { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .ic-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .ic-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .ic-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .ic-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .ic-pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
        .ic-yellow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .ic-teal { background: rgba(20, 184, 166, 0.1); color: #14b8a6; }

        .detail-text { display: flex; flex-direction: column; gap: 2px; overflow: hidden; width: 100%; }
        .detail-label { font-size: 0.65rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.03em; margin: 0; }
        .detail-value { font-size: 0.8rem; color: #334155; font-weight: 600; margin: 0; word-break: break-word; line-height: 1.3; }
        
        .btn-whatsapp {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(37, 211, 102, 0.1); color: #16a34a !important; 
            padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; 
            font-weight: 700; text-decoration: none; margin-top: 6px; 
            transition: all 0.2s ease; width: max-content; border: 1px solid rgba(37, 211, 102, 0.2);
        }
        .btn-whatsapp:hover { background: #25D366; color: white !important; }

        .firma-box { border: 1px dashed var(--border); border-radius: 12px; padding: 16px; background: #f8fafc; text-align: center; margin-top: 16px; }
        .firma-box img { max-height: 90px; max-width: 100%; object-fit: contain; }
        
        .modal-detalles-footer { 
            padding: 16px 24px; border-top: 1px solid var(--border); background: #ffffff; 
            display: flex; justify-content: flex-end; gap: 10px; 
        }
        .btn-secondary { background: #ffffff; color: #64748b; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
        .btn-secondary:hover { background: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
        
        .btn-primary-action { color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; gap: 6px; }
        .btn-primary-action:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }

        @media (max-width: 1024px) { .summary-cards-grid { grid-template-columns: repeat(2, 1fr); } .detail-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; } .content-area { padding: 16px; }
            .summary-cards-grid { grid-template-columns: 1fr; } 
            .estandar-header-group { flex-direction: column; align-items: flex-start; gap: 12px; }
            .toolbar-left { flex-direction: column; } .search-box, .filter-box select { width: 100%; max-width: 100%; }
            .detail-grid { grid-template-columns: 1fr; gap: 10px; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-inbox" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Gestión de Solicitudes</h1>
                        <p class="estandar-subtitle">Administra y aprueba registros y peticiones del sistema.</p>
                    </div>
                </div>
            </div>

            <div class="summary-cards-grid">
                
                <div class="summary-card card-blue">
                    <i class="fa-solid fa-layer-group summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_solicitudes; ?></h2>
                        </div>
                        <h3 class="summary-title">Total Solicitudes</h3>
                        <p class="summary-desc">Recibidas en el sistema.</p>
                    </div>
                </div>

                <div class="summary-card card-orange">
                    <i class="fa-regular fa-clock summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-regular fa-clock"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_pendientes; ?></h2>
                        </div>
                        <h3 class="summary-title">Pendientes</h3>
                        <p class="summary-desc">Esperando revisión.</p>
                    </div>
                </div>

                <div class="summary-card card-green">
                    <i class="fa-regular fa-circle-check summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-regular fa-circle-check"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_aprobadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Aprobadas</h3>
                        <p class="summary-desc">Usuarios creados.</p>
                    </div>
                </div>

                <div class="summary-card card-red">
                    <i class="fa-regular fa-circle-xmark summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <i class="fa-regular fa-circle-xmark"></i>
                            </div>
                            <h2 class="summary-value"><?php echo $total_rechazadas; ?></h2>
                        </div>
                        <h3 class="summary-title">Rechazadas</h3>
                        <p class="summary-desc">Solicitudes denegadas.</p>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
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
            </div>

            <div class="empty-state" id="emptyStateMsg" style="<?php echo empty($solicitudes) ? 'display: flex;' : ''; ?>">
                <i class="fa-regular fa-folder-open"></i>
                <span>No se encontraron solicitudes.</span>
            </div>

            <div class="cards-wrapper" id="viewCards">
                <?php foreach ($solicitudes as $req): ?>
                    <div class="req-card filter-item" data-status="<?php echo $req['estado']; ?>" data-text="<?php echo strtolower($req['id'] . ' ' . $req['empresa'] . ' ' . $req['tipo']); ?>">
                        
                        <div class="req-card-header">
                            <span class="req-card-id">#REQ-<?php echo str_pad($req['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="req-card-date">
                                <i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($req['fecha'])); ?>
                            </span>
                        </div>

                        <div class="req-card-body">
                            <h4 class="req-card-empresa"><?php echo $req['empresa']; ?></h4>
                            <p class="req-card-tipo">
                                <i class="fa-solid fa-tag"></i> <?php echo $req['tipo']; ?>
                            </p>
                        </div>

                        <div class="req-card-footer">
                            <span class="badge-status status-<?php echo $req['estado']; ?>"><?php echo $req['estado']; ?></span>
                            
                            <div class="action-btns">
                                <button type="button" class="btn-icon btn-view" title="Ver Detalles" onclick="verDetalles(<?php echo $req['id']; ?>)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                
                                <?php if ($req['estado'] === 'pendiente'): ?>
                                    <button type="button" class="btn-icon btn-approve" title="Aprobar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'aprobar')">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button type="button" class="btn-icon btn-reject" title="Rechazar" onclick="procesarSolicitud(<?php echo $req['id']; ?>, 'rechazar')">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($req['estado'] === 'rechazada'): ?>
                                    <button type="button" class="btn-icon btn-reject" title="Eliminar Definitivamente" onclick="borrarSolicitud(<?php echo $req['id']; ?>)">
                                        <i class="fa-regular fa-trash-can"></i>
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
                    <i class="fa-regular fa-id-card"></i>
                </div>
                <div class="modal-detalles-header-text">
                    <h3>Detalles de la Solicitud</h3>
                    <p>Información de registro enviada por el representante.</p>
                </div>
                <button type="button" class="btn-close-detalles" onclick="cerrarDetalles()" title="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
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
        // Arreglo PHP a JS
        const datosSolicitudes = <?php echo json_encode($solicitudes); ?>;

        function verDetalles(id) {
            const data = datosSolicitudes.find(s => s.id === id);
            if (!data) return;

            let badgeClass = 'status-pendiente';
            if (data.estado === 'aprobada') badgeClass = 'status-aprobada';
            if (data.estado === 'rechazada') badgeClass = 'status-rechazada';

            let formattedId = String(data.id).padStart(4, '0');

            // Limpieza del teléfono y botón de WhatsApp
            let cleanPhone = data.telefono ? data.telefono.replace(/\D/g, '') : '';
            let waButton = cleanPhone ? `<a href="https://wa.me/57${cleanPhone}" target="_blank" class="btn-whatsapp" title="Contactar por WhatsApp">
                <i class="fa-brands fa-whatsapp" style="font-size:0.9rem;"></i> Mensaje
            </a>` : '';

            const bodyHtml = `
                <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-family: monospace; font-weight: 700; font-size: 1.1rem; color: #64748b;">#REQ-${formattedId}</span>
                    </div>
                    <span class="badge-status ${badgeClass}">${data.estado}</span>
                </div>
                
                <div class="detail-grid">
                    
                    <div class="detail-item-styled">
                        <div class="detail-icon ic-orange"><i class="fa-solid fa-building"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Empresa</span>
                            <p class="detail-value">${data.empresa}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item-styled">
                        <div class="detail-icon ic-blue"><i class="fa-solid fa-id-card"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Cédula o NIT</span>
                            <p class="detail-value">${data.cedula}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-green"><i class="fa-solid fa-envelope"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Correo</span>
                            <p class="detail-value">${data.email}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-purple"><i class="fa-solid fa-phone"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Teléfono</span>
                            <div style="display:flex; flex-direction: column; align-items:flex-start;">
                                <p class="detail-value">${data.telefono || 'N/A'}</p>
                                ${waButton}
                            </div>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-red"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Dirección</span>
                            <p class="detail-value">${data.direccion || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-pink"><i class="fa-solid fa-city"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Ciudad</span>
                            <p class="detail-value">${data.ciudad || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-yellow"><i class="fa-solid fa-map-location-dot"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Barrio</span>
                            <p class="detail-value">${data.barrio || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="detail-item-styled">
                        <div class="detail-icon ic-teal"><i class="fa-solid fa-map"></i></div>
                        <div class="detail-text">
                            <span class="detail-label">Localidad</span>
                            <p class="detail-value">${data.localidad || 'N/A'}</p>
                        </div>
                    </div>

                </div>
                
                <div class="firma-box">
                    <span class="detail-label" style="display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:12px;">
                        <i class="fa-solid fa-signature"></i> Firma Digital Capturada
                    </span>
                    ${data.firma ? `<img src="${data.firma}" alt="Firma de la solicitud">` : '<span style="color:#94a3b8; font-style:italic; font-size:0.8rem;">No se adjuntó firma digital.</span>'}
                </div>
            `;

            document.getElementById('detallesBody').innerHTML = bodyHtml;

            let footerHtml = `<button type="button" class="btn-secondary" onclick="cerrarDetalles()">Cerrar Detalles</button>`;
            
            if (data.estado === 'pendiente') {
                footerHtml += `
                    <button type="button" class="btn-primary-action" style="background: #ef4444;" onclick="cerrarDetalles(); procesarSolicitud(${data.id}, 'rechazar')">
                        <i class="fa-solid fa-xmark"></i> Rechazar
                    </button>
                    <button type="button" class="btn-primary-action" style="background: #10b981;" onclick="cerrarDetalles(); procesarSolicitud(${data.id}, 'aprobar')">
                        <i class="fa-solid fa-check"></i> Aprobar y Crear Usuario
                    </button>
                `;
            } else if (data.estado === 'rechazada') {
                footerHtml += `
                    <button type="button" class="btn-primary-action" style="background: #ef4444;" onclick="cerrarDetalles(); borrarSolicitud(${data.id})">
                        <i class="fa-regular fa-trash-can"></i> Eliminar Definitivamente
                    </button>
                `;
            }
            document.getElementById('detallesFooter').innerHTML = footerHtml;

            document.getElementById('modalDetalles').classList.add('active');
        }

        function cerrarDetalles() {
            document.getElementById('modalDetalles').classList.remove('active');
        }

        // Cerrar modal al hacer clic por fuera
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

        // Lógica del buscador y filtros para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const items = document.querySelectorAll('.filter-item'); 
            const emptyStateMsg = document.getElementById('emptyStateMsg');
            const viewCards = document.getElementById('viewCards');

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
                        item.style.display = 'flex'; // Mantener el flexbox de la tarjeta
                        visibleCount++;
                    } else {
                        item.style.display = 'none'; 
                    }
                });

                if (visibleCount === 0 && items.length > 0) {
                    emptyStateMsg.style.display = 'flex';
                    viewCards.style.display = 'none';
                } else {
                    emptyStateMsg.style.display = 'none';
                    viewCards.style.display = 'grid';
                }
            }

            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
        });
    </script>
</body>
</html>