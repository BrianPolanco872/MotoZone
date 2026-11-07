---
entity: fix-corte-reabrir-dia-01
type: fix
---

- Objetivo: Empleado que cerró su propio corte del día puede reabrirlo para seguir vendiendo el mismo día, sin afectar el corte global.
- Archivos tocados:
    1. app/Models/DailyCut.php — agregado isDayClosedForUser()
    2. app/Http/Controllers/Pos/SaleController.php — verificar corte individual antes del global; respuesta JSON con day_closed_for_user y reopen_url
    3. app/Http/Controllers/Pos/DailyCutController.php — agregado reopenMyDay()
    4. routes/web.php — ruta POST /corte/reabrir-mi-dia -> cuts.reopen-my-day
    5. resources/views/pos/index.blade.php — botón "Reabrir mi corte del día" oculto + ruta en config
    6. resources/js/pos/pos.js — detectar day_closed_for_user, mostrar botón, función reopenMyDay() con fetch POST
- Cambios aplicados:
    1. isDayClosedForUser verifica corte SCOPE_EMPLOYEE cerrado del usuario para hoy.
    2. SaleController@store y @charge: si el usuario tiene su corte cerrado, retorna 422 con day_closed_for_user=true y reopen_url en lugar de bloquear genérico.
    3. DailyCutController@reopenMyDay reabre (closed_at=null) el corte de empleado del usuario autenticado para hoy, con auditoría.
    4. Ruta protegida con auth, active y role_or_permission:pos.cut.manage|pos.cut.own.
    5. Vista POS muestra botón de reabrir solo cuando el error indica día cerrado para el usuario.
    6. JS hace POST a la ruta de reabrir y recarga la página al éxito.
- Estado: IMPLEMENTADO
- Última actualización: 07/11/2026
