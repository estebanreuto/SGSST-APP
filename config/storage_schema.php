<?php

/**
 * Esquema y utilidades del almacenamiento documental por empresa.
 * Los archivos fisicos viven fuera de la navegacion directa y se entregan
 * exclusivamente mediante descargar_archivo.php.
 */
function ensure_storage_schema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $column = $conn->query("SHOW COLUMNS FROM planes LIKE 'almacenamiento_gb'")->fetch(PDO::FETCH_ASSOC);
    if (!$column) {
        $conn->exec("ALTER TABLE planes ADD COLUMN almacenamiento_gb DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER trabajadores");
    }

    $conn->exec("UPDATE planes SET almacenamiento_gb = 30 WHERE UPPER(nombre) LIKE '%PEM%' AND almacenamiento_gb <= 0");
    $conn->exec("UPDATE planes SET almacenamiento_gb = 100 WHERE UPPER(nombre) LIKE '%MEM%' AND almacenamiento_gb <= 0");
    $conn->exec("UPDATE planes SET almacenamiento_gb = 200 WHERE UPPER(nombre) LIKE '%GEM%' AND almacenamiento_gb <= 0");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS almacenamiento_archivos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT NOT NULL,
            estandar_numero SMALLINT UNSIGNED NOT NULL,
            estandar_nombre VARCHAR(220) NOT NULL,
            subestandar_slug VARCHAR(120) DEFAULT NULL,
            subestandar_nombre VARCHAR(220) DEFAULT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            nombre_guardado VARCHAR(255) NOT NULL,
            ruta_relativa VARCHAR(700) NOT NULL,
            tipo_mime VARCHAR(150) DEFAULT NULL,
            extension VARCHAR(20) DEFAULT NULL,
            tamano_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            usuario_id INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_storage_empresa (empresa_id),
            KEY idx_storage_carpeta (empresa_id, estandar_numero, subestandar_slug),
            KEY idx_storage_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $folderColumn = $conn->query("SHOW COLUMNS FROM almacenamiento_archivos LIKE 'carpeta_id'")->fetch(PDO::FETCH_ASSOC);
    if (!$folderColumn) {
        $conn->exec("ALTER TABLE almacenamiento_archivos ADD COLUMN carpeta_id BIGINT UNSIGNED NULL AFTER subestandar_nombre");
    }
    $folderIndex = $conn->query("SHOW INDEX FROM almacenamiento_archivos WHERE Key_name = 'idx_storage_carpeta_personalizada'")->fetch(PDO::FETCH_ASSOC);
    if (!$folderIndex) {
        $conn->exec("ALTER TABLE almacenamiento_archivos ADD KEY idx_storage_carpeta_personalizada (empresa_id, carpeta_id)");
    }

    $updatedColumn = $conn->query("SHOW COLUMNS FROM almacenamiento_archivos LIKE 'actualizado_en'")->fetch(PDO::FETCH_ASSOC);
    if (!$updatedColumn) {
        $conn->exec("ALTER TABLE almacenamiento_archivos ADD COLUMN actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creado_en");
    }

    $conn->exec("
        CREATE TABLE IF NOT EXISTS almacenamiento_carpetas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT NOT NULL,
            estandar_numero SMALLINT UNSIGNED NOT NULL,
            subestandar_slug VARCHAR(120) DEFAULT NULL,
            parent_id BIGINT UNSIGNED DEFAULT NULL,
            nombre VARCHAR(180) NOT NULL,
            nombre_guardado VARCHAR(220) NOT NULL,
            usuario_id INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_storage_folder_company (empresa_id, estandar_numero, subestandar_slug),
            KEY idx_storage_folder_parent (empresa_id, parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS almacenamiento_compartidos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            empresa_id INT NOT NULL,
            tipo_objeto ENUM('archivo','carpeta') NOT NULL,
            objeto_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            creado_por INT DEFAULT NULL,
            vence_en DATETIME NOT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_storage_share_token (token_hash),
            KEY idx_storage_share_object (empresa_id, tipo_objeto, objeto_id),
            KEY idx_storage_share_expiry (activo, vence_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ready = true;
}

function storage_plan_level(string $planName): int
{
    $name = strtoupper($planName);
    if (str_contains($name, 'GEM') || str_contains($name, 'ENTERPRISE')) {
        return 3;
    }
    if (str_contains($name, 'MEM') || str_contains($name, 'PRO')) {
        return 2;
    }
    return 1;
}

function storage_default_quota_gb(string $planName): float
{
    return match (storage_plan_level($planName)) {
        3 => 200.0,
        2 => 100.0,
        default => 30.0,
    };
}

function storage_company_context(PDO $conn, int $empresaId): ?array
{
    ensure_storage_schema($conn);
    $stmt = $conn->prepare("
        SELECT se.id AS empresa_id, se.empresa_nombre, se.nombre, se.apellido,
               p.id AS plan_id, p.nombre AS plan_nombre, p.almacenamiento_gb
        FROM solicitudes_empresas se
        LEFT JOIN planes p ON p.id = se.plan_id
        WHERE se.id = ?
        LIMIT 1
    ");
    $stmt->execute([$empresaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $companyName = trim((string)($row['empresa_nombre'] ?? ''));
    if ($companyName === '') {
        $stmtName = $conn->prepare("SELECT nombre_empresa FROM usuarios WHERE empresa_id = ? AND nombre_empresa IS NOT NULL AND nombre_empresa <> '' ORDER BY (rol = 'representante') DESC, id ASC LIMIT 1");
        $stmtName->execute([$empresaId]);
        $companyName = trim((string)$stmtName->fetchColumn());
    }
    if ($companyName === '') {
        $companyName = trim((string)($row['nombre'] ?? '') . ' ' . (string)($row['apellido'] ?? ''));
    }
    if ($companyName === '') {
        $companyName = 'Empresa ' . $empresaId;
    }

    $planName = (string)($row['plan_nombre'] ?? 'Plan PEM');
    $quotaGb = (float)($row['almacenamiento_gb'] ?? 0);
    if ($quotaGb <= 0) {
        $quotaGb = storage_default_quota_gb($planName);
    }

    $stmtUsed = $conn->prepare("SELECT COALESCE(SUM(tamano_bytes), 0) FROM almacenamiento_archivos WHERE empresa_id = ?");
    $stmtUsed->execute([$empresaId]);
    $usedBytes = (int)$stmtUsed->fetchColumn();
    $quotaBytes = (int)round($quotaGb * 1024 * 1024 * 1024);
    $percentage = $quotaBytes > 0 ? min(100, ($usedBytes / $quotaBytes) * 100) : 0;

    return array_merge($row, [
        'empresa_nombre' => $companyName,
        'plan_nombre' => $planName,
        'nivel_plan' => storage_plan_level($planName),
        'cuota_gb' => $quotaGb,
        'cuota_bytes' => $quotaBytes,
        'usado_bytes' => $usedBytes,
        'porcentaje' => $percentage,
    ]);
}

function storage_user_company_id(PDO $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function storage_standard_catalog(): array
{
    return [
        1 => 'Asignacion de persona que disena el Sistema de Gestion de SST',
        2 => 'Afiliacion al Sistema de Seguridad Social Integral',
        3 => 'Capacitacion en SST',
        4 => 'Plan Anual de Trabajo',
        5 => 'Evaluaciones medicas ocupacionales',
        6 => 'Identificacion de peligros, evaluacion y valoracion de riesgos',
        7 => 'Medidas de prevencion y control frente a peligros y riesgos',
        8 => 'Asignacion de recursos para el Sistema de Gestion de SST',
        9 => 'Conformacion y funcionamiento del COPASST',
        10 => 'Comite de Convivencia Laboral',
        11 => 'Politica de Seguridad y Salud en el Trabajo',
        12 => 'Archivo y retencion documental del SG-SST',
        13 => 'Descripcion sociodemografica y diagnostico de salud',
        14 => 'Medicina del trabajo, prevencion y promocion de la salud',
        15 => 'Restricciones y recomendaciones medicas laborales',
        16 => 'Reporte de accidentes y enfermedades laborales',
        17 => 'Investigacion de incidentes, accidentes y enfermedades laborales',
        18 => 'Mantenimiento de instalaciones, equipos y herramientas',
        19 => 'Elementos de proteccion personal y capacitacion',
        20 => 'Plan de prevencion, preparacion y respuesta ante emergencias',
        21 => 'Brigada de emergencias',
        22 => 'Revision por la alta direccion',
        23 => 'Asignacion de responsabilidades en SST',
        24 => 'Trabajadores en actividades de alto riesgo',
        25 => 'Capacitacion de integrantes del COPASST',
        26 => 'Induccion y reinduccion en SST',
        27 => 'Curso virtual de cincuenta horas en SST',
        28 => 'Objetivos de SST',
        29 => 'Evaluacion inicial del Sistema de Gestion',
        30 => 'Rendicion de cuentas',
        31 => 'Matriz legal',
        32 => 'Mecanismos de comunicacion',
        33 => 'Adquisicion de bienes y servicios',
        34 => 'Evaluacion de proveedores y contratistas',
        35 => 'Gestion del cambio',
        36 => 'Perfiles de cargos',
        37 => 'Custodia de historias clinicas',
        38 => 'Estilos de vida y entorno saludable',
        39 => 'Servicios de higiene',
        40 => 'Manejo de residuos',
        41 => 'Analisis estadistico de accidentes y enfermedades laborales',
        42 => 'Frecuencia de accidentalidad',
        43 => 'Severidad de accidentalidad',
        44 => 'Accidentes de trabajo mortales',
        45 => 'Prevalencia de enfermedad laboral',
        46 => 'Incidencia de enfermedad laboral',
        47 => 'Ausentismo por causa medica',
        48 => 'Metodologia de identificacion y valoracion de riesgos',
        49 => 'Sustancias carcinogenas o con toxicidad aguda',
        50 => 'Mediciones ambientales',
        51 => 'Aplicacion de medidas de prevencion y control',
        52 => 'Procedimientos e instructivos internos',
        53 => 'Inspecciones a instalaciones, maquinaria y equipos',
        54 => 'Indicadores del SG-SST',
        55 => 'Auditoria anual',
        56 => 'Planificacion de auditoria con el COPASST',
        57 => 'Acciones preventivas y correctivas',
        58 => 'Mejora por revision de la alta direccion',
        59 => 'Mejora por investigaciones de accidentes y enfermedades',
        60 => 'Plan de mejoramiento',
    ];
}

function storage_substandard_catalog(): array
{
    return [
        5 => [
            'sociodemografica' => '3.1.1 Descripcion sociodemografica y diagnostico de salud',
            'promocion-prevencion' => '3.1.2 Promocion y prevencion en salud',
            'perfiles-cargo' => '3.1.3 Informacion al medico de perfiles de cargo',
            'evaluaciones-medicas' => '3.1.4 Evaluaciones medicas ocupacionales',
            'historias-clinicas' => '3.1.5 Custodia de historias clinicas',
            'restricciones' => '3.1.6 Restricciones y recomendaciones medicas',
        ],
        7 => [
            'recursos-sg-sst' => '1.1.3 Recursos para el SG-SST',
            'mantenimiento' => '4.2.5 Mantenimiento periodico',
            'epp' => '4.2.6 Elementos de proteccion personal',
            'plan-emergencias' => '5.1.1 Plan de emergencias',
            'brigada' => '5.1.2 Brigada de emergencias',
            'mediciones-ambientales' => '4.1.4 Mediciones ambientales',
            'verificacion-medidas' => '4.2.2 Verificacion de medidas',
            'procedimientos' => '4.2.3 Procedimientos e instructivos',
            'inspecciones' => '4.2.4 Inspecciones sistematicas',
        ],
    ];
}

function storage_max_standard(int $planLevel): int
{
    return match ($planLevel) {
        3 => 60,
        2 => 21,
        default => 7,
    };
}

function storage_slug(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii));
    return trim($slug, '-') ?: 'carpeta';
}

function storage_company_root(int $empresaId): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'empresas' . DIRECTORY_SEPARATOR . 'empresa-' . $empresaId;
}

function storage_prepare_company_folders(int $empresaId, int $planLevel): void
{
    $root = storage_company_root($empresaId);
    if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) {
        throw new RuntimeException('No fue posible crear la carpeta de la empresa.');
    }

    $guard = dirname($root) . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "Options -Indexes\nRequire all denied\n");
    }

    $standards = storage_standard_catalog();
    $substandards = storage_substandard_catalog();
    for ($number = 1; $number <= storage_max_standard($planLevel); $number++) {
        $folder = $root . DIRECTORY_SEPARATOR . sprintf('estandar-%02d-%s', $number, storage_slug($standards[$number]));
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }
        foreach ($substandards[$number] ?? [] as $slug => $label) {
            $subfolder = $folder . DIRECTORY_SEPARATOR . 'subestandar-' . storage_slug($slug);
            if (!is_dir($subfolder)) {
                @mkdir($subfolder, 0775, true);
            }
        }
    }
}

function storage_folder_path(int $empresaId, int $standard, ?string $substandard = null): string
{
    $standards = storage_standard_catalog();
    if (!isset($standards[$standard])) {
        throw new InvalidArgumentException('Estandar no valido.');
    }
    $path = storage_company_root($empresaId) . DIRECTORY_SEPARATOR
        . sprintf('estandar-%02d-%s', $standard, storage_slug($standards[$standard]));
    if ($substandard !== null && $substandard !== '') {
        $path .= DIRECTORY_SEPARATOR . 'subestandar-' . storage_slug($substandard);
    }
    return $path;
}

function storage_custom_folder_path(int $empresaId, int $folderId): string
{
    if ($empresaId <= 0 || $folderId <= 0) {
        throw new InvalidArgumentException('Carpeta no valida.');
    }

    return storage_company_root($empresaId) . DIRECTORY_SEPARATOR . '_personalizadas' . DIRECTORY_SEPARATOR . 'carpeta-' . $folderId;
}

function storage_folder_descendant_ids(PDO $conn, int $empresaId, int $folderId): array
{
    $pending = [$folderId];
    $result = [];
    $stmt = $conn->prepare("SELECT id FROM almacenamiento_carpetas WHERE empresa_id = ? AND parent_id = ?");

    while ($pending) {
        $current = (int)array_shift($pending);
        if ($current <= 0 || in_array($current, $result, true)) {
            continue;
        }
        $result[] = $current;
        $stmt->execute([$empresaId, $current]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $childId) {
            $pending[] = (int)$childId;
        }
    }

    return $result;
}

function storage_path_is_within(string $path, string $root): bool
{
    $normalizedPath = str_replace('\\', '/', $path);
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/') . '/';
    return str_starts_with($normalizedPath . (is_dir($path) ? '/' : ''), $normalizedRoot);
}

function storage_format_bytes(int $bytes): string
{
    if ($bytes >= 1024 ** 3) {
        return number_format($bytes / (1024 ** 3), 2, ',', '.') . ' GB';
    }
    if ($bytes >= 1024 ** 2) {
        return number_format($bytes / (1024 ** 2), 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return $bytes . ' B';
}
