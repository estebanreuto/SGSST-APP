ALTER TABLE estandar5_perfiles_cargo
    ADD COLUMN IF NOT EXISTS tipo_operacion ENUM('Administrativo','Operativo','Mixto') NOT NULL DEFAULT 'Mixto'
    AFTER tipo_proceso;

CREATE TABLE IF NOT EXISTS estandar5_procesos_perfil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nombre VARCHAR(140) NOT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estandar5_proceso_empresa_nombre (empresa_id, nombre),
    KEY idx_estandar5_proceso_empresa (empresa_id),
    CONSTRAINT fk_estandar5_proceso_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
