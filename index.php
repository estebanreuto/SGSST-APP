<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>SG-SST | Plataforma Digita</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Fuente -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      /* ATMOSFÉRICO */
      --bg-top: #dceaf6;
      --bg-mid: #eef4fa;
      --bg-bottom: #f7f9fc;

      --accent: #ff8a1f;
      --blue-main: #4a7fbf;

      --text-main: #1f2d3d;
      --text-muted: #5f6f82;

      --card-bg: rgba(255,255,255,0.88);
      --card-border: rgba(74,127,191,0.12);

      --shadow-soft: 0 32px 80px rgba(47, 95, 167, 0.14);

      /* Bordes más sutiles */
      --radius-lg: 10px;
      --radius-md: 7px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      width: 100%;
      height: 100%;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--text-main);
      background:
        linear-gradient(
          180deg,
          var(--bg-top) 0%,
          var(--bg-mid) 45%,
          var(--bg-bottom) 100%
        );
    }

    /* HEADER */
    header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      padding: 24px 6vw;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 10;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      font-size: 1.05rem;
      color: var(--blue-main);
    }

    .header-btn {
      background: var(--accent);
      color: #fff;
      /* Botón header más compacto */
      padding: 8px 16px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 600;
      font-size: .88rem;
      box-shadow: 0 6px 18px rgba(255,138,31,.24);
    }

    /* HERO */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 6vw;
    }

    .hero-grid {
      max-width: 1180px;
      width: 100%;
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 72px; /* MÁS AIRE */
      align-items: center;
    }

    /* TEXTO */
    .hero-title {
      font-size: clamp(2rem, 3vw, 2.5rem);
      line-height: 1.18;
      margin-bottom: 16px;
    }

    .hero-title span {
      color: var(--accent);
    }

    .hero-text {
      font-size: 1rem;
      color: var(--text-muted);
      max-width: 520px;
      line-height: 1.65;
      margin-bottom: 30px;
    }

    .hero-actions {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }

    .btn-primary, .btn-secondary {
      /* Botones principales más compactos */
      padding: 10px 20px;
      border-radius: var(--radius-md);
      font-weight: 600;
      font-size: .9rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent), #ff7a00);
      color: #fff;
      box-shadow: 0 8px 22px rgba(255,138,31,.22);
    }

    .btn-secondary {
      background: rgba(255,255,255,.75);
      border: 2px solid rgba(255,138,31,.55);
      color: #d96800;
    }

    /* TARJETA DERECHA – INTEGRADA AL FONDO */
    .info-card {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      padding: 42px;
      box-shadow: var(--shadow-soft);
      border: 1px solid var(--card-border);
    }

    .info-card h3 {
      font-size: 1.15rem;
      margin-bottom: 10px;
    }

    .info-card p {
      font-size: .95rem;
      color: var(--text-muted);
      margin-bottom: 28px;
      line-height: 1.6;
    }

    .indicators {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .indicator {
      background: rgba(240,245,250,.9); /* MISMO PLANO QUE EL HERO */
      border-radius: var(--radius-md);
      padding: 14px 16px;
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      color: var(--text-main);
    }

    .indicator i {
      color: var(--accent);
      font-size: .95rem;
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
      header {
        position: static;
      }

      .hero {
        padding: 40px 6vw 70px;
        align-items: flex-start;
      }

      .hero-grid {
        grid-template-columns: 1fr;
        gap: 32px;
        text-align: center;
      }

      .hero-text {
        margin: 0 auto 26px;
      }

      .hero-actions {
        justify-content: center;
      }
    }
  </style>
</head>

<body>

<section class="hero">
  <div class="hero-grid">

    <!-- IZQUIERDA -->
    <div>
      <h1 class="hero-title">
        Gestión <span>inteligente</span><br>
        de Seguridad y Salud
      </h1>

      <p class="hero-text">
        Centraliza tu SG-SST, mejora el control normativo y toma decisiones
        basadas en datos reales. Simple, seguro y profesional.
      </p>

      <div class="hero-actions">
        <a href="login.php" class="btn-primary">
          <i class="fa-solid fa-right-to-bracket"></i>
          Iniciar sesión
        </a>

        <a href="register.php" class="btn-secondary">
          <i class="fa-solid fa-user-plus"></i>
          Registrarse
        </a>
      </div>
    </div>

    <!-- DERECHA -->
    <div class="info-card">
      <h3>Plataforma SG-SST</h3>
      <p>
        Control centralizado del sistema, seguimiento continuo y trazabilidad
        completa para la toma de decisiones.
      </p>

      <div class="indicators">
        <div class="indicator">
          <i class="fa-solid fa-check-circle"></i>
          Cumplimiento normativo
        </div>
        <div class="indicator">
          <i class="fa-solid fa-route"></i>
          Trazabilidad total
        </div>
        <div class="indicator">
          <i class="fa-solid fa-chart-column"></i>
          Indicadores clave
        </div>
        <div class="indicator">
          <i class="fa-solid fa-bell"></i>
          Alertas y control
        </div>
      </div>
    </div>

  </div>
</section>

</body>
</html>
