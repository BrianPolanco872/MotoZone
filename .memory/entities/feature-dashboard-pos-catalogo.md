---
entity: feature-dashboard-pos-catalogo
type: feature
---

- Objetivo: Que el admin permita identificar rápidamente qué requiere atención en el POS de la refaccionaria de motos y en el catálogo público.
- Alcance confirmado por usuario: ventas del día, productos más vendidos, existencias bajas y agotadas, productos publicados sin foto o precio y revisión de compatibilidad con motos; acceso al detalle.
- Fuera de alcance confirmado: tienda en línea, contabilidad y cambios al flujo de cobro del POS.
- Criterios de aceptación confirmados: responder cómo van las ventas, qué reponer y qué corregir del catálogo, con acceso directo al detalle.
- Archivos inicialmente por confirmar; explorados: app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/dashboard.blade.php, app/Http/Controllers/Admin/ProductController.php, resources/views/admin/products/index.blade.php, app/Models/Product.php, tests/Feature/AdminTest.php y tests/Feature/AdminProductTest.php.
- Solicitada por: usuario.
- Estado: PROPUESTA; pendiente decisión de alcance sobre compatibilidad antes de generar plan-dashboard-pos-catalogo.
- Decisión pendiente: primera versión sin indicador automático de compatibilidad, o ampliar alcance para captura estructurada de modelo/cilindrada/años. No existe campo estructurado de compatibilidad en el modelo ni en las migraciones inspeccionadas; no inferirla del texto libre.
- Propuesta visual: resumen diario de ventas cobradas, tickets, stock bajo y agotados; lista prioritaria de reposición; salud de catálogo y destacados de home; top 5 productos por unidades de ventas cobradas de últimos 30 días, rotulado como unidades brutas.
- Reglas monetarias propuestas: ventas cobradas según paid_at y zona horaria de aplicación; excluir pendientes/canceladas; devoluciones aprobadas hoy según approved_at por separado; neto del día = cobrado hoy menos devoluciones aprobadas hoy, incluso de ventas anteriores; no confundir ventas con efectivo en caja ni utilidad.
- Dependencias existentes: feature-pos y feature-caja-corte-empleados; mantener el detalle por empleado y corte en sus pantallas actuales, sin duplicarlo ni cambiar fórmulas de DailyCutService.
- Inventario: respetar track_inventory y min_stock con fallback LOW_STOCK_THRESHOLD; filtros del detalle deben coincidir con conteos del dashboard. Los filtros actuales usan mínimo fijo de 5, por lo que deberán alinearse dentro de la feature y cubrirse con pruebas.
- Compatibilidad pública: is_active define publicación; HomeController selecciona los últimos 8 activos destacados. No mostrar visitas, conversiones ni estado de disponibilidad del servidor sin instrumentación.
- Memoria previa: bug-corte-reabrir-dia-01 figura con causa confirmada pero fix-corte-reabrir-dia-01 lo declara implementado. Los archivos propuestos no modifican el flujo de cortes afectado.
- Verificación de herramientas: no se encontraron memory_* ni codesearch_* en el catálogo accesible; se usa fallback .memory y herramientas read/glob/grep. .ai/rules no existe. Laravel 13.38.0, PHPUnit 12.5.40, Tailwind declarado ^4.3.3.
- No se modificó código ni se ejecutaron tests ni se inspeccionó visualmente localhost:8000/admin.
- Última actualización: 2026-11-10.
- Decisión aprobada: opción 1, implementar con datos existentes; compatibilidad estructurada fuera de alcance.
- Estado: APROBADA para implementación mediante plan-dashboard-pos-catalogo, con rollback confirmado por usuario.
- Estado: IMPLEMENTADO
- Implementada en: fix-dashboard-pos-catalogo.
- Alcance real: opción 1 con datos existentes, sin compatibilidad estructurada; dashboard y filtros accionables. Testing y revisión visual pendientes; build no ejecutado por falta de build.sh.
