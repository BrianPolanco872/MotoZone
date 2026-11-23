---
entity: fix-productos-calculadora-listas-03
type: fix
---

- Objetivo: convertir la calculadora en cascada de proveedor: Precio Público = Costo * (1 + IVA%/100); L1..L5 = Precio anterior * (1 - Desc%/100), con redondeo a 2 decimales por fila.
- Archivos tocados: resources/views/admin/products/partials/form.blade.php; resources/js/admin/product-form.js; tests/product-form.test.js; tests/Feature/ProductPriceListsCalculatorTest.php.
- Cambios: etiqueta «IVA / Desc. (%)»; defaults 16/10/21/18/15/10; texto inferior «Precio Público suma IVA al costo; las listas L1 a L5 aplican descuento en cascada sobre la lista anterior.»; cascada implementada en el handler reutilizando `round`. Sin tocar campos principales ni persistir.
- Verificación: 12/12 pruebas node (ejemplo 100.33 → 116.38, 104.74, 82.74, 67.85, 57.67, 51.90), 1/1 feature (44 aserciones), Pint y build app-Cwwn1UCV.js.
- Nota: el usuario aceptó que dos cifras de su ejemplo difieren en $0.01 de la fórmula literal con redondeo por fila (104.73 y 67.84 en el pedido).
- Estado: IMPLEMENTADO (2026-11-22). Sin desviaciones del plan aprobado.
