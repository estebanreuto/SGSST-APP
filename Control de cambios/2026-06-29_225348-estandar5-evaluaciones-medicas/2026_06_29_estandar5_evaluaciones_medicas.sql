CREATE TABLE IF NOT EXISTS estandar5_evaluaciones_medicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    perfil_cargo_id INT NOT NULL,
    centro_medico_id INT NOT NULL,
    tipo_examen ENUM('Ingreso','Periodico','Egreso','Post incapacidad','Reubicacion') NOT NULL DEFAULT 'Periodico',
    estado ENUM('solicitada','programada','realizada','cancelada') NOT NULL DEFAULT 'solicitada',
    correo_destino VARCHAR(160) NOT NULL,
    observaciones TEXT DEFAULT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_estandar5_eval_empresa (empresa_id),
    KEY idx_estandar5_eval_trabajador (trabajador_id),
    KEY idx_estandar5_eval_perfil (perfil_cargo_id),
    KEY idx_estandar5_eval_centro (centro_medico_id),
    KEY idx_estandar5_eval_estado (estado),
    CONSTRAINT fk_estandar5_eval_trabajador FOREIGN KEY (trabajador_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_estandar5_eval_perfil FOREIGN KEY (perfil_cargo_id)
        REFERENCES estandar5_perfiles_cargo(id) ON DELETE CASCADE,
    CONSTRAINT fk_estandar5_eval_centro FOREIGN KEY (centro_medico_id)
        REFERENCES estandar5_centros_medicos(id) ON DELETE CASCADE,
    CONSTRAINT fk_estandar5_eval_creador FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
