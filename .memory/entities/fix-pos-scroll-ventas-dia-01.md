---
entity: fix-pos-scroll-ventas-dia-01
type: fix
---

- Objetivo: Habilitar desplazamiento vertical en la lista cargada de «Ventas del día y devoluciones» sin recorte en escritorio.
- Archivos tocados: `resources/views/pos/index.blade.php`, `public/build/manifest.json` y reemplazo de `public/build/assets/app-AhqW-xfc.css` por `public/build/assets/app-CrzeHjrq.css`.
- Estado: IMPLEMENTADO.
- Verificación básica: Pint, compilación Blade aislada, sintaxis, `git diff --check`, `./build.sh` y presencia de las tres reglas nuevas en el CSS del manifiesto. Sin cambios en JS, datos, consultas, filtros ni impresión.
- Pendiente: Verificación en navegador de caja y Testing solo por solicitud explícita. No se desplegó.
