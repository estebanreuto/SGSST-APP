# Estándar 6 - Valoración final de la eficiencia de los controles

## Cambio realizado

- Se creó un apartado independiente después de los controles recomendados.
- Se agregó el factor de reducción automático con la fórmula `((NR1 - NRF) / NR1) × 100`.
- Se incorporaron los registros de accidentes del año anterior y del año actual con años dinámicos.
- Se añadió la valoración explícita de eficacia de los controles con las opciones Sí, No y pendiente de valorar.
- Se conservaron las observaciones como conclusión de la evaluación final.
- El resultado del factor cambia de color según la reducción sea alta, moderada o baja.
- El procesamiento ahora respeta la valoración Sí/No seleccionada; si queda pendiente y existen ambos registros de accidentes, calcula una valoración de respaldo comparando los dos años.

## Validación

- Caso visual verificado: NR1 400 y NRF 80 producen un factor de reducción del 80,0 %.
- Se comprobaron los cinco campos del apartado y el texto renderizado sin caracteres dañados.
- Se verificó que una selección explícita No permanezca como No durante el cálculo del servidor.
- No se envió el formulario ni se guardaron datos durante la prueba.
- Sintaxis PHP y revisión de diferencias correctas.
