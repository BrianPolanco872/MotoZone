---
entity: plan-tickets-termicos-5sprinter-01
type: plan
---

- Tipo: feature.
- Antecedente: el usuario dijo «apruebo el plan» sobre la propuesta conversacional antes de registrarla y de presentar su rollback. No confundirlo con plan-tickets-ancho-impresion-01, ya implementado en fuente.
- Aprobación explícita: tras presentar el plan registrado y el rollback, el usuario respondió «sí» a empezar por el formato y el número de artículos con esa reversión. Autoriza solo la etapa 1.
- Alcance propuesto: un plan con dos etapas: impresión y conteo primero; captura y persistencia de pagos mixtos con su efecto en caja después. La aprobación de la primera etapa no autoriza resolver por cuenta propia las reglas contables pendientes de la segunda.

## Etapa 1: Tickets

1. Usar el documento independiente existente de Tickets para imprimir desde el botón del POS y desde el historial; excluir el diseño del POS de la impresión. Mantener el diálogo nativo del navegador, disparado por el botón correspondiente, con previsualización, selección de impresora, confirmación y cancelación. Conservar permisos de folio/propietario y comportamiento de reimpresión. No elegir impresora ni imprimir en silencio.
2. Usar el mismo estilo de comprobante en impresión de venta y ejemplo de preferencias, conservando 58 y 80 mm. Para la caja del usuario, seleccionar 58 mm en Tickets y en el driver; no tomar el diámetro de 60 mm como ancho ni fijar una altura de página arbitraria. Ajustar el ancho útil con el modelo y controlador reales.
3. En `resources/views/pos/tickets/receipt.blade.php` y su estilo: centrar encabezado, tipografía negra legible, separar cantidad, descripción e importe, mover precio unitario debajo de la descripción en 58 mm si hace falta, envolver nombres largos, alinear números y destacar Total. Mantener datos reales, opciones vigentes, folio, QR, impuestos, devoluciones registradas y pie configurable. No copiar turno ni leyenda de la foto. El fragmento que aparece arriba no tiene una causa física demostrada; no eliminar preferencias guardadas sin autorización.
4. Agregar N.º de artículos a partir de las cantidades guardadas: sumar piezas y paquetes vendidos (un paquete cuenta como uno, sin expandir componentes), y mostrar las partidas a granel por separado si existen, sin sumarlas como piezas. Conservar la cantidad comprada original y el renglón de devolución aparte en reimpresiones.
5. Archivos previstos: `resources/views/pos/tickets/{receipt,ticket,settings}.blade.php`, `resources/views/pos/index.blade.php`, `resources/js/pos/pos.js` y `resources/js/ticket-settings.js` si el flujo de prueba lo requiere, más cobertura dirigida en `tests/Feature/TicketSettingsTest.php` y `tests/pos.test.js`. Reutilizar la ruta de comprobante autorizada y conservar el JSON del ticket mientras otros consumidores lo requieran.

## Etapa 2: desglose real de pago mixto

1. Capturar efectivo, tarjeta y transferencia solo para mixto, tanto al cobrar una venta nueva como un pendiente. Validar en servidor al menos dos importes positivos, precisión de centavos y suma de importes aplicados igual al total real; si se admite cambio, separarlo del efectivo aplicado. No cambiar el total de venta, inventario, impuestos ni el funcionamiento de pagos simples.
2. Persistir el desglose en `Sale` con un campo nuevo nullable y guardarlo en la misma transacción e idempotencia que la venta. Dejar ventas históricas sin desglose intactas; al reimprimir mostrar «Pago mixto» sin importes inventados. Leer el dato persistido desde el comprobante compartido.
3. Incluir únicamente el efectivo neto confirmado de las nuevas ventas mixtas en caja/corte; no contabilizar el componente electrónico como efectivo, duplicar ingresos ni reinterpretar cortes cerrados. Antes de ejecutar esta etapa, acordar cómo se registra un reembolso/cancelación de un pago mixto y cómo se asigna efectivo en una venta mixta que incluye Navidad, dado que esos artículos no entran a caja. No improvisar estas reglas ni declarar conciliadas operaciones sin datos suficientes.
4. Archivos previstos: formulario de cobro del POS, `resources/js/pos/pos.js`, `StoreSaleRequest`, `ChargePendingSaleRequest`, `SaleController`, `SaleService`, `Sale`, migración de `sales`, `DailyCutService`, comprobante compartido y pruebas de POS/cobro/conciliación; `ReturnService` solo si queda autorizado por la regla de devolución acordada.

## Verificación y aceptación

- Complementación: comprobar alcance, sintaxis/compilación básica y formato. Testing dirigido solo a petición explícita, en entorno aislado; cubrir 58/80 mm, impresión/cancelación, folio desde POS e historial, conteo entero y granel, nombres largos, importes grandes, permisos y QR. Etapa 2: cobrar/reimprimir una venta de $320.00, con transferencia de $200.00 y efectivo de $120.00, también desde pendiente; cubrir montos inválidos, ventas antiguas y que la caja no reciba los $200 electrónicos.
- Aceptación física pendiente: en el equipo de caja seleccionar la 5Sprinter real y el formato de 58 mm; comparar vista previa y papel del mismo folio desde POS e historial. Revisar un comprobante corto sin hoja/tira en blanco extra y uno largo sin recorte, con importes intactos y pie al final. Si la vista previa es correcta y el papel no, revisar controlador, tamaño, márgenes/escalado y área imprimible antes de retocar CSS. No se garantiza una copia exacta de la foto.
- No hay impresión silenciosa, ESC/POS, nuevas dependencias ni publicación en esta sesión. Cualquier build autorizado debe pasar por `./build.sh`, previa verificación de versión y tag correspondiente apuntando al commit correcto.

## Plan de rollback propuesto

- Impresión: respaldar y restituir solo vistas/JS propios y, si se publicaron, el conjunto previo de assets y manifest junto con las vistas. Reponer únicamente preferencias que se hayan cambiado a propósito desde su respaldo; no tocar ventas, catálogo, QR ni cambios locales ajenos.
- Pago mixto: respaldo previo y migración aditiva; si todavía no hay ventas con desglose, puede retirarse el código y dejar el campo nullable. Si ya hay ventas mixtas nuevas, pausar temporalmente ese medio, conservar columna, importes y movimientos, y preparar una corrección compatible con los registros existentes. No borrar la columna, restaurar toda la BD ni revertir movimientos/cortes automáticamente. No iniciar esta etapa hasta confirmar la estrategia y las reglas pendientes.

## Implementación de etapa 1

- Estado: IMPLEMENTADO EN FUENTE (etapa 1); etapa 2 pendiente y no autorizada.
- Cambios aplicados: el botón del POS carga la ruta de comprobante autorizada en un iframe temporal e imprime ese documento, sin imprimir la pantalla del POS ni abrir otra pestaña. El historial mantiene la vista independiente y el botón nativo. El comprobante y el ejemplo de Tickets comparten estilos térmicos de 58/80 mm. Se añade N.º de artículos (piezas y paquetes vendidos) con partidas a granel separadas.
- Desviaciones del plan original: ninguna en el alcance aprobado; no fue necesario modificar `ticket-settings.js`. Se añadió la vista parcial de estilos dentro del directorio de Tickets. Verificación en el driver y prueba física pendientes. Sin Testing, publicación ni build: `package.json` 1.0.1, HEAD `63016d8a224566bc1151f2caf160ef9d7da9f948`, tag `v1.0.1` apunta a `3cee29d2ac119c3f51d827757b6bc9143ef23a93`. No combinar Blade nuevo con JavaScript compilado anterior.
