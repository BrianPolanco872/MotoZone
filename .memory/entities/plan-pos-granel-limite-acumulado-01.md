---
entity: plan-pos-granel-limite-acumulado-01
type: plan
---

- Tipo: bug-fix.
- Objetivo: Hacer visible el error del límite acumulado de granel sin cerrar el diálogo y alinear la prueba de edición con la validación de existencias al cobrar.
- Evidencia: En `51c88af` se reproducen ambos fallos. La edición acepta `2.751` con `2.750` de existencia mostrada según la política acordada. En `confirmBulk`, `closeBulkModal()` ocurre antes de `addProduct()`; este último rechaza correctamente el acumulado `9999.001`.
- Alcance de código: Solo `resources/js/pos/pos.js` y `tests/pos.test.js`. No tocar tickets, existencias, backend, configuración ni build.
- Paso 1: En la prueba de edición, comprobar que `2.751` se conserva y recalcula el importe aun cuando el stock mostrado sea `2.750`. Conservar casos realmente inválidos y la validación al cobrar.
- Paso 2: En la confirmación de granel, cerrar el diálogo solo si `addProduct` agregó la cantidad. Si el acumulado supera `9999`, mantener el diálogo y la cantidad capturada, mostrar allí el mensaje del límite y conservar intacto el renglón del carrito. Mantener el manejo de errores de otras vías de agregado.
- Paso 3: En la prueba del acumulado, usar existencias superiores a `9999`; partir de `9998.999`, comprobar que `0.002` no altera el renglón y mantiene el diálogo abierto con error visible, y que `0.001` llega a `9999` y lo cierra.
- Verificación: Ejecutar la suite completa de JavaScript con `node --test` y comunicar el resultado real. No ejecutar pruebas PHP ni generar artefactos.
- Reversión: Restaurar únicamente las líneas modificadas en ambos archivos; sin cambios de datos.
- Estado: Registrado a partir del alcance solicitado; pendiente de confirmar este plan antes de tocar código.
- Estado: IMPLEMENTADO
- Cambios aplicados: La prueba de edición acepta 2.751 con stock mostrado de 2.750. El diálogo conserva la captura y el carrito cuando 9998.999 + 0.002 supera el límite; muestra el error dentro y cierra cuando sumar 0.001 alcanza 9999. La prueba usa existencias de 10000. Suite JS completa: 71 pasaron, 0 fallaron.
- Desviaciones del plan original: Ninguna. Impresión, validación al cobrar y archivos de build sin cambios.
