---
entity: feature-pos
type: feature
---

- Objetivo: Implementar un módulo de Punto de Venta (POS) integrado en el sistema actual, con catálogo público, venta rápida, tickets, pendientes, movimientos de efectivo, ventas del día y devoluciones controladas.
- Alcance: reutilizar el catálogo, productos, stock y layout administrativo existentes; agregar autenticación/roles mínimos, búsqueda por código, carrito, cobro, ticket imprimible, tickets pendientes, movimientos de caja, consulta de ventas del día y solicitudes de devolución.
- Fuera de alcance: corte de caja (solo documentado), creación automática de productos desde conceptos manuales, devoluciones con efecto en inventario/dinero sin reglas aprobadas, otros métodos de pago en el MVP, módulo de clientes con cuentas.
- Criterios de aceptación: rutas protegidas por rol; venta confirmada en transacción con folio único e idempotencia; stock y precios validados en servidor; ticket con folio/fecha/productos/totales/pago/cambio; pendientes sin descontar stock; movimientos de efectivo con usuario/fecha/motivo; ventas del día desde BD; devoluciones sin exceder lo vendido.
- Solicitada por: usuario
- Fecha: 2026-10-26
- Repositorio analizado: C:\Users\Administrador\Documents\MotoZone
- Hallazgo clave: el repositorio es MotoZone (Laravel 13.38, PHP 8.3, Blade, Tailwind v4, JS vanilla, MySQL y SQLite en tests), no "Bs Hotel".
- Hallazgo clave: no existe autenticación, roles, permisos, módulo de clientes, ventas, caja, código de barras, imágenes, costos ni movimientos de inventario.
- Hallazgo clave: ya existe catálogo público (`catalog.index`, `catalog.show`) y layout administrativo con entrada "Ventas" marcada como "Pronto".
- Decisiones aprobadas 2026-10-26: solo repositorio MotoZone; usar spatie/laravel-permission (8.3.2 compatible con Laravel 13) para roles/permisos; proteger /admin; configuración de mayoreo desde admin; movimientos de efectivo sin sesión de caja; devoluciones solo solicitud; nueva columna barcode; image_path opcional; folio V-000001; datos del ticket desde config/pos.php; admin puede otorgar a empleado permiso de edición de precios; solo efectivo en MVP con estructura para tarjeta/transferencia; empleado ve sus propias ventas y admin todas; timezone America/Mexico_City; precios con IVA incluido mostrado como en catálogo, con tasa de IVA editable al crear/editar producto.
- Nueva implicación aprobada: se requiere CRUD de productos (crear/editar) para editar IVA, barcode e imagen, y pantalla admin de roles/permisos de Spatie.
- Estado 2026-10-26: Fase 2 implementada (auth, Spatie, roles/permisos, CRUD usuarios/roles/productos, /admin y /pos protegidos, timezone, shell POS). Pendientes Fases 3-6. Ver fix-pos-fase-2.
- Plan 2026-11-02: el POS operativo y el Corte del día se planifican como feature-corte-dia / plan-feat-corte-dia-01, que extiende las fases 3-6 de este plan.
