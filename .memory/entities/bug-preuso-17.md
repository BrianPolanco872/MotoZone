---
entity: bug-preuso-17
type: bug
---

- Auditoría: #17. Escape al cancelar ciertos diálogos también vacía el carrito.
- Estado: CAUSA RAÍZ CONFIRMADA por handler global JS; sin navegador.
- Evidencia: resources/js/pos/pos.js:1073-1118.
- Caso: carrito con productos, abrir Artículo libre y pulsar Escape cumple el guard global porque solo contempla payment-dialog/cash-dialog.
- Causa confirmada: hipotesis-H17-preuso.
- Fecha: 2026-11-16. Sin implementación.
