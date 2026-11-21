---
entity: plan-pos-modal-granel-bidireccional
type: plan
---

- Tipo: feature.
- Estado: PROPUESTO; pendiente de aprobación explícita para implementar.
- Objetivo: completar el modal de granel del POS con título/encabezado nuevos, importe editable y cálculo bidireccional, validación visible de existencia y atajos Enter/Esc con retorno de foco.
- Fecha: 2026-11-21.

## Punto de partida verificado

- El disparador ya existe: `resources/js/pos/pos.js` `addProduct()` intercepta `sale_mode === 'bulk'` y abre `#bulk-dialog` sin mutar el carrito; pieza y kit siguen directos con cantidad 1.
- El modal actual (`resources/views/pos/index.blade.php` 605-632) ya tiene nombre, precio, cantidad `step=0.001`, autofoco con select, importe calculado como `<output>` de solo lectura, Enter por submit y Esc nativo del `<dialog>`.
- Causa del síntoma reportado ("se agrega con cantidad 1"): el bundle `public/build/assets/app-DGFmVHf8.js` es del 20/11 12:04 y no contiene `bulk-dialog`; `pos.js` es del 21/11 16:06. Falta `npm run build` (o `npm run dev`). No es un defecto del código fuente.
- Contrato de cantidades vigente: granel hasta 3 decimales; el exceso de precisión se rechaza, no se trunca. `lineTotal()` redondea a centavos; `stockFor()` devuelve existencia derivada del kit o `available_stock ?? stock` solo con `track_inventory`.

## Pasos de implementación

1. Vista: título `¿Cantidad del Producto?` y encabezado `Precio Unitario = $X` reutilizando `#bulk-name` y `#bulk-price`; añadir `#bulk-stock` con el disponible.
2. Vista: sustituir el `<output id="bulk-total">` por `<input id="bulk-amount">` numérico con prefijo `$` visible, `inputmode=decimal`, `step=0.01`, `min=0`.
3. Vista: cantidad con `value="1.00"`, `step=0.001`, `max` dinámico desde JS; botón de envío pasa a `Aceptar`.
4. JS: refs de `bulkAmount` y `bulkStock`; helper `openBulk(product)` que fija cantidad 1.00, importe = precio y enfoca/selecciona la cantidad.
5. JS: dos handlers con origen explícito (bandera de sincronización para no entrar en bucle): cantidad → importe = `lineTotal`, importe → cantidad = `round(amount / price * 1000) / 1000` (3 decimales, medio hacia arriba). Si el precio es 0 o el importe no es válido, no se recalcula y se muestra el error.
6. JS: precio unitario tomado de `items.find(...)?.unit_price ?? priceFor(product)` para respetar mayoreo y precio manual del renglón ya existente.
7. JS: validación previa al submit: cantidad válida (0.001–9999, ≤3 decimales) y cantidad acumulada (renglón existente + nueva) ≤ existencia; si excede, mostrar `Disponible: X` en `#bulk-error` y dejar el modal abierto, sin recortar el valor.
8. JS: submit (Enter en cualquiera de los dos inputs) agrega con la cantidad como fuente de verdad y el importe recalculado; cerrar y enfocar el buscador. `close` del dialog mantiene el retorno de foco.
9. Pruebas JS: añadir `bulk-amount` y `bulk-stock` al DOM falso de `tests/pos.test.js`; cubrir importe→cantidad, redondeo a 3 decimales, exceso de existencia sin mutar el carrito, Enter desde el importe y Esc sin agregar.
10. Compilar con `npm run build` para que el cambio llegue al navegador y volver a correr `node --test tests/pos.test.js tests/product-form.test.js`.

## Archivos a tocar

- `resources/views/pos/index.blade.php`: título, encabezado, inputs de cantidad/importe, disponible y etiqueta del botón.
- `resources/js/pos/pos.js`: cálculo bidireccional, validación de existencia y flujo de apertura/confirmación.
- `tests/pos.test.js`: DOM falso y casos nuevos del modal.
- `.memory/`: esta entidad, la feature y las relaciones.

## Plan de rollback

- Revertir los tres archivos y volver a compilar con `npm run build`. No hay cambios de esquema, datos, payload ni contratos de API, por lo que no requiere conciliación de BD.

## Riesgos y dependencias

- Redondeo aceptado: al teclear importe, la cantidad a 3 decimales puede dejar el importe del renglón hasta un centavo por debajo o por encima de lo tecleado; la cantidad es la fuente de verdad al confirmar.
- Entradas parciales del input numérico (`1.`, vacío) deben ignorarse sin borrar el otro campo.
- El stock se valida contra la cantidad acumulada del renglón, no solo contra la captura del modal.
- Productos sin control de inventario no se limitan.
- Depende de que el bundle se recompile; sin build el síntoma original persiste.

## Alternativas consideradas

- Reimplementar el modal desde cero: descartado, ya existe y está cubierto por pruebas.
- Truncar en lugar de redondear: descartado por decisión del usuario (redondeo a 3 decimales).
- Rechazar importes no exactos: descartado por fricción en caja.
