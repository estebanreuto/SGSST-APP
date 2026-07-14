CREATE TABLE IF NOT EXISTS estandar7_recursos_analisis_consumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    anio SMALLINT NOT NULL,
    trimestre TINYINT NOT NULL,
    seguimiento TEXT DEFAULT NULL,
    accion TEXT DEFAULT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_e7_analisis_trimestre (empresa_id, anio, trimestre),
    KEY idx_e7_analisis_empresa_anio (empresa_id, anio),
    KEY idx_e7_analisis_creador (creado_por),
    CONSTRAINT fk_e7_analisis_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
