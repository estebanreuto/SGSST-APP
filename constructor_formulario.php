<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';

// Validar permisos
if ($usuario_rol === 'trabajador') {
    header('Location: dashboard.php');
    exit;
}

// Variables simuladas para las tarjetas (Luego las conectas a tu BD real)
$asistencias_historicas = 145; 
$asistencias_mes = 32;         

$current_page = 'estandar3.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructor de Asistencia | SG-SST Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #ff8a1f; --primary2: #ff7a00; --bg1: #edf4fb; --bg2: #f7f9fc; --card: #ffffff; --text: #1f2d3d; --muted: #5f6f82; --border: #dbe3ec; --radius: 10px; --blue-dark: #1e3a8a; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, var(--bg1), var(--bg2)); margin: 0; padding: 0; min-height: 100vh; color: var(--text); display: flex; font-size: 0.85rem; overflow-x: hidden; }
        
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
        .content-area { padding: 24px 32px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; display: flex; flex-direction: column;}
        
        /* ENCABEZADO COMPACTO */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 16px;}
        .estandar-header-group { display: flex; align-items: center; gap: 12px; }
        .icon-box-std { width: 38px; height: 38px; background: rgba(255, 138, 31, 0.08); color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(255, 138, 31, 0.2); }
        .icon-box-std i { font-size: 1.1rem; }
        .estandar-header-text { display: flex; flex-direction: column; }
        .estandar-title { margin: 0; font-size: 1.1rem; color: var(--blue-dark); font-weight: 800; letter-spacing: -0.01em; }
        .estandar-subtitle { margin: 2px 0 0 0; color: var(--muted); font-size: 0.75rem; font-weight: 500; }

        /* TARJETAS RESUMEN (MÁS PEQUEÑAS) */
        .summary-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; position: relative; overflow: hidden !important; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: transform 0.2s ease; z-index: 1;}
        .summary-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        .summary-bg-icon { position: absolute; right: -10px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 70px; color: var(--primary); opacity: 0.04; transition: all 0.3s ease; pointer-events: none; z-index: 0; }
        .summary-card:hover .summary-bg-icon { transform: translateY(-50%) rotate(0deg) scale(1.1); opacity: 0.08; }
        
        .summary-content { position: relative; z-index: 2; }
        .summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .summary-icon-box { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;}
        .summary-value { font-size: 1.6rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1; }
        .summary-title { font-size: 0.85rem; font-weight: 700; color: var(--blue-dark); margin: 0 0 2px 0; }
        .summary-desc { font-size: 0.75rem; color: var(--muted); margin: 0; }

        .card-preguntas .summary-icon-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .card-preguntas .summary-value { color: #3b82f6; }
        .card-preguntas .summary-bg-icon { color: #3b82f6; }

        .card-total .summary-icon-box { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .card-total .summary-value { color: #16a34a; }
        .card-total .summary-bg-icon { color: #16a34a; }

        .card-mes .summary-icon-box { background: rgba(255, 138, 31, 0.1); color: var(--primary); }
        .card-mes .summary-value { color: var(--primary); }
        .card-mes .summary-bg-icon { color: var(--primary); }

        /* =========================================
           BUILDER LAYOUT (2 COLUMNAS COMPACTAS)
           ========================================= */
        .builder-container { display: grid; grid-template-columns: 240px 1fr; gap: 24px; flex: 1; align-items: start; }
        
        /* SIDEBAR DE HERRAMIENTAS */
        .tools-sidebar { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 16px; position: sticky; top: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .tools-title { font-size: 0.75rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0; display: flex; align-items: center; gap: 6px;}
        .tool-btn { width: 100%; background: #f8fafc; border: 1px solid #cbd5e1; color: var(--text); padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s; font-family: inherit; font-weight: 600; font-size: 0.8rem; text-align: left;}
        .tool-btn i { font-size: 1rem; color: var(--primary); width: 16px; text-align: center; }
        .tool-btn:hover { background: #fff8f3; border-color: var(--primary); color: var(--primary2);}

        .btn-preview { width: 100%; background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 12px;}
        .btn-preview:hover { background: #4f46e5; color: white; border-color: #4f46e5;}

        .btn-save-form { width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 8px; box-shadow: 0 2px 8px rgba(255, 138, 31, 0.2); }
        .btn-save-form:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255, 138, 31, 0.3); }

        /* AREA DEL FORMULARIO CENTRAL */
        .form-canvas-area { background: transparent; display: flex; flex-direction: column; align-items: flex-start; width: 100%; padding-bottom: 40px;}
        .preview-content { width: 100%; max-width: 650px; display: flex; flex-direction: column; gap: 16px; }
        
        .preview-header-card { background: white; padding: 20px; border-radius: var(--radius); border: 1px solid var(--border); border-left: 4px solid var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.02);}
        .preview-header-card h2 { margin: 0 0 4px 0; font-size: 1.15rem; color: var(--blue-dark); font-weight: 800;}
        .preview-header-card p { margin: 0; color: var(--muted); font-size: 0.85rem; font-weight: 500;}

        /* Preguntas Dinámicas */
        .question-card { background: white; padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); position: relative; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.02);}
        .question-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .question-card.fixed-field { background: #f8fafc; border: 1px dashed #cbd5e1; }
        .question-card.fixed-field::after { content: 'Requerido'; position: absolute; top: 12px; right: 12px; font-size: 0.65rem; color: #94a3b8; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase;}

        .btn-delete-q { position: absolute; top: 12px; right: 12px; background: transparent; color: #cbd5e1; border: none; cursor: pointer; font-size: 1rem; transition: 0.2s;}
        .btn-delete-q:hover { color: #ef4444; transform: scale(1.1); }

        .q-title-input { width: 85%; border: none; font-size: 1rem; font-weight: 700; color: var(--blue-dark); padding: 4px 0; border-bottom: 2px solid transparent; outline: none; margin-bottom: 12px; transition: 0.2s; font-family: inherit; background: transparent;}
        .q-title-input:focus { border-bottom-color: var(--primary); }
        .q-title-input::placeholder { color: #94a3b8; font-weight: 500; }

        /* Tipos de Input Fake */
        .fake-input { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; color: #94a3b8; font-size: 0.85rem; background: #ffffff; pointer-events: none;}
        .options-list { display: flex; flex-direction: column; gap: 8px; }
        .option-item { display: flex; align-items: center; gap: 8px; }
        .option-item i { color: #cbd5e1; font-size: 1rem; }
        .option-input { border: none; outline: none; font-size: 0.85rem; color: var(--text); padding: 2px 0; font-family: inherit; flex: 1; border-bottom: 1px solid transparent; transition: 0.2s; background: transparent;}
        .option-input:focus { border-bottom-color: var(--primary); }
        .btn-add-option { background: transparent; border: none; color: var(--primary); font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 4px 0; margin-top: 4px;}
        .btn-add-option:hover { color: var(--primary2); text-decoration: underline;}

        .signature-fake { width: 100%; height: 80px; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 1.2rem; background: #ffffff;}

        /* =========================================
           MODAL DE VISTA PREVIA ELEGANTE
           ========================================= */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 10000; opacity: 0; transition: opacity 0.2s ease; padding: 20px;}
        .modal-overlay.active { display: flex; opacity: 1; }
        
        .modal-preview-box { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; position: relative;}
        
        .btn-close-modal { position: absolute; top: 0; right: 20px; background: white; border: 1px solid #cbd5e1; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #475569; cursor: pointer; transition: all 0.2s; font-size: 1rem; z-index: 10001; box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
        .btn-close-modal:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

        .device-toggles { display: flex; justify-content: center; gap: 8px; background: white; padding: 8px 16px; border-radius: 50px; margin-bottom: 16px; border: 1px solid #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
        .device-btn { background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; color: #94a3b8; font-size: 1rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .device-btn:hover { background: #f1f5f9; color: var(--blue-dark); }
        .device-btn.active { background: rgba(255, 138, 31, 0.1); color: var(--primary); }

        .device-simulator { background: transparent; display: flex; justify-content: center; align-items: flex-start; height: calc(100vh - 120px); overflow-y: auto; width: 100%; padding-bottom: 20px; transition: all 0.3s ease; }
        .device-simulator::-webkit-scrollbar { display: none; }
        
        .device-frame { background: var(--bg2); width: 100%; border-radius: 12px; transition: all 0.3s ease; margin: 0 auto; display: flex; flex-direction: column; overflow: hidden; position: relative;}
        
        .frame-desktop { max-width: 800px; border: 1px solid #cbd5e1; min-height: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);}
        .frame-tablet { max-width: 600px; border: 1px solid #cbd5e1; min-height: 700px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);}
        .frame-mobile { max-width: 360px; border: 8px solid #1e293b; border-radius: 24px; min-height: 700px; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.15);}

        /* ESTILOS CLONADOS DENTRO DEL PREVIEW */
        .preview-cloned-content { padding: 24px 20px; display: flex; flex-direction: column; gap: 16px; width: 100%; height: 100%; overflow-y: auto; }
        .preview-cloned-content .question-card { pointer-events: none; border-color: var(--border); box-shadow: none; border: 1px solid #e2e8f0;}
        .preview-cloned-content .btn-delete-q { display: none; }
        .preview-cloned-content .btn-add-option { display: none; }
        .preview-cloned-content .q-title-input { border: none !important; margin-bottom: 8px;}

        @media (max-width: 900px) {
            .builder-container { grid-template-columns: 1fr; }
            .tools-sidebar { position: static; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; }
            .tools-title { grid-column: 1 / -1; margin-bottom: 4px;}
            .tool-btn { margin-bottom: 0; }
            .btn-preview, .btn-save-form { grid-column: 1 / -1; margin-top: 4px;}
            
            .btn-close-modal { top: 10px; right: 10px; }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include 'components/header.php'; ?>

        <div class="content-area">

            <div class="header-actions">
                <div class="estandar-header-group">
                    <div class="icon-box-std">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="estandar-header-text">
                        <h1 class="estandar-title">Constructor de Asistencia</h1>
                        <p class="estandar-subtitle">Personaliza las preguntas del formulario para los trabajadores.</p>
                    </div>
                </div>
            </div>

            <div class="summary-cards-grid">
                <div class="summary-card card-preguntas">
                    <i class="fa-solid fa-list-ol summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-list-ol"></i></div>
                            <h2 class="summary-value" id="cardTotalPreguntas">3</h2>
                        </div>
                        <h3 class="summary-title">Total Preguntas</h3>
                        <p class="summary-desc">Campos activos en tu formulario.</p>
                    </div>
                </div>

                <div class="summary-card card-total">
                    <i class="fa-solid fa-users summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-users"></i></div>
                            <h2 class="summary-value"><?php echo $asistencias_historicas; ?></h2>
                        </div>
                        <h3 class="summary-title">Histórico General</h3>
                        <p class="summary-desc">Asistencias firmadas en total.</p>
                    </div>
                </div>

                <div class="summary-card card-mes">
                    <i class="fa-solid fa-calendar-check summary-bg-icon"></i>
                    <div class="summary-content">
                        <div class="summary-header">
                            <div class="summary-icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                            <h2 class="summary-value"><?php echo $asistencias_mes; ?></h2>
                        </div>
                        <h3 class="summary-title">Asistencias del Mes</h3>
                        <p class="summary-desc">Firmas recolectadas este periodo.</p>
                    </div>
                </div>
            </div>

            <div class="builder-container">
                
                <div class="tools-sidebar">
                    <h3 class="tools-title"><i class="fa-solid fa-cube"></i> Herramientas</h3>
                    
                    <button class="tool-btn" onclick="addQuestion('text')">
                        <i class="fa-solid fa-minus"></i> Respuesta Corta
                    </button>
                    
                    <button class="tool-btn" onclick="addQuestion('paragraph')">
                        <i class="fa-solid fa-align-left"></i> Párrafo Largo
                    </button>
                    
                    <button class="tool-btn" onclick="addQuestion('radio')">
                        <i class="fa-regular fa-circle-dot"></i> Única Opción
                    </button>
                    
                    <button class="tool-btn" onclick="addQuestion('checkbox')">
                        <i class="fa-regular fa-square-check"></i> Casillas (Varias)
                    </button>

                    <div style="border-top: 1px dashed #cbd5e1; margin: 16px 0 12px 0;"></div>

                    <button class="btn-preview" onclick="abrirVistaPrevia()">
                        <i class="fa-regular fa-eye"></i> Vista Previa
                    </button>

                    <button class="btn-save-form" onclick="guardarFormulario()">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Diseño
                    </button>
                </div>

                <div class="form-canvas-area">
                    <div class="preview-content" id="formCanvasWork">
                        
                        <div class="preview-header-card">
                            <h2>Registro de Asistencia</h2>
                            <p>Capacitación: Uso Correcto de Extintores</p>
                        </div>

                        <div class="question-card fixed-field">
                            <input type="text" class="q-title-input" value="Número de Cédula" readonly>
                            <div class="fake-input">El trabajador escribirá su cédula aquí...</div>
                        </div>
                        <div class="question-card fixed-field">
                            <input type="text" class="q-title-input" value="Nombre Completo" readonly>
                            <div class="fake-input">El trabajador escribirá su nombre aquí...</div>
                        </div>

                        <div id="dynamicQuestionsZone" style="display: flex; flex-direction: column; gap: 16px;">
                            </div>

                        <div class="question-card fixed-field">
                            <input type="text" class="q-title-input" value="Firma Digital" readonly>
                            <div class="signature-fake">
                                <i class="fa-solid fa-pen"></i>
                            </div>
                        </div>

                        <div style="width: 100%; padding: 12px; background: var(--primary); color: white; text-align: center; border-radius: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.9rem; margin-top: 4px;">
                            Enviar Asistencia
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </main>

    <div class="modal-overlay" id="modalPreview">
        <button class="btn-close-modal" onclick="cerrarVistaPrevia()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-preview-box">
            
            <div class="device-toggles">
                <button class="device-btn active" onclick="changeDevice('desktop', this)" title="Vista Computador">
                    <i class="fa-solid fa-desktop"></i>
                </button>
                <button class="device-btn" onclick="changeDevice('tablet', this)" title="Vista Tablet">
                    <i class="fa-solid fa-tablet-screen-button"></i>
                </button>
                <button class="device-btn" onclick="changeDevice('mobile', this)" title="Vista Celular">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </button>
            </div>

            <div class="device-simulator">
                <div id="deviceFrame" class="device-frame frame-desktop">
                    <div class="preview-cloned-content" id="clonedCanvas"></div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // === LÓGICA DE TARJETAS Y CONTADOR ===
        let questionCounter = 0;
        let baseQuestions = 3; // Cédula, Nombre, Firma

        function actualizarContador() {
            const total = baseQuestions + questionCounter;
            document.getElementById('cardTotalPreguntas').innerText = total;
            
            const card = document.querySelector('.card-preguntas');
            card.style.transform = 'scale(1.02)';
            setTimeout(() => { card.style.transform = 'scale(1)'; }, 200);
        }

        // === LÓGICA DE AGREGAR PREGUNTAS ===
        function addQuestion(type) {
            questionCounter++;
            const zone = document.getElementById('dynamicQuestionsZone');
            
            const card = document.createElement('div');
            card.className = 'question-card';
            card.id = `q-${questionCounter}`;

            let htmlContent = `
                <button class="btn-delete-q" onclick="deleteQuestion('${card.id}')" title="Eliminar pregunta">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <input type="text" class="q-title-input" placeholder="Pregunta sin título" autofocus>
            `;

            if (type === 'text') {
                htmlContent += `<div class="fake-input">Texto de respuesta corta</div>`;
            } 
            else if (type === 'paragraph') {
                htmlContent += `<div class="fake-input" style="height: 60px;">Texto de respuesta larga</div>`;
            } 
            else if (type === 'radio') {
                htmlContent += `
                    <div class="options-list" id="opts-${questionCounter}">
                        <div class="option-item">
                            <i class="fa-regular fa-circle"></i>
                            <input type="text" class="option-input" placeholder="Opción 1" value="Opción 1">
                        </div>
                    </div>
                    <button class="btn-add-option" onclick="addOption('${questionCounter}', 'radio')">
                        <i class="fa-solid fa-plus"></i> Agregar opción
                    </button>
                `;
            }
            else if (type === 'checkbox') {
                htmlContent += `
                    <div class="options-list" id="opts-${questionCounter}">
                        <div class="option-item">
                            <i class="fa-regular fa-square"></i>
                            <input type="text" class="option-input" placeholder="Opción 1" value="Opción 1">
                        </div>
                    </div>
                    <button class="btn-add-option" onclick="addOption('${questionCounter}', 'checkbox')">
                        <i class="fa-solid fa-plus"></i> Agregar opción
                    </button>
                `;
            }

            card.innerHTML = htmlContent;
            zone.appendChild(card);
            
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            actualizarContador();
        }

        function deleteQuestion(id) {
            const card = document.getElementById(id);
            if(card) {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    questionCounter--;
                    actualizarContador();
                }, 200);
            }
        }

        function addOption(qId, type) {
            const list = document.getElementById(`opts-${qId}`);
            const optCount = list.children.length + 1;
            const icon = type === 'radio' ? 'fa-circle' : 'fa-square';
            
            const div = document.createElement('div');
            div.className = 'option-item';
            div.innerHTML = `
                <i class="fa-regular ${icon}"></i>
                <input type="text" class="option-input" placeholder="Opción ${optCount}" value="Opción ${optCount}" autofocus>
            `;
            list.appendChild(div);
        }

        function guardarFormulario() {
            const btn = document.querySelector('.btn-save-form');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardado';
                btn.style.background = '#10b981'; 
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '';
                }, 2000);
            }, 800);
        }

        // === LÓGICA DEL MODAL DE VISTA PREVIA ===
        function abrirVistaPrevia() {
            const modal = document.getElementById('modalPreview');
            const canvasWork = document.getElementById('formCanvasWork');
            const clonedCanvas = document.getElementById('clonedCanvas');
            
            clonedCanvas.innerHTML = canvasWork.innerHTML;
            
            const originalInputs = canvasWork.querySelectorAll('input');
            const clonedInputs = clonedCanvas.querySelectorAll('input');
            originalInputs.forEach((input, index) => {
                clonedInputs[index].value = input.value;
            });

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Iniciar por defecto en la vista PC que ahora es compacta
            const desktopBtn = document.querySelector('.device-btn[title*="Computador"]');
            if(desktopBtn) changeDevice('desktop', desktopBtn);
        }

        function cerrarVistaPrevia() {
            const modal = document.getElementById('modalPreview');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function changeDevice(device, btn) {
            document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const frame = document.getElementById('deviceFrame');
            frame.className = 'device-frame'; 
            frame.classList.add(`frame-${device}`);
        }
    </script>
</body>
</html>