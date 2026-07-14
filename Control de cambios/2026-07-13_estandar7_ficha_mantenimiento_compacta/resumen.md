# Control de cambios - Ficha mantenimiento compacta

Fecha: 2026-07-13

## Cambio realizado
- Se ajusto la ficha tecnica de registro de equipos, maquinas y herramientas para verse en dos columnas en pantallas de escritorio y portatil.
- Se redujo el alto visual de inputs y selects para que los recuadros no se vean tan grandes.
- `Fabricante` paso de campo abierto a lista plegable con fabricantes frecuentes y opcion `Otro`.
- Si se selecciona `Otro`, se despliega un campo para escribir el fabricante.
- `Tipo de energia` paso de selector multiple nativo a lista plegable personalizada con checks, permitiendo seleccionar varias energias combinadas.

## Archivos modificados
- `estandar7.php`
- `procesar_estandar7.php`

## Base de datos
- No se realizaron cambios de base de datos.
- No se requiere migracion SQL para este ajuste.

## Validacion
- `php -l estandar7.php`: sin errores.
- `php -l procesar_estandar7.php`: sin errores.
- Navegador integrado en `estandar7.php?modulo=mantenimiento&categoria=registro-equipos`:
  - Se confirmaron 2 columnas de formulario.
  - Se confirmo `Fabricante` como lista plegable.
  - Se confirmo `Tipo de energia` como desplegable multiseleccion con 10 opciones.
  - Se confirmo que ya no queda selector multiple nativo visible.
