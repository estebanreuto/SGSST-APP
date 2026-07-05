-- PreventWork SG-SST - Resumen ejecutivo de planillas PILA
-- Fecha: 2026-06-22
-- Uso: copiar y pegar en phpMyAdmin sobre la base de datos de la app.

DELIMITER $$

DROP PROCEDURE IF EXISTS sgsst_add_column_if_missing$$
CREATE PROCEDURE sgsst_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL sgsst_add_column_if_missing('estandar2_planillas', 'valor_total', 'DECIMAL(14,2) NULL AFTER `archivo_url`');
CALL sgsst_add_column_if_missing('estandar2_planillas', 'cedulas_detectadas', 'INT UNSIGNED NULL AFTER `valor_total`');
CALL sgsst_add_column_if_missing('estandar2_planillas', 'trabajadores_esperados', 'INT UNSIGNED NULL AFTER `cedulas_detectadas`');
CALL sgsst_add_column_if_missing('estandar2_planillas', 'riesgos_detectados', 'VARCHAR(120) NULL AFTER `trabajadores_esperados`');
CALL sgsst_add_column_if_missing('estandar2_planillas', 'nit_coincide', 'ENUM(''SI'',''NO'') NULL AFTER `riesgos_detectados`');
CALL sgsst_add_column_if_missing('estandar2_planillas', 'novedades_resumen', 'TEXT NULL AFTER `nit_coincide`');

DROP PROCEDURE IF EXISTS sgsst_add_column_if_missing;
