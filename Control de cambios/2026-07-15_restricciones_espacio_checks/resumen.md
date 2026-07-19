# Gestión de restricciones médicas - Espaciado y checks

## Problemas encontrados

- La página aplicaba 88 px de relleno superior después de un header que ya ocupa su propio espacio, generando un vacío vertical duplicado.
- Los checks de PVE heredaban el `min-height: 40px` definido para los inputs del formulario, por lo que se mostraban altos y desalineados.
- Chrome conservaba en caché la versión anterior de la hoja de estilos.

## Cambios realizados

- El relleno superior de escritorio se redujo de 88 px a 18 px y el móvil de 82 px a 14 px.
- Los checks ahora tienen un tamaño fijo de 18 × 18 px, sin margen ni relleno heredado.
- Se añadió un chulito blanco centrado con Font Awesome y resaltado naranja al seleccionar.
- Se versionó la hoja de estilos en la vista para forzar la actualización inmediata del navegador.

## Validación

- Separación medida entre el header y el contenido: 34 px visuales.
- Checkbox medido: 18 × 18 px y `min-height` de 18 px.
- Estado seleccionado verificado con chulito blanco visible y centrado.
- Los cinco programas PVE continúan disponibles y no se envió el formulario.
