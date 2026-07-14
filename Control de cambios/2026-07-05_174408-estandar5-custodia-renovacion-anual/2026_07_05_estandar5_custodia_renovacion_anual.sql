ALTER TABLE `estandar5_historia_clinica_custodias`
    ADD COLUMN IF NOT EXISTS `fecha_renovacion` DATE DEFAULT NULL AFTER `fecha_emision`,
    ADD COLUMN IF NOT EXISTS `texto_extraido` LONGTEXT DEFAULT NULL AFTER `observaciones`;

ALTER TABLE `estandar5_historia_clinica_custodias`
    ADD INDEX IF NOT EXISTS `idx_e5_custodia_renovacion` (`fecha_renovacion`);

UPDATE `estandar5_historia_clinica_custodias`
SET `fecha_renovacion` = DATE_ADD(`fecha_emision`, INTERVAL 1 YEAR)
WHERE `fecha_emision` IS NOT NULL
  AND `fecha_renovacion` IS NULL;
