<?php
// Validar que solo se renderice para los representantes legales
if (isset($usuario_rol) && $usuario_rol === 'representante'): 

    // ========================================================
    // OBTENER DATOS ACTUALES DEL USUARIO Y LA EMPRESA (FALLBACK)
    // ========================================================
    $usuario_id_modal = $_SESSION['usuario_id'] ?? 0;
    
    $stmt_modal = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt_modal->execute([$usuario_id_modal]);
    $user_modal_info = $stmt_modal->fetch(PDO::FETCH_ASSOC);

    // Variables unificadas
    $modal_emp_nombre = $user_modal_info['nombre_empresa'] ?? '';
    $modal_emp_num_doc = $user_modal_info['num_doc_empresa'] ?? '';
    $modal_emp_tipo_doc = $user_modal_info['tipo_doc_empresa'] ?? '';
    $modal_emp_tipo_per = $user_modal_info['tipo_persona'] ?? '';
    $modal_emp_regimen = $user_modal_info['regimen_tributario'] ?? '';
    $modal_emp_actividades = $user_modal_info['actividad_economica'] ?? '';

    // Si NO ha llenado el perfil corporativo aún, jalamos la info de la solicitud
    if (!empty($user_modal_info['empresa_id'])) {
        if (empty($modal_emp_nombre) || empty($modal_emp_num_doc)) {
            $stmt_sol = $conn->prepare("SELECT nombre, cedula FROM solicitudes_empresas WHERE id = ?");
            $stmt_sol->execute([$user_modal_info['empresa_id']]);
            $sol_data = $stmt_sol->fetch(PDO::FETCH_ASSOC);
            
            if ($sol_data) {
                $modal_emp_nombre = empty($modal_emp_nombre) ? $sol_data['nombre'] : $modal_emp_nombre;
                $modal_emp_num_doc = empty($modal_emp_num_doc) ? $sol_data['cedula'] : $modal_emp_num_doc;
                // Asumimos NIT por defecto si viene de la solicitud
                $modal_emp_tipo_doc = empty($modal_emp_tipo_doc) ? 'NIT' : $modal_emp_tipo_doc;
            }
        }
    }
?>

<style>
    /* =========================================
       OVERLAY DEL MODAL (Fondo Oscuro)
       ========================================= */
    .modal-empresa-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: center; z-index: 10000;
        opacity: 0; transition: opacity 0.3s ease; padding: 24px; box-sizing: border-box;
        overflow: hidden;
    }
    .modal-empresa-overlay.active { display: flex; opacity: 1; }

    /* =========================================
       CAJA PRINCIPAL DEL MODAL
       ========================================= */
    .modal-empresa-box {
        background: #ffffff; border-radius: 20px; 
        width: 100%; max-width: 900px;
        height: auto; max-height: 90vh;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
        transform: translateY(-20px) scale(0.98); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; flex-direction: column;
        font-family: 'Inter', sans-serif;
        overflow: hidden; 
    }
    .modal-empresa-overlay.active .modal-empresa-box { transform: translateY(0) scale(1); }

    /* =========================================
       HEADER DEL MODAL
       ========================================= */
    .modal-empresa-header {
        background: linear-gradient(to right, #f8fafc, #ffffff);
        padding: 24px 32px; padding-right: 64px;
        border-bottom: 1px solid var(--border);
        text-align: left; position: relative; display: flex; align-items: center; gap: 16px;
        flex: 0 0 auto;
    }
    
    .btn-close-empresa {
        position: absolute; top: 24px; right: 24px;
        background: #f1f5f9; border: none; width: 36px; height: 36px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: var(--muted); cursor: pointer; transition: all 0.2s;
    }
    .btn-close-empresa:hover { background: #fee2e2; color: #dc2626; transform: rotate(90deg); }

    .modal-empresa-icon-top {
        width: 48px; height: 48px;
        background: rgba(255, 138, 31, 0.1); color: #ff8a1f;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; box-shadow: 0 4px 10px rgba(255, 138, 31, 0.1);
    }
    .modal-empresa-icon-top svg { width: 24px; height: 24px; }
    
    .modal-empresa-header-text h3 { margin: 0 0 4px 0; font-size: 1.15rem; color: #1e293b; font-weight: 800; letter-spacing: -0.01em; }
    .modal-empresa-header-text p { margin: 0; color: var(--muted); font-size: 0.85rem; line-height: 1.4; }

    /* =========================================
       CUERPO DEL MODAL (Zona de Scroll)
       ========================================= */
    .modal-empresa-body { 
        padding: 32px; 
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto; 
        background: #ffffff;
    }

    .modal-empresa-body::-webkit-scrollbar { width: 6px; }
    .modal-empresa-body::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
    .modal-empresa-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .modal-empresa-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .main-layout-grid { display: grid; grid-template-columns: 280px 1fr; gap: 32px; align-items: stretch; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; text-align: left; position: relative; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.8rem; color: #334155; }
    
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: color 0.3s; pointer-events: none; }
    .input-icon-wrapper input, .input-icon-wrapper select { 
        width: 100%; padding: 10px 14px 10px 40px; border: 1px solid #cbd5e1; 
        border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; color: #1e293b;
        transition: all 0.3s ease; box-sizing: border-box; background: #ffffff;
    }
    .input-icon-wrapper input:focus, .input-icon-wrapper select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
    .input-icon-wrapper input:focus + svg, .input-icon-wrapper select:focus + svg { color: var(--primary); }

    /* CAMPO TIPO FILE CUSTOM */
    .file-upload-wrapper {
        border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 200px;
    }
    .file-upload-wrapper:hover { border-color: var(--primary); background: rgba(255, 138, 31, 0.03); }
    .file-upload-wrapper input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
    .file-upload-content { display: flex; flex-direction: column; align-items: center; gap: 12px; pointer-events: none; z-index: 1; }
    .file-upload-content svg { color: var(--primary); width: 40px; height: 40px; }
    .file-upload-content span { font-size: 0.85rem; color: var(--muted); font-weight: 500; line-height: 1.4; padding: 0 10px; }
    .file-name-display { font-size: 0.85rem; color: var(--primary); font-weight: 700; margin-top: 12px; display: none; background: rgba(255,138,31,0.1); padding: 6px 12px; border-radius: 6px; word-break: break-all; }

    /* BUSCADOR Y TABLA CIIU */
    .multi-select-container { position: relative; margin-bottom: 12px; }
    .search-icon-inside { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 2; }
    .multi-search-input { width: 100%; padding: 12px 14px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; box-sizing: border-box; transition: all 0.3s ease; }
    .multi-search-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 138, 31, 0.15); }
    
    .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
    .dropdown-list.active { display: block; }
    .dropdown-item { padding: 10px 14px; cursor: pointer; display: flex; flex-direction: column; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-item:hover { background: #f8fafc; }
    .dropdown-item-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; }
    .dropdown-item-desc { font-size: 0.75rem; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .risk-indicator { font-size: 0.7rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-block; text-align: center; min-width: 45px;}
    .risk-I { background: #dcfce7; color: #166534; }
    .risk-II { background: #fef9c3; color: #854d0e; }
    .risk-III { background: #ffedd5; color: #9a3412; }
    .risk-IV { background: #fee2e2; color: #991b1b; }
    .risk-V { background: #fecaca; color: #991b1b; }

    .activity-table-wrapper { border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #ffffff; max-height: 250px; overflow-y: auto; }
    .activity-table { width: 100%; border-collapse: collapse; text-align: left; }
    .activity-table th { background: #f8fafc; padding: 10px 14px; font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; position: sticky; top: 0; z-index: 5; border-bottom: 1px solid #cbd5e1; }
    .activity-table td { padding: 12px 14px; font-size: 0.85rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .activity-table tr:last-child td { border-bottom: none; }
    .btn-remove-row { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; margin: 0 auto; }
    .btn-remove-row:hover { background: #ef4444; color: #ffffff; }
    .remove-text-mobile { display: none; }
    .empty-table-msg { text-align: center; padding: 24px !important; color: #94a3b8 !important; font-style: italic; }

    /* =========================================
       FOOTER DEL MODAL
       ========================================= */
    .modal-empresa-footer {
        padding: 20px 32px; border-top: 1px solid var(--border); background: #ffffff;
        display: flex; justify-content: flex-end; gap: 12px; 
        flex: 0 0 auto;
    }
    .btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.85rem; }
    .btn-secondary:hover { background: #f1f5f9; color: #1e293b; }
    .btn-primary-empresa { background: linear-gradient(135deg, var(--primary), var(--primary2)); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; gap: 8px; }
    .btn-primary-empresa:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(255, 138, 31, 0.25); }

    /* WARNING */
    .unsaved-warning { display: none; background: #fff3cd; color: #856404; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; margin-bottom: 24px; border: 1px solid #ffeeba; align-items: center; gap: 10px; }
    .unsaved-warning.active { display: flex; animation: shake 0.5s; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

    /* =========================================
       RESPONSIVE LEVEL ENTERPRISE (MÓVILES)
       ========================================= */
    @media (max-width: 768px) {
        .modal-empresa-overlay { padding: 0; }
        .modal-empresa-box { height: 100%; max-height: 100%; border-radius: 0; }
        .modal-empresa-header { padding: 16px 20px; padding-right: 60px; gap: 12px; }
        .modal-empresa-icon-top { width: 40px; height: 40px; border-radius: 10px;}
        .modal-empresa-icon-top svg { width: 20px; height: 20px; }
        .modal-empresa-header-text h3 { font-size: 1.05rem; }
        .modal-empresa-header-text p { font-size: 0.75rem; }
        .btn-close-empresa { top: 20px; right: 16px; width: 32px; height: 32px; }
        .modal-empresa-body { padding: 20px 16px 30px 16px; }
        .main-layout-grid { display: flex; flex-direction: column; gap: 20px; }
        .left-column { order: 2; margin-top: 10px; }
        .right-column { order: 1; }
        .file-upload-wrapper { min-height: 140px; padding: 16px; }
        .file-upload-content svg { width: 32px; height: 32px; }
        .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
        .form-group { margin-bottom: 16px; }

        /* CERO SCROLL HORIZONTAL: TABLA COMO TARJETAS */
        .activity-table-wrapper { border: none; overflow: visible; max-height: none; }
        .activity-table, .activity-table tbody, .activity-table tr, .activity-table td { display: block; width: 100%; box-sizing: border-box; }
        .activity-table thead { display: none; }
        .activity-table tr { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; margin-bottom: 16px; padding: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px; }
        .activity-table td { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border: none; font-size: 0.85rem; text-align: right; }
        .activity-table td::before { content: attr(data-label); font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: left; margin-right: 16px; }
        .activity-table td:last-child { margin-top: 8px; padding-top: 14px; border-top: 1px solid #f1f5f9; justify-content: center; }
        .btn-remove-row { width: 100%; height: auto; padding: 10px; border-radius: 8px; }
        .remove-text-mobile { display: inline-block !important; }
        .empty-table-msg { text-align: center; }
        .empty-table-msg::before { display: none; }

        .modal-empresa-footer { flex-direction: column; padding: 16px; gap: 12px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); }
        .btn-primary-empresa { order: 1; width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem;}
        .btn-secondary { order: 2; width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem;}
    }
</style>

<div class="modal-empresa-overlay" id="modalEmpresa">
    <form id="formRegistroEmpresa" action="guardar_empresa.php" method="POST" enctype="multipart/form-data" class="modal-empresa-box">
        
        <div class="modal-empresa-header">
            <div class="modal-empresa-icon-top">
                <svg fill="none" stroke="#ff8a1f" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div class="modal-empresa-header-text">
                <h3>Perfil Corporativo</h3>
                <p>Completa la información legal de tu empresa.</p>
            </div>
            <button type="button" class="btn-close-empresa" id="btnCerrarModalEmpresa" title="Cerrar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="modal-empresa-body">
            
            <div class="unsaved-warning" id="warningUnsaved">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Tienes cambios sin guardar. Haz clic en "Guardar y Continuar".
            </div>

            <div class="main-layout-grid">
                
                <div class="left-column">
                    <div class="form-group">
                        <label>Logo de la Empresa (Opcional)</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="logo_empresa" id="logo_empresa_input" accept="image/*">
                            <div class="file-upload-content">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <span>Haz clic o arrastra tu logo aquí (.jpg, .png)</span>
                            </div>
                            <div class="file-name-display" id="fileNameDisplay">Ningún archivo seleccionado</div>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <div class="form-group">
                        <label for="nombre_empresa">Razón Social / Nombre de la Empresa *</label>
                        <div class="input-icon-wrapper">
                            <input type="text" name="nombre_empresa" id="nombre_empresa" class="empresa-input" required placeholder="Ej. Constructora Vertix S.A.S" value="<?php echo htmlspecialchars($modal_emp_nombre); ?>">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="tipo_persona">Tipo de Persona *</label>
                            <div class="input-icon-wrapper">
                                <select name="tipo_persona" id="tipo_persona" class="empresa-input" required>
                                    <option value="">Selecciona...</option>
                                    <option value="Juridica" <?php echo ($modal_emp_tipo_per == 'Juridica') ? 'selected' : ''; ?>>Persona Jurídica</option>
                                    <option value="Natural" <?php echo ($modal_emp_tipo_per == 'Natural') ? 'selected' : ''; ?>>Persona Natural</option>
                                </select>
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="regimen_tributario">Régimen Tributario *</label>
                            <div class="input-icon-wrapper">
                                <select name="regimen_tributario" id="regimen_tributario" class="empresa-input" required>
                                    <option value="">Selecciona...</option>
                                    <option value="Responsable de IVA" <?php echo ($modal_emp_regimen == 'Responsable de IVA') ? 'selected' : ''; ?>>Responsable IVA</option>
                                    <option value="No responsable de IVA" <?php echo ($modal_emp_regimen == 'No responsable de IVA') ? 'selected' : ''; ?>>No responsable IVA</option>
                                    <option value="Regimen Simple" <?php echo ($modal_emp_regimen == 'Regimen Simple') ? 'selected' : ''; ?>>Régimen Simple</option>
                                    <option value="Régimen Especial" <?php echo ($modal_emp_regimen == 'Régimen Especial') ? 'selected' : ''; ?>>Régimen Especial</option>
                                </select>
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="tipo_doc_empresa">Tipo Documento *</label>
                            <div class="input-icon-wrapper">
                                <select name="tipo_doc_empresa" id="tipo_doc_empresa" class="empresa-input" required>
                                    <option value="">Selecciona...</option>
                                    <option value="NIT" <?php echo ($modal_emp_tipo_doc == 'NIT') ? 'selected' : ''; ?>>NIT</option>
                                    <option value="CC" <?php echo ($modal_emp_tipo_doc == 'CC') ? 'selected' : ''; ?>>Cédula Ciudadanía</option>
                                    <option value="CE" <?php echo ($modal_emp_tipo_doc == 'CE') ? 'selected' : ''; ?>>Cédula Extranjería</option>
                                </select>
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="num_doc_empresa">Número Documento *</label>
                            <div class="input-icon-wrapper">
                                <input type="text" name="num_doc_empresa" id="num_doc_empresa" class="empresa-input" required placeholder="Ej. 900.123.456-7" value="<?php echo htmlspecialchars($modal_emp_num_doc); ?>">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0; padding-top: 10px; border-top: 1px solid var(--border);">
                        <label style="margin-top: 10px;">Actividades Económicas CIIU * (Busca y selecciona)</label>
                        
                        <div class="multi-select-container" id="activityContainerModal">
                            <svg class="search-icon-inside" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" id="activitySearchInputModal" class="multi-search-input" placeholder="Buscar código o palabra clave..." autocomplete="off">
                            <div class="dropdown-list" id="activityDropdownModal"></div>
                        </div>
                        
                        <div class="activity-table-wrapper">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Código</th>
                                        <th style="width: 55%;">Descripción de la Actividad</th>
                                        <th style="width: 15%; text-align: center;">Riesgo</th>
                                        <th style="width: 15%; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="activityTableBodyModal">
                                    <tr id="emptyTableMsgModal">
                                        <td colspan="4" class="empty-table-msg">Aún no has agregado actividades económicas. Utiliza el buscador de arriba.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <input type="hidden" name="actividades_economicas" id="actividades_economicas_input_modal" required value="<?php echo htmlspecialchars($modal_emp_actividades); ?>">
                    </div>

                </div>
            </div>
        </div>

        <div class="modal-empresa-footer">
            <button type="submit" class="btn-primary-empresa">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Guardar y Continuar
            </button>
            <button type="button" class="btn-secondary" id="btnCerrarModalFooter">Descartar Cambios</button>
        </div>
        
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- BASE DE DATOS DE EJEMPLO: ACTIVIDADES CIIU ---
        const actividadesCIIU = [
            { codigo: "0111", descripcion: "Cultivo de cereales (excepto arroz), legumbres y semillas oleaginosas", riesgo: "III" },
            { codigo: "1410", descripcion: "Confección de prendas de vestir, excepto prendas de piel", riesgo: "II" },
            { codigo: "4111", descripcion: "Construcción de edificios residenciales", riesgo: "V" },
            { codigo: "4112", descripcion: "Construcción de edificios no residenciales", riesgo: "V" },
            { codigo: "4711", descripcion: "Comercio al por menor en establecimientos no especializados", riesgo: "I" },
            { codigo: "4921", descripcion: "Transporte de pasajeros urbano y suburbano", riesgo: "IV" },
            { codigo: "5611", descripcion: "Expendio a la mesa de comidas preparadas", riesgo: "II" },
            { codigo: "6201", descripcion: "Actividades de desarrollo de sistemas informáticos", riesgo: "I" },
            { codigo: "6920", descripcion: "Actividades de contabilidad, teneduría de libros y auditoría", riesgo: "I" },
            { codigo: "8621", descripcion: "Actividades de la práctica médica, sin internación", riesgo: "III" }
        ];

        // --- REFERENCIAS DOM MODAL ---
        const modalEmpresa = document.getElementById('modalEmpresa');
        const formEmpresa = document.getElementById('formRegistroEmpresa');
        const btnCerrarIcon = document.getElementById('btnCerrarModalEmpresa');
        const btnCerrarFooter = document.getElementById('btnCerrarModalFooter');
        const warningUnsaved = document.getElementById('warningUnsaved');
        const fileInput = document.getElementById('logo_empresa_input');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        
        const actSearchInputModal = document.getElementById('activitySearchInputModal');
        const actDropdownModal = document.getElementById('activityDropdownModal');
        const actContainerModal = document.getElementById('activityContainerModal');
        const actTableBodyModal = document.getElementById('activityTableBodyModal');
        const actInputHiddenModal = document.getElementById('actividades_economicas_input_modal');
        
        let isFormDirty = false;
        
        // Cargar actividades previas si existen
        let selectedActivitiesModal = actInputHiddenModal && actInputHiddenModal.value ? actInputHiddenModal.value.split(',').filter(Boolean) : [];

        // --- LÓGICA DE LA TABLA Y EL BUSCADOR (MODAL) ---
        function renderTableModal() {
            if(!actTableBodyModal) return;
            actTableBodyModal.innerHTML = '';

            if (selectedActivitiesModal.length === 0) {
                actTableBodyModal.innerHTML = `
                    <tr id="emptyTableMsgModal">
                        <td colspan="4" class="empty-table-msg">Aún no has agregado actividades económicas. Utiliza el buscador de arriba.</td>
                    </tr>
                `;
            } else {
                selectedActivitiesModal.forEach(code => {
                    const activity = actividadesCIIU.find(a => a.codigo === code);
                    if(activity) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td data-label="Código CIIU" style="font-family: monospace; font-weight: 600;">${activity.codigo}</td>
                            <td data-label="Descripción" style="text-align: right;">${activity.descripcion}</td>
                            <td data-label="Nivel de Riesgo" style="text-align: center;">
                                <span class="risk-indicator risk-${activity.riesgo}">Riesgo ${activity.riesgo}</span>
                            </td>
                            <td data-label="Acción" style="text-align: center;">
                                <button type="button" class="btn-remove-row" title="Eliminar" onclick="removeActivityModal('${activity.codigo}')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span class="remove-text-mobile">Eliminar Actividad</span>
                                </button>
                            </td>
                        `;
                        actTableBodyModal.appendChild(tr);
                    }
                });
            }

            if(actInputHiddenModal) {
                actInputHiddenModal.value = selectedActivitiesModal.join(',');
            }
            isFormDirty = true;
        }

        window.removeActivityModal = function(code) {
            selectedActivitiesModal = selectedActivitiesModal.filter(c => c !== code);
            renderTableModal();
        };

        if (actSearchInputModal) {
            actSearchInputModal.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                actDropdownModal.innerHTML = '';
                
                if (term.length === 0) {
                    actDropdownModal.classList.remove('active');
                    return;
                }

                const results = actividadesCIIU.filter(a => 
                    (a.codigo.includes(term) || a.descripcion.toLowerCase().includes(term)) &&
                    !selectedActivitiesModal.includes(a.codigo)
                );

                if (results.length > 0) {
                    results.forEach(res => {
                        const div = document.createElement('div');
                        div.className = 'dropdown-item';
                        div.innerHTML = `
                            <div class="dropdown-item-title">
                                <span>[${res.codigo}] <span class="risk-indicator risk-${res.riesgo}">Riesgo ${res.riesgo}</span></span>
                            </div>
                            <div class="dropdown-item-desc">${res.descripcion}</div>
                        `;
                        div.addEventListener('click', () => {
                            selectedActivitiesModal.push(res.codigo);
                            renderTableModal();
                            actSearchInputModal.value = '';
                            actDropdownModal.classList.remove('active');
                            actSearchInputModal.focus();
                        });
                        actDropdownModal.appendChild(div);
                    });
                    actDropdownModal.classList.add('active');
                } else {
                    actDropdownModal.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8rem; color: #64748b;">No se encontraron resultados</div>';
                    actDropdownModal.classList.add('active');
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (actContainerModal && !actContainerModal.contains(e.target) && actDropdownModal) {
                actDropdownModal.classList.remove('active');
            }
        });

        // Inicializar la tabla si hay datos precargados
        if(selectedActivitiesModal.length > 0) {
            renderTableModal();
            isFormDirty = false; // Reset porque es carga inicial
        }

        // --- LÓGICA DEL FORMULARIO ---
        if(fileInput) {
            fileInput.addEventListener('change', function(e) {
                if(e.target.files.length > 0) {
                    fileNameDisplay.textContent = 'Archivo: ' + e.target.files[0].name;
                    fileNameDisplay.style.display = 'block';
                    isFormDirty = true;
                }
            });
        }

        const inputs = document.querySelectorAll('.empresa-input');
        inputs.forEach(input => {
            input.addEventListener('input', () => { isFormDirty = true; });
            input.addEventListener('change', () => { isFormDirty = true; });
        });

        const mostrarFuerza = <?php echo (isset($mostrar_modal_empresa) && $mostrar_modal_empresa) ? 'true' : 'false'; ?>;
        if (mostrarFuerza && modalEmpresa) {
            setTimeout(() => { modalEmpresa.classList.add('active'); }, 500);
        }

        const attemptCloseModal = (e) => {
            e.preventDefault();
            if (isFormDirty) {
                warningUnsaved.classList.remove('active');
                void warningUnsaved.offsetWidth; 
                warningUnsaved.classList.add('active');
                
                const modalBody = document.querySelector('.modal-empresa-body');
                if (modalBody) modalBody.scrollTo({ top: 0, behavior: 'smooth' });
                
            } else {
                modalEmpresa.classList.remove('active');
            }
        };

        if(btnCerrarIcon) btnCerrarIcon.addEventListener('click', attemptCloseModal);
        
        if(btnCerrarFooter) {
            btnCerrarFooter.addEventListener('click', (e) => {
                formEmpresa.reset();
                isFormDirty = false;
                
                // Reiniciar al estado guardado original en BD
                selectedActivitiesModal = actInputHiddenModal && actInputHiddenModal.defaultValue ? actInputHiddenModal.defaultValue.split(',').filter(Boolean) : [];
                renderTableModal(); 
                
                fileNameDisplay.style.display = 'none';
                modalEmpresa.classList.remove('active');
                warningUnsaved.classList.remove('active');
            });
        }
    });
</script>

<?php endif; ?>