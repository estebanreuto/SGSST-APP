<?php
session_start();
require_once 'config/db.php';

// Verificar si hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
$usuario_email = $_SESSION['usuario_email'] ?? '';

// Mapear roles a nombres legibles
$roles_nombres = [
    'representante' => 'Representante Legal',
    'sst' => 'Responsable SG-SST',
    'trabajador' => 'Trabajador'
];

$rol_display = $roles_nombres[$usuario_rol] ?? 'Usuario';

// Obtener estadísticas básicas del usuario
try {
    $stmt = $conn->prepare("SELECT cedula, telefono, DATE_FORMAT(fecha_registro, '%d/%m/%Y') as fecha_registro FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuario_info = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SG-SST</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #1e293b;
        }

        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 32px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fb923c;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo svg {
            width: 28px;
            height: 28px;
        }

        .header-title {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-info {
            text-align: right;
            margin-right: 4px;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
        }

        .user-role {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Logout button */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(100, 116, 139, 0.2);
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(100, 116, 139, 0.3);
        }

        .btn-logout svg {
            width: 16px;
            height: 16px;
        }

        /* Main content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 32px;
        }

        .welcome-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .welcome-section h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .welcome-section p {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.6;
        }

        /* Role badge */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 16px;
            box-shadow: 0 2px 8px rgba(251, 146, 60, 0.25);
        }

        .role-badge svg {
            width: 18px;
            height: 18px;
        }

        /* Info cards grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .info-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            color: white;
        }

        .info-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .info-card h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .info-card-label {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* Actions section */
        .actions-section {
            background: white;
            border-radius: 16px;
            padding: 32px 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .actions-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            color: #475569;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: white;
            border-color: #fb923c;
            color: #fb923c;
            transform: translateX(4px);
        }

        .action-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 0 16px;
                height: auto;
                flex-direction: column;
                padding: 16px;
                gap: 12px;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }

            .header-title {
                display: none;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .container {
                padding: 24px 16px;
            }

            .welcome-section {
                padding: 24px;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .actions-section {
                padding: 24px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="logo">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                SG-SST
            </div>
            <span class="header-title">Sistema de Gestión de Seguridad y Salud en el Trabajo</span>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($usuario_nombre); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($rol_display); ?></div>
            </div>
            <a href="logout.php" class="btn-logout">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Cerrar Sesión
            </a>
        </div>
    </header>

    <!-- Main content -->
    <div class="container">
        <!-- Welcome section -->
        <div class="welcome-section">
            <h1>Bienvenido, <?php echo htmlspecialchars(explode(' ', $usuario_nombre)[0]); ?></h1>
            <p>Has iniciado sesión correctamente en el Sistema de Gestión de Seguridad y Salud en el Trabajo. Aquí podrás gestionar toda la información relacionada con tu rol en la organización.</p>
            
            <div class="role-badge">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($rol_display); ?>
            </div>
        </div>

        <!-- Info cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3>Email</h3>
                </div>
                <div class="info-card-value"><?php echo htmlspecialchars($usuario_email); ?></div>
            </div>

            <?php if ($usuario_info): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                    </div>
                    <h3>Cédula</h3>
                </div>
                <div class="info-card-value"><?php echo htmlspecialchars($usuario_info['cedula']); ?></div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3>Registro</h3>
                </div>
                <div class="info-card-value"><?php echo htmlspecialchars($usuario_info['fecha_registro']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions section -->
        <div class="actions-section">
            <h2>Acciones Rápidas</h2>
            <div class="actions-grid">
                <a href="#" class="action-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mi Perfil
                </a>
                <a href="#" class="action-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Documentos
                </a>
                <a href="#" class="action-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Reportes
                </a>
                <a href="#" class="action-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>
            </div>
        </div>
    </div>
</body>
</html>
