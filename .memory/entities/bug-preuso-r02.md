---
entity: bug-preuso-r02
type: bug
---

- Responsividad R02. Tarjetas estrechas de categorías recortan imagen y controles a 1024px.
- Estado: CAUSA RAÍZ CONFIRMADA por restricciones CSS del layout; no captura en navegador.
- Evidencia: resources/views/home.blade.php:226-235,263-268; resources/views/components/carousel.blade.php:14; resources/css/app.css:901-910.
- Caso: tarjeta lg:col-span-3 tiene aproximadamente 225px pero imagen lg:w-72 ocupa 288px bajo overflow-hidden; controles anclados a la derecha quedan recortados.
- Causa confirmada: hipotesis-H22-preuso.
- Fecha: 2026-11-16. Sin implementación. Diálogo de cobro con teclado móvil es riesgo separado no confirmado.
