---
entity: hipotesis-H2-productos-costo-vacio-01
type: hipotesis
---

- Estado: DESCARTADA COMO CAUSA DEL INCIDENTE; el usuario confirmó fallo en el primer envío, sin redirección previa. El defecto de repoblación sigue siendo un hallazgo separado, no una explicación de la pérdida inicial.
- Archivo/línea: resources/views/admin/products/partials/form.blade.php:86.
- Evidencia: input cost usa solo product.cost; no recupera old(cost) tras redirección de validación, mientras price sí lo hace. En creación, el nuevo Product tiene cost null y el valor introducido puede desaparecer al volver a mostrar el formulario.
- Hipótesis de secuencia: escribir 7.50 -> error de validación de otro campo -> formulario regresa sin restaurar costo -> segundo envío cost vacío/null -> MySQL 1048. No se afirma que esta secuencia haya ocurrido sin confirmación del usuario.
- Entrada pendiente: saber si el error apareció en el primer envío o después de corregir un aviso anterior; si fue al primer envío, inspeccionar payload cost del POST para ubicar la pérdida.
- Respuesta recibida: primer intento. Siguiente evidencia necesaria: valor de cost en Request Body de esa solicitud.
