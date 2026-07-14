# Control de cambios - Estandar 7 / Mantenimiento

Fecha: 2026-07-13

## Cambio realizado
- En el subestandar 4.2.5 Mantenimiento periodico de instalaciones, equipos, maquinas y herramientas se crearon dos tarjetas visuales de acceso:
  - Registro de equipos, maquinas y herramientas.
  - Seguimiento mantenimiento de equipos, maquinas y herramientas.
- Las tarjetas quedan como base visual para definir posteriormente la estructura interna de cada categoria.

## Archivos modificados
- `estandar7.php`

## Base de datos
- No se realizaron cambios de base de datos.
- No se requiere migracion SQL para este ajuste.

## Validacion
- `php -l estandar7.php`: sin errores de sintaxis.
- Navegador integrado en `http://localhost/SGSST-APP2/estandar7.php?modulo=mantenimiento`: se confirmaron 2 tarjetas visibles.
