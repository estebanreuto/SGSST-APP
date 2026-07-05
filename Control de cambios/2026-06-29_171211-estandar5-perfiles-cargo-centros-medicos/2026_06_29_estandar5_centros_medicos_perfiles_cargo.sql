CREATE TABLE IF NOT EXISTS estandar5_centros_medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    nit VARCHAR(40) NOT NULL,
    direccion_principal VARCHAR(220) NOT NULL,
    sedes_json LONGTEXT DEFAULT NULL,
    telefono VARCHAR(60) NOT NULL,
    correo VARCHAR(160) NOT NULL,
    licencia_funcionamiento_archivo VARCHAR(500) DEFAULT NULL,
    licencia_sst_archivo VARCHAR(500) DEFAULT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_estandar5_centro_empresa (empresa_id),
    KEY idx_estandar5_centro_estado (estado),
    CONSTRAINT fk_estandar5_centro_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estandar5_perfiles_cargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    centro_medico_id INT DEFAULT NULL,
    nombre_cargo VARCHAR(180) NOT NULL,
    tipo_proceso VARCHAR(140) NOT NULL,
    jefe_inmediato VARCHAR(180) NOT NULL,
    tareas_json LONGTEXT NOT NULL,
    herramientas_json LONGTEXT DEFAULT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_estandar5_perfil_empresa (empresa_id),
    KEY idx_estandar5_perfil_centro (centro_medico_id),
    KEY idx_estandar5_perfil_estado (estado),
    CONSTRAINT fk_estandar5_perfil_centro FOREIGN KEY (centro_medico_id)
        REFERENCES estandar5_centros_medicos(id) ON DELETE SET NULL,
    CONSTRAINT fk_estandar5_perfil_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
