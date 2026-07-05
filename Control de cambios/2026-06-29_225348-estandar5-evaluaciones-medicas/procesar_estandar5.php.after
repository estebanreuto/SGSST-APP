<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar5_schema.php';
require_once 'vendor/autoload.php';

$u = require_auth($conn);
ensure_estandar5_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
$accion = $_POST['accion'] ?? '';

$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$empresa_id = (int)$stmt->fetchColumn();

function estandar5_redirect(string $msg, string $tipo = 'ok', string $modulo = 'perfiles-cargo'): never
{
    header('Location: estandar5.php?modulo=' . urlencode($modulo) . '&msg=' . urlencode($msg) . '&tipo=' . urlencode($tipo));
    exit;
}

function estandar5_require_sst(string $rol): void
{
    if ($rol !== 'sst') {
        estandar5_redirect('No tienes permiso para modificar esta información.', 'error');
    }
}

function estandar5_clean_list(array $values): array
{
    $clean = [];
    foreach ($values as $value) {
        $text = trim((string)$value);
        if ($text !== '') {
            $clean[] = $text;
        }
    }
    return array_values(array_unique($clean));
}

function estandar5_grouped_tools(array $post): array
{
    $groups = [
        'Administrativo' => estandar5_clean_list($post['herramientas_administrativo'] ?? []),
        'Herramientas menores' => estandar5_clean_list($post['herramientas_menores'] ?? []),
        'Herramientas eléctricas' => estandar5_clean_list($post['herramientas_electricas'] ?? []),
    ];

    $others = [
        'Administrativo' => trim((string)($post['herramienta_otra_administrativo'] ?? '')),
        'Herramientas menores' => trim((string)($post['herramienta_otra_menores'] ?? '')),
        'Herramientas eléctricas' => trim((string)($post['herramienta_otra_electricas'] ?? '')),
    ];

    foreach ($others as $group => $value) {
        if ($value !== '') {
            $groups[$group][] = $value;
        }
    }

    $tools = [];
    foreach ($groups as $group => $items) {
        foreach (estandar5_clean_list($items) as $item) {
            $tools[] = $group . ': ' . $item;
        }
    }

    $legacy = estandar5_clean_list($post['herramientas'] ?? []);
    foreach ($legacy as $item) {
        $tools[] = $item;
    }

    return estandar5_clean_list($tools);
}

function estandar5_upload_file(string $field, int $empresa_id): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar uno de los documentos.');
    }

    $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
    $original = (string)$_FILES[$field]['name'];
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Solo se permiten archivos PDF o imagen para las licencias.');
    }

    if ((int)$_FILES[$field]['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Cada documento debe pesar máximo 8 MB.');
    }

    $base_dir = __DIR__ . '/uploads/estandar5/empresa-' . $empresa_id;
    if (!is_dir($base_dir) && !mkdir($base_dir, 0775, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de documentos.');
    }

    $filename = $field . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $base_dir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar el documento cargado.');
    }

    return 'uploads/estandar5/empresa-' . $empresa_id . '/' . $filename;
}

function estandar5_decode_list(?string $json): array
{
    $data = json_decode((string)$json, true);
    return is_array($data) ? array_values(array_filter($data, fn($item) => trim((string)$item) !== '')) : [];
}

function estandar5_perfil_pdf_html(array $perfil, array $trabajador, string $empresa_nombre): string
{
    $tareas = estandar5_decode_list($perfil['tareas_json'] ?? '');
    $herramientas = estandar5_decode_list($perfil['herramientas_json'] ?? '');
    $riesgos = estandar5_decode_list($perfil['tareas_alto_riesgo_json'] ?? '');
    $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2d3d;font-size:12px}
        h1{color:#1e3a8a;font-size:20px;margin:0 0 4px} h2{color:#1e3a8a;font-size:15px;margin:18px 0 8px}
        .meta{color:#64748b;margin-bottom:16px}.box{border:1px solid #dbe3ec;border-radius:6px;padding:10px;margin-bottom:10px}
        table{width:100%;border-collapse:collapse}td,th{border:1px solid #dbe3ec;padding:7px;text-align:left;vertical-align:top}
        th{background:#f8fafc;color:#334155}.pill{display:inline-block;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:20px;padding:4px 8px;margin:2px}
    </style></head><body>';
    $html .= '<h1>Perfil de cargo para evaluación médica</h1>';
    $html .= '<div class="meta">Empresa: ' . htmlspecialchars($empresa_nombre) . ' | Generado: ' . date('d/m/Y H:i') . '</div>';
    $html .= '<table><tr><th>Trabajador</th><td>' . htmlspecialchars(trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''))) . '</td></tr>';
    $html .= '<tr><th>Cédula</th><td>' . htmlspecialchars($trabajador['cedula'] ?? '') . '</td></tr>';
    $html .= '<tr><th>Cargo / perfil</th><td>' . htmlspecialchars($perfil['nombre_cargo'] ?? '') . '</td></tr>';
    $html .= '<tr><th>Proceso</th><td>' . htmlspecialchars($perfil['tipo_proceso'] ?? '') . '</td></tr>';
    $html .= '<tr><th>Tipo de operación</th><td>' . htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto') . '</td></tr>';
    $html .= '<tr><th>Jefe inmediato</th><td>' . htmlspecialchars($perfil['jefe_inmediato'] ?? '') . '</td></tr></table>';
    $html .= '<h2>Tareas del cargo</h2><ol>';
    foreach ($tareas as $tarea) $html .= '<li>' . htmlspecialchars($tarea) . '</li>';
    $html .= '</ol><h2>Herramientas autorizadas</h2><div class="box">';
    $html .= $herramientas ? '' : 'Sin herramientas registradas.';
    foreach ($herramientas as $item) $html .= '<span class="pill">' . htmlspecialchars($item) . '</span>';
    $html .= '</div><h2>Tareas de alto riesgo</h2><div class="box">';
    $html .= $riesgos ? '' : 'Sin tareas de alto riesgo registradas.';
    foreach ($riesgos as $item) $html .= '<span class="pill">' . htmlspecialchars($item) . '</span>';
    $html .= '</div></body></html>';
    return $html;
}

function estandar5_pdf_bytes(array $perfil, array $trabajador, string $empresa_nombre): string
{
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml(estandar5_perfil_pdf_html($perfil, $trabajador, $empresa_nombre), 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}

function estandar5_enviar_solicitud_medica(array $centro, array $trabajador, array $perfil, string $empresa_nombre, string $tipo_examen, string $observaciones): array
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no está instalado.'];
    }

    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    if ($user === '' || $pass === '') {
        return [false, 'Faltan credenciales SMTP.'];
    }

    $correo = trim((string)($centro['correo'] ?? ''));
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return [false, 'El centro médico no tiene un correo válido.'];
    }

    $nombre_trabajador = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''));
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $secure = getenv('SMTP_SECURE') ?: 'tls';
        $mail->SMTPSecure = ($secure === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(getenv('SMTP_FROM_ADDRESS') ?: $user, getenv('SMTP_FROM_NAME') ?: 'PreventWork');
        $mail->addAddress($correo, $centro['nombre'] ?? 'Centro médico');
        $mail->isHTML(true);
        $mail->Subject = 'Solicitud de programación de examen médico ocupacional';
        $mail->Body = '
            <p>Cordial saludo,</p>
            <p>Solicitamos la programación del examen médico ocupacional para el siguiente trabajador:</p>
            <ul>
                <li><strong>Empresa:</strong> ' . htmlspecialchars($empresa_nombre) . '</li>
                <li><strong>Trabajador:</strong> ' . htmlspecialchars($nombre_trabajador) . '</li>
                <li><strong>Cédula:</strong> ' . htmlspecialchars($trabajador['cedula'] ?? '') . '</li>
                <li><strong>Tipo de examen:</strong> ' . htmlspecialchars($tipo_examen) . '</li>
                <li><strong>Perfil de cargo:</strong> ' . htmlspecialchars($perfil['nombre_cargo'] ?? '') . '</li>
            </ul>
            <p>Adjuntamos el perfil de cargo en PDF para orientar la evaluación médica.</p>' .
            ($observaciones !== '' ? '<p><strong>Observaciones:</strong> ' . nl2br(htmlspecialchars($observaciones)) . '</p>' : '') .
            '<p>Gracias.</p>';
        $mail->AltBody = "Solicitud de programación de examen médico ocupacional para $nombre_trabajador, cédula " . ($trabajador['cedula'] ?? '') . ".";
        $filename = 'perfil_cargo_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $perfil['nombre_cargo'] ?? 'trabajador') . '.pdf';
        $mail->addStringAttachment(estandar5_pdf_bytes($perfil, $trabajador, $empresa_nombre), $filename, 'base64', 'application/pdf');
        $mail->send();
        return [true, 'Correo enviado.'];
    } catch (Throwable $e) {
        return [false, $e->getMessage()];
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        estandar5_redirect('Solicitud no válida.', 'error');
    }

    if (!$empresa_id || !in_array($rol, ['sst', 'representante'], true)) {
        estandar5_redirect('Sesión no válida.', 'error');
    }

    estandar5_require_sst($rol);

    if ($accion === 'guardar_centro_medico') {
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $direccion = trim($_POST['direccion_principal'] ?? '');
        $sedes = estandar5_clean_list($_POST['sedes'] ?? []);
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $nit === '' || $direccion === '' || $telefono === '' || $correo === '') {
            throw new RuntimeException('Completa los datos obligatorios del centro médico.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo del centro médico no es válido.');
        }

        $licencia_funcionamiento = estandar5_upload_file('licencia_funcionamiento', $empresa_id);
        $licencia_sst = estandar5_upload_file('licencia_sst', $empresa_id);

        $stmt = $conn->prepare("
            INSERT INTO estandar5_centros_medicos
                (empresa_id, nombre, nit, direccion_principal, sedes_json, telefono, correo,
                 licencia_funcionamiento_archivo, licencia_sst_archivo, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $nombre,
            $nit,
            $direccion,
            json_encode($sedes, JSON_UNESCAPED_UNICODE),
            $telefono,
            $correo,
            $licencia_funcionamiento,
            $licencia_sst,
            $usuario_id,
        ]);

        estandar5_redirect('Centro médico autorizado registrado.');
    }

    if ($accion === 'guardar_perfil_cargo') {
        $centro_medico_id = (int)($_POST['centro_medico_id'] ?? 0);
        $nombre_cargo = trim($_POST['nombre_cargo'] ?? '');
        $tipo_operacion = trim($_POST['tipo_operacion'] ?? '');
        $tipo_proceso_select = trim($_POST['tipo_proceso_select'] ?? '');
        $tipo_proceso_nuevo = trim($_POST['tipo_proceso_nuevo'] ?? '');
        $tipo_proceso = $tipo_proceso_select === '__nuevo_proceso__' ? $tipo_proceso_nuevo : $tipo_proceso_select;
        $guardar_proceso = ($_POST['guardar_proceso'] ?? '0') === '1';
        $jefe_inmediato = trim($_POST['jefe_inmediato'] ?? '');
        $tareas = estandar5_clean_list($_POST['tareas'] ?? []);
        $tareas_alto_riesgo = estandar5_clean_list($_POST['tareas_alto_riesgo'] ?? []);
        $herramientas = estandar5_grouped_tools($_POST);

        if (!in_array($tipo_operacion, ['Administrativo', 'Operativo', 'Mixto'], true)) {
            throw new RuntimeException('Selecciona un tipo de operación válido.');
        }

        if ($nombre_cargo === '' || $tipo_proceso === '' || $jefe_inmediato === '' || empty($tareas)) {
            throw new RuntimeException('Completa cargo, proceso, tipo de operación, jefe inmediato y al menos una tarea.');
        }

        if ($centro_medico_id > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar5_centros_medicos WHERE id = ? AND empresa_id = ? AND estado = 'activo'");
            $stmt->execute([$centro_medico_id, $empresa_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new RuntimeException('El centro médico seleccionado no pertenece a la empresa.');
            }
        } else {
            $centro_medico_id = null;
        }

        if ($guardar_proceso && $tipo_proceso !== '') {
            $stmt = $conn->prepare("
                INSERT IGNORE INTO estandar5_procesos_perfil (empresa_id, nombre, creado_por)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$empresa_id, $tipo_proceso, $usuario_id]);
        }

        $stmt = $conn->prepare("
            INSERT INTO estandar5_perfiles_cargo
                (empresa_id, centro_medico_id, nombre_cargo, tipo_proceso, tipo_operacion, jefe_inmediato,
                 tareas_json, tareas_alto_riesgo_json, herramientas_json, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $centro_medico_id,
            $nombre_cargo,
            $tipo_proceso,
            $tipo_operacion,
            $jefe_inmediato,
            json_encode($tareas, JSON_UNESCAPED_UNICODE),
            json_encode($tareas_alto_riesgo, JSON_UNESCAPED_UNICODE),
            json_encode($herramientas, JSON_UNESCAPED_UNICODE),
            $usuario_id,
        ]);

        estandar5_redirect('Perfil de cargo guardado y disponible para reutilizar.');
    }

    if ($accion === 'programar_evaluacion_medica') {
        $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
        $perfil_cargo_id = (int)($_POST['perfil_cargo_id'] ?? 0);
        $centro_medico_id = (int)($_POST['centro_medico_id'] ?? 0);
        $tipo_examen = trim($_POST['tipo_examen'] ?? 'Periodico');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $tipos_validos = ['Ingreso', 'Periodico', 'Egreso', 'Post incapacidad', 'Reubicacion'];

        if (!in_array($tipo_examen, $tipos_validos, true)) {
            throw new RuntimeException('Selecciona un tipo de examen válido.');
        }

        $stmt = $conn->prepare("
            SELECT u.*, g.nombre AS grupo_nombre, e.tipo_personal
            FROM usuarios u
            LEFT JOIN grupos_personal g ON g.id = u.grupo_id
            LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
            WHERE u.id = ? AND u.empresa_id = ? AND u.rol = 'trabajador'
            LIMIT 1
        ");
        $stmt->execute([$trabajador_id, $empresa_id]);
        $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trabajador) {
            throw new RuntimeException('No se encontró el trabajador seleccionado.');
        }

        $stmt = $conn->prepare("SELECT * FROM estandar5_perfiles_cargo WHERE id = ? AND empresa_id = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$perfil_cargo_id, $empresa_id]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$perfil) {
            throw new RuntimeException('Selecciona un perfil de cargo válido.');
        }

        $stmt = $conn->prepare("SELECT * FROM estandar5_centros_medicos WHERE id = ? AND empresa_id = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$centro_medico_id, $empresa_id]);
        $centro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$centro) {
            throw new RuntimeException('Selecciona un centro médico válido.');
        }

        if (!filter_var((string)$centro['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El centro médico seleccionado no tiene correo válido.');
        }

        $stmt = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE empresa_id = ? AND nombre_empresa IS NOT NULL AND nombre_empresa <> '' LIMIT 1");
        $stmt->execute([$empresa_id]);
        $empresa_nombre = $stmt->fetchColumn() ?: 'Empresa';

        [$ok, $mail_msg] = estandar5_enviar_solicitud_medica($centro, $trabajador, $perfil, $empresa_nombre, $tipo_examen, $observaciones);
        if (!$ok) {
            throw new RuntimeException('No se pudo enviar el correo al centro médico: ' . $mail_msg);
        }

        $stmt = $conn->prepare("
            INSERT INTO estandar5_evaluaciones_medicas
                (empresa_id, trabajador_id, perfil_cargo_id, centro_medico_id, tipo_examen, correo_destino, observaciones, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $trabajador_id,
            $perfil_cargo_id,
            $centro_medico_id,
            $tipo_examen,
            $centro['correo'],
            $observaciones,
            $usuario_id,
        ]);

        estandar5_redirect('Solicitud enviada al centro médico y programación registrada.', 'ok', 'evaluaciones-medicas');
    }

    estandar5_redirect('Acción no reconocida.', 'error');
} catch (Throwable $e) {
    $modulo_error = $accion === 'programar_evaluacion_medica' ? 'evaluaciones-medicas' : 'perfiles-cargo';
    estandar5_redirect($e->getMessage(), 'error', $modulo_error);
}
