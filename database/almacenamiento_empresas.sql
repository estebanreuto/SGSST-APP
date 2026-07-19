ALTER TABLE planes
    ADD COLUMN IF NOT EXISTS almacenamiento_gb DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER trabajadores;

UPDATE planes SET almacenamiento_gb = 30 WHERE UPPER(nombre) LIKE '%PEM%' AND almacenamiento_gb <= 0;
UPDATE planes SET almacenamiento_gb = 100 WHERE UPPER(nombre) LIKE '%MEM%' AND almacenamiento_gb <= 0;
UPDATE planes SET almacenamiento_gb = 200 WHERE UPPER(nombre) LIKE '%GEM%' AND almacenamiento_gb <= 0;

CREATE TABLE IF NOT EXISTS almacenamiento_archivos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    estandar_numero SMALLINT UNSIGNED NOT NULL,
    estandar_nombre VARCHAR(220) NOT NULL,
    subestandar_slug VARCHAR(120) DEFAULT NULL,
    subestandar_nombre VARCHAR(220) DEFAULT NULL,
    carpeta_id BIGINT UNSIGNED DEFAULT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_guardado VARCHAR(255) NOT NULL,
    ruta_relativa VARCHAR(700) NOT NULL,
    tipo_mime VARCHAR(150) DEFAULT NULL,
    extension VARCHAR(20) DEFAULT NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    usuario_id INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_storage_empresa (empresa_id),
    KEY idx_storage_carpeta (empresa_id, estandar_numero, subestandar_slug),
    KEY idx_storage_carpeta_personalizada (empresa_id, carpeta_id),
    KEY idx_storage_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE almacenamiento_archivos
    ADD COLUMN IF NOT EXISTS carpeta_id BIGINT UNSIGNED NULL AFTER subestandar_nombre,
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creado_en;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
