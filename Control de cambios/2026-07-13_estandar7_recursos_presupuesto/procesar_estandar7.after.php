<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar7_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar7_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
$accion = $_POST['accion'] ?? '';

function estandar7_redirect(string $msg, string $tipo = 'ok', array $extra = []): never
{
    $params = array_merge([
        'modulo' => 'recursos-sg-sst',
        'msg' => $msg,
        'tipo' => $tipo,
    ], $extra);
    header('Location: estandar7.php?' . http_build_query($params));
    exit;
}

if ($accion !== 'guardar_recursos') {
    estandar7_redirect('Accion no reconocida.', 'error');
}

if ($rol !== 'sst') {
    estandar7_redirect('Solo el responsable SST puede gestionar la asignacion de recursos.', 'error');
}

$stmtEmpresa = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmtEmpresa->execute([$usuario_id]);
$empresa_id = (int)$stmtEmpresa->fetchColumn();
if ($empresa_id <= 0) {
    estandar7_redirect('No se encontro una empresa asociada al usuario actual.', 'error');
}

$anio = (int)($_POST['anio'] ?? date('Y'));
if ($anio < 2020 || $anio > 2100) {
    $anio = (int)date('Y');
}

$catalogoItems = estandar7_recursos_flat_items();
$presupuesto = $_POST['presupuesto'] ?? [];
$ejecutado = $_POST['ejecutado'] ?? [];

try {
    $conn->beginTransaction();
    $sql = "
        INSERT INTO estandar7_recursos_presupuesto (
            empresa_id, anio, categoria_slug, categoria_nombre, item_slug, item_nombre,
            periodo, presupuestado, ejecutado, creado_por
        ) VALUES (
            :empresa_id, :anio, :categoria_slug, :categoria_nombre, :item_slug, :item_nombre,
            :periodo, :presupuestado, :ejecutado, :creado_por
        )
        ON DUPLICATE KEY UPDATE
            categoria_slug = VALUES(categoria_slug),
            categoria_nombre = VALUES(categoria_nombre),
            item_nombre = VALUES(item_nombre),
            presupuestado = VALUES(presupuestado),
            ejecutado = VALUES(ejecutado),
            actualizado_en = CURRENT_TIMESTAMP
    ";
    $stmt = $conn->prepare($sql);

    foreach ($catalogoItems as $itemSlug => $item) {
        for ($mes = 1; $mes <= 12; $mes++) {
            $stmt->execute([
                ':empresa_id' => $empresa_id,
                ':anio' => $anio,
                ':categoria_slug' => $item['categoria_slug'],
                ':categoria_nombre' => $item['categoria_nombre'],
                ':item_slug' => $itemSlug,
                ':item_nombre' => $item['item_nombre'],
                ':periodo' => $mes,
                ':presupuestado' => estandar7_clean_money($presupuesto[$itemSlug][$mes] ?? 0),
                ':ejecutado' => estandar7_clean_money($ejecutado[$itemSlug][$mes] ?? 0),
                ':creado_por' => $usuario_id,
            ]);
        }
    }

    $conn->commit();
    estandar7_redirect('Presupuesto de recursos actualizado.', 'ok', ['anio' => $anio]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    estandar7_redirect('No se pudo guardar el presupuesto de recursos.', 'error', ['anio' => $anio]);
}
