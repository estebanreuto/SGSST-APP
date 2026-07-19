# Estándar 6: colores por nivel de riesgo

Fecha: 2026-07-14

## Semáforo aplicado

- Nivel I, riesgo mayor o igual a 600: rojo, situación crítica.
- Nivel II, riesgo entre 150 y 599: naranja, requiere corrección.
- Nivel III, riesgo entre 40 y 149: amarillo, condición mejorable.
- Nivel IV, riesgo menor de 40: verde, condición aceptable.

## Cambios

- Se aplicó el color correspondiente al valor inicial y residual de cada card del Estándar 6.
- El borde principal de cada card representa el nivel de riesgo residual.
- Se agregaron etiquetas con nivel y acción: crítico, corregir, mejorable o aceptable.
- El detalle desplegable muestra con color independiente la valoración inicial y el resultado después del control.
- Se agregó una leyenda visual con los cuatro niveles.
- El cálculo en vivo de la página de registro cambia automáticamente los colores del riesgo inicial, aceptabilidad y riesgo residual.
- Se corrigió la clasificación del valor 600 para que pertenezca al Nivel I.

## Validación

- Los seis ejemplos muestran niveles II, III y IV según su riesgo residual, y un riesgo inicial Nivel I en rojo.
- En el formulario, el riesgo 400 inicia en Nivel II; 4000 cambia a Nivel I y el residual 0 cambia a Nivel IV.
- No se guardaron registros durante las pruebas visuales.
