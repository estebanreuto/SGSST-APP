# Restricciones médicas - Alineación de checks PVE

## Causa

La regla general `.rr-field label` tenía mayor especificidad que `.rr-check` y convertía las tarjetas nuevamente en elementos de bloque. Esto eliminaba la alineación flex y dejaba el cuadro pegado al texto.

## Corrección

- Se agregó una regla específica para `label.rr-check` dentro de los campos del formulario.
- Las tarjetas usan alineación flex centrada, separación interna de 10 px y altura mínima de 52 px.
- Se normalizaron márgenes, interlineado y espaciado del texto.
- Se mantuvo el check en 18 × 18 px y el chulito blanco centrado.
- Se actualizó la versión del CSS para evitar que Chrome reutilice estilos anteriores.

## Validación

- Desfase vertical medido: 0 px.
- Separación entre check y texto: 10 px.
- Estado seleccionado verificado con chulito blanco visible.
- El formulario no fue enviado durante la prueba.
