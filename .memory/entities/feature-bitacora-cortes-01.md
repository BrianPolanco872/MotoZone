---
entity: feature-bitacora-cortes-01
type: feature
---

- Objetivo: Crear una Bitácora de solo consulta para buscar cortes guardados por fecha y empleado o por el corte global, verlos paginados de 8 en 8 y exportar los resultados filtrados en un PDF consolidado.
- Alcance: Nueva pantalla Bitácora separada del historial existente; filtro de una fecha opcional y selector «Todos (global)» o empleado; listar snapshots persistidos de DailyCut; mostrar 8 cortes por página; exportar todos los resultados coincidentes en un PDF consolidado, con datos de snapshot, estructura legible y varias páginas cuando haga falta; incluir empleados históricos inactivos o con soft delete en el selector.
- Fuera de alcance: Cambiar cálculos, creación, cierre, edición o eliminación de cortes; modificar el historial existente; exportar a CSV/Excel; crear cortes que no estén persistidos.
- Criterios de aceptación: (1) Usuarios autorizados pueden consultar la Bitácora y usuarios sin permiso reciben la respuesta de autorización del proyecto. (2) La fecha limita los resultados al día seleccionado; sin fecha se consulta el historial disponible. (3) «Todos (global)» muestra únicamente snapshots de scope global; elegir un empleado muestra únicamente sus snapshots de scope employee. (4) La lista pagina exactamente 8 registros y conserva filtros al navegar. (5) Un PDF consolidado incluye todos los registros coincidentes, no solo los de la página actual, con filtros y datos identificables por corte/versión. (6) La Bitácora no presenta ni ejecuta acciones para modificar o borrar cortes y no recalcula snapshots históricos.
- Solicitada por: usuario
- PDF: Un PDF consolidado, decisión confirmada por el usuario el 2026-11-16.
- Estado: IMPLEMENTADO
- Implementada en: fix-bitacora-cortes-01
