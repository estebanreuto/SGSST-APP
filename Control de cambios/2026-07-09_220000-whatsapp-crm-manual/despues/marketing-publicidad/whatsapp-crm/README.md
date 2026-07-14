# CRM WhatsApp Business PreventWork

Este paquete documenta el flujo inicial para trabajar WhatsApp Business gratis con apoyo manual de ChatGPT y registro interno en la app.

## Ubicacion operativa

Este flujo se administra desde el **Super Administrador** de PreventWork, no desde las cuentas cliente.

Los roles `representante`, `sst` y `trabajador` pertenecen a empresas cliente y no deben controlar la operacion comercial global de WhatsApp. El Super Administrador centraliza prospectos, mensajes, seguimiento, planes, propuestas y futuras automatizaciones.

## Objetivo del MVP

- Registrar prospectos que llegan por WhatsApp Business.
- Guardar datos comerciales clave antes de cotizar.
- Abrir WhatsApp con mensajes prellenados desde el panel admin.
- Usar ChatGPT como apoyo para redactar respuestas, seguimientos y objeciones.
- Evitar costos adicionales mientras se valida el flujo comercial.

## Alcance actual

- No lee chats automaticamente.
- No responde solo por WhatsApp.
- No usa WhatsApp Cloud API ni n8n.
- Depende de registro manual desde el admin.

## Archivos relacionados

- `admin/whatsapp_crm.php`: pantalla de gestion de prospectos.
- `config/whatsapp_crm_schema.php`: crea la tabla si no existe.
- `database/migrations/2026_07_09_whatsapp_crm_prospectos.sql`: migracion manual.
- `marketing-publicidad/whatsapp-crm/flujo-comercial.md`: pasos de atencion.
- `marketing-publicidad/whatsapp-crm/prompts-chatgpt.md`: prompts de apoyo.
