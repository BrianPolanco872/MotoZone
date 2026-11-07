# MotoZone — Design System y Especificaciones Visuales del POS

> **Propósito:** Este documento es la referencia visual única para el rediseño de MotoZone.
> Se usa como entrada para crear las pantallas en Google Stitch y, posteriormente, como guía de implementación en Blade/Tailwind.
>
> **Estado:** Solo especificación. No se ha modificado ningún archivo del proyecto Laravel.
> No se generaron diseños reales en Stitch desde este entorno.

---

## 1. Identidad Visual

| Concepto | Valor |
|----------|-------|
| Nombre del sistema | MotoZone |
| Concepto | Tecnología, velocidad, potencia, confianza, profesionalismo |
| Ámbito | Punto de venta, caja, cortes, inventario, administración y catálogo público |
| Modo visual principal | Oscuro (dark mode) para POS y administración |
| Modo secundario | Claro para el catálogo público |

---

## 2. Paleta de Colores

| Token | Hex | Uso |
|-------|-----|-----|
| `--color-bg-primary` | `#111318` | Fondo principal de aplicaciones internas (POS, admin) |
| `--color-bg-surface` | `#1C2028` | Superficies elevadas, tarjetas, modales, paneles |
| `--color-bg-surface-2` | `#292F39` | Superficies secundarias, inputs, bordes de tarjetas |
| `--color-accent-primary` | `#F97316` | Botones primarios, enlaces activos, totales principales, marca |
| `--color-accent-hover` | `#EA580C` | Hover de botones primarios |
| `--color-text-primary` | `#FFFFFF` | Texto principal sobre fondos oscuros |
| `--color-text-secondary` | `#E5E7EB` | Texto secundario, placeholders, descripciones |
| `--color-text-muted` | `#9CA3AF` | Texto auxiliar, estados inactivos, etiquetas pequeñas |
| `--color-success` | `#10B981` | Éxito, stock disponible, conciliado |
| `--color-error` | `#EF4444` | Error, faltante, rechazado, eliminado |
| `--color-warning` | `#F59E0B` | Advertencia, stock bajo, pendiente |
| `--color-border` | `#292F39` | Bordes discretos en dark mode |
| `--color-border-light` | `#374151` | Bordes de inputs y tarjetas |
| `--color-shadow-sm` | `0 1px 2px 0 rgba(0,0,0,0.05)` | Inputs activos |
| `--color-shadow-md` | `0 4px 6px -1px rgba(0,0,0,0.1)` | Tarjetas, tablas |
| `--color-shadow-lg` | `0 10px 15px -3px rgba(0,0,0,0.1)` | Modales, overlays |

**Nota:** El catálogo público puede usar fondo claro (`#FFFFFF` o `#F9FAFB`) con texto oscuro (`#111827`), manteniendo `#F97316` como acento de marca.

---

## 3. Tipografía

| Elemento | Fuente sugerida | Peso | Uso |
|----------|----------------|------|-----|
| Títulos y encabezados | *Inter* o *Plus Jakarta Sans* | 600–700 | Títulos de página, secciones |
| Cuerpo de texto | *Inter* o *DM Sans* | 400–500 | Descripciones, etiquetas, mensajes |
| Códigos (SKU, folio, código de barras) | *JetBrains Mono* o *Courier New* | 500 | SKUs, folios, códigos de barras, precios unitarios en tablas |
| Números monetarios | *Inter* (tabular-nums) | 600–700 | Totales, subtotales, precios grandes |

**Tamaño mínimo:** `14px` para texto principal, `12px` para texto auxiliar.

---

## 4. Espaciado (escala base 4px)

| Token | Valor | Uso |
|-------|-------|-----|
| `--space-1` | 4px | Margen interno mínimo |
| `--space-2` | 8px | Padding de botones pequeños |
| `--space-3` | 12px | Gap entre elementos relacionados |
| `--space-4` | 16px | Padding estándar de tarjetas y inputs |
| `--space-5` | 20px | Separación de secciones dentro de una tarjeta |
| `--space-6` | 24px | Padding de secciones grandes |
| `--space-8` | 32px | Separación de módulos principales |
| `--space-12` | 48px | Márgenes de página |

---

## 5. Bordes y Sombras

| Token | Valor | Uso |
|-------|-------|-----|
| `--radius-sm` | 6px | Inputs, badges pequeños |
| `--radius-md` | 8px | Botones, tarjetas |
| `--radius-lg` | 12px | Modales, contenedores principales |
| `--shadow-sm` | 0 1px 2px 0 rgba(0,0,0,0.05) | Inputs activos, elementos interactivos |
| `--shadow-md` | 0 4px 6px -1px rgba(0,0,0,0.1) | Tarjetas, tablas, paneles |
| `--shadow-lg` | 0 10px 15px -3px rgba(0,0,0,0.1) | Modales, overlays, diálogos |

---

## 6. Componentes del Sistema de Diseño

### Botones (`x-button`)
| Variante | Fondo | Texto | Borde | Hover |
|----------|-------|-------|-------|-------|
| Primario | `#F97316` | Blanco | — | `#EA580C` |
| Secundario | Transparente | Blanco | `#292F39` | `#1C2028` |
| Fantasma | Transparente | `#E5E7EB` | — | `#1C2028` |
| Éxito | `#10B981` | Blanco | — | `#059669` |
| Peligro | `#EF4444` | Blanco | — | `#DC2626` |
| Advertencia | `#F59E0B` | Blanco | — | `#D97706` |

Todos los botones: `radius-md`, padding `8px 16px`, texto `font-medium`, transición `150ms`, cursor `not-allowed` cuando están deshabilitados.

### Inputs (`x-input`)
- Fondo: `#292F39` (dark)
- Borde: `#374151`, `radius-md`
- Texto: `#FFFFFF`
- Placeholder: `#9CA3AF`
- Focus: borde `#F97316`, sombra `0 0 0 2px rgba(249,115,22,0.2)`
- Error: borde `#EF4444`, texto de error `#EF4444` debajo del campo

### Tablas (`x-table`)
- Encabezado: fondo `#1C2028`, texto `#E5E7EB`, texto pequeño uppercase
- Cuerpo: fondo `#111318`, texto `#FFFFFF`
- Filas: borde inferior `#292F39`, hover `#1C2028`
- Alineación: numéricos a la derecha, texto a la izquierda

### Tarjetas (`x-card`)
- Fondo: `#1C2028`
- Borde: `#292F39`
- Radio: `8px`
- Sombra: `0 4px 6px -1px rgba(0,0,0,0.1)`
- Padding: `16px`

### Badges (`x-badge`)
| Estado | Fondo | Texto |
|--------|-------|-------|
| Disponible | `#10B981` (20% opacidad) | `#10B981` |
| Stock bajo | `#F59E0B` (20% opacidad) | `#F59E0B` |
| Agotado | `#6B7280` (20% opacidad) | `#6B7280` |
| Pendiente | `#F59E0B` (20% opacidad) | `#F59E0B` |
| Aprobado | `#10B981` (20% opacidad) | `#10B981` |
| Rechazado | `#EF4444` (20% opacidad) | `#EF4444` |
| Archivado | `#F59E0B` (15% opacidad) | `#F59E0B` |

### Alertas (`x-alert`)
- Éxito: fondo verde 10%, borde verde 30%, texto verde
- Error: fondo rojo 10%, borde rojo 30%, texto rojo
- Advertencia: fondo amarillo 10%, borde amarillo 30%, texto amarillo

### Modales (`x-modal`)
- Overlay: fondo negro 60% opacidad
- Contenedor: fondo `#1C2028`, radio `12px`, sombra grande
- Header: borde inferior, título semibold
- Footer: borde superior, acciones alineadas a derecha

### Navegación (sidebar + header admin)
- Sidebar: fondo `#111318`, ancho `256px`, fijo en desktop
- Íconos activos: naranja `#F97316`
- Íconos inactivos: gris `#9CA3AF`
- Header: fondo `#1C2028`, sticky top, borde inferior sutil

---

## 7. Especificaciones Visuales del Punto de Venta (POS)

**Distribución principal:** Dos paneles.
- Panel izquierdo (aprox. 35%): Búsqueda de productos, resultados rápidos, productos frecuentes.
- Panel derecho (aprox. 65%): Carrito de compras, resumen de totales, acciones de cobro.

> **Nota sobre datos:** Todos los productos, precios, folios, usuarios y cantidades mostrados en los ejemplos de esta sección son **datos ficticios de demostración**. No representan información real del sistema.

### Pantalla 1: Pantalla Principal del POS

**Objetivo:** Interfaz principal de venta, optimizada para computadoras de escritorio.

**Distribución:**
```
┌─────────────────────────────────────────────────────────────────────┐
│  BARRA SUPERIOR (sticky)                                            │
│  [Logo MotoZone]  [Usuario: Juan Pérez]  [Caja: Abierta ●]         │
│  [06/11/2026 14:32]  [Menú acciones ⋮]                              │
├──────────────┬──────────────────────────────────────────────────────┤
│              │                                                      │
│  PANEL       │  PANEL DERECHO (Carrito y Cobro)                     │
│  IZQUIERDO   │                                                      │
│  (35%)       │  ┌──────────────────────────────────────────────┐   │
│              │  │  CARRITO DE COMPRAS                          │   │
│  ┌─────────┐ │  │  Código   │ Descripción        │ Cant │ Imp. │   │
│  │ BÚSQUEDA│ │  │  ──────── │ ─────────────────  │ ──── │ ────│   │
│  │         │ │  │  MOT-001  │ Kit de cilindro    │ 2    │ $3, │   │
│  │ [▔▔▔▔▔] │ │  │           │ 150 cc Bajaj       │      │ 780 │   │
│  │         │ │  │           │ [+][-]             │ [×]  │     │   │
│  │ Resultados│ │  │  BAL-002  │ Balatas DT175    │ 1    │ $320│   │
│  │ rápidos   │ │  │           │ Yamaha           │ [+][-]│     │   │
│  │ (dropdown)│ │  │           │                  │ [×]  │     │   │
│  └─────────┘ │  │           ...                                              │   │
│              │  ├──────────────────────────────────────────────┤   │
│  ┌─────────┐ │  │  RESUMEN Y TOTALES                           │   │
│  │ FRECUENT│ │  │  Subtotal     $4,100.00                      │   │
│  │ ES      │ │  │  IVA              $656.00                    │   │
│  │ ● Prod. │ │  │                                                │   │
│  │   A $XXX│ │  │  TOTAL        $4,756.00  (grande, naranja)   │   │
│  │ ● Prod. │ │  │                                                │   │
│  │   B $XXX│ │  │  [Eliminar venta] [Dejar pendiente]          │   │
│  └─────────┘ │  │                                                │   │
│              │  │  [COBRAR — botón grande primario naranja]    │   │
│              │  └──────────────────────────────────────────────┘   │
└──────────────┴──────────────────────────────────────────────────────┘
```

**Elementos:**

**Barra superior:**
- Tipo: Header fijo (sticky top), altura 64px
- Fondo: `#1C2028`, texto `#FFFFFF`
- Elementos: Logo MotoZone, nombre de usuario, indicador de estado de caja (badge verde si abierta, rojo si cerrada), fecha y hora, menú de acciones

**Panel izquierdo — Búsqueda:**
- Campo de búsqueda con autofoco al cargar
- Placeholder: `"Escanear código de barras, SKU, nombre o marca"`
- Dropdown de resultados rápidos debajo del input (máximo 8 productos)
- Cada resultado: miniatura, nombre, marca, SKU, precio, badge de stock, botón `+`
- Checkbox "Mayoreo": solo visible si el usuario tiene permiso `pos.wholesale`
- Productos frecuentes: lista con nombre truncado, precio, botón de agregar rápido

**Panel derecho — Carrito y cobro:**
- Tabla del carrito con columnas: Código, Descripción, Cantidad (input con `+` y `-`), Importe, Existencia (badge verde/amarillo/gris), Quitar (icono papelera)
- Precio editable: solo si permiso `pos.price.edit` (indicar con ícono lápiz gris)
- Resumen de totales fijo al hacer scroll: Subtotal, IVA (valores exactos del backend, no asumir 16%), TOTAL (grande, naranja)
- Botones: "Eliminar venta", "Dejar pendiente", "COBRAR" (primario, ancho completo)

**Estados:**
- Carrito vacío: icono carrito grande, texto "La venta está vacía", subtítulo para buscar productos
- Carga: skeleton en resultados y filas del carrito

---

### Pantalla 2: Resultados de Búsqueda de Productos

**Objetivo:** Mostrar productos encontrados tras buscar por código de barras, SKU, nombre o marca.

**Distribución:** Variante dentro de la pantalla principal del POS (dropdown o panel lateral).

```
┌─────────────────────────────────────────────────────────────────────┐
│  BÚSQUEDA: "kit de cilindro"                                        │
│  [▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔]                               │
│                                                                     │
│  Resultados (3)                                                     │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐                               │
│  │ Imagen  │ │ Imagen  │ │ Imagen  │                               │
│  │         │ │         │ │         │                               │
│  │ Kit cil.│ │ Carbur. │ │ Pistón  │                               │
│  │ $1,890  │ │ $2,450  │ │ $950.00 │                               │
│  │ Stock: 12│ │ Stock: 4 │ │ Stock: 7 │                               │
│  │ [+ Agregar]│ │ [+ Agregar]│ │ [+ Agregar]│                         │
│  └─────────┘ └─────────┘ └─────────┘                               │
└─────────────────────────────────────────────────────────────────────┘
```

**Elementos:**
- Contador de resultados: "Resultados (3)"
- Grid de tarjetas de producto: imagen, nombre, marca, precio, badge de stock, botón `+ Agregar`
- Hover en tarjeta: sombra elevada, borde naranja sutil
- Estado vacío: icono búsqueda, texto "No se encontraron productos"

---

### Pantalla 3: Carrito con Varios Productos

**Objetivo:** Variante visual de la pantalla principal del POS donde el carrito contiene productos agregados. No es una vista independiente obligatoria; es el mismo layout con el carrito poblado.

**Distribución:** Misma distribución de dos paneles de la Pantalla 1.

```
┌─────────────────────────────────────────────────────────────────────┐
│  CARRITO DE COMPRAS — 3 artículos                                   │
│  Código   │ Descripción              │ Cant │ Importe   │ Exist.   │
│  ──────── │ ─────────────────────    │ ──── │ ────────  │ ──────── │
│  MOT-001  │ Kit de cilindro 150 cc   │ 2    │ $3,780.00 │ ● Dispon │
│           │ Bajaj                    │ [+][-]│           │          │
│  ──────── │ ─────────────────────    │ ──── │ ────────  │ ──────── │
│  BAL-002  │ Balatas DT175 Yamaha     │ 1    │ $320.00   │ ● Dispon │
│           │                        │ [+][-]│           │          │
│  ──────── │ ─────────────────────    │ ──── │ ────────  │ ──────── │
│  BUJ-003  │ Bujía NGK CR7HSA         │ 3    │ $285.00   │ ● Bajo   │
│           │ NGK                    │ [+][-]│           │          │
└─────────────────────────────────────────────────────────────────────┘
│  Subtotal      $4,385.00                                            │
│  IVA             $701.60                                            │
│  ─────────────────────────────                                        │
│  TOTAL        $5,086.60                                             │
│                                                                     │
│  [Eliminar venta] [Dejar pendiente]                                 │
│                                                                     │
│  [COBRAR — botón grande naranja]                                    │
└─────────────────────────────────────────────────────────────────────┘
```

**Elementos:**
- Contador de artículos en la parte superior de la tabla
- Cada fila: código (font mono), descripción, cantidad con controles `+` y `-`, importe, badge de existencia, botón quitar
- Resumen: Subtotal, IVA (valor exacto del backend), TOTAL
- Botones de acción secundaria y botón primario "COBRAR"

---

### Pantalla 4: Modal de Cobro

**Objetivo:** Diálogo para finalizar la venta. Se abre al hacer clic en "COBRAR".

**Distribución:** Modal centrado con overlay oscuro.

```
┌─────────────────────────────────────────────────────────────────────┐
│  Cobrar venta                       [X]                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Método de pago: [Efectivo ▼]                                       │
│                                                                     │
│  Total a cobrar:                                                    │
│  $4,756.00                                                          │
│                                                                     │
│  Pago con: [__________.00]                                          │
│                                                                     │
│  [Exacto]  [$100]  [$200]  [$500]                                   │
│                                                                     │
│  Cambio:                                                            │
│  $0.00                                                              │
│                                                                     │
│  [Cancelar]                    [Confirmar cobro]                    │
└─────────────────────────────────────────────────────────────────────┘
```

**Elementos:**
- Overlay: fondo negro 60% opacidad
- Modal: fondo `#1C2028`, radio `12px`, sombra grande, ancho máximo 560px
- Selector de método de pago: Efectivo, Tarjeta, Transferencia, Mixto
  - **Nota:** Tarjeta, transferencia y mixto son solo etiquetas del backend. No simular integraciones bancarias.
- Campo "Pago con": visible solo para Efectivo o Mixto
- Botones rápidos: "Exacto", "$100", "$200", "$500"
- Campo "Cambio": cálculo simple (resta), visible solo para Efectivo o Mixto
- Validación visual: borde rojo si el pago es menor al total, mensaje de error debajo
- Botones footer: "Cancelar" (secundario), "Confirmar cobro" (primario naranja)

**Variantes por método de pago:**
| Método | Campos visibles |
|--------|-----------------|
| Efectivo | Total, Pago con, Cambio, botones rápidos |
| Tarjeta | Total solamente |
| Transferencia | Total solamente (campo opcional de referencia) |
| Mixto | Total, pago en efectivo, otro método, suma debe igualar total |

---

### Pantalla 5: Ventas Pendientes

**Objetivo:** Listar ventas en estado `pendiente` para recuperarlas y cobrarlas.

**Distribución:** Pantalla completa dentro del módulo POS.

```
┌─────────────────────────────────────────────────────────────────────┐
│  VENTAS PENDIENTES                                                  │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ Folio     │ Fecha       │ Cajero   │ Total   │ Acciones    │   │
│  │ ────────  │ ──────────  │ ──────── │ ─────── │ ────────    │   │
│  │ VEN-001   │ 06/11/2026  │ Juan     │ $450.00 │ [Cobrar]    │   │
│  │ VEN-002   │ 06/11/2026  │ María    │ $1,200  │ [Cobrar]    │   │
│  │ VEN-003   │ 05/11/2026  │ Carlos   │ $890.00 │ [Cobrar]    │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  [Nueva venta]                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Elementos:**
- Tabla: Folio, Fecha, Cajero, Total, Acciones
- Botón "Cobrar" por fila: naranja sutil o texto naranja
- Estado vacío: icono pausa, texto "No hay ventas pendientes"
- Botón "Nueva venta": naranja `#F97316`, texto blanco

---

## 8. Restricciones Obligatorias

1. **No asumir IVA del 16 %:** Mostrar "Impuestos" o "IVA" con el valor exacto que entregue el backend. No agregar cálculos fiscales nuevos.
2. **Métodos de pago reales:** Tarjeta, transferencia y mixto son solo etiquetas existentes en `StoreSaleRequest` y `ChargePendingSaleRequest`. No simular terminales bancarias, pasarelas ni flujos de pago externo.
3. **No agregar funcionalidades inexistentes:** El diseño debe adaptarse exactamente a las capacidades verificadas en el código (controladores, servicios, modelos, rutas). No inventar campos, botones ni permisos.
4. **Conservar lógica del carrito:** Cantidades, precios, descuentos, mayoreo y ventas pendientes se mantienen tal como existen actualmente.
5. **Impresión de ticket:** No cambiar la lógica de impresión ni el contenido fiscal del ticket.
6. **Datos ficticios:** Todos los ejemplos de productos, precios, folios, usuarios y cantidades son datos de demostración.
7. **Carrito como variante:** La pantalla de carrito con productos es una variante visual de la pantalla principal del POS, no una vista independiente.
8. **Distribución de dos paneles:** Búsqueda y productos a la izquierda; carrito y cobro a la derecha.
9. **Diseño oscuro y naranja:** Priorizar legibilidad, rapidez de operación y aprovechamiento del espacio. No parecer un dashboard administrativo.
10. **No modificar archivos:** Este documento es solo especificación. No se han tocado archivos del proyecto Laravel.

---

## 9. Comportamiento Responsive (Prioridad: Desktop)

| Breakpoint | Comportamiento |
|------------|----------------|
| **Desktop (>1024px)** | Layout de dos columnas, carrito fijo al scroll, búsqueda prioritaria. |
| **Tablet (768–1024px)** | Una columna apilada: búsqueda arriba, carrito en medio, cobro abajo. |
| **Móvil (<768px)** | Una columna, botones apilados, inputs de ancho completo, diálogo de cobro en pantalla completa. |

---

## 10. Componentes Específicos del POS (a crear en implementación)

| Componente | Propósito |
|------------|-----------|
| `x-pos-search` | Barra de búsqueda con dropdown de resultados |
| `x-pos-cart-item` | Fila del carrito con cantidad, precio, acciones |
| `x-pos-cart-total` | Resumen de subtotal, impuestos, total |
| `x-pos-payment-dialog` | Modal de cobro con métodos de pago |
| `x-pos-stock-badge` | Indicador de existencia (verde/amarillo/gris) |

---

## 11. Siguiente Paso

1. Crear las cinco pantallas en Google Stitch siguiendo estas especificaciones.
2. Compartir las capturas de pantalla para evaluación.
3. Una vez aprobado el diseño visual, iniciar la **Fase 1** de implementación en Laravel (sistema de diseño y layouts sin tocar backend).

**No se ha modificado ningún archivo del proyecto Laravel hasta este momento.**