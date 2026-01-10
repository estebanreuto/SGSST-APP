<?php
session_start();
require_once 'config/db.php';

// Si ya hay sesión activa, redirigir según rol
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $codigo_2fa = trim($_POST['codigo_2fa'] ?? '');
    
    if (empty($identifier)) {
        $error = 'Por favor ingresa tu cédula o correo electrónico';
    } else {
        try {
            // Buscar usuario por cédula o email
            $stmt = $conn->prepare("
                SELECT id, nombre, apellido, cedula, email, rol, telefono 
                FROM usuarios 
                WHERE cedula = :identifier OR email = :identifier
                LIMIT 1
            ");
            $stmt->execute(['identifier' => $identifier]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Generar código 2FA (en producción enviar por SMS/Email)
                if (empty($_SESSION['codigo_2fa'])) {
                    $_SESSION['codigo_2fa'] = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $_SESSION['usuario_temp'] = $usuario;
                    $_SESSION['codigo_expira'] = time() + 300; // 5 minutos
                    $success = 'Código enviado. En producción lo recibirías por SMS/Email. DEMO: ' . $_SESSION['codigo_2fa'];
                } else {
                    // Verificar código 2FA
                    if ($codigo_2fa === $_SESSION['codigo_2fa'] && time() < $_SESSION['codigo_expira']) {
                        // Login exitoso
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                        $_SESSION['usuario_rol'] = $usuario['rol'];
                        $_SESSION['usuario_email'] = $usuario['email'];
                        
                        // Limpiar datos temporales
                        unset($_SESSION['codigo_2fa']);
                        unset($_SESSION['usuario_temp']);
                        unset($_SESSION['codigo_expira']);
                        
                        // Redirigir según rol
                        switch ($usuario['rol']) {
                            case 'Administrador':
                                header('Location: dashboard-admin.php');
                                break;
                            case 'Responsable SG-SST':
                                header('Location: dashboard-responsable.php');
                                break;
                            case 'Trabajador':
                                header('Location: dashboard-trabajador.php');
                                break;
                            default:
                                header('Location: dashboard.php');
                        }
                        exit;
                    } else {
                        $error = 'Código incorrecto o expirado';
                        unset($_SESSION['codigo_2fa']);
                        unset($_SESSION['usuario_temp']);
                        unset($_SESSION['codigo_expira']);
                    }
                }
            } else {
                $error = 'Usuario no encontrado. Verifica tus datos o regístrate';
            }
        } catch (PDOException $e) {
            $error = 'Error al procesar la solicitud. Intenta nuevamente';
        }
    }
}

$mostrar_2fa = !empty($_SESSION['codigo_2fa']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | SG-SST</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(251, 146, 60, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
            animation: drift 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, 20px); }
        }

        .container {
            position: relative;
            z-index: 1;
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 600px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        /* Left side branding */
        .branding {
            flex: 1;
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .branding::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(251, 146, 60, 0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .branding-content {
            position: relative;
            z-index: 1;
        }

        .branding h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .branding h1 .highlight {
            color: #fb923c;
        }

        .branding p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #cbd5e1;
        }

        /* Form side */
        .form-area {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .form-header p {
            font-size: 0.95rem;
            color: #64748b;
        }

        /* Alert messages */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            border-left: 4px solid #22c55e;
            color: #166534;
        }

        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            width: 20px;
            height: 20px;
            color: #94a3b8;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px 14px 12px 44px;
            font-size: 0.95rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #1e293b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            background: white;
            border-color: #fb923c;
            box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.1);
        }

        input[type="text"]:focus ~ .input-icon,
        input[type="email"]:focus ~ .input-icon {
            color: #fb923c;
        }

        input[type="text"]:not(:placeholder-shown),
        input[type="email"]:not(:placeholder-shown) {
            background: white;
            border-color: #cbd5e1;
        }

        .separator {
            text-align: center;
            position: relative;
            margin: 24px 0;
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 30px);
            height: 1px;
            background: #e2e8f0;
        }

        .separator::before {
            left: 0;
        }

        .separator::after {
            right: 0;
        }

        /* Button styles - compact and elegant */
        .btn {
            width: 100%;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(251, 146, 60, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(251, 146, 60, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(100, 116, 139, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(100, 116, 139, 0.3);
        }

        .btn-outline {
            background: white;
            border: 2px solid #e2e8f0;
            color: #64748b;
        }

        .btn-outline:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-1px);
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        /* Footer links */
        .form-footer {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .link-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
            justify-content: center;
            padding: 8px;
        }

        .link-button:hover {
            color: #fb923c;
            gap: 12px;
        }

        .link-button svg {
            width: 16px;
            height: 16px;
            transition: transform 0.3s ease;
        }

        .link-button:hover svg {
            transform: translateX(-3px);
        }

        /* Responsive design */
        @media (max-width: 968px) {
            .container {
                flex-direction: column;
                min-height: auto;
            }

            .branding {
                padding: 40px 30px;
            }

            .branding h1 {
                font-size: 1.8rem;
            }

            .form-area {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .branding {
                padding: 30px 20px;
            }

            .form-area {
                padding: 30px 20px;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left branding side -->
        <div class="branding">
            <div class="branding-content">
                <h1>Gestión <span class="highlight">inteligente</span><br>de Seguridad y Salud</h1>
                <p>Centraliza tu SG-SST, cumple la normatividad y toma decisiones basadas en datos reales desde una sola plataforma.</p>
            </div>
        </div>

        <!-- Right form side -->
        <div class="form-area">
            <div class="form-header">
                <h2><?php echo $mostrar_2fa ? 'Verificación 2FA' : 'Iniciar Sesión'; ?></h2>
                <p><?php echo $mostrar_2fa ? 'Ingresa el código de verificación' : 'Ingresa con tu cédula o correo electrónico'; ?></p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php if (!$mostrar_2fa): ?>
                    <!-- Login identifier input -->
                    <div class="form-group">
                        <label for="identifier">Cédula o Correo Electrónico</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="identifier" 
                                name="identifier" 
                                placeholder="Ingresa tu cédula o correo"
                                required
                                autofocus
                            >
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Continuar
                    </button>
                <?php else: ?>
                    <!-- 2FA verification input -->
                    <div class="form-group">
                        <label for="codigo_2fa">Código de Verificación</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="codigo_2fa" 
                                name="codigo_2fa" 
                                placeholder="Ingresa el código de 6 dígitos"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                required
                                autofocus
                            >
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>

                    <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($_SESSION['usuario_temp']['cedula'] ?? ''); ?>">

                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Verificar e Ingresar
                    </button>
                <?php endif; ?>
            </form>

            <div class="form-footer">
                <a href="register.php" class="btn btn-outline">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Crear cuenta nueva
                </a>

                <a href="index.php" class="link-button">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>
