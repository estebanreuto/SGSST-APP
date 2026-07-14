CREATE TABLE IF NOT EXISTS estandar7_recursos_presupuesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    anio SMALLINT NOT NULL,
    categoria_slug VARCHAR(80) NOT NULL,
    categoria_nombre VARCHAR(180) NOT NULL,
    item_slug VARCHAR(100) NOT NULL,
    item_nombre VARCHAR(220) NOT NULL,
    periodo TINYINT NOT NULL,
    presupuestado DECIMAL(14,2) NOT NULL DEFAULT 0,
    ejecutado DECIMAL(14,2) NOT NULL DEFAULT 0,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_e7_recursos_periodo (empresa_id, anio, item_slug, periodo),
    KEY idx_e7_recursos_empresa_anio (empresa_id, anio),
    KEY idx_e7_recursos_categoria (categoria_slug),
    KEY idx_e7_recursos_item (item_slug),
    KEY idx_e7_recursos_creador (creado_por),
    CONSTRAINT fk_e7_recursos_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
