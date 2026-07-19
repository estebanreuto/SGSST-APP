# Estándar 6 - Controles recomendados a implementar

## Cambio realizado

- Se separó la jerarquía de intervención en un apartado propio dentro del tercer paso del registro IPVR.
- Se presentaron seis tarjetas ordenadas de la A a la F: eliminación, sustitución, controles de ingeniería, señalización y advertencia, controles administrativos, y equipos o elementos de protección personal.
- Cada tarjeta incluye icono, descripción breve y un campo para registrar la medida recomendada.
- Los campos de accidentalidad y observaciones se conservaron en una sección independiente de seguimiento del control.
- Se mantuvieron los nombres originales de los seis campos para conservar la compatibilidad con `procesar_estandar6.php` y la estructura actual de la base de datos.
- La grilla se adapta de tres columnas a dos y finalmente a una columna según el ancho disponible.

## Validación

- Se verificaron seis tarjetas y seis campos conectados dentro del formulario real.
- La navegación hasta el tercer paso funciona y no se envió el formulario durante la prueba.
- No se detectaron caracteres dañados en el contenido renderizado.
- Validación de sintaxis PHP correcta.
