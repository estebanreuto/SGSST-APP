# Control de cambios - Vista representante recursos SG-SST

Fecha: 2026-07-13

## Objetivo
Mostrar al representante legal la informacion ejecutiva del subestandar 1.1.3 Asignacion de recursos, sin habilitar gestion ni edicion.

## Archivo modificado
- `estandar7.php`

## Cambios realizados
- Las tarjetas `Presupuesto anual asignado`, `Presupuesto consumido` y `Porcentaje ejecutado` ahora quedan fuera de la matriz editable.
- Se agregaron fichas informativas por categoria:
  - Seguridad industrial.
  - Medicina preventiva y del trabajo.
  - Capacitacion.
  - Higiene industrial.
- Cada ficha muestra presupuesto asignado, ejecutado y avance mediante barra visual.
- El rol Responsable SST conserva la matriz editable.
- El rol Representante Legal ve solo resumen ejecutivo y fichas informativas.

## Base de datos
No se realizaron cambios de base de datos.

## Validacion
- `php -l estandar7.php`
- Validacion visual en navegador integrado:
  - 3 tarjetas generales.
  - 4 fichas por categoria.
  - Matriz editable disponible para Responsable SST.
