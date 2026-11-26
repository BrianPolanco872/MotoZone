---
entity: feature-busqueda-codigo-exacto-pos-productos-01
type: feature
---

- Objetivo: En POS y Productos, si escribes o escaneas 039, solo debe aparecer el producto con ese código exacto.
- Alcance: Cambiar solo los filtros de los buscadores principales de POS y Productos para que los códigos de barras principal y secundario coincidan completos y se traten como texto.
- Fuera de alcance: Cambios a búsqueda por nombre o marca, controles o modos nuevos, otros buscadores, flujo de escaneo/agregado al carrito, datos, importación, precios, existencias y migraciones.
- Criterios de aceptación: Al escribir o escanear `039`, se devuelve solo su coincidencia exacta; quedan fuera `39`, `1039`, `039A` y coincidencias por nombre o marca que contienen `039`. Sin coincidencia de código para un término solo numérico no se sugieren productos. Un código alfanumérico existente se devuelve por coincidencia completa; cuando no se reconoce como código y tiene letras se conserva el comportamiento textual. Buscar por nombre o marca no cambia.
- Solicitada por: usuario.
- Estado: IMPLEMENTADO.
- Implementada en: fix-busqueda-codigo-exacto-pos-productos-01.
