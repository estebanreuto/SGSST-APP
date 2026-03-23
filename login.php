<?php
session_start();
require_once 'config/db.php';
require_once __DIR__ . '/config/auth.php';

// Cargar autoload si existe (PHPMailer por Composer)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Cargar .env si NO tienes Dotenv (fallback simple)
function loadEnvSimple(string $path): void
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#'))
            continue;
        if (!str_contains($line, '='))
            continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // quitar comillas si vienen
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
loadEnvSimple(__DIR__ . '/.env');

// ==========================================
// FUNCIÓN PARA OCULTAR EL CORREO (Data Masking)
// ==========================================
function maskEmail(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;

    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);

    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '***';
    } else {
        $maskedName = substr($name, 0, 2) . '***' . substr($name, -1);
    }

    return $maskedName . '@' . $domain;
}

// ---- Envío de correo 2FA con PHPMailer ----
function send2FAEmail(string $toEmail, string $toName, string $code): array
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no está instalado. Ejecuta: composer require phpmailer/phpmailer'];
    }

    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $secure = getenv('SMTP_SECURE') ?: 'tls';
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'SG-SST';

    if ($user === '' || $pass === '') {
        return [false, 'Faltan SMTP_USER o SMTP_PASS en el .env'];
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;

        if ($secure === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($user, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Tu código de verificación (2FA)';

        $mail->Body = '
            <div style="font-family: Arial, sans-serif; line-height:1.5; color:#111;">
              <h2 style="margin:0 0 8px; color:#ff8a1f;">Código de verificación</h2>
              <p style="margin:0 0 12px;">Hola <b>' . htmlspecialchars($toName) . '</b>, usa este código para ingresar a la plataforma:</p>
              <div style="font-size:28px; font-weight:700; letter-spacing:6px; padding:12px 16px; background:#f1f5f9; display:inline-block; border-radius:10px; border: 1px solid #dbe3ec;">
                ' . htmlspecialchars($code) . '
              </div>
              <p style="margin:12px 0 0; color:#475569;">Este código expira en 5 minutos.</p>
            </div>
        ';

        $mail->AltBody = "Tu código 2FA es: $code (expira en 5 minutos).";

        $mail->send();
        return [true, 'OK'];
    } catch (\Throwable $e) {
        return [false, $e->getMessage()];
    }
}

// ==========================================
// LÓGICA DE CANCELAR / CAMBIAR CUENTA (NUEVO)
// ==========================================
if (isset($_GET['cancel'])) {
    unset($_SESSION['codigo_2fa'], $_SESSION['usuario_temp'], $_SESSION['codigo_expira'], $_SESSION['correo_censurado']);
    header('Location: login.php');
    exit;
}

// Si ya hay sesión activa, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// ==========================================
// LÓGICA DE REENVÍO DE CÓDIGO
// ==========================================
if (isset($_GET['resend']) && !empty($_SESSION['usuario_temp'])) {
    $usuario = $_SESSION['usuario_temp'];
    $_SESSION['codigo_2fa'] = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['codigo_expira'] = time() + 300;
    $_SESSION['correo_censurado'] = maskEmail($usuario['email']);

    $fullName = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
    [$ok, $msg] = send2FAEmail($usuario['email'], $fullName !== '' ? $fullName : 'Usuario', $_SESSION['codigo_2fa']);

    if ($ok) {
        $success = 'Te hemos enviado un nuevo código a <b>' . $_SESSION['correo_censurado'] . '</b>';
    } else {
        $error = 'No se pudo reenviar el código. Detalle: ' . $msg;
    }
}

// ==========================================
// LÓGICA PRINCIPAL POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $codigo_2fa = trim($_POST['codigo_2fa'] ?? '');

    // === NUEVO: REDIRECCIÓN SECRETA A SUPER ADMIN ===
    // Cambia 'acceso_master' por la palabra clave que quieras usar
    $palabra_secreta = 'acceso_master';

    // Si escribió la palabra secreta y no está en el paso del código 2FA, lo mandamos al admin
    if (strtolower($identifier) === $palabra_secreta && empty($_SESSION['codigo_2fa'])) {
        header('Location: admin/login.php');
        exit;
    }
    // ================================================

    $mostrar_2fa = !empty($_SESSION['codigo_2fa']);
    if ($mostrar_2fa && $identifier === '' && isset($_SESSION['usuario_temp']['email'])) {
        $identifier = $_SESSION['usuario_temp']['email'];
    }

    if (empty($identifier)) {
        $error = 'Por favor ingresa tu cédula o correo electrónico';
    } else {
        try {
            if (!empty($_SESSION['codigo_2fa']) && !empty($_SESSION['usuario_temp'])) {
                $usuario = $_SESSION['usuario_temp'];
            } else {
                $stmt = $conn->prepare("
                    SELECT id, nombre, apellido, cedula, email, rol, telefono
                    FROM usuarios
                    WHERE cedula = :identifier OR email = :identifier
                    LIMIT 1
                ");
                $stmt->execute(['identifier' => $identifier]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($usuario) {
                // Paso 1: Generar código inicial
                if (empty($_SESSION['codigo_2fa'])) {
                    $_SESSION['codigo_2fa'] = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $_SESSION['usuario_temp'] = $usuario;
                    $_SESSION['codigo_expira'] = time() + 300; // 5 minutos
                    $_SESSION['correo_censurado'] = maskEmail($usuario['email']);

                    $fullName = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
                    [$ok, $msg] = send2FAEmail($usuario['email'], $fullName !== '' ? $fullName : 'Usuario', $_SESSION['codigo_2fa']);

                    if ($ok) {
                        $success = 'Te enviamos un código de verificación a <b>' . $_SESSION['correo_censurado'] . '</b>';
                    } else {
                        unset($_SESSION['codigo_2fa'], $_SESSION['usuario_temp'], $_SESSION['codigo_expira'], $_SESSION['correo_censurado']);
                        $error = 'No se pudo enviar el código por correo. Detalle técnico: ' . $msg;
                    }
                }
                // Paso 2: Validar Código
                else {
                    if ($codigo_2fa !== '' && $codigo_2fa === $_SESSION['codigo_2fa'] && time() < ($_SESSION['codigo_expira'] ?? 0)) {
                        create_db_session($conn, $usuario, 8);
                        log_activity($conn, (int) $usuario['id'], 'LOGIN_OK', 'Ingreso exitoso con 2FA');

                        unset($_SESSION['codigo_2fa'], $_SESSION['usuario_temp'], $_SESSION['codigo_expira'], $_SESSION['correo_censurado']);

                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Código incorrecto o ha expirado. Si expiró, solicita uno nuevo.';
                    }
                }
            } else {
                $error = 'Usuario no encontrado. Verifica tus datos o regístrate';
            }
        } catch (Throwable $e) {
            $error = 'Error al procesar la solicitud. Intenta nuevamente. Detalle: ' . $e->getMessage();
        }
    }
}
$mostrar_2fa = !empty($_SESSION['codigo_2fa']);
$correo_seguro = $_SESSION['correo_censurado'] ?? 'tu correo';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión | SG-SST Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* PALETA DE COLORES PREMIUM (Glassmorphism) */
            --primary: #ff8a1f;
            --primary2: #ff7a00;
            --bg-top: #e8f0f8;
            --bg-mid: #f3f7fb;
            --bg-bottom: #ffffff;
            --blue-main: #2b5a9e;
            --blue-dark: #1e3a8a;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 25px 50px -12px rgba(43, 90, 158, 0.15);
            --text-main: #1f2d3d;
            --text-muted: #5f6f82;
            --border: #dbe3ec;
            --radius: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%);
            color: var(--text-main);
            overflow-x: hidden;
            position: relative;
        }

        /* FIGURAS FLOTANTES DE FONDO */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.35;
            animation: float 12s infinite ease-in-out alternate;
        }

        .blob-1 {
            top: -5%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: var(--blue-main);
        }

        .blob-2 {
            bottom: -10%;
            right: -5%;
            width: 600px;
            height: 600px;
            background: var(--primary);
            animation-delay: -6s;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) scale(1);
            }

            100% {
                transform: translateY(40px) scale(1.05);
            }
        }

        /* ANIMACIONES DE ENTRADA */
        .fade-in-up {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== LAYOUT ===== */
        .wrapper {
            display: grid;
            grid-template-columns: 42% 58%;
            height: 100vh;
            overflow: hidden;
        }

        /* ===== BRAND (IZQUIERDA) ===== */
        .brand {
            padding: 64px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
        }

        .brand h1 {
            margin: 0;
            font-size: clamp(2rem, 3.5vw, 3rem);
            line-height: 1.15;
            font-weight: 800;
            color: var(--blue-dark);
            letter-spacing: -0.02em;
        }

        .brand h1 span {
            background: linear-gradient(135deg, var(--primary), #ff5e00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand p {
            margin: 16px 0 0;
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 440px;
            line-height: 1.6;
        }

        /* ===== FORM AREA (DERECHA) ===== */
        .form-area {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            overflow-y: auto;
            height: 100vh;
            z-index: 2;
        }

        .form-area::-webkit-scrollbar {
            width: 0;
        }

        .card {
            width: 100%;
            max-width: 480px;
            margin: auto;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
            padding: 40px;
        }

        .header {
            margin-bottom: 24px;
        }

        .header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--blue-dark);
            font-weight: 800;
        }

        .header .hint {
            margin: 6px 0 0;
            font-size: .85rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ===== ALERTAS ===== */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* ===== INPUTS ===== */
        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0 0 8px;
            color: var(--text-main);
        }

        .control {
            position: relative;
        }

        .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
            transition: all .2s ease;
        }

        input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: var(--text-main);
            transition: all .2s ease;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 138, 31, .15);
        }

        input:focus~.icon {
            color: var(--primary);
        }

        /* Estilo especial para el input del código 2FA */
        .input-2fa {
            text-align: center;
            font-size: 1.3rem;
            letter-spacing: 6px;
            font-weight: 700;
            color: var(--primary);
            padding-left: 14px !important;
        }

        .input-2fa~.icon {
            display: none;
        }

        /* Temporizador */
        .timer-badge {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .timer-badge span {
            color: var(--primary);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        /* ===== ENLACE SUTIL DE REENVÍO ===== */
        .resend-link-subtle {
            font-size: 0.8rem;
            color: var(--blue-main);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s ease;
        }

        .resend-link-subtle:hover {
            color: var(--blue-dark);
            text-decoration: underline;
        }

        /* ===== BUTTONS ===== */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary2));
            color: #ffffff;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(255, 138, 31, 0.25);
            margin-top: 24px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(255, 138, 31, 0.35);
        }

        .btn-submit:disabled {
            background: #cbd5e1;
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        .spin-icon {
            animation: spin 1s linear infinite;
            width: 18px;
            height: 18px;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        /* ===== FOOTER LINKS ===== */
        .footer-links {
            margin-top: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a {
            text-decoration: none;
            color: var(--blue-main);
            font-weight: 600;
            transition: color 0.2s;
            margin-left: 4px;
        }

        .footer-links a:hover {
            color: var(--blue-dark);
            text-decoration: underline;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: var(--text-muted) !important;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 6px;
            transition: all 0.2s ease;
        }

        .btn-outline:hover {
            background: #f1f5f9;
            color: var(--text-main) !important;
            text-decoration: none !important;
        }

        /* RESPONSIVE */
        @media(max-width:1100px) {
            .wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }

            .brand {
                padding: 40px 40px 20px;
                text-align: center;
            }

            .brand p {
                margin: 16px auto 0;
            }

            .form-area {
                padding: 20px 40px 60px;
                height: auto;
                overflow: visible;
            }
        }

        @media(max-width:500px) {
            .form-area {
                padding: 20px;
            }

            .card {
                padding: 30px 24px;
            }
        }
    </style>
</head>

<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="wrapper">

        <div class="brand fade-in-up delay-1">
            <h1>Bienvenido a<br><span>SG-SST Pro</span></h1>
            <p>
                Accede a tu panel de control para gestionar el Sistema de Seguridad y Salud en el Trabajo de tu empresa de manera rápida y segura.
            </p>
        </div>

        <div class="form-area">
            <div class="card fade-in-up delay-2">

                <div class="header">
                    <h2><?php echo $mostrar_2fa ? 'Verificación 2FA' : 'Iniciar Sesión'; ?></h2>
                    <div class="hint">
                        <?php if (!$mostrar_2fa): ?>
                            Ingresa tu documento o correo electrónico registrado.
                        <?php else: ?>
                            Ingresa el código de 6 dígitos que enviamos a<br><b><?php echo $correo_seguro; ?></b>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg fill="none" stroke="currentColor" width="20" height="20" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="loginForm">

                    <?php if (!$mostrar_2fa): ?>
                        <div class="field">
                            <label for="identifier">Cédula o Correo Electrónico</label>
                            <div class="control">
                                <input type="text" id="identifier" name="identifier" required placeholder="Ej: 1002345678 o correo" autofocus>
                                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit" id="btnSubmit">Continuar</button>

                    <?php else: ?>
                        <div class="field" style="margin-bottom: 8px;">
                            <label for="codigo_2fa">
                                Código de Verificación
                                <div class="timer-badge" id="timerContainer">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Expira en: <span id="timerDisplay">05:00</span>
                                </div>
                            </label>
                            <div class="control">
                                <input type="text" class="input-2fa" id="codigo_2fa" name="codigo_2fa" required placeholder="Ej: 123456" maxlength="6" autocomplete="off" autofocus>
                                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>

                        <div id="resendContainer" style="display: none; text-align: right; margin-bottom: 20px;">
                            <a href="login.php?resend=1" class="resend-link-subtle" id="resendLink">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reenviar código
                            </a>
                        </div>

                        <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($_SESSION['usuario_temp']['cedula'] ?? ''); ?>">

                        <button type="submit" class="btn-submit" id="btnSubmit">Entrar al Sistema</button>

                    <?php endif; ?>

                </form>

                <div class="footer-links">
                    <?php if (!$mostrar_2fa): ?>
                        <div style="margin-bottom: 12px;">
                            ¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>
                        </div>
                    <?php else: ?>
                        <a href="login.php?cancel=1" class="btn-outline" style="width: 100%; border-color: #cbd5e1; color: #475569 !important; margin-bottom: 8px;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Ingresar con otra cédula o correo
                        </a>
                    <?php endif; ?>

                    <a href="index.php" class="btn-outline" style="width: 100%;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Volver al inicio
                    </a>
                </div>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Animación al presionar "Continuar" o "Entrar"
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    const btn = document.getElementById('btnSubmit');
                    const is2FAStep = document.getElementById('codigo_2fa') !== null;

                    if (!is2FAStep) {
                        btn.innerHTML = '<svg class="spin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Enviando código...';
                    } else {
                        btn.innerHTML = '<svg class="spin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Verificando...';
                    }
                    btn.style.opacity = '0.8';
                    btn.style.pointerEvents = 'none';
                });
            }

            // Lógica del Temporizador para el código 2FA
            <?php if ($mostrar_2fa): ?>
                const expireTime = <?php echo isset($_SESSION['codigo_expira']) ? $_SESSION['codigo_expira'] : 0; ?>;
                const serverTime = <?php echo time(); ?>;
                let timeLeft = expireTime - serverTime;

                const timerDisplay = document.getElementById('timerDisplay');
                const timerContainer = document.getElementById('timerContainer');
                const resendContainer = document.getElementById('resendContainer');
                const codeInput = document.getElementById('codigo_2fa');
                const btnSubmit = document.getElementById('btnSubmit');

                function endTimer() {
                    // Texto sutil y no exagerado
                    timerContainer.innerHTML = '<span style="color: #ef4444; font-size: 0.75rem;">Expirado</span>';
                    resendContainer.style.display = 'block';
                    if (codeInput) {
                        codeInput.disabled = true;
                        codeInput.value = '';
                        codeInput.placeholder = 'Expirado';
                    }
                    if (btnSubmit) {
                        btnSubmit.disabled = true;
                    }
                }

                if (timeLeft > 0) {
                    const interval = setInterval(() => {
                        if (timeLeft <= 0) {
                            clearInterval(interval);
                            endTimer();
                        } else {
                            let m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                            let s = (timeLeft % 60).toString().padStart(2, '0');
                            timerDisplay.innerText = m + ':' + s;
                            timeLeft--;
                        }
                    }, 1000);
                } else {
                    // Si ya expiró al recargar la página
                    endTimer();
                }

                // Mostrar el botón de reenviar a los 30 segundos por si el correo no llegó
                setTimeout(() => {
                    if (resendContainer.style.display === 'none') {
                        resendContainer.style.display = 'block';
                    }
                }, 30000);
            <?php endif; ?>

        });
    </script>
</body>

</html>