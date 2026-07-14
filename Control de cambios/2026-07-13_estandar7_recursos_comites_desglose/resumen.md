# Control de cambios - Comites y desglose de recursos

Fecha: 2026-07-13

## Objetivo
Agregar la categoria Comites al presupuesto de recursos y permitir consultar el desglose interno de cada categoria desde una ventana emergente.

## Archivos modificados
- `estandar7.php`
- `config/estandar7_schema.php`

## Cambios realizados
- Se agrego la categoria `Comites`.
- Se agregaron los rubros internos:
  - COPASST.
  - COCOLA.
  - Brigadas.
- Las fichas de categoria ahora son clicables.
- Cada categoria abre un modal con el desglose por rubro.
- El modal muestra:
  - Presupuestado.
  - Ejecutado.
  - Porcentaje de avance.
  - Barra visual de avance.
- La matriz editable incorpora automaticamente los nuevos rubros de Comites.

## Base de datos
No se realizo migracion nueva. Se reutiliza la tabla existente `estandar7_recursos_presupuesto`, que ya guarda categoria y rubro dinamicamente.

## Validacion
- `php -l estandar7.php`
- `php -l config/estandar7_schema.php`
- Validacion visual/DOM en navegador integrado:
  - 5 fichas de categoria.
  - 5 ventanas de desglose.
  - Categoria Comites con COPASST, COCOLA y Brigadas.
  - Matriz ampliada a 288 campos editables.
