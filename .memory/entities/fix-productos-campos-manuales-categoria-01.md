---
entity: fix-productos-campos-manuales-categoria-01
type: fix
---

- Objetivo: que existencia en tienda, mínimo para alerta, bodega y tienda 2 se escriban a mano (sin flechas incrementadoras) y añadir campo de Categoría manual en Información del producto, como la columna Categoría del POS.
- Archivos tocados: resources/views/admin/products/partials/form.blade.php; app/Http/Controllers/Admin/ProductController.php; app/Http/Requests/Admin/ProductRequest.php; tests/Feature/AdminProductTest.php.
- Cambios aplicados: los cuatro campos de inventario pasaron de type=number a type=text con inputmode=decimal y autocomplete=off; se eliminaron step/min/max y la variable stockStep. La validación serverside sigue en ProductRequest mediante Quantity::valid (enteros en unidad, hasta 3 decimales en granel).
- Categoría: nuevo input text con datalist de categorías existentes en el panel Información del producto. El controlador pasa categoryNames() a create/edit, carga la relación en edit, y productData() resuelve category_id con resolveCategoryId(): reutiliza categoría por nombre o la crea con slug único si no existe. Campo vacío desasigna la categoría; si la petición no trae clave category se conserva la relación actual.
- Verificación: prueba nueva test_inventory_fields_are_plain_text_and_category_is_written_manually pasa (35 aserciones); AdminProductTest completo 163/168, con 5 errores preexistentes de audit_logs.deleted_at en SQLite. Pint correcto. Sin build: no hay cambios CSS/JS.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
