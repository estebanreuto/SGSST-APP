<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quiénes Somos | PreventWork SG-SST</title>
    
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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background-color: var(--bg-color); color: var(--text-main); line-height: 1.6; overflow-x: hidden; scroll-behavior: smooth; }
        
        /* ANIMACIONES BÁSICAS */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; } .delay-4 { animation-delay: 0.4s; }

        /* ✨ ICONOS ANIMADOS DE FONDO */
        .floating-icon { position: absolute; color: var(--blue-dark); opacity: 0.04; z-index: 0; pointer-events: none; }
        .floating-icon svg { width: 100%; height: 100%; stroke-width: 2; }
        .float-2 { width: 150px; height: 150px; bottom: 10%; right: -50px; animation: floatAnim2 16s ease-in-out infinite alternate; }
        .float-3 { width: 120px; height: 120px; bottom: 20%; left: 10%; animation: floatAnim3 20s linear infinite; opacity: 0.03;}
        .float-4 { width: 140px; height: 140px; top: 15%; right: 5%; animation: floatAnim4 18s ease-in-out infinite; opacity: 0.04;}

        @keyframes floatAnim2 { 0%, 100% { transform: translateY(0) rotate(15deg) scale(1); } 50% { transform: translateY(40px) rotate(-5deg) scale(0.95); } }
        @keyframes floatAnim3 { 0% { transform: rotate(0deg) translate(0, 0); } 50% { transform: rotate(180deg) translate(30px, -30px); } 100% { transform: rotate(360deg) translate(0, 0); } }
        @keyframes floatAnim4 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(-30px, 30px) rotate(12deg); } }

        /* 👑 HERO SECTION */
        .about-hero {
            padding: 180px 5% 80px 5%;
            background: radial-gradient(circle at top, #FFFFFF 0%, #FAFAFA 100%);
            text-align: center;
            border-bottom: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        .about-hero::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#D1D5DB 1px, transparent 1px); background-size: 24px 24px; opacity: 0.4; z-index: 0; pointer-events: none;
        }
        .hero-content { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; }
        
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; background: #fff; border: 1px solid #E5E7EB;
            border-radius: 30px; font-size: 11px; font-weight: 800; color: var(--blue-dark); margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .about-hero h1 { font-size: 52px; font-weight: 900; letter-spacing: -1.8px; line-height: 1.1; margin-bottom: 24px; color: var(--blue-dark); }
        .about-hero p { font-size: 15px; color: var(--text-muted); line-height: 1.6; max-width: 660px; margin: 0 auto; font-weight: 500;}

        /* 📖 NUESTRA HISTORIA */
        .story-section { padding: 100px 5%; background: var(--bg-color); position: relative;}
        .story-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 1fr; gap: 70px; align-items: center; }
        
        .story-text h2 { font-size: 36px; font-weight: 900; letter-spacing: -1.3px; margin-bottom: 20px; color: var(--blue-dark); line-height: 1.1;}
        .story-text p { font-size: 15px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.7; text-align: justify; }
        
        /* Cifras Destacadas */
        .story-highlights { display: flex; gap: 32px; margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px; margin-bottom: 32px;}
        .highlight-item { display: flex; flex-direction: column; align-items: flex-start; }
        
        .highlight-icon {
            width: 36px; height: 36px; border-radius: 10px; background: #FFF7ED; color: var(--primary);
            display: flex; justify-content: center; align-items: center; margin-bottom: 12px;
        }
        .highlight-icon svg { width: 18px; height: 18px; stroke-width: 2.5; }

        .anim-cloud svg { animation: floatIcon 3s ease-in-out infinite; }
        .anim-pulse svg { animation: pulseIcon 3s ease-in-out infinite; }
        .anim-spin svg { animation: spinIcon 6s linear infinite; }

        @keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        @keyframes pulseIcon { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.15); } }
        @keyframes spinIcon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .highlight-item h4 { font-size: 28px; font-weight: 900; color: #111; letter-spacing: -1px; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;}
        .highlight-item h4 span { color: var(--primary); font-size: 18px;}
        .highlight-item p { font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.4; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ✨ BOTONES COMPACTOS EN HISTORIA */
        .story-actions { display: flex; gap: 12px; align-items: center; }
        
        .btn-small-primary {
            padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; color: #fff; background: var(--blue-dark); 
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
            border: 1px solid var(--blue-dark); box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);
        }
        .btn-small-primary:hover { background: #172554; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(30, 58, 138, 0.3); }
        .btn-small-primary svg { width: 14px; height: 14px; transition: transform 0.2s;}
        .btn-small-primary:hover svg { transform: translateX(3px); }

        .btn-small-outline {
            padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #4B5563; background: transparent; 
            text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s; border: 1px solid #D1D5DB;
        }
        .btn-small-outline:hover { background: #F9FAFB; color: #111; border-color: #9CA3AF; }

        /* Tarjeta de Frase Premium */
        .story-image-box {
            position: relative; border-radius: 32px; background: linear-gradient(145deg, var(--blue-dark) 0%, #0f172a 100%);
            padding: 56px 40px; color: #fff; box-shadow: 0 24px 48px rgba(0,0,0,0.15), inset 0 1px 1px rgba(255,255,255,0.1);
            overflow: hidden; transition: transform 0.4s ease; z-index: 2;
        }
        .story-image-box:hover { transform: translateY(-6px); box-shadow: 0 32px 64px rgba(0,0,0,0.2);}
        .story-image-box::before {
            content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 100%;
            background: radial-gradient(circle, rgba(255,138,31,0.15) 0%, transparent 60%); pointer-events: none; z-index: 1;
        }
        .story-image-box::after {
            content: '"'; position: absolute; top: -30px; left: 24px; font-size: 160px; color: rgba(255,255,255,0.04); font-family: serif; font-weight: 900; line-height: 1; z-index: 1;
        }
        
        .story-stars { display: flex; gap: 4px; margin-bottom: 16px; position: relative; z-index: 2; color: var(--primary); }
        .story-stars svg { width: 16px; height: 16px; fill: currentColor; }

        .story-quote { 
            font-size: 18px; font-weight: 500; line-height: 1.6; letter-spacing: -0.2px; color: #F3F4F6;
            position: relative; z-index: 2; margin-bottom: 32px; font-style: italic;
        }
        
        .story-author-box { display: flex; align-items: center; gap: 12px; position: relative; z-index: 2; }
        .story-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); display: flex; justify-content: center; align-items: center; color: #fff; font-size: 14px; font-weight: 800; border: 2px solid #fff;}
        .story-author-info { display: flex; flex-direction: column; }
        .story-author-name { font-size: 14px; font-weight: 700; color: #fff; letter-spacing: 0.5px;}
        .story-author-role { font-size: 12px; color: #9CA3AF; }
        
        .floating-badge {
            position: absolute; top: -15px; right: -15px; background: var(--primary); color: #fff; padding: 10px 20px; border-radius: 20px;
            font-size: 12px; font-weight: 800; box-shadow: 0 10px 20px rgba(255, 138, 31, 0.3); text-transform: uppercase; letter-spacing: 0.5px;
            transform: rotate(4deg); z-index: 3; animation: floatBadge 4s ease-in-out infinite;
        }
        @keyframes floatBadge { 0%, 100% { transform: rotate(4deg) translateY(0); } 50% { transform: rotate(4deg) translateY(-8px); } }

        /* ✨ NUESTROS PILARES ✨ */
        .values-section { 
            padding: 80px 5%; background: var(--bg-color); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); position: relative; overflow: hidden;
        }
        .values-section::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#D1D5DB 1px, transparent 1px); background-size: 24px 24px; opacity: 0.5; z-index: 0; pointer-events: none;
        }
        .values-header { text-align: center; max-width: 600px; margin: 0 auto 40px auto; position: relative; z-index: 1;} 
        .values-header h2 { font-size: 33px; font-weight: 900; letter-spacing: -1.3px; margin-bottom: 12px; color: var(--blue-dark); line-height: 1.1;} 
        .values-header p { color: var(--text-muted); font-size: 15px; line-height: 1.6; }
        
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; position: relative; z-index: 1; }
        
        .feature-card { 
            position: relative; overflow: hidden; padding: 24px 20px; border-radius: 16px; border: 1px solid var(--border); 
            background: var(--surface); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); text-align: left; box-shadow: 0 4px 12px rgba(0,0,0,0.02); z-index: 1;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); border-color: #D1D5DB; }
        
        .feature-bg-icon { position: absolute; bottom: -15px; right: -15px; width: 110px; height: 110px; color: var(--blue-dark); opacity: 0.03; z-index: -1; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transform: rotate(-10deg); }
        .feature-bg-icon svg { width: 100%; height: 100%; stroke-width: 1.5; }
        .feature-card:hover .feature-bg-icon { transform: scale(1.15) rotate(0deg); opacity: 0.05; }
        
        .feature-content { position: relative; z-index: 2; }
        .feature-icon-wrapper { width: 40px; height: 40px; border-radius: 10px; background: #F3F4F6; color: var(--blue-dark); display: flex; justify-content: center; align-items: center; margin-bottom: 16px; transition: all 0.3s ease; }
        .feature-card:hover .feature-icon-wrapper { background: var(--primary); color: #fff; transform: scale(1.1) rotate(-5deg); }
        .feature-icon-wrapper svg { width: 20px; height: 20px; stroke-width: 2.5; } 
        
        .feature-content h3 { font-size: 16px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.3px; color: var(--blue-dark); } 
        .feature-content p { font-size: 14px; color: var(--text-muted); line-height: 1.5; }

        /* ✨ 🚀 CTA COMPACTO Y ELEGANTE ✨ */
        .cta-section { padding: 80px 5%; background: var(--bg-color); text-align: center; overflow: hidden;} 
        .cta-box { 
            max-width: 900px; margin: 0 auto; padding: 64px 32px; background: var(--blue-dark); 
            border-radius: 24px; color: #fff; box-shadow: 0 24px 48px rgba(30,58,138,0.2); 
            position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);
        }
        
        .cta-glow { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.4; pointer-events: none; z-index: 0;}
        .orb-1 { width: 300px; height: 300px; background: var(--primary); top: -100px; left: -50px; animation: floatOrb 8s ease-in-out infinite alternate; }
        .orb-2 { width: 250px; height: 250px; background: #3B82F6; bottom: -80px; right: -30px; animation: floatOrb 10s ease-in-out infinite alternate-reverse; }

        @keyframes floatOrb { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(30px, 30px) scale(1.1); } }

        .cta-content { position: relative; z-index: 2; }
        .cta-content h2 { font-size: 35px; font-weight: 900; letter-spacing: -1.3px; margin-bottom: 16px; line-height: 1.1;}
        .cta-content p { font-size: 15px; color: #E2E8F0; margin-bottom: 24px; max-width: 550px; margin-left: auto; margin-right: auto; line-height: 1.6;}
        
        /* Puntos de Confianza */
        .cta-trust { display: flex; justify-content: center; gap: 20px; margin-bottom: 32px; flex-wrap: wrap; }
        .cta-trust span { font-size: 13px; font-weight: 600; color: #CBD5E1; display: flex; align-items: center; gap: 6px; }
        .cta-trust svg { width: 14px; height: 14px; color: var(--primary); stroke-width: 3;}

        .cta-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        /* Botones CTA */
        .btn-cta-primary {
            padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none;
            background-color: var(--primary); color: #fff; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            border: none;
        }
        .btn-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,138,31,0.4); background: var(--primary-dark);}
        .btn-cta-primary svg { width: 16px; height: 16px; transition: transform 0.2s; stroke-width: 2.5;}
        .btn-cta-primary:hover svg { transform: translateX(4px); }

        .btn-cta-outline {
            padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
            background-color: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.3); 
            display: inline-flex; align-items: center; transition: all 0.2s;
        }
        .btn-cta-outline:hover { background-color: rgba(255,255,255,0.1); border-color: #fff; }

        /* 📱 RESPONSIVE */
        @media (max-width: 992px) {
            .story-grid { grid-template-columns: 1fr; gap: 48px; }
            .story-text { text-align: center; }
            .story-highlights { justify-content: center; }
            .story-actions { justify-content: center; }
            .floating-badge { right: 20px; }
        }
        @media (max-width: 768px) {
            .about-hero { padding: 140px 5% 40px 5%; }
            .about-hero h1 { font-size: 37px; letter-spacing: -1px; }
            
            .story-section { padding: 60px 5%; }
            .story-text h2 { font-size: 28px; letter-spacing: -1px;}
            .story-highlights { gap: 20px; flex-direction: column; align-items: center; text-align: center; }
            .highlight-item { align-items: center; }
            
            .story-actions { flex-direction: column; width: 100%; }
            .btn-small-primary, .btn-small-outline { width: 100%; justify-content: center; }
            
            .story-image-box { padding: 40px 24px; border-radius: 20px; }
            .story-quote { font-size: 17px; margin-bottom: 24px; }
            .floating-badge { right: 10px; top: -15px; padding: 10px 16px; font-size: 12px; }
            
            .values-section { padding: 60px 5%; }
            .values-header h2 { font-size: 26px; letter-spacing: -1px; }
            .feature-card { padding: 20px 16px; text-align: left; }
            
            .cta-section { padding: 60px 5%; }
            .cta-box { padding: 40px 20px; border-radius: 20px; }
            .cta-content h2 { font-size: 26px; letter-spacing: -1px; }
            .cta-trust { flex-direction: column; gap: 10px; align-items: center; }
            .cta-buttons { flex-direction: column; }
            .btn-cta-primary, .btn-cta-outline { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/components/public_page_loader.php'; ?>

    <?php include 'components/public_header.php'; ?>

    <section class="about-hero">
        <div class="floating-icon float-2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        </div>
        <div class="floating-icon float-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div class="floating-icon float-4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        </div>

        <div class="hero-content animate-up">
            <div class="hero-badge">Nuestra Misión</div>
            <h1>Simplificar el SG-SST.</h1>
            <p>Nacimos para liberar a las empresas y prevencionistas de la carga del papeleo, firmas perdidas y procesos anticuados. Creemos que la tecnología debe ser intuitiva para que te enfoques en lo verdaderamente importante: <strong>proteger vidas.</strong></p>
        </div>
    </section>

    <section class="story-section">
        <div class="story-grid">
            <div class="story-text animate-up delay-1">
                <h2>Cambiando las reglas de la prevención.</h2>
                <p>Durante años, el cumplimiento de la Seguridad y Salud en el Trabajo (Resolución 0312 y Decreto 1072) ha sido sinónimo de archivos físicos interminables, seguimientos complejos en Excel y auditorías llenas de estrés.</p>
                <p>En PreventWork nos hicimos una pregunta fundamental: <strong>¿Por qué no podemos gestionar la salud ocupacional con la misma fluidez y seguridad con la que usamos nuestro teléfono celular?</strong></p>
                <p>Así nació nuestra plataforma. Más que un software documental, construimos un ecosistema en la nube con validez jurídica. PreventWork funciona de maravilla en tu computadora de escritorio o directamente desde el celular en campo.</p>
                
                <div class="story-highlights">
                    <div class="highlight-item">
                        <div class="highlight-icon anim-cloud">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path></svg>
                        </div>
                        <h4>100<span>%</span></h4>
                        <p>Nube Segura</p>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon anim-pulse">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h4>100<span>%</span></h4>
                        <p>Validez Jurídica</p>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon anim-spin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                        </div>
                        <h4>24/7</h4>
                        <p>Trazabilidad</p>
                    </div>
                </div>

                <div class="story-actions animate-up delay-2">
                    <a href="index.php#caracteristicas" class="btn-small-primary">
                        Explorar plataforma
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                    <a href="index.php#planes" class="btn-small-outline">Ver planes corporativos</a>
                </div>
            </div>
            
            <div style="position: relative;" class="animate-up delay-2">
                <div class="floating-badge">La nueva era SG-SST</div>
                
                <div class="story-image-box">
                    <div class="story-stars">
                        <svg><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        <svg><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        <svg><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        <svg><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        <svg><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    </div>
                    <div class="story-quote">"El mejor sistema de gestión es el que te respalda ante auditorías. Debe ser tan fácil como dar un clic, impulsado por firmas digitales y datos inmutables en la nube."</div>
                    
                    <div class="story-author-box">
                        <div class="story-avatar"><i class="fa-solid fa-helmet-safety"></i></div>
                        <div class="story-author-info">
                            <span class="story-author-name">Equipo PreventWork</span>
                            <span class="story-author-role">Fundadores</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="values-section">
        <div class="values-header animate-up">
            <h2>Nuestros Pilares</h2>
            <p>La base tecnológica y operativa de nuestro desarrollo.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card animate-up delay-1">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <h3>Diseño Intuitivo</h3>
                    <p>Diseñamos eliminando lo innecesario. Creemos en una plataforma amigable que permita a cualquier trabajador firmar o consultar información sin curva de aprendizaje.</p>
                </div>
            </div>

            <div class="feature-card animate-up delay-2">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                    <h3>Automatización Total</h3>
                    <p>Desde la creación automática de enlaces en Google Meet hasta el reporte sociodemográfico. Reducimos horas de trabajo manual en segundos de proceso en la nube.</p>
                </div>
            </div>

            <div class="feature-card animate-up delay-3">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></div>
                    <h3>Actualización Normativa</h3>
                    <p>La legislación cambia, nuestro software también. Mantenemos el ecosistema alineado a los últimos requerimientos del Ministerio de Trabajo sin que debas reinstalar nada.</p>
                </div>
            </div>

            <div class="feature-card animate-up delay-1">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div>
                    <h3>Trazabilidad Absoluta</h3>
                    <p>Cada firma digital, asistencia y acta queda registrada con fecha, hora y usuario. Garantizamos la inmutabilidad de los datos para blindar a tu empresa en las auditorías.</p>
                </div>
            </div>

            <div class="feature-card animate-up delay-2">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg></div>
                    <h3>Soporte Experto</h3>
                    <p>Tu tranquilidad legal es nuestra prioridad. Contamos con soporte técnico ágil y humano para resolver dudas e impulsar el éxito de tu departamento SST.</p>
                </div>
            </div>

            <div class="feature-card animate-up delay-3">
                <div class="feature-bg-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg></div>
                <div class="feature-content">
                    <div class="feature-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg></div>
                    <h3>100% Accesible</h3>
                    <p>Gestiona y firma desde cualquier lugar. Nuestra plataforma responsiva funciona impecablemente tanto en equipos de escritorio de la oficina como en smartphones.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-box animate-up">
            <div class="cta-glow orb-1"></div>
            <div class="cta-glow orb-2"></div>
            
            <div class="cta-content">
                <h2 class="animate-up delay-1">Es hora de tomar el control del SG-SST.</h2>
                <p class="animate-up delay-2">Deja atrás las carpetas físicas y los excels desactualizados. Únete a las empresas que ya están gestionando sus riesgos y normativas de forma inteligente con PreventWork.</p>
                
                <div class="cta-trust animate-up delay-3">
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Cumplimiento Res. 0312</span>
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Soporte Técnico</span>
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Datos Encriptados (AWS)</span>
                </div>

                <div class="cta-buttons animate-up delay-4">
                    <a href="register.php" class="btn-cta-primary">
                        Transformar mi gestión
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                    <a href="https://wa.me/573012994599" target="_blank" class="btn-cta-outline">
                        Hablar con un asesor
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/public_footer.php'; ?>
    <?php include 'components/floating_buttons.php'; ?>

</body>
</html>
