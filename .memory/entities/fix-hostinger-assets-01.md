---
entity: fix-hostinger-assets-01
type: fix
---

- Plan: plan-hostinger-assets-01.
- Causa confirmada en workspace local: `public/hot` apuntaba a `http://127.0.0.1:5173` mientras el puerto 5173 no aceptaba conexiones; Laravel `@vite` prefería ese marcador sobre el manifest de producción y el navegador no podía cargar Tailwind.
- Aplicado: se eliminó el marcador local obsoleto `public/hot`; no se modificaron código fuente, dependencias ni manifest/assets.
- Verificación: `/` y `/login` local responden HTTP 200; CSS responde 200 `text/css`, JS responde 200 `application/javascript`; el HTML no contiene `127.0.0.1:5173` ni `@vite/client`; `public/hot` ya no existe y el puerto Vite permanece cerrado.
- Pendiente: no se abrió navegador para revisión visual ni se probó Hostinger remoto (URL/acceso no proporcionados). Después de esta aplicación se añadió `build.sh` y se validó su sintaxis con Git Bash, pero no se ejecutó porque no hay versión/tag SemVer verificados.
