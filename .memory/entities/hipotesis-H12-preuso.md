---
entity: hipotesis-H12-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Admin/ProductController.php:106,115,142-157; resources/views/admin/products/index.blade.php:67.
- Mecanismo: trim de código ausente produce cadena vacía no null bajo índice unique; cada fila se escribe sin validación integral ni transacción. El MIME permite formatos comprimidos/binarios que fgetcsv no interpreta.
- Verificación: importador, validación normal de productos, índice y formulario contrastados. No se importaron archivos ni ejecutaron tests.
