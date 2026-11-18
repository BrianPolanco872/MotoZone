---
entity: feature-productos-limpiar-tabla-01
type: feature
---

- Objetivo: que Limpiar inventario borre todos los registros de productos existentes, en lugar de poner sus existencias a cero.
- Aprobación explícita en esta conversación: usuario insiste en borrar productos, aclara «solo productos» y finalmente «no debe aver ningun movimiento ... haz que ese boton me deje limpiar mi tabla de productos existentes» (2026-11-17).
- Alcance: únicamente la acción clearInventory, su confirmación en Productos y las pruebas existentes relacionadas. No cambiar el botón individual Eliminar.
- Fuera de alcance: ejecutar el borrado en la BD real, modificar esquema/FKs, desactivar integridad referencial, borrar ventas/caja/cortes/movimientos, modificar otros módulos, eliminar imágenes físicas o restablecer IDs.
- Criterios de aceptación: con productos de desarrollo sin referencias restrictivas, confirmar el botón vacía products incluyendo stock cero, inactivos y no controlados; conserva otras tablas; mantiene permisos y CSRF; tabla vacía es válida; si existe una referencia restrictiva, rollback completo y mensaje comprensible sin 500 ni borrado histórico.
- Estado: APROBADA para implementación de código, no ejecución del borrado real.
- Estado: IMPLEMENTADO.
- Implementada en: fix-productos-limpiar-tabla-01.
- Verificación: siete pruebas dirigidas correctas en SQLite :memory:, 55 aserciones; no se ejecutó borrado sobre productos reales.
- Ampliación autorizada por usuario e implementada: limpiar también únicamente los ajustes automáticos de la puesta a cero antigua, identificados por tipo/nota/sale_id/stock_after/cantidad, en la misma transacción que el borrado de productos. Conservar y bloquear ante cualquier otro movimiento; mostrar errores en la vista de Productos.
- Estado: IMPLEMENTADO.
- Implementada en: fix-productos-limpiar-tabla-01.
- Verificación actualizada: 9 pruebas dirigidas/69 aserciones correctas en SQLite :memory:. En MySQL solo lectura se confirmaron 27 ajustes que cumplen todos los filtros, sin otros movimientos. El agente no ejecutó el borrado real ni clic de navegador.
