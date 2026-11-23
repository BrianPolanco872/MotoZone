---
entity: feature-pos-vista-amplia-existencias-01
type: feature
---

- Objetivo: simplificar el punto de venta, ampliar y estabilizar Detalle de la venta, mostrar resultados de productos como la referencia y consultar existencias por ubicación con ampliación de foto.
- Alcance: retirar paneles de atajos, turno, subtotal e IVA; ocultar sidebar solo en pos.index; detalle amplio estable; tabla de búsqueda con foto/código/producto/categoría/precio/cantidad/importe/tienda/bodega, sin Acción; campos opcionales Bodega y Tienda 2 en alta y edición; foto ampliable en diálogo cerrable.
- Fuera de alcance: gestión de sucursales, traspasos, descuentos automáticos de bodega o Tienda 2, nuevas dependencias, recuperar CRUD de categorías, modificar cálculos de IVA/cobro/tickets, CSV de campos nuevos, correcciones de bugs anteriores o rediseños ajenos.
- Criterios de aceptación: paneles solicitados ausentes; sidebar ausente solo en POS; detalle no cambia de tamaño al buscar/agregar; tabla con columnas solicitadas sin Acción; selección agrega cantidad sin duplicaciones; fotos amplían y cierran sin alterar carrito; tienda usa stock actual; bodega/Tienda 2 se guardan manualmente; vacío permanece NULL y visualmente vacío, cero explícito se muestra; Tienda 2 no aparece en POS si no hay valores en la tabla visible; conservar granel/kits/pendientes/permisos.
- Archivos/módulos afectados: Punto de venta y Productos; usuario confirmó archivos por determinar mediante revisión.
- Decisión explícita mediante question: reutilizar existencia actual como Tienda; Bodega y Tienda 2 opcionales, editables y solo de consulta, sin descuentos automáticos al cobrar. Usuario eligió «Sí, así de sencillo».
- Solicitada por: usuario.
- Fecha: 2026-11-22.
- Estado: REQUERIMIENTO CONFIRMADO; solo planificación, sin autorización para implementar.
- Aprobación posterior del usuario: «si esta bien aplicalo».
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-01.
- Alcance del estado: implementada en fuente local, sin migración real, build ni Testing ejecutados. Build bloqueado por tag/versionado; no publicada.
- Ajuste posterior solicitado: buscador sin Cantidad/Importe/Acción, selección por fila y detalle fijo con estado vacío centrado; sustituye el criterio anterior de cantidades en buscador.
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-02 (ajuste visual; misma limitación de build pendiente).
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-03 (total compacto, fotos de filtro 80px e información más espaciosa; build pendiente por versiones desalineadas).
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-04 (sincronización de versión 1.0.1 y build local completado; bloqueo previo resuelto). Sin Testing, navegador ni despliegue.
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-05 (campos completos del producto en Detalle de la venta, foto96px, columna Tienda 2 condicional; build local completado). Sin Testing ni navegador.
- Estado: IMPLEMENTADO.
- Implementada en: fix-pos-vista-amplia-existencias-06 (detalle ocupa espacio libre y cobro al borde inferior con margen8px; build completado). Sin Testing ni navegador.
