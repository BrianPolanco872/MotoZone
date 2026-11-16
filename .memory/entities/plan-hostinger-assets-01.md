---
entity: plan-hostinger-assets-01
type: plan
---

- Tipo: feature
- Feature: feature-hostinger-assets
- Objetivo: Evitar que el sitio de MotoZone se muestre sin estilos en Hostinger y comprobar la instalación y entrega de sus assets de producción.
- Alcance: Verificar dependencias bloqueadas por composer.lock/package-lock.json, Tailwind v4/Vite y el build; prevenir referencias al servidor Vite local en producción; publicar y probar los assets con el document root `public`; mantener intactas las funciones del programa.
- Fuera de alcance: Cambios funcionales o de reglas de negocio, autenticación, base de datos/migraciones, rediseño, actualización/agregado de dependencias no justificado y modificaciones de secretos de producción.
- Criterios de aceptación:
  1. `composer install` desde `composer.lock` y `npm ci` desde `package-lock.json` terminan sin errores de dependencias requeridas.
  2. `./build.sh` termina correctamente; el manifest apunta a CSS/JS existentes e incluye selectores Tailwind usados por home y login.
  3. La publicación de Hostinger no incluye `public/hot` ni referencias a `127.0.0.1:5173` o `@vite/client`.
  4. Los enlaces CSS y JS renderizados por la home y `/login` en Hostinger responden HTTP 200 con Content-Type adecuado.
  5. La home y el login se revisan visualmente en navegador sin fallback sin estilos ni errores de carga de CSS/JS.
  6. No se modifica la lógica funcional del programa.
- Pasos:
  1. Registrar baseline de manifests, estado de assets, versiones y archivos ignorados; no sobrescribir `.env` ni exponer secretos.
  2. Instalar Composer desde `composer.lock`, sin `composer update` ni cambios de versiones. No instalar `@laravel/multiplex`: aparece como opcional no instalado y no se encontraron imports de la app.
  3. Ejecutar `./build.sh`, que instala npm desde `package-lock.json`, compila con el script existente de Vite y verifica el manifest, CSS/JS, selectores Tailwind y ausencia de `public/hot`.
  4. Empaquetar el release con `public/build` y excluir `public/hot`; conservar el `public/hot` local de desarrollo sin borrarlo por defecto. Mantener el document root Hostinger apuntando a Laravel `public`.
  5. En Hostinger, revisar si quedó un `public/hot` previo y retirar solo ese marcador de producción antes de validar; confirmar URLs de assets extraídas del HTML real.
  6. Validar HTTP/Content-Type para home y login, y hacer prueba visual/network en navegador. Si no se proporciona una URL accesible, declarar esta aceptación pendiente y no afirmar que se verificó el hosting.
  7. Comparar estado funcional y cambios con el alcance; reportar cualquier dependencia requerida ausente y detenerse antes de cambiar manifests/versions.
- Archivos/módulos afectados:
  - `package.json` y `package-lock.json`: inspección de scripts y dependencias; no editar salvo bloqueo reproducible que requiera aprobación adicional.
  - `composer.json` y `composer.lock`: verificación de requisitos PHP/Laravel y resolución; no actualizar el lock.
  - `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`: revisar inputs/plugins actuales; modificar solo si el build prueba un defecto de fuente/configuración.
  - `resources/views/layouts/site.blade.php` y `resources/views/auth/login.blade.php`: consumidores de `@vite` correspondientes a la home y acceso; inspección/prueba, sin cambios previstos.
  - `public/build/manifest.json` y `public/build/assets/*`: artefactos generados para el despliegue; están ignorados por Git y deben incluirse en el paquete de Hostinger.
  - `public/hot`: marcador local obsoleto con `http://127.0.0.1:5173`, eliminado al comprobar que el puerto estaba cerrado; nunca debe formar parte del release. `build.sh` falla de forma segura si reaparece.
  - `build.sh`: build reproducible y guardia que rechaza un marcador Vite activo/obsoleto antes de compilar; verifica que los assets y selectores esperados estén presentes.
  - Hostinger: revisar el document root `public` confirmado por el usuario y la ubicación real de los assets; no modificar valores secretos de `.env`.
- Plan de rollback: guardar el release actual antes de reemplazar assets; si el nuevo build falla, restaurar el `public/build` anterior completo y la versión anterior de la aplicación, manteniendo `public/hot` ausente en producción. No hay migraciones ni cambios de datos.
- Riesgos y dependencias externas: `public/hot` local apunta al loopback del desarrollador; no está confirmado que exista en Hostinger, pero si se copia/queda allí hace que Laravel cargue recursos desde un Vite inaccesible para visitantes. `public/build`, `vendor` y `node_modules` están ignorados por Git. `.env.example` contiene APP_ENV=local, APP_DEBUG=true, APP_URL localhost y SQLite; no debe usarse como `.env` de producción. Falta URL pública/staging y método de acceso para la validación HTTP/visual en Hostinger. Existe `plan-deploy-hostinger-01` con una auditoría anterior (Laravel/PHP/Composer/Node/Tailwind/build); sus resultados se reutilizan como antecedentes, no como validación actual de esta incidencia. No hay bug activo de memoria en los archivos del alcance; `bug-corte-reabrir-dia-01` corresponde al POS y no aplica.
- Alternativas consideradas: Build local + publicación de `public/build` (seleccionado por reproducible y no depender de Node/SSH en Hostinger); compilar en Hostinger (requiere SSH y Node compatible y añade variación del entorno, sin ventaja demostrada).
- Verificación previa realizada: PHP 8.3.35, Node 24.15.0, npm 11.12.1, Composer 2.10.3; `vendor/`, `node_modules/`, `public/build/manifest.json` y los assets actuales existen; Composer platform requirements pasan; `npm ls --depth=0` muestra Tailwind/@tailwindcss-vite 4.3.3 y Vite 8.3.5 instalados, con solo `@laravel/multiplex` opcional no instalado; el manifest actual referencia CSS/JS existentes del 2026-11-15. `public/hot` contiene `http://127.0.0.1:5173`. No se ejecutó build ni prueba HTTP/visual en Hostinger.
- Verificación inicial: memory MCP y `codesearch_*` no disponibles; se usó `.memory/` y búsqueda local. Skills aplicables: Ponytail, Tailwind; Laravel/testing aplican si se requiere modificar PHP o pruebas. `.ai/rules` no existe.
- Aplicación local 2026-11-16: se retiró `public/hot` al confirmar que el Vite local en 5173 estaba apagado. Home y login devolvieron HTTP 200, y los assets CSS/JS referenciados desde manifest respondieron 200 con MIME correcto; no quedó referencia local al dev server. No se cambió fuente ni se reconstruyó el bundle. Validación visual y Hostinger remoto siguen pendientes por falta de URL/acceso. En esa primera revisión, `build.sh` todavía no existía.
- Preparación adicional 2026-11-16: por autorización explícita del usuario se añadió `build.sh`, con guardia para `public/hot`, `npm ci`, build Vite y comprobaciones de manifest/assets/selectores. Git Bash `bash -n` pasa y el archivo tiene modo ejecutable. No se ejecutó el build: no hay versión de aplicación ni tag SemVer en HEAD, por lo que sigue bloqueado por la verificación de release; Hostinger remoto también requiere URL/acceso.
