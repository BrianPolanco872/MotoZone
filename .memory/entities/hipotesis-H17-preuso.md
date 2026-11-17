---
entity: hipotesis-H17-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: resources/js/pos/pos.js:1073-1118.
- Mecanismo: Escape invoca resetSale salvo si están abiertos pago o caja; no protege custom-product-dialog/start-day-dialog. Otros atajos usan el mismo guard incompleto.
- Verificación: condiciones, reset y diálogos leídos; no reproducción DOM. Debe probarse Escape y edición de campos sin perder carrito.
