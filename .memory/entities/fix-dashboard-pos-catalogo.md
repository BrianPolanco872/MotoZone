---
entity: fix-dashboard-pos-catalogo
type: fix
---

- Objetivo: Implementar el dashboard operativo aprobado para el POS de refaccionaria de motos y su catálogo público.
- Archivos tocados: app/Http/Controllers/Admin/DashboardController.php; app/Http/Controllers/Admin/ProductController.php; app/Models/Product.php; resources/views/admin/dashboard.blade.php; resources/views/admin/products/index.blade.php; tests/Feature/AdminTest.php; tests/Feature/AdminProductTest.php.
- Cambios: resumen de ventas/tickets cobrados por paid_at; devoluciones aprobadas hoy separadas; mínimos propios de inventario controlado activo; reposición prioritaria; top 5 activos por unidades brutas cobradas de 30 días calendario, excluyendo conceptos manuales; pendientes públicos de foto capturada y precio válido; los últimos 8 destacados activos según consulta de home; acceso POS/home/catálogo y enlaces a edición/detalle.
- Cobertura añadida: límites de fechas, estados de venta/devolución, importes, ranking bruto y manuales excluidos, mínimos propios y fallback, inventario sin control e inactivos excluidos, calidad de publicación, destacados, vacíos, escape HTML, filtros combinados y paginación, acceso de invitado/empleado.
- Verificación: php -l correcto en los cinco PHP modificados; Pint --dirty --format agent limitado a esos archivos correcto; php artisan view:cache --no-interaction correcto; git diff --check limitado a los siete archivos correcto. El check global identifica whitespace preexistente en resources/views/pos/cuts/history.blade.php; no se modificó.
- Testing: no ejecutado, requiere solicitud explícita. La cobertura escrita no implica resultados de tests verificados.
- Build: no ejecutado; no existe build.sh, package.json no declara versión de aplicación y no se confirmó tag SemVer. public/hot no existe; si faltan estilos, iniciar npm run dev. No se usaron scripts alternativos para generar artefactos.
- Pendiente: revisión visual en navegador y verificación funcional explícita. Foto capturada no verifica existencia física del archivo. Ranking bruto no descuenta devoluciones y está rotulado.
- Desviaciones del plan original: ninguna funcional. No se alteraron esquema, dependencias, cobro, cortes, rutas, layout global ni CSS preexistentes.
- Estado: IMPLEMENTADO
- Última actualización: 2026-11-10.
