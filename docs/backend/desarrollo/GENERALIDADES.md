# Generalidades del Proyecto

Este archivo concentra las reglas de negocio y los contratos técnicos que comparten los módulos de Blapos.

## Historial de estados operativos

Los estados con trazabilidad deben registrar la fecha real y el usuario que realizó cada transición dentro de la misma transacción que sus efectos. Conservar una copia del nombre para el historial y evitar duplicar eventos al reintentar. No sustituir al creador del documento por el usuario que confirma un paso posterior ni inventar estados intermedios cuando se registra directamente en un estado avanzado.

Una operación nueva puede conservar el mismo estado: cada recepción parcial de Traslados registra su documento, posiciones, cantidades, fecha y responsable aunque la cabecera siga Recibido parcialmente. La idempotencia usa una clave de petición y su contenido, junto con la versión del documento y el bloqueo transaccional. No imponer unicidad al estado del historial. Los estados de recepción se calculan con las cantidades pendientes de cada posición y no se editan manualmente.

`StatusRoadmap.vue` presenta eventos de solo lectura con `id`, `label`, `tone`, `occurred_at` y `user_name`, usando las fuentes y colores existentes. El orden proviene del backend. El componente no modifica estados ni calcula responsables. Los registros sin historial muestran su ausencia explícitamente. Traslados aplica estas reglas y distingue creador/envío de aceptación/recepción. Ver [Trazabilidad de traslados](../system/modules/16_stock_management.md).

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

Las páginas Blade/Vue y sus datos JSON usan rutas GET distintas, con control de autenticación, permisos y tenant en el servidor. No usar parámetros ni cabeceras del navegador para alternar HTML y JSON en una misma URL; ver `../system/ARCHITECTURE.md`.

## Multi-tenant por BD

Blapos opera exclusivamente desde subdominios registrados y con una base de datos por cliente. El dominio raíz pertenece a otro proyecto. El registro central mínimo vive en `landlord`; la operación del cliente se ejecuta en `tenant`. La guía completa está en `backend/tenancy/MULTITENANT.md`.

Cada base tenant representa exactamente una empresa. El aislamiento operativo depende de la conexión resuelta, no de repetir `company_id` en cada fila. La regla y sus cuatro excepciones estructurales están en `backend/tenancy/TENANT_COMPANY_BOUNDARY.md`.

La sesión también se aísla por tenant. `ResolveTenant` cambia el nombre de cookie antes de iniciar sesión y `EnsureTenantSession` invalida cualquier sesión que intente cruzar de un tenant a otro. Para producción, usar HTTPS, `SESSION_SECURE_COOKIE=true` y mantener `SESSION_DOMAIN` vacío. Ver `backend/seguridad/SECURITY_AND_AUTH.md`.
## Tenant y empresa raíz

Los datos operativos, hijos y maestros viven exclusivamente en la base tenant activa. Solo `branches`, `company_settings`, `company_socials_media` y `companies_sub_sections` conservan la relación estructural con `companies`.

Criterios esperados:

- No aceptar `company_id` desde frontend.
- Resolver la empresa raíz con `TenantCompanyContext`.
- Validar IDs con `ExistsInTenant` y unicidad con `UniqueInTenant`.
- Usar `UniqueInTenant` para unicidad lógica dentro del tenant.
- Evitar índices explícitos salvo decisión justificada; conservar claves primarias, foráneas y `unique` cuando expresen integridad real.

[Estándares de interfaz](../../frontend/fundamentos/ESTANDARES_UI.md) reúne branding, tipografía, tablas, formularios y alertas.

### Terminología de membresías

Usar «membresía» para el plan del catálogo y para la membresía del cliente. Emplear el mismo término en navegación, formularios, validaciones, reportes y plantillas de correo. Los contratos técnicos conservan las claves existentes, como `subscription` y `subscriptions`.

### Estado del stock

Usar los mismos tres estados en tablas, filtros, consultas por almacén, reportes Excel y PDF y correos de alerta. Las etiquetas se definen en `resources/stock-statuses.json` y se consumen desde `StockStatus.php` y `StockStatus.js`.

- **Agotado** cuando el stock actual es cero o negativo.
- **Stock bajo** cuando el stock actual es positivo y está en el mínimo o por debajo.
- **Stock saludable** cuando el stock actual es positivo y supera el mínimo.

En el resumen del catálogo, clasificar como **Agotado** si el total del producto es cero o negativo. Si el total es positivo y algún almacén tiene una alerta, mostrar **Stock bajo**. La etiqueta **Stock saludable** corresponde al total positivo con todos sus almacenes sin alertas.

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

- Archivo del módulo en `docs/backend/system/modules` o `docs/backend/guest/modules`.
- `TABLES.md` si cambian tablas, campos o relaciones.
- `../arquitectura/ARCHITECTURE.md` si cambia arquitectura.
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

Mantén las reglas transversales en su guía y registra las mejoras puntuales en el módulo correspondiente.

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
