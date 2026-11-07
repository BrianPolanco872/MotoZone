---
entity: plan-caja-05
type: plan
---

- Tipo: feature
- Estado: IMPLEMENTADO
- Objetivo: Que la pantalla Caja (`/pos/caja`) muestre el avance del corte del día de cada empleado activo en una sola vista clara, con refresco automático cada 20–30 s, y sin los paneles que hoy compiten con esa información.
- Diagnóstico base: existen dos módulos de caja (`pos.cash.index` en `resources/views/pos/cash/index.blade.php` y `admin.caja.index` en `Admin\CajaController`); el panel "Por Empleado" del admin lista a todos los usuarios (no solo activos) y solo suma movimientos de caja; la pantalla del POS no tiene ningún panel de empleados. `DailyCutService::employeeBreakdown()` ya devuelve por empleado: status done/pending, latest_cut, tickets, sales, cash, refunds, cash_out y should_have, pero solo se usa en `pos/cuts/index.blade.php`.
- Pasos:
  1. Inyectar `DailyCutService` en `PosCashMovementController::index()` y pasar `$breakdown` a la vista (si el usuario no tiene `pos.sales.day.any`, filtrar a su propia fila).
  2. Crear partial `resources/views/pos/cash/partials/employee-cut-board.blade.php` con tabla, badges de sesión y corte, totales al pie y estado vacío (template único para SSR y polling).
  3. Agregar endpoint de solo lectura `GET /pos/caja/corte-empleados` (`pos.cash.cut-board`) en el grupo `permission:pos.cash.manage`; responde `{ html, generated_at, date, count }` reutilizando `employeeBreakdown()`.
  4. Reestructurar `resources/views/pos/cash/index.blade.php`: eliminar las 3 tarjetas KPI (líneas 90–104), insertar el panel de empleados arriba, filtros en contenedor colapsable con estado en `sessionStorage`, formulario "Registrar movimiento" colapsable conservando IDs y el script inline.
  5. Añadir barra "Actualizado HH:mm:ss · Actualizar" + polling inline cada 25 s con `AbortController`, pausa cuando `document.hidden` y refresco al volver a la pestaña.
  6. Unificar navegación: en `layouts/admin.blade.php` el ítem "Caja" apunta a `pos.cash.index`; en `routes/web.php` convertir `admin.caja.index` y `admin.caja.corte` en redirects con query string a `pos.cash.index` y `pos.cuts.index`; dejar `admin.caja.archive/destroy/export` sin enlaces (deprecados).
  7. Verificar en 3 escenarios (sin actividad, con ventas, con corte hecho) que las cifras coinciden con `pos.cuts.index`; correr tests de caja/corte y `npm run build` si se edita JS compilado.
- Archivos afectados:
  - `app/Http/Controllers/Pos/CashMovementController.php` (breakdown + método `cutBoard`).
  - `resources/views/pos/cash/partials/employee-cut-board.blade.php` (nuevo).
  - `resources/views/pos/cash/index.blade.php` (reestructura, colapsables, polling).
  - `routes/web.php` (ruta nueva + redirects).
  - `resources/views/layouts/admin.blade.php` (ítem "Caja" → `pos.cash.index`).
  - `app/Services/DailyCutService.php` y `app/Services/PresenceService.php` (sin cambios; se reutilizan).
- Criterios de aceptación: ver `feature-caja-corte-empleados`.
- Plan de rollback: revertir `pos/cash/index.blade.php`, `CashMovementController.php`, `routes/web.php` y `layouts/admin.blade.php`; eliminar el partial nuevo y la ruta `pos.cash.cut-board`; restaurar la ruta del menú. Sin migraciones ni cambios de datos, el rollback es limpio.
- Riesgos: `employeeBreakdown()` ejecuta `build()` por empleado (N+1) — aceptable con 1–5 empleados, cachear 15 s si crece; si se dejan vivos los `admin.caja.*` la duplicación vuelve; el filtro por permisos debe aplicarse en el controlador, no solo en la vista; pausar polling con pestaña oculta.
- Alternativas descartadas: panel en `/admin/caja` (módulo duplicado); JSON + render en JS (duplica markup); SSE/WebSockets (requiere proceso persistente); polling 60 s (menos sensación de vivo sin ahorro relevante).
- Contrato hacia 03-fix: leer esta entidad en `.memory/entities/plan-caja-05.md`.
- Estado: IMPLEMENTADO
- Cambios aplicados: panel "Corte del día por empleado" en `/pos/caja` con polling de 25 s; partial único `pos/cash/partials/employee-cut-board.blade.php`; endpoint `pos.cash.cut-board`; KPIs eliminadas; filtros y formulario colapsables; menú "Caja" unificado y `admin.caja.*` redirigido.
- Desviaciones del plan original: rutas `pos.cash.*` movidas a un grupo propio con `permission:pos.cash.manage`; `concept` opcional en este formulario; redirect de `admin.caja.corte` condicionado a `pos.cut.manage`; cache de rutas regenerado.
- Última actualización: 2026-11-06
