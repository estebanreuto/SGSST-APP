# Control de cambios - Autoajuste ficha mantenimiento

Fecha: 2026-07-13

## Cambio realizado
- Se compacto mas la ficha tecnica de registro de equipos, maquinas y herramientas.
- La grilla ahora usa columnas autoajustables segun el ancho disponible.
- Los campos bajaron a 36px de alto minimo para que entre mas informacion visible.
- Se redujo padding del panel, textos de ayuda y placeholders largos.
- Direccion y tipo de energia ya no fuerzan todo el ancho en escritorio; se acomodan segun espacio disponible.

## Archivos modificados
- `estandar7.php`

## Base de datos
- No se realizaron cambios de base de datos.
- No se requiere migracion SQL para este ajuste.

## Validacion
- `php -l estandar7.php`: sin errores.
- Navegador integrado en `estandar7.php?modulo=mantenimiento&categoria=registro-equipos`:
  - Se confirmaron 5 columnas en el ancho actual.
  - Se confirmo que la grilla no desborda el panel.
  - Se confirmo altura minima de campos en 36px.
  - Se mantiene el desplegable multiseleccion de energia.
