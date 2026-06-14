<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones | PreventWork SG-SST</title>
    
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

        /* BOTONES FLOTANTES (WASS Y SCROLL-TOP) */
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
            background: #25D366; /* Verde WhatsApp */
        }
        .wa-btn:hover {
            background: #1ebc5b;
            transform: scale(1.1) rotate(5deg);
        }
        .wa-btn i { font-size: 1.6rem; }

        .top-btn {
            right: 24px;
            background: var(--blue-main);
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
        }
        .top-btn i { font-size: 1.2rem; }
        .top-btn.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .top-btn.visible:hover {
            background: var(--blue-dark);
            transform: translateY(-5px);
        }

        /* MEDIA QUERIES PARA ADAPTABILIDAD */
        @media (max-width: 900px) {
            .legal-layout { grid-template-columns: 1fr; padding: 0 20px; }
            .toc-sidebar { display: none; }
            
            .public-header {
                flex-wrap: wrap;
                justify-content: center;
                padding: 12px 16px;
            }
            .public-nav {
                order: 3;
                width: 100%;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed var(--border);
            }
            
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
            <a href="terminos.php" class="active">Términos y Condiciones</a>
            <a href="privacidad.php">Política de Privacidad</a>
            <a href="cookies.php">Política de Cookies</a>
            <a href="faq.php">Preguntas Frecuentes (FAQ)</a>
        </nav>

        <a href="register.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Registro
        </a>
    </header>

    <div class="hero-legal fade-in-up">
        <h1>Términos y Condiciones</h1>
        <p>Políticas de uso de la plataforma para la gestión inteligente de Seguridad y Salud en el Trabajo.</p>
    </div>

    <main class="legal-layout">
        
        <aside class="toc-sidebar fade-in-up" style="animation-delay: 0.1s">
            <h3>Contenido</h3>
            <nav id="tocMenu">
                <a href="#sec-aceptacion" class="toc-link active">1. Aceptación de los Términos</a>
                <a href="#sec-servicio" class="toc-link">2. Descripción del Servicio</a>
                <a href="#sec-roles" class="toc-link">3. Cuentas y Roles</a>
                <a href="#sec-firmas" class="toc-link">4. Validez de Firmas</a>
                <a href="#sec-pagos" class="toc-link">5. Suscripciones y Pagos</a>
                <a href="#sec-privacidad" class="toc-link">6. Tratamiento de Datos</a>
                <a href="#sec-soporte" class="toc-link">7. Soporte Técnico</a>
                <a href="#sec-propiedad" class="toc-link">8. Propiedad Intelectual</a>
            </nav>
        </aside>

        <div class="legal-content fade-in-up" style="animation-delay: 0.2s">
            
            <section id="sec-aceptacion" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-handshake"></i></div> 1. Aceptación de los Términos</h2>
                <p>Al acceder, registrarse y utilizar la plataforma <strong>PreventWork (SG-SST Pro)</strong> (en adelante, "la Plataforma" o "el Servicio"), usted acepta someterse a los presentes Términos y Condiciones. Si no está de acuerdo con alguna parte de estos términos, no deberá utilizar nuestros servicios. La Plataforma está dirigida exclusivamente a empresas, entidades y trabajadores que operan bajo la legislación de la República de Colombia.</p>
            </section>

            <section id="sec-servicio" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-laptop-code"></i></div> 2. Descripción del Servicio</h2>
                <p>PreventWork es una plataforma tecnológica tipo Software as a Service (SaaS) diseñada para facilitar la administración, diseño, ejecución y control del <strong>Sistema de Gestión de Seguridad y Salud en el Trabajo (SG-SST)</strong>, en concordancia con el Decreto 1072 de 2015 y la Resolución 0312 de 2019.</p>
                <p>El Servicio permite la creación de actas, registro de asistencias, gestión documental, recolección de firmas digitales, entre otros. La disponibilidad de módulos específicos dependerá del plan de suscripción adquirido por la Empresa.</p>

                <div class="highlight-box">
                    <p><i class="fa-solid fa-triangle-exclamation"></i> Importante: PreventWork es una herramienta tecnológica. La correcta implementación, veracidad de los datos, ejecución en campo y el cumplimiento legal del SG-SST ante el Ministerio de Trabajo y las ARL son responsabilidad exclusiva y absoluta de la Empresa contratante y su Representante Legal.</p>
                </div>
            </section>

            <section id="sec-roles" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-users"></i></div> 3. Cuentas y Roles de Usuario</h2>
                <p>La plataforma define claramente tres niveles de acceso, cada uno con responsabilidades específicas:</p>
                <ul>
                    <li><strong>Representante Legal:</strong> Es el administrador principal, responsable de la suscripción, facturación y la veracidad de la información corporativa (NIT, Razón Social). Tiene el control absoluto sobre los datos de su empresa en la Plataforma.</li>
                    <li><strong>Responsable SG-SST:</strong> Es el usuario designado por la Empresa (con su respectiva licencia) para administrar el sistema operativo del SG-SST. Sus acciones dentro de la Plataforma se realizan en nombre y representación de la Empresa.</li>
                    <li><strong>Trabajadores:</strong> Usuarios con acceso limitado, cuyo propósito principal es la consulta de información pública de la empresa, el diligenciamiento de la encuesta sociodemográfica y el registro de sus asistencias o notificaciones.</li>
                </ul>
                <p>El cuidado de las credenciales de acceso (usuario, contraseña, códigos 2FA) es responsabilidad de cada usuario. Cualquier actividad realizada bajo una cuenta será atribuida legalmente al titular de la misma.</p>
            </section>

            <section id="sec-firmas" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-file-signature"></i></div> 4. Validez de la Firma Electrónica</h2>
                <p>La Plataforma permite la recolección de firmas mediante trazos táctiles (en dispositivos móviles) o a través del mouse, las cuales se adjuntan a los documentos generados (Actas, Planillas, Asistencias).</p>
                <p>Al utilizar este método, el usuario reconoce y acepta que su firma electrónica trazada en la Plataforma tiene <strong>plena validez jurídica, eficacia probatoria y fuerza vinculante</strong>, en los términos establecidos por la <strong>Ley 527 de 1999</strong> en Colombia, equivaliendo a su firma manuscrita para los documentos internos de gestión de Seguridad y Salud en el Trabajo.</p>
            </section>

            <section id="sec-pagos" class="term-section">
                <h2><div class="icon-section"><i class="fa-regular fa-credit-card"></i></div> 5. Suscripciones y Pagos (Wompi)</h2>
                <p>El acceso a las funcionalidades de la Empresa requiere el pago de una suscripción activa (Mensual o Anual). Los pagos son procesados de forma segura a través de la pasarela de pagos <strong>Wompi</strong> (Bancolombia).</p>
                <ul>
                    <li>Los planes están limitados por la cantidad de trabajadores (Ej: PEM, MEM, GEM). Si la Empresa excede el límite de su plan, deberá realizar un <em>upgrade</em> pagando la diferencia correspondiente.</li>
                    <li>Por la naturaleza digital del Servicio (SaaS), los pagos realizados <strong>no son reembolsables</strong>, salvo en los casos en que aplique el derecho de retracto conforme a la Ley 1480 de 2011 (Estatuto del Consumidor).</li>
                    <li>En caso de mora en el pago, PreventWork se reserva el derecho de suspender el acceso a la cuenta tras un periodo de gracia notificado, manteniendo los datos en custodia temporal.</li>
                </ul>
            </section>

            <section id="sec-privacidad" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-shield-halved"></i></div> 6. Privacidad y Tratamiento de Datos Personales</h2>
                <p>La recolección y tratamiento de datos personales (incluyendo información sociodemográfica y de salud de los trabajadores) se rige bajo la <strong>Ley 1581 de 2012</strong> de Habeas Data. Al registrarse, los usuarios autorizan el tratamiento de sus datos conforme a nuestra Política de Privacidad.</p>
                <p>La Empresa contratante actúa como <strong>Responsable</strong> de los datos de sus trabajadores, mientras que PreventWork actúa únicamente en calidad de <strong>Encargado</strong> del tratamiento, proporcionando la infraestructura tecnológica para su almacenamiento y procesamiento seguro.</p>
            </section>

            <section id="sec-soporte" class="term-section">
                <h2><div class="icon-section"><i class="fa-solid fa-server"></i></div> 7. Disponibilidad y Soporte Técnico</h2>
                <p>Nos esforzamos por mantener la Plataforma operativa el 99.9% del tiempo. Sin embargo, PreventWork no garantiza que el acceso sea ininterrumpido o libre de errores debido a mantenimientos programados, actualizaciones o fallos de terceros (servidores de hosting, pasarelas de pago, etc.).</p>
                <p>El soporte técnico se brindará de acuerdo con los niveles de servicio establecidos en el plan de suscripción adquirido a través de los canales oficiales de la plataforma.</p>
            </section>

            <section id="sec-propiedad" class="term-section">
                <h2><div class="icon-section"><i class="fa-regular fa-copyright"></i></div> 8. Propiedad Intelectual</h2>
                <p>El código fuente, diseño, logotipos, algoritmos, y bases de datos que conforman PreventWork son propiedad exclusiva de los creadores de la Plataforma. El pago de una suscripción otorga a la Empresa una licencia de uso temporal, limitada, no exclusiva e intransferible, mas no otorga derechos de propiedad sobre el software.</p>
                <p>PreventWork se reserva el derecho de modificar estos Términos y Condiciones en cualquier momento. Los cambios sustanciales serán notificados al Representante Legal de cada Empresa a través de correo electrónico o mediante avisos en la Plataforma.</p>
            </section>

        </div>
    </main>

    <a href="https://wa.me/573000000000?text=Hola,%20necesito%20ayuda%20con%20PreventWork" class="floating-btn wa-btn" target="_blank" title="Contáctanos por WhatsApp">
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
                        slowScrollTo(targetPos, 1000); // 1000ms = 1 segundo de scroll súper suave
                    }
                });
            });

            // 4. CLIC EN EL BOTÓN FLOTANTE "VOLVER ARRIBA" CON SCROLL LENTO
            btnScrollTop.addEventListener('click', function() {
                slowScrollTo(0, 1200); // 1200ms = 1.2 segundos para subir relajadamente
            });
        });
    </script>
    <?php include_once __DIR__ . '/components/cookie_banner.php'; ?>
</body>
</html>
