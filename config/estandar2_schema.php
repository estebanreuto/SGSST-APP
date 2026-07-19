<?php

require_once __DIR__ . '/document_control_schema.php';

function ensure_estandar2_schema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    ensure_document_control_schema($conn);
    $columns = [
        'empresa_id' => 'INT NULL AFTER id',
        'almacenamiento_archivo_id' => 'BIGINT UNSIGNED NULL AFTER archivo_url',
        'version_actual' => 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER almacenamiento_archivo_id',
    ];
    foreach ($columns as $name => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM estandar2_planillas LIKE " . $conn->quote($name));
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec('ALTER TABLE estandar2_planillas ADD COLUMN `' . $name . '` ' . $definition);
        }
    }

    $conn->exec("UPDATE estandar2_planillas p JOIN usuarios u ON u.id=p.subido_por SET p.empresa_id=u.empresa_id WHERE p.empresa_id IS NULL");
    $nullCompanies = (int)$conn->query('SELECT COUNT(*) FROM estandar2_planillas WHERE empresa_id IS NULL')->fetchColumn();
    $companyColumn = $conn->query("SHOW COLUMNS FROM estandar2_planillas LIKE 'empresa_id'")->fetch(PDO::FETCH_ASSOC);
    if ($nullCompanies === 0 && strtoupper((string)($companyColumn['Null'] ?? 'YES')) === 'YES') {
        $conn->exec('ALTER TABLE estandar2_planillas MODIFY empresa_id INT NOT NULL');
    }

    $oldIndex = $conn->query("SHOW INDEX FROM estandar2_planillas WHERE Key_name='idx_mes_anio'")->fetch(PDO::FETCH_ASSOC);
    if ($oldIndex) {
        $conn->exec('ALTER TABLE estandar2_planillas DROP INDEX idx_mes_anio');
    }
    $companyIndex = $conn->query("SHOW INDEX FROM estandar2_planillas WHERE Key_name='uq_e2_empresa_periodo'")->fetch(PDO::FETCH_ASSOC);
    if (!$companyIndex) {
        $conn->exec('ALTER TABLE estandar2_planillas ADD UNIQUE KEY uq_e2_empresa_periodo (empresa_id, anio, mes)');
    }

    $conn->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS estandar2_planilla_versiones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            planilla_id INT NOT NULL,
            empresa_id INT NOT NULL,
            almacenamiento_archivo_id BIGINT UNSIGNED DEFAULT NULL,
            numero_version INT UNSIGNED NOT NULL,
            archivo_original VARCHAR(255) NOT NULL,
            valor_total DECIMAL(14,2) DEFAULT NULL,
            cedulas_detectadas INT UNSIGNED DEFAULT NULL,
            trabajadores_esperados INT UNSIGNED DEFAULT NULL,
            riesgos_detectados VARCHAR(120) DEFAULT NULL,
            nit_coincide ENUM('SI','NO') DEFAULT NULL,
            novedades_resumen TEXT DEFAULT NULL,
            subido_por INT NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_e2_planilla_version (planilla_id, numero_version),
            KEY idx_e2_version_empresa (empresa_id, creado_en),
            KEY idx_e2_version_archivo (almacenamiento_archivo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $ready = true;
}

function estandar2_document_code(PDO $conn, int $companyId, int $year, int $month): string
{
    $config = document_control_config($conn, $companyId, 2);
    return document_control_code_example($config, 2, 'PILA') . '-' . $year . str_pad((string)$month, 2, '0', STR_PAD_LEFT);
}
