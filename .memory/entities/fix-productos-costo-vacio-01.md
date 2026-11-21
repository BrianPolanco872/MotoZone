---
entity: fix-productos-costo-vacio-01
type: fix
---

- Objetivo: permitir guardar productos con precio de venta directo y costo deliberadamente vacío, evitando persistir NULL en cost y min_stock sin recalcular el precio.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; tests/Feature/AdminProductTest.php. Registro de memoria: .memory/entities/plan-productos-costo-vacio-01.md; .memory/entities/fix-productos-costo-vacio-01.md; .memory/relations.md.
- Cambios: normalización mediante FormRequest::validated con fallback 0 para vacíos/null; alta con campos omitidos usa 0 y edición con campos omitidos conserva los valores anteriores. Los valores válidos, incluido costo 7.50, no se sustituyen. wholesale_price sigue admitiendo NULL.
- Cobertura añadida: dos métodos parametrizados con 12 escenarios de alta/edición para campos omitidos, vacíos, null, valores válidos y cero; comprueban precio final, IVA, códigos, costo, min_stock y precio mayoreo nullable.
- Verificación: php vendor/bin/pint --dirty --format agent aprobado; php -l aprobado en los dos archivos PHP; git diff --check aprobado. Diff revisado contra el alcance: solo dos líneas de normalización en productData y las pruebas previstas.
- Testing: pruebas añadidas pero NO ejecutadas; requiere solicitud explícita. No se han escrito datos reales ni ejecutado migraciones, build, despliegue u operaciones de historial Git.
- Alcance: implementado en el workspace local, no publicado. El reporte anterior de pérdida de un costo introducido no se declara resuelto por este cambio.
- Estado: IMPLEMENTADO EN FUENTE.
- Fecha: 2026-11-20.
