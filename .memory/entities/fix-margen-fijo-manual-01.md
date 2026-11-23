---
entity: fix-margen-fijo-manual-01
type: fix
---

- Objetivo: que la ganancia escrita manualmente no se mueva cuando el usuario ajusta el precio de venta (costo 80, ganancia 30%, IVA 16%, precio calculado 120.64, modificado a 121 debe respetarse y conservar 30%).
- Archivos tocados: database/migrations/2026_11_22_202723_add_margin_percentage_to_products_table.php; app/Models/Product.php; app/Http/Requests/Admin/ProductRequest.php; app/Http/Controllers/Admin/ProductController.php; resources/views/admin/products/partials/form.blade.php; resources/js/admin/product-form.js; tests/Feature/AdminProductTest.php; tests/product-form.test.js; public/build/assets/app-Dcbfne9Y.js (bundle regenerado).
- Causa raiz: el margen no era un dato guardado; el campo #margin no tenia name y Product::marginPercentage() lo derivaba del precio. El listener price -> calculateMargin recalculaba el margen en cada tecla del precio. Por eso el 30% saltaba a 51.25% (o 30.39% tras descontar IVA).
- Cambio aplicado: nueva columna nullable products.margin_percentage (migracion aplicada con php artisan migrate --force); el campo #margin ahora tiene name y se valida between:0,100; productData() guarda margin_percentage solo si la peticion trae margin (las integraciones que no lo envian conservan el valor); Product::marginPercentage() devuelve el margen guardado y usa el calculo derivado con IVA descontado como fallback para productos antiguos; massUpdate actualiza tambien margin_percentage para no desincronizar; se elimino calculateMargin y su listener de price en product-form.js.
- Resultado: con costo 80, IVA 16%, margen guardado 30 y precio 121, el precio se guarda tal cual y el margen sigue siendo 30 al reabrir el producto. El precio sigue calculandose automaticamente al cambiar costo, margen o IVA; solo se deja de recalcular el margen al editar el precio.
- Verificacion: node --test tests/product-form.test.js 10/10; test_manual_selling_price_does_not_change_the_entered_margin pasa (9 aserciones); AdminProductTest 164/169 con 5 errores preexistentes de audit_logs.deleted_at; PosProductCostTest 2/3 con el fallo preexistente de SKU-001; Pint pasa; build con npm run build (build.sh no ejecutable: WSL no instalado) y bundle verificado sin calculo de margen desde precio.
- Nota: este fix sustituye el enfoque de fix-margen-iva-redondeo-01 para productos con margen guardado; el desconto de IVA queda solo como fallback de productos legacy.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
