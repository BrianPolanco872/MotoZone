---
entity: fix-pos-vista-amplia-existencias-01
type: fix
---

- Objetivo: aplicar la feature aprobada de POS simplificado/amplio/estable, búsqueda con metadata y cantidades por ubicación, y fotos ampliables, conservando inventario/cobro existentes.
- Plan: plan-pos-vista-amplia-existencias-01.
- Feature: feature-pos-vista-amplia-existencias-01.
- Archivos tocados: app/Http/Controllers/Admin/ProductController.php; app/Http/Controllers/PosController.php; app/Http/Requests/Admin/ProductRequest.php; app/Models/Product.php; database/migrations/2026_11_22_113802_add_optional_location_stocks_to_products_table.php; resources/js/admin/product-form.js; resources/js/pos/pos.js; resources/views/admin/products/partials/form.blade.php; resources/views/layouts/admin.blade.php; resources/views/pos/index.blade.php; tests/Feature/AdminProductTest.php; tests/Feature/PosAccessTest.php; tests/Feature/PosProductSearchTest.php; tests/Feature/PosSalesTest.php; tests/pos.test.js; tests/product-form.test.js.
- Cambios: paneles solicitados retirados, layout POS sin sidebar, detalle y búsqueda estables, tabla sin Acción y selección por nombre/código, cantidad editable/importe efectivo incluido mayoreo, granel conserva modal y cantidad propuesta; foto de carrito mediante dialog con Cerrar/Escape/foco y fallback; dos campos manuales nullable, cero visible, Tienda 2 condicional, tienda usa stock vigente; eager loading/metadata de pendientes sin alterar precios ni snapshots.
- Verificación: Pint dirty, sintaxis PHP/JS y git diff --check correctos; diff comparado con alcance. Pruebas agregadas/adaptadas pero NO ejecutadas.
- Limitaciones: sin migración de BD, build ni navegador; tag de versión no apunta a HEAD y bloquea ./build.sh. No se modificaron versiones, dependencias ni historial Git.
- Estado: IMPLEMENTADO EN FUENTE.
- Fecha: 2026-11-22.
