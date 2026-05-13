<style>
  /* TOP BAR CLARA Y MINIMALISTA */
  .top-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 36px;
    background: #ffffff;
    color: #475569;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 6vw;
    z-index: 1001;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    border-bottom: 1px solid #e2e8f0;
    overflow: hidden;
  }
  .top-bar a {
    color: #475569;
    text-decoration: none;
    transition: color 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .top-bar a:hover { color: var(--primary, #ff8a1f); }
  
  .top-bar-left, .top-bar-right { display: flex; align-items: center; }
  .top-bar-left { gap: 20px; }
  .top-bar-right { gap: 16px; }
  .top-bar-right i { font-size: 0.9rem; }

  /* HEADER PRINCIPAL BLANCO TRANSLÚCIDO */
  .public-header {
    width: 100%;
    padding: 16px 6vw;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 36px; 
    left: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s ease;
  }
  
  .public-header .logo { display: flex; align-items: center; text-decoration: none; }
  .public-header .logo img { height: 28px; object-fit: contain; }
  
  .public-header .header-nav { display: flex; gap: 24px; align-items: center; }
  .public-header .header-link { text-decoration: none; color: #334155; font-weight: 600; font-size: 0.95rem; transition: color 0.2s; white-space: nowrap;}
  .public-header .header-link:hover { color: var(--blue-main, #2b5a9e); }
  
  /* ESTILOS DEL BOTÓN INICIAR SESIÓN (Por si no están globales) */
  .public-header .header-btn { 
      padding: 10px 20px !important; 
      font-size: 0.9rem !important; 
      white-space: nowrap;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 700;
      transition: all 0.2s ease;
  }
  .btn-primary {
      background-color: var(--primary, #ff8a1f);
      color: #ffffff !important;
  }
  .btn-primary:hover {
      background-color: var(--primary-dark, #ff7a00);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(255, 138, 31, 0.25);
  }
  
  /* RESPONSIVE TABLET */
  @media (max-width: 992px) {
    .public-header .hide-mobile { display: none; }
    .public-header .header-nav { gap: 16px; }
    .public-header .logo img { height: 26px; } 
  }

  /* RESPONSIVE MÓVIL (CORREGIDO PARA ESPACIOS) */
  @media (max-width: 768px) {
    .top-bar { padding: 0 4vw; }
    .top-bar-left .hide-mobile-top { display: none; } 
    .top-bar-left { gap: 12px; }
    .top-bar-right { gap: 12px; }

    .public-header { padding: 12px 4vw; background: rgba(255, 255, 255, 0.95); }
    .public-header .header-nav { gap: 10px; }
    .public-header .header-link { font-size: 0.85rem; }
    .public-header .header-btn { padding: 8px 12px !important; font-size: 0.85rem !important; }
    .public-header .logo img { height: 22px; } 
  }

  /* PANTALLAS MUY PEQUEÑAS (iPhone SE, etc.) */
  @media (max-width: 400px) {
    .public-header .logo img { height: 18px; } /* Logo un poco más chico para que quepan los botones */
    .public-header .header-nav { gap: 8px; }
    .public-header .header-link { font-size: 0.8rem; }
    .public-header .header-btn { padding: 6px 10px !important; font-size: 0.8rem !important; }
  }
</style>

<div class="top-bar">
    <div class="top-bar-left">
        <a href="login.php" style="color: var(--primary, #ff8a1f);">
            <i class="fa-solid fa-laptop-code"></i> Sucursal Virtual
        </a>
        <a href="faq.php" class="hide-mobile-top">FAQ y Comunidad</a>
    </div>
    <div class="top-bar-right">
        <a href="#" target="_blank" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
        <a href="#" target="_blank" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="#" target="_blank" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="#" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
    </div>
</div>

<header class="public-header fade-in-up">
  <a href="index.php" class="logo">
    <img src="assets/logo_preventwork.png" alt="PREVENTWORK" onerror="this.outerHTML='<span style=\'font-weight:800; color:#1e3a8a; font-size:1.2rem;\'>SG-SST <span style=\'color:#ff8a1f\'>Pro</span></span>'">
  </a>
  <div class="header-nav">
    <a href="index.php" class="header-link hide-mobile">Inicio</a>
    <a href="index.php#planes" class="header-link hide-mobile">Planes</a>
    <a href="nosotros.php" class="header-link hide-mobile">Quiénes somos</a>
    <a href="contacto.php" class="header-link hide-mobile">Contacto</a>
    
    <a href="register.php" class="header-link">Regístrate</a>
    <a href="login.php" class="btn-primary header-btn">Iniciar Sesión</a>
  </div>
</header>