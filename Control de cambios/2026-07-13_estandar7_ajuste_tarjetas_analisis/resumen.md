# Control de cambios - Ajuste tarjetas y posicion analisis

Fecha: 2026-07-13

## Objetivo
Ajustar visualmente las tarjetas resumen de recursos y mover la seccion Analisis de consumos despues de la matriz anual.

## Archivo modificado
- `estandar7.php`

## Cambios realizados
- Las tarjetas de resumen se compactaron.
- En vista de escritorio/tablet quedan las tres tarjetas en una sola fila:
  - Presupuesto anual asignado.
  - Presupuesto consumido.
  - Porcentaje ejecutado.
- En vista movil se mantienen apiladas para conservar legibilidad.
- La seccion `Analisis de consumos` se reubica visualmente despues de la matriz anual de recursos cuando el rol Responsable SST tiene matriz editable.

## Base de datos
No se realizaron cambios de base de datos.

## Validacion
- `php -l estandar7.php`
- Validacion en navegador integrado:
  - 3 tarjetas en una fila.
  - Tarjetas compactas.
  - Analisis de consumos despues de la matriz anual.
