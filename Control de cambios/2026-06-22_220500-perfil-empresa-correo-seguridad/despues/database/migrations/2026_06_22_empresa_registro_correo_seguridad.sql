-- Migracion: datos de empresa desde registro y correo de seguridad 2FA
-- Fecha: 2026-06-22
-- Uso: copiar y pegar en MySQL/phpMyAdmin sobre la base de datos de la app.

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE solicitudes_empresas ADD COLUMN empresa_nombre VARCHAR(150) NULL AFTER telefono',
        'SELECT "solicitudes_empresas.empresa_nombre ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'solicitudes_empresas'
      AND COLUMN_NAME = 'empresa_nombre'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE solicitudes_empresas ADD COLUMN empresa_nit VARCHAR(50) NULL AFTER empresa_nombre',
        'SELECT "solicitudes_empresas.empresa_nit ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'solicitudes_empresas'
      AND COLUMN_NAME = 'empresa_nit'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE solicitudes_empresas ADD COLUMN empresa_clase_riesgo VARCHAR(10) NULL AFTER empresa_nit',
        'SELECT "solicitudes_empresas.empresa_clase_riesgo ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'solicitudes_empresas'
      AND COLUMN_NAME = 'empresa_clase_riesgo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN correo_seguridad VARCHAR(150) NULL AFTER email',
        'SELECT "usuarios.correo_seguridad ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'correo_seguridad'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE usuarios
SET correo_seguridad = email
WHERE correo_seguridad IS NULL OR correo_seguridad = '';

UPDATE usuarios u
JOIN solicitudes_empresas se ON se.id = u.empresa_id
SET
    u.nombre_empresa = COALESCE(NULLIF(u.nombre_empresa, ''), NULLIF(se.empresa_nombre, ''), NULLIF(se.nombre, '')),
    u.tipo_doc_empresa = COALESCE(NULLIF(u.tipo_doc_empresa, ''), 'NIT'),
    u.num_doc_empresa = COALESCE(NULLIF(u.num_doc_empresa, ''), NULLIF(se.empresa_nit, ''), NULLIF(se.cedula, '')),
    u.clase_riesgo = COALESCE(NULLIF(u.clase_riesgo, ''), NULLIF(se.empresa_clase_riesgo, ''))
WHERE u.rol = 'representante';
