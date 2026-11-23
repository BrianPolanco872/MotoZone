---
entity: plan-pos-ultima-venta-pendientes-01
type: plan
---

- Tipo: feature
- Estado: APROBADO (usuario aprobó el plan el 2026-11-22, con decisiones: imprimir siempre la última venta cobrada; «Traer a mi panel» copia productos y deja el pendiente guardado).
- Objetivo: (1) mostrar «Pago con:» y «Cambio:» de la última venta en el panel inferior del POS; (2) renombrar el botón a «Reimprimir último ticket» (imprime la última venta cobrada); (3) «Dejar pendiente» guarda y limpia el panel sin navegar; (4) botón pequeño al lado con modal de tickets pendientes y acción «Traer a mi panel» que copia los productos al carrito como venta nueva editable.
- Alcance: resources/views/pos/index.blade.php; resources/js/pos/pos.js; app/Http/Controllers/Pos/SaleController.php; routes/web.php (endpoint JSON de pendientes si hace falta); tests/Feature/PosPendingSalesTest.php (nueva prueba focal); .memory/.
- No incluido: migraciones, cambios de BD, permisos, sidebar, corte, responsividad, testing completo (requiere solicitud explícita).
- Plan de rollback: antes de editar copiar los archivos tocados a C:\Users\Administrador\AppData\Local\Temp\opencode\backup-pos-ultima-venta-pendientes-01\; si falla, restaurar esas copias y rebuildear con npm run build. NO usar git checkout (todo está sin commitear y borraría trabajo anterior).
- Verificación planeada: vendor/bin/pint --dirty --format agent; prueba focal; npm run build.
- Estado: IMPLEMENTADO (2026-11-22). Implementado en fix-pos-ultima-venta-pendientes-01. Sin desviaciones del plan aprobado.
