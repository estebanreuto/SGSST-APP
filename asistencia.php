<?php
require_once 'config/db.php';

// ==========================================
// 0. API INVISIBLE PARA AUTO-COMPLETAR DATOS
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'buscar_trabajador') {
    header('Content-Type: application/json');
    $cedula = trim($_GET['cedula'] ?? '');
    $emp_id = intval($_GET['empresa_id'] ?? 0);
    
    if ($cedula !== '' && $emp_id > 0) {
        try {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE cedula = ? AND empresa_id = ? AND rol = 'trabajador' LIMIT 1");
            $stmt->execute([$cedula, $emp_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode([
                    'success' => true, 
                    'nombre' => $user['nombre'].' '.$user['apellido'], 
                    'email' => $user['email'] ?? '', 
                    'cargo' => $user['cargo'] ?? ''
                ]);
                exit;
            }
        } catch(PDOException $e) {}
    }
    echo json_encode(['success' => false]);
    exit;
}

// ==========================================
// 1. VALIDAR ID DE LA ACTIVIDAD Y EMPRESA
// ==========================================
$actividad_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$actividad = null;
$error_msg = "";
$success_msg = "";

if ($actividad_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM actividades_capacitacion WHERE id = ?");
        $stmt->execute([$actividad_id]);
        $actividad = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actividad) {
            $error_msg = "La actividad a la que intentas acceder no existe o fue eliminada.";
        } else {
            // Traer datos de la empresa para personalizar el formulario
            $stmt_emp = $conn->prepare("SELECT nombre_empresa, logo_empresa FROM usuarios WHERE empresa_id = ? AND rol = 'representante' LIMIT 1");
            $stmt_emp->execute([$actividad['empresa_id']]);
            $emp_data = $stmt_emp->fetch(PDO::FETCH_ASSOC);
            
            $actividad['nombre_empresa'] = $emp_data['nombre_empresa'] ?? 'La Empresa';
            $actividad['logo_empresa'] = $emp_data['logo_empresa'] ?? '';
        }
    } catch (PDOException $e) {
        $error_msg = "Error de conexión con la base de datos.";
    }
} else {
    // Sin ID válido, mostramos error
    $error_msg = "Enlace de asistencia no válido. Por favor, solicita un nuevo enlace a tu Responsable SG-SST.";
}

// ==========================================
// VALIDACIÓN DE TABLA NUEVA (ALERTA DEV)
// ==========================================
$mostrar_alerta_db = false;
try {
    $stmt_check = $conn->query("SHOW COLUMNS FROM asistencias_capacitacion LIKE 'correo'");
    if (!$stmt_check->fetch()) {
        $mostrar_alerta_db = true;
    }
} catch (Exception $e) { $mostrar_alerta_db = true; }

// ==========================================
// 2. PROCESAR EL FORMULARIO DE ASISTENCIA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_asistencia') {
    $act_id = intval($_POST['actividad_id']);
    $cedula = trim($_POST['cedula']);
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo'] ?? '');
    $cargo  = trim($_POST['cargo'] ?? '');
    $firma_base64 = $_POST['firma_base64'] ?? '';

    if (empty($cedula) || empty($nombre) || empty($firma_base64)) {
        $error_msg = "Por favor, completa los campos obligatorios y dibuja tu firma.";
    } else {
        try {
            // Verificar duplicados
            $stmt_check_asis = $conn->prepare("SELECT id FROM asistencias_capacitacion WHERE actividad_id = ? AND cedula = ?");
            $stmt_check_asis->execute([$act_id, $cedula]);
            
            if ($stmt_check_asis->fetch()) {
                $error_msg = "Ya hay un registro de asistencia con esta cédula para esta capacitación.";
            } else {
                // Registrar asistencia
                $stmt_insert = $conn->prepare("INSERT INTO asistencias_capacitacion (actividad_id, cedula, nombre_completo, correo, cargo, firma, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt_insert->execute([$act_id, $cedula, $nombre, $correo, $cargo, $firma_base64]);
                
                header("Location: asistencia.php?id=" . $act_id . "&status=success");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Error al guardar la asistencia. Comunícate con soporte técnico.";
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success_msg = "¡Asistencia registrada con éxito! Ya puedes cerrar esta ventana.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro de Asistencia | <?php echo htmlspecialchars($actividad['nombre_empresa'] ?? 'SG-SST'); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 10px; --blue-dark: #1e3a8a; }
        * { box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--bg1), var(--bg2)); 
            margin: 0; padding: 20px 16px 40px 16px; min-height: 100vh; 
            color: var(--text); 
            display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
        }

        /* CONTENEDOR COMPACTO Y PROFESIONAL */
        .auth-wrapper { width: 100%; max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }

        /* BRANDING DE EMPRESA */
        .brand-logo { text-align: center; margin-bottom: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; width: 100%;}
        .brand-logo img { max-width: 120px; max-height: 50px; object-fit: contain; }
        .brand-logo .placeholder-logo { width: 44px; height: 44px; background: var(--primary); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); }
        .brand-logo h1 { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--blue-dark); letter-spacing: -0.01em; }

        /* ALERTAS */
        .alert-container { width: 100%; max-width: 900px; margin: 0 auto; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; font-size: 0.8rem; line-height: 1.4; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .alert i { font-size: 1rem; margin-top: 2px; }
        .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-dev { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; flex-direction: column; }

        /* MAIN GRID */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr; /* Móvil */
            gap: 20px;
            width: 100%;
            align-items: start;
        }

        /* TARJETA DEL EVENTO */
        .event-card { background: #ffffff; border-radius: var(--radius); padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); position: relative; overflow: hidden; height: fit-content; }
        .event-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary); }
        .event-badge { background: rgba(255,138,31,0.1); color: var(--primary2); padding: 4px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; display: inline-block; margin-bottom: 10px; letter-spacing: 0.05em;}
        .event-title { margin: 0 0 16px 0; font-size: 1.05rem; color: var(--blue-dark); font-weight: 800; line-height: 1.3;}
        
        .event-detail-grid { display: flex; flex-direction: column; gap: 10px; }
        .event-detail { display: flex; align-items: flex-start; gap: 8px; color: #334155; font-size: 0.8rem; font-weight: 600; line-height: 1.4;}
        .event-detail i { color: #94a3b8; width: 14px; text-align: center; margin-top: 2px; font-size: 0.9rem;}
        .event-desc { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 0.8rem; color: var(--muted); line-height: 1.5; font-weight: 500;}

        /* FORMULARIO */
        .form-card { background: #ffffff; border-radius: var(--radius); padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border); }
        .form-title { margin: 0 0 20px 0; font-size: 1rem; color: var(--blue-dark); font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: 0.02em;}

        /* GRILLA DE 2 COLUMNAS PARA INPUTS */
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 20px; }
        
        .form-group { width: 100%; }
        .form-group label { display: block; font-size: 0.7rem; font-weight: 800; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .input-wrapper { position: relative; display: flex; align-items: center;}
        .input-wrapper i.field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 0.9rem;}
        .form-control { width: 100%; padding: 10px 12px 10px 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: var(--text); background: #f8fafc; transition: all 0.2s; font-weight: 600; box-sizing: border-box;}
        .form-control:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
        .form-control::placeholder { color: #94a3b8; font-weight: 400; }

        /* Loader para Auto-completado */
        .loader-input { position: absolute; right: 12px; border: 2px solid #e2e8f0; border-top-color: var(--primary); border-radius: 50%; width: 14px; height: 14px; animation: spin 1s linear infinite; display: none; }

        /* CANVAS DE FIRMA */
        .signature-section { margin-top: 16px; margin-bottom: 20px; }
        .signature-container { position: relative; width: 100%; border: 1px dashed #cbd5e1; border-radius: 8px; background: #ffffff; overflow: hidden; transition: border-color 0.3s; }
        .signature-container.active { border-color: var(--primary); border-style: solid; background: #fffaf5; }
        .signature-canvas { width: 100%; height: 120px; display: block; touch-action: none; cursor: crosshair; }
        .signature-placeholder { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #cbd5e1; font-weight: 600; font-size: 0.85rem; pointer-events: none; transition: opacity 0.2s; display: flex; flex-direction: column; align-items: center; gap: 6px;}
        .signature-placeholder i { font-size: 1.2rem; }
        
        .btn-clear-sig { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: inherit; margin-top: 8px; display: inline-flex; align-items: center; gap: 6px;}
        .btn-clear-sig:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

        /* BOTÓN SUBMIT */
        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; width: 100%; cursor: pointer; transition: all 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.2); text-transform: uppercase; letter-spacing: 0.05em;}
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.3); }

        /* LOADER OVERLAY */
        #loader { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); z-index: 9999; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        #loader.active { display: flex; }
        .spinner { font-size: 2.5rem; color: var(--primary); animation: spin 1s infinite linear; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* SUCCESS STATE */
        .success-state { text-align: center; padding: 30px 20px; width: 100%; max-width: 500px; margin: 0 auto;}
        .success-icon { width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 16px auto; }
        .success-title { color: var(--blue-dark); font-size: 1.2rem; font-weight: 800; margin: 0 0 8px 0; letter-spacing: -0.01em;}
        .success-desc { color: var(--muted); font-size: 0.85rem; line-height: 1.5; margin: 0; font-weight: 500;}

        /* =========================================
           RESPONSIVE DESIGN (2 COLUMNAS COMPACTAS EN PC)
           ========================================= */
        @media (min-width: 800px) {
            body { padding: 30px; }
            .brand-logo { margin-bottom: 30px; }
            
            /* Grilla principal de PC (Tarjeta a la izq, Form a la der) */
            .main-grid {
                grid-template-columns: 320px 1fr;
                gap: 24px;
            }
            .event-card { position: sticky; top: 30px; }
            
            /* El formulario adentro usa 2 columnas para quedar súper limpio y pro */
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .signature-canvas { height: 140px; } /* Altura sutil, sin exagerar */
            .btn-submit { width: auto; padding: 12px 32px; float: right;} /* Botón a la derecha */
            
            /* Fix clearfix for button */
            .form-card form::after {
                content: "";
                clear: both;
                display: table;
            }
        }
    </style>
</head>
<body>

    <div id="loader">
        <i class="fa-solid fa-circle-notch spinner"></i>
        <h3 style="margin-top:16px; font-size:1rem; color: var(--blue-dark); font-weight:800;">Guardando tu firma...</h3>
    </div>

    <div class="auth-wrapper">
        
        <?php if ($actividad || !empty($success_msg)): ?>
            <div class="brand-logo">
                <?php if (!empty($actividad['logo_empresa'])): ?>
                    <img src="<?php echo htmlspecialchars($actividad['logo_empresa']); ?>" alt="Logo Empresa">
                <?php else: ?>
                    <div class="placeholder-logo"><i class="fa-solid fa-building-shield"></i></div>
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($actividad['nombre_empresa'] ?? 'SG-SST Pro'); ?></h1>
            </div>
        <?php endif; ?>

        <div class="alert-container">
            <?php if ($mostrar_alerta_db): ?>
                <div class="alert alert-dev">
                    <div style="display:flex; gap: 8px; font-weight: 800; margin-bottom: 6px;">
                        <i class="fa-solid fa-code"></i> Alerta de Desarrollador
                    </div>
                    Actualiza la tabla en phpMyAdmin ejecutando este SQL:
                    <code style="background: rgba(0,0,0,0.05); padding: 8px; border-radius: 6px; display: block; margin-top: 6px; font-size: 0.7rem; font-family: monospace; word-break: break-all;">
                        ALTER TABLE asistencias_capacitacion ADD COLUMN correo VARCHAR(150) NULL AFTER nombre_completo, ADD COLUMN cargo VARCHAR(100) NULL AFTER correo;
                    </code>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="justify-content: center; text-align: center; flex-direction: column; align-items: center; padding: 24px;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; margin-bottom: 8px;"></i>
                    <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($error_msg); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="form-card success-state">
                <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                <h2 class="success-title">¡Registro Exitoso!</h2>
                <p class="success-desc">Tu asistencia ha sido guardada encriptada en el sistema. Ya puedes cerrar esta pestaña.</p>
            </div>
        <?php elseif ($actividad): ?>
            
            <div class="main-grid">
                
                <div>
                    <div class="event-card">
                        <span class="event-badge"><?php echo htmlspecialchars($actividad['categoria']); ?></span>
                        <h2 class="event-title"><?php echo htmlspecialchars($actividad['nombre_actividad']); ?></h2>
                        
                        <div class="event-detail-grid">
                            <div class="event-detail">
                                <i class="fa-regular fa-calendar"></i>
                                <?php 
                                    $inicio = date('d M Y, g:i a', strtotime($actividad['fecha_inicio']));
                                    $fin = !empty($actividad['fecha_fin']) ? ' - ' . date('g:i a', strtotime($actividad['fecha_fin'])) : '';
                                    echo $inicio . $fin; 
                                ?>
                            </div>
                            <div class="event-detail">
                                <i class="fa-solid fa-location-dot"></i>
                                <?php echo htmlspecialchars($actividad['modalidad']); ?> 
                                <?php echo !empty($actividad['lugar_exacto']) ? '('.htmlspecialchars($actividad['lugar_exacto']).')' : ''; ?>
                            </div>
                        </div>

                        <?php if(!empty($actividad['descripcion'])): ?>
                            <div class="event-desc">
                                <?php echo nl2br(htmlspecialchars($actividad['descripcion'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="form-card">
                        <h3 class="form-title">Firmar Asistencia</h3>
                        
                        <form id="formAsistencia" method="POST" action="asistencia.php?id=<?php echo $actividad_id; ?>">
                            <input type="hidden" name="accion" value="registrar_asistencia">
                            <input type="hidden" name="actividad_id" value="<?php echo $actividad_id; ?>">
                            <input type="hidden" name="firma_base64" id="firmaBase64">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Número de Cédula</label>
                                    <div class="input-wrapper">
                                        <i class="fa-regular fa-address-card field-icon"></i>
                                        <input type="number" name="cedula" id="inputCedula" class="form-control" placeholder="Ej. 1002345678" required autocomplete="off">
                                        <div class="loader-input" id="loaderCedula"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Nombre Completo</label>
                                    <div class="input-wrapper">
                                        <i class="fa-regular fa-user field-icon"></i>
                                        <input type="text" name="nombre" id="inputNombre" class="form-control" placeholder="Tus nombres y apellidos" required autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Correo (Opcional)</label>
                                    <div class="input-wrapper">
                                        <i class="fa-regular fa-envelope field-icon"></i>
                                        <input type="email" name="correo" id="inputCorreo" class="form-control" placeholder="usuario@email.com" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Cargo (Opcional)</label>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-briefcase field-icon"></i>
                                        <input type="text" name="cargo" id="inputCargo" class="form-control" placeholder="Tu área o cargo" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="signature-section">
                                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:6px;">
                                    <label style="margin:0;">Firma Digital (Obligatorio)</label>
                                    <button type="button" class="btn-clear-sig" onclick="limpiarFirma()" style="margin:0; padding:4px 8px; font-size:0.65rem;">
                                        <i class="fa-solid fa-eraser"></i> Borrar
                                    </button>
                                </div>
                                <div class="signature-container" id="sigContainer">
                                    <div class="signature-placeholder" id="sigPlaceholder">
                                        <i class="fa-solid fa-pen-nib"></i>
                                        Dibuja tu firma aquí
                                    </div>
                                    <canvas id="canvasFirma" class="signature-canvas"></canvas>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit" onclick="prepararEnvio(event)">
                                <i class="fa-solid fa-file-signature"></i> Guardar Asistencia
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>

    <script>
        // ==========================================
        // AUTO-COMPLETADO AJAX INTELIGENTE
        // ==========================================
        const inputCedula = document.getElementById('inputCedula');
        const inputNombre = document.getElementById('inputNombre');
        const inputCorreo = document.getElementById('inputCorreo');
        const inputCargo  = document.getElementById('inputCargo');
        const loaderCedula = document.getElementById('loaderCedula');
        
        let timeoutBuscador;

        if (inputCedula) {
            inputCedula.addEventListener('input', function() {
                const cedula = this.value.trim();
                clearTimeout(timeoutBuscador);
                
                if(cedula.length >= 6) {
                    loaderCedula.style.display = 'block'; 
                    timeoutBuscador = setTimeout(() => {
                        fetch(`asistencia.php?action=buscar_trabajador&cedula=${cedula}&empresa_id=<?php echo $actividad['empresa_id'] ?? 0; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            loaderCedula.style.display = 'none'; 
                            if(data.success) {
                                if(data.nombre && inputNombre.value === '') inputNombre.value = data.nombre;
                                if(data.email && inputCorreo.value === '') inputCorreo.value = data.email;
                                if(data.cargo && inputCargo.value === '') inputCargo.value = data.cargo;
                                inputNombre.style.borderColor = '#10b981';
                                setTimeout(() => inputNombre.style.borderColor = '', 1500);
                            }
                        }).catch(err => { loaderCedula.style.display = 'none'; });
                    }, 400);
                } else {
                    loaderCedula.style.display = 'none';
                }
            });
        }

        // ==========================================
        // LÓGICA DEL CANVAS PARA FIRMAR
        // ==========================================
        const canvas = document.getElementById('canvasFirma');
        const container = document.getElementById('sigContainer');
        const placeholder = document.getElementById('sigPlaceholder');
        const inputFirma = document.getElementById('firmaBase64');
        const form = document.getElementById('formAsistencia');
        let ctx, isDrawing = false, hasSignature = false;

        if (canvas) {
            ctx = canvas.getContext('2d');

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                ctx.scale(ratio, ratio);
                
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#0f172a';
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas(); 

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return { x: clientX - rect.left, y: clientY - rect.top };
            }

            function startPosition(e) {
                if(e.cancelable) e.preventDefault(); 
                isDrawing = true;
                hasSignature = true;
                placeholder.style.opacity = '0';
                container.classList.add('active');
                
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }

            function draw(e) {
                if (!isDrawing) return;
                if(e.cancelable) e.preventDefault(); 
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }

            function endPosition() { isDrawing = false; }

            canvas.addEventListener('mousedown', startPosition);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', endPosition);
            canvas.addEventListener('mouseleave', endPosition);
            canvas.addEventListener('touchstart', startPosition, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', endPosition);
        }

        window.limpiarFirma = function() {
            if(!ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            placeholder.style.opacity = '1';
            container.classList.remove('active');
        }

        window.prepararEnvio = function(e) {
            if(!form.checkValidity()) return; 
            
            if (!hasSignature) {
                e.preventDefault();
                alert('Por favor, dibuja tu firma en el recuadro para registrar tu asistencia.');
                return;
            }

            inputFirma.value = canvas.toDataURL("image/png");
            document.getElementById('loader').classList.add('active');
        }
    </script>
</body>
</html>