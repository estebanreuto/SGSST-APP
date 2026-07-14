-- Migracion: ampliar opciones de consumo de cigarrillo en encuesta sociodemografica.
-- Uso: ejecutar una sola vez en la base de datos del aplicativo.

ALTER TABLE encuesta_sociodemografica
  MODIFY fuma ENUM(
    'No fumo',
    'Un cigarrillo al dia',
    'Media caja al dia',
    'Una caja completa al dia'
  ) DEFAULT NULL;
