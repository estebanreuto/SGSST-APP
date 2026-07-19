<?php
require_once 'config/db.php';

$mensaje_exito = "";
$mensaje_error = "";

// =========================================================
// LÓGICA PARA RECIBIR Y GUARDAR NUEVAS PREGUNTAS DEL PÚBLICO
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_pregunta'])) {
    $pregunta = trim($_POST['pregunta']);
    
    if (!empty($pregunta)) {
        try {
            // Guardamos la pregunta como "inactivo" para que el Admin la revise
            $stmt_insert = $conn->prepare("INSERT INTO preguntas_frecuentes (pregunta, respuesta, estado) VALUES (?, ?, 'inactivo')");
            $stmt_insert->execute([$pregunta, 'Pendiente de revisión y respuesta por el equipo de PreventWork.']);
            
            $mensaje_exito = "¡Tu pregunta ha sido enviada! La revisaremos y publicaremos la respuesta en este muro muy pronto.";
        } catch (PDOException $e) {
            $mensaje_error = "Hubo un error de conexión al enviar tu pregunta. Intenta nuevamente.";
        }
    } else {
        $mensaje_error = "Por favor, escribe una pregunta válida antes de enviar.";
    }
}

// =========================================================
// CONSULTAR LAS PREGUNTAS ACTIVAS (AHORA TRAEMOS LA FECHA)
// =========================================================
try {
    $stmt = $conn->prepare("SELECT pregunta, respuesta, fecha_creacion FROM preguntas_frecuentes WHERE estado = 'activo' ORDER BY orden ASC, id DESC");
    $stmt->execute();
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faqs = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad y Ayuda | PreventWork SG-SST</title>
    
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
            --border: #dbe3ec; --radius: 12px;
        }

        * { box-sizing: border-box; }

        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%); 
            color: var(--text-main); 
            font-size: 0.85rem; /* Letra base un poco más pequeña */
            line-height: 1.55;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* FONDOS Y MARCAS DE AGUA CORPORATIVAS */
        .watermark-bg { position: fixed; top: 75%; left: 20%; transform: translate(-50%, -50%) rotate(-15deg); font-size: 40vh; color: var(--blue-main); opacity: 0.03; z-index: -2; pointer-events: none; }
        
        .blob { position: fixed; border-radius: 50%; filter: blur(80px); z-index: -1; opacity: 0.3; animation: float 12s infinite ease-in-out alternate; }
        .blob-1 { top: -5%; left: -5%; width: 35vw; height: 35vw; background: var(--blue-main); }
        .blob-2 { bottom: -10%; right: -5%; width: 40vw; height: 40vw; background: var(--primary); animation-delay: -6s; }
        @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(30px) scale(1.05); } }

        .fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* HEADER PÚBLICO COMPACTO */
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

        .logo-link img { height: 24px; width: auto; transition: transform 0.2s; display: block; }
        .logo-link:hover img { transform: scale(1.02); }

        .public-nav { display: flex; gap: 16px; align-items: center; flex: 1; justify-content: center; }
        .public-nav a { text-decoration: none; color: var(--text-muted); font-size: 0.8rem; font-weight: 600; transition: all 0.2s; padding: 6px 10px; border-radius: 6px; }
        .public-nav a:hover { color: var(--blue-dark); background: rgba(255,255,255,0.6); }
        .public-nav a.active { color: var(--primary2); font-weight: 700; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.03);}

        .btn-back { background: rgba(255,255,255,0.9); border: 1px solid var(--border); color: var(--text-muted); padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: all 0.2s; flex-shrink: 0; }
        .btn-back:hover { background: #fff; color: var(--blue-dark); border-color: var(--blue-main); box-shadow: 0 2px 8px rgba(0,0,0,0.04);}

        /* HERO MINIMALISTA */
        .hero-legal { padding: 40px 20px 30px; max-width: 900px; margin: 0 auto; text-align: center; }
        .hero-legal h1 { font-size: 1.7rem; color: var(--blue-dark); font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.01em; } /* Letra reducida */
        .hero-legal p { color: var(--text-muted); font-size: 0.9rem; margin: 0; font-weight: 500; }

        /* =========================================
           LAYOUT PRINCIPAL EXPANDIDO
           ========================================= */
        .faq-split-layout {
            max-width: 1500px; 
            margin: 0 auto 60px auto;
            padding: 0 40px; 
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 40px;
            align-items: start;
            width: 100%;
            flex: 1;
        }

        /* =========================================
           LADO IZQUIERDO: DISEÑO TIPO BLOG / MURO
           ========================================= */
        .blog-list-container {
            display: flex;
            flex-direction: column;
            gap: 20px; 
        }

        .blog-post {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            padding: 24px 30px; 
            transition: all 0.3s ease;
        }
        .blog-post:hover { border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.04); transform: translateY(-2px); }

        .blog-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.7rem; /* Reducido */
            color: var(--text-muted);
            margin-bottom: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .blog-meta i { color: var(--primary); font-size: 0.8rem;}

        .blog-title {
            font-size: 1.05rem; /* Título reducido */
            color: var(--blue-dark);
            font-weight: 800;
            margin: 0 0 16px 0; 
            line-height: 1.4;
            letter-spacing: -0.01em;
        }

        /* CAJA DE RESPUESTA OFICIAL */
        .blog-answer-box {
            background: rgba(43, 90, 158, 0.04);
            border-left: 4px solid var(--blue-main);
            padding: 16px 20px; 
            border-radius: 0 8px 8px 0;
            position: relative;
        }
        
        .blog-answer-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem; /* Reducido */
            font-weight: 800;
            color: var(--blue-main);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .blog-answer-header img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: contain;
            background: white;
            border: 1px solid var(--border);
        }

        .blog-answer-text {
            color: #334155;
            font-size: 0.88rem; /* Reducido */
            line-height: 1.55;
            margin: 0;
            text-align: justify;
        }
        .blog-answer-text strong { color: var(--blue-dark); font-weight: 700; }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); background: var(--card-bg); border-radius: var(--radius); border: 2px dashed var(--border);}
        .empty-state i { font-size: 2.5rem; color: var(--border); margin-bottom: 16px; display: block;}

        /* =========================================
           LADO DERECHO: SECCIÓN FORO (NUEVO DISEÑO PREMIUM)
           ========================================= */
        .ask-section {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-top: 4px solid var(--primary);
            border-radius: var(--radius);
            padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: sticky;
            top: 90px; 
        }

        .ask-header {
            display: flex;
            gap: 14px;
            margin-bottom: 20px;
        }
        .ask-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, rgba(255,138,31,0.1), rgba(255,122,0,0.15));
            color: var(--primary2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .ask-header-text h3 {
            color: var(--blue-dark);
            font-size: 1.1rem; /* Reducido */
            font-weight: 800;
            margin: 0 0 4px 0;
            letter-spacing: -0.01em;
        }
        .ask-header-text p {
            color: var(--text-muted);
            font-size: 0.8rem; /* Reducido */
            margin: 0;
            line-height: 1.5;
        }

        .textarea-box {
            width: 100%;
            background: #f8fafc; 
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px;
            font-family: inherit;
            font-size: 0.88rem; /* Reducido */
            color: var(--text-main);
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            margin-bottom: 16px;
        }
        .textarea-box:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 138, 31, 0.15);
        }

        .ask-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ask-hint {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ask-hint i { color: #10b981; font-size: 0.9rem; }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary2));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem; /* Reducido */
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 138, 31, 0.25);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 138, 31, 0.35); }
        .btn-submit i { transition: transform 0.3s ease; }
        .btn-submit:hover i { transform: translateX(4px) translateY(-4px); } 

        /* ALERTAS DEL FORMULARIO */
        .alert-box {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        .alert-box.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;}
        .alert-box.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}

        /* BOTONES FLOTANTES */
        .floating-btn {
            position: fixed;
            bottom: 24px;
            width: 45px;
            height: 45px;
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
        .wa-btn { left: 24px; background: #25D366; }
        .wa-btn:hover { background: #1ebc5b; transform: scale(1.1) rotate(5deg); }
        .wa-btn i { font-size: 1.4rem; }

        .top-btn { right: 24px; background: var(--blue-main); opacity: 0; visibility: hidden; transform: translateY(20px); }
        .top-btn i { font-size: 1.1rem; }
        .top-btn.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .top-btn.visible:hover { background: var(--blue-dark); transform: translateY(-5px); }

        /* MEDIA QUERIES */
        @media (max-width: 1024px) {
            .faq-split-layout { 
                grid-template-columns: 1fr; 
                padding: 0 30px; 
            }
            .ask-section {
                position: static; 
                margin-top: 10px;
            }
        }
        @media (max-width: 768px) {
            .public-header { flex-wrap: wrap; justify-content: center; padding: 12px 16px; }
            .public-nav { order: 3; width: 100%; flex-wrap: wrap; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); }
            .floating-btn { width: 40px; height: 40px; bottom: 16px; }
            .wa-btn { left: 16px; }
            .top-btn { right: 16px; }
            .wa-btn i { font-size: 1.2rem; }
            .top-btn i { font-size: 0.9rem; }
            .ask-section { padding: 20px; }
            .blog-post { padding: 20px 20px; }
            
            .ask-footer { flex-direction: column; align-items: stretch; }
            .btn-submit { width: 100%; }
            .ask-hint { justify-content: center; margin-bottom: 8px;}
        }
        @media (max-width: 600px) {
            .faq-split-layout { padding: 0 16px; }
            .hero-legal { padding: 30px 16px 16px 16px; }
            .hero-legal h1 { font-size: 1.5rem; }
            .public-nav a { font-size: 0.75rem; padding: 4px 8px;}
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . '/components/public_page_loader.php'; ?>

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
            <a href="cookies.php">Política de Cookies</a>
            <a href="faq.php" class="active">Preguntas Frecuentes (FAQ)</a>
        </nav>

        <a href="register.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Registro
        </a>
    </header>

    <div class="hero-legal fade-in-up">
        <h1>Muro de la Comunidad</h1>
        <p>Encuentra respuestas rápidas en nuestro blog o pregúntale directamente a nuestra comunidad.</p>
    </div>

    <main class="faq-split-layout fade-in-up" style="animation-delay: 0.2s">
        
        <div class="blog-list-container">
            <?php if (!empty($faqs)): ?>
                <?php foreach ($faqs as $index => $faq): ?>
                    
                    <article class="blog-post">
                        <div class="blog-meta">
                            <i class="fa-regular fa-circle-user"></i> Pregunta de la Comunidad 
                            &bull; 
                            <i class="fa-regular fa-calendar-days" style="margin-left: 2px;"></i> 
                            <?php 
                                echo !empty($faq['fecha_creacion']) ? date('d/m/Y', strtotime($faq['fecha_creacion'])) : 'Reciente'; 
                            ?>
                        </div>
                        
                        <h2 class="blog-title">
                            <?php echo htmlspecialchars($faq['pregunta']); ?>
                        </h2>
                        
                        <div class="blog-answer-box">
                            <div class="blog-answer-header">
                                <img src="assets/logo_preventwork.png" alt="Logo" onerror="this.outerHTML='<i class=\'fa-solid fa-shield-halved\'></i>'">
                                Respuesta Oficial
                            </div>
                            <div class="blog-answer-text">
                                <?php echo $faq['respuesta']; ?>
                            </div>
                        </div>
                    </article>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <h3>El muro está vacío</h3>
                    <p>Sé el primero en hacer una pregunta a nuestra comunidad.</p>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="ask-section" id="ask-box">
                
                <div class="ask-header">
                    <div class="ask-avatar">
                        <i class="fa-solid fa-user-astronaut"></i>
                    </div>
                    <div class="ask-header-text">
                        <h3>Crear Publicación</h3>
                        <p>Escribe tu duda. Nuestro equipo la revisará y la publicará en el muro para ayudar a la comunidad.</p>
                    </div>
                </div>

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert-box success">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $mensaje_exito; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert-box error">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $mensaje_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="faq.php#ask-box" class="ask-form">
                    <input type="hidden" name="nueva_pregunta" value="1">
                    
                    <textarea name="pregunta" class="textarea-box" placeholder="Ej: ¿Cómo vinculo a un nuevo trabajador a mi empresa en la plataforma?" required></textarea>
                    
                    <div class="ask-footer">
                        <span class="ask-hint" title="Para evitar spam, revisamos las preguntas antes de subirlas.">
                            <i class="fa-solid fa-shield-halved"></i> Revisión previa
                        </span>
                        <button type="submit" class="btn-submit">
                            <span>Publicar Pregunta</span> <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </form>

            </div>
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
        // FUNCIÓN MATEMÁTICA PARA SCROLL LENTO Y FLUIDO
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

        // LÓGICA DEL BOTÓN FLOTANTE "VOLVER ARRIBA"
        const btnScrollTop = document.getElementById('btnScrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                btnScrollTop.classList.add('visible');
            } else {
                btnScrollTop.classList.remove('visible');
            }
        });

        btnScrollTop.addEventListener('click', function() {
            slowScrollTo(0, 1200); // Sube suavecito en 1.2 segundos
        });
    </script>
    <?php include_once __DIR__ . '/components/cookie_banner.php'; ?>
</body>
</html>
