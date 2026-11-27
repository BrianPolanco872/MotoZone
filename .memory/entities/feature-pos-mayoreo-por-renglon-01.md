---
entity: feature-pos-mayoreo-por-renglon-01
type: feature
---

- Objetivo: Aplicar o quitar Mayoreo únicamente al renglón que el cajero seleccione en el carrito del POS.
- Criterios de aceptación: Sin selección, Mayoreo no actúa. Seleccionar un renglón y marcar Mayoreo solo cambia su precio; al seleccionar otro se muestra el estado del nuevo renglón sin cambiar precios. El cobro y el guardado de pendientes envían el indicador de mayoreo por línea y siguen las reglas de permiso actuales.
- Fuera de alcance: Precios de catálogo, inventario, datos de ventas pasadas, impresión y cambios al modelo de caja.
- Estado: IMPLEMENTADO
- Implementada en: fix-pos-mayoreo-por-renglon-01
