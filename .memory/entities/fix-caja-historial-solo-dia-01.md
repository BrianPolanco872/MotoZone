---
entity: fix-caja-historial-solo-dia-01
type: fix
---

- Objetivo: Limitar el historial de movimientos al día en curso y dejar los días anteriores solo en la vista de archivados.
- Archivos tocados: app/Http/Controllers/Pos/CashMovementController.php, resources/views/pos/cash/index.blade.php, tests/Feature/PosCashHistoryFiltersTest.php
- Estado: IMPLEMENTADO
- Cambios: `$day = $showArchived ? $requestedDay : today('America/Mexico_City')` en index/export; nuevo flag `filters.day_requested`; input Día visible solo con «Solo archivados»; subtítulo y vacío «del día»; tests actualizados (nuevo test de día por defecto y test de filtro por día en archivados).
- Verificación: PosCash* 30/30, Pint passed.
