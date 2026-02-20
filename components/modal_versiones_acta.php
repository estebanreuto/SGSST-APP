<style>
    /* Forzar la fuente Inter en todo el modal */
    .versiones-overlay, .versiones-content * {
        font-family: 'Inter', sans-serif;
    }

    /* Fondo del Modal de Versiones */
    .versiones-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); display: none; justify-content: center; align-items: center; 
        z-index: 2000; backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease; padding: 16px; box-sizing: border-box;
    }
    .versiones-overlay.active { display: flex; opacity: 1; }
    
    /* Caja del Modal */
    .versiones-content {
        background: var(--card); border-radius: var(--radius); width: 100%; max-width: 800px; height: 85vh;
        display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        transform: translateY(-20px); transition: transform 0.3s ease; overflow: hidden;
    }
    .versiones-overlay.active .versiones-content { transform: translateY(0); }
    
    /* Encabezado limpio con tamaño de letra controlado */
    .versiones-header {
        padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-shrink: 0;
    }
    .versiones-header h3 { margin: 0; font-size: 1rem; color: var(--text); font-weight: 700; }
    
    /* Barra de Controles (Buscador y Filtro) */
    .versiones-controls {
        padding: 14px 24px; border-bottom: 1px solid var(--border); background: #ffffff; display: flex; gap: 16px; align-items: center; flex-shrink: 0;
    }
    
    /* Buscador */
    .search-wrapper { position: relative; flex: 1; }
    .search-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 16px; height: 16px; pointer-events: none;
    }
    
    .search-input {
        width: 100%; padding: 8px 12px 8px 36px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 500; color: var(--text); box-sizing: border-box; outline: none; transition: all 0.2s; background: #ffffff;
    }
    .search-input::placeholder { color: #94a3b8; font-weight: 400; }
    
    /* Select Personalizado (Idéntico a tu foto) */
    .filter-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231f2d3d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding: 8px 36px 8px 16px; 
        border-radius: 8px; 
        border: 1px solid var(--border); 
        font-size: 0.85rem; 
        font-weight: 500;
        color: var(--text); 
        background-color: #ffffff; 
        outline: none; 
        cursor: pointer; 
        transition: all 0.2s; 
        min-width: 180px;
    }

    .search-input:focus, .filter-select:focus {
        border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255,138,31,0.1);
    }
    
    /* Lista de versiones */
    .versiones-body { padding: 0; overflow-y: auto; flex: 1; display: block; background: #fafbfc; }
    
    .version-item {
        padding: 14px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; transition: background 0.2s; background: #fff;
    }
    .version-item:hover { background: #f1f5f9; }
    
    .version-info { display: flex; flex-direction: column; gap: 4px; }
    .version-date-wrapper { display: flex; align-items: center; }
    .version-date { font-weight: 600; color: var(--text); font-size: 0.85rem; }
    
    /* Estados (VIGENTE / VENCIDA) */
    .badge-status {
        padding: 2px 8px; border-radius: 6px; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; margin-left: 10px; letter-spacing: 0.05em;
    }
    .badge-status.vigente { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-status.vencida { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

    .version-users { font-size: 0.75rem; color: var(--muted); }
    
    .acciones-version { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
    
    .btn-vista-previa {
        background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 7px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
    }
    .btn-vista-previa:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
    
    .btn-download-pdf {
        background: rgba(255, 138, 31, 0.1); color: var(--primary2); padding: 7px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
    }
    .btn-download-pdf:hover { background: var(--primary); color: white; }
    .empty-versions { padding: 40px; text-align: center; color: var(--muted); font-size: 0.85rem; }

    /* Vista Previa del PDF */
    .versiones-preview-panel {
        display: none; flex-direction: column; flex: 1; height: 100%; background: #f1f5f9;
    }
    .preview-toolbar {
        padding: 10px 24px; border-bottom: 1px solid var(--border); background: #fff; display: flex; align-items: center; gap: 16px; flex-shrink: 0;
    }
    
    .btn-volver-lista {
        background: none; border: 1px solid transparent; color: var(--primary2); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; transition: all 0.2s; margin-left: -12px;
    }
    .btn-volver-lista:hover { background: rgba(255, 138, 31, 0.1); color: var(--primary); text-decoration: none; border: 1px solid rgba(255, 138, 31, 0.2); }
    
    .preview-info-text { font-size: 0.85rem; font-weight: 600; color: var(--text); border-left: 1px solid #cbd5e1; padding-left: 16px; }
    
    #iframe-pdf-preview { width: 100%; flex: 1; border: none; background: #94a3b8; }

    @media (max-width: 600px) {
        .versiones-controls { flex-direction: column; padding: 14px; }
        .filter-select { width: 100%; }
        .version-item { flex-direction: column; align-items: flex-start; gap: 12px; padding: 14px; }
        .acciones-version { width: 100%; flex-direction: row; }
        .acciones-version button, .acciones-version a { flex: 1; justify-content: center; }
    }
</style>

<div class="versiones-overlay" id="modalVersionesActa">
    <div class="versiones-content">
        
        <div class="versiones-header">
            <h3>Historial de Actas</h3>
            <button class="btn-close" id="btnCloseVersiones" type="button" style="background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; border-radius: 6px; transition: 0.2s; display:flex; align-items:center;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="versiones-controls" id="versiones-toolbar">
            <div class="search-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchVersiones" class="search-input" placeholder="Buscar por responsable, representante o fecha...">
            </div>
            
            <select id="filterVersiones" class="filter-select">
                <option value="todas">Todas las versiones</option>
                <option value="vigente">Solo Vigente</option>
                <option value="vencida">Solo Vencidas</option>
            </select>
        </div>

        <div class="versiones-body" id="versiones-list-container">
            <?php if (!empty($historial_actas)): ?>
                <?php foreach ($historial_actas as $index => $acta): ?>
                    <?php 
                        $estado_acta = ($index === 0) ? 'vigente' : 'vencida'; 
                    ?>
                    <div class="version-item" data-estado="<?php echo $estado_acta; ?>">
                        <div class="version-info">
                            <div class="version-date-wrapper">
                                <span class="version-date">Versión #<?php echo count($historial_actas) - $index; ?> - <?php echo date('d/m/Y', strtotime($acta['fecha_firma'])); ?></span>
                                <?php if ($estado_acta === 'vigente'): ?>
                                    <span class="badge-status vigente">Vigente</span>
                                <?php else: ?>
                                    <span class="badge-status vencida">Vencida</span>
                                <?php endif; ?>
                            </div>
                            <span class="version-users">SST: <?php echo htmlspecialchars($acta['sst_nombre'] . ' ' . $acta['sst_apellido']); ?> | Rep: <?php echo htmlspecialchars($acta['rep_nombre'] . ' ' . $acta['rep_apellido']); ?></span>
                        </div>
                        <div class="acciones-version">
                            <?php if (!empty($acta['archivo_pdf'])): ?>
                                <button type="button" class="btn-vista-previa" onclick="verVistaPreviaPDF(this.nextElementSibling.href, 'Versión #<?php echo count($historial_actas) - $index; ?>')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Vista Previa
                                </button>
                                <a href="<?php echo $acta['archivo_pdf']; ?>" download="Acta_Designacion_v<?php echo $acta['id']; ?>.pdf" class="btn-download-pdf">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Sin PDF</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div id="msgNoResultados" class="empty-versions" style="display: none;">No hay actas que coincidan con la búsqueda.</div>
            <?php else: ?>
                <div class="empty-versions">No hay versiones anteriores firmadas ni archivadas.</div>
            <?php endif; ?>
        </div>

        <div class="versiones-preview-panel" id="versiones-preview-container">
            <div class="preview-toolbar">
                <button class="btn-volver-lista" onclick="cerrarVistaPreviaPDF()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver a la lista
                </button>
                <span class="preview-info-text" id="preview-version-text">Visualizando Documento</span>
            </div>
            <iframe id="iframe-pdf-preview" src=""></iframe>
        </div>

    </div>
</div>

<script>
    // Buscador y Filtro simultáneo
    function aplicarFiltrosActas() {
        const inputElement = document.getElementById('searchVersiones');
        const selectElement = document.getElementById('filterVersiones');
        
        if (!inputElement || !selectElement) return;

        const searchText = inputElement.value.toLowerCase();
        const estadoFiltro = selectElement.value;
        const items = document.querySelectorAll('.version-item');
        let contadorVisibles = 0;
        
        items.forEach(item => {
            const estadoItem = item.getAttribute('data-estado');
            const infoText = item.querySelector('.version-info').textContent.toLowerCase();
            
            const cumpleBusqueda = infoText.includes(searchText);
            const cumpleEstado = (estadoFiltro === 'todas' || estadoFiltro === estadoItem);
            
            if (cumpleBusqueda && cumpleEstado) {
                item.style.display = 'flex';
                contadorVisibles++;
            } else {
                item.style.display = 'none';
            }
        });

        const msgEmpty = document.getElementById('msgNoResultados');
        if (msgEmpty) {
            msgEmpty.style.display = (contadorVisibles === 0) ? 'block' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const inputSearch = document.getElementById('searchVersiones');
        const selectFilter = document.getElementById('filterVersiones');
        
        if (inputSearch) inputSearch.addEventListener('input', aplicarFiltrosActas);
        if (selectFilter) selectFilter.addEventListener('change', aplicarFiltrosActas);
    });

    // Abrir Modal
    function openVersionesModal() {
        document.getElementById('modalVersionesActa').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Navegación Vista Previa
    function verVistaPreviaPDF(base64Data, textVersion) {
        document.getElementById('versiones-list-container').style.display = 'none';
        document.getElementById('versiones-toolbar').style.display = 'none';
        document.getElementById('versiones-preview-container').style.display = 'flex';
        document.getElementById('iframe-pdf-preview').src = base64Data;
        document.getElementById('preview-version-text').innerText = 'Visualizando: ' + textVersion;
    }

    function cerrarVistaPreviaPDF() {
        document.getElementById('iframe-pdf-preview').src = '';
        document.getElementById('versiones-preview-container').style.display = 'none';
        document.getElementById('versiones-list-container').style.display = 'block';
        document.getElementById('versiones-toolbar').style.display = 'flex';
    }

    // Cerrar Modal Completo
    document.addEventListener('DOMContentLoaded', () => {
        const modalVersiones = document.getElementById('modalVersionesActa');
        const btnCloseVers = document.getElementById('btnCloseVersiones');

        const closeVers = () => {
            modalVersiones.classList.remove('active');
            document.body.style.overflow = 'auto';
            setTimeout(cerrarVistaPreviaPDF, 300); 
        }

        if(btnCloseVers) btnCloseVers.addEventListener('click', closeVers);
        if(modalVersiones) {
            modalVersiones.addEventListener('click', (e) => {
                if (e.target === modalVersiones) closeVers();
            });
        }
    });
</script>