---
entity: bug-productos-costo-vacio-01
type: bug
---

- Reporte corregido: alta manual falla con MySQL 1048 Column cost cannot be null; el usuario aclara que escribió 7.50, no dejó costo vacío. INSERT terminó enviando cost NULL. La afirmación previa de campo dejado vacío fue una inferencia incorrecta del agente.
- Estado: PENDIENTE DE CONFIRMAR DÓNDE SE PIERDE EL 7.50. El rechazo de NULL está confirmado, pero no explica por sí solo la pérdida del dato informado.
- Evidencia: ProductRequest.php:51 permite cost nullable; productData copia los campos validados sin normalizar cost. La migración add_cost_and_inventory_fields declara cost NOT NULL DEFAULT 0. El default de la BD no se aplica cuando el INSERT envía explícitamente NULL.
- Mismo mecanismo confirmado para min_stock: request nullable, columna NOT NULL DEFAULT 0 y persistencia sin normalización. wholesale_price sí admite NULL y no requiere cambio.
- Alcance propuesto: corregir normalización compartida de alta/edición de cost/min_stock, sin cambiar códigos, precios ni importación.
- Hallazgo posterior: formulario form.blade.php:86 usa value=product.cost y no old(cost, product.cost), a diferencia de precio y otros campos; después de una redirección con errores no restaura el costo introducido. Es un defecto confirmado de repoblación, pero todavía no se confirmó que hubiera una redirección previa en el caso reportado.
- Lectura de JS/request/controlador/modelo: JS calcula precio y margen, no asigna cost.value; request no modifica cost y productData lo incluye. No se encontró mutador/observer de costo que lo sustituya. Falta secuencia exacta de reproducción o payload HTTP.
- Requisito explícito: respetar exactamente el costo válido 7.50; no sustituirlo por cero como parche que oculte pérdida de datos.
- Confirmación posterior del usuario: el error apareció al PRIMER intento de guardar. Descartada para este incidente la secuencia de pérdida de old(cost) tras una redirección anterior.
- Diagnóstico pendiente: comparar el valor cost del Body de la solicitud fallida con el NULL del INSERT. Solicitar únicamente ese campo, sin cabeceras/cookies ni datos sensibles; no sustituir por 0 ni aplicar plan pausado.
