---
entity: fix-productos-calculadora-listas-02
type: fix
---

- Objetivo: simplificar la calculadora de listas a 3 columnas (Lista, Porcentaje, Precio), quitar «Base de cálculo», el desplazamiento horizontal y la columna IVA; calcular siempre desde Costo con `Costo + (Costo * %/100)`.
- Archivos tocados: resources/views/admin/products/partials/form.blade.php; resources/js/admin/product-form.js; tests/product-form.test.js; tests/Feature/ProductPriceListsCalculatorTest.php.
- Cambios: base fija `#cost`, sin `taxRate` ni selector; porcentajes default 16/0/0/0/0/0; tabla `w-full text-sm` sin `overflow-x-auto` ni `min-w-[36rem]`; sin celdas `data-price-list-tax`. Verificado 10 con 16% = $11.60 y 10 con 50% = $15.00.
- Verificación: 12/12 pruebas node, 1/1 feature (46 aserciones), Pint y build app-CXQ5Odij.js.
- Estado: IMPLEMENTADO (2026-11-22). Sin desviaciones del plan aprobado.
