<style>
    .cookie-banner {
        /* POSICIÓN: Abajo a la izquierda */
        position: fixed; 
        bottom: 24px; 
        left: 24px; 
        
        /* DISEÑO DE LA TARJETA */
        background: #1e293b; 
        color: #f8fafc; 
        padding: 20px; 
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.25); 
        display: flex; 
        flex-direction: column; 
        gap: 16px;
        z-index: 99999; 
        font-family: 'Inter', sans-serif; 
        font-size: 0.85rem; 
        max-width: 340px; /* Más compacto para la esquina */
        width: 100%;
        
        /* ANIMACIÓN */
        opacity: 0; 
        visibility: hidden; 
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        transform: translateY(20px); /* Aparece de abajo hacia arriba */
    }
    
    .cookie-banner.show {
        opacity: 1; 
        visibility: visible; 
        transform: translateY(0);
    }
    
    .cookie-content { 
        line-height: 1.5; 
        color: #cbd5e1;
    }
    
    .cookie-content strong {
        display: block;
        color: #ffffff;
        font-size: 0.95rem;
        margin-bottom: 6px;
    }
    
    .cookie-buttons { 
        display: flex; 
        gap: 10px; 
        width: 100%; 
        justify-content: flex-end; /* Botones alineados a la derecha */
    }
    
    .btn-cookie {
        padding: 8px 16px; 
        border-radius: 8px; 
        font-weight: 600; 
        cursor: pointer; 
        transition: all 0.2s; 
        border: none; 
        font-size: 0.8rem;
    }
    
    .btn-cookie-accept { 
        background: var(--primary, #ff8a1f); 
        color: white; 
    }
    .btn-cookie-accept:hover { 
        background: var(--primary2, #ff7a00); 
        transform: translateY(-1px);
    }
    
    .btn-cookie-reject { 
        background: rgba(255,255,255,0.1); 
        color: white; 
    }
    .btn-cookie-reject:hover { 
        background: rgba(255,255,255,0.2); 
    }
    
    /* ADAPTACIÓN PARA CELULARES */
    @media (max-width: 480px) {
        .cookie-banner { 
            left: 16px; 
            right: 16px; 
            bottom: 16px; 
            max-width: calc(100% - 32px); 
        }
        .cookie-buttons { 
            justify-content: stretch; 
        }
        .btn-cookie { 
            flex: 1; 
            text-align: center;
        }
    }
</style>

<div class="cookie-banner" id="cookieBanner">
    <div class="cookie-content">
        <strong>Privacidad y Cookies</strong>
        Utilizamos cookies esenciales para recordar tu sesión y mejorar tu experiencia en la plataforma.
    </div>
    <div class="cookie-buttons">
        <button class="btn-cookie btn-cookie-reject" onclick="manejarCookies(false)">Rechazar</button>
        <button class="btn-cookie btn-cookie-accept" onclick="manejarCookies(true)">Entendido</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Verifica si el usuario ya tomó una decisión antes
        const cookieStatus = localStorage.getItem('cookies_sgsst_estado');
        if (!cookieStatus) {
            // Muestra el banner después de 1 segundo de entrar a la página
            setTimeout(() => {
                document.getElementById('cookieBanner').classList.add('show');
            }, 1000);
        }
    });

    function manejarCookies(aceptadas) {
        // Guardamos la decisión en el almacenamiento local para no volver a preguntar
        localStorage.setItem('cookies_sgsst_estado', aceptadas ? 'aceptadas' : 'rechazadas');
        document.getElementById('cookieBanner').classList.remove('show');

        if (!aceptadas) {
            // Si el usuario rechaza, destruimos forzosamente la cookie de "Mantener sesión"
            document.cookie = "sgsst_remember=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    }
</script>