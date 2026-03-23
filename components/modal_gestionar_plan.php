<div class="modal-detalles-overlay" id="modalGestionPlan">
    <div class="modal-detalles-box" style="max-width: 850px;">
        <div class="modal-detalles-header">
            <div class="modal-detalles-icon-top" style="background: rgba(255, 138, 31, 0.1); color: var(--primary);">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div class="modal-detalles-header-text">
                <h3>Gestión de Plan y Accesos</h3>
                <p>Modifica el plan base y personaliza los costos extra para <b id="gp_empresa_nombre" style="color: var(--primary);"></b>.</p>
            </div>
            <button type="button" class="btn-close-detalles" onclick="cerrarModalGestionPlan()" title="Cerrar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="formGestionarPlan" action="procesar_upgrade.php" method="POST" style="display:flex; flex-direction:column; flex:1; min-height:0;">
            <input type="hidden" id="gp_empresa_id" name="empresa_id">
            
            <div class="modal-detalles-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 30px; overflow-y: auto;">
                
                <div>
                    <h4 style="font-size: 0.7rem; text-transform: uppercase; color: var(--muted); font-weight: 800; letter-spacing: 0.05em; margin: 0 0 16px 0; display: flex; align-items: center; gap: 6px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Configuración de la Suscripción
                    </h4>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.75rem; color: var(--text); font-weight: 700; margin-bottom: 8px; display: block;">Plan Base Asignado</label>
                        <select id="gp_plan_select" name="plan_id" onchange="calcularProyeccion()" style="width: 100%; padding: 12px 32px 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; color: var(--text); cursor: pointer; background-color: #f8fafc; appearance: none; background-image: url('data:image/svg+xml,%3Csvg fill=\'none\' stroke=\'%2394a3b8\' viewBox=\'0 0 24 24\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'%3E%3C/path%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px; transition: all 0.2s;">
                            </select>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.75rem; color: var(--text); font-weight: 700; margin-bottom: 4px; display: block;">Trabajadores Operativos Adicionales</label>
                        <input type="number" id="gp_extra_workers" name="trabajadores_extra" value="0" min="0" oninput="calcularProyeccion()" style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600; box-sizing: border-box; transition: all 0.2s;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.75rem; color: var(--text); font-weight: 700; margin-bottom: 4px; display: block;">Precio por Trabajador Extra (COP)</label>
                        <input type="number" id="gp_costo_extra" name="precio_trabajador_extra" value="10000" min="0" oninput="calcularProyeccion()" style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600; box-sizing: border-box; transition: all 0.2s; background: #fffbeb; border-color: #fcd34d;">
                        <span style="font-size: 0.65rem; color: #d97706; display: block; margin-top: 6px;">Puedes aplicar una tarifa diferente para este cliente.</span>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 0.7rem; text-transform: uppercase; color: var(--primary); font-weight: 800; letter-spacing: 0.05em; margin: 0 0 16px 0; display: flex; align-items: center; gap: 6px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Proyección Financiera
                    </h4>
                    
                    <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">
                            <span>Costo Plan Base</span>
                            <span id="lbl_base_price" style="font-weight: 700; color: var(--text);">$0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">
                            <span>Costo Accesos Extra (<span id="lbl_extra_workers">0</span> trab.)</span>
                            <span id="lbl_extra_price" style="font-weight: 700; color: var(--text);">$0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 16px; margin-top: 16px; font-size: 1.1rem; font-weight: 800; color: var(--primary);">
                            <span>Total a Pagar</span>
                            <span id="lbl_total_price">$0</span>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; margin-top: 20px;">
                        <h5 style="font-size: 0.75rem; color: var(--text); margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.05em;">Desglose de Usuarios Permitidos</h5>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px;">
                            <span style="color: var(--muted);">Representante Legal</span>
                            <span style="font-weight: 700; color: var(--text);">1 <span style="color: #10b981; font-size: 0.7rem; font-weight: 800;">(Fijo)</span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px;">
                            <span style="color: var(--muted);">Responsable SG-SST</span>
                            <span style="font-weight: 700; color: var(--text);">1 <span style="color: #10b981; font-size: 0.7rem; font-weight: 800;">(Fijo)</span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px;">
                            <span style="color: var(--muted);">Trabajadores (Plan Base: <span id="lbl_base_workers">0</span> + Extras)</span>
                            <span style="font-weight: 800; color: var(--primary);" id="lbl_total_limit">0</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-top: 12px; padding-top: 12px; border-top: 1px dashed #cbd5e1; font-weight: 800;">
                            <span style="color: var(--text);">Total Cuentas en Sistema</span>
                            <span style="color: #3b82f6;" id="lbl_platform_users">0</span>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="modal-detalles-footer" style="padding: 20px 30px; border-top: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px; flex-shrink: 0;">
                <button type="button" class="btn-secondary" style="background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem;" onclick="cerrarModalGestionPlan()">Cancelar</button>
                <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s; display: flex; align-items: center; gap: 8px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    Actualizar Plan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variables globales que le pasará accesos.php
    let window_planes = []; 

    function abrirModalGestionPlan(empresa_id, empresa_nombre, plan_id_actual, extra_actuales, precio_extra) {
        document.getElementById('gp_empresa_id').value = empresa_id;
        document.getElementById('gp_empresa_nombre').innerText = empresa_nombre;
        
        // Llenar el select de planes
        const select = document.getElementById('gp_plan_select');
        select.innerHTML = '<option value="">-- Seleccionar Plan --</option>'; // Opción por defecto
        
        window_planes.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.innerText = p.nombre;
            if (plan_id_actual && p.id == plan_id_actual) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        // Setear los trabajadores extra y su costo actual
        document.getElementById('gp_extra_workers').value = extra_actuales || 0;
        document.getElementById('gp_costo_extra').value = precio_extra || 10000;

        // Calcular matemática
        calcularProyeccion();

        // Mostrar modal
        document.getElementById('modalGestionPlan').classList.add('active');
    }

    function cerrarModalGestionPlan() {
        document.getElementById('modalGestionPlan').classList.remove('active');
    }

    function calcularProyeccion() {
        const select = document.getElementById('gp_plan_select');
        const planId = select.value;
        const plan = window_planes.find(p => p.id == planId);
        
        const trabajadoresExtra = parseInt(document.getElementById('gp_extra_workers').value) || 0;
        const costoPorTrabajadorExtra = parseInt(document.getElementById('gp_costo_extra').value) || 0;
        
        const costoExtra = trabajadoresExtra * costoPorTrabajadorExtra;
        const formatter = new Intl.NumberFormat('es-CO');

        // Si no hay plan seleccionado, mostramos solo los extras
        if (!plan) {
            document.getElementById('lbl_base_workers').innerText = '0';
            document.getElementById('lbl_base_price').innerText = '$0';
            document.getElementById('lbl_extra_workers').innerText = trabajadoresExtra;
            document.getElementById('lbl_extra_price').innerText = '$' + formatter.format(costoExtra);
            document.getElementById('lbl_total_price').innerText = '$' + formatter.format(costoExtra) + ' COP';
            document.getElementById('lbl_total_limit').innerText = trabajadoresExtra;
            document.getElementById('lbl_platform_users').innerText = (trabajadoresExtra + 2); // 2 fijos
            return;
        }
        
        // El precio base ya viene con descuento si aplica
        const precioBase = (plan.precio_descuento > 0 && plan.precio_descuento < plan.precio_normal) ? plan.precio_descuento : plan.precio_normal;
        const total = parseInt(precioBase) + costoExtra;
        
        const isIlimitado = plan.trabajadores == 999;
        
        const limitBase = isIlimitado ? 'Ilimitados' : plan.trabajadores;
        const limitTotal = isIlimitado ? 'Ilimitados' : (parseInt(plan.trabajadores) + trabajadoresExtra);
        // El total en plataforma incluye al Representante y al Responsable SST (2 cuentas fijas)
        const platformUsers = isIlimitado ? 'Ilimitados' : (limitTotal + 2);

        // Actualizar UI
        document.getElementById('lbl_base_workers').innerText = limitBase;
        document.getElementById('lbl_base_price').innerText = '$' + formatter.format(precioBase);
        
        document.getElementById('lbl_extra_workers').innerText = trabajadoresExtra;
        document.getElementById('lbl_extra_price').innerText = '$' + formatter.format(costoExtra);
        
        document.getElementById('lbl_total_price').innerText = '$' + formatter.format(total) + ' COP';
        document.getElementById('lbl_total_limit').innerText = limitTotal;
        document.getElementById('lbl_platform_users').innerText = platformUsers;
    }

    // Cerrar dando clic afuera
    document.getElementById('modalGestionPlan').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalGestionPlan();
    });
</script>