---
entity: fix-margen-iva-redondeo-01
type: fix
---

- Objetivo: corregir el cálculo de ganancia al redondear el precio de venta. Con costo 80, margen 30%, IVA 16%, precio 120.64 redondeado a 121, la ganancia saltaba a 50% porque se calculaba sobre el precio con IVA.
- Archivos tocados: app/Models/Product.php; resources/js/admin/product-form.js; tests/Feature/PosProductCostTest.php; tests/product-form.test.js; public/build/assets/app-B-rp6HJv.js (bundle regenerado).
- Causa raiz: marginPercentage() y calculateMargin() usaban ((precio - costo)/costo)*100 con el precio final que incluye IVA. El IVA se añade al precio, no es ganancia, asi que al redondear 120.64 -> 121 el IVA residual (~16.72) se contaba como utilidad: (121-80)/80 = 51.25%.
- Cambio aplicado: descontar el IVA antes de calcular: precioSinIva = precio / (1 + tasa_iva); margen = (precioSinIva - costo)/costo * 100. En PHP se usa $this->tax_rate (fraccion, 0.16); en JS tax_rate.value es porcentaje (16), por eso /100.
- Resultado: costo 80, IVA 16%, precio 121 -> precioSinIva 104.31 -> margen 30.39% (no 50%). Precio exacto 120.64 -> 30.00%.
- Verificacion: node --test tests/product-form.test.js 10/10; php artisan test --compact tests/Feature/PosProductCostTest.php 2/3 (unico fallo preexistente de SKU-001, no relacionado); Pint pasa. Build con npm run build (build.sh no ejecutable: WSL no instalado); bundle verificado contiene la division por (1+iva).
- Nota: se actualizo la expectativa preexistente de marginPercentage() de 100.00 a 72.41 en test_product_can_be_created_with_cost_wholesale_and_min_stock (price 100 con IVA 16% y costo 50).
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
