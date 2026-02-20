<style>
    /* Fondo del Modal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.5);
        display: none; 
        justify-content: center;
        align-items: center; 
        z-index: 2000; 
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 0.3s ease;
        padding: 16px; /* Espacio para que en celular no toque los bordes superior/inferior */
        box-sizing: border-box;
    }

    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    /* Caja del Modal */
    .modal-content {
        background: var(--card, #ffffff);
        border-radius: var(--radius, 16px);
        width: 100%;
        max-width: 850px; 
        max-height: 100%; /* Permite que crezca hasta el límite del padding del overlay */
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: translateY(-30px);
        transition: transform 0.3s ease;
        overflow: hidden; 
    }

    .modal-overlay.active .modal-content {
        transform: translateY(0);
    }

    .modal-header {
        padding: 20px 28px;
        border-bottom: 1px solid var(--border, #dbe3ec);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        flex-shrink: 0; /* Evita que el header se encoja */
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.2rem;
        color: var(--text, #1f2d3d);
    }

    .btn-close {
        background: none;
        border: none;
        color: var(--muted, #5f6f82);
        cursor: pointer;
        padding: 6px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }

    .btn-close:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* SOLUCIÓN AL SCROLL DEL CELULAR AQUÍ */
    .modal-body {
        padding: 28px;
        overflow-y: auto; 
        flex: 1; /* Permite que el cuerpo ocupe el espacio restante */
        min-height: 0; /* Obliga al navegador móvil a calcular el scroll correctamente */
    }

    .grid-form {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px 20px;
    }

    .field label {
        display: block;
        font-size: .75rem;
        font-weight: 600;
        margin: 0 0 6px;
        color: var(--text, #1f2d3d);
    }

    .control {
        position: relative;
    }

    .icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        opacity: .45;
        color: #94a3b8;
        pointer-events: none;
        transition: all .2s ease;
    }

    .control input, .control select {
        width: 100%;
        padding: 10px 12px 10px 38px; 
        font-size: .85rem;
        border: 1px solid var(--border, #dbe3ec);
        border-radius: 10px;
        background: #f8fafc;
        color: #64748b;
        transition: all .2s ease;
        box-sizing: border-box;
    }

    .control input:focus, .control select:focus {
        outline: none;
        border-color: var(--primary, #ff8a1f);
        box-shadow: 0 0 0 3px rgba(255, 138, 31, .14);
        background: #fff;
        color: var(--text, #1f2d3d);
    }

    .control input:focus ~ .icon, .control select:focus ~ .icon {
        opacity: .8;
        color: var(--primary, #ff8a1f);
    }

    .control input:not(:placeholder-shown), .control select:not([value=""]) {
        background: #fff;
        color: var(--text, #1f2d3d);
    }

    .control input:not(:placeholder-shown) ~ .icon, .control select:not([value=""]) ~ .icon {
        opacity: .7;
        color: var(--text, #1f2d3d);
    }

    .full-width {
        grid-column: span 2;
    }

    .modal-footer {
        padding: 16px 28px;
        border-top: 1px solid var(--border, #dbe3ec);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #f8fafc;
        flex-shrink: 0; /* Evita que el footer se encoja */
    }

    .btn-cancel {
        background: #fff;
        color: var(--text, #1f2d3d);
        border: 1px solid var(--border, #dbe3ec);
        padding: 10px 20px;
        border-radius: 8px;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #f1f5f9;
    }

    /* ====== RESPONSIVE MODAL ====== */
    @media (max-width: 768px) {
        .modal-body, .modal-header, .modal-footer {
            padding: 20px; 
        }
        .grid-form {
            grid-template-columns: 1fr; /* 1 Sola columna en celular */
            gap: 12px;
        }
        .full-width {
            grid-column: span 1; 
        }
    }
</style>

<div class="modal-overlay" id="modalEdit">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Perfil</h3>
            <button class="btn-close" id="btnCloseModal" type="button">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form action="actualizar_perfil.php" method="POST" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
            <div class="modal-body">
                
                <h4 class="section-title" style="margin-top: 0; margin-bottom: 16px;">Información Personal</h4>
                <div class="grid-form">
                    
                    <div class="field">
                        <label>Nombre</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" />
                            </svg>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario_info['nombre'] ?? ''); ?>" required placeholder=" ">
                        </div>
                    </div>

                    <div class="field">
                        <label>Apellido</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" />
                            </svg>
                            <input type="text" name="apellido" value="<?php echo htmlspecialchars($usuario_info['apellido'] ?? ''); ?>" required placeholder=" ">
                        </div>
                    </div>

                    <div class="field">
                        <label>Cédula</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10M7 12h10M7 16h6" />
                            </svg>
                            <input type="text" name="cedula" value="<?php echo htmlspecialchars($usuario_info['cedula'] ?? ''); ?>" required placeholder=" ">
                        </div>
                    </div>

                    <div class="field">
                        <label>Teléfono</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.86.31 1.7.57 2.5a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.58-1.09a2 2 0 0 1 2.11-.45c.8.26 1.64.45 2.5.57a2 2 0 0 1 1.72 2v3z" />
                            </svg>
                            <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario_info['telefono'] ?? ''); ?>" placeholder=" ">
                        </div>
                    </div>

                    <div class="field full-width">
                        <label>Ciudad</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />
                            </svg>
                            <input type="text" name="ciudad" value="<?php echo htmlspecialchars($usuario_info['ciudad'] ?? ''); ?>" placeholder=" ">
                        </div>
                    </div>
                </div>

                <?php if ($usuario_rol === 'sst' || $usuario_rol === 'representante'): ?>
                <h4 class="section-title" style="margin-top: 32px; margin-bottom: 16px;">Certificación SG-SST</h4>
                <div class="grid-form">
                    
                    <div class="field">
                        <label>¿Tiene Licencia SST?</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4" /><circle cx="12" cy="12" r="10" />
                            </svg>
                            <select name="licencia_sst">
                                <option value="no" <?php echo (isset($usuario_info['licencia_sst']) && $usuario_info['licencia_sst'] == 'no') ? 'selected' : ''; ?>>No</option>
                                <option value="si" <?php echo (isset($usuario_info['licencia_sst']) && $usuario_info['licencia_sst'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label>Tipo de Licencia</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <input type="text" name="tipo_licencia" value="<?php echo htmlspecialchars($usuario_info['tipo_licencia'] ?? ''); ?>" placeholder=" ">
                        </div>
                    </div>

                    <div class="field">
                        <label>Número de Licencia</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>
                            </svg>
                            <input type="text" name="numero_licencia" value="<?php echo htmlspecialchars($usuario_info['numero_licencia'] ?? ''); ?>" placeholder=" ">
                        </div>
                    </div>

                    <div class="field">
                        <label>Fecha de Expedición</label>
                        <div class="control">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <input type="date" name="fecha_licencia" value="<?php echo htmlspecialchars($usuario_info['fecha_licencia_raw'] ?? ''); ?>" placeholder=" ">
                        </div>
                    </div>

                </div>
                <?php endif; ?>

            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="btnCancelModal">Cancelar</button>
                <button type="submit" class="btn-edit" style="width: auto;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnOpen = document.getElementById('btnOpenModal');
        const modal = document.getElementById('modalEdit');
        const btnClose = document.getElementById('btnCloseModal');
        const btnCancel = document.getElementById('btnCancelModal');

        if(btnOpen) {
            btnOpen.addEventListener('click', () => {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; 
            });
        }

        const closeModal = () => {
            modal.classList.remove('active');
            if (!document.getElementById('mainSidebar') || !document.getElementById('mainSidebar').classList.contains('active')) {
                document.body.style.overflow = 'auto'; 
            }
        }

        if(btnClose) btnClose.addEventListener('click', closeModal);
        if(btnCancel) btnCancel.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });
</script>