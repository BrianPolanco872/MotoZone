---
entity: plan-pos-pendientes-eliminar-01
type: feature
---

- Tipo: feature
- Estado: APROBADO (usuario aprobó el 2026-11-22; eligió «Cancelarlo»: conserva historial/auditoría, sin borrado físico).
- Objetivo: agregar un botón «Eliminar» por ticket en el modal de pendientes del POS que cancele el pendiente usando el endpoint existente `pos.sales.cancel`, visible solo si el cajero tiene `pos.cancel.sale`; Confirmar antes de cancelar y refrescar la lista.
- Alcance: resources/views/pos/index.blade.php (ruta cancel en pos-config); resources/js/pos/pos.js (botón + handler DELETE); tests/Feature/PosPendingSalesTest.php (prueba focal). Sin controlador, ruta ni BD nuevos.
- Plan de rollback: copiar esos tres archivos a C:\Users\Administrador\AppData\Local\Temp\opencode\backup-pos-pendientes-eliminar-01\ antes de editar; si falla, restaurar y rebuildear con npm run build. NO usar git checkout (trabajo sin commitear).
- Verificación planeada: Pint; prueba focal; npm run build.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-pos-pendientes-eliminar-01. Sin desviaciones del plan aprobado.
