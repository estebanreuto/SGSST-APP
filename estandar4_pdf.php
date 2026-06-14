<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar4_schema.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$u = require_auth($conn);
ensure_estandar4_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id=?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$empresa_id = (int)($usuario['empresa_id'] ?? 0);
$anio = max(2020, min(2100, (int)($_GET['anio'] ?? date('Y'))));

$stmt = $conn->prepare("SELECT * FROM estandar4_planes WHERE empresa_id=? AND anio=? AND estado='firmado' LIMIT 1");
$stmt->execute([$empresa_id, $anio]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$plan) {
    header('Location: estandar4.php?anio=' . $anio . '&msg=' . urlencode('El PDF requiere las dos firmas.') . '&tipo=error');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM estandar4_actividades WHERE plan_id=? ORDER BY orden,id");
$stmt->execute([$plan['id']]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->prepare("SELECT * FROM estandar4_seguimientos WHERE plan_id=? ORDER BY fecha_max_ejecucion,id");
$stmt->execute([$plan['id']]);
$seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT nombre,apellido,cedula,nombre_empresa,num_doc_empresa,logo_empresa FROM usuarios WHERE id=?");
$stmt->execute([$plan['sst_id']]);
$sst = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$stmt->execute([$plan['representante_id']]);
$representante = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$empresa_nombre = ($representante['nombre_empresa'] ?? '') ?: (($sst['nombre_empresa'] ?? '') ?: 'Empresa');
$empresa_nit = ($representante['num_doc_empresa'] ?? '') ?: ($sst['num_doc_empresa'] ?? '');
$metricas = estandar4_metricas($actividades);
$meses = [1=>'ENE',2=>'FEB',3=>'MAR',4=>'ABR',5=>'MAY',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SEP',10=>'OCT',11=>'NOV',12=>'DIC'];

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page{margin:24px 26px}body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:8px;margin:0}
    h1{margin:0;color:#1e3a8a;font-size:15px}.muted{color:#64748b}.header{border-bottom:2px solid #ff8a1f;padding-bottom:10px;margin-bottom:12px}.brand{color:#ff7a00;font-weight:bold;font-size:11px}.meta{float:right;text-align:right;line-height:1.5}.summary{width:100%;border-collapse:separate;border-spacing:5px;margin:0 -5px 10px}.summary td{border:1px solid #dbe3ec;background:#f8fafc;border-radius:4px;padding:7px}.summary strong{font-size:13px;color:#1e3a8a}.section-title{font-size:10px;color:#1e3a8a;margin:12px 0 5px}.matrix,.tracking{width:100%;border-collapse:collapse;table-layout:fixed}.matrix th,.matrix td,.tracking th,.tracking td{border:1px solid #dbe3ec;padding:4px;vertical-align:top}.matrix th,.tracking th{background:#edf4fb;color:#1e3a8a;font-size:6.5px}.matrix .topic{width:10%}.matrix .activity{width:17%}.matrix .responsible{width:12%}.matrix .month{width:4%}.state{text-align:center;font-weight:bold}.p{color:#4338ca}.e{color:#15803d}.r{color:#b45309}.tracking{font-size:6.5px}.tracking th{font-size:6px}.signatures{width:100%;margin-top:15px}.signatures td{width:50%;text-align:center;padding:8px 25px}.signature{height:55px;max-width:220px;object-fit:contain}.line{border-top:1px solid #334155;margin-top:5px;padding-top:4px}.footer{position:fixed;bottom:-12px;left:0;right:0;text-align:center;color:#94a3b8;font-size:6px}
</style>
</head>
<body>
    <div class="header">
        <div class="meta"><strong>Vigencia <?php echo $anio; ?></strong><br>Generado: <?php echo date('d/m/Y H:i'); ?><br>Meta anual: <?php echo (int)$plan['meta_cumplimiento']; ?>%</div>
        <div class="brand">PREVENTWORK</div>
        <h1>PLAN ANUAL DE TRABAJO SG-SST</h1>
        <div class="muted"><?php echo htmlspecialchars($empresa_nombre); ?><?php echo $empresa_nit ? ' · NIT ' . htmlspecialchars($empresa_nit) : ''; ?></div>
    </div>
    <table class="summary"><tr>
        <td><strong><?php echo count($actividades); ?></strong><br>Actividades</td>
        <td><strong><?php echo $metricas['programadas']; ?></strong><br>Programadas</td>
        <td><strong><?php echo $metricas['ejecutadas']; ?></strong><br>Ejecutadas</td>
        <td><strong><?php echo $metricas['reprogramadas']; ?></strong><br>Reprogramadas</td>
        <td><strong><?php echo $metricas['cumplimiento']; ?>%</strong><br>Cumplimiento</td>
    </tr></table>

    <div class="section-title">Programación anual</div>
    <table class="matrix">
        <thead><tr><th class="topic">Tema</th><th class="activity">Actividad</th><th class="responsible">Responsable</th><?php foreach($meses as $mes): ?><th class="month"><?php echo $mes; ?></th><?php endforeach; ?><th>Observaciones</th></tr></thead>
        <tbody>
        <?php foreach($actividades as $actividad): $programacion=estandar4_decode_programacion($actividad['programacion_json']); ?>
            <tr>
                <td><?php echo htmlspecialchars($actividad['tema']); ?></td>
                <td><?php echo htmlspecialchars($actividad['actividad']); ?></td>
                <td><?php echo htmlspecialchars($actividad['responsable']); ?></td>
                <?php foreach($meses as $numero=>$mes): $dato=$programacion[(string)$numero]??[];$estado=is_array($dato)?($dato['estado']??''):$dato; ?>
                    <td class="state <?php echo strtolower($estado); ?>"><?php echo htmlspecialchars($estado); ?></td>
                <?php endforeach; ?>
                <td><?php echo htmlspecialchars($actividad['observaciones'] ?: ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if($seguimientos): ?>
        <div class="section-title">Seguimiento del Responsable SST</div>
        <table class="tracking"><thead><tr><th>Periodo</th><th>Análisis</th><th>Acción propuesta</th><th>Responsable</th><th>Fecha máxima</th><th>Fecha seguimiento</th><th>Responsable seguimiento</th><th>Resultado</th></tr></thead><tbody>
        <?php foreach($seguimientos as $seguimiento): ?><tr>
            <td><?php echo htmlspecialchars($seguimiento['periodo']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($seguimiento['analisis_resultado'])); ?></td>
            <td><?php echo nl2br(htmlspecialchars($seguimiento['accion_propuesta'])); ?></td>
            <td><?php echo htmlspecialchars($seguimiento['responsable']); ?></td>
            <td><?php echo $seguimiento['fecha_max_ejecucion'] ? date('d/m/Y',strtotime($seguimiento['fecha_max_ejecucion'])) : ''; ?></td>
            <td><?php echo $seguimiento['fecha_seguimiento'] ? date('d/m/Y',strtotime($seguimiento['fecha_seguimiento'])) : ''; ?></td>
            <td><?php echo htmlspecialchars($seguimiento['responsable_seguimiento'] ?: ''); ?></td>
            <td><?php echo nl2br(htmlspecialchars($seguimiento['resultado_seguimiento'] ?: '')); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>

    <table class="signatures"><tr>
        <td><img class="signature" src="<?php echo htmlspecialchars($plan['firma_sst']); ?>"><div class="line"><strong><?php echo htmlspecialchars(trim(($sst['nombre']??'').' '.($sst['apellido']??''))); ?></strong><br>Responsable del SG-SST<br>CC <?php echo htmlspecialchars($sst['cedula']??''); ?></div></td>
        <td><img class="signature" src="<?php echo htmlspecialchars($plan['firma_representante']); ?>"><div class="line"><strong><?php echo htmlspecialchars(trim(($representante['nombre']??'').' '.($representante['apellido']??''))); ?></strong><br>Representante Legal<br>CC <?php echo htmlspecialchars($representante['cedula']??''); ?></div></td>
    </tr></table>
    <div class="footer">Documento generado y firmado electrónicamente en PreventWork · Estándar 4</div>
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
$dompdf->stream('Plan_Anual_SGSST_' . $anio . '.pdf', ['Attachment' => true]);
