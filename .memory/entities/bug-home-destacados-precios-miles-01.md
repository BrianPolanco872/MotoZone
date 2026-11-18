---
entity: bug-home-destacados-precios-miles-01
type: bug
---

- Reporte: en Editar inicio > Destacados, los precios no permiten respetar la coma de miles; captura muestra Precio regular como input number con 1299.
- Estado: CAUSA RAÍZ CONFIRMADA por inspección de formulario y request.
- Evidencia: card-form.blade.php:50,55 usa type=number para price/sale_price, que no admite comas de agrupación. HomeCardRequest.php:44-45 exige numeric sin normalizar miles, por lo que enviar 1,299 tampoco pasa validación.
- Contrato de datos: HomeCard castea price/sale_price a decimal:2; promo-card.blade.php:33-35 ya muestra precios públicos mediante number_format(...,2). El defecto está en la captura/edición, no en necesidad de convertir precios a texto en BD.
- Alcance solicitado: Destacados (section=products), ambos precios; no mezclar con costo de inventario cuyo diagnóstico permanece pendiente.
- Estado posterior: CORREGIDO EN FUENTE por fix-home-destacados-precios-miles-01; pendiente validación funcional solicitada explícitamente.
