CREATE TABLE IF NOT EXISTS `estandar5_historia_clinica_custodias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id` INT NOT NULL,
    `centro_medico_id` INT NOT NULL,
    `archivo_pdf` VARCHAR(500) NOT NULL,
    `fecha_emision` DATE DEFAULT NULL,
    `observaciones` TEXT DEFAULT NULL,
    `creado_por` INT DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_e5_custodia_empresa` (`empresa_id`),
    KEY `idx_e5_custodia_centro` (`centro_medico_id`),
    KEY `idx_e5_custodia_fecha` (`fecha_emision`),
    CONSTRAINT `fk_e5_custodia_centro` FOREIGN KEY (`centro_medico_id`)
        REFERENCES `estandar5_centros_medicos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_e5_custodia_creador` FOREIGN KEY (`creado_por`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
