<?php

function ensure_demo_prospectos_schema(PDO $conn): void
{
    $conn->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS demo_prospectos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre_completo VARCHAR(150) NOT NULL,
            empresa VARCHAR(180) NOT NULL,
            email VARCHAR(180) NOT NULL,
            telefono VARCHAR(40) NOT NULL,
            ciudad VARCHAR(100) NULL,
            cargo VARCHAR(120) NULL,
            cantidad_trabajadores INT UNSIGNED NOT NULL DEFAULT 1,
            interes VARCHAR(80) NOT NULL DEFAULT 'Plan PEM',
            estado VARCHAR(30) NOT NULL DEFAULT 'nuevo',
            notas TEXT NULL,
            origen VARCHAR(60) NOT NULL DEFAULT 'demo_pem',
            paginas_vistas INT UNSIGNED NOT NULL DEFAULT 0,
            primera_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ultima_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            acepta_contacto TINYINT(1) NOT NULL DEFAULT 1,
            acceso_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
            acceso_token_hash CHAR(64) NULL,
            acceso_token_sufijo VARCHAR(12) NULL,
            acceso_generado_en DATETIME NULL,
            acceso_expira_en DATETIME NULL,
            acceso_revocado_en DATETIME NULL,
            acceso_decidido_en DATETIME NULL,
            acceso_decidido_por INT NULL,
            notificacion_enviada_en DATETIME NULL,
            notificacion_error VARCHAR(500) NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_demo_prospectos_email (email),
            UNIQUE KEY uq_demo_prospectos_token (acceso_token_hash),
            KEY idx_demo_prospectos_estado (estado),
            KEY idx_demo_prospectos_acceso (acceso_estado, acceso_expira_en),
            KEY idx_demo_prospectos_ultima_visita (ultima_visita),
            KEY idx_demo_prospectos_creado_en (creado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $columns = [
        'acceso_estado' => "VARCHAR(20) NOT NULL DEFAULT 'pendiente' AFTER acepta_contacto",
        'acceso_token_hash' => 'CHAR(64) NULL AFTER acceso_estado',
        'acceso_token_sufijo' => 'VARCHAR(12) NULL AFTER acceso_token_hash',
        'acceso_generado_en' => 'DATETIME NULL AFTER acceso_token_sufijo',
        'acceso_expira_en' => 'DATETIME NULL AFTER acceso_generado_en',
        'acceso_revocado_en' => 'DATETIME NULL AFTER acceso_expira_en',
        'acceso_decidido_en' => 'DATETIME NULL AFTER acceso_revocado_en',
        'acceso_decidido_por' => 'INT NULL AFTER acceso_decidido_en',
        'notificacion_enviada_en' => 'DATETIME NULL AFTER acceso_decidido_por',
        'notificacion_error' => 'VARCHAR(500) NULL AFTER notificacion_enviada_en',
    ];
    foreach ($columns as $name => $definition) {
        $check = $conn->prepare('SHOW COLUMNS FROM demo_prospectos LIKE ?');
        $check->execute([$name]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec('ALTER TABLE demo_prospectos ADD COLUMN `' . $name . '` ' . $definition);
        }
    }

    $indexes = [
        'uq_demo_prospectos_token' => 'CREATE UNIQUE INDEX uq_demo_prospectos_token ON demo_prospectos (acceso_token_hash)',
        'idx_demo_prospectos_acceso' => 'CREATE INDEX idx_demo_prospectos_acceso ON demo_prospectos (acceso_estado, acceso_expira_en)',
    ];
    foreach ($indexes as $name => $sql) {
        $check = $conn->prepare('SHOW INDEX FROM demo_prospectos WHERE Key_name = ?');
        $check->execute([$name]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec($sql);
        }
    }
}

function demo_client_ip(): string
{
    $forwarded = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwarded !== '') {
        return substr(trim(explode(',', $forwarded)[0]), 0, 45);
    }
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function demo_app_url(string $path = ''): string
{
    $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/SGSST-APP/index.php'));
    $root = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (basename($root) === 'admin') {
        $root = rtrim(str_replace('\\', '/', dirname($root)), '/');
    }
    return $scheme . '://' . $host . ($root !== '' ? $root : '') . '/' . ltrim($path, '/');
}
