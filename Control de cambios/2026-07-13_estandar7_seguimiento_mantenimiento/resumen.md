# Control de cambios - Seguimiento mantenimiento

Fecha: 2026-07-13

## Cambio realizado
- En `Registro de equipos, maquinas y herramientas` se agrego el campo `Registro fotografico`.
- La foto se guarda en `uploads/estandar7/empresa-{id}/mantenimiento-equipos`.
- En `Seguimiento mantenimiento de equipos, maquinas y herramientas` se reemplazo la vista base por:
  - Lista de equipos registrados.
  - Boton `Registro de mantenimiento` por equipo.
  - Formulario de mantenimiento al seleccionar un equipo.
  - Tabla de historial de mantenimientos.
- El formulario de mantenimiento incluye:
  - Fecha.
  - Localizacion de averia con convenciones A-F.
  - Orden No.
  - Mecanismo.
  - Tipo de MTTO 1, 2 o 3.
  - Descripcion del trabajo.
  - Horas maquina parada.
  - Costo mano de obra.
  - Costo repuestos.
  - Costo total.
  - Quien realizo el mantenimiento.
  - Quien lo recibio.
  - Soporte o factura de mantenimiento en imagen o PDF.

## Archivos modificados
- `estandar7.php`
- `procesar_estandar7.php`
- `config/estandar7_schema.php`

## Archivo SQL
- `database/migrations/2026_07_13_estandar7_mantenimiento_seguimiento.sql`

## Base de datos
- Se agrego la columna `foto_equipo` a `estandar7_mantenimiento_equipos`.
- Se creo la tabla `estandar7_mantenimiento_registros`.
- La migracion se ejecuto localmente sin error.
- Se confirmo localmente:
  - `foto_col_ok`
  - `registros_table_ok`

## Validacion
- `php -l estandar7.php`: sin errores.
- `php -l procesar_estandar7.php`: sin errores.
- `php -l config/estandar7_schema.php`: sin errores.
- Navegador integrado:
  - `registro-equipos`: se confirmo campo `foto_equipo` y formulario `multipart/form-data`.
  - `seguimiento-mantenimiento`: se confirmo lista de equipos e historial de mantenimientos.

## Nota
- No se crearon registros de prueba para no ensuciar la base de datos.
