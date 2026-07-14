<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar7_schema.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

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

if (!in_array($accion, ['guardar_recursos', 'guardar_analisis_consumo', 'guardar_epp_entrega', 'enviar_codigo_epp', 'firmar_epp_entrega', 'guardar_programa_documental', 'guardar_mantenimiento_equipo', 'guardar_mantenimiento_registro'], true)) {
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

function estandar7_programa_redirect(string $msg, string $tipo = 'ok', array $extra = []): never
{
    estandar7_redirect($msg, $tipo, array_merge(['modulo' => 'procedimientos', 'categoria' => 'programas'], $extra));
}

function estandar7_mantenimiento_redirect(string $msg, string $tipo = 'ok', array $extra = []): never
{
    estandar7_redirect($msg, $tipo, array_merge(['modulo' => 'mantenimiento', 'categoria' => 'registro-equipos'], $extra));
}

function estandar7_mantenimiento_seguimiento_redirect(string $msg, string $tipo = 'ok', array $extra = []): never
{
    estandar7_redirect($msg, $tipo, array_merge(['modulo' => 'mantenimiento', 'categoria' => 'seguimiento-mantenimiento'], $extra));
}

function estandar7_clean_text(string $value): string
{
    return trim(strip_tags($value));
}

function estandar7_clean_money_value($value): float
{
    $value = str_replace(['$', '.', ' '], '', (string)$value);
    $value = str_replace(',', '.', $value);
    return max(0, (float)$value);
}

function estandar7_upload_file(string $field, int $empresa_id, string $folder, bool $required = false): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Carga el archivo solicitado.');
        }
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar el archivo seleccionado.');
    }

    $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
    $extension = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Solo se permiten archivos PDF o imagen.');
    }
    if ((int)$_FILES[$field]['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('El archivo debe pesar maximo 8 MB.');
    }

    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
    $baseDir = __DIR__ . '/uploads/estandar7/empresa-' . $empresa_id . '/' . $folder;
    if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de archivos.');
    }

    $filename = $field . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $baseDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar el archivo cargado.');
    }

    return 'uploads/estandar7/empresa-' . $empresa_id . '/' . $folder . '/' . $filename;
}

function estandar7_send_epp_code_email(string $toEmail, string $toName, string $code): array
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no esta instalado.'];
    }

    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int)(getenv('SMTP_PORT') ?: 587);
    $secure = getenv('SMTP_SECURE') ?: 'tls';
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'SG-SST';
    if ($user === '' || $pass === '') {
        return [false, 'Faltan SMTP_USER o SMTP_PASS en el .env'];
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        $mail->SMTPSecure = ($secure === 'ssl')
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($user, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Codigo para firma de recibido EPP - SG-SST';
        $mail->Body = '<div style="font-family:Arial,sans-serif;color:#1f2937">
            <h2 style="color:#1e3a8a">Codigo de validacion para firma</h2>
            <p>Hola <b>' . htmlspecialchars($toName) . '</b>,</p>
            <p>Para firmar el recibido de tus elementos de proteccion personal, ingresa este codigo en la plataforma:</p>
            <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#ff7a00;background:#fff7ed;border:2px dashed #ff8a1f;border-radius:12px;padding:18px;text-align:center;max-width:280px">' . htmlspecialchars($code) . '</div>
            <p>Este codigo expira en 10 minutos. Si no solicitaste esta firma, informa al Responsable SST.</p>
        </div>';
        $mail->AltBody = 'Codigo para firma de recibido EPP: ' . $code . ' (expira en 10 minutos).';
        $mail->send();
        return [true, 'OK'];
    } catch (Throwable $e) {
        return [false, $e->getMessage()];
    }
}

if ($accion === 'enviar_codigo_epp') {
    if ($rol !== 'trabajador') {
        estandar7_epp_redirect('Solo el trabajador asignado puede solicitar el codigo de firma.', 'error');
    }

    $entrega_id = (int)($_POST['entrega_id'] ?? 0);
    $stmtEntrega = $conn->prepare("
        SELECT e.id, e.trabajador_id, e.estado, u.email, u.correo_seguridad, u.nombre, u.apellido
        FROM estandar7_epp_entregas e
        INNER JOIN usuarios u ON u.id = e.trabajador_id
        WHERE e.id=? AND e.empresa_id=? AND e.trabajador_id=? AND e.estado='pendiente_firma'
        LIMIT 1
    ");
    $stmtEntrega->execute([$entrega_id, $empresa_id, $usuario_id]);
    $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);
    if (!$entrega) {
        estandar7_epp_redirect('No se encontro una entrega pendiente para validar.', 'error', ['entrega_id' => $entrega_id]);
    }

    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $expira = date('Y-m-d H:i:s', time() + 600);
    $stmtCode = $conn->prepare("
        UPDATE estandar7_epp_entregas
        SET firma_codigo_hash=?, firma_codigo_expira=?, firma_codigo_validado_at=NULL
        WHERE id=? AND empresa_id=? AND trabajador_id=?
    ");
    $stmtCode->execute([$hash, $expira, $entrega_id, $empresa_id, $usuario_id]);

    $correo = trim((string)($entrega['correo_seguridad'] ?? '')) ?: (string)$entrega['email'];
    $nombre = trim(($entrega['nombre'] ?? '') . ' ' . ($entrega['apellido'] ?? '')) ?: 'Trabajador';
    [$ok, $msg] = estandar7_send_epp_code_email($correo, $nombre, $code);
    if (!$ok) {
        estandar7_epp_redirect('No se pudo enviar el codigo al correo. Detalle: ' . $msg, 'error', ['entrega_id' => $entrega_id]);
    }

    estandar7_epp_redirect('Codigo enviado al correo registrado. Revisa tu bandeja e ingresalo para firmar.', 'ok', ['entrega_id' => $entrega_id]);
}

if ($accion === 'firmar_epp_entrega') {
    if ($rol !== 'trabajador') {
        estandar7_epp_redirect('Solo el trabajador asignado puede firmar el recibido.', 'error');
    }

    $entrega_id = (int)($_POST['entrega_id'] ?? 0);
    $codigo_firma = trim((string)($_POST['codigo_firma'] ?? ''));
    $firma = (string)($_POST['firma_trabajador'] ?? '');
    if ($entrega_id <= 0 || !estandar7_validar_firma($firma)) {
        estandar7_epp_redirect('La firma no es valida. Intenta firmar de nuevo.', 'error', ['entrega_id' => $entrega_id]);
    }

    $stmtCodigo = $conn->prepare("
        SELECT firma_codigo_hash, firma_codigo_expira
        FROM estandar7_epp_entregas
        WHERE id=? AND empresa_id=? AND trabajador_id=? AND estado='pendiente_firma'
        LIMIT 1
    ");
    $stmtCodigo->execute([$entrega_id, $empresa_id, $usuario_id]);
    $codigoRow = $stmtCodigo->fetch(PDO::FETCH_ASSOC);
    if (!$codigoRow || empty($codigoRow['firma_codigo_hash']) || empty($codigoRow['firma_codigo_expira'])) {
        estandar7_epp_redirect('Solicita primero el codigo enviado al correo antes de firmar.', 'error', ['entrega_id' => $entrega_id]);
    }
    if (strtotime((string)$codigoRow['firma_codigo_expira']) < time()) {
        estandar7_epp_redirect('El codigo expiro. Solicita uno nuevo.', 'error', ['entrega_id' => $entrega_id]);
    }
    if ($codigo_firma === '' || !password_verify($codigo_firma, (string)$codigoRow['firma_codigo_hash'])) {
        estandar7_epp_redirect('El codigo ingresado no es valido.', 'error', ['entrega_id' => $entrega_id]);
    }

    $stmt = $conn->prepare("
        UPDATE estandar7_epp_entregas
        SET firma_trabajador=?, fecha_firma=NOW(), estado='firmado', firma_codigo_validado_at=NOW()
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

if (in_array($accion, ['guardar_recursos', 'guardar_analisis_consumo', 'guardar_epp_entrega', 'guardar_programa_documental', 'guardar_mantenimiento_equipo', 'guardar_mantenimiento_registro'], true) && $rol !== 'sst') {
    estandar7_redirect('Solo el responsable SST puede registrar o modificar esta informacion.', 'error');
}

if ($accion === 'guardar_mantenimiento_equipo') {
    $tipoElemento = estandar7_clean_text((string)($_POST['tipo_elemento'] ?? 'Equipo'));
    if (!in_array($tipoElemento, ['Maquina', 'Equipo', 'Herramienta'], true)) {
        $tipoElemento = 'Equipo';
    }

    $nombreElemento = estandar7_clean_text((string)($_POST['nombre_elemento'] ?? ''));
    if ($nombreElemento === '') {
        estandar7_mantenimiento_redirect('Escribe el nombre de la maquina, equipo o herramienta.', 'error');
    }

    $fabricante = estandar7_clean_text((string)($_POST['fabricante'] ?? ''));
    if ($fabricante === 'Otro') {
        $fabricante = estandar7_clean_text((string)($_POST['fabricante_otro'] ?? ''));
    }

    $catalogoEnergia = estandar7_tipos_energia_mantenimiento();
    $energiasPost = is_array($_POST['tipo_energia'] ?? null) ? $_POST['tipo_energia'] : [];
    $energias = [];
    foreach ($energiasPost as $energia) {
        $energia = estandar7_clean_text((string)$energia);
        if (isset($catalogoEnergia[$energia])) {
            $energias[] = $energia;
        }
    }
    $energias = array_values(array_unique($energias));

    try {
        $fotoEquipo = estandar7_upload_file('foto_equipo', $empresa_id, 'mantenimiento-equipos', false);
        $conn->beginTransaction();
        $stmtCodigo = $conn->prepare("
            SELECT codigo_interno
            FROM estandar7_mantenimiento_equipos
            WHERE empresa_id = ?
            ORDER BY CAST(codigo_interno AS UNSIGNED) DESC, id DESC
            LIMIT 1
        ");
        $stmtCodigo->execute([$empresa_id]);
        $ultimoCodigo = (string)($stmtCodigo->fetchColumn() ?: '000');
        $siguienteNumero = max(0, (int)$ultimoCodigo) + 1;
        $codigoInterno = str_pad((string)$siguienteNumero, 3, '0', STR_PAD_LEFT);

        $stmtInsert = $conn->prepare("
            INSERT INTO estandar7_mantenimiento_equipos (
                empresa_id, codigo_interno, tipo_elemento, nombre_elemento, marca,
                serie, modelo, tipo_energia_json, ubicacion, seccion, tipo_combustible,
                fabricante, direccion, telefono, foto_equipo, creado_por
            ) VALUES (
                :empresa_id, :codigo_interno, :tipo_elemento, :nombre_elemento, :marca,
                :serie, :modelo, :tipo_energia_json, :ubicacion, :seccion, :tipo_combustible,
                :fabricante, :direccion, :telefono, :foto_equipo, :creado_por
            )
        ");
        $stmtInsert->execute([
            ':empresa_id' => $empresa_id,
            ':codigo_interno' => $codigoInterno,
            ':tipo_elemento' => $tipoElemento,
            ':nombre_elemento' => $nombreElemento,
            ':marca' => estandar7_clean_text((string)($_POST['marca'] ?? '')),
            ':serie' => estandar7_clean_text((string)($_POST['serie'] ?? '')),
            ':modelo' => estandar7_clean_text((string)($_POST['modelo'] ?? '')),
            ':tipo_energia_json' => json_encode($energias, JSON_UNESCAPED_UNICODE),
            ':ubicacion' => estandar7_clean_text((string)($_POST['ubicacion'] ?? '')),
            ':seccion' => estandar7_clean_text((string)($_POST['seccion'] ?? '')),
            ':tipo_combustible' => estandar7_clean_text((string)($_POST['tipo_combustible'] ?? '')),
            ':fabricante' => $fabricante,
            ':direccion' => estandar7_clean_text((string)($_POST['direccion'] ?? '')),
            ':telefono' => estandar7_clean_text((string)($_POST['telefono'] ?? '')),
            ':foto_equipo' => $fotoEquipo,
            ':creado_por' => $usuario_id,
        ]);
        $conn->commit();
        estandar7_mantenimiento_redirect('Equipo registrado con codigo interno ' . $codigoInterno . '.', 'ok');
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        estandar7_mantenimiento_redirect('No se pudo guardar el registro del equipo.', 'error');
    }
}

if ($accion === 'guardar_mantenimiento_registro') {
    $equipoId = (int)($_POST['equipo_id'] ?? 0);
    $fecha = trim((string)($_POST['fecha'] ?? ''));
    if ($equipoId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        estandar7_mantenimiento_seguimiento_redirect('Selecciona el equipo y la fecha del mantenimiento.', 'error');
    }

    $stmtEquipo = $conn->prepare("SELECT id FROM estandar7_mantenimiento_equipos WHERE id=? AND empresa_id=? LIMIT 1");
    $stmtEquipo->execute([$equipoId, $empresa_id]);
    if (!$stmtEquipo->fetchColumn()) {
        estandar7_mantenimiento_seguimiento_redirect('El equipo seleccionado no pertenece a esta empresa.', 'error');
    }

    $convenciones = ['A', 'B', 'C', 'D', 'E', 'F'];
    $localizacionesPost = is_array($_POST['localizacion_averia'] ?? null) ? $_POST['localizacion_averia'] : [];
    $localizaciones = [];
    foreach ($localizacionesPost as $item) {
        $item = strtoupper(estandar7_clean_text((string)$item));
        if (in_array($item, $convenciones, true)) {
            $localizaciones[] = $item;
        }
    }
    $localizaciones = array_values(array_unique($localizaciones));

    $tipoMantenimiento = (int)($_POST['tipo_mantenimiento'] ?? 1);
    if (!in_array($tipoMantenimiento, [1, 2, 3], true)) {
        $tipoMantenimiento = 1;
    }
    $costoManoObra = estandar7_clean_money_value($_POST['costo_mano_obra'] ?? 0);
    $costoRepuestos = estandar7_clean_money_value($_POST['costo_repuestos'] ?? 0);
    $costoTotal = estandar7_clean_money_value($_POST['costo_total'] ?? 0);
    $horasMaquinaParada = trim((string)($_POST['horas_maquina_parada'] ?? ''));
    if ($costoTotal <= 0) {
        $costoTotal = $costoManoObra + $costoRepuestos;
    }

    try {
        $soporte = estandar7_upload_file('soporte_mantenimiento', $empresa_id, 'mantenimiento-soportes', false);
        $stmt = $conn->prepare("
            INSERT INTO estandar7_mantenimiento_registros (
                empresa_id, equipo_id, fecha, localizacion_averia_json, orden_no, mecanismo,
                tipo_mantenimiento, descripcion_trabajo, horas_maquina_parada,
                costo_mano_obra, costo_repuestos, costo_total, quien_realizo,
                quien_recibio, soporte_mantenimiento, creado_por
            ) VALUES (
                :empresa_id, :equipo_id, :fecha, :localizacion_averia_json, :orden_no, :mecanismo,
                :tipo_mantenimiento, :descripcion_trabajo, :horas_maquina_parada,
                :costo_mano_obra, :costo_repuestos, :costo_total, :quien_realizo,
                :quien_recibio, :soporte_mantenimiento, :creado_por
            )
        ");
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':equipo_id' => $equipoId,
            ':fecha' => $fecha,
            ':localizacion_averia_json' => json_encode($localizaciones, JSON_UNESCAPED_UNICODE),
            ':orden_no' => estandar7_clean_text((string)($_POST['orden_no'] ?? '')),
            ':mecanismo' => estandar7_clean_text((string)($_POST['mecanismo'] ?? '')),
            ':tipo_mantenimiento' => $tipoMantenimiento,
            ':descripcion_trabajo' => estandar7_clean_text((string)($_POST['descripcion_trabajo'] ?? '')),
            ':horas_maquina_parada' => $horasMaquinaParada === '' ? null : max(0, (float)$horasMaquinaParada),
            ':costo_mano_obra' => $costoManoObra,
            ':costo_repuestos' => $costoRepuestos,
            ':costo_total' => $costoTotal,
            ':quien_realizo' => estandar7_clean_text((string)($_POST['quien_realizo'] ?? '')),
            ':quien_recibio' => estandar7_clean_text((string)($_POST['quien_recibio'] ?? '')),
            ':soporte_mantenimiento' => $soporte,
            ':creado_por' => $usuario_id,
        ]);
        estandar7_mantenimiento_seguimiento_redirect('Registro de mantenimiento guardado.', 'ok', ['equipo_id' => $equipoId]);
    } catch (Throwable $e) {
        estandar7_mantenimiento_seguimiento_redirect($e->getMessage(), 'error', ['equipo_id' => $equipoId]);
    }
}

if ($accion === 'guardar_programa_documental') {
    $programa_slug = estandar7_clean_text((string)($_POST['programa_slug'] ?? ''));
    $catalogoProgramas = estandar7_programas_catalogo();
    if (!isset($catalogoProgramas[$programa_slug])) {
        estandar7_programa_redirect('El programa seleccionado no es valido.', 'error');
    }

    $seccionesPost = is_array($_POST['secciones'] ?? null) ? $_POST['secciones'] : [];
    $contenido = [];
    foreach ($catalogoProgramas[$programa_slug]['items'] as $itemSlug => $itemNombre) {
        $contenido[$itemSlug] = trim(strip_tags((string)($seccionesPost[$itemSlug] ?? '')));
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO estandar7_programas_documentales (
                empresa_id, programa_slug, programa_nombre, contenido_json, creado_por
            ) VALUES (
                :empresa_id, :programa_slug, :programa_nombre, :contenido_json, :creado_por
            )
            ON DUPLICATE KEY UPDATE
                programa_nombre = VALUES(programa_nombre),
                contenido_json = VALUES(contenido_json),
                actualizado_en = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':programa_slug' => $programa_slug,
            ':programa_nombre' => $catalogoProgramas[$programa_slug]['nombre'],
            ':contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
            ':creado_por' => $usuario_id,
        ]);

        estandar7_programa_redirect('Programa documental guardado correctamente.', 'ok', ['programa' => $programa_slug]);
    } catch (Throwable $e) {
        estandar7_programa_redirect('No se pudo guardar el programa documental.', 'error', ['programa' => $programa_slug]);
    }
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
