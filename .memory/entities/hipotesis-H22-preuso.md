---
entity: hipotesis-H22-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: resources/views/home.blade.php:226-235,263-268; resources/views/components/carousel.blade.php:14.
- Mecanismo: ancho rígido lg:w-72 se aplica tanto a cards verticales estrechas como a horizontales; padre overflow-hidden corta la región de imagen y navegación. Reglas CSS examinadas no neutralizan esa clase en este contenedor.
- Verificación: spans, breakpoint, anchos y componente contrastados; validación visual a 1024px y navegación táctil pendiente.
