---
entity: fix-productos-calculadora-listas-01
type: fix
---

- Objetivo: implementar la tarjeta independiente «Listas de precios (referencia)» debajo de «Publicación» en create/edit de productos, con botón «Calcular listas», selector de base (precio venta / precio costo), 6 filas con porcentaje editable y columnas Precio e IVA de solo lectura.
- Archivos tocados: resources/views/admin/products/partials/form.blade.php; resources/js/admin/product-form.js; tests/product-form.test.js; tests/Feature/ProductPriceListsCalculatorTest.php.
- Cambios: `precio = base * (1 + %/100)`; `IVA = precio * tasa/(100+tasa)` con tasa de `#tax_rate`. Sin `name` en los nuevos controles, sin persistencia ni migraciones. Botón `type="button"`.
- Verificación: 12/12 pruebas node, 1/1 prueba feature (34 aserciones), Pint y build app-BWsaubvs.js.
- Estado: IMPLEMENTADO (2026-11-22). Sin desviaciones del plan aprobado.
