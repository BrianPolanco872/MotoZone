---
entity: fix-sidebar-orden-modulos-y-sin-panel-01
type: fix
---

- Objetivo: reordenar el sidebar del panel y quitar el módulo "Panel" (ya no se usa, menos carga). Orden pedido: Punto de venta → Corte del día → Caja → Abonos → Empleados → Tickets → Bitácora → Mi perfil (último).
- Archivos tocados: resources/views/layouts/admin.blade.php; tests/Feature/TicketSettingsTest.php.
- Cambio aplicado: se eliminó el bloque que agregaba 'Panel' (route admin.dashboard) al array $adminNavigation. Se movió 'Bitácora' fuera de la rama de 'Corte del día' y se colocó entre 'Tickets' y 'Mi perfil', con la condición equivalente pos.cut.manage + pos.access. Se invirtieron 'Empleados' y 'Tickets' para que Empleados quede antes. Se mantuvieron Productos, Editar inicio y el separador 'Operación' (no pidieron quitarlos) y las condiciones de permisos de cada ítem.
- Orden final: Productos, Editar inicio, [Operación], Punto de venta, Corte del día (o Mi corte), Caja, Abonos / Créditos, Empleados, Tickets, Bitácora, Mi perfil.
- Verificación: TicketSettingsTest 20/20 con assertSeeInOrder de la secuencia completa; PosCutTest 14/14; Pint pasa. Sin build (solo Blade y un test).
- Fallos preexistentes confirmados (no corregidos): los 3 de PosBitacoraTest (filtros sin datos, paginación 0 vs 8 y mock de Pdf::loadView) siguen igual; las 2 pruebas de Bitácora que verifican visibilidad del enlace siguen pasando tras el movimiento.
- Nota: $panelHome (enlace del logo) sigue apuntando a admin.dashboard; solo se quitó el ítem del menú, como se pidió.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
