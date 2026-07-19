<?php
session_start();

require_once 'config/db.php';
require_once 'config/demo_schema.php';
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function demo_notify_sales_team(array $lead): array
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no está instalado.'];
    }

    $user = (string)(getenv('SMTP_USER') ?: '');
    $pass = (string)(getenv('SMTP_PASS') ?: '');
    if ($user === '' || $pass === '') {
        return [false, 'Faltan credenciales SMTP.'];
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $secure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls'));
        $mail->SMTPSecure = $secure === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(getenv('SMTP_FROM_ADDRESS') ?: $user, getenv('SMTP_FROM_NAME') ?: 'PreventWork');
        $mail->addAddress('ArturoOspina@vertixtecnosas.com.co', 'Arturo Ospina');
        $mail->addCC('EstebanReuto@vertixtecnosas.com.co', 'Esteban Reuto');
        $mail->addReplyTo((string)$lead['email'], (string)$lead['nombre_completo']);
        $mail->isHTML(true);
        $mail->Subject = 'Nueva solicitud de acceso a la demo PreventWork - ' . $lead['empresa'];

        $h = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $adminUrl = demo_app_url('admin/prospectos_demo');
        $mail->Body = '<div style="font-family:Arial,sans-serif;color:#1e293b;line-height:1.55;max-width:640px">'
            . '<h2 style="color:#102a67">Nueva solicitud de demo PreventWork</h2>'
            . '<p>El prospecto completó el formulario público. La demo permanece bloqueada hasta que el SuperAdmin apruebe el acceso.</p>'
            . '<table style="width:100%;border-collapse:collapse">'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Nombre</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['nombre_completo']) . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Empresa</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['empresa']) . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Correo</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['email']) . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Teléfono</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['telefono']) . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Cargo</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['cargo'] ?: 'No indicado') . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Ciudad</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . $h($lead['ciudad'] ?: 'No indicada') . '</td></tr>'
            . '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>Trabajadores</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . (int)$lead['cantidad_trabajadores'] . '</td></tr>'
            . '</table>'
            . '<p style="margin-top:22px"><a href="' . $h($adminUrl) . '" style="display:inline-block;padding:11px 16px;border-radius:8px;background:#ff7a00;color:#fff;text-decoration:none;font-weight:700">Revisar y decidir acceso</a></p>'
            . '</div>';
        $mail->AltBody = 'Nueva solicitud de demo: ' . $lead['nombre_completo'] . ' - ' . $lead['empresa']
            . '. Revisar en ' . $adminUrl;
        $mail->send();
        return [true, 'Correo enviado.'];
    } catch (Throwable $e) {
        return [false, substr($e->getMessage(), 0, 480)];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: demo');
    exit;
}

$old = [
    'nombre_completo' => trim((string)($_POST['nombre_completo'] ?? '')),
    'empresa' => trim((string)($_POST['empresa'] ?? '')),
    'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
    'telefono' => trim((string)($_POST['telefono'] ?? '')),
    'cargo' => trim((string)($_POST['cargo'] ?? '')),
    'ciudad' => trim((string)($_POST['ciudad'] ?? '')),
    'cantidad_trabajadores' => (int)($_POST['cantidad_trabajadores'] ?? 0),
];
$_SESSION['demo_old'] = $old;

$csrf = (string)($_POST['csrf'] ?? '');
if (empty($_SESSION['demo_csrf']) || !hash_equals((string)$_SESSION['demo_csrf'], $csrf)) {
    $_SESSION['demo_error'] = 'La sesión del formulario venció. Intenta nuevamente.';
    header('Location: demo');
    exit;
}
if (trim((string)($_POST['website'] ?? '')) !== '') {
    header('Location: demo');
    exit;
}
if ($old['nombre_completo'] === '' || $old['empresa'] === '' || $old['telefono'] === '' || $old['cantidad_trabajadores'] < 1) {
    $_SESSION['demo_error'] = 'Completa los campos obligatorios para solicitar la demostración.';
    header('Location: demo');
    exit;
}
if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['demo_error'] = 'Ingresa un correo electrónico válido.';
    header('Location: demo');
    exit;
}
if (strlen((string)preg_replace('/\D+/', '', $old['telefono'])) < 7) {
    $_SESSION['demo_error'] = 'Ingresa un teléfono o WhatsApp válido.';
    header('Location: demo');
    exit;
}
if (empty($_POST['acepta_contacto'])) {
    $_SESSION['demo_error'] = 'Debes aceptar el tratamiento de datos para solicitar la demo.';
    header('Location: demo');
    exit;
}

try {
    ensure_demo_prospectos_schema($conn);
    $ip = demo_client_ip();
    $agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $rate = $conn->prepare("SELECT COUNT(*) FROM demo_prospectos WHERE ip_address = ? AND actualizado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $rate->execute([$ip]);
    if ((int)$rate->fetchColumn() >= 8) {
        $_SESSION['demo_error'] = 'Alcanzaste el límite temporal de solicitudes. Intenta nuevamente más tarde.';
        header('Location: demo');
        exit;
    }

    $stmt = $conn->prepare(<<<'SQL'
        INSERT INTO demo_prospectos
            (nombre_completo, empresa, email, telefono, ciudad, cargo, cantidad_trabajadores,
             interes, estado, acceso_estado, origen, paginas_vistas, primera_visita, ultima_visita,
             ip_address, user_agent, acepta_contacto)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Plan PEM', 'nuevo', 'pendiente', 'demo_pem', 0, NOW(), NOW(), ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            nombre_completo = VALUES(nombre_completo), empresa = VALUES(empresa), telefono = VALUES(telefono),
            ciudad = VALUES(ciudad), cargo = VALUES(cargo), cantidad_trabajadores = VALUES(cantidad_trabajadores),
            ultima_visita = NOW(), ip_address = VALUES(ip_address), user_agent = VALUES(user_agent),
            acepta_contacto = 1, estado = IF(estado = 'descartado', 'nuevo', estado),
            acceso_token_hash = IF(acceso_estado = 'aprobado' AND acceso_expira_en > NOW(), acceso_token_hash, NULL),
            acceso_token_sufijo = IF(acceso_estado = 'aprobado' AND acceso_expira_en > NOW(), acceso_token_sufijo, NULL),
            acceso_estado = IF(acceso_estado = 'aprobado' AND acceso_expira_en > NOW(), 'aprobado', 'pendiente'),
            acceso_revocado_en = NULL,
            notificacion_error = NULL
    SQL);
    $stmt->execute([
        $old['nombre_completo'], $old['empresa'], $old['email'], $old['telefono'],
        $old['ciudad'], $old['cargo'], min($old['cantidad_trabajadores'], 10000), $ip, $agent,
    ]);

    $find = $conn->prepare('SELECT * FROM demo_prospectos WHERE email = ? LIMIT 1');
    $find->execute([$old['email']]);
    $lead = $find->fetch(PDO::FETCH_ASSOC);
    if (!$lead) {
        throw new RuntimeException('No se pudo recuperar la solicitud registrada.');
    }

    [$mailOk, $mailMessage] = demo_notify_sales_team($lead);
    $mailUpdate = $conn->prepare('UPDATE demo_prospectos SET notificacion_enviada_en = ?, notificacion_error = ? WHERE id = ?');
    $mailUpdate->execute([$mailOk ? date('Y-m-d H:i:s') : null, $mailOk ? null : $mailMessage, (int)$lead['id']]);

    unset(
        $_SESSION['demo_pem_prospecto_id'], $_SESSION['demo_pem_started_at'],
        $_SESSION['demo_pem_nombre'], $_SESSION['demo_pem_empresa'], $_SESSION['demo_pem_access_hash'],
        $_SESSION['demo_old'], $_SESSION['demo_error']
    );
    $_SESSION['demo_request_success'] = [
        'nombre' => $old['nombre_completo'],
        'empresa' => $old['empresa'],
        'email' => $old['email'],
    ];
    $_SESSION['demo_csrf'] = bin2hex(random_bytes(24));
    header('Location: demo?solicitud=recibida');
    exit;
} catch (Throwable $e) {
    error_log('Solicitud demo PreventWork: ' . $e->getMessage());
    $_SESSION['demo_error'] = 'No pudimos registrar la solicitud en este momento. Intenta nuevamente.';
    header('Location: demo');
    exit;
}
