# 11 - Productos

## Qué hace

Administra los registros de `items` cuyo `type` es `product`. Centraliza identificación comercial, categorías, precios, publicación y configuración inicial de inventario por almacén.

Aunque productos, servicios y membresías comparten la tabla `items`, este módulo aplica reglas exclusivas de productos: código de barras EAN-13, stock físico y alertas por almacén.

## Archivos

- Ruta: `routes/System/Catalogs/Product.php`
- Controlador: `app/Http/Controllers/System/Catalogs/ProductController.php`
- Servicio principal: `app/Services/System/Catalogs/Products/ProductService.php`
- Exportador Excel: `app/Exports/System/Catalogs/Products/ProductListExport.php`
- Configuración: `app/Services/System/Catalogs/Products/ProductConfigService.php`
- Servicio de inventario: `app/Services/System/Warehouses/Warehouses/WarehouseItemService.php`
- Request base: `app/Http/Requests/System/Catalogs/Products/ProductRequest.php`
- Requests HTTP: `StoreProductRequest`, `UpdateProductRequest`
- Regla EAN-13: `app/Rules/System/Catalogs/ValidEan13.php`
- Modelo: `app/Models/System/Catalogs/Item.php`
- Vue: `resources/js/System/Pages/Catalogs/products/main.vue`
- Generador PNG reutilizable: `resources/js/System/Components/BarcodeDownloadButton.vue`
- Estilos: `public/System/assets/css/br-branding.css`, parcial `br-branding/81-core-roles-entities.css`
- Tablas: `items`, `brands`, `category_items`, `categories`, `warehouses`, `warehouse_items`

## Exportación Excel

La barra de filtros permite usar `Descargar Excel` junto a `Agregar producto`. La acción está habilitada en la configuración del módulo mediante `hasDownloadRecords: true`; el componente reutilizable `FiltersSection` mantiene `showDownloadButton: false` por defecto para no mostrarla en módulos que todavía no ofrecen exportación.

El endpoint `GET /products/export` recibe `filter_by` y `word`, exactamente los mismos parámetros del listado. No recibe `page` ni `per_page`: descarga todos los registros que coinciden con el filtro actual y conserva el orden alfabético por producto.

La consulta no se duplica. `ProductService::getFilteredListQuery()` centraliza empresa, tipo de item, filtros, relaciones y orden; `getPaginatedList()` agrega únicamente la paginación y `ProductListExport` consume la consulta completa mediante `FromQuery`.

El archivo incluye:

- código interno y código de barras preservados como texto;
- producto, marca, categorías y descripción comercial;
- moneda, precio de venta, mínimo y máximo;
- stock total, cantidad de almacenes y almacenes con stock bajo;
- estado y detalle del inventario por sucursal/almacén;
- visibilidad para clientes, visibilidad del precio y estado del producto.

El encabezado utiliza secondary navy y un acento primary. Las filas con inventario que requiere atención resaltan las columnas de alerta con fondo rojo suave; el inventario saludable usa verde suave. La primera fila queda fija y el reporte incorpora autofiltro.

La descarga frontend usa `Requests.download()`, helper genérico que solicita un `blob`, respeta el nombre enviado por `Content-Disposition`, libera el objeto temporal del navegador y normaliza respuestas de error JSON aunque lleguen como `Blob`.

Esta exportación vive exclusivamente bajo `System/Catalogs/Products`, porque contiene información operativa de la empresa autenticada. `Guest` no comparte la ruta, consulta ni exportador.

En escritorio, la descarga se presenta como un botón verde compacto con el icono de Excel y el tooltip `Descargar Excel`; por debajo de `992px` recupera icono y texto para que la acción sea explícita y fácil de pulsar. Este comportamiento se activa mediante `downloadIconOnlyOnDesktop`, deshabilitado por defecto en `FiltersSection`.

## Datos del producto

### Identificación

- `items.brand_id`: marca opcional de la empresa; una marca puede agrupar muchos productos.
- `items.internal_code`: código interno único entre productos de la empresa.
- `items.barcode`: código de barras validado como único entre todos los items de la empresa desde el backend; técnicamente usa formato EAN-13.
- `items.name`: nombre comercial.
- `items.description`: descripción breve.
- `items.type`: siempre `product`.
- `items.status`: `active` o `inactive`.

### Precio

- `items.currency_id`: moneda.
- `items.price`: precio de venta.
- `items.price_includes_tax`: indica si el precio de venta ya incluye IGV. Por defecto queda activo para venta al público.
- `items.igv_exempt`: indica si el producto está exonerado de IGV. Cuando está activo, no se calcula IGV para ese detalle y `price_includes_tax` queda desactivado.
- `items.min_price`: límite inferior opcional.
- `items.max_price`: límite superior opcional.
- `items.commission_type`: regla interna de comisión para ventas del producto (`none`, `percentage`, `fixed`).
- `items.commission_value`: valor de la comisión. Si es porcentaje aplica sobre el total de línea; si es monto fijo aplica por unidad vendida.
- `items.capacity_control_enabled`: activa cupos comerciales opcionales para el producto, adicionales al stock físico.
- `items.capacity_limit`: cantidad máxima comercial disponible cuando el control de cupos está activo.
- `items.capacity_used`: cupos ya consumidos por ventas confirmadas.

### Publicación

- `items.see_my_web`: control principal para exponer el producto en el catálogo comercial.
- `items.see_my_web_price`: permite exponer el precio únicamente cuando `see_my_web` está activo.

El nombre histórico de estas columnas se conserva por compatibilidad. La interfaz utiliza los conceptos “Publicar producto” y “Mostrar precio” para no limitar su uso futuro exclusivamente a una página web.

## Inventario por almacén

Cada producto tiene un registro en `warehouse_items` por cada almacén activo de la empresa:

- `warehouse_id`: almacén específico.
- `item_id`: producto.
- `quantity`: stock actual.
- `minimum_stock`: umbral de alerta del producto en ese almacén.
- `status`: estado de la relación.

El mínimo se guarda por almacén y no de forma general. Una sucursal puede tener distinta demanda, capacidad o frecuencia de reposición, y la estructura también admite múltiples almacenes por sucursal.

Existe una restricción única para `warehouse_id + item_id`, evitando duplicar el inventario de un producto dentro del mismo almacén.

## Relación con recetas y platillos

El módulo `recipes.index` usa `items` como base comercial vendible. Esto evita duplicar productos o crear un tipo nuevo de item antes de ajustar ventas, POS, compras y reportes.

Cuando un producto representa un platillo, la fórmula operativa vive en `recipe_dishes` y sus tablas hijas. Productos sigue administrando precio, marca, categorías, código de barras, publicación y stock inicial; Recetas y platillos administra insumos, toppings, extras, sabores, merma y rendimiento.

## Flujo de creación

1. Vue carga categorías, monedas, estados y todos los almacenes activos de la empresa.
2. Se generan valores iniciales para código interno y código de barras.
3. El usuario registra stock inicial y stock mínimo por almacén.
4. El backend valida el producto, EAN-13, unicidad, almacenes y cantidades.
5. `ProductService` crea `items` dentro de una transacción.
6. `WarehouseItemService::syncProductInventory()` crea un registro por almacén.
7. En creación, `quantity` toma el stock inicial indicado.
8. Se sincronizan las categorías.

Si un almacén activo no tiene valores explícitos, se crea con cantidad y mínimo en cero.

## Flujo de edición

- Se pueden actualizar datos comerciales, código de barras, publicación, estado y stock mínimo.
- El stock actual se muestra como solo lectura.
- La cantidad existente no se modifica desde Productos; debe cambiarse desde Inventario para no mezclar catálogo con operaciones de inventario.
- El stock inicial informado al crear un producto genera una entrada `product_opening` en `inventory_movements`, con saldo anterior y resultante por almacén.
- En edición, `initial_stock` no se valida ni se envía al backend. El formulario guarda únicamente `minimum_stock` por almacén; el saldo actual puede ser negativo y es informativo.
- Si aparece un almacén nuevo, se crea automáticamente la relación faltante con cantidad cero.

## Código de barras

- Formato requerido: EAN-13.
- Debe contener trece dígitos y un dígito de control válido.
- Es único por empresa, sin limitar la validación al tipo `product`.
- Puede escribirse o leerse con escáner.
- El botón con icono de código de barras genera un EAN-13 con prefijo interno `200`, evitando competir con rangos comerciales GS1.
- La generación frontend mejora la experiencia, pero el backend siempre vuelve a validar formato y unicidad.
- Desde el listado, `BarcodeDownloadButton` renderiza el EAN-13 mediante `JsBarcode` y descarga una etiqueta PNG sin solicitar otra respuesta al backend.
- La imagen contiene únicamente las barras EAN-13 y su valor legible. El archivo usa el patrón `codigo-barras-{codigo-interno}.png`.
- El PNG se genera con fondo transparente y barras negras para poder superponerlo sobre distintos diseños de etiqueta o superficies de impresión. La validez del símbolo depende del EAN-13 ya validado al guardar el producto.

## Reglas de negocio

- El producto siempre se guarda con `type = product`.
- La marca es opcional, debe pertenecer a la empresa y no puede asignarse si está inactiva.
- Una marca inactiva ya asociada puede conservarse durante una edición para no romper datos históricos.
- Código interno, código de barras, nombre, precio, moneda y estado son obligatorios.
- El código interno es único entre productos de la empresa.
- El código de barras es único entre todos los items de la empresa.
- Precio, stock inicial y stock mínimo no pueden ser negativos.
- `price_includes_tax` se guarda como booleano y se usa en ventas para decidir si el IGV configurado incrementa o no el total del detalle.
- `igv_exempt` se guarda como booleano y tiene prioridad sobre `price_includes_tax`: el detalle no genera IGV, no aporta base gravada y el precio completo queda como precio del producto.
- La comisión es opcional y no altera el precio ni el total cobrado al cliente; se guarda como dato interno para liquidaciones y reportes.
- Si la comisión es porcentual, no puede superar el 100%. Si es monto fijo, se calcula por unidad vendida.
- El control de cupos es opcional. Si está desactivado, `capacity_limit` queda nulo y `capacity_used` queda en cero.
- Si se activa control de cupos, `capacity_limit` es obligatorio, entero y no puede ser menor que los cupos ya consumidos.
- Los cupos no reemplazan el inventario físico del producto; sirven para campañas, packs, cupos comerciales o disponibilidad limitada adicional al stock por almacén.
- El precio debe respetar los límites mínimo y máximo configurados.
- Los almacenes enviados deben estar activos y pertenecer a sucursales activas de la empresa autenticada.
- Las categorías deben estar activas y pertenecer a la empresa autenticada.
- La moneda debe existir y estar activa.
- El precio mínimo no puede superar al precio de venta.
- El precio máximo no puede ser menor que el precio de venta ni que el precio mínimo.
- Código interno, código de barras, precios, arreglos y relaciones se vuelven a validar en backend aunque exista validación frontend.
- No se permite repetir un almacén dentro del formulario.
- Si `see_my_web` es falso, `see_my_web_price` se fuerza a falso.
- Crear o editar producto, inventario y categorías ocurre dentro de una transacción.

## Interfaz

- Tabla compacta con producto, identificación, precio, inventario, publicación, estado y acción.
- Código interno y código de barras se muestran como identificadores distintos; el formato EAN-13 se explica únicamente como ayuda técnica en tooltips y documentación.
- El inventario resume cantidad total y almacenes que alcanzaron su mínimo.
- La marca aparece inmediatamente debajo del nombre en una cápsula compacta de azul suave, con icono y nombre. Se diferencia del código interno sin añadir otra columna; el icono muestra el tooltip `Marca`.
- Cuando existe descripción, se conserva una separación adicional después de la marca para que ambos datos puedan leerse como niveles distintos.
- Los iconos de publicación distinguen disponibilidad del producto y visibilidad del precio.
- El formulario se organiza en tres pestañas: Datos y precio, Inventario e Información adicional.
- La primera pestaña agrupa nombre, código interno y código de barras en una fila; precio de venta, precio mínimo y precio máximo en otra.
- El estado se selecciona mediante el selector reutilizable `vue-select`, con las mismas reglas visuales y de interacción que el resto de selectores del sistema.
- El selector de estado ocupa cuatro columnas en escritorio para evitar opciones innecesariamente anchas.
- En resoluciones `lg`, el selector de estado ocupa seis columnas para conservar una proporción cómoda.
- La primera pestaña contiene también Marca inmediatamente antes de Estado, evitando separar datos básicos de clasificación durante el alta.
- La segunda pestaña corresponde a Inventario y la tercera, `Información adicional`, presenta primero la descripción comercial, luego el control opcional de cupos, las categorías y finalmente la visibilidad para clientes.
- La sección `Visibilidad para clientes` explica expresamente que publicar el producto o mostrar su precio controla la información visible fuera de la plataforma y no modifica el estado interno Activo o Inactivo.
- Marca y Categorías incluyen una acción contextual `Agregar` presentada como enlace azul primary, acompañada por un icono circular de suma alineado verticalmente con el texto. Cada acción abre un modal rápido sin cerrar ni limpiar el formulario de Producto.
- Al crear una Marca o Categoría, el registro se incorpora a las opciones disponibles sin reemplazar ni ampliar automáticamente la selección actual del producto.
- Las altas rápidas no vuelven a solicitar todo `initParams`: actualizan de forma reactiva `options.brands.records` u `options.categories.records`; el backend ya invalida `ProductConfigService` para futuras cargas.
- El selector de Marca permite limpiar la relación y muestra únicamente marcas activas.
- Los selectores reutilizan una `X` tipográfica compacta y centrada ópticamente con la flecha para limpiar valores, evitando deformaciones del SVG y manteniendo un área clicable cómoda.
- Las tres pestañas ocupan todo el ancho del modal, tienen separación visual y resaltan claramente la sección activa.
- El modal utiliza el desplazamiento natural de Bootstrap, sin `modal-dialog-scrollable`; la navegación `nav-pills` permanece en el flujo del formulario para impedir que los campos pasen visualmente por detrás.
- Al existir errores, se marca la pestaña afectada y se abre automáticamente la primera que requiere corrección.
- Cada apertura reemplaza completamente los datos del formulario y genera nuevos códigos al crear, evitando mezclar información entre creación y edición.
- El cierre, Cancelar y el evento `hidden.bs.modal` limpian datos, errores y pestaña activa.
- Los campos de stock reutilizan el componente `InputNumber`.
- Las sucursales y almacenes se muestran juntos para evitar ambigüedad.
- En móvil, cada fila de inventario pasa a disposición vertical.
- En móvil, al ocultarse el encabezado tabular, cada control conserva su label visible (`Stock inicial`, `Stock actual` o `Stock mínimo`) y se agrupa en una superficie neutral para mantener el contexto.
- Los controles y estados usan los tokens `--br-*` del branding.
- Los botones de generación y edición usan iconos con tooltip.
- Los generadores de código interno y código de barras permanecen disponibles tanto al agregar como al editar. Esto evita exigir que el usuario conozca los formatos válidos; al utilizarlos, reemplazan el valor actual y las reglas backend vuelven a comprobar formato y unicidad.

## Integraciones impactadas

- `ProductConfigService` obtiene categorías y almacenes mediante `CompanyReferenceDataService`, y monedas mediante `MasterReferenceDataService`.
- `ProductConfigService` obtiene también las marcas activas mediante `CompanyReferenceDataService::brands()`.
- Inventario usa `warehouse_items.minimum_stock` y deja de comparar contra el valor fijo `5`.
- Al crear un almacén predeterminado, `WarehouseService` genera relaciones en cero para todos los productos existentes.
- El listado de Productos carga `warehouseItems.warehouse.branch` para resumir stock y alertas sin consultas posteriores desde Vue.

## Mejoras aplicadas

- La caché de `initParams` ya no se invalida de forma aislada. Productos declara una mutación del recurso compartido `items`, por lo que también refresca la configuración de Ventas.
- Cuando se crea o modifica una categoría, `InitParamsCacheInvalidationService` elimina la caché de Productos para que el selector muestre inmediatamente las categorías activas de la empresa.
- Cuando se crea o modifica una marca, la dependencia `BRANDS` elimina la caché de Marcas y Productos para actualizar el selector sin esperar el TTL.
- `ProductRequest` extiende `CompanyFormRequest`, normaliza cadenas y usa `ExistsInTenant` para sus relaciones directas; el almacén se valida además por alcance de sucursal.
- `items.barcode` tiene unicidad tenant-wide en backend y base de datos cuando el valor no es nulo.
- La tabla `items`, su relación opcional con `brands` y el menú de Marcas se definen directamente en las migraciones iniciales. Mientras el proyecto permita reiniciar el esquema, no se crean migraciones incrementales para modificar estas tablas existentes.
- `UniqueInTenant` cuenta con pruebas para duplicados, exclusión durante edición y filtros adicionales como `type`.

- Código de barras generado, editable y validado con formato EAN-13.
- Unicidad de código de barras por empresa.
- Stock inicial por almacén durante la creación.
- Stock mínimo por almacén.
- Compatibilidad con múltiples almacenes por sucursal.
- Publicación y visibilidad de precio habilitadas en la interfaz.
- Request base reutilizable para creación y actualización.
- Validación de pertenencia de almacenes a la empresa.
- Sincronización transaccional de inventario.
- Alertas de stock basadas en configuración real.
- UI responsive y alineada al branding.
- Cierre con una `X` simple y sin borde mediante la clase reutilizable `br-modal-close`.
- Footer de modal compacto y equilibrado mediante `br-entity-modal__footer`, con padding vertical simétrico y separación prudente entre acciones.
- Footer de modal con superficie gris `#f7f8fa`, configurable mediante `--br-entity-modal-footer-bg`.
- Body de modal sin reserva lateral fija del scrollbar y con padding horizontal interno reducido mediante variables reutilizables de `br-entity-modal`.
- Pestañas con fondo blanco y contenido del formulario en gris suave; las secciones no usan altura mínima fija para evitar scroll innecesario en pestañas cortas.
- Pestañas sin sombra superior y con fondo blanco; las inactivas mantienen borde superior gris y la activa solo cambia el borde superior y el círculo secuencial a secondary navy.
- Switches comerciales con fondo y borde verde suave cuando están activados; el color del checkbox marcado se hereda desde la regla global reutilizable de `br-branding.css`.
- Encabezados numéricos de inventario centrados para mejorar la lectura de stock inicial y stock mínimo.
- Tooltips explican que el código interno es privado para la empresa y que el código de barras identifica la etiqueta visible o escaneable del producto.
- Botones de generación compactos mediante `br-input-action`, integrados al input como una sola unidad: sin divisor interno, mismo borde del campo, foco compartido en el grupo y hover solo en el icono.
- `br-input-action` se adapta automáticamente a la altura real del input para mantener bordes superiores e inferiores nivelados.
- Prefijos de moneda y contadores de caracteres siguen el mismo patrón de control compuesto: sin divisor interno, borde unificado y foco compartido con el input.
- Los prefijos y contadores compactan el padding del borde compartido para evitar separaciones excesivas entre el addon y el valor editable.
- Los contadores de caracteres reutilizan `br-character-counter`, con fondo blanco, tamaño reducido y jerarquía visual equivalente a los símbolos e iconos integrados.
- Los select2 basados en `vue-select` conservan únicamente una flecha de despliegue pequeña, completa en ambos sentidos y sin fondo; su color comunica reposo o apertura sin añadir ruido visual.
- Estado y Categorías renderizan sus opciones mediante `append-to-body`; el menú queda sobre la modal y su footer, sin aumentar el scroll interno del formulario.
- El selector reutilizable de filtros conserva una sola línea aunque la opción sea extensa; muestra el contenido completo al mantener el cursor sobre el texto truncado.
- Los selectores de filtro, Estado y Categorías comparten menú flotante, altura, flecha, colores, elipsis y visualización completa por hover; ninguna opción extensa genera saltos de línea ni scroll horizontal.
- Los selectores de Productos reutilizan `SelectNoOptions` para comunicar `No hay opciones disponibles.` en español con un estado compacto. `br-branding.css` ofrece el mismo texto como cobertura visual para selectores System todavía no migrados al slot reutilizable.
- Botón Cancelar con borde gris visible y hover suave mediante `br-btn-cancel`.
- Las ayudas de campo reutilizan `br-field-help`; los controles compartidos residen en `br-branding.css` y no dependen del módulo Productos.
- El campo Estado se mantiene como select2 no buscable para conservar consistencia con los formularios existentes.
- Los prefijos de moneda reutilizan `br-currency-prefix`, con fondo blanco integrado al input, símbolo compacto tipo icono, color secondary suavizado y borde compartido.
- El código interno usa `company_settings.internal_code_prefixes.product` (`PRO` por defecto). El prefijo se presenta integrado al inicio del input y el backend lo normaliza mediante `InternalCodeService`.
- Al crear, stock inicial y stock mínimo comienzan vacíos para permitir escritura directa; el payload normaliza los campos no informados a cero. Mientras ambos estén vacíos, el almacén muestra el estado neutral `Pendiente de registrar`.
- Las altas rápidas de Marca y Categoría usan modales Bootstrap reales. El loader SweetAlert se muestra por encima de toda la interfaz y bloquea la interacción durante el guardado.
- Los errores inline son breves; los resúmenes frontend/backend muestran el nombre del campo mediante `Forms.getDescriptiveErrors`.
- La validación frontend se ejecuta antes de abrir el loader global. Los errores locales aparecen inmediatamente; el bloqueo visual se reserva para la petición asíncrona al backend.
- Ante errores en otra pestaña, el resumen se muestra primero y el formulario cambia a la primera sección afectada solo después de cerrar el aviso. Así se evita movimiento visual detrás de SweetAlert.
- El contenido de las pestañas usa una transición de entrada de `120ms`, limitada a opacidad y dos píxeles de desplazamiento.
- El listado muestra nombre, marca y descripción. Se retiró la cantidad de categorías para reducir ruido visual.
- El prefijo monetario usa `br-currency-prefix__symbol` para controlar su escala de forma independiente, con color secondary, peso medio y separación compacta respecto del importe.
- El CTA utiliza “Agregar producto” o “Editar producto”; durante el proceso muestra “Agregando” o “Editando” sin puntos suspensivos.
- El CTA principal no usa un icono fijo, porque su acción cambia entre agregar y editar.
- Las acciones reutilizan variantes semánticas: `br-btn-action-search`, `br-btn-action-open-create`, `br-btn-action-create`, `br-btn-action-update` y `br-icon-action-edit`.
- Buscar usa celeste informativo, agregar usa el azul primario de marca y editar usa el secundario navy; apertura y confirmación conservan el color correspondiente a su intención.
- Abrir el alta usa `br-btn-action-open-create` y confirmar el alta usa `br-btn-action-create`, ambos con azul primario.
- Abrir y confirmar una edición usan `br-icon-action-edit` y `br-btn-action-update` respectivamente, ambos con el secundario navy.
- Agregar y editar se distinguen entre sí por color; apertura y confirmación conservan la semántica cromática de su acción.
- `FiltersSection` usa la barra reutilizable `br-filter-bar`: controles compactos, etiquetas discretas y acciones alineadas al final.
- Todos los `vue-select` de System usan un indicador compacto con superficie neutral; al desplegar rota y adopta el azul de marca.
- Las acciones de la barra se alinean al inicio para evitar espacios muertos después del campo de búsqueda.
- La tabla usa `table-layout: fixed` y un `colgroup` con proporciones estables; Precio gana espacio y Producto se compacta para separar mejor los importes de Identificación.
- Identificación diferencia visualmente “Código interno” y “Código de barras” en filas etiquetadas.
- Identificación usa siempre las etiquetas compactas `Cód. interno` y `Cód. barras`, evitando abreviaciones variables o elipsis confusas.
- Ambos valores utilizan cápsulas equivalentes; el código de barras incorpora un borde y fondo primary muy suaves para diferenciarlo del código interno sin competir visualmente.
- Solo las etiquetas abreviadas de identificación muestran su nombre completo mediante el tooltip global; los valores no generan tooltips.
- El loading de persistencia informa la acción actual: `Agregando producto` o `Editando producto`.
- Código interno y código de barras usan el componente reutilizable `CopyButton`; por defecto muestra `Copiar` y `Copiado`, y puede activar `useLabelInTooltip` para mostrar textos contextuales como `Copiar código interno` y `Código interno copiado`.
- El código de barras incorpora además `BarcodeDownloadButton`, una acción con fondo celeste primary suave e icono secondary navy que genera y descarga su etiqueta. No usa verde para evitar confundirse con estados como `Activo`; sigue diferenciándose de `CopyButton`, que conserva una superficie neutral. El tooltip comunica `Descargar` y confirma `Descargado`.
- La barra de filtros incorpora `Etiquetas`, acción reutilizable de impresión por lote. Toma los registros visibles del listado filtrado, genera etiquetas EAN-13 en una hoja imprimible y omite registros sin código de barras válido para evitar impresiones incorrectas.
- Las filas de identificación reservan una columna uniforme para acciones alineada desde la izquierda. Copiar ocupa siempre la primera posición; Código de barras añade Descargar como segunda acción sin alterar la alineación de los valores.
- En pantallas pequeñas, los identificadores conservan su contenido completo y nunca usan puntos suspensivos. La tabla prioriza exactitud mediante desplazamiento horizontal antes que recortar un código interno o código de barras.
- El valor y sus acciones ocupan columnas independientes con ancho reservado; los botones Copiar y Descargar no pueden superponerse sobre los identificadores.
- `StatusBadge` añade automáticamente una clase normalizada desde la BD, como `br-status-active` o `br-status-inactive`, además de la variante semántica existente.
- Las etiquetas de estado son compactas y se perciben como información, no como botones.
- La tabla de Productos se adapta al ancho disponible en escritorio; el ancho mínimo y scroll horizontal se reservan para resoluciones de hasta `991.98px`.
- La barra de filtros no usa divisor inferior para mantener una composición minimalista.
- Venta, Mínimo y Máximo se alinean en un bloque de tres filas; únicamente los rótulos Mínimo y Máximo usan acentos discretos danger/success.
- La alerta de stock usa el texto explícito “N almacén/almacenes con stock bajo” en una etiqueta warning compacta.
- El inventario sin alertas muestra un check verde con fondo suave y el texto singular o plural “Stock saludable en N almacén/almacenes”.
- En la pestaña Inventario, cada almacén muestra una etiqueta sutil: “Stock bajo o en el mínimo” o “Inventario saludable”, usando la misma semántica warning/success del listado.
- Cuando el stock actual alcanza o queda por debajo del mínimo, únicamente los controles de stock adoptan fondo y contorno warning suaves. La fila mantiene fondo blanco para evitar una alerta visual excesiva; el badge conserva el contexto del problema.
- La nota general de inventario fue retirada para reducir ruido visual. Los encabezados `Stock inicial` o `Stock actual` y `Stock mínimo` incluyen ayudas contextuales reutilizables mediante `br-field-help`.
- El encabezado de inventario reutiliza `br-table-header-surface`, la misma regla mate aplicada al encabezado del listado, sin degradados alternativos ni bordes inferiores adicionales. `br-label-with-help` centra ópticamente texto e icono.
- Los tooltips de Marca e inventario usan la variante global compacta `br-tooltip`, con fondo secondary y aparición inmediata, sin animación.
- `CopyButton` conserva esa variante compacta al alternar entre `Copiar` y `Copiado`, porque recrea su instancia mediante la configuración centralizada de `Alerts.createTooltip`.
- Los switches `Publicar producto` y `Mostrar precio` usan verde success al seleccionarse, foco verde suave y estado neutral al desmarcarse o deshabilitarse, sin heredar el morado del tema base.
- En edición, el stock actual conserva su comportamiento de solo lectura y se presenta mediante un `span` neutral formateado con `separatorNumber`; no se renderiza como un control deshabilitado porque la cantidad se modifica desde Inventario, no desde Productos. En creación se conserva `InputNumber` para registrar el stock inicial.

## Publicación y venta asistida

- `see_my_web` controla si el producto puede salir en catálogo visible para clientes o PDF comercial.
- `see_my_web_price` solo tiene efecto cuando `see_my_web` está activo; permite ocultar precio sin retirar el producto del catálogo público.
- El selector de ítems de Nueva venta busca por nombre, código interno y código de barras. Esto permite usar lector de código de barras como entrada rápida: el lector escribe el código y el selector encuentra el ítem sin agregar otro campo visual.
- La impresión por lote es una ayuda operativa, no una mutación: no altera productos, stock ni movimientos de inventario.

## Carga masiva básica

Productos incorpora una acción compacta **Carga masiva** junto a Descargar Excel.

- La modal permite descargar la plantilla oficial.
- Descargar la plantilla muestra un loading global mientras se genera el archivo y lo cierra al finalizar.
- Columnas: `Nombre`, `Precio`, `Código interno`, `Código de barras`, `Descripción`, `Stock inicial` y `Stock mínimo`.
- `Nombre` y `Precio` son obligatorios.
- Código interno y código de barras son opcionales; si están vacíos se generan automáticamente.
- El usuario selecciona el almacén al que corresponden el stock inicial y mínimo.
- Los demás almacenes se crean con saldo y mínimo en cero.
- Los productos importados se crean activos, no publicados para clientes y con la moneda principal de la empresa.
- No se solicitan IDs, marca ni categorías para mantener el flujo comprensible.
- El archivo admite hasta 500 productos y 5 MB.
- La importación es transaccional: una fila inválida evita una carga parcial y muestra fila, campo y motivo.
- `ImportProductsRequest` valida archivo y pertenencia del almacén antes de iniciar la lectura; el controlador consume únicamente datos validados.
- Cada stock inicial mayor que cero genera su movimiento `product_opening`.
- Al terminar se invalida la referencia compartida de items, incluyendo la configuración de Inventario.
- El botón de carga masiva usa una acción ámbar sólida para diferenciarse visualmente de agregar, buscar y exportar.
- La modal usa espaciado lateral propio y consistente en escritorio y móvil.
- Las pestañas reutilizables mantienen una separación visual moderada entre opciones para facilitar el escaneo sin incrementar su altura.
- Las pestañas reutilizan `nav-pills`, tienen una separación superior más amplia respecto al encabezado y márgenes laterales alineados con los campos del formulario.
- La navegación ya no usa posición sticky ni desenfoque: forma parte del flujo normal del modal, evitando superposiciones y filtraciones de texto durante el desplazamiento.
- En escritorio, las pestañas reducen su separación y mantienen las tres etapas visibles; en móvil se presenta únicamente la etapa activa con su título y descripción completos, acompañada por controles anterior/siguiente accesibles y alineados al branding.
- Todas las etapas móviles conservan una altura fija independientemente de la longitud del título o la descripción; admiten hasta dos líneas por texto sin alterar la estructura. Las flechas son controles compactos y sutiles, centrados verticalmente, con énfasis azul únicamente en hover o foco.
- Los márgenes horizontales de la navegación y del contenido comparten la variable `--br-entity-modal-content-space-x`, ampliada para mejorar la respiración del formulario y ajustada de forma responsive.
- La separación superior entre el encabezado del modal y la navegación se controla mediante `--br-entity-modal-body-space-y`, incrementada para mejorar la jerarquía y respiración visual.
- La distancia entre navegación y campos se controla de manera independiente con `--br-entity-modal-tabs-content-space-y`; es más compacta que la separación superior y se reduce adicionalmente en móvil.
- Las pestañas mantienen una separación intermedia de `0.48rem`, suficiente para distinguir cada etapa sin fragmentar visualmente el flujo.
- En pantallas de hasta `767.98px`, `br-entity-modal` elimina el límite intermedio de Bootstrap y usa casi todo el ancho disponible, dejando únicamente `0.375rem` por lado.
- `AddCategory` y `AddBrand` reutilizan `QuickCreateCatalogEntity` y `QuickCreateTrigger`. El disparador admite modos `link`, `button` e `icon`, además de texto, icono, título, clases y estado deshabilitado parametrizables.
- El alta rápida solicita únicamente Nombre y Descripción. El código interno no se expone: se genera automáticamente con los prefijos `MAR-` o `CAT-`, y una nota informa que el registro se creará activo.
- Presionar Enter desde Nombre o Descripción ejecuta el alta. Al completarse, un SweetAlert success confirma que el registro quedó activo y disponible para seleccionarlo, sin alterar la selección existente.
- Mientras se registra una Marca o Categoría, el loader global bloquea el resto de acciones y el botón permanece deshabilitado para evitar solicitudes duplicadas.
- Los SweetAlert de validación y resultado usan la capa global máxima `br-swal-backdrop`, por encima de la modal rápida de Marca o Categoría y de la modal principal de Producto.
- Los errores frontend y backend usan mensajes breves sin repetir el nombre del campo. `Campo obligatorio.` es el estándar para obligatoriedad.
- Los inputs inválidos muestran un contorno rojo sobre el control completo, incluyendo moneda, contadores, botones anexos y `vue-select`; el texto de error usa una escala menor que el label.
- El modal rápido usa Bootstrap y se teletransporta a `body`. Su backdrop se identifica con `br-quick-create-backdrop`, conserva la modal de Producto debajo y devuelve el foco al contexto anterior al cerrarse.
- Los errores de validación se muestran dentro del modal rápido y bajo sus campos. SweetAlert se reserva para confirmar una creación exitosa.
- `br-branding.css` utiliza versionado por fecha de modificación en el layout System para evitar que el navegador conserve estilos anteriores durante las mejoras visuales.
- El breadcrumb global es compacto, se alinea a la derecha y resalta únicamente la ubicación actual con el azul de marca.
- El listado omite la columna Publicación; esa configuración se consulta y modifica dentro del formulario.
- Los generadores ocultan únicamente su propio tooltip mediante `Alerts.dismissTooltip()`; no destruyen las instancias de otros controles.
- “Descripción comercial adicional” se conserva en etiqueta, filtros, validaciones frontend y atributos de error del backend.
- Las respuestas del backend mantienen el mismo lenguaje: “Producto agregado exitosamente” y “Producto editado exitosamente”.
- Los estilos comunes del módulo se exponen mediante el namespace reutilizable `br-entity-*`; no se mantienen selectores `br-products-*`.

## Evolución

## Seeder comercial demo

Se agrego `database/seeders/CommercialCatalogSeeder.php` para cargar datos base sin depender de factories aleatorias.

Incluye:

- Marcas demo: Hola, Blapos y Wellness.
- Categorias demo: Bebidas, Suplementos, Accesorios, Servicios y Membresias.
- 30 productos con codigo interno, codigo de barras, precio, rango de precios, marca, categoria, `price_includes_tax` e `igv_exempt`.
- 10 servicios comerciales sin inventario.
- 10 membresias con duracion.
- Stock inicial por cada almacen activo de la empresa demo.
- Movimiento de inventario `initial_stock` para dejar trazabilidad del stock cargado por seeder.

El seeder es idempotente por `type + internal_code`: si se vuelve a ejecutar, actualiza los registros demo y sincroniza el stock demo por almacén.
## Estado y disponibilidad operativa

- El estado `active` permite que el producto, servicio o membresía participe en ventas, Venta POS, compras e inventario.
- El estado `inactive` conserva el registro en catálogo para consulta o edición, pero lo excluye de operaciones nuevas.
- Esta regla evita vender, comprar o mover inventario de ítems deshabilitados sin borrar información histórica.

## Actualizacion funcional: vencimiento comercial

- `items.expires_at` es opcional para productos. Al vencer, el backend inactiva el producto al listar catalogo o referencias comerciales y bloquea ventas con datos obsoletos.
- Productos no usa cupos comerciales; su disponibilidad se controla mediante `warehouse_items` e inventario/Kardex.
- El formulario de productos muestra fecha de vencimiento junto a datos y precio. Si no se informa, el producto no vence automaticamente.
