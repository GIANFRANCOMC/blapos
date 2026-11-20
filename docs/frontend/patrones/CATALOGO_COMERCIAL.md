# Catálogo comercial

Productos, Servicios, Membresías, Categorías, Marcas y Recetas y platillos comparten la botonera de filtros, una tabla compacta y el paginador con el total de resultados.

| Vista | Información visible y detalle disponible |
| --- | --- |
| Productos | Código interno, código de barras cuando existe, nombre, precio de venta y estado. El icono junto al nombre abre el inventario. Los límites de precio se despliegan desde la misma celda. |
| Servicios y Membresías | Código interno, nombre, duración, precio y estado. Los cupos y la comisión aparecen en Detalle cuando corresponden. Los límites de precio usan el mismo componente de Productos. |
| Categorías y Marcas | El nombre tiene más espacio. **En uso** muestra el total de registros activos y permite consultar el conteo por cada tipo de catálogo habilitado. **Sin uso** identifica un total de cero. Marcas conserva el estado del sitio oficial en su propia columna. |
| Recetas y platillos | Código, platillo, fórmula, rendimiento y estado. La edición está en el menú de fila. El formulario usa las pestañas Datos, Insumos, Extras y Sabores. |

Los códigos se muestran como texto seleccionable. En Productos, **Descargar código de barras** está disponible desde el menú de fila cuando existe un código de barras.

Las descripciones se muestran en una línea dentro de su columna. Si se recortan, **Ver más** las expande dentro de la misma celda. **Limpiar filtros** restablece los campos y confirma mediante Toastr. La tabla conserva el resultado aplicado hasta que se pulse **Buscar**. La búsqueda sin coincidencias tiene un mensaje distinto del estado sin registros.

Todos los listados usan el mismo `CatalogListSkeleton`. La carga inicial conserva la distribución de los filtros y el ancho de su tabla. `br-filter-bar--compact` permite que las acciones bajen de línea conservando el tamaño de los botones de icono. En anchos reducidos, el desplazamiento horizontal queda dentro del contenedor de la tabla y se puede usar con teclado. Los editores con pestañas conservan el icono, el título y la descripción con una presentación compacta.

[ResponsiveTable](../../../resources/js/Shared/ResponsiveTable.vue) agrega una parada de teclado cuando hay columnas que requieren desplazamiento horizontal. En ese caso, una línea inferior indica el foco del área de desplazamiento. Si la tabla cabe, Tab y Mayús + Tab pasan entre sus controles.

## Importar registros

Productos, Servicios, Membresías, Categorías y Marcas usan [FileImportModal](../../../resources/js/System/Components/Generics/FileImportModal.vue). El primer bloque reúne las instrucciones y la descarga de la plantilla Excel. El segundo muestra el archivo seleccionado y permite cambiarlo. En Productos también pide el almacén al que ingresará el stock inicial. Cada entidad conserva sus reglas de campos, prefijos y validación. Los estilos de importación viven en [Shared](../../../resources/css/Shared/file-import.css).

La acción **Importar** identifica los registros y muestra qué se está procesando. Si el archivo tiene errores, la modal conserva el archivo para consultar el detalle o sustituirlo. Al confirmar una importación, el [patrón compartido de resultados](../fundamentos/ESTANDARES_UI.md#resultado-de-operaciones-en-modales) cierra la modal antes de que aparezca el conteo del servidor. Si se interrumpe la comunicación, se pide comprobar el listado antes de repetir la importación.

## Agregar, editar y descargar

- **Agregar** abre el formulario y **Guardar** nombra la entidad nueva. **Editar** nombra la entidad desde el menú de fila y **Guardar cambios** guarda la edición. **Cerrar** se mantiene en la misma posición en los editores.
- [FormGroup](../../../resources/js/Shared/FormGroup.vue) agrupa campos relacionados por espacio, título e icono sobre la superficie del formulario. El azul destaca el título y el icono de Identificación. El encabezado va al costado en escritorio y encima de los campos en pantallas estrechas. En Inventario y las listas editables de Recetas, el encabezado superior deja el ancho completo para los registros y su acción. Reutiliza [los estilos de Shared](../../../resources/css/Shared/form-sections.css).
- En Productos, Servicios y Membresías, la primera pestaña reúne identificación, precios y estado, e información comercial. Allí se editan la descripción, las categorías y, donde corresponde, la marca, los beneficios y las restricciones. **Configuración** reúne la aplicación del IGV, la vigencia, la comisión y la visibilidad. En Servicios y Membresías también agrupa los límites de atención.
- **Descargar listado en Excel** confirma si se descargará el listado completo o los resultados de la búsqueda aplicada. La descarga usa esa búsqueda aunque el texto del filtro todavía se esté editando. El aviso confirma el inicio de la descarga en el navegador. [Flujo de descarga](../../../resources/js/System/Components/Catalogs/downloadCatalogList.js)

## Visibilidad para clientes

[CatalogVisibilityFields](../../../resources/js/System/Components/Catalogs/CatalogVisibilityFields.vue) agrupa la publicación de Productos, Servicios y Membresías y la visibilidad del precio. Cuando el registro está activo y publicado, ocultar el importe muestra «Precio a consultar» en el catálogo público. Desactivar la publicación apaga y deshabilita el control de precio. Los campos se aplican al guardar el formulario.

La [skill de frontend](../../../.agents/skills/blapos-frontend/SKILL.md#consulta-antes-de-cambios-drásticos) indica cuándo consultar al usuario antes de alterar la navegación, los pasos o el alcance de un flujo. Reutiliza los patrones de Shared para ajustes de presentación y conserva en el CSS del módulo las composiciones específicas, como la edición de insumos y variantes de una receta.

Para extender el patrón, reutiliza [FiltersSection](../../../resources/js/System/Components/Generics/FiltersSection.vue), [CatalogPriceCell](../../../resources/js/System/Components/Catalogs/CatalogPriceCell.vue), [CatalogUsageBreakdown](../../../resources/js/System/Components/Catalogs/CatalogUsageBreakdown.vue) y [AppTabs](../../../resources/js/Shared/AppTabs.vue). Para la lógica de cada entidad, consulta las [fichas de System](../../backend/system/modules).
