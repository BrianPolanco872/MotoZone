---
entity: feature-barcode2-productos-01
type: feature
---

- Objetivo: Permitir registrar dos códigos de barras por producto y localizar el producto en el POS por cualquiera de los dos.
- Alcance: Agregar un campo dedicado barcode2 en lugar del SKU visible en el formulario de productos; aceptar ambos códigos como texto sin alterar números, ceros iniciales, símbolos ni paréntesis; buscar en POS por barcode y barcode2, no por SKU; conservar SKU internamente y generar uno para productos nuevos; mostrar los dos códigos en el catálogo de productos.
- Fuera de alcance: No tocar módulos no relacionados, el destino tras iniciar sesión ni el editor de inicio; no añadir integración o configuración específica de marcas de escáner.
- Criterios de aceptación: Crear/editar y persistir ambos códigos exactamente; poder ingresarlos con un escáner tipo teclado en el campo enfocado; buscar en POS por cualquiera de los dos y no por SKU; mantener búsqueda textual por nombre/marca; evitar códigos duplicados ambiguos.
- Solicitada por: usuario
- Estado: IMPLEMENTADO
- Implementada en: fix-barcode2-productos-01
- Aclaración adicional del usuario: Ninguno de los campos se restringe a una simbología o formato de código específico; se acepta el texto que entregue el escáner, hasta 255 caracteres.
