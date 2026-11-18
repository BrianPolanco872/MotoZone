---
entity: hipotesis-H1-home-destacados-precios-miles-01
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivos/líneas: resources/views/admin/home/partials/card-form.blade.php:50,55; app/Http/Requests/Admin/HomeCardRequest.php:17-20,44-45.
- Mecanismo: input HTML number no representa agrupación de miles; request numeric no acepta comas, de modo que el importe formateado no puede mantenerse como entrada válida. Se requiere permitir texto numérico y normalizar exclusivamente agrupaciones válidas antes de validar, conservando almacenamiento decimal.
- Contratos conectados inspeccionados: HomeCard::casts decimal:2; HomeCardController store/update usan HomeCardRequest; promo-card presenta miles con number_format; home-editor.js:455 copia un precio decimal sin separadores y sigue siendo entrada compatible.
