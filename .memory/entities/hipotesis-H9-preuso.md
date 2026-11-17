---
entity: hipotesis-H9-preuso
type: hipotesis
---

- Estado: CONFIRMADA.
- Archivo/líneas: app/Http/Controllers/Pos/DailyCutController.php:413-425; resources/views/pos/cuts/pdf.blade.php:36-84.
- Mecanismo: controller pasa snapshot.totals pero además calcula desglose actual; la plantilla consume este último, ignora totals y calcula una fórmula que incluye ventas no efectivas y omite otros conceptos del corte.
- Verificación: variables, plantilla y esquema JSON de totals contrastados. No se generaron documentos ni se validó su representación visual.
