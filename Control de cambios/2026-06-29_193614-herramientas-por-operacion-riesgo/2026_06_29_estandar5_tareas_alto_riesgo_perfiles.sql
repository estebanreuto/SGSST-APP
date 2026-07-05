ALTER TABLE estandar5_perfiles_cargo
    ADD COLUMN IF NOT EXISTS tareas_alto_riesgo_json LONGTEXT DEFAULT NULL
    AFTER tareas_json;
