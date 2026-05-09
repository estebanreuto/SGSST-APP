<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Validar permisos (Trabajador no debería estar aquí directamente administrando)
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
    } catch (PDOException $e) {
        $grupos = [];
    }
}

// 2. Obtener los trabajadores activos para el buscador múltiple
$trabajadores_activos = [];
if ($empresa_id) {
    try {
        $stmt_ta = $conn->prepare("SELECT id, nombre, apellido, cedula, foto_perfil FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador' AND activo = 1 ORDER BY nombre ASC, apellido ASC");
        $stmt_ta->execute([$empresa_id]);
        $trabajadores_activos = $stmt_ta->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $trabajadores_activos = [];
    }
}

$current_page = 'estandar3.php'; // Mantener para el resaltado del sidebar
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Actividad | SG-SST Pro</title>
    
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
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        
        .icon-box-std { 
            width: 44px; height: 44px; 
            background: rgba(255, 138, 31, 0.08); color: var(--primary); 
            border-radius: 10px; display: flex; align-items: center; justify-content: center; 
            flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); 
        }
        .icon-box-std svg { width: 22px; height: 22px; }
        
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; display: block; }
        .estandar-subtitle { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 500; }
        
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }

        /* =========================================
           BANNER GOOGLE CALENDAR
           ========================================= */
        .google-sync-banner {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 20px; border-radius: 12px; margin-bottom: 32px;
            font-family: 'Inter', sans-serif; transition: all 0.3s ease;
        }
        .google-sync-banner.disconnected { background: #fffbeb; border: 1px solid #fde68a; }
        .google-sync-banner.connected { background: #f0fdf4; border: 1px solid #bbf7d0; }
        
        .g-sync-info { display: flex; align-items: center; gap: 14px; }
        .g-sync-icon { 
            width: 40px; height: 40px; border-radius: 10px; display: flex; 
            align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; 
        }
        .disconnected .g-sync-icon { background: rgba(217, 119, 6, 0.1); color: #d97706; }
        .connected .g-sync-icon { background: rgba(22, 163, 74, 0.1); color: #16a34a; }
        
        .g-sync-text h4 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .g-sync-text p { margin: 2px 0 0 0; font-size: 0.8rem; color: #475569; }
        
        .btn-google { 
            background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; padding: 8px 16px; 
            border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; 
            font-size: 0.85rem; display: flex; align-items: center; gap: 8px; text-decoration: none; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .btn-google:hover { background: #f8fafc; border-color: #94a3b8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transform: translateY(-1px); }
        .btn-google svg { width: 16px; height: 16px; }

        /* =========================================
           LAYOUT 2 COLUMNAS
           ========================================= */
        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 32px;
            align-items: start;
        }

        .left-column { display: flex; flex-direction: column; gap: 24px; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { text-align: left; }
        .form-group label.title-label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.75rem; color: #475569; text-transform: uppercase; letter-spacing: 0.03em; }
        
        .input-icon-wrapper { position: relative; width: 100%; }
        .input-icon-wrapper > i.icon-form { 
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; pointer-events: none; z-index: 10; font-size: 0.95rem;
        }
        
        .input-icon-wrapper > input.actividad-input, 
        .input-icon-wrapper > select.actividad-input { 
            width: 100%; padding: 10px 14px 10px 40px; border: 1px solid #cbd5e1; 
            border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b;
            transition: all 0.2s ease; box-sizing: border-box; background: #ffffff; height: 42px;
        }
        
        .input-icon-wrapper > select.actividad-input {
            appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;
        }
        
        .input-icon-wrapper > input.actividad-input:focus, 
        .input-icon-wrapper > select.actividad-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }

        /* =========================================
           TARJETAS FEATURE COMPACTAS
           ========================================= */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        
        .feature-card {
            background: #ffffff; border-radius: 14px; padding: 16px; border: 1px solid #cbd5e1;
            position: relative; overflow: hidden; transition: all 0.2s ease; cursor: pointer;
            display: flex; flex-direction: column; gap: 12px; z-index: 1; margin: 0 !important; font-weight: 400 !important;
        }

        .feature-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.04); border-color: #94a3b8; }
        .feature-card:has(input:checked) { background: #fff8f3; border-color: var(--primary); box-shadow: 0 4px 12px rgba(255, 138, 31, 0.08); }

        .watermark-icon { 
            position: absolute; right: -8px; bottom: -10px; font-size: 75px; color: var(--primary); 
            opacity: 0.06; transform: rotate(-15deg); z-index: 0; transition: all 0.3s ease; pointer-events: none; 
        }
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

        /* =========================================
           LISTA DE TRABAJADORES
           ========================================= */
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

        /* =========================================
           COLUMNA DERECHA (PANEL LATERAL FIJO)
           ========================================= */
        .right-column {
            position: sticky;
            top: 32px; 
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .schedule-group { display: flex; flex-direction: column; gap: 8px; }

        .datetime-row { display: grid; grid-template-columns: 3fr 2fr; gap: 12px; }

        .btn-primary-act { 
            width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #fff; 
            border: none; padding: 12px; border-radius: 10px; font-size: 0.9rem; font-weight: 700; 
            cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; 
            justify-content: center; align-items: center; gap: 8px; box-sizing: border-box;
        }
        .btn-primary-act:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.25); }
        
        .btn-cancel { 
            width: 100%; background: transparent; color: #64748b; border: 1px solid #cbd5e1; 
            padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; 
            transition: all 0.2s; font-size: 0.9rem; display: flex; justify-content: center; 
            align-items: center; gap: 8px; box-sizing: border-box;
        }
        .btn-cancel:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        .flatpickr-calendar { font-family: 'Inter', sans-serif !important; border-radius: 12px !important; box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border: 1px solid #e2e8f0 !important;}
        .flatpickr-day.selected { background: var(--primary) !important; border-color: var(--primary) !important; }
        input.datepicker[readonly], input.timepicker[readonly] { background-color: #ffffff; cursor: pointer; }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
            .layout-grid { grid-template-columns: 1fr; gap: 40px;}
            .right-column { position: static; order: -1; } 
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
            .btn-back { order: -1; width: max-content; padding: 6px 12px; }
            .form-grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .cards-grid { grid-template-columns: 1fr; }
            .datetime-row { grid-template-columns: 1fr; gap: 12px; } 
            .google-sync-banner { flex-direction: column; align-items: flex-start; gap: 16px; }
            .btn-google { width: 100%; justify-content: center; }
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
                        <h1 class="estandar-title">Programar Nueva Capacitación</h1>
                        <p class="estandar-subtitle">
                            Crea tus reuniones o capacitaciones conectadas con Google Calendar
                        </p>
                    </div>
                </div>
                <a href="estandar3.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Estándar 3
                </a>
            </div>

            <?php if ($google_connected): ?>
                <div class="google-sync-banner connected">
                    <div class="g-sync-info">
                        <div class="g-sync-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="g-sync-text">
                            <h4>Conectado a Google Calendar</h4>
                            <p>Tus reuniones crearán un enlace de Google Meet automáticamente.</p>
                        </div>
                    </div>
                    <span style="color: #16a34a; font-weight: 700; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-check-circle"></i> Sincronizado
                    </span>
                </div>
            <?php else: ?>
                <div class="google-sync-banner disconnected">
                    <div class="g-sync-info">
                        <div class="g-sync-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="g-sync-text">
                            <h4>Google Calendar no conectado</h4>
                            <p>Conecta tu cuenta para agendar y crear reuniones de Meet en automático.</p>
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

            <form id="formRegistroActividad" action="procesar_estandar3.php" method="POST">
                <input type="hidden" name="accion" value="crear_actividad">
                
                <div class="layout-grid">
                    
                    <div class="left-column">
                        
                        <div class="form-group full" style="margin-bottom: 0;">
                            <label class="title-label" for="nombre_actividad">Nombre de la Actividad *</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-pen-to-square icon-form"></i>
                                <input type="text" name="nombre_actividad" id="nombre_actividad" class="actividad-input" required placeholder="Ej. Uso y manejo de extintores">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="title-label" for="tipo_capacitacion">Tipo de Capacitación *</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-layer-group icon-form"></i>
                                    <select name="tipo_capacitacion" id="tipo_capacitacion" class="actividad-input" required>
                                        <option value="">Selecciona...</option>
                                        <option value="Inducción">Inducción</option>
                                        <option value="Re Inducción">Re Inducción</option>
                                        <option value="Charla de Seguridad">Charla de Seguridad</option>
                                        <option value="Capacitación">Capacitación</option>
                                        <option value="Entrenamiento">Entrenamiento</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="title-label" for="categoria">Categoría *</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-tag icon-form"></i>
                                    <select name="categoria" id="categoria" class="actividad-input" required>
                                        <option value="">Selecciona...</option>
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
                            </div>
                        </div>

                        <div class="form-group full" style="margin-bottom: 0;">
                            <label class="title-label">Dirigido a *</label>
                            
                            <div class="cards-grid">
                                <label class="feature-card">
                                    <i class="fa-solid fa-building-user watermark-icon"></i>
                                    <div class="card-header">
                                        <div class="feature-icon blue"><i class="fa-solid fa-building-user"></i></div>
                                        <input type="radio" name="dirigido_a" value="Toda la empresa" class="radio-custom-dir" required>
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
                                        <input type="radio" name="dirigido_a" value="Trabajador Específico" class="radio-custom-dir" required>
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
                                                <input type="radio" name="dirigido_a" value="Grupo: <?php echo htmlspecialchars($g['nombre']); ?>" class="radio-custom-dir" required>
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

                        <div class="form-group full" id="contenedor_trabajadores_especificos" style="display: none; animation: slideInUp 0.2s ease; margin-bottom: 0;">
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
                                                <input type="checkbox" name="trabajadores_seleccionados[]" value="<?php echo $ta['id']; ?>" class="chk-trabajador custom-chk">
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
                        
                        <div class="schedule-group">
                            <label class="title-label">Inicio *</label>
                            <div class="datetime-row">
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-calendar icon-form"></i>
                                    <input type="text" name="fecha_inicio" id="fecha_inicio" class="actividad-input datepicker" required placeholder="Añadir fecha">
                                </div>
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-clock icon-form"></i>
                                    <input type="text" name="hora_inicio" id="hora_inicio" class="actividad-input timepicker" required placeholder="00:00">
                                </div>
                            </div>
                        </div>

                        <div class="schedule-group" style="margin-top: 6px;">
                            <label class="title-label">Finalización *</label>
                            <div class="datetime-row">
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-calendar icon-form"></i>
                                    <input type="text" name="fecha_fin" id="fecha_fin" class="actividad-input datepicker" required placeholder="Añadir fecha">
                                </div>
                                <div class="input-icon-wrapper">
                                    <i class="fa-regular fa-clock icon-form"></i>
                                    <input type="text" name="hora_fin" id="hora_fin" class="actividad-input timepicker" required placeholder="00:00">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                            <button type="submit" class="btn-primary-act">
                                <i class="fa-solid fa-check"></i>
                                Guardar Actividad
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
                        checkboxes.forEach(chk => chk.checked = false);
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
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>