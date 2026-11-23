---
entity: plan-pos-reabrir-corte-visibilidad-01
type: bug-fix
---

- Tipo: bug-fix
- Estado: APROBADO (usuario aprobó el 2026-11-22 la opción «Sí, aprobar e implementar»).
- Objetivo: el botón «Reabrir mi corte del día» del POS debe verse únicamente cuando el usuario ya cerró su propio corte del día; en cualquier otro caso debe estar oculto.
- Causa confirmada: en el CSS generado por Tailwind v4, `.hidden{display:none}` aparece antes que `.inline-flex{display:inline-flex}` (índices 39708 y 39787 de app-CPVW5sBK.css). Como ambas reglas tienen la misma especificidad, `inline-flex` gana y el botón se muestra siempre aunque la marca `hidden` esté presente en el HTML.
- Hallazgo adicional: el botón solo se mostraba dinámicamente cuando un intento de venta/cobro devolvía 422 con `day_closed_for_user`; al recargar la página con el corte propio cerrado volvía a ocultarse. `permissions.day_closed` hoy es el corte GLOBAL (`DailyCut::isDayClosed`), no el del usuario.
- Alcance: app/Http/Controllers/PosController.php (exponer `own_day_closed = DailyCut::isDayClosedForUser(today(), $user)`); resources/views/pos/index.blade.php (regla CSS específica `#pos-reopen-day.hidden{display:none}` y estado inicial del botón según `own_day_closed`); resources/js/pos/pos.js (mantener el toggle por 422, que sigue siendo válido); tests/Feature/PosReopenCutVisibilityTest.php (prueba focal: oculto con día abierto, visible con corte propio cerrado, admin con corte ajeno cerrado no lo ve).
- Plan de rollback: copiar los archivos a tocar a C:\Users\Administrador\AppData\Local\Temp\opencode\backup-pos-reabrir-corte-visibilidad-01\ antes de editar; restaurar y rebuildear con `npm run build` si falla. NO usar `git checkout` (hay trabajo sin commitear).
- Verificación planeada: prueba focal; `vendor/bin/pint --dirty --format agent`; `npm run build`. Sin testing completo ni commits.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-pos-reabrir-corte-visibilidad-01. Sin desviaciones del plan aprobado.
- Cambios aplicados: `own_day_closed` calculado con `DailyCut::isDayClosedForUser`; botón con `hidden` condicional y regla CSS con especificidad de ID; se conservó el toggle por 422. Prueba focal con 3 casos (día abierto, corte propio cerrado, corte ajeno cerrado).
- Verificación real: 3/3 focales, Pint y build app-uIAdEu89.js.
