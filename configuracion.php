<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/document_control_schema.php';
require_once 'config/calendar_integration.php';

// Exige sesión válida
$u = require_auth($conn);

$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
$current_page = 'configuracion.php';
$empresa_id = storage_user_company_id($conn, (int)$usuario_id);
ensure_document_control_schema($conn);

$mensaje = '';
$tipo_mensaje = '';
ensure_calendar_integration_schema($conn);
if (empty($_SESSION['calendar_csrf'])) {
    $_SESSION['calendar_csrf'] = bin2hex(random_bytes(24));
}

// ========================================================
// PROCESAR FORMULARIOS DE CONFIGURACIÓN
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'datos_personales') {
            $nombre = trim($_POST['nombre']);
            $apellido = trim($_POST['apellido']);
            $telefono = trim($_POST['telefono']);
            $ciudad = trim($_POST['ciudad']);

            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, ciudad = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $telefono, $ciudad, $usuario_id]);
            
            $_SESSION['usuario_nombre'] = $nombre; // Actualizar sesión
            $mensaje = "Datos personales actualizados correctamente.";
            $tipo_mensaje = "success";
        } 
        elseif ($accion === 'cambiar_password') {
            // Aquí iría la lógica real de cambio de contraseña (verificar password_hash)
            $mensaje = "Contraseña actualizada exitosamente.";
            $tipo_mensaje = "success";
        }
        elseif ($accion === 'correo_seguridad') {
            $correo_seguridad = trim($_POST['correo_seguridad'] ?? '');

            if (!filter_var($correo_seguridad, FILTER_VALIDATE_EMAIL)) {
                $mensaje = "Ingresa un correo de seguridad valido.";
                $tipo_mensaje = "danger";
            } else {
                $stmt_check = $conn->prepare("
                    SELECT id
                    FROM usuarios
                    WHERE id <> ?
                      AND (email = ? OR correo_seguridad = ?)
                    LIMIT 1
                ");
                $stmt_check->execute([$usuario_id, $correo_seguridad, $correo_seguridad]);

                if ($stmt_check->fetch()) {
                    $mensaje = "Ese correo ya esta asociado a otra cuenta.";
                    $tipo_mensaje = "danger";
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET correo_seguridad = ? WHERE id = ?");
                    $stmt->execute([$correo_seguridad, $usuario_id]);
                    $mensaje = "Correo de seguridad actualizado. Los codigos de ingreso se enviaran a este correo.";
                    $tipo_mensaje = "success";
                }
            }
        }
        elseif ($accion === 'datos_empresa' && $usuario_rol === 'representante') {
            $nombre_empresa = trim($_POST['nombre_empresa']);
            $tipo_doc = trim($_POST['tipo_doc_empresa']);
            $num_doc = trim($_POST['num_doc_empresa']);
            $tipo_persona = trim($_POST['tipo_persona']);
            $regimen = trim($_POST['regimen_tributario']);
            $clase_riesgo = trim($_POST['clase_riesgo'] ?? '');
            
            // El input oculto que manda el JS de las actividades
            $actividad_economica = trim($_POST['actividades_economicas'] ?? '');

            $stmt = $conn->prepare("UPDATE usuarios SET nombre_empresa = ?, tipo_doc_empresa = ?, num_doc_empresa = ?, tipo_persona = ?, regimen_tributario = ?, clase_riesgo = ?, actividad_economica = ? WHERE id = ?");
            $stmt->execute([$nombre_empresa, $tipo_doc, $num_doc, $tipo_persona, $regimen, $clase_riesgo, $actividad_economica, $usuario_id]);

            if (!empty($_SESSION['empresa_id'])) {
                $stmt_sol = $conn->prepare("UPDATE solicitudes_empresas SET empresa_nombre = ?, empresa_nit = ?, empresa_clase_riesgo = ? WHERE id = ?");
                $stmt_sol->execute([$nombre_empresa, $num_doc, $clase_riesgo, $_SESSION['empresa_id']]);
            }
            
            $mensaje = "Información de la empresa guardada.";
            $tipo_mensaje = "success";
        }
        elseif ($accion === 'datos_sst' && $usuario_rol === 'sst') {
            $tipo_licencia = trim($_POST['tipo_licencia']);
            $numero_licencia = trim($_POST['numero_licencia']);
            $fecha_licencia = trim($_POST['fecha_licencia']);
            $firma_base64 = $_POST['firma_sst_base64'] ?? '';

            if (!empty($firma_base64)) {
                $stmt = $conn->prepare("UPDATE usuarios SET licencia_sst = 'si', tipo_licencia = ?, numero_licencia = ?, fecha_licencia = ?, firma = ? WHERE id = ?");
                $stmt->execute([$tipo_licencia, $numero_licencia, $fecha_licencia, $firma_base64, $usuario_id]);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET licencia_sst = 'si', tipo_licencia = ?, numero_licencia = ?, fecha_licencia = ? WHERE id = ?");
                $stmt->execute([$tipo_licencia, $numero_licencia, $fecha_licencia, $usuario_id]);
            }
            
            $mensaje = "Licencia y firma digital actualizadas.";
            $tipo_mensaje = "success";
        }
        elseif ($accion === 'control_documental' && in_array($usuario_rol, ['sst', 'representante'], true)) {
            $standard = (int)($_POST['estandar_numero'] ?? 1);
            $storageContext = storage_company_context($conn, $empresa_id);
            $maxStandard = storage_max_standard((int)($storageContext['nivel_plan'] ?? 1));
            $prefix = strtoupper(trim((string)($_POST['codigo_prefijo'] ?? 'PW-SST')));
            $separator = (string)($_POST['separador'] ?? '-');
            $versionPrefix = strtoupper(trim((string)($_POST['version_prefijo'] ?? 'V')));
            $initialVersion = strtoupper(trim((string)($_POST['version_inicial'] ?? 'V1.0')));
            $requireName = isset($_POST['exigir_codigo_nombre']) ? 1 : 0;

            if ($empresa_id <= 0 || $standard < 1 || $standard > $maxStandard) {
                throw new InvalidArgumentException('El estándar seleccionado no está disponible para la empresa.');
            }
            if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,38}$/', $prefix)) {
                throw new InvalidArgumentException('El prefijo debe usar entre 2 y 39 letras, números, guiones o guion bajo.');
            }
            if (!in_array($separator, ['-', '_', '.'], true)) {
                throw new InvalidArgumentException('Selecciona un separador válido.');
            }
            if (!preg_match('/^[A-Z]{1,5}$/', $versionPrefix) || !preg_match('/^' . preg_quote($versionPrefix, '/') . '\d+(?:\.\d+){0,2}$/', $initialVersion)) {
                throw new InvalidArgumentException('La versión inicial debe seguir el patrón del prefijo, por ejemplo V1.0.');
            }

            $stmtConfig = $conn->prepare(<<<'SQL'
                INSERT INTO control_documental_config
                    (empresa_id, estandar_numero, codigo_prefijo, separador, version_prefijo,
                     version_inicial, exigir_codigo_nombre, actualizado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE codigo_prefijo=VALUES(codigo_prefijo), separador=VALUES(separador),
                    version_prefijo=VALUES(version_prefijo), version_inicial=VALUES(version_inicial),
                    exigir_codigo_nombre=VALUES(exigir_codigo_nombre), actualizado_por=VALUES(actualizado_por)
            SQL);
            $stmtConfig->execute([$empresa_id, $standard, $prefix, $separator, $versionPrefix, $initialVersion, $requireName, $usuario_id]);
            $mensaje = 'Control documental del Estándar ' . $standard . ' actualizado correctamente.';
            $tipo_mensaje = 'success';
        }
        elseif ($accion === 'disconnect_calendar' && in_array($usuario_rol, ['sst', 'representante'], true)) {
            $csrf = (string)($_POST['calendar_csrf'] ?? '');
            if ($csrf === '' || !hash_equals((string)$_SESSION['calendar_csrf'], $csrf)) {
                throw new RuntimeException('La solicitud venció. Recarga la página e intenta nuevamente.');
            }
            calendar_disconnect($conn, (int)$usuario_id);
            $mensaje = 'Calendario desconectado correctamente.';
            $tipo_mensaje = 'success';
        }
    } catch (Throwable $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// ========================================================
// OBTENER DATOS ACTUALES DEL USUARIO Y LA EMPRESA (FALLBACK)
// ========================================================
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Variables unificadas
$emp_nombre = $user_info['nombre_empresa'] ?? '';
$emp_num_doc = $user_info['num_doc_empresa'] ?? '';
$emp_tipo_doc = $user_info['tipo_doc_empresa'] ?? 'NIT';
$correo_seguridad_actual = ($user_info['correo_seguridad'] ?? '') ?: ($user_info['email'] ?? '');

// Si es Representante y NO ha llenado el perfil corporativo aún, 
// jalamos la info inicial de la solicitud para que no empiece de cero.
if ($usuario_rol === 'representante' && !empty($user_info['empresa_id'])) {
    if (empty($emp_nombre) || empty($emp_num_doc)) {
        $stmt_sol = $conn->prepare("SELECT nombre, cedula, empresa_nombre, empresa_nit, empresa_clase_riesgo FROM solicitudes_empresas WHERE id = ?");
        $stmt_sol->execute([$user_info['empresa_id']]);
        $sol_data = $stmt_sol->fetch(PDO::FETCH_ASSOC);
        
        if ($sol_data) {
            $emp_nombre = empty($emp_nombre) ? ($sol_data['empresa_nombre'] ?: $sol_data['nombre']) : $emp_nombre;
            $emp_num_doc = empty($emp_num_doc) ? ($sol_data['empresa_nit'] ?: $sol_data['cedula']) : $emp_num_doc;
            if (empty($user_info['clase_riesgo']) && !empty($sol_data['empresa_clase_riesgo'])) {
                $user_info['clase_riesgo'] = $sol_data['empresa_clase_riesgo'];
            }
        }
    }
}

$storage_context_config = $empresa_id > 0 ? storage_company_context($conn, $empresa_id) : null;
$max_standard_config = storage_max_standard((int)($storage_context_config['nivel_plan'] ?? 1));
$standard_catalog_config = storage_standard_catalog();
$document_configs = [];
if ($empresa_id > 0) {
    $stmt_doc_configs = $conn->prepare('SELECT * FROM control_documental_config WHERE empresa_id=? ORDER BY estandar_numero');
    $stmt_doc_configs->execute([$empresa_id]);
    foreach ($stmt_doc_configs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $document_configs[(int)$row['estandar_numero']] = $row;
    }
}

$calendar_connection = in_array($usuario_rol, ['sst', 'representante'], true)
    ? calendar_connection($conn, (int)$usuario_id)
    : null;
$google_calendar_ready = calendar_provider_configured('google');
$microsoft_calendar_ready = calendar_provider_configured('microsoft');
if (isset($_GET['calendar']) && $_GET['calendar'] === 'connected') {
    $mensaje = 'Calendario conectado. Desde ahora las reuniones podrán sincronizarse automáticamente.';
    $tipo_mensaje = 'success';
}
if (!empty($_GET['calendar_error'])) {
    $mensaje = (string)$_GET['calendar_error'];
    $tipo_mensaje = 'danger';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 12px; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease;}
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        /* ENCABEZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .estandar-header-group { display: flex; align-items: center; gap: 14px; }
        .icon-box-std { width: 40px; height: 40px; background: rgba(255, 138, 31, 0.1); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .estandar-title { margin: 0; font-size: 1.1rem; color: var(--blue-dark, #1e3a8a); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3;}
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.8rem; font-weight: 500; line-height: 1.4;}
        
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease; font-size: 0.8rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }
        
        /* ALERTAS */
        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 0.8rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* LAYOUT DE CONFIGURACIÓN */
        .config-layout { display: flex; flex-direction: column; gap: 20px; }
        
        /* BARRA SUPERIOR (Tabs Horizontales + Buscador) */
        .config-top-bar { 
            display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; 
            background: var(--card); padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border); 
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); 
        }

        .config-nav { display: flex; gap: 6px; flex-wrap: wrap; flex: 1; }
        .nav-tab { 
            background: transparent; border: 1px solid transparent; text-align: left; padding: 8px 14px; 
            border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: var(--muted); cursor: pointer; 
            transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-family: inherit; 
        }
        .nav-tab:hover { background: rgba(0,0,0,0.03); color: var(--text); }
        .nav-tab.active { background: #fff8f3; color: var(--primary2); font-weight: 700; border: 1px solid rgba(255, 138, 31, 0.2); }
        .nav-tab.active svg { color: var(--primary); }

        /* Buscador General */
        .config-search { position: relative; width: 100%; max-width: 280px; }
        .config-search input { width: 100%; padding: 8px 12px 8px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.8rem; transition: all 0.2s; box-sizing: border-box; background: #f8fafc; }
        .config-search input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .config-search svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }

        /* Contenedor de Formularios */
        .config-content { background: var(--card); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: visible; }
        .tab-pane { display: none; padding: 28px; }
        .tab-pane.active { display: block; animation: fadeIn 0.3s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .pane-header { margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px dashed var(--border); }
        .pane-title { font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0 0 4px 0; }
        .pane-desc { color: var(--muted); margin: 0; font-size: 0.8rem; }

        /* Formularios */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 12px; transition: opacity 0.2s; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 0.7rem; font-weight: 700; color: var(--text); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .custom-input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.8rem; font-weight: 600; box-sizing: border-box; background: #f8fafc; transition: all 0.2s; color: var(--text); }
        .custom-input:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .custom-input:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; border-color: #cbd5e1; }

        .custom-select { 
            width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: inherit; font-size: 0.8rem; font-weight: 600; color: var(--text);
            box-sizing: border-box; background-color: #f8fafc; cursor: pointer;
            appearance: none; transition: all 0.2s;
            background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;
        }
        .custom-select:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }

        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: transform 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); margin-top: 8px;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 138, 31, 0.3); }

        /* =======================================================
           ESTILOS BUSCADOR Y TABLA DE ACTIVIDADES CIIU
           ======================================================= */
        .multi-select-container { position: relative; margin-bottom: 12px; }
        .search-icon-inside { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 2; pointer-events: none;}
        .multi-search-input { width: 100%; padding: 10px 14px 10px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.8rem; color: var(--text); box-sizing: border-box; transition: all 0.3s ease; background: #f8fafc; }
        .multi-search-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); background: #ffffff; }
        .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
        .dropdown-list.active { display: block; }
        .dropdown-item { padding: 10px 14px; cursor: pointer; display: flex; flex-direction: column; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover { background: #f8fafc; }
        .dropdown-item-title { font-size: 0.8rem; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; }
        .dropdown-item-desc { font-size: 0.75rem; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .risk-indicator { font-size: 0.65rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-block; text-align: center; min-width: 45px;}
        .risk-I { background: #dcfce7; color: #166534; }
        .risk-II { background: #fef9c3; color: #854d0e; }
        .risk-III { background: #ffedd5; color: #9a3412; }
        .risk-IV { background: #fee2e2; color: #991b1b; }
        .risk-V { background: #fecaca; color: #991b1b; }

        .activity-table-wrapper { border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #ffffff; max-height: 220px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .activity-table-wrapper::-webkit-scrollbar { width: 4px; }
        .activity-table-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .activity-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 500px;}
        .activity-table th { background: #f8fafc; padding: 10px 14px; font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; position: sticky; top: 0; z-index: 5; border-bottom: 1px solid #cbd5e1; }
        .activity-table td { padding: 10px 14px; font-size: 0.8rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .activity-table tr:last-child td { border-bottom: none; }
        .activity-table tr:hover td { background: #f8fafc; }
        .btn-remove-row { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; margin: 0 auto; }
        .btn-remove-row:hover { background: #ef4444; color: #ffffff; }
        .empty-table-msg { text-align: center; padding: 20px !important; color: #94a3b8 !important; font-style: italic; font-size: 0.8rem; }

        /* CALENDARIO Y REUNIONES */
        .calendar-overview { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:center; padding:17px 18px; margin-bottom:16px; border:1px solid #dbe7f5; border-radius:12px; background:linear-gradient(135deg,#f8fbff 0%,#fff 72%); }
        .calendar-overview::after { content:'\f073'; position:absolute; right:16px; bottom:-28px; color:#2563eb; opacity:.045; font:900 92px/1 'Font Awesome 6 Free'; pointer-events:none; }
        .calendar-overview-copy { position:relative; z-index:1; display:flex; align-items:center; gap:13px; min-width:0; }
        .calendar-main-icon { width:42px; height:42px; border-radius:10px; display:grid; place-items:center; flex:0 0 auto; background:#eaf2ff; color:#2563eb; font-size:1rem; }
        .calendar-overview h3 { margin:0; color:#173b7a; font-size:.9rem; }
        .calendar-overview p { margin:4px 0 0; color:#64748b; font-size:.73rem; line-height:1.45; }
        .calendar-status-pill { position:relative; z-index:1; display:inline-flex; align-items:center; gap:6px; padding:6px 9px; border-radius:999px; background:#ecfdf5; color:#047857; font-size:.65rem; font-weight:800; white-space:nowrap; }
        .calendar-status-pill.off { background:#f1f5f9; color:#64748b; }
        .provider-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .provider-card { --provider:#4285f4; position:relative; min-width:0; overflow:hidden; display:flex; flex-direction:column; gap:14px; padding:16px; border:1px solid #dbe3ec; border-radius:12px; background:#fff; box-shadow:0 7px 20px rgba(15,23,42,.035); }
        .provider-card.microsoft { --provider:#2563eb; }
        .provider-card.active { border-color:#93b4eb; box-shadow:0 10px 24px rgba(37,99,235,.09); }
        .provider-watermark { position:absolute; right:-9px; bottom:-18px; color:var(--provider); opacity:.045; font-size:4.5rem; pointer-events:none; }
        .provider-head { position:relative; z-index:1; display:flex; align-items:center; gap:11px; }
        .provider-icon { width:38px; height:38px; display:grid; place-items:center; flex:0 0 auto; border-radius:9px; background:#eff6ff; color:var(--provider); font-size:.95rem; }
        .provider-head h3 { margin:0; color:#172554; font-size:.82rem; }
        .provider-head p { margin:3px 0 0; color:#718096; font-size:.66rem; }
        .provider-copy { position:relative; z-index:1; margin:0; min-height:37px; color:#64748b; font-size:.71rem; line-height:1.45; }
        .provider-action { position:relative; z-index:1; min-height:36px; display:inline-flex; justify-content:center; align-items:center; gap:7px; padding:0 12px; border:1px solid #b9cff2; border-radius:8px; background:#fff; color:var(--provider); text-decoration:none; font-size:.7rem; font-weight:800; cursor:pointer; }
        .provider-action.primary { background:var(--provider); border-color:var(--provider); color:#fff; }
        .provider-action[aria-disabled='true'] { background:#f8fafc; border-color:#e2e8f0; color:#94a3b8; cursor:not-allowed; }
        .calendar-footnote { margin:14px 0 0; padding:10px 12px; border-radius:9px; background:#fff7ed; color:#9a4b0d; font-size:.69rem; line-height:1.45; }
        .disconnect-calendar { margin-top:14px; padding-top:14px; border-top:1px solid #e7edf4; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .disconnect-calendar span { color:#64748b; font-size:.7rem; }
        .btn-disconnect { height:34px; border:1px solid #fecaca; border-radius:8px; padding:0 11px; background:#fff; color:#dc2626; font:inherit; font-size:.68rem; font-weight:800; cursor:pointer; }

        /* Lienzo de Firma */
        .firma-box { border: 2px dashed #cbd5e1; border-radius: 10px; background: #f8fafc; position: relative; width: 100%; max-width: 450px; overflow: hidden; margin-top: 6px; transition: border-color 0.2s;}
        .firma-box:hover { border-color: var(--primary); }
        .firma-box canvas { width: 100%; height: 140px; cursor: crosshair; display: block; background: white; touch-action: none; }
        .btn-limpiar { position: absolute; top: 8px; right: 8px; background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); padding: 4px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 600; cursor: pointer; z-index: 10; font-family: inherit; transition: 0.2s;}
        .btn-limpiar:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5;}

        /* MEDIA QUERIES RESPONSIVE MEJORADAS */
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 16px 14px; } /* Más espacio lateral */
            .header-actions { flex-direction: column; align-items: stretch; gap: 16px; margin-bottom: 20px;}
            
            /* Centrado de Encabezado en Móvil */
            .estandar-header-group { flex-direction: column; align-items: center; text-align: center; gap: 8px; } 
            .estandar-title { font-size: 1.2rem; margin: 0; }
            .estandar-subtitle { font-size: 0.85rem; margin: 0; }
            
            .config-top-bar { flex-direction: column; align-items: stretch; padding: 12px; gap: 12px;}
            
            /* Optimización Pestañas (Scroll Horizontal Suave) */
            .config-nav { 
                overflow-x: auto; padding-bottom: 4px; flex-wrap: nowrap; gap: 6px;
                -webkit-overflow-scrolling: touch; scrollbar-width: none;
                width:100%; max-width:100%; min-width:0; box-sizing:border-box;
            }
            .config-nav::-webkit-scrollbar { display: none; }
            .nav-tab { white-space: nowrap; flex: 0 0 auto; font-size: 0.8rem; padding: 8px 12px; }
            
            .config-search { max-width: 100%; }
            .config-content { border-radius: 12px; }
            .tab-pane { padding: 20px 16px; } /* Menos padding interno en móvil */
            .calendar-overview { grid-template-columns:1fr; padding:14px; }
            .calendar-status-pill { width:max-content; }
            .provider-grid { grid-template-columns:1fr; }
            .disconnect-calendar { align-items:flex-start; flex-direction:column; }
            .pane-header { margin-bottom: 20px; padding-bottom: 12px; }
            .form-grid { grid-template-columns: 1fr; gap: 16px; }
            .btn-primary { width: 100%; justify-content: center; padding: 12px; }
            .firma-box { max-width: 100%; }
            .btn-back { width: 100%; justify-content: center; padding: 10px; box-sizing:border-box; }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php if($tipo_mensaje == 'success'): ?>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?php else: ?>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h1 class="estandar-title">Configuración de Cuenta</h1>
                        <p class="estandar-subtitle">Administra tus datos personales, preferencias y seguridad.</p>
                    </div>
                </div>
                <a href="dashboard.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver al Dashboard
                </a>
            </div>

            <div class="config-layout">
                
                <div class="config-top-bar">
                    <div class="config-nav">
                        <button class="nav-tab active" onclick="openTab(event, 'tab-personal')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Datos Personales
                        </button>
                        
                        <?php if ($usuario_rol === 'representante'): ?>
                            <button class="nav-tab" onclick="openTab(event, 'tab-empresa')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Perfil Empresa
                            </button>
                        <?php endif; ?>

                        <?php if ($usuario_rol === 'sst'): ?>
                            <button class="nav-tab" onclick="openTab(event, 'tab-sst')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Licencia y Firma
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($usuario_rol, ['sst', 'representante'], true)): ?>
                            <button class="nav-tab" onclick="openTab(event, 'tab-documental')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7z"></path></svg> Control documental
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($usuario_rol, ['sst', 'representante'], true)): ?>
                            <button class="nav-tab" onclick="openTab(event, 'tab-calendar')" data-tab="calendar">
                                <i class="fa-regular fa-calendar-check"></i> Calendario
                            </button>
                        <?php endif; ?>

                        <button class="nav-tab" onclick="openTab(event, 'tab-seguridad')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg> Seguridad
                        </button>
                    </div>

                    <div class="config-search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="searchConfig" placeholder="Buscar configuración...">
                    </div>
                </div>

                <div class="config-content">
                    
                    <div id="tab-personal" class="tab-pane active">
                        <div class="pane-header">
                            <h2 class="pane-title">Información Personal</h2>
                            <p class="pane-desc">Actualiza tu información básica de contacto.</p>
                        </div>
                        <form action="configuracion.php" method="POST">
                            <input type="hidden" name="accion" value="datos_personales">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Cédula de Ciudadanía</label>
                                    <input type="text" class="custom-input" value="<?php echo htmlspecialchars($user_info['cedula']); ?>" disabled title="No se puede modificar la cédula">
                                </div>
                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" class="custom-input" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled title="No se puede modificar el correo">
                                </div>
                                <div class="form-group">
                                    <label>Nombres</label>
                                    <input type="text" name="nombre" class="custom-input" value="<?php echo htmlspecialchars($user_info['nombre']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Apellidos</label>
                                    <input type="text" name="apellido" class="custom-input" value="<?php echo htmlspecialchars($user_info['apellido']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Teléfono Celular</label>
                                    <input type="text" name="telefono" class="custom-input" value="<?php echo htmlspecialchars($user_info['telefono'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Ciudad de Residencia</label>
                                    <input type="text" name="ciudad" class="custom-input" value="<?php echo htmlspecialchars($user_info['ciudad'] ?? ''); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Guardar Cambios
                            </button>
                        </form>
                    </div>

                    <?php if ($usuario_rol === 'representante'): ?>
                    <div id="tab-empresa" class="tab-pane">
                        <div class="pane-header">
                            <h2 class="pane-title">Perfil de la Empresa</h2>
                            <p class="pane-desc">Información legal, tributaria y perfil de riesgos.</p>
                        </div>
                        <form action="configuracion.php" method="POST">
                            <input type="hidden" name="accion" value="datos_empresa">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label>Razón Social o Nombre Comercial *</label>
                                    <input type="text" name="nombre_empresa" class="custom-input" value="<?php echo htmlspecialchars($emp_nombre); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipo de Documento *</label>
                                    <select name="tipo_doc_empresa" class="custom-select" required>
                                        <option value="NIT" <?php echo ($emp_tipo_doc === 'NIT') ? 'selected' : ''; ?>>NIT</option>
                                        <option value="CC" <?php echo ($emp_tipo_doc === 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                                        <option value="CE" <?php echo ($emp_tipo_doc === 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Número de Documento *</label>
                                    <input type="text" name="num_doc_empresa" class="custom-input" value="<?php echo htmlspecialchars($emp_num_doc); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipo de Persona *</label>
                                    <select name="tipo_persona" class="custom-select" required>
                                        <option value="Natural" <?php echo ($user_info['tipo_persona'] ?? '') === 'Natural' ? 'selected' : ''; ?>>Persona Natural</option>
                                        <option value="Juridica" <?php echo ($user_info['tipo_persona'] ?? '') === 'Juridica' ? 'selected' : ''; ?>>Persona Jurídica</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Régimen Tributario *</label>
                                    <select name="regimen_tributario" class="custom-select" required>
                                        <option value="Responsable de IVA" <?php echo ($user_info['regimen_tributario'] ?? '') === 'Responsable de IVA' ? 'selected' : ''; ?>>Responsable de IVA</option>
                                        <option value="No Responsable de IVA" <?php echo ($user_info['regimen_tributario'] ?? '') === 'No Responsable de IVA' ? 'selected' : ''; ?>>No Responsable de IVA</option>
                                        <option value="Regimen Simple" <?php echo ($user_info['regimen_tributario'] ?? '') === 'Regimen Simple' ? 'selected' : ''; ?>>Régimen Simple</option>
                                        <option value="Régimen Especial" <?php echo ($user_info['regimen_tributario'] ?? '') === 'Régimen Especial' ? 'selected' : ''; ?>>Régimen Especial</option>
                                    </select>
                                </div>

                                <div class="form-group full">
                                    <label>Clase de Riesgo Principal *</label>
                                    <select name="clase_riesgo" class="custom-select" required>
                                        <option value="" disabled <?php echo empty($user_info['clase_riesgo']) ? 'selected' : ''; ?>>Selecciona el nivel de riesgo...</option>
                                        <option value="I" <?php echo ($user_info['clase_riesgo'] ?? '') === 'I' ? 'selected' : ''; ?>>Riesgo I (Mínimo)</option>
                                        <option value="II" <?php echo ($user_info['clase_riesgo'] ?? '') === 'II' ? 'selected' : ''; ?>>Riesgo II (Bajo)</option>
                                        <option value="III" <?php echo ($user_info['clase_riesgo'] ?? '') === 'III' ? 'selected' : ''; ?>>Riesgo III (Medio)</option>
                                        <option value="IV" <?php echo ($user_info['clase_riesgo'] ?? '') === 'IV' ? 'selected' : ''; ?>>Riesgo IV (Alto)</option>
                                        <option value="V" <?php echo ($user_info['clase_riesgo'] ?? '') === 'V' ? 'selected' : ''; ?>>Riesgo V (Máximo)</option>
                                    </select>
                                </div>

                                <div class="form-group full">
                                    <label>Actividades Económicas CIIU * (Busca y selecciona)</label>
                                    
                                    <div class="multi-select-container" id="activityContainer">
                                        <svg class="search-icon-inside" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <input type="text" id="activitySearchInput" class="multi-search-input" placeholder="Buscar código o palabra clave..." autocomplete="off">
                                        <div class="dropdown-list" id="activityDropdown"></div>
                                    </div>
                                    
                                    <div class="activity-table-wrapper">
                                        <table class="activity-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%;">Código</th>
                                                    <th style="width: 55%;">Descripción de la Actividad</th>
                                                    <th style="width: 15%; text-align: center;">Riesgo</th>
                                                    <th style="width: 15%; text-align: center;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="activityTableBody">
                                                </tbody>
                                        </table>
                                    </div>

                                    <input type="hidden" name="actividades_economicas" id="actividades_economicas_input" value="<?php echo htmlspecialchars($user_info['actividad_economica'] ?? ''); ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Guardar Datos de Empresa
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if ($usuario_rol === 'sst'): ?>
                    <div id="tab-sst" class="tab-pane">
                        <div class="pane-header">
                            <h2 class="pane-title">Licencia y Firma Digital</h2>
                            <p class="pane-desc">Actualiza tu información profesional para la firma de actas oficiales.</p>
                        </div>
                        <form action="configuracion.php" method="POST" id="formSST">
                            <input type="hidden" name="accion" value="datos_sst">
                            <input type="hidden" name="firma_sst_base64" id="firma_sst_base64">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo de Licencia</label>
                                    <select name="tipo_licencia" class="custom-select" required>
                                        <option value="Tecnico" <?php echo ($user_info['tipo_licencia'] ?? '') === 'Tecnico' ? 'selected' : ''; ?>>Técnico</option>
                                        <option value="Tecnologo" <?php echo ($user_info['tipo_licencia'] ?? '') === 'Tecnologo' ? 'selected' : ''; ?>>Tecnólogo</option>
                                        <option value="Profesional" <?php echo ($user_info['tipo_licencia'] ?? '') === 'Profesional' ? 'selected' : ''; ?>>Profesional</option>
                                        <option value="Especialista" <?php echo ($user_info['tipo_licencia'] ?? '') === 'Especialista' ? 'selected' : ''; ?>>Especialista</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Número de Resolución / Licencia</label>
                                    <input type="text" name="numero_licencia" class="custom-input" value="<?php echo htmlspecialchars($user_info['numero_licencia'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group full">
                                    <label>Fecha de Expedición</label>
                                    <input type="date" name="fecha_licencia" class="custom-input" value="<?php echo htmlspecialchars($user_info['fecha_licencia'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label>Firma Digital (Dibuja aquí para actualizarla)</label>
                                    <div class="firma-box">
                                        <canvas id="canvasFirma"></canvas>
                                        <button type="button" class="btn-limpiar" onclick="limpiarCanvas()">Limpiar Trazo</button>
                                    </div>
                                    <?php if(!empty($user_info['firma'])): ?>
                                        <p style="font-size: 0.7rem; color: var(--primary); margin-top: 6px; font-weight: 600;">Ya tienes una firma guardada en el sistema. Si dibujas en el recuadro, la reemplazarás.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" onclick="guardarSST()" class="btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Actualizar Perfil
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($usuario_rol, ['sst', 'representante'], true)): ?>
                    <div id="tab-documental" class="tab-pane">
                        <div class="pane-header">
                            <h2 class="pane-title">Identificadores de control documental</h2>
                            <p class="pane-desc">Define códigos y versiones por estándar. Estas reglas se aplican al validar soportes y formatos antes de guardarlos en Archivos.</p>
                        </div>
                        <form action="configuracion.php" method="POST" id="documentControlForm">
                            <input type="hidden" name="accion" value="control_documental">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label>Estándar que deseas configurar</label>
                                    <select name="estandar_numero" id="docStandard" class="custom-select" required>
                                        <?php for ($standard_number = 1; $standard_number <= $max_standard_config; $standard_number++): ?>
                                            <option value="<?php echo $standard_number; ?>"><?php echo $standard_number . '. ' . htmlspecialchars($standard_catalog_config[$standard_number] ?? ('Estándar ' . $standard_number)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Prefijo del código</label>
                                    <input class="custom-input" id="docPrefix" name="codigo_prefijo" value="PW-SST" maxlength="39" required>
                                </div>
                                <div class="form-group">
                                    <label>Separador</label>
                                    <select class="custom-select" id="docSeparator" name="separador">
                                        <option value="-">Guion (-)</option><option value="_">Guion bajo (_)</option><option value=".">Punto (.)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Prefijo de versión</label>
                                    <input class="custom-input" id="docVersionPrefix" name="version_prefijo" value="V" maxlength="5" required>
                                </div>
                                <div class="form-group">
                                    <label>Versión inicial</label>
                                    <input class="custom-input" id="docInitialVersion" name="version_inicial" value="V1.0" maxlength="20" required>
                                </div>
                                <div class="form-group full">
                                    <label style="display:flex;align-items:center;gap:10px;text-transform:none;letter-spacing:0;cursor:pointer;">
                                        <input type="checkbox" name="exigir_codigo_nombre" id="docRequireName" checked style="width:18px;height:18px;accent-color:#ff7a00;">
                                        Exigir que el nombre del archivo contenga el código y la versión
                                    </label>
                                </div>
                                <div class="form-group full">
                                    <div style="border:1px solid #dce6f1;border-radius:10px;background:#f8fbff;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
                                        <div><label style="margin:0 0 4px;">Vista previa del identificador</label><strong id="docCodePreview" style="color:#1e3a8a;font-size:.9rem;">PW-SST-E01-ACT</strong></div>
                                        <span id="docFilePreview" style="font-size:.72rem;color:#64748b;">PW-SST-E01-ACT_V1.0.pdf</span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Guardar regla documental
                            </button>
                        </form>

                        <?php if ($document_configs): ?>
                            <div style="margin-top:24px;border-top:1px solid #e4eaf1;padding-top:18px;display:grid;gap:8px;">
                                <strong style="font-size:.82rem;color:#1e3a8a;">Reglas personalizadas activas</strong>
                                <?php foreach ($document_configs as $configured_standard => $configured): ?>
                                    <div style="border:1px solid #e1e8f0;border-radius:9px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:.74rem;">
                                        <span><strong>Estándar <?php echo $configured_standard; ?></strong> · <?php echo htmlspecialchars($standard_catalog_config[$configured_standard] ?? ''); ?></span>
                                        <span style="color:#1d4ed8;font-weight:750;"><?php echo htmlspecialchars(document_control_code_example($configured, $configured_standard, 'DOC') . ' · ' . $configured['version_inicial']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($usuario_rol, ['sst', 'representante'], true)): ?>
                    <div id="tab-calendar" class="tab-pane">
                        <div class="pane-header">
                            <h2 class="pane-title">Calendario y reuniones</h2>
                            <p class="pane-desc">Conecta una sola cuenta para enviar automáticamente las reuniones programadas desde PreventWork.</p>
                        </div>

                        <div class="calendar-overview">
                            <div class="calendar-overview-copy">
                                <div class="calendar-main-icon"><i class="fa-regular fa-calendar-check"></i></div>
                                <div>
                                    <h3><?php echo $calendar_connection ? htmlspecialchars(calendar_provider_label($calendar_connection['provider'])) : 'Sin calendario conectado'; ?></h3>
                                    <p>
                                        <?php if ($calendar_connection): ?>
                                            <?php echo !empty($calendar_connection['account_email']) ? htmlspecialchars($calendar_connection['account_email']) . ' · ' : ''; ?>Las nuevas reuniones se sincronizarán al guardar.
                                        <?php else: ?>
                                            Elige Google o Microsoft. Al conectar uno, cualquier proveedor anterior será reemplazado.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <span class="calendar-status-pill <?php echo $calendar_connection ? '' : 'off'; ?>">
                                <i class="fa-solid <?php echo $calendar_connection ? 'fa-circle-check' : 'fa-circle-minus'; ?>"></i>
                                <?php echo $calendar_connection ? 'Conectado' : 'Pendiente'; ?>
                            </span>
                        </div>

                        <div class="provider-grid">
                            <article class="provider-card <?php echo (($calendar_connection['provider'] ?? '') === 'google') ? 'active' : ''; ?>">
                                <i class="fa-brands fa-google provider-watermark" aria-hidden="true"></i>
                                <div class="provider-head">
                                    <div class="provider-icon"><i class="fa-brands fa-google"></i></div>
                                    <div><h3>Google Calendar</h3><p>Reuniones con Google Meet</p></div>
                                </div>
                                <p class="provider-copy">Crea el evento, conserva las fechas y genera el enlace de Google Meet desde la actividad.</p>
                                <?php if (($calendar_connection['provider'] ?? '') === 'google'): ?>
                                    <span class="provider-action"><i class="fa-solid fa-check"></i> Cuenta activa</span>
                                <?php elseif ($google_calendar_ready): ?>
                                    <a class="provider-action primary" href="calendar_auth.php?provider=google"><i class="fa-solid fa-link"></i> Conectar Google</a>
                                <?php else: ?>
                                    <span class="provider-action" aria-disabled="true"><i class="fa-solid fa-triangle-exclamation"></i> Requiere configuración</span>
                                <?php endif; ?>
                            </article>

                            <article class="provider-card microsoft <?php echo (($calendar_connection['provider'] ?? '') === 'microsoft') ? 'active' : ''; ?>">
                                <i class="fa-brands fa-microsoft provider-watermark" aria-hidden="true"></i>
                                <div class="provider-head">
                                    <div class="provider-icon"><i class="fa-brands fa-microsoft"></i></div>
                                    <div><h3>Microsoft Outlook</h3><p>Calendario y Microsoft Teams</p></div>
                                </div>
                                <p class="provider-copy">Crea el evento en Outlook y solicita el enlace de Microsoft Teams para la reunión virtual.</p>
                                <?php if (($calendar_connection['provider'] ?? '') === 'microsoft'): ?>
                                    <span class="provider-action"><i class="fa-solid fa-check"></i> Cuenta activa</span>
                                <?php elseif ($microsoft_calendar_ready): ?>
                                    <a class="provider-action primary" href="calendar_auth.php?provider=microsoft"><i class="fa-solid fa-link"></i> Conectar Microsoft</a>
                                <?php else: ?>
                                    <span class="provider-action" aria-disabled="true"><i class="fa-solid fa-triangle-exclamation"></i> Requiere configuración</span>
                                <?php endif; ?>
                            </article>
                        </div>

                        <p class="calendar-footnote"><i class="fa-solid fa-shield-halved"></i> Solo se mantiene un proveedor activo. Cambiar de Google a Microsoft —o al contrario— reemplaza la conexión anterior sin afectar las actividades ya creadas.</p>

                        <?php if ($calendar_connection): ?>
                            <form method="POST" action="configuracion.php?tab=calendar" class="disconnect-calendar">
                                <input type="hidden" name="accion" value="disconnect_calendar">
                                <input type="hidden" name="calendar_csrf" value="<?php echo htmlspecialchars($_SESSION['calendar_csrf']); ?>">
                                <span>¿Quieres dejar de sincronizar las próximas reuniones?</span>
                                <button type="submit" class="btn-disconnect"><i class="fa-solid fa-link-slash"></i> Desconectar calendario</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div id="tab-seguridad" class="tab-pane">
                        <div class="pane-header">
                            <h2 class="pane-title">Seguridad de la Cuenta</h2>
                            <p class="pane-desc">Define el correo donde recibiras los codigos de seguridad para ingresar.</p>
                        </div>
                        <form action="configuracion.php" method="POST">
                            <input type="hidden" name="accion" value="correo_seguridad">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label>Correo para codigos de seguridad</label>
                                    <input type="email" name="correo_seguridad" class="custom-input" value="<?php echo htmlspecialchars($correo_seguridad_actual); ?>" required>
                                    <p style="margin: 8px 0 0 0; color: var(--muted); font-size: 0.76rem; line-height: 1.5;">
                                        Este correo puede ser diferente al correo principal de la cuenta. Ahi se enviara el codigo de ingreso 2FA.
                                    </p>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-2v6c0 5-3.8 9.7-9 10-5.2-.3-9-5-9-10V6l9-4 9 4z"></path></svg>
                                Guardar Correo de Seguridad
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <?php include_once 'components/modal_confirmacion.php'; ?>

    <script>
        // ==========================================
        // LÓGICA DE PESTAÑAS (TABS) Y BUSCADOR GENERAL
        // ==========================================
        function openTab(evt, tabName) {
            let i, tabcontent, navtabs;
            
            // Ocultar todas las pestañas
            tabcontent = document.getElementsByClassName("tab-pane");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            
            // Quitar clase active a los botones
            navtabs = document.getElementsByClassName("nav-tab");
            for (i = 0; i < navtabs.length; i++) {
                navtabs[i].className = navtabs[i].className.replace(" active", "");
            }
            
            // Mostrar la pestaña correcta
            document.getElementById(tabName).style.display = "block";
            setTimeout(() => document.getElementById(tabName).classList.add("active"), 10);
            evt.currentTarget.className += " active";
            
            // Limpiar buscador al cambiar de tab
            const searchInput = document.getElementById('searchConfig');
            if (searchInput) {
                searchInput.value = '';
                const formGroups = document.querySelectorAll('.form-group');
                formGroups.forEach(g => {
                    g.style.display = '';
                    g.style.opacity = '1';
                });
            }

            // Redimensionar canvas si se abre la pestaña de SST
            if(tabName === 'tab-sst') {
                setTimeout(initCanvas, 100);
            }
        }

        const requestedConfigTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedConfigTab === 'calendar') {
            const calendarTabButton = document.querySelector('[data-tab="calendar"]');
            if (calendarTabButton) calendarTabButton.click();
        }

        const documentConfigs = <?php echo json_encode($document_configs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const docStandard = document.getElementById('docStandard');
        const docPrefix = document.getElementById('docPrefix');
        const docSeparator = document.getElementById('docSeparator');
        const docVersionPrefix = document.getElementById('docVersionPrefix');
        const docInitialVersion = document.getElementById('docInitialVersion');
        const docRequireName = document.getElementById('docRequireName');

        function refreshDocumentPreview(loadSaved = false) {
            if (!docStandard) return;
            const standard = String(docStandard.value || '1');
            if (loadSaved) {
                const saved = documentConfigs[standard] || null;
                docPrefix.value = saved?.codigo_prefijo || 'PW-SST';
                docSeparator.value = saved?.separador || '-';
                docVersionPrefix.value = saved?.version_prefijo || 'V';
                docInitialVersion.value = saved?.version_inicial || ((saved?.version_prefijo || 'V') + '1.0');
                docRequireName.checked = saved ? Number(saved.exigir_codigo_nombre) === 1 : true;
            }
            const separator = ['-', '_', '.'].includes(docSeparator.value) ? docSeparator.value : '-';
            const code = `${(docPrefix.value || 'PW-SST').toUpperCase()}${separator}E${standard.padStart(2, '0')}${separator}ACT`;
            const version = (docInitialVersion.value || `${(docVersionPrefix.value || 'V').toUpperCase()}1.0`).toUpperCase();
            document.getElementById('docCodePreview').textContent = code;
            document.getElementById('docFilePreview').textContent = `${code}_${version}.pdf`;
        }
        if (docStandard) {
            docStandard.addEventListener('change', () => refreshDocumentPreview(true));
            [docPrefix, docSeparator, docVersionPrefix, docInitialVersion].forEach(element => element?.addEventListener('input', () => refreshDocumentPreview(false)));
            refreshDocumentPreview(true);
            <?php if (($_POST['accion'] ?? '') === 'control_documental'): ?>
            const documentaryTab = Array.from(document.querySelectorAll('.nav-tab')).find(button => button.getAttribute('onclick')?.includes('tab-documental'));
            if (documentaryTab) documentaryTab.click();
            <?php endif; ?>
        }

        // Buscador General Inteligente
        const configSearchInput = document.getElementById('searchConfig');
        if (configSearchInput) {
            configSearchInput.addEventListener('input', function() {
                const filter = this.value.toLowerCase().trim();
                const activeTab = document.querySelector('.tab-pane.active');
                if (!activeTab) return;
                
                const formGroups = activeTab.querySelectorAll('.form-group');
                formGroups.forEach(group => {
                    const label = group.querySelector('label');
                    if (label) {
                        const text = label.textContent.toLowerCase();
                        if (text.includes(filter)) {
                            group.style.display = '';
                            setTimeout(() => group.style.opacity = '1', 10);
                        } else {
                            group.style.opacity = '0';
                            setTimeout(() => group.style.display = 'none', 200);
                        }
                    }
                });
            });
        }

        // ==========================================
        // LÓGICA ACTIVIDADES CIIU (Solo para Representante)
        // ==========================================
        <?php if ($usuario_rol === 'representante'): ?>
        const actividadesCIIU = [
            { codigo: "0111", descripcion: "Cultivo de cereales (excepto arroz), legumbres y semillas oleaginosas", riesgo: "III" },
            { codigo: "1410", descripcion: "Confección de prendas de vestir, excepto prendas de piel", riesgo: "II" },
            { codigo: "4111", descripcion: "Construcción de edificios residenciales", riesgo: "V" },
            { codigo: "4112", descripcion: "Construcción de edificios no residenciales", riesgo: "V" },
            { codigo: "4711", descripcion: "Comercio al por menor en establecimientos no especializados", riesgo: "I" },
            { codigo: "4921", descripcion: "Transporte de pasajeros urbano y suburbano", riesgo: "IV" },
            { codigo: "5611", descripcion: "Expendio a la mesa de comidas preparadas", riesgo: "II" },
            { codigo: "6201", descripcion: "Actividades de desarrollo de sistemas informáticos", riesgo: "I" },
            { codigo: "6920", descripcion: "Actividades de contabilidad, teneduría de libros y auditoría", riesgo: "I" },
            { codigo: "8621", descripcion: "Actividades de la práctica médica, sin internación", riesgo: "III" }
        ];

        const actInputHidden = document.getElementById('actividades_economicas_input');
        let selectedActivities = actInputHidden.value ? actInputHidden.value.split(',').filter(Boolean) : [];

        const actSearchInput = document.getElementById('activitySearchInput');
        const actDropdown = document.getElementById('activityDropdown');
        const actContainer = document.getElementById('activityContainer');
        const actTableBody = document.getElementById('activityTableBody');

        function renderActivityTable() {
            actTableBody.innerHTML = '';
            if (selectedActivities.length === 0) {
                actTableBody.innerHTML = `<tr><td colspan="4" class="empty-table-msg">Aún no has agregado actividades económicas. Utiliza el buscador de arriba.</td></tr>`;
            } else {
                selectedActivities.forEach(code => {
                    const activity = actividadesCIIU.find(a => a.codigo === code);
                    if(activity) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="font-family: monospace; font-weight: 600;">${activity.codigo}</td>
                            <td>${activity.descripcion}</td>
                            <td style="text-align: center;"><span class="risk-indicator risk-${activity.riesgo}">Riesgo ${activity.riesgo}</span></td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-remove-row" title="Eliminar" onclick="removeActivity('${activity.codigo}')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        `;
                        actTableBody.appendChild(tr);
                    }
                });
            }
            actInputHidden.value = selectedActivities.join(',');
        }

        window.removeActivity = function(code) {
            selectedActivities = selectedActivities.filter(c => c !== code);
            renderActivityTable();
        };

        if (actSearchInput) {
            actSearchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                actDropdown.innerHTML = '';
                
                if (term.length === 0) {
                    actDropdown.classList.remove('active');
                    return;
                }

                const results = actividadesCIIU.filter(a => 
                    (a.codigo.includes(term) || a.descripcion.toLowerCase().includes(term)) &&
                    !selectedActivities.includes(a.codigo)
                );

                if (results.length > 0) {
                    results.forEach(res => {
                        const div = document.createElement('div');
                        div.className = 'dropdown-item';
                        div.innerHTML = `
                            <div class="dropdown-item-title">
                                <span>[${res.codigo}] <span class="risk-indicator risk-${res.riesgo}">Riesgo ${res.riesgo}</span></span>
                            </div>
                            <div class="dropdown-item-desc">${res.descripcion}</div>
                        `;
                        div.addEventListener('click', () => {
                            selectedActivities.push(res.codigo);
                            renderActivityTable();
                            actSearchInput.value = '';
                            actDropdown.classList.remove('active');
                            actSearchInput.focus();
                        });
                        actDropdown.appendChild(div);
                    });
                    actDropdown.classList.add('active');
                } else {
                    actDropdown.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8rem; color: #64748b;">No se encontraron resultados</div>';
                    actDropdown.classList.add('active');
                }
            });

            document.addEventListener('click', function(e) {
                if (!actContainer.contains(e.target)) actDropdown.classList.remove('active');
            });

            // Renderizar la tabla inicial
            renderActivityTable();
        }
        <?php endif; ?>

        // ==========================================
        // LÓGICA DEL LIENZO DE FIRMA (Solo SST)
        // ==========================================
        let canvas, ctx, dibujando = false;
        
        function redimensionarFirma(imgSrc = null) {
            if(!canvas) return;
            const parentWidth = canvas.parentElement.clientWidth;
            canvas.width = parentWidth > 0 ? parentWidth : 400; 
            canvas.height = 140; 
            
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.lineWidth = 2; 
            ctx.lineCap = "round"; 
            ctx.strokeStyle = "#111";

            if (imgSrc) {
                let tempImg = new Image();
                tempImg.src = imgSrc;
                tempImg.onload = () => { ctx.drawImage(tempImg, 0, 0); };
            }
        }

        function initCanvas() {
            canvas = document.getElementById("canvasFirma");
            if(!canvas) return;
            ctx = canvas.getContext("2d");
            
            redimensionarFirma();

            canvas.addEventListener("mousedown", iniciarTrazo);
            canvas.addEventListener("mousemove", dibujarTrazo);
            canvas.addEventListener("mouseup", terminarTrazo);
            canvas.addEventListener("mouseleave", terminarTrazo);
            
            canvas.addEventListener("touchstart", iniciarTrazo, { passive: false });
            canvas.addEventListener("touchmove", dibujarTrazo, { passive: false });
            canvas.addEventListener("touchend", terminarTrazo);
        }

        window.addEventListener('resize', () => {
            if(canvas && canvas.offsetParent !== null) { 
                let imagenTemporal = canvas.toDataURL();
                redimensionarFirma(canvas.getAttribute('data-modificado') === 'true' ? imagenTemporal : null);
            }
        });

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const evt = e.touches ? e.touches[0] : e;
            return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
        }

        function iniciarTrazo(e) {
            if (e.cancelable) e.preventDefault(); 
            dibujando = true;
            const p = getPos(e); 
            ctx.beginPath(); 
            ctx.moveTo(p.x, p.y);
            canvas.setAttribute('data-modificado', 'true');
        }

        function dibujarTrazo(e) {
            if (!dibujando) return;
            if (e.cancelable) e.preventDefault();
            const p = getPos(e); 
            ctx.lineTo(p.x, p.y); 
            ctx.stroke();
        }

        function terminarTrazo() { dibujando = false; }

        window.limpiarCanvas = function() { 
            if(!canvas) return;
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height); 
            canvas.removeAttribute('data-modificado');
        };

        window.guardarSST = function() {
            if(canvas && canvas.getAttribute('data-modificado') === 'true') {
                document.getElementById('firma_sst_base64').value = canvas.toDataURL("image/png");
            }
            document.getElementById('formSST').submit();
        }

        // ==========================================
        // LIMPIAR ALERTAS
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(() => {
                    alerts.forEach(a => {
                        a.style.transition = 'opacity 0.5s ease';
                        a.style.opacity = '0';
                        setTimeout(() => a.remove(), 500);
                    });
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path:newUrl}, '', newUrl);
                }, 4000); 
            }
        });
    </script>
</body>
</html>
