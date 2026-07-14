# Control de cambios - EPP trabajador y codigo para firma

Fecha: 2026-07-13

## Objetivo
Validar y completar el flujo del rol Trabajador para el subestandar 4.2.6 Entrega de EPP, agregando codigo de correo antes de firmar.

## Archivos modificados
- `estandar7.php`
- `procesar_estandar7.php`
- `config/estandar7_schema.php`
- `components/sidebar.php`

## Archivo SQL creado
- `database/migrations/2026_07_13_estandar7_epp_codigo_firma.sql`

## Cambios realizados
- El rol Trabajador puede acceder a `estandar7.php?modulo=epp`.
- Si el trabajador abre `estandar7.php`, se redirige automaticamente a EPP.
- En el rol Trabajador solo se muestra el subestandar EPP dentro del estandar de medidas de prevencion y control.
- Se agrego acceso lateral `Entrega de EPP` para trabajadores.
- La notificacion de entrega EPP mantiene enlace directo al registro pendiente.
- Antes de firmar, el trabajador debe solicitar un codigo de validacion por correo.
- El codigo se guarda como hash, expira en 10 minutos y se valida antes de aceptar la firma.
- La firma solo se guarda cuando:
  - La entrega pertenece al trabajador.
  - La entrega esta pendiente.
  - La firma canvas es valida.
  - El codigo de correo es correcto y no ha expirado.

## Base de datos
Se creo y aplico la migracion:
- `database/migrations/2026_07_13_estandar7_epp_codigo_firma.sql`

Campos agregados a `estandar7_epp_entregas`:
- `firma_codigo_hash`
- `firma_codigo_expira`
- `firma_codigo_validado_at`

## Validacion
- `php -l estandar7.php`
- `php -l procesar_estandar7.php`
- `php -l config/estandar7_schema.php`
- `php -l components/sidebar.php`
- Migracion SQL aplicada localmente.
- Validacion visual en navegador integrado como SST:
  - Titulo del estandar visible.
  - Subestandar EPP visible.
  - Formulario SST conservado.

Nota: los controles de codigo y firma se renderizan solo cuando entra un trabajador con una entrega pendiente.
