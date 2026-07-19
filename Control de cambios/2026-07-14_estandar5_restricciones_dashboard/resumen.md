# Estándar 5 - tablero y gestión de restricciones médicas

Fecha: 2026-07-14

## Cambios

- El submódulo `restricciones` se convirtió en un tablero principal de consulta.
- Se agregaron cuatro tarjetas de resumen con marcas de agua para registros, cartas firmadas, cierres y pendientes SST.
- Se incorporaron accesos directos para crear cartas y actualizar seguimientos.
- La matriz horizontal fue sustituida visualmente por cards desplegables con trabajador, concepto, carta, restricción, PVE y trazabilidad SST/ARL.
- Los formularios largos dejaron de mostrarse en el tablero principal.
- Se creó `gestion_restricciones_medicas.php` con dos vistas separadas: nueva carta y actualización de seguimiento.
- La nueva página conserva las acciones existentes de `procesar_estandar5.php` y retorna los mensajes al mismo espacio de gestión.
- No se crearon registros de muestra ni se modificaron datos reales.

## Validación

- Sintaxis PHP comprobada en el tablero, la nueva página y el procesador.
- Tablero principal comprobado en localhost sin formularios antiguos visibles.
- Vistas de nueva carta y seguimiento verificadas mediante URL sin extensión `.php`.
- Consola del navegador sin errores.

## Transición de navegación

- Se agregó una transición compartida de entrada y salida para las páginas que usan el encabezado del panel.
- La salida dura 180 ms y la entrada 260 ms, con desvanecido y desplazamiento vertical leve.
- La navegación conserva el comportamiento normal de formularios, enlaces externos, descargas, PDFs y enlaces que abren otra pestaña.
- La animación se desactiva automáticamente cuando el sistema solicita reducción de movimiento.
