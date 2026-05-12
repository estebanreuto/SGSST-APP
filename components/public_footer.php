<style>
  /* FOOTER CLARO Y MODERNO */
  .public-footer {
    background-color: #ffffff;
    color: #475569;
    padding: 60px 6vw 30px;
    font-family: 'Inter', sans-serif;
    border-top: 1px solid #e2e8f0;
  }

  .footer-container {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
    gap: 40px;
    margin-bottom: 50px;
  }

  .footer-col h4 {
    color: #0f172a; 
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .footer-logo-col img {
    height: 32px;
    margin-bottom: 20px;
    object-fit: contain;
  }

  .footer-about-text {
    line-height: 1.6;
    font-size: 0.9rem;
    margin-bottom: 20px;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-links li { margin-bottom: 12px; }

  .footer-links a {
    color: #475569;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
  }

  .footer-links a:hover {
    color: var(--accent, #ff8a1f);
    padding-left: 5px; 
  }

  .footer-socials { display: flex; gap: 15px; }

  .social-icon {
    width: 38px;
    height: 38px;
    background: #f1f5f9; 
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.1rem;
  }

  .social-icon:hover {
    background: var(--accent, #ff8a1f);
    border-color: var(--accent, #ff8a1f);
    color: #ffffff;
    transform: translateY(-3px);
  }

  /* BARRA DE COPYRIGHT CLARA */
  .footer-bottom {
    max-width: 1280px;
    margin: 0 auto;
    padding-top: 30px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #64748b;
  }

  /* RESPONSIVE TABLET */
  @media (max-width: 992px) { 
      .footer-container { grid-template-columns: 1fr 1fr; } 
  }
  
  /* RESPONSIVE MÓVIL (MAGIA PARA OCULTAR) */
  @media (max-width: 768px) {
    /* Esta es la clase que oculta las columnas en celulares */
    .hide-mobile-footer { display: none !important; }
    
    .footer-container { grid-template-columns: 1fr; text-align: center; gap: 30px; margin-bottom: 30px;}
    .footer-socials { justify-content: center; }
    .footer-bottom { flex-direction: column; gap: 15px; text-align: center; }
    .public-footer { padding: 40px 6vw 20px; } /* Footer más pequeñito en celular */
  }
</style>

<footer class="public-footer">
  <div class="footer-container">
    <div class="footer-col footer-logo-col">
      <img src="assets/logo_preventwork.png" alt="PREVENTWORK" onerror="this.outerHTML='<span style=\'font-weight:800; color:#1e3a8a; font-size:1.4rem;\'>SG-SST <span style=\'color:#ff8a1f\'>Pro</span></span>'">
      <p class="footer-about-text">
        Solución integral para la gestión de Seguridad y Salud en el Trabajo. Ayudamos a las empresas a cumplir la normatividad de forma digital, ágil y segura.
      </p>
      <div class="footer-socials">
        <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
        <a href="#" class="social-icon"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="#" class="social-icon"><i class="fa-brands fa-youtube"></i></a>
      </div>
    </div>

    <div class="footer-col hide-mobile-footer">
      <h4>Navegación</h4>
      <ul class="footer-links">
        <li><a href="index.php">Inicio</a></li>
        <li><a href="index.php#caracteristicas">Características</a></li>
        <li><a href="index.php#planes">Planes</a></li>
        <li><a href="index.php#nosotros">Quiénes somos</a></li>
      </ul>
    </div>

    <div class="footer-col hide-mobile-footer">
      <h4>Legal</h4>
      <ul class="footer-links">
        <li><a href="terminos.php">Términos y Condiciones</a></li>
        <li><a href="privacidad.php">Política de Privacidad</a></li>
        <li><a href="cookies.php">Política de Cookies</a></li>
        <li><a href="faq.php">Preguntas Frecuentes (FAQ)</a></li>
      </ul>
    </div>

    <div class="footer-col hide-mobile-footer">
      <h4>Contacto</h4>
      <ul class="footer-links">
        <li><a href="mailto:soporte@preventwork.com"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i> soporte@preventwork.com</a></li>
        <li><a href="https://wa.me/573012994599" target="_blank"><i class="fa-solid fa-phone" style="margin-right: 8px;"></i> +57 301 299 4599</a></li>
        <li><a href="login.php"><i class="fa-solid fa-laptop-code" style="margin-right: 8px;"></i> Sucursal Virtual 24/7</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> <strong>PREVENTWORK</strong>. Todos los derechos reservados.</p>
    <p>Desarrollado para el cumplimiento de la Resolución 0312.</p>
  </div>
</footer>