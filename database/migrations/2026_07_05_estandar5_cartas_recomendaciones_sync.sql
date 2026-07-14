ALTER TABLE `estandar5_restricciones_recomendaciones`
    ADD COLUMN IF NOT EXISTS `carta_fecha` DATE DEFAULT NULL AFTER `fecha_ingreso`,
    ADD COLUMN IF NOT EXISTS `fecha_examen` DATE DEFAULT NULL AFTER `carta_fecha`,
    ADD COLUMN IF NOT EXISTS `ips_nombre` VARCHAR(180) DEFAULT NULL AFTER `fecha_examen`,
    ADD COLUMN IF NOT EXISTS `concepto_medico` VARCHAR(180) DEFAULT NULL AFTER `ips_nombre`,
    ADD COLUMN IF NOT EXISTS `proyecto` VARCHAR(180) DEFAULT NULL AFTER `concepto_medico`,
    ADD COLUMN IF NOT EXISTS `carta_pdf` VARCHAR(500) DEFAULT NULL AFTER `proyecto`;

ALTER TABLE `estandar5_restricciones_recomendaciones`
    ADD INDEX IF NOT EXISTS `idx_e5_restr_carta_fecha` (`carta_fecha`);
