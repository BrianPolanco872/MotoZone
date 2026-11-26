---
entity: plan-pos-reimpresion-01
type: plan
---

- Tipo: bug-fix
- Aprobación explícita: «aprobado aplica» (2026-11-25).
- Objetivo: recuperar «Reimprimir último ticket» e «Imprimir copia» sin volver a cobrar, cancelar ni cambiar ventas.

## Cambios propuestos

1. `app/Http/Controllers/PosController.php` y `resources/views/pos/index.blade.php`: inicializar `pos-config` con la última venta pagada **propia** del usuario autenticado que tenga `pos.tickets.print.own` o `pos.tickets.print.any`. Usar `Sale::paid()`, excluir ventas sin `paid_at`, ordenar por `paid_at` y desempatar por `id` descendente. Exponer solo ID, folio, pago con y cambio. Sin permisos o venta elegible, entregar `null`. Pasar al modal la capacidad de imprimir tickets propios usando la misma condición, sin ampliar permisos.
2. `resources/js/pos/pos.js`: inicializar el indicador y el botón con ese dato; actualizarlo tras un nuevo cobro confirmado y conservarlo al pulsar «Nueva venta» o «Limpiar». «Imprimir copia» sigue usando exclusivamente el ID de la fila seleccionada, nunca el de la última venta. Mantener deshabilitado cada botón si falta su venta o permiso. No condicionar la impresión al fondo de caja.
3. `resources/js/pos/pos.js`, `resources/views/pos/index.blade.php` y `resources/views/pos/tickets/ticket.blade.php`: reemplazar el iframe oculto de ambas acciones por la vista de ticket ya existente, abierta directamente desde el clic en una pestaña, con solicitud de impresión automática solo para estos dos botones y el botón «Imprimir» de la vista como respaldo. Esperar a que cargue el comprobante sin cancelar toda la impresión por un QR opcional que falle. Conservar la pestaña y la impresión manual si se cancela el diálogo o el navegador bloquea la impresión automática. Opción de apertura del mismo comprobante si se bloquea la pestaña, sin silencios ni cambios en la venta. Mantener la autorización de la ruta GET y el formato actual. Antes de editar este flujo, contrastar la respuesta de la ruta directa para una venta propia en el entorno de prueba: si falla el servidor o un permiso legítimo, notificar la causa y la desviación necesaria en vez de ocultarla con el cambio de navegador.
4. Añadir regresiones focales en `tests/Feature/PosAccessTest.php` y `tests/pos.test.js`: seleccionar la última pagada del usuario (no pendientes, anuladas ni ventas ajenas), mantenerla al limpiar y tras recargar, actualizarla tras otro cobro y abrir los tickets correctos desde los dos botones, sin enviar solicitudes de mutación ni exponer tickets a quien no tiene permiso.

## Verificación y reversión

- Verificación básica al implementar: sintaxis PHP/JS, Pint según la regla del repositorio y revisión de alcance. Si se van a generar artefactos, comprobar versiones y el tag que corresponde a la rama/commit, y usar únicamente `./build.sh`; detener el build si hay discrepancias.
- Testing solo bajo solicitud explícita: pruebas focales en entorno aislado y validación en Chrome del POS de que se abre el diálogo para la venta correcta desde cada botón. Probar una segunda impresión después de cancelar el diálogo y el caso de QR no disponible. Usar V-000016 si está disponible como referencia sin mutar datos; salida física en la impresora de caja por separado.
- Fuera de alcance: modificar caja, cancelar ventas, tocar inventario, migraciones, permisos, formato del ticket, dependencias y despliegue.
- Rollback: respaldar los archivos afectados antes de editarlos; si falla, restaurar solo esas copias sin tocar cambios ajenos ni datos de ventas.

- Estado: IMPLEMENTADO.
- Cambios aplicados: carga de la última pagada propia desde el servidor, persistencia al limpiar el carrito y actualización al cobrar; la copia conserva el ID de la fila seleccionada. Ambos botones abren la ruta GET existente con impresión a la carga, botón manual y confirmación antes de navegar en la misma pestaña si se bloquea la nueva.
- Desviaciones del plan original: ninguna. Build detenido según el plan por discrepancia de tag; pruebas focales añadidas, no ejecutadas.
