<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Cookies | PreventWork SG-SST</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #ff8a1f; --primary2: #ff7a00;
            --bg-top: #e8f0f8; --bg-mid: #f3f7fb; --bg-bottom: #ffffff;
            --blue-main: #2b5a9e; --blue-dark: #1e3a8a;
            --card-bg: rgba(255, 255, 255, 0.7); 
            --card-border: rgba(255, 255, 255, 0.6);
            --text-main: #1f2d3d; --text-muted: #5f6f82; 
            --border: #dbe3ec; --radius: 10px;
        }

        * { box-sizing: border-box; }

        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%); 
            color: var(--text-main); 
            font-size: 0.88rem;
            line-height: 1.55;
            overflow-x: hidden;
            position: relative;
        }

        /* FONDOS Y MARCAS DE AGUA CORPORATIVAS */
        .watermark-bg { position: fixed; top: 75%; left: 20%; transform: translate(-50%, -50%) rotate(-15deg); font-size: 40vh; color: var(--blue-main); opacity: 0.03; z-index: -2; pointer-events: none; }
        
        .blob { position: fixed; border-radius: 50%; filter: blur(80px); z-index: -1; opacity: 0.3; animation: float 12s infinite ease-in-out alternate; }
        .blob-1 { top: -5%; left: -5%; width: 35vw; height: 35vw; background: var(--blue-main); }
        .blob-2 { bottom: -10%; right: -5%; width: 40vw; height: 40vw; background: var(--primary); animation-delay: -6s; }
        @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(30px) scale(1.05); } }

        .fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* HEADER PÚBLICO ULTRA LIMPIO Y COMPACTO */
        .public-header {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 10px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .logo-link img { 
            height: 24px; 
            width: auto; 
            transition: transform 0.2s; 
            display: block;
        }
        .logo-link:hover img { transform: scale(1.02); }

        /* MENÚ DE NAVEGACIÓN LEGAL */
        .public-nav {
            display: flex;
            gap: 16px;
            align-items: center;
            flex: 1;
            justify-content: center;
        }
        .public-nav a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
            padding: 6px 10px;
            border-radius: 6px;
        }
        .public-nav a:hover { color: var(--blue-dark); background: rgba(255,255,255,0.6); }
        .public-nav a.active { color: var(--primary2); font-weight: 700; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.03);}

        .btn-back {
            background: rgba(255,255,255,0.9);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.75rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .btn-back:hover { background: #fff; color: var(--blue-dark); border-color: var(--blue-main); box-shadow: 0 2px 8px rgba(0,0,0,0.04);}

        /* HERO MINIMALISTA */
        .hero-legal {
            padding: 30px 20px 20px;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }
        .hero-legal h1 {
            font-size: 1.8rem;
            color: var(--blue-dark);
            font-weight: 800;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }
        .hero-legal p { color: var(--text-muted); font-size: 0.9rem; margin: 0; font-weight: 500; }

        /* LAYOUT DE DOS COLUMNAS COMPACTAS */
        .legal-layout {
            max-width: 1200px;
            margin: 0 auto 50px auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* NAVEGACIÓN LATERAL DISCRETA */
        .toc-sidebar {
            position: sticky;
            top: 70px;
            padding: 10px;
        }
        .toc-sidebar h3 {
            margin: 0 0 12px 10px;
            color: var(--blue-dark);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.8;
        }
        .toc-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.2s;
            display: block;
            margin-bottom: 2px;
        }
        .toc-link:hover { color: var(--primary2); background: rgba(255,255,255,0.4); }
        .toc-link.active {
            background: white;
            color: var(--primary2);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            border-left: 3px solid var(--primary);
        }

        /* CONTENIDO LEGAL REFINADO Y COMPACTO */
        .term-section {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 24px 30px; 
            margin-bottom: 16px;
            scroll-margin-top: 70px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }
        .term-section:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }
        
        .term-section h2 {
            font-size: 1.1rem; 
            color: var(--blue-dark);
            font-weight: 800;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .icon-section { 
            background: rgba(43, 90, 158, 0.1);
            color: var(--blue-main);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .term-section p, .term-section li {
            font-size: 0.88rem;
            color: #334155;
            margin-bottom: 10px; 
            line-height: 1.55;
            text-align: justify;
        }

        .term-section ul { padding-left: 20px; margin-bottom: 14px; margin-top: 4px; }
        .term-section li { margin-bottom: 6px; }
        .term-section strong { color: var(--blue-dark); font-weight: 700; }

        .highlight-box {
            background: #fff8f3;
            border-left: 3px solid var(--primary);
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            margin: 16px 0;
        }
        .highlight-box p { margin: 0; color: #9a3412; font-size: 0.85rem; font-weight: 600; text-align: left;}

        /* BOTONES FLOTANTES */
        .floating-btn {
            position: fixed;
            bottom: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        
        .wa-btn {
            left: 24px;
            background: #25D366; 
        }
        .wa-btn:hover { background: #1ebc5b; transform: scale(1.1) rotate(5deg); }
        .wa-btn i { font-size: 1.6rem; }

        .top-btn {
            right: 24px;
            background: var(--blue-main);
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
        }
        .top-btn i { font-size: 1.2rem; }
        .top-btn.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .top-btn.visible:hover { background: var(--blue-dark); transform: translateY(-5px); }

        /* MEDIA QUERIES */
        @media (max-width: 900px) {
            .legal-layout { grid-template-columns: 1fr; padding: 0 20px; }
            .toc-sidebar { display: none; }
            
            .public-header { flex-wrap: wrap; justify-content: center; padding: 12px 16px; }
            .public-nav { order: 3; width: 100%; flex-wrap: wrap; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); }
            
            .floating-btn { width: 44px; height: 44px; bottom: 16px; }
            .wa-btn { left: 16px; }
            .top-btn { right: 16px; }
            .wa-btn i { font-size: 1.4rem; }
            .top-btn i { font-size: 1rem; }
        }
        @media (max-width: 600px) {
            .hero-legal { padding: 30px 16px 16px 16px; }
            .hero-legal h1 { font-size: 1.6rem; }
            .term-section { padding: 20px 16px; margin-bottom: 12px;}
            .public-nav a { font-size: 0.75rem; padding: 4px 8px;}
        }
    </style>
</head>
<body>

    <i class="fa-solid fa-helmet-safety watermark-bg"></i>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <header class="public-header">
        <a href="index.php" class="logo-link">
            <img src="assets/logo_preventwork.png" alt="PreventWork Logo" onerror="this.outerHTML='<span style=\'font-weight:800; color:#1e3a8a; font-size:1.1rem;\'>SG-SST <span style=\'color:#ff8a1f\'>Pro</span></span>'">
        </a>
        
        <nav class="public-nav">
            <a href="terminos.php">Términos y Condiciones</a>
            <a href="privacidad.php">Política de Privacidad</a>
            <a href="cookies.php" class="active">Política de Cookies</a>
            <a href="faq.php">Preguntas Frecuentes (FAQ)</a>
        </nav>

        <a href="register.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Registro
        </a>
    </header>

    <div class="hero-legal fade-in-up">
        <h1>Política de Cookies</h1>
        <p>Aprende cómo usamos las cookies para mejorar tu experiencia en la plataforma.</p>
    </div>

    <main class="legal-layout">
        
        <aside class="toc-sidebar fade-in-up" style="animation-delay: 0.1s">
            <h3>Contenido</h3>
            <nav id="tocMenu">
                <a href="#sec-que-son" class="toc-link active">1. ¿Qué son las Cookies?</a>
                <a href="#sec-tipos" class="toc-link">2. Tipos de Cookies que Usamos</a>
                <a href="#sec-terceros" class="toc-link">3. Cookies de Terceros</a>
                <a href="#sec-consentimiento" class="toc-link">4. Consentimiento</a>
                <a href="#sec-gestion" class="toc-link">5. Gestión y Deshabilitación</a>
                <a href="#sec-actualizaciones" class="toc-link">6. Actualizaciones</a>
            </nav>
        </aside>

        <div class="legal-content fade-in-up" style="animation-delay: 0.2s">
            
            <section id="sec-que-son" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-cookie-bite"></i></div> 1. ¿Qué son las Cookies?</h2>
                <p>Las cookies son pequeños archivos de texto que se almacenan en su navegador o dispositivo (computadora, tablet, teléfono móvil) cuando visita un sitio web o utiliza una aplicación. Su función principal es permitir que la plataforma "recuerde" sus acciones y preferencias durante un período de tiempo.</p>
                <p>En <strong>PreventWork SG-SST Pro</strong>, utilizamos cookies y tecnologías similares para garantizar el funcionamiento correcto de la plataforma, mejorar su seguridad (como el manejo del doble factor de autenticación) y ofrecerle una experiencia más fluida.</p>
            </section>

            <section id="sec-tipos" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-layer-group"></i></div> 2. Tipos de Cookies que Utilizamos</h2>
                <p>Nuestra plataforma SaaS utiliza principalmente los siguientes tipos de cookies para su funcionamiento:</p>
                <ul>
                    <li><strong>Cookies Estrictamente Necesarias (Técnicas):</strong> Son fundamentales para que la plataforma funcione. Incluyen las cookies que permiten iniciar sesión en áreas seguras, mantener su sesión activa, utilizar la función de "Mantener sesión iniciada" y gestionar el proceso de seguridad 2FA (Doble Factor de Autenticación). No pueden ser desactivadas.</li>
                    <li><strong>Cookies de Personalización:</strong> Permiten a la plataforma recordar información que cambia el aspecto o comportamiento del software, como los datos pre-cargados para evitar que escriba la misma información (Ej: su número de cédula o NIT) de forma repetitiva.</li>
                    <li><strong>Cookies de Seguridad:</strong> Empleadas para prevenir fraudes, proteger sus datos, detectar accesos no autorizados e interrumpir bucles infinitos en el inicio de sesión.</li>
                </ul>
            </section>

            <section id="sec-terceros" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-globe"></i></div> 3. Cookies de Terceros</h2>
                <p>En algunos casos, utilizamos herramientas proporcionadas por terceros confiables que también pueden emplear cookies. En PreventWork, las cookies de terceros se asocian principalmente a:</p>
                <ul>
                    <li><strong>Pasarela de Pagos (Wompi):</strong> Al realizar el pago de su suscripción, la pasarela de pagos Wompi (Bancolombia) puede instalar cookies en su dispositivo para procesar la transacción de forma segura y evitar fraudes.</li>
                    <li><strong>Librerías Externas:</strong> Utilizamos servicios y librerías externas de diseño (como íconos y fuentes) que, aunque no rastrean su identidad personal, pueden registrar información básica del servidor y el navegador.</li>
                </ul>
            </section>

            <section id="sec-consentimiento" class="term-section">
                <h2><div class="icon-section"><i class="fa-regular fa-thumbs-up"></i></div> 4. Consentimiento</h2>
                <p>Al ingresar a nuestra plataforma y visualizar el banner informativo de cookies, o al marcar activamente funciones como "Mantener sesión iniciada", usted nos otorga su consentimiento para el uso de estas tecnologías conforme a lo establecido en la presente política.</p>
                <div class="highlight-box">
                    <p><i class="fa-solid fa-info-circle"></i> Tenga en cuenta: Dado que la mayoría de nuestras cookies son de índole técnica y necesarias para la operatividad del software SaaS, rechazar su instalación puede impedirle el acceso a su panel de control o interrumpir los flujos de firma.</p>
                </div>
            </section>

            <section id="sec-gestion" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-sliders"></i></div> 5. Gestión y Deshabilitación de Cookies</h2>
                <p>Usted tiene el control sobre las cookies que se instalan en su dispositivo. Puede configurar su navegador web para rechazar todas las cookies, para que le avise cuando se envía una cookie o para borrarlas una vez finalizada su sesión. A continuación, encontrará enlaces a la documentación oficial para la gestión de cookies en los navegadores más comunes:</p>
                <ul>
                    <li><strong>Google Chrome:</strong> Configuración > Privacidad y seguridad > Cookies y otros datos de sitios.</li>
                    <li><strong>Mozilla Firefox:</strong> Opciones > Privacidad & Seguridad > Cookies y datos del sitio.</li>
                    <li><strong>Safari (Apple):</strong> Preferencias > Privacidad > Bloquear todas las cookies.</li>
                </ul>
                <p>Recuerde que deshabilitar las cookies de sesión (como <code>PHPSESSID</code> o <code>sgsst_remember</code>) hará imposible que inicie sesión y trabaje dentro del sistema de PreventWork.</p>
            </section>

            <section id="sec-actualizaciones" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-rotate"></i></div> 6. Actualizaciones de esta Política</h2>
                <p>Podemos actualizar esta Política de Cookies ocasionalmente para reflejar, por ejemplo, cambios en las tecnologías de rastreo que utilizamos o por razones operativas, legales o reglamentarias. Por favor, visite esta página con regularidad para mantenerse informado sobre nuestro uso de cookies y tecnologías relacionadas.</p>
            </section>

        </div>
    </main>

    <a href="https://wa.me/573000000000?text=Hola,%20tengo%20una%20duda%20sobre%20PreventWork" class="floating-btn wa-btn" target="_blank" title="Contáctanos por WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
    
    <button class="floating-btn top-btn" id="btnScrollTop" title="Volver arriba">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <?php include 'components/footer.php'; ?>

    <script>
        // FUNCIÓN MATEMÁTICA PARA SCROLL LENTO Y SUAVE (EaseInOutQuad)
        function slowScrollTo(targetPosition, duration) {
            const startPosition = window.pageYOffset;
            const distance = targetPosition - startPosition;
            let startTime = null;

            function animation(currentTime) {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                const run = easeInOutQuad(timeElapsed, startPosition, distance, duration);
                window.scrollTo(0, run);
                if (timeElapsed < duration) requestAnimationFrame(animation);
            }

            function easeInOutQuad(t, b, c, d) {
                t /= d / 2;
                if (t < 1) return c / 2 * t * t + b;
                t--;
                return -c / 2 * (t * (t - 2) - 1) + b;
            }

            requestAnimationFrame(animation);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.term-section');
            const navLinks = document.querySelectorAll('.toc-link');
            const btnScrollTop = document.getElementById('btnScrollTop');

            // 1. SCROLLSPY (Actualiza el menú al bajar)
            window.addEventListener('scroll', () => {
                let current = "";
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    if (pageYOffset >= sectionTop - 150) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href').includes(current)) {
                        link.classList.add('active');
                    }
                });

                // 2. MOSTRAR/OCULTAR BOTÓN SUBIR
                if (window.pageYOffset > 300) {
                    btnScrollTop.classList.add('visible');
                } else {
                    btnScrollTop.classList.remove('visible');
                }
            });

            // 3. INTERCEPTAR CLICS DEL MENÚ LATERAL PARA SCROLL LENTO (1 segundo = 1000ms)
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        // El 80 es el espacio del header flotante para que no tape el título
                        const targetPos = targetElement.getBoundingClientRect().top + window.pageYOffset - 80;
                        slowScrollTo(targetPos, 1000); 
                    }
                });
            });

            // 4. CLIC EN EL BOTÓN FLOTANTE "VOLVER ARRIBA" CON SCROLL LENTO
            btnScrollTop.addEventListener('click', function() {
                slowScrollTo(0, 1200); 
            });
        });
    </script>
</body>
</html>