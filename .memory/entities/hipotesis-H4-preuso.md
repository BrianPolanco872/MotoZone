---
entity: hipotesis-H4-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Services/DailyCutService.php:40-60,95-128; app/Services/SaleService.php:130-156,376-384; app/Services/ReturnService.php:127-140.
- Mecanismo: ventas se filtran por sale.user_id del creador y movimientos por cash_movement.user_id del cobrador; devoluciones se filtran por vendedor pero la salida pertenece al aprobador. Son operaciones permitidas por SaleController:272-280 y el flujo de aprobación.
- Verificación: autoría, permisos y consumidores de cortes revisados; pruebas existentes no comparan ambos cortes al cobrar usuarios distintos.
