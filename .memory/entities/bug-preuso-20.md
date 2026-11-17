---
entity: bug-preuso-20
type: bug
---

- Auditoría: #20. Interruptores de burbujas de contacto no se reflejan en el sitio.
- Estado: CAUSA RAÍZ CONFIRMADA por editor, claves admitidas y layout.
- Evidencia: resources/views/admin/home/partials/contact.blade.php:31-40; resources/views/layouts/site.blade.php:1-7,369-374.
- Caso: desactivar burbuja WhatsApp no la oculta si hay número; activar burbuja correo no la muestra.
- Causa confirmada: hipotesis-H20-preuso.
- Fecha: 2026-11-16. Sin implementación. No autoriza cambiar contactos/fallbacks del #18.
