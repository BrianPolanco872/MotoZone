---
entity: bug-preuso-13
type: bug
---

- Auditoría: #13. Borrado de producto sin ventas llama a una relación inexistente.
- Estado: CAUSA RAÍZ CONFIRMADA por definición del modelo y búsqueda de referencias.
- Evidencia: app/Http/Controllers/Admin/ProductController.php:44-58; app/Models/Product.php:57-65.
- Caso: saleItems()->exists() es false, se evalúa stockMovements() y falla antes del DELETE.
- Causa confirmada: hipotesis-H13-preuso.
- Fecha: 2026-11-16. Sin implementación.
