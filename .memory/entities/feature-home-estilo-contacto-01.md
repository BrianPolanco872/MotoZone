---
entity: feature-home-estilo-contacto-01
type: feature
---

- Objetivo: Adaptar solo la home al estilo proporcionado y añadir en Editar inicio los campos o módulos faltantes.
- Solicitada por: usuario.
- Alcance: presentación pública en crema/arena/naranja conforme al HTML de referencia; conservar presentación, categorías editoriales, destacados y recién llegados; incorporar ubicación/mapa, datos de mostrador/contacto y textos complementarios del diseño dentro del editor actual. Correo y WhatsApp alimentan las burbujas y las llamadas a contacto de la home.
- Fuera de alcance: POS, ventas, caja, cortes, inventario, empleados, permisos/login, fichas de producto y otras pantallas; no restaurar catálogo público ni categorías de inventario; no importar contenido de ejemplo, modificar precios ni reemplazar cards existentes; no CMS genérico, dependencias nuevas o migraciones.
- Criterios de aceptación: solo la home y su editor cambian; la home adopta el estilo de referencia en móvil/escritorio; los campos nuevos se guardan y aparecen en los bloques correspondientes; mapa configurable mediante URL de inserción de Google Maps o pin por coordenadas; correo/WhatsApp de las burbujas salen de la configuración; datos editoriales y comportamiento existentes se conservan. Condiciones concretadas a partir de la petición, sin ampliación a otros módulos.
- Módulos afectados indicados por usuario: Home y Editar home. Archivos concretos identificados por inspección en plan-home-estilo-contacto-01.
- Contexto integrado: editor CMS oscuro existente; cards promocionales manuales y familias editoriales existentes; retirada previa del catálogo público. Conservar esas decisiones.
- Estado: PLANIFICADA, no implementada.
- Última actualización: 2026-11-15.
- Estado: IMPLEMENTADO.
- Implementada en: fix-home-estilo-contacto-01.
- Nota de aceptación: código y compilación básica verificados; datos comerciales nuevos vacíos hasta configuración del usuario. Testing y revisión visual en navegador pendientes, sin afirmar fidelidad visual comprobada.
- Complementación visual aplicada dentro de la home; parallax respeta movimiento reducido y filtro de categorías conserva control de reset.
- Foto principal presentada como fondo con degradado y contenido superpuesto tanto en el preview de Presentación como en el hero público.
- Ampliación implementada en fix-home-estilo-contacto-01: retirados los campos de botón/enlace de Presentación y el botón manual Ver novedades de la home y vista previa, según confirmación posterior. Se conservan datos guardados, contacto y acceso al panel. Sintaxis/compilación verificadas; sin ejecutar Testing.
