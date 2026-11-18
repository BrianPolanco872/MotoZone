---
entity: fix-importar-csv-barcodes-01
type: fix
---

- Objetivo: persistir el código original también como código principal real y permitir completar principales vacíos al reimportar, sin generar segundos códigos ni borrar productos.
- Estado: IMPLEMENTADO.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminProductTest.php; tests/Feature/PosProductSearchTest.php.
- Cambios: prioridad de barcode explícito o fallback a código original; preservación de texto; rechazo de origen ambiguo/AUTO-UUID sin código explícito; recuperación por SKU con bloqueo de fila y actualización exclusiva de barcode vacío; validación cruzada; auditoría before/after usando servicio existente dentro de transacción; ayuda, resultados y mensajes coherentes.
- Preservado: IDs, precios, existencias, historial, segundos códigos existentes, SKU original y flujo de alta/edición manual; POS sigue buscando barcode/barcode2, no SKU. Sin nuevas dependencias, migraciones ni cambios de vendor.
- Cobertura: importación, recuperación idempotente, auditoría/rollback, conflictos entre ambos campos y mismo producto, y CSV -> campo visible real -> POS con segundo manual. Pruebas añadidas, no ejecutadas porque Testing requiere solicitud explícita.
- Verificación básica: Pint, php -l, ruta y revisión de diff correctos.
- Desviaciones: ninguna.
- Pendiente crítico: no se ejecutó recuperación real; no hay CSV original ni respaldo realizado. No borrar inventario. Antes de reimportar, revisar archivo y respaldar. No se garantiza escáner físico sin prueba de hardware.
- Fecha: 2026-11-17.
