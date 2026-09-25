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

Los layouts deben declarar `data-assets-path` con una URL absoluta generada por Laravel, por ejemplo `{{ asset('System/assets') }}/`. Los scripts heredados de la plantilla (`helpers.js`, `template-customizer.js`, `config.js`, `main.js` y vistas demo que usen `assetsPath`) dependen de ese atributo para cargar CSS dinámico, imágenes y JSON. `config.js` normaliza cualquier valor relativo antiguo, pero no se deben volver a introducir rutas como `../System/assets/` porque fallan en páginas profundas.

Los CSS públicos de System se generan desde parciales en `resources/css/System`. Editar los parciales y ejecutar `npm run build:css:system`; no editar directamente `public/System/assets/css/br-branding.css`, `br-login.css` ni `demo.css`.

`resources/css/System/platform.css` mantiene el mismo orden de parciales y queda preparado como entry de Vite. Mientras los layouts sigan usando `<link rel="stylesheet">`, el comando de build CSS conserva los archivos públicos compatibles.

Los CSS de plantilla en `public/System/assets/vendor/css/core.css`, `public/System/assets/vendor/css/rtl/core.css`, `public/System/assets/vendor/css/theme-default.css` y `public/System/assets/vendor/css/rtl/theme-default.css` no deben introducir colores de marca fijos. La paleta primary heredada de Vuexy se mapea a tokens `--br-vuexy-*`, definidos en `br-branding/00-tokens.css`, para evitar conflictos con `!important` y permitir personalizar la marca desde un solo archivo.

La plataforma usa una densidad visual compacta definida en `br-branding/70-visual-density-brand-refresh.css`: botones, inputs, labels, tablas, modales, badges y tooltips deben respetar esa escala. Si una pantalla se ve grande o pesada, ajustar primero variables como `--br-control-height`, `--br-btn-height`, `--br-font-size-ui` o `--br-table-cell-pad-y`, no crear tamaños locales por módulo.

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
- Los icon buttons requieren `aria-label` y tooltip.
- En desktop, botones secundarios de descarga pueden mostrarse sólo con icono si el tooltip es claro; en móvil deben mostrar icono y texto.
- Las tablas deben reutilizar la superficie global de encabezado y estados compactos.
- Los estados vacíos y loaders deben usar componentes compartidos (`WithoutData`, `Loader`) para evitar estilos distintos por módulo.

## Formularios

Todos los formularios deben reutilizar componentes y patrones existentes:

- `InputText` para texto.
- `InputNumber` para numéricos y dinero.
- `InputSlot` cuando el input necesita prefijos, sufijos, acciones, moneda o contadores.
- `vue-select` con estilos globales y `SelectNoOptions` para mensajes vacíos en español.
- `CopyButton` para copiar valores puntuales.
- `BarcodeDownloadButton` para códigos de barras descargables.

Los controles compuestos se tratan como una sola unidad visual. El hover, foco, disabled y error deben bordear el conjunto completo: input, prefijo, contador, botón lateral o selector.

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

Montos, cantidades operativas, costos, tributos, pagos, inventario y valorización deben tomar su precisión desde configuración por cliente. El valor `3` es el default inicial sembrado en `company_settings.numeric_validation.decimal_precision`; no debe quedar quemado como regla de negocio en requests, servicios ni componentes.

Configuración vigente por empresa:

- `numeric_validation.decimal_precision`: cantidad de decimales permitidos y usados para normalizar.
- `numeric_validation.default_min_value`: mínimo operativo por defecto.
- `numeric_validation.default_max_value`: máximo operativo por defecto para importes, cantidades, pagos y saldos.
- `numeric_validation.max_file_size_kb`: tamaño máximo configurable para archivos de formularios.

Reglas:

- Los `FormRequest` company-scoped deben extender `CompanyFormRequest` cuando validen números, archivos o normalicen entradas. Usar `decimalPrecision()`, `numericMinValue()`, `numericMaxValue()`, `numericMaxFileSizeKb()`, `numericRules()` y `normalizeDecimalInput()` en vez de valores fijos.
- `BaseConfigService` expone `config.generalConfig.forms.inputs` en `initParams`; el frontend aplica esos valores con `applyGeneralConfig()` para que `InputNumber`, `InputSlot` y validaciones visuales queden alineadas con el backend.
- `Utilities::round($value, null, $companyId)` puede usar la precisión por empresa cuando el servicio conoce el `companyId`. Si no lo conoce, conserva el fallback global para compatibilidad.
- Usar `fixedNumber`/`InputNumber` sin forzar `2` decimales salvo que sea una métrica no monetaria ni operativa.
- Los casts Eloquent usan `ConfigurableDecimal` y las migraciones nuevas nacen con escala técnica de 3 decimales; la validación y redondeo funcional se gobiernan por `company_settings`.
- Métricas de tiempo, latencia o porcentajes visuales pueden conservar reglas propias si no afectan dinero, stock o saldo.

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

Convención visual de SweetAlert:

- Error: borde/icono rojo, botón principal celeste.
- Warning: borde/icono amarillo, botón de confirmación danger.
- Question: borde/icono celeste, botón secondary.
- Success: borde/icono verde, botón principal celeste.
- Loading: logomark centrado con respiración sutil y texto amable.

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
