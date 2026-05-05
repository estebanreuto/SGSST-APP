<?php
// ========================================================
// LÓGICA ESTÁNDAR 1 (DOCUMENTO DE ASIGNACIÓN)
// ========================================================
$doc_asignacion = null;
try {
    if ($usuario_rol === 'sst') {
        $stmt_doc = $conn->prepare("SELECT d.*, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula FROM doc_asignacion_sst d LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id WHERE d.sst_id = ? ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute([$_SESSION['usuario_id']]);
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    } elseif ($usuario_rol === 'representante') {
        $stmt_doc = $conn->prepare("SELECT d.*, u_sst.nombre as sst_nombre, u_sst.apellido as sst_apellido, u_sst.cedula as sst_cedula, u_sst.licencia_sst, u_sst.tipo_licencia as sst_tipo_licencia, u_sst.numero_licencia as sst_num_licencia, DATE_FORMAT(u_sst.fecha_licencia, '%d/%m/%Y') as sst_fecha_licencia, u_sst.ciudad as sst_ciudad, u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula FROM doc_asignacion_sst d JOIN usuarios u_sst ON d.sst_id = u_sst.id LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id WHERE d.estado IN ('pendiente_firma', 'firmado') ORDER BY d.id DESC LIMIT 1");
        $stmt_doc->execute();
        $doc_asignacion = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $doc_asignacion = null; }

$doc_empresa = "Sistemas P";  $doc_rol = "Responsable"; $doc_nombre = ""; $doc_cedula = ""; $doc_tipo_lic = ""; $doc_num_lic = ""; $doc_fecha_lic = ""; $doc_ciudad = ""; $doc_fecha_firma = "____/____/________"; $doc_firma_sst = ""; $doc_rep_nombre = ""; $doc_rep_cedula = ""; $doc_firma_rep = "";

if ($usuario_rol === 'sst') {
    $doc_nombre = ($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? ''); $doc_cedula = $usuario_info['cedula'] ?? ''; $doc_tipo_lic = $usuario_info['tipo_licencia'] ?? ''; $doc_num_lic = $usuario_info['numero_licencia'] ?? ''; $doc_fecha_lic = $usuario_info['fecha_licencia'] ?? ''; $doc_ciudad = $usuario_info['ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; $doc_rep_nombre = trim(($doc_asignacion['rep_nombre'] ?? '') . ' ' . ($doc_asignacion['rep_apellido'] ?? '')); $doc_rep_cedula = $doc_asignacion['rep_cedula'] ?? ''; $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    if ($doc_asignacion && $doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
} elseif ($doc_asignacion) {
    $doc_nombre = ($doc_asignacion['sst_nombre'] ?? '') . ' ' . ($doc_asignacion['sst_apellido'] ?? ''); $doc_cedula = $doc_asignacion['sst_cedula'] ?? ''; $doc_tipo_lic = $doc_asignacion['sst_tipo_licencia'] ?? ''; $doc_num_lic = $doc_asignacion['sst_num_licencia'] ?? ''; $doc_fecha_lic = $doc_asignacion['sst_fecha_licencia'] ?? ''; $doc_ciudad = $doc_asignacion['sst_ciudad'] ?? ''; $doc_firma_sst = $doc_asignacion['firma_sst'] ?? ''; $doc_firma_rep = $doc_asignacion['firma_representante'] ?? '';
    if (!empty($doc_asignacion['rep_nombre'])) { $doc_rep_nombre = trim($doc_asignacion['rep_nombre'] . ' ' . $doc_asignacion['rep_apellido']); $doc_rep_cedula = $doc_asignacion['rep_cedula'];
    } else { $doc_rep_nombre = trim(($usuario_info['nombre'] ?? '') . ' ' . ($usuario_info['apellido'] ?? '')); $doc_rep_cedula = $usuario_info['cedula'] ?? ''; }
    if ($doc_asignacion['estado'] === 'firmado' && !empty($doc_asignacion['fecha_firma'])) { $doc_fecha_firma = date('d/m/Y', strtotime($doc_asignacion['fecha_firma'])); }
}

// ========================================================
// LÓGICA ESTÁNDAR 2 (REVISIÓN DE ESTADO GENERAL)
// ========================================================
$anio_actual = (int)date('Y');
$planillas_db = [];
try {
    $stmt_p = $conn->prepare("SELECT id, mes FROM estandar2_planillas WHERE anio = ?");
    $stmt_p->execute([$anio_actual]);
    while ($row = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
        $planillas_db[$row['mes']] = $row;
    }
} catch (PDOException $e) {}

$hoy = new DateTime();
$hoy->setTime(0, 0, 0);

// ========================================================
// ESTADOS DE LOS ESTÁNDARES (PARA LOS BADGES VISUALES)
// ========================================================

// Estado Estándar 1
$std1_estado = ($doc_asignacion && $doc_asignacion['estado'] === 'firmado') ? 'Completado' : 'Pendiente';
$std1_class = ($std1_estado === 'Completado') ? 'badge-std-success' : 'badge-std-pending';

// Estado Estándar 2 (Revisar si hay vencidos en el año actual)
$std2_tiene_vencidos = false;
for ($m = 1; $m <= 12; $m++) {
    if (!isset($planillas_db[$m])) {
        $fecha_vencimiento = new DateTime("$anio_actual-" . sprintf('%02d', $m) . "-10");
        $fecha_vencimiento->modify('+1 month'); 
        $fecha_vencimiento->setTime(0, 0, 0);
        $intervalo = $hoy->diff($fecha_vencimiento);
        $dias_restantes = (int)$intervalo->format('%R%a');
        if ($dias_restantes < 0) {
            $std2_tiene_vencidos = true;
            break;
        }
    }
}
$std2_estado = $std2_tiene_vencidos ? 'Pendiente' : 'Al Día';
$std2_class = $std2_tiene_vencidos ? 'badge-std-pending' : 'badge-std-success';

// Estado Estándares 3 al 7 (Por defecto en Pendiente hasta que se programen)
$std3_estado = 'Pendiente'; $std3_class = 'badge-std-pending';
$std4_estado = 'Pendiente'; $std4_class = 'badge-std-pending';
$std5_estado = 'Pendiente'; $std5_class = 'badge-std-pending';
$std6_estado = 'Pendiente'; $std6_class = 'badge-std-pending';
$std7_estado = 'Pendiente'; $std7_class = 'badge-std-pending';

?>

<style>
    /* ========================================================
       ESTILOS PARA LOS BADGES DE ESTADO (Completado / Pendiente)
       ======================================================== */
    .badge-std {
        font-size: 0.65rem;
        padding: 4px 8px;
        border-radius: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }
    .badge-std-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-std-pending { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* ========================================================
       NUEVO DISEÑO DE GRID PARA ESTÁNDARES COMPACTO
       ======================================================== */
    .standards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
        margin-top: 12px;
        font-family: 'Inter', sans-serif;
    }
    .standard-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .standard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.04);
        border-color: #cbd5e1;
    }
    .std-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .std-icon-box {
        width: 36px;
        height: 36px;
        background: rgba(255, 138, 31, 0.08);
        color: var(--primary2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .std-icon-box svg {
        width: 18px;
        height: 18px;
    }
    .std-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text);
        margin: 0 0 6px 0;
        line-height: 1.3;
    }
    .std-desc {
        color: var(--muted);
        font-size: 0.75rem;
        line-height: 1.3;
        margin: 0 0 12px 0;
        flex-grow: 1;
    }
    .std-footer {
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    .btn-std {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 6px 10px;
        background: #f8fafc;
        color: var(--primary2);
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }
    .btn-std:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .btn-std.disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: not-allowed;
    }
    
    @media (max-width: 1024px) {
        .standards-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }
    }

    @media (max-width: 768px) {
        .standards-grid {
            grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
            gap: 12px;
        }
        .standard-card {
            padding: 12px;
        }
        .std-header {
             margin-bottom: 8px;
        }
        .std-icon-box {
            width: 32px;
            height: 32px;
        }
        .std-icon-box svg {
            width: 16px;
            height: 16px;
        }
        .std-title {
            font-size: 0.9rem;
        }
        .std-desc {
            font-size: 0.75rem;
            margin-bottom: 12px;
        }
        .btn-std {
            padding: 8px 10px;
            font-size: 0.75rem;
        }
    }
</style>

<h2 class="section-title" style="margin-top: 24px; border-bottom: none; padding-bottom: 0;">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" style="color: var(--primary2);">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
    </svg>
    Estándares Mínimos (10 o menos trabajadores)
</h2>
<p style="color: var(--muted); font-size: 0.85rem; margin-top: 0; margin-bottom: 8px;">Resolución 0312 de 2019: Requisitos aplicables para empresas, empleadores y contratantes clasificados en riesgo I, II o III.</p>

<div class="standards-grid">
    
    <!-- ESTÁNDAR 1 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <span class="badge-std <?php echo $std1_class; ?>"><?php echo $std1_estado; ?></span>
        </div>
        <h3 class="std-title">1. Asignación de persona que diseña el SG-SST</h3>
        <?php if ($usuario_rol === 'sst'): ?>
            <p class="std-desc">Genera el documento de designación, fírmalo y envíalo para la aprobación del Representante Legal.</p>
            <div class="std-footer">
                <a href="estandar1.php" class="btn-std">Abrir Panel de Designación <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></a>
            </div>
        <?php elseif ($usuario_rol === 'representante'): ?>
            <p class="std-desc">El Responsable SG-SST gestionará el acta de designación desde este módulo. Revisa y aprueba el documento.</p>
            <div class="std-footer">
                <a href="estandar1.php" class="btn-std">Ver Estado de Acta <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTÁNDAR 2 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <span class="badge-std <?php echo $std2_class; ?>"><?php echo $std2_estado; ?></span>
        </div>
        <h3 class="std-title">2. Afiliación al Sistema de Seguridad Social</h3>
        <?php if ($usuario_rol === 'sst'): ?>
            <p class="std-desc">Control y carga mensual de las planillas de pago de Seguridad Social (PILA) de la empresa.</p>
            <div class="std-footer">
                <a href="estandar2.php" class="btn-std">Abrir Panel de Planillas <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></a>
            </div>
        <?php elseif ($usuario_rol === 'representante'): ?>
            <p class="std-desc">El Responsable SG-SST se encarga de subir las planillas. Visualiza el resumen de cumplimiento mensual.</p>
            <div class="std-footer">
                <a href="estandar2.php" class="btn-std">Ver Estado <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTÁNDAR 3 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477 4.5 1.253" /></svg>
            </div>
            <span class="badge-std <?php echo $std3_class; ?>"><?php echo $std3_estado; ?></span>
        </div>
        <h3 class="std-title">3. Capacitación en SST</h3>
        <?php if ($usuario_rol === 'sst'): ?>
            <p class="std-desc">Elabora, ejecuta y realiza el seguimiento al programa de capacitación en promoción y prevención.</p>
            <div class="std-footer">
                <a href="estandar3.php" class="btn-std">Abrir Panel de Capacitaciones <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></a>
            </div>
        <?php elseif ($usuario_rol === 'representante'): ?>
            <p class="std-desc">El Responsable SG-SST gestionará las capacitaciones. Consulta el cronograma y estado del personal.</p>
            <div class="std-footer">
                <a href="estandar3.php" class="btn-std">Ver Capacitaciones <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTÁNDAR 4 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            </div>
            <span class="badge-std <?php echo $std4_class; ?>"><?php echo $std4_estado; ?></span>
        </div>
        <h3 class="std-title">4. Plan Anual de Trabajo</h3>
        <p class="std-desc">Elaborar el Plan Anual de Trabajo firmado por el empleador o contratante.</p>
        <div class="std-footer">
            <button class="btn-std disabled" disabled>Próximamente</button>
        </div>
    </div>

    <!-- ESTÁNDAR 5 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <span class="badge-std <?php echo $std5_class; ?>"><?php echo $std5_estado; ?></span>
        </div>
        <h3 class="std-title">5. Evaluaciones médicas ocupacionales</h3>
        <p class="std-desc">Realizar las evaluaciones médicas ocupacionales (ingreso, periódicas, retiro).</p>
        <div class="std-footer">
            <button class="btn-std disabled" disabled>Próximamente</button>
        </div>
    </div>

    <!-- ESTÁNDAR 6 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <span class="badge-std <?php echo $std6_class; ?>"><?php echo $std6_estado; ?></span>
        </div>
        <h3 class="std-title">6. Identificación de peligros, evaluación de riesgos</h3>
        <p class="std-desc">Realizar la identificación de peligros y la evaluación y valoración de los riesgos.</p>
        <div class="std-footer">
            <button class="btn-std disabled" disabled>Próximamente</button>
        </div>
    </div>

    <!-- ESTÁNDAR 7 -->
    <div class="standard-card">
        <div class="std-header">
            <div class="std-icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" /></svg>
            </div>
            <span class="badge-std <?php echo $std7_class; ?>"><?php echo $std7_estado; ?></span>
        </div>
        <h3 class="std-title">7. Medidas de prevención y control</h3>
        <p class="std-desc">Ejecutar medidas de prevención y control con base en los resultados de la identificación de peligros.</p>
        <div class="std-footer">
            <button class="btn-std disabled" disabled>Próximamente</button>
        </div>
    </div>

</div>
