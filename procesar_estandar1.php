<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Cargar el Autoload de Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Exige sesión válida
$u = require_auth($conn);
$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'];

$accion = $_REQUEST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. SST envía a firma
    if ($accion === 'enviar_firma' && $usuario_rol === 'sst') {
        $firma_sst = str_replace(' ', '+', trim($_POST['firma_sst'] ?? ''));
        if (!empty($firma_sst)) {
            $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado, firma_sst) VALUES (?, 'pendiente_firma', ?) 
                    ON DUPLICATE KEY UPDATE estado = 'pendiente_firma', firma_sst = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_sst, $firma_sst]);
            header("Location: dashboard.php?doc=enviado");
            exit;
        }
    } 
    
    // 2. Representante firma el documento
    elseif ($accion === 'firmar_doc' && $usuario_rol === 'representante') {
        $firma_base64 = str_replace(' ', '+', trim($_POST['firma_rep'] ?? ''));
        $doc_id = $_POST['doc_id'] ?? 0;
        
        if (!empty($firma_base64) && $doc_id > 0) {
            $sql = "UPDATE doc_asignacion_sst 
                    SET estado = 'firmado', representante_id = ?, firma_representante = ?, fecha_firma = NOW() 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_base64, $doc_id]);
            header("Location: dashboard.php?doc=firmado");
            exit;
        }
    }
    
    // 3. GENERAR EL PDF USANDO mPDF
    elseif ($accion === 'generar_pdf') {
        $doc_id = $_POST['doc_id'] ?? 0;
        
        if (!class_exists(\Mpdf\Mpdf::class)) {
            echo json_encode(['status' => 'error', 'message' => 'Librería mPDF no instalada.']);
            exit;
        }

        if ($doc_id > 0) {
            $stmt_doc = $conn->prepare("SELECT d.*, 
                                           u_sst.nombre as sst_nombre, u_sst.apellido as sst_apellido, u_sst.cedula as sst_cedula, 
                                           u_sst.licencia_sst, u_sst.tipo_licencia as sst_tipo_licencia, u_sst.numero_licencia as sst_num_licencia,
                                           DATE_FORMAT(u_sst.fecha_licencia, '%d/%m/%Y') as sst_fecha_licencia, u_sst.ciudad as sst_ciudad,
                                           u_rep.nombre as rep_nombre, u_rep.apellido as rep_apellido, u_rep.cedula as rep_cedula
                                    FROM doc_asignacion_sst d 
                                    JOIN usuarios u_sst ON d.sst_id = u_sst.id 
                                    LEFT JOIN usuarios u_rep ON d.representante_id = u_rep.id
                                    WHERE d.id = ?");
            $stmt_doc->execute([$doc_id]);
            $doc = $stmt_doc->fetch(PDO::FETCH_ASSOC);

            if ($doc) {
                // Variables
                $empresa = "Sistemas P";
                $rol = "Responsable";
                $nombreSST = trim($doc['sst_nombre'] . ' ' . $doc['sst_apellido']);
                $cedulaSST = $doc['sst_cedula'];
                $tipoLic = !empty($doc['sst_tipo_licencia']) ? $doc['sst_tipo_licencia'] : 'No registrada';
                $numLic = !empty($doc['sst_num_licencia']) ? $doc['sst_num_licencia'] : 'N/A';
                $fechaLic = !empty($doc['sst_fecha_licencia']) ? $doc['sst_fecha_licencia'] : 'N/A';
                $ciudad = !empty($doc['sst_ciudad']) ? $doc['sst_ciudad'] : 'Bogotá D.C.';
                $fechaFirma = date('d/m/Y', strtotime($doc['fecha_firma']));
                $nombreRep = trim($doc['rep_nombre'] . ' ' . $doc['rep_apellido']);
                $cedulaRep = $doc['rep_cedula'];
                
                // Reparamos base64
                $firmaRep = !empty($doc['firma_representante']) ? str_replace(' ', '+', $doc['firma_representante']) : '';
                $firmaSST = !empty($doc['firma_sst']) ? str_replace(' ', '+', $doc['firma_sst']) : '';

                // HTML Sólido para mPDF
                $html = '
                <div style="font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.6; text-align: justify;">
                    <h3 style="text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase;">
                        Acta de designación del responsable del SG-SST
                    </h3>
                    
                    <p style="margin-bottom: 20px;">En cumplimiento de lo establecido en la normatividad vigente en Seguridad y Salud en el Trabajo, la empresa <b>' . htmlspecialchars($empresa) . '</b> designa como <b>' . htmlspecialchars($rol) . '</b> del Sistema de Gestión de Seguridad y Salud en el Trabajo – SG-SST a:</p>
                    
                    <p style="margin-bottom: 20px;"><b>' . htmlspecialchars($nombreSST) . '</b>, identificado(a) con cédula No. <b>' . htmlspecialchars($cedulaSST) . '</b>, con licencia en Seguridad y Salud en el Trabajo de tipo <b>' . htmlspecialchars($tipoLic) . '</b>, No. <b>' . htmlspecialchars($numLic) . '</b>, expedida el <b>' . htmlspecialchars($fechaLic) . '</b>.</p>
                    
                    <p style="margin-bottom: 20px;">El responsable del SG-SST se compromete a liderar, coordinar y hacer seguimiento a las actividades del Sistema de Gestión de acuerdo con el Decreto 1072 de 2015, la Resolución 0312 de 2019 y demás normas aplicables, garantizando la mejora continua y la protección de la seguridad y salud de todos los trabajadores.</p>
                    
                    <p style="margin-bottom: 50px;">Para constancia se firma la presente en la ciudad de <b>' . htmlspecialchars($ciudad) . '</b>, a los <b>' . htmlspecialchars($fechaFirma) . '</b>.</p>

                    <table width="100%" style="border-collapse: collapse; margin-top: 50px;">
                        <tr>
                            <td width="50%" align="center" valign="bottom" style="padding: 10px;">
                                <div style="height: 100px;">
                                    ' . ($firmaRep ? '<img src="' . $firmaRep . '" style="max-height: 90px; width: auto;">' : '') . '
                                </div>
                                <div style="border-top: 1px solid #000; width: 70%; margin: 5px auto;"></div>
                                <b style="font-size: 15px;">Representante Legal</b><br>
                                <span style="font-size: 13px; color: #333;">' . htmlspecialchars($nombreRep) . '</span><br>
                                <span style="font-size: 13px; color: #333;">C.C. ' . htmlspecialchars($cedulaRep) . '</span>
                            </td>
                            <td width="50%" align="center" valign="bottom" style="padding: 10px;">
                                <div style="height: 100px;">
                                    ' . ($firmaSST ? '<img src="' . $firmaSST . '" style="max-height: 90px; width: auto;">' : '') . '
                                </div>
                                <div style="border-top: 1px solid #000; width: 70%; margin: 5px auto;"></div>
                                <b style="font-size: 15px;">Responsable SG-SST</b><br>
                                <span style="font-size: 13px; color: #333;">' . htmlspecialchars($nombreSST) . '</span><br>
                                <span style="font-size: 13px; color: #333;">C.C. ' . htmlspecialchars($cedulaSST) . '</span>
                            </td>
                        </tr>
                    </table>
                </div>';

                try {
                    // ---------------------------------------------------------
                    // ALGORITMO INFALIBLE DE CARPETAS TEMPORALES PARA MAC XAMPP
                    // ---------------------------------------------------------
                    $tempDir = '';
                    
                    // Intento 1: Usar la carpeta del sistema puro de Mac (Siempre funciona)
                    $dir1 = sys_get_temp_dir();
                    if (is_writable($dir1)) { 
                        $tempDir = $dir1; 
                    } 
                    // Intento 2: Usar la carpeta de XAMPP
                    else {
                        $dir2 = '/Applications/XAMPP/xamppfiles/temp';
                        if (is_writable($dir2)) { $tempDir = $dir2; }
                    }
                    
                    // Intento 3: Crear una en tu proyecto y forzar permisos
                    if (empty($tempDir)) {
                        $dir3 = __DIR__ . '/tmp';
                        if (!is_dir($dir3)) { @mkdir($dir3, 0777, true); @chmod($dir3, 0777); }
                        if (is_writable($dir3)) { $tempDir = $dir3; }
                    }
                    // ---------------------------------------------------------

                    // Configurar mPDF (Letter-L = Carta Horizontal)
                    $mpdf_config = [
                        'format' => 'Letter-L',
                        'margin_left' => 25,
                        'margin_right' => 25,
                        'margin_top' => 30,
                        'margin_bottom' => 30,
                    ];

                    // Le pasamos la carpeta desbloqueada
                    if (!empty($tempDir)) {
                        $mpdf_config['tempDir'] = $tempDir;
                    }

                    $mpdf = new \Mpdf\Mpdf($mpdf_config);
                    $mpdf->WriteHTML($html);
                    
                    // Extraer PDF como String y convertir a Base64
                    $pdf_content = $mpdf->Output('', 'S');
                    $pdf_base64 = 'data:application/pdf;base64,' . base64_encode($pdf_content);

                    // Guardar en BD
                    $stmt_upd = $conn->prepare("UPDATE doc_asignacion_sst SET archivo_pdf = ? WHERE id = ?");
                    $stmt_upd->execute([$pdf_base64, $doc_id]);

                    echo json_encode(['status' => 'success', 'pdf' => $pdf_base64]);
                    exit;
                } catch (\Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => 'Error de mPDF: ' . $e->getMessage()]);
                    exit;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Documento no encontrado.']);
        exit;
    }
} 
// 4. Crear una NUEVA VERSIÓN del Acta
elseif ($accion === 'nueva_version' && $usuario_rol === 'sst') {
    $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado) VALUES (?, 'borrador')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario_id]);
    header("Location: dashboard.php?doc=nueva_version");
    exit;
}

header("Location: dashboard.php");
exit;