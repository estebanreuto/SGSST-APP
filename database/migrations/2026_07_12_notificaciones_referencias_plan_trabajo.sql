-- Permite sincronizar notificaciones con registros concretos sin duplicarlas.
-- Ejecutar una sola vez antes de usar las notificaciones de asignacion del Plan de Trabajo.

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN referencia_tipo VARCHAR(80) NULL AFTER enlace',
        'SELECT "notificaciones.referencia_tipo ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'notificaciones'
      AND COLUMN_NAME = 'referencia_tipo'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN referencia_id INT NULL AFTER referencia_tipo',
        'SELECT "notificaciones.referencia_id ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'notificaciones'
      AND COLUMN_NAME = 'referencia_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD INDEX idx_notificaciones_referencia (referencia_tipo, referencia_id, usuario_id)',
        'SELECT "idx_notificaciones_referencia ya existe"'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'notificaciones'
      AND INDEX_NAME = 'idx_notificaciones_referencia'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
