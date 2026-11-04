---
entity: proyecto-motozone
type: Proyecto
---

- Nombre: MotoZone
- Objetivo: Catálogo web de refacciones y accesorios para motos, con panel administrativo de inventario y un futuro módulo POS.
- Stack: Laravel 13.38, PHP 8.3, Blade, Tailwind CSS v4, Vite 8, JS vanilla, MySQL/MariaDB; tests PHPUnit con SQLite en memoria.
- Estado: desarrollo; catálogo público, panel admin de productos, autenticación con roles (admin/empleado) y POS por fases; login con nombre, correo y contraseña.
- Despliegue previsto: Hostinger con dominio propio.
- Administrador bootstrap: admin@motozone.com (nombre "motozone", rol admin, activo), creado por AdminUserSeeder; la contraseña la definió el usuario y no se almacena en memoria.
- Permisos: rol admin con los 14 permisos; rol empleado sin permisos base desde 2026-11-02, con permisos individuales pos.access y admin.products.manage asignables desde /admin/usuarios.
- Última actualización: 2026-11-02
