---
entity: plan-productos-calculadora-listas-02
type: feature
---

- Tipo: feature (corrección de feature-productos-calculadora-listas-01, aún no desplegada).
- Estado: APROBADO (usuario aprobó el 2026-11-22 la opción «Sí, aprobar e implementar»).
- Objetivo: simplificar «Listas de precios (referencia)» en create/edit de productos a 3 columnas (Lista, Porcentaje (%), Precio) sin selector de base ni columna IVA, calculando siempre desde Costo con `Costo + (Costo * Porcentaje / 100)` al presionar «Calcular listas».
- Alcance confirmado: `resources/views/admin/products/partials/form.blade.php` tiene el bloque con `data-price-lists-base`, `overflow-x-auto min-w-[36rem]`, `<th>IVA</th>` y celdas `data-price-list-tax`; `resources/js/admin/product-form.js` calcula con base seleccionable y desglosa IVA; `tests/product-form.test.js` y `tests/Feature/ProductPriceListsCalculatorTest.php` cubren el comportamiento anterior.
- Cambios exactos:
  - Blade: eliminar el div «Base de cálculo» y su `<select>`; quitar `overflow-x-auto` y `min-w-[36rem]` (usar tabla simple `w-full text-sm`); eliminar `<th>IVA</th>` y el `<td data-price-list-tax>`; dejar porcentajes por defecto `16` en Precio Público y `0` en las otras cinco filas; conservar los 6 nombres y el orden.
  - JS: eliminar `priceListsBase`, el uso de `#price` y el cálculo de IVA; base fija `#cost`; `precio = round(costo * (1 + porcentaje / 100))`; formatear con `$${toFixed(2)}` reutilizando `round`.
  - Pruebas: actualizar mocks y aserciones node (12 → 12 pruebas: reemplazar el test de selector por uno que verifique que siempre usa Costo aunque Precio venta sea distinto; verificar 10 + 16% = 11.60, 10 + 50% = 15.00 y que no cambian los campos principales); actualizar feature test para exigir exactamente 3 `th`, 0 celdas de IVA y 0 selects de base en la tarjeta.
  - Sin persistencia, migraciones ni dependencias nuevas.
- Archivos a tocar: `resources/views/admin/products/partials/form.blade.php`; `resources/js/admin/product-form.js`; `tests/product-form.test.js`; `tests/Feature/ProductPriceListsCalculatorTest.php`.
- Plan de rollback: copiar los cuatro archivos a `C:\\Users\\Administrador\\AppData\\Local\\Temp\\opencode\\backup-productos-calculadora-listas-02\\` antes de editar; si falla, restaurar copias y ejecutar `npm run build`. NO usar `git checkout` (hay trabajo sin commitear).
- Verificación planeada: `node --test tests/product-form.test.js`; `php artisan test --compact tests/Feature/ProductPriceListsCalculatorTest.php`; `vendor/bin/pint --dirty --format agent`; `npm run build`. Sin testing completo ni commits.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-productos-calculadora-listas-02. Sin desviaciones del plan aprobado.
- Cambios aplicados: tabla de 3 columnas sin scroll horizontal; eliminados selector de base y columna IVA; base fija `#cost` con `Costo + (Costo * %/100)`; defaults 16/0/0/0/0/0; pruebas actualizadas.
- Verificación real: 12/12 node, 1/1 feature (46 aserciones), Pint y build app-CXQ5Odij.js. Rollback en backup-productos-calculadora-listas-02.
