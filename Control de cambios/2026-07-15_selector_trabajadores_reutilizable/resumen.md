# Selector reutilizable de trabajadores

## Objetivo

Evitar volver a escribir información que ya existe en la base de datos y mantener formularios utilizables cuando una empresa tenga muchos trabajadores.

## Cambios

- Se creó un buscador reutilizable para selects de trabajadores.
- La búsqueda funciona por nombre, cédula, correo, teléfono y cargo, ignorando diferencias de mayúsculas y tildes.
- Las opciones se indexan una sola vez y el filtrado se actualiza por cuadro de animación para responder bien con listados grandes.
- Se muestra la cantidad de coincidencias y el trabajador seleccionado.
- El patrón se integró en restricciones médicas, entrega de EPP y asignación de trabajadores a grupos.
- En restricciones médicas, seleccionar un trabajador llena automáticamente el cargo desde su grupo, perfil sociodemográfico o último registro médico disponible.
- Si existe una fecha de ingreso guardada en una carta anterior del mismo trabajador, también se recupera; si no existe, el campo permanece vacío.
- En EPP se mejoró la consulta del cargo para priorizar el grupo del trabajador y se agregó búsqueda por correo.
- Evaluaciones médicas y capacitaciones conservaron sus buscadores existentes porque ya cumplen este flujo.

## Validación

- Búsqueda de Esteban verificada con una coincidencia.
- Selección de Esteban verificada con autollenado del cargo `Tecnologia` desde la base de datos.
- La fecha de ingreso permaneció vacía porque no existe un valor previo guardado para ese trabajador.
- El selector reutilizable quedó activo en la entrega de EPP.
- Sintaxis PHP, consulta SQL y revisión de diferencias correctas.
- No se envió ningún formulario durante las pruebas.
