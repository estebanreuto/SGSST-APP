CREATE TABLE IF NOT EXISTS estandar4_planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    meta_cumplimiento TINYINT UNSIGNED NOT NULL DEFAULT 85,
    estado ENUM('borrador','pendiente_firma','firmado') NOT NULL DEFAULT 'borrador',
    sst_id INT DEFAULT NULL,
    representante_id INT DEFAULT NULL,
    firma_sst LONGTEXT DEFAULT NULL,
    firma_representante LONGTEXT DEFAULT NULL,
    fecha_envio DATETIME DEFAULT NULL,
    fecha_firma DATETIME DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estandar4_empresa_anio (empresa_id, anio),
    CONSTRAINT fk_estandar4_sst FOREIGN KEY (sst_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_estandar4_representante FOREIGN KEY (representante_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estandar4_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    actividad_capacitacion_id INT DEFAULT NULL,
    tema VARCHAR(180) NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    responsable VARCHAR(180) NOT NULL,
    programacion_json LONGTEXT NOT NULL,
    observaciones TEXT DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estandar4_actividad_capacitacion (plan_id, actividad_capacitacion_id),
    CONSTRAINT fk_estandar4_actividad_plan FOREIGN KEY (plan_id) REFERENCES estandar4_planes(id) ON DELETE CASCADE,
    CONSTRAINT fk_estandar4_actividad_capacitacion FOREIGN KEY (actividad_capacitacion_id)
        REFERENCES actividades_capacitacion(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estandar4_seguimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    periodo VARCHAR(120) NOT NULL,
    analisis_resultado TEXT NOT NULL,
    accion_propuesta TEXT NOT NULL,
    responsable VARCHAR(180) NOT NULL,
    fecha_max_ejecucion DATE DEFAULT NULL,
    fecha_seguimiento DATE DEFAULT NULL,
    responsable_seguimiento VARCHAR(180) DEFAULT NULL,
    resultado_seguimiento TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_estandar4_seguimiento_plan FOREIGN KEY (plan_id) REFERENCES estandar4_planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
