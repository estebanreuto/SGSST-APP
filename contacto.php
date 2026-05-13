<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

$mensaje_exito = "";
$mensaje_error = "";

// LÓGICA DEL FORMULARIO DE CONTACTO (Simulación de envío)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_contacto'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!empty($nombre) && !empty($email) && !empty($mensaje)) {
        // Aquí en el futuro puedes poner la función mail() de PHP o PHPMailer
        // Por ahora, simulamos que se envió correctamente.
        $mensaje_exito = "¡Gracias por escribirnos, <strong>$nombre</strong>! Hemos recibido tu mensaje y un asesor se comunicará contigo muy pronto.";
    } else {
        $mensaje_error = "Por favor, completa todos los campos obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Contacto | PreventWork SG-SST</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #FAFAFA;
            --surface: #FFFFFF;
            --text-main: #111111;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --primary: #ff8a1f; 
            --primary-dark: #ff7a00;
            --blue-dark: #1e3a8a;
            --blue-main: #2b5a9e;
            --radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background-color: var(--bg-color); color: var(--text-main); line-height: 1.6; overflow-x: hidden; scroll-behavior: smooth; }
        
        /* ⏳ PRELOADER */
        #preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background-color: var(--surface); z-index: 99999;
            display: flex; justify-content: center; align-items: center; transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        .spinner { 
            width: 36px; height: 36px; border: 3px solid rgba(255, 138, 31, 0.2); 
            border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; 
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ANIMACIONES BÁSICAS */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }

        /* ✨ ICONOS ANIMADOS DE FONDO */
        .floating-icon { position: absolute; color: var(--blue-dark); opacity: 0.04; z-index: 0; pointer-events: none; }
        .floating-icon svg { width: 100%; height: 100%; stroke-width: 2; }
        .float-2 { width: 150px; height: 150px; bottom: 10%; right: -50px; animation: floatAnim2 16s ease-in-out infinite alternate; }
        .float-4 { width: 140px; height: 140px; top: 15%; right: 5%; animation: floatAnim4 18s ease-in-out infinite; opacity: 0.04;}

        @keyframes floatAnim2 { 0%, 100% { transform: translateY(0) rotate(15deg) scale(1); } 50% { transform: translateY(40px) rotate(-5deg) scale(0.95); } }
        @keyframes floatAnim4 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(-30px, 30px) rotate(12deg); } }

        /* 👑 HERO SECTION */
        .contact-hero {
            padding: 160px 5% 60px 5%;
            background: radial-gradient(circle at top, #FFFFFF 0%, #FAFAFA 100%);
            text-align: center;
            border-bottom: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        .contact-hero::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#D1D5DB 1px, transparent 1px); background-size: 24px 24px; opacity: 0.4; z-index: 0; pointer-events: none;
        }
        .hero-content { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; }
        
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; background: #fff; border: 1px solid #E5E7EB;
            border-radius: 30px; font-size: 11px; font-weight: 800; color: var(--blue-dark); margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .contact-hero h1 { font-size: 48px; font-weight: 900; letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 20px; color: var(--blue-dark); }
        .contact-hero p { font-size: 16px; color: var(--text-muted); line-height: 1.6; max-width: 600px; margin: 0 auto; font-weight: 500;}

        /* =========================================
           LAYOUT PRINCIPAL: CONTACTO (2 COLUMNAS)
           ========================================= */
        .contact-container {
            max-width: 1200px;
            margin: -30px auto 80px auto;
            padding: 0 5%;
            position: relative;
            z-index: 10;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 40px;
            align-items: start;
        }

        /* LADO IZQUIERDO: INFORMACIÓN */
        .contact-info-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        .info-card:hover { transform: translateY(-3px); border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.04); }

        .info-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(43, 90, 158, 0.1), rgba(43, 90, 158, 0.2));
            color: var(--blue-main);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .info-card.accent .info-icon { background: linear-gradient(135deg, rgba(255, 138, 31, 0.15), rgba(255, 122, 0, 0.25)); color: var(--primary-dark); }

        .info-text h3 { font-size: 1.1rem; font-weight: 800; color: var(--blue-dark); margin: 0 0 6px 0; letter-spacing: -0.01em;}
        .info-text p { font-size: 0.95rem; color: #334155; margin: 0; line-height: 1.5;}
        .info-text a { color: var(--blue-main); text-decoration: none; font-weight: 600; transition: color 0.2s;}
        .info-text a:hover { color: var(--primary); }

        /* LADO DERECHO: FORMULARIO PREMIUM */
        .form-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-top: 4px solid var(--primary);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        }

        .form-header { margin-bottom: 24px; }
        .form-header h2 { font-size: 1.6rem; font-weight: 800; color: var(--blue-dark); margin-bottom: 8px; letter-spacing: -0.02em;}
        .form-header p { font-size: 0.95rem; color: var(--text-muted); margin: 0;}

        /* Alertas */
        .alert-box { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; line-height: 1.4; }
        .alert-box.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;}
        .alert-box.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}
        .alert-box i { margin-top: 3px; font-size: 1.1rem;}

        /* Inputs */
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;}
        
        .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--blue-dark); margin-bottom: 8px; }
        .form-control {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px 16px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        textarea.form-control { resize: vertical; min-height: 120px; }
        
        .form-control:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 138, 31, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 138, 31, 0.25);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 138, 31, 0.35); }
        .btn-submit i { transition: transform 0.3s ease; }
        .btn-submit:hover i { transform: translateX(4px) translateY(-4px); }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .contact-grid { grid-template-columns: 1fr; gap: 30px; }
            .contact-container { margin-top: 0; }
            .form-card { padding: 30px; }
        }
        @media (max-width: 768px) {
            .contact-hero { padding: 140px 5% 40px 5%; }
            .contact-hero h1 { font-size: 36px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .info-card { padding: 20px; }
            .info-icon { width: 40px; height: 40px; font-size: 1.1rem; }
        }
    </style>
</head>
<body>

    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <?php include 'components/public_header.php'; ?>

    <section class="contact-hero">
        <div class="floating-icon float-2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <div class="floating-icon float-4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </div>

        <div class="hero-content animate-up">
            <div class="hero-badge">Contacto PreventWork</div>
            <h1>¿Listo para transformar tu gestión?</h1>
            <p>Escríbenos si tienes dudas sobre nuestros planes, necesitas soporte técnico o deseas una demostración personalizada de la plataforma.</p>
        </div>
    </section>

    <section class="contact-container">
        <div class="contact-grid">
            
            <div class="contact-info-wrapper animate-up delay-1">
                
                <div class="info-card accent">
                    <div class="info-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <div class="info-text">
                        <h3>Línea de Atención</h3>
                        <p>Respuestas rápidas vía WhatsApp.</p>
                        <p><a href="https://wa.me/573012994599" target="_blank">+57 301 299 4599</a></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fa-regular fa-envelope"></i></div>
                    <div class="info-text">
                        <h3>Soporte y Ventas</h3>
                        <p>Escríbenos y te responderemos en breve.</p>
                        <p><a href="mailto:soporte@preventwork.com">soporte@preventwork.com</a></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text">
                        <h3>Ubicación</h3>
                        <p>Operando desde Tame, Arauca para toda Colombia.</p>
                        <p>Tecnología 100% Colombiana.</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="info-text">
                        <h3>Horario de Soporte</h3>
                        <p>Lunes a Viernes: 8:00 AM - 6:00 PM</p>
                        <p>Plataforma SaaS disponible 24/7.</p>
                    </div>
                </div>

            </div>

            <div class="form-card animate-up delay-2">
                <div class="form-header">
                    <h2>Envíanos un mensaje</h2>
                    <p>Completa el formulario y nuestro equipo experto en SG-SST se pondrá en contacto contigo.</p>
                </div>

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert-box success">
                        <i class="fa-solid fa-circle-check"></i> 
                        <span><?php echo $mensaje_exito; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert-box error">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        <span><?php echo $mensaje_error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contacto.php">
                    <input type="hidden" name="enviar_contacto" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre o Empresa *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="Ej: 300 123 4567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control" placeholder="tucorreo@empresa.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">¿En qué podemos ayudarte? *</label>
                        <textarea name="mensaje" class="form-control" placeholder="Escribe tu mensaje, duda o solicitud de demostración aquí..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        Enviar Mensaje <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>

        </div>
    </section>

    <?php include 'components/public_footer.php'; ?>
    <?php include 'components/floating_buttons.php'; ?>

    <script>
        // Ocultar Preloader
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            preloader.style.opacity = '0';
            setTimeout(() => { preloader.style.visibility = 'hidden'; }, 400);
        });
    </script>
</body>
</html>