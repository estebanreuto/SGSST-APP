<?php
session_start();
require_once '../config/db.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Master | SG-SST Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a; --bg-card: #1e293b; --border: #334155;
            --primary: #3b82f6; --accent: #ff8a1f; --text-light: #f8fafc; --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-light); display: flex; min-height: 100vh; }

        /* SIDEBAR DARK */
        .sidebar { width: 260px; background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(20px); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-header { height: 70px; display: flex; align-items: center; padding: 0 24px; border-bottom: 1px solid var(--border); font-size: 1.2rem; font-weight: 800; color: white; }
        .sidebar-header span { color: var(--primary); margin-left: 6px; }
        .sidebar-nav { padding: 20px 16px; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: white; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; font-weight: 600; text-decoration: none; }
        .sidebar-footer { padding: 20px; border-top: 1px solid var(--border); }
        .btn-logout { display: flex; justify-content: center; background: rgba(239, 68, 68, 0.1); color: #f87171; text-decoration: none; padding: 12px; border-radius: 10px; font-weight: 600; transition: 0.2s; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.2); }

        /* MAIN CONTENT */
        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .top-header { height: 70px; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 90; }
        .top-header h2 { font-size: 1.2rem; font-weight: 700; }
        .admin-badge { background: rgba(255, 138, 31, 0.15); color: var(--accent); padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; }

        .dashboard-body { padding: 40px; }

        /* CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary); }
        .stat-card.accent::before { background: var(--accent); }
        .stat-card.green::before { background: #22c55e; }
        .stat-card.purple::before { background: #a855f7; }
        .stat-title { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .stat-value { font-size: 2.2rem; font-weight: 800; color: white; line-height: 1; }

        /* TABLE */
        .section-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; color: white; }
        .table-wrapper { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(15, 23, 42, 0.5); padding: 16px 24px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; border-bottom: 1px solid var(--border); }
        td { padding: 16px 24px; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: #cbd5e1; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .badge-status { background: rgba(34, 197, 94, 0.15); color: #4ade80; padding: 4px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            Master <span>Panel</span>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Vista General
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h2>Estadísticas Globales</h2>
            <div class="admin-badge">HOLA, <?php echo strtoupper(htmlspecialchars($admin_nombre)); ?></div>
        </header>

        <div class="dashboard-body">
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Usuarios</div>
                    <div class="stat-value"><?php echo $total_usuarios; ?></div>
                </div>
                <div class="stat-card accent">
                    <div class="stat-title">Empresas (Representantes)</div>
                    <div class="stat-value"><?php echo $total_empresas; ?></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-title">Responsables SST</div>
                    <div class="stat-value"><?php echo $total_sst; ?></div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-title">Trabajadores</div>
                    <div class="stat-value"><?php echo $total_trabajadores; ?></div>
                </div>
            </div>

            <h2 class="section-title">Directorio de Empresas (Clientes)</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empresa / Representante</th>
                            <th>NIT / Cédula</th>
                            <th>Email</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">Sin registros aún.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $emp): ?>
                                <tr>
                                    <td style="color: var(--primary); font-weight: 700;">#<?php echo $emp['id']; ?></td>
                                    <td style="color: white; font-weight: 500;"><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['ciudad'] ?: '-'); ?></td>
                                    <td><span class="badge-status">ACTIVA</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>