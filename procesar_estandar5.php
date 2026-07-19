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
    $origen = trim((string)($_POST['origen_formulario'] ?? ''));
    if ($origen === 'control-examenes' && $modulo === 'evaluaciones-medicas') {
        header('Location: control_examenes_medicos?msg=' . urlencode($msg) . '&tipo=' . urlencode($tipo));
        exit;
    }
    if ($origen === 'gestion-restricciones' && $modulo === 'restricciones') {
        $vista = ($_POST['vista_restricciones'] ?? '') === 'seguimiento' ? 'seguimiento' : 'nueva';
        header('Location: gestion_restricciones_medicas?vista=' . urlencode($vista) . '&msg=' . urlencode($msg) . '&tipo=' . urlencode($tipo));
        exit;
    }
    if ($modulo === 'perfiles-cargo') {
        $origenes = [
            'centro' => 'nuevo_centro_medico',
            'proceso' => 'nuevo_proceso_perfil',
            'perfil' => 'nuevo_perfil_cargo',
        ];
        if (isset($origenes[$origen])) {
            header('Location: ' . $origenes[$origen] . '?msg=' . urlencode($msg) . '&tipo=' . urlencode($tipo));
            exit;
        }
    }
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

function estandar5_upload_medical_pdf(string $field, int $empresa_id): string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Carga el PDF del examen o certificado.');
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar el PDF seleccionado.');
    }

    $extension = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new RuntimeException('Solo se permite cargar archivo PDF.');
    }
    if ((int)$_FILES[$field]['size'] > 12 * 1024 * 1024) {
        throw new RuntimeException('El PDF debe pesar maximo 12 MB.');
    }

    $base_dir = __DIR__ . '/uploads/estandar5/empresa-' . $empresa_id . '/evaluaciones';
    if (!is_dir($base_dir) && !mkdir($base_dir, 0775, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de examenes medicos.');
    }

    $filename = 'examen_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $target = $base_dir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar el PDF cargado.');
    }

    return 'uploads/estandar5/empresa-' . $empresa_id . '/evaluaciones/' . $filename;
}

function estandar5_upload_custody_pdf(string $field, int $empresa_id): string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Carga el PDF del certificado de custodia.');
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar el PDF seleccionado.');
    }

    $extension = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new RuntimeException('Solo se permite cargar certificado en PDF.');
    }
    if ((int)$_FILES[$field]['size'] > 12 * 1024 * 1024) {
        throw new RuntimeException('El PDF debe pesar maximo 12 MB.');
    }

    $base_dir = __DIR__ . '/uploads/estandar5/empresa-' . $empresa_id . '/historias-clinicas';
    if (!is_dir($base_dir) && !mkdir($base_dir, 0775, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de certificados.');
    }

    $filename = 'custodia_historias_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $target = $base_dir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar el PDF cargado.');
    }

    return 'uploads/estandar5/empresa-' . $empresa_id . '/historias-clinicas/' . $filename;
}

function estandar5_date_or_null(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return $dt && $dt->format('Y-m-d') === $value ? $value : null;
}

function estandar5_int_or_null(string $value): ?int
{
    $value = trim($value);
    return $value === '' ? null : (int)$value;
}

function estandar5_add_year(?string $date): ?string
{
    if (!$date) {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->modify('+1 year')->format('Y-m-d') : null;
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

function estandar5_restriccion_carta_html(array $data, string $empresa_nombre): string
{
    $trabajador = htmlspecialchars($data['trabajador'] ?? '');
    $cedula = htmlspecialchars($data['cedula'] ?? '');
    $cargo = htmlspecialchars($data['cargo'] ?? '');
    $proyecto = htmlspecialchars($data['proyecto'] ?? '');
    $fecha = htmlspecialchars($data['carta_fecha_texto'] ?? date('d/m/Y'));
    $fecha_examen = htmlspecialchars($data['fecha_examen_texto'] ?? '');
    $ips = htmlspecialchars($data['ips_nombre'] ?? '');
    $concepto = htmlspecialchars($data['concepto_medico'] ?? '');
    $restricciones = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($data['restriccion'] ?? ''))));
    $recom_laborales = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($data['recomendaciones_laborales'] ?? ''))));
    $recom_generales = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($data['recomendaciones_generales'] ?? ''))));

    $list = function (array $items): string {
        if (!$items) {
            return '<li>Sin detalle registrado.</li>';
        }
        return implode('', array_map(fn($item) => '<li>' . htmlspecialchars($item) . '</li>', $items));
    };

    return '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#111827;font-size:12px;line-height:1.42}
        .page{padding:26px 34px}.brand{font-size:18px;font-weight:800;color:#6aa35f;margin-bottom:12px}
        h1{text-align:center;font-size:14px;text-transform:uppercase;margin:0 0 24px;color:#374151}
        .date{text-align:right;margin-bottom:22px}.recipient{font-weight:700;margin-bottom:18px}
        .subject{margin:18px 0}.section-title{font-weight:800;margin:18px 0 6px}
        ul{margin-top:6px}.note{font-weight:800;margin-top:18px}.muted{color:#6b7280}
        .signatures{display:grid;grid-template-columns:1fr 1fr;gap:44px;margin-top:32px}
        .line{border-top:1px solid #111827;padding-top:6px;margin-top:30px}.footer{margin-top:28px;color:#6b7280;font-size:10px}
    </style></head><body><div class="page">
        <div class="brand">' . htmlspecialchars($empresa_nombre) . '</div>
        <h1>Comunicación resultado de evaluación médica ocupacional,<br>recomendaciones y/o restricciones médicas</h1>
        <div class="date">Bogotá, ' . $fecha . '</div>
        <div class="recipient">Señor(a):<br>' . $trabajador . '<br>C.C. ' . $cedula . '<br>' . $cargo . '<br>' . $proyecto . '</div>
        <div class="subject"><strong>Asunto:</strong> Comunicación resultado de evaluación médica ocupacional, recomendaciones y/o restricciones.</div>
        <p><strong>Cordial saludo:</strong></p>
        <p>Respetuosamente informamos que, de acuerdo con el resultado de la valoración médica ocupacional realizada el ' . $fecha_examen . ' en la IPS ' . $ips . ', el concepto emitido es: <strong>' . $concepto . '</strong>.</p>
        <p>El médico ocupacional generó las siguientes recomendaciones y/o restricciones:</p>
        <div class="section-title">Restricciones Médico Generales - Laborales</div><ul>' . $list($restricciones ?: $recom_laborales) . '</ul>
        <div class="section-title">Recomendaciones Médico Generales - Laborales</div><ul>' . $list($recom_generales) . '</ul>
        <p>Con el fin de apoyar el cuidado integral de su salud, es importante que se remita a su EPS e inicie el proceso de valoración lo más pronto posible y entregue los soportes de asistencia o controles.</p>
        <p class="note">NOTA: NO SE ENCUENTRA AUTORIZADO para realizar actividades en las cuales exista exposición no permitida según las restricciones temporales o recomendaciones médicas registradas.</p>
        <div class="signatures"><div><strong>Cordialmente,</strong><div class="line">Cargo responsable del SG-SST</div></div><div><strong>Recibido / Notificado:</strong><div class="line">Trabajador / Jefe inmediato</div></div></div>
        <div class="footer">Código: F-SST-028 · Generado desde PreventWork</div>
    </div></body></html>';
}

function estandar5_save_restriccion_carta_pdf(array $data, string $empresa_nombre, int $empresa_id): string
{
    $base_dir = __DIR__ . '/uploads/estandar5/empresa-' . $empresa_id . '/restricciones';
    if (!is_dir($base_dir) && !mkdir($base_dir, 0775, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de cartas.');
    }
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml(estandar5_restriccion_carta_html($data, $empresa_nombre), 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'carta_recomendaciones_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    file_put_contents($base_dir . '/' . $filename, $dompdf->output());
    return 'uploads/estandar5/empresa-' . $empresa_id . '/restricciones/' . $filename;
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

    if ($accion === 'guardar_proceso_perfil') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            throw new RuntimeException('Escribe el nombre del proceso.');
        }
        if (mb_strlen($nombre) > 160) {
            throw new RuntimeException('El nombre del proceso es demasiado largo.');
        }

        $stmt = $conn->prepare("
            INSERT IGNORE INTO estandar5_procesos_perfil (empresa_id, nombre, creado_por)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$empresa_id, $nombre, $usuario_id]);

        $mensaje = $stmt->rowCount() > 0
            ? 'Proceso guardado y disponible para crear perfiles.'
            : 'Ese proceso ya estaba registrado y sigue disponible.';
        estandar5_redirect($mensaje);
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
        $trabajador_ids = $_POST['trabajador_ids'] ?? [];
        if (!is_array($trabajador_ids)) {
            $trabajador_ids = [];
        }
        $trabajador_id_individual = (int)($_POST['trabajador_id'] ?? 0);
        if ($trabajador_id_individual > 0) {
            $trabajador_ids[] = $trabajador_id_individual;
        }
        $trabajador_ids = array_values(array_unique(array_filter(array_map('intval', $trabajador_ids), fn($id) => $id > 0)));
        $perfil_cargo_id = (int)($_POST['perfil_cargo_id'] ?? 0);
        $centro_medico_id = (int)($_POST['centro_medico_id'] ?? 0);
        $tipo_examen = trim($_POST['tipo_examen'] ?? 'Periodico');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $tipos_validos = ['Ingreso', 'Periodico', 'Egreso', 'Post incapacidad', 'Reubicacion'];

        if (empty($trabajador_ids)) {
            throw new RuntimeException('Selecciona al menos un trabajador para programar.');
        }
        if (count($trabajador_ids) > 200) {
            throw new RuntimeException('Puedes programar máximo 200 trabajadores por envío.');
        }
        if (!in_array($tipo_examen, $tipos_validos, true)) {
            throw new RuntimeException('Selecciona un tipo de examen válido.');
        }

        $placeholders = implode(',', array_fill(0, count($trabajador_ids), '?'));
        $stmt = $conn->prepare("
            SELECT u.*, g.nombre AS grupo_nombre, e.tipo_personal
            FROM usuarios u
            LEFT JOIN grupos_personal g ON g.id = u.grupo_id
            LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
            WHERE u.id IN ($placeholders) AND u.empresa_id = ? AND u.rol = 'trabajador'
            ORDER BY u.nombre ASC, u.apellido ASC
        ");
        $stmt->execute([...$trabajador_ids, $empresa_id]);
        $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($trabajadores) !== count($trabajador_ids)) {
            throw new RuntimeException('Uno o más trabajadores seleccionados no pertenecen a la empresa.');
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

        $insertar_programacion = $conn->prepare("
            INSERT INTO estandar5_evaluaciones_medicas
                (empresa_id, trabajador_id, perfil_cargo_id, centro_medico_id, tipo_examen, correo_destino, observaciones, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $programados = 0;
        $fallidos = [];
        foreach ($trabajadores as $trabajador) {
            [$ok, $mail_msg] = estandar5_enviar_solicitud_medica($centro, $trabajador, $perfil, $empresa_nombre, $tipo_examen, $observaciones);
            if (!$ok) {
                $fallidos[] = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? '')) . ': ' . $mail_msg;
                continue;
            }
            $insertar_programacion->execute([
                $empresa_id,
                (int)$trabajador['id'],
                $perfil_cargo_id,
                $centro_medico_id,
                $tipo_examen,
                $centro['correo'],
                $observaciones,
                $usuario_id,
            ]);
            $programados++;
        }

        if ($programados === 0) {
            throw new RuntimeException('No se pudo programar ningún trabajador. ' . implode(' | ', array_slice($fallidos, 0, 3)));
        }
        $mensaje_programacion = $programados . ' trabajador(es) programado(s) y solicitud(es) enviada(s) al centro médico.';
        if (!empty($fallidos)) {
            $mensaje_programacion .= ' ' . count($fallidos) . ' envío(s) no se completaron; puedes intentarlos nuevamente.';
        }
        estandar5_redirect($mensaje_programacion, 'ok', 'evaluaciones-medicas');
    }

    if ($accion === 'guardar_soporte_evaluacion_medica') {
        $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
        $evaluacion_id = (int)($_POST['evaluacion_id'] ?? 0);
        $perfil_cargo_id = (int)($_POST['perfil_cargo_id'] ?? 0);
        $centro_medico_id = (int)($_POST['centro_medico_id'] ?? 0);

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
            throw new RuntimeException('Selecciona un trabajador valido.');
        }

        if ($evaluacion_id > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar5_evaluaciones_medicas WHERE id = ? AND empresa_id = ? AND trabajador_id = ?");
            $stmt->execute([$evaluacion_id, $empresa_id, $trabajador_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new RuntimeException('La solicitud seleccionada no pertenece al trabajador.');
            }
        } else {
            $evaluacion_id = null;
        }

        if ($perfil_cargo_id > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar5_perfiles_cargo WHERE id = ? AND empresa_id = ? AND estado = 'activo'");
            $stmt->execute([$perfil_cargo_id, $empresa_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new RuntimeException('Selecciona un perfil de cargo valido.');
            }
        } else {
            $perfil_cargo_id = null;
        }

        if ($centro_medico_id > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar5_centros_medicos WHERE id = ? AND empresa_id = ? AND estado = 'activo'");
            $stmt->execute([$centro_medico_id, $empresa_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new RuntimeException('Selecciona un centro medico valido.');
            }
        } else {
            $centro_medico_id = null;
        }

        $archivo_pdf = estandar5_upload_medical_pdf('archivo_pdf', $empresa_id);
        $nombre_trabajador = trim((string)($_POST['nombre_trabajador'] ?? ''));
        $cedula = trim((string)($_POST['cedula'] ?? ''));
        $cargo = trim((string)($_POST['cargo'] ?? ''));
        $centro_medico = trim((string)($_POST['centro_medico'] ?? ''));

        if ($nombre_trabajador === '') {
            $nombre_trabajador = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''));
        }
        if ($cedula === '') {
            $cedula = (string)($trabajador['cedula'] ?? '');
        }
        if ($cargo === '') {
            $cargo = trim((string)($trabajador['grupo_nombre'] ?? '')) ?: trim((string)($trabajador['tipo_personal'] ?? ''));
        }
        if ($nombre_trabajador === '' || $cedula === '') {
            throw new RuntimeException('Confirma nombre y cedula del trabajador.');
        }

        $stmt = $conn->prepare("
            INSERT INTO estandar5_evaluaciones_medicas_soportes
                (empresa_id, trabajador_id, evaluacion_id, perfil_cargo_id, centro_medico_id,
                 nombre_trabajador, cedula, cargo, tipo_examen, resultado, tipo_aptitud, centro_medico,
                 fecha_expedicion, fecha_vencimiento, tiempo_para_programar, dias_accion,
                 altura_nivel_curso, altura_centro_capacitador, altura_fecha_expedicion, altura_fecha_vencimiento, altura_programar, altura_dias_accion,
                 confinado_nivel_curso, confinado_centro_capacitador, confinado_fecha_expedicion, confinado_fecha_vencimiento, confinado_programar, confinado_dias_accion,
                 archivo_pdf, texto_extraido, observaciones, creado_por)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $trabajador_id,
            $evaluacion_id,
            $perfil_cargo_id,
            $centro_medico_id,
            $nombre_trabajador,
            $cedula,
            $cargo,
            trim((string)($_POST['tipo_examen'] ?? '')),
            trim((string)($_POST['resultado'] ?? '')),
            trim((string)($_POST['tipo_aptitud'] ?? '')),
            $centro_medico,
            estandar5_date_or_null((string)($_POST['fecha_expedicion'] ?? '')),
            estandar5_date_or_null((string)($_POST['fecha_vencimiento'] ?? '')),
            trim((string)($_POST['tiempo_para_programar'] ?? '')),
            estandar5_int_or_null((string)($_POST['dias_accion'] ?? '')),
            trim((string)($_POST['altura_nivel_curso'] ?? '')),
            trim((string)($_POST['altura_centro_capacitador'] ?? '')),
            estandar5_date_or_null((string)($_POST['altura_fecha_expedicion'] ?? '')),
            estandar5_date_or_null((string)($_POST['altura_fecha_vencimiento'] ?? '')),
            trim((string)($_POST['altura_programar'] ?? '')),
            estandar5_int_or_null((string)($_POST['altura_dias_accion'] ?? '')),
            trim((string)($_POST['confinado_nivel_curso'] ?? '')),
            trim((string)($_POST['confinado_centro_capacitador'] ?? '')),
            estandar5_date_or_null((string)($_POST['confinado_fecha_expedicion'] ?? '')),
            estandar5_date_or_null((string)($_POST['confinado_fecha_vencimiento'] ?? '')),
            trim((string)($_POST['confinado_programar'] ?? '')),
            estandar5_int_or_null((string)($_POST['confinado_dias_accion'] ?? '')),
            $archivo_pdf,
            trim((string)($_POST['texto_extraido'] ?? '')),
            trim((string)($_POST['observaciones'] ?? '')),
            $usuario_id,
        ]);

        if ($evaluacion_id) {
            $stmt = $conn->prepare("UPDATE estandar5_evaluaciones_medicas SET estado = 'realizada' WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$evaluacion_id, $empresa_id]);
        }

        estandar5_redirect('Examen cargado y vencimientos registrados.', 'ok', 'evaluaciones-medicas');
    }

    if ($accion === 'guardar_custodia_historia_clinica') {
        $centro_medico_id = (int)($_POST['centro_medico_id'] ?? 0);
        $fecha_emision = estandar5_date_or_null((string)($_POST['fecha_emision'] ?? ''));
        $fecha_renovacion = estandar5_add_year($fecha_emision);
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $texto_extraido = trim((string)($_POST['texto_extraido'] ?? ''));

        if ($centro_medico_id <= 0) {
            throw new RuntimeException('Selecciona un centro medico registrado.');
        }
        if (!$fecha_emision || !$fecha_renovacion) {
            throw new RuntimeException('Confirma la fecha de emision del certificado para calcular la renovacion anual.');
        }

        $stmt = $conn->prepare("
            SELECT id
            FROM estandar5_centros_medicos
            WHERE id = ? AND empresa_id = ? AND estado = 'activo'
            LIMIT 1
        ");
        $stmt->execute([$centro_medico_id, $empresa_id]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('El centro medico seleccionado no pertenece a la empresa.');
        }

        $archivo_pdf = estandar5_upload_custody_pdf('archivo_pdf', $empresa_id);

        $stmt = $conn->prepare("
            INSERT INTO estandar5_historia_clinica_custodias
                (empresa_id, centro_medico_id, archivo_pdf, fecha_emision, fecha_renovacion, observaciones, texto_extraido, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $centro_medico_id,
            $archivo_pdf,
            $fecha_emision,
            $fecha_renovacion,
            $observaciones,
            $texto_extraido,
            $usuario_id,
        ]);

        estandar5_redirect('Certificado de custodia cargado.', 'ok', 'historias-clinicas');
    }

    if (in_array($accion, ['guardar_restriccion_recomendacion', 'crear_carta_recomendacion_medica'], true)) {
        $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
        $cargo = trim((string)($_POST['cargo'] ?? ''));
        $fecha_ingreso = estandar5_date_or_null((string)($_POST['fecha_ingreso'] ?? ''));
        $carta_fecha = estandar5_date_or_null((string)($_POST['carta_fecha'] ?? date('Y-m-d')));
        $fecha_examen = estandar5_date_or_null((string)($_POST['fecha_examen'] ?? ''));
        $ips_nombre = trim((string)($_POST['ips_nombre'] ?? ''));
        $concepto_medico = trim((string)($_POST['concepto_medico'] ?? ''));
        $proyecto = trim((string)($_POST['proyecto'] ?? ''));
        $carta_firmada = 'No';
        $fecha_entrega_carta = null;
        $recomendaciones_laborales = trim((string)($_POST['recomendaciones_laborales'] ?? ''));
        $recomendaciones_generales = trim((string)($_POST['recomendaciones_generales'] ?? ''));
        $pve = estandar5_clean_list($_POST['pve'] ?? []);
        $tipo_restriccion = trim((string)($_POST['tipo_restriccion'] ?? ''));
        $restriccion = trim((string)($_POST['restriccion'] ?? ''));
        $sst_fecha_programada = estandar5_date_or_null((string)($_POST['sst_fecha_programada'] ?? ''));
        $sst_fecha_real = estandar5_date_or_null((string)($_POST['sst_fecha_real'] ?? ''));
        $sst_responsable = trim((string)($_POST['sst_responsable'] ?? ''));
        $sst_estado = trim((string)($_POST['sst_estado'] ?? ''));
        $sst_historial = trim((string)($_POST['sst_historial'] ?? ''));
        $arl_fecha_real = estandar5_date_or_null((string)($_POST['arl_fecha_real'] ?? ''));
        $arl_responsable = trim((string)($_POST['arl_responsable'] ?? ''));
        $arl_historial = trim((string)($_POST['arl_historial'] ?? ''));

        $stmt = $conn->prepare("
            SELECT u.id, u.nombre, u.apellido, u.cedula, g.nombre AS grupo_nombre, e.tipo_personal
            FROM usuarios u
            LEFT JOIN grupos_personal g ON g.id = u.grupo_id
            LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
            WHERE u.id = ? AND u.empresa_id = ? AND u.rol = 'trabajador'
            LIMIT 1
        ");
        $stmt->execute([$trabajador_id, $empresa_id]);
        $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trabajador) {
            throw new RuntimeException('Selecciona un trabajador valido.');
        }

        if ($cargo === '') {
            $cargo = trim((string)($trabajador['grupo_nombre'] ?? '')) ?: trim((string)($trabajador['tipo_personal'] ?? ''));
        }
        if ($tipo_restriccion === '') {
            throw new RuntimeException('Selecciona el tipo de restriccion.');
        }
        if (!$carta_fecha) {
            throw new RuntimeException('Confirma la fecha de la carta.');
        }
        if ($concepto_medico === '') {
            $concepto_medico = $tipo_restriccion;
        }

        $stmt = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE empresa_id = ? AND nombre_empresa IS NOT NULL AND nombre_empresa <> '' LIMIT 1");
        $stmt->execute([$empresa_id]);
        $empresa_nombre = $stmt->fetchColumn() ?: 'Empresa';
        $nombre_trabajador = trim(($trabajador['nombre'] ?? '') . ' ' . ($trabajador['apellido'] ?? ''));
        $carta_pdf = estandar5_save_restriccion_carta_pdf([
            'trabajador' => $nombre_trabajador,
            'cedula' => $trabajador['cedula'] ?? '',
            'cargo' => $cargo,
            'proyecto' => $proyecto,
            'carta_fecha_texto' => date('d/m/Y', strtotime($carta_fecha)),
            'fecha_examen_texto' => $fecha_examen ? date('d/m/Y', strtotime($fecha_examen)) : '',
            'ips_nombre' => $ips_nombre,
            'concepto_medico' => $concepto_medico,
            'restriccion' => $restriccion,
            'recomendaciones_laborales' => $recomendaciones_laborales,
            'recomendaciones_generales' => $recomendaciones_generales,
        ], $empresa_nombre, $empresa_id);

        $stmt = $conn->prepare("
            INSERT INTO estandar5_restricciones_recomendaciones
                (empresa_id, trabajador_id, cargo, fecha_ingreso, carta_fecha, fecha_examen, ips_nombre,
                 concepto_medico, proyecto, carta_pdf, carta_firmada, fecha_entrega_carta,
                 recomendaciones_laborales, recomendaciones_generales, pve_json, tipo_restriccion, restriccion,
                 sst_fecha_programada, sst_fecha_real, sst_responsable, sst_estado, sst_historial,
                 arl_fecha_real, arl_responsable, arl_historial, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $trabajador_id,
            $cargo,
            $fecha_ingreso,
            $carta_fecha,
            $fecha_examen,
            $ips_nombre,
            $concepto_medico,
            $proyecto,
            $carta_pdf,
            $carta_firmada,
            $fecha_entrega_carta,
            $recomendaciones_laborales,
            $recomendaciones_generales,
            json_encode($pve, JSON_UNESCAPED_UNICODE),
            $tipo_restriccion,
            $restriccion,
            $sst_fecha_programada,
            $sst_fecha_real,
            $sst_responsable,
            $sst_estado,
            $sst_historial,
            $arl_fecha_real,
            $arl_responsable,
            $arl_historial,
            $usuario_id,
        ]);

        estandar5_redirect('Carta creada y sincronizada con la matriz de seguimiento.', 'ok', 'restricciones');
    }

    if ($accion === 'actualizar_seguimiento_restriccion') {
        $registro_id = (int)($_POST['registro_id'] ?? 0);
        $carta_firmada = ($_POST['carta_firmada'] ?? 'No') === 'Si' ? 'Si' : 'No';
        $fecha_entrega_carta = estandar5_date_or_null((string)($_POST['fecha_entrega_carta'] ?? ''));
        $sst_fecha_programada = estandar5_date_or_null((string)($_POST['sst_fecha_programada'] ?? ''));
        $sst_fecha_real = estandar5_date_or_null((string)($_POST['sst_fecha_real'] ?? ''));
        $sst_responsable = trim((string)($_POST['sst_responsable'] ?? ''));
        $sst_estado = trim((string)($_POST['sst_estado'] ?? ''));
        $sst_historial = trim((string)($_POST['sst_historial'] ?? ''));
        $arl_fecha_real = estandar5_date_or_null((string)($_POST['arl_fecha_real'] ?? ''));
        $arl_responsable = trim((string)($_POST['arl_responsable'] ?? ''));
        $arl_historial = trim((string)($_POST['arl_historial'] ?? ''));

        $stmt = $conn->prepare("SELECT COUNT(*) FROM estandar5_restricciones_recomendaciones WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$registro_id, $empresa_id]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('Selecciona una carta registrada para actualizar seguimiento.');
        }

        $stmt = $conn->prepare("
            UPDATE estandar5_restricciones_recomendaciones
            SET carta_firmada = ?, fecha_entrega_carta = ?, sst_fecha_programada = ?, sst_fecha_real = ?,
                sst_responsable = ?, sst_estado = ?, sst_historial = ?, arl_fecha_real = ?,
                arl_responsable = ?, arl_historial = ?
            WHERE id = ? AND empresa_id = ?
        ");
        $stmt->execute([
            $carta_firmada,
            $fecha_entrega_carta,
            $sst_fecha_programada,
            $sst_fecha_real,
            $sst_responsable,
            $sst_estado,
            $sst_historial,
            $arl_fecha_real,
            $arl_responsable,
            $arl_historial,
            $registro_id,
            $empresa_id,
        ]);

        estandar5_redirect('Seguimiento actualizado sobre la carta existente.', 'ok', 'restricciones');
    }
    estandar5_redirect('Acción no reconocida.', 'error');
} catch (Throwable $e) {
    if ($accion === 'guardar_custodia_historia_clinica') {
        $modulo_error = 'historias-clinicas';
    } elseif (in_array($accion, ['guardar_restriccion_recomendacion', 'crear_carta_recomendacion_medica', 'actualizar_seguimiento_restriccion'], true)) {
        $modulo_error = 'restricciones';
    } elseif (in_array($accion, ['programar_evaluacion_medica', 'guardar_soporte_evaluacion_medica'], true)) {
        $modulo_error = 'evaluaciones-medicas';
    } else {
        $modulo_error = 'perfiles-cargo';
    }
    estandar5_redirect($e->getMessage(), 'error', $modulo_error);
}
