---
entity: plan-productos-calculadora-listas-01
type: feature
---

- Tipo: feature
- Estado: APROBADO (usuario aprobó el 2026-11-22 la opción «Sí, aprobar e implementar»).
- Objetivo: agregar en Agregar/Editar Producto una tarjeta independiente debajo de «Publicación» que sirva solo como calculadora de referencia de listas de precios, sin modificar Precio de venta, Costo, IVA ni ganancias principales.
- Alcance confirmado por inspección: `resources/views/admin/products/partials/form.blade.php` es el partial compartido por create/edit; el panel «Publicación» termina en la línea 277 dentro del `div.lg:col-span-2`; los campos reales son `#cost`, `#margin`, `#price`, `#tax_rate`; el JS es `resources/js/admin/product-form.js`, importado por `resources/js/app.js`. No existe código de listas de precios previo ni `.ai/rules` en el proyecto.
- Diseño propuesto:
  - Tarjeta `<x-card padded>` nueva justo después de Publicación, con título «Listas de precios (referencia)», un selector de base «Precio venta / Precio costo» (el usuario pidió «Precio de venta o Precio costo») y botón «Calcular listas».
  - Tabla con columnas Lista, Porcentaje (%), Precio e IVA. Filas: Precio Público (default 16), Lista 1 / CESVE, TALLER; Lista 2 / MINORISTA; Lista 3 / SENIOR; Lista 4 / MASTER; Lista 5 / DISTRIBUIDOR, POWERSPORT. Las listas 1-5 arrancan vacías para que el usuario escriba su porcentaje.
  - Fórmula: `precio = base * (1 + porcentaje / 100)`; `iva = precio * tasa / (100 + tasa)`, donde `tasa` es `#tax_rate` del formulario. Con base = precio venta, porcentaje 16 y tasa 16, el IVA desglosado es exactamente `base * 0.16` y el precio es `base * 1.16`. Un porcentaje negativo funciona como descuento.
  - Los porcentajes son inputs sin `name` (no se envían al servidor) y las columnas Precio/IVA son celdas de solo lectura. El botón es `type="button"` y no hace submit.
  - Sin persistencia, migraciones ni dependencias nuevas.
- Archivos a tocar: `resources/views/admin/products/partials/form.blade.php`; `resources/js/admin/product-form.js`; `tests/product-form.test.js`; nuevo `tests/Feature/ProductPriceListsCalculatorTest.php`.
- Plan de rollback: copiar los tres archivos existentes a `C:\\Users\\Administrador\\AppData\\Local\\Temp\\opencode\\backup-productos-calculadora-listas-01\\` antes de editar; si falla, restaurar copias, borrar el test nuevo y ejecutar `npm run build`. NO usar `git checkout` (hay trabajo sin commitear).
- Verificación planeada: `node --test tests/product-form.test.js`; prueba feature focal (create y edit muestran las 6 filas y no envían campos nuevos); `vendor/bin/pint --dirty --format agent`; `npm run build`. Sin testing completo ni commits.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-productos-calculadora-listas-01. Sin desviaciones del plan aprobado.
- Cambios aplicados: tarjeta nueva en el partial compartido tras Publicación con selector de base, botón y tabla de 6 filas; handler en product-form.js usando `round` existente; pruebas node y feature. No se tocaron `#price`, `#cost`, `#margin` ni `#tax_rate`.
- Verificación real: 12/12 node, 1/1 feature (34 aserciones), Pint y build app-BWsaubvs.js. Rollback disponible en backup-productos-calculadora-listas-01.
