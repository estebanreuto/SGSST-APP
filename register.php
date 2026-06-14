<?php
require_once "config/db.php";

$registro_pendiente = false; 
$error_msg = ""; 

// ================================================================
// TRAER LOS PLANES Y SUS CARACTERÍSTICAS PARA LA VISTA PREVIA
// ================================================================
$stmt_planes = $conn->query("SELECT * FROM planes ORDER BY precio_normal ASC");
$planes_db = $stmt_planes->fetchAll(PDO::FETCH_ASSOC);

$planes_info = [];
foreach ($planes_db as $plan) {
    $stmt_feat = $conn->prepare("SELECT * FROM plan_caracteristicas WHERE plan_id = ? ORDER BY id ASC");
    $stmt_feat->execute([$plan['id']]);
    $features_db = $stmt_feat->fetchAll(PDO::FETCH_ASSOC);
    
    $features = [];
    foreach ($features_db as $f) {
        $features[] = [
            'texto' => $f['texto'],
            'incluido' => (bool)$f['incluido']
        ];
    }
    $plan['features'] = $features;
    $planes_info[] = $plan;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST["autorizacion"]) || $_POST["autorizacion"] !== "si") {
        $error_msg = "Debe aceptar la autorización de tratamiento de datos personales.";
    } else {
        $rol = $_POST["rol"] ?? '';
        $empresa_nit = trim($_POST["empresa_nit"] ?? '');
        $plan_id = $_POST["plan_id"] ?? null;

        // =========================
        // LÓGICA PARA REPRESENTANTE
        // =========================
        if ($rol === 'representante') {
            if (empty($plan_id)) {
                $error_msg = "Debe seleccionar un plan de suscripción para registrar su empresa.";
            } else {
                $sql = "INSERT INTO solicitudes_empresas (
                    nombre, apellido, cedula, email, telefono,
                    direccion, ciudad, barrio, localidad, firma, plan_id, estado
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,'pendiente')";

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $_POST["nombre"], $_POST["apellido"], $_POST["cedula"], $_POST["email"],
                    $_POST["telefono"], $_POST["direccion"] ?? null, $_POST["ciudad"] ?? null,
                    $_POST["barrio"] ?? null, $_POST["localidad"] ?? null, $_POST["firmaDigital"], $plan_id
                ]);

                // Capturamos el ID de la solicitud recién creada
                $solicitud_id = $conn->lastInsertId();

                // MAGIA: Enviar directamente a la pasarela de pagos
                header("Location: checkout_wompi.php?id=" . $solicitud_id);
                exit;
            }
        } 
        // =========================
        // LÓGICA PARA SST Y TRABAJADOR
        // =========================
        else {
            if (empty($empresa_nit)) {
                $error_msg = "Debes ingresar el NIT de la empresa a la que perteneces.";
            } else {
                $stmt_buscar = $conn->prepare("SELECT id FROM solicitudes_empresas WHERE cedula = ? AND estado = 'aprobada'");
                $stmt_buscar->execute([$empresa_nit]);
                $empresa_data = $stmt_buscar->fetch(PDO::FETCH_ASSOC);

                if (!$empresa_data) {
                    $error_msg = "No se encontró ninguna empresa aprobada con el NIT ingresado ($empresa_nit).";
                } else {
                    $empresa_id = $empresa_data['id'];

                    $stmt_plan = $conn->prepare("
                        SELECT se.estado, se.trabajadores_extra, p.trabajadores as limite_base 
                        FROM solicitudes_empresas se 
                        LEFT JOIN planes p ON se.plan_id = p.id 
                        WHERE se.id = ?
                    ");
                    $stmt_plan->execute([$empresa_id]);
                    $plan_empresa = $stmt_plan->fetch(PDO::FETCH_ASSOC);

                    if (!$plan_empresa || $plan_empresa['estado'] !== 'aprobada') {
                        $error_msg = "La empresa vinculada a este NIT no tiene una suscripción activa.";
                    } else {
                        
                        if ($rol === 'sst') {
                            $stmt_check_sst = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND rol = 'sst'");
                            $stmt_check_sst->execute([$empresa_id]);
                            if ($stmt_check_sst->fetchColumn() >= 1) {
                                $error_msg = "Acceso Denegado: Esta empresa ya tiene un Responsable SG-SST registrado.";
                            }
                        }

                        if ($rol === 'trabajador' && empty($error_msg)) {
                            $limite_base = $plan_empresa['limite_base'] ?? 0;
                            $extras = $plan_empresa['trabajadores_extra'] ?? 0;
                            $limite_total = $limite_base + $extras;

                            if ($limite_base != 999) {
                                $stmt_check_trab = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador'");
                                $stmt_check_trab->execute([$empresa_id]);
                                $trabajadores_actuales = $stmt_check_trab->fetchColumn();

                                if ($trabajadores_actuales >= $limite_total) {
                                    $error_msg = "Acceso Denegado: La empresa ha alcanzado el límite máximo de trabajadores de su plan ($limite_total).";
                                }
                            }
                        }

                        if (empty($error_msg)) {
                            $sql = "INSERT INTO usuarios (
                                empresa_id, nombre, apellido, cedula, email, telefono, rol,
                                licencia_sst, tipo_licencia, numero_licencia, fecha_licencia, expedida_por,
                                direccion, ciudad, barrio, localidad, firma
                            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                            $stmt = $conn->prepare($sql);
                            $stmt->execute([
                                $empresa_id, 
                                $_POST["nombre"], $_POST["apellido"], $_POST["cedula"], $_POST["email"], $_POST["telefono"], $rol,
                                $_POST["licencia"] ?? null, $_POST["tipoLicencia"] ?? null, $_POST["numLicencia"] ?? null,
                                $_POST["fechaLicencia"] ?? null, $_POST["expedida"] ?? null, $_POST["direccion"] ?? null,
                                $_POST["ciudad"] ?? null, $_POST["barrio"] ?? null, $_POST["localidad"] ?? null, $_POST["firmaDigital"]
                            ]);

                            $usuario_id = $conn->lastInsertId();

                            if ($rol === "trabajador") {
                                $sql2 = "INSERT INTO encuesta_sociodemografica (
                                    usuario_id, edad, estado_civil, genero, personas_cargo,
                                    escolaridad, vivienda, tiempo_libre, experiencia, estrato,
                                    convive_con, raza, tipo_contrato, turno, antiguedad,
                                    enfermedad, fuma, alcohol, deporte, tipo_personal
                                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                                $stmt2 = $conn->prepare($sql2);
                                $stmt2->execute([
                                    $usuario_id,
                                    $_POST["edad"], $_POST["estado_civil"], $_POST["genero"], $_POST["personas_cargo"],
                                    $_POST["escolaridad"], $_POST["vivienda"], $_POST["tiempo_libre"], $_POST["experiencia"],
                                    $_POST["estrato"], $_POST["convive_con"], $_POST["raza"], $_POST["tipo_contrato"],
                                    $_POST["turno"], $_POST["antiguedad"], $_POST["enfermedad"], $_POST["fuma"],
                                    $_POST["alcohol"], $_POST["deporte"], $_POST["tipo_personal"]
                                ]);
                            }

                            header("Location: login.php?registro=ok");
                            exit;
                        }
                    }
                }
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
    <title>Registro | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #ff8a1f; --primary2: #ff7a00;
            --bg-top: #e8f0f8; --bg-mid: #f3f7fb; --bg-bottom: #ffffff;
            --blue-main: #2b5a9e; --blue-dark: #1e3a8a;
            --card-bg: rgba(255, 255, 255, 0.85); --card-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 25px 50px -12px rgba(43, 90, 158, 0.15);
            --text-main: #1f2d3d; --text-muted: #5f6f82; --border: #dbe3ec; --radius: 18px;
        }

        * { box-sizing: border-box; }
        html { max-width: 100%; overflow-x: hidden; }

        body { margin: 0; font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%); color: var(--text-main); position: relative; min-height: 100vh; overflow-x: hidden;}

        /* MARCA DE AGUA (CASCO DE SEGURIDAD) */
        .watermark-bg { position: fixed; top: 75%; left: 20%; transform: translate(-50%, -50%) rotate(-15deg); font-size: 45vh; color: var(--blue-main); opacity: 0.03; z-index: 0; pointer-events: none; }

        .blob { position: fixed; border-radius: 50%; filter: blur(80px); z-index: -1; opacity: 0.35; animation: float 12s infinite ease-in-out alternate; }
        .blob-1 { top: -5%; left: -5%; width: 50vw; height: 50vw; background: var(--blue-main); max-width: 500px; max-height: 500px; }
        .blob-2 { bottom: -10%; right: -5%; width: 60vw; height: 60vw; background: var(--primary); animation-delay: -6s; max-width: 600px; max-height: 600px; }
        @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(40px) scale(1.05); } }

        /* ANIMACIONES DE ENTRADA */
        .fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
        .delay-1 { animation-delay: 0.1s; } 
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        .wrapper { display: flex; flex-direction: row; min-height: 100vh; width: 100%; }

        .brand { flex: 0 0 42%; padding: 64px; display: flex; flex-direction: column; justify-content: center; z-index: 2; position: sticky; top: 0; height: 100vh; }
        
        /* LOGO EN LA ESQUINA SUPERIOR IZQUIERDA (ENLACE) */
        .logo-register-link {
            position: absolute;
            top: 48px;
            left: 64px;
            z-index: 10;
            display: inline-block;
            transition: transform 0.2s ease;
        }
        .logo-register-link:hover {
            transform: scale(1.03);
        }
        .logo-register-link img {
            max-width: 250px;
            height: auto;
            display: block;
        }

        /* CONTENEDOR DEL TEXTO CENTRAL Y BENEFICIOS */
        .brand-text-container {
            margin-top: 40px;
            max-width: 480px;
        }
        .brand h1 { margin: 0; font-size: clamp(1.8rem, 4vw, 2.5rem); line-height: 1.15; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.02em;}
        .brand h1 span { background: linear-gradient(135deg, var(--primary), #ff5e00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .brand p { margin: 16px 0 0; color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; }

        /* LISTA DE CARACTERÍSTICAS ANIMADA */
        .brand-features {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.95rem;
            color: var(--blue-dark);
            font-weight: 600;
        }
        .feature-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 138, 31, 0.15);
            color: var(--primary2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1);
            animation: pulseIcon 2.5s infinite alternate;
        }
        .feature-item:nth-child(2) .feature-icon { animation-delay: 0.5s; }
        .feature-item:nth-child(3) .feature-icon { animation-delay: 1s; }

        @keyframes pulseIcon {
            0% { transform: scale(1); box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1); }
            100% { transform: scale(1.1); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.25); }
        }

        .form-area { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 48px; z-index: 2; min-height: 100vh; overflow-y: auto; }
        
        .card { width: 100%; max-width: 900px; background: var(--card-bg); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--card-border); border-radius: var(--radius); box-shadow: var(--shadow-soft); padding: 40px; margin: auto; }

        .success-card { max-width: 500px; padding: 56px 48px; text-align: center; display: flex; flex-direction: column; align-items: center; animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; margin: auto;}
        .success-icon-wrapper { width: 90px; height: 90px; background: linear-gradient(135deg, rgba(255, 138, 31, 0.15), rgba(255, 122, 0, 0.05)); color: var(--primary2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; position: relative; }
        .success-icon-wrapper::after { content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%; border: 2px solid var(--primary); animation: pulse-orange 2s infinite; }
        .success-icon-wrapper svg { width: 42px; height: 42px; animation: floatingIcon 3s ease-in-out infinite; }
        .success-title { color: var(--blue-dark); font-size: 1.6rem; margin: 0 0 16px 0; font-weight: 800; letter-spacing: -0.02em; }
        .success-message { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin: 0 0 36px 0; font-weight: 400; }
        .btn-return { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #ffffff !important; padding: 14px 28px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 20px rgba(255, 138, 31, 0.25); transition: all 0.3s ease; width: 100%; justify-content: center; border: none; }
        .btn-return:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(255, 138, 31, 0.35); }

        @keyframes popIn { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        @keyframes pulse-orange { 0% { transform: scale(1); opacity: 0.8; box-shadow: 0 0 0 0 rgba(255, 138, 31, 0.4); } 70% { transform: scale(1.3); opacity: 0; box-shadow: 0 0 0 20px rgba(255, 138, 31, 0); } 100% { transform: scale(1.3); opacity: 0; box-shadow: 0 0 0 0 rgba(255, 138, 31, 0); } }
        @keyframes floatingIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }

        .header { margin-bottom: 24px; }
        .header h2 { margin: 0; font-size: 1.4rem; color: var(--blue-dark); font-weight: 800; }
        .header .hint { margin: 4px 0 0; font-size: .85rem; color: var(--text-muted); }

        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px 20px; 
            border-radius: 12px; margin-bottom: 24px; font-size: 0.85rem; font-weight: 600; 
            display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
        }

        .grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .full-width { grid-column: 1 / -1 !important; } 

        @media (min-width: 600px) { .grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 992px) { .grid { grid-template-columns: repeat(3, 1fr); } }

        .field label { display: block; font-size: .75rem; font-weight: 600; margin: 0 0 8px; color: var(--text-main); text-transform: uppercase; letter-spacing: 0.05em; }
        .label-opt { font-weight: 400; color: #94a3b8; font-size: 0.65rem; text-transform: none; letter-spacing: normal;}
        .control { position: relative; }
        .icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; opacity: .45; color: #94a3b8; pointer-events: none; transition: all .2s ease; }
        
        input, select { width: 100%; padding: 12px 14px 12px 40px; font-size: .9rem; border: 1px solid var(--border); border-radius: 10px; background: #ffffff; color: var(--text-main); transition: all .2s ease; font-family: inherit; box-sizing: border-box; font-weight: 500;}
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, .14); }
        input:focus ~ .icon, select:focus ~ .icon { opacity: .75; color: var(--primary); }

        .input-nit { border-color: var(--primary); background: #fffaf5; font-weight: 600; }
        .input-plan { border-color: var(--blue-main); background: #f8fafc; font-weight: 600; color: var(--blue-dark); cursor: pointer;}

        .membership-heading { display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; margin-bottom: 14px; }
        .membership-heading label { margin: 0; color: var(--blue-main); }
        .membership-heading p { margin: 0; color: var(--text-muted); font-size: 0.75rem; }
        .membership-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .membership-option {
            min-height: 118px; padding: 16px; border: 1px solid var(--border); border-radius: 8px;
            background: #ffffff; color: var(--text-main); display: flex; flex-direction: column;
            align-items: flex-start; justify-content: space-between; text-align: left; box-shadow: none;
        }
        .membership-option:hover { border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06); }
        .membership-option.selected { border: 2px solid var(--primary); background: #fffaf5; padding: 15px; }
        .membership-option-top { display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px; }
        .membership-option-name { font-size: 0.82rem; font-weight: 800; color: var(--blue-dark); }
        .membership-check { width: 20px; height: 20px; border: 1px solid #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: transparent; flex-shrink: 0; }
        .membership-option.selected .membership-check { background: var(--primary); border-color: var(--primary); color: #ffffff; }
        .membership-option-price { font-size: 1.05rem; font-weight: 800; color: var(--text-main); }
        .membership-option-price small { color: var(--text-muted); font-size: 0.68rem; font-weight: 600; }
        .membership-option-capacity { color: var(--text-muted); font-size: 0.7rem; font-weight: 600; }
        .membership-error { margin: 10px 0 0; color: #dc2626; font-size: 0.75rem; font-weight: 700; }

        .section { margin-top: 24px; border: 1px solid var(--card-border); background: rgba(255, 255, 255, 0.6); border-radius: 16px; padding: 24px; }
        .section-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;}
        .section-title h3 { margin: 0; font-size: 1rem; color: var(--blue-dark); font-weight: 800; text-transform: uppercase;}
        .badge { font-size: .7rem; background: rgba(43, 90, 158, 0.1); color: var(--blue-main); padding: 4px 10px; border-radius: 999px; font-weight: 700; white-space: nowrap;}
        
        .legal { margin-top: 24px; background: rgba(241, 245, 249, 0.6); border: 1px dashed var(--border); border-radius: 16px; padding: 24px; }
        .legal p { margin: 0 0 14px; font-size: .8rem; color: var(--text-muted); line-height: 1.5; }

        .signature { margin-top: 24px; }
        .signature h3 { margin: 0 0 12px; font-size: 1rem; color: var(--blue-dark); font-weight: 800; text-transform: uppercase;}
        
        .canvas-container { width: 100%; position: relative; }
        canvas { width: 100%; height: 150px; border: 2px dashed #cbd5e1; border-radius: 14px; background: #ffffff; cursor: crosshair; touch-action: none; transition: border-color 0.3s;}
        canvas:hover { border-color: var(--primary); }

        #trabajador { padding-right: 8px; }
        @media (min-width: 992px) {
            #trabajador { max-height: 600px; overflow-y: auto; }
            #trabajador::-webkit-scrollbar { width: 6px; }
            #trabajador::-webkit-scrollbar-track { background: transparent; }
            #trabajador::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        }

        .hidden { display: none !important; }

        .footer { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 16px; }
        .footer a { text-decoration: none; font-size: .85rem; color: var(--blue-main); font-weight: 700; padding: 10px 16px; border-radius: 8px; transition: all .25s ease; display: inline-flex; align-items: center; gap: 8px; }
        .footer a:hover { background: rgba(43, 90, 158, 0.1); color: var(--blue-dark); }
        
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        
        button { border: none; padding: 12px 24px; font-size: .9rem; font-weight: 700; border-radius: 8px; cursor: pointer; transition: all .25s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; }
        .btn-danger { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; }
        .btn-danger:hover { background: #f1f5f9; color: var(--text-main); }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #ffffff; box-shadow: 0 4px 12px rgba(255, 138, 31, .25); text-transform: uppercase; letter-spacing: 0.05em; padding: 14px 28px;}
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 138, 31, .35); }

        /* =========================================
           VISTA PREVIA DEL PLAN - DISEÑO PREMIUM
           ========================================= */
        .plan-preview-box {
            background: #f8fafc;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            padding: 18px;
            margin-top: 16px;
            box-shadow: none;
            position: relative;
            overflow: hidden;
            animation: popInPlan 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popInPlan {
            from { opacity: 0; transform: translateY(-10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .preview-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 1px solid #dbe3ec; padding-bottom: 14px; margin-bottom: 14px;
        }

        .preview-title-wrapper { display: flex; flex-direction: column; gap: 4px; }
        .preview-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary); font-weight: 800; }
        .preview-title { margin: 0; font-size: 1.3rem; color: var(--blue-dark); font-weight: 800; }

        .preview-price-wrapper { display: flex; flex-direction: column; align-items: flex-end; }
        .preview-price-old { font-size: 0.85rem; color: #94a3b8; text-decoration: line-through; line-height: 1; margin-bottom: 2px;}
        .preview-price { font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin: 0; line-height: 1; }
        .preview-price span { font-size: 0.85rem; color: var(--muted); font-weight: 600; }

        .preview-features { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 18px; font-size: 0.82rem; color: #334155; font-weight: 500;}
        .preview-features li { display: flex; align-items: flex-start; gap: 10px; }
        .preview-features li i.fa-check { color: #10b981; margin-top: 3px; font-size: 1rem;}
        .preview-more { margin: 12px 0 0; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; }

        @media(max-width: 992px) { 
            .watermark-bg { left: 50%; top: 30%; transform: translate(-50%, -50%) rotate(-10deg); font-size: 35vh; }
            .wrapper { flex-direction: column; height: auto; overflow: visible; }
            
            .brand { flex: none; height: auto; padding: 100px 24px 30px; text-align: left; position: relative; }
            
            .logo-register-link { top: 24px; left: 24px; margin: 0; }
            .logo-register-link img { max-width: 180px; }
            
            .brand-text-container { margin-top: 0; }
            .brand h1 { font-size: 2rem; }
            .brand p { margin: 12px 0 0 0; } 

            .brand-features { margin-top: 24px; gap: 12px;}
            .feature-item { font-size: 0.9rem; }
            .feature-icon { width: 30px; height: 30px; font-size: 0.9rem; }
            
            .form-area { padding: 16px; height: auto; overflow: visible; }
            .card { padding: 24px 16px; border-radius: 16px; }
            
            .section { padding: 20px 10px; margin-top: 20px; border-radius: 12px; }
            .legal { padding: 16px 10px; margin-top: 20px; border-radius: 12px; }
            
            .footer { flex-direction: column-reverse; align-items: stretch; gap: 20px; }
            .footer a { justify-content: center; background: #f1f5f9; }
            .actions { flex-direction: column-reverse; width: 100%; }
            .actions button { width: 100%; }
            .membership-options, .preview-features { grid-template-columns: 1fr; }
            .membership-heading { align-items: flex-start; flex-direction: column; gap: 4px; }
        }
    </style>
</head>

<body>

    <i class="fa-solid fa-helmet-safety watermark-bg"></i>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="wrapper">

        <div class="brand">
            <a href="index.php" class="logo-register-link" title="Ir al Inicio">
                <img src="assets/logo_preventwork.png" alt="PreventWork">
            </a>

            <div class="brand-text-container fade-in-up delay-1">
                <h1>Gestión <span>inteligente</span><br>de Seguridad y Salud</h1>
                <p>Centraliza tu SG-SST, cumple la normatividad y toma decisiones basadas en datos reales desde una sola plataforma.</p>
                
                <div class="brand-features">
                    <div class="feature-item fade-in-up delay-2">
                        <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Cumplimiento 100% normativo y legal</span>
                    </div>
                    <div class="feature-item fade-in-up delay-3">
                        <div class="feature-icon"><i class="fa-solid fa-chart-pie"></i></div>
                        <span>Reportes y métricas en tiempo real</span>
                    </div>
                    <div class="feature-item fade-in-up delay-4">
                        <div class="feature-icon"><i class="fa-solid fa-file-signature"></i></div>
                        <span>Firmas digitales y gestión documental segura</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-area">
            
            <div class="card fade-in-up delay-2">
            <?php if ($registro_pendiente): ?>
                <div class="success-card">
                    <div class="success-icon-wrapper">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            <path d="M9 14l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h2 class="success-title">¡Solicitud en Revisión!</h2>
                    <p class="success-message">
                        Hemos recibido tus datos correctamente. El equipo de <b>Prevención</b> se comunicará contigo muy pronto para validar la información de tu empresa y activar tu cuenta y plan en la plataforma.
                    </p>
                    <a href="login.php" class="btn-return">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver a la pantalla de Inicio
                    </a>
                </div>

            <?php else: ?>

                    <div class="header">
                        <div>
                            <h2>Registro de Usuario</h2>
                            <div class="hint">Completa los datos para crear tu cuenta en la plataforma.</div>
                        </div>
                    </div>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert-error">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span><?php echo $error_msg; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="mainForm" onsubmit="return beforeSubmit()">

                        <div class="grid">
                            
                            <div class="field full-width">
                                <label>Rol de Ingreso</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z" />
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                    </svg>
                                    <select name="rol" id="rol" onchange="mostrar()" required>
                                        <option value="">Seleccione el rol de acceso...</option>
                                        <option value="representante" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'representante') ? 'selected' : ''; ?>>Representante Legal (Crear Nueva Empresa)</option>
                                        <option value="sst" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'sst') ? 'selected' : ''; ?>>Responsable SST (Vincular a Empresa)</option>
                                        <option value="trabajador" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'trabajador') ? 'selected' : ''; ?>>Trabajador (Vincular a Empresa)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="field full-width hidden" id="seccion_planes">
                                <div class="membership-heading">
                                    <label>Elige la membresía de tu empresa</label>
                                    <p>Podrás revisar el resumen antes de continuar al pago.</p>
                                </div>
                                <input type="hidden" name="plan_id" id="plan_id" value="<?php echo htmlspecialchars($_GET['plan'] ?? $_POST['plan_id'] ?? ''); ?>">
                                <div class="membership-options" id="membership_options">
                                    <?php foreach($planes_info as $p):
                                        $tiene_descuento = ($p['precio_descuento'] > 0 && $p['precio_descuento'] < $p['precio_normal']);
                                        $precio_final = $tiene_descuento ? $p['precio_descuento'] : $p['precio_normal'];
                                    ?>
                                        <button type="button" class="membership-option" data-plan-id="<?php echo $p['id']; ?>" onclick="seleccionarPlan('<?php echo $p['id']; ?>')">
                                            <span class="membership-option-top">
                                                <span class="membership-option-name"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                                <span class="membership-check"><i class="fa-solid fa-check"></i></span>
                                            </span>
                                            <span class="membership-option-price">$<?php echo number_format($precio_final, 0, ',', '.'); ?> <small>/ año</small></span>
                                            <span class="membership-option-capacity">
                                                <?php echo $p['trabajadores'] == 999 ? 'Usuarios ilimitados' : 'Hasta ' . $p['trabajadores'] . ' usuarios'; ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div id="plan_preview" class="hidden"></div>
                                <p id="membership_error" class="membership-error hidden">Selecciona una membresía para continuar.</p>
                            </div>

                            <div class="field full-width hidden" id="empresa_section">
                                <label style="color: var(--primary);">NIT o Cédula de la Empresa a la que pertenece</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="16" rx="2" />
                                        <path d="M7 8h10M7 12h10M7 16h6" />
                                    </svg>
                                    <input type="text" name="empresa_nit" id="empresa_nit" placeholder="Escribe el NIT de la empresa" class="input-nit" value="<?php echo htmlspecialchars($_POST['empresa_nit'] ?? ''); ?>">
                                </div>
                                <div id="nit_feedback" style="font-size: 0.75rem; margin-top: 6px; font-weight: 600;"></div>
                            </div>

                            <div class="field">
                                <label>Nombre</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" /></svg>
                                    <input name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="field">
                                <label>Apellido</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" /></svg>
                                    <input name="apellido" value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="field">
                                <label>Cédula o NIT</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10M7 12h10M7 16h6" /></svg>
                                    <input name="cedula" value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="field">
                                <label>Email</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z" /><path d="m4 6 8 7 8-7" /></svg>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="field">
                                <label>Teléfono</label>
                                <div class="control">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.86.31 1.7.57 2.5a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.58-1.09a2 2 0 0 1 2.11-.45c.8.26 1.64.45 2.5.57A2 2 0 0 1 22 16.92z" /></svg>
                                    <input name="telefono" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <div class="section-title">
                                <h3>Datos de Residencia</h3>
                                <span class="badge">Todos los roles</span>
                            </div>
                            <div class="grid">
                                <div class="field">
                                    <label>Dirección</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11 12 2l9 9" /><path d="M5 10v11h14V10" /></svg>
                                        <input name="direccion" value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Ciudad / Municipio</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18" /><path d="M7 21V7l5-4 5 4v14" /><path d="M9 9h6M9 12h6M9 15h6" /></svg>
                                        <input name="ciudad" value="<?php echo htmlspecialchars($_POST['ciudad'] ?? ''); ?>" required placeholder="Ej. Tame, Arauca">
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Barrio / Corregimiento <span class="label-opt">(Opcional)</span></label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z" /><circle cx="12" cy="11" r="2" /></svg>
                                        <input name="barrio" value="<?php echo htmlspecialchars($_POST['barrio'] ?? ''); ?>" placeholder="Si aplica">
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Localidad / Vereda <span class="label-opt">(Opcional)</span></label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z" /><circle cx="12" cy="10" r="3" /></svg>
                                        <input name="localidad" value="<?php echo htmlspecialchars($_POST['localidad'] ?? ''); ?>" placeholder="Si aplica">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="sst" class="section hidden">
                            <div class="section-title">
                                <h3>Responsable SST</h3>
                                <span class="badge">Campos SST</span>
                            </div>

                            <div class="grid">
                                <div class="field">
                                    <label>Licencia SST</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 6v6c0 5 3.8 9.7 9 10 5.2-.3 9-5 9-10V6z" /></svg>
                                        <select name="licencia">
                                            <option value="No">No</option>
                                            <option value="Sí">Sí</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Tipo de Licencia</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" /></svg>
                                        <input name="tipoLicencia" placeholder="Ej: Profesional">
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Número de Licencia</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7h10M7 12h10M7 17h6" /><rect x="3" y="4" width="18" height="16" rx="2" /></svg>
                                        <input name="numLicencia">
                                    </div>
                                </div>

                                <div class="field">
                                    <label>Fecha de Expedición</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
                                        <input type="date" name="fechaLicencia">
                                    </div>
                                </div>

                                <div class="field full-width">
                                    <label>Expedida por</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18" /><path d="M5 8h14" /><path d="M5 16h14" /></svg>
                                        <input name="expedida" placeholder="Entidad">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="trabajador" class="section hidden">
                            <div class="section-title">
                                <h3>Encuesta Sociodemográfica</h3>
                                <span class="badge">Solo Trabajador</span>
                            </div>

                            <div class="grid">
                                <div class="field"><label>Edad</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2h8M9 2v4l-2 2v2a5 5 0 0 0 10 0V8l-2-2V2" /></svg>
                                        <select name="edad" id="edadSelect">
                                            <option value="">Seleccione</option>
                                            <option>18 a 29 años</option>
                                            <option>30 a 39 años</option>
                                            <option>40 a 49 años</option>
                                            <option>50 a 59 años</option>
                                            <option>60 o más años</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Estado civil</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-8-4.5-8-11a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6.5-10 11-10 11z" /></svg>
                                        <select name="estado_civil" id="civilSelect">
                                            <option value="">Seleccione</option>
                                            <option>Soltero(a)</option>
                                            <option>Casado(a) / Unión libre</option>
                                            <option>Separado(a)</option>
                                            <option>Viudo(a)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Género</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4" /><path d="M5.5 21a8.5 8.5 0 0 1 13 0" /></svg>
                                        <select name="genero" id="generoSelect">
                                            <option value="">Seleccione</option>
                                            <option>Masculino</option>
                                            <option>Femenino</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Personas a cargo</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                        <select name="personas_cargo" id="personasSelect">
                                            <option value="">Seleccione</option>
                                            <option>Ninguna</option>
                                            <option>1 a 3 personas</option>
                                            <option>4 a 6 personas</option>
                                            <option>Más de 6 personas</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Escolaridad</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5z" /><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5" /></svg>
                                        <select name="escolaridad" id="escolaridadSelect">
                                            <option value="">Seleccione</option>
                                            <option>Primaria</option>
                                            <option>Secundaria</option>
                                            <option>Técnico / Tecnólogo</option>
                                            <option>Universitario</option>
                                            <option>Especialista / Magister</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Vivienda</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11 12 2l9 9" /><path d="M5 10v11h14V10" /></svg>
                                        <select name="vivienda" id="viviendaSelect">
                                            <option value="">Seleccione</option>
                                            <option>Propia</option>
                                            <option>Arrendada</option>
                                            <option>Familiar</option>
                                            <option>Compartida con otra familia</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Tiempo libre</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                        <select name="tiempo_libre" id="tiempoSelect">
                                            <option value="">Seleccione</option>
                                            <option>Otro trabajo</option>
                                            <option>Labor doméstica</option>
                                            <option>Recreación y deporte</option>
                                            <option>Estudio</option>
                                            <option>Ninguno</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Experiencia</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" /></svg>
                                        <select name="experiencia" id="experienciaSelect">
                                            <option value="">Seleccione</option>
                                            <option>Menos de 1 año</option>
                                            <option>1 a 5 años</option>
                                            <option>5 a 10 años</option>
                                            <option>10 a 15 años</option>
                                            <option>Más de 15 años</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Estrato</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20" /><path d="M7 7h10" /><path d="M7 12h10" /><path d="M7 17h10" /></svg>
                                        <select name="estrato" id="estratoSelect">
                                            <option value="">Seleccione</option>
                                            <option>1</option><option>2</option><option>3</option>
                                            <option>4</option><option>5</option><option>6</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Convive con</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                        <select name="convive_con" id="conviveSelect">
                                            <option value="">Seleccione</option>
                                            <option>Pareja</option>
                                            <option>Pareja e hijos</option>
                                            <option>Pareja, hijos, padres</option>
                                            <option>Hijos</option>
                                            <option>Padres</option>
                                            <option>Padres e hijos</option>
                                            <option>Hermanos o padres</option>
                                            <option>Solo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Raza</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z" /><path d="M2 12h20" /></svg>
                                        <select name="raza" id="razaSelect">
                                            <option value="">Seleccione</option>
                                            <option>Mestizo</option>
                                            <option>Mulato</option>
                                            <option>Negro</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Tipo contrato</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" /></svg>
                                        <select name="tipo_contrato" id="contratoSelect">
                                            <option value="">Seleccione</option>
                                            <option>Fijo</option>
                                            <option>Indefinido</option>
                                            <option>Obra labor</option>
                                            <option>Prestación de Servicios</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field full-width"><label>Turno</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2" /><circle cx="12" cy="12" r="10" /></svg>
                                        <select name="turno" id="turnoSelect">
                                            <option value="">Seleccione</option>
                                            <option>Oficina 08:00 am - 05:00 pm</option>
                                            <option>Proyecto 07:00 am - 04:00 pm / Sábado medio día</option>
                                            <option>Sala de Ventas 09:00 am - 05:00 pm</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Antigüedad</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18" /><path d="M7 14l4-4 3 3 5-5" /></svg>
                                        <select name="antiguedad" id="antiguedadSelect">
                                            <option value="">Seleccione</option>
                                            <option>Menor a 1 año</option>
                                            <option>1 a 3 años</option>
                                            <option>3 a 5 años</option>
                                            <option>Más de 5 años</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field full-width"><label>Enfermedad</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-8-4.5-8-11a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6.5-10 11-10 11z" /></svg>
                                        <select name="enfermedad" id="enfermedadSelect">
                                            <option value="">Seleccione</option>
                                            <option>No me han diagnosticado ninguna</option>
                                            <option>Otras</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Fuma</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20" /><path d="M2 16h20" /><path d="M6 12v4M10 12v4M14 12v4M18 12v4" /></svg>
                                        <select name="fuma" id="fumaSelect">
                                            <option value="">Seleccione</option>
                                            <option>No fumo</option>
                                            <option>Otras</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Alcohol</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2h8" /><path d="M9 2v6l-3 5a4 4 0 0 0 3 6h6a4 4 0 0 0 3-6l-3-5V2" /></svg>
                                        <select name="alcohol" id="alcoholSelect">
                                            <option value="">Seleccione</option>
                                            <option>No consumo</option>
                                            <option>Semanal</option>
                                            <option>Quincenal</option>
                                            <option>Mensual</option>
                                            <option>Ocasional</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field"><label>Deporte</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 2a10 10 0 0 0 0 20" /></svg>
                                        <select name="deporte" id="deporteSelect">
                                            <option value="">Seleccione</option>
                                            <option>No practico</option>
                                            <option>Diario</option>
                                            <option>Semanal</option>
                                            <option>Quincenal</option>
                                            <option>Mensual</option>
                                            <option>Ocasional</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field full-width"><label>Tipo personal</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z" /></svg>
                                        <select name="tipo_personal" id="personalSelect">
                                            <option value="">Seleccione</option>
                                            <option>Personal no conductor</option>
                                            <option>Mensajero motorizado</option>
                                            <option>Conductor</option>
                                            <option>Operario</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="legal full-width">
                            <p>
                                Autorizo el tratamiento de datos personales por parte del Responsable del Tratamiento,
                                conforme a la Ley 1581 de 2012.
                            </p>
                            <div class="grid">
                                <div class="field full-width">
                                    <label>Acepto la autorización</label>
                                    <div class="control">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4" /><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" /></svg>
                                        <select name="autorizacion" required>
                                            <option value="">---</option>
                                            <option value="si">Sí</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="signature full-width">
                            <h3>Firma Digital</h3>
                            <div class="canvas-container" id="canvasContainer">
                                <canvas id="firma"></canvas>
                            </div>
                            <input type="hidden" name="firmaDigital" id="firmaDigital">

                            <div class="footer">
                                <a href="login.php">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"></path></svg>
                                    Ya tengo cuenta
                                </a>
                                <div class="actions">
                                    <button type="button" class="btn-danger" onclick="limpiar()">Limpiar</button>
                                    <button type="submit" class="btn-primary" onclick="guardarFirma()" id="btnSubmit">Continuar al Pago <i class="fa-solid fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php if (!$registro_pendiente): ?>
    <script>
        // ========================================================
        // 1. SISTEMA DE AUTOGUARDADO (LOCAL STORAGE)
        // ========================================================
        function saveFormData() {
            const formData = {};
            // Seleccionamos inputs de texto, email, number y selects. Omitimos archivos y ocultos.
            const inputs = document.querySelectorAll('#mainForm input:not([type="hidden"]):not([type="file"]), #mainForm select');
            
            inputs.forEach(input => {
                if (input.name) {
                    formData[input.name] = input.value;
                }
            });
            localStorage.setItem('sgst_registro_form', JSON.stringify(formData));
        }

        function loadFormData() {
            const saved = localStorage.getItem('sgst_registro_form');
            if (saved) {
                const formData = JSON.parse(saved);
                const inputs = document.querySelectorAll('#mainForm input:not([type="hidden"]):not([type="file"]), #mainForm select');
                
                // Saber si en la URL viene el plan, porque la URL manda sobre el localStorage
                const urlParams = new URLSearchParams(window.location.search);
                const isPlanInUrl = urlParams.has('plan');

                inputs.forEach(input => {
                    if (input.name) {
                        // Si la URL trae un plan, no sobreescribimos 'rol' ni 'plan_id' con lo viejo de localStorage
                        if (isPlanInUrl && (input.name === 'rol' || input.name === 'plan_id')) {
                            return; 
                        }
                        
                        if (formData[input.name] !== undefined) {
                            input.value = formData[input.name];
                        }
                    }
                });
            }
        }

        // Detectar cualquier cambio en el formulario para autoguardar
        document.getElementById('mainForm').addEventListener('input', saveFormData);
        document.getElementById('mainForm').addEventListener('change', saveFormData);


        // ========================================================
        // 2. MAGIA DE LA URL Y VISTA PREVIA DEL PLAN
        // ========================================================
        const planesData = <?php echo json_encode($planes_info); ?>;

        function seleccionarPlan(planId) {
            document.getElementById('plan_id').value = planId;
            document.getElementById('membership_error').classList.add('hidden');
            actualizarVistaPlan();
            saveFormData();
        }
        
        function actualizarVistaPlan() {
            const planSelect = document.getElementById('plan_id');
            const previewBox = document.getElementById('plan_preview');
            const planId = planSelect.value;

            document.querySelectorAll('.membership-option').forEach(option => {
                const selected = option.dataset.planId == planId;
                option.classList.toggle('selected', selected);
                option.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            
            // ACTUALIZAR LA URL DINÁMICAMENTE SIN RECARGAR LA PÁGINA
            const url = new URL(window.location);
            if (planId) {
                url.searchParams.set('plan', planId);
            } else {
                url.searchParams.delete('plan');
            }
            window.history.replaceState({}, '', url);
            // ----------------------------------------------------

            if (!planId) {
                previewBox.classList.add('hidden');
                previewBox.innerHTML = '';
                return;
            }

            const plan = planesData.find(p => p.id == planId);
            if (!plan) return;

            let hasDiscount = plan.precio_descuento > 0 && plan.precio_descuento < plan.precio_normal;
            let finalPrice = hasDiscount ? plan.precio_descuento : plan.precio_normal;
            
            let formatPrice = new Intl.NumberFormat('es-CO').format(finalPrice);
            let formatOldPrice = new Intl.NumberFormat('es-CO').format(plan.precio_normal);

            let featuresHtml = '';
            let includedFeatures = plan.features.filter(f => f.incluido);
            
            let textoTrabajadores = plan.trabajadores == 999 ? 'Trabajadores Ilimitados' : 'Hasta ' + plan.trabajadores + ' Trabajadores';
            featuresHtml += `<li><i class="fa-solid fa-check"></i> <span>${textoTrabajadores}</span></li>`;
            
            includedFeatures.slice(0, 3).forEach(f => {
                featuresHtml += `<li><i class="fa-solid fa-check"></i> <span>${f.texto}</span></li>`;
            });

            previewBox.innerHTML = `
                <div class="plan-preview-box">
                    <div class="preview-header">
                        <div class="preview-title-wrapper">
                            <span class="preview-label">Plan Seleccionado</span>
                            <h4 class="preview-title">${plan.nombre}</h4>
                        </div>
                        <div class="preview-price-wrapper">
                            ${hasDiscount ? `<span class="preview-price-old">$${formatOldPrice}</span>` : ''}
                            <h2 class="preview-price">$${formatPrice}<span>/anual</span></h2>
                        </div>
                    </div>
                    <ul class="preview-features">
                        ${featuresHtml}
                    </ul>
                    ${includedFeatures.length > 3 ? `<p class="preview-more">Incluye ${includedFeatures.length - 3} beneficios adicionales de la membresía.</p>` : ''}
                </div>
            `;
            
            previewBox.classList.remove('hidden');
        }

        // ========================================================
        // 3. INICIALIZACIÓN (ON LOAD)
        // ========================================================
        window.onload = function() {
            // 1. Restaurar datos del autoguardado (Local Storage)
            loadFormData();

            // 2. Revisar si la URL trajo un plan pre-seleccionado
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('plan')) {
                document.getElementById('rol').value = 'representante';
                document.getElementById('plan_id').value = urlParams.get('plan');
            }
            
            // 3. Mostrar y adaptar el formulario a lo que se eligió
            mostrar(); 
        };

        let debounceTimer;
        const nitInput = document.getElementById('empresa_nit');
        const feedback = document.getElementById('nit_feedback');
        const btnSubmit = document.getElementById('btnSubmit');

        if(nitInput) {
            nitInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const nit = this.value.trim();
                
                if(nit.length === 0) {
                    feedback.innerHTML = '';
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    return;
                }

                feedback.innerHTML = '<span style="color: var(--text-muted);">Buscando empresa...</span>';
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';

                debounceTimer = setTimeout(() => {
                    fetch('ajax_check_empresa.php?nit=' + encodeURIComponent(nit))
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            feedback.innerHTML = '<span style="color: #10b981; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> Empresa válida: ' + data.nombre + '</span>';
                            btnSubmit.disabled = false;
                            btnSubmit.style.opacity = '1';
                        } else {
                            feedback.innerHTML = '<span style="color: #ef4444; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg> No se encontró empresa aprobada con este NIT.</span>';
                        }
                    })
                    .catch(err => {
                        feedback.innerHTML = '';
                        btnSubmit.disabled = false;
                        btnSubmit.style.opacity = '1';
                    });
                }, 500); 
            });
        }

        function mostrar() {
            const rol = document.getElementById("rol").value;

            document.getElementById("sst").classList.add("hidden");
            document.getElementById("trabajador").classList.add("hidden");
            document.getElementById("empresa_section").classList.add("hidden");
            document.getElementById("seccion_planes").classList.add("hidden");
            
            const selectsTrabajador = document.querySelectorAll('#trabajador select');
            const inputEmpresa = document.getElementById('empresa_nit');
            const inputPlan = document.getElementById('plan_id');
            
            if (rol === "sst" || rol === "trabajador") {
                document.getElementById("empresa_section").classList.remove("hidden");
                inputEmpresa.setAttribute('required', 'required');
                inputPlan.removeAttribute('required');
                btnSubmit.innerHTML = 'Registrar Cuenta <i class="fa-solid fa-arrow-right"></i>';
            } else if (rol === "representante") {
                document.getElementById("seccion_planes").classList.remove("hidden");
                inputPlan.setAttribute('required', 'required');
                inputEmpresa.removeAttribute('required');
                
                // Actualizar la vista previa y la URL
                actualizarVistaPlan();
                
                btnSubmit.innerHTML = 'Continuar al Pago <i class="fa-solid fa-arrow-right"></i>';
                
                if(feedback) feedback.innerHTML = ''; 
                if(btnSubmit) { btnSubmit.disabled = false; btnSubmit.style.opacity = '1'; }
            } else {
                inputEmpresa.removeAttribute('required');
                inputPlan.removeAttribute('required');
                btnSubmit.innerHTML = 'Registrar';
                if(feedback) feedback.innerHTML = ''; 
                if(btnSubmit) { btnSubmit.disabled = false; btnSubmit.style.opacity = '1'; }
            }

            if (rol === "sst") {
                document.getElementById("sst").classList.remove("hidden");
                selectsTrabajador.forEach(s => s.removeAttribute('required'));
            } else if (rol === "trabajador") {
                document.getElementById("trabajador").classList.remove("hidden");
                selectsTrabajador.forEach(s => s.setAttribute('required', 'required'));
            } else {
                selectsTrabajador.forEach(s => s.removeAttribute('required'));
            }
            
            setTimeout(resizeCanvas, 100);
        }

        // ===== Firma Responsiva =====
        const canvas = document.getElementById("firma");
        const canvasContainer = document.getElementById("canvasContainer");
        const ctx = canvas.getContext("2d");
        let draw = false;

        function resizeCanvas() {
            if(!canvas || !canvasContainer) return;
            const rect = canvasContainer.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 150;
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        window.addEventListener("resize", resizeCanvas);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            }
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        }

        function start(e) { e.preventDefault(); draw = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
        function move(e) { if (!draw) return; e.preventDefault(); const p = getPos(e); ctx.lineWidth = 2; ctx.lineCap = "round"; ctx.strokeStyle = "#111"; ctx.lineTo(p.x, p.y); ctx.stroke(); }
        function end(e) { if (e) e.preventDefault(); draw = false; }

        if(canvas) {
            canvas.addEventListener("mousedown", start); canvas.addEventListener("mousemove", move); canvas.addEventListener("mouseup", end); canvas.addEventListener("mouseleave", end);
            canvas.addEventListener("touchstart", start, { passive: false }); canvas.addEventListener("touchmove", move, { passive: false }); canvas.addEventListener("touchend", end, { passive: false }); canvas.addEventListener("touchcancel", end, { passive: false });
        }

        function limpiar() { if(!ctx) return; ctx.fillStyle = "#ffffff"; ctx.fillRect(0, 0, canvas.width, canvas.height); }

        function guardarFirma() {
            if(canvas) { document.getElementById("firmaDigital").value = canvas.toDataURL("image/png"); }
        }

        function beforeSubmit() { 
            const rolSeleccionado = document.getElementById('rol').value;
            const planSeleccionado = document.getElementById('plan_id').value;

            if (rolSeleccionado === 'representante' && !planSeleccionado) {
                const membershipError = document.getElementById('membership_error');
                membershipError.classList.remove('hidden');
                document.getElementById('seccion_planes').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            guardarFirma(); 
            // Limpiamos el localStorage al enviar para que el próximo registro esté limpio
            localStorage.removeItem('sgst_registro_form');
            return true; 
        }
    </script>
    <?php endif; ?>

    <?php include_once __DIR__ . '/components/cookie_banner.php'; ?>
</body>
</html>
