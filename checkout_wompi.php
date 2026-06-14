<?php
session_start();
require_once "config/db.php";

// ========================================================
// CARGAR AUTOLOAD Y ENV PARA PHPMAILER 
// ========================================================
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function loadEnvSimple(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
loadEnvSimple(__DIR__ . '/.env');

function sendWelcomeEmail(string $toEmail, string $toName, string $cedula, string $planNombre): array {
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no está instalado.'];
    }

    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $secure = getenv('SMTP_SECURE') ?: 'tls';
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'SG-SST Pro';

    if ($user === '' || $pass === '') return [false, 'Faltan credenciales SMTP'];

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        $mail->SMTPSecure = ($secure === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($user, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido a SG-SST Pro! - Tu cuenta ha sido activada';
        
        $loginUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/login.php";

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="color-scheme" content="light dark">
            <meta name="supported-color-schemes" content="light dark">
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f1f5f9; color: #0f172a; }
                .email-wrapper { background-color: #f1f5f9; padding: 40px 20px; }
                .email-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
                .content-box { padding: 40px 32px; text-align: center; }
                .text-body { color: #475569; font-size: 16px; line-height: 1.6; text-align: left; margin: 0 0 24px 0; }
                .info-panel { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: left; margin-bottom: 32px; }
                .text-title { color: #1e3a8a; font-size: 22px; font-weight: 800; margin: 0 0 20px 0; }
                .footer-box { background-color: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0; }

                @media (prefers-color-scheme: dark) {
                    body, .email-wrapper { background-color: #0f172a !important; color: #f8fafc !important; }
                    .email-card { background-color: #1e293b !important; box-shadow: 0 10px 25px rgba(0,0,0,0.4) !important; }
                    .content-box { background-color: #1e293b !important; }
                    .text-title { color: #ffffff !important; }
                    .text-body { color: #cbd5e1 !important; }
                    .info-panel { background-color: #0f172a !important; border-color: #334155 !important; }
                    .info-panel p, .info-panel strong { color: #f8fafc !important; }
                    .footer-box { background-color: #0f172a !important; border-top-color: #334155 !important; }
                    .footer-box p { color: #94a3b8 !important; }
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-card">
                    <div style="background: linear-gradient(135deg, #ff8a1f, #ff7a00); padding: 35px 20px; text-align: center;">
                        <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">PREVENT<span style="opacity: 0.8; font-weight: 400;">WORK</span></h1>
                        <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 15px;">Sistema de Gestión de Seguridad y Salud</p>
                    </div>

                    <div class="content-box">
                        <h2 class="text-title">¡Tu cuenta está lista!</h2>
                        <p class="text-body">Hola <b>' . htmlspecialchars($toName) . '</b>,</p>
                        <p class="text-body">Hemos recibido tu pago con éxito y tu suscripción al plan <strong style="color: #ff8a1f;">' . htmlspecialchars($planNombre) . '</strong> ya está activa.</p>
                        
                        <div class="info-panel">
                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #ff8a1f; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">¿Cómo ingresar al sistema?</p>
                            <p style="margin: 0 0 10px 0; font-size: 16px;"><strong>Tu Usuario:</strong> ' . htmlspecialchars($cedula) . ' <span style="font-size:14px; color: #64748b;">(o tu correo electrónico)</span></p>
                            <p style="margin: 0 0 14px 0; font-size: 16px;"><strong>Tu Contraseña:</strong> ¡No necesitas! 🔒</p>
                            <p style="margin: 0; font-size: 14px; line-height: 1.5;">Nuestra plataforma utiliza autenticación segura sin contraseña (2FA). Solo debes ingresar tu usuario y te enviaremos un <strong>código de 6 dígitos a este correo</strong> para acceder al instante.</p>
                        </div>
                        
                        <a href="' . $loginUrl . '" style="background: linear-gradient(135deg, #2b5a9e, #1e3a8a); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 16px; display: inline-block;">Iniciar Sesión Ahora</a>
                    </div>

                    <div class="footer-box">
                        <p style="margin: 0 0 8px 0; font-size: 13px;">&copy; ' . date('Y') . ' PREVENTWORK. Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->AltBody = "Hola $toName, tu cuenta ha sido activada en el plan $planNombre. Usuario: $cedula. No necesitas contraseña, al ingresar te enviaremos un código de seguridad a este correo. Ingresa en: $loginUrl";
        $mail->send();
        return [true, 'OK'];
    } catch (\Throwable $e) {
        return [false, $e->getMessage()];
    }
}

// 1. Validar que la URL traiga el ID de la solicitud
if (!isset($_GET['id'])) {
    die("Acceso inválido a la pasarela de pagos.");
}

$solicitud_id = intval($_GET['id']);

// 2. Traer la solicitud y los detalles de su plan
$stmt = $conn->prepare("
    SELECT se.*, p.nombre as plan_nombre, p.precio_normal, p.precio_descuento 
    FROM solicitudes_empresas se
    LEFT JOIN planes p ON se.plan_id = p.id
    WHERE se.id = ?
");
$stmt->execute([$solicitud_id]);
$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitud) {
    die("La solicitud no existe.");
}

// 3. Traer las llaves de Wompi 
$stmt_keys = $conn->query("SELECT wompi_public, wompi_private, wompi_integrity FROM cpanel_admins LIMIT 1");
$keys = $stmt_keys->fetch(PDO::FETCH_ASSOC);

$wompi_pub = trim($keys['wompi_public'] ?? '');
$wompi_prv = trim($keys['wompi_private'] ?? '');
$wompi_integrity = trim($keys['wompi_integrity'] ?? '');

// 4. Cálculos Financieros (Unificando Fee de Wompi + IVA del Plan total en una sola línea)
$precio = ($solicitud['precio_descuento'] > 0 && $solicitud['precio_descuento'] < $solicitud['precio_normal']) 
          ? $solicitud['precio_descuento'] : $solicitud['precio_normal'];

$cargos_wompi_total = 0;
$total_pagar = $precio;

if ($precio > 0) {
    // Calculamos el Fee base (2.65% + 700)
    $fee_wompi = ($precio * 0.0265) + 700;
    
    // Calculamos el IVA sobre el total (Plan + Fee) tal como en tu imagen
    $iva = ($precio + $fee_wompi) * 0.19; 
    
    // Sumamos todo el costo operativo en una sola variable para mostrarlo unificado
    $cargos_wompi_total = ceil($fee_wompi + $iva); 
    
    // Total final
    $total_pagar = ceil($precio + $cargos_wompi_total); 
}

// 5. ESTADO DE LA TRANSACCIÓN
$transaccion_id = isset($_GET['tx_id']) ? htmlspecialchars($_GET['tx_id']) : '';
$estado_pago = 'PENDING';
$correo_enviado = false; 

if ($transaccion_id) {
    if (!empty($wompi_prv)) {
        $url_wompi = "https://sandbox.wompi.co/v1/transactions/" . $transaccion_id; // Cambiar a production para entorno real
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_wompi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $wompi_prv]);
        
        $respuesta = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $respuesta) {
            $datos_wompi = json_decode($respuesta, true);
            
            if (isset($datos_wompi['data']['status'])) {
                $estado_pago = $datos_wompi['data']['status'];
                
                if ($estado_pago === 'APPROVED' && $solicitud['estado'] === 'pendiente') {
                    
                    try {
                        $conn->beginTransaction();

                        // A. Pasamos la solicitud a aprobada 
                        $stmt_upd = $conn->prepare("UPDATE solicitudes_empresas SET estado = 'aprobada' WHERE id = ?");
                        $stmt_upd->execute([$solicitud_id]);

                        // B. Creamos el usuario Representante 
                        $hash_pass = password_hash($solicitud['cedula'], PASSWORD_DEFAULT);
                        $stmt_ins = $conn->prepare("INSERT INTO usuarios (empresa_id, nombre, apellido, cedula, email, telefono, rol, direccion, ciudad, barrio, localidad, firma, activo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 1)");
                        $stmt_ins->execute([
                            $solicitud_id, $solicitud['nombre'], $solicitud['apellido'], $solicitud['cedula'], 
                            $solicitud['email'], $solicitud['telefono'], 'representante', 
                            $solicitud['direccion'], $solicitud['ciudad'], $solicitud['barrio'], 
                            $solicitud['localidad'], $solicitud['firma']
                        ]);

                        // C. INSERTAR VENTA EN LA TABLA DE CONTABILIDAD
                        $stmt_pago_contable = $conn->prepare("INSERT INTO pagos_suscripciones (empresa_id, transaccion_wompi, monto, plan_nombre, estado) VALUES (?, ?, ?, ?, ?)");
                        $stmt_pago_contable->execute([
                            $solicitud_id, 
                            $transaccion_id, 
                            $total_pagar, 
                            $solicitud['plan_nombre'], 
                            'APPROVED'
                        ]);

                        $conn->commit();

                        // D. Enviamos el correo con PHPMailer 
                        $fullName = trim($solicitud['nombre'] . ' ' . $solicitud['apellido']);
                        [$ok, $msg] = sendWelcomeEmail($solicitud['email'], $fullName, $solicitud['cedula'], $solicitud['plan_nombre']);
                        
                        if ($ok) {
                            $correo_enviado = true;
                        }
                        
                    } catch (Exception $e) {
                        $conn->rollBack();
                    }
                }
            } else {
                $estado_pago = 'ERROR';
            }
        } else {
            $estado_pago = 'ERROR';
        }
    } else {
        $estado_pago = 'ERROR_NO_KEY';
    }

} else {
    // 💳 PREPARAMOS LA FIRMA PARA ABRIR WOMPI
    $monto_centavos = (string)($total_pagar * 100); 
    $currency = "COP";
    $reference = "SUB_" . $solicitud_id . "_" . time(); 
    
    $signature = "";
    if (!empty($wompi_integrity)) {
        $cadena_firma = $reference . $monto_centavos . $currency . $wompi_integrity;
        $signature = hash("sha256", $cadena_firma);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Completar Pago | PREVENTWORK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #ff8a1f; 
            --primary-hover: #ff7a00;
            --blue-main: #2b5a9e;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background-color: #ffffff; color: #111; display: flex; min-height: 100vh; overflow-x: hidden;}
        a { text-decoration: none; }

        /* ================= MITAD IZQUIERDA (OSCURA) ================= */
        .split-left { width: 40%; background: #0f172a; color: #fff; padding: 60px; display: flex; flex-direction: column; justify-content: space-between; position: sticky; top: 0; height: 100vh; border-right: 1px solid #1e293b; z-index: 10; overflow: hidden; }
        .split-left::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; pointer-events: none; z-index: 0; }
        .glow-bg { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); width: 600px; height: 600px; background: radial-gradient(circle, rgba(43, 90, 158, 0.25) 0%, transparent 60%); z-index: 0; pointer-events: none; animation: pulseLight 4s infinite alternate ease-in-out; }
        @keyframes pulseLight { 0% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.95); } 100% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); } }

        /* LOGO */
        .brand-logo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 60px; position: relative; z-index: 2; width: 100%; }
        .brand-logo-text { font-size: 1.6rem; font-weight: 900; color: #fff; letter-spacing: -0.05em;}
        .brand-logo-text span { color: var(--primary); }
        
        .back-link { font-size: 13px; font-weight: 500; color: #94a3b8; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; background:transparent; border:none;}
        .back-link:hover { color: #fff; }

        .invoice-container { width: 100%; max-width: 440px; position: relative; z-index: 2;}
        .summary-label { font-size: 13px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 24px;}
        .invoice-amount { font-size: 56px; font-weight: 900; letter-spacing: -2px; margin-bottom: 8px; color: #fff;}
        .invoice-amount span { font-size: 18px; font-weight: 500; color: #94a3b8; letter-spacing: 0;}
        .invoice-user { font-size: 14px; color: #cbd5e1; margin-bottom: 40px; font-weight: 400; line-height: 1.6;}

        .invoice-details { border-top: 1px solid #1e293b; padding-top: 32px; display: flex; flex-direction: column; gap: 16px;}
        .detail-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #cbd5e1;}
        .detail-row span:last-child { color: #fff; font-weight: 600;}
        .detail-row.sub { font-size: 13px; color: #94a3b8; }
        .detail-row.sub span:last-child { color: #94a3b8; font-weight: 500; }
        .detail-row.total { font-size: 20px; color: var(--primary); font-weight: 800; border-top: 1px dashed #1e293b; padding-top: 20px; margin-top: 10px;}

        .footer-left { font-size: 12px; color: #94a3b8; margin-top: 40px; display: flex; align-items: center; gap:8px; position: relative; z-index: 2;}

        /* ================= MITAD DERECHA (BLANCA) ================= */
        .split-right { width: 60%; background: #ffffff; padding: 60px 8%; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .content-wrapper { width: 100%; max-width: 480px; animation: fadeUp 0.6s ease; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .payment-header { margin-bottom: 32px; text-align: center;}
        .payment-header h1 { font-size: 30px; font-weight: 800; letter-spacing: -1px; color: #0f172a; margin-bottom: 12px;}
        .payment-header p { font-size: 15px; color: #64748b; line-height: 1.6;}

        /* Botón Wompi */
        .wompi-custom-wrapper { position: relative; width: 100%; margin-bottom: 24px; }
        .btn-lumo-wompi { width: 100%; height: 50px; background: linear-gradient(135deg, var(--blue-main), #1e3a8a); border-radius: 12px; box-shadow: 0 4px 15px rgba(43, 90, 158, 0.3); display: flex; justify-content: center; align-items: center; color: #fff; font-size: 15px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; }
        .btn-lumo-wompi:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(43, 90, 158, 0.4); }

        /* Cajas Informativas */
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; display: flex; gap: 12px; margin-bottom: 10px; }
        .info-box:last-child { margin-bottom: 32px; }
        .info-box .icon { width: 36px; height: 36px; background: #e0f2fe; border-radius: 10px; display: flex; justify-content: center; align-items: center; color: var(--blue-main); flex-shrink: 0; }
        .info-box .icon svg { width: 18px; height: 18px; stroke-width: 2.5; }
        .info-box .text h4 { font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 2px 0;}
        .info-box .text p { font-size: 12.5px; color: #64748b; line-height: 1.4; margin: 0; }

        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 16px 20px; border-radius: 16px; font-size: 14px; font-weight: 600; margin-bottom: 24px; display: flex; gap: 12px; align-items: center;}
        
        .alert-warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 16px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 24px; display: none; gap: 12px; align-items: center; animation: fadeUp 0.3s ease;}

        /* --- PANTALLAS DE ESTADO --- */
        .status-wrapper { width: 100%; max-width: 440px; margin: 0 auto; text-align: center; display: flex; flex-direction: column; align-items: center; }
        
        .status-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; border: 3px solid; flex-shrink: 0; animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: scale(0.5); }
        .status-icon svg { width: 32px; height: 32px; stroke-width: 2.5; }
        @keyframes scaleIn { to { opacity: 1; transform: scale(1); } }
        
        .status-header { width: 100%; margin-bottom: 24px; }
        .status-header h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; color: #0f172a; margin-bottom: 8px; }
        .status-header p { font-size: 14px; color: #475569; line-height: 1.6; margin: 0; }

        .icon-success { background: #dcfce7; color: #16a34a; border-color: #bbf7d0; }
        .icon-pending { background: #fef3c7; color: #d97706; border-color: #fde68a; }
        .icon-error { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

        .spinner { animation: spin 2s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* --- CAJA DE PASOS --- */
        .steps-box { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 28px; text-align: left; display: flex; flex-direction: column; gap: 16px;}
        .steps-title { font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; text-align: center;}
        
        .step-item { display: flex; gap: 12px; align-items: flex-start; }
        .step-num { width: 28px; height: 28px; background: var(--blue-main); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 13px; font-weight: 800; flex-shrink: 0; margin-top: 2px;}
        .step-text { flex: 1; }
        .step-text strong { display: block; font-size: 14px; color: #0f172a; margin-bottom: 2px; }
        .step-text p { font-size: 13px; color: #64748b; line-height: 1.4; margin: 0; }
        
        .step-mail-error { color: #d97706 !important; font-weight: 600; background: #fffbeb; padding: 8px 12px; border-radius: 8px; display: inline-block; margin-top: 6px; border: 1px solid #fde68a;}

        .tx-box { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 32px; text-align: left; display: flex; justify-content: space-between; align-items: center; }
        .tx-box .tx-left .tx-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;}
        .tx-box .tx-left .tx-id { font-family: monospace; font-size: 14px; color: #0f172a; font-weight: 700; word-break: break-all;}

        .error-reasons { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 32px;}
        .error-reasons h4 { font-size: 13px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0;}
        .error-reasons ul { font-size: 14px; color: #475569; line-height: 1.6; padding-left: 20px; margin: 0; }
        .error-reasons li { margin-bottom: 6px; }

        .action-buttons { width: 100%; display: flex; flex-direction: column; gap: 12px; }
        .btn-login { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 16px; background: var(--blue-main); color: #fff; border: none; border-radius: 12px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none;}
        .btn-login:hover { background: #1e3a8a; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(43, 90, 158, 0.2);}
        
        .btn-support { background: #fff; color: #0f172a; border: 1px solid #cbd5e1; }
        .btn-support:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; box-shadow: none; transform: translateY(0);}

        /* Modal de Cancelación */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; opacity: 0; visibility: hidden; transition: 0.3s; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-box { background: #fff; padding: 32px; border-radius: 24px; width: 90%; max-width: 400px; text-align: center; transform: translateY(20px); transition: 0.3s; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;}
        .modal-overlay.active .modal-box { transform: translateY(0); }
        .modal-icon { width: 56px; height: 56px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; }
        .modal-actions { display: flex; gap: 12px; margin-top: 32px;}
        .btn-modal-cancel { flex: 1; padding: 14px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 14px; border-radius: 12px; border: none; cursor: pointer; transition: 0.2s; }
        .btn-modal-cancel:hover { background: #e2e8f0; color: #0f172a;}
        .btn-modal-confirm { flex: 1; padding: 14px; background: #dc2626; color: #fff; font-weight: 600; font-size: 14px; border-radius: 12px; text-decoration: none; display: flex; justify-content: center; align-items: center;}
        .btn-modal-confirm:hover { background: #b91c1c; }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .split-left { width: 100%; position: relative; height: auto; padding: 40px 24px; border-right: none; }
            .split-right { width: 100%; padding: 40px 24px; }
        }
    </style>
</head>
<body>

    <?php if (!$transaccion_id): ?>
    <div class="modal-overlay" id="cancelModal">
        <div class="modal-box">
            <div class="modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="28" height="28"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">¿Deseas cancelar el pago?</h3>
            <p style="font-size: 14px; color: #64748b; line-height: 1.5;">Si sales ahora, tu cuenta quedará como "Pendiente". El equipo revisará tu solicitud manualmente.</p>
            <div class="modal-actions">
                <button class="btn-modal-cancel" onclick="document.getElementById('cancelModal').classList.remove('active');">Volver al pago</button>
                <a href="login.php" class="btn-modal-confirm">Sí, salir</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="split-left">
        <div class="glow-bg"></div>

        <div>
            <div class="brand-logo">
                <div class="brand-logo-text">PREVENT<span>WORK</span></div>
                <?php if (!$transaccion_id): ?>
                <button class="back-link" onclick="document.getElementById('cancelModal').classList.add('active');">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Cancelar pago
                </button>
                <?php endif; ?>
            </div>
            
            <div class="invoice-container">
                <div class="summary-label"><?php echo ($estado_pago === 'APPROVED') ? "Recibo de Pago" : "Resumen de Compra"; ?></div>
                
                <div class="invoice-amount">
                    $<?php echo number_format($total_pagar, 0, ',', '.'); ?><span> COP</span>
                </div>
                
                <div class="invoice-user">
                    Suscripción a nombre de:<br>
                    <strong style="color:#fff; font-size: 16px;"><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido']); ?></strong><br>
                    <?php echo htmlspecialchars($solicitud['email']); ?>
                </div>

                <div class="invoice-details">
                    <div class="detail-row">
                        <span>Plan <?php echo htmlspecialchars($solicitud['plan_nombre']); ?></span>
                        <span>$<?php echo number_format($precio, 0, ',', '.'); ?></span>
                    </div>
                    <?php if($precio > 0): ?>
                    <div class="detail-row sub">
                        <span>Tarifa de procesamiento Wompi</span>
                        <span>$<?php echo number_format($cargos_wompi_total, 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row total">
                        <span><?php echo ($estado_pago === 'APPROVED') ? "Total Pagado" : "Total a Pagar"; ?></span>
                        <span>$<?php echo number_format($total_pagar, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-left">
            <?php if ($estado_pago === 'APPROVED'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="16" height="16"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span style="color:#10B981; font-weight: 500;">Tu cuenta ha sido creada exitosamente.</span>
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Cifrado de extremo a extremo.
            <?php endif; ?>
        </div>
    </div>

    <div class="split-right">
        <div class="content-wrapper">
            
            <?php if ($transaccion_id): ?>
                
                <?php if ($estado_pago === 'APPROVED'): ?>
                <div class="status-wrapper">
                    <div class="status-icon icon-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="status-header">
                        <h1>¡Pago Exitoso!</h1>
                    </div>

                    <div class="steps-box">
                        <div class="steps-title">Próximos Pasos</div>
                        
                        <div class="step-item">
                            <div class="step-num">1</div>
                            <div class="step-text">
                                <strong>Cuenta Activada</strong>
                                <p>Tu suscripción al plan <?php echo htmlspecialchars($solicitud['plan_nombre']); ?> ya está lista y configurada.</p>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-num">2</div>
                            <div class="step-text">
                                <strong>Instrucciones de Acceso</strong>
                                <?php if ($correo_enviado): ?>
                                    <p>Te enviamos un mensaje a <b><?php echo htmlspecialchars($solicitud['email']); ?></b>. Recuerda que no usamos contraseñas, tu acceso será siempre validado con un código de seguridad a tu correo.</p>
                                <?php else: ?>
                                    <div class="step-mail-error">
                                        <p style="margin: 0; color: #b45309;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="14" height="14" style="margin-bottom:-2px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> 
                                        <b>No se pudo enviar el correo de bienvenida</b>, pero no te preocupes, tu cuenta sí está activa.</p>
                                        <p style="margin: 8px 0 0 0; color: #b45309;">Para ingresar, usa tu documento: <b><?php echo htmlspecialchars($solicitud['cedula']); ?></b> y el sistema te pedirá el código de seguridad.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-num">3</div>
                            <div class="step-text">
                                <strong>Inicia Sesión</strong>
                                <p>Ingresa al panel para empezar a gestionar tu empresa en el sistema.</p>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons" style="border:none;">
                        <a href="login.php" class="btn-login">Ir al Login ahora <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
                    </div>
                </div>

                <?php elseif ($estado_pago === 'PENDING'): ?>
                <div class="status-wrapper">
                    <div class="status-icon icon-pending">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                    </div>
                    <div class="status-header">
                        <h1>Validando tu pago...</h1>
                        <p>Estamos confirmando la transacción con tu banco. Esto suele tomar unos segundos. <br><strong style="color:#0f172a;">Por favor, no cierres esta ventana.</strong></p>
                    </div>
                    <div class="tx-box">
                        <div class="tx-left">
                            <div class="tx-title">ID de Transacción a validar</div>
                            <div class="tx-id"><?php echo $transaccion_id; ?></div>
                        </div>
                    </div>
                    <script>
                        setTimeout(function() { window.location.reload(); }, 5000);
                    </script>
                </div>

                <?php else: ?>
                <div class="status-wrapper">
                    <div class="status-icon icon-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    </div>
                    <div class="status-header">
                        <h1>El pago no se pudo procesar</h1>
                        <p>Wompi ha declinado la transacción o hubo un error de comunicación. Tu membresía no ha sido activada.</p>
                    </div>
                    
                    <div class="error-reasons">
                        <h4>Posibles razones:</h4>
                        <ul>
                            <li>Fondos insuficientes en la cuenta bancaria.</li>
                            <li>Bloqueo preventivo de seguridad por parte de tu banco.</li>
                            <li>Datos de la tarjeta o código de seguridad (CVC) incorrectos.</li>
                        </ul>
                    </div>

                    <?php if($estado_pago === 'ERROR_NO_KEY'): ?>
                        <div style="font-size:12px; color:#b91c1c; margin-bottom:20px; padding:10px; background:#fef2f2; border-radius:8px; width: 100%; text-align: left; box-sizing: border-box;">[Admin Info]: Falta configurar la Llave Privada de Wompi en el panel de Configuración para validar pagos automáticamente.</div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <a href="checkout_wompi.php?id=<?php echo $solicitud_id; ?>" class="btn-login">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path></svg>
                            Reintentar el pago
                        </a>
                        <a href="https://wa.me/573000000000" target="_blank" class="btn-login btn-support">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Necesito ayuda (Soporte)
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
            <div class="payment-header">
                <h1>Elige cómo pagar</h1>
                <p>Transacción segura. Aceptamos todas las tarjetas, transferencias bancarias y billeteras digitales.</p>
            </div>

            <?php if ($total_pagar > 0): ?>
                <?php if (empty($wompi_pub) || empty($wompi_integrity)): ?>
                    <div class="alert-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Faltan las llaves de Wompi en el panel de administrador.
                    </div>
                <?php else: ?>
                    
                    <div class="alert-warning" id="warningMsg">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Cancelaste el proceso o la ventana se cerró inesperadamente. Tu pago no se procesó.
                    </div>

                    <div class="wompi-custom-wrapper">
                        <div class="btn-lumo-wompi" id="btn-visual">
                            <i class="fa-solid fa-lock" style="margin-right: 8px;"></i>
                            <span>Pagar Seguro con Wompi</span>
                        </div>
                    </div>

                    <script src="https://checkout.wompi.co/widget.js"></script>
                    
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var btnVisual = document.getElementById("btn-visual");
                            var warningMsg = document.getElementById("warningMsg");
                            
                            var checkout = new WidgetCheckout({
                                currency: 'COP',
                                amountInCents: <?php echo $monto_centavos; ?>,
                                reference: '<?php echo $reference; ?>',
                                publicKey: '<?php echo htmlspecialchars($wompi_pub); ?>'
                                <?php if($signature): ?>
                                , signature: { integrity: '<?php echo $signature; ?>' }
                                <?php endif; ?>
                            });

                            btnVisual.addEventListener("click", function(e) {
                                e.preventDefault();
                                
                                // Esconder alerta si estaba visible
                                warningMsg.style.display = "none";
                                
                                // Bloqueamos el botón visualmente
                                btnVisual.innerHTML = "<i class='fa-solid fa-spinner fa-spin' style='margin-right: 8px;'></i><span>Abriendo pasarela...</span>";
                                btnVisual.style.opacity = "0.7";
                                btnVisual.style.pointerEvents = "none";

                                checkout.open(function (result) {
                                    var transaction = result.transaction;
                                    
                                    if (transaction && transaction.id) {
                                        window.location.href = "checkout_wompi.php?id=<?php echo $solicitud_id; ?>&tx_id=" + transaction.id;
                                    } else {
                                        // MAGIA: El usuario cerró la pestaña de pago SIN pagar
                                        resetBotonWompi(true);
                                    }
                                });

                                // DOBLE BLINDAJE: Vigilar si el iframe de Wompi desaparece de la pantalla
                                const checkIframe = setInterval(() => {
                                    const wompiIframe = document.querySelector('iframe[src*="wompi"]');
                                    if (!wompiIframe && btnVisual.style.pointerEvents === 'none') {
                                        resetBotonWompi(true);
                                        clearInterval(checkIframe);
                                    }
                                }, 1500);

                                function resetBotonWompi(mostrarAviso = false) {
                                    btnVisual.innerHTML = "<i class='fa-solid fa-lock' style='margin-right: 8px;'></i><span>Pagar Seguro con Wompi</span>";
                                    btnVisual.style.opacity = "1";
                                    btnVisual.style.pointerEvents = "auto";
                                    
                                    // Si la función es llamada porque se abortó el pago, mostramos la alerta
                                    if (mostrarAviso) {
                                        warningMsg.style.display = "flex";
                                    }
                                }
                            });
                        });
                    </script>

                <?php endif; ?>
            <?php else: ?>
                <div style="margin-bottom: 32px; width: 100%;">
                    <a href="?id=<?php echo $solicitud_id; ?>&tx_id=FREE_PLAN_ACTIVATED" class="btn-login">Activar cuenta gratis ahora</a>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="text">
                    <h4>Pago 100% Seguro y Encriptado</h4>
                    <p>La transacción es procesada por los servidores seguros de Wompi Bancolombia.</p>
                </div>
            </div>

            <div class="info-box">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="text">
                    <h4>Activación automática y Seguridad</h4>
                    <p>Validaremos tu pago con el banco al instante y recibirás las instrucciones de acceso 2FA en tu correo.</p>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/components/cookie_banner.php'; ?>
</body>
</html>
