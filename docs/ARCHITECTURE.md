# Arquitectura y convenciones tecnicas

## Estructura general

El proyecto usa Laravel como backend principal y monta experiencias Vue en paginas Blade. La estructura esta dividida en dos grandes ambitos:

- `System`: area interna autenticada.
- `Guest`: area publica por empresa.

Carpetas clave:

- `app/Http/Controllers/System`: controladores internos por modulo.
- `app/Http/Controllers/Guest`: controladores publicos.
- `app/Services/System`: reglas de negocio y datos iniciales.
- `app/Models/System`: modelos internos.
- `app/Models/Guest`: modelos para consultas publicas.
- `app/Http/Requests/System`: validaciones de formularios internos.
- `routes/System`: rutas por modulo interno.
- `routes/Guest`: rutas publicas por modulo.
- `resources/views/System`: contenedores Blade internos.
- `resources/views/Guest`: contenedores Blade publicos.
- `resources/js/System`: componentes, helpers y paginas Vue internas.
- `resources/js/Guest`: componentes, helpers y paginas Vue publicas.

## Rutas

`routes/web.php` agrupa todo bajo middleware `web`.

Rutas publicas:

- `{company_slug}/book_complaints`
- `{company_slug}/home`
- `{company_slug}/tracking_attendances`
- `{company_slug}/biometric_devices`

Todas usan `company.exists`, lo que indica que la empresa se resuelve por slug antes de entrar al controlador.

Rutas internas:

- Protegidas por `auth` y `verified`.
- Se agrupan por prefijos como `/customers`, `/sales`, `/branches`, `/dashboard`.
- Cada archivo de `routes/System` define el CRUD o acciones especificas del modulo.

## Patron de controlador interno

La mayoria de controladores internos repiten este flujo:

- `initParams(Request $request)`: devuelve configuracion inicial para la pagina Vue.
- `list(Request $request)`: devuelve registros paginados con filtros.
- `index()`: retorna la vista Blade principal.
- `create()`, `edit()`: a veces vacios o placeholders.
- `store(FormRequest|Request $request)`: crea registros.
- `show(Model $record|int $id)`: devuelve un registro.
- `update(FormRequest|Request $request, int $id)`: actualiza.
- `destroy(Model $record)`: elimina o inactiva.

Los controladores internos suelen extender `BaseController`, que concentra:

- `getAuthUser()`
- `getCompanyId()`
- `getUserId()`
- `getPerPage()`
- `getFilters()`
- `getPage()`

Tambien usan traits/concerns para respuestas API y manejo de excepciones:

- `HandlesApiResponses`
- `HandlesExceptions`

## Patron de servicio

Existen dos familias de servicios:

- Servicios de configuracion (`*ConfigService`): preparan datos para `initParams`, usualmente con cache.
- Servicios de negocio (`*Service`, `*BusinessService`): crean, actualizan, cancelan o consultan entidades.

Ejemplos:

- `ProductConfigService` y `ProductService`.
- `TrackingAttendanceConfigService`, `TrackingAttendanceService` y `TrackingAttendanceBusinessService`.
- `SaleConfigService` y `SaleService`.
- `BiometricDeviceConfigService` y `BiometricDeviceService`.

## Frontend

Cada modulo interno suele tener:

- Vista Blade: `resources/views/System/general/<Module>/<page>/main.blade.php`.
- Entrada JS: `resources/js/System/Pages/<Module>/<page>/main.js`.
- Componente Vue: `resources/js/System/Pages/<Module>/<page>/main.vue`.

Hay componentes y helpers compartidos:

- Componentes de formulario: `InputText`, `InputDate`, `InputSelect`, `InputNumber`, etc.
- Componentes genericos: `DataTable`, `FiltersSection`, `FormModal`, `StatusBadge`.
- Helpers CRUD: `BaseCrudModule.js`, `CrudMixin.js`, `ModuleFactory.js`.
- Helpers de request, fechas, numeros, strings y validaciones.

## Base de datos

Las migraciones principales inicializan grupos funcionales:

- `create_init_masters_table`: maestros, empresas, secciones, roles, usuarios.
- `create_init_companies_table`: sucursales, series, items, activos, categorias, clientes, almacenes.
- `create_init_sales_table`: ventas.
- `create_init_subscriptions_table`: membresias, asistencias, emails de suscripcion.
- `create_init_clients_table`: libro de reclamaciones.
- `create_init_biometrics_table`: dispositivos biometricos y huellas.

## Tenant y empresa raíz

Cada tenant es una empresa y su conexión de base de datos es el límite de aislamiento. Al modificar código, revisar:

- Que consultas internas operen exclusivamente sobre la conexión tenant resuelta.
- Que ids recibidos por request no permitan acceder a datos de otra empresa.
- Que las rutas publicas usen la empresa cargada por middleware y no datos arbitrarios.
- Que entidades por sucursal validen que la sucursal pertenece a la empresa.

Solo las cuatro relaciones estructurales descritas en `System/TENANT_COMPANY_BOUNDARY.md` conservan `company_id`.

## Cache

Los servicios `*ConfigService` construyen claves por empresa y página; cuando contienen referencias restringidas, también por usuario. Las mutaciones invalidan consumidores mediante `InitParamsCacheInvalidationService`.

## Auditoria

Muchas tablas tienen campos:

- `created_by`
- `updated_by`
- `deleted_by`
- `canceled_by`

Al crear nuevas operaciones, conservar este estilo. Si se cancela o retira algo, no asumir borrado fisico salvo que el modulo ya lo haga.

## Validaciones

Hay `FormRequest` para muchos CRUDs internos. Cuando se agreguen campos a un modulo existente, revisar:

- `Store*Request`
- `Update*Request`
- formulario Vue
- servicio/controlador
- migracion/modelo
- listado o detalle

## Estado técnico

- Las rutas SPA publican únicamente operaciones implementadas; los métodos REST de plantilla que devolvían `501` ya no tienen rutas activas.
- Los servicios de escritura, configuración y referencias reciben `companyId` y `userId` explícitos. La auditoría automática obtiene el actor desde el request de frontera y no usa `Auth` como dependencia oculta.
- Autorización funcional usa módulo + acción; el alcance operativo intersecta empresa, sucursal, caja y almacén.
- La invalidación de caché se centraliza por dependencia y las claves tenant no usan versiones paralelas.
- Guest usa modelos/servicios con contrato público y límites antiabuso centralizados.
- Migraciones, servicios y módulos documentan sus tablas, reglas y mejoras visuales implementadas; los criterios visuales transversales viven en `GENERALIDADES.md`.

## Criterios de evolución

- Llevar mensajes repetidos a traducciones o constantes cuando exista más de un consumidor.
- Introducir enums o value objects cuando un estado se comparta entre varios dominios y aporte validación real.
- Toda ruta mutable debe usar FormRequest o una validación dedicada equivalente, además de permiso y alcance.
- Las pruebas de feature se agregan cuando sean solicitadas para el flujo correspondiente; no se generan automáticamente en esta fase.
- Una capacidad incompleta no debe exponerse mediante una ruta placeholder.

## Referencia transversal

Las reglas compartidas de multiempresa, UI, branding, formularios, modales, cache, migraciones e impuestos viven en [GENERALIDADES.md](GENERALIDADES.md). Esta arquitectura debe describir la estructura; las decisiones reutilizables deben mantenerse en ese archivo para no duplicarlas.

Los servicios transversales de inventario, caja, impuestos, permisos, seguridad y tenancy se detallan en `docs/System`. Las migraciones base continúan organizadas por dependencias de creación para permitir `migrate:fresh`; una separación física adicional solo debe hacerse si mantiene el orden de claves foráneas y no agrega migraciones correctivas innecesarias.
