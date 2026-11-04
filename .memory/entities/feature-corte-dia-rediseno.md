---
entity: feature-corte-dia-rediseno
type: feature
---

- Objetivo: Rediseñar el módulo Corte del Día de MotoZone con dos paneles (dinero registrado en computadora y caja), conciliación con badge Cuadra/Diferencia, campo de efectivo contado físico, snapshots inmutables con versiones, y corte global = suma de cortes por empleado.
- Alcance: Dos paneles (computadora y caja), conciliación, campo efectivo contado físico (sobrante/faltante), snapshot inmutable al hacer corte, historial de snapshots, corte global = suma de cortes individuales, empleado solo ve/hace su corte, admin ve global y por empleado, re-hacer corte mismo día genera nueva versión sin borrar anterior (con motivo), impresión ticket térmico (58/80mm) y PDF, filtros fecha + empleado/global existentes.
- Fuera de alcance: Desglose por departamento como foco (sección secundaria colapsable solo), métodos de pago tarjeta/transferencia/mixto (FASE 3), archivado/eliminación de movimientos de caja (FASE 2), descuentos avanzados, mayoreo.
- Criterios de aceptación: (1) Día sin movimientos → todo $0.00 y "Cuadra". (2) Dinero inicial $500 + 3 ventas efectivo ($100, $250.50, $80) − salida $200 → $730.50 en ambos paneles. (3) Corte global = suma exacta de cortes individuales por empleado (prueba). (4) Empleado no puede ver corte de otro → 403. (5) Rehacer corte genera nueva versión, anterior se conserva. (6) Efectivo contado físico calcula sobrante/faltante y se guarda. (7) Badge verde "Cuadra" si subtotales coinciden, rojo con diferencia si no. (8) Impresión ticket térmico y PDF disponible.
- Solicitada por: usuario
- Última actualización: 2026-11-04
