<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/document_control_schema.php';

// Cargar el Autoload de Composer para PHPMailer y mPDF
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Cargar variables de entorno para los correos (Igual que en tu login)
function loadEnvSimpleMail($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) { putenv("$key=$value"); $_ENV[$key] = $value; }
    }
}
loadEnvSimpleMail(__DIR__ . '/.env');

// ==============================================================
// FUNCIÓN PODEROSA: ENVÍA CORREOS (PHPMailer) Y NOTIFICACIONES
// ==============================================================
function notificarUsuario($conn, $usuario_id, $email, $nombre, $asunto, $mensaje_notif, $mensaje_correo, $enlace) {
    // 1. Guardar en Base de Datos (Para la Campanita en el Header)
    $sql = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, enlace) VALUES (?, ?, ?, ?)";
    $conn->prepare($sql)->execute([$usuario_id, $asunto, $mensaje_notif, $enlace]);

    // 2. Enviar Correo Electrónico
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER');
            $mail->Password = getenv('SMTP_PASS');
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->SMTPSecure = (getenv('SMTP_SECURE') === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($mail->Username, 'Notificaciones SG-SST');
            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #111; border: 1px solid #e2e8f0; border-radius: 8px; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #ff8a1f;'>$asunto</h2>
                    <p style='font-size: 16px; line-height: 1.6;'>Hola <b>$nombre</b>,</p>
                    <p style='font-size: 15px; line-height: 1.6;'>$mensaje_correo</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #64748b;'>Este es un mensaje automático del Sistema de Gestión de Seguridad y Salud en el Trabajo.</p>
                </div>";
            $mail->send();
        } catch (\Throwable $e) { /* Ignorar error de correo para que el proceso no se caiga */ }
    }
}

// Exige sesión válida
$u = require_auth($conn);
$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'];
$accion = $_REQUEST['accion'] ?? '';
$empresa_id = storage_user_company_id($conn, (int)$usuario_id);
ensure_document_control_schema($conn);

// Obtener nombre del usuario actual para las notificaciones
$stmt_current = $conn->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_current->execute([$usuario_id]);
$currentUser = $stmt_current->fetch(PDO::FETCH_ASSOC);
$currentUserName = trim(($currentUser['nombre'] ?? '') . ' ' . ($currentUser['apellido'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ==========================================================
    // 0. RESPONSABLE SST CARGA Y VALIDA UN FORMATO DOCUMENTAL
    // ==========================================================
    if ($accion === 'cargar_formato' && $usuario_rol === 'sst') {
        if (!hash_equals((string)($_SESSION['estandar1_csrf'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['estandar1_document_error'] = ['La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }
        $config = document_control_config($conn, $empresa_id, 1);
        $metadata = [
            'nombre_documento' => trim((string)($_POST['nombre_documento'] ?? '')),
            'codigo_documento' => trim((string)($_POST['codigo_documento'] ?? '')),
            'version_documento' => trim((string)($_POST['version_documento'] ?? '')),
            'fecha_documento' => trim((string)($_POST['fecha_documento'] ?? '')),
        ];
        $validation = document_control_validate_upload($_FILES['archivo_formato'] ?? [], $metadata, $config, 1);
        if (!$validation['valid']) {
            $_SESSION['estandar1_document_error'] = $validation['errors'];
            $_SESSION['estandar1_document_old'] = $metadata;
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }

        $storage = storage_company_context($conn, $empresa_id);
        if (!$storage) {
            $_SESSION['estandar1_document_error'] = ['No fue posible identificar el almacenamiento de la empresa.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }
        if (((int)$storage['usado_bytes'] + (int)$validation['size']) > (int)$storage['cuota_bytes']) {
            $_SESSION['estandar1_document_error'] = ['El archivo supera el espacio disponible del plan.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }

        storage_prepare_company_folders($empresa_id, (int)$storage['nivel_plan']);
        $folder = storage_folder_path($empresa_id, 1);
        if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
            $_SESSION['estandar1_document_error'] = ['No fue posible preparar la carpeta del Estándar 1.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }

        $savedName = date('Ymd-His') . '-' . bin2hex(random_bytes(7)) . '.' . $validation['extension'];
        $destination = $folder . DIRECTORY_SEPARATOR . $savedName;
        if (!move_uploaded_file((string)$_FILES['archivo_formato']['tmp_name'], $destination)) {
            $_SESSION['estandar1_document_error'] = ['El archivo pasó la validación, pero no pudo guardarse. Inténtalo nuevamente.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }

        try {
            $conn->beginTransaction();
            $relative = str_replace('\\', '/', substr($destination, strlen(dirname(__DIR__)) + 1));
            $conn->prepare("UPDATE control_documental_registros SET estado='obsoleto' WHERE empresa_id=? AND estandar_numero=1 AND codigo_documento=? AND estado IN ('validado','aprobado')")
                ->execute([$empresa_id, $validation['metadata']['codigo_documento']]);
            $stmtFile = $conn->prepare(<<<'SQL'
                INSERT INTO almacenamiento_archivos
                    (empresa_id, estandar_numero, estandar_nombre, nombre_original, nombre_guardado,
                     ruta_relativa, tipo_mime, extension, tamano_bytes, usuario_id, codigo_documento,
                     version_documento, fecha_documento, estado_documental, origen_modulo)
                VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'validado', 'estandar1_formato')
            SQL);
            $stmtFile->execute([
                $empresa_id, storage_standard_catalog()[1], $validation['original'], $savedName, $relative,
                $validation['mime'], $validation['extension'], $validation['size'], $usuario_id,
                $validation['metadata']['codigo_documento'], $validation['metadata']['version_documento'],
                $validation['metadata']['fecha_documento'],
            ]);
            $fileId = (int)$conn->lastInsertId();
            $result = json_encode(['validaciones' => ['estructura_archivo', 'peso', 'codigo', 'version', 'fecha', 'nombre_archivo']], JSON_UNESCAPED_UNICODE);
            $stmtControl = $conn->prepare(<<<'SQL'
                INSERT INTO control_documental_registros
                    (empresa_id, estandar_numero, almacenamiento_archivo_id, tipo_documento, nombre_documento,
                     codigo_documento, version_documento, fecha_documento, estado, archivo_original,
                     resultado_validacion, usuario_id)
                VALUES (?, 1, ?, 'formato', ?, ?, ?, ?, 'validado', ?, ?, ?)
            SQL);
            $stmtControl->execute([
                $empresa_id, $fileId, $validation['metadata']['nombre_documento'],
                $validation['metadata']['codigo_documento'], $validation['metadata']['version_documento'],
                $validation['metadata']['fecha_documento'], $validation['original'], $result, $usuario_id,
            ]);
            $controlId = (int)$conn->lastInsertId();
            $conn->prepare('UPDATE almacenamiento_archivos SET control_registro_id = ? WHERE id = ?')->execute([$controlId, $fileId]);
            $conn->commit();
            unset($_SESSION['estandar1_document_error'], $_SESSION['estandar1_document_old']);
            header('Location: estandar1?formato=validado#historial-documental');
            exit;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if (is_file($destination)) {
                @unlink($destination);
            }
            error_log('Control documental Estándar 1: ' . $e->getMessage());
            $_SESSION['estandar1_document_error'] = ['No fue posible registrar el control documental.'];
            header('Location: estandar1?formato=rechazado#control-documental');
            exit;
        }
    }
    
    // ==========================================================
    // 1. SST ENVÍA A FIRMA (Guarda y Notifica)
    // ==========================================================
    if ($accion === 'enviar_firma' && $usuario_rol === 'sst') {
        $firma_sst = str_replace(' ', '+', trim($_POST['firma_sst'] ?? ''));
        if (!empty($firma_sst)) {
            $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado, firma_sst) VALUES (?, 'pendiente_firma', ?) 
                    ON DUPLICATE KEY UPDATE estado = 'pendiente_firma', firma_sst = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_sst, $firma_sst]);

            // Notificar únicamente a los representantes de la misma empresa.
            $stmtReps = $conn->prepare("SELECT id, email, nombre, apellido FROM usuarios WHERE rol='representante' AND empresa_id=?");
            $stmtReps->execute([$empresa_id]);
            $reps = $stmtReps->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($reps as $rep) {
                $nombre_rep = trim($rep['nombre'] . ' ' . $rep['apellido']);
                $msg_notif = "El Responsable SG-SST ($currentUserName) ha enviado un acta para tu aprobación.";
                $msg_correo = "El Responsable <b>$currentUserName</b> ha generado el Acta de Designación y está esperando tu firma.<br><br>Ingresa a la plataforma para aprobarla.";
                notificarUsuario($conn, $rep['id'], $rep['email'], $nombre_rep, "Firma Requerida: Acta SG-SST", $msg_notif, $msg_correo, "estandar1.php");
            }

            header("Location: estandar1.php?doc=enviado");
            exit;
        }
    } 
    
    // ==========================================================
    // 2. REPRESENTANTE FIRMA EL DOCUMENTO (Guarda y Notifica)
    // ==========================================================
    elseif ($accion === 'firmar_doc' && $usuario_rol === 'representante') {
        $firma_base64 = str_replace(' ', '+', trim($_POST['firma_rep'] ?? ''));
        $doc_id = $_POST['doc_id'] ?? 0;
        
        if (!empty($firma_base64) && $doc_id > 0) {
            $stmtAllowed = $conn->prepare("SELECT d.id FROM doc_asignacion_sst d JOIN usuarios u ON u.id=d.sst_id WHERE d.id=? AND u.empresa_id=? AND d.estado='pendiente_firma' LIMIT 1");
            $stmtAllowed->execute([$doc_id, $empresa_id]);
            if (!$stmtAllowed->fetchColumn()) {
                header('Location: estandar1?doc=no_autorizado');
                exit;
            }
            $sql = "UPDATE doc_asignacion_sst SET estado = 'firmado', representante_id = ?, firma_representante = ?, fecha_firma = NOW() WHERE id = ? AND estado='pendiente_firma'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id, $firma_base64, $doc_id]);

            // NOTIFICAR AL RESPONSABLE SST DUEÑO DEL DOCUMENTO
            $stmt_sst = $conn->prepare("SELECT u.id, u.email, u.nombre, u.apellido FROM doc_asignacion_sst d JOIN usuarios u ON d.sst_id = u.id WHERE d.id = ?");
            $stmt_sst->execute([$doc_id]);
            $sst = $stmt_sst->fetch(PDO::FETCH_ASSOC);
            
            if ($sst) {
                $nombre_sst = trim($sst['nombre'] . ' ' . $sst['apellido']);
                $msg_notif = "El Representante Legal ($currentUserName) ha firmado y legalizado tu acta.";
                $msg_correo = "El Representante Legal <b>$currentUserName</b> ha firmado el Acta de Designación.<br><br>El documento ya está legalizado, puedes ingresar a la plataforma y descargar el PDF oficial.";
                notificarUsuario($conn, $sst['id'], $sst['email'], $nombre_sst, "Acta Aprobada y Firmada", $msg_notif, $msg_correo, "estandar1.php");
            }

            header("Location: estandar1.php?doc=firmado");
            exit;
        }
    }
    
    // ==========================================================
    // 3. GENERAR EL PDF USANDO mPDF (LA SOLUCIÓN A LAS IMÁGENES ROTAS)
    // ==========================================================
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
                                    WHERE d.id = ? AND u_sst.empresa_id = ? AND d.estado='firmado'");
            $stmt_doc->execute([$doc_id, $empresa_id]);
            $doc = $stmt_doc->fetch(PDO::FETCH_ASSOC);

            if ($doc) {
                // Variables de texto dinámicas
                $stmt_empresa = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE id = ?");
                $stmt_empresa->execute([$doc['sst_id']]);
                $empresa_data = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
                
                $empresa = !empty($empresa_data['nombre_empresa']) ? $empresa_data['nombre_empresa'] : "La Empresa";
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

                try {
                    // 1. Configurar mPDF
                    $mpdf_config = [
                        'format' => 'Letter-L',
                        'margin_left' => 25,
                        'margin_right' => 25,
                        'margin_top' => 30,
                        'margin_bottom' => 30,
                    ];

                    // Evitar el error de permisos en Mac/Linux (is not writable)
                    $sysTmp = sys_get_temp_dir();
                    if (is_writable($sysTmp)) {
                        $mpdf_config['tempDir'] = $sysTmp;
                    }

                    $mpdf = new \Mpdf\Mpdf($mpdf_config);

                    // 2. MAGIA DE IMÁGENES: Inyectar Base64 directamente a la memoria de mPDF
                    
                    // Firma Representante
                    if (!empty($doc['firma_representante'])) {
                        $b64Rep = str_replace(' ', '+', $doc['firma_representante']);
                        // Quitamos la cabecera data:image/png;base64,
                        $b64Rep = preg_replace('#^data:image/[^;]+;base64,#', '', $b64Rep);
                        // Guardamos la imagen en la variable interna de mPDF
                        $mpdf->imageVars['firma_rep'] = base64_decode($b64Rep);
                        $imgRepHtml = '<img src="var:firma_rep" style="max-height: 90px; width: auto;">';
                    } else {
                        $imgRepHtml = '';
                    }

                    // Firma SST
                    if (!empty($doc['firma_sst'])) {
                        $b64Sst = str_replace(' ', '+', $doc['firma_sst']);
                        $b64Sst = preg_replace('#^data:image/[^;]+;base64,#', '', $b64Sst);
                        $mpdf->imageVars['firma_sst'] = base64_decode($b64Sst);
                        $imgSstHtml = '<img src="var:firma_sst" style="max-height: 90px; width: auto;">';
                    } else {
                        $imgSstHtml = '';
                    }

                    // 3. HTML Sólido (Usando var:firma_rep en vez de Data URI)
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
                                        ' . $imgRepHtml . '
                                    </div>
                                    <div style="border-top: 1px solid #000; width: 70%; margin: 5px auto;"></div>
                                    <b style="font-size: 15px;">Representante Legal</b><br>
                                    <span style="font-size: 13px; color: #333;">' . htmlspecialchars($nombreRep) . '</span><br>
                                    <span style="font-size: 13px; color: #333;">C.C. ' . htmlspecialchars($cedulaRep) . '</span>
                                </td>
                                <td width="50%" align="center" valign="bottom" style="padding: 10px;">
                                    <div style="height: 100px;">
                                        ' . $imgSstHtml . '
                                    </div>
                                    <div style="border-top: 1px solid #000; width: 70%; margin: 5px auto;"></div>
                                    <b style="font-size: 15px;">Responsable SG-SST</b><br>
                                    <span style="font-size: 13px; color: #333;">' . htmlspecialchars($nombreSST) . '</span><br>
                                    <span style="font-size: 13px; color: #333;">C.C. ' . htmlspecialchars($cedulaSST) . '</span>
                                </td>
                            </tr>
                        </table>
                    </div>';

                    // 4. Armar el PDF
                    $mpdf->WriteHTML($html);
                    
                    // Extraer PDF como String y convertir a Base64
                    $pdf_content = $mpdf->Output('', 'S');
                    $pdf_base64 = 'data:application/pdf;base64,' . base64_encode($pdf_content);

                    // Guardar en BD para que quede en el historial
                    $stmt_upd = $conn->prepare("UPDATE doc_asignacion_sst SET archivo_pdf = ? WHERE id = ?");
                    $stmt_upd->execute([$pdf_base64, $doc_id]);

                    // El PDF final también queda organizado en Archivos > Estándar 1.
                    try {
                        document_control_archive_legalized_pdf($conn, $empresa_id, (int)$usuario_id, (int)$doc_id, $pdf_content);
                    } catch (Throwable $archiveError) {
                        error_log('Archivo documental Estándar 1: ' . $archiveError->getMessage());
                    }

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
// ==========================================================
// 4. CREAR NUEVA VERSIÓN
// ==========================================================
elseif ($accion === 'nueva_version' && $usuario_rol === 'sst') {
    $sql = "INSERT INTO doc_asignacion_sst (sst_id, estado) VALUES (?, 'borrador')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario_id]);
    header("Location: estandar1.php?doc=nueva_version");
    exit;
}

header("Location: estandar1.php");
exit;
