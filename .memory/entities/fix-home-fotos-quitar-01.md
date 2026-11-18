---
entity: fix-home-fotos-quitar-01
type: fix
---

- Objetivo: respetar Quitar foto principal al guardar, manteniendo el carrusel intacto.
- Estado: IMPLEMENTADO.
- Archivos tocados: resources/js/home-editor.js; resources/views/admin/home/partials/card-form.blade.php; tests/home-editor.test.js; tests/Feature/HomeCardTest.php; tests/Feature/AdminHomeContentTest.php.
- Cambios: invalidar/restaurar estado de preview sin emitir input que desmarca la casilla; limpiar/ocultar principal manteniendo remove_image; mostrar solo imageUrl real en campo principal. No se tocaron endpoints, modelos ni galería.
- Verificación: sintaxis JS/PHP, Pint y diff correctos; cobertura de regresión añadida pero no ejecutada. Testing requiere solicitud explícita. Sin borrados reales.
- Activación local: usuario autorizó iniciar Vite; npm run dev -- --host 127.0.0.1 --port 5173 --strictPort en background sh_1ed012354001IjRllLSc4ljPkQ; /@vite/client y /resources/js/home-editor.js devuelven HTTP 200, public/hot apunta a http://127.0.0.1:5173. Recargar navegador para abandonar script compilado anterior.
- Desviaciones: ninguna en código; servidor de desarrollo autorizado adicionalmente. Build de producción BLOQUEADO por versión/tag desalineados, sin operaciones de historial git.
- Limitaciones: no se comprobó el flujo completo en navegador ni se ejecutaron suites. El cambio local depende de Vite activo; al detenerlo debe respetarse el manejo de public/hot y construir solo mediante build.sh tras resolver versionado.
- Fecha: 2026-11-17.
