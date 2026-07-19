# Estándar 6: vista demostrativa y diseño IPVR

Fecha: 2026-07-14

## Cambios

- Se agregaron seis registros ficticios en memoria cuando la empresa todavía no tiene una matriz IPVR registrada.
- Los datos de muestra están identificados visualmente y no se insertan en la base de datos.
- Se compactaron títulos, tipografías, espaciados, tarjetas de resumen y gráficos para mantener el estilo de los módulos anteriores.
- Se añadieron iconos como marca de agua en las tarjetas de resumen.
- La tabla horizontal fue reemplazada por tarjetas de riesgo con detalle desplegable, valoración inicial, riesgo residual, controles y plan de intervención.
- El formulario existente se conserva dentro de un panel plegable para que no domine la vista principal.
- Se agregó búsqueda por proceso, actividad, cargo, peligro y clasificación.
- Se fijó la respuesta del módulo a UTF-8 y se corrigió el título del encabezado para evitar caracteres dañados.

## Validación

- Sintaxis PHP verificada en `estandar6.php` y `components/header.php`.
- Vista local comprobada en navegador con seis tarjetas, gráficos, buscador y detalles desplegables.
- La búsqueda `químico` devuelve una tarjeta y oculta las otras cinco.
- No se detectaron secuencias visibles `Ã`, `Â` o `�` en el contenido renderizado.
