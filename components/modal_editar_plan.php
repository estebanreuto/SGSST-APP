<style>
    /* ======================================================== */
    /* MODAL DE EDICIÓN DE PLANES (CONSTRUCTOR DINÁMICO)        */
    /* ======================================================== */
    .modal-plan-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); 
        display: none; justify-content: center; align-items: center; 
        z-index: 10000; opacity: 0; transition: opacity 0.3s ease; padding: 20px; box-sizing: border-box; 
    }
    .modal-plan-overlay.active { display: flex; opacity: 1; }
    
    .modal-plan-box { 
        background: #ffffff; border-radius: 20px; width: 100%; max-width: 900px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); 
        transform: translateY(-30px) scale(0.95); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; 
    }
    .modal-plan-overlay.active .modal-plan-box { transform: translateY(0) scale(1); }
    
    .modal-plan-header { 
        background: linear-gradient(to right, #f8fafc, #ffffff); padding: 20px 30px; 
        border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 14px; position: relative; 
    }
    .modal-plan-icon { 
        width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; 
        flex-shrink: 0; background: rgba(255, 138, 31, 0.1); color: var(--primary); 
    }
    .modal-plan-header-text h3 { margin: 0 0 2px 0; font-size: 1.05rem; color: #1e293b; font-weight: 800; }
    .modal-plan-header-text p { margin: 0; color: var(--muted); font-size: 0.75rem; }
    
    .btn-close-plan { 
        position: absolute; top: 24px; right: 24px; background: #f1f5f9; border: none; width: 32px; height: 32px; 
        border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--muted); 
        cursor: pointer; transition: all 0.2s; 
    }
    .btn-close-plan:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }
    
    .modal-plan-body { 
        display: grid; grid-template-columns: 1.3fr 1fr; overflow-y: auto; 
    }
    .modal-plan-body::-webkit-scrollbar { width: 6px; }
    .modal-plan-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    
    /* COLUMNA IZQUIERDA: CARACTERÍSTICAS DINÁMICAS */
    .plan-features-edit { background: #f8fafc; padding: 24px 30px; border-right: 1px solid #e2e8f0; }
    .section-lbl { font-size: 0.7rem; text-transform: uppercase; color: var(--primary); font-weight: 800; letter-spacing: 0.05em; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;}
    
    .feature-row { display: flex; gap: 8px; margin-bottom: 10px; align-items: center; }
    .feature-row input[type="text"] { 
        flex: 1; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; 
        font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.2s ease;
    }
    
    .feature-row select { 
        width: 145px; 
        padding: 10px 32px 10px 12px; 
        border: 1px solid #cbd5e1; 
        border-radius: 8px; 
        font-family: 'Inter', sans-serif; 
        font-size: 0.8rem; 
        font-weight: 600; 
        color: var(--text);
        cursor: pointer; 
        background-color: #f8fafc;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2394a3b8' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat; 
        background-position: right 10px center; 
        background-size: 16px;
        transition: all 0.2s ease;
    }
    .feature-row select:hover { border-color: #94a3b8; background-color: #ffffff; }
    .feature-row input:focus, .feature-row select:focus { 
        outline: none; border-color: var(--primary); background-color: #ffffff; box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); 
    }

    .btn-del-feat { 
        background: #fee2e2; color: #ef4444; border: none; width: 36px; height: 36px; border-radius: 8px; 
        display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0;
    }
    .btn-del-feat:hover { background: #ef4444; color: #fff; transform: scale(1.05); }

    .btn-add-feat { 
        background: rgba(59, 130, 246, 0.05); color: #2563eb; border: 1px dashed #93c5fd; padding: 10px; 
        border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; width: 100%; 
        display: flex; justify-content: center; align-items: center; gap: 6px; transition: all 0.2s; margin-top: 10px;
    }
    .btn-add-feat:hover { background: rgba(59, 130, 246, 0.15); border-color: #60a5fa; }

    /* COLUMNA DERECHA: PRECIOS Y OFERTAS */
    .plan-pricing-edit { padding: 24px 30px; background: #ffffff; }
    
    .form-group { margin-bottom: 18px; }
    .form-group label { font-size: 0.75rem; color: var(--text); font-weight: 700; margin-bottom: 6px; display: block; }
    .form-group input { 
        width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; 
        font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600; transition: all 0.2s; box-sizing: border-box; 
    }
    .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
    
    .discount-box { background: #fffbeb; border: 1px dashed #f59e0b; padding: 16px; border-radius: 12px; }
    .discount-box label { color: #d97706; }
    .discount-box input { border-color: #fcd34d; }
    .discount-box input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
    .hint { font-size: 0.7rem; color: #92400e; margin-top: 6px; display: block; line-height: 1.4;}

    .modal-plan-footer { padding: 20px 30px; border-top: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px; }
    .btn-sec { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem;}
    .btn-sec:hover { background: #f1f5f9; color: #1e293b; }
    .btn-prim { color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, var(--primary), var(--primary2)); }
    .btn-prim:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 138, 31, 0.25); }

    @media (max-width: 768px) {
        .modal-plan-body { grid-template-columns: 1fr; }
        .plan-features-edit { border-right: none; border-bottom: 1px solid #e2e8f0; }
    }
</style>

<div class="modal-plan-overlay" id="modalEditarPlan">
    <div class="modal-plan-box">
        <div class="modal-plan-header">
            <div class="modal-plan-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </div>
            <div class="modal-plan-header-text">
                <h3>Constructor de Planes</h3>
                <p>Configura los límites, características y precios.</p>
            </div>
            <button type="button" class="btn-close-plan" onclick="cerrarModalPlan()" title="Cerrar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form id="formEditarPlan" action="procesar_plan.php" method="POST" style="display:flex; flex-direction:column; flex:1; min-height:0;">
            <input type="hidden" id="plan_id" name="id">
            
            <div class="modal-plan-body">
                
                <div class="plan-features-edit">
                    <h4 class="section-lbl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Características del Plan (Visuales)
                    </h4>
                    
                    <div id="features_container">
                        </div>
                    
                    <button type="button" class="btn-add-feat" onclick="addFeatureRow()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Agregar Característica
                    </button>
                </div>

                <div class="plan-pricing-edit">
                    <h4 class="section-lbl" style="color: #3b82f6;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Configuración Lógica y Precio
                    </h4>
                    
                    <div class="form-group">
                        <label>Nombre del Plan</label>
                        <input type="text" id="edit_nombre" name="nombre" required>
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <label style="color: var(--primary);">Límite de Trabajadores Base</label>
                        <input type="number" id="edit_trabajadores" name="trabajadores" required min="1">
                        <span class="hint" style="color: var(--muted); margin-top: 4px;">Escribe <b>999</b> si el plan incluye trabajadores ilimitados. Este número define el límite del sistema.</span>
                    </div>

                    <div class="form-group" style="background:#eff6ff;padding:12px;border-radius:8px;border:1px solid #bfdbfe;">
                        <label style="color:#1d4ed8;">Limite de almacenamiento (GB)</label>
                        <input type="number" id="edit_almacenamiento_gb" name="almacenamiento_gb" required min="1" max="10000" step="1">
                        <span class="hint" style="color:#64748b;margin-top:4px;">Esta cuota se aplica a todas las empresas que tengan este plan.</span>
                    </div>

                    <div class="form-group">
                        <label>Precio Normal Mensual (COP)</label>
                        <input type="number" id="edit_precio_normal" name="precio_normal" required>
                    </div>

                    <div class="discount-box">
                        <div class="form-group" style="margin:0;">
                            <label>Promoción / Precio Descuento (COP)</label>
                            <input type="number" id="edit_precio_descuento" name="precio_descuento" placeholder="Ej: 50000">
                            <span class="hint">Si dejas este campo en blanco, no se mostrará oferta en la tarjeta.</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-plan-footer">
                <button type="button" class="btn-sec" onclick="cerrarModalPlan()">Cancelar</button>
                <button type="submit" class="btn-prim">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function addFeatureRow(texto = '', incluido = true) {
        const container = document.getElementById('features_container');
        const row = document.createElement('div');
        row.className = 'feature-row';
        
        row.innerHTML = `
            <input type="text" name="feat_text[]" value="${texto}" placeholder="Ej: Soporte 24/7" required>
            <select name="feat_status[]">
                <option value="1" ${incluido ? 'selected' : ''}>✔ Incluye</option>
                <option value="0" ${!incluido ? 'selected' : ''}>✖ No Incluye</option>
            </select>
            <button type="button" class="btn-del-feat" onclick="this.parentElement.remove()" title="Quitar">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        `;
        container.appendChild(row);
    }

    // AHORA RECIBIMOS LA CANTIDAD DE TRABAJADORES COMO PARÁMETRO (3ER LUGAR)
    function abrirModalPlan(id, nombre, trabajadores, almacenamiento_gb, precio_normal, precio_descuento, featuresJson) {
        document.getElementById('plan_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_trabajadores').value = trabajadores;
        document.getElementById('edit_almacenamiento_gb').value = almacenamiento_gb;
        document.getElementById('edit_precio_normal').value = precio_normal;
        
        document.getElementById('edit_precio_descuento').value = (precio_descuento > 0 && precio_descuento < precio_normal) ? precio_descuento : '';

        const container = document.getElementById('features_container');
        container.innerHTML = ''; 
        const features = JSON.parse(featuresJson);
        
        if (features.length > 0) {
            features.forEach(f => addFeatureRow(f.texto, f.incluido));
        } else {
            addFeatureRow(); 
        }

        document.getElementById('modalEditarPlan').classList.add('active');
    }

    function cerrarModalPlan() {
        document.getElementById('modalEditarPlan').classList.remove('active');
    }
</script>
