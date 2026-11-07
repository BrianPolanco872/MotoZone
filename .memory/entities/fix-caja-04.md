---
entity: fix-caja-04
type: fix
---

- Objetivo: Hacer opcional el concepto en los movimientos manuales de caja y mostrar los errores de validación dentro del modal Entrada/Salida de efectivo del POS, para que un movimiento rechazado no se pierda en silencio.
- Archivos tocados: app/Http/Requests/Pos/StoreCashMovementRequest.php, app/Services/CashMovementService.php, resources/views/pos/index.blade.php, resources/js/pos/pos.js
- Cambios aplicados: `concept` a `nullable` y eliminado el mensaje `concept.required`; quitada la validación obligatoria del concepto en `CashMovementService`; contenedor `#cash-error` dentro de `#cash-dialog`; submit por AJAX con `FormData` + `Accept: application/json` + `redirect: manual`, errores 422 renderizados en el modal y redirección a `pos.cash.index` al guardar; guard anti doble envío y limpieza de estado al cerrar el diálogo.
- Verificación: `php -l` OK, `php artisan view:cache` OK, `npm run build` OK.
- Nota: `vendor/bin/pint --dirty` normalizó además el formato de app/Http/Controllers/Admin/CajaController.php (ya estaba modificado en el árbol de trabajo).
- Fecha: 2026-11-06
