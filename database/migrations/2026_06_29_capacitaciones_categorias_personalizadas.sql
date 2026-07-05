CREATE TABLE IF NOT EXISTS capacitaciones_categorias_personalizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    tipo_capacitacion VARCHAR(100) NOT NULL,
    categoria VARCHAR(180) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cap_cat_empresa_tipo_categoria (empresa_id, tipo_capacitacion, categoria),
    KEY idx_cap_cat_empresa_tipo (empresa_id, tipo_capacitacion),
    CONSTRAINT fk_cap_cat_empresa FOREIGN KEY (empresa_id) REFERENCES solicitudes_empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_cap_cat_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
