<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

// Variables de sesión básicas
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Mapear roles a nombres legibles
$roles_nombres = [
    'representante' => 'Representante Legal',
    'sst' => 'Responsable SG-SST',
    'trabajador' => 'Trabajador'
];
$rol_display = $roles_nombres[$usuario_rol] ?? 'Usuario';

// 1. Obtener TODA la información del usuario en sesión
try {
    $sql = "SELECT nombre, apellido, cedula, email, telefono, ciudad, rol, 
                   licencia_sst, tipo_licencia, numero_licencia, firma,
                   fecha_licencia as fecha_licencia_raw,
                   DATE_FORMAT(fecha_licencia, '%d/%m/%Y') as fecha_licencia,
                   logo_empresa, nombre_empresa, tipo_persona, regimen_tributario, tipo_doc_empresa, num_doc_empresa
            FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_info) {
        $usuario_info = [];
    }
} catch (PDOException $e) {
    $usuario_info = [];
}

// LÓGICA DEL MODAL DE EMPRESA: Verificar si faltan datos de la empresa para el representante
$mostrar_modal_empresa = false;
if ($usuario_rol === 'representante') {
    if (empty($usuario_info['nombre_empresa']) || 
        empty($usuario_info['tipo_persona']) || 
        empty($usuario_info['regimen_tributario']) || 
        empty($usuario_info['tipo_doc_empresa']) || 
        empty($usuario_info['num_doc_empresa'])) {
        $mostrar_modal_empresa = true;
    }
}

// 2. OBTENER EL HISTORIAL DE ACTAS (Para el Modal de Versiones)
$historial_actas = [];
if ($usuario_rol === 'sst' || $usuario_rol === 'representante') {
    try {
        $sst_target_id = 0;
        
        if ($usuario_rol === 'sst') {
            $sst_target_id = $_SESSION['usuario_id'];
        } elseif ($usuario_rol === 'representante') {
            $stmt_doc = $conn->prepare("SELECT sst_id FROM doc_asignacion_sst WHERE estado IN ('pendiente_firma', 'firmado') ORDER BY id DESC LIMIT 1");
            $stmt_doc->execute();
            $doc_temp = $stmt_doc->fetch(PDO::FETCH_ASSOC);
            if ($doc_temp) {
                $sst_target_id = $doc_temp['sst_id'];
            }
        }

        if ($sst_target_id > 0) {
            $stmt_hist = $conn->prepare("SELECT d.*, 
                                           u_rep.nombre as rep_nombre, 
                                           u_rep.apellido as rep_apellido, 
                                           u_sst.nombre as sst_nombre, 
                                           u_sst.apellido as sst_apellido
                                    FROM doc_asignacion_sst d 
                                    LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id 
                                    LEFT JOIN usuarios u_sst ON d.sst_id = u_sst.id 
                                    WHERE d.sst_id = ? AND d.estado = 'firmado' 
                                    ORDER BY d.id DESC");
            $stmt_hist->execute([$sst_target_id]);
            $historial_actas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { 
        // Silencioso
    }
}

function mostrarDato($dato)
{
    return !empty($dato) ? htmlspecialchars($dato) : '<span style="color: #94a3b8; font-style: italic; font-weight: 400;">No registrado</span>';
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
        :root {
            --primary: #ff8a1f;
            --primary2: #ff7a00;
            --bg1: #edf4fb;
            --bg2: #f7f9fc;
            --card: #ffffff;
            --text: #1f2d3d;
            --muted: #5f6f82;
            --border: #dbe3ec;
            --radius: 12px;
        }

        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 32px 40px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .alert-success { background: #dcfce7; color: #166534; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid #bbf7d0; transition: opacity 0.5s ease, transform 0.5s ease; opacity: 1; transform: translateY(0); }
        .alert-success.hide { opacity: 0; transform: translateY(-10px); pointer-events: none; }
        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .welcome-title { margin: 0 0 6px 0; font-size: 1.25rem; color: var(--text); letter-spacing: -0.01em; }
        .welcome-text { color: var(--muted); margin: 0; font-size: 0.85rem; }
        .btn-edit { background-color: var(--primary); color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background-color 0.2s, transform 0.1s; }
        .btn-edit:hover { background-color: var(--primary2); transform: translateY(-1px); }
        .section-title { font-size: 0.85rem; font-weight: 700; color: var(--text); margin: 24px 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .info-card { background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: flex-start; gap: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        .icon-box { width: 36px; height: 36px; background: rgba(255, 138, 31, 0.08); color: var(--primary2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.3s ease; }
        .info-content { display: flex; flex-direction: column; gap: 3px; overflow: hidden; padding-top: 2px; }
        .info-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin: 0; }
        .info-value { font-size: 0.85rem; font-weight: 600; color: var(--text); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge-rol { display: inline-block; background: rgba(255, 138, 31, 0.12); color: var(--primary2); padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .header-actions { flex-direction: column; gap: 16px; }
            .btn-edit { width: 100%; justify-content: center; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                <div class="alert-success" id="alertSuccess">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ¡Tu información ha sido actualizada correctamente!
                </div>
            <?php endif; ?>

            <div class="header-actions">
                <div>
                    <h1 class="welcome-title">Mi Perfil</h1>
                    <p class="welcome-text">Resumen de tu información personal y roles dentro de la plataforma.</p>
                </div>
                <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                    <button class="btn-edit" id="btnOpenModal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Editar Información
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($usuario_info)): ?>

                <h2 class="section-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Información Personal
                </h2>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Nombre Completo</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['nombre'] . ' ' . $usuario_info['apellido']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Documento</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['cedula']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Correo Electrónico</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['email']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Teléfono</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['telefono']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Ciudad</p>
                            <p class="info-value"><?php echo mostrarDato($usuario_info['ciudad']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                        </div>
                        <div class="info-content">
                            <p class="info-label">Rol Asignado</p>
                            <p class="info-value"><span class="badge-rol"><?php echo htmlspecialchars($rol_display); ?></span></p>
                        </div>
                    </div>
                </div>

                <?php if ($usuario_info['licencia_sst'] === 'si' || !empty($usuario_info['numero_licencia']) || $usuario_rol === 'sst'): ?>
                    <h2 class="section-title" style="margin-top: 30px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Certificación SG-SST
                    </h2>
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                            <div class="info-content"><p class="info-label">Tipo de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['tipo_licencia']); ?></p></div>
                        </div>
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg></div>
                            <div class="info-content"><p class="info-label">Número de Licencia</p><p class="info-value"><?php echo mostrarDato($usuario_info['numero_licencia']); ?></p></div>
                        </div>
                        <div class="info-card">
                            <div class="icon-box" style="background: rgba(74, 127, 191, 0.08); color: #4a7fbf;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                            <div class="info-content"><p class="info-label">Fecha de Expedición</p><p class="info-value"><?php echo mostrarDato($usuario_info['fecha_licencia']); ?></p></div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="padding: 16px; background: #fee2e2; color: #dc2626; border-radius: 8px; font-size: 0.85rem;">
                    Error al cargar la información del usuario.
                </div>
            <?php endif; ?>

            <?php include 'components/estandares_minimos.php'; ?>

        </div>
    </main>

    <?php include 'components/modal_editar_perfil.php'; ?>
    <?php include 'components/modal_confirmacion.php'; ?>
    <?php include 'components/modal_versiones_acta.php'; ?>
    
    <?php include 'components/modal_empresa.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alertSuccess = document.getElementById('alertSuccess');
            if (alertSuccess) {
                setTimeout(() => {
                    alertSuccess.classList.add('hide');
                    setTimeout(() => {
                        alertSuccess.remove();
                        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({path:newUrl}, '', newUrl);
                    }, 500);
                }, 4000); 
            }
        });
    </script>
</body>
</html>