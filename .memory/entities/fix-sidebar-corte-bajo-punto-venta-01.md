---
entity: fix-sidebar-corte-bajo-punto-venta-01
type: fix
---

- Objetivo: en el sidebar del panel, mover el módulo "Corte del día" para que quede justo debajo de "Punto de venta".
- Archivos tocados: resources/views/layouts/admin.blade.php; tests/Feature/TicketSettingsTest.php.
- Cambio aplicado: se movió el bloque completo del corte (Corte del día + Bitácora cuando aplica, o Mi corte en la rama elseif) desde su posición original hasta inmediatamente después del bloque de "Punto de venta". Orden final: Panel/Productos/Editar inicio/Operación → Punto de venta → Corte del día (+ Bitácora o Mi corte) → Caja → Abonos/Créditos → Tickets → Empleados → Mi perfil. No se cambió ninguna condición de permisos, solo el orden del array $adminNavigation.
- Verificación: TicketSettingsTest 20/20 (asegura assertSeeInOrder ['Punto de venta', 'Corte del día', 'Tickets', 'Empleados']); PosCutTest 14/14; Pint pasa. Sin build: solo Blade y un test, sin JS/CSS.
- Fallos no relacionados confirmados en aislamiento (preexistentes, no corregidos): PosAccessTest.php:35 espera "La venta está vacía" en el carrito del POS y no aparece; PosBitacoraTest 2/5 con 3 fallos (filtros de fecha/empleado sin datos, paginación 0 vs 8, y mock de Pdf::loadView('pos.bitacora.pdf', ...) sin handler). Ninguno depende del orden del sidebar: se probaron sueltos y siguen fallando igual.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
