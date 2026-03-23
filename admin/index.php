<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

// Para que el header.php no falle intentando buscar notificaciones del admin
$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 0;

// 1. Obtener contadores globales
$stmt = $conn->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
$conteos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total_empresas = $conteos['representante'] ?? 0;
$total_sst = $conteos['sst'] ?? 0;
$total_trabajadores = $conteos['trabajador'] ?? 0;
$total_usuarios = array_sum($conteos);

// 2. Obtener lista de empresas (Representantes Legales)
$stmt_empresas = $conn->query("SELECT id, nombre, apellido, cedula, email, ciudad, telefono, fecha_registro FROM usuarios WHERE rol = 'representante' ORDER BY id DESC");
$empresas = $stmt_empresas->fetchAll(PDO::FETCH_ASSOC);

$admin_nombre = $_SESSION['cpanel_admin_nombre'] ?? 'Administrador';

// Variables para igualar el header.php
$current_page = 'index.php';
$titulo_header = "Panel de Control Master";
$rol_display = "Super Administrador";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Master | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ESTILOS EXACTOS DE DASHBOARD.PHP */
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

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, var(--bg1), var(--bg2));
            margin: 0; padding: 0; min-height: 100vh;
            color: var(--text); display: flex; font-size: 0.85rem;
        }

        .main-wrapper {
            margin-left: 260px; width: calc(100% - 260px);
            display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease;
        }

        .content-area {
            padding: 32px 40px; flex: 1; max-width: 1400px;
            margin: 0 auto; width: 100%; box-sizing: border-box;
        }

        /* ENCABEZADOS IDENTICOS A DASHBOARD */
        .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .welcome-title { margin: 0 0 6px 0; font-size: 1.25rem; color: var(--text); letter-spacing: -0.01em; }
        .welcome-text { color: var(--muted); margin: 0; font-size: 0.85rem; }

        .section-title {
            font-size: 0.85rem; font-weight: 700; color: var(--text); margin: 24px 0 12px 0; padding-bottom: 8px;
            border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.05em;
        }

        /* GRID Y TARJETAS EXACTAS */
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;}
        
        .info-card {
            background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: flex-start; gap: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        
        .icon-box {
            width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .info-content { display: flex; flex-direction: column; gap: 3px; overflow: hidden; padding-top: 2px; }
        .info-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin: 0; }
        .info-value { font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0; }

        /* GRÁFICAS ADAPTADAS AL ESTILO */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .chart-container {
            background: var(--card); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); height: 300px; display: flex; flex-direction: column;
        }
        .chart-wrapper { position: relative; flex: 1; width: 100%; min-height: 0; }

        /* TABLA CON ESTILOS BASE */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 12px 16px; font-size: 0.65rem; text-transform: uppercase; color: var(--muted); font-weight: 700; border-bottom: 1px solid var(--border); letter-spacing: 0.05em; }
        td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: var(--text); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        .badge-status { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-block; }
        .client-avatar { width: 28px; height: 28px; border-radius: 6px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; margin-right: 8px; vertical-align: middle; }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .content-area { padding: 20px 16px; }
            .charts-grid { grid-template-columns: 1fr; }
            .table-wrapper { overflow-x: auto; }
            table { min-width: 600px; }
        }
    </style>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <main class="main-wrapper">

        <?php include '../components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div>
                    <h1 class="welcome-title">Métricas y Panel General</h1>
                    <p class="welcome-text">Resumen global de la plataforma, roles y empresas registradas.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="icon-box" style="background: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Total Usuarios</p>
                        <p class="info-value"><?php echo $total_usuarios; ?></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="icon-box" style="background: rgba(255, 138, 31, 0.08); color: var(--primary2);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Empresas Activas</p>
                        <p class="info-value"><?php echo $total_empresas; ?></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="icon-box" style="background: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Responsables SST</p>
                        <p class="info-value"><?php echo $total_sst; ?></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="icon-box" style="background: rgba(139, 92, 246, 0.08); color: #8b5cf6;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="info-content">
                        <p class="info-label">Trabajadores</p>
                        <p class="info-value"><?php echo $total_trabajadores; ?></p>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <p class="info-label" style="margin-bottom: 12px;">Distribución de Roles</p>
                    <div class="chart-wrapper">
                        <canvas id="rolesChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <p class="info-label" style="margin-bottom: 12px;">Volumen de Plataforma</p>
                    <div class="chart-wrapper">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <h2 class="section-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Directorio de Empresas (Clientes)
            </h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empresa / Representante</th>
                            <th>NIT / Cédula</th>
                            <th>Email de Contacto</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--muted); font-style: italic;">Sin registros aún.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $emp): ?>
                                <tr>
                                    <td style="color: var(--muted); font-weight: 600;">#<?php echo str_pad($emp['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td style="font-weight: 500;">
                                        <div class="client-avatar">
                                            <?php echo strtoupper(substr($emp['nombre'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>
                                    </td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($emp['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['ciudad'] ?: '-'); ?></td>
                                    <td><span class="badge-status">Activa</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?php include '../components/modal_confirmacion.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltip Configuration
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#64748b';
            const commonTooltip = {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleFont: { family: 'Inter', size: 13, weight: '700' },
                bodyFont: { family: 'Inter', size: 12 },
                padding: 10, cornerRadius: 8
            };

            const dataRoles = [ <?php echo $total_empresas; ?>, <?php echo $total_sst; ?>, <?php echo $total_trabajadores; ?> ];

            // Gráfica de Anillo (Roles)
            new Chart(document.getElementById('rolesChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Empresas', 'Resp. SST', 'Trabajadores'],
                    datasets: [{
                        data: dataRoles,
                        backgroundColor: ['#ff8a1f', '#10b981', '#8b5cf6'],
                        borderWidth: 0, hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { bottom: 10 } },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } },
                        tooltip: commonTooltip
                    },
                    cutout: '72%'
                }
            });

            // Gráfica de Barras (Resumen)
            new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Empresas', 'Resp. SST', 'Trabajadores'],
                    datasets: [{
                        data: dataRoles,
                        backgroundColor: ['rgba(255,138,31,0.85)', 'rgba(16,185,129,0.85)', 'rgba(139,92,246,0.85)'],
                        borderRadius: 6, maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: { top: 10 } },
                    plugins: { legend: { display: false }, tooltip: commonTooltip },
                    scales: {
                        y: {
                            beginAtZero: true, border: { display: false },
                            ticks: { font: { size: 11 }, padding: 8 },
                            grid: { color: '#f1f5f9', drawTicks: false }
                        },
                        x: {
                            border: { display: false },
                            ticks: { font: { size: 11, weight: '500' }, padding: 8 },
                            grid: { display: false, drawTicks: false }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>