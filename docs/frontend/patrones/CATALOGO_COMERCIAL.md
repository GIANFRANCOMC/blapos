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

Servicios, Membresías, Categorías y Marcas usan [FgCatalogList](../../../resources/js/System/Components/Catalogs/FgCatalogList.vue) con la cabecera, filtros, acciones, tabla, estados y paginación de la interfaz FG de Productos. Cada listado conserva sus filtros y columnas. Las columnas de estas vistas se declaran en [catalogColumns.js](../../../resources/js/System/Components/Catalogs/catalogColumns.js), que también consume `CatalogListSkeleton`. El orden seleccionable de Productos usa su propia consulta.

[ResponsiveTable](../../../resources/js/Shared/ResponsiveTable.vue) agrega una parada de teclado cuando hay columnas que requieren desplazamiento horizontal. En ese caso, una línea inferior indica el foco del área de desplazamiento. Si la tabla cabe, Tab y Mayús + Tab pasan entre sus controles.

## Importar registros

Productos, Servicios, Membresías, Categorías y Marcas usan [FileImportModal](../../../resources/js/System/Components/Generics/FileImportModal.vue). El primer bloque reúne las instrucciones y la descarga de la plantilla Excel. El segundo muestra el archivo seleccionado y permite cambiarlo. En Productos también pide el almacén al que ingresará el stock inicial. Cada entidad conserva sus reglas de campos, prefijos y validación. Los estilos de importación viven en [Shared](../../../resources/css/Shared/file-import.css).

La acción **Importar** identifica los registros y muestra qué se está procesando. Si el archivo tiene errores, la modal conserva el archivo para consultar el detalle o sustituirlo. Al confirmar una importación, el [patrón compartido de resultados](../fundamentos/ESTANDARES_UI.md#resultado-de-operaciones-en-modales) cierra la modal antes de que aparezca el conteo del servidor. Si se interrumpe la comunicación, se pide comprobar el listado antes de repetir la importación.

## Agregar, editar y descargar

- **Agregar** abre el formulario y **Guardar** nombra la entidad nueva. **Editar** nombra la entidad desde el menú de fila y **Guardar cambios** guarda la edición. **Cerrar** se mantiene en la misma posición en los editores.
- Los editores reutilizan `FormModal`, `FgField`, `FgSelect` y las composiciones `fg-stack` y `fg-grid`. Productos, Servicios y Membresías muestran [los controles de IGV](../../../resources/js/System/Components/Catalogs/CatalogTaxFields.vue) justo después del precio en su pestaña principal. Al activar la exoneración, **Precio incluye IGV** se desmarca y se deshabilita.
- **Configuración** empieza con publicación y visibilidad del precio. Después aparecen vigencia y comisión, y los cupos cuando corresponden. Membresías conserva su unidad de duración y su límite diario de asistencias. Los errores de descripción, beneficios, restricciones y límites de precio abren el apartado que corresponda.
- Categorías y Marcas reúnen sus datos en una sola vista y muestran **Código interno** primero. Marcas incluye el sitio oficial. El alta rápida desde otro formulario conserva el código generado y pide nombre y descripción. El control de stock por almacén pertenece a Productos.
- Los [placeholders compartidos](../../../resources/js/System/Components/Catalogs/catalogFormPlaceholders.js) mantienen el mismo texto para precio de venta, mínimo, máximo, descripción opcional y selector de categorías. El nombre muestra un ejemplo según la entidad.
- **Descargar listado en Excel** confirma si se descargará el listado completo o los resultados de la búsqueda aplicada. La descarga usa esa búsqueda aunque el texto del filtro todavía se esté editando. El aviso confirma el inicio de la descarga en el navegador. [Flujo de descarga](../../../resources/js/System/Components/Catalogs/downloadCatalogList.js)

## Visibilidad para clientes

[CatalogVisibilityFields](../../../resources/js/System/Components/Catalogs/CatalogVisibilityFields.vue) muestra la publicación y la visibilidad del precio con los mismos controles y textos en Productos, Servicios y Membresías. Cuando el registro está activo y publicado, ocultar el importe muestra «Precio a consultar» en el catálogo público. Desactivar la publicación deshabilita **Mostrar precio**. Si se vuelve a activar antes de guardar, se recupera la elección previa. Guardar con la publicación desactivada registra el precio como oculto.

La [skill de frontend](../../../.agents/skills/blapos-frontend/SKILL.md#consulta-antes-de-cambios-drásticos) indica cuándo consultar al usuario antes de alterar la navegación, los pasos o el alcance de un flujo. Reutiliza los patrones de Shared para ajustes de presentación y conserva en el CSS del módulo las composiciones específicas, como la edición de insumos y variantes de una receta.

Para extender el patrón, reutiliza `FgCatalogList` y los componentes [CatalogPriceCell](../../../resources/js/System/Components/Catalogs/CatalogPriceCell.vue), [CatalogUsageBreakdown](../../../resources/js/System/Components/Catalogs/CatalogUsageBreakdown.vue) y [AppTabs](../../../resources/js/Shared/AppTabs.vue). Para la lógica de cada entidad, consulta las [fichas de System](../../backend/system/modules).
