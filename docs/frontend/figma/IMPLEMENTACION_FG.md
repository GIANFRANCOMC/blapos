# Implementación FG · System y Productos

Actualizada: 20 de noviembre de 2026. Alcance: frontend del ERP tenant (System). Se conservan APIs, permisos, contexto de empresa, cálculos y validaciones de negocio.

## 1. Un solo sistema visual

FG es el estándar global de System. No hay banderas por Productos ni proveedores de tema por componente. El layout carga siempre la navegación y el navbar FG.

La fuente editable está en `resources/css/System/fg/`:

- `00-tokens.css`: colores, fuente, tamaños, pesos, alturas, bordes y espacios.
- `components.css`: controles, botones, pestañas, badges, modales, paginación y estados.
- `navigation.css`: mundos, submódulos, navbar y contenedor común.
- `feedback.css`: SweetAlert, capas y tooltips.
- `Shared/viewport.css`: scroll nativo y foco transversal, también cargado en Guest y Platform.
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

### Ayudas y errores de campo

`FgField` organiza **control → error, si existe → ayuda**. `--fg-field-gap` define una separación uniforme de 4 px; los mensajes no añaden márgenes. Sin error, la ayuda ocupa la primera posición bajo el control; con error, este ocupa esa posición y la ayuda continúa debajo. No se reserva una fila vacía y los textos largos pueden envolver naturalmente.

Pasar la ayuda mediante `help` a `InputText`, `InputNumber`, `InputDate`, `InputTextArea`, `InputSelect`, `InputSelect2` o `InputSlot`. Para Vue Select, pasarla al `FgField` que lo contiene. Evitar párrafos de ayuda independientes dentro de `.fg-stack`, cuyo espaciado corresponde a secciones. Los errores conservan su contrato actual (`hasTextBottom` y `textBottomInfo` en los adaptadores).

`FgField` expone `describedBy` en su slot y contexto para asociar los IDs de error y ayuda a `aria-describedby`. `FgSelect` lo consume desde el contexto; los controles nativos lo reciben desde el slot. `InputSlot` reenvía esos datos a su slot `input` para los controles personalizados.

Verificación de este ajuste: 297 pruebas de frontend aprobadas y compilación de producción correcta. Navegador autenticado: fecha y comisión con separación medida de 4 px; errores de Nombre y Precio también a 4 px del control. Comprobados escritorio y 390 px, sin desbordamiento horizontal del documento; restablecido el viewport. La coexistencia de error y ayuda, sus referencias accesibles y la recuperación al estado válido se cubren en las pruebas de controles compartidos. No se guardaron productos. Captura de comprobación: `fg-ayuda-campos.png` en los artefactos de la sesión.

## 3. Navegación global y dependencias

### Scroll y foco sin ruido

La fuente común es `resources/css/Shared/viewport.css`. System la importa desde FG, Platform desde su entry CSS y Guest la enlaza desde el head. `Shared/disabled-controls.js` carga el seguimiento de modalidad de entrada para las tres áreas. No cambian sus rutas, permisos, datos ni contexto de tenant.

- Scroll: canal nativo de 10 px y thumb redondo de 6 px, fondo transparente y sin flechas decorativas. Reposo `#7b8ba0`, hover `muted` y arrastre `primary`; todos mediante tokens FG. Aplica a documento, paneles, modales, tablas y opciones, incluidas las listas en portal. No reemplaza rueda, touchpad, arrastre ni teclado. Firefox usa `scrollbar-width: thin`; los detalles nativos pueden variar según navegador y sistema operativo.
- Shift, Control, Alt o Meta aislados no activan el modo de teclado. Tab, Shift+Tab y teclas de navegación conservan un foco visible interior. Las secciones usan superficie suave y subrayado azul de 2 px, sin marco exterior; los botones conservan su color y un subrayado interior con `currentColor`. Los campos mantienen su borde/halo. No se cancelan eventos ni se alteran trampas de foco de modales. Alto contraste recupera la señal nativa de contorno.
- Figma: [Scroll y guía de uso](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-119), [maestro Scroll](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-94), [Foco de sección](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-107). Publicados los dos componentes y sus seis tokens; [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=253-36372) incorpora cuatro instancias vinculadas, sin duplicar maestros locales. Otros consumidores de Figma deberán aceptar la actualización cuando corresponda; el CSS compartido de la aplicación ya aplica globalmente.
- Verificación: 299 pruebas aprobadas, compilación de producción y vistas Blade correctas. Navegador autenticado en escritorio: Shift mantiene `pointer` y no muestra outline ni sombra; Tab/Shift+Tab muestra subrayado azul; Enter conserva la apertura nativa; PageUp desplaza el panel. Modal y opciones de Categorías usan el canal de 10 px, con portal en capa 1080. No se guardaron datos. Queda pendiente la comprobación específica en Firefox, tacto y alto contraste. El intento de emulación móvil no cambió el viewport del tab de prueba y no se cuenta como una validación móvil.

`System/layouts/main.blade.php` incluye fg-navigation, fg-navbar, fg-up y fg-down para todos sus consumidores. Los mundos siguen usando las secciones autorizadas, sus grupos y rutas. Los favoritos conservan la actualización al cambiar preferencias.

El riel centra los mundos, muestra borde izquierdo en activo y tooltips azules. Cerrar sesión usa un cuadrado rojo y tooltip rojo. El submenú no repite los iconos del mundo. El drawer móvil y el contenedor de página también son compartidos.

System autenticado dejó de cargar core/theme/demo/branding y el inicializador de menú de Vuexy, además de Waves, Hammer, PerfectScrollbar y el personalizador de plantilla. Bootstrap 5.3.2 local aporta modales, dropdowns y tooltips; vue-select, Select2, jQuery y Font Awesome conservan las funciones que ya dependen de ellos. No se instaló una nueva biblioteca de componentes o iconos.

No borrar los CSS antiguos mientras tengan referencias en Guest o autenticación. El acceso carga también FG para los campos compartidos. Guest y Platform conservan sus límites y contratos. La composición de los otros submódulos se migrará al mismo estándar: no existe un modo visual alternativo exclusivo de Productos.

## 4. Productos integrado

Listado, filtros, búsquedas aplicadas, tabla, acciones, resultados, paginación, agregar/editar, consulta de inventario, altas de categoría/marca, importación, exportación y mensajes utilizan las piezas compartidas.

Se mantiene **Códigos → Nombre → Precio**. Inventario conserva todos los almacenes; editar no cambia existencias, solo permite ajustar el mínimo. Las altas de marca/categoría muestran Nombre y Descripción; el código requerido por el contrato se sigue generando internamente.

**Ordenamiento:** el selector permite Nombre A–Z y Nombre Z–A, con `sort_direction=asc|desc` en listado y exportación. Cambiarlo vuelve a la página 1 usando filtros aplicados; la API ordena el conjunto completo y desempata por ID. La descarga de código de barras sigue siendo una imagen, sin un flujo adicional.

**Acciones, carga y fecha de alta:** los menús general y de fila muestran solo el label. El disparador conserva chevron o tres puntos; `BarcodeDownloadButton` conserva el icono únicamente cuando se presenta como botón independiente. `FgListLoading` reutiliza el SVG de carga de 32 px, contenedor mínimo de 200 px, estado anunciado una sola vez y giro desactivado con movimiento reducido. En alta, Disponible hasta admite hoy o una fecha futura; al editar no tiene mínimo. La ayuda pertenece a `FgField` y la API conserva la validación definitiva.

Los menús de acciones ajustan su ancho al contenido, sin el mínimo general de 244 px: `max-content`, máximo 240 px o ancho de ventana menos 32 px. Los textos largos pueden envolver sin recortarse. Se mantiene el anclaje derecho junto al disparador. Comprobado en local: general 140 px y por registro 180 px, sin etiquetas recortadas; dos pruebas del componente y compilación aprobadas. En Figma se ajustaron cinco menús de escritorio y móvil a 141/181 px, reutilizando sus instancias existentes. No cambia la lógica de las acciones.

En Figma se publicaron Opción de menú, Ordenar listado, Listado / Carga y su token de altura. Se aceptó la actualización para todas las páginas de Productos: 100 instancias de orden y 10 opciones de menú. La vista [Buscando productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-1390) utiliza el nuevo maestro enlazado; las configuraciones de alta de escritorio y móvil explican la fecha mínima. Las instancias se conservan vinculadas.

Verificación de esta etapa: 302 pruebas de frontend aprobadas; cuatro casos funcionales de Productos aprobados con 24 aserciones sobre fechas, orden global, filtros, paginación estable, exportación y rechazo de dirección inválida. Vite y Blade compilan. Navegador local: ambas listas de acciones sin iconos, selección Z–A con resultados descendentes y calendario con mínimo al crear y sin mínimo al editar. No se guardaron registros reales. La suite backend completa dio 236 casos aprobados y 11 fallidos: arquitectura tenant (4), inicialización de seeders (1), datos maestros (1), inventario/unidad (1), navegación (2) y workspace (2). Los fallos corresponden a archivos o expectativas fuera de estos cuatro ajustes; se registran para su revisión sin ampliar este cambio. No se declara la suite general aprobada.

## 5. Verificación y mantenimiento

### Indicadores y navegación compartidos · 20 de noviembre de 2026

- El riel usa `justify-content: safe center`, sin el antiguo umbral de 620 px. Los mundos se mantienen centrados cuando caben y pueden desplazarse desde el primer acceso cuando falta altura, incluida la reducción de espacio al aumentar el zoom.
- `control-icons.css` es la fuente común del chevron de Vue Select, Select2, secciones nativas y botones de dropdown. Reutiliza el SVG original de Componentes de 16 × 16 y lo gira 180° según el estado real del control, con `--fg-control-motion: 160ms ease`. Con movimiento reducido se elimina la transición. La X es de 16 × 16 en selección única y 10 × 10 en etiquetas; conserva los botones, eventos y accesibilidad de las bibliotecas.
- Consumidores: formulario de Productos, límites de precio, menú de acciones, navbar, usos de categorías/marcas y operaciones de traslados. Los resúmenes de selección de producto y almacén, las unidades de cantidad y el desglose de uso ya no emiten clases `br-*`; se componen con primitivas y tokens FG. La limpieza del resto de submódulos continúa por sus fuentes compartidas; no se renombraron IDs ni atributos funcionales.
- Referencias: [chevron](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-513), [limpiar selección](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-559), [etiqueta](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=13-22). Assets locales sin alterar en `resources/assets/fg/controls/`.
- Verificación de código: 295 pruebas de frontend aprobadas; se repitieron los 29 casos de navegación, inventario y traslados tras el ajuste final de la etiqueta obligatoria. Compilación de producción y de vistas Blade aprobadas, con caché de vistas limpiada y `git diff --check` sin incidencias. La revisión visual actual de zoom, flechas y X queda pendiente: el navegador integrado abrió el inicio de sesión y el control de Chrome se interrumpió porque no pudo verificar su URL. Las comprobaciones de navegador registradas abajo corresponden a las etapas anteriores.

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

### Skeleton FG y transición inicial

`CatalogListSkeleton` reutiliza `FgSkeletonFilters`, las cabeceras del catálogo y `FgListLoading`; Productos conserva el encabezado real con acciones deshabilitadas durante `initParams`. Las búsquedas y la primera consulta del listado mantienen su loader en la tabla, sin demoras artificiales ni skeletons de filas con cantidades inventadas. Columnas de Productos: 22/40/16/13/9 %, precio a la derecha y estado/acciones al centro.

La fuente única es `Shared/skeleton.css`: primitivas `fg-skeleton-*`, controles, pulso y transición `fg-initial-load`. `InitialLoadTransition` conserva ambas vistas en la misma celda y vuelve inert/aria-hidden la saliente. Fundido de 180 ms y entrada de 3 px; movimiento reducido sin animación. No usar modo out-in, temporizadores para prolongar la carga ni cuatro bloques genéricos.

Limpieza: migradas las primitivas de los siete skeletons específicos y los componentes compartidos; retirados los selectores antiguos de página/transición y el archivo `catalog-list-skeleton.css` con sus siete imports. Los componentes compartidos de carga ya no contienen clases `br-`. Los estilos específicos de módulos todavía pendientes de FG mantienen sus consumidores; esta entrega no elimina indiscriminadamente sus layouts. Los componentes compartidos conservan fallbacks para Guest/Platform sin cargar el tema de System.

Verificado con la muestra `/tests/Frontend/fixtures/FgSkeleton.preview.html` desde Vite: Productos, Marcas y PageSkeleton, sin APIs. Skeleton y contenido de Productos mantienen 416,5 px en escritorio y 595,39 px en el viewport móvil de 390 px; filtros y botones coinciden. A 320 px, el documento no desborda y solo la tabla permite scroll horizontal. El loader se centra en el área visible mediante el contenedor de la tabla. El ancho mínimo de Productos pasa a 860 px para conservar sus cabeceras de 44 px. FG declara su propio border-box y reserva 1 em para iconos de botones, sin depender del reset de Bootstrap.

Compilación de producción y 303 pruebas de frontend correctas, incluida la protección de la vista saliente durante la transición. Movimiento reducido comprobado en las reglas y pruebas; no se cambió la configuración del sistema operativo.

Figma: publicadas juntas Línea, Control y Catálogo (Escritorio/Móvil) en Componentes. La [carga inicial de Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=279-36231) usa una instancia vinculada del maestro, sin duplicarlo. El prototipo pasa a la carga de tabla y al listado con fundidos de 180 ms; sus esperas son solo de demostración y no se incorporan al código.

### Campos no editables

`fg/unavailable-controls.css`, importado después de `control-icons.css`, unifica texto, textarea, número, importe, fecha, Vue Select, Select2 y select nativo con los tokens `--fg-disabled-bg`, `--fg-disabled-text`, `--fg-disabled-border` y `--fg-disabled-tag-bg`. Aplica a disabled, readonly y estados ARIA equivalentes existentes. La superficie exterior es gris y los fondos internos son transparentes; prefijos, contadores, etiquetas múltiples, texto y flecha usan tonos neutros. Readonly conserva copia y foco de teclado. Se excluyen los inputs de búsqueda internos de un selector activo. No cambia selección, permisos, envío de valores ni contratos.

Verificado con los componentes reales en una muestra temporal: Vue Select único vacío/con valor, múltiple y Select2 único/múltiple. Sin franjas blancas; fondo calculado #f3f4f6 y texto/flecha #6b7280. Compilación de producción y 13 pruebas específicas correctas. La muestra temporal se retira tras la revisión.

Biblioteca Componentes publicada y actualización aceptada para todas las páginas de Productos, incluidas sus instancias de Comisión. La regla transversal también queda registrada en la skill frontend.

Extensión a todos los campos: retiradas las reglas anteriores que daban otro gris a `.fg-input:disabled` y a su contenedor. Comprobación de navegador con CSS de producción: texto, textarea, número con prefijo y contador, fecha, select nativo y readonly muestran fondo #f3f4f6, borde #d1d5db y texto #6b7280. El campo editable conserva su superficie blanca. Compilación y 13 pruebas específicas correctas; sin operaciones de escritura de negocio. La muestra temporal no forma parte de la aplicación.

Fuentes: [Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes) y [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos).

Figma y el repositorio no se sincronizan automáticamente. Registrar cualquier cambio reutilizable primero en Componentes y después aplicarlo en sus consumidores. Avisar al usuario cuando publicar la biblioteca requiera aceptar actualizaciones en otros archivos de Figma, según el [registro de actualizaciones](REGISTRO_Y_CALIDAD.md). Una vez implementado en código, cambiar una fuente compartida se refleja en sus consumidores al compilar.
