---
entity: fix-home-destacados-precios-miles-01
type: fix
---

- Objetivo: permitir precios con separador de miles en Editar inicio > Destacados sin alterar el importe.
- Estado: IMPLEMENTADO.
- Archivos tocados: app/Http/Requests/Admin/HomeCardRequest.php; resources/views/admin/home/partials/card-form.blade.php; tests/Feature/AdminHomeContentTest.php.
- Cambios: inputs de texto numérico para regular/oferta solo en Destacados; presentación 1,299.50; normalización estricta y textual a decimal antes de validar; entrada mal formada no se convierte silenciosamente en otro precio. Se mantienen las reglas originales de límites/oferta/autorización.
- Verificación: Pint, sintaxis PHP y diff correctos. Cobertura añadida para persistencia/render, decimales, miles, límites, inválidos y alcance de secciones; no ejecutada porque Testing requiere solicitud explícita.
- Preservado: almacenamiento decimal, JS/selector existentes, home pública y otras secciones; sin cambios de inventario ni POS, esquema, dependencias o build.
- Desviaciones: ninguna.
- Limitación: sin verificación en navegador ni pruebas funcionales; no hay máscara automática durante cada pulsación, como excluía el plan.
- Fecha: 2026-11-17.
