<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>SG-SST Pro | Plataforma de Gestión Integral</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      /* PALETA DE COLORES PREMIUM */
      --bg-top: #e8f0f8;
      --bg-mid: #f3f7fb;
      --bg-bottom: #ffffff;

      --accent: #ff8a1f;
      --accent-hover: #ff7a00;
      --blue-main: #2b5a9e;
      --blue-dark: #1e3a8a;

      --text-main: #0f172a;
      --text-muted: #475569;

      /* EFECTOS GLASSMORPHISM */
      --card-bg: rgba(255, 255, 255, 0.75);
      --card-border: rgba(255, 255, 255, 0.6);
      --shadow-soft: 0 25px 50px -12px rgba(43, 90, 158, 0.15);
      
      --radius-lg: 20px;
      --radius-md: 12px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      width: 100%;
      height: 100%;
      overflow-x: hidden;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--text-main);
      background: linear-gradient(135deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%);
      position: relative;
    }

    /* FIGURAS FLOTANTES DE FONDO */
    .blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(60px);
      z-index: -1;
      opacity: 0.4;
      animation: float 10s infinite ease-in-out alternate;
    }
    .blob-1 {
      top: -10%; left: -10%;
      width: 400px; height: 400px;
      background: var(--blue-main);
    }
    .blob-2 {
      bottom: -10%; right: -5%;
      width: 500px; height: 500px;
      background: var(--accent);
      animation-delay: -5s;
    }

    @keyframes float {
      0% { transform: translateY(0px) scale(1); }
      100% { transform: translateY(30px) scale(1.05); }
    }

    /* ANIMACIONES DE ENTRADA */
    .fade-in-up {
      animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      opacity: 0;
      transform: translateY(20px);
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    @keyframes fadeInUp {
      to { opacity: 1; transform: translateY(0); }
    }

    /* HEADER */
    header {
      width: 100%;
      padding: 24px 6vw;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: absolute;
      top: 0;
      z-index: 10;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--blue-dark);
      letter-spacing: -0.02em;
    }

    .logo i {
      color: var(--accent);
      font-size: 1.4rem;
    }

    .header-nav {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .header-link {
      text-decoration: none;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.95rem;
      transition: color 0.2s;
    }

    .header-link:hover { color: var(--blue-main); }

    /* HERO */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px 6vw 40px;
    }

    .hero-grid {
      max-width: 1240px;
      width: 100%;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 60px;
      align-items: center;
    }

    /* TEXTO IZQUIERDA */
    .hero-tag {
      display: inline-block;
      background: rgba(43, 90, 158, 0.1);
      color: var(--blue-main);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 20px;
    }

    .hero-title {
      font-size: clamp(2.5rem, 4vw, 3.5rem);
      line-height: 1.15;
      font-weight: 800;
      margin-bottom: 24px;
      letter-spacing: -0.02em;
      color: var(--blue-dark);
    }

    .hero-title span {
      background: linear-gradient(135deg, var(--accent), #ff5e00);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-text {
      font-size: 1.1rem;
      color: var(--text-muted);
      max-width: 540px;
      line-height: 1.7;
      margin-bottom: 36px;
    }

    .hero-actions {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
    }

    .btn-primary, .btn-secondary {
      padding: 14px 28px;
      border-radius: var(--radius-md);
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent), var(--accent-hover));
      color: #fff;
      box-shadow: 0 10px 25px rgba(255, 138, 31, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(255, 138, 31, 0.4);
    }

    .btn-secondary {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      color: var(--text-main);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .btn-secondary:hover {
      border-color: var(--blue-main);
      color: var(--blue-main);
      background: #f8fafc;
      transform: translateY(-3px);
    }

    /* TARJETA DERECHA - GLASSMORPHISM */
    .info-card {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-lg);
      padding: 48px 40px;
      box-shadow: var(--shadow-soft);
      position: relative;
    }

    .info-card h3 {
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--blue-dark);
    }

    .info-card p {
      font-size: 0.95rem;
      color: var(--text-muted);
      margin-bottom: 32px;
      line-height: 1.6;
    }

    .indicators {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .indicator {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: var(--radius-md);
      padding: 16px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .indicator:hover {
      transform: translateX(5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.04);
      border-color: #cbd5e1;
    }

    .icon-box {
      background: rgba(255, 138, 31, 0.1);
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .icon-box i {
      color: var(--accent);
      font-size: 1.1rem;
    }

    .indicator-text h4 {
      font-size: 0.95rem;
      color: var(--text-main);
      margin-bottom: 4px;
      font-weight: 600;
    }

    .indicator-text span {
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .hero {
        padding: 120px 6vw 60px;
        align-items: flex-start;
      }
      .hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
      }
      .hero-text {
        margin: 0 auto 30px;
      }
      .hero-actions {
        justify-content: center;
      }
      .hero-tag {
        margin: 0 auto 20px;
      }
      .info-card {
        text-align: left;
      }
    }

    @media (max-width: 480px) {
      .header-link { display: none; } /* Ocultar links en cel, dejar solo logo y boton */
      .hero-title { font-size: 2.2rem; }
      .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
      .info-card { padding: 30px 20px; }
    }
  </style>
</head>

<body>

  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <header class="fade-in-up">
    <div class="logo">
      <i class="fa-solid fa-shield-halved"></i>
      SG-SST Pro
    </div>
    <div class="header-nav">
      <a href="login.php" class="header-link">Inicia sesión</a>
      <a href="register.php" class="btn-primary" style="padding: 8px 20px; font-size: 0.9rem;">
        Pruébalo Gratis
      </a>
    </div>
  </header>

  <section class="hero">
    <div class="hero-grid">

      <div class="fade-in-up delay-1">
        <span class="hero-tag">Normatividad Resolución 0312</span>
        <h1 class="hero-title">
          Automatiza tu<br>
          Gestión de <span>SST</span>
        </h1>

        <p class="hero-text">
          La plataforma definitiva para gestionar riesgos, cumplir con el Ministerio de Trabajo, firmar actas digitalmente y mantener a tu personal protegido sin dolores de cabeza.
        </p>

        <div class="hero-actions">
          <a href="register.php" class="btn-primary">
            Comenzar ahora
            <i class="fa-solid fa-arrow-right"></i>
          </a>
          <a href="login.php" class="btn-secondary">
            <i class="fa-solid fa-right-to-bracket"></i>
            Acceso a usuarios
          </a>
        </div>
      </div>

      <div class="info-card fade-in-up delay-2">
        <h3>Todo bajo control</h3>
        <p>
          Olvídate de las carpetas llenas de papeles. Digitaliza todo tu ecosistema de Seguridad y Salud en un entorno seguro y en la nube.
        </p>

        <div class="indicators">
          <div class="indicator">
            <div class="icon-box"><i class="fa-solid fa-file-signature"></i></div>
            <div class="indicator-text">
              <h4>Actas y Firmas Digitales</h4>
              <span>Genera PDFs oficiales en tiempo real.</span>
            </div>
          </div>
          <div class="indicator">
            <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="indicator-text">
              <h4>Control de Planillas (PILA)</h4>
              <span>Alertas automáticas antes de vencimientos.</span>
            </div>
          </div>
          <div class="indicator">
            <div class="icon-box"><i class="fa-solid fa-users-viewfinder"></i></div>
            <div class="indicator-text">
              <h4>Encuestas Sociodemográficas</h4>
              <span>Perfiles completos de todos tus trabajadores.</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

</body>
</html>