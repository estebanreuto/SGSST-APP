# Estándar 5 - diseño de custodia de historias clínicas

Fecha: 2026-07-14

## Cambio

- Se modernizaron las tarjetas de resumen con descripción, color e iconos como marca de agua.
- Se retiraron por completo los centros, certificados, estados y alertas usados como datos demostrativos.
- Cuando no existen registros se muestran únicamente contadores reales y estados vacíos.
- Los centros reales conservarán el diseño en cards y permitirán desplegar el formulario funcional de carga documental.
- Los certificados reales conservarán el consolidado en cards con detalle desplegable en lugar de una tabla extensa.
- Se reemplazó el icono `fa-folder-lock`, no disponible en la versión cargada, por `fa-folder-open` en el encabezado, resumen representativo y acceso principal.

## Validación

- `php -l estandar5.php` sin errores.
- Renderizado comprobado en `estandar5?modulo=historias-clinicas`.
- Se comprobó que el icono del encabezado renderiza desde Font Awesome 6 Free.
- Se comprobó que no aparecen nombres ni certificados ficticios.
- Consola del navegador sin errores.
