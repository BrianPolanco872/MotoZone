---
entity: plan-corte-reabrir-dia-01
type: plan
---

- Objetivo: Cuando un empleado cierra su propio corte del día y luego intenta vender, mostrar aviso de día cerrado con botón para reabrir su corte de ese día y continuar vendiendo (mismo turno), sin afectar el corte global ni a otros empleados.
- Tipo: bug-fix
- Pasos:
    1. Agregar DailyCut::isDayClosedForUser() para verificar corte individual cerrado del empleado.
    2. Modificar SaleController para verificar corte del usuario antes de bloquear ventas; lanzar excepción identificable si está cerrado.
    3. Agregar DailyCutController@reopenMyDay para reabrir (closed_at = null) el corte del empleado autenticado para hoy, con auditoría.
    4. Agregar ruta POST /corte/reabrir-mi-dia protegida con auth, active y permiso de corte.
    5. Vista POS: mostrar aviso + botón "Reabrir mi corte del día" cuando el usuario tiene su corte cerrado.
    6. Probar flujo completo: cerrar día -> intentar vender -> ver aviso -> reabrir -> vender de nuevo.
- Archivos afectados: app/Models/DailyCut.php; app/Http/Controllers/Pos/SaleController.php; app/Http/Controllers/Pos/DailyCutController.php; routes/web.php; vista POS principal (por identificar).
- Criterios de aceptación: aviso con botón al intentar vender tras cierre propio; reabrir reactiva ventas; corte global no afectado; auditoría de reapertura.
- Riesgos: reabrir permite ventas en corte previo del mismo día; no afecta cierre global del admin.
- Plan de rollback: revertir SaleController a isDayClosed() global y eliminar ruta/método de reabrir.
- Estado: PLAN APROBADO (pendiente de implementación)
- Última actualización: 07/11/2026
