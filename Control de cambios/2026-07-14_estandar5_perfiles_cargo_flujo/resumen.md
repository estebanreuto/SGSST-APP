# Control de cambios - Flujo de perfiles de cargo

Fecha: 2026-07-14

## Archivos principales

- `estandar5.php`
- `procesar_estandar5.php`
- `nuevo_centro_medico.php`
- `nuevo_proceso_perfil.php`
- `nuevo_perfil_cargo.php`
- `assets/estandar5-perfiles.css`
- `config/estandar5_perfiles_helpers.php`
- `components/sidebar.php`
- `components/header.php`

## Módulo principal

- Se reemplazó la experiencia de formularios mezclados por un tablero de gestión.
- Se agregaron cuatro cards de resumen: centros, procesos, perfiles y cobertura de asignación.
- Se agregaron tres cards de acceso directo con iconos, marca de agua y pasos del flujo.
- Centros, procesos y perfiles se presentan en cards informativas reutilizables.
- Los perfiles conservan la descarga en PDF y muestran tareas, riesgos y herramientas.

## Flujo separado

- El registro de centros médicos se realiza en una página independiente.
- La creación de procesos se realiza en una página independiente y evita duplicados.
- La creación del perfil se realiza en una página independiente con centro médico opcional.
- Los errores y mensajes de guardado regresan al formulario que originó la acción.
- Cada página muestra los tres pasos y el avance disponible de la empresa.

## Mejora visual de formularios

- Las tarjetas de los pasos 1, 2 y 3 tienen mayor jerarquía, profundidad y una señal lateral de estado sin cambiar sus números.
- Las cards de acceso a formularios resaltan su color, marca de agua y llamada a la acción.
- Los campos se agrupan visualmente para facilitar el recorrido y destacar el bloque activo.
- Las licencias se cargan desde tarjetas documentales con icono, nombre del archivo y estado seleccionado.
- Las herramientas y tareas de alto riesgo usan checks tipo card con selección resaltada, conservando los mismos nombres y valores enviados al backend.
- Se retiraron las líneas decorativas naranjas y los círculos de fondo de los formularios; ahora se utilizan iconos de marca de agua.
- Los campos normales ya no tienen un contenedor visual adicional alrededor del input.
- La decisión de conservar un proceso se presenta en dos tarjetas seleccionables, manteniendo los valores `1` y `0` esperados por el backend.
- Los checks se compactaron y usan una selección azul más limpia, sin modificar sus valores ni agrupaciones.
- Las tres cards de acceso Paso 1, Paso 2 y Paso 3 del módulo principal se redujeron a una altura uniforme de 138 px, conservando descripción y llamada a la acción.
- El indicador de los checks seleccionados ahora usa un icono Font Awesome centrado, en lugar del símbolo construido con bordes.

## Navegación

- El sidebar mantiene activo el submódulo 3.1.3 en las tres páginas nuevas.
- Las rutas nuevas funcionan sin extensión `.php` mediante la reescritura existente.
- El encabezado identifica cada pantalla del flujo.

## Validación

- Validación de sintaxis PHP superada en los ocho archivos PHP involucrados.
- Módulo principal y tres páginas nuevas abiertos correctamente en localhost.
- Acceso principal a la creación del perfil probado desde la card correspondiente.
- Campos dinámicos de proceso y herramientas probados en el navegador.
- Sidebar activo confirmado en una página independiente y sin errores de consola.
- No se insertaron datos de prueba en la base de datos.
