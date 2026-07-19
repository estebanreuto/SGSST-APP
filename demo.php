<?php
session_start();

if (empty($_SESSION['demo_csrf'])) {
    $_SESSION['demo_csrf'] = bin2hex(random_bytes(24));
}

$error = (string)($_SESSION['demo_error'] ?? '');
$old = is_array($_SESSION['demo_old'] ?? null) ? $_SESSION['demo_old'] : [];
$requestSuccess = is_array($_SESSION['demo_request_success'] ?? null) ? $_SESSION['demo_request_success'] : [];
unset($_SESSION['demo_error'], $_SESSION['demo_old'], $_SESSION['demo_request_success']);

function demo_form_value(array $old, string $key): string
{
    return htmlspecialchars((string)($old[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Plan PEM | PreventWork</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--primary:#ff8a1f;--primary2:#ff7a00;--blue-main:#2b5a9e;--blue-dark:#102a67;--text:#1e293b;--muted:#64748b;--border:#dbe3ec;--bg:#eef5fb}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;color:var(--text);background:linear-gradient(180deg,#edf4fb,#f8fafc);min-height:100vh}.demo-entry{padding:150px clamp(16px,5vw,72px) 64px}.demo-entry-shell{width:min(1180px,100%);margin:auto;display:grid;grid-template-columns:minmax(0,1.04fr) minmax(360px,.72fr);gap:22px;align-items:stretch}.demo-showcase,.demo-form-card{position:relative;overflow:hidden;border:1px solid var(--border);border-radius:20px;background:#fff;box-shadow:0 22px 50px rgba(15,23,42,.08)}.demo-showcase{padding:34px;background:linear-gradient(145deg,#fff 0%,#f7fbff 65%,#fff7ed 100%)}.demo-showcase>*,.demo-form-card>*{position:relative;z-index:1}.demo-watermark{position:absolute;right:-25px;bottom:-35px;color:#2563eb;opacity:.035;font-size:220px;transform:rotate(-10deg);z-index:0}.demo-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid #fed7aa;border-radius:99px;background:#fff7ed;color:#c2410c;font-size:.68rem;font-weight:800;text-transform:uppercase}.demo-showcase h1{max-width:720px;margin:18px 0 12px;color:var(--blue-dark);font-size:clamp(1.85rem,3.5vw,3rem);line-height:1.08;letter-spacing:-.035em}.demo-showcase h1 span{color:var(--primary2)}.demo-lead{max-width:680px;margin:0;color:var(--muted);font-size:.92rem;line-height:1.65}.demo-preview{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:26px 0 22px}.demo-preview-card{position:relative;overflow:hidden;min-height:108px;padding:14px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}.demo-preview-card i{color:var(--primary2);font-size:1rem}.demo-preview-card strong{display:block;margin-top:15px;color:var(--blue-dark);font-size:.78rem}.demo-preview-card span{display:block;margin-top:4px;color:var(--muted);font-size:.66rem;line-height:1.4}.demo-preview-card .wm{position:absolute;right:5px;bottom:-15px;color:#2563eb;opacity:.05;font-size:4.5rem;transform:rotate(-8deg)}.demo-trust{display:flex;flex-wrap:wrap;gap:8px}.demo-trust span{display:inline-flex;align-items:center;gap:6px;color:#475569;font-size:.67rem;font-weight:700}.demo-trust i{color:#059669}.demo-form-card{padding:28px}.demo-form-head{margin-bottom:20px}.demo-form-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:10px;background:#fff3e8;color:var(--primary2)}.demo-form-head h2{margin:13px 0 6px;color:var(--blue-dark);font-size:1.08rem}.demo-form-head p{margin:0;color:var(--muted);font-size:.72rem;line-height:1.5}.demo-error{display:flex;gap:8px;margin-bottom:14px;padding:10px 11px;border:1px solid #fecaca;border-radius:9px;background:#fef2f2;color:#b91c1c;font-size:.7rem;font-weight:700}.demo-success{display:grid;place-items:center;min-height:390px;padding:28px 10px;text-align:center}.demo-success>i{width:58px;height:58px;display:grid;place-items:center;border-radius:15px;background:#ecfdf5;color:#059669;font-size:1.35rem}.demo-success h3{margin:16px 0 7px;color:var(--blue-dark);font-size:1.05rem}.demo-success p{max-width:360px;margin:0;color:var(--muted);font-size:.72rem;line-height:1.55}.demo-success strong{color:#334155}.demo-success a{display:inline-flex;align-items:center;gap:7px;margin-top:18px;padding:10px 13px;border:1px solid #dbe3ec;border-radius:8px;color:#334155;text-decoration:none;font-size:.67rem;font-weight:800}.demo-form{display:grid;grid-template-columns:1fr 1fr;gap:12px}.demo-field{display:flex;flex-direction:column;gap:6px}.demo-field.full{grid-column:1/-1}.demo-field label{color:#334155;font-size:.62rem;font-weight:800;text-transform:uppercase}.demo-field input,.demo-field select{width:100%;height:43px;border:1px solid #cbd5e1;border-radius:9px;background:#f8fafc;padding:0 11px;color:#0f172a;font:inherit;font-size:.74rem;outline:none}.demo-field input:focus,.demo-field select:focus{border-color:#fb923c;background:#fff;box-shadow:0 0 0 3px rgba(251,146,60,.12)}.demo-consent{grid-column:1/-1;display:grid;grid-template-columns:18px 1fr;gap:8px;align-items:start;color:#64748b;font-size:.62rem;line-height:1.45}.demo-consent input{width:16px;height:16px;margin:1px 0 0;accent-color:var(--primary2)}.demo-consent a{color:#2563eb}.demo-submit{grid-column:1/-1;min-height:46px;border:0;border-radius:9px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;font:inherit;font-size:.75rem;font-weight:800;cursor:pointer;box-shadow:0 9px 20px rgba(255,122,0,.22);transition:transform .2s ease,box-shadow .2s ease}.demo-submit:hover{transform:translateY(-2px);box-shadow:0 13px 26px rgba(255,122,0,.28)}.demo-hp{position:absolute!important;left:-9999px!important;opacity:0!important;pointer-events:none!important}@media(max-width:900px){.demo-entry{padding-top:132px}.demo-entry-shell{grid-template-columns:1fr}.demo-form-card{order:-1}.demo-showcase{padding:26px}}@media(max-width:560px){.demo-entry{padding:124px 12px 34px}.demo-showcase,.demo-form-card{border-radius:14px}.demo-showcase,.demo-form-card{padding:19px}.demo-form{grid-template-columns:1fr}.demo-field.full,.demo-consent,.demo-submit{grid-column:auto}.demo-preview{grid-template-columns:1fr}.demo-showcase h1{font-size:1.7rem}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important}.demo-submit{transition:none}}
    </style>
</head>
<body>
<?php include 'components/public_header.php'; ?>
<main class="demo-entry">
    <div class="demo-entry-shell">
        <section class="demo-showcase">
            <i class="fa-solid fa-shield-halved demo-watermark"></i>
            <span class="demo-pill"><i class="fa-solid fa-play"></i> Demo interactiva Plan PEM</span>
            <h1>Conoce cómo PreventWork organiza los <span>7 estándares PEM</span></h1>
            <p class="demo-lead">Explora una empresa ficticia con trabajadores, evidencias, indicadores, evaluaciones médicas, matriz de peligros y medidas de control. No necesitas instalar nada y ningún cambio afectará información real.</p>

            <div class="demo-preview">
                <article class="demo-preview-card"><i class="fa-solid fa-gauge-high"></i><strong>Panel ejecutivo</strong><span>Avance, alertas y acciones prioritarias.</span><i class="fa-solid fa-chart-pie wm"></i></article>
                <article class="demo-preview-card"><i class="fa-solid fa-users"></i><strong>Gestión de personal</strong><span>Perfiles, grupos y trazabilidad del trabajador.</span><i class="fa-solid fa-user-group wm"></i></article>
                <article class="demo-preview-card"><i class="fa-solid fa-triangle-exclamation"></i><strong>Matriz IPVR</strong><span>Peligros, valoración y controles según GTC 45.</span><i class="fa-solid fa-shield-virus wm"></i></article>
                <article class="demo-preview-card"><i class="fa-solid fa-file-signature"></i><strong>Evidencias listas</strong><span>Documentos, soportes, firmas y seguimiento.</span><i class="fa-solid fa-file-circle-check wm"></i></article>
            </div>

            <div class="demo-trust">
                <span><i class="fa-solid fa-circle-check"></i> Datos totalmente ficticios</span>
                <span><i class="fa-solid fa-circle-check"></i> Acceso privado previa aprobación</span>
                <span><i class="fa-solid fa-circle-check"></i> Responsive</span>
            </div>
        </section>

        <section class="demo-form-card">
            <?php if ($requestSuccess): ?>
                <div class="demo-success">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                    <h3>Solicitud recibida</h3>
                    <p>Gracias, <strong><?php echo htmlspecialchars((string)($requestSuccess['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>. Revisaremos el interés de <strong><?php echo htmlspecialchars((string)($requestSuccess['empresa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>. Si aprobamos el acceso, te enviaremos un enlace privado al correo registrado.</p>
                    <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
                </div>
            <?php else: ?>
            <div class="demo-form-head">
                <div class="demo-form-icon"><i class="fa-solid fa-rocket"></i></div>
                <h2>Solicita un acceso privado</h2>
                <p>Cuéntanos sobre tu organización. Nuestro equipo revisará la solicitud y, si es aprobada, generará un enlace personal desde el SuperAdmin.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="demo-error"><i class="fa-solid fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <?php endif; ?>

            <form class="demo-form" action="procesar_demo.php" method="post" autocomplete="on">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['demo_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="demo-hp" aria-hidden="true"><label>Sitio web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="demo-field full"><label for="nombre_completo">Nombre completo *</label><input id="nombre_completo" name="nombre_completo" maxlength="150" required value="<?php echo demo_form_value($old, 'nombre_completo'); ?>" placeholder="Ej. Laura Gómez"></div>
                <div class="demo-field full"><label for="empresa">Empresa *</label><input id="empresa" name="empresa" maxlength="180" required value="<?php echo demo_form_value($old, 'empresa'); ?>" placeholder="Nombre de la organización"></div>
                <div class="demo-field"><label for="email">Correo corporativo *</label><input id="email" type="email" name="email" maxlength="180" required value="<?php echo demo_form_value($old, 'email'); ?>" placeholder="nombre@empresa.com"></div>
                <div class="demo-field"><label for="telefono">WhatsApp o teléfono *</label><input id="telefono" type="tel" name="telefono" maxlength="40" required value="<?php echo demo_form_value($old, 'telefono'); ?>" placeholder="300 000 0000"></div>
                <div class="demo-field"><label for="cargo">Cargo</label><input id="cargo" name="cargo" maxlength="120" value="<?php echo demo_form_value($old, 'cargo'); ?>" placeholder="Responsable SST"></div>
                <div class="demo-field"><label for="cantidad_trabajadores">Trabajadores *</label><select id="cantidad_trabajadores" name="cantidad_trabajadores" required><option value="">Selecciona...</option><?php foreach ([5=>'1 a 10',20=>'11 a 30',40=>'31 a 50',75=>'51 a 100',150=>'101 a 200',300=>'Más de 200'] as $value=>$label): ?><option value="<?php echo $value; ?>" <?php echo ((string)($old['cantidad_trabajadores'] ?? '') === (string)$value) ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
                <div class="demo-field full"><label for="ciudad">Ciudad</label><input id="ciudad" name="ciudad" maxlength="100" value="<?php echo demo_form_value($old, 'ciudad'); ?>" placeholder="Ciudad principal"></div>
                <label class="demo-consent"><input type="checkbox" name="acepta_contacto" value="1" required><span>Acepto que PreventWork trate estos datos para habilitar la demo y contactarme con información comercial. Consulta la <a href="privacidad.php" target="_blank">política de privacidad</a>.</span></label>
                <button class="demo-submit" type="submit">Solicitar acceso a la demo <i class="fa-solid fa-paper-plane"></i></button>
            </form>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php include 'components/public_footer.php'; ?>
</body>
</html>
