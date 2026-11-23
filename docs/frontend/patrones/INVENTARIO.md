# Inventario

Control de stock, Traslados, Guías, Kardex y Kardex valorizado usan la misma cabecera, controles de filtro, tabla y paginación FG que Catálogo comercial. Cada vista conserva sus columnas, acciones y reglas de consulta. [Operación y permisos](../../backend/system/modules/16_stock_management.md)

- `InventoryListing` y `InventorySkeleton` leen las columnas y los filtros de `inventoryPresentation.js`. Los parámetros iniciales usan el skeleton. Las consultas posteriores usan el indicador dentro de la tabla y ocultan temporalmente el total y la paginación.
- Los filtros reutilizan `FgFilterActions` de Productos. **Buscar** lleva lupa y texto, y **Limpiar** aparece como acción de texto. **Acciones** está en la cabecera y muestra la etiqueta con un chevrón. Los menús de fila conservan los tres puntos. En Alertas se usan **Buscar** y **Acciones**.
- **Buscar** aplica los filtros pendientes. Limpiar un campo o toda la selección conserva los resultados hasta la siguiente búsqueda. Consultas, exportaciones y páginas posteriores usan el contexto aplicado.
- Los importes de Kardex valorizado y las cantidades principales de Control de stock y Kardex se alinean a la derecha. El estado de stock se centra. La identificación de producto conserva el despliegue de códigos y las unidades se distinguen por tamaño.
- Los márgenes, bordes y tipografía reutilizan FG. El documento mantiene su ancho disponible. Las tablas anchas se desplazan en su propio contenedor. Alertas, Trazabilidad y las tablas de productos en modales tienen cabecera fija y la barra vertical aparece cuando hay contenido para desplazar.
- **Registrar operación**, **Editar stock** y **Registrar traslado** comparten la modal y los campos FG. El detalle de traslado deja la cabecera visible por encima de las pestañas Productos, Recepciones e Historial. La descarga PDF y las acciones permitidas siguen en su pie.
