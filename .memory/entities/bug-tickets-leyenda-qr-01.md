---
entity: bug-tickets-leyenda-qr-01
type: bug
---

- Síntoma: el usuario no configuró la leyenda «Facturación / Soporte», pero aparece al activar el QR y sí sale en el papel. Pide quitarla y conservar el QR.
- Causa confirmada: `hipotesis-H1-tickets-leyenda-qr-01`; la leyenda está escrita directamente en las dos vistas.
- Incidencia física relacionada, aún sin causa confirmada: el QR se ve en la vista previa de Chrome con 5SPRINTER seleccionada pero no en papel, aunque su leyenda inferior sí se imprime. Esto hace improbable un recorte general al final, pero no distingue el envío de gráficos desde Chrome del controlador ni otros ajustes de la impresora.
