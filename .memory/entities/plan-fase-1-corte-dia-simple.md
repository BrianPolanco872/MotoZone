---
entity: plan-fase-1-corte-dia-simple
type: plan
---

- Tipo: feature
- Estado: APROBADO
- Objetivo: Rediseñar la pantalla de "Corte del día" a una presentación simple, estilo Abarrotes PDV (una sola pantalla, sin scroll en escritorio). Solo cambia la presentación (vista Blade); los cálculos del backend, los snapshots, la verificación interna entre computadora y caja, y la BD se mantienen iguales.
- Diseño final: Barra superior (Fecha · Empleado/Corte global · Ver corte · Imprimir · Historial · Hacer corte). Dos columnas: ENTRADAS DE EFECTIVO y DINERO EN CAJA. Franja inferior fija con VENTAS TOTALES y GANANCIA DEL DÍA. Se quitan las 4 tarjetas superiores, etiquetas técnicas y texto largo del pie.
- Reglas clave: letra grande (14px+), números a la derecha, verde/entrada y rojo/salida. Solo efectivo suma a "Dinero en caja". Tarjeta/transferencia van a "Pagos de contado" pero no al total de caja. "Ventas por departamento" con categorías del inventario. Campo opcional "Efectivo contado" con resultado "✔ Cuadra" / "Faltan $X" / "Sobran $X". Barra amarilla si hay diferencia entre ventas y movimientos de caja. Responsive (columna en móvil). Ticket térmico 58/80mm con mismos bloques.
- Pasos:
  1. Leer DailyCutService para conocer estructura de $totals.
  2. Reescribir resources/views/pos/cuts/index.blade.php con el diseño simple.
  3. Ajustar DailyCutController si faltan datos para la vista.
  4. Ejecutar php artisan test (cortes) y php artisan test --filter='PosCut'.
  5. Ejecutar vendor/bin/pint.
- Archivos afectados:
  - resources/views/pos/cuts/index.blade.php (reescribir)
  - app/Http/Controllers/Pos/DailyCutController.php (ajuste menor si faltan datos)
- Criterios de aceptación: Números idénticos a antes; caso $500 inicial + $100+$250.50+$80 efectivo − $200 salida = $730.50; venta con tarjeta no cambia caja pero sí ventas totales; día sin movimientos = todo $0.00 sin errores.
- Plan de rollback: Restaurar vista Blade desde versión anterior (git checkout).
- Última actualización: 2026-11-04
- Estado: IMPLEMENTADO
- Cambios aplicados: Se reescribió resources/views/pos/cuts/index.blade.php con el diseño simple estilo Abarrotes PDV: barra superior (fecha, empleado/global, ver corte, imprimir, historial, hacer/rehacer corte), dos columnas (Entradas de efectivo y Dinero en caja con TOTAL grande), franja intermedia (Pagos de contado y Ventas por departamento), franja inferior fija (Ventas totales y Ganancia del día), alerta amarilla de diferencia entre ventas y movimientos de caja, campo opcional Efectivo contado con resultado ✔ Cuadra / Faltan $X / Sobran $X, se quitan tarjetas superiores y etiquetas técnicas. Backend, snapshots, cálculos y BD sin cambios. php artisan test (91/92; 1 fallo pre-existing ajeno) y vendor/bin/pint pasando.
- Desviaciones del plan original: Ninguna.
