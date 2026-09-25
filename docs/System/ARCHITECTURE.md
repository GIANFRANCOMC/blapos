# System - Arquitectura

## Proposito

System es una aplicación Laravel 10 con frontend Vue 3 montado sobre vistas Blade. Está orientada a usuarios internos de una empresa. Cada conexión tenant representa una única empresa y acota todas sus operaciones.

## Capas

- Rutas: archivos por modulo en `routes/System`.
- Controladores: reciben requests, preparan filtros, validan ownership basico y delegan a servicios.
- FormRequests: validan creacion/actualizacion en modulos CRUD.
- Servicios: concentran reglas de negocio, consultas paginadas, transacciones y cache de parametros.
- Modelos: relaciones, accessors, scopes y helpers de entidad.
- Blade: contenedor inicial de cada pantalla.
- Vue: experiencia interactiva de listados, formularios, modales y acciones.

## Patron comun de modulo

Un modulo System normalmente tiene:

- Ruta con prefijo: `/customers`, `/sales`, `/branches`, etc.
- Controlador: `*Controller`.
- Servicio principal: `*Service`.
- Servicio de configuracion: `*ConfigService`.
- Request de creacion y actualizacion si modifica datos.
- Modelo o modelos asociados.
- Vista Blade en `resources/views/System/general`.
- Pagina Vue en `resources/js/System/Pages`.


## Multi-tenant por base de datos

La separación física por cliente está documentada en `MULTITENANT.md`. La aplicación solo atiende subdominios de un nivel registrados bajo `TENANCY_BASE_DOMAIN`; el dominio raíz usa otro proyecto. `landlord` resuelve el nombre de la BD y `tenant` opera sobre ella con credenciales externas al registry. No existen subcompañías dentro de una base tenant; el contrato completo está en `TENANT_COMPANY_BOUNDARY.md`.

`ResolveTenant` es middleware global para proteger rutas web y API antes de cualquier consulta funcional. El grupo `web` se aplica una sola vez desde `RouteServiceProvider`; no debe volver a declararse dentro de `routes/web.php`.
## Tenant y empresa raíz

Regla fuerte: toda consulta operativa usa la conexión tenant resuelta y valida los alcances funcionales de sucursal, almacén, caja o documento cuando correspondan.

Cuando se reciba un id por request:

- Validar que el registro exista en el tenant actual.
- Validar sucursal si la tabla depende de `branch_id`.
- Validar serie mediante su sucursal si la venta usa `serie_id`.
- Evitar confiar en ids enviados por frontend.

Las mutaciones tenant pueden extender `CompanyFormRequest`. Este contrato:

- Autoriza únicamente usuarios con tenant y empresa raíz válidos.
- Permite normalizar cadenas antes de ejecutar reglas.
- Evita repetir autorización básica en cada Store/Update Request.

`ExistsInTenant` valida relaciones directas en la base activa. Los servicios aplican adicionalmente el alcance operativo cuando una entidad depende de una sucursal, almacén o caja.

La validación HTTP no reemplaza las restricciones de base de datos ni las comprobaciones del servicio. Para relaciones sensibles se aplican tres niveles: FormRequest, defensa de negocio en Service y claves/índices en migración.

## Estados

Estados observados:

- Generales: `active`, `inactive`.
- Ventas: `active`, `canceled`, `inactive`.
- Asistencias: `active`, `canceled`, `inactive`, `finalized`.
- Reclamaciones: `pending`, `in_progress`, `resolved`.
- Emails: `pending`, `sent`, `failed`.
- Activos asignados: `active`, `maintenance`, `retired`.

## Cache

Todos los servicios `*ConfigService` heredan de `BaseConfigService`.

- La clave incluye namespace tenant, módulo y página.
- El TTL predeterminado es una hora.
- Cada servicio implementa únicamente `getCachePrefix()` y `buildConfig()`.
- Los servicios dependientes del colaborador añaden `userId` a la clave y mantienen su índice dentro del mismo namespace tenant.
- Los módulos con más de una página declaran `cachePages()`; actualmente Ventas usa `main` y `list`.
- Una página vacía o desconocida se normaliza a la primera página soportada.
- `clearAllCache($companyId)` elimina todas las páginas declaradas por el módulo.
- `InitParamsCacheInvalidationService` resuelve dependencias entre recursos y módulos consumidores.
- La invalidación dependiente del usuario recorre solamente IDs que realmente generaron caché; no consulta la tabla `users` durante una limpieza.
- No se invalida caché cuando una mutación no modifica datos incluidos en `initParams`.

Los maestros activos se reutilizan durante seis horas mediante `MasterReferenceDataService`. `MasterDataService` invalida esa caché y los `initParams` dependientes después de cada mutación correcta.

## Configuración por empresa

`company_settings` concentra valores configurables que pertenecen a una empresa y que no justifican una tabla funcional independiente.

- Cada valor se identifica por `company_id`, `group` y `key`.
- `description` documenta el impacto operativo de cada clave para futuras pantallas de configuración y soporte.
- `value_type` permite interpretar strings, booleanos, enteros, decimales o JSON.
- El grupo inicial `internal_code_prefixes` define prefijos para productos, servicios, membresías, marcas, categorías, sucursales y activos.
- El grupo `inventory` define políticas como bloqueo de stock negativo en ventas y reposición automática al anular ventas.
- `CompanySettingService` entrega valores por grupo y mantiene defaults de compatibilidad.
- `BaseConfigService::internalCodePrefixes()` expone el mismo contrato a los módulos que lo requieren.
- `InternalCodeService` es la autoridad para aplicar el prefijo en backend. La presentación Vue no reemplaza esta validación.
- `AppliesInternalCodePrefix` evita repetir la preparación del código en los FormRequests equivalentes.
- Un valor nulo o vacío desactiva el prefijo para esa entidad y empresa.

`GET|POST|PATCH /master-data/company-settings` permite administrar estas claves por empresa. Las mutaciones usan permisos de Mi empresa, auditoría empresarial e invalidación centralizada de `initParams`.

## Errores de formulario

- Los mensajes inline deben describir únicamente la corrección, por ejemplo `Campo obligatorio.`.
- Los resúmenes de modal o SweetAlert deben añadir el label, por ejemplo `Precio de venta: Campo obligatorio.`.
- `Forms.getDescriptiveErrors` resuelve el contexto frontend.
- `Forms.handleFormResponseErrors` conserva el error bajo el campo y genera un resumen contextual para respuestas backend, incluidos los `422` de FormRequest.

## Datos de referencia para `initParams`

Los modelos no deben exponer métodos genéricos como `getAll($type, $companyId)`. Ese contrato ocultaba filtros tras strings, repetía el identificador de empresa y permitía combinaciones inválidas.

- `CompanyReferenceDataService::for($companyId, $userId)` concentra consultas acotadas a empresa y alcance operativo del colaborador.
- Sus métodos expresan la intención: `brands()`, `categories()`, `stockWarehouses()`, `branchesWithSeries()`, `activeCustomers()`, `saleItems()`, entre otros.
- `MasterReferenceDataService` entrega maestros globales activos, como monedas y tipos de documento según su uso.
- Cada `ConfigService` crea una referencia por empresa y usuario y reutiliza esa instancia dentro de su construcción de `initParams`.
- Los modelos conservan relaciones, accessors, scopes y reglas propias de la entidad; no conocen el contexto de una pantalla.
- La prueba `ModelGetAllConventionTest` evita reintroducir `Model::getAll(...)`.

## Menú por empresa

`CompanySectionService` consulta y almacena categorías, secciones, grupos y opciones habilitadas por `companies_sub_sections`.

- El layout solicita las secciones al servicio; no lee claves de caché directamente.
- La clave es `company_sections:company:{id}:role:{roleId|all}` y su TTL es de 30 minutos.
- La consulta selecciona únicamente los campos requeridos por sidebar, favoritos y Home.
- `CompanySubSectionObserver` invalida automáticamente la empresa afectada al crear, editar o eliminar una asignación.
- `Company` ya no contiene `getActiveSections`; la consulta pertenece al servicio que conoce su uso y caché.
- No se utiliza un listener de autenticación para precargar el menú.

## Estado actual

- Los servicios de escritura, configuración, referencias y auditoría reciben contexto explícito o lo obtienen del request únicamente en observers de frontera; ningún servicio de dominio consulta el facade `Auth`.
- Permisos combinan módulo + acción y alcances de sucursal, caja y almacén.
- Los endpoints mutables usan FormRequest o validadores dedicados cuando el payload es dinámico.
- Las rutas REST de plantilla sin implementación ya no se publican.
- Inventario, caja, compras, ventas y POS tienen permisos y rutas diferenciadas aunque algunos reutilicen controlador o componente.

## Criterio para evolucionar

No se recomienda reescribir toda la arquitectura. El criterio adecuado es mejorar por flujo:

- Mantener patron actual si el cambio es pequeno.
- Extraer servicios compartidos si hay duplicacion real.
- Introducir tests en flujos criticos antes de cambiar reglas sensibles.
- Mejorar autorizacion y validacion sin romper la estructura existente.

Esta arquitectura debe mantenerse alineada con `../GENERALIDADES.md`.
