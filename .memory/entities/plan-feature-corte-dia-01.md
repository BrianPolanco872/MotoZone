---
entity: plan-feature-corte-dia-01
type: plan
---

- Objetivo: Vista Corte del Día simplificada: empleado ve cuánto efectivo DEBE haber en caja (solo visualiza), puede finalizar el día para guardar corte inmutable y reiniciar contadores.
- Tipo: feature (Modo B)
- Alcance: Eliminar "Efectivo contado"/"Guardar conteo"/Sobran-Faltan. Paneles "Entradas de efectivo" y "Dinero en caja". Modales de detalle para Abonos de Clientes y Pagos a Proveedores. Botón "Finalizar día" con confirmación. Historial con filtros y permisos. Backend valida employee_id.
- Archivos afectados: Migraciones customer_payments/supplier_payments; Modelos CustomerPayment/SupplierPayment; StoreCashMovementRequest; CashMovementService; DailyCutService; DailyCutController; Admin\CajaController; routes/web.php; vistas pos/cuts/index, history, print; pos/cash/index.
- Decisiones tomadas con usuario: Cierre por empleado + cierre global admin; Tablas nuevas (no extender cash_movements); Nombre + FK opcional para clientes/proveedores; Dinero inicial desde Caja/POS; Turno derivado del corte; Capturar abonos/pagos en formulario de caja existente.
- Criterios de aceptación: Empleado ve solo sus datos; Admin ve todos; Paneles correctos; Modales solo lectura con Escape/Cerrar; Motivo obligatorio proveedor; Ver corte sin modificar; Finalizar día guarda inmutable y evita doble cierre; Backend ignora employee_id de no-admin; Historial filtrado.
