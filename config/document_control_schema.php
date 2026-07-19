<?php

require_once __DIR__ . '/storage_schema.php';

function ensure_document_control_schema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    ensure_storage_schema($conn);
    $conn->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS control_documental_config (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT NOT NULL,
            estandar_numero SMALLINT UNSIGNED NOT NULL,
            codigo_prefijo VARCHAR(40) NOT NULL DEFAULT 'PW-SST',
            separador CHAR(1) NOT NULL DEFAULT '-',
            version_prefijo VARCHAR(8) NOT NULL DEFAULT 'V',
            version_inicial VARCHAR(20) NOT NULL DEFAULT 'V1.0',
            exigir_codigo_nombre TINYINT(1) NOT NULL DEFAULT 1,
            actualizado_por INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_doc_config_empresa_estandar (empresa_id, estandar_numero),
            KEY idx_doc_config_empresa (empresa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $conn->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS control_documental_registros (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT NOT NULL,
            estandar_numero SMALLINT UNSIGNED NOT NULL,
            almacenamiento_archivo_id BIGINT UNSIGNED DEFAULT NULL,
            doc_asignacion_id INT DEFAULT NULL,
            tipo_documento ENUM('formato','soporte','pdf_legalizado') NOT NULL DEFAULT 'soporte',
            nombre_documento VARCHAR(220) NOT NULL,
            codigo_documento VARCHAR(80) NOT NULL,
            version_documento VARCHAR(30) NOT NULL,
            fecha_documento DATE NOT NULL,
            estado ENUM('validado','aprobado','rechazado','obsoleto') NOT NULL DEFAULT 'validado',
            archivo_original VARCHAR(255) DEFAULT NULL,
            resultado_validacion TEXT DEFAULT NULL,
            observaciones VARCHAR(500) DEFAULT NULL,
            usuario_id INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc_control_empresa_estandar (empresa_id, estandar_numero, creado_en),
            KEY idx_doc_control_archivo (almacenamiento_archivo_id),
            KEY idx_doc_control_acta (doc_asignacion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $columns = [
        'codigo_documento' => 'VARCHAR(80) NULL AFTER usuario_id',
        'version_documento' => 'VARCHAR(30) NULL AFTER codigo_documento',
        'fecha_documento' => 'DATE NULL AFTER version_documento',
        'estado_documental' => "VARCHAR(30) NOT NULL DEFAULT 'sin_control' AFTER fecha_documento",
        'origen_modulo' => 'VARCHAR(80) NULL AFTER estado_documental',
        'control_registro_id' => 'BIGINT UNSIGNED NULL AFTER origen_modulo',
    ];
    foreach ($columns as $name => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM almacenamiento_archivos LIKE " . $conn->quote($name));
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec('ALTER TABLE almacenamiento_archivos ADD COLUMN `' . $name . '` ' . $definition);
        }
    }

    $index = $conn->query("SHOW INDEX FROM almacenamiento_archivos WHERE Key_name = 'idx_storage_control_documental'")->fetch(PDO::FETCH_ASSOC);
    if (!$index) {
        $conn->exec('ALTER TABLE almacenamiento_archivos ADD KEY idx_storage_control_documental (empresa_id, estandar_numero, codigo_documento, version_documento)');
    }

    $ready = true;
}

function document_control_config(PDO $conn, int $companyId, int $standard): array
{
    ensure_document_control_schema($conn);
    $stmt = $conn->prepare('SELECT * FROM control_documental_config WHERE empresa_id = ? AND estandar_numero = ? LIMIT 1');
    $stmt->execute([$companyId, $standard]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: [
        'empresa_id' => $companyId,
        'estandar_numero' => $standard,
        'codigo_prefijo' => 'PW-SST',
        'separador' => '-',
        'version_prefijo' => 'V',
        'version_inicial' => 'V1.0',
        'exigir_codigo_nombre' => 1,
    ];
}

function document_control_code_example(array $config, int $standard, string $suffix = 'ACT'): string
{
    $separator = in_array((string)($config['separador'] ?? '-'), ['-', '_', '.'], true) ? (string)$config['separador'] : '-';
    $prefix = strtoupper((string)preg_replace('/[^A-Z0-9_-]/i', '', (string)($config['codigo_prefijo'] ?? 'PW-SST')));
    $prefix = trim($prefix, '-_.') ?: 'PW-SST';
    return $prefix . $separator . 'E' . str_pad((string)$standard, 2, '0', STR_PAD_LEFT) . $separator . strtoupper($suffix);
}

function document_control_validate_upload(array $file, array $metadata, array $config, int $standard): array
{
    $errors = [];
    $original = trim((string)($file['name'] ?? ''));
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if ($uploadError !== UPLOAD_ERR_OK || $original === '' || !is_uploaded_file($tmp)) {
        $errors[] = 'Selecciona un archivo válido para analizar.';
    }
    if ($size <= 0 || $size > 15 * 1024 * 1024) {
        $errors[] = 'El archivo debe pesar entre 1 byte y 15 MB.';
    }
    if (!in_array($extension, ['pdf', 'docx'], true)) {
        $errors[] = 'El formato debe ser PDF o DOCX.';
    }

    if ($tmp !== '' && is_file($tmp)) {
        $signature = (string)file_get_contents($tmp, false, null, 0, 8);
        if ($extension === 'pdf' && !str_starts_with($signature, '%PDF-')) {
            $errors[] = 'El archivo no contiene una estructura PDF válida.';
        }
        if ($extension === 'docx' && !str_starts_with($signature, "PK\x03\x04")) {
            $errors[] = 'El archivo no contiene una estructura DOCX válida.';
        }
    }

    $title = trim((string)($metadata['nombre_documento'] ?? ''));
    $code = strtoupper(trim((string)($metadata['codigo_documento'] ?? '')));
    $version = strtoupper(trim((string)($metadata['version_documento'] ?? '')));
    $date = trim((string)($metadata['fecha_documento'] ?? ''));
    $prefix = strtoupper(trim((string)($config['codigo_prefijo'] ?? 'PW-SST')));
    $versionPrefix = strtoupper(trim((string)($config['version_prefijo'] ?? 'V')));

    if (mb_strlen($title, 'UTF-8') < 8) {
        $errors[] = 'El nombre documental debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/^[A-Z0-9]+(?:[-_.][A-Z0-9]+){1,8}$/', $code)) {
        $errors[] = 'El código solo puede usar letras, números y separadores (- _ .).';
    }
    if ($prefix !== '' && !str_starts_with($code, $prefix)) {
        $errors[] = 'El código debe iniciar con el prefijo configurado ' . $prefix . '.';
    }
    if (!str_contains($code, 'E' . str_pad((string)$standard, 2, '0', STR_PAD_LEFT))) {
        $errors[] = 'El código debe identificar el estándar E' . str_pad((string)$standard, 2, '0', STR_PAD_LEFT) . '.';
    }
    if (!preg_match('/^' . preg_quote($versionPrefix ?: 'V', '/') . '\d+(?:\.\d+){0,2}$/', $version)) {
        $errors[] = 'La versión debe seguir el patrón ' . ($versionPrefix ?: 'V') . '1.0.';
    }
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        $errors[] = 'Selecciona una fecha de emisión válida.';
    }

    if (!empty($config['exigir_codigo_nombre']) && $original !== '') {
        $fileKey = strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', pathinfo($original, PATHINFO_FILENAME)));
        $codeKey = strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', $code));
        $versionKey = strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', $version));
        if ($codeKey !== '' && !str_contains($fileKey, $codeKey)) {
            $errors[] = 'El nombre del archivo debe incluir el código documental.';
        }
        if ($versionKey !== '' && !str_contains($fileKey, $versionKey)) {
            $errors[] = 'El nombre del archivo debe incluir la versión.';
        }
    }

    return [
        'valid' => $errors === [],
        'errors' => $errors,
        'extension' => $extension,
        'original' => $original,
        'mime' => ($tmp !== '' && is_file($tmp)) ? ((new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream') : 'application/octet-stream',
        'size' => $size,
        'metadata' => ['nombre_documento' => $title, 'codigo_documento' => $code, 'version_documento' => $version, 'fecha_documento' => $date],
    ];
}

function document_control_archive_legalized_pdf(
    PDO $conn,
    int $companyId,
    int $userId,
    int $documentId,
    string $pdfContent
): ?int {
    ensure_document_control_schema($conn);
    if ($companyId <= 0 || $documentId <= 0 || $pdfContent === '') {
        return null;
    }

    $existing = $conn->prepare("SELECT almacenamiento_archivo_id FROM control_documental_registros WHERE empresa_id=? AND doc_asignacion_id=? AND tipo_documento='pdf_legalizado' LIMIT 1");
    $existing->execute([$companyId, $documentId]);
    $existingId = (int)($existing->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    $storage = storage_company_context($conn, $companyId);
    if (!$storage || ((int)$storage['usado_bytes'] + strlen($pdfContent)) > (int)$storage['cuota_bytes']) {
        throw new RuntimeException('No hay espacio disponible para archivar el PDF legalizado.');
    }

    $config = document_control_config($conn, $companyId, 1);
    $count = $conn->prepare("SELECT COUNT(*) FROM doc_asignacion_sst d JOIN usuarios u ON u.id=d.sst_id WHERE u.empresa_id=? AND d.estado='firmado' AND d.id<=?");
    $count->execute([$companyId, $documentId]);
    $sequence = max(1, (int)$count->fetchColumn());
    $versionPrefix = strtoupper(trim((string)($config['version_prefijo'] ?? 'V'))) ?: 'V';
    $version = $versionPrefix . $sequence . '.0';
    $code = document_control_code_example($config, 1, 'ACTA');
    $originalName = $code . '_' . $version . '_Acta-Designacion-SST.pdf';

    storage_prepare_company_folders($companyId, (int)$storage['nivel_plan']);
    $folder = storage_folder_path($companyId, 1);
    $savedName = date('Ymd-His') . '-' . bin2hex(random_bytes(7)) . '.pdf';
    $destination = $folder . DIRECTORY_SEPARATOR . $savedName;
    if (file_put_contents($destination, $pdfContent, LOCK_EX) === false) {
        throw new RuntimeException('No fue posible archivar el PDF legalizado.');
    }

    try {
        $conn->beginTransaction();
        // Las rutas de almacenamiento son relativas a C:\xampp\htdocs, igual que en almacenamiento.php.
        $htdocsRoot = dirname(dirname(__DIR__));
        $relative = str_replace('\\', '/', substr($destination, strlen($htdocsRoot) + 1));
        $stmtFile = $conn->prepare(<<<'SQL'
            INSERT INTO almacenamiento_archivos
                (empresa_id, estandar_numero, estandar_nombre, nombre_original, nombre_guardado,
                 ruta_relativa, tipo_mime, extension, tamano_bytes, usuario_id, codigo_documento,
                 version_documento, fecha_documento, estado_documental, origen_modulo)
            VALUES (?, 1, ?, ?, ?, ?, 'application/pdf', 'pdf', ?, ?, ?, ?, CURDATE(), 'aprobado', 'estandar1_pdf')
        SQL);
        $stmtFile->execute([
            $companyId, storage_standard_catalog()[1], $originalName, $savedName, $relative,
            strlen($pdfContent), $userId, $code, $version,
        ]);
        $fileId = (int)$conn->lastInsertId();

        $stmtControl = $conn->prepare(<<<'SQL'
            INSERT INTO control_documental_registros
                (empresa_id, estandar_numero, almacenamiento_archivo_id, doc_asignacion_id,
                 tipo_documento, nombre_documento, codigo_documento, version_documento,
                 fecha_documento, estado, archivo_original, resultado_validacion, usuario_id)
            VALUES (?, 1, ?, ?, 'pdf_legalizado', 'Acta de designación del Responsable SG-SST',
                    ?, ?, CURDATE(), 'aprobado', ?, ?, ?)
        SQL);
        $stmtControl->execute([
            $companyId, $fileId, $documentId, $code, $version, $originalName,
            json_encode(['origen' => 'firmas_electronicas', 'estado' => 'legalizado'], JSON_UNESCAPED_UNICODE),
            $userId,
        ]);
        $controlId = (int)$conn->lastInsertId();
        $conn->prepare('UPDATE almacenamiento_archivos SET control_registro_id=? WHERE id=?')->execute([$controlId, $fileId]);
        $conn->commit();
        return $fileId;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        if (is_file($destination)) {
            @unlink($destination);
        }
        throw $error;
    }
}

function document_control_backfill_legalized_pdfs(PDO $conn, int $companyId, int $userId): int
{
    ensure_document_control_schema($conn);
    if ($companyId <= 0) {
        return 0;
    }

    $stmt = $conn->prepare(<<<'SQL'
        SELECT d.id, d.archivo_pdf
        FROM doc_asignacion_sst d
        JOIN usuarios u ON u.id=d.sst_id
        LEFT JOIN control_documental_registros r
          ON r.empresa_id=u.empresa_id
         AND r.doc_asignacion_id=d.id
         AND r.tipo_documento='pdf_legalizado'
        WHERE u.empresa_id=?
          AND d.estado='firmado'
          AND d.archivo_pdf IS NOT NULL
          AND LENGTH(d.archivo_pdf)>100
          AND r.id IS NULL
        ORDER BY d.id
    SQL);
    $stmt->execute([$companyId]);
    $migrated = 0;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $legacy) {
        $encoded = trim((string)$legacy['archivo_pdf']);
        $encoded = (string)preg_replace('#^data:application/pdf;base64,#i', '', $encoded);
        $pdf = base64_decode($encoded, true);
        if ($pdf === false || !str_starts_with($pdf, '%PDF-')) {
            error_log('Control documental: el acta ' . (int)$legacy['id'] . ' no contiene un PDF válido para migrar.');
            continue;
        }

        try {
            if (document_control_archive_legalized_pdf($conn, $companyId, $userId, (int)$legacy['id'], $pdf)) {
                $migrated++;
            }
        } catch (Throwable $error) {
            error_log('Migración documental del acta ' . (int)$legacy['id'] . ': ' . $error->getMessage());
        }
    }

    return $migrated;
}
