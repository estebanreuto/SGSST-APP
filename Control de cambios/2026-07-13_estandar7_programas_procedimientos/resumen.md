# Control de cambios - Estandar 7 / Programas documentales

Fecha: 2026-07-13

## Cambio realizado
- En el subestandar 4.2.3 se habilito la categoria `Programas` con dos programas:
  - Programa de trabajo seguro en alturas.
  - Programa de gestion para el trabajo en espacios confinados.
- Cada programa abre un formulario editable por el rol Responsable SST.
- El programa de alturas contiene 15 secciones conforme a la guia indicada.
- El programa de espacios confinados contiene 11 secciones conforme a la guia indicada.
- Se agrego boton para generar PDF por programa.
- Las tarjetas muestran avance por secciones diligenciadas.

## Archivos modificados
- `estandar7.php`
- `procesar_estandar7.php`
- `config/estandar7_schema.php`

## Archivos nuevos
- `estandar7_programa_pdf.php`
- `database/migrations/2026_07_13_estandar7_programas_documentales.sql`

## Base de datos
- Se creo la tabla `estandar7_programas_documentales`.
- La migracion SQL quedo disponible para copiar y pegar en:
  `database/migrations/2026_07_13_estandar7_programas_documentales.sql`
- La migracion se ejecuto localmente sin error.

## Validacion
- `php -l estandar7.php`: sin errores.
- `php -l procesar_estandar7.php`: sin errores.
- `php -l config/estandar7_schema.php`: sin errores.
- `php -l estandar7_programa_pdf.php`: sin errores.
- Navegador integrado:
  - `procedimientos > programas`: se confirmaron 2 tarjetas de programa.
  - `Programa de trabajo seguro en alturas`: se confirmaron 15 secciones y boton PDF.
  - `Programa de gestion para el trabajo en espacios confinados`: se confirmaron 11 secciones y boton PDF.
