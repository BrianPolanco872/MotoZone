---
entity: bug-preuso-12
type: bug
---

- Auditoría: #12. Importación parcial con códigos vacíos y aceptación de Excel sin parser de Excel.
- Estado: CAUSAS RAÍZ CONFIRMADAS por lector, escrituras y esquema; sin importación real.
- Evidencia: app/Http/Controllers/Admin/ProductController.php:103-166; database/migrations/2026_10_27_051904_add_pos_fields_to_products_table.php:23; resources/views/admin/products/index.blade.php:67.
- Casos: dos SKU sin código se guardan con barcode='' y la segunda fila viola unicidad dejando la primera; xlsx/xls se admiten aunque solo se usa fgetcsv.
- Causa confirmada: hipotesis-H12-preuso.
- Fecha: 2026-11-16. Sin implementación.
