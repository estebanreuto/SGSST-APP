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
        email VARCHAR(100) DEFAULT NULL,
        foto_perfil VARCHAR(255) DEFAULT NULL,
        wompi_public VARCHAR(255) DEFAULT NULL,
        wompi_private VARCHAR(255) DEFAULT NULL,
        wompi_integrity VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->query("SELECT COUNT(*) FROM super_admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('sgsst2026', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO super_admins (username, password_hash, nombre, email) VALUES ('admin', '$hash', 'Super Administrador', 'admin@preventwork.com')");
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
    <?php include_once __DIR__ . '/../components/brand_favicon.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff8a1f;        
            --primary-hover: #ea580c;
            --blue-main: #2b5a9e;
            --sidebar-bg: #0f172a;     
            
            --text-main: #0f172a;
            --text-muted: #64748b;
            
            --border-light: #e2e8f0;
            --input-bg: #f8fafc;
            --input-focus: #ffffff;
            
            --radius-md: 12px;
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
            -webkit-font-smoothing: antialiased;
        }

        /* CONTENEDOR PRINCIPAL - PANTALLA DIVIDIDA */
        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
            max-width: 100vw;
        }

        /* ================= MITAD IZQUIERDA (OSCURA) ================= */
        .brand-side {
            flex: 1; 
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
            border-right: 1px solid #1e293b;
        }

        /* Efectos de fondo */
        .brand-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
            z-index: 0;
        }
        
        .glow-bg {
            position: absolute;
            top: 40%; left: 50%;
            transform: translate(-50%, -50%);
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255, 138, 31, 0.15) 0%, transparent 60%);
            z-index: 0;
            pointer-events: none;
            animation: pulseLight 4s infinite alternate ease-in-out;
        }
        
        @keyframes pulseLight {
            0% { opacity: 0.6; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
        }

        /* ÍCONOS FLOTANTES MARCA DE AGUA */
        .watermark-icon {
            position: absolute;
            color: #ffffff;
            opacity: 0.04;
            pointer-events: none;
            z-index: 1;
        }

        .wm-1 { top: 10%; left: 15%; font-size: 100px; animation: floatAnim1 20s infinite alternate ease-in-out; }
        .wm-2 { bottom: 15%; right: 10%; font-size: 140px; animation: floatAnim2 25s infinite alternate ease-in-out; color: #ff8a1f; opacity: 0.03;}
        .wm-3 { top: 30%; right: 15%; font-size: 80px; animation: floatAnim3 18s infinite alternate ease-in-out; }
        .wm-4 { bottom: 20%; left: 10%; font-size: 110px; animation: floatAnim4 22s infinite alternate ease-in-out; }
        .wm-5 { top: 50%; left: 5%; font-size: 60px; animation: floatAnim1 15s infinite alternate ease-in-out; color: #ff8a1f; opacity: 0.03;}

        @keyframes floatAnim1 { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(-40px) rotate(15deg); } }
        @keyframes floatAnim2 { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(-60px) rotate(-15deg); } }
        @keyframes floatAnim3 { 0% { transform: translateY(0) rotate(0deg) scale(1); } 100% { transform: translateY(30px) rotate(20deg) scale(1.1); } }
        @keyframes floatAnim4 { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(50px) rotate(-20deg); } }

        .brand-content {
            position: relative;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .logo-wrapper {
            margin-bottom: 20px;
        }

        .brand-logo-large {
            max-width: 220px;
            height: auto;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
        }
        
        .brand-side h1 {
            color: #ffffff;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .brand-side h1 span {
            color: #ff8a1f;
        }

        .brand-side p {
            color: #94a3b8;
            font-size: 1.05rem;
            max-width: 350px;
            line-height: 1.5;
        }

        /* ================= MITAD DERECHA (BLANCA) ================= */
        .form-side {
            flex: 1; 
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        /* Contenedor sin bordes (Borderless) */
        .login-card {
            width: 100%;
            max-width: 380px;
            background: transparent;
            padding: 0;
            border: none;
            box-shadow: none;
            animation: fadeUp 0.5s ease-out forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card h2 {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.03em;
        }
        
        .login-card p.subtitle {
            color: var(--text-muted);
            margin-bottom: 32px;
            font-size: 1rem;
            font-weight: 400;
        }

        /* ALERTA DE ERROR */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        /* INPUTS */
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .control { position: relative; }
        .control svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #94a3b8;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .control input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            background: var(--input-bg);
            color: var(--text-main);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .control input::placeholder { color: #cbd5e1; font-weight: 400; }

        .control input:focus {
            outline: none;
            border-color: #ff8a1f;
            background: var(--input-focus);
            box-shadow: 0 0 0 4px rgba(255, 138, 31, 0.15);
        }
        
        .control input:focus ~ svg { color: #ff8a1f; }
        
        /* BOTÓN PRINCIPAL REPARADO (Cores directos para evitar fallos de CSS) */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background-color: #ff8a1f; /* Respaldo */
            background-image: linear-gradient(135deg, #ff8a1f 0%, #ff7a00 100%);
            color: #ffffff !important;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(255, 138, 31, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 138, 31, 0.4);
            background-image: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        }

        .spin-icon {
            animation: spin 1s linear infinite;
            width: 18px; height: 18px;
            color: #ffffff;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* LINK DE RETORNO */
        .footer-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 32px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s ease;
            justify-content: center;
            width: 100%;
        }
        
        .footer-link:hover { color: #ff8a1f; }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .split-container { flex-direction: column; }
            .brand-side { display: none; } 
            .form-side { padding: 40px 24px; align-items: flex-start; padding-top: 15vh;}
        }
    </style>
</head>
<body>

    <div class="split-container">
        
        <div class="brand-side">
            <div class="glow-bg"></div>
            
            <i class="fa-solid fa-shield-halved watermark-icon wm-1"></i>
            <i class="fa-solid fa-chart-line watermark-icon wm-2"></i>
            <i class="fa-solid fa-users watermark-icon wm-3"></i>
            <i class="fa-solid fa-file-signature watermark-icon wm-4"></i>
            <i class="fa-solid fa-helmet-safety watermark-icon wm-5"></i>

            <div class="brand-content">
                <div class="logo-wrapper">
                    <img src="../assets/vertixlogo.png" alt="Logo Empresa" class="brand-logo-large" onerror="this.src='../assets/logo_preventwork.jpeg'">
                </div>

                <h1>Panel <span>Maestro</span></h1>
                <p>Centro de control y gestión administrativa SG-SST.</p>
            </div>
        </div>

        <div class="form-side">
            <div class="login-card">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Ingresa tus credenciales maestras.</p>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="adminLoginForm">
                    <div class="field">
                        <label>Usuario</label>
                        <div class="control">
                            <input type="text" name="username" required autofocus autocomplete="off" placeholder="ej. admin">
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
                        Acceder al Panel
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>

                <div style="text-align: center;">
                    <a href="../index.php" class="footer-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver al sitio principal
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmit');
            // Reemplazar ícono por spinner manteniendo el color de fuente
            btn.innerHTML = '<svg class="spin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Verificando...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        });
    </script>
    <?php include_once __DIR__ . '/../components/cookie_banner.php'; ?>
</body>
</html>
