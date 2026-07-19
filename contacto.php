<?php
session_start();

require_once __DIR__ . '/config/env.php';
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(24));
}

$mensaje_exito = '';
$mensaje_error = '';
$form = [
    'nombre' => '',
    'empresa' => '',
    'email' => '',
    'telefono' => '',
    'motivo' => 'Información de planes',
    'mensaje' => '',
];

function contact_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function contact_send_email(array $data): void
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        throw new RuntimeException('El servicio de correo no está disponible.');
    }

    $smtpUser = (string)(getenv('SMTP_USER') ?: '');
    $smtpPass = (string)(getenv('SMTP_PASS') ?: '');
    if ($smtpUser === '' || $smtpPass === '') {
        throw new RuntimeException('El canal de contacto está temporalmente sin configuración.');
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
    $mail->SMTPSecure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls')) === 'ssl'
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(getenv('SMTP_FROM_ADDRESS') ?: $smtpUser, getenv('SMTP_FROM_NAME') ?: 'PreventWork');
    $mail->addAddress('ArturoOspina@vertixtecnosas.com.co', 'Arturo Ospina');
    $mail->addCC('EstebanReuto@vertixtecnosas.com.co', 'Esteban Reuto');
    $mail->addReplyTo($data['email'], $data['nombre']);
    $mail->isHTML(true);
    $mail->Subject = 'Nueva solicitud de información PreventWork - ' . $data['motivo'];

    $rows = [
        'Nombre' => $data['nombre'],
        'Empresa' => $data['empresa'] ?: 'No indicada',
        'Correo' => $data['email'],
        'Teléfono' => $data['telefono'] ?: 'No indicado',
        'Interés' => $data['motivo'],
    ];
    $table = '';
    foreach ($rows as $label => $value) {
        $table .= '<tr><td style="padding:8px;border:1px solid #dbe3ec"><b>' . contact_h($label) . '</b></td><td style="padding:8px;border:1px solid #dbe3ec">' . contact_h($value) . '</td></tr>';
    }
    $mail->Body = '<div style="max-width:640px;font-family:Arial,sans-serif;color:#1e293b;line-height:1.55">'
        . '<h2 style="color:#102a67">Nueva solicitud de información</h2>'
        . '<p>Un prospecto completó el formulario comercial de PreventWork.</p>'
        . '<table style="width:100%;border-collapse:collapse">' . $table . '</table>'
        . '<h3 style="margin-top:20px;color:#102a67">Mensaje</h3>'
        . '<p style="padding:12px;border-radius:8px;background:#f8fafc">' . nl2br(contact_h($data['mensaje'])) . '</p>'
        . '</div>';
    $mail->AltBody = "Nueva solicitud de información PreventWork\n"
        . 'Nombre: ' . $data['nombre'] . "\nEmpresa: " . ($data['empresa'] ?: 'No indicada')
        . "\nCorreo: " . $data['email'] . "\nTeléfono: " . ($data['telefono'] ?: 'No indicado')
        . "\nInterés: " . $data['motivo'] . "\n\nMensaje:\n" . $data['mensaje'];
    $mail->send();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_contacto'])) {
    foreach (array_keys($form) as $key) {
        $form[$key] = trim((string)($_POST[$key] ?? $form[$key]));
    }

    $csrf = (string)($_POST['csrf'] ?? '');
    $honeypot = trim((string)($_POST['website'] ?? ''));
    $allowedReasons = ['Información de planes', 'Solicitar una demostración', 'Implementación en mi empresa', 'Soporte de la plataforma', 'Otro'];

    if ($honeypot !== '') {
        $mensaje_error = 'No pudimos procesar la solicitud.';
    } elseif (!hash_equals((string)$_SESSION['contact_csrf'], $csrf)) {
        $mensaje_error = 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.';
    } elseif ($form['nombre'] === '' || $form['email'] === '' || $form['mensaje'] === '') {
        $mensaje_error = 'Completa el nombre, correo y mensaje para poder contactarte.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Ingresa un correo electrónico válido.';
    } elseif (!in_array($form['motivo'], $allowedReasons, true)) {
        $mensaje_error = 'Selecciona un motivo de contacto válido.';
    } elseif ((int)($_SESSION['contact_last_submit'] ?? 0) > (time() - 20)) {
        $mensaje_error = 'Espera unos segundos antes de enviar otra solicitud.';
    } else {
        try {
            contact_send_email($form);
            $_SESSION['contact_last_submit'] = time();
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(24));
            $mensaje_exito = 'Gracias, <strong>' . contact_h($form['nombre']) . '</strong>. Recibimos tu solicitud y nuestro equipo comercial se comunicará contigo.';
            $form = ['nombre' => '', 'empresa' => '', 'email' => '', 'telefono' => '', 'motivo' => 'Información de planes', 'mensaje' => ''];
        } catch (Throwable $e) {
            error_log('Contacto PreventWork: ' . $e->getMessage());
            $mensaje_error = 'No pudimos enviar la solicitud en este momento. También puedes escribirnos directamente por WhatsApp.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicita información | PreventWork</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--orange:#ff7a00;--orange-soft:#fff3e8;--navy:#102a67;--blue:#2563eb;--green:#059669;--text:#1e293b;--muted:#64748b;--border:#dbe3ec;--bg:#f3f7fb}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:linear-gradient(180deg,#edf4fb,#f8fafc);color:var(--text);font-family:Inter,sans-serif;overflow-x:hidden}.contact-page{padding:132px 20px 64px}.contact-shell{width:min(1160px,100%);margin:auto}.contact-intro{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:28px;align-items:end;margin-bottom:16px;padding:26px 28px;border:1px solid var(--border);border-radius:16px;background:linear-gradient(135deg,#fff 0%,#f7fbff 72%,#fff7ed 100%);box-shadow:0 14px 35px rgba(15,23,42,.06)}.contact-intro>*{position:relative;z-index:1}.contact-intro-watermark{position:absolute;right:25%;bottom:-62px;color:var(--blue);opacity:.035;font-size:190px;transform:rotate(-12deg)}.contact-kicker{display:inline-flex;align-items:center;gap:7px;padding:6px 9px;border:1px solid #fed7aa;border-radius:99px;background:#fff7ed;color:#c2410c;font-size:.61rem;font-weight:850;text-transform:uppercase}.contact-intro h1{max-width:680px;margin:13px 0 7px;color:var(--navy);font-size:clamp(1.65rem,3vw,2.35rem);line-height:1.12;letter-spacing:-.03em}.contact-intro p{max-width:680px;margin:0;color:var(--muted);font-size:.78rem;line-height:1.6}.contact-intro-proof{display:grid;grid-template-columns:repeat(3,auto);gap:7px}.contact-intro-proof span{min-width:112px;padding:9px;border:1px solid #e2e8f0;border-radius:9px;background:rgba(255,255,255,.85)}.contact-intro-proof i,.contact-intro-proof strong,.contact-intro-proof small{display:block}.contact-intro-proof i{margin-bottom:7px;color:var(--orange);font-size:.72rem}.contact-intro-proof strong{color:var(--navy);font-size:.57rem}.contact-intro-proof small{margin-top:2px;color:var(--muted);font-size:.48rem}.contact-grid{display:grid;grid-template-columns:minmax(280px,.72fr) minmax(0,1.28fr);gap:16px;align-items:start}.contact-side,.contact-form-card{border:1px solid var(--border);border-radius:14px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.045)}.contact-side{overflow:hidden}.contact-side-head{position:relative;overflow:hidden;padding:21px;background:linear-gradient(145deg,#102a67,#173d8c);color:#fff}.contact-side-head>i{position:absolute;right:-12px;bottom:-24px;color:#fff;opacity:.05;font-size:100px;transform:rotate(-8deg)}.contact-side-head span{color:#fdba74;font-size:.55rem;font-weight:850;text-transform:uppercase}.contact-side-head h2{margin:6px 0 5px;font-size:1.05rem}.contact-side-head p{margin:0;color:#dbeafe;font-size:.65rem;line-height:1.5}.contact-actions{display:grid;gap:7px;padding:12px}.contact-action{display:grid;grid-template-columns:35px 1fr auto;gap:9px;align-items:center;min-height:55px;padding:8px;border:1px solid #e2e8f0;border-radius:9px;color:inherit;text-decoration:none;transition:.2s ease}.contact-action:hover{border-color:#fdba74;background:#fffaf5;transform:translateY(-1px)}.contact-action>i{width:35px;height:35px;display:grid;place-items:center;border-radius:8px;background:var(--orange-soft);color:var(--orange);font-size:.75rem}.contact-action strong,.contact-action span{display:block}.contact-action strong{color:var(--navy);font-size:.63rem}.contact-action span{margin-top:2px;color:var(--muted);font-size:.52rem}.contact-action>small{color:#94a3b8;font-size:.5rem}.contact-next{padding:4px 20px 19px}.contact-next>strong{display:block;padding-top:13px;border-top:1px solid #edf2f7;color:var(--navy);font-size:.62rem}.contact-next ol{display:grid;gap:9px;margin:12px 0 0;padding:0;list-style:none;counter-reset:steps}.contact-next li{counter-increment:steps;display:grid;grid-template-columns:23px 1fr;gap:8px;align-items:start;color:var(--muted);font-size:.54rem;line-height:1.4}.contact-next li:before{content:counter(steps);width:23px;height:23px;display:grid;place-items:center;border-radius:7px;background:#eff6ff;color:#2563eb;font-size:.49rem;font-weight:900}.contact-form-card{padding:23px}.form-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:17px}.form-heading h2{margin:0;color:var(--navy);font-size:1.05rem}.form-heading p{margin:4px 0 0;color:var(--muted);font-size:.62rem;line-height:1.5}.form-heading>span{flex:none;padding:6px 8px;border-radius:99px;background:#ecfdf5;color:#047857;font-size:.5rem;font-weight:850}.alert-box{display:flex;gap:8px;margin-bottom:14px;padding:10px 11px;border:1px solid;border-radius:9px;font-size:.61rem;line-height:1.45}.alert-box.success{border-color:#bbf7d0;background:#ecfdf5;color:#047857}.alert-box.error{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.contact-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.form-group{display:grid;gap:5px}.form-group.full{grid-column:1/-1}.form-label{color:#334155;font-size:.55rem;font-weight:850;text-transform:uppercase}.form-control{width:100%;height:42px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;padding:0 11px;color:#0f172a;font:inherit;font-size:.66rem;outline:0;transition:.2s ease}.form-control:focus{border-color:#fb923c;background:#fff;box-shadow:0 0 0 3px rgba(251,146,60,.12)}textarea.form-control{height:auto;min-height:88px;padding-top:10px;resize:vertical;line-height:1.5}.contact-hp{position:absolute!important;left:-9999px!important;opacity:0!important;pointer-events:none!important}.form-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:14px;padding-top:4px}.form-footer small{max-width:390px;color:#94a3b8;font-size:.5rem;line-height:1.45}.form-footer small i{color:#16a34a}.btn-submit{flex:none;min-height:41px;border:0;border-radius:8px;background:linear-gradient(135deg,#ff8a1f,#ff7a00);color:#fff;padding:0 14px;font:inherit;font-size:.63rem;font-weight:850;cursor:pointer;box-shadow:0 8px 18px rgba(255,122,0,.2);transition:.2s ease}.btn-submit:hover{transform:translateY(-2px);box-shadow:0 11px 24px rgba(255,122,0,.27)}@media(max-width:980px){.contact-intro{grid-template-columns:1fr;align-items:start}.contact-intro-proof{grid-template-columns:repeat(3,minmax(0,1fr))}.contact-grid{grid-template-columns:1fr}.contact-side{display:grid;grid-template-columns:.8fr 1.2fr}.contact-side-head{min-height:100%}.contact-next{padding-top:12px}}@media(max-width:680px){.contact-page{padding:124px 12px 42px}.contact-intro{padding:20px;border-radius:13px}.contact-intro-proof{grid-template-columns:1fr}.contact-intro-proof span{display:grid;grid-template-columns:20px 1fr;column-gap:7px;align-items:center}.contact-intro-proof i{grid-row:1/3;margin:0}.contact-side{display:block}.contact-form-card{padding:18px}.contact-form{grid-template-columns:1fr}.form-group.full{grid-column:auto}.form-heading{flex-direction:column}.form-footer{grid-column:auto;align-items:stretch;flex-direction:column}.btn-submit{width:100%}.contact-intro h1{font-size:1.65rem}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
<?php include 'components/public_header.php'; ?>

<main class="contact-page">
    <div class="contact-shell">
        <section class="contact-intro">
            <i class="fa-solid fa-comments contact-intro-watermark" aria-hidden="true"></i>
            <div>
                <span class="contact-kicker"><i class="fa-solid fa-headset"></i> Hablemos de tu empresa</span>
                <h1>Recibe información clara para elegir PreventWork</h1>
                <p>Cuéntanos qué necesita tu organización. Te ayudamos a entender los planes, conocer la plataforma y definir el siguiente paso sin compromisos.</p>
            </div>
            <div class="contact-intro-proof">
                <span><i class="fa-solid fa-message"></i><strong>Respuesta personal</strong><small>Equipo comercial</small></span>
                <span><i class="fa-solid fa-display"></i><strong>Demo guiada</strong><small>Según tu empresa</small></span>
                <span><i class="fa-solid fa-location-dot"></i><strong>Cobertura nacional</strong><small>Atención en Colombia</small></span>
            </div>
        </section>

        <section class="contact-grid">
            <aside class="contact-side">
                <div>
                    <div class="contact-side-head"><span>Canales directos</span><h2>Habla con PreventWork</h2><p>Elige el canal que te resulte más cómodo.</p><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></div>
                    <div class="contact-actions">
                        <a class="contact-action" href="https://wa.me/573012994599?text=Hola%20PreventWork%2C%20quiero%20recibir%20informaci%C3%B3n" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i><div><strong>WhatsApp comercial</strong><span>+57 301 299 4599</span></div><small>Escribir</small></a>
                        <a class="contact-action" href="mailto:soporte@preventwork.com?subject=Solicitud%20de%20informaci%C3%B3n"><i class="fa-regular fa-envelope"></i><div><strong>Correo electrónico</strong><span>soporte@preventwork.com</span></div><small>Enviar</small></a>
                        <div class="contact-action"><i class="fa-regular fa-clock"></i><div><strong>Horario de atención</strong><span>Lunes a viernes · 8:00 a. m. – 6:00 p. m.</span></div><small>Colombia</small></div>
                    </div>
                </div>
                <div class="contact-next"><strong>¿Qué pasa después?</strong><ol><li>Revisamos la necesidad de tu empresa.</li><li>Te explicamos el plan y las funciones adecuadas.</li><li>Coordinamos una demo o propuesta si estás interesado.</li></ol></div>
            </aside>

            <section class="contact-form-card">
                <div class="form-heading"><div><h2>Solicita más información</h2><p>Déjanos tus datos y cuéntanos brevemente qué deseas conocer.</p></div><span><i class="fa-solid fa-shield-halved"></i> Datos protegidos</span></div>

                <?php if ($mensaje_exito !== ''): ?><div class="alert-box success"><i class="fa-solid fa-circle-check"></i><span><?php echo $mensaje_exito; ?></span></div><?php endif; ?>
                <?php if ($mensaje_error !== ''): ?><div class="alert-box error"><i class="fa-solid fa-triangle-exclamation"></i><span><?php echo contact_h($mensaje_error); ?></span></div><?php endif; ?>

                <form class="contact-form" method="post" action="contacto">
                    <input type="hidden" name="enviar_contacto" value="1">
                    <input type="hidden" name="csrf" value="<?php echo contact_h($_SESSION['contact_csrf']); ?>">
                    <label class="contact-hp" aria-hidden="true">Sitio web<input type="text" name="website" tabindex="-1" autocomplete="off"></label>

                    <div class="form-group"><label class="form-label" for="nombre">Nombre completo *</label><input id="nombre" name="nombre" class="form-control" maxlength="150" required value="<?php echo contact_h($form['nombre']); ?>" placeholder="Ej. Laura Gómez"></div>
                    <div class="form-group"><label class="form-label" for="empresa">Empresa</label><input id="empresa" name="empresa" class="form-control" maxlength="180" value="<?php echo contact_h($form['empresa']); ?>" placeholder="Nombre de la organización"></div>
                    <div class="form-group"><label class="form-label" for="email">Correo corporativo *</label><input id="email" type="email" name="email" class="form-control" maxlength="180" required value="<?php echo contact_h($form['email']); ?>" placeholder="nombre@empresa.com"></div>
                    <div class="form-group"><label class="form-label" for="telefono">Teléfono o WhatsApp</label><input id="telefono" type="tel" name="telefono" class="form-control" maxlength="40" value="<?php echo contact_h($form['telefono']); ?>" placeholder="300 000 0000"></div>
                    <div class="form-group full"><label class="form-label" for="motivo">¿Qué deseas conocer? *</label><select id="motivo" name="motivo" class="form-control" required><?php foreach (['Información de planes','Solicitar una demostración','Implementación en mi empresa','Soporte de la plataforma','Otro'] as $reason): ?><option value="<?php echo contact_h($reason); ?>" <?php echo $form['motivo'] === $reason ? 'selected' : ''; ?>><?php echo contact_h($reason); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group full"><label class="form-label" for="mensaje">Cuéntanos qué necesitas *</label><textarea id="mensaje" name="mensaje" class="form-control" maxlength="1500" required placeholder="Ej. Quiero conocer qué plan aplica para una empresa de 35 trabajadores..."><?php echo contact_h($form['mensaje']); ?></textarea></div>

                    <div class="form-footer"><small><i class="fa-solid fa-lock"></i> Usaremos tus datos únicamente para responder esta solicitud comercial.</small><button class="btn-submit" type="submit">Solicitar información <i class="fa-solid fa-paper-plane"></i></button></div>
                </form>
            </section>
        </section>
    </div>
</main>

<?php include 'components/public_footer.php'; ?>
<?php include 'components/floating_buttons.php'; ?>
</body>
</html>
