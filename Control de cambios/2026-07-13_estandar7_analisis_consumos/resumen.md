# Control de cambios - Analisis de consumos recursos SG-SST

Fecha: 2026-07-13

## Objetivo
Agregar la seccion de analisis de consumos al subestandar 1.1.3 Asignacion de recursos.

## Archivos modificados
- `estandar7.php`
- `config/estandar7_schema.php`
- `procesar_estandar7.php`

## Archivo SQL creado
- `database/migrations/2026_07_13_estandar7_analisis_consumos.sql`

## Cambios realizados
- Se agrego la tabla visual `Analisis de consumos`.
- La tabla muestra:
  - Periodo trimestral.
  - Presupuestado.
  - Ejecutado.
  - % Ejecucion.
  - Seguimiento.
  - Accion.
- Los valores de Presupuestado, Ejecutado y % Ejecucion se calculan automaticamente desde la matriz mensual de recursos.
- Responsable SST puede editar Seguimiento y Accion.
- Representante Legal puede ver la informacion de forma informativa, sin editar.
- Se agrego total anual.

## Base de datos
Se creo y aplico la migracion:
- `database/migrations/2026_07_13_estandar7_analisis_consumos.sql`

Tabla creada:
- `estandar7_recursos_analisis_consumo`

Esta tabla guarda solo textos de seguimiento y accion por trimestre; los valores monetarios no se duplican porque se calculan desde `estandar7_recursos_presupuesto`.

## Validacion
- `php -l estandar7.php`
- `php -l config/estandar7_schema.php`
- `php -l procesar_estandar7.php`
- Migracion SQL aplicada localmente.
- Validacion en navegador integrado:
  - 4 trimestres.
  - Total anual.
  - 8 campos editables para Responsable SST.
  - Valores monetarios derivados de la matriz.
