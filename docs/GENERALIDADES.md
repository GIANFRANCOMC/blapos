# Generalidades del Proyecto

Este archivo concentra criterios transversales que aplican a todos los módulos de Blapos. Sirve como memoria común para mantener coherencia funcional, visual y técnica entre `System`, `Guest`, backend, frontend, migraciones y documentación.

## Propósito

Blapos es una plataforma multiempresa orientada a operaciones comerciales y administrativas. La regla central es separar claramente:

- `System`: plataforma autenticada para empresas y colaboradores.
- `Guest`: superficies públicas para visitantes, clientes finales y recursos expuestos por empresa.

Toda implementación debe preguntarse primero si pertenece a la operación interna (`System`) o a la experiencia pública (`Guest`). No mezclar rutas, controladores, componentes ni documentación entre ambos dominios.

## Arquitectura Base

El flujo preferido en `System` es:

1. Ruta en `routes/System`.
2. Controlador en `app/Http/Controllers/System`.
3. FormRequest para validar entrada.
4. Servicio para reglas de negocio.
5. Modelo Eloquent para relaciones y persistencia.
6. ConfigService para `initParams` y referencias de pantalla.
7. Vista Blade como contenedor.
8. Entrada Vue y componentes reutilizables.
9. Documentación del módulo y tablas afectadas.

Evitar colocar reglas críticas sólo en Vue. El frontend guía al usuario; el backend decide y protege.

Las páginas Blade/Vue y sus datos JSON usan rutas GET distintas, con control de autenticación, permisos y tenant en el servidor. No usar parámetros ni cabeceras del navegador para alternar HTML y JSON en una misma URL; ver `System/ARCHITECTURE.md`.

## Multi-tenant por BD

Blapos opera exclusivamente desde subdominios registrados y con una base de datos por cliente. El dominio raíz pertenece a otro proyecto. El registro central mínimo vive en `landlord`; la operación del cliente se ejecuta en `tenant`. La guía completa está en `System/MULTITENANT.md`.

Cada base tenant representa exactamente una empresa. El aislamiento operativo depende de la conexión resuelta, no de repetir `company_id` en cada fila. La regla y sus cuatro excepciones estructurales están en `System/TENANT_COMPANY_BOUNDARY.md`.

La sesión también se aísla por tenant. `ResolveTenant` cambia el nombre de cookie antes de iniciar sesión y `EnsureTenantSession` invalida cualquier sesión que intente cruzar de un tenant a otro. Para producción, usar HTTPS, `SESSION_SECURE_COOKIE=true` y mantener `SESSION_DOMAIN` vacío. Ver `System/SECURITY_AND_AUTH.md`.
## Tenant y empresa raíz

Los datos operativos, hijos y maestros viven exclusivamente en la base tenant activa. Solo `branches`, `company_settings`, `company_socials_media` y `companies_sub_sections` conservan la relación estructural con `companies`.

Criterios esperados:

- No aceptar `company_id` desde frontend.
- Resolver la empresa raíz con `TenantCompanyContext`.
- Validar IDs con `ExistsInTenant` y unicidad con `UniqueInTenant`.
- Usar `UniqueInTenant` para unicidad lógica dentro del tenant.
- Evitar índices explícitos salvo decisión justificada; conservar claves primarias, foráneas y `unique` cuando expresen integridad real.

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

`public/System/assets/css/br-branding.css` es la fuente única de estilos visuales de la plataforma. Los colores de marca, superficies, bordes, estados, sombras, foco, compatibilidad de plantilla y estilos de módulos se organizan en parciales dentro de `resources/css/System/br-branding`.

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
| Etiqueta de formulario | `--br-font-size-label` | Usa el paso existente `--br-font-size-066` en las etiquetas de campos. |
| Ayuda o descripción breve | `--br-table-caption-font-size` | Contexto subordinado al dato principal; no reducirlo por pantalla. |
| Dato secundario | `--br-ui-font-size` | Desgloses comparables y valores secundarios. |
| Dato principal destacado | `--br-table-value-font-size` | Precios y métricas que requieren mayor jerarquía. |
| Encabezado de tabla | `--br-table-heading-font-size` | Cabeceras de listados y tablas compartidas. |
| Cantidad de stock | `--br-stock-quantity-font-size` | Stock actual, Diferencia, Variación y Saldo. |
| Títulos de página, diálogo y sección | `--br-table-value-font-size` | Los títulos destacados comparten este paso. Los títulos de modales compactas heredan el tamaño operativo. |

Los elementos del mismo rol comparten tamaño y peso. Al reutilizar un control, etiqueta o tabla, retirar del CSS de la pantalla las declaraciones de tipografía que ya aporta el estilo común. Los textos internos heredan el tamaño y el peso de su contenedor. Las ayudas y los datos destacados usan su alias de rol. Los códigos internos y de barras comparten los tamaños de etiquetas y valores de Control de stock.

Para ampliar toda la tipografía, cambiar `--br-font-scale` de `1` a `1.1` (10 % más). Los iconos en `em` siguen el tamaño de su texto. Para cambiar un rol, apuntar su alias en `00-tokens.css` a otro paso existente para todos sus consumidores.

Stock actual y Diferencia comparten `--br-stock-quantity-font-size` (`0.8rem` de base), conservando sus colores y peso. Los encabezados de tablas usan `--br-table-heading-font-size`; las etiquetas y valores de los códigos de inventario usan `--br-product-code-label-font-size` y `--br-product-code-font-size`. Usar `colon-at-end` en Cód. interno y Cód. barras en lugar de escribir dos puntos manuales.

Para mostrar una cantidad con unidad en System, reutilizar `QuantityValue`. El número hereda el tamaño de su contexto y la abreviatura registrada usa el paso compartido `--br-font-size-066`. **Saldo** y **Variación** comparten el rol de cantidad de stock. **Variación** mantiene el signo, el número y la unidad en una línea.

Reutilizar `ProductIdentity` al presentar un producto con su nombre e identificadores en el mismo bloque. Pasar el objeto con `name`, `internal_code` y `barcode`. La variante predeterminada `full` muestra los códigos desde el inicio. Control de stock y las demás vistas de Inventario usan `expandable`, que muestra el nombre con un botón «+» para abrir los códigos disponibles. El botón cambia a «−» para cerrarlos y admite foco y activación por teclado. Agregar futuras variantes en el mismo componente.

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

### Estado del stock

Usar los mismos tres estados en tablas, filtros, consultas por almacén, reportes Excel y PDF y correos de alerta. Las etiquetas se definen en `resources/stock-statuses.json` y se consumen desde `StockStatus.php` y `StockStatus.js`.

- **Agotado** cuando el stock actual es cero o negativo.
- **Stock bajo** cuando el stock actual es positivo y está en el mínimo o por debajo.
- **Stock saludable** cuando el stock actual es positivo y supera el mínimo.

En el resumen del catálogo, clasificar como **Agotado** si el total del producto es cero o negativo. Si el total es positivo y algún almacén tiene una alerta, mostrar **Stock bajo**. La etiqueta **Stock saludable** corresponde al total positivo con todos sus almacenes sin alertas.

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
- Ofrecer «Limpiar filtros» en barras con varios campos mediante `ClearFiltersButton`. El botón usa la escoba `fa-broom` de Font Awesome y comparte por defecto los colores de la cabecera de tabla mediante `br-filter-clear`. Se alinea al borde inferior y usa un tooltip de Bootstrap y una etiqueta accesible. Restablecer los campos juntos y conservar el contexto operativo, como el almacén. En filtros aplicados con Buscar, la limpieza solo modifica los campos pendientes. Evitar consultas o invalidaciones parciales de watchers durante el reinicio.
- Al terminar el restablecimiento, `Alerts.showFiltersCleared()` muestra el aviso informativo «Se limpiaron los filtros correctamente.» mediante Toastr. El botón oculta su tooltip al pulsarlo y lo vuelve a mostrar al pasar el cursor por encima.
- Los icon buttons requieren `aria-label` y tooltip.
- La integración compartida de SweetAlert oculta los tooltips de la página al abrir una alerta y durante el retorno de foco. Una vez cerrada, el tooltip vuelve a responder a una nueva interacción con su disparador. Los formularios y listados reutilizan este comportamiento en sus llamadas a `Swal.fire`.
- En desktop, botones secundarios de descarga pueden mostrarse sólo con icono si el tooltip es claro; en móvil deben mostrar icono y texto.
- Las tablas deben reutilizar la superficie global de encabezado y estados compactos.
- Encerrar cada tabla ancha en `table-responsive`. El contenedor toma el ancho disponible y la tabla desplaza sus columnas dentro de él. Menú lateral, filtros, acciones superiores y paginación quedan fuera del scroll. Los estados de carga y vacío se centran en el área visible. Los menús de fila usan posicionamiento fijo de Popper para abrir sin liberar el overflow de la tabla.
- Debajo de todas las tablas paginadas, incluidas las modales, usar `Paginator`, que integra el pie completo `br-list-footer`: navegación centrada y `ListResultCount` a la derecha. Pasar `:pagination` con el objeto paginado completo del servidor, `:links` y `:loading`; no añadir otro contador ni wrappers de centrado o margen. El contador muestra únicamente el total filtrado del servidor con singular o plural (por ejemplo, «Resultado: 1 registro» o «Resultado: 50 registros»), nunca el número de filas de la página ni el rango. Durante cualquier carga inicial, búsqueda, cambio de filtro o página, ocultar juntos navegación y contador hasta que termine la consulta. El componente compartido centraliza esta condición mediante `loading`; no mostrar cifras anteriores ni permitir navegación mientras se actualiza la tabla. Al terminar, reaparecen con el total actualizado, incluso con cero registros o una sola página. No añadir condiciones locales por `total > 0` o `links`, ni colocar el pie dentro del scroll horizontal/vertical. Mantener únicamente condiciones de contexto, como la vista activa o el almacén seleccionado. La regla aplica también a Guest y Platform, incluidos sus paginadores propios.
- La distancia entre tabla y pie se define una sola vez mediante `--br-list-pagination-gap` en `br-branding/00-tokens.css` (`0.75rem` de base). No sumar `mt-*`, márgenes propios del módulo o márgenes inferiores de tabla a esa separación. En móvil y contenedores estrechos, incluidas modales en pantallas grandes, el pie dispone navegación y contador en filas según su propio ancho para evitar superposición; el contador conserva alineación a la derecha. `DataTable` recibe el objeto completo mediante `pagination` cuando es paginada.
- El paginador compartido muestra `Primera`, `Anterior`, páginas próximas, `Siguiente` y `Última`. Sus botones son compactos y usan peso tipográfico normal; la página activa se distingue por el fondo, no por negrita. Los extremos no disponibles usan botones nativos deshabilitados. Cada módulo conserva sus propios colores y estilos de botones deshabilitados; no aplicar una neutralización global.
- Los estados vacíos y loaders usan `WithoutData` y `Loader`. Sus filas llevan `br-table-state` y quedan excluidas de fondos, bordes, sombras, cursor y transiciones de hover de los registros. Neutralizar las variables de tabla Bootstrap sin bloquear botones de reintento. Los skeletons usan filas de estado en blanco, sin spinner.
- Los tooltips que se descartan al abrir una modal se restauran globalmente al cerrarla desde `Alerts.js`; los módulos no deben repetir esta restauración para los disparadores de la página.
- Las modales conservan `modal-dialog-centered`. Apertura y cierre comparten duración y curva para el diálogo, la superficie y el fondo; respetar `prefers-reduced-motion`.
- Las pestañas de formularios comerciales usan `CatalogFormTabs`. Su variante `descriptive` admite `icon` y `description` por sección para formularios largos; activarla por módulo tras revisar el espacio y los textos. El icono se alinea a la izquierda del título y la descripción. Cada tab conserva un contorno tenue; el activo usa un borde azul claro, un icono en recuadro primary y una línea inferior fina, sin el borde grueso de la variante compacta. La variante compacta sigue siendo la predeterminada. Conservar `aria-selected`, navegación por teclado y desplazamiento horizontal accesible.
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

Las búsquedas remotas de selectores usan `Options.js` y `GET /options/{origin}/{entity}`. `entity` identifica el catálogo y `origin` identifica la pantalla que decide qué registros y campos devuelve. Registrar la combinación en `OptionController` y asignar el módulo en `config/permissions.php`. El controlador delega la consulta al servicio de dominio dentro del tenant activo y devuelve `records`, `page` y `has_more`. Usar el mismo cliente para Vue Select y Select2. Las listas fijas y las referencias que ya forman parte de `initParams` pueden resolverse localmente.

Todos los formularios deben reutilizar componentes y patrones existentes:

- `InputText` para texto.
- `InputNumber` para numéricos y dinero.
- `InputDate`, `InputMonth` e `InputDatetime` para fechas, meses y fecha con hora.
- `InputSelect` y `InputSelect2` para selección nativa y Select2.
- `InputSlot` cuando el input necesita prefijos, sufijos, acciones, moneda o contadores.
- `vue-select` con estilos globales y `SelectNoOptions` para mensajes vacíos en español.
- `CopyButton` para copiar valores puntuales.
- `BarcodeDownloadButton` para códigos de barras descargables.

Los controles compuestos se tratan como una sola unidad visual. El hover, el foco y los estados deshabilitado y de error se aplican al conjunto. En cualquier campo agrupado, los complementos informativos como prefijos, símbolos de moneda y unidades usan el azul de foco `--br-primary` mientras el campo tiene el foco. El valor editable, los fondos y los botones de acción conservan sus propios estilos.

System, Guest y Platform comparten las métricas de `03-form-controls.css`. Los campos de una línea usan `--br-control-height`, `--br-font-size-ui` y peso regular. El placeholder, la búsqueda y el valor seleccionado usan el mismo tamaño. Los textos e iconos se centran verticalmente y los valores arrancan con el mismo padding. Las áreas de texto y las selecciones múltiples pueden crecer según su contenido.

En los selectores, el fondo se pinta en la superficie redondeada del campo, como `.vs__dropdown-toggle` o `.select2-selection`. El contenedor externo queda transparente para que las esquinas muestren el fondo de la página.

Errores de campo:

- Bajo el campo, mostrar sólo el error: `Campo obligatorio.`.
- En resúmenes o modales, incluir el campo: `Nombre: Campo obligatorio.`.
- Los errores deben ser pequeños, legibles y no competir con el label.
- El borde rojo debe envolver todo el control compuesto.

### Convención de validaciones

- Los `FormRequest` deben parsear y normalizar IDs, montos, fechas opcionales y arrays antes de llegar al controlador.
- Las validaciones transversales viven en `CompanyFormRequest`; cuando una entidad requiera texto propio, debe mantener el mismo estilo corto y accionable.
- Bajo el campo se muestra solo el error, por ejemplo `Campo obligatorio.`. En resumen o modal se incluye el campo, por ejemplo `Nombre: Campo obligatorio.`.
- Pendiente controlado: revisar `resources/lang/es/validation.php` entidad por entidad para reemplazar textos residuales de Laravel/librerías y mantener errores visibles y consistentes en código.

## Precisión Numérica

Montos, cantidades operativas, costos, tributos, pagos, inventario y valorización usan la precisión de la empresa. Se configura en **Maestros internos → Configuraciones → Decimales de operación**. Las empresas nuevas empiezan con `2` decimales y pueden elegir un entero de `0` a `3`. El cambio afecta la presentación y las operaciones siguientes. Los datos históricos permanecen almacenados con su precisión original.

Configuración vigente por empresa:

- `numeric_validation.decimal_precision`: cantidad de decimales para validar, redondear y mostrar. Si falta la configuración o el valor no es entero, se usa el valor inicial. Los valores históricos fuera del rango se ajustan al extremo correspondiente al leerlos.
- `numeric_validation.default_min_value`: mínimo operativo por defecto.
- `numeric_validation.default_max_value`: máximo operativo por defecto para importes, cantidades, pagos y saldos.
- `numeric_validation.max_file_size_kb`: tamaño máximo configurable para archivos de formularios.

Reglas:

- En backend, `app/Helpers/System/Numbers.php` concentra la configuración, validación, normalización, redondeo, expresiones SQL y formatos de exportación. Los `FormRequest` delegan las reglas comunes a través de `CompanyFormRequest`. Las importaciones de catálogo normalizan las entradas con `Numbers::normalizeDecimalInput()` y las plantillas de inventario validan con `Numbers::spreadsheetDecimal()`.
- En frontend, `resources/js/Shared/Numbers.js` es el módulo compartido por System y Guest. Gestiona el formato, los límites y el análisis de los campos. Usar `roundNumber()` para cálculos, `fixedNumber()` para cadenas sin separador de miles y `separatorNumber()` para presentación.
- El mismo objeto de configuración se entrega al cargar las vistas y en `config.generalConfig.forms.inputs` de `initParams`. El valor `0` es válido y los empates se redondean alejándose de cero en ambos lenguajes. PHP usa aritmética decimal y JavaScript usa enteros escalados. En cálculos de importes, operar con `sum`, `subtract`, `multiply`, `divide` y `percentage` desde los valores originales para que una operación binaria previa no cambie el resultado en un empate.
- Los límites se comparan como decimales exactos. La validación convierte los números nativos de JSON a su representación decimal antes de aplicar las reglas de escala. Las plantillas de inventario hacen lo mismo con las celdas numéricas. Se rechaza el exceso de decimales donde el formulario o la plantilla aplica validación estricta.
- Para totales acumulados, usar `sumText()` y `subtractText()` cuando el resultado vaya a una vista, un reporte o una respuesta JSON. Así se conserva toda la precisión aunque el total supere el rango seguro de un valor binario. Si el total se calcula en SQL sobre columnas decimales, usar `Numbers::storage()` sobre el resultado. Cuando Excel pueda conservar todas las cifras, `Numbers::spreadsheetNumber()` permite una celda numérica. Los consolidados que exceden sus 15 cifras significativas se escriben como texto.
- En reportes con varias monedas, agrupar por moneda y conservar su signo o código. El saldo vencido de una cuenta corresponde a la suma pendiente de sus cuotas vencidas.
- Ventas, cotizaciones y compras usan `lineTotal()` para cuantizar cantidad y precio unitario antes de multiplicar y redondear el importe de la línea. `taxAmount()` y `documentTax()` concentran el cálculo de tributos en ambos lenguajes. El servicio de documentos determina qué tributos y exoneraciones corresponden a cada línea. `Numbers::commission()` aplica los mismos límites y fórmulas en ventas y operaciones de servicio.
- Las escrituras por lote de importes y cantidades usan `Numbers::storage()` o `Numbers::storageRow()` porque Laravel no aplica casts a `insert` o `upsert`. Los valores históricos se redondean al presentar el registro, con independencia de su estado. El límite de almacenamiento de `DECIMAL(15,3)` es `999999999999.999`.
- En tareas programadas, resolver el tenant antes de leer la configuración numérica. Los jobs reutilizan `UseTenantConnection`, que limpia el contexto al terminar. Los correos de alertas capturan los importes ya formateados mientras la empresa está activa.
- Los casts Eloquent usan `ConfigurableDecimal`. La escala técnica de las columnas se mantiene para soportar el máximo permitido. En cálculos con distribución, la suma de los importes redondeados debe coincidir con el total.
- Los conteos, identificadores, duraciones expresadas en minutos enteros y formatos técnicos de integraciones conservan su propia representación.

## Modales y SweetAlert

Las modales de System deben usar el patrón global:

- Backdrop estático.
- No cerrar al hacer clic fuera.
- No cerrar con Escape.
- Header, body y footer con espaciado estándar.
- Botón de cerrar visible y consistente.
- Footer con superficie neutral cuando aplique.
- Nuevas pantallas deben reutilizar `@System/Components/Generics/FormModal.vue` como base. El componente aplica `br-entity-modal`, `br-modal-standard` y `br-modal-shell`, bloquea backdrop/teclado y expone slots `header`, `body`, `footerClose` y `footer`.
- El footer del componente reserva el cierre/cancelación a la izquierda y las acciones de trabajo a la derecha. No duplicar estructuras `modal-header`, `modal-body` y `modal-footer` directamente salvo migraciones legacy.
- El header del componente muestra eyebrow opcional, título, subtítulo opcional y X centrada verticalmente; el body usa superficie neutral y el footer `#f7f8fa`.

SweetAlert debe usarse para confirmaciones, errores, success y procesos globales. No introducir delays artificiales con `setTimeout`; si hay trabajo asíncrono, mostrar loader inmediatamente.

Todos los SweetAlert de System y Guest llevan `allowEscapeKey: false`: pulsar Escape nunca debe descartarlos, ni siquiera cuando contienen formularios. Cerrar solo mediante la acción visible prevista (`Cancelar`, `X` o `Entendido`, según corresponda); los loaders se cierran desde el flujo que los inició. Aplicar esta regla también a llamadas directas a `Swal.fire`, no solo a los helpers compartidos.

### Confirmaciones SweetAlert

En todos los submódulos, activos o no, las confirmaciones siguen el mismo patrón. `Alerts.confirmAction` es la opción preferida para acciones simples; si una confirmación requiere inputs o `preConfirm`, conservar esa lógica y aplicar la misma convención. El estilo global reside en `resources/css/System/br-branding/95-swal-confirmations.css` y se genera en `public/System/assets/css/br-branding.css` con `npm run build:css:system`.

- No mostrar icono de question/warning ni centrar la cabecera. Título, descripción y campos se alinean a la izquierda. El color de peligro puede mantenerse en el botón de una acción destructiva, sin desplazar la jerarquía del texto.
- El título formula una pregunta breve y específica con verbo en infinitivo y sustantivo, entre `¿` y `?`: `¿Anular venta?`, `¿Duplicar perfil?`, `¿Cerrar sesión?`. No usar `¿Deseas…?`, `Confirmar acción` ni títulos vagos. El texto del botón mantiene la acción en afirmativo, sin signos.
- La cabecera y el pie usan el mismo gris muy tenue (`--br-neutral-panel`), mientras el cuerpo permanece blanco. Una línea separa cabecera y cuerpo; otra separa cuerpo y botones. Conservar esta estructura también cuando haya campos de entrada.
- El cuerpo no repite el título ni vuelve a preguntar. Explica consecuencias, alcance, datos afectados, irreversibilidad o pasos que el usuario no conoce. Mantenerlo breve y concreto; si hay campos obligatorios, indicar para qué se solicitan.
- Mostrar `Cancelar` a la izquierda y la acción principal a la derecha. El texto del botón repite la acción específica (`Anular venta`, no `Sí`, `Sí, anular` ni `Continuar`), con mayúscula solo al inicio salvo nombres propios. La acción debe ser visualmente preponderante.
- Mantener la `X` visible arriba a la derecha con `showCloseButton: true` y `closeButtonAriaLabel: "Cerrar confirmación"`. Debe tener un área de clic de al menos `2rem`, sin fondo, con contraste suficiente y separación del título. La X y `Cancelar` descartan la operación; nunca ejecutan el cambio. No cerrar haciendo clic fuera si se están revisando consecuencias o completando campos.
- Confirmaciones de descarga también siguen el patrón. Los mensajes de éxito, error y el loader no son decisiones de dos botones y conservan su presentación específica.

### Mensajes de estado SweetAlert

Los mensajes de éxito, advertencia, información, error y pregunta usan `Alerts.showMessage` (texto plano) o `Alerts.generateAlert` (detalle tabular) y la clase visual `br-swal-alert--message`. Este patrón se aplica a todos los mensajes de un solo botón, no a las confirmaciones ni al loader.

- Ancho predeterminado compacto de `25rem`; si el contenido requiere más espacio, pasar `width` en píxeles (por ejemplo, `width: 520`). El modal nunca debe exceder el viewport. No ampliar todos los mensajes por una excepción.
- El texto visible, esté en `title` o en el cuerpo HTML, usa la misma escala tipográfica que el cuerpo de una confirmación mediante `--br-table-value-font-size`. Mantener esa equivalencia para éxito, advertencia, información, error y pregunta. La jerarquía se expresa con peso y espaciado.
- Icono circular compacto, centrado y proporcionado al símbolo, con color semántico: verde para éxito, ámbar para advertencia, azul para información o pregunta y rojo para error. El éxito usa una marca SVG completa; no mezclar el círculo reducido con los trazos internos nativos de SweetAlert. Usar el icono común de `br-swal-alert--message`.
- La entrada del icono es breve y sin saltos de layout; en éxito, la marca se dibuja tras aparecer el círculo. Desactivar toda esa animación con `prefers-reduced-motion: reduce`, dejando el icono completo visible de inmediato.
- Título o mensaje breve y concreto; el cuerpo añade información útil y no repite el título. Mostrar una única respuesta principal (`Entendido`) y evitar duplicar el mismo resultado con un aviso en la página o un toastr.
- Las confirmaciones conservan el patrón de dos botones descrito arriba, sin icono; el loader usa el `fa-circle-notch` compartido y no los iconos de estado.

## Cache e initParams

Los `ConfigService` preparan referencias para pantallas Vue. Deben extender `BaseConfigService` y construir datos en `buildConfig(int $companyId, string $page, ?int $userId = null)`.

- `getInitParams` siempre recibe empresa, página y usuario explícitos.
- Un servicio que incluya sucursales, cajas o almacenes filtrables declara `USER_SCOPED_CACHE = true`.
- `clearCache($companyId)` elimina todas las variantes de usuario cuando la caché está segmentada.

Reglas:

- Cache por empresa y página.
- Invalidar por recurso usando `InitParamsCacheInvalidationService`.
- No usar `Cache::flush()` para resolver dependencias funcionales.
- Si un catálogo alimenta varios módulos, invalidar todos los consumidores registrados.
- Para maestros tenant, usar servicios como `MasterReferenceDataService` con claves de caché aisladas por `TenantContext::cacheNamespace()`.

## Inventario y Trazabilidad

Las operaciones que afecten stock deben registrar movimiento. No actualizar directamente `warehouse_items.quantity`, `average_cost` o `inventory_value` desde módulos externos.

Criterios:

- Entradas, salidas, correcciones, traslados, compras, ventas, devoluciones y reposiciones deben pasar por servicio de inventario.
- El costo unitario pertenece a la trazabilidad y valorización, no al precio de venta.
- La anulación de venta no siempre repone stock; depende de `company_settings.inventory.restore_stock_on_sale_cancellation`.
- El bloqueo de stock negativo depende de configuración por empresa.
- Cuando existan varios almacenes por sucursal, la operación debe exigir almacén claro.

## Ventas, POS, Compras e Impuestos

Los impuestos son configurables por empresa, módulo y obligatoriedad. En frontend no usar el texto genérico `impuestos` cuando existe nombre fiscal como `IGV` o `ICBP`.

Criterios:

- `IGV` debe representar el impuesto principal cuando aplique.
- Los taxes pueden ser porcentaje o monto fijo.
- Un impuesto puede ser obligatorio u opcional.
- Los impuestos opcionales no porcentuales pueden requerir cantidad de aplicaciones.
- `items.igv_exempt` exonera solo IGV y tiene prioridad sobre `items.price_includes_tax`; si está activo, la UI debe desmarcar y deshabilitar `Incluye IGV`.
- Ventas normales y POS deben compartir cálculo, trazabilidad de pagos, impuestos aplicados y generación de cabecera/detalle.
- Los métodos de pago deben guardar monto por método y sumar el total del documento.

## Migraciones

Durante la etapa reiniciable del proyecto, se puede refactorizar migraciones base. Criterios actuales:

- Separar estructura y datos iniciales.
- Crear una migración dedicada para inserts iniciales.
- Separar por dominio: maestros, empresas, catálogo, inventario, ventas, compras, caja, biometría y reportes.
- Usar `decimal(15, 3)` como estándar para cantidades y montos cuando se requieran hasta 12 enteros.
- Limitar `string` con tamaño explícito, máximo recomendado `500`; usar `text` o `longText` si corresponde.
- No agregar `company_id` a tablas operativas. Una extensión directa de `companies` requiere justificación arquitectónica y una prueba de límite actualizada.
- Evitar comentarios decorativos, símbolos extraños o encoding roto.
- Tabular y espaciar consistentemente.

## Documentación

Cada cambio debe actualizar documentación afectada:

- Archivo del módulo en `docs/System/modules` o `docs/Guest/modules`.
- `TABLES.md` si cambian tablas, campos o relaciones.
- `ARCHITECTURE.md` si cambia arquitectura.
- `DEVELOPMENT_GUIDE.md` si cambia una práctica de desarrollo.
- `new_requirements` para decisiones de evolución; una mejora implementada se integra al módulo correspondiente.

Formato recomendado por módulo:

1. Propósito.
2. Alcance actual.
3. Flujo funcional.
4. Archivos técnicos relacionados.
5. Tablas y relaciones.
6. Reglas de negocio.
7. Validaciones.
8. Contrato HTTP disponible para que la interfaz no replique reglas de negocio.
9. Integraciones con otros módulos.
10. Integraciones, seguridad y trazabilidad.

## Criterios Transversales Vigentes

Las reglas visuales transversales se administran en este archivo. Las mejoras puntuales de una pantalla se documentan en el módulo correspondiente al implementarse.

- Las migraciones crean esquema y transformaciones históricas necesarias; catálogos vigentes y defaults organizacionales se sincronizan mediante servicios idempotentes.
- Los servicios de escritura, configuración y referencias reciben `companyId` y `userId` explícitos. Los observers de auditoría pueden obtener el actor desde el request de frontera, sin consultar `Auth` dentro del dominio.
- Las pruebas automatizadas se incorporan únicamente cuando el usuario las solicite; no deben crearse de forma implícita.
- Usar `php artisan system:install` para una base vacía, `system:sync` para el catálogo, `company:enable` para defaults y `system:doctor` para validar integridad.
- Mantener sincronizados los nuevos endpoints con `config/permissions.php` cuando compartan un prefijo entre varias páginas.
- Los reportes deben reutilizar consultas filtradas, declarar límites por empresa y rechazar volúmenes excesivos antes de materializar colecciones.
- Los reportes compartibles fuera de sesión deben usar rutas firmadas y con expiración. No compartir rutas basadas solo en ids o parámetros codificados.
- En columnas `datetime` o `timestamp`, no usar `whereDate` para filtros de día; usar rangos `>= startOfDay` y `<= endOfDay` mediante `Utilities::startOfDay()` y `Utilities::endOfDay()` para conservar consultas indexables.
- En columnas `date` puras, usar comparación directa (`>=`, `<=`) sin envolver la columna en funciones SQL.
- Stock, caja, ventas, compras, perfiles, configuración, reclamos, asistencia y activos conservan trazabilidad específica o auditoría empresarial.
- Todo accessor incluido en `$appends` debe tolerar modelos con selección parcial; leer atributos mediante `??` y devolver un valor neutral evita errores `Undefined array key` en listados optimizados.
