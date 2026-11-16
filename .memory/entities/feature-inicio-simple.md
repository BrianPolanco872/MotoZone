---
entity: feature-inicio-simple
type: feature
---

- Objetivo: Simplificar la administración de la home para usuarios no técnicos y dejar una presentación inicial clara, con bloques predefinidos, fotografías por enlace o dispositivo y decoración mensual automática.
- Alcance confirmado: una sola pantalla Editar inicio; bloques Presentación, Categorías, Productos destacados (unifica populares/más vendidos), Recién llegados, Novedades y temporada; textos iniciales, mostrar/ocultar, editar, vista previa y sin posiciones numéricas visibles. Imágenes HTTPS o subida desde dispositivo para presentación y publicaciones. Decoración mensual ligera y automática con interruptor único.
- Decisiones confirmadas: ambas fuentes de imagen; máximo 20MB por archivo sujeto a límites del servidor, aprobado por usuario en el último sí. No archivos ilimitados. Mantener un solo inventario físico y el catálogo completo/POS actuales.
- Fuera de alcance: modificar precios/existencias/cobro, reclasificar automáticamente productos, inventar ventas/popularidad/promociones, duplicar inventario, CMS/blog genérico, nuevas dependencias, edición global de cabecera/pie/contacto y cambios de PHP/Apache fuera workspace sin autorización.
- Aceptación: una sola entrada principal del sidebar y editor comprensible por bloques; valores iniciales editables; imágenes por enlace/dispositivo con preview/fallback; límite20MB; selección fácil y orden interno automático; decoración cambia por mes usando zona horaria de la app y puede desactivarse; POS/catálogo/datos anteriores conservados.
- Estado: IMPLEMENTADO (plan-inicio-simple, fix-inicio-simple); contenido y clasificación inicial de datos reales siguen pendientes.
- Implementada en: fix-inicio-simple.
- Contexto: plan-contenido-home ya implementó módulos, pero dejó selección/publicaciones vacías y 5367 productos en Sin categoría. Nuevo plan simplifica esa experiencia; no declara finalizada la clasificación anterior.
- Preflight realizado: PHP CLI carga C:/www/php8/php.ini, upload_max_filesize64M, post_max_size128M, memory_limit512M. Esto NO confirma la configuración del servidor web; debe verificarse antes de prometer20MB efectivos. Validadores actuales del editor limitan imágenes a2MB, aún sin modificar.
- Memoria: fallback .memory, sin MCP memory/codesearch disponible en catálogo previo. .ai/rules no existe. Skills aplicables Laravel, Tailwind, PHPUnit y Ponytail cargadas en sesión.
- Última actualización: 2026-11-10.
