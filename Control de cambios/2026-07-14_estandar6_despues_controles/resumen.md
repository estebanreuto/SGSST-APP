# Estándar 6 - Después de implementar controles

## Cambio realizado

- Se creó el tercer apartado formal del registro IPVR para documentar el escenario posterior a la implementación de controles.
- Se organizaron los mecanismos existentes en cuatro campos: control en la fuente, control en el medio, control en la persona e instrumento de medición.
- Se agregó la evaluación completa del riesgo residual: ND, NE, NP, interpretación de NP, NC, NR y aceptabilidad.
- Se añadió una advertencia para conservar el nivel de consecuencia cuando los controles sean únicamente administrativos, como señalización o capacitación.
- El NP, el NR, la interpretación, la aceptabilidad y el color del nivel de riesgo se actualizan automáticamente.
- Se conservaron los nombres de los campos enviados al procesamiento para no afectar el guardado existente.

## Validación

- Validación PHP correcta en `nuevo_peligro_ipvr.php` y `procesar_estandar6.php`.
- Prueba visual realizada sin enviar el formulario ni guardar información en la base de datos.
- Caso inicial verificado: ND 2 × NE 4 = NP 8; NP 8 × NC 10 = NR 80, Nivel III, color amarillo y aceptabilidad Mejorable.
- No se detectaron caracteres dañados en los textos nuevos.
