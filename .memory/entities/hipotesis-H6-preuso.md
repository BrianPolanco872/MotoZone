---
entity: hipotesis-H6-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Services/ReturnService.php:79-89,148-158.
- Mecanismo: approve usa transacción, recarga y lockForUpdate; reject consulta isPending sobre el modelo recibido y actualiza sin recargar/bloquear. Un UPDATE tardío puede reemplazar approved por rejected sin deshacer caja/stock.
- Verificación: trazado de intercalado de peticiones. No se ejecutó prueba concurrente; debe validarse con el motor de producción.
