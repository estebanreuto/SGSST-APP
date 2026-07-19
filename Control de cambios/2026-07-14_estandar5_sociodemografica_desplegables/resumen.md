# Control de cambios - Perfil sociodemográfico desplegable

Fecha: 2026-07-14

## Objetivo

Mejorar la lectura del submódulo 3.1.1 del Estándar 5 y aplicar a los porcentajes el patrón desplegable utilizado en las tarjetas de resumen del Plan Anual de Trabajo.

## Archivo modificado

- `estandar5.php`

## Cambios realizados

- Las tarjetas por pregunta ahora muestran inicialmente el porcentaje predominante.
- Cada tarjeta permite desplegar la distribución, las barras de porcentaje y su tabla de detalle consolidado completa.
- Se eliminó la sección inferior duplicada de detalle consolidado; toda la información de una pregunta vive ahora en una sola card.
- Se agregaron marcas de agua con el icono correspondiente a cada pregunta.
- Las tarjetas superiores reemplazaron el círculo decorativo por iconos visibles y marcas de agua temáticas.
- El listado de preguntas se reorganizó finalmente como una lista compacta de una sola columna en todos los tamaños de pantalla.
- Las tarjetas cerradas ahora aprovechan todo el ancho mostrando respuesta principal, trabajadores asociados, nivel de participación, barra de porcentaje, número de opciones y trabajadores distribuidos en otras respuestas.
- Se añadió un control visual `Ver análisis / Ocultar` para hacer evidente la acción desplegable.
- El detalle consolidado dejó de usar tabla y ahora presenta cada respuesta como una mini-card con cantidad, porcentaje, barra, posición en el ranking, nivel de participación, trabajadores en otras respuestas, brecha frente al líder y lectura explicativa.
- Se redujo el tamaño del valor textual `Gestión` / `Resumen` en la tarjeta de rol.
- La portada de `estandar5` ahora funciona como tablero general con seis accesos directos y métricas reales de cada submódulo para ambos roles autorizados.
- Los filtros ahora muestran la cantidad de preguntas encontradas y un estado vacío cuando no hay coincidencias.
- La búsqueda se aclaró para indicar que consulta preguntas o respuestas.
- El Representante Legal recibe una vista ejecutiva informativa de solo lectura con los mismos desplegables.
- Los resultados filtrados se abren automáticamente para facilitar la revisión.
- Se ajustó la presentación responsive para escritorio y celular.

## Base de datos

- No se realizaron cambios de estructura ni de datos.
- No aplica migración SQL.

## Validación

- Sintaxis PHP validada con `php -l estandar5.php`.
- Comportamiento de apertura y cierre validado visualmente en navegador local con datos de muestra aislados.
- Se comprobó la actualización de `aria-expanded` y `aria-hidden`.
- Se comprobó que cada card controla únicamente su propio detalle consolidado.
- Se comprobó visualmente el listado de una columna, su expansión y la ausencia de tablas en el consolidado enriquecido.
- Se comprobó visualmente la nueva distribución informativa de las tarjetas tanto cerradas como abiertas.
- Se comprobó visualmente la portada con los seis accesos directos y sus resúmenes.
