<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// 1. Exigir sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// 2. Validar permisos: Solo SST y Representante pueden gestionar trabajadores
if (!in_array($usuario_rol, ['sst', 'representante'])) {
    header('Location: dashboard.php');
    exit;
}

$current_page = 'trabajadores.php';

// 3. Obtener el ID de la empresa del usuario actual
$stmt_emp = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt_emp->execute([$usuario_id]);
$empresa_id = $stmt_emp->fetchColumn();

// ========================================================
// PROCESAMIENTO DE FORMULARIOS (ACCIONES MASIVAS E INDIVIDUALES)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    // Crear un nuevo grupo
    if ($accion === 'crear_grupo') {
        $nombre_grupo = trim($_POST['nombre_grupo']);
        if (!empty($nombre_grupo) && $empresa_id) {
            $stmt = $conn->prepare("INSERT INTO grupos_personal (empresa_id, nombre) VALUES (?, ?)");
            $stmt->execute([$empresa_id, $nombre_grupo]);
            header("Location: trabajadores.php?msg=grupo_creado");
            exit;
        }
    }
    
    // Asignar trabajador a un grupo (Individual)
    if ($accion === 'asignar_grupo') {
        $trabajador_id = (int)$_POST['trabajador_id'];
        $grupo_id = (!empty($_POST['grupo_id']) && $_POST['grupo_id'] !== '0') ? (int)$_POST['grupo_id'] : null;
        if ($empresa_id) {
            $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$grupo_id, $trabajador_id, $empresa_id]);
            header("Location: trabajadores.php?msg=grupo_asignado");
            exit;
        }
    }

    // ===================================
    // ASIGNACIÓN MASIVA AVANZADA
    // ===================================
    if ($accion === 'asignar_grupo_masivo') {
        $ids_str = $_POST['trabajadores_ids'] ?? '';
        $grupo_id = (isset($_POST['grupo_id']) && $_POST['grupo_id'] !== '0') ? (int)$_POST['grupo_id'] : null;
        
        if (!empty($ids_str) && $empresa_id) {
            $ids = explode(',', $ids_str);
            $ids = array_map('intval', $ids); 
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$grupo_id], $ids, [$empresa_id]);
            
            $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = ? WHERE id IN ($placeholders) AND empresa_id = ?");
            $stmt->execute($params);
            header("Location: trabajadores.php?msg=grupo_asignado_masivo");
            exit;
        }
    }

    // ===================================
    // ELIMINACIÓN MASIVA
    // ===================================
    if ($accion === 'eliminar_masivo') {
        $ids_str = $_POST['trabajadores_ids'] ?? '';
        if (!empty($ids_str) && $empresa_id) {
            $ids = explode(',', $ids_str);
            $ids = array_map('intval', $ids);
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$empresa_id]);
            
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id IN ($placeholders) AND empresa_id = ?");
            $stmt->execute($params);
            header("Location: trabajadores.php?msg=trabajadores_eliminados");
            exit;
        }
    }
}

// ========================================================
// CONSULTAS PARA LA VISTA Y CONTEO DE GRUPOS
// ========================================================
$trabajadores = [];
$grupos = [];
$conteo_grupos = ['sin_grupo' => 0];

// Variables para las tarjetas de resumen
$total_trabajadores = 0;
$trabajadores_activos = 0;
$total_grupos = 0;

if ($empresa_id) {
    try {
        $stmt_g = $conn->prepare("SELECT * FROM grupos_personal WHERE empresa_id = ? ORDER BY nombre ASC");
        $stmt_g->execute([$empresa_id]);
        $grupos = $stmt_g->fetchAll(PDO::FETCH_ASSOC);
        $total_grupos = count($grupos);

        $stmt_trab = $conn->prepare("
            SELECT u.id, u.nombre, u.apellido, u.cedula, u.email, u.telefono, u.activo, u.fecha_registro, u.grupo_id, g.nombre as grupo_nombre 
            FROM usuarios u 
            LEFT JOIN grupos_personal g ON u.grupo_id = g.id
            WHERE u.empresa_id = ? AND u.rol = 'trabajador' 
            ORDER BY u.nombre ASC, u.apellido ASC
        ");
        $stmt_trab->execute([$empresa_id]);
        $trabajadores = $stmt_trab->fetchAll(PDO::FETCH_ASSOC);
        $total_trabajadores = count($trabajadores);

        foreach ($trabajadores as $t) {
            if ($t['activo'] == 1) {
                $trabajadores_activos++;
            }

            if (empty($t['grupo_id'])) {
                $conteo_grupos['sin_grupo']++;
            } else {
                if (!isset($conteo_grupos[$t['grupo_id']])) {
                    $conteo_grupos[$t['grupo_id']] = 0;
                }
                $conteo_grupos[$t['grupo_id']]++;
            }
        }

    } catch (PDOException $e) {
        $error_bd = "Error al cargar los datos. Verifica que ejecutaste el SQL en la base de datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; --blue-dark: #1e3a8a; }
        
        /* FIX GLOBAL SCROLL HORIZONTAL */
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg2);
            background-image: linear-gradient(180deg, var(--bg1), var(--bg2)); 
            background-attachment: fixed; 
            margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; 
        }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        
        .content-area { padding: 32px 40px 60px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; }
        
        /* ENCABEZADO Y BOTONES */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 250px;}
        .icon-box-std { width: 44px; height: 44px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.05); }
        .icon-box-std svg { width: 22px; height: 22px; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4; }

        .header-buttons { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn-outline-primary { background: #fff8f3; border: 1px solid var(--primary); color: var(--primary2); padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; text-decoration: none; }
        .btn-outline-primary:hover { background: var(--primary); color: white; }
        .btn-disabled { opacity: 0.5 !important; pointer-events: none !important; cursor: not-allowed !important; background: #f1f5f9 !important; color: #94a3b8 !important; border-color: #cbd5e1 !important; box-shadow: none !important; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.15); white-space: nowrap; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.25); }

        /* TARJETAS DE RESUMEN (ESTILO MARCA DE AGUA) */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .summary-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 20px; display: flex; flex-direction: column; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); position: relative; overflow: hidden; }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); border-color: var(--primary2); }
        .summary-bg-icon { position: absolute; bottom: -15px; right: -15px; width: 90px; height: 90px; color: var(--primary); opacity: 0.15; z-index: 1; transform: rotate(-15deg); pointer-events: none; }
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .summary-icon-box { width: 32px; height: 32px; background: rgba(255, 138, 31, 0.1); color: var(--primary2); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .summary-icon-box svg { width: 16px; height: 16px; }
        .summary-value { font-size: 1.5rem; font-weight: 800; color: var(--blue-dark); margin: 0; line-height: 1; }
        .summary-title { font-size: 0.85rem; font-weight: 700; color: var(--text); margin: 0 0 4px 0; }
        .summary-desc { color: var(--muted); font-size: 0.75rem; margin: 0; line-height: 1.3; }

        /* BARRA DE FILTROS ELEGANTE (PC) */
        .filters-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 8px 16px; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .filter-search { flex: 1; position: relative; display: flex; align-items: center; }
        .filter-search input { width: 100%; border: none; background: transparent; padding: 10px 10px 10px 32px; font-size: 0.85rem; color: var(--text); outline: none; font-family: inherit; }
        .filter-search input::placeholder { color: #94a3b8; }
        .filter-search svg.icon-search { position: absolute; left: 8px; color: #94a3b8; width: 18px; height: 18px; pointer-events: none; }
        .filter-divider { width: 1px; height: 30px; background: var(--border); }
        
        .filter-item { display: flex; align-items: center; gap: 10px; }
        .filter-item label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
        
        .select-wrapper { position: relative; width: 100%; display: flex; align-items: center; }
        .mobile-only-icon { display: none; } /* Oculto en PC */

        .simple-select { border: 1px solid transparent; background-color: transparent; padding: 8px 32px 8px 12px; border-radius: 8px; font-size: 0.85rem; color: #1e293b; font-weight: 600; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; outline: none; min-width: 180px; transition: all 0.2s; font-family: inherit; }
        .simple-select:hover, .simple-select:focus { border-color: #cbd5e1; background-color: #f8fafc; }

        .spinner-loader { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; display: none; }
        @keyframes spin { 100% { transform: translateY(-50%) rotate(360deg); } }

        /* VISTAS RESPONSIVAS AUTOMÁTICAS */
        .view-lista { display: block; animation: fadeIn 0.3s ease; }
        .view-tarjetas { display: none; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* CHECKBOX PERSONALIZADO */
        .custom-checkbox { appearance: none; width: 18px; height: 18px; border: 2px solid #cbd5e1; border-radius: 5px; cursor: pointer; position: relative; transition: all 0.2s; background: #ffffff; display: flex; align-items: center; justify-content: center; margin: 0; }
        .custom-checkbox:checked { background: var(--primary); border-color: var(--primary); }
        .custom-checkbox:checked::after { content: ''; position: absolute; left: 5px; top: 2px; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }

        /* BARRA DE ACCIONES MASIVAS (PC) - ESTÁTICA ARRIBA DE LA TABLA */
        .bulk-action-bar {
            background: #1e293b; 
            border-radius: 12px;
            padding: 16px 24px;
            display: none; 
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.3s ease;
        }
        .bulk-action-bar.active { display: flex; }
        
        .bulk-left { display: flex; align-items: center; gap: 12px; }
        .bulk-badge { background: var(--primary); color: white; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; }
        .bulk-text { color: #f8fafc; font-weight: 500; font-size: 0.9rem; margin-right: 8px;}
        
        .bulk-right { display: flex; align-items: center; gap: 12px; width: auto;}
        .form-eliminar-masivo { display: inline; margin: 0; padding: 0; }
        .btn-bulk { cursor: pointer; font-weight: 600; font-size: 0.85rem; padding: 9px 16px; border-radius: 8px; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; border: none; }
        
        .btn-bulk-text { display: inline; } /* Texto visible siempre en PC */
        
        .btn-bulk-assign { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .btn-bulk-assign:hover { background: #38bdf8; color: #0f172a; }
        .btn-bulk-danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
        .btn-bulk-danger:hover { background: #ef4444; color: white; }
        .btn-bulk-cancel { background: transparent; color: #94a3b8; border: 1px solid #475569; }
        .btn-bulk-cancel:hover { background: #334155; color: white; }

        /* TABLAS Y TARJETAS TRABAJADORES */
        .table-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); overflow: hidden; }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .custom-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 900px; }
        .custom-table th { background: #f8fafc; padding: 14px 20px; font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #cbd5e1; }
        .custom-table td { padding: 16px 20px; font-size: 0.85rem; color: var(--text); border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .custom-table tr.selected td { background: #fff8f3; }
        .custom-table tr:last-child td { border-bottom: none; }
        .custom-table tr:hover td { background: #f8fafc; }

        .workers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .worker-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; display: flex; flex-direction: column; gap: 16px; position: relative; }
        .worker-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05); border-color: #cbd5e1; }
        .worker-card.selected { border-color: var(--primary); background: #fff8f3; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed var(--border); padding-bottom: 12px; }
        .card-body { display: flex; flex-direction: column; gap: 8px; flex: 1; overflow: hidden; }
        .card-footer { display: flex; gap: 8px; justify-content: flex-end; padding-top: 12px; border-top: 1px dashed var(--border); }
        .info-row { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--muted); width: 100%; }
        .info-row svg { color: #94a3b8; flex-shrink: 0; }
        .info-row span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: calc(100% - 24px); }

        .user-info-td { display: flex; align-items: center; gap: 12px; width: 100%; overflow: hidden; }
        .user-avatar { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, var(--bg1), #dbe3ec); color: var(--blue-dark); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; }
        .user-info-text-box { display: flex; flex-direction: column; overflow: hidden; flex: 1; }
        .user-name-text { font-weight: 700; color: var(--blue-dark); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-cc-text { font-size: 0.75rem; color: var(--muted); }

        .status-badge { font-size: 0.65rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; display: inline-block; text-transform: uppercase; letter-spacing: 0.05em; height: fit-content; white-space: nowrap;}
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .grupo-badge { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .grupo-missing { background: #fef08a; color: #854d0e; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; border: 1px dashed #eab308; }
        
        .btn-asignar-mini { background: transparent; border: none; color: var(--primary2); font-size: 0.75rem; font-weight: 700; cursor: pointer; text-decoration: none; padding: 0; margin-top: 4px; display: inline-block; transition: color 0.2s;}
        .btn-asignar-mini:hover { color: var(--primary); text-decoration: underline; }

        .actions-td { display: flex; gap: 8px; }
        .btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-view { background: #e0f2fe; color: #0284c7; }
        .btn-view:hover { background: #bae6fd; }
        .btn-edit { background: #fef08a; color: #854d0e; }
        .btn-edit:hover { background: #fde047; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fca5a5; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state svg { color: #cbd5e1; margin-bottom: 16px; width: 64px; height: 64px; }
        .empty-state h3 { margin: 0 0 8px 0; color: var(--blue-dark); font-size: 1.1rem; }
        .empty-state p { margin: 0; font-size: 0.85rem; max-width: 400px; margin: 0 auto; line-height: 1.5; }

        /* MODALES PREMIUM */
        .modal-premium-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 24px; box-sizing: border-box; overflow: hidden; }
        .modal-premium-overlay.active { display: flex; opacity: 1; }
        .modal-premium-box { background: #ffffff; border-radius: 20px; width: 100%; max-width: 750px; height: auto; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; flex-direction: column; overflow: visible; }
        .modal-premium-overlay.active .modal-premium-box { transform: translateY(0) scale(1); }
        .modal-premium-header { background: linear-gradient(to right, #f8fafc, #ffffff); padding: 24px 32px; padding-right: 64px; border-bottom: 1px solid var(--border); text-align: left; position: relative; display: flex; align-items: center; gap: 16px; flex: 0 0 auto; border-top-left-radius: 20px; border-top-right-radius: 20px; }
        .btn-close-premium { position: absolute; top: 24px; right: 24px; background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; transition: all 0.2s; }
        .btn-close-premium:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
        .modal-premium-icon-top { width: 48px; height: 48px; background: rgba(255, 138, 31, 0.1); color: #ff8a1f; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1); }
        .modal-premium-icon-top svg { width: 24px; height: 24px; }
        .modal-premium-header-text h3 { margin: 0 0 4px 0; font-size: 1.15rem; color: #1e293b; font-weight: 800; letter-spacing: -0.01em; }
        .modal-premium-header-text p { margin: 0; color: var(--muted); font-size: 0.85rem; line-height: 1.4; }
        .modal-premium-body { padding: 32px 32px 24px 32px; flex: 1 1 auto; overflow-y: auto; background: #ffffff; display: flex; flex-direction: column; gap: 16px; }
        .modal-premium-footer { padding: 20px 32px; border-top: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px; flex: 0 0 auto; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; align-items: center; }
        .modal-premium-body::-webkit-scrollbar { width: 6px; }
        .modal-premium-body::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
        .modal-premium-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .input-icon-wrapper-modal { position: relative; width: 100%; }
        .input-icon-wrapper-modal > svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 18px; height: 18px; z-index: 10; pointer-events: none;}
        .input-icon-wrapper-modal input { width: 100%; padding: 12px 14px 12px 42px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b; transition: all 0.3s ease; box-sizing: border-box; background: #ffffff; font-weight: 500; }
        .input-icon-wrapper-modal input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }

        .modal-groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; max-height: 350px; overflow-y: auto; padding-right: 8px; padding-top: 8px; padding-bottom: 12px; }
        .modal-groups-grid::-webkit-scrollbar { width: 6px; }
        .modal-groups-grid::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
        .modal-groups-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .mini-group-card { background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: all 0.25s ease; margin: 0; user-select: none; position: relative; overflow: hidden; }
        .mini-group-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 15px rgba(0,0,0,0.03); transform: translateY(-2px); }
        .mini-group-card input[type="radio"] { display: none; }
        .mini-group-card.selected { border-color: var(--primary); background: #fffaf5; box-shadow: 0 4px 15px rgba(255, 138, 31, 0.1); }
        .mini-group-card.selected .group-icon { background: var(--primary); color: white; }
        .group-icon { width: 42px; height: 42px; background: #f1f5f9; color: #64748b; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.25s; }
        .group-icon svg { width: 22px; height: 22px; }
        .group-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .g-name { font-weight: 700; color: var(--blue-dark); font-size: 0.95rem; margin:0; line-height: 1.2; }
        .g-count { font-size: 0.75rem; color: var(--muted); font-weight: 500; }
        .check-indicator { width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: transparent; display: flex; align-items: center; justify-content: center; transition: all 0.25s; flex-shrink: 0; border: 2px solid transparent; box-sizing: border-box; }
        .mini-group-card.selected .check-indicator { background: var(--primary); color: white; transform: scale(1.1); border-color: #fffaf5; }
        .check-indicator svg { width: 12px; height: 12px; }

        /* =======================================
           MÓVIL RESPONSIVE
           ======================================= */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px 40px 14px; }
            
            .header-actions { flex-direction: column; align-items: stretch; gap: 16px; margin-bottom: 24px; }
            
            /* Botones del encabezado: Fila dividida en 2 equitativamente con TEXTO (NO ICONOS SOLOS) */
            .header-buttons { width: 100%; display: flex; flex-direction: row; gap: 8px; justify-content: space-between; }
            .header-buttons a, .header-buttons button { 
                flex: 1; justify-content: center; padding: 12px 6px; font-size: 0.8rem; height: auto;
            }

            /* Filtros en móvil: Buscador a la izquierda, Íconos a la derecha en la misma línea */
            .filters-card { 
                display: flex; 
                flex-direction: row; 
                padding: 12px; 
                gap: 8px; 
                border-radius: 12px; 
                background: var(--card);
            }
            .filter-divider { display: none; }
            
            .filter-search { flex: 1; height: 44px; }
            .filter-search input { 
                padding: 0 12px 0 36px !important; 
                height: 100% !important; 
                background: #f8fafc !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 12px !important; 
            }
            
            .filter-item { width: 44px; height: 44px; margin: 0; flex-shrink: 0; }
            .filter-item label { display: none; } /* Ocultamos textos "Grupo" y "Estado" */
            
            .select-wrapper { 
                width: 100%; height: 100%; background: #f8fafc; border: 1px solid #cbd5e1; 
                border-radius: 10px; display: flex; align-items: center; justify-content: center; position: relative; 
            }
            .select-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); background: #ffffff; }
            .mobile-only-icon { display: block; width: 20px; height: 20px; color: #64748b; pointer-events: none; }
            .simple-select { 
                position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                opacity: 0.01; padding: 0; min-width: 0; border: none; 
                -webkit-appearance: none; appearance: none; cursor: pointer; z-index: 10;
                background-image: none !important;
            }

            /* BARRA DE ACCIONES MASIVAS - ESTILO MÓVIL (ESTÁTICA, ABAJO DE LOS FILTROS) */
            .bulk-action-bar { 
                position: static !important;
                transform: none !important;
                flex-direction: column !important; 
                padding: 16px !important; 
                gap: 12px !important;
                background: #1e293b !important;
                border-radius: 16px !important;
                width: 100% !important;
                max-width: 100% !important;
                margin-bottom: 20px !important;
            }
            
            .bulk-action-bar .bulk-left { width: 100%; justify-content: center; padding-bottom: 4px; display: flex; align-items: center;}
            /* Ocultar el texto largo, solo dejamos el badge grande */
            #bulkTextPC { display: none !important; }
            .bulk-badge { font-size: 1rem; padding: 8px 20px; border-radius: 12px; margin: 0;}
            
            /* Cuadrícula para botones de acción masiva en móvil en una sola fila + Cancelar abajo */
            .mobile-bulk-actions {
                display: flex !important;
                flex-wrap: wrap;
                width: 100%;
                gap: 10px;
                align-items: center;
                justify-content: space-between;
            }
            
            /* Asignar y Eliminar 50/50 y SIN TEXTO */
            .btn-bulk-assign { flex: 1; justify-content: center; padding: 12px; border-radius: 12px; margin: 0;}
            .form-eliminar-masivo { flex: 1; display: flex !important; margin: 0; padding: 0; }
            .form-eliminar-masivo .btn-bulk { width: 100%; justify-content: center; padding: 12px; border-radius: 12px; margin: 0;}
            
            .btn-bulk-assign .btn-bulk-text, 
            .form-eliminar-masivo .btn-bulk-text { display: none !important; }
            
            /* Cancelar ocupa 100% en la fila de abajo y muestra su texto */
            .btn-bulk-cancel { width: 100%; justify-content: center; padding: 12px; border-radius: 12px; margin-top: 4px; font-size: 0.95rem; }
            .btn-bulk-cancel .btn-bulk-text { display: inline !important; }
            
            /* Ajustes para evitar overflow (Scroll horizontal) en las tarjetas */
            .worker-card { padding: 16px; gap: 12px; }
            .user-info-td { width: 100%; overflow: hidden; }
            .user-info-text-box { max-width: calc(100% - 48px); }
            
            .modal-premium-overlay { padding: 0; }
            .modal-premium-box { height: 100%; max-height: 100%; border-radius: 0; }
            .modal-premium-header { padding: 16px 20px; padding-right: 60px; gap: 12px; }
            .modal-premium-icon-top { width: 40px; height: 40px; border-radius: 10px;}
            .modal-premium-icon-top svg { width: 20px; height: 20px; }
            .modal-premium-header-text h3 { font-size: 1.05rem; }
            .modal-premium-header-text p { font-size: 0.75rem; }
            .btn-close-premium { top: 20px; right: 16px; width: 32px; height: 32px; }
            .modal-premium-body { padding: 20px 16px 30px 16px; }
            .modal-premium-footer { flex-direction: column; padding: 16px; gap: 12px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); }
            .modal-premium-footer button { width: 100%; justify-content: center; }
            
            .modal-groups-grid { grid-template-columns: 1fr; }
            
            .view-lista { display: none !important; }
            .view-tarjetas { display: block !important; }
            .workers-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        }
        
        @media (max-width: 480px) {
            .workers-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if(isset($error_bd)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_bd); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'grupo_creado'): ?>
                    <div class="alert alert-success">¡Grupo de trabajadores creado exitosamente!</div>
                <?php elseif ($_GET['msg'] === 'grupo_asignado'): ?>
                    <div class="alert alert-success">Trabajador asignado al grupo correctamente.</div>
                <?php elseif ($_GET['msg'] === 'grupo_asignado_masivo'): ?>
                    <div class="alert alert-success">¡Trabajadores asignados al grupo masivamente!</div>
                <?php elseif ($_GET['msg'] === 'trabajadores_eliminados'): ?>
                    <div class="alert alert-success">Los trabajadores seleccionados fueron eliminados.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="header-actions" style="margin-bottom: 24px; padding-bottom: 0; border-bottom: none;">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Gestión de Personal</h1>
                        <p class="estandar-subtitle">Administra los trabajadores y agrúpalos por áreas o actividades.</p>
                    </div>
                </div>
                
                <div class="header-buttons">
                    <a href="grupos.php" class="btn-outline-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span class="btn-text">Panel de Grupos</span>
                    </a>

                    <button type="button" class="btn-primary" onclick="alert('Pronto habilitaremos el formulario para registrar un trabajador directamente desde aquí.')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="btn-text">Añadir Trabajador</span>
                    </button>
                </div>
            </div>

            <!-- TARJETAS DE RESUMEN -->
            <div class="summary-cards-grid">
                <div class="summary-card">
                    <svg class="summary-bg-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h2 class="summary-value"><?php echo $total_trabajadores; ?></h2>
                        </div>
                        <h3 class="summary-title">Total Trabajadores</h3>
                        <p class="summary-desc">Personal registrado en la empresa.</p>
                    </div>
                </div>

                <div class="summary-card">
                    <svg class="summary-bg-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box" style="background: rgba(34, 197, 94, 0.1); color: #16a34a;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h2 class="summary-value" style="color: #16a34a;"><?php echo $trabajadores_activos; ?></h2>
                        </div>
                        <h3 class="summary-title">Activos</h3>
                        <p class="summary-desc">Trabajadores actualmente operativos.</p>
                    </div>
                </div>

                <div class="summary-card">
                    <svg class="summary-bg-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box" style="background: rgba(14, 165, 233, 0.1); color: #0284c7;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <h2 class="summary-value" style="color: #0284c7;"><?php echo $total_grupos; ?></h2>
                        </div>
                        <h3 class="summary-title">Grupos Creados</h3>
                        <p class="summary-desc">Áreas o equipos de trabajo registrados.</p>
                    </div>
                </div>
            </div>

            <!-- BARRA DE FILTROS ELEGANTE -->
            <div class="filters-card">
                <div class="filter-search">
                    <svg class="icon-search" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, cédula o email...">
                    <div class="spinner-loader" id="filterLoader"></div>
                </div>
                
                <div class="filter-divider"></div>
                
                <div class="filter-item">
                    <label>Grupo:</label>
                    <div class="select-wrapper">
                        <!-- Icono solo visible en móvil -->
                        <svg class="mobile-only-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <select id="filterGroup" class="simple-select">
                            <option value="all">Todos los grupos</option>
                            <option value="assigned">Con grupo asignado</option>
                            <option value="unassigned">Sin grupo</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-divider"></div>
                
                <div class="filter-item">
                    <label>Estado:</label>
                    <div class="select-wrapper">
                        <!-- Icono solo visible en móvil -->
                        <svg class="mobile-only-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <select id="filterStatus" class="simple-select">
                            <option value="all">Todos los estados</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BARRA DE ACCIONES MASIVAS INLINE (Aparece justo arriba de la tabla/tarjetas) -->
            <div class="bulk-action-bar" id="bulkActionBar">
                <div class="bulk-left">
                    <span class="bulk-badge" id="bulkCount">0 seleccionados</span>
                    <span class="bulk-text" id="bulkTextPC">¿Qué deseas hacer con los trabajadores seleccionados?</span>
                </div>
                <div class="bulk-right mobile-bulk-actions">
                    <button type="button" class="btn-bulk btn-bulk-assign" onclick="verificarYabrirModalMasivo()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="btn-bulk-text">Asignar</span>
                    </button>
                    <form action="trabajadores.php" method="POST" id="formEliminarMasivo" class="form-eliminar-masivo" onsubmit="return confirm('¿Estás SEGURO de eliminar a TODOS los trabajadores seleccionados?');">
                        <input type="hidden" name="accion" value="eliminar_masivo">
                        <input type="hidden" name="trabajadores_ids" id="input_eliminar_masivo_ids">
                        <button type="submit" class="btn-bulk btn-bulk-danger">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span class="btn-bulk-text">Eliminar</span>
                        </button>
                    </form>
                    <button type="button" class="btn-bulk btn-bulk-cancel" onclick="limpiarSeleccion()">
                        <span class="btn-bulk-text">Cancelar</span>
                    </button>
                </div>
            </div>

            <?php if (empty($trabajadores)): ?>
                <div class="table-card" style="padding: 20px;">
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3>No hay trabajadores registrados</h3>
                        <p>Actualmente tu empresa no tiene trabajadores vinculados. Comparte tu enlace de registro con el personal.</p>
                    </div>
                </div>
            <?php else: ?>

                <!-- VISTA DE LISTA (Se muestra solo en Escritorio) -->
                <div class="view-lista">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;">
                                            <input type="checkbox" class="custom-checkbox" id="selectAllTable">
                                        </th>
                                        <th>Trabajador</th>
                                        <th>Contacto</th>
                                        <th>Grupo Asignado</th>
                                        <th>Estado</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trabajadores as $t): ?>
                                        <?php 
                                            $status_val = $t['activo'] == 1 ? '1' : '0';
                                            $group_val = empty($t['grupo_id']) ? 'unassigned' : 'assigned';
                                        ?>
                                        <tr class="worker-item row-<?php echo $t['id']; ?>" 
                                            data-text="<?php echo strtolower($t['nombre'] . ' ' . $t['apellido'] . ' ' . $t['cedula'] . ' ' . $t['email'] . ' ' . ($t['grupo_nombre'] ?? 'sin grupo')); ?>"
                                            data-status="<?php echo $status_val; ?>"
                                            data-has-group="<?php echo $group_val; ?>">
                                            
                                            <td style="text-align: center;">
                                                <input type="checkbox" class="custom-checkbox worker-checkbox" value="<?php echo $t['id']; ?>">
                                            </td>
                                            <td>
                                                <div class="user-info-td">
                                                    <div class="user-avatar"><?php echo strtoupper(substr($t['nombre'], 0, 1)); ?></div>
                                                    <div class="user-info-text-box">
                                                        <span class="user-name-text"><?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido']); ?></span>
                                                        <span class="user-cc-text">C.C. <?php echo htmlspecialchars($t['cedula']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.8rem; color: var(--text); margin-bottom: 2px;">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12" style="vertical-align: middle; margin-right: 4px; color: var(--muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    <?php echo htmlspecialchars($t['email']); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--muted);">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12" style="vertical-align: middle; margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                    <?php echo htmlspecialchars($t['telefono'] ?: 'No registrado'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($t['grupo_nombre'])): ?>
                                                    <span class="grupo-badge">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                        <?php echo htmlspecialchars($t['grupo_nombre']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="grupo-missing">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                        Sin grupo
                                                    </span>
                                                <?php endif; ?>
                                                <button class="btn-asignar-mini" onclick="abrirAsignarModal(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido'], ENT_QUOTES); ?>', <?php echo $t['grupo_id'] ?: 'null'; ?>)" style="display:block; margin-top:6px; color:var(--muted); font-weight: 500;">Cambiar</button>
                                            </td>
                                            <td>
                                                <?php if ($t['activo'] == 1): ?>
                                                    <span class="status-badge status-active">Activo</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-inactive">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="actions-td" style="justify-content: flex-end;">
                                                    <button class="btn-action btn-view" title="Ver Perfil" onclick="alert('Próximamente: Ver perfil de <?php echo $t['nombre']; ?>')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                                                    <button class="btn-action btn-edit" title="Editar" onclick="alert('Próximamente: Editar a <?php echo $t['nombre']; ?>')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- VISTA DE TARJETAS (Se muestra solo en Móviles) -->
                <div class="view-tarjetas">
                    <div class="workers-grid">
                        <?php foreach ($trabajadores as $t): ?>
                            <?php 
                                $status_val = $t['activo'] == 1 ? '1' : '0';
                                $group_val = empty($t['grupo_id']) ? 'unassigned' : 'assigned';
                            ?>
                            <div class="worker-card worker-item card-<?php echo $t['id']; ?>" 
                                 data-text="<?php echo strtolower($t['nombre'] . ' ' . $t['apellido'] . ' ' . $t['cedula'] . ' ' . $t['email'] . ' ' . ($t['grupo_nombre'] ?? 'sin grupo')); ?>"
                                 data-status="<?php echo $status_val; ?>"
                                 data-has-group="<?php echo $group_val; ?>">
                                
                                <div class="card-header">
                                    <div class="user-info-td">
                                        <input type="checkbox" class="custom-checkbox worker-checkbox" value="<?php echo $t['id']; ?>">
                                        <div class="user-avatar"><?php echo strtoupper(substr($t['nombre'], 0, 1)); ?></div>
                                        <div class="user-info-text-box">
                                            <span class="user-name-text"><?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido']); ?></span>
                                            <span class="user-cc-text">C.C. <?php echo htmlspecialchars($t['cedula']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="info-row">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($t['email']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        <span><?php echo htmlspecialchars($t['telefono'] ?: 'No registrado'); ?></span>
                                    </div>
                                    <div class="info-row" style="margin-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                                        <?php if (!empty($t['grupo_nombre'])): ?>
                                            <span class="grupo-badge">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                <?php echo htmlspecialchars($t['grupo_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="grupo-missing">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                Sin grupo
                                            </span>
                                        <?php endif; ?>
                                        <button class="btn-asignar-mini" onclick="abrirAsignarModal(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido'], ENT_QUOTES); ?>', <?php echo $t['grupo_id'] ?: 'null'; ?>)">Cambiar</button>
                                    </div>
                                    <div style="margin-top: 4px;">
                                        <?php if ($t['activo'] == 1): ?>
                                            <span class="status-badge status-active">Activo</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button class="btn-action btn-view" title="Ver Perfil" onclick="alert('Próximamente: Ver perfil de <?php echo $t['nombre']; ?>')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                                    <button class="btn-action btn-edit" title="Editar" onclick="alert('Próximamente: Editar a <?php echo $t['nombre']; ?>')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <div class="modal-premium-overlay" id="modalAsignarGrupo">
        <form method="POST" class="modal-premium-box modal-masivo">
            <input type="hidden" name="accion" value="asignar_grupo">
            <input type="hidden" name="trabajador_id" id="input_trabajador_id">
            
            <div class="modal-premium-header">
                <div class="modal-premium-icon-top">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="modal-premium-header-text">
                    <h3>Cambiar de Grupo</h3>
                    <p>Trabajador: <strong id="lbl_trabajador_nombre" style="color: var(--primary2);"></strong></p>
                </div>
                <button type="button" class="btn-close-premium" onclick="closeModal('modalAsignarGrupo')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="modal-premium-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="input-icon-wrapper-modal" style="margin-bottom: 8px;">
                    <input type="text" id="searchGroupModalInd" placeholder="Buscar grupo por nombre...">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <div class="modal-groups-grid" id="gridGroupModalInd">
                    <label class="mini-group-card" data-search="sin grupo quitar eliminar">
                        <input type="radio" name="grupo_id" value="0">
                        <div class="group-icon" style="background: #fee2e2; color: #dc2626;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="group-info">
                            <span class="g-name" style="color: #dc2626;">Quitar del grupo</span>
                            <span class="g-count">Actualmente: <?php echo $conteo_grupos['sin_grupo']; ?> sin asignar</span>
                        </div>
                        <div class="check-indicator"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg></div>
                    </label>

                    <?php foreach ($grupos as $g): ?>
                        <?php $cantidad = $conteo_grupos[$g['id']] ?? 0; ?>
                        <label class="mini-group-card" data-search="<?php echo strtolower($g['nombre']); ?>">
                            <input type="radio" name="grupo_id" value="<?php echo $g['id']; ?>">
                            <div class="group-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                            </div>
                            <div class="group-info">
                                <span class="g-name"><?php echo htmlspecialchars($g['nombre']); ?></span>
                                <span class="g-count"><?php echo $cantidad; ?> trabajador(es) asignados</span>
                            </div>
                            <div class="check-indicator"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="modal-premium-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('modalAsignarGrupo')">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Asignación</button>
            </div>
        </form>
    </div>

    <div class="modal-premium-overlay" id="modalAsignarMasivo">
        <form method="POST" class="modal-premium-box modal-masivo">
            <input type="hidden" name="accion" value="asignar_grupo_masivo">
            <input type="hidden" name="trabajadores_ids" id="input_asignar_masivo_ids_final">
            
            <div class="modal-premium-header">
                <div class="modal-premium-icon-top">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="modal-premium-header-text">
                    <h3>Asignación Masiva a Grupo</h3>
                    <p>Moviendo a <strong id="lbl_masivo_count_modal" style="color: var(--primary2);"></strong>. Selecciona el destino:</p>
                </div>
                <button type="button" class="btn-close-premium" onclick="closeModal('modalAsignarMasivo')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="modal-premium-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="input-icon-wrapper-modal" style="margin-bottom: 8px;">
                    <input type="text" id="searchGroupModalMasivo" placeholder="Buscar grupo por nombre...">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <div class="modal-groups-grid" id="gridGroupModalMasivo">
                    <label class="mini-group-card" data-search="sin grupo quitar eliminar">
                        <input type="radio" name="grupo_id" value="0">
                        <div class="group-icon" style="background: #fee2e2; color: #dc2626;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="group-info">
                            <span class="g-name" style="color: #dc2626;">Quitar del grupo</span>
                            <span class="g-count">Actualmente: <?php echo $conteo_grupos['sin_grupo']; ?> sin asignar</span>
                        </div>
                        <div class="check-indicator"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg></div>
                    </label>

                    <?php foreach ($grupos as $g): ?>
                        <?php $cantidad = $conteo_grupos[$g['id']] ?? 0; ?>
                        <label class="mini-group-card" data-search="<?php echo strtolower($g['nombre']); ?>">
                            <input type="radio" name="grupo_id" value="<?php echo $g['id']; ?>">
                            <div class="group-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                            </div>
                            <div class="group-info">
                                <span class="g-name"><?php echo htmlspecialchars($g['nombre']); ?></span>
                                <span class="g-count"><?php echo $cantidad; ?> trabajador(es) asignados</span>
                            </div>
                            <div class="check-indicator"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="modal-premium-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('modalAsignarMasivo')">Cancelar</button>
                <button type="button" class="btn-primary" id="btnSubmitMasivo">Aplicar a Todos</button>
            </div>
        </form>
    </div>

    <script>
        // ==========================================
        // LÓGICA DE MODALES
        // ==========================================
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }

        function abrirAsignarModal(id, nombre, currentGroupId) {
            document.getElementById('input_trabajador_id').value = id;
            document.getElementById('lbl_trabajador_nombre').textContent = nombre;
            
            document.querySelectorAll('#modalAsignarGrupo .mini-group-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('#modalAsignarGrupo input[name="grupo_id"]').forEach(r => r.checked = false);
            
            let valToSelect = currentGroupId ? currentGroupId.toString() : "0";
            let radioToSelect = document.querySelector(`#modalAsignarGrupo input[name="grupo_id"][value="${valToSelect}"]`);
            if (radioToSelect) {
                radioToSelect.checked = true;
                radioToSelect.closest('.mini-group-card').classList.add('selected');
            }

            document.getElementById('searchGroupModalInd').value = '';
            filterGroupsInd(); 

            openModal('modalAsignarGrupo');
        }

        function verificarYabrirModalMasivo() {
            const inputAssignIdsGlobal = document.getElementById('input_asignar_masivo_ids_final');
            if (inputAssignIdsGlobal.value === '') {
                alert("Por favor, selecciona al menos un trabajador en la lista marcando su casilla.");
                return;
            }
            
            const count = inputAssignIdsGlobal.value.split(',').length;
            document.getElementById('lbl_masivo_count_modal').textContent = count + (count === 1 ? ' trabajador' : ' trabajadores');
            
            document.querySelectorAll('#modalAsignarMasivo .mini-group-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('#modalAsignarMasivo input[name="grupo_id"]').forEach(r => r.checked = false);
            
            document.getElementById('searchGroupModalMasivo').value = '';
            filterGroupsMasivo(); 

            openModal('modalAsignarMasivo');
        }

        // ==========================================
        // BUSCADORES INTERNOS (TARJETAS DE GRUPO)
        // ==========================================
        function filterGroupsInd() {
            const search = document.getElementById('searchGroupModalInd').value.toLowerCase().trim();
            document.querySelectorAll('#gridGroupModalInd .mini-group-card').forEach(item => {
                const text = item.getAttribute('data-search');
                if (text.includes(search)) item.style.display = '';
                else item.style.display = 'none';
            });
        }
        if(document.getElementById('searchGroupModalInd')) document.getElementById('searchGroupModalInd').addEventListener('keyup', filterGroupsInd);

        function filterGroupsMasivo() {
            const search = document.getElementById('searchGroupModalMasivo').value.toLowerCase().trim();
            document.querySelectorAll('#gridGroupModalMasivo .mini-group-card').forEach(item => {
                const text = item.getAttribute('data-search');
                if (text.includes(search)) item.style.display = '';
                else item.style.display = 'none';
            });
        }
        if(document.getElementById('searchGroupModalMasivo')) document.getElementById('searchGroupModalMasivo').addEventListener('keyup', filterGroupsMasivo);

        // ==========================================
        // LÓGICA DE SELECCIÓN MASIVA
        // ==========================================
        const checkboxesGlobal = document.querySelectorAll('.worker-checkbox');
        const selectAllTable = document.getElementById('selectAllTable');
        const bulkBar = document.getElementById('bulkActionBar');
        const bulkCount = document.getElementById('bulkCount');
        const inputAssignIdsGlobal = document.getElementById('input_asignar_masivo_ids_final');
        const inputDeleteIdsGlobal = document.getElementById('input_eliminar_masivo_ids');

        function syncCheckboxesGlobal(id, isChecked) {
            document.querySelectorAll(`.worker-checkbox[value="${id}"]`).forEach(cb => {
                cb.checked = isChecked;
                const row = document.querySelector(`.row-${id}`);
                const card = document.querySelector(`.card-${id}`);
                if (isChecked) {
                    if (row) row.classList.add('selected');
                    if (card) card.classList.add('selected');
                } else {
                    if (row) row.classList.remove('selected');
                    if (card) card.classList.remove('selected');
                }
            });
        }

        function updateBulkBar() {
            const selectedSet = new Set();
            Array.from(checkboxesGlobal).filter(cb => cb.checked).forEach(cb => selectedSet.add(cb.value));
            
            const selectedArray = Array.from(selectedSet);
            
            if (selectedArray.length > 0) {
                bulkCount.textContent = selectedArray.length + (selectedArray.length === 1 ? ' seleccionado' : ' seleccionados');
                
                const idString = selectedArray.join(',');
                inputAssignIdsGlobal.value = idString;
                inputDeleteIdsGlobal.value = idString;
                
                bulkBar.classList.add('active');
            } else {
                bulkBar.classList.remove('active');
                if (selectAllTable) selectAllTable.checked = false;
                
                inputAssignIdsGlobal.value = '';
                inputDeleteIdsGlobal.value = '';
            }
        }

        function limpiarSeleccion() {
            checkboxesGlobal.forEach(cb => {
                cb.checked = false;
                syncCheckboxesGlobal(cb.value, false);
            });
            if (selectAllTable) selectAllTable.checked = false;
            updateBulkBar();
        }

        // ==========================================
        // FILTROS Y BÚSQUEDA GLOBAL
        // ==========================================
        let filterTimeout;

        function applyGlobalFilters() {
            const loader = document.getElementById('filterLoader');
            if (loader) loader.style.display = 'block';

            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                const searchInput = document.getElementById('searchInput');
                const searchText = searchInput ? searchInput.value.toLowerCase().trim() : '';
                const filterGroup = document.getElementById('filterGroup').value;
                const filterStatus = document.getElementById('filterStatus').value;

                document.querySelectorAll('.worker-item').forEach(item => {
                    const text = item.getAttribute('data-text');
                    const status = item.getAttribute('data-status');
                    const hasGroup = item.getAttribute('data-has-group');

                    let matchSearch = text.includes(searchText);
                    let matchGroup = (filterGroup === 'all') || (filterGroup === hasGroup);
                    let matchStatus = (filterStatus === 'all') || (filterStatus === status);

                    if (matchSearch && matchGroup && matchStatus) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (loader) loader.style.display = 'none';
            }, 400);
        }

        // ==========================================
        // EVENTOS DOM GLOBALES
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {

            // --- Filtros ---
            const searchInputGlobal = document.getElementById('searchInput');
            if (searchInputGlobal) searchInputGlobal.addEventListener('input', applyGlobalFilters);
            const filterGroupGlobal = document.getElementById('filterGroup');
            if (filterGroupGlobal) filterGroupGlobal.addEventListener('change', applyGlobalFilters);
            const filterStatusGlobal = document.getElementById('filterStatus');
            if (filterStatusGlobal) filterStatusGlobal.addEventListener('change', applyGlobalFilters);

            // --- Checkboxes ---
            checkboxesGlobal.forEach(cb => {
                cb.addEventListener('change', function() {
                    syncCheckboxesGlobal(this.value, this.checked);
                    updateBulkBar();
                });
            });

            if (selectAllTable) {
                selectAllTable.addEventListener('change', function() {
                    const isChecked = this.checked;
                    document.querySelectorAll('.view-lista .worker-checkbox').forEach(cb => {
                        const row = cb.closest('.worker-item');
                        if (row && row.style.display !== 'none') {
                            cb.checked = isChecked;
                            syncCheckboxesGlobal(cb.value, isChecked);
                        }
                    });
                    updateBulkBar();
                });
            }

            // Al hacer clic en una tarjeta de grupo (En cualquier modal), seleccionarla
            document.querySelectorAll('.mini-group-card input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const containerGrid = this.closest('.modal-groups-grid');
                    containerGrid.querySelectorAll('.mini-group-card').forEach(card => card.classList.remove('selected'));
                    if (this.checked) {
                        this.closest('.mini-group-card').classList.add('selected');
                    }
                });
            });

            // Enviar Formulario del Modal Masivo
            const btnSubmitMasivo = document.getElementById('btnSubmitMasivo');
            if (btnSubmitMasivo) {
                btnSubmitMasivo.addEventListener('click', function() {
                    const isGroupSelected = document.querySelector('#modalAsignarMasivo input[name="grupo_id"]:checked');
                    if (!isGroupSelected) {
                        alert('Por favor selecciona la tarjeta del grupo destino o la opción de "Quitar del grupo".');
                        return;
                    }
                    document.getElementById('modalAsignarMasivo').querySelector('form').submit();
                });
            }

            // Cerrar modales si se hace clic afuera de la caja
            document.querySelectorAll('.modal-premium-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeModal(this.id);
                });
            });

            // Ocultar Alertas Automáticamente
            const alerts = document.querySelectorAll('.alert');
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
</body>
</html>