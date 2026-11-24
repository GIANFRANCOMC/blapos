# Estándares de interfaz de Blapos

## Sistema visual

System, Guest y Platform comparten [tokens FG](../../../resources/css/Shared/tokens.css), controles y patrones mediante [Shared/ui.css](../../../resources/css/Shared/ui.css). Sus entradas cargan la base desde el head con Vite. Los estilos de composición propios se mantienen en la superficie o el módulo que los usa. La pantalla de error carga directamente los tokens que necesita.

Source Sans 3 se sirve desde los assets del repositorio. Consulta la [escala tipográfica](TYPOGRAPHY.md) y la [paleta](COLOR_PALETTE.md) para elegir el token según su función. Usa `fg-` en clases propias y `--fg-*` en variables. Si un componente aporta el tamaño, peso o color, herédalo en sus hijos. Antes de añadir una clase, revisa Shared y los componentes existentes.

Las acciones primarias usan `fg-button`. Las variantes transmiten intención. Las acciones de fila usan `TableActionsDropdown` con el nombre accesible «Acciones». El botón principal de una modal nombra la entidad que se guarda, por ejemplo «Guardar producto».

### Convención tipográfica de tablas

Las celdas comparten alineación vertical al centro. Los valores comparables, como Stock actual, Diferencia, Variación y Saldo, usan `--fg-section-size`. `QuantityValue` mantiene el signo, el número y la unidad en una línea y reduce el tamaño de la unidad. Usa `ProductIdentity` para el nombre y los identificadores de producto. Su versión `expandable` permite mostrar los códigos en inventario. Usa `ExpandableText` para descripciones truncadas con «Ver más» y «Ver menos».

Los layouts que usan scripts de plantilla declaran `data-assets-path` con la URL absoluta de assets generada por Laravel. Los documentos exportados y los correos conservan sus propios formatos.

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
- Ofrecer «Limpiar filtros» en barras con varios campos mediante `ClearFiltersButton`. El botón usa una adaptación de `fa-brush` de Font Awesome, invertida verticalmente y con el mango alargado. Comparte por defecto los colores de la cabecera de tabla mediante `fg-filter-clear`. Se alinea al borde inferior y usa un tooltip de Bootstrap y una etiqueta accesible. Restablecer los campos juntos y conservar el contexto operativo, como el almacén. En filtros aplicados con Buscar, la limpieza solo modifica los campos pendientes. Evitar consultas o invalidaciones parciales de watchers durante el reinicio.
- Al terminar el restablecimiento, `Alerts.showFiltersCleared()` muestra el aviso informativo «Se limpiaron los filtros correctamente.» mediante Toastr. El botón oculta su tooltip al pulsarlo y lo vuelve a mostrar al pasar el cursor por encima.
- Los icon buttons requieren `aria-label` y tooltip.
- La integración compartida de SweetAlert oculta los tooltips de la página al abrir una alerta y durante el retorno de foco. Una vez cerrada, el tooltip vuelve a responder a una nueva interacción con su disparador. Los formularios y listados reutilizan este comportamiento en sus llamadas a `Swal.fire`.
- En desktop, botones secundarios de descarga pueden mostrarse sólo con icono si el tooltip es claro; en móvil deben mostrar icono y texto.
- Las tablas deben reutilizar la superficie global de encabezado y estados compactos.
- Servicios, Membresías, Categorías, Marcas y Recetas y platillos usan `InternalCodeValue` para mostrar el código interno centrado como texto. Productos muestra los códigos en su columna Identificación. Consulta el [patrón de Catálogo comercial](../patrones/CATALOGO_COMERCIAL.md).
- Encerrar cada tabla ancha en `table-responsive`. El contenedor toma el ancho disponible y la tabla desplaza sus columnas dentro de él. Menú lateral, filtros, acciones superiores y paginación quedan fuera del scroll. Los estados de carga y vacío se centran en el área visible. Los menús de fila usan posicionamiento fijo de Popper para abrir sin liberar el overflow de la tabla.
- Debajo de toda tabla paginada, incluidas las modales, usa `Paginator` con el objeto `pagination` del servidor, `links` y `loading`. El pie reúne la navegación y el total filtrado en `ListResultCount`. Mantén el pie fuera del scroll de la tabla. La carga lo oculta y al terminar vuelve a mostrar el total, incluso si es cero o hay una sola página.
- El pie usa el espaciado compartido de `fg-pagination`. En móvil permite que la navegación y el contador se acomoden al espacio disponible.
- El paginador compartido muestra `Primera`, `Anterior`, páginas próximas, `Siguiente` y `Última`. Sus botones son compactos y usan peso tipográfico normal; la página activa se distingue por el fondo, no por negrita. Los extremos no disponibles usan botones nativos deshabilitados. Cada módulo conserva sus propios colores y estilos de botones deshabilitados; no aplicar una neutralización global.
- Los estados vacíos y loaders usan `WithoutData` y `Loader`. En la tabla, el loader muestra únicamente el icono giratorio centrado. El texto de progreso se anuncia a lectores de pantalla. Sus filas llevan `fg-table__state` y quedan excluidas de fondos, bordes, sombras, cursor y transiciones de hover de los registros. Neutralizar las variables de tabla Bootstrap sin bloquear botones de reintento. El skeleton reserva la estructura del listado durante la carga inicial.
- Los tooltips que se descartan al abrir una modal se restauran globalmente al cerrarla desde `Alerts.js`; los módulos no deben repetir esta restauración para los disparadores de la página.
- Las modales conservan `modal-dialog-centered`. Apertura y cierre comparten duración y curva para el diálogo, la superficie y el fondo; respetar `prefers-reduced-motion`.
- Usar [AppTabs](../../../resources/js/Shared/AppTabs.vue) en formularios y páginas. `variant="descriptive"` muestra icono y descripción. La variante `compact` muestra el título. Con `layout="content"`, cada pestaña se ajusta a su texto. El modo `stretch` distribuye el espacio disponible. En ambos, la selección conserva dimensiones y posición. Si la pestaña seleccionada queda fuera del área visible, la barra se desplaza solo lo necesario para mostrarla.
- Asocia cada pestaña a su panel con `controlsPrefix` o usa `sharedControlsId` cuando varias pestañas comparten un panel. Se conserva la navegación por teclado. Los paneles que entran al flujo pueden usar el fundido de `Shared/skeleton.css` según la preferencia de movimiento.
- En modales con `AppTabs`, usar `fg-entity-modal--tabbed` y `fg-entity-form-section` para el contenido. El diálogo conserva su posición superior al cambiar la altura del panel. Aplica las utilidades de display de Bootstrap a un contenedor interior si el panel usa `v-show`.
- Las modales comparten `--fg-table-head` para encabezado y pie (gris azulado suave) y `--fg-surface` para el cuerpo (casi blanco). Ajustar estos tokens en `resources/css/Shared/tokens.css` para mantener contraste uniforme; no crear paletas de modal por módulo.

### Consultas de tablas y limpieza de registros

- Los listados paginados de Inventario y Catálogo comercial muestran 10 registros por defecto. Los controladores reutilizan `Utilities::$per_page_default` y aceptan un tamaño explícito mediante `per_page`, dentro del límite definido para cada listado.
- Antes de cada consulta de listado (búsqueda, paginación, reintento o recarga), activar `loading` y vaciar los registros antes del `await`. Para tablas paginadas, usar `Requests.emptyPagination()` de `Helpers/ListState.js`, reexportado por `Requests.js`: devuelve un objeto nuevo con `data: []`, `total: 0`, `links: []` y metadatos reiniciados. Para listados sin paginación, asignar `[]`. Limpiar también los resúmenes que dependan de esos resultados.
- Solo una respuesta válida puede repoblar la tabla. Si la consulta falla, conservar el estado vacío; nunca restaurar filas, totales o enlaces de la búsqueda anterior. Liberar `loading` en `finally`, también ante excepciones. Las pantallas con consultas simultáneas deben conservar sus controles de versión para descartar respuestas antiguas.
- **Buscar** aplica una copia de los campos de filtro y consulta desde la primera página. Escribir, elegir una opción, cambiar una fecha o limpiar un campo actualiza únicamente los filtros pendientes. Las filas, los totales y la paginación permanecen visibles hasta iniciar una consulta. Los watchers de filtros pueden ajustar opciones dependientes del formulario.
- Los campos de una barra comparten una fila flexible de Bootstrap. Los componentes que agrupan filtros aportan sus columnas directamente a esa fila para que los campos mantengan su orden y pasen a la línea siguiente según el ancho disponible. El skeleton usa la misma cuadrícula.
- Paginar, reintentar, refrescar o exportar reutiliza los filtros aplicados. `Requests.get` elimina de la URL los filtros enviados en `data` mediante `currentQueryRoute`, incluso vacíos o nulos. En URLs construidas manualmente, sobrescribir o eliminar cada parámetro. Cuando se reabre una modal de búsqueda que inicia su propia consulta, preparar primero sus filtros iniciales.
- Durante la consulta mostrar el indicador habitual del listado y ocultar conjuntamente paginador y cantidad de resultados. Al finalizar, mostrar el total de la respuesta actual o cero si falló.

### Carga inicial con skeleton

System, Guest y Platform muestran un skeleton durante la carga inicial. El skeleton conserva el espacio de filtros, pestañas, encabezados y formularios. En los listados, usa el mismo `Loader` de la tabla real en una fila `fg-table__state`, con el `colspan` correspondiente. El estado inicial muestra solo esa fila de carga y mantiene ocultos el contador y la paginación. Mantener el breadcrumb visible cuando sea posible.

- Las pantallas genéricas reutilizan `InitialPageMixin` y `PageSkeleton`. Pasar `table` a `PageSkeleton` cuando la vista presenta una tabla. Las pantallas con skeleton propio reutilizan `Loader` en su fila de carga.
- Ejecutar `await this.loadInitialParams(() => this.initParams(...))` o liberar `initialLoading` en el `finally` de la consulta inicial. El listado dependiente comienza después de los parámetros y presenta su loader habitual. Las pantallas cuyo primer dato remoto es un listado, como tenants y proveedores, mantienen el skeleton durante esa primera consulta. Las búsquedas y la paginación posteriores usan el estado de carga del listado.
- `initParams` muestra su avance dentro de la página. Reservar Swal para confirmaciones, errores, guardados y exportaciones.
- Construir el skeleton en Vue y su CSS de pantalla o componente reutilizable. Compartir variantes cuando varias pantallas tengan la misma estructura. Usar alturas y tokens `--fg-*` de los controles reales, un estado accesible (`role="status"` y texto legible para lectores de pantalla) y desactivar la animación con `prefers-reduced-motion`.
- Las cinco vistas de Inventario usan `InventorySkeleton` con la distribución de filtros y acciones de cada vista. La tabla conserva las cabeceras, los anchos y el contenedor responsive del listado correspondiente durante la carga inicial.
- Envolver el skeleton y su rama de contenido en `InitialLoadTransition` de `Components/Generics`. El fundido simultáneo de opacidad comparte una celda de grid: las dos ramas no se apilan ni duplican la altura durante el relevo. Usar exclusivamente `--fg-load-duration` y `--fg-load-easing` de `Shared/tokens.css` (180 ms de base); no añadir tiempos por pantalla, desplazamientos, esperas mínimas ni retrasar peticiones para animar. La rama que sale queda inerte y oculta para lectores de pantalla; `prefers-reduced-motion` desactiva la transición. Las modales y otros elementos ajenos a la carga permanecen fuera del componente.
- En todo listado, activar `loading` antes de solicitar datos y liberarlo en `finally`, también ante errores. Mantener ocultos el paginador y el contador mientras ese estado esté activo.

## Formularios

### Botones y menús de acciones

`ActionsButton.vue` centraliza la presentación y el tooltip de acciones. Por defecto usa `type="icon"`, con tres puntos verticales, fondo anaranjado estándar, tooltip «Acciones», área de botón, estados hover/foco/clic y Waves. En las botoneras, su ancho y alto usan `--fg-control-height`, igual que Buscar y Limpiar filtros, sin reducir su tamaño al cambiar el icono. Para mostrar el texto «Acciones» y la flecha, solicitar explícitamente `type="button"`. `label` identifica el contexto para lectores de pantalla y `text` permite cambiar el texto de la variante con label. `variant="plain"` conserva la presentación sin recuadro de las acciones de fila; `solid` es la variante predeterminada para botoneras.

Los menús usan `ActionsDropdown.vue`, que reutiliza ese control y recibe las opciones en su slot. `TableActionsDropdown.vue` conserva compatibilidad con las tablas existentes, aplica `variant="plain"` y delega en el mismo menú, incluyendo `type`, `disabled` y `menuClass`. `FiltersSection` usa la variante de icono por defecto y expone `secondaryActionsType` si una pantalla necesita la variante con texto. Evitar botones de acciones y tooltips implementados por pantalla. Los botones que abren una modal de acciones usan `ActionsButton` sin desplegable. Las acciones específicas como Buscar, Agregar o Imprimir conservan su control propio.

### Resultado de operaciones en modales

`Shared/modal-results.js`, integrado con el SweetAlert compartido, coordina los mensajes finales de éxito y error en System, Guest y Platform. En éxito, cierra el formulario activo y espera `hidden.bs.modal` antes de mostrar el Swal; también reconoce un cierre ya iniciado por el módulo. No añadir retrasos por pantalla ni depender de hacer clic en un botón Cerrar que todavía pueda estar deshabilitado.

Durante un resultado se ocultan las modales y sus backdrops para presentar un único mensaje. En error se conserva el formulario y se restaura con sus datos, validaciones y foco al cerrar el aviso. En modales apiladas, un éxito cierra el formulario activo y conserva el formulario padre. La clase compartida `fg-modal-result-hidden` está definida en `Shared/modal-results.css`. Confirmaciones, loaders y Swal con campos de entrada conservan su flujo habitual. Los callbacks existentes de SweetAlert y la limpieza global de tooltips deben respetarse.

Las búsquedas remotas de selectores usan `Options.js` y `GET /options/{origin}/{entity}`. `entity` identifica el catálogo y `origin` identifica la pantalla que decide qué registros y campos devuelve. Registrar la combinación en `OptionController` y asignar el módulo en `config/permissions.php`. El controlador delega la consulta al servicio de dominio dentro del tenant activo y devuelve `records`, `page` y `has_more`. Usar el mismo cliente para Vue Select y Select2. Las listas fijas y las referencias que ya forman parte de `initParams` pueden resolverse localmente.

Todos los formularios deben reutilizar componentes y patrones existentes:

- `InputText` para texto.
- `InputNumber` para numéricos y dinero.
- `InputDate`, `InputMonth` e `InputDatetime` para fechas, meses y fecha con hora.
- `InputSelect` y `InputSelect2` para selección nativa y Select2.
- `InputSlot` cuando el input necesita prefijos, sufijos, acciones, moneda o contadores.
- `vue-select` con estilos globales y `SelectNoOptions` para mensajes vacíos en español.
- `CopyButton` copia valores puntuales. La presentación estándar en Vue y Blade es `type="icon"`, con `fa-solid fa-clipboard`, tooltip y activación por clic, Enter o Espacio. El icono usa `--fg-body-size` y toma el estilo de contorno al pasar el cursor o recibir foco del teclado. Usar `type="button"` solo cuando el diseño requiera un botón. El valor y el control son hijos directos de `fg-copy-value`, que define una separación común de `0.3125rem`.
- `BarcodeDownloadButton` para códigos de barras descargables.

Los controles compuestos se tratan como una sola unidad visual. El hover, el foco y los estados deshabilitado y de error se aplican al conjunto. En cualquier campo agrupado, los complementos informativos como prefijos, símbolos de moneda y unidades usan el azul de foco `--fg-primary` mientras el campo tiene el foco. El valor editable, los fondos y los botones de acción conservan sus propios estilos.

System, Guest y Platform comparten las métricas de `Shared/controls.css`. Los campos de una línea usan `--fg-control-height`, `--fg-body-size` y peso regular. El placeholder, la búsqueda y el valor seleccionado usan el mismo tamaño. Los textos e iconos se centran verticalmente y los valores arrancan con el mismo padding. Las áreas de texto y las selecciones múltiples pueden crecer según su contenido.

En los selectores, el fondo se pinta en la superficie redondeada del campo, como `.vs__dropdown-toggle` o `.select2-selection`. El contenedor externo queda transparente para que las esquinas muestren el fondo de la página.

En Vue Select y Select2, cada opción elegida que aparece al reabrir el desplegable lleva un fondo suave, borde lateral primario y una marca de selección. El estilo vive en `Shared/controls.css` y usa el estado de selección de cada biblioteca tanto para selección única como múltiple, incluidos menús flotantes y la opción Todos. Todas las opciones reservan el mismo borde transparente para evitar desplazamientos al seleccionar o enfocar. El hover y el foco por teclado mantienen el fondo azul suave, pero usan un borde lateral gris discreto en opciones no seleccionadas; la opción seleccionada conserva siempre su borde azul y la marca, incluso al pasar el cursor. No duplicar estas reglas en estilos de almacenes u otras entidades.

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
- Nuevas pantallas deben reutilizar `@System/Components/Generics/FormModal.vue` como base. El componente aplica `fg-modal` y sus elementos de cabecera, cuerpo y pie, bloquea backdrop/teclado y expone slots `header`, `body`, `footerClose` y `footer`.
- El footer del componente reserva **Cerrar** a la izquierda y las acciones de trabajo a la derecha. El slot `footerClose` conserva el mismo texto cuando necesita un botón personalizado. No duplicar estructuras `modal-header`, `modal-body` y `modal-footer` directamente salvo migraciones legacy.
- El header del componente muestra eyebrow opcional, título, subtítulo opcional y X centrada verticalmente; el body usa superficie neutral y el footer `#f7f8fa`.

SweetAlert debe usarse para confirmaciones, errores, success y procesos globales. No introducir delays artificiales con `setTimeout`; si hay trabajo asíncrono, mostrar loader inmediatamente.

Todos los SweetAlert de System y Guest llevan `allowEscapeKey: false`. El cierre se realiza con `Cerrar`, la X o la acción de reconocimiento `Entendido`, según corresponda. Los loaders se cierran desde el flujo que los inició. Estas reglas también aplican a llamadas directas a `Swal.fire`.

### Confirmaciones SweetAlert

En todos los submódulos, activos o no, las confirmaciones siguen el mismo patrón. `Alerts.confirmAction` es la opción preferida para acciones simples; si una confirmación requiere inputs o `preConfirm`, conservar esa lógica y aplicar la misma convención. El estilo global reside en `resources/css/Shared/feedback.css` y se carga desde la entrada común de Vite.

- No mostrar icono de question/warning ni centrar la cabecera. Título, descripción y campos se alinean a la izquierda. El color de peligro puede mantenerse en el botón de una acción destructiva, sin desplazar la jerarquía del texto.
- El título formula una pregunta breve y específica con verbo en infinitivo y sustantivo, entre `¿` y `?`: `¿Anular venta?`, `¿Duplicar perfil?`, `¿Cerrar sesión?`. No usar `¿Deseas…?`, `Confirmar acción` ni títulos vagos. El texto del botón mantiene la acción en afirmativo, sin signos.
- La cabecera y el pie usan el mismo gris muy tenue (`--fg-canvas`), mientras el cuerpo permanece blanco. Una línea separa cabecera y cuerpo; otra separa cuerpo y botones. Conservar esta estructura también cuando haya campos de entrada.
- El cuerpo no repite el título ni vuelve a preguntar. Explica consecuencias, alcance, datos afectados, irreversibilidad o pasos que el usuario no conoce. Mantenerlo breve y concreto; si hay campos obligatorios, indicar para qué se solicitan.
- Mostrar `Cerrar` a la izquierda y la acción principal a la derecha. La inicialización compartida de SweetAlert aplica el texto de cierre. El botón principal expresa la acción específica, por ejemplo `Anular venta`, con mayúscula solo al inicio salvo nombres propios. La acción principal debe ser visualmente preponderante.
- Mantener la `X` visible arriba a la derecha con `showCloseButton: true` y `closeButtonAriaLabel: "Cerrar confirmación"`. Debe tener un área de clic de al menos `2rem`, sin fondo, con contraste suficiente y separación del título. La X y `Cerrar` descartan la confirmación. No cerrar haciendo clic fuera si se están revisando consecuencias o completando campos.
- Confirmaciones de descarga también siguen el patrón. Los mensajes de éxito, error y el loader no son decisiones de dos botones y conservan su presentación específica.

### Mensajes de estado SweetAlert

Los mensajes de éxito, advertencia, información, error y pregunta usan `Alerts.showMessage` (texto plano) o `Alerts.generateAlert` (detalle tabular) y la clase visual `fg-swal`. Este patrón se aplica a todos los mensajes de un solo botón, no a las confirmaciones ni al loader.

- Ancho predeterminado compacto de `25rem`; si el contenido requiere más espacio, pasar `width` en píxeles (por ejemplo, `width: 520`). El modal nunca debe exceder el viewport. No ampliar todos los mensajes por una excepción.
- El texto visible, esté en `title` o en el cuerpo HTML, usa la misma escala tipográfica que el cuerpo de una confirmación mediante `--fg-section-size`. Mantener esa equivalencia para éxito, advertencia, información, error y pregunta. La jerarquía se expresa con peso y espaciado.
- Icono circular compacto, centrado y proporcionado al símbolo, con color semántico: verde para éxito, ámbar para advertencia, azul para información o pregunta y rojo para error. El éxito usa una marca SVG completa; no mezclar el círculo reducido con los trazos internos nativos de SweetAlert. Usar el icono común de `fg-swal`.
- La entrada del icono es breve y sin saltos de layout; en éxito, la marca se dibuja tras aparecer el círculo. Desactivar toda esa animación con `prefers-reduced-motion: reduce`, dejando el icono completo visible de inmediato.
- Título o mensaje breve y concreto; el cuerpo añade información útil y no repite el título. Mostrar una única respuesta principal (`Entendido`) y evitar duplicar el mismo resultado con un aviso en la página o un toastr.
- Las confirmaciones conservan el patrón de dos botones descrito arriba, sin icono; el loader usa el `fa-circle-notch` compartido y no los iconos de estado.
