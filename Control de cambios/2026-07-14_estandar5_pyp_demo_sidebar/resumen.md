# Control de cambios - Vista PyP y navegación del sidebar

Fecha: 2026-07-14

## Archivos modificados

- `estandar5.php`
- `components/sidebar.php`

## Promoción y Prevención

- La vista demostrativa temporal y todos sus datos de muestra fueron retirados del módulo.
- Los indicadores muestran exclusivamente campañas reales sincronizadas desde capacitación.
- Cuando no hay campañas se presenta un estado vacío con accesos para consultar capacitación o crear la primera campaña.
- Las cuatro cards de resumen incluyen iconos de marca de agua y colores diferenciados.
- Cada campaña real del seguimiento incluye un acceso compacto `Ver detalle` hacia el módulo de capacitaciones.
- Las campañas reales abren el mes correcto, enfocan la actividad seleccionada y despliegan automáticamente su detalle en el Estándar 3.

## Sidebar

- El desplazamiento automático ahora prioriza el submódulo activo exacto.
- Se abren el grupo del estándar y el desplegable padre antes de calcular la posición.
- Solo se desplaza el contenedor interno del sidebar; la página principal no cambia de posición.
- La inicialización funciona tanto antes como después de `DOMContentLoaded`.
- Cuando el usuario contrae el sidebar con el botón, pasar el puntero lo expande de forma temporal.
- Al retirar el puntero, el sidebar vuelve al estado compacto sin mover el contenido principal.
- Si el usuario lo expande con el botón, permanece abierto aunque retire el puntero.
- El comportamiento por puntero se limita a escritorio y no altera la navegación móvil.

## Validación

- Vista PyP comprobada sin rótulos, categorías ni actividades demostrativas.
- Estado vacío y cuatro indicadores reales en cero comprobados en localhost.
- Sidebar comprobado con el último submódulo activo visible y centrado.
- No se realizaron cambios de estructura ni de datos en la base de datos.
