<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/estandar2_schema.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_auth($conn);
ensure_estandar2_schema($conn);

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$rol = (string)($_SESSION['usuario_rol'] ?? '');
if (!in_array($rol, ['sst', 'representante'], true)) {
    header('Location: dashboard');
    exit;
}

$stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id=? LIMIT 1');
$stmt->execute([$usuarioId]);
$empresaId = (int)$stmt->fetchColumn();
if ($empresaId <= 0) {
    http_response_code(403);
    exit('Empresa no disponible.');
}

$anio = max(2020, min(2050, (int)($_GET['anio'] ?? date('Y'))));
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$stmt = $conn->prepare("SELECT num_doc_empresa,nombre_empresa FROM usuarios WHERE empresa_id=? AND rol='representante' LIMIT 1");
$stmt->execute([$empresaId]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
if (empty($empresa['nombre_empresa']) || empty($empresa['num_doc_empresa'])) {
    $stmt = $conn->prepare('SELECT nombre,cedula FROM solicitudes_empresas WHERE id=? LIMIT 1');
    $stmt->execute([$empresaId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $empresa['nombre_empresa'] = $empresa['nombre_empresa'] ?: ($solicitud['nombre'] ?? 'Empresa');
    $empresa['num_doc_empresa'] = $empresa['num_doc_empresa'] ?: ($solicitud['cedula'] ?? '');
}

$nombreEmpresa = trim((string)($empresa['nombre_empresa'] ?? 'Empresa')) ?: 'Empresa';
$nitEmpresa = trim((string)($empresa['num_doc_empresa'] ?? ''));
$nitBase = explode('-', $nitEmpresa)[0] ?? '';
$nitLimpio = preg_replace('/\D+/', '', $nitBase);
$terminacion = str_pad(substr($nitLimpio, -2), 2, '0', STR_PAD_LEFT);
$terminacionNumero = (int)$terminacion;

function estandar2_dia_habil_por_nit(int $terminacion): int
{
    $rangos = [7, 14, 21, 28, 35, 42, 49, 56, 63, 69, 75, 81, 87, 93, 99];
    foreach ($rangos as $indice => $maximo) {
        if ($terminacion <= $maximo) {
            return $indice + 2;
        }
    }
    return 16;
}

function estandar2_fecha_limite_pdf(int $anio, int $mes, int $diaHabil): string
{
    $contador = 0;
    $dias = (int)date('t', mktime(0, 0, 0, $mes, 1, $anio));
    for ($dia = 1; $dia <= $dias; $dia++) {
        $marca = mktime(0, 0, 0, $mes, $dia, $anio);
        $semana = (int)date('w', $marca);
        if ($semana !== 0 && $semana !== 6) {
            $contador++;
        }
        if ($contador === $diaHabil) {
            return date('d/m/Y', $marca);
        }
    }
    return 'Por definir';
}

$diaHabil = $nitLimpio !== '' ? estandar2_dia_habil_por_nit($terminacionNumero) : 0;
$fechasLimite = [];
for ($mes = 1; $mes <= 12; $mes++) {
    $fechasLimite[$mes] = $diaHabil > 0 ? estandar2_fecha_limite_pdf($anio, $mes, $diaHabil) : 'Por definir';
}

$stmt = $conn->prepare(
    'SELECT p.*,TRIM(CONCAT(COALESCE(u.nombre,\'\'),\' \',COALESCE(u.apellido,\'\'))) AS responsable
     FROM estandar2_planillas p
     LEFT JOIN usuarios u ON u.id=p.subido_por
     WHERE p.empresa_id=? AND p.anio=?
     ORDER BY p.mes'
);
$stmt->execute([$empresaId, $anio]);
$planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$porMes = [];
foreach ($planillas as $planilla) {
    $porMes[(int)$planilla['mes']] = $planilla;
}

$cargadas = count($porMes);
$anioActual = (int)date('Y');
$periodosExigibles = $anio < $anioActual ? 12 : ($anio > $anioActual ? 0 : (int)date('n'));
$cargadasExigibles = count(array_filter(array_keys($porMes), static fn($mes) => (int)$mes <= $periodosExigibles));
$pendientesFecha = max(0, $periodosExigibles - $cargadasExigibles);
$valorAnual = 0.0;
$coberturas = [];
$riesgosConsolidados = [];
foreach ($planillas as $planilla) {
    $valorAnual += (float)($planilla['valor_total'] ?? 0);
    $detectados = (int)($planilla['cedulas_detectadas'] ?? 0);
    $esperados = (int)($planilla['trabajadores_esperados'] ?? 0);
    if ($esperados > 0) {
        $coberturas[] = min(100, round(($detectados / $esperados) * 100));
    }
    foreach (explode(',', (string)($planilla['riesgos_detectados'] ?? '')) as $riesgo) {
        $riesgo = trim($riesgo);
        if ($riesgo !== '') {
            $riesgosConsolidados[$riesgo] = true;
        }
    }
}
$coberturaPromedio = $coberturas ? (int)round(array_sum($coberturas) / count($coberturas)) : null;

function e2pdf($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page{margin:24px 26px 30px}body{margin:0;color:#172554;font-family:DejaVu Sans,sans-serif;font-size:7px;line-height:1.35}
    .header{position:relative;margin-bottom:11px;padding:0 0 10px;border-bottom:2px solid #ff7a00}.brand{color:#ff7a00;font-size:11px;font-weight:bold;letter-spacing:.5px}.header h1{margin:3px 0 1px;color:#1e3a8a;font-size:16px}.header p{margin:0;color:#64748b}.meta{position:absolute;right:0;top:0;text-align:right;color:#475569;line-height:1.55}.meta strong{color:#1e3a8a}
    .summary{width:100%;margin:0 0 10px;border-collapse:separate;border-spacing:5px}.summary td{width:25%;padding:8px;border:1px solid #dbe3ec;border-radius:5px;background:#f8fafc;color:#64748b}.summary strong{display:block;margin-bottom:2px;color:#1e3a8a;font-size:13px}
    .context{width:100%;margin-bottom:11px;border-collapse:collapse}.context td{padding:7px 8px;border:1px solid #fed7aa;background:#fffaf5}.context .label{display:block;margin-bottom:2px;color:#9a3412;font-size:5.7px;font-weight:bold;text-transform:uppercase}.context strong{color:#172554;font-size:8px}.section-title{margin:10px 0 5px;color:#1e3a8a;font-size:10px;font-weight:bold}.section-copy{margin:-3px 0 6px;color:#64748b;font-size:6.4px}
    .annual{width:100%;border-collapse:collapse;table-layout:fixed}.annual th,.annual td{padding:4px;border:1px solid #dbe3ec;vertical-align:top;overflow-wrap:anywhere}.annual th{background:#eaf2fb;color:#1e3a8a;font-size:5.8px;text-transform:uppercase}.annual td{font-size:6.1px}.annual tr:nth-child(even) td{background:#f8fafc}.month{font-weight:bold;color:#1e3a8a}.ok{color:#047857;font-weight:bold}.pending{color:#c2410c;font-weight:bold}.money{text-align:right;white-space:nowrap}.center{text-align:center}
    .details{width:100%;border-collapse:separate;border-spacing:0 6px}.details td{padding:7px 8px;border:1px solid #dbe3ec;background:#fff;vertical-align:top}.details .period{width:15%;border-right:0;background:#eff6ff;color:#1e3a8a}.details .analysis{border-left:0}.details strong{color:#172554}.muted{color:#64748b}.empty{padding:18px;border:1px dashed #cbd5e1;background:#f8fafc;text-align:center;color:#64748b}.risk{display:inline-block;margin:0 3px 3px 0;padding:3px 6px;border-radius:8px;background:#fff7ed;color:#c2410c;font-weight:bold}.footer{position:fixed;right:0;bottom:-17px;left:0;border-top:1px solid #e2e8f0;padding-top:5px;text-align:center;color:#94a3b8;font-size:5.8px}
</style>
</head>
<body>
    <div class="header">
        <div class="meta"><strong>Vigencia <?= $anio ?></strong><br>Generado: <?= date('d/m/Y H:i') ?><br>Estándar 2 - Afiliación al SSSI</div>
        <div class="brand">PREVENTWORK</div>
        <h1>REPORTE ANUAL DE PLANILLAS PILA</h1>
        <p><?= e2pdf($nombreEmpresa) ?><?= $nitEmpresa !== '' ? ' · NIT ' . e2pdf($nitEmpresa) : '' ?></p>
    </div>

    <table class="summary"><tr>
        <td><strong><?= $cargadas ?>/12</strong>Planillas cargadas</td>
        <td><strong><?= $pendientesFecha ?></strong>Pendientes a la fecha</td>
        <td><strong><?= $coberturaPromedio !== null ? $coberturaPromedio . '%' : 'Sin dato' ?></strong>Cobertura promedio</td>
        <td><strong><?= $valorAnual > 0 ? '$' . number_format($valorAnual, 0, ',', '.') : 'Sin dato' ?></strong>Valor anual reportado</td>
    </tr></table>

    <table class="context"><tr>
        <td><span class="label">Empresa</span><strong><?= e2pdf($nombreEmpresa) ?></strong></td>
        <td><span class="label">Terminación NIT</span><strong><?= $nitLimpio !== '' ? e2pdf($terminacion) : 'Sin dato' ?></strong></td>
        <td><span class="label">Día hábil de pago</span><strong><?= $diaHabil > 0 ? $diaHabil . '°' : 'Sin dato' ?></strong></td>
        <td><span class="label">Riesgos consolidados</span><strong><?= $riesgosConsolidados ? e2pdf(implode(', ', array_keys($riesgosConsolidados))) : 'Sin riesgos detectados' ?></strong></td>
    </tr></table>

    <div class="section-title">Control anual por periodo</div>
    <div class="section-copy">Incluye los doce meses de la vigencia, aun cuando el soporte se encuentre pendiente.</div>
    <table class="annual">
        <thead><tr>
            <th style="width:8%">Periodo</th><th style="width:8%">Límite</th><th style="width:7%">Estado</th><th style="width:9%">Trabajadores</th><th style="width:7%">Cobertura</th><th style="width:10%">Valor</th><th style="width:7%">NIT</th><th style="width:6%">Versión</th><th style="width:16%">Responsable</th><th style="width:12%">Registro</th>
        </tr></thead>
        <tbody>
        <?php for ($mes = 1; $mes <= 12; $mes++): $dato = $porMes[$mes] ?? null; ?>
            <?php
                $detectados = $dato ? (int)($dato['cedulas_detectadas'] ?? 0) : 0;
                $esperados = $dato ? (int)($dato['trabajadores_esperados'] ?? 0) : 0;
                $cobertura = $esperados > 0 ? min(100, round(($detectados / $esperados) * 100)) . '%' : 'Sin dato';
            ?>
            <tr>
                <td class="month"><?= e2pdf($meses[$mes]) ?></td>
                <td class="center"><?= e2pdf($fechasLimite[$mes]) ?></td>
                <td class="<?= $dato ? 'ok' : 'pending' ?> center"><?= $dato ? 'Cargada' : 'Pendiente' ?></td>
                <td class="center"><?= $dato ? ($detectados ?: 'S/D') . ' / ' . ($esperados ?: 'S/D') : '-' ?></td>
                <td class="center"><?= $dato ? $cobertura : '-' ?></td>
                <td class="money"><?= $dato && (float)($dato['valor_total'] ?? 0) > 0 ? '$' . number_format((float)$dato['valor_total'], 0, ',', '.') : '-' ?></td>
                <td class="center"><?= $dato ? ((($dato['nit_coincide'] ?? 'NO') === 'SI') ? 'Coincide' : 'Revisar') : '-' ?></td>
                <td class="center"><?= $dato ? 'V' . (int)($dato['version_actual'] ?? 1) . '.0' : '-' ?></td>
                <td><?= $dato ? e2pdf($dato['responsable'] ?: 'Sin responsable') : '-' ?></td>
                <td><?= $dato && !empty($dato['fecha_subida']) ? date('d/m/Y H:i', strtotime($dato['fecha_subida'])) : '-' ?></td>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>

    <div class="section-title">Detalle consolidado de soportes cargados</div>
    <div class="section-copy">Análisis documental, riesgos y novedades registrados para cada planilla.</div>
    <?php if ($planillas): ?>
        <table class="details">
        <?php foreach ($planillas as $planilla): ?>
            <tr>
                <td class="period"><strong><?= e2pdf($meses[(int)$planilla['mes']] ?? 'Periodo') ?> <?= $anio ?></strong><br><span class="muted">V<?= (int)($planilla['version_actual'] ?? 1) ?>.0 · <?= e2pdf($planilla['responsable'] ?: 'Sin responsable') ?></span></td>
                <td class="analysis"><strong>Riesgos:</strong> <?= e2pdf($planilla['riesgos_detectados'] ?: 'No detectados') ?><br><strong>Novedades:</strong> <?= e2pdf($planilla['novedades_resumen'] ?: 'Sin novedades reportadas.') ?><br><span class="muted">NIT: <?= (($planilla['nit_coincide'] ?? 'NO') === 'SI') ? 'coincide' : 'requiere revisión' ?> · Archivo: <?= e2pdf(basename((string)($planilla['archivo_url'] ?? 'soporte.pdf'))) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="empty"><strong>No hay planillas cargadas para <?= $anio ?>.</strong><br>El reporte conserva el control de los doce periodos y sus fechas límite.</div>
    <?php endif; ?>

    <div class="footer">Reporte generado por PreventWork · Documento de control del Estándar 2 SG-SST</div>
</body>
</html>
<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$canvas = $dompdf->getCanvas();
$canvas->page_text(742, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 6, [0.58, 0.64, 0.72]);
$dompdf->stream('Reporte_Planillas_PILA_' . $anio . '.pdf', ['Attachment' => true]);
