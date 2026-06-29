<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'JSON invalido'], 400);
    }
    return $data;
}

function scalar($value, $default = null)
{
    return isset($value) && $value !== '' ? $value : $default;
}

function month_bounds(int $year, int $month): array
{
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    return [$start, $end];
}

function ensure_schema(PDO $conn): void
{
    $sql = file_get_contents(__DIR__ . '/../../database/volqueta_finanzas.sql');
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el esquema SQL.');
    }
    $conn->exec($sql);
}

function config(PDO $conn): array
{
    $row = $conn->query('SELECT * FROM volqueta_config WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    return [
        'anio' => (int)($row['anio'] ?? 2026),
        'mes' => (int)($row['mes'] ?? 6),
        'meta_diaria_lj' => (int)($row['meta_diaria_lj'] ?? 20),
        'porcentaje_mio' => (float)($row['porcentaje_mio'] ?? 0.22),
        'juancho_porcentaje' => (float)($row['juancho_porcentaje'] ?? 0.5),
        'beto_porcentaje' => (float)($row['beto_porcentaje'] ?? 0.5),
    ];
}

function get_catalogs(PDO $conn): array
{
    $rows = $conn->query("SELECT tipo, nombre FROM volqueta_catalogos WHERE activo = 1 ORDER BY tipo, orden, nombre")->fetchAll(PDO::FETCH_ASSOC);
    $catalogs = [
        'categoria_gasto' => [],
        'pagado_por' => [],
        'medio_pago' => [],
        'tio' => [],
    ];
    foreach ($rows as $row) {
        $catalogs[$row['tipo']][] = $row['nombre'];
    }
    return $catalogs;
}

function summary(PDO $conn, int $year, int $month): array
{
    [$start, $end] = month_bounds($year, $month);
    $cfg = config($conn);

    $routes = $conn->query('SELECT id, nombre, tarifa, orden FROM volqueta_rutas WHERE activa = 1 ORDER BY orden, id')->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT v.fecha, v.ruta_id, v.cantidad, r.tarifa
        FROM volqueta_viajes v
        INNER JOIN volqueta_rutas r ON r.id = v.ruta_id
        WHERE v.fecha BETWEEN ? AND ?
    ");
    $stmt->execute([$start, $end]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tripsByDate = [];
    $totalTrips = 0;
    $gross = 0.0;
    foreach ($trips as $trip) {
        $date = $trip['fecha'];
        $qty = (int)$trip['cantidad'];
        $totalTrips += $qty;
        $gross += $qty * (float)$trip['tarifa'];
        $tripsByDate[$date][(int)$trip['ruta_id']] = $qty;
    }

    $days = [];
    $goal = 0;
    $cursor = strtotime($start);
    $last = strtotime($end);
    while ($cursor <= $last) {
        $date = date('Y-m-d', $cursor);
        $weekday = (int)date('N', $cursor);
        $base = $weekday <= 4 ? (int)$cfg['meta_diaria_lj'] : 0;
        $goal += $base;
        $qtyDay = array_sum($tripsByDate[$date] ?? []);
        $days[] = [
            'fecha' => $date,
            'dia' => ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'][$weekday - 1],
            'semana' => (int)ceil(((int)date('j', $cursor)) / 7),
            'total_viajes' => $qtyDay,
            'meta_base' => $base,
        ];
        $cursor = strtotime('+1 day', $cursor);
    }

    $expensesStmt = $conn->prepare('SELECT COALESCE(SUM(valor),0) FROM volqueta_gastos WHERE fecha BETWEEN ? AND ?');
    $expensesStmt->execute([$start, $end]);
    $expenses = (float)$expensesStmt->fetchColumn();

    $contribStmt = $conn->prepare('SELECT COALESCE(SUM(valor),0) FROM volqueta_aportes WHERE fecha BETWEEN ? AND ?');
    $contribStmt->execute([$start, $end]);
    $contribs = (float)$contribStmt->fetchColumn();

    $byTioStmt = $conn->prepare('SELECT tio, COALESCE(SUM(valor),0) total FROM volqueta_aportes WHERE fecha BETWEEN ? AND ? GROUP BY tio');
    $byTioStmt->execute([$start, $end]);
    $sent = ['Juancho' => 0.0, 'Beto' => 0.0];
    foreach ($byTioStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sent[$row['tio']] = (float)$row['total'];
    }

    $categoryStmt = $conn->prepare('SELECT categoria, COALESCE(SUM(valor),0) total FROM volqueta_gastos WHERE fecha BETWEEN ? AND ? GROUP BY categoria ORDER BY total DESC');
    $categoryStmt->execute([$start, $end]);

    $routeTotalsStmt = $conn->prepare("
        SELECT r.id, r.nombre, r.tarifa, COALESCE(SUM(v.cantidad),0) viajes,
               COALESCE(SUM(v.cantidad * r.tarifa),0) valor
        FROM volqueta_rutas r
        LEFT JOIN volqueta_viajes v ON v.ruta_id = r.id AND v.fecha BETWEEN ? AND ?
        WHERE r.activa = 1
        GROUP BY r.id, r.nombre, r.tarifa, r.orden
        ORDER BY r.orden, r.id
    ");
    $routeTotalsStmt->execute([$start, $end]);

    $mine = $gross * (float)$cfg['porcentaje_mio'];
    $juanchoDue = $expenses * (float)$cfg['juancho_porcentaje'];
    $betoDue = $expenses * (float)$cfg['beto_porcentaje'];

    return [
        'config' => $cfg,
        'periodo' => ['anio' => $year, 'mes' => $month, 'inicio' => $start, 'fin' => $end],
        'rutas' => $routes,
        'dias' => $days,
        'totales' => [
            'meta_mensual' => $goal,
            'viajes_realizados' => $totalTrips,
            'viajes_pendientes' => max(0, $goal - $totalTrips),
            'valor_bruto' => $gross,
            'mi_ganancia' => $mine,
            'gastos' => $expenses,
            'aportes_recibidos' => $contribs,
            'resultado_caja' => $mine + $contribs - $expenses,
            'ganancia_menos_gastos' => $mine - $expenses,
        ],
        'tios' => [
            [
                'nombre' => 'Juancho',
                'porcentaje' => (float)$cfg['juancho_porcentaje'],
                'debe_cubrir' => $juanchoDue,
                'ya_envio' => $sent['Juancho'],
                'falta' => max(0, $juanchoDue - $sent['Juancho']),
                'saldo_favor' => max(0, $sent['Juancho'] - $juanchoDue),
            ],
            [
                'nombre' => 'Beto',
                'porcentaje' => (float)$cfg['beto_porcentaje'],
                'debe_cubrir' => $betoDue,
                'ya_envio' => $sent['Beto'],
                'falta' => max(0, $betoDue - $sent['Beto']),
                'saldo_favor' => max(0, $sent['Beto'] - $betoDue),
            ],
        ],
        'por_ruta' => $routeTotalsStmt->fetchAll(PDO::FETCH_ASSOC),
        'por_categoria' => $categoryStmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

try {
    ensure_schema($conn);
    $resource = $_GET['resource'] ?? 'summary';
    $method = $_SERVER['REQUEST_METHOD'];
    $cfg = config($conn);
    $year = (int)($_GET['anio'] ?? $cfg['anio']);
    $month = (int)($_GET['mes'] ?? $cfg['mes']);

    if ($resource === 'bootstrap' && $method === 'GET') {
        json_response([
            'config' => $cfg,
            'catalogos' => get_catalogs($conn),
            'rutas' => $conn->query('SELECT * FROM volqueta_rutas WHERE activa = 1 ORDER BY orden, id')->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    if ($resource === 'summary' && $method === 'GET') {
        json_response(summary($conn, $year, $month));
    }

    if ($resource === 'config' && $method === 'PUT') {
        $data = body();
        $stmt = $conn->prepare('
            UPDATE volqueta_config
            SET anio = ?, mes = ?, meta_diaria_lj = ?, porcentaje_mio = ?,
                juancho_porcentaje = ?, beto_porcentaje = ?
            WHERE id = 1
        ');
        $stmt->execute([
            (int)$data['anio'],
            (int)$data['mes'],
            (int)$data['meta_diaria_lj'],
            (float)$data['porcentaje_mio'],
            (float)$data['juancho_porcentaje'],
            (float)$data['beto_porcentaje'],
        ]);
        json_response(['ok' => true, 'config' => config($conn)]);
    }

    if ($resource === 'routes' && $method === 'PUT') {
        foreach (body()['rutas'] ?? [] as $route) {
            $stmt = $conn->prepare('UPDATE volqueta_rutas SET tarifa = ?, nombre = ?, orden = ? WHERE id = ?');
            $stmt->execute([(float)$route['tarifa'], $route['nombre'], (int)$route['orden'], (int)$route['id']]);
        }
        json_response(['ok' => true]);
    }

    if ($resource === 'catalogs' && $method === 'PUT') {
        $data = body();
        foreach (['categoria_gasto','pagado_por','medio_pago'] as $type) {
            if (!isset($data[$type]) || !is_array($data[$type])) {
                continue;
            }
            $conn->prepare('DELETE FROM volqueta_catalogos WHERE tipo = ?')->execute([$type]);
            $order = 1;
            foreach ($data[$type] as $name) {
                $name = trim((string)$name);
                if ($name === '') {
                    continue;
                }
                $stmt = $conn->prepare('INSERT INTO volqueta_catalogos (tipo, nombre, orden, activo) VALUES (?, ?, ?, 1)');
                $stmt->execute([$type, $name, $order++]);
            }
        }
        json_response(['ok' => true, 'catalogos' => get_catalogs($conn)]);
    }

    if ($resource === 'trips' && $method === 'GET') {
        [$start, $end] = month_bounds($year, $month);
        $stmt = $conn->prepare('SELECT * FROM volqueta_viajes WHERE fecha BETWEEN ? AND ? ORDER BY fecha, ruta_id');
        $stmt->execute([$start, $end]);
        json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($resource === 'trips' && $method === 'POST') {
        $data = body();
        $stmt = $conn->prepare('
            INSERT INTO volqueta_viajes (fecha, ruta_id, cantidad, observaciones)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), observaciones = VALUES(observaciones)
        ');
        $stmt->execute([$data['fecha'], (int)$data['ruta_id'], (int)$data['cantidad'], scalar($data['observaciones'] ?? null)]);
        json_response(['ok' => true]);
    }

    if (in_array($resource, ['expenses', 'contributions'], true)) {
        $table = $resource === 'expenses' ? 'volqueta_gastos' : 'volqueta_aportes';
        if ($method === 'GET') {
            [$start, $end] = month_bounds($year, $month);
            $stmt = $conn->prepare("SELECT * FROM {$table} WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC, id DESC");
            $stmt->execute([$start, $end]);
            json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        if ($method === 'POST' || $method === 'PUT') {
            $data = body();
            if ($resource === 'expenses') {
                if (!empty($data['id'])) {
                    $stmt = $conn->prepare('UPDATE volqueta_gastos SET fecha=?, categoria=?, detalle=?, valor=?, pagado_por=?, medio_pago=?, observaciones=? WHERE id=?');
                    $stmt->execute([$data['fecha'], $data['categoria'], scalar($data['detalle'] ?? null), (float)$data['valor'], scalar($data['pagado_por'] ?? null), scalar($data['medio_pago'] ?? null), scalar($data['observaciones'] ?? null), (int)$data['id']]);
                } else {
                    $stmt = $conn->prepare('INSERT INTO volqueta_gastos (fecha,categoria,detalle,valor,pagado_por,medio_pago,observaciones) VALUES (?,?,?,?,?,?,?)');
                    $stmt->execute([$data['fecha'], $data['categoria'], scalar($data['detalle'] ?? null), (float)$data['valor'], scalar($data['pagado_por'] ?? null), scalar($data['medio_pago'] ?? null), scalar($data['observaciones'] ?? null)]);
                }
            } else {
                if (!empty($data['id'])) {
                    $stmt = $conn->prepare('UPDATE volqueta_aportes SET fecha=?, tio=?, concepto=?, valor=?, medio_envio=?, observaciones=? WHERE id=?');
                    $stmt->execute([$data['fecha'], $data['tio'], scalar($data['concepto'] ?? null), (float)$data['valor'], scalar($data['medio_envio'] ?? null), scalar($data['observaciones'] ?? null), (int)$data['id']]);
                } else {
                    $stmt = $conn->prepare('INSERT INTO volqueta_aportes (fecha,tio,concepto,valor,medio_envio,observaciones) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$data['fecha'], $data['tio'], scalar($data['concepto'] ?? null), (float)$data['valor'], scalar($data['medio_envio'] ?? null), scalar($data['observaciones'] ?? null)]);
                }
            }
            json_response(['ok' => true]);
        }
        if ($method === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                json_response(['error' => 'ID requerido'], 400);
            }
            $conn->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
            json_response(['ok' => true]);
        }
    }

    json_response(['error' => 'Endpoint no encontrado'], 404);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
