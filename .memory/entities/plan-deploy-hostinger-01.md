---
entity: plan-deploy-hostinger-01
type: plan
---

- Tipo: revisión de despliegue
- Objetivo: Validar que MotoZone pueda desplegarse en Hostinger con dominio propio sin fallos de PHP, Composer, Tailwind/Vite, base de datos ni permisos.
- Fecha: 2026-10-26
- Verificado: Laravel 13.38 + PHP 8.3.35; Hostinger ofrece PHP 8.3 por defecto; Composer 2.10.3; `composer install --no-dev --dry-run` correcto; Node 24.15 y Vite 8.3.5; `npm run build` correcto con Tailwind v4 (app CSS 85.18 kB); `public/build/manifest.json` y fuentes generadas; `public/.htaccess` presente; 11 tests pasan (35 aserciones).
- Riesgos detectados: `.env.example` no es de producción (APP_ENV=local, DEBUG=true, APP_URL localhost, DB sqlite, QUEUE database, LOG debug, sin APP_TIMEZONE ni SESSION_SECURE_COOKIE); timezone UTC hardcodeado; `/public/build` y `/vendor` están en .gitignore; Hostinger sirve `public_html` y Laravel necesita `public`; hay que definir symlink o mover público; storage/bootstrap cache deben ser escribibles; no ejecutar `db:seed` en producción porque inserta catálogo demo; CLI de Hostinger puede usar PHP distinto al web; `/admin` sigue público hasta Fase 2.
- Decisiones pendientes: plan de Hostinger (Web/Premium/Business/Cloud/VPS), disponibilidad de SSH/Composer/Node, estrategia de subida de `public/build` (subir artefacto, commitearlo o build en servidor), estrategia de document root (symlink `public_html -> app/public` o copiar `public`), y si se usará Git deploy o FTP/File Manager.
- Recomendación: construir assets localmente con `npm ci && npm run build`; subir `public/build`; usar `composer install --no-dev --optimize-autoloader` en servidor con PHP 8.3 o empaquetar vendor no-dev; configurar `.env` de producción con HTTPS, MySQL, timezone America/Mexico_City, debug false, queue sync y logs daily; migrar sin seed demo; optimizar cachés; verificar SSL y permisos.
