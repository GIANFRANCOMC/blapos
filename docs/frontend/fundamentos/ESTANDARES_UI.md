# Estándares de interfaz de Blapos

> Estándar vigente de System: [FG global](../figma/IMPLEMENTACION_FG.md). Sus tokens, componentes y layout sustituyen la apariencia anterior. Las reglas funcionales de esta guía siguen vigentes. Guest y Platform conservan sus límites.


## Branding

Los colores oficiales detectados del branding son:

- Primario: `#2899E5` (`--br-primary`).
- Secundario: `#1A1A35` (`--br-secondary`).
- Success: `#12a974`.
- Warning: `#e99a16`.
- Danger: `#e5484d`.
- Info: `#2496d8`.
- Superficie base: `#f5f8fb`.
- Superficie elevada: `#ffffff`.
- Bordes: `#dfe7ef` y `#bdcad8`.
- Texto principal: `#263243`.
- Texto secundario: `#66758a`.

`resources/css/System/br-branding` contiene los parciales editables de color, superficies, bordes, estados, sombras, foco, compatibilidad y estilos de módulo. El proceso de compilación genera `public/System/assets/css/br-branding.css` a partir de ellos.

`public/System/assets/css/br-login.css`, `public/System/assets/css/demo.css` y cualquier CSS especializado deben consumir los tokens de branding con `var(--br-*)`. No agregar colores hexadecimales, `rgba()` o paletas locales fuera de `br-branding.css` salvo que sea una excepción documentada y reutilizable.

Orden de carga recomendado:

- CSS de plantilla y vendors.
- `br-branding.css`.
- CSS especializado de pantalla, por ejemplo `br-login.css`, siempre después de `br-branding.css`.

### Acciones y modales de edición

- Crear o agregar usa `br-btn-action-create` en azul primario. Editar usa `br-btn-action-update` o `br-btn-action-edit` en amarillo de advertencia con texto blanco. La distinción debe mantenerse también en hover, foco y estado activo; no resolverla con colores locales del módulo.
- El botón principal de una modal debe nombrar el objeto que se guarda: «Guardar producto», «Guardar colaborador», «Guardar sucursal», etc. `FormModal` deriva el nombre de los títulos «AGREGAR…» y «EDITAR…» cuando no recibe `submitButtonText`; las acciones especiales pueden indicar el texto explícitamente.
- Las acciones de fila usan `TableActionsDropdown` con tres puntos verticales del color de Acciones y el tooltip «Acciones», visible con el cursor o el foco del teclado. El área del botón es transparente y sin borde en reposo. Un fondo suave indica cuando el cursor pasa por encima, se presiona el botón o se abre el menú. El botón de copiar se muestra como icono primario sin recuadro mediante `CopyButton`.
- Mantener la lectura de permisos como información tabular: nombre y contexto en una sola celda; módulos, colaboradores y estado en columnas separadas. En la edición, agrupar permisos por sección con divisores discretos y mostrar las acciones sin tarjetas anidadas.

Los layouts deben declarar `data-assets-path` con una URL absoluta generada por Laravel, por ejemplo `{{ asset('System/assets') }}/`. Los scripts heredados de la plantilla (`helpers.js`, `template-customizer.js`, `config.js`, `main.js` y vistas demo que usen `assetsPath`) dependen de ese atributo para cargar CSS dinámico, imágenes y JSON. `config.js` normaliza cualquier valor relativo antiguo, pero no se deben volver a introducir rutas como `../System/assets/` porque fallan en páginas profundas.

Los CSS públicos de System se generan desde parciales en `resources/css/System`. Editar los parciales y ejecutar `npm run build:css:system`; no editar directamente `public/System/assets/css/br-branding.css`, `br-login.css` ni `demo.css`.

`resources/css/System/platform.css` mantiene el mismo orden de parciales y queda preparado como entry de Vite. Mientras los layouts sigan usando `<link rel="stylesheet">`, el comando de build CSS conserva los archivos públicos compatibles.

Los CSS de plantilla en `public/System/assets/vendor/css/core.css`, `public/System/assets/vendor/css/rtl/core.css`, `public/System/assets/vendor/css/theme-default.css` y `public/System/assets/vendor/css/rtl/theme-default.css` no deben introducir colores de marca fijos. La paleta primary heredada de Vuexy se mapea a tokens `--br-vuexy-*`, definidos en `br-branding/00-tokens.css`, para evitar conflictos con `!important` y permitir personalizar la marca desde un solo archivo.

La plataforma usa una densidad visual compacta definida en `br-branding/70-visual-density-brand-refresh.css`: botones, inputs, labels, tablas, modales, badges y tooltips deben respetar esa escala. Si una pantalla se ve grande o pesada, ajustar primero variables como `--br-control-height`, `--br-btn-height`, `--br-font-size-ui` o `--br-table-cell-pad-y`, no crear tamaños locales por módulo.

### Fuente de interfaz

Inter es la única fuente de interfaz en System, Guest y Platform. Se carga una vez por página mediante `resources/views/shared/font-inter.blade.php`; `--br-font-sans` en `br-branding/00-tokens.css` y `fontFamily.sans` en Tailwind son sus puntos de configuración. Los controles, menús, modales y componentes de terceros heredan esta fuente desde `br-branding/01-typography.css`. La plantilla Bootstrap ya no declara Public Sans. En JavaScript, `config.typography.fontFamily` lee ese token y alimenta ApexCharts y el valor predeterminado de Chart.js: no escribir el nombre de la fuente en cada gráfico. Las fuentes de Font Awesome y Tabler Icons se conservan exclusivamente para iconos.

Jerarquía de peso: títulos y totales `600–700`, etiquetas y botones `500`, texto, ayudas e inputs `400`. Reservar las negritas para jerarquía o énfasis útil; los códigos, estados y datos repetidos no necesitan negrita por defecto. Mantener los tamaños compactos existentes: cambiar la familia tipográfica no autoriza ampliar controles ni alterar espaciados. En códigos, importes y reloj usar `font-variant-numeric: tabular-nums` cuando sea útil alinear cifras, sin recurrir a otra familia. Los correos y PDF conservan fuentes seguras para sus respectivos clientes/renderizadores, donde Google Fonts no está garantizado.

### Tokens reutilizables para el aspecto visual

Todo valor visual nuevo debe pertenecer al sistema compartido antes de usarse en una pantalla: tipografía, colores, pesos, espaciado, altura de controles, radios, bordes, sombras y transiciones. Primero elegir un token existente según la función del elemento; si falta, añadirlo al catálogo central y documentar su propósito. No duplicar valores ni crear clases por módulo para representar el mismo rol visual.

Control de stock es la referencia tipográfica para System, Guest y Platform. Los pasos de la escala y los pesos se definen en `resources/css/Shared/typography-tokens.css`. Los roles viven en `br-branding/00-tokens.css` y la tipografía de controles, etiquetas, botones y tablas se aplica desde `br-branding/01-typography.css`. Este parcial también define el color y el margen base de `.form-label` para las tres superficies.

### Tamaños estándar según el rol

El catálogo contiene solo los tamaños de Control de stock. Al migrar una pantalla, elegir el tamaño existente más cercano que cumpla la misma función. Si el componente ya lo hereda, retirar la declaración local. Usar la misma escala en escritorio y móvil.

| Rol de interfaz | Tamaño estándar existente | Uso |
| --- | --- | --- |
| Texto operativo | `--br-ui-font-size` | Texto común, navegación y celdas normales. Los inputs y botones parten de este tamaño. |
| Etiqueta de formulario | `--br-font-size-label` | Usa el paso existente `--br-font-size-0705` en las etiquetas de campos. |
| Ayuda o descripción breve | `--br-table-caption-font-size` | Contexto subordinado al dato principal; no reducirlo por pantalla. |
| Dato secundario | `--br-ui-font-size` | Desgloses comparables y valores secundarios. |
| Dato principal destacado | `--br-table-value-font-size` | Precios y métricas que requieren mayor jerarquía. |
| Encabezado de tabla | `--br-table-heading-font-size` | Cabeceras de listados y tablas compartidas. |
| Cantidad de stock | `--br-stock-quantity-font-size` | Stock actual, Diferencia, Variación y Saldo. |
| Títulos de página, diálogo y sección | `--br-table-value-font-size` | Los títulos destacados comparten este paso. Los títulos de modales compactas heredan el tamaño operativo. |

Los elementos del mismo rol comparten tamaño y peso. Al reutilizar un control, etiqueta o tabla, retirar del CSS de la pantalla las declaraciones de tipografía que ya aporta el estilo común. Los textos internos heredan el tamaño y el peso de su contenedor. Las ayudas y los datos destacados usan su alias de rol. Los códigos internos y de barras comparten los tamaños de etiquetas y valores de Control de stock.

Las opciones con `br-entity-switch` usan el tamaño operativo y el peso de etiqueta para el título. Su descripción `small` usa el tamaño compacto `--br-font-size-066` y el peso del texto base.

Para ampliar toda la tipografía, cambiar `--br-font-scale` de `1` a `1.1` (10 % más). Los iconos en `em` siguen el tamaño de su texto. Para cambiar un rol, apuntar su alias en `00-tokens.css` a otro paso existente para todos sus consumidores.

Stock actual y Diferencia comparten `--br-stock-quantity-font-size` (`0.8rem` de base), conservando sus colores y peso. Los encabezados de tablas usan `--br-table-heading-font-size`; las etiquetas y valores de los códigos de inventario usan `--br-product-code-label-font-size` y `--br-product-code-font-size`. Usar `colon-at-end` en Cód. interno y Cód. barras en lugar de escribir dos puntos manuales.

Para mostrar una cantidad con unidad en System, reutilizar `QuantityValue`. El número hereda el tamaño de su contexto y la abreviatura registrada usa el paso compartido `--br-font-size-066`. **Saldo** y **Variación** comparten el rol de cantidad de stock. **Variación** mantiene el signo, el número y la unidad en una línea.

Reutilizar `ProductIdentity` al presentar un producto con su nombre e identificadores en el mismo bloque. Pasar el objeto con `name`, `internal_code` y `barcode`. La variante predeterminada `full` muestra los códigos desde el inicio. Control de stock y las demás vistas de Inventario usan `expandable`, que muestra el nombre con un botón «+» para abrir los códigos disponibles. El botón cambia a «−» para cerrarlos y admite foco y activación por teclado. Agregar futuras variantes en el mismo componente.

La referencia del traslado es un identificador: en la tabla y el detalle usa el mismo tamaño, peso normal, color y cifras tabulares que los valores de código interno y código de barras de `ProductIdentity`, reutilizando sus tokens existentes.

Modificar la regla fuente del componente y eliminar declaraciones redundantes antes de aumentar la especificidad. No resolver conflictos entre estilos propios añadiendo clases o `!important`. Las excepciones de librerías y los ajustes responsive deben consumir los mismos tokens. Los tamaños de documentos exportados, correos y dibujos de canvas se definen según su formato de salida, fuera de la escala de interfaz.

### Pesos tipográficos reutilizables

La interfaz comparte tres pesos definidos una sola vez en `resources/css/Shared/typography-tokens.css`: regular (400), medio (500) y seminegrita (600). Elegir el rol antes de aplicar peso; no introducir pesos literales ni negritas decorativas por pantalla.

| Rol | Token | Base |
| --- | --- | --- |
| Texto común, inputs y datos secundarios | `--br-font-weight-body` | 400 |
| Etiquetas | `--br-font-weight-label` | 500 |
| Botones y acciones | `--br-font-weight-control` | 500 |
| Encabezados de tabla | `--br-font-weight-table-heading` | 500 |
| Valores principales y contadores | `--br-font-weight-value` | 500 |
| Títulos | `--br-font-weight-heading` | 600 |
| Énfasis justificado y `strong`/`b` | `--br-font-weight-emphasis` | 600 |

Modificar el alias global para ajustar un rol; no redefinirlo en módulos ni capas de pulido. Los estilos heredados sin rol específico usan `--br-font-weight-regular`, `--br-font-weight-medium` o `--br-font-weight-semibold`. Las utilidades Bootstrap `fw-bold`, `fw-bolder` y `fw-semibold` se normalizan al énfasis compartido; evitar combinarlas con otro peso del componente. Los iconos conservan el peso técnico de su fuente mediante `--br-font-weight-icon` (900), que no es un peso del texto. PDFs, correos y canvas tienen formatos independientes.

### Convención tipográfica de tablas

Todas las celdas de cabecera, cuerpo y pie se centran verticalmente mediante la regla compartida de `01-typography.css`. Los módulos definen la alineación horizontal según el contenido de cada columna.

Usar `ExpandableText` para descripciones que deben ocupar una línea en tablas. El componente mide el espacio disponible y ofrece «Ver más» cuando el texto se recorta. «Ver menos» vuelve a compactar la descripción.

Las etiquetas de Detalles usan una separación de `0.25rem` entre el rótulo con dos puntos y el valor. El valor usa `--br-font-size-066` de la escala compartida. Los iconos que explican un valor quedan junto a ese valor dentro de la misma etiqueta.

Los aliases compartidos se declaran en `resources/css/System/br-branding/00-tokens.css`, referencian la escala de `Shared/typography-tokens.css` y se aplican desde los parciales de `br-branding`; no repetir tamaños literales por pantalla. Se usan `rem` para respetar el tamaño base del navegador:

| Rol | Token | Tamaño base |
| --- | --- | --- |
| Texto operativo y celdas comunes | `--br-ui-font-size` / `--br-font-size-table` | `0.705rem` |
| Dato principal (precio de venta, inventario u otro valor destacado) | `--br-table-value-font-size` | `0.82rem` |
| Dato secundario (precio mínimo/máximo u otro desglose) | `--br-ui-font-size` | `0.705rem` |
| Etiqueta breve de un dato compuesto | `--br-table-caption-font-size` | `0.68rem` |
| Cantidades comparables de inventario: Stock actual y Diferencia | `--br-stock-quantity-font-size` | `0.8rem` |
| Encabezado de tabla | `--br-table-heading-font-size` | `0.66rem` |

Los valores principales comparables de una misma fila, como precio e inventario, deben usar el mismo token, peso y altura de línea compatibles. La jerarquía se crea con color y peso, no con tamaños distintos para datos del mismo nivel. Los encabezados, estados e iconos conservan sus tokens o reglas propios. Antes de crear otro tamaño, identificar el rol del texto y reutilizar el token correspondiente.

`br-branding/99-system-wide-visual-polish.css` es la capa final de armonización para pantallas heredadas. Ahí se compactan y alinean menú, navbar, formularios, placeholders, selects, tablas, modales, cards, POS, estados, loaders y scrollbars usando únicamente tokens `--br-*`. Los estilos específicos deben permanecer en el parcial de su módulo y no duplicarse en esta capa final.

Las clases nuevas reutilizables deben iniciar con `br-`.

## UI y UX

La interfaz debe sentirse operativa, seria, compacta y clara. Evitar pantallas recargadas, textos redundantes, tarjetas innecesarias y colores que compitan con la acción principal.

Criterios prácticos:

- Usar contraste suficiente, no saturación excesiva.
- Mantener formularios compactos y agrupados por intención.
- Preferir tooltips para aclaraciones breves, no alertas visuales invasivas.
- Usar labels sutiles, no grandes ni demasiado oscuros.
- Los botones deben diferenciar intención: buscar, crear, editar, importar, descargar, cancelar, confirmar y peligro.
- Los botones de los layouts compartidos usan el efecto Waves al presionarse. La inicialización de `main.js` también registra los botones incorporados después de cargar la página, como los de Vue y SweetAlert.
- Usar `SearchButton` para ejecutar búsquedas en barras de filtros, formularios y modales. El botón muestra la lupa de Font Awesome y usa «Buscar» como tooltip y nombre accesible. El tooltip aparece con el cursor o el foco del teclado y se oculta al pulsar el botón.
- Ofrecer «Limpiar filtros» en barras con varios campos mediante `ClearFiltersButton`. El botón usa una adaptación de `fa-brush` de Font Awesome, invertida verticalmente y con el mango alargado. Comparte por defecto los colores de la cabecera de tabla mediante `br-filter-clear`. Se alinea al borde inferior y usa un tooltip de Bootstrap y una etiqueta accesible. Restablecer los campos juntos y conservar el contexto operativo, como el almacén. En filtros aplicados con Buscar, la limpieza solo modifica los campos pendientes. Evitar consultas o invalidaciones parciales de watchers durante el reinicio.
- Al terminar el restablecimiento, `Alerts.showFiltersCleared()` muestra el aviso informativo «Se limpiaron los filtros correctamente.» mediante Toastr. El botón oculta su tooltip al pulsarlo y lo vuelve a mostrar al pasar el cursor por encima.
- Los icon buttons requieren `aria-label` y tooltip.
- La integración compartida de SweetAlert oculta los tooltips de la página al abrir una alerta y durante el retorno de foco. Una vez cerrada, el tooltip vuelve a responder a una nueva interacción con su disparador. Los formularios y listados reutilizan este comportamiento en sus llamadas a `Swal.fire`.
- En desktop, botones secundarios de descarga pueden mostrarse sólo con icono si el tooltip es claro; en móvil deben mostrar icono y texto.
- Las tablas deben reutilizar la superficie global de encabezado y estados compactos.
- Servicios, Membresías, Categorías, Marcas y Recetas y platillos usan `InternalCodeValue` para mostrar el código interno centrado como texto. Productos muestra los códigos en su columna Identificación. Consulta el [patrón de Catálogo comercial](../patrones/CATALOGO_COMERCIAL.md).
- Encerrar cada tabla ancha en `table-responsive`. El contenedor toma el ancho disponible y la tabla desplaza sus columnas dentro de él. Menú lateral, filtros, acciones superiores y paginación quedan fuera del scroll. Los estados de carga y vacío se centran en el área visible. Los menús de fila usan posicionamiento fijo de Popper para abrir sin liberar el overflow de la tabla.
- Debajo de todas las tablas paginadas, incluidas las modales, usar `Paginator`, que integra el pie completo `br-list-footer`: navegación centrada y `ListResultCount` a la derecha. Pasar `:pagination` con el objeto paginado completo del servidor, `:links` y `:loading`; no añadir otro contador ni wrappers de centrado o margen. El contador muestra únicamente el total filtrado del servidor con singular o plural (por ejemplo, «Resultado: 1 registro» o «Resultado: 50 registros»), nunca el número de filas de la página ni el rango. Durante cualquier carga inicial, búsqueda, cambio de filtro o página, ocultar juntos navegación y contador hasta que termine la consulta. El componente compartido centraliza esta condición mediante `loading`; no mostrar cifras anteriores ni permitir navegación mientras se actualiza la tabla. Al terminar, reaparecen con el total actualizado, incluso con cero registros o una sola página. No añadir condiciones locales por `total > 0` o `links`, ni colocar el pie dentro del scroll horizontal/vertical. Mantener únicamente condiciones de contexto, como la vista activa o el almacén seleccionado. La regla aplica también a Guest y Platform, incluidos sus paginadores propios.
- La distancia entre tabla y pie se define una sola vez mediante `--br-list-pagination-gap` en `br-branding/00-tokens.css` (`0.75rem` de base). No sumar `mt-*`, márgenes propios del módulo o márgenes inferiores de tabla a esa separación. En móvil y contenedores estrechos, incluidas modales en pantallas grandes, el pie dispone navegación y contador en filas según su propio ancho para evitar superposición; el contador conserva alineación a la derecha. `DataTable` recibe el objeto completo mediante `pagination` cuando es paginada.
- El paginador compartido muestra `Primera`, `Anterior`, páginas próximas, `Siguiente` y `Última`. Sus botones son compactos y usan peso tipográfico normal; la página activa se distingue por el fondo, no por negrita. Los extremos no disponibles usan botones nativos deshabilitados. Cada módulo conserva sus propios colores y estilos de botones deshabilitados; no aplicar una neutralización global.
- Los estados vacíos y loaders usan `WithoutData` y `Loader`. En la tabla, el loader muestra únicamente el icono giratorio centrado. El texto de progreso se anuncia a lectores de pantalla. Sus filas llevan `br-table-state` y quedan excluidas de fondos, bordes, sombras, cursor y transiciones de hover de los registros. Neutralizar las variables de tabla Bootstrap sin bloquear botones de reintento. Los skeletons usan filas de estado en blanco, sin spinner.
- Los tooltips que se descartan al abrir una modal se restauran globalmente al cerrarla desde `Alerts.js`; los módulos no deben repetir esta restauración para los disparadores de la página.
- Las modales conservan `modal-dialog-centered`. Apertura y cierre comparten duración y curva para el diálogo, la superficie y el fondo; respetar `prefers-reduced-motion`.
- Usar [AppTabs](../../../resources/js/Shared/AppTabs.vue) en formularios y páginas. `variant="descriptive"` muestra icono y descripción. La variante `compact` muestra el título. Con `layout="content"`, cada pestaña se ajusta a su texto. El modo `stretch` distribuye el espacio disponible. En ambos, la selección conserva dimensiones y posición. Si la pestaña seleccionada queda fuera del área visible, la barra se desplaza solo lo necesario para mostrarla.
- Asocia cada pestaña a su panel con `controlsPrefix` o usa `sharedControlsId` cuando varias pestañas comparten un panel. Se conserva la navegación por teclado. Los paneles que entran al flujo pueden usar el fundido de `02-loading-transitions.css` según la preferencia de movimiento.
- En modales con `AppTabs`, usar `br-entity-modal--tabbed` y `br-entity-form-section` para el contenido. El diálogo conserva su posición superior al cambiar la altura del panel. Aplica las utilidades de display de Bootstrap a un contenedor interior si el panel usa `v-show`.
- Las modales comparten `--br-modal-chrome-surface` para encabezado y pie (gris azulado suave) y `--br-modal-surface` para el cuerpo (casi blanco). Ajustar estos tokens en `br-branding/00-tokens.css` para mantener contraste uniforme; no crear paletas de modal por módulo.

### Consultas de tablas y limpieza de registros

- Los listados paginados de Inventario y Catálogo comercial muestran 10 registros por defecto. Los controladores reutilizan `Utilities::$per_page_default` y aceptan un tamaño explícito mediante `per_page`, dentro del límite definido para cada listado.
- Antes de cada consulta de listado (búsqueda, paginación, reintento o recarga), activar `loading` y vaciar los registros antes del `await`. Para tablas paginadas, usar `Requests.emptyPagination()` de `Helpers/ListState.js`, reexportado por `Requests.js`: devuelve un objeto nuevo con `data: []`, `total: 0`, `links: []` y metadatos reiniciados. Para listados sin paginación, asignar `[]`. Limpiar también los resúmenes que dependan de esos resultados.
- Solo una respuesta válida puede repoblar la tabla. Si la consulta falla, conservar el estado vacío; nunca restaurar filas, totales o enlaces de la búsqueda anterior. Liberar `loading` en `finally`, también ante excepciones. Las pantallas con consultas simultáneas deben conservar sus controles de versión para descartar respuestas antiguas.
- **Buscar** aplica una copia de los campos de filtro y consulta desde la primera página. Escribir, elegir una opción, cambiar una fecha o limpiar un campo actualiza únicamente los filtros pendientes. Las filas, los totales y la paginación permanecen visibles hasta iniciar una consulta. Los watchers de filtros pueden ajustar opciones dependientes del formulario.
- Los campos de una barra comparten una fila flexible de Bootstrap. Los componentes que agrupan filtros aportan sus columnas directamente a esa fila para que los campos mantengan su orden y pasen a la línea siguiente según el ancho disponible. El skeleton usa la misma cuadrícula.
- Paginar, reintentar, refrescar o exportar reutiliza los filtros aplicados. `Requests.get` elimina de la URL los filtros enviados en `data` mediante `currentQueryRoute`, incluso vacíos o nulos. En URLs construidas manualmente, sobrescribir o eliminar cada parámetro. Cuando se reabre una modal de búsqueda que inicia su propia consulta, preparar primero sus filtros iniciales.
- Durante la consulta mostrar el indicador habitual del listado y ocultar conjuntamente paginador y cantidad de resultados. Al finalizar, mostrar el total de la respuesta actual o cero si falló.

### Carga inicial con skeleton

System, Guest y Platform muestran un skeleton durante la carga inicial. El skeleton conserva el espacio de filtros, pestañas, encabezados y formularios. En los listados, usa el mismo `Loader` de la tabla real en una fila `br-table-state`, con el `colspan` correspondiente. El estado inicial muestra solo esa fila de carga y mantiene ocultos el contador y la paginación. Mantener el breadcrumb visible cuando sea posible.

- Las pantallas genéricas reutilizan `InitialPageMixin` y `PageSkeleton`. Pasar `table` a `PageSkeleton` cuando la vista presenta una tabla. Las pantallas con skeleton propio reutilizan `Loader` en su fila de carga.
- Ejecutar `await this.loadInitialParams(() => this.initParams(...))` o liberar `initialLoading` en el `finally` de la consulta inicial. El listado dependiente comienza después de los parámetros y presenta su loader habitual. Las pantallas cuyo primer dato remoto es un listado, como tenants y proveedores, mantienen el skeleton durante esa primera consulta. Las búsquedas y la paginación posteriores usan el estado de carga del listado.
- `initParams` muestra su avance dentro de la página. Reservar Swal para confirmaciones, errores, guardados y exportaciones.
- Construir el skeleton en Vue y su CSS de pantalla o componente reutilizable. Compartir variantes cuando varias pantallas tengan la misma estructura. Usar alturas y tokens `--br-*` de los controles reales, un estado accesible (`role="status"` y texto legible para lectores de pantalla) y desactivar la animación con `prefers-reduced-motion`.
- Las cinco vistas de Inventario usan `InventorySkeleton` con la distribución de filtros y acciones de cada vista. La tabla conserva las cabeceras, los anchos y el contenedor responsive del listado correspondiente durante la carga inicial.
- Envolver el skeleton y su rama de contenido en `InitialLoadTransition` de `Components/Generics`. El fundido simultáneo de opacidad comparte una celda de grid: las dos ramas no se apilan ni duplican la altura durante el relevo. Usar exclusivamente `--br-initial-load-duration` y `--br-initial-load-easing` de `00-tokens.css` (180 ms de base); no añadir tiempos por pantalla, desplazamientos, esperas mínimas ni retrasar peticiones para animar. La rama que sale queda inerte y oculta para lectores de pantalla; `prefers-reduced-motion` desactiva la transición. Las modales y otros elementos ajenos a la carga permanecen fuera del componente.
- En todo listado, activar `loading` antes de solicitar datos y liberarlo en `finally`, también ante errores. Mantener ocultos el paginador y el contador mientras ese estado esté activo.

## Formularios

### Botones y menús de acciones

`ActionsButton.vue` centraliza la presentación y el tooltip de acciones. Por defecto usa `type="icon"`, con tres puntos verticales, fondo anaranjado estándar, tooltip «Acciones», área de botón, estados hover/foco/clic y Waves. En las botoneras, su ancho y alto usan `--br-control-height`, igual que Buscar y Limpiar filtros, sin reducir su tamaño al cambiar el icono. Para mostrar el texto «Acciones» y la flecha, solicitar explícitamente `type="button"`. `label` identifica el contexto para lectores de pantalla y `text` permite cambiar el texto de la variante con label. `variant="plain"` conserva la presentación sin recuadro de las acciones de fila; `solid` es la variante predeterminada para botoneras.

Los menús usan `ActionsDropdown.vue`, que reutiliza ese control y recibe las opciones en su slot. `TableActionsDropdown.vue` conserva compatibilidad con las tablas existentes, aplica `variant="plain"` y delega en el mismo menú, incluyendo `type`, `disabled` y `menuClass`. `FiltersSection` usa la variante de icono por defecto y expone `secondaryActionsType` si una pantalla necesita la variante con texto. Evitar botones de acciones y tooltips implementados por pantalla. Los botones que abren una modal de acciones usan `ActionsButton` sin desplegable. Las acciones específicas como Buscar, Agregar o Imprimir conservan su control propio.

### Resultado de operaciones en modales

`Shared/modal-results.js`, integrado con el SweetAlert compartido, coordina los mensajes finales de éxito y error en System, Guest y Platform. En éxito, cierra el formulario activo y espera `hidden.bs.modal` antes de mostrar el Swal; también reconoce un cierre ya iniciado por el módulo. No añadir retrasos por pantalla ni depender de hacer clic en un botón Cerrar que todavía pueda estar deshabilitado.

Durante un resultado se ocultan las modales y sus backdrops para presentar un único mensaje. En error se conserva el formulario y se restaura con sus datos, validaciones y foco al cerrar el aviso. En modales apiladas, un éxito cierra el formulario activo y conserva el formulario padre. La clase compartida `br-modal-result-hidden` está definida en `Shared/modal-results.css`. Confirmaciones, loaders y Swal con campos de entrada conservan su flujo habitual. Los callbacks existentes de SweetAlert y la limpieza global de tooltips deben respetarse.

Las búsquedas remotas de selectores usan `Options.js` y `GET /options/{origin}/{entity}`. `entity` identifica el catálogo y `origin` identifica la pantalla que decide qué registros y campos devuelve. Registrar la combinación en `OptionController` y asignar el módulo en `config/permissions.php`. El controlador delega la consulta al servicio de dominio dentro del tenant activo y devuelve `records`, `page` y `has_more`. Usar el mismo cliente para Vue Select y Select2. Las listas fijas y las referencias que ya forman parte de `initParams` pueden resolverse localmente.

Todos los formularios deben reutilizar componentes y patrones existentes:

- `InputText` para texto.
- `InputNumber` para numéricos y dinero.
- `InputDate`, `InputMonth` e `InputDatetime` para fechas, meses y fecha con hora.
- `InputSelect` y `InputSelect2` para selección nativa y Select2.
- `InputSlot` cuando el input necesita prefijos, sufijos, acciones, moneda o contadores.
- `vue-select` con estilos globales y `SelectNoOptions` para mensajes vacíos en español.
- `CopyButton` copia valores puntuales. La presentación estándar en Vue y Blade es `type="icon"`, con `fa-solid fa-clipboard`, tooltip y activación por clic, Enter o Espacio. El icono usa `--br-font-size-table` y toma el estilo de contorno al pasar el cursor o recibir foco del teclado. Usar `type="button"` solo cuando el diseño requiera un botón. El valor y el control son hijos directos de `br-copy-value`, que define una separación común de `0.3125rem`.
- `BarcodeDownloadButton` para códigos de barras descargables.

Los controles compuestos se tratan como una sola unidad visual. El hover, el foco y los estados deshabilitado y de error se aplican al conjunto. En cualquier campo agrupado, los complementos informativos como prefijos, símbolos de moneda y unidades usan el azul de foco `--br-primary` mientras el campo tiene el foco. El valor editable, los fondos y los botones de acción conservan sus propios estilos.

System, Guest y Platform comparten las métricas de `03-form-controls.css`. Los campos de una línea usan `--br-control-height`, `--br-font-size-ui` y peso regular. El placeholder, la búsqueda y el valor seleccionado usan el mismo tamaño. Los textos e iconos se centran verticalmente y los valores arrancan con el mismo padding. Las áreas de texto y las selecciones múltiples pueden crecer según su contenido.

En los selectores, el fondo se pinta en la superficie redondeada del campo, como `.vs__dropdown-toggle` o `.select2-selection`. El contenedor externo queda transparente para que las esquinas muestren el fondo de la página.

En Vue Select y Select2, cada opción elegida que aparece al reabrir el desplegable lleva un fondo suave, borde lateral primario y una marca de selección. El estilo vive en `03-form-controls.css` y usa el estado de selección de cada biblioteca tanto para selección única como múltiple, incluidos menús flotantes y la opción Todos. Todas las opciones reservan el mismo borde transparente para evitar desplazamientos al seleccionar o enfocar. El hover y el foco por teclado mantienen el fondo azul suave, pero usan un borde lateral gris discreto en opciones no seleccionadas; la opción seleccionada conserva siempre su borde azul y la marca, incluso al pasar el cursor. No duplicar estas reglas en estilos de almacenes u otras entidades.

Errores de campo:

- Bajo el campo, mostrar sólo el error: `Campo obligatorio.`.
- En resúmenes o modales, incluir el campo: `Nombre: Campo obligatorio.`.
- Los errores deben ser pequeños, legibles y no competir con el label.
- El borde rojo debe envolver todo el control compuesto.

### Convención de validaciones

- Los `FormRequest` deben parsear y normalizar IDs, montos, fechas opcionales y arrays antes de llegar al controlador.
- Las validaciones transversales viven en `CompanyFormRequest`; cuando una entidad requiera texto propio, debe mantener el mismo estilo corto y accionable.
- Bajo el campo se muestra solo el error, por ejemplo `Campo obligatorio.`. En resumen o modal se incluye el campo, por ejemplo `Nombre: Campo obligatorio.`.
- En el catálogo, `CommissionValueInput` muestra el asterisco cuando se selecciona porcentaje o monto fijo. El valor debe ser mayor que cero. Un porcentaje admite hasta 100 %. Servicios y Membresías también exigen una cantidad entera positiva cuando se activa el control de cupos.
- Pendiente controlado: revisar `resources/lang/es/validation.php` entidad por entidad para reemplazar textos residuales de Laravel/librerías y mantener errores visibles y consistentes en código.

## Modales y SweetAlert

Las modales de System deben usar el patrón global:

- Backdrop estático.
- No cerrar al hacer clic fuera.
- No cerrar con Escape.
- Header, body y footer con espaciado estándar.
- El botón secundario que descarta una modal se llama **Cerrar** en toda la plataforma, incluidas las confirmaciones de SweetAlert. La X del encabezado lleva un nombre accesible que empieza por «Cerrar».
- Footer con superficie neutral cuando aplique.
- Nuevas pantallas deben reutilizar `@System/Components/Generics/FormModal.vue` como base. El componente aplica `br-entity-modal`, `br-modal-standard` y `br-modal-shell`, bloquea backdrop/teclado y expone slots `header`, `body`, `footerClose` y `footer`.
- El footer del componente reserva **Cerrar** a la izquierda y las acciones de trabajo a la derecha. El slot `footerClose` conserva el mismo texto cuando necesita un botón personalizado. No duplicar estructuras `modal-header`, `modal-body` y `modal-footer` directamente salvo migraciones legacy.
- El header del componente muestra eyebrow opcional, título, subtítulo opcional y X centrada verticalmente; el body usa superficie neutral y el footer `#f7f8fa`.

SweetAlert debe usarse para confirmaciones, errores, success y procesos globales. No introducir delays artificiales con `setTimeout`; si hay trabajo asíncrono, mostrar loader inmediatamente.

Todos los SweetAlert de System y Guest llevan `allowEscapeKey: false`. El cierre se realiza con `Cerrar`, la X o la acción de reconocimiento `Entendido`, según corresponda. Los loaders se cierran desde el flujo que los inició. Estas reglas también aplican a llamadas directas a `Swal.fire`.

### Confirmaciones SweetAlert

En todos los submódulos, activos o no, las confirmaciones siguen el mismo patrón. `Alerts.confirmAction` es la opción preferida para acciones simples; si una confirmación requiere inputs o `preConfirm`, conservar esa lógica y aplicar la misma convención. El estilo global reside en `resources/css/System/br-branding/95-swal-confirmations.css` y se genera en `public/System/assets/css/br-branding.css` con `npm run build:css:system`.

- No mostrar icono de question/warning ni centrar la cabecera. Título, descripción y campos se alinean a la izquierda. El color de peligro puede mantenerse en el botón de una acción destructiva, sin desplazar la jerarquía del texto.
- El título formula una pregunta breve y específica con verbo en infinitivo y sustantivo, entre `¿` y `?`: `¿Anular venta?`, `¿Duplicar perfil?`, `¿Cerrar sesión?`. No usar `¿Deseas…?`, `Confirmar acción` ni títulos vagos. El texto del botón mantiene la acción en afirmativo, sin signos.
- La cabecera y el pie usan el mismo gris muy tenue (`--br-neutral-panel`), mientras el cuerpo permanece blanco. Una línea separa cabecera y cuerpo; otra separa cuerpo y botones. Conservar esta estructura también cuando haya campos de entrada.
- El cuerpo no repite el título ni vuelve a preguntar. Explica consecuencias, alcance, datos afectados, irreversibilidad o pasos que el usuario no conoce. Mantenerlo breve y concreto; si hay campos obligatorios, indicar para qué se solicitan.
- Mostrar `Cerrar` a la izquierda y la acción principal a la derecha. La inicialización compartida de SweetAlert aplica el texto de cierre. El botón principal expresa la acción específica, por ejemplo `Anular venta`, con mayúscula solo al inicio salvo nombres propios. La acción principal debe ser visualmente preponderante.
- Mantener la `X` visible arriba a la derecha con `showCloseButton: true` y `closeButtonAriaLabel: "Cerrar confirmación"`. Debe tener un área de clic de al menos `2rem`, sin fondo, con contraste suficiente y separación del título. La X y `Cerrar` descartan la confirmación. No cerrar haciendo clic fuera si se están revisando consecuencias o completando campos.
- Confirmaciones de descarga también siguen el patrón. Los mensajes de éxito, error y el loader no son decisiones de dos botones y conservan su presentación específica.

### Mensajes de estado SweetAlert

Los mensajes de éxito, advertencia, información, error y pregunta usan `Alerts.showMessage` (texto plano) o `Alerts.generateAlert` (detalle tabular) y la clase visual `br-swal-alert--message`. Este patrón se aplica a todos los mensajes de un solo botón, no a las confirmaciones ni al loader.

- Ancho predeterminado compacto de `25rem`; si el contenido requiere más espacio, pasar `width` en píxeles (por ejemplo, `width: 520`). El modal nunca debe exceder el viewport. No ampliar todos los mensajes por una excepción.
- El texto visible, esté en `title` o en el cuerpo HTML, usa la misma escala tipográfica que el cuerpo de una confirmación mediante `--br-table-value-font-size`. Mantener esa equivalencia para éxito, advertencia, información, error y pregunta. La jerarquía se expresa con peso y espaciado.
- Icono circular compacto, centrado y proporcionado al símbolo, con color semántico: verde para éxito, ámbar para advertencia, azul para información o pregunta y rojo para error. El éxito usa una marca SVG completa; no mezclar el círculo reducido con los trazos internos nativos de SweetAlert. Usar el icono común de `br-swal-alert--message`.
- La entrada del icono es breve y sin saltos de layout; en éxito, la marca se dibuja tras aparecer el círculo. Desactivar toda esa animación con `prefers-reduced-motion: reduce`, dejando el icono completo visible de inmediato.
- Título o mensaje breve y concreto; el cuerpo añade información útil y no repite el título. Mostrar una única respuesta principal (`Entendido`) y evitar duplicar el mismo resultado con un aviso en la página o un toastr.
- Las confirmaciones conservan el patrón de dos botones descrito arriba, sin icono; el loader usa el `fa-circle-notch` compartido y no los iconos de estado.
