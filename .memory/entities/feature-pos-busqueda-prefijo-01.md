---
entity: feature-pos-busqueda-prefijo-01
type: feature
---

- Objetivo: Al escribir en el buscador de ventas, mostrar todos los productos activos cuyo nombre empieza con lo escrito y permitir recorrerlos hacia abajo en el mismo panel.
- Alcance: Búsqueda textual por inicio del nombre en POS y carga de más resultados al desplazarse. Mostrar aproximadamente cinco filas inicialmente, según el alto de pantalla.
- Fuera de alcance: Coincidencias solo por marca o dentro del nombre, otros buscadores, escáner y búsqueda exacta por código, selección y carrito, precios, existencias, importación y tickets.
- Criterios de aceptación: `cas` encuentra `CASCO…`, pero no `REFACCIÓN PARA CASCO` ni productos que coincidan solo por marca. `foco` encuentra `FOCO…`, pero no `CASCADA DE 500 FOCOS`. Con más de 12 nombres elegibles se llega hasta el último al bajar, sin duplicados ni pérdida de selección por teclado. Se conservan el escáner y ambos códigos exactos.
- Solicitada por: usuario.
- Estado: IMPLEMENTADO
- Implementada en: fix-pos-busqueda-prefijo-01
