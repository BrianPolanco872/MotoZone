---
entity: hipotesis-H20-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: resources/views/layouts/site.blade.php:4-6,369-374; resources/views/admin/home/partials/contact.blade.php:31-40.
- Mecanismo: layout muestra WhatsApp solo por URL válida, ignora show_whatsapp_bubble y no incluye elemento para show_email_bubble. Las claves sí están en el editor/request.
- Verificación: helpers y render revisados. HomeTest contiene etiquetas accesibles antiguas; deben verificarse los elementos actuales, no solo ausencia de textos antiguos.
