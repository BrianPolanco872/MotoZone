---
entity: fix-preuso-motozone-lote2-01
type: fix
---

- Objetivo: lote 2 de plan-preuso-motozone-01, hallazgos #5/#6/#7/#8.
- Estado: IMPLEMENTADO; regresión integrada SQLite correcta; concurrencia multiproceso MySQL pendiente por falta de entorno aislado.
- Archivos tocados: app/Models/CashMovement.php; app/Http/Controllers/Admin/CajaController.php; app/Services/ReturnService.php; app/Services/DailyCutService.php; app/Http/Controllers/Pos/DailyCutController.php; tests/Feature/PosCashArchiveTest.php; tests/Feature/PosCashHistoryFiltersTest.php; tests/Feature/PosReturnsTest.php.
- Cambios aplicados: guard de movimiento por happened_at/cierre global efectivo, exportación heredada limitada por propietario, rechazo recargado/bloqueado en transacción, aprobación rechaza día cerrado y comparte coordinación por fecha con generar/cerrar corte global. Se captura una fecha/instante de aprobación coherente para evitar cruce de medianoche entre validación y escritura.
- Coordinación: reutiliza database cache/cache_locks ya migrados y configurados. Adquisición y liberación dentro de transacción de la conexión de locks, manteniendo la fila bloqueada hasta commit incluso si vence la lease; transacción de negocio incluida. Sin nuevas tablas, servicios externos o dependencias.
- Verificación: baseline core 13/50 correcto; regresiones core 5 fallos reproducidos y 9 casos/50 aserciones después correctos; regresiones caja 4 fallos #7 y 2 #8 reproducidos. Integración PosCashArchiveTest + PosCashHistoryFiltersTest + PosReturnsTest + PosCutTest + PosCutReconciliationTest: 64 pruebas / 251 aserciones correctas.
- Entorno: APP_ENV=testing, SQLite :memory:, DB_URL vacío, DB_CACHE_CONNECTION/DB_CACHE_LOCK_CONNECTION sqlite para core, sesión/cache array; vistas en temporal aislado cuando procede. Sin datos reales.
- Desviaciones: fixtures antiguos distinguen ahora snapshot generado de cierre efectivo con closed_at; se añadió pos.caja.view al helper que ya pretendía probar lectura de caja. No cambio de permisos productivos.
- Rollback: copias C:/Users/Administrador/AppData/Local/Temp/opencode/motozone-preuso-lote2-core-20261116-223314 y motozone-lote2-7-8-20261116-223206.
- Pendientes: no declarar validada concurrencia real, ni producción; #18 no tocado. Autorización actual permite seguir con lotes restantes sin nueva pausa de aprobación.
- Fecha: 2026-11-16.
