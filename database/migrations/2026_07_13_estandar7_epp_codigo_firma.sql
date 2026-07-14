SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE estandar7_epp_entregas ADD COLUMN firma_codigo_hash VARCHAR(255) NULL AFTER firma_trabajador',
        'SELECT "estandar7_epp_entregas.firma_codigo_hash ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'estandar7_epp_entregas'
      AND COLUMN_NAME = 'firma_codigo_hash'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE estandar7_epp_entregas ADD COLUMN firma_codigo_expira DATETIME NULL AFTER firma_codigo_hash',
        'SELECT "estandar7_epp_entregas.firma_codigo_expira ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'estandar7_epp_entregas'
      AND COLUMN_NAME = 'firma_codigo_expira'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE estandar7_epp_entregas ADD COLUMN firma_codigo_validado_at DATETIME NULL AFTER firma_codigo_expira',
        'SELECT "estandar7_epp_entregas.firma_codigo_validado_at ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'estandar7_epp_entregas'
      AND COLUMN_NAME = 'firma_codigo_validado_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
