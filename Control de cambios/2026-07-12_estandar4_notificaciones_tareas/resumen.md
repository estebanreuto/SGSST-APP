# Cambio: notificaciones y tareas asignadas del Plan de Trabajo

Fecha: 2026-07-12

## Archivos modificados

- `procesar_estandar4.php`
- `estandar4.php`
- `components/sidebar.php`
- `database/migrations/2026_07_12_notificaciones_referencias_plan_trabajo.sql`

## Ajustes realizados

- Se agrego una migracion para que `notificaciones` pueda referenciar registros concretos mediante `referencia_tipo` y `referencia_id`.
- Al guardar una actividad del Plan Anual de Trabajo, el sistema identifica el responsable asignado:
  - `Representante Legal` notifica a usuarios con rol representante de la empresa.
  - `Responsable SST` notifica a usuarios con rol SST de la empresa.
  - Un nombre de grupo notifica a trabajadores activos asociados a ese grupo.
- Si una actividad se edita, la notificacion se actualiza y se reabre como no leida.
- Si una actividad se reasigna, se eliminan las notificaciones anteriores de usuarios que ya no aplican.
- Si una actividad se elimina, tambien se eliminan sus notificaciones asociadas.
- En `estandar4.php` se habilito vista de solo lectura para trabajador.
- Representante y trabajador ahora ven una seccion de tareas asignadas:
  - Representante: tareas asignadas al rol `Representante Legal`.
  - Trabajador: tareas asignadas a su grupo de personal.
- Se agrego acceso `Plan de Trabajo` en el menu del rol trabajador.

## Validacion

- `php -l estandar4.php`
- `php -l procesar_estandar4.php`
- `php -l components/sidebar.php`
- Migracion aplicada en base local.
- Validacion visual de `estandar4.php` en navegador integrado como Responsable SST sin errores de consola.
