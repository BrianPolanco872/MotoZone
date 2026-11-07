---
entity: feature-caja-corte-empleados
type: feature
---

- Objetivo: Mostrar en la pantalla Caja (`/pos/caja`) el avance del corte del día de cada empleado activo (en línea / con actividad / ya hizo corte), con sus totales y actualización automática, eliminando los paneles que compiten con esa información.
- Alcance: Reutilizar `DailyCutService::employeeBreakdown()` + `PresenceService::onlineUsers()`; panel "Corte del día por empleado"; endpoint de solo lectura + polling 20–30 s; quitar las 3 tarjetas KPI; filtros y formulario colapsables; unificar navegación (`admin.caja.index` y `admin.caja.corte` redirigen a las pantallas del POS).
- Fuera de alcance: Generar/cerrar cortes desde Caja; cambiar fórmulas de `DailyCutService::build()`; WebSockets/SSE; edición de movimientos; retiro definitivo de `Admin\CajaController`.
- Criterios de aceptación: (1) solo empleados con actividad hoy o corte generado; (2) fila con sesión, estado de corte, tickets, ventas, efectivo, saldo esperado, diferencia y "Ver corte"; (3) cifras coinciden con `pos.cuts.index?employee=ID&date=hoy`; (4) total del pie coincide con corte global; (5) refresco cada 25 s sin recargar; (6) empleado sin `pos.sales.day.any` solo ve su fila; (7) estado vacío claro si nadie tuvo actividad; (8) `admin.caja.index`/`admin.caja.corte` dejan de ser destinos navegables.
- Decisiones confirmadas 2026-11-06: panel en `/caja` del POS; simplificar todos los paneles (KPIs, filtros, formulario, módulo duplicado); polling 20–30 s; mostrar todos los datos por empleado.
- Solicitada por: usuario
- Estado: IMPLEMENTADO
- Implementada en: fix-caja-05
- Última actualización: 2026-11-06
