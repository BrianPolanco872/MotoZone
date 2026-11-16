---
entity: fix-caja-quitar-botones-01
type: fix
---

- Objetivo: Quitar los botones "Exportar CSV" y "Registrar movimiento" de la barra de acciones del módulo de caja.
- Archivos tocados: resources/views/pos/cash/index.blade.php
- Estado: IMPLEMENTADO
- Notas: la sección colapsable del formulario "Registrar movimiento" (id=cash-register) y la ruta pos.cash.store se mantienen en el código, pero ya no son accesibles desde la UI.
- Verificación: php artisan view:cache compiló todas las vistas correctamente.
