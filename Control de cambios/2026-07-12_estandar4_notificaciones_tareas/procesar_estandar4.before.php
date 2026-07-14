<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar4_schema.php';

$u = require_auth($conn);
ensure_estandar4_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
$accion = $_POST['accion'] ?? '';
$anio = max(2020, min(2100, (int)($_POST['anio'] ?? date('Y'))));

$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();

if (!$empresa_id || !in_array($rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$plan = estandar4_get_or_create_plan($conn, $empresa_id, $anio);
$plan_id = (int)$plan['id'];

function estandar4_redirect(int $anio, string $msg, string $tipo = 'ok'): never
{
    header('Location: estandar4.php?anio=' . $anio . '&msg=' . urlencode($msg) . '&tipo=' . $tipo);
    exit;
}

function estandar4_require_sst(string $rol, int $anio): void
{
    if ($rol !== 'sst') {
        estandar4_redirect($anio, 'No tienes permiso para modificar el plan.', 'error');
    }
}

function estandar4_validar_firma(string $firma): bool
{
    return strlen($firma) <= 1500000
        && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $firma) === 1;
}

function estandar4_invalidar_firmas(PDO $conn, int $plan_id): void
{
    $stmt = $conn->prepare("
        UPDATE estandar4_planes
        SET estado='borrador', firma_sst=NULL, firma_representante=NULL,
            sst_id=NULL, representante_id=NULL, fecha_envio=NULL, fecha_firma=NULL
        WHERE id=?
    ");
    $stmt->execute([$plan_id]);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        estandar4_redirect($anio, 'Solicitud no válida.', 'error');
    }

    if ($accion === 'guardar_meta') {
        estandar4_require_sst($rol, $anio);
        $meta = max(1, min(100, (int)($_POST['meta_cumplimiento'] ?? 85)));
        $stmt = $conn->prepare("
            UPDATE estandar4_planes
            SET meta_cumplimiento=?, estado='borrador', firma_sst=NULL, firma_representante=NULL,
                sst_id=NULL, representante_id=NULL, fecha_envio=NULL, fecha_firma=NULL
            WHERE id=? AND empresa_id=?
        ");
        $stmt->execute([$meta, $plan_id, $empresa_id]);
        estandar4_redirect($anio, 'Meta anual actualizada.');
    }

    if ($accion === 'guardar_actividad') {
        estandar4_require_sst($rol, $anio);
        $actividad_id = (int)($_POST['actividad_id'] ?? 0);
        $tema = trim($_POST['tema'] ?? '');
        $actividad = trim($_POST['actividad'] ?? '');
        $responsable = trim($_POST['responsable'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $meses = array_map('intval', $_POST['meses'] ?? []);
        $programacion = [];
        $programacion_actual = [];
        if ($actividad_id > 0) {
            $stmt = $conn->prepare("SELECT programacion_json FROM estandar4_actividades WHERE id=? AND plan_id=?");
            $stmt->execute([$actividad_id, $plan_id]);
            $programacion_actual = estandar4_decode_programacion($stmt->fetchColumn() ?: '');
        }

        foreach (array_unique($meses) as $mes) {
            if ($mes >= 1 && $mes <= 12) {
                $programacion[(string)$mes] = $programacion_actual[(string)$mes]
                    ?? ['estado' => 'P', 'fecha' => null];
            }
        }

        if ($tema === '' || $actividad === '' || $responsable === '') {
            throw new RuntimeException('Completa tema, actividad y responsable.');
        }
        if (!$programacion) {
            throw new RuntimeException('Selecciona al menos un mes para la actividad.');
        }

        if ($actividad_id > 0) {
            $stmt = $conn->prepare("
                UPDATE estandar4_actividades
                SET tema=?, actividad=?, responsable=?, programacion_json=?, observaciones=?
                WHERE id=? AND plan_id=?
            ");
            $stmt->execute([
                $tema, $actividad, $responsable,
                json_encode($programacion, JSON_UNESCAPED_UNICODE),
                $observaciones, $actividad_id, $plan_id
            ]);
        } else {
            $orden = (int)$conn->query("SELECT COALESCE(MAX(orden), 0) + 1 FROM estandar4_actividades WHERE plan_id = " . $plan_id)->fetchColumn();
            $stmt = $conn->prepare("
                INSERT INTO estandar4_actividades
                    (plan_id, tema, actividad, responsable, programacion_json, observaciones, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $plan_id, $tema, $actividad, $responsable,
                json_encode($programacion, JSON_UNESCAPED_UNICODE),
                $observaciones, $orden
            ]);
        }

        estandar4_invalidar_firmas($conn, $plan_id);
        estandar4_redirect($anio, $actividad_id ? 'Actividad actualizada.' : 'Actividad agregada al plan.');
    }

    if ($accion === 'eliminar_actividad') {
        estandar4_require_sst($rol, $anio);
        $stmt = $conn->prepare("DELETE FROM estandar4_actividades WHERE id=? AND plan_id=?");
        $stmt->execute([(int)($_POST['actividad_id'] ?? 0), $plan_id]);
        estandar4_invalidar_firmas($conn, $plan_id);
        estandar4_redirect($anio, 'Actividad eliminada.');
    }

    if ($accion === 'actualizar_mes') {
        estandar4_require_sst($rol, $anio);
        $actividad_id = (int)($_POST['actividad_id'] ?? 0);
        $mes = (int)($_POST['mes'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($mes < 1 || $mes > 12 || !in_array($estado, ['', 'P', 'E', 'R'], true)) {
            throw new RuntimeException('Estado mensual no válido.');
        }

        $stmt = $conn->prepare("SELECT programacion_json FROM estandar4_actividades WHERE id=? AND plan_id=?");
        $stmt->execute([$actividad_id, $plan_id]);
        $json = $stmt->fetchColumn();
        if ($json === false) {
            throw new RuntimeException('No se encontró la actividad.');
        }
        $programacion = estandar4_decode_programacion($json);
        if ($estado === '') {
            unset($programacion[(string)$mes]);
        } else {
            $programacion[(string)$mes] = [
                'estado' => $estado,
                'fecha' => date('Y-m-d'),
            ];
        }
        $stmt = $conn->prepare("UPDATE estandar4_actividades SET programacion_json=? WHERE id=? AND plan_id=?");
        $stmt->execute([json_encode($programacion, JSON_UNESCAPED_UNICODE), $actividad_id, $plan_id]);
        estandar4_invalidar_firmas($conn, $plan_id);
        estandar4_redirect($anio, 'Programación mensual actualizada.');
    }

    if ($accion === 'importar_capacitaciones') {
        estandar4_require_sst($rol, $anio);
        $importadas = estandar4_importar_capacitaciones($conn, $plan_id, $empresa_id, $anio);
        if ($importadas) {
            estandar4_invalidar_firmas($conn, $plan_id);
        }
        estandar4_redirect($anio, $importadas
            ? $importadas . ' capacitación(es) sincronizada(s) con el Estándar 3.'
            : 'El plan ya está actualizado con el Estándar 3.');
    }

    if ($accion === 'guardar_seguimiento') {
        estandar4_require_sst($rol, $anio);
        $seguimiento_id = (int)($_POST['seguimiento_id'] ?? 0);
        $campos = [
            trim($_POST['periodo'] ?? ''),
            trim($_POST['analisis_resultado'] ?? ''),
            trim($_POST['accion_propuesta'] ?? ''),
            trim($_POST['responsable'] ?? ''),
            ($_POST['fecha_max_ejecucion'] ?? '') ?: null,
            ($_POST['fecha_seguimiento'] ?? '') ?: null,
            trim($_POST['responsable_seguimiento'] ?? ''),
            trim($_POST['resultado_seguimiento'] ?? ''),
        ];
        if ($campos[0] === '' || $campos[1] === '' || $campos[2] === '' || $campos[3] === '') {
            throw new RuntimeException('Completa los campos principales del seguimiento.');
        }

        if ($seguimiento_id > 0) {
            $stmt = $conn->prepare("
                UPDATE estandar4_seguimientos
                SET periodo=?, analisis_resultado=?, accion_propuesta=?, responsable=?,
                    fecha_max_ejecucion=?, fecha_seguimiento=?, responsable_seguimiento=?, resultado_seguimiento=?
                WHERE id=? AND plan_id=?
            ");
            $stmt->execute([...$campos, $seguimiento_id, $plan_id]);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO estandar4_seguimientos
                    (plan_id, periodo, analisis_resultado, accion_propuesta, responsable,
                     fecha_max_ejecucion, fecha_seguimiento, responsable_seguimiento, resultado_seguimiento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$plan_id, ...$campos]);
        }
        estandar4_invalidar_firmas($conn, $plan_id);
        estandar4_redirect($anio, 'Seguimiento guardado.');
    }

    if ($accion === 'eliminar_seguimiento') {
        estandar4_require_sst($rol, $anio);
        $stmt = $conn->prepare("DELETE FROM estandar4_seguimientos WHERE id=? AND plan_id=?");
        $stmt->execute([(int)($_POST['seguimiento_id'] ?? 0), $plan_id]);
        estandar4_invalidar_firmas($conn, $plan_id);
        estandar4_redirect($anio, 'Seguimiento eliminado.');
    }

    if ($accion === 'firmar_sst') {
        estandar4_require_sst($rol, $anio);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar4_actividades WHERE plan_id=?");
        $stmt->execute([$plan_id]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('Agrega al menos una actividad antes de firmar el plan.');
        }
        $firma = $_POST['firma'] ?? '';
        if (!estandar4_validar_firma($firma)) {
            throw new RuntimeException('Dibuja una firma válida antes de continuar.');
        }
        $stmt = $conn->prepare("
            UPDATE estandar4_planes
            SET firma_sst=?, sst_id=?, estado='pendiente_firma', fecha_envio=NOW()
            WHERE id=? AND empresa_id=?
        ");
        $stmt->execute([$firma, $usuario_id, $plan_id, $empresa_id]);
        estandar4_redirect($anio, 'Plan firmado y enviado al Representante Legal.');
    }

    if ($accion === 'firmar_representante') {
        if ($rol !== 'representante' || $plan['estado'] !== 'pendiente_firma') {
            throw new RuntimeException('El plan no está disponible para firma.');
        }
        $firma = $_POST['firma'] ?? '';
        if (!estandar4_validar_firma($firma)) {
            throw new RuntimeException('Dibuja una firma válida antes de continuar.');
        }
        $stmt = $conn->prepare("
            UPDATE estandar4_planes
            SET firma_representante=?, representante_id=?, estado='firmado', fecha_firma=NOW()
            WHERE id=? AND empresa_id=? AND estado='pendiente_firma'
        ");
        $stmt->execute([$firma, $usuario_id, $plan_id, $empresa_id]);
        estandar4_redirect($anio, 'Plan anual aprobado y firmado.');
    }

    if ($accion === 'reabrir_plan') {
        estandar4_require_sst($rol, $anio);
        $stmt = $conn->prepare("
            UPDATE estandar4_planes
            SET estado='borrador', firma_sst=NULL, firma_representante=NULL,
                sst_id=NULL, representante_id=NULL, fecha_envio=NULL, fecha_firma=NULL
            WHERE id=? AND empresa_id=?
        ");
        $stmt->execute([$plan_id, $empresa_id]);
        estandar4_redirect($anio, 'El plan volvió a borrador para ser actualizado.');
    }

    throw new RuntimeException('Acción no reconocida.');
} catch (Throwable $e) {
    estandar4_redirect($anio, $e->getMessage(), 'error');
}
