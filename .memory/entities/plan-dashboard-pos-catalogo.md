---
entity: plan-dashboard-pos-catalogo
type: plan
---

- Tipo: feature
- Estado: APROBADO
- Aprobación: usuario autorizó implementación y confirmó formalización de este plan con rollback en la conversación actual.
- Feature: feature-dashboard-pos-catalogo. Opción 1, sin compatibilidad estructurada de motos.
- Objetivo: dashboard profesional, operativo y accionable del POS y catálogo público de MotoZone.
- Paso 1: actualizar app/Http/Controllers/Admin/DashboardController.php con ventas cobradas hoy por paid_at, tickets, devoluciones aprobadas hoy separadas, conteos de inventario activo controlado, pendientes públicos de foto/precio, los mismos 8 destacados de HomeController y top 5 refacciones activas por unidades brutas cobradas de los últimos 30 días calendario hasta la consulta.
- Paso 2: rediseñar resources/views/admin/dashboard.blade.php con encabezado de fecha/hora, accesos POS/home/catálogo, cuatro indicadores, reposición prioritaria y top vendidos en columna principal, pendientes y destacados de home en secundaria; estilos y componentes existentes, responsive, alertas con texto, estados vacíos y enlaces a detalle. No presentar ventas como efectivo o utilidad.
- Paso 3: agregar scopes compartidos en app/Models/Product.php para bajo stock por min_stock con fallback a LOW_STOCK_THRESHOLD, agotados controlados, foto no capturada y precio no positivo. Alinear filtros activos en app/Http/Controllers/Admin/ProductController.php y resources/views/admin/products/index.blade.php para que el detalle coincida con el dashboard, conservando búsqueda, categoría y paginación.
- Paso 4: actualizar tests/Feature/AdminTest.php y tests/Feature/AdminProductTest.php con cobertura de cifras, exclusión de pendientes/canceladas, límites de fechas, devoluciones, ranking, inventario controlado y mínimos, calidad de publicación, destacados, estados vacíos y acceso restringido.
- Aceptación: responder cuánto se cobró, qué reponer y qué corregir de la publicación; cifras desde BD con alcance/periodo rotulados; enlaces a detalle coherentes; sin N+1 ni cargar todas las ventas; sin indicadores inventados de visitas o compatibilidad.
- Fuera de alcance: esquema BD, dependencias, cobro y formularios POS, fórmulas de caja/cortes, contabilidad, ecommerce, analítica pública y compatibilidad de motos.
- Plan de rollback: restaurar únicamente los cambios de este plan en los siete archivos de aplicación/pruebas enumerados, conservando modificaciones preexistentes; no hay migraciones ni escrituras a datos de negocio que revertir. No usar operaciones de historial git.
- Verificación: php -l en PHP modificado, compilación Blade y Pint limitado a archivos propios con --dirty --format agent; revisión de diff contra alcance. Testing solo con solicitud explícita. No construir artefactos sin build.sh y versiones/tags verificados; no existe build.sh actualmente.
- Precaución: el workspace tiene cambios preexistentes en POS/layout/CSS/rutas y otros archivos, no modificarlos ni revertirlos.
- Herramientas: read/glob/grep/patch y shell PowerShell disponibles; bash.exe localizado. memory_* no está disponible en catálogo, se usa fallback .memory. Skills aplicables activadas: ponytail, laravel-best-practices, tailwindcss-development y testing-best-practices; resto de skills disponibles no aplica a este alcance. .ai/rules no existe.
- Última actualización: 2026-11-10.
- Estado: IMPLEMENTADO
- Cambios aplicados: dashboard y filtros coherentes en los siete archivos previstos; cobertura PHPUnit añadida sin ejecutar suite; consultas agregadas y listas limitadas, sin cargar todas las ventas.
- Desviaciones del plan original: ninguna funcional; sin build por ausencia de build.sh y sin Testing ni revisión visual de navegador.
- Implementado en: fix-dashboard-pos-catalogo.
- Verificación de correspondencia: diff limitado revisado contra pasos 1 a 4, sintaxis/Pint/compilación Blade y whitespace propios correctos. Pendiente validación funcional explícita y assets según entorno.
