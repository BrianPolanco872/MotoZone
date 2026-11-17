---
entity: bug-preuso-04
type: bug
---

- Auditoría: #4. Creador/cobrador y vendedor/aprobador distintos descuadran los cortes individuales.
- Estado: CAUSA RAÍZ CONFIRMADA por trazado de responsabilidad; sin escenario integrado ejecutado.
- Evidencia: app/Services/SaleService.php:130-156,376-384; app/Services/DailyCutService.php:40-60,95-128,606; app/Services/ReturnService.php:127-140.
- Caso: A crea pendiente de $100, B cobra; computadora atribuye a A y caja a B. El global oculta el desfase. Devoluciones tienen asimetría equivalente.
- Causa confirmada: hipotesis-H4-preuso.
- Fecha: 2026-11-16. Sin implementación.
