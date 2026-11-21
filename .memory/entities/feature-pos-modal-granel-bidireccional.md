---
entity: feature-pos-modal-granel-bidireccional
type: feature
---

- Objetivo: que el modal de captura previa de productos a granel del POS permita fijar la cantidad desde la cantidad o desde el importe en dinero, con validación de existencia y atajos de teclado.
- Alcance: solo el modal `#bulk-dialog` del POS. Título "¿Cantidad del Producto?", nombre y "Precio Unitario = $X" (respeta mayoreo y precio manual del renglón existente), cantidad decimal con autofoco y valor inicial 1.00, importe editable con prefijo `$` y cálculo bidireccional, alerta de existencia sin truncar, Enter acepta en cualquiera de los dos inputs, Esc y Cancelar descartan, foco de vuelta al buscador.
- Fuera de alcance (confirmado por el usuario): importe editable por renglón en la tabla del detalle; precios/mayoreo/IVA; modal de artículo libre; atajos globales nuevos; backend, payload, migraciones y datos.
- Criterios de aceptación: escanear o seleccionar granel abre el modal sin mutar el carrito; pieza y kit siguen agregando con 1; escribir cantidad recalcula importe; escribir importe recalcula cantidad redondeada a 3 decimales; $50.00 a $14.10 da cantidad 3.546 y el renglón usa esa cantidad; cantidad acumulada por encima de la existencia muestra alerta con el disponible y no agrega; Enter en cualquier input confirma; Esc y Cancelar no agregan; en ambos cierres el foco vuelve a "Escanear o buscar producto"; las 35 pruebas JS actuales siguen pasando.
- Solicitada por: usuario.
- Fecha: 2026-11-21.
