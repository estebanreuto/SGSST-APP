# Control de cambios - Estandar 7 / Registro de equipos

Fecha: 2026-07-13

## Cambio realizado
- Se habilito la primera tarjeta de mantenimiento: `Registro de equipos, maquinas y herramientas`.
- Se creo una ficha tecnica para registrar:
  - Tipo de elemento.
  - Maquina, equipo o herramienta.
  - Marca.
  - Serie.
  - Modelo.
  - Tipo de energia.
  - Tipo de combustible.
  - Ubicacion.
  - Seccion.
  - Fabricante.
  - Direccion.
  - Telefono.
- El sistema calcula automaticamente el siguiente codigo interno por empresa: `001`, `002`, `003`, etc.
- Se agrego una lista de registros recientes para dejar preparada la conexion con el seguimiento de mantenimiento.
- Se dejo la segunda tarjeta como base de seguimiento, mostrando cuantos registros existen.

## Tipos de energia incluidos
- Electrica.
- Mecanica.
- Hidraulica.
- Neumatica.
- Termica.
- Quimica.
- Gravitacional.
- Combustion / combustible.
- Energia almacenada o residual.
- Otra.

## Archivos modificados
- `estandar7.php`
- `procesar_estandar7.php`
- `config/estandar7_schema.php`

## Archivos nuevos
- `database/migrations/2026_07_13_estandar7_mantenimiento_equipos.sql`

## Base de datos
- Se creo la tabla `estandar7_mantenimiento_equipos`.
- La migracion SQL quedo disponible para copiar y pegar en:
  `database/migrations/2026_07_13_estandar7_mantenimiento_equipos.sql`
- La migracion se ejecuto localmente sin error.

## Validacion
- `php -l estandar7.php`: sin errores.
- `php -l procesar_estandar7.php`: sin errores.
- `php -l config/estandar7_schema.php`: sin errores.
- Navegador integrado en `estandar7.php?modulo=mantenimiento&categoria=registro-equipos`:
  - Se confirmo la ficha tecnica.
  - Se confirmo el siguiente codigo interno `001`.
  - Se confirmaron 12 campos visibles.
  - Se confirmaron 10 opciones de energia.

## Referencias consultadas
- OSHA: Control of Hazardous Energy, fuentes electrica, mecanica, hidraulica, neumatica, quimica, termica u otras.
- CCOHS: Hazardous Energy Control Programs, energia electrica, mecanica, hidraulica, neumatica, quimica, termica, gravitacional y otras.
