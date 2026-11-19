# Catálogo comercial

Productos, Servicios, Membresías, Categorías, Marcas y Recetas y platillos comparten la botonera de filtros, una tabla compacta y el paginador con el total de resultados.

| Vista | Información visible y detalle disponible |
| --- | --- |
| Productos | Código interno, código de barras cuando existe, nombre, precio de venta y estado. El icono junto al nombre abre el inventario. Los límites de precio se despliegan desde la misma celda. |
| Servicios y Membresías | Código interno, nombre, duración, precio y estado. Los cupos y la comisión aparecen en Detalle cuando corresponden. Los límites de precio usan el mismo componente de Productos. |
| Categorías y Marcas | El nombre tiene más espacio. **En uso** muestra el total de registros activos y permite consultar el conteo por cada tipo de catálogo habilitado. **Sin uso** identifica un total de cero. Marcas conserva el estado del sitio oficial en su propia columna. |
| Recetas y platillos | Código, platillo, fórmula, rendimiento y estado. La edición está en el menú de fila. El formulario usa las pestañas Datos, Insumos, Extras y Sabores. |

Los códigos se muestran como texto seleccionable. En Productos, **Descargar cód. barras** está disponible desde el menú de fila cuando existe un código de barras.

Las descripciones se muestran en una línea dentro de su columna. Si se recortan, **Ver más** las expande dentro de la misma celda. **Limpiar filtros** restablece los campos y confirma mediante Toastr. La tabla conserva el resultado aplicado hasta que se pulse **Buscar**. La búsqueda sin coincidencias tiene un mensaje distinto del estado sin registros.

Todos los listados usan el mismo `CatalogListSkeleton`. La carga inicial conserva la distribución de los filtros y el ancho de su tabla. `br-filter-bar--compact` permite que las acciones bajen de línea conservando el tamaño de los botones de icono. En anchos reducidos, el desplazamiento horizontal queda dentro del contenedor de la tabla y se puede usar con teclado. Los editores con pestañas conservan el icono, el título y la descripción con una presentación compacta.

Para extender el patrón, reutiliza [FiltersSection](../../../resources/js/System/Components/Generics/FiltersSection.vue), [CatalogPriceCell](../../../resources/js/System/Components/Catalogs/CatalogPriceCell.vue), [CatalogUsageBreakdown](../../../resources/js/System/Components/Catalogs/CatalogUsageBreakdown.vue) y [AppTabs](../../../resources/js/Shared/AppTabs.vue). Para la lógica de cada entidad, consulta las [fichas de System](../../backend/system/modules).
