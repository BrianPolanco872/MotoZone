---
entity: feature-productos-modalidades-venta-01
type: feature
---

- Objetivo: Permitir elegir y conservar cómo se vende cada producto de MotoZone —por unidad/pieza, a granel o como paquete (kit)—, adaptando Productos, Inventario y POS sin alterar los campos ni el diseño actuales.
- Solicitada por: usuario. En esta etapa exclusivamente revisión y plan; no implementación, pruebas ejecutadas, migraciones aplicadas ni despliegue.
- Alcance: selector exclusivo debajo de Información del producto, default pieza, alta/edición persistidas; existencias/mínimos/cantidades decimales en granel; modal de cantidad/precio/importe/aceptar/cancelar al agregar o escanear granel; composición editable del kit, precio propio y casilla de descuento de componentes; consumo transaccional y compatibilidad con pendientes, devoluciones, tickets e inventario existente.
- Fuera de alcance: rediseño oscuro; cambiar reglas de precios, costo, IVA, mayoreo, códigos, imagen o publicación salvo adaptación imprescindible de cantidades; aplicar cambios; correcciones de bugs previos ajenos; compras/recepciones nuevas; conversiones de unidades; captura de granel por importe; kit anidado o venta/devolución de componentes separados como si fueran el kit; importación nueva de modalidades/recetas; dependencias nuevas.
- Decisiones explícitas del usuario en esta sesión: granel hasta 3 decimales (mínimo 0.001); kits admiten productos por pieza y granel, no otros kits ni autorreferencias; pendientes conservan composición y casilla guardadas y validan stock al cobrar sin reservarlo.
- Criterios de aceptación: selector visible con tres radios excluyentes; pieza por defecto e históricos sin reinterpretar; edición restaura modalidad, casilla y composición; pieza/kit vendidos con enteros; granel con hasta 3 decimales sin truncamiento; 0.5 a 14.10 produce 7.05; cancelar modal no cambia carrito; kit permite buscar/agregar/quitar y validar cantidades por componente; 3 kits con 2 bujías y 1 filtro consumen 6 y 3 con casilla activa, nunca el padre; casilla apagada no genera movimiento alguno; control agregado y atomicidad evitan sobreventa/doble descuento; pendientes, devoluciones y tickets mantienen modalidad/historia y precisión.
- Archivos/módulos afectados: Productos (formulario/request/controlador/modelo), migraciones, servicios de venta/precio/devolución, contratos POS y JS/Blade, modelos de cantidades, vistas de inventario/tickets/reportes, factories y pruebas. Lista y razones en plan-productos-modalidades-venta-01.
- Antecedentes: plan-productos-limpiar-con-historial-01 y su fix en fuente; plan-barcode2-productos-01; fix-importar-csv-barcodes-01; fix-preuso-motozone-lote3-01; fix-productos-costo-vacio-01. No existe feature equivalente localizada; granel/paquetes estaban fuera del MVP en feature-corte-dia.
- Estado: REQUISITOS Y TRES DECISIONES CONFIRMADOS; plan propuesto, sin autorización de implementación.
- Fecha: 2026-11-21.

- Estado: IMPLEMENTADO.
- Implementada en: fix-productos-modalidades-venta-01.
- Alcance de IMPLEMENTADO: fuente, migraciones preparadas y cobertura escrita; Testing/validación MySQL/migraciones/build/publicación pendientes. Ninguna escritura de base real ni operación de historial Git.

- Estado: IMPLEMENTADO en fuente y aplicado en la BD MySQL local el 2026-11-21; aceptación funcional del usuario pendiente. Detalle en fix-productos-modalidades-venta-01.
