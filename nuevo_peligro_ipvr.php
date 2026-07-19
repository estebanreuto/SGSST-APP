<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar6_schema.php';

date_default_timezone_set('America/Bogota');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
$u = require_auth($conn);
ensure_estandar6_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if ($usuario_rol !== 'sst') {
    header('Location: estandar6?msg=' . urlencode('Solo el responsable SST puede registrar peligros en la matriz IPVR.') . '&tipo=error');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmtEmpresa = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
$stmtEmpresa->execute([$usuario_id]);
$empresa_id = (int)$stmtEmpresa->fetchColumn();
$totalRegistros = 0;
if ($empresa_id > 0) {
    $stmtTotal = $conn->prepare('SELECT COUNT(*) FROM estandar6_ipvr_registros WHERE empresa_id = ?');
    $stmtTotal->execute([$empresa_id]);
    $totalRegistros = (int)$stmtTotal->fetchColumn();
}

$catalogos = estandar6_catalogos();
$msg = trim((string)($_GET['msg'] ?? ''));
$tipoMsg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
$current_page = 'nuevo_peligro_ipvr.php';

function ipvrh($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo peligro IPVR | Est&aacute;ndar 6</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar6-form.css">
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="ipvr-page">
        <header class="ipvr-head">
            <div class="ipvr-head-copy">
                <div class="ipvr-head-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <h1>Registrar peligro en la matriz IPVR</h1>
                    <p>Completa el contexto, valora el riesgo y define los controles sin salir del flujo.</p>
                </div>
            </div>
            <a class="ipvr-back" href="estandar6#matriz"><i class="fa-solid fa-arrow-left"></i> Volver al Est&aacute;ndar 6</a>
        </header>

        <?php if ($msg !== ''): ?>
            <div class="ipvr-alert <?php echo $tipoMsg === 'error' ? 'error' : ''; ?>">
                <i class="fa-solid <?php echo $tipoMsg === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                <span><?php echo ipvrh($msg); ?></span>
            </div>
        <?php endif; ?>

        <nav class="ipvr-flow" aria-label="Flujo de registro IPVR">
            <button type="button" class="ipvr-step active" data-step-target="1"><span class="ipvr-step-number">1</span><span><strong>Contexto inicial</strong><small>Organizaci&oacute;n, actividad, exposici&oacute;n y peligro</small></span><i class="fa-solid fa-building-shield ipvr-step-watermark"></i></button>
            <button type="button" class="ipvr-step" data-step-target="2"><span class="ipvr-step-number">2</span><span><strong>Evaluaci&oacute;n inicial</strong><small>Momento cero: ND, NE, NP, NC y NR</small></span><i class="fa-solid fa-chart-line ipvr-step-watermark"></i></button>
            <button type="button" class="ipvr-step" data-step-target="3"><span class="ipvr-step-number">3</span><span><strong>Controles e intervenci&oacute;n</strong><small>Controles, riesgo residual y acciones</small></span><i class="fa-solid fa-shield-halved ipvr-step-watermark"></i></button>
        </nav>

        <form class="ipvr-shell" action="procesar_estandar6.php" method="POST" id="ipvrForm">
            <input type="hidden" name="accion" value="guardar_ipvr">
            <input type="hidden" name="origen_formulario" value="nuevo_peligro_ipvr">
            <section class="ipvr-form-card">
                <div class="ipvr-form-head">
                    <h2 id="stepTitle">1. Contexto inicial de la organizaci&oacute;n</h2>
                    <p id="stepDescription">Consolida la actividad desarrollada, las personas expuestas y el peligro identificado.</p>
                    <i class="fa-solid fa-clipboard-list ipvr-form-watermark"></i>
                </div>
                <div class="ipvr-form">
                    <div class="ipvr-panel active" data-step-panel="1">
                        <div class="ipvr-context-banner">
                            <span class="ipvr-context-banner-icon"><i class="fa-solid fa-building-shield"></i></span>
                            <div><small>Apartado inicial de la matriz</small><h3>Contexto de la actividad a desarrollar y peligro identificado</h3><p>Esta informaci&oacute;n describe la organizaci&oacute;n antes de iniciar la valoraci&oacute;n num&eacute;rica del riesgo.</p></div>
                        </div>
                        <div class="ipvr-section-title"><span class="ipvr-section-icon"><i class="fa-solid fa-location-dot"></i></span><div><h3>Actividad evaluada</h3><p>Ubica el peligro dentro del proceso y describe la tarea real.</p></div></div>
                        <div class="ipvr-grid">
                            <div class="ipvr-field"><label>N&uacute;mero</label><input type="number" name="numero" min="1" placeholder="Autom&aacute;tico"></div>
                            <div class="ipvr-field"><label>Sitio de trabajo</label><input name="sitio_trabajo" placeholder="Centro o sede"></div>
                            <div class="ipvr-field"><label>Cuadro b&aacute;sico</label><input name="cuadro_basico" placeholder="&Aacute;rea o grupo"></div>
                            <div class="ipvr-field"><label>Proceso *</label><input name="proceso" required placeholder="Ej. Producci&oacute;n"></div>
                            <div class="ipvr-field span-2"><label>Actividad *</label><input name="actividad" required placeholder="Actividad que ser&aacute; evaluada"></div>
                            <div class="ipvr-field span-2"><label>Tarea dentro de la actividad</label><input name="tarea" placeholder="Tarea espec&iacute;fica o secuencia"></div>
                            <div class="ipvr-field"><label>Zona o lugar</label><input name="zona_lugar" placeholder="Lugar de ejecuci&oacute;n"></div>
                            <div class="ipvr-field"><label>Clase de actividad</label><select name="clase_actividad"><option>Rutinaria</option><option>No Rutinaria</option></select></div>
                            <div class="ipvr-field"><label>Origen de la actividad</label><select name="origen_actividad"><option>Interna</option><option>Externa</option></select></div>
                            <div class="ipvr-field"><label>Cargos expuestos</label><input name="cargos" placeholder="Ej. Operario de producci&oacute;n"></div>
                        </div>
                        <div class="ipvr-divider"></div>
                        <div class="ipvr-section-title"><span class="ipvr-section-icon"><i class="fa-solid fa-people-group"></i></span><div><h3>Personas expuestas</h3><p>Separa la poblaci&oacute;n por vinculaci&oacute;n; el total se calcula autom&aacute;ticamente.</p></div></div>
                        <div class="ipvr-grid ipvr-grid-four">
                            <div class="ipvr-field"><label>Trabajadores directos</label><input type="number" min="0" name="directos" id="directos" value="0"></div>
                            <div class="ipvr-field"><label>Contratistas</label><input type="number" min="0" name="contratistas" id="contratistas" value="0"></div>
                            <div class="ipvr-field"><label>Visitantes</label><input type="number" min="0" name="visitantes" id="visitantes" value="0"></div>
                            <div class="ipvr-field"><label>Total expuestos</label><input id="totalExposedPreview" value="0" readonly aria-label="Total de personas expuestas"></div>
                        </div>
                        <div class="ipvr-divider"></div>
                        <div class="ipvr-section-title"><span class="ipvr-section-icon"><i class="fa-solid fa-triangle-exclamation"></i></span><div><h3>Identificaci&oacute;n y clasificaci&oacute;n del peligro</h3><p>Selecciona el tipo y concreta la fuente o condici&oacute;n peligrosa.</p></div></div>
                        <div class="ipvr-grid">
                            <div class="ipvr-field"><label>Peligro *</label><select name="peligro" id="peligro" required><option value="">Selecciona...</option><?php foreach ($catalogos['peligros'] as $peligro => $items): ?><option value="<?php echo ipvrh($peligro); ?>"><?php echo ipvrh($peligro); ?></option><?php endforeach; ?></select></div>
                            <div class="ipvr-field span-2"><label>Clasificaci&oacute;n *</label><select name="clasificacion_peligro" id="clasificacion_peligro" required><option value="">Selecciona primero el peligro</option></select></div>
                            <div class="ipvr-field full"><label>Par&aacute;metro de la metodolog&iacute;a</label><input id="methodologyPreview" value="Se asigna autom&aacute;ticamente seg&uacute;n la clasificaci&oacute;n seleccionada" readonly></div>
                            <div class="ipvr-field full"><label>Descripci&oacute;n del peligro</label><textarea name="descripcion_peligro" placeholder="Describe la fuente, condici&oacute;n o situaci&oacute;n que puede causar da&ntilde;o"></textarea></div>
                        </div>
                        <div class="ipvr-divider"></div>
                        <div class="ipvr-section-title"><span class="ipvr-section-icon"><i class="fa-solid fa-heart-pulse"></i></span><div><h3>Efecto posible y consecuencia</h3><p>Clasifica el tipo de afectaci&oacute;n y el nivel de da&ntilde;o que podr&iacute;a producir el peligro.</p></div></div>
                        <div class="ipvr-grid">
                            <div class="ipvr-field"><label>Categor&iacute;a *</label><select name="categoria" id="categoria" required><option>Salud</option><option selected>Seguridad</option><option>Propiedad_Proceso</option></select></div>
                            <div class="ipvr-field span-2"><label>Nivel de da&ntilde;o *</label><select name="nivel_danio" id="nivel_danio" required></select><div class="ipvr-hint">Este valor interviene en el c&aacute;lculo del nivel de consecuencia.</div></div>
                        </div>
                    </div>

                    <div class="ipvr-panel" data-step-panel="2">
                        <div class="ipvr-context-banner ipvr-evaluation-banner">
                            <span class="ipvr-context-banner-icon"><i class="fa-solid fa-chart-line"></i></span>
                            <div><small>Segundo apartado de la matriz</small><h3>Momento Cero &ndash; Evaluaci&oacute;n inicial del peligro y riesgo identificado</h3><p>Valora el riesgo antes de considerar los controles existentes o las acciones de intervenci&oacute;n.</p></div>
                        </div>
                        <section class="ipvr-evaluation-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-sliders"></i><span>Evaluaci&oacute;n subjetiva del riesgo</span></div>
                            <div class="ipvr-evaluation-body ipvr-grid">
                                <div class="ipvr-field"><label>Valoraci&oacute;n cualitativa (ND)</label><select name="nivel_deficiencia" id="nivel_deficiencia"><?php foreach ($catalogos['nivel_deficiencia'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . ipvrh($label); ?></option><?php endforeach; ?></select></div>
                                <div class="ipvr-field"><label>Nivel de exposici&oacute;n (NE)</label><select name="nivel_exposicion" id="nivel_exposicion"><?php foreach ($catalogos['nivel_exposicion'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . ipvrh($label); ?></option><?php endforeach; ?></select></div>
                                <div class="ipvr-field"><label>Nivel de probabilidad (NP = ND &times; NE)</label><input id="npBeforePreview" value="0" readonly></div>
                                <div class="ipvr-field span-2"><label>Interpretaci&oacute;n del nivel de probabilidad</label><input id="npInterpretationPreview" value="BAJO" readonly></div>
                                <div class="ipvr-field"><label>Nivel de consecuencia antes del control (NC)</label><input id="ncBeforePreview" value="10" readonly></div>
                            </div>
                        </section>
                        <section class="ipvr-evaluation-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-chart-column"></i><span>Valoraci&oacute;n del riesgo</span></div>
                            <div class="ipvr-evaluation-body ipvr-grid">
                                <div class="ipvr-field span-2 ipvr-result-field" id="nrBeforeField"><label>Nivel del riesgo antes del control (NR = NP &times; NC)</label><input id="nrBeforePreview" value="0" readonly></div>
                                <div class="ipvr-field ipvr-result-field" id="acceptBeforeField"><label>Aceptabilidad del riesgo antes del control</label><input id="acceptabilityBeforePreview" value="ACEPTABLE" readonly></div>
                            </div>
                        </section>
                        <section class="ipvr-evaluation-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-list-check"></i><span>Criterios para establecer controles</span></div>
                            <div class="ipvr-evaluation-body ipvr-grid">
                                <div class="ipvr-field span-2"><label>Peor consecuencia</label><input id="worstConsequencePreview" value="Se determina seg&uacute;n el peligro y su clasificaci&oacute;n" readonly></div>
                                <div class="ipvr-field"><label>Existencia de requisito legal espec&iacute;fico</label><select name="requisito_legal"><option>NO</option><option>SI</option></select></div>
                            </div>
                        </section>
                    </div>

                    <div class="ipvr-panel" data-step-panel="3">
                        <div class="ipvr-context-banner ipvr-after-controls-banner">
                            <span class="ipvr-context-banner-icon"><i class="fa-solid fa-shield-halved"></i></span>
                            <div><small>Tercer apartado de la matriz</small><h3>Despu&eacute;s de implementar controles</h3><p>Documenta los mecanismos existentes y calcula el riesgo que permanece despu&eacute;s de su aplicaci&oacute;n.</p></div>
                        </div>
                        <section class="ipvr-evaluation-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-shield"></i><span>Tipo de control y mecanismo de medici&oacute;n existente</span></div>
                            <div class="ipvr-evaluation-body ipvr-control-grid">
                                <div class="ipvr-field ipvr-control-card"><label>M. Control fuente</label><textarea name="control_fuente" placeholder="Cambios o controles aplicados en el origen"></textarea></div>
                                <div class="ipvr-field ipvr-control-card"><label>M. Control medio</label><textarea name="control_medio" placeholder="Barreras, aislamiento o condiciones del entorno"></textarea></div>
                                <div class="ipvr-field ipvr-control-card"><label>M. Control persona</label><textarea name="control_persona" placeholder="Capacitaci&oacute;n, entrenamiento o pr&aacute;cticas seguras"></textarea></div>
                                <div class="ipvr-field ipvr-control-card"><label>Instrumento</label><textarea name="instrumento" placeholder="Procedimiento, formato, inspecci&oacute;n o medici&oacute;n"></textarea></div>
                            </div>
                        </section>
                        <section class="ipvr-evaluation-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-arrow-trend-down"></i><span>Evaluaci&oacute;n del riesgo residual</span></div>
                            <div class="ipvr-evaluation-body">
                                <div class="ipvr-residual-note"><i class="fa-solid fa-circle-info"></i><span>Si los controles son administrativos, como se&ntilde;alizaci&oacute;n o capacitaci&oacute;n, el nivel de consecuencia (NC) no deber&iacute;a cambiar en la f&oacute;rmula.</span></div>
                                <div class="ipvr-grid">
                                    <div class="ipvr-field"><label>Nivel de deficiencia despu&eacute;s del control (ND)</label><select name="nivel_deficiencia_residual" id="nivel_deficiencia_residual"><?php foreach ($catalogos['nivel_deficiencia'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>" <?php echo (int)$valor === 2 ? 'selected' : ''; ?>><?php echo (int)$valor . ' - ' . ipvrh($label); ?></option><?php endforeach; ?></select></div>
                                    <div class="ipvr-field"><label>Nivel de exposici&oacute;n despu&eacute;s del control (NE)</label><select name="nivel_exposicion_residual" id="nivel_exposicion_residual"><?php foreach ($catalogos['nivel_exposicion'] as $valor => $label): ?><option value="<?php echo (int)$valor; ?>"><?php echo (int)$valor . ' - ' . ipvrh($label); ?></option><?php endforeach; ?></select></div>
                                    <div class="ipvr-field"><label>Nivel de probabilidad despu&eacute;s (NP = ND &times; NE)</label><input id="npResidualPreview" value="0" readonly></div>
                                    <div class="ipvr-field span-2"><label>Interpretaci&oacute;n del nivel de probabilidad despu&eacute;s</label><input id="npResidualInterpretationPreview" value="BAJO" readonly></div>
                                    <div class="ipvr-field"><label>Nivel de consecuencia despu&eacute;s del control (NC)</label><input type="number" min="0" name="nivel_consecuencia_residual" id="nivel_consecuencia_residual" value="10"><div class="ipvr-hint">Mant&eacute;n el NC inicial cuando el control sea &uacute;nicamente administrativo.</div></div>
                                    <div class="ipvr-field span-2 ipvr-result-field" id="nrResidualField"><label>Nivel del riesgo despu&eacute;s del control (NR = NP &times; NC)</label><input id="nrResidualPreview" value="0" readonly></div>
                                    <div class="ipvr-field ipvr-result-field" id="acceptResidualField"><label>Aceptabilidad del riesgo despu&eacute;s del control</label><input id="acceptabilityResidualPreview" value="ACEPTABLE" readonly></div>
                                </div>
                            </div>
                        </section>
                        <section class="ipvr-evaluation-block ipvr-recommended-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-layer-group"></i><span>Controles recomendados a implementar</span></div>
                            <div class="ipvr-evaluation-body">
                                <div class="ipvr-intervention-intro">
                                    <span class="ipvr-intervention-intro-icon"><i class="fa-solid fa-arrow-down-short-wide"></i></span>
                                    <div><strong>Medidas de intervenci&oacute;n</strong><p>Registra las acciones siguiendo la jerarqu&iacute;a de control. Prioriza eliminar y sustituir antes de depender de medidas administrativas o EPP.</p></div>
                                </div>
                                <div class="ipvr-intervention-grid">
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>A</span><i class="fa-solid fa-ban"></i><div><strong>Eliminaci&oacute;n</strong><small>Retirar completamente el peligro.</small></div></div>
                                        <textarea name="eliminacion" aria-label="Control recomendado de eliminaci&oacute;n" placeholder="Ej. Eliminar la tarea, sustancia o fuente del peligro"></textarea>
                                    </div>
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>B</span><i class="fa-solid fa-repeat"></i><div><strong>Sustituci&oacute;n</strong><small>Reemplazar por una alternativa segura.</small></div></div>
                                        <textarea name="sustitucion" aria-label="Control recomendado de sustitución" placeholder="Ej. Sustituir por un proceso o producto menos peligroso"></textarea>
                                    </div>
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>C</span><i class="fa-solid fa-gears"></i><div><strong>Controles de ingenier&iacute;a</strong><small>Aislar el peligro de las personas.</small></div></div>
                                        <textarea name="controles_ingenieria" aria-label="Control recomendado de ingeniería" placeholder="Ej. Guardas, ventilaci&oacute;n, aislamiento o redise&ntilde;o"></textarea>
                                    </div>
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>D</span><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Se&ntilde;alizaci&oacute;n y advertencia</strong><small>Comunicar y demarcar el riesgo.</small></div></div>
                                        <textarea name="senalizacion_advertencia" aria-label="Control recomendado de señalización y advertencia" placeholder="Ej. Avisos, alarmas, etiquetas o demarcaci&oacute;n"></textarea>
                                    </div>
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>E</span><i class="fa-solid fa-clipboard-check"></i><div><strong>Controles administrativos</strong><small>Definir procedimientos y organizaci&oacute;n.</small></div></div>
                                        <textarea name="administrativos" aria-label="Control administrativo recomendado" placeholder="Ej. Formaci&oacute;n, inspecciones, permisos o rotaci&oacute;n"></textarea>
                                    </div>
                                    <div class="ipvr-intervention-card">
                                        <div class="ipvr-intervention-title"><span>F</span><i class="fa-solid fa-helmet-safety"></i><div><strong>Equipos y elementos de protecci&oacute;n personal</strong><small>Proteger al trabajador del riesgo residual.</small></div></div>
                                        <textarea name="epp" aria-label="Equipo de protección personal recomendado" placeholder="Ej. Casco, respirador, guantes o protecci&oacute;n auditiva"></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="ipvr-evaluation-block ipvr-efficiency-block">
                            <div class="ipvr-evaluation-head"><i class="fa-solid fa-gauge-high"></i><span>Valoraci&oacute;n final de la eficiencia de los controles</span></div>
                            <div class="ipvr-evaluation-body">
                                <div class="ipvr-efficiency-note">
                                    <span class="ipvr-efficiency-note-icon"><i class="fa-solid fa-percent"></i></span>
                                    <div><strong>Evaluaci&oacute;n de la eficacia</strong><p>Compara el nivel de riesgo inicial (NR1) con el nivel de riesgo final o residual (NRF) y complementa el resultado con el historial de accidentes.</p></div>
                                </div>
                                <div class="ipvr-efficiency-grid">
                                    <div class="ipvr-efficiency-field ipvr-efficiency-result" id="efficiencyReductionCard">
                                        <div class="ipvr-efficiency-label"><i class="fa-solid fa-arrow-trend-down"></i><span>Factor de reducci&oacute;n</span></div>
                                        <label for="factorReductionPreview">F = ((NR1 - NRF) / NR1) &times; 100</label>
                                        <input id="factorReductionPreview" value="0,0%" readonly>
                                        <small id="factorReductionReading">Sin reducci&oacute;n calculada</small>
                                    </div>
                                    <div class="ipvr-efficiency-field">
                                        <div class="ipvr-efficiency-label"><i class="fa-solid fa-calendar-minus"></i><span>Registro hist&oacute;rico</span></div>
                                        <label for="accidentes_anterior">Accidentes a&ntilde;o <?php echo (int)date('Y') - 1; ?></label>
                                        <input type="number" min="0" name="accidentes_anterior" id="accidentes_anterior" placeholder="Ej. 2">
                                    </div>
                                    <div class="ipvr-efficiency-field">
                                        <div class="ipvr-efficiency-label"><i class="fa-solid fa-calendar-check"></i><span>Registro actual</span></div>
                                        <label for="accidentes_actual">Accidentes a&ntilde;o <?php echo (int)date('Y'); ?></label>
                                        <input type="number" min="0" name="accidentes_actual" id="accidentes_actual" placeholder="Ej. 0">
                                    </div>
                                    <div class="ipvr-efficiency-field">
                                        <div class="ipvr-efficiency-label"><i class="fa-solid fa-shield-circle-check"></i><span>Resultado</span></div>
                                        <label for="eficacia_controles">Eficacia de los controles</label>
                                        <select name="eficacia_controles" id="eficacia_controles"><option value="">Pendiente de valorar</option><option value="SI">S&iacute;, fueron eficaces</option><option value="NO">No fueron eficaces</option></select>
                                        <small>Selecciona el resultado luego de revisar la reducci&oacute;n y los accidentes.</small>
                                    </div>
                                    <div class="ipvr-efficiency-field ipvr-efficiency-observations">
                                        <div class="ipvr-efficiency-label"><i class="fa-solid fa-message"></i><span>Conclusi&oacute;n</span></div>
                                        <label for="efficiencyObservations">Observaciones</label>
                                        <textarea name="observaciones" id="efficiencyObservations" placeholder="Conclusiones, evidencias revisadas y acciones de seguimiento"></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="ipvr-actions">
                        <a class="ipvr-btn" href="estandar6#matriz"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                        <div class="ipvr-actions-group">
                            <button type="button" class="ipvr-btn" id="prevStep" hidden><i class="fa-solid fa-arrow-left"></i> Anterior</button>
                            <button type="button" class="ipvr-btn blue" id="nextStep">Continuar <i class="fa-solid fa-arrow-right"></i></button>
                            <button type="submit" class="ipvr-btn primary" id="saveIpvr" hidden><i class="fa-solid fa-floppy-disk"></i> Guardar peligro</button>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="ipvr-guide">
                <div class="ipvr-guide-head"><span class="ipvr-guide-icon"><i class="fa-solid fa-calculator"></i></span><div><h3>Lectura en tiempo real</h3></div></div>
                <p>Los resultados se actualizan mientras completas la valoraci&oacute;n. El sistema recalcular&aacute; y validar&aacute; todo al guardar.</p>
                <div class="ipvr-metrics">
                    <div class="ipvr-metric"><span>Total expuestos</span><strong id="calcTotal">0</strong></div>
                    <div class="ipvr-metric" id="initialRiskMetric"><span>Riesgo inicial</span><strong id="calcRiesgoAntes">0</strong></div>
                    <div class="ipvr-metric" id="acceptabilityMetric"><span>Aceptabilidad inicial</span><strong id="calcAceptAntes">ACEPTABLE</strong></div>
                    <div class="ipvr-metric" id="residualRiskMetric"><span>Riesgo residual</span><strong id="calcRiesgoDespues">0</strong></div>
                </div>
                <div class="ipvr-risk-legend" aria-label="Colores por nivel de riesgo">
                    <span class="risk-i"><i class="fa-solid fa-circle"></i> I Cr&iacute;tico</span>
                    <span class="risk-ii"><i class="fa-solid fa-circle"></i> II Corregir</span>
                    <span class="risk-iii"><i class="fa-solid fa-circle"></i> III Mejorable</span>
                    <span class="risk-iv"><i class="fa-solid fa-circle"></i> IV Aceptable</span>
                </div>
                <div class="ipvr-progress"><div class="ipvr-progress-bar" id="stepProgress"></div></div>
                <div class="ipvr-guide-list">
                    <div class="ipvr-guide-item"><i class="fa-solid fa-circle-check"></i><span>Hay <?php echo $totalRegistros; ?> registro(s) reales en la matriz de la empresa.</span></div>
                    <div class="ipvr-guide-item"><i class="fa-solid fa-circle-check"></i><span>Los campos con * son obligatorios para guardar.</span></div>
                    <div class="ipvr-guide-item"><i class="fa-solid fa-circle-check"></i><span>Describe controles concretos y verificables.</span></div>
                </div>
            </aside>
        </form>
    </div>
</main>
<script>
const catalogos = <?php echo json_encode($catalogos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let currentStep = 1;
const stepCopy = {
    1: ['1. Contexto inicial de la organización', 'Consolida la actividad, la exposición, el peligro identificado y su posible consecuencia.'],
    2: ['2. Momento Cero – Evaluación inicial', 'Valora la probabilidad, consecuencia y aceptabilidad antes de considerar los controles.'],
    3: ['3. Controles e intervención', 'Registra controles existentes, riesgo residual y acciones antes de guardar.']
};
function showStep(step) {
    currentStep = Math.max(1, Math.min(3, step));
    document.querySelectorAll('[data-step-panel]').forEach(panel => panel.classList.toggle('active', Number(panel.dataset.stepPanel) === currentStep));
    document.querySelectorAll('[data-step-target]').forEach(button => {
        const number = Number(button.dataset.stepTarget);
        button.classList.toggle('active', number === currentStep);
        button.classList.toggle('done', number < currentStep);
    });
    document.getElementById('stepTitle').textContent = stepCopy[currentStep][0];
    document.getElementById('stepDescription').textContent = stepCopy[currentStep][1];
    document.getElementById('prevStep').hidden = currentStep === 1;
    document.getElementById('nextStep').hidden = currentStep === 3;
    document.getElementById('saveIpvr').hidden = currentStep !== 3;
    document.getElementById('stepProgress').style.width = `${currentStep * 33.333}%`;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function validateCurrentStep() {
    const panel = document.querySelector(`[data-step-panel="${currentStep}"]`);
    const invalid = panel.querySelector(':invalid');
    if (!invalid) return true;
    invalid.reportValidity();
    invalid.focus();
    return false;
}
function fillClasificaciones() {
    const peligro = document.getElementById('peligro');
    const clasificacion = document.getElementById('clasificacion_peligro');
    clasificacion.innerHTML = '<option value="">Selecciona...</option>';
    (catalogos.peligros[peligro.value] || []).forEach(item => {
        const option = document.createElement('option'); option.value = item; option.textContent = item; clasificacion.appendChild(option);
    });
    updateMethodologyPreview();
}
function updateMethodologyPreview() {
    const selected = document.getElementById('clasificacion_peligro').value;
    document.getElementById('methodologyPreview').value = selected
        ? `GTC 45 · ${selected}`
        : 'Se asigna automáticamente según la clasificación seleccionada';
    updateWorstConsequence();
}
function updateWorstConsequence() {
    const value = document.getElementById('clasificacion_peligro').value.toLocaleLowerCase('es');
    let consequence = 'Lesión o afectación a la salud por exposición ocupacional';
    if (/alturas|confinados|eléctrico|electrico|caliente|excavaciones|tránsito|transito|mecánico|mecanico/.test(value)) consequence = 'Lesión grave, fatalidad o incapacidad permanente';
    else if (/químico|quimico|gas|vapor|humo|polvo|fibra/.test(value)) consequence = 'Intoxicación, enfermedad laboral o daño respiratorio';
    else if (/postura|esfuerzo|movimiento|manipulación|manipulacion/.test(value)) consequence = 'Lesión osteomuscular o restricción médico-laboral';
    else if (/psico|gestión|gestion|jornada|grupo|tarea/.test(value)) consequence = 'Afectación psicosocial, estrés o ausentismo';
    else if (/virus|bacteria|hongo|parásito|parasito|fluido/.test(value)) consequence = 'Enfermedad infecciosa o contagio ocupacional';
    document.getElementById('worstConsequencePreview').value = consequence;
}
function ncPorNivel(nivel) {
    const value = (nivel || '').toLowerCase();
    if (value.includes('mortal') || value.includes('catastrofico')) return 100;
    if (value.includes('grave') || value.includes('mayor')) return 60;
    if (value.includes('moderado')) return 25;
    return 10;
}
function aceptabilidad(riesgo) {
    if (riesgo >= 600) return 'NO ACEPTABLE';
    if (riesgo >= 150) return 'CONTROL ESPECÍFICO';
    if (riesgo >= 40) return 'MEJORABLE';
    return 'ACEPTABLE';
}
function riskLevel(riesgo) {
    if (riesgo >= 600) return {className: 'risk-i', level: 'Nivel I', label: 'Crítico'};
    if (riesgo >= 150) return {className: 'risk-ii', level: 'Nivel II', label: 'Corregir'};
    if (riesgo >= 40) return {className: 'risk-iii', level: 'Nivel III', label: 'Mejorable'};
    return {className: 'risk-iv', level: 'Nivel IV', label: 'Aceptable'};
}
function colorRiskMetric(elementId, level) {
    const metric = document.getElementById(elementId);
    metric.classList.remove('risk-i', 'risk-ii', 'risk-iii', 'risk-iv');
    metric.classList.add(level.className);
}
function colorRiskField(elementId, level) {
    const field = document.getElementById(elementId);
    field.classList.remove('risk-i', 'risk-ii', 'risk-iii', 'risk-iv');
    field.classList.add(level.className);
}
function interpretacionNp(np) {
    if (np >= 24) return 'MUY ALTO';
    if (np >= 10) return 'ALTO';
    if (np >= 6) return 'MEDIO';
    return 'BAJO';
}
function fillNivelesDanio() {
    const categoria = document.getElementById('categoria');
    const nivel = document.getElementById('nivel_danio');
    nivel.innerHTML = '';
    (catalogos.niveles_danio[categoria.value] || []).forEach(item => {
        const option = document.createElement('option'); option.value = item; option.textContent = item; nivel.appendChild(option);
    });
    updateCalc();
}
function updateCalc() {
    const value = id => document.getElementById(id)?.value || '0';
    const total = Number(value('directos')) + Number(value('contratistas')) + Number(value('visitantes'));
    const nd = Number(value('nivel_deficiencia')); const ne = Number(value('nivel_exposicion'));
    const np = nd * ne;
    const nc = ncPorNivel(value('nivel_danio'));
    const riesgo = np * nc;
    const ndResidual = Number(value('nivel_deficiencia_residual'));
    const neResidual = Number(value('nivel_exposicion_residual'));
    const ncResidual = Number(value('nivel_consecuencia_residual'));
    const npResidual = ndResidual * neResidual;
    const residual = npResidual * ncResidual;
    const factorReduction = riesgo > 0 ? ((riesgo - residual) / riesgo) * 100 : 0;
    const nivelInicial = riskLevel(riesgo);
    const nivelResidual = riskLevel(residual);
    document.getElementById('calcTotal').textContent = total;
    document.getElementById('totalExposedPreview').value = total;
    document.getElementById('calcRiesgoAntes').textContent = `${riesgo} · ${nivelInicial.level}`;
    document.getElementById('calcAceptAntes').textContent = aceptabilidad(riesgo);
    document.getElementById('calcRiesgoDespues').textContent = `${residual} · ${nivelResidual.level}`;
    document.getElementById('npBeforePreview').value = np;
    document.getElementById('npInterpretationPreview').value = interpretacionNp(np);
    document.getElementById('ncBeforePreview').value = nc;
    document.getElementById('nrBeforePreview').value = `${riesgo} · ${nivelInicial.level} · ${nivelInicial.label}`;
    document.getElementById('acceptabilityBeforePreview').value = aceptabilidad(riesgo);
    document.getElementById('npResidualPreview').value = npResidual;
    document.getElementById('npResidualInterpretationPreview').value = interpretacionNp(npResidual);
    document.getElementById('nrResidualPreview').value = `${residual} · ${nivelResidual.level} · ${nivelResidual.label}`;
    document.getElementById('acceptabilityResidualPreview').value = aceptabilidad(residual);
    document.getElementById('factorReductionPreview').value = `${factorReduction.toLocaleString('es-CO', {minimumFractionDigits: 1, maximumFractionDigits: 1})}%`;
    const efficiencyCard = document.getElementById('efficiencyReductionCard');
    const efficiencyReading = document.getElementById('factorReductionReading');
    efficiencyCard.classList.remove('eff-high', 'eff-medium', 'eff-low');
    if (factorReduction >= 70) {
        efficiencyCard.classList.add('eff-high');
        efficiencyReading.textContent = 'Reducci\u00f3n alta del nivel de riesgo';
    } else if (factorReduction >= 40) {
        efficiencyCard.classList.add('eff-medium');
        efficiencyReading.textContent = 'Reducci\u00f3n moderada; requiere seguimiento';
    } else {
        efficiencyCard.classList.add('eff-low');
        efficiencyReading.textContent = factorReduction > 0 ? 'Reducci\u00f3n baja; revisa los controles' : 'Sin reducci\u00f3n efectiva del riesgo';
    }
    colorRiskMetric('initialRiskMetric', nivelInicial);
    colorRiskMetric('acceptabilityMetric', nivelInicial);
    colorRiskMetric('residualRiskMetric', nivelResidual);
    colorRiskField('nrBeforeField', nivelInicial);
    colorRiskField('acceptBeforeField', nivelInicial);
    colorRiskField('nrResidualField', nivelResidual);
    colorRiskField('acceptResidualField', nivelResidual);
}
document.querySelectorAll('[data-step-target]').forEach(button => button.addEventListener('click', () => {
    const target = Number(button.dataset.stepTarget);
    if (target <= currentStep || validateCurrentStep()) showStep(target);
}));
document.getElementById('nextStep').addEventListener('click', () => { if (validateCurrentStep()) showStep(currentStep + 1); });
document.getElementById('prevStep').addEventListener('click', () => showStep(currentStep - 1));
document.getElementById('peligro').addEventListener('change', fillClasificaciones);
document.getElementById('clasificacion_peligro').addEventListener('change', updateMethodologyPreview);
document.getElementById('categoria').addEventListener('change', fillNivelesDanio);
['directos','contratistas','visitantes','nivel_deficiencia','nivel_exposicion','nivel_danio','nivel_deficiencia_residual','nivel_exposicion_residual','nivel_consecuencia_residual'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateCalc);
    document.getElementById(id)?.addEventListener('change', updateCalc);
});
fillClasificaciones(); fillNivelesDanio(); updateCalc(); showStep(1);
</script>
</body>
</html>
