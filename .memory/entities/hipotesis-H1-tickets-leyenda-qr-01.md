---
entity: hipotesis-H1-tickets-leyenda-qr-01
type: hipotesis
---

- Estado: CONFIRMADA para el texto no solicitado, no para la falta de la imagen en papel.
- Mecanismo: `resources/views/pos/tickets/receipt.blade.php:75-79` y `resources/views/pos/tickets/settings.blade.php:158-161` incluyen el literal «Facturación / Soporte» dentro del bloque del QR. Se muestra al mostrarse el bloque, sin leer una leyenda configurada.
