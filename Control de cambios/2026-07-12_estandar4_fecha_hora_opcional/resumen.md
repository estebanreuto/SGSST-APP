# Cambio: fecha y hora opcional en actividades del Plan de Trabajo

Fecha: 2026-07-12

## Archivos modificados

- `estandar4.php`
- `procesar_estandar4.php`

## Ajustes realizados

- Se agrego una seccion opcional de dia y hora dentro del modal de nueva/editar actividad.
- La fila de fecha/hora aparece solo para los meses seleccionados.
- Los datos se guardan dentro de `programacion_json` como `fecha_programada` y `hora_programada` por cada mes.
- No se hicieron cambios estructurales a la base de datos.
- Las tarjetas muestran la fecha/hora programada cuando existe.
- Las notificaciones de tarea asignada incluyen la fecha/hora cuando fue registrada.
- La vista de tareas asignadas para representante/trabajador tambien muestra fecha/hora si existe.

## Validacion

- `php -l estandar4.php`
- `php -l procesar_estandar4.php`
- Prueba visual en navegador integrado: al seleccionar un mes aparece su fila de fecha y hora opcional.
