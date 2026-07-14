CREATE TABLE IF NOT EXISTS estandar7_programas_documentales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    programa_slug VARCHAR(80) NOT NULL,
    programa_nombre VARCHAR(220) NOT NULL,
    contenido_json LONGTEXT NOT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_e7_programa_empresa (empresa_id, programa_slug),
    KEY idx_e7_programa_empresa (empresa_id),
    KEY idx_e7_programa_slug (programa_slug),
    KEY idx_e7_programa_creador (creado_por),
    CONSTRAINT fk_e7_programa_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
