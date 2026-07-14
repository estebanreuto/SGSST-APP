<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar6_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar6_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
$accion = $_POST['accion'] ?? '';

$stmtEmpresa = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmtEmpresa->execute([$usuario_id]);
$empresa_id = (int)$stmtEmpresa->fetchColumn();

function estandar6_redirect(string $msg, string $tipo = 'ok'): never
{
    header('Location: estandar6.php?msg=' . urlencode($msg) . '&tipo=' . urlencode($tipo));
    exit;
}

function estandar6_clean_text(string $value): string
{
    return trim(strip_tags($value));
}

function estandar6_int_or_zero($value): int
{
    return max(0, (int)$value);
}

if ($accion !== 'guardar_ipvr') {
    estandar6_redirect('Accion no reconocida.', 'error');
}

if ($rol !== 'sst') {
    estandar6_redirect('Solo el responsable SST puede registrar o modificar la matriz IPVR.', 'error');
}

if ($empresa_id <= 0) {
    estandar6_redirect('No se encontro una empresa asociada al usuario actual.', 'error');
}

try {
    $required = [
        'proceso' => 'proceso',
        'actividad' => 'actividad',
        'peligro' => 'peligro',
        'clasificacion_peligro' => 'clasificacion del peligro',
        'categoria' => 'categoria',
        'nivel_danio' => 'nivel de dano',
    ];
    foreach ($required as $field => $label) {
        if (trim((string)($_POST[$field] ?? '')) === '') {
            estandar6_redirect('Completa el campo ' . $label . '.', 'error');
        }
    }

    $numero = (int)($_POST['numero'] ?? 0);
    if ($numero <= 0) {
        $stmtNumero = $conn->prepare("SELECT COALESCE(MAX(numero), 0) + 1 FROM estandar6_ipvr_registros WHERE empresa_id = ?");
        $stmtNumero->execute([$empresa_id]);
        $numero = (int)$stmtNumero->fetchColumn();
    }

    $data = [
        'directos' => estandar6_int_or_zero($_POST['directos'] ?? 0),
        'contratistas' => estandar6_int_or_zero($_POST['contratistas'] ?? 0),
        'visitantes' => estandar6_int_or_zero($_POST['visitantes'] ?? 0),
        'clasificacion_peligro' => estandar6_clean_text((string)($_POST['clasificacion_peligro'] ?? '')),
        'categoria' => estandar6_clean_text((string)($_POST['categoria'] ?? 'Seguridad')),
        'nivel_danio' => estandar6_clean_text((string)($_POST['nivel_danio'] ?? 'Leve')),
        'nivel_deficiencia' => (int)($_POST['nivel_deficiencia'] ?? 2),
        'nivel_exposicion' => (int)($_POST['nivel_exposicion'] ?? 1),
        'nivel_deficiencia_residual' => (int)($_POST['nivel_deficiencia_residual'] ?? 2),
        'nivel_exposicion_residual' => (int)($_POST['nivel_exposicion_residual'] ?? 1),
        'nivel_consecuencia_residual' => (int)($_POST['nivel_consecuencia_residual'] ?? 10),
        'accidentes_anterior' => $_POST['accidentes_anterior'] ?? '',
    ];
    $calc = estandar6_calcular($data);

    $sql = "
        INSERT INTO estandar6_ipvr_registros (
            empresa_id, numero, sitio_trabajo, cuadro_basico, proceso, actividad, tarea, zona_lugar,
            clase_actividad, origen_actividad, cargos, directos, contratistas, visitantes, total_expuestos,
            peligro, clasificacion_peligro, metodologia_ref, descripcion_peligro, categoria, nivel_danio,
            nivel_deficiencia, valoracion_nd, valoracion_nd_descripcion, nivel_exposicion, nivel_probabilidad,
            interpretacion_probabilidad, nivel_consecuencia, nivel_riesgo, significado_riesgo, aceptabilidad,
            peor_consecuencia, requisito_legal, control_fuente, control_medio, control_persona, instrumento,
            nivel_deficiencia_residual, nivel_exposicion_residual, nivel_probabilidad_residual,
            interpretacion_probabilidad_residual, nivel_consecuencia_residual, nivel_riesgo_residual,
            significado_riesgo_residual, aceptabilidad_residual, eliminacion, sustitucion, controles_ingenieria,
            senalizacion_advertencia, administrativos, epp, factor_reduccion, accidentes_anterior,
            accidentes_actual, eficacia_controles, observaciones, creado_por
        ) VALUES (
            :empresa_id, :numero, :sitio_trabajo, :cuadro_basico, :proceso, :actividad, :tarea, :zona_lugar,
            :clase_actividad, :origen_actividad, :cargos, :directos, :contratistas, :visitantes, :total_expuestos,
            :peligro, :clasificacion_peligro, :metodologia_ref, :descripcion_peligro, :categoria, :nivel_danio,
            :nivel_deficiencia, :valoracion_nd, :valoracion_nd_descripcion, :nivel_exposicion, :nivel_probabilidad,
            :interpretacion_probabilidad, :nivel_consecuencia, :nivel_riesgo, :significado_riesgo, :aceptabilidad,
            :peor_consecuencia, :requisito_legal, :control_fuente, :control_medio, :control_persona, :instrumento,
            :nivel_deficiencia_residual, :nivel_exposicion_residual, :nivel_probabilidad_residual,
            :interpretacion_probabilidad_residual, :nivel_consecuencia_residual, :nivel_riesgo_residual,
            :significado_riesgo_residual, :aceptabilidad_residual, :eliminacion, :sustitucion, :controles_ingenieria,
            :senalizacion_advertencia, :administrativos, :epp, :factor_reduccion, :accidentes_anterior,
            :accidentes_actual, :eficacia_controles, :observaciones, :creado_por
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':empresa_id' => $empresa_id,
        ':numero' => $numero,
        ':sitio_trabajo' => estandar6_clean_text((string)($_POST['sitio_trabajo'] ?? '')),
        ':cuadro_basico' => estandar6_clean_text((string)($_POST['cuadro_basico'] ?? '')),
        ':proceso' => estandar6_clean_text((string)($_POST['proceso'] ?? '')),
        ':actividad' => estandar6_clean_text((string)($_POST['actividad'] ?? '')),
        ':tarea' => estandar6_clean_text((string)($_POST['tarea'] ?? '')),
        ':zona_lugar' => estandar6_clean_text((string)($_POST['zona_lugar'] ?? '')),
        ':clase_actividad' => in_array($_POST['clase_actividad'] ?? '', ['Rutinaria', 'No Rutinaria'], true) ? $_POST['clase_actividad'] : 'Rutinaria',
        ':origen_actividad' => in_array($_POST['origen_actividad'] ?? '', ['Interna', 'Externa'], true) ? $_POST['origen_actividad'] : 'Interna',
        ':cargos' => estandar6_clean_text((string)($_POST['cargos'] ?? '')),
        ':directos' => $data['directos'],
        ':contratistas' => $data['contratistas'],
        ':visitantes' => $data['visitantes'],
        ':total_expuestos' => $calc['total_expuestos'],
        ':peligro' => estandar6_clean_text((string)($_POST['peligro'] ?? '')),
        ':clasificacion_peligro' => $data['clasificacion_peligro'],
        ':metodologia_ref' => $calc['metodologia_ref'],
        ':descripcion_peligro' => estandar6_clean_text((string)($_POST['descripcion_peligro'] ?? '')),
        ':categoria' => in_array($data['categoria'], ['Salud', 'Seguridad', 'Propiedad_Proceso'], true) ? $data['categoria'] : 'Seguridad',
        ':nivel_danio' => $data['nivel_danio'],
        ':nivel_deficiencia' => $data['nivel_deficiencia'],
        ':valoracion_nd' => $calc['valoracion_nd'],
        ':valoracion_nd_descripcion' => $calc['valoracion_nd_descripcion'],
        ':nivel_exposicion' => $data['nivel_exposicion'],
        ':nivel_probabilidad' => $calc['nivel_probabilidad'],
        ':interpretacion_probabilidad' => $calc['interpretacion_probabilidad'],
        ':nivel_consecuencia' => $calc['nivel_consecuencia'],
        ':nivel_riesgo' => $calc['nivel_riesgo'],
        ':significado_riesgo' => $calc['significado_riesgo'],
        ':aceptabilidad' => $calc['aceptabilidad'],
        ':peor_consecuencia' => $calc['peor_consecuencia'],
        ':requisito_legal' => in_array($_POST['requisito_legal'] ?? '', ['SI', 'NO'], true) ? $_POST['requisito_legal'] : 'NO',
        ':control_fuente' => estandar6_clean_text((string)($_POST['control_fuente'] ?? '')),
        ':control_medio' => estandar6_clean_text((string)($_POST['control_medio'] ?? '')),
        ':control_persona' => estandar6_clean_text((string)($_POST['control_persona'] ?? '')),
        ':instrumento' => estandar6_clean_text((string)($_POST['instrumento'] ?? '')),
        ':nivel_deficiencia_residual' => $data['nivel_deficiencia_residual'],
        ':nivel_exposicion_residual' => $calc['nivel_exposicion_residual'],
        ':nivel_probabilidad_residual' => $calc['nivel_probabilidad_residual'],
        ':interpretacion_probabilidad_residual' => $calc['interpretacion_probabilidad_residual'],
        ':nivel_consecuencia_residual' => $calc['nivel_consecuencia_residual'],
        ':nivel_riesgo_residual' => $calc['nivel_riesgo_residual'],
        ':significado_riesgo_residual' => $calc['significado_riesgo_residual'],
        ':aceptabilidad_residual' => $calc['aceptabilidad_residual'],
        ':eliminacion' => estandar6_clean_text((string)($_POST['eliminacion'] ?? '')),
        ':sustitucion' => estandar6_clean_text((string)($_POST['sustitucion'] ?? '')),
        ':controles_ingenieria' => estandar6_clean_text((string)($_POST['controles_ingenieria'] ?? '')),
        ':senalizacion_advertencia' => estandar6_clean_text((string)($_POST['senalizacion_advertencia'] ?? '')),
        ':administrativos' => estandar6_clean_text((string)($_POST['administrativos'] ?? '')),
        ':epp' => estandar6_clean_text((string)($_POST['epp'] ?? '')),
        ':factor_reduccion' => $calc['factor_reduccion'],
        ':accidentes_anterior' => ($_POST['accidentes_anterior'] ?? '') === '' ? null : estandar6_int_or_zero($_POST['accidentes_anterior']),
        ':accidentes_actual' => ($_POST['accidentes_actual'] ?? '') === '' ? null : estandar6_int_or_zero($_POST['accidentes_actual']),
        ':eficacia_controles' => $calc['eficacia_controles'],
        ':observaciones' => estandar6_clean_text((string)($_POST['observaciones'] ?? '')),
        ':creado_por' => $usuario_id,
    ]);

    estandar6_redirect('Registro IPVR guardado correctamente.');
} catch (Throwable $e) {
    estandar6_redirect('No se pudo guardar el registro IPVR: ' . $e->getMessage(), 'error');
}
