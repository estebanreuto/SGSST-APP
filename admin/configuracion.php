<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['cpanel_admin_id'];
$success_msg = "";
$error_msg = "";

// =======================================================
// LÓGICA PARA ACTUALIZAR DATOS, CONTRASEÑA Y WOMPI
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ACTUALIZAR PERFIL (NOMBRE Y EMAIL)
    if (isset($_POST['update_profile'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (!empty($nombre) && !empty($email)) {
            try {
                $stmt = $conn->prepare("UPDATE cpanel_admins SET nombre = ?, email = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $admin_id]);
                
                // Actualizar la variable de sesión
                $_SESSION['cpanel_admin_nombre'] = $nombre;
                $success_msg = "Perfil actualizado correctamente.";
            } catch (PDOException $e) {
                $error_msg = "Error al actualizar el perfil. Verifica las columnas en la BD.";
            }
        } else {
            $error_msg = "El nombre y el correo son obligatorios.";
        }
    }
    
    // 2. ACTUALIZAR CONTRASEÑA
    if (isset($_POST['update_password'])) {
        $pass_actual = $_POST['pass_actual'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        $pass_confirma = $_POST['pass_confirma'] ?? '';
        
        if (!empty($pass_actual) && !empty($pass_nueva) && !empty($pass_confirma)) {
            if ($pass_nueva !== $pass_confirma) {
                $error_msg = "Las contraseñas nuevas no coinciden.";
            } else {
                // Verificar contraseña actual
                $stmt = $conn->prepare("SELECT password FROM cpanel_admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin_data && password_verify($pass_actual, $admin_data['password'])) {
                    // Encriptar y guardar la nueva
                    $hash_nuevo = password_hash($pass_nueva, PASSWORD_DEFAULT);
                    $stmt_upd = $conn->prepare("UPDATE cpanel_admins SET password = ? WHERE id = ?");
                    $stmt_upd->execute([$hash_nuevo, $admin_id]);
                    
                    $success_msg = "Contraseña actualizada de forma segura.";
                } else {
                    $error_msg = "La contraseña actual es incorrecta.";
                }
            }
        } else {
            $error_msg = "Todos los campos de contraseña son obligatorios.";
        }
    }

    // 3. ACTUALIZAR LLAVES DE WOMPI
    if (isset($_POST['update_wompi'])) {
        $wompi_public = trim($_POST['wompi_public'] ?? '');
        $wompi_private = trim($_POST['wompi_private'] ?? '');
        $wompi_integrity = trim($_POST['wompi_integrity'] ?? '');

        try {
            $stmt = $conn->prepare("UPDATE cpanel_admins SET wompi_public = ?, wompi_private = ?, wompi_integrity = ? WHERE id = ?");
            $stmt->execute([$wompi_public, $wompi_private, $wompi_integrity, $admin_id]);
            $success_msg = "Credenciales de Wompi guardadas con éxito.";
        } catch (PDOException $e) {
            $error_msg = "Error al guardar Wompi. Por favor ejecuta el código SQL que aparece en pantalla.";
        }
    }
}

// OBTENER DATOS ACTUALES DEL ADMIN
$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';
$admin_email = '';
$wompi_public = '';
$wompi_private = '';
$wompi_integrity = '';
$error_columnas_db = false;

try {
    $stmt_admin = $conn->prepare("SELECT nombre, email, wompi_public, wompi_private, wompi_integrity FROM cpanel_admins WHERE id = ?");
    $stmt_admin->execute([$admin_id]);
    if ($admin_info = $stmt_admin->fetch(PDO::FETCH_ASSOC)) {
        $admin_nombre = $admin_info['nombre'];
        $admin_email = $admin_info['email'] ?? '';
        $wompi_public = $admin_info['wompi_public'] ?? '';
        $wompi_private = $admin_info['wompi_private'] ?? '';
        $wompi_integrity = $admin_info['wompi_integrity'] ?? '';
    }
} catch (PDOException $e) { 
    $error_columnas_db = true;
}

// Variables para el header
$current_page = 'configuracion.php';
$_SESSION['usuario_id'] = 0; // Prevenir errores del header
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
        :root { 
            --primary: #ff8a1f; --primary2: #ff7a00; 
            --bg1: #edf4fb; --bg2: #f7f9fc; 
            --card: #ffffff; --text: #1f2d3d; 
            --muted: #5f6f82; --border: #dbe3ec; 
            --radius: 16px; --blue-dark: #1e3a8a; --blue-main: #3b82f6;
        }
        
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; }
        
        /* ENCABEZADO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .estandar-header-group { display: flex; align-items: center; gap: 16px; }
        .icon-box-std { width: 48px; height: 48px; background: rgba(59, 130, 246, 0.1); color: var(--blue-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(59, 130, 246, 0.2); font-size: 1.3rem;}
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.25rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; line-height: 1.3; }
        .estandar-subtitle { margin: 4px 0 0 0; color: var(--muted); font-size: 0.85rem; font-weight: 500; line-height: 1.4; }

        /* GRID DE CONFIGURACIÓN */
        .config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        
        .config-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); 
            padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            position: relative; overflow: hidden;
        }
        
        .card-title { font-size: 1.1rem; color: var(--blue-dark); font-weight: 800; margin: 0 0 8px 0; display: flex; align-items: center; gap: 10px; }
        .card-desc { font-size: 0.85rem; color: var(--muted); margin: 0 0 24px 0; line-height: 1.5; }
        
        /* FORMULARIOS */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
        
        .input-control { position: relative; }
        .input-control i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; pointer-events: none; transition: color 0.2s;}
        .input-control input { 
            width: 100%; padding: 12px 14px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-family: 'Inter', sans-serif; font-size: 0.9rem; color: var(--text); 
            background: #f8fafc; transition: all 0.2s; box-sizing: border-box; 
        }
        .input-control input:focus { outline: none; border-color: var(--blue-main); background: #ffffff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        .input-control input:focus + i { color: var(--blue-main); }

        /* Wompi Inputs (Sin icono, estilo limpio) */
        .input-clean input { padding: 12px 14px; }
        
        /* BOTONES */
        .btn-save { 
            background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; 
            border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; 
            cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; 
            width: 100%; justify-content: center; margin-top: 10px; box-shadow: 0 4px 12px rgba(255, 138, 31, 0.2);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.3); }

        .btn-dark { background: #0f172a; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); width: auto; padding: 12px 28px; float: right; margin-top: 20px;}
        .btn-dark:hover { background: #020617; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25); }

        /* TARJETA WOMPI ESPECIAL */
        .wompi-card {
            grid-column: 1 / -1; /* Ocupa todo el ancho */
            border-left: 5px solid #10b981; /* Borde verde elegante */
            padding: 30px;
        }
        .wompi-card .card-title { color: #1e293b; font-size: 1.15rem; gap: 8px;}
        .wompi-card .card-title i { color: #64748b; font-size: 1.2rem;}

        /* ALERTAS */
        .alert-error, .alert-success {
            padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 0.85rem; 
            font-weight: 600; display: flex; align-items: center; gap: 12px; animation: fadeInDown 0.3s ease;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.05); }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.05); }
        
        .alert-dev { background: #fffbeb; border: 1px solid #fde68a; padding: 16px; border-radius: 12px; color: #d97706; margin-bottom: 24px; display: flex; gap: 12px; align-items: center; }
        
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* RESPONSIVE */
        @media (max-width: 992px) { .config-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .estandar-header-group { flex-direction: column; align-items: flex-start; gap: 12px; }
            .btn-dark { width: 100%; float: none; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-gear"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Configuración de Cuenta</h1>
                        <p class="estandar-subtitle">Administra tu información personal y los ajustes de seguridad y pagos.</p>
                    </div>
                </div>
            </div>

            <?php if ($error_columnas_db): ?>
                <div class="alert-dev">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">Atención desarrollador:</strong>
                        Faltan columnas en la base de datos para que esto funcione. Ejecuta este código en phpMyAdmin:<br>
                        <code style="background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; color: #b45309; display: inline-block; margin-top: 6px; font-weight: 700; word-break: break-all;">
                            ALTER TABLE cpanel_admins ADD COLUMN wompi_public VARCHAR(255) NULL, ADD COLUMN wompi_private VARCHAR(255) NULL, ADD COLUMN wompi_integrity VARCHAR(255) NULL;
                        </code>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.2rem;"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>

            <div class="config-grid">
                
                <div class="config-card">
                    <h2 class="card-title"><i class="fa-solid fa-user-shield" style="color: var(--primary);"></i> Perfil del Administrador</h2>
                    <p class="card-desc">Actualiza tu nombre visible y el correo electrónico asociado a la cuenta maestra.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <div class="input-control">
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($admin_nombre); ?>" required>
                                <i class="fa-regular fa-user"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <div class="input-control">
                                <input type="email" name="email" value="<?php echo htmlspecialchars($admin_email); ?>" placeholder="admin@preventwork.com" required>
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Perfil
                        </button>
                    </form>
                </div>

                <div class="config-card">
                    <h2 class="card-title"><i class="fa-solid fa-lock" style="color: var(--blue-main);"></i> Seguridad y Contraseña</h2>
                    <p class="card-desc">Cambia tu contraseña regularmente para mantener la plataforma segura.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_password" value="1">
                        
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <div class="input-control">
                                <input type="password" name="pass_actual" placeholder="••••••••" required>
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <div class="input-control">
                                <input type="password" name="pass_nueva" placeholder="••••••••" required minlength="6">
                                <i class="fa-solid fa-key"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <div class="input-control">
                                <input type="password" name="pass_confirma" placeholder="••••••••" required minlength="6">
                                <i class="fa-solid fa-check-double"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-save" style="background: linear-gradient(135deg, var(--blue-main), #1e3a8a); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);">
                            <i class="fa-solid fa-lock"></i> Actualizar Contraseña
                        </button>
                    </form>
                </div>

                <div class="config-card wompi-card">
                    <h2 class="card-title"><i class="fa-regular fa-credit-card"></i> Integración Wompi</h2>
                    <p class="card-desc" style="margin-bottom: 20px;">Ingresa tus llaves proporcionadas por Wompi Bancolombia para habilitar la pasarela de pagos automática.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_wompi" value="1">
                        
                        <div class="form-group">
                            <label>Llave Pública (Public Key)</label>
                            <div class="input-control input-clean">
                                <input type="text" name="wompi_public" value="<?php echo htmlspecialchars($wompi_public); ?>" placeholder="pub_test_..." required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Llave Privada (Private Key)</label>
                            <div class="input-control input-clean">
                                <input type="text" name="wompi_private" value="<?php echo htmlspecialchars($wompi_private); ?>" placeholder="prv_test_..." required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Secreto de Integridad</label>
                            <div class="input-control input-clean">
                                <input type="text" name="wompi_integrity" value="<?php echo htmlspecialchars($wompi_integrity); ?>" placeholder="test_integrity_..." required>
                            </div>
                        </div>

                        <div style="width: 100%; border-top: 1px solid #e2e8f0; margin-top: 24px;">
                            <button type="submit" class="btn-save btn-dark">
                                Guardar Credenciales
                            </button>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </main>

    <script>
        // Ocultar alertas automáticamente después de 5 segundos
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>