<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// 1. Exigir sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// 2. Validar permisos: Solo SST y Representante
if (!in_array($usuario_rol, ['sst', 'representante'])) {
    header('Location: dashboard.php');
    exit;
}

$current_page = 'trabajadores.php'; // Lo mantenemos activo en el menú de trabajadores

// 3. Obtener el ID de la empresa
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

// ========================================================
// PROCESAMIENTO DE FORMULARIOS (POST)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    // Crear un nuevo grupo
    if ($accion === 'crear_grupo') {
        $nombre_grupo = trim($_POST['nombre_grupo']);
        if (!empty($nombre_grupo) && $empresa_id) {
            $stmt = $conn->prepare("INSERT INTO grupos_personal (empresa_id, nombre) VALUES (?, ?)");
            $stmt->execute([$empresa_id, $nombre_grupo]);
            header("Location: grupos.php?msg=grupo_creado");
            exit;
        }
    }
    
    // Editar nombre del grupo
    if ($accion === 'editar_grupo') {
        $grupo_id = (int)$_POST['grupo_id'];
        $nombre_grupo = trim($_POST['nombre_grupo']);
        if (!empty($nombre_grupo) && $empresa_id) {
            $stmt = $conn->prepare("UPDATE grupos_personal SET nombre = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$nombre_grupo, $grupo_id, $empresa_id]);
            header("Location: grupos.php?msg=grupo_editado");
            exit;
        }
    }

    // Eliminar grupo (Los trabajadores quedarán "Sin grupo" automáticamente por el SET NULL de la BD)
    if ($accion === 'eliminar_grupo') {
        $grupo_id = (int)$_POST['grupo_id'];
        if ($empresa_id) {
            $stmt = $conn->prepare("DELETE FROM grupos_personal WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$grupo_id, $empresa_id]);
            header("Location: grupos.php?msg=grupo_eliminado");
            exit;
        }
    }
    
    // Asignar un trabajador a un grupo
    if ($accion === 'asignar_trabajador') {
        $trabajador_id = (int)$_POST['trabajador_id'];
        $grupo_id = (int)$_POST['grupo_id'];
        if ($empresa_id && $trabajador_id && $grupo_id) {
            $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$grupo_id, $trabajador_id, $empresa_id]);
            header("Location: grupos.php?msg=trabajador_asignado");
            exit;
        }
    }

    // Quitar un trabajador de un grupo (Dejarlo "Sin grupo")
    if ($accion === 'quitar_trabajador') {
        $trabajador_id = (int)$_POST['trabajador_id'];
        if ($empresa_id && $trabajador_id) {
            $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = NULL WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$trabajador_id, $empresa_id]);
            header("Location: grupos.php?msg=trabajador_removido");
            exit;
        }
    }
}

// ========================================================
// OBTENER DATOS PARA LA VISTA
// ========================================================
$grupos = [];
$trabajadores = [];
$trabajadores_sin_grupo = [];
$trabajadores_por_grupo = [];

if ($empresa_id) {
    // 1. Obtener Grupos
    $stmt_g = $conn->prepare("SELECT * FROM grupos_personal WHERE empresa_id = ? ORDER BY nombre ASC");
    $stmt_g->execute([$empresa_id]);
    $grupos = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar el arreglo de trabajadores por grupo
    foreach ($grupos as $g) {
        $trabajadores_por_grupo[$g['id']] = [];
    }

    // 2. Obtener Trabajadores
    $stmt_t = $conn->prepare("SELECT u.id,u.nombre,u.apellido,u.cedula,u.email,u.grupo_id,COALESCE(NULLIF(e.tipo_personal,''),'Sin cargo registrado') AS cargo FROM usuarios u LEFT JOIN encuesta_sociodemografica e ON e.usuario_id=u.id WHERE u.empresa_id = ? AND u.rol = 'trabajador' ORDER BY u.nombre ASC");
    $stmt_t->execute([$empresa_id]);
    $todos_trabajadores = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    // 3. Organizar Trabajadores
    foreach ($todos_trabajadores as $t) {
        if (empty($t['grupo_id'])) {
            $trabajadores_sin_grupo[] = $t;
        } else {
            // Solo si el grupo existe en el array (por seguridad)
            if (isset($trabajadores_por_grupo[$t['grupo_id']])) {
                $trabajadores_por_grupo[$t['grupo_id']][] = $t;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Grupos | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        
        /* FIX GLOBAL SCROLL HORIZONTAL */
        html, body { max-width: 100vw; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }

        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); background-attachment: fixed; margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px 60px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO MEJORADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 20px; flex-wrap: wrap; gap: 16px;}
        .estandar-header-group { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 250px;}
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }
        
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; font-size: 0.85rem; justify-content: center;}
        .btn-back:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8;}

        /* ALERTA SIN GRUPO */
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); width: 100%; box-sizing: border-box;}
        .alert-warning-text { display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.9rem; }
        .alert-warning-text svg { color: #d97706; width: 24px; height: 24px; flex-shrink: 0;}

        /* BARRA DE HERRAMIENTAS DE WORKSPACE */
        .workspace-toolbar {
            background: var(--card); border: 1px solid var(--border); border-radius: 12px;
            padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
        }
        .workspace-title { font-weight: 800; color: var(--blue-dark); font-size: 1.05rem; }

        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.15); white-space: nowrap; justify-content: center;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.25); }
        
        .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem; font-family: inherit; justify-content: center; display: inline-flex;}
        .btn-secondary:hover { background: #f1f5f9; color: #1e293b; }

        /* GRID DE GRUPOS */
        .grupos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        
        .grupo-card {
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column;
            overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; width: 100%; box-sizing: border-box;
        }
        .grupo-card:hover { box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05); border-color: #cbd5e1; }
        
        .grupo-header {
            background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
        }
        .grupo-title { display: flex; align-items: center; gap: 10px; font-weight: 800; color: var(--blue-dark); font-size: 1rem; margin: 0; flex: 1; word-break: break-word;}
        .grupo-title svg { color: var(--primary); flex-shrink: 0;}
        .grupo-count { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; flex-shrink: 0;}
        
        .grupo-actions { display: flex; gap: 6px; flex-shrink: 0;}
        .btn-icon { background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 6px; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-icon:hover { background: #e2e8f0; color: var(--blue-dark); }
        .btn-icon.delete:hover { background: #fee2e2; color: #dc2626; }

        .grupo-body { padding: 20px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
        
        /* PÍLDORAS DE TRABAJADORES */
        .worker-pill {
            background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px;
            display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem;
            color: #334155; font-weight: 600; transition: 0.2s; gap: 8px;
        }
        .worker-pill:hover { background: #e2e8f0; border-color: #cbd5e1; }
        .worker-pill span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;}
        .btn-remove-worker { background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: 0.2s; flex-shrink: 0;}
        .btn-remove-worker:hover { color: #dc2626; background: #fee2e2; }

        .empty-group { text-align: center; color: #94a3b8; font-size: 0.8rem; padding: 20px 0; font-style: italic; }

        .grupo-footer { padding: 16px 20px; border-top: 1px solid #f1f5f9; background: #ffffff; }
        .select-assign { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.8rem; color: var(--text); background: #f8fafc; margin-bottom: 8px; box-sizing: border-box; outline: none;}
        .select-assign:focus { border-color: var(--primary); }
        .btn-assign { width: 100%; background: #fff8f3; border: 1px dashed var(--primary); color: var(--primary2); padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s; }
        .btn-assign:hover { background: var(--primary); color: white; border-style: solid; }

        /* ALERTAS GLOBALES */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        /* =========================================
           MODALES PREMIUM (ESTILO EMPRESA EXACTO)
           ========================================= */
        .modal-premium-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
            display: none; justify-content: center; align-items: center; z-index: 10000;
            opacity: 0; transition: opacity 0.3s ease; padding: 24px; box-sizing: border-box; overflow: hidden;
        }
        .modal-premium-overlay.active { display: flex; opacity: 1; }

        .modal-premium-box {
            background: #ffffff; border-radius: 20px; width: 100%; max-width: 600px; 
            height: auto; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex; flex-direction: column; overflow: hidden; 
        }
        .modal-premium-overlay.active .modal-premium-box { transform: translateY(0) scale(1); }

        .modal-premium-header {
            background: linear-gradient(to right, #f8fafc, #ffffff); padding: 24px 32px; padding-right: 64px;
            border-bottom: 1px solid var(--border); text-align: left; position: relative; display: flex; align-items: center; gap: 16px; flex: 0 0 auto;
        }
        
        .btn-close-premium {
            position: absolute; top: 24px; right: 24px; background: #f1f5f9; border: none; width: 36px; height: 36px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: var(--muted); cursor: pointer; transition: all 0.2s;
        }
        .btn-close-premium:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }

        .modal-premium-icon-top {
            width: 48px; height: 48px; background: rgba(255, 138, 31, 0.1); color: #ff8a1f;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1);
        }
        
        .modal-premium-header-text h3 { margin: 0 0 4px 0; font-size: 1.15rem; color: #1e293b; font-weight: 800; letter-spacing: -0.01em; }
        .modal-premium-header-text p { margin: 0; color: var(--muted); font-size: 0.85rem; line-height: 1.4; }

        .modal-premium-body { padding: 32px; flex: 1 1 auto; min-height: 0; overflow-y: auto; background: #ffffff; }
        .modal-premium-footer { padding: 20px 32px; border-top: 1px solid var(--border); background: #ffffff; display: flex; justify-content: flex-end; gap: 12px; flex: 0 0 auto; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; color: #334155; font-size: 0.8rem; margin-bottom: 8px; }
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: color 0.3s; pointer-events: none; width: 18px; height: 18px; }
        .input-icon-wrapper input { 
            width: 100%; padding: 12px 14px 12px 42px; border: 1px solid #cbd5e1; 
            border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b;
            transition: all 0.3s ease; box-sizing: border-box; background: #ffffff; font-weight: 500;
        }
        .input-icon-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .input-icon-wrapper input:focus + svg { color: var(--primary); }

        /* =======================================
           MÓVIL RESPONSIVE
           ======================================= */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px 40px 14px; }
            
            .header-actions { flex-direction: column; align-items: stretch; gap: 16px; margin-bottom: 20px; border-bottom: none; padding-bottom: 0; }
            /* Botón volver arriba de todo en móvil para mejor UX */
            .btn-back { order: -1; width: 100%; justify-content: center; }
            
            .workspace-toolbar { flex-direction: column; align-items: stretch; text-align: center; gap: 12px; padding: 16px; margin-bottom: 20px;}
            .workspace-title { font-size: 1rem; }
            .workspace-toolbar button { width: 100%; justify-content: center; }
            
            /* Tarjetas y Grid en móvil más compactas */
            .grupos-grid { grid-template-columns: 1fr; gap: 12px; }
            .grupo-card { border-radius: 12px; }
            .grupo-header { padding: 14px 16px; }
            .grupo-body { padding: 16px; gap: 8px; }
            .grupo-footer { padding: 14px 16px; }
            
            /* Modales en móvil */
            .modal-premium-overlay { padding: 0; }
            .modal-premium-box { height: 100%; max-height: 100%; border-radius: 0; }
            .modal-premium-header { padding: 16px 20px; padding-right: 60px; gap: 12px; border-bottom: 1px solid var(--border);}
            .modal-premium-icon-top { width: 40px; height: 40px; border-radius: 10px;}
            .modal-premium-icon-top svg { width: 20px; height: 20px; }
            .modal-premium-header-text h3 { font-size: 1.05rem; }
            .modal-premium-header-text p { font-size: 0.75rem; }
            .btn-close-premium { top: 20px; right: 16px; width: 32px; height: 32px; }
            .modal-premium-body { padding: 20px 16px 30px 16px; }
            .modal-premium-footer { flex-direction: column; padding: 16px; gap: 12px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); }
            .modal-premium-footer button { width: 100%; justify-content: center; }
            .modal-premium-footer .btn-primary { order: 1; padding: 12px; font-size: 0.9rem;}
            .modal-premium-footer .btn-secondary { order: 2; padding: 12px; font-size: 0.9rem;}
        }
    </style>
    <link rel="stylesheet" href="assets/worker-selector.css?v=20260715-1">
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'grupo_creado'): ?>
                    <div class="alert alert-success">¡Grupo creado exitosamente!</div>
                <?php elseif ($_GET['msg'] === 'grupo_editado'): ?>
                    <div class="alert alert-success">Nombre del grupo actualizado.</div>
                <?php elseif ($_GET['msg'] === 'grupo_eliminado'): ?>
                    <div class="alert alert-success">Grupo eliminado. Los trabajadores han quedado sin asignar.</div>
                <?php elseif ($_GET['msg'] === 'trabajador_asignado'): ?>
                    <div class="alert alert-success">Trabajador asignado al grupo correctamente.</div>
                <?php elseif ($_GET['msg'] === 'trabajador_removido'): ?>
                    <div class="alert alert-success">Trabajador removido del grupo.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Panel de Grupos</h1>
                        <p class="estandar-subtitle">Crea áreas o equipos y organiza a tus trabajadores.</p>
                    </div>
                </div>
                <a href="trabajadores.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver a Personal
                </a>
            </div>

            <?php if (count($trabajadores_sin_grupo) > 0): ?>
                <div class="alert-warning">
                    <div class="alert-warning-text">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Tienes <?php echo count($trabajadores_sin_grupo); ?> trabajador(es) sin grupo asignado.
                    </div>
                    <span style="font-size: 0.8rem; color: #92400e;">Usa los selectores debajo de cada grupo para agregarlos.</span>
                </div>
            <?php endif; ?>

            <div class="workspace-toolbar">
                <div class="workspace-title">
                    Mis Grupos Activos (<?php echo count($grupos); ?>)
                </div>
                <button type="button" class="btn-primary" onclick="openModal('modalCrearGrupo')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Grupo
                </button>
            </div>

            <div class="grupos-grid">
                <?php if (empty($grupos)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--muted); background: var(--card); border-radius: var(--radius); border: 1px dashed var(--border);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48" stroke-width="1.5" style="color: #cbd5e1; margin-bottom: 16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <p style="margin: 0; font-weight: 600; color: var(--blue-dark); font-size: 1rem;">Comienza creando tu primer grupo</p>
                        <p style="margin-top: 6px; font-size: 0.85rem;">Ej: Operativos, Administrativos, Brigada de Emergencia.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($grupos as $g): ?>
                        <?php $trabajadores_del_grupo = $trabajadores_por_grupo[$g['id']] ?? []; ?>
                        <div class="grupo-card">
                            <div class="grupo-header">
                                <h3 class="grupo-title">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                    <?php echo htmlspecialchars($g['nombre']); ?>
                                    <span class="grupo-count"><?php echo count($trabajadores_del_grupo); ?></span>
                                </h3>
                                <div class="grupo-actions">
                                    <button type="button" class="btn-icon" title="Editar Nombre" onclick="abrirEditarModal(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars($g['nombre'], ENT_QUOTES); ?>')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <form action="grupos.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este grupo? Los trabajadores quedarán sin grupo asignado.');">
                                        <input type="hidden" name="accion" value="eliminar_grupo">
                                        <input type="hidden" name="grupo_id" value="<?php echo $g['id']; ?>">
                                        <button type="submit" class="btn-icon delete" title="Eliminar Grupo">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="grupo-body">
                                <?php if (empty($trabajadores_del_grupo)): ?>
                                    <div class="empty-group">Este grupo está vacío.</div>
                                <?php else: ?>
                                    <?php foreach ($trabajadores_del_grupo as $trab): ?>
                                        <div class="worker-pill">
                                            <span><?php echo htmlspecialchars($trab['nombre'] . ' ' . $trab['apellido']); ?></span>
                                            <form action="grupos.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="quitar_trabajador">
                                                <input type="hidden" name="trabajador_id" value="<?php echo $trab['id']; ?>">
                                                <button type="submit" class="btn-remove-worker" title="Quitar del grupo">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="grupo-footer">
                                <?php if (empty($trabajadores_sin_grupo)): ?>
                                    <span style="font-size: 0.75rem; color: #94a3b8; display: block; text-align: center;">Todos los trabajadores ya tienen un grupo asignado.</span>
                                <?php else: ?>
                                    <form action="grupos.php" method="POST">
                                        <input type="hidden" name="accion" value="asignar_trabajador">
                                        <input type="hidden" name="grupo_id" value="<?php echo $g['id']; ?>">
                                        <select name="trabajador_id" class="select-assign" data-worker-search data-worker-search-placeholder="Buscar trabajador sin grupo" required>
                                            <option value="">Seleccionar de "Sin Grupo"...</option>
                                            <?php foreach ($trabajadores_sin_grupo as $tsg): ?>
                                                <option value="<?php echo $tsg['id']; ?>" data-nombre="<?php echo htmlspecialchars(trim($tsg['nombre'].' '.$tsg['apellido'])); ?>" data-cedula="<?php echo htmlspecialchars($tsg['cedula'] ?? ''); ?>" data-email="<?php echo htmlspecialchars($tsg['email'] ?? ''); ?>" data-cargo="<?php echo htmlspecialchars($tsg['cargo'] ?? ''); ?>"><?php echo htmlspecialchars(trim($tsg['nombre'].' '.$tsg['apellido']).' · C.C. '.($tsg['cedula'] ?? '')); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-assign">+ Agregar al grupo</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div class="modal-premium-overlay" id="modalCrearGrupo">
        <form method="POST" class="modal-premium-box">
            <input type="hidden" name="accion" value="crear_grupo">
            
            <div class="modal-premium-header">
                <div class="modal-premium-icon-top">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="modal-premium-header-text">
                    <h3>Crear Nuevo Grupo</h3>
                    <p>Organiza a tu personal por áreas o especialidades.</p>
                </div>
                <button type="button" class="btn-close-premium" onclick="closeModal('modalCrearGrupo')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="modal-premium-body">
                <div class="form-group">
                    <label>Nombre del Grupo *</label>
                    <div class="input-icon-wrapper">
                        <input type="text" name="nombre_grupo" required placeholder="Ej. Equipo de Bodega">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
            </div>
            
            <div class="modal-premium-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('modalCrearGrupo')">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Grupo</button>
            </div>
        </form>
    </div>

    <div class="modal-premium-overlay" id="modalEditarGrupo">
        <form method="POST" class="modal-premium-box">
            <input type="hidden" name="accion" value="editar_grupo">
            <input type="hidden" name="grupo_id" id="input_edit_grupo_id">
            
            <div class="modal-premium-header">
                <div class="modal-premium-icon-top">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div class="modal-premium-header-text">
                    <h3>Editar Nombre del Grupo</h3>
                    <p>Actualiza la información de esta carpeta de trabajadores.</p>
                </div>
                <button type="button" class="btn-close-premium" onclick="closeModal('modalEditarGrupo')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="modal-premium-body">
                <div class="form-group">
                    <label>Nombre del Grupo *</label>
                    <div class="input-icon-wrapper">
                        <input type="text" name="nombre_grupo" id="input_edit_nombre_grupo" required>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
            </div>
            
            <div class="modal-premium-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('modalEditarGrupo')">Cancelar</button>
                <button type="submit" class="btn-primary">Actualizar Grupo</button>
            </div>
        </form>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }

        function abrirEditarModal(id, nombre_actual) {
            document.getElementById('input_edit_grupo_id').value = id;
            document.getElementById('input_edit_nombre_grupo').value = nombre_actual;
            openModal('modalEditarGrupo');
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Cerrar modales si se hace clic afuera de la caja
            document.querySelectorAll('.modal-premium-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            // Ocultar Alertas Automáticamente
            const alerts = document.querySelectorAll('.alert, .alert-success, .alert-danger');
            if (alerts.length > 0) {
                setTimeout(() => {
                    alerts.forEach(a => {
                        a.style.transition = 'opacity 0.5s ease';
                        a.style.opacity = '0';
                        setTimeout(() => a.remove(), 500);
                    });
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: newUrl}, '', newUrl);
                }, 4000);
            }
        });
    </script>
    <script src="assets/worker-selector.js?v=20260715-1"></script>
</body>
</html>
