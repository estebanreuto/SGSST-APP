SET @foto_equipo_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'estandar7_mantenimiento_equipos'
      AND COLUMN_NAME = 'foto_equipo'
);

SET @foto_equipo_sql := IF(
    @foto_equipo_exists = 0,
    'ALTER TABLE estandar7_mantenimiento_equipos ADD COLUMN foto_equipo VARCHAR(255) DEFAULT NULL AFTER telefono',
    'SELECT 1'
);

PREPARE foto_equipo_stmt FROM @foto_equipo_sql;
EXECUTE foto_equipo_stmt;
DEALLOCATE PREPARE foto_equipo_stmt;

CREATE TABLE IF NOT EXISTS estandar7_mantenimiento_registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha DATE NOT NULL,
    localizacion_averia_json LONGTEXT DEFAULT NULL,
    orden_no VARCHAR(80) DEFAULT NULL,
    mecanismo VARCHAR(180) DEFAULT NULL,
    tipo_mantenimiento TINYINT NOT NULL DEFAULT 1,
    descripcion_trabajo TEXT DEFAULT NULL,
    horas_maquina_parada DECIMAL(8,2) DEFAULT NULL,
    costo_mano_obra DECIMAL(14,2) NOT NULL DEFAULT 0,
    costo_repuestos DECIMAL(14,2) NOT NULL DEFAULT 0,
    costo_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    quien_realizo VARCHAR(180) DEFAULT NULL,
    quien_recibio VARCHAR(180) DEFAULT NULL,
    soporte_mantenimiento VARCHAR(255) DEFAULT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_e7_mant_reg_empresa (empresa_id),
    KEY idx_e7_mant_reg_equipo (equipo_id),
    KEY idx_e7_mant_reg_fecha (fecha),
    KEY idx_e7_mant_reg_creador (creado_por),
    CONSTRAINT fk_e7_mant_reg_equipo FOREIGN KEY (equipo_id)
        REFERENCES estandar7_mantenimiento_equipos(id) ON DELETE CASCADE,
    CONSTRAINT fk_e7_mant_reg_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
