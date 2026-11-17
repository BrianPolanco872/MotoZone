---
entity: bug-preuso-11
type: bug
---

- Auditoría: #11. Cambiar a tarjeta o transferencia bloquea confirmar cobro.
- Estado: CAUSA RAÍZ CONFIRMADA por trazado de eventos JS; sin navegador.
- Evidencia: resources/js/pos/pos.js:859-868,1122-1134.
- Caso: abrir cobro de $100 y cambiar a card/transfer oculta el importe y lo pone a cero; confirmar retorna por pago insuficiente.
- Causa confirmada: hipotesis-H11-preuso.
- Fecha: 2026-11-16. Sin implementación.
