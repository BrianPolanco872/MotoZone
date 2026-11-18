---
entity: hipotesis-H1-importar-csv-barcodes-01
type: hipotesis
---

- Estado: CONFIRMADA mediante inspección de código y aclaración explícita del usuario sobre el mapeo esperado.
- Contrato esperado: el código original importado se conserva como SKU y, si no hay un principal explícito, se persiste como barcode visible y escaneable; barcode2 nunca se deriva automáticamente de SKU.
- Archivo/líneas: app/Http/Controllers/Admin/ProductController.php:171 asigna sku|codigo a SKU; :174 omite SKU existentes; :252-253 solo mapea aliases barcode y barcode2 sin fallback al código original. resources/views/admin/products/partials/form.blade.php:64 muestra product.barcode. app/Http/Controllers/PosController.php:77-78 busca barcode/barcode2, no SKU.
- Mecanismo causal: una fila con codigo o sku y sin un alias de principal reconocido puede guardarse con SKU completo y barcode null; el campo queda vacío y el escáner no lo encuentra por ese código. Reimportar no lo corrige porque los SKU existentes se omiten.
- Límite de evidencia: no se recibió el CSV; confirmación del mecanismo y del contrato solicitado, no de todos los datos ni encabezados del archivo real.
