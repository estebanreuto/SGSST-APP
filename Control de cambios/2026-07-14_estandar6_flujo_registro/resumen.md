# Estándar 6: flujo independiente para registrar peligros

Fecha: 2026-07-14

## Cambios

- Se retiró el formulario extenso de la página principal del Estándar 6.
- Se agregó el acceso `Registrar peligro` dentro de la sección Peligros por actividad.
- Se creó `nuevo_peligro_ipvr.php` como página exclusiva para el registro.
- El formulario se organizó en tres etapas: contexto y exposición, valoración y controles, y plan de intervención.
- Se conservaron los nombres de campos, catálogos y procesamiento existentes.
- Se añadió cálculo lateral en tiempo real para expuestos, riesgo inicial, aceptabilidad y riesgo residual.
- Los campos obligatorios se validan antes de avanzar al siguiente paso.
- Los errores de guardado regresan a la nueva página; un guardado exitoso regresa al Estándar 6.
- El sidebar mantiene activo el Estándar 6 y el encabezado muestra `Nuevo Peligro IPVR`.
- Se mantuvo la respuesta UTF-8 para evitar caracteres dañados.

## Validación

- Sintaxis PHP verificada en las páginas, procesamiento y componentes modificados.
- El dashboard ya no contiene el formulario incrustado.
- El botón abre correctamente `/nuevo_peligro_ipvr`.
- Los tres pasos avanzan, retroceden y validan los campos requeridos.
- El cálculo de personas expuestas se actualizó de 0 a 5 durante la prueba.
- La prueba regresó al módulo sin enviar el formulario ni insertar registros.
