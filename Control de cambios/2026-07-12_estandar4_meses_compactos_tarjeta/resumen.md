# Cambio: tarjetas compactas de meses en Plan de Trabajo

Fecha: 2026-07-12

## Archivo modificado

- `estandar4.php`

## Ajuste realizado

- Las tarjetas del Plan de Trabajo ya no muestran enero a diciembre completo.
- Cada tarjeta muestra en el encabezado los meses programados como chips compactos.
- La zona inferior solo renderiza los meses realmente programados, manteniendo los controles `P`, `E` y `R` para seguimiento.
- Se conserva el resumen de conteos: programado, ejecutado y reprogramado.

## Validacion

- `php -l estandar4.php`
- Recarga en navegador integrado de `estandar4.php`.
- Verificado que las tarjetas visibles solo renderizan los meses programados.
