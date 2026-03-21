<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['cpanel_admin_id'])) {
    header("Location: index.php");
    exit;
}

// Auto-crear tabla
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS super_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->query("SELECT COUNT(*) FROM super_admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('sgsst2026', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO super_admins (username, password_hash, nombre) VALUES ('admin', '$hash', 'Super Administrador')");
    }
} catch (PDOException $e) {
    die("Error configurando BD del Admin: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM super_admins WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($pass, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['cpanel_admin_id'] = $admin['id'];
        $_SESSION['cpanel_admin_nombre'] = $admin['nombre'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Login | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* TEMA CORPORATIVO BASADO EN LA IMAGEN */
            --primary: #ff8a1f;        /* Naranja del proyecto */
            --primary-hover: #ea580c;
            --sidebar-bg: #1e293b;     /* Azul grisáceo muy oscuro para el panel izquierdo */
            
            --text-main: #0f172a;
            --text-muted: #64748b;
            
            --border-light: #e2e8f0;
            --input-bg: #f8fafc;
            --input-focus: #ffffff;
            
            --radius-md: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* CONTENEDOR PRINCIPAL - PANTALLA DIVIDIDA */
        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
            max-width: 100vw;
        }

        /* LADO IZQUIERDO - BRANDING */
        .brand-side {
            flex: 1; /* Ocupa la mitad del espacio */
            background: linear-gradient(135deg, var(--sidebar-bg) 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        /* Elementos decorativos en el lado oscuro */
        .brand-side::before {
            content: '';
            position: absolute;
            top: -10%; left: -10%;
            width: 50%; height: 50%;
            background: radial-gradient(circle, rgba(255, 138, 31, 0.1) 0%, transparent 60%);
            border-radius: 50%;
        }

        /* ====== NUEVO: CÍRCULO DE FONDO PARA EL LOGO ====== */
        .logo-circle {
            width: 220px;
            height: 220px;
            background: #ffffff; /* Fondo blanco sólido para que el logo resalte */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            z-index: 10;
            padding: 20px;
            /* Sombra profunda y un borde exterior translúcido (efecto anillo) */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 10px rgba(255, 255, 255, 0.05);
        }

        .brand-logo-large {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        /* ================================================= */
        
        .brand-side h1 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            z-index: 10;
        }

        .brand-side p {
            color: #94a3b8;
            margin-top: 8px;
            font-size: 1rem;
            z-index: 10;
        }

        /* LADO DERECHO - FORMULARIO */
        .form-side {
            flex: 1; /* Ocupa la otra mitad */
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        
        .login-card p.subtitle {
            color: var(--text-muted);
            margin-bottom: 32px;
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* ALERTA DE ERROR ESTILO LIMPIO */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
        }

        /* INPUTS LIMPIOS Y CLAROS (Estilo Corporativo) */
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-main);
        }
        
        .control { position: relative; }
        .control svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #94a3b8;
            transition: all 0.3s ease;
        }
        
        .control input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            background: var(--input-bg);
            color: var(--text-main);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .control input::placeholder { color: #cbd5e1; }

        .control input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.1);
        }
        
        .control input:focus ~ svg { color: var(--primary); }
        
        /* BOTÓN PRINCIPAL */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .spin-icon {
            animation: spin 1s linear infinite;
            width: 20px; height: 20px;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* LINK DE RETORNO */
        .footer-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 32px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .footer-link:hover { color: var(--primary); }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .brand-side { display: none; }
            .form-side { padding: 20px; }
            .login-card { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="split-container">
        
        <div class="brand-side">
            
            <div class="logo-circle">
                <img src="../assets/vertixlogo.png" alt="Logo Empresa" class="brand-logo-large">
            </div>

            <h1>Panel de Control</h1>
            <p>Acceso administrativo maestro</p>
        </div>

        <div class="form-side">
            <div class="login-card">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Ingresa tus credenciales para continuar.</p>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="adminLoginForm">
                    <div class="field">
                        <label>Usuario</label>
                        <div class="control">
                            <input type="text" name="username" required autofocus autocomplete="off" placeholder="admin">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label>Contraseña</label>
                        <div class="control">
                            <input type="password" name="password" required placeholder="••••••••••••">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="btnSubmit">
                        Acceder
                    </button>
                </form>

                <a href="../index.php" class="footer-link">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver al sitio principal
                </a>
            </div>
        </div>

    </div>

    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmit');
            btn.innerHTML = '<svg class="spin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Verificando...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>
</html>