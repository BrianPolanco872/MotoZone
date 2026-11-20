---
entity: feature-productos-limpiar-con-historial-01
type: feature
---

- Tipo: feature; ampliación del contrato anterior, no diagnóstico ni fix del incidente actual de producción.
- Objetivo: Quiero que «Limpiar inventario» elimine todos los productos, tengan o no existencias o referencias.
- Alcance: borrado físico desde el botón de Productos, identificación histórica de los productos en movimientos, resolución de referencias, conservación de ventas/caja/cortes/movimientos, permisos y confirmación existentes, pruebas y respaldo previo.
- Fuera de alcance: borrar movimientos, incluso los ajustes automáticos antiguos; borrar ventas/caja/cortes/devoluciones/auditoría/cards/categorías; cambiar importación/costos/borrado individual; borrar imágenes físicas; resetear IDs; deshabilitar FKs; ejecutar limpieza real desde esta sesión; nuevas dependencias.
- Archivos/módulos aportados por usuario: Productos / Limpiar inventario; archivos concretos por confirmar durante revisión. Confirmado mediante pregunta de alcance en esta sesión.
- Criterios de aceptación: products vacío tras una limpieza válida, incluyendo productos con referencias; movimientos identificables y conservados; importes/cantidades/fechas/historial contable sin cambios; solo desvinculación prevista de referencias; permisos/CSRF/confirmación conservados; tabla vacía válida; fallo real revierte toda la operación; pruebas aisladas y respaldo previo verificado.
- Decisión arquitectónica confirmada por usuario: «Borrado real con historial», en lugar de ocultar/archivar productos. Se informó de la necesidad de migración, identificación histórica y referencias anulables.
- Solicitada por: usuario.
- Estado: REQUERIMIENTO Y ARQUITECTURA CONFIRMADOS; plan propuesto, sin autorización nueva de implementación ni ejecución de borrados reales.
- Antecedente: feature-productos-limpiar-tabla-01 / plan-productos-limpiar-tabla-01 / fix-productos-limpiar-tabla-01 preservaban la restricción ante movimientos reales y permitían eliminar ajustes antiguos específicos. Este requerimiento cambia ambos puntos: permitir borrar productos referenciados y conservar todos los movimientos.
- Fecha: 2026-11-20.
- Aprobación posterior: usuario responde «aplicalo» al plan propuesto en esta conversación (2026-11-20). Estado: APROBADA PARA IMPLEMENTACIÓN EN FUENTE, no ejecución de limpieza real.
- Estado: IMPLEMENTADO.
- Implementada en: fix-productos-limpiar-con-historial-01.
- Alcance real completado: código, migración y regresiones preparados para borrar productos preservando movimientos/identificación e historial, con permisos/confirmación/rollback. Sintaxis, Pint y revisión estática correctos.
- Pendiente de activación/aceptación: Testing solicitado explícitamente, respaldo de BD con restauración comprobada, validación del esquema/upgrade/concurrencia en clon MySQL y aplicación autorizada de migración. No se ejecutó limpieza real, no se afirma tabla vacía ni funcionalidad publicada.
