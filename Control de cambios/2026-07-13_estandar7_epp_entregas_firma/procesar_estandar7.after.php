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

if (!in_array($accion, ['guardar_recursos', 'guardar_analisis_consumo', 'guardar_epp_entrega', 'firmar_epp_entrega'], true)) {
    estandar7_redirect('Accion no reconocida.', 'error');
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

function estandar7_validar_firma(string $firma): bool
{
    return strlen($firma) <= 1500000
        && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $firma) === 1;
}

function estandar7_epp_redirect(string $msg, string $tipo = 'ok', array $extra = []): never
{
    estandar7_redirect($msg, $tipo, array_merge(['modulo' => 'epp'], $extra));
}

function estandar7_clean_text(string $value): string
{
    return trim(strip_tags($value));
}

if ($accion === 'firmar_epp_entrega') {
    if ($rol !== 'trabajador') {
        estandar7_epp_redirect('Solo el trabajador asignado puede firmar el recibido.', 'error');
    }

    $entrega_id = (int)($_POST['entrega_id'] ?? 0);
    $firma = (string)($_POST['firma_trabajador'] ?? '');
    if ($entrega_id <= 0 || !estandar7_validar_firma($firma)) {
        estandar7_epp_redirect('La firma no es valida. Intenta firmar de nuevo.', 'error', ['entrega_id' => $entrega_id]);
    }

    $stmt = $conn->prepare("
        UPDATE estandar7_epp_entregas
        SET firma_trabajador=?, fecha_firma=NOW(), estado='firmado'
        WHERE id=? AND empresa_id=? AND trabajador_id=? AND estado='pendiente_firma'
    ");
    $stmt->execute([$firma, $entrega_id, $empresa_id, $usuario_id]);

    if ($stmt->rowCount() <= 0) {
        estandar7_epp_redirect('No se encontro una entrega pendiente para firmar.', 'error', ['entrega_id' => $entrega_id]);
    }

    $stmtNotif = $conn->prepare("
        UPDATE notificaciones
        SET leida=1
        WHERE usuario_id=? AND referencia_tipo='estandar7_epp_entrega' AND referencia_id=?
    ");
    $stmtNotif->execute([$usuario_id, $entrega_id]);

    estandar7_epp_redirect('Recibido de EPP firmado correctamente.', 'ok', ['entrega_id' => $entrega_id]);
}

if (in_array($accion, ['guardar_recursos', 'guardar_analisis_consumo', 'guardar_epp_entrega'], true) && $rol !== 'sst') {
    estandar7_redirect('Solo el responsable SST puede registrar o modificar esta informacion.', 'error');
}

if ($accion === 'guardar_epp_entrega') {
    $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
    $fecha_entrega = trim((string)($_POST['fecha_entrega'] ?? ''));
    $tipo_entrega = estandar7_clean_text((string)($_POST['tipo_entrega'] ?? 'Ordinaria'));
    if (!in_array($tipo_entrega, ['Ordinaria', 'Desgaste', 'Perdida'], true)) {
        $tipo_entrega = 'Ordinaria';
    }

    if ($trabajador_id <= 0 || $fecha_entrega === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_entrega) !== 1) {
        estandar7_epp_redirect('Selecciona trabajador y fecha de entrega.', 'error');
    }

    $stmtTrabajador = $conn->prepare("
        SELECT u.id, u.nombre, u.apellido, u.cedula, COALESCE(e.tipo_personal, '') AS cargo
        FROM usuarios u
        LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
        WHERE u.id=? AND u.empresa_id=? AND u.rol='trabajador' AND COALESCE(u.activo, 1)=1
        LIMIT 1
    ");
    $stmtTrabajador->execute([$trabajador_id, $empresa_id]);
    $trabajador = $stmtTrabajador->fetch(PDO::FETCH_ASSOC);
    if (!$trabajador) {
        estandar7_epp_redirect('El trabajador seleccionado no pertenece a esta empresa.', 'error');
    }

    $catalogo = estandar7_epp_catalogo();
    $cantidades = is_array($_POST['epp_qty'] ?? null) ? $_POST['epp_qty'] : [];
    $items = [];
    foreach ($catalogo as $categoriaSlug => $categoria) {
        foreach ($categoria['items'] as $itemSlug => $itemNombre) {
            $cantidad = max(0, (int)($cantidades[$itemSlug] ?? 0));
            if ($cantidad <= 0) {
                continue;
            }
            $items[] = [
                'categoria_slug' => $categoriaSlug,
                'categoria_nombre' => $categoria['nombre'],
                'item_slug' => $itemSlug,
                'item_nombre' => $itemNombre,
                'cantidad' => $cantidad,
            ];
        }
    }

    $otrosNombres = is_array($_POST['otro_epp_nombre'] ?? null) ? $_POST['otro_epp_nombre'] : [];
    $otrosCantidades = is_array($_POST['otro_epp_cantidad'] ?? null) ? $_POST['otro_epp_cantidad'] : [];
    for ($i = 0; $i < 3; $i++) {
        $nombreOtro = estandar7_clean_text((string)($otrosNombres[$i] ?? ''));
        $cantidadOtro = max(0, (int)($otrosCantidades[$i] ?? 0));
        if ($nombreOtro !== '' && $cantidadOtro > 0) {
            $items[] = [
                'categoria_slug' => 'otros-epp',
                'categoria_nombre' => 'Otros EPP',
                'item_slug' => 'otro_' . ($i + 1),
                'item_nombre' => $nombreOtro,
                'cantidad' => $cantidadOtro,
            ];
        }
    }

    if (!$items) {
        estandar7_epp_redirect('Registra al menos un elemento de proteccion personal con cantidad.', 'error');
    }

    $entregadoTipo = estandar7_clean_text((string)($_POST['entregado_por_tipo'] ?? 'responsable_sst'));
    $entregadoUsuarioId = null;
    $entregadoNombre = '';
    if ($entregadoTipo === 'responsable_sst') {
        $entregadoUsuarioId = $usuario_id;
        $entregadoNombre = (string)($_SESSION['usuario_nombre'] ?? 'Responsable SST');
    } elseif ($entregadoTipo === 'representante_legal') {
        $stmtRep = $conn->prepare("SELECT id, CONCAT(nombre, ' ', apellido) FROM usuarios WHERE empresa_id=? AND rol='representante' AND COALESCE(activo, 1)=1 ORDER BY id ASC LIMIT 1");
        $stmtRep->execute([$empresa_id]);
        $rep = $stmtRep->fetch(PDO::FETCH_NUM);
        if ($rep) {
            $entregadoUsuarioId = (int)$rep[0];
            $entregadoNombre = (string)$rep[1];
        } else {
            $entregadoNombre = 'Representante legal';
        }
    } else {
        $entregadoTipo = 'otro';
        $entregadoNombre = estandar7_clean_text((string)($_POST['entregado_por_otro'] ?? ''));
        if ($entregadoNombre === '') {
            estandar7_epp_redirect('Escribe el nombre de quien entrega.', 'error');
        }
    }

    try {
        $conn->beginTransaction();
        $stmtInsert = $conn->prepare("
            INSERT INTO estandar7_epp_entregas (
                empresa_id, trabajador_id, nombre_trabajador, cedula, cargo, fecha_entrega,
                items_json, tipo_entrega, entregado_por_tipo, entregado_por_usuario_id,
                entregado_por_nombre, observaciones, creado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $nombreTrabajador = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''));
        $stmtInsert->execute([
            $empresa_id,
            $trabajador_id,
            $nombreTrabajador,
            (string)$trabajador['cedula'],
            trim((string)($trabajador['cargo'] ?? '')) ?: 'Sin cargo registrado',
            $fecha_entrega,
            json_encode($items, JSON_UNESCAPED_UNICODE),
            $tipo_entrega,
            $entregadoTipo,
            $entregadoUsuarioId,
            $entregadoNombre,
            estandar7_clean_text((string)($_POST['observaciones'] ?? '')),
            $usuario_id,
        ]);
        $entrega_id = (int)$conn->lastInsertId();

        $titulo = 'Firma de recibido de EPP pendiente';
        $mensaje = 'Tienes una entrega de elementos de proteccion personal pendiente por firmar con fecha ' . date('d/m/Y', strtotime($fecha_entrega)) . '.';
        $enlace = 'estandar7.php?modulo=epp&entrega_id=' . $entrega_id;
        $stmtNotif = $conn->prepare("
            INSERT INTO notificaciones
                (usuario_id, titulo, mensaje, enlace, referencia_tipo, referencia_id, leida)
            VALUES (?, ?, ?, ?, 'estandar7_epp_entrega', ?, 0)
        ");
        $stmtNotif->execute([$trabajador_id, $titulo, $mensaje, $enlace, $entrega_id]);

        $conn->commit();
        estandar7_epp_redirect('Entrega de EPP creada y enviada al trabajador para firma.', 'ok', ['entrega_id' => $entrega_id]);
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        estandar7_epp_redirect('No se pudo guardar la entrega de EPP.', 'error');
    }
}

if ($accion === 'guardar_analisis_consumo') {
    $seguimientos = $_POST['seguimiento'] ?? [];
    $acciones = $_POST['accion_trimestre'] ?? [];

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("
            INSERT INTO estandar7_recursos_analisis_consumo (
                empresa_id, anio, trimestre, seguimiento, accion, creado_por
            ) VALUES (
                :empresa_id, :anio, :trimestre, :seguimiento, :accion, :creado_por
            )
            ON DUPLICATE KEY UPDATE
                seguimiento = VALUES(seguimiento),
                accion = VALUES(accion),
                actualizado_en = CURRENT_TIMESTAMP
        ");

        for ($trimestre = 1; $trimestre <= 4; $trimestre++) {
            $stmt->execute([
                ':empresa_id' => $empresa_id,
                ':anio' => $anio,
                ':trimestre' => $trimestre,
                ':seguimiento' => trim(strip_tags((string)($seguimientos[$trimestre] ?? ''))),
                ':accion' => trim(strip_tags((string)($acciones[$trimestre] ?? ''))),
                ':creado_por' => $usuario_id,
            ]);
        }

        $conn->commit();
        estandar7_redirect('Analisis de consumos actualizado.', 'ok', ['anio' => $anio]);
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        estandar7_redirect('No se pudo guardar el analisis de consumos.', 'error', ['anio' => $anio]);
    }
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
