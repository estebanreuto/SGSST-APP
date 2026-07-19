# Control de cambios - Vista demostrativa de evaluaciones médicas

Fecha: 2026-07-14

## Archivo modificado

- `estandar5.php`

## Cambios

- Se agregó una muestra visual temporal al submódulo 3.1.4 cuando todavía no existen solicitudes reales.
- La muestra incluye cuatro tarjetas de resumen con iconos de marca de agua.
- Se creó un seguimiento en cards para visualizar trabajador, tipo de examen, estado, centro médico, avance y acceso a detalle.
- Los datos ficticios están identificados como muestra y no ejecutan acciones ni se guardan en la base de datos.
- La vista demostrativa desaparece automáticamente cuando se registra la primera solicitud real.
- El diseño se adapta a escritorio, tablet y móvil.

## Validación

- Se comprobó la sintaxis PHP de `estandar5.php`.
- Se verificaron en localhost cuatro indicadores y cuatro casos de muestra.
- No se modificó la estructura ni el contenido de la base de datos.

## Mejora del flujo operativo

- Las tarjetas reales y demostrativas adoptan el estilo de marca de agua usado en los módulos anteriores, sin círculos decorativos.
- La programación dejó de usar una tabla horizontal por trabajador y ahora cuenta con buscador, paginación de 8 registros y cards seleccionables.
- Se permite seleccionar uno o varios trabajadores, incluso entre distintas búsquedas o páginas, para programarlos en un solo envío.
- El servidor valida todos los trabajadores seleccionados, limita cada envío masivo a 200 personas y registra únicamente los correos enviados correctamente.
- El control de exámenes realizados fue trasladado a `control_examenes_medicos.php` con formulario por etapas, carga de PDF, seguimiento de fechas y listado de soportes recientes.
- El guardado desde la nueva página vuelve al mismo espacio de trabajo y mantiene el procesamiento existente.

## Ajustes finales de listado y seguimiento

- Se retiró completamente de la interfaz la muestra temporal de seguimiento de evaluaciones.
- La paginación de trabajadores permite elegir entre 10 o 20 registros visibles por página.
- El buscador, las selecciones múltiples y el contador se conservan al cambiar el límite o la página.
- Solicitudes enviadas fue rediseñada con cards adaptables y un estado vacío cuando todavía no existen datos reales.
- Control médico del personal dejó la tabla horizontal y ahora presenta una card por trabajador con cargo, resultado, vigencia y acceso al soporte.
- Alertas de vencimiento usa cards por nivel de prioridad y un estado vacío más claro cuando el control está al día.
- Se reforzaron los quiebres responsive para convertir encabezados, cards y acciones a una sola columna en pantallas pequeñas.
- El selector de trabajadores ahora incluye límites de 50, 100 y la opción `Todos` sin perder búsqueda ni selección múltiple.
- Cada solicitud enviada puede expandirse para consultar contacto, documento, correo del centro, perfil enviado, observaciones y siguiente acción.
- Los registros reales de solicitudes usan un detalle desplegable para consultar la información sin recurrir a una tabla extensa.
- Control médico del personal incorpora indicadores de cobertura, soportes cargados, vigencias al día y pendientes.
- Cada trabajador presenta identidad y acciones en el encabezado, más bloques separados para cargo, examen, resultado, aptitud y vencimiento.
- El estado sin alertas se convirtió en un panel preventivo con vencidas, próximos 90 días, próxima vigencia y acceso al control documental.
- El sidebar vuelve a centrar el submódulo activo después de terminar la expansión temporal por hover.
- El mismo recálculo se ejecuta al expandir permanentemente con el botón, evitando quedar posicionado únicamente en el estándar padre.

## Correcciones finales de selección y personal

- Los checks de selección masiva ahora dibujan el chulito en blanco, centrado sobre el fondo naranja.
- Se corrigió la cascada de estilos que desplazaba el icono del trabajador en Control médico del personal.
- El icono quedó centrado en un contenedor compacto de 34 px y separado correctamente de la identidad del trabajador.
- Se retiraron las solicitudes ficticias que aparecían cuando no había programaciones; ahora se presenta únicamente un estado vacío con instrucciones.
