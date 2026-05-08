<style>
  .floating-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 9999;
  }

  .float-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    outline: none;
  }

  .float-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    color: white;
  }

  /* Estilo WhatsApp */
  .btn-whatsapp {
    background-color: #25D366;
  }
  .btn-whatsapp:hover {
    background-color: #1ebe57;
  }

  /* Estilo Volver Arriba */
  .btn-scroll-top {
    background-color: var(--blue-dark, #1e3a8a);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
  }
  .btn-scroll-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  /* Responsive Móvil */
  @media (max-width: 768px) {
    .floating-container {
        bottom: 20px;
        right: 20px;
    }
    .float-btn {
        width: 45px;
        height: 45px;
        font-size: 1.3rem;
    }
  }
</style>

<div class="floating-container">
    <button class="float-btn btn-scroll-top" id="btnScrollTop" title="Volver arriba" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
        <i class="fa-solid fa-chevron-up"></i>
    </button>

    <a href="https://wa.me/573000000000?text=Hola,%20necesito%20más%20información%20sobre%20PREVENTWORK." target="_blank" class="float-btn btn-whatsapp" title="Contáctanos por WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
</div>

<script>
    // Mostrar/ocultar botón de scroll top
    window.addEventListener('scroll', function() {
        var btnScroll = document.getElementById('btnScrollTop');
        if (window.scrollY > 300) {
            btnScroll.classList.add('show');
        } else {
            btnScroll.classList.remove('show');
        }
    });
</script>