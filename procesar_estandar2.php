<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar2_schema.php';

$u = require_auth($conn);
ensure_estandar2_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'sst') {
    header('Location: estandar2?error=no_permission');
    exit;
}

$accion = trim((string)($_POST['accion'] ?? $_GET['accion'] ?? ''));
if (!hash_equals((string)($_SESSION['estandar2_csrf'] ?? ''), (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? ''))) {
    header('Location: estandar2?error=sesion');
    exit;
}

$stmtEmpresa = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id=?');
$stmtEmpresa->execute([$usuario_id]);
$empresa_id = (int)$stmtEmpresa->fetchColumn();
if ($empresa_id <= 0) {
    header('Location: estandar2?error=empresa');
    exit;
}

function estandar2_delete_storage_file(PDO $conn, int $companyId, int $fileId): void
{
    if ($fileId <= 0) {
        return;
    }
    $stmt = $conn->prepare('SELECT * FROM almacenamiento_archivos WHERE id=? AND empresa_id=? LIMIT 1');
    $stmt->execute([$fileId, $companyId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) {
        return;
    }
    $root = realpath(storage_company_root($companyId));
    $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file['ruta_relativa']);
    $real = is_file($absolute) ? realpath($absolute) : false;
    if ($root && $real && storage_path_is_within($real, $root)) {
        @unlink($real);
    }
    $conn->prepare('DELETE FROM control_documental_registros WHERE almacenamiento_archivo_id=? AND empresa_id=?')->execute([$fileId, $companyId]);
    $conn->prepare('DELETE FROM almacenamiento_archivos WHERE id=? AND empresa_id=?')->execute([$fileId, $companyId]);
}

if ($accion === 'subir_planilla') {
    $mes = (int)($_POST['mes'] ?? 0);
    $anio = (int)($_POST['anio'] ?? 0);
    if ($mes < 1 || $mes > 12 || $anio < 2020 || $anio > 2050) {
        header('Location: estandar2?error=periodo');
        exit;
    }
    if (!isset($_FILES['archivo']) || (int)$_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        header('Location: estandar2?error=archivo');
        exit;
    }

    $file = $_FILES['archivo'];
    $tmp = (string)$file['tmp_name'];
    $size = (int)$file['size'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    $signature = (string)file_get_contents($tmp, false, null, 0, 5);
    if ($size <= 0 || $size > 15 * 1024 * 1024 || $mime !== 'application/pdf' || $signature !== '%PDF-') {
        header('Location: estandar2?error=formato');
        exit;
    }

    $valor_total = ($_POST['valor_total'] ?? '') !== '' ? (float)$_POST['valor_total'] : null;
    $cedulas_detectadas = ($_POST['cedulas_detectadas'] ?? '') !== '' ? (int)$_POST['cedulas_detectadas'] : null;
    $trabajadores_esperados = ($_POST['trabajadores_esperados'] ?? '') !== '' ? (int)$_POST['trabajadores_esperados'] : null;
    $riesgos_detectados = mb_substr(trim((string)($_POST['riesgos_detectados'] ?? '')), 0, 120, 'UTF-8');
    $nit_coincide = ($_POST['nit_coincide'] ?? 'NO') === 'SI' ? 'SI' : 'NO';
    $trab_found = trim((string)($_POST['trab_found'] ?? '0/0'));
    $trab_faltantes = trim((string)($_POST['trab_faltantes'] ?? ''));
    $detalle_trab = $trab_found . ($trab_faltantes !== '' ? ' (Faltan en PILA: ' . $trab_faltantes . ')' : '');
    $incapacidades = json_decode((string)($_POST['incapacidades_json'] ?? '[]'), true) ?: [];
    $novedades = 'Sin novedades IGE/IRL.';
    if ($incapacidades) {
        $novedades = 'Novedades encontradas:';
        foreach ($incapacidades as $item) {
            $novedades .= ' [' . ($item['tipo'] ?? 'Novedad') . ': ' . ($item['nombre'] ?? 'Trabajador') . ' CC. ' . ($item['cedula'] ?? '') . ']';
        }
    }

    $stmtActual = $conn->prepare('SELECT * FROM estandar2_planillas WHERE empresa_id=? AND anio=? AND mes=? LIMIT 1');
    $stmtActual->execute([$empresa_id, $anio, $mes]);
    $actual = $stmtActual->fetch(PDO::FETCH_ASSOC) ?: null;
    $numeroVersion = $actual ? (int)$actual['version_actual'] + 1 : 1;
    $docConfig = document_control_config($conn, $empresa_id, 2);
    $versionPrefix = strtoupper(trim((string)($docConfig['version_prefijo'] ?? 'V'))) ?: 'V';
    $versionLabel = $versionPrefix . $numeroVersion . '.0';
    $codigo = estandar2_document_code($conn, $empresa_id, $anio, $mes);
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $nombreDocumento = 'Planilla PILA ' . $meses[$mes] . ' ' . $anio;
    $nombreOriginal = $codigo . '_' . $versionLabel . '.pdf';

    $storage = storage_company_context($conn, $empresa_id);
    if (!$storage || ((int)$storage['usado_bytes'] + $size) > (int)$storage['cuota_bytes']) {
        header('Location: estandar2?error=almacenamiento');
        exit;
    }
    storage_prepare_company_folders($empresa_id, (int)$storage['nivel_plan']);
    $folder = storage_folder_path($empresa_id, 2);
    $savedName = date('Ymd-His') . '-' . bin2hex(random_bytes(7)) . '.pdf';
    $destination = $folder . DIRECTORY_SEPARATOR . $savedName;
    if (!move_uploaded_file($tmp, $destination)) {
        header('Location: estandar2?error=guardado');
        exit;
    }

    try {
        $conn->beginTransaction();
        if ($actual && !empty($actual['almacenamiento_archivo_id'])) {
            $conn->prepare("UPDATE almacenamiento_archivos SET estado_documental='obsoleto' WHERE id=? AND empresa_id=?")
                ->execute([(int)$actual['almacenamiento_archivo_id'], $empresa_id]);
            $conn->prepare("UPDATE control_documental_registros SET estado='obsoleto' WHERE almacenamiento_archivo_id=? AND empresa_id=?")
                ->execute([(int)$actual['almacenamiento_archivo_id'], $empresa_id]);
        }

        $relative = str_replace('\\', '/', substr($destination, strlen(dirname(__DIR__)) + 1));
        $stmtFile = $conn->prepare(<<<'SQL'
            INSERT INTO almacenamiento_archivos
                (empresa_id,estandar_numero,estandar_nombre,nombre_original,nombre_guardado,ruta_relativa,
                 tipo_mime,extension,tamano_bytes,usuario_id,codigo_documento,version_documento,
                 fecha_documento,estado_documental,origen_modulo)
            VALUES (?,2,?,?,?,?,?,'pdf',?,?,?,?,CURDATE(),'validado','estandar2_pila')
        SQL);
        $stmtFile->execute([$empresa_id,storage_standard_catalog()[2],$nombreOriginal,$savedName,$relative,'application/pdf',$size,$usuario_id,$codigo,$versionLabel]);
        $storageId = (int)$conn->lastInsertId();

        $stmtControl = $conn->prepare(<<<'SQL'
            INSERT INTO control_documental_registros
                (empresa_id,estandar_numero,almacenamiento_archivo_id,tipo_documento,nombre_documento,
                 codigo_documento,version_documento,fecha_documento,estado,archivo_original,
                 resultado_validacion,usuario_id)
            VALUES (?,2,?,'soporte',?,?,?,CURDATE(),'validado',?,?,?)
        SQL);
        $stmtControl->execute([$empresa_id,$storageId,$nombreDocumento,$codigo,$versionLabel,basename((string)$file['name']),json_encode(['nit'=>$nit_coincide,'trabajadores'=>$detalle_trab], JSON_UNESCAPED_UNICODE),$usuario_id]);
        $controlId = (int)$conn->lastInsertId();
        $conn->prepare('UPDATE almacenamiento_archivos SET control_registro_id=? WHERE id=?')->execute([$controlId,$storageId]);

        if ($actual) {
            $planillaId = (int)$actual['id'];
            $stmtPlanilla = $conn->prepare('UPDATE estandar2_planillas SET archivo_url=?,almacenamiento_archivo_id=?,version_actual=?,valor_total=?,cedulas_detectadas=?,trabajadores_esperados=?,riesgos_detectados=?,nit_coincide=?,novedades_resumen=?,subido_por=?,fecha_subida=NOW() WHERE id=? AND empresa_id=?');
            $stmtPlanilla->execute([$relative,$storageId,$numeroVersion,$valor_total,$cedulas_detectadas,$trabajadores_esperados,$riesgos_detectados,$nit_coincide,$novedades,$usuario_id,$planillaId,$empresa_id]);
        } else {
            $stmtPlanilla = $conn->prepare('INSERT INTO estandar2_planillas (empresa_id,mes,anio,archivo_url,almacenamiento_archivo_id,version_actual,valor_total,cedulas_detectadas,trabajadores_esperados,riesgos_detectados,nit_coincide,novedades_resumen,subido_por,fecha_subida) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
            $stmtPlanilla->execute([$empresa_id,$mes,$anio,$relative,$storageId,$numeroVersion,$valor_total,$cedulas_detectadas,$trabajadores_esperados,$riesgos_detectados,$nit_coincide,$novedades,$usuario_id]);
            $planillaId = (int)$conn->lastInsertId();
        }

        $stmtVersion = $conn->prepare('INSERT INTO estandar2_planilla_versiones (planilla_id,empresa_id,almacenamiento_archivo_id,numero_version,archivo_original,valor_total,cedulas_detectadas,trabajadores_esperados,riesgos_detectados,nit_coincide,novedades_resumen,subido_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmtVersion->execute([$planillaId,$empresa_id,$storageId,$numeroVersion,basename((string)$file['name']),$valor_total,$cedulas_detectadas,$trabajadores_esperados,$riesgos_detectados,$nit_coincide,$novedades,$usuario_id]);
        $conn->commit();

        $descripcion = "Análisis PILA ($mes/$anio). NIT Coincide: $nit_coincide. Trabajadores detectados: $detalle_trab. $novedades";
        $conn->prepare("INSERT INTO logs_actividad (usuario_id,accion,descripcion,ip_address,fecha) VALUES (?,'ANALISIS_PILA',?,?,NOW())")
            ->execute([$usuario_id,$descripcion,$_SERVER['REMOTE_ADDR'] ?? '']);
        header('Location: estandar2?msg=subido&anio=' . $anio);
        exit;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        if (is_file($destination)) {
            @unlink($destination);
        }
        error_log('Carga PILA: ' . $error->getMessage());
        header('Location: estandar2?error=registro');
        exit;
    }
}

if ($accion === 'eliminar_planilla') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare('SELECT * FROM estandar2_planillas WHERE id=? AND empresa_id=? LIMIT 1');
    $stmt->execute([$id,$empresa_id]);
    $planilla = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$planilla) {
        header('Location: estandar2?error=no_autorizado');
        exit;
    }

    $stmtVersions = $conn->prepare('SELECT almacenamiento_archivo_id FROM estandar2_planilla_versiones WHERE planilla_id=? AND empresa_id=?');
    $stmtVersions->execute([$id,$empresa_id]);
    $fileIds = array_unique(array_filter(array_map('intval', $stmtVersions->fetchAll(PDO::FETCH_COLUMN))));
    if (!empty($planilla['almacenamiento_archivo_id'])) {
        $fileIds[] = (int)$planilla['almacenamiento_archivo_id'];
    }
    foreach (array_unique($fileIds) as $fileId) {
        estandar2_delete_storage_file($conn,$empresa_id,$fileId);
    }
    $conn->prepare('DELETE FROM estandar2_planilla_versiones WHERE planilla_id=? AND empresa_id=?')->execute([$id,$empresa_id]);
    $conn->prepare('DELETE FROM estandar2_planillas WHERE id=? AND empresa_id=?')->execute([$id,$empresa_id]);
    $conn->prepare("INSERT INTO logs_actividad (usuario_id,accion,descripcion,ip_address,fecha) VALUES (?,'ELIMINAR_PILA',?,?,NOW())")
        ->execute([$usuario_id,"Eliminó planilla del periodo {$planilla['mes']}/{$planilla['anio']}.",$_SERVER['REMOTE_ADDR'] ?? '']);
    header('Location: estandar2?msg=eliminado&anio=' . (int)$planilla['anio']);
    exit;
}

header('Location: estandar2');
exit;
