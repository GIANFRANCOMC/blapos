# System - Guía de desarrollo

## Generalidades transversales

Las reglas compartidas de branding, formularios, modales, SweetAlert, cache, migraciones, multiempresa, impuestos, inventario y documentación viven en [../GENERALIDADES.md](../GENERALIDADES.md). Esta guía mantiene el criterio de desarrollo System; si una regla aplica a todos los módulos o también a Guest, documentarla primero en `GENERALIDADES.md`.

## Principios

- Respetar `Controller -> Service -> Model`.
- Mantener `System` aislado de `Guest`.
- Filtrar por empresa en toda operación interna.
- Preferir cambios pequeños, verificables y compatibles con datos actuales.
- No hacer refactors globales si el requerimiento solo toca un módulo.
- Mantener nombres técnicos en inglés y textos de UI en español, con tildes y signos de apertura correctos.
- Aplicar la misma estructura de rutas, controladores, requests, servicios, Vue, CSS y documentación en módulos equivalentes.
- Los entries Vue de módulos CRUD deben usar `mountEntityApp(App)` desde `resources/js/System/Helpers/MountEntityApp.js` para registrar componentes compartidos como `Breadcrumb`, `InputText`, `Paginator`, `Loader`, `WithoutData`, `FiltersSection`, `StatusBadge`, `CopyButton` y `vue-select` sin repetir imports por módulo.
- Las pantallas nuevas o migradas no deben depender de JavaScript de plantilla ubicado en `public/System/assets/js/app-*`. Esos archivos permanecen como legado visual del template y cualquier comportamiento operativo debe vivir en Vue, helpers `System` o componentes reutilizables.

## Al modificar un módulo CRUD

Revisar siempre:

- Ruta en `routes/System`.
- Controlador.
- Servicio principal.
- Servicio de configuración.
- FormRequests de store/update.
- Modelo y relaciones.
- Migración base correspondiente si hay cambios de esquema.
- Vista Blade si cambia entrada Vite.
- Pagina Vue y componentes usados.
- Traducciones y mensajes si aplica.
- Caché de `initParams` si el cambio afecta selects o configuración.

## Política de migraciones durante la etapa reiniciable

- Mientras el proyecto permita ejecutar `migrate:fresh`, modificar directamente la migración base propietaria de la tabla.
- No crear migraciones incrementales para añadir, retirar o alterar campos, relaciones, restricciones o índices de tablas existentes.
- Las tablas nuevas relacionadas con un módulo existente deben incorporarse en la migración base del dominio y respetar el orden de sus claves foráneas.
- Registrar el menú en las tablas `menu_categories`, `sections`, `menu_groups` y `sub_sections`. Para instalaciones nuevas, consolidar el catálogo inicial en `SystemNavigationSeeder`.
- Los defaults por organización pertenecen a `CompanyProvisioningService`, no a migraciones ni seeders.
- Solo adoptar migraciones incrementales cuando el proyecto entre en una etapa con datos persistentes que no puedan reiniciarse, o cuando se solicite explícitamente.

## Caché de `initParams` y dependencias

- Todo `*ConfigService` debe extender `BaseConfigService`; no implementar `Cache::remember`, claves o TTL en cada módulo.
- La configuración se construye en `buildConfig(int $companyId, string $page, ?int $userId = null)` y se devuelve como `stdClass`.
- Declarar `cachePages()` solamente cuando el módulo tenga más de una página de configuración.
- Una mutación no debe limpiar únicamente la caché de su propio módulo cuando el recurso alimenta selects de otros módulos.
- Usar `InitParamsCacheInvalidationService::invalidate($resource, $companyId)` después de completar correctamente la transacción.
- No invalidar caché por crear ventas, cambiar stock, cancelar asistencias o actualizar asignaciones si esos registros no forman parte de `initParams`.
- La invalidación siempre es por empresa y por recurso. No usar `Cache::flush()` para resolver dependencias funcionales.
- Los servicios con `USER_SCOPED_CACHE=true` registran automáticamente los usuarios que generaron caché. La invalidación no debe consultar todos los usuarios de la organización.
- La matriz central vive en `app/Services/System/Base/InitParamsCacheInvalidationService.php`.
- Al añadir un nuevo `ConfigService` que consuma datos compartidos, registrarlo en la dependencia correspondiente y validar su contrato de caché.

Dependencias registradas:

- `brands`: Marcas y Productos.
- `categories`: Categorías, Productos, Servicios y Membresías.
- `items`: Productos, Servicios, Membresías y Ventas.
- `customers`: Clientes, Ventas y seguimientos de asistencia, clientes y membresías.
- `branches`: Sucursales, Productos, Ventas, seguimientos, dispositivos biométricos, asignación de activos y gestión de stock.
- `assets`: Activos y asignación de activos.
- `users`: Usuarios y asignación de activos.
- `roles`: Roles y Colaboradores.
- `biometric_devices`: Dispositivos biométricos y Clientes.

## Consultas compartidas de `initParams`

- No crear ni consumir `Model::getAll($type, $companyId)`.
- Para datos dependientes de empresa y alcance operativo, crear una sola instancia con `CompanyReferenceDataService::for($companyId, $userId)` y usar métodos con intención explícita.
- Reutilizar esa instancia durante todo `buildConfig`; memoriza usuario y alcances para no repetir consultas por sucursal, caja y almacén.
- Los módulos documentales que afecten inventario deben separar documento y ejecución física. Compras registra intención comercial; la recepción es el evento que mueve stock.
- Ningún módulo actualiza `warehouse_items.quantity`, `average_cost` o `inventory_value` directamente. Debe invocar `InventoryMovementService`.
- Las operaciones con cabecera, detalles y movimientos se ejecutan dentro de una única transacción.
- La valorización usa costos de entrada y promedio ponderado por almacén; nunca reutiliza el precio de venta.
- Para monedas y tipos de documento configurables por empresa, usar `MasterReferenceDataService` pasando siempre `companyId`.
- El mantenimiento de maestros debe invalidar `MasterReferenceDataService` y los `ConfigService` registrados en `InitParamsCacheInvalidationService`.
- Si una pantalla necesita una variante nueva, agregar un método descriptivo al servicio adecuado; no introducir strings como `"default"`, `"sale"` o `"management"` para modificar consultas.
- Mantener en el método explícito los filtros de estado, orden y relaciones precargadas que necesita el consumidor.
- Registrar el `ConfigService` consumidor en `InitParamsCacheInvalidationService` cuando la nueva referencia pueda cambiar por una mutación.

Ejemplo:

```php
$references = CompanyReferenceDataService::for($companyId, $userId);

$config->brands->records = $references->brands();
$config->categories->records = $references->categories();
$config->warehouses->records = $references->stockWarehouses();
$config->currencies->records = MasterReferenceDataService::currencies($companyId);
```

## Secciones y menú

- La fuente canónica son las tablas `menu_categories`, `sections`, `menu_groups` y `sub_sections`.
- `SystemNavigationSeeder` solo inicializa una base vacía; `SystemCatalogSyncService` no redefine el catálogo, sino que lo proyecta a organizaciones y perfiles de acceso total.
- `menu_categories` define categorías y `menu_groups` añade agrupaciones visuales sin alterar permisos.
- Obtener módulos habilitados mediante `CompanySectionService::getSections($companyId)`.
- Obtener módulos visibles para un colaborador mediante `CompanySectionService::getSections($companyId, $roleId)`.
- Las rutas internas usan `module.permission`; todo módulo nuevo debe registrar `sub_sections.dom_route` y mapear endpoints compartidos en `config/permissions.php`.
- `role_sub_sections.actions` implementa permisos por módulo + acción sin romper registros anteriores: `null` conserva todas las acciones.
- Las acciones estándar son `view`, `create`, `update`, `delete`, `export`, `import` y `operate`.
- Las rutas que reciben sucursal, caja o almacén usan también `resource.scope`; los listados e `initParams` deben aplicar el mismo alcance mediante `CompanyReferenceDataService`.
- El alcance del colaborador solo puede heredar o reducir el del perfil. Nunca debe ampliarlo desde frontend ni backend.
- No leer ni escribir manualmente la clave de caché desde controladores, listeners o Blade.
- Las mutaciones de `CompanySubSection` invalidan el menú mediante `CompanySubSectionObserver`.
- Las mutaciones de `Role` o `RoleSubSection` invalidan menú por rol y permisos mediante sus observers.
- Si se incorpora administración de `sections` o `sub_sections`, debe invalidarse el menú de las empresas impactadas.

## Interfaz y experiencia de usuario

- Mantener una interfaz seria, minimalista y coherente con el propósito operativo de System.
- Reutilizar los tokens `--br-*` y colocar los estilos del sistema en el parcial correspondiente de `resources/css/System/br-branding`.
- Evitar colores aislados que no pertenezcan al branding vigente.
- Usar iconos conocidos para acciones compactas y texto visible para comandos que puedan ser ambiguos.
- Los `form-label` deben usar la clase global sin `fs-6`: tamaño compacto, peso medio y color secundario suavizado. Evitar labels grandes o excesivamente oscuros en formularios operativos.
- Todo campo obligatorio debe mostrar un asterisco al final del label mediante `platform-required`; esta señal visual complementa, pero nunca reemplaza, la validación del frontend y del backend.
- Las altas rápidas relacionadas a un selector no deben modificar la selección actual salvo que el flujo lo solicite expresamente. Deben refrescar las opciones y confirmar el resultado.
- Si un código técnico puede generarse de forma segura, ocultarlo al usuario y explicar brevemente que se creará automáticamente con el estado inicial correspondiente.

### Identidad visual Blapos

- El logomark maestro de la aplicación es `public/System/assets/img/utils/owner_app/logomark.png` y la identidad se denomina **Blapos**.
- La administración central (`app.*`) y las interfaces tenant deben reutilizar este archivo para el logomark global, la miniatura de aplicación, el avatar de producto y los iconos del documento HTML. No deben crearse iniciales, isotipos alternativos ni copias locales por módulo.
- La identidad propia de una empresa tenant puede mostrarse en los espacios reservados para esa organización, sin sustituir el logomark maestro en los controles globales del producto.
- Las altas rápidas que persisten datos deben bloquear temporalmente la interacción mediante el loader global; el botón también permanece deshabilitado para impedir envíos duplicados.
- Los mensajes bajo un campo no repiten su label. Usar textos breves como `Campo obligatorio.` y normalizar respuestas HTTP mediante `Forms.handleFormErrors`.
- Los componentes compartidos aplican `br-form-control-group` y `br-form-error`: el estado inválido bordea como una sola unidad el input, sus addons, botones, contadores y selectores.
- Hover, foco y error deben afectar el control compuesto completo. No resaltar únicamente el elemento editable cuando forma parte de un `input-group`.
- Todo botón que muestre únicamente un icono debe incluir `aria-label` y tooltip descriptivo.
- Usar `br-table-header-surface` cuando una grilla o matriz interna deba compartir exactamente la superficie mate, contraste y acento de los encabezados de tablas.
- Usar `br-label-with-help` junto con `br-field-help` para alinear títulos e iconos de ayuda sin introducir alertas de bloque.
- Los atributos secundarios relevantes de un registro pueden mostrarse con `br-entity-table__attribute`: icono contextual con tooltip y valor compacto, sin crear columnas innecesarias.
- Inicializar los tooltips con el helper compartido `Alerts.tooltips({})` después de renderizados o actualizados los controles dinámicos.
- El tooltip debe explicar la acción que ocurrirá, por ejemplo: `Agregar a favoritos` o `Quitar de favoritos`.
- Los tooltips usan globalmente `br-tooltip`: superficie secondary oscura, tipografía compacta y sombra ligera, sin animación ni retraso. No crear variantes grandes por módulo.
- Los estados `disabled` y `readonly` de `form-control`, `form-select`, addons, acciones de `input-group` y `vue-select` reutilizan la superficie neutral de `br-branding.css`; deben conservar contraste, opacidad completa, cursor no permitido y cambio visual inmediato.
- Los `vue-select` múltiples reutilizan chips secondary definidos en `br-branding.css`; no crear estilos locales por módulo para sus valores, separación o controles de eliminación.
- El layout versiona `br-branding.css` con `filemtime`; los cambios visuales globales invalidan la caché del navegador sin agregar versiones manuales.
- Mantener estados de foco visibles, áreas de interacción suficientes y navegación por teclado.
- Los formularios deben reutilizar el patrón global de `br-branding.css`: `form-control`, `form-select`, `vue-select`, `select2`, prefijos, contadores y acciones dentro de `input-group` comparten hover/foco con `--br-primary`, borde unificado y sin divisores internos innecesarios.
- Los botones y addons integrados en `input-group` deben usar altura flexible (`align-self: stretch`) y no alturas fijas, para conservar la alineación vertical con cualquier variante de input.
- Los addons anteriores o posteriores compactan únicamente el padding del campo en el borde compartido. El valor queda próximo al addon sin perder el espacio exterior normal del control.
- Los símbolos monetarios usan el elemento interno reutilizable `br-currency-prefix__symbol`: color `--br-secondary`, peso medio y escala independiente de `.input-group-text`, sin competir visualmente con el importe.
- Los códigos internos configurables muestran su prefijo con `br-internal-code-prefix`. El usuario edita la parte variable y `InternalCodeService` compone el valor definitivo.
- `br-internal-code-prefix` debe integrarse al control como moneda, contador o acción: sin divisor interno, foco compartido, jerarquía tipográfica compacta y borde exterior único.
- Los disparadores reutilizables `br-quick-create-trigger` mantienen apariencia de acción compacta sin subrayado en reposo, hover o foco.
- Los listados reutilizan `Loader.vue` y las clases globales `br-loader*`; el indicador orbital combina primary y secondary sin crear spinners o colores locales por módulo.
- `Loader` y `WithoutData` comparten `br-feedback-state`: misma jerarquía de título, descripción, espaciado y alineación. El estado vacío usa un SVG inline basado en primary/secondary y no depende de ilustraciones externas.
- Los prefijos integrados usan `letter-spacing: 0.04em`; la unidad relativa conserva legibilidad al cambiar la escala del formulario.
- El loader global de SweetAlert no usa bordes decorativos ni órbitas. Mantiene el logomark centrado con respiración sutil y pulsos primary/secondary como único indicador de actividad. Su HTML vive en `Alerts.js` y toda la presentación reutilizable en `br-branding.css`.
- `Alerts.swals()` acepta `type`, `entity` y `title`. Preferir acciones semánticas como `{type: "create", entity: "producto"}` o `{type: "update", entity: "producto"}`; usar `title` únicamente cuando el proceso requiera un texto específico.
- Para exportes filtrados, reutilizar la consulta del listado y añadir únicamente la estrategia de salida. `FiltersSection` expone `showDownloadButton` desactivado por defecto, `downloadButtonText`, `downloading` y el evento `download`; cada módulo decide si habilita la capacidad mediante su configuración.
- `FiltersSection` puede ocultar el input de búsqueda con `showSearchInput`, ocultar el botón Buscar con `showSearchButton`, personalizar el botón de descarga/consulta con `downloadButtonIcon` y `downloadButtonClass`, y recibir filtros avanzados mediante el slot `extraFilters`. Usarlo para reportes o barras con parámetros dinámicos antes de crear una barra local.
- `FiltersSection.downloadIconOnlyOnDesktop` permite mostrar únicamente el icono del archivo desde `992px`, conservando texto completo en móvil. Siempre debe acompañarse de tooltip y `aria-label`.
- Las descargas autenticadas de archivos deben usar `Requests.download()`: centraliza `responseType: "blob"`, nombre de archivo, liberación de URL temporal y lectura de errores JSON devueltos como blob.
- Los códigos de barras imprimibles deben reutilizar `BarcodeDownloadButton`. El componente usa `JsBarcode`, genera en frontend un PNG transparente con barras y numeración, recibe valor, nombre de archivo y formato, y comunica el resultado en su propio tooltip.
- Los `vue-select` nuevos deben implementar el slot `no-options` con `SelectNoOptions`; evita mensajes internos en inglés y mantiene una respuesta vacía homogénea y accesible.
- Los textos truncables deben usar el tooltip global de Bootstrap mediante `data-bs-toggle="tooltip"` y conservar el contenido completo en `title`.
- El espacio entre logomark, título y mensaje debe ser compacto; el texto secundario utiliza una indicación breve y amable: `Espera un momento, por favor.`
- Modales y SweetAlert deben responder inmediatamente. No introducir `setTimeout` para coordinar overlays: usar el helper `Alerts.modals`, que abre de inmediato o en el siguiente frame cuando recibe `timeout`.
- Las modales usan una transición global breve de `80ms`; SweetAlert no utiliza animaciones de entrada o salida para evitar esperas artificiales.
- SweetAlert conserva un backdrop navy semitransparente inmediato. Al desactivar animaciones no se debe retirar la clase de backdrop, porque también controla la opacidad del fondo.
- `br-swal-top-layer` y `br-swal-backdrop` deben permanecer por encima de modales anidadas. No asignar z-index locales a una alerta concreta.
- Ejecutar validaciones frontend antes de `Alerts.swals({})`; el loader global se muestra únicamente al comenzar trabajo asíncrono o una petición HTTP.
- `Alerts.generateAlert()` devuelve la promesa de SweetAlert. Cuando una acción posterior cambie pestañas, enfoque o scroll, esperar su cierre con `await` para impedir movimientos detrás del aviso.
- Las secciones `br-entity-form-section` reutilizan una transición de entrada de `120ms`; no añadir `setTimeout` ni animaciones de altura para navegar entre pestañas.
- Los módulos con código interno deben declarar `internalCodeEntity`, cargar `internal_code_prefixes` desde su `ConfigService` y reutilizar `InternalCodePrefixMixin`; no calcular prefijos dentro de cada página Vue.
- Los FormRequests equivalentes deben extender `CompanyFormRequest` y reutilizar `AppliesInternalCodePrefix`. La interfaz es una ayuda visual; el backend siempre normaliza el valor.
- Las configuraciones particulares de empresa deben almacenarse en `company_settings`; no crear una tabla específica para cada bandera cuando pertenece al mismo dominio de configuración.
- Los errores bajo el campo deben ser breves. Los resúmenes deben recuperar el contexto mediante `Forms.getDescriptiveErrors`.
- Las altas contextuales usan una modal Bootstrap teletransportada al `body`; su backdrop debe identificarse de forma explícita y SweetAlert debe usar `br-swal-top-layer` durante operaciones persistentes.
- `InputText` presenta sus límites mediante `br-character-counter`: fondo blanco integrado, ancho compacto y contenido tipográfico secundario; el estado cercano al límite cambia únicamente a warning.
- Los indicadores de `vue-select` muestran únicamente una flecha compacta: sin cápsula, borde ni fondo; conservan un lienzo SVG suficiente para no recortarse al rotar y usan secondary en reposo y primary al interactuar.
- Los `vue-select` ubicados dentro de modales deben usar `append-to-body`. Su menú reutiliza la capa global `body > .vs__dropdown-menu`, superior a Bootstrap, para no alterar el scroll del modal.
- Los `vue-select` simples mantienen una sola línea mediante elipsis. Cuando el texto seleccionado sea largo, debe exponerse completo con `title` sobre `br-select-selected-text`, sin modificar la altura del formulario.
- Filtros y modales deben compartir el mismo contrato de `vue-select`: `append-to-body`, altura estable, opciones sin scroll horizontal, elipsis mediante `br-select-option-text` y contenido completo disponible con `title`.
- Las modales CRUD reutilizan `br-entity-modal` y sus variables de espaciado/superficie; ajustar `--br-entity-modal-body-space-y`, `--br-entity-modal-content-space-x` o `--br-entity-modal-footer-bg` antes de crear variantes específicas por módulo.
- Toda modal System se prepara mediante `Alerts.modals` o el listener global de `Alerts.js`: backdrop `static`, teclado deshabilitado y clase `br-modal-standard`. No debe cerrarse al hacer clic fuera ni al presionar Escape.
- Los bodies sin navegación por pestañas reutilizan `br-modal-standard__body`; no crear padding horizontal por módulo. Header, body y footer comparten `--br-modal-space-x`, con su ajuste responsive.
- `Alerts.generateAlert()` usa un popup compacto y clases semánticas por tipo: borde e icono comparten color; error/success confirman con primary, warning con danger y question con secondary.
- Los accessors incluidos en `$appends` deben tolerar columnas ausentes mediante `?? null`. Si dependen de una relación, comprobar `relationLoaded()` y no disparar lazy loading durante la serialización de consultas parciales.
- Anular un documento comercial y devolver inventario son acciones distintas. Las reposiciones automáticas deben depender de una política tipada en `company_settings`; cuando existan varios almacenes, deben recuperar el almacén del movimiento original y no asumir el primero de la sucursal.
- El paginador compartido debe mostrarse incluso con una sola página: `Anterior` y `Siguiente` quedan deshabilitados, y la página actual permanece visible con contraste alto.
- Revisar que textos, títulos, confirmaciones y mensajes respeten tildes, puntuación y signos de interrogación.
- Centralizar títulos, subtítulos, filtros, estados vacíos, tooltips y confirmaciones en `config.entity.page` cuando la página use la estructura de configuración por entidad.
- Evitar cadenas funcionales repetidas directamente en templates Vue; la vista debe consumir la configuración declarativa.
- Para navegación compartida entre módulos, ubicar la interfaz en el layout y comunicar actualizaciones mediante eventos con nombres bajo el prefijo `br:`.

## Al agregar campos

Pasos recomendados:

1. Mientras el proyecto siga en fase reiniciable, modificar la migración base propietaria; crear una migración incremental únicamente cuando existan datos persistentes que deban conservarse.
2. Actualizar `$fillable` o asignaciones controladas en servicio/modelo.
3. Actualizar casts si es fecha, boolean, decimal o json.
4. Actualizar StoreRequest y UpdateRequest.
5. Actualizar formulario Vue.
6. Actualizar listado/detalle si el campo debe verse.
7. Agregar prueba o lista de verificación manual.
8. Actualizar documentación del módulo y `TABLES.md`.

## Al modificar reglas de negocio

- Identificar efectos en otros módulos.
- Buscar transacciones existentes.
- Evitar duplicar reglas en Vue; Vue puede validar UX, pero backend decide.
- Mantener mensajes claros.
- Revisar cancelaciones, estados y auditoría.
- Documentar en `new_requirements` si la mejora todavía no se implementa.

## Clean code pragmático

Se busca mejorar sin romper el sistema:

- Extraer métodos cuando una regla se repite o se vuelve difícil de probar.
- No crear abstracciones genéricas si solo se usan una vez.
- Usar nombres explícitos antes que comentarios largos.
- Mantener comentarios solo para reglas no obvias.
- No mezclar query compleja, transformacion y respuesta en el mismo bloque si se puede separar.

## Seguridad minima esperada

- Validar pertenencia a empresa/sucursal.
- Usar `FormRequest` en mutaciones.
- Extender `CompanyFormRequest` en nuevos CRUD propiedad de una empresa.
- Usar `ExistsInTenant` para IDs directos y validar el alcance funcional en el servicio.
- Normalizar strings mediante `normalizedStringFields()` y no repetir `trim()` en controladores.
- Reforzar relaciones estructurales con claves foráneas.
- Mantener las reglas comerciales de unicidad en backend mediante `UniqueInTenant` y respaldarlas con restricciones únicas cuando la integridad también deba garantizarse ante concurrencia.
- Añadir una comprobación de servicio cuando la entidad pueda modificarse fuera del `FormRequest` del endpoint.
- No exponer datos de otra empresa en listados o initParams.
- No aceptar `company_id` enviado desde frontend.
- Resolver entidades mutables dentro de la conexión tenant activa y comprobar su alcance funcional antes de escribir.
- Cuando una ruta contiene el ID del recurso, ese ID es la referencia autoritativa. No sustituirlo por IDs enviados en el cuerpo para decidir qué registro actualizar.
- Aplicar el alcance operativo después de comprobar pertenencia empresarial y antes de ejecutar el servicio de escritura.
- Registrar al usuario que crea, actualiza, cancela o elimina.

## Verificación recomendada

Para cada cambio:

- Probar crear/editar/listar si es CRUD.
- Probar estados límite: activo, inactivo y cancelado.
- Probar empresa/sucursal incorrecta si el endpoint recibe ids.
- Probar impacto en ventas, stock, membresías o asistencias si hay relación.
- Ejecutar validaciones de sintaxis y carga de rutas. Crear o ejecutar pruebas PHP cuando el usuario las solicite expresamente.

## Documentación obligatoria por cambio

Cada implementación debe actualizar los archivos `.md` impactados:

- Archivo del modulo en `System/modules`.
- `TABLES.md` si cambian campos, tablas o relaciones.
- `ARCHITECTURE.md` o esta guia si cambia un patron transversal.
- `new_requirements` para conservar decisiones de evolución; al implementar una mejora, integrar el resultado en el módulo.

La documentación debe describir el comportamiento final, no solamente la intención inicial.

## Mantenimiento de la guía

- `GENERALIDADES.md` conserva reglas transversales; aquí permanecen únicamente convenciones de System.
- Los servicios de escritura, configuración y referencias reciben `companyId` y `userId` explícitos. La auditoría automática obtiene el actor desde el request de frontera, sin depender del facade `Auth`.
- Todo módulo nuevo debe quedar documentado antes de abrir trabajo visual adicional.
