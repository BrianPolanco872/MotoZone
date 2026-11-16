---
entity: feature-pos-iniciar-dia-reabrir-venta
type: feature
---

- Objetivo: Permitir iniciar el día en el POS con un botón centrado que registre solo el efectivo inicial, y reabrir la venta desde el módulo de corte cuando el turno del empleado ya fue finalizado.
- Alcance: botón centrado "Iniciar día" visible antes de iniciar; modal que solo pide monto; movimiento `initial` de caja; ocultar el botón al registrar; reflejo automático en el corte; botón "Reabrir venta" junto a "Finalizar día" para el dueño del turno; reapertura del corte de empleado y ventas normales de nuevo; regeneración de totales al volver a finalizar.
- Fuera de alcance: tabla/modelo formal de turnos, migraciones o cambios de esquema, cierre global distinto, nuevos permisos, impresión/reportes/historial, archivado de caja, bloqueo de ventas hasta registrar efectivo inicial, multi-sucursal o multi-caja, devoluciones.
- Criterios de aceptación:
  1. El POS muestra "Iniciar día" centrado antes de que el empleado registre su efectivo inicial del día.
  2. El modal pide únicamente el monto, sin motivo/concepto obligatorio.
  3. Al guardar se crea un `CashMovement` de categoría `initial`, tipo `in`.
  4. El botón desaparece y el monto se refleja en el corte como dinero inicial.
  5. Solo se permite un efectivo inicial por empleado por día; un segundo intento se rechaza en servidor.
  6. "Finalizar día" cierra el corte del empleado autenticado y guarda el corte final.
  7. Tras el cierre, "Reabrir venta" aparece junto a "Finalizar día" únicamente para el dueño del turno.
  8. Al reabrir, el empleado puede cobrar de nuevo; al volver a finalizar, el corte guarda los totales actualizados.
  9. "Reabrir venta" no aparece para otros empleados ni cuando el día global está cerrado.
  10. Suite de tests verde y Pint sin cambios pendientes.
- Decisiones confirmadas: cierre lógico por empleado reutilizando `daily_cuts`; "Iniciar día" no bloquea ventas; efectivo inicial una vez al día por empleado; "Reabrir venta" solo para el dueño en el módulo de corte; sin cambios de esquema.
- Solicitada por: usuario
- Fecha: 2026-11-08
- Estado: PLANEADA
