---
entity: plan-productos-calculadora-listas-03
type: feature
---

- Tipo: feature (corrección de feature-productos-calculadora-listas-01).
- Estado: APROBADO (usuario aprobó el 2026-11-22 la opción «Sí, aprobar e implementar», aceptando la diferencia de $0.01 en dos filas del ejemplo).
- Objetivo: que «Listas de precios (referencia)» calcule en cascada desde Costo: Precio Público = `Costo * (1 + IVA%/100)`; cada lista siguiente = `Precio anterior * (1 - Desc%/100)`.
- Alcance confirmado: `resources/views/admin/products/partials/form.blade.php` tiene los defaults 16/0/0/0/0/0, la etiqueta «Porcentaje (%)» y el texto «Usa un porcentaje negativo…»; `resources/js/admin/product-form.js` aplica hoy `Costo + (Costo * %/100)` de forma independiente por fila; `tests/product-form.test.js` y `tests/Feature/ProductPriceListsCalculatorTest.php` cubren el comportamiento anterior.
- Cambios exactos:
  - Blade: etiqueta intermedia «IVA / Desc. (%)»; defaults `16, 10, 21, 18, 15, 10`; texto inferior exacto: «Precio Público suma IVA al costo; las listas L1 a L5 aplican descuento en cascada sobre la lista anterior.»
  - JS: en el clic, primera fila `round(costo * (1 + porcentaje / 100))`; filas 2..6 `round(precioAnterior * (1 - porcentaje / 100))`, usando el precio ya redondeado de la fila inmediata anterior para que la cascada sea reproducible desde lo que se ve en pantalla. Costo vacío/inválido → no escribe nada. Sin tocar campos principales ni guardar.
  - Pruebas: mocks con 6 filas y defaults nuevos; test node con el ejemplo `Costo 100.33 / 16, 10, 21, 18, 15, 10`; feature test con etiqueta, texto y defaults.
  - Sin persistencia, migraciones ni dependencias nuevas.
- Nota sobre los ejemplos del usuario: con `Costo=100.33` y `16%`, Precio Público = `116.3828 → $116.38` (coincide). La cascada con redondeo por fila da `$116.38, $104.74, $82.74, $67.85, $57.67, $51.90`; los importes del pedido son `$104.73` y `$67.84`, es decir, 1 centavo distintos en dos filas. Se implementa la fórmula literal con redondeo a 2 decimales por fila; si el usuario quiere emular exactamente esos centavos hay que definir una regla de redondeo distinta.
- Archivos a tocar: `resources/views/admin/products/partials/form.blade.php`; `resources/js/admin/product-form.js`; `tests/product-form.test.js`; `tests/Feature/ProductPriceListsCalculatorTest.php`.
- Plan de rollback: copiar los cuatro archivos a `C:\\Users\\Administrador\\AppData\\Local\\Temp\\opencode\\backup-productos-calculadora-listas-03\\` antes de editar; si falla, restaurar copias y ejecutar `npm run build`. NO usar `git checkout` (hay trabajo sin commitear).
- Verificación planeada: `node --test tests/product-form.test.js`; `php artisan test --compact tests/Feature/ProductPriceListsCalculatorTest.php`; `vendor/bin/pint --dirty --format agent`; `npm run build`. Sin testing completo ni commits.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-productos-calculadora-listas-03. Sin desviaciones del plan aprobado.
- Cambios aplicados: cascada desde Costo con IVA en la primera fila y descuento sobre el precio anterior redondeado en las siguientes; defaults 16/10/21/18/15/10; etiqueta y texto nuevos; pruebas actualizadas con el ejemplo de 100.33.
- Verificación real: 12/12 node, 1/1 feature (44 aserciones), Pint y build app-Cwwn1UCV.js. Rollback en backup-productos-calculadora-listas-03.
