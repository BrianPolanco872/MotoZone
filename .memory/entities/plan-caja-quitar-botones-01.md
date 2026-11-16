---
entity: plan-caja-quitar-botones-01
type: plan
---

- Tipo: bug-fix (ajuste de UI)
- Objetivo: Quitar de la barra de acciones del módulo de caja (pos/cash/index) los botones "Exportar CSV" y "Registrar movimiento".
- Archivos en alcance: resources/views/pos/cash/index.blade.php
- Aprobado por el usuario en la conversación: 2026-11-09
- Estado: IMPLEMENTADO
- Cambios aplicados: se eliminaron los botones "Exportar CSV" (enlace a pos.cash.export) y "Registrar movimiento" (toggle de cash-register) de la barra de acciones superiores de resources/views/pos/cash/index.blade.php.
- Desviaciones del plan original: ninguna.
