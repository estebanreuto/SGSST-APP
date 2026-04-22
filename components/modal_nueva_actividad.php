<?php
// Validar que solo se renderice para el Responsable SST
if (isset($usuario_rol) && $usuario_rol === 'sst'): 
?>

<style>
    /* =========================================
       OVERLAY DEL MODAL (Fondo Oscuro)
       ========================================= */
    .modal-actividad-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: center; z-index: 10000;
        opacity: 0; transition: opacity 0.3s ease; padding: 24px; box-sizing: border-box;
        overflow: hidden; 
    }
    .modal-actividad-overlay.active { display: flex; opacity: 1; }

    /* =========================================
       CAJA PRINCIPAL DEL MODAL
       ========================================= */
    .modal-actividad-box {
        background: #ffffff; border-radius: 20px; 
        width: 100%; max-width: 800px; 
        height: auto; max-height: 90vh; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
        transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; flex-direction: column;
        font-family: 'Inter', sans-serif;
        overflow: visible; /* IMPORTANTE para que el buscador múltiple flote bien */
    }
    .modal-actividad-overlay.active .modal-actividad-box { transform: translateY(0) scale(1); }

    /* =========================================
       HEADER DEL MODAL
       ========================================= */
    .modal-actividad-header {
        background: linear-gradient(to right, #f8fafc, #ffffff);
        padding: 24px 32px; padding-right: 64px;
        border-bottom: 1px solid var(--border); border-top-left-radius: 20px; border-top-right-radius: 20px;
        text-align: left; position: relative; display: flex; align-items: center; gap: 16px;
        flex: 0 0 auto;
    }
    
    .btn-close-actividad {
        position: absolute; top: 24px; right: 24px;
        background: #f1f5f9; border: none; width: 36px; height: 36px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: var(--muted); cursor: pointer; transition: all 0.2s;
    }
    .btn-close-actividad:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }

    .modal-actividad-icon-top {
        width: 48px; height: 48px;
        background: rgba(255, 138, 31, 0.1); color: #ff8a1f;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1);
    }
    .modal-actividad-icon-top svg { width: 24px; height: 24px; }
    
    .modal-actividad-header-text h3 { margin: 0 0 4px 0; font-size: 1.15rem; color: #1e293b; font-weight: 800; letter-spacing: -0.01em; }
    .modal-actividad-header-text p { margin: 0; color: var(--muted); font-size: 0.85rem; line-height: 1.4; }

    /* =========================================
       CUERPO DEL MODAL (Zona de Scroll)
       ========================================= */
    .modal-actividad-body { 
        padding: 32px; 
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto; 
        background: #ffffff;
    }

    .modal-actividad-body::-webkit-scrollbar { width: 6px; }
    .modal-actividad-body::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
    .modal-actividad-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .modal-actividad-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    /* WARNING ESTILO EMPRESA */
    .unsaved-warning { display: none; background: #fff3cd; color: #856404; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; margin-bottom: 24px; border: 1px solid #ffeeba; align-items: center; gap: 10px; }
    .unsaved-warning.active { display: flex; animation: shake 0.5s; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

    /* =========================================
       ESTILOS DEL FORMULARIO BÁSICO
       ========================================= */
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; text-align: left; position: relative; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.8rem; color: #334155; }
    
    /* Wrapper Relativo para Íconos */
    .input-icon-wrapper { position: relative; width: 100%; }
    .input-icon-wrapper > svg.icon-form { 
        position: absolute; left: 14px; top: 13px; /* Anclado arriba para que no se mueva si crece a 2 líneas */
        color: #94a3b8; transition: color 0.3s; pointer-events: none; z-index: 10;
        width: 18px; height: 18px;
    }
    
    /* Color del ícono cuando el contenedor tiene el foco (Click) */
    .input-icon-wrapper:focus-within > svg.icon-form { color: var(--primary); }
    
    /* Estilos SOLO para inputs y selects estandar (Excluye a Select2) */
    .input-icon-wrapper > input.actividad-input, 
    .input-icon-wrapper > select.actividad-input { 
        width: 100%; padding: 10px 14px 10px 40px; border: 1px solid #cbd5e1; 
        border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b;
        transition: all 0.3s ease; box-sizing: border-box; background: #ffffff; height: 44px;
    }
    
    .input-icon-wrapper > select.actividad-input {
        appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center; background-size: 16px;
    }
    
    .input-icon-wrapper > input.actividad-input:focus, 
    .input-icon-wrapper > select.actividad-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }

    /* =========================================
       ESTILOS SELECT2 MULTIPLE (PÍLDORAS)
       ========================================= */
    
    /* Ocultar el ícono de 'Limpiar todo' de Select2 que daña el diseño */
    .select2-container--default .select2-selection--multiple .select2-selection__clear {
        display: none !important; 
    }

    /* Caja principal Select2 Múltiple */
    .select2-container--default .select2-selection--multiple {
        background-color: #ffffff;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        min-height: 44px !important;
        padding: 0 14px 0 40px !important; /* Espacio exclusivo para el ícono a la izquierda */
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    /* Foco de la caja */
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15) !important;
    }

    /* El contenedor de las píldoras y el input */
    .select2-container .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
        padding: 6px 0 !important;
        margin: 0 !important;
        list-style: none;
        width: 100%;
        align-items: center;
    }

    /* Las píldoras individuales */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #fff8f3 !important;
        border: 1px solid rgba(255, 138, 31, 0.3) !important;
        color: var(--primary2) !important;
        border-radius: 6px !important;
        padding: 4px 8px !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: row-reverse !important; /* Invierte orden: X a la derecha */
        align-items: center !important;
        gap: 6px !important;
        font-weight: 600 !important;
        font-size: 0.8rem;
    }

    /* La X para cerrar la píldora */
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #ff8a1f !important;
        border: none !important;
        background: transparent !important;
        font-weight: 700 !important;
        font-size: 1.1rem !important;
        padding: 0 !important;
        margin: 0 !important;
        position: static !important; /* Previene posiciones extrañas */
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #dc2626 !important;
    }

    /* Contenedor del Input de Búsqueda */
    .select2-container .select2-selection--multiple .select2-search--inline {
        float: none !important; /* Desactiva el float nativo que rompe el flex */
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
    }

    /* Input de Búsqueda */
    .select2-container--default .select2-selection--multiple .select2-search__field {
        font-family: 'Inter', sans-serif !important;
        color: var(--text) !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 24px !important;
        line-height: 24px !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .select2-container--default .select2-selection--multiple .select2-search__field::placeholder {
        color: #94a3b8 !important;
    }

    /* Menú desplegable */
    .select2-dropdown { 
        border: 1px solid #cbd5e1; 
        border-radius: 8px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
        font-family: 'Inter', sans-serif; 
        font-size: 0.85rem; 
        z-index: 10001; 
        overflow: hidden; 
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { 
        background-color: var(--primary); color: white; 
    }

    /* =========================================
       FOOTER DEL MODAL
       ========================================= */
    .modal-actividad-footer {
        padding: 20px 32px; border-top: 1px solid var(--border); background: #f8fafc;
        display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
        flex: 0 0 auto;
    }
    .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem; }
    .btn-secondary:hover { background: #f1f5f9; color: #1e293b; }
    .btn-primary-act { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; gap: 8px; }
    .btn-primary-act:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(255, 138, 31, 0.25); }

    /* =========================================
       RESPONSIVE MÓVILES
       ========================================= */
    @media (max-width: 768px) {
        .modal-actividad-overlay { padding: 0; }
        .modal-actividad-box { height: 100%; max-height: 100%; border-radius: 0; }
        .modal-actividad-header { padding: 16px 20px; padding-right: 60px; gap: 12px; }
        .modal-actividad-icon-top { width: 40px; height: 40px; border-radius: 10px;}
        .modal-actividad-icon-top svg { width: 20px; height: 20px; }
        .modal-actividad-header-text h3 { font-size: 1.05rem; }
        .modal-actividad-header-text p { font-size: 0.75rem; }
        .btn-close-actividad { top: 20px; right: 16px; width: 32px; height: 32px; }
        .modal-actividad-body { padding: 20px 16px 30px 16px; }
        .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
        .form-group { margin-bottom: 16px; }

        .modal-actividad-footer { flex-direction: column; padding: 16px; gap: 12px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); }
        .btn-primary-act { order: 1; width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem;}
        .btn-secondary { order: 2; width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem;}
    }
</style>

<div class="modal-actividad-overlay" id="modalNuevaActividad">
    <form id="formRegistroActividad" action="procesar_estandar3.php" method="POST" class="modal-actividad-box">
        <input type="hidden" name="accion" value="crear_actividad">
        
        <div class="modal-actividad-header">
            <div class="modal-actividad-icon-top">
                <svg fill="none" stroke="#ff8a1f" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253"></path>
                </svg>
            </div>
            <div class="modal-actividad-header-text">
                <h3>Programar Nueva Capacitación</h3>
                <p>Registra los detalles y la temática de la actividad.</p>
            </div>
            <button type="button" class="btn-close-actividad" id="btnCerrarModalActividad" title="Cerrar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="modal-actividad-body">
            
            <div class="unsaved-warning" id="warningUnsavedActividad">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Tienes cambios sin guardar. Haz clic en "Guardar y Continuar".
            </div>

            <div class="form-group full">
                <label for="nombre_actividad">Nombre de la Actividad *</label>
                <div class="input-icon-wrapper">
                    <svg class="icon-form" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <input type="text" name="nombre_actividad" id="nombre_actividad" class="actividad-input" required placeholder="Ej. Uso y manejo de extintores">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="tipo_capacitacion">Tipo de Capacitación *</label>
                    <div class="input-icon-wrapper">
                        <svg class="icon-form" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <select name="tipo_capacitacion" id="tipo_capacitacion" class="actividad-input" required>
                            <option value="">Selecciona...</option>
                            <option value="Inducción">Inducción</option>
                            <option value="Re Inducción">Re Inducción</option>
                            <option value="Charla de Seguridad">Charla de Seguridad</option>
                            <option value="Capacitación">Capacitación</option>
                            <option value="Entrenamiento">Entrenamiento</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <div class="input-icon-wrapper">
                        <svg class="icon-form" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <select name="categoria" id="categoria" class="actividad-input" required>
                            <option value="">Selecciona...</option>
                            <option value="Biológico">Biológico</option>
                            <option value="Físico">Físico</option>
                            <option value="Químico">Químico</option>
                            <option value="Psicosocial">Psicosocial</option>
                            <option value="Biomecánicos">Biomecánicos</option>
                            <option value="Mecánico">Mecánico</option>
                            <option value="Eléctrico">Eléctrico</option>
                            <option value="Locativo">Locativo</option>
                            <option value="Tecnológico">Tecnológico</option>
                            <option value="Seguridad Vial">Seguridad Vial</option>
                            <option value="Públicos">Públicos</option>
                            <option value="Trabajo en alturas">Trabajo en alturas</option>
                            <option value="Espacios Confinados">Espacios Confinados</option>
                            <option value="Excavaciones">Excavaciones</option>
                            <option value="Trabajo en Caliente">Trabajo en Caliente</option>
                            <option value="Legal">Legal</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group full">
                <label for="dirigido_a">Dirigido a *</label>
                <div class="input-icon-wrapper">
                    <svg class="icon-form" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <select name="dirigido_a" id="dirigido_a" class="actividad-input" required>
                        <option value="">Selecciona a quién va dirigido...</option>
                        <option value="Toda la empresa">Toda la Empresa</option>
                        <option value="Trabajador Específico">Trabajador Específico</option>
                        
                        <?php if (isset($grupos) && !empty($grupos)): ?>
                            <?php foreach ($grupos as $g): ?>
                                <option value="Grupo: <?php echo htmlspecialchars($g['nombre']); ?>">
                                    Grupo: <?php echo htmlspecialchars($g['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group full" id="contenedor_trabajadores_especificos" style="display: none; animation: fadeIn 0.3s ease;">
                <label for="trabajadores_seleccionados">Selecciona los Trabajadores *</label>
                <div class="input-icon-wrapper">
                    <svg class="icon-form" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    
                    <select name="trabajadores_seleccionados[]" id="trabajadores_seleccionados" multiple="multiple" style="width: 100%;">
                        <?php if (isset($trabajadores_activos) && !empty($trabajadores_activos)): ?>
                            <?php foreach ($trabajadores_activos as $ta): ?>
                                <option value="<?php echo $ta['id']; ?>">
                                    <?php echo htmlspecialchars($ta['nombre'] . ' ' . $ta['apellido'] . ' - C.C. ' . $ta['cedula']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

        </div>

        <div class="modal-actividad-footer">
            <button type="button" class="btn-secondary" id="btnDescartarActividad">Descartar Cambios</button>
            <button type="submit" class="btn-primary-act">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Guardar y Continuar
            </button>
        </div>
        
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnOpenModal = document.getElementById('btnCrearActividad');
        const btnCloseModal = document.getElementById('btnCerrarModalActividad');
        const btnDescartar = document.getElementById('btnDescartarActividad');
        const modalActividad = document.getElementById('modalNuevaActividad');
        const formActividad = document.getElementById('formRegistroActividad');
        const warningUnsaved = document.getElementById('warningUnsavedActividad');
        
        let isFormDirtyAct = false;

        // Detectar cambios en los inputs regulares
        const inputsAct = document.querySelectorAll('.actividad-input:not([multiple])');
        inputsAct.forEach(input => {
            input.addEventListener('input', () => { isFormDirtyAct = true; });
            input.addEventListener('change', () => { isFormDirtyAct = true; });
        });

        // ===============================================
        // LÓGICA DE INICIALIZACIÓN SELECT2
        // ===============================================
        if ($.fn.select2) {
            $('#trabajadores_seleccionados').select2({
                placeholder: "Busca y selecciona...",
                allowClear: true,
                dropdownParent: $('#modalNuevaActividad')
            });

            // Registrar si hacen cambios en el selector múltiple
            $('#trabajadores_seleccionados').on('change', function() {
                isFormDirtyAct = true;
            });
        }

        const selectDirigidoA = document.getElementById('dirigido_a');
        const contenedorTrabajadores = document.getElementById('contenedor_trabajadores_especificos');
        const selectTrabajadores = document.getElementById('trabajadores_seleccionados');

        if (selectDirigidoA) {
            selectDirigidoA.addEventListener('change', function() {
                if (this.value === 'Trabajador Específico') {
                    contenedorTrabajadores.style.display = 'block';
                    selectTrabajadores.setAttribute('required', 'required');
                } else {
                    contenedorTrabajadores.style.display = 'none';
                    selectTrabajadores.removeAttribute('required');
                    // Limpiar la selección si se arrepienten
                    $('#trabajadores_seleccionados').val(null).trigger('change'); 
                }
            });
        }

        // ===============================================
        // MANEJO DE ESTADOS DEL MODAL
        // ===============================================
        function openModal() {
            modalActividad.classList.add('active');
            document.body.style.overflow = 'hidden'; 
        }

        const attemptCloseModal = (e) => {
            e.preventDefault();
            if (isFormDirtyAct) {
                warningUnsaved.classList.remove('active');
                void warningUnsaved.offsetWidth; 
                warningUnsaved.classList.add('active');
                
                const modalBody = document.querySelector('.modal-actividad-body');
                if (modalBody) modalBody.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                modalActividad.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        if (btnOpenModal && modalActividad) {
            btnOpenModal.addEventListener('click', openModal);
        }

        if (btnCloseModal && modalActividad) {
            btnCloseModal.addEventListener('click', attemptCloseModal);
        }

        if (btnDescartar && modalActividad) {
            btnDescartar.addEventListener('click', (e) => {
                formActividad.reset();
                $('#trabajadores_seleccionados').val(null).trigger('change');
                contenedorTrabajadores.style.display = 'none';
                selectTrabajadores.removeAttribute('required');

                isFormDirtyAct = false;
                modalActividad.classList.remove('active');
                warningUnsaved.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });
</script>

<?php endif; ?>