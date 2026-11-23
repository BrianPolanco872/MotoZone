---
entity: fix-corte-navidad-panel-01
type: fix
---

- Objetivo: reutilizar el panel sin usar "Ventas por departamento" del corte del día para mostrar solo las ventas de productos de la categoría Navidad (navidad/Navidad, sin importar mayúsculas), con los productos vendidos, el importe pagado y el total sumado. No debe afectar entradas ni salidas.
- Archivos tocados: app/Services/DailyCutService.php; resources/views/pos/cuts/index.blade.php; tests/Feature/PosCutTest.php.
- Cambios aplicados: build() sigue recorriendo las mismas ventas pagadas del día y ahora acumula, aparte, los items cuyo category_name (snapshot al vender) es 'navidad' tras mb_strtolower+trim. Agrupa por product_name, suma cantidad (Quantity::milli/number) e importe con line_total, ordena por importe y expone navidad_products y navidad_total. No se tocaron los calculos de caja, entradas, salidas ni totales existentes.
- Vista: el panel paso a llamarse "Ventas de Navidad", con columnas Producto / Cantidad vendida / Importe, fila "Total Navidad" con la suma del panel y estado vacio "Sin ventas de Navidad hoy". Se elimino el uso de $departments en ese panel; $departments sigue disponible para la vista de impresion.
- Verificacion: prueba nueva test_navidad_panel_shows_only_navidad_products_and_their_total (case-insensitive, ignora otra categoria, total 125.00) pasa; PosCutTest completo 13/13 con 61 aserciones; Pint pasa. Sin build: solo cambio Blade/PHP, sin JS ni CSS.
- Nota: la seccion "Desglose por departamento" de la vista de impresion no se cambio; el pedido se referia al panel de la pantalla del corte.
- Ajuste posterior: el usuario pidio que el panel se llame "Ventas segunda tienda"; se cambiaron el titulo y el estado vacio ("Sin ventas de segunda tienda hoy"), y las aserciones del test. El filtro de datos (categoria navidad, sin importar mayusculas) y el total sumado quedaron iguales. PosCutTest 13/13; Pint pasa.
- Fecha: 2026-11-22.
- Estado: IMPLEMENTADO.
