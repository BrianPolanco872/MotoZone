---
entity: hipotesis-H11-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: resources/js/pos/pos.js:570-608,859-868,1122-1134.
- Mecanismo: change del medio fija paymentAmount=0 en medios electrónicos; click de confirmar compara ese mismo valor con dataset.total sin distinguir el medio. Los tests HTTP envían importes explícitos y no ejercitan esta secuencia.
- Verificación: apertura, cambio, cálculo y envío JS revisados; node --check pasó, pero eso no ejecuta comportamiento DOM.
