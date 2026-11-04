---
entity: feature-permisos-seguridad-fase4
type: feature
---

- Objetivo: Definir matriz de permisos granulares para admin, aplicar cada permiso en BACKEND y frontend, auditoría de acciones sensibles, y revisar seguridad (hash, expiración sesión, rate limit, validación inputs, CSRF/XSS/SQL injection).
- Alcance: Matriz de permisos granulares (vender, dar descuento, cambiar precio, cancelar venta, hacer devolución, registrar entrada/salida de caja, ver ventas del día, ver su corte, hacer su corte, ver productos, editar productos), middleware/guard en backend, ocultar/deshabilitar en frontend, auditoría de acciones sensibles, revisión de seguridad.
- Fuera de alcance: Cambiar framework de autenticación, agregar 2FA, agregar SSO.
- Criterios de aceptación: (1) Matriz de permisos definida en User::PERMISSION_CATALOG. (2) Cada permiso aplicado en backend middleware/guard. (3) Cada permiso ocultado/deshabilitado en frontend. (4) Log de auditoría para acciones sensibles. (5) Hash de contraseñas bcrypt. (6) Rate limit en login. (7) Validación de inputs. (8) Protección CSRF/XSS/SQL injection.
- Solicitada por: usuario
- Última actualización: 2026-11-04
- Estado: IMPLEMENTADO
- Implementada en: fix-permisos-seguridad-fase4
