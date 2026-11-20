# Implementación FG · System y Productos

Actualizada: 20 de noviembre de 2026. Alcance: frontend del ERP tenant (System). Se conservan APIs, permisos, contexto de empresa, cálculos y validaciones de negocio.

## 1. Un solo sistema visual

FG es el estándar global de System. No hay banderas por Productos ni proveedores de tema por componente. El layout carga siempre la navegación y el navbar FG.

La fuente editable está en `resources/css/System/fg/`:

- `00-tokens.css`: colores, fuente, tamaños, pesos, alturas, bordes y espacios.
- `components.css`: controles, botones, pestañas, badges, modales, paginación y estados.
- `navigation.css`: mundos, submódulos, navbar y contenedor común.
- `feedback.css`: SweetAlert, capas y tooltips.
- `products.css`: composición del listado, edición, inventario e importación.

El entry `index.css` se enlaza desde el head con Vite, antes de montar Vue, para evitar la carga tardía de estilos desde JavaScript. En producción Vite genera CSS y fuentes con hash. Source Sans 3 está alojada en `resources/fonts/`, con licencia OFL; no requiere Google Fonts en ejecución.

## 2. Componentes y convenciones

Cambiar primero el componente o token compartido; después revisar sus consumidores. Todas las clases y variables nuevas usan `fg-` y `--fg-*`. Evitar paletas locales, duplicar controles e introducir `!important`.

| Necesidad | Fuente reutilizable |
| --- | --- |
| Botones y carga | Figma/FgButton.vue, clases fg-button |
| Etiqueta, obligatorio, ayuda y error asociado | Figma/FgField.vue |
| Selección única o múltiple con vue-select | Figma/FgSelect.vue |
| Texto, número, fecha y descripción | InputBase, InputText, InputNumber, InputDate, InputTextArea |
| Select nativo y adaptador Select2 | InputSelect, InputSelect2 |
| Pestañas con navegación por teclado | Shared/AppTabs.vue |
| Badge de estado | Generics/StatusBadge.vue y Helpers/ModuleConstants.js |
| Modal | Generics/FormModal.vue |
| Acciones | Generics/ActionsButton.vue, ActionsDropdown.vue |
| Descripción expandible | Generics/ExpandableText.vue |
| Vacío genérico | Figma/FgEmptyState.vue |
| Paginación y resultados | Paginator.vue, Generics/ListResultCount.vue |
| Importación | Generics/FileImportModal.vue |
| Confirmación, carga y resultado | Helpers/Alerts.js |
| Tooltips | Helpers/TooltipOptions.js |

Las rutas Vue son relativas a `resources/js/System/Components/`, excepto Shared y Helpers.

- Icono y loader heredan el color del texto del botón. La excepción aprobada es la carpeta amarilla junto a Seleccionar archivo.
- Los badges comparten texto oscuro: activo/saludable tiene fondo verde, inactivo/stock bajo amarillo y agotado rojo.
- Los campos con límite muestran el contador dentro del control. Pasar el límite real del contrato; el código interno incluye su prefijo en el contador.
- Categorías admite múltiples opciones; Marca admite una.
- Comisión muestra `%`, el signo de moneda o un campo deshabilitado según corresponda. El desplegable flota sobre el formulario, sin reservar espacio ni desplazar campos.
- Carga, validación, éxito y error describen la operación. Conservar el bloqueo de acciones duplicadas y el foco de los controles.

## 3. Navegación global y dependencias

`System/layouts/main.blade.php` incluye fg-navigation, fg-navbar, fg-up y fg-down para todos sus consumidores. Los mundos siguen usando las secciones autorizadas, sus grupos y rutas. Los favoritos conservan la actualización al cambiar preferencias.

El riel centra los mundos, muestra borde izquierdo en activo y tooltips azules. Cerrar sesión usa un cuadrado rojo y tooltip rojo. El submenú no repite los iconos del mundo. El drawer móvil y el contenedor de página también son compartidos.

System autenticado dejó de cargar core/theme/demo/branding y el inicializador de menú de Vuexy, además de Waves, Hammer, PerfectScrollbar y el personalizador de plantilla. Bootstrap 5.3.2 local aporta modales, dropdowns y tooltips; vue-select, Select2, jQuery y Font Awesome conservan las funciones que ya dependen de ellos. No se instaló una nueva biblioteca de componentes o iconos.

No borrar los CSS antiguos mientras tengan referencias en Guest o autenticación. El acceso carga también FG para los campos compartidos. Guest y Platform conservan sus límites y contratos. La composición de los otros submódulos se migrará al mismo estándar: no existe un modo visual alternativo exclusivo de Productos.

## 4. Productos integrado

Listado, filtros, búsquedas aplicadas, tabla, acciones, resultados, paginación, agregar/editar, consulta de inventario, altas de categoría/marca, importación, exportación y mensajes utilizan las piezas compartidas.

Se mantiene **Códigos → Nombre → Precio**. Inventario conserva todos los almacenes; editar no cambia existencias, solo permite ajustar el mínimo. Las altas de marca/categoría muestran Nombre y Descripción; el código requerido por el contrato se sigue generando internamente.

**Ordenamiento:** por decisión del usuario se conserva A–Z como texto. Z–A queda pendiente de una API que lo admita. No ordenar solamente los resultados de una página en el navegador. La descarga de código de barras sigue siendo una imagen, sin un flujo adicional.

## 5. Verificación y mantenimiento

Ejecutar `npm run build` y `tests/Frontend/*.test.mjs` con una versión de Node que admita atributos de importación JSON. Las pruebas cubren contratos numéricos, formularios, búsqueda, paginación, navegación, opciones, importación y accesibilidad de campos FG. Las aserciones de la apariencia anterior se adaptaron al diseño único.

La comprobación de navegador debe cubrir listado, agregar/editar, inventario, comisión, altas anidadas, importación, feedback, navegación y otro consumidor compartido, en escritorio y móvil. Las operaciones de escritura e importación real se documentan por separado de las comprobaciones sin modificar datos.

### Resultado comprobado el 20 de noviembre de 2026

- 290 pruebas de frontend aprobadas con Node 22.16.0; los cinco casos de campos FG también pasaron tras retirar la importación duplicada de CSS de vue-select.
- Compilación de producción aprobada; sintaxis y plantillas de 32 componentes Vue modificados comprobadas. Blade compiló correctamente y se limpió su caché de verificación.
- Navegador autenticado: listado, búsqueda sin coincidencias, recuperación, página 2, agregar, validación obligatoria, editar con stock actual de solo lectura, consulta de inventario, las tres comisiones y alta anidada de marca con sus dos campos.
- Importación: plantilla verde suave, carpeta amarilla, almacén y archivo obligatorio. Exportación: confirmación y cancelación. Se verificaron cambio de mundo, drawer móvil y navegación compartida en Marcas.
- Escritorio y 390 × 844: Productos no desplaza horizontalmente el documento; el pie de la modal permanece visible. El desplegable de comisión se superpone sin modificar la posición del valor.
- También se abrió Productos usando los assets compilados, con CSS enlazado desde el head y Source Sans 3 local. La sesión nueva de comprobación no mostró errores ni advertencias de consola. La configuración de Vite de desarrollo quedó restaurada.

No se guardaron productos ni se ejecutó una importación real durante estas comprobaciones. La generación efectiva de Excel y las operaciones de escritura se mantienen con sus contratos y pruebas existentes, pendientes de una prueba operativa con datos destinados a ese fin. Los otros submódulos reciben los componentes y el layout globales; su composición específica se revisará en las siguientes migraciones autorizadas.

## Ajustes de feedback, menús y selección · 20 de noviembre de 2026

- SweetAlert2 mantiene sus archivos originales. Su CSS se incorpora a la capa `fg-vendor` desde el entry FG; se retiran los enlaces duplicados y la antigua regla propia global sobre `.swal2-title`. No se añaden `!important` para competir con la plantilla. Las clases antiguas de feedback se sustituyen en la configuración común de System, conservando decisiones, callbacks, inputs y resultados.
- `Helpers/Feedback.js` centraliza esa configuración y los SVG originales locales de Componentes. Resultado: ancho 420 px, icono 32 px, título 16 px, cuerpo y botón 13 px, botón centrado y espacios de 14 px. Confirmación con alcance: ancho 480 px, acción a la izquierda, alcance y decisiones a la derecha. El ancho queda limitado por el viewport.
- Los menús compartidos usan texto de 13 px, iconos de 14 px y opciones de al menos 40 px, sin heredar la escala del dropdown de Bootstrap. Icono y etiqueta de las acciones mantienen el mismo color.
- `FgSelect` monta su lista en body mediante la API de vue-select. `SelectOverlay.js` la posiciona con capa 1080, ancho del control, desplazamiento y cambio de tamaño; abre hacia arriba cuando falta espacio y elimina listeners/observadores al cerrar. `select-overlay.css` mantiene la misma apariencia en el portal. Ningún select reserva espacio al abrir, ni modifica la altura del formulario, las etiquetas o el scroll.
- En selects con searchable: false, el input interno sigue siendo readonly y enfocable para teclado, pero no muestra caret ni cursor de escritura. Se conserva el foco exterior; los campos deshabilitados mantienen el cursor de bloqueo. Regla compartida en components.css para todos los consumidores de Vue Select en FG.
- El input interno de búsqueda no tiene un segundo borde ni outline. El foco visible se conserva en el control completo. Las acciones del encabezado del campo se agrupan en `fg-field__actions`, con un separador sutil entre acciones.
- En el inventario de alta de Productos, vacío, null, undefined y cero se muestran como Agotado. No cambia el payload, la validación numérica ni el inventario persistido.
- Para comprobar los cuatro resultados sin modificar datos, abrir `/tests/Frontend/fixtures/FgFeedback.preview.html` desde el servidor Vite. La página utiliza los mismos helpers, CSS y SVG de la aplicación y no llama APIs.

La revisión de estos cambios debe incluir resultado exitoso, error, atención, carga, confirmación, selección única/múltiple, scroll del modal, móvil, teclado, limpieza del portal y stock inicial antes de guardar. El registro de pruebas y capturas distingue ejemplos visuales de operaciones reales.

Verificación de estos ajustes: 295 pruebas de frontend aprobadas, compilación de producción y Blade correctos. Resultado simple medido a 420 px con icono de 32 px, título de 16 px, cuerpo/botón de 13 px y botón de 36 px; en móvil, ancho 358 px y botón de 44 px. Menús medidos a 13 px y opciones de 40 px. Se comprobaron éxito, error, atención y carga mediante los helpers reales sin APIs; en Productos se comprobaron confirmación de exportación y cancelación, selección múltiple en portal con foco sin marco interior, actualización Agotado/Disponible y limpieza del portal. Marcas confirmó la escala del menú compartido. La versión compilada abrió sin errores ni advertencias de consola. No se guardaron productos ni se ejecutaron importaciones reales.

## Relación con Figma

Fuentes: [Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes) y [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos).

Figma y el repositorio no se sincronizan automáticamente. Registrar cualquier cambio reutilizable primero en Componentes y después aplicarlo en sus consumidores. Avisar al usuario cuando publicar la biblioteca requiera aceptar actualizaciones en otros archivos de Figma, según el [registro de actualizaciones](REGISTRO_Y_CALIDAD.md). Una vez implementado en código, cambiar una fuente compartida se refleja en sus consumidores al compilar.
