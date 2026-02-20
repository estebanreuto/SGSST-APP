<style>
    /* Fondo del Modal de Confirmación */
    .confirm-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        display: none; 
        justify-content: center;
        align-items: center; 
        z-index: 9999; /* Por encima de todo */
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 0.3s ease;
        padding: 16px;
        box-sizing: border-box;
    }

    .confirm-overlay.active {
        display: flex;
        opacity: 1;
    }

    /* Caja de Confirmación */
    .confirm-box {
        background: var(--card, #ffffff);
        border-radius: var(--radius, 16px);
        width: 100%;
        max-width: 400px; /* Más pequeño que el modal de edición */
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.95);
        transition: transform 0.3s ease;
        overflow: hidden; 
        text-align: center;
    }

    .confirm-overlay.active .confirm-box {
        transform: scale(1);
    }

    .confirm-body {
        padding: 32px 24px 24px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Contenedor del ícono dinámico */
    .confirm-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }

    /* Modificador para advertencia (naranja) o peligro (rojo) */
    .confirm-icon-wrapper.warning { background: rgba(255, 138, 31, 0.15); color: var(--primary2, #ff7a00); }
    .confirm-icon-wrapper.danger { background: #fee2e2; color: #dc2626; }

    .confirm-title {
        margin: 0 0 8px 0;
        font-size: 1.25rem;
        color: var(--text, #1f2d3d);
        font-weight: 700;
    }

    .confirm-message {
        margin: 0;
        color: var(--muted, #5f6f82);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .confirm-footer {
        padding: 16px 24px 24px 24px;
        display: flex;
        justify-content: center;
        gap: 12px;
    }

    .btn-confirm-cancel {
        background: #f1f5f9;
        color: #475569;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        flex: 1;
    }

    .btn-confirm-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .btn-confirm-action {
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Estilos dinámicos para el botón de acción */
    .btn-confirm-action.warning { background: var(--primary, #ff8a1f); color: white; }
    .btn-confirm-action.warning:hover { background: var(--primary2, #ff7a00); }
    
    .btn-confirm-action.danger { background: #ef4444; color: white; }
    .btn-confirm-action.danger:hover { background: #dc2626; }

    /* Responsive */
    @media (max-width: 480px) {
        .confirm-footer {
            flex-direction: column-reverse; /* El cancelar queda abajo en móviles */
        }
        .btn-confirm-cancel, .btn-confirm-action {
            width: 100%;
        }
    }
</style>

<div class="confirm-overlay" id="modalConfirmGeneric">
    <div class="confirm-box">
        <div class="confirm-body">
            <div class="confirm-icon-wrapper" id="confirmIconWrapper">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="confirm-title" id="confirmTitle">¿Estás seguro?</h3>
            <p class="confirm-message" id="confirmMessage">Esta acción no se puede deshacer.</p>
        </div>
        <div class="confirm-footer">
            <button type="button" class="btn-confirm-cancel" id="btnCancelConfirm">Cancelar</button>
            <a href="#" class="btn-confirm-action" id="btnAcceptConfirm">Aceptar</a>
        </div>
    </div>
</div>

<script>
    /**
     * Función global para llamar al modal de confirmación desde cualquier lugar
     * @param {string} title - Título del modal
     * @param {string} message - Mensaje descriptivo
     * @param {string} actionUrl - URL a donde irá si presiona "Aceptar"
     * @param {string} type - 'warning' (naranja) o 'danger' (rojo)
     * @param {string} confirmText - Texto del botón de aceptar
     */
    function showConfirmModal(title, message, actionUrl, type = 'warning', confirmText = 'Aceptar') {
        const modal = document.getElementById('modalConfirmGeneric');
        const titleEl = document.getElementById('confirmTitle');
        const msgEl = document.getElementById('confirmMessage');
        const btnAccept = document.getElementById('btnAcceptConfirm');
        const iconWrapper = document.getElementById('confirmIconWrapper');

        // Llenar datos
        titleEl.textContent = title;
        msgEl.textContent = message;
        btnAccept.href = actionUrl;
        btnAccept.textContent = confirmText;

        // Limpiar clases previas
        iconWrapper.className = 'confirm-icon-wrapper';
        btnAccept.className = 'btn-confirm-action';

        // Aplicar tipo (color)
        iconWrapper.classList.add(type);
        btnAccept.classList.add(type);

        // Mostrar
        modal.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modalConfirmGeneric');
        const btnCancel = document.getElementById('btnCancelConfirm');

        const closeModal = () => {
            modal.classList.remove('active');
        }

        if(btnCancel) btnCancel.addEventListener('click', closeModal);

        // Cerrar al dar click afuera de la cajita blanca
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });
</script>