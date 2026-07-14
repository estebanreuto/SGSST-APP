# Control de cambios - Estandar 7 asignacion de recursos

Fecha: 2026-07-13

## Objetivo
Construir la estructura interna del subestandar 1.1.3 Asignacion de recursos para el SG-SST.

## Archivos creados
- `config/estandar7_schema.php`
- `procesar_estandar7.php`
- `database/migrations/2026_07_13_estandar7_recursos_presupuesto.sql`

## Archivos modificados
- `estandar7.php`

## Cambios realizados
- Se creo una matriz anual de recursos para el rol Responsable SST.
- La matriz agrupa los rubros por:
  - Seguridad industrial.
  - Medicina preventiva y del trabajo.
  - Capacitacion.
  - Higiene industrial.
- Cada rubro permite registrar por mes:
  - Presupuestado.
  - Ejecutado.
- Se agregaron subtotales por rubro y porcentaje ejecutado.
- Se agregaron indicadores superiores de presupuesto anual asignado, presupuesto consumido y porcentaje ejecutado.
- Se agrego selector de ano.
- La vista de este subestandar queda restringida funcionalmente al rol Responsable SST.

## Base de datos
Se creo y aplico la migracion:
- `database/migrations/2026_07_13_estandar7_recursos_presupuesto.sql`

Tabla creada:
- `estandar7_recursos_presupuesto`

## Validacion
- `php -l estandar7.php`
- `php -l procesar_estandar7.php`
- `php -l config/estandar7_schema.php`
- Migracion SQL aplicada localmente.
- Validacion visual en navegador integrado:
  - 4 grupos de presupuesto.
  - 12 meses.
  - 216 campos editables.
