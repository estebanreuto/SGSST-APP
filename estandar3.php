<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Validar permisos (Trabajador no debería estar aquí directamente administrando)
if ($usuario_rol === 'trabajador') {
    header('Location: dashboard.php');
    exit;
}

// Obtener el ID de la empresa del usuario actual
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

// 1. Obtener los grupos de la empresa para el Modal
$grupos = [];
if ($empresa_id) {
    try {
        $stmt_g = $conn->prepare("SELECT id, nombre FROM grupos_personal WHERE empresa_id = ? ORDER BY nombre ASC");
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
        $stmt_ta = $conn->prepare("SELECT id, nombre, apellido, cedula FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador' AND activo = 1 ORDER BY nombre ASC, apellido ASC");
        $stmt_ta->execute([$empresa_id]);
        $trabajadores_activos = $stmt_ta->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $trabajadores_activos = [];
    }
}

// Aquí luego agregaremos las consultas a la base de datos para el Estándar 3
$current_page = 'estandar3.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 3 | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO PREMIUM MINIMALISTA */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
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
        .std-num-marker { color: var(--primary); font-weight: 800; margin-right: 4px; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }
        
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }

        /* BARRA DE HERRAMIENTAS (WORKSPACE) */
        .workspace-toolbar {
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); flex-wrap: wrap; gap: 16px;
        }

        .search-box { position: relative; flex: 1; max-width: 350px; }
        .search-box input {
            width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-family: inherit; font-size: 0.85rem; color: var(--text); background: #f8fafc;
            transition: all 0.3s ease; box-sizing: border-box;
        }
        .search-box input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }

        /* BOTONES GLOBALES */
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; 
            padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer; 
            transition: all 0.2s ease; display: inline-flex; justify-content: center; align-items: center; 
            gap: 6px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.15); white-space: nowrap;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.25); }

        .btn-secondary { 
            background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; 
            border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; 
            font-size: 0.8rem; font-family: inherit; display: inline-flex; justify-content: center; align-items: center; 
        }
        .btn-secondary:hover { background: #f1f5f9; color: #1e293b; }

        /* MÓVIL RESPONSIVE */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            .btn-back { order: -1; width: max-content; align-self: flex-start; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; margin: 0 0 4px 0; }
            .estandar-header-group { display: flex; flex-direction: row; align-items: flex-start; text-align: left; gap: 12px; width: 100%; }
            .icon-box-std { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; margin-top: 2px; }
            .icon-box-std svg { width: 20px; height: 20px; }
            .estandar-title { font-size: 1.05rem; line-height: 1.3; }
            .estandar-subtitle { font-size: 0.75rem; line-height: 1.4; margin-top: 4px; }
            .workspace-toolbar { flex-direction: column; align-items: stretch; padding: 16px; }
            .search-box { max-width: 100%; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
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
                <a href="dashboard.php?std=3" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Dashboard
                </a>
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
                        <button type="button" class="btn-primary" id="btnCrearActividad">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nueva Actividad
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <?php include 'components/modal_nueva_actividad.php'; ?>

</body>
</html>