---
entity: fix-pos-vista-amplia-existencias-04
type: fix
---

- Objetivo: sincronizar versión del proyecto a 1.0.1 con tag existente y ejecutar el build pendiente del POS mediante ./build.sh.
- Archivos tocados: composer.json; composer.lock (solo content-hash); package.json; package-lock.json (solo versión raíz y packages[""].version). Artefactos generados: public/build/manifest.json, fonts-manifest.json y assets compilados, ignorados por Git. Memoria de plan/feature/relaciones.
- Precondiciones: rama produccion; HEAD = v1.0.1^{commit} = 3cee29d2ac119c3f51d827757b6bc9143ef23a93; versiones raíz verificadas 1.0.1; public/hot ausente. Sin modificar tag/historial.
- Composer: validate detectó content-hash desactualizado al cambiar versión; calculado con Composer\Package\Locker::getContentHash usando el Composer instalado. Solo se actualizó ese hash; no se resolvieron ni cambiaron dependencias. composer validate --no-check-publish correcto con advertencias previas sobre version explícita y constraint exacto de spatie.
- Ejecución: Git Bash instalado ejecutó ./build.sh sin argumentos, script original sin modificar; npm ci + vite build + comprobaciones internas del script completaron correctamente. La comprobación inicial PowerShell de package-lock falló por su clave vacía; se sustituyó por lectura JSON en Node antes de ejecutar el script. No fue un fallo de versiones ni de build.
- Resultado: Vite 8.3.5 compiló correctamente; JS app-CiPYpWx0.js y CSS app-D_ssDLvs.css. Inspección de artefactos confirma tabla de búsqueda, modal de imagen, fotos size-20 (80px), CSS correspondiente y ancho de tabla 76rem.
- Advertencia no bloqueante: laravel:fonts sugiere dependencia opcional fontaine para fallbacks optimizados; no añadida, fuera del alcance.
- Verificación: composer validate, versiones y tag/HEAD, controles internos build.sh y git diff --check correctos; sin cambios ajenos a metadata/artefactos/memoria en esta operación.
- Estado: IMPLEMENTADO; BUILD LOCAL COMPLETADO. Sin Testing, navegador, migraciones ni despliegue.
- Fecha: 2026-11-22.
