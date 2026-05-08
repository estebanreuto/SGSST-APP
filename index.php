<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>PREVENTWORK | Gestión Integral SG-SST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --bg-top: #ffffff;
      --bg-mid: #f8fafc;
      --bg-bottom: #ffffff;
      --accent: #ff8a1f;
      --accent-hover: #ff7a00;
      --blue-main: #2b5a9e;
      --blue-dark: #0f172a;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --card-bg: #ffffff;
      --card-border: #e2e8f0;
      --shadow-soft: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
      --radius-lg: 24px;
      --radius-md: 12px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    
    body { font-family: 'Inter', sans-serif; color: var(--text-main); background: #ffffff; overflow-x: hidden; width: 100%; position: relative; }
    .page-wrapper { width: 100%; overflow-x: hidden; position: relative; }

    .bg-gradient { position: absolute; top: 0; left: 0; right: 0; height: 100vh; background: radial-gradient(circle at top right, #f1f5f9 0%, #ffffff 100%); z-index: -2; }
    .blob { position: absolute; border-radius: 50%; filter: blur(90px); z-index: -1; opacity: 0.15; animation: float 10s infinite ease-in-out alternate; }
    .blob-1 { top: -5%; left: -10%; width: 500px; height: 500px; background: var(--blue-main); }
    .blob-2 { top: 20%; right: -5%; width: 600px; height: 600px; background: var(--accent); animation-delay: -5s; }

    @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(30px) scale(1.05); } }

    .fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

    .btn-primary, .btn-secondary { padding: 14px 28px; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; border: none; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: #fff; box-shadow: 0 10px 25px rgba(255, 138, 31, 0.25); }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(255, 138, 31, 0.35); }
    .btn-secondary { background: #ffffff; border: 1px solid #cbd5e1; color: var(--text-main); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02); }
    .btn-secondary:hover { border-color: var(--blue-main); color: var(--blue-main); background: #f8fafc; transform: translateY(-3px); }

    .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 140px 6vw 60px; position: relative;}
    .hero-grid { max-width: 1280px; width: 100%; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 60px; align-items: center; }

    .hero-tag { display: inline-block; background: #f1f5f9; color: var(--blue-main); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .hero-title { font-size: clamp(2.5rem, 4.2vw, 4rem); line-height: 1.1; font-weight: 800; margin-bottom: 24px; letter-spacing: -0.03em; color: var(--blue-dark); }
    .hero-title span { background: linear-gradient(135deg, var(--accent), #ff5e00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero-text { font-size: 1rem; color: var(--text-muted); max-width: 500px; line-height: 1.6; margin-bottom: 36px; }
    .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; position: relative; z-index: 10; } 

    .visual-container {
      position: relative;
      width: 100%;
      height: 550px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .phone-mockup {
      width: 280px;
      height: 560px;
      background: #ffffff;
      border: 12px solid #0f172a; 
      border-radius: 40px;
      position: relative;
      box-shadow: 0 30px 60px rgba(0,0,0,0.15);
      z-index: 5;
      overflow: hidden;
      animation: floatPhone 6s ease-in-out infinite;
    }

    .phone-notch {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 120px;
      height: 25px;
      background: #0f172a;
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
      z-index: 10;
    }

    .phone-screen {
      width: 100%;
      height: 100%;
      background: #f8fafc;
      padding: 40px 16px 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      position: relative;
    }

    .mock-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .mock-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; }
    .mock-lines { display: flex; flex-direction: column; gap: 6px; flex: 1;}
    .mock-line-1 { height: 10px; width: 60%; background: #cbd5e1; border-radius: 4px; }
    .mock-line-2 { height: 8px; width: 40%; background: #e2e8f0; border-radius: 4px; }

    .mock-chart { 
      background: white; border-radius: 12px; padding: 16px; 
      height: 120px; display: flex; align-items: flex-end; gap: 10px; 
      box-shadow: 0 4px 6px rgba(0,0,0,0.02); justify-content: center;
    }
    .mock-bar { width: 25%; background: var(--blue-main); border-radius: 4px 4px 0 0; }
    .mock-bar.b1 { animation: barGrow1 3s infinite alternate ease-in-out; }
    .mock-bar.b2 { background: var(--accent); animation: barGrow2 3.5s infinite alternate ease-in-out; }
    .mock-bar.b3 { background: #94a3b8; animation: barGrow3 2.5s infinite alternate ease-in-out; }

    .mock-list { display: flex; flex-direction: column; gap: 10px; }
    .mock-item { 
      background: white; height: 45px; border-radius: 10px; 
      box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
      animation: slideUp 4s infinite linear;
      opacity: 0;
    }
    .mock-item.m-1 { animation-delay: 0s; }
    .mock-item.m-2 { animation-delay: 1s; }
    .mock-item.m-3 { animation-delay: 2s; }

    @keyframes floatPhone { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
    @keyframes barGrow1 { 0% { height: 30%; } 100% { height: 80%; } }
    @keyframes barGrow2 { 0% { height: 50%; } 100% { height: 100%; } }
    @keyframes barGrow3 { 0% { height: 20%; } 100% { height: 60%; } }
    @keyframes slideUp { 
      0% { transform: translateY(20px); opacity: 0; } 
      10% { transform: translateY(0); opacity: 1; }
      80% { transform: translateY(0); opacity: 1; }
      100% { transform: translateY(-20px); opacity: 0; }
    }

    .floating-badge {
      position: absolute;
      background: white;
      padding: 12px 18px;
      border-radius: 50px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      font-size: 0.85rem;
      border: 1px solid #f1f5f9;
      white-space: nowrap;
      animation: floatBadge 6s infinite ease-in-out;
      z-index: 6;
      color: var(--blue-dark);
    }
    .floating-badge i { color: var(--accent); font-size: 1rem; }

    .badge-1 { top: 5%; left: 0; animation-delay: 0s; } 
    .badge-2 { bottom: 15%; right: -5%; animation-delay: 1s; } 
    .badge-3 { top: 40%; left: -10%; animation-delay: 2s; } 
    .badge-4 { top: 20%; right: 0; animation-delay: 3s; } 
    .badge-5 { bottom: 25%; left: -5%; animation-delay: 4s; } 

    @keyframes floatBadge { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

    /* =========================================
       SECCIÓN DE CARACTERÍSTICAS (4 ARRIBA, 2 CENTRADAS ABAJO)
       ========================================= */
    .features { 
        padding: 60px 6vw; 
        background: #ffffff; 
        position: relative; 
        z-index: 2;
    }
    
    .section-header { 
        text-align: center; 
        max-width: 700px; 
        margin: 0 auto 40px; 
    }
    
    .section-header h2 { 
        font-size: 1.8rem; 
        color: var(--blue-dark); 
        font-weight: 800; 
        margin-bottom: 10px; 
        letter-spacing: -0.02em;
    }
    
    .section-header p { 
        font-size: 0.9rem; 
        color: var(--text-muted); 
        line-height: 1.5; 
    }
    
    /* CAMBIO A FLEXBOX PARA CENTRAR LAS QUE SOBRAN */
    .features-grid { 
        display: flex; 
        flex-wrap: wrap; 
        justify-content: center; /* Centra las tarjetas sobrantes en la última fila */
        gap: 20px; 
        max-width: 1200px; 
        margin: 0 auto; 
    }
    
    .feature-card { 
        width: calc(25% - 15px); /* 4 tarjetas por fila en PC (100% / 4) */
        padding: 20px; 
        border-radius: 16px; 
        background: #ffffff; 
        border: 1px solid #e2e8f0; 
        transition: all 0.3s ease; 
        text-align: left; /* El texto por dentro sigue alineado a la izquierda */
        position: relative;
        overflow: hidden; 
        display: flex;
        flex-direction: column;
        gap: 8px; 
    }
    
    .feature-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.05); 
        border-color: #cbd5e1;
    }
    
    .feature-icon { 
        width: 38px; height: 38px; 
        background: rgba(43, 90, 158, 0.08); 
        border-radius: 10px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 1rem; color: var(--blue-main); 
        z-index: 2; position: relative;
    }
    
    .feature-card h3 { 
        font-size: 1rem; 
        font-weight: 800; 
        color: var(--blue-dark); 
        margin: 0; 
        z-index: 2; position: relative;
    }
    
    .feature-card p { 
        font-size: 0.8rem; 
        color: var(--text-muted); 
        line-height: 1.4; 
        margin: 0; 
        z-index: 2; position: relative;
    }

    .watermark-icon {
        position: absolute;
        right: -10px;
        bottom: -15px;
        font-size: 100px;
        color: var(--blue-main);
        opacity: 0.03; 
        transform: rotate(-15deg);
        transition: all 0.4s ease;
        z-index: 1;
        pointer-events: none; 
    }
    
    .feature-card:hover .watermark-icon {
        transform: rotate(0deg) scale(1.05);
        opacity: 0.06;
        color: var(--accent);
    }

    /* =========================================
       MEDIA QUERIES RESPONSIVES
       ========================================= */

    @media (max-width: 992px) {
      .hero-grid { grid-template-columns: 1fr; gap: 60px; text-align: center; }
      .hero-text { margin: 0 auto 30px; max-width: 600px;}
      .hero-actions { justify-content: center; }
      .visual-container { transform: scale(0.9); height: 500px; margin-top: 20px; }
      
      /* En Tablet se ven de a 2 en 2 */
      .feature-card { width: calc(50% - 10px); } 
    }

    @media (max-width: 768px) {
      .hero { padding-top: 150px; padding-bottom: 150px; height: auto;}
      .hero-title { font-size: 2.3rem; }
      .hero-actions { flex-direction: column; width: 100%; gap: 12px; margin-bottom: 30px; }
      .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
      
      .visual-container { 
          transform: scale(0.7); 
          transform-origin: top center; 
          height: 420px; 
          margin-top: 50px; 
          margin-bottom: 0px; 
      }
      
      .section-header h2 { font-size: 1.8rem; }
      .features { padding: 40px 6vw; }
    }

    @media (max-width: 480px) {
      .hero { padding-top: 140px; padding-bottom: 150px; height: auto;} 
      .hero-title { font-size: 2rem; margin-bottom: 16px; }
      .hero-text { font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px; }
      .hero-grid { gap: 10px; }
      .hero-actions { margin-bottom: 40px; } 
      
      .visual-container { 
          transform: scale(0.6); 
          transform-origin: top center; 
          height: 400px; 
          margin-top: 65px; 
          margin-bottom: 0; 
      }
      
      .badge-1 { left: 15%; top: 5%; } 
      .badge-4 { right: 5%; top: 20%; } 
      .badge-3 { left: 5%; top: 40%; } 
      .badge-5 { left: 10%; bottom: 15%; } 
      .badge-2 { right: 0%; bottom: 0%; } 
      
      /* En móviles se apilan una debajo de otra (100%) */
      .feature-card { width: 100%; } 
    }
  </style>
</head>
<body>

<div class="page-wrapper">
  <div class="bg-gradient"></div>
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <?php include 'components/public_header.php'; ?>

  <main>
    <section class="hero">
      <div class="hero-grid">
        <div class="fade-in-up delay-1">
          <span class="hero-tag">Cumplimiento Resolución 0312</span>
          <h1 class="hero-title">
            Automatiza tu<br>
            Gestión de <span>SST</span>
          </h1>
          <p class="hero-text">
            La plataforma definitiva para gestionar riesgos, organizar trabajadores, firmar actas digitalmente y mantener tu empresa al 100% de cumplimiento.
          </p>
          <div class="hero-actions">
            <a href="register.php" class="btn-primary">
              Regístrate ahora
              <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="login.php" class="btn-secondary">
              <i class="fa-solid fa-right-to-bracket"></i>
              Iniciar Sesión
            </a>
          </div>
        </div>

        <div class="visual-container fade-in-up delay-2">
            <div class="phone-mockup">
                <div class="phone-notch"></div>
                <div class="phone-screen">
                    <div class="mock-header">
                        <div class="mock-avatar"></div>
                        <div class="mock-lines">
                            <div class="mock-line-1"></div>
                            <div class="mock-line-2"></div>
                        </div>
                    </div>
                    <div class="mock-chart">
                        <div class="mock-bar b1"></div>
                        <div class="mock-bar b2"></div>
                        <div class="mock-bar b3"></div>
                    </div>
                    <div class="mock-list">
                        <div class="mock-item m-1"></div>
                        <div class="mock-item m-2"></div>
                        <div class="mock-item m-3"></div>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="fa-solid fa-file-pdf"></i><span>Normatividad 0312</span></div>
            <div class="floating-badge badge-2"><i class="fa-solid fa-signature"></i><span>Firmas Digitales</span></div>
            <div class="floating-badge badge-3"><i class="fa-solid fa-users"></i><span>Control Personal</span></div>
            <div class="floating-badge badge-4"><i class="fa-solid fa-chart-pie"></i><span>Reportes en Vivo</span></div>
            <div class="floating-badge badge-5"><i class="fa-solid fa-shield-virus"></i><span>Matriz GTC 45</span></div>
        </div>
      </div>
    </section>

    <section id="caracteristicas" class="features fade-in-up delay-3">
        <div class="section-header">
            <h2>Diseñado para el cumplimiento total</h2>
            <p>Nuestra plataforma integra todas las herramientas que necesitas para operar de manera sincronizada y sin dolores de cabeza.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <i class="fa-solid fa-file-pdf watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <h3>Documentos en 1 Clic</h3>
                <p>Crea actas de nombramiento y políticas corporativas listas para firmar digitalmente en segundos.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-bell watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-bell"></i></div>
                <h3>Alertas y Seguimiento</h3>
                <p>Monitorea y mantén actualizado el registro en todos los estándares mínimos para estar siempre al día.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-shield-virus watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-shield-virus"></i></div>
                <h3>Matriz de Riesgos</h3>
                <p>Identifica peligros, valora riesgos y asigna controles efectivos alineados a la normatividad GTC 45.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-users-gear watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-users-gear"></i></div>
                <h3>Control de Personal</h3>
                <p>Agrupa trabajadores por áreas, gestiona estados y automatiza encuestas sociodemográficas.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-signature watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-signature"></i></div>
                <h3>Firmas Digitales</h3>
                <p>Recolecta firmas legalmente válidas enviando un enlace directamente al celular del trabajador.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-chart-line watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Reportes en Vivo</h3>
                <p>Genera métricas en tiempo real de accidentalidad, ausentismo y cumplimiento normativo de tu empresa.</p>
            </div>
        </div>
    </section>
  </main>

  <?php include 'components/public_footer.php'; ?>

  <?php include 'components/floating_buttons.php'; ?>

</div> </body>
</html>