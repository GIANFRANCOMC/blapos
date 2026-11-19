# System - Guía de desarrollo

## Generalidades transversales

Las reglas compartidas de caché, migraciones, multiempresa, impuestos e inventario viven en [Generalidades](../desarrollo/GENERALIDADES.md). Para componentes, formularios y estilos, consulta los [estándares de interfaz](../../frontend/fundamentos/ESTANDARES_UI.md).

## Principios

- Respetar `Controller -> Service -> Model`.
- Mantener `System` aislado de `Guest`.
- Filtrar por empresa en toda operación interna.
- Preferir cambios pequeños, verificables y compatibles con datos actuales.
- No hacer refactors globales si el requerimiento solo toca un módulo.
- Mantener nombres técnicos en inglés y textos de UI en español, con tildes y signos de apertura correctos.
- Aplicar la misma estructura de rutas, controladores, requests, servicios, Vue, CSS y documentación en módulos equivalentes.


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
- `SystemNavigationSeeder` inicializa una base vacía y consolida agrupaciones heredadas del catálogo; `SystemCatalogSyncService` proyecta el resultado a organizaciones y perfiles de acceso total.
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

## Interfaz

Consulta la [guía frontend de System](../../frontend/desarrollo/GUIA_SYSTEM.md) para componentes, controles y convenciones visuales.

## Al agregar campos

Pasos recomendados:

1. Mientras el proyecto siga en fase reiniciable, modificar la migración base propietaria; crear una migración incremental únicamente cuando existan datos persistentes que deban conservarse.
2. Actualizar `$fillable` o asignaciones controladas en servicio/modelo.
3. Actualizar casts si es fecha, boolean, decimal o json.
4. Actualizar StoreRequest y UpdateRequest.
5. Actualizar formulario Vue.
6. Actualizar listado/detalle si el campo debe verse.
7. Agregar prueba o lista de verificación manual.
8. Actualizar documentación del módulo y [tablas de System](../datos/system/TABLES.md).

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
- [Tablas de System](../datos/system/TABLES.md) si cambian campos, tablas o relaciones.
- `ARCHITECTURE.md` o esta guia si cambia un patron transversal.
- `new_requirements` para conservar decisiones de evolución; al implementar una mejora, integrar el resultado en el módulo.

La documentación debe describir el comportamiento final, no solamente la intención inicial.

## Mantenimiento de la guía

- `GENERALIDADES.md` conserva reglas transversales; aquí permanecen únicamente convenciones de System.
- Los servicios de escritura, configuración y referencias reciben `companyId` y `userId` explícitos. La auditoría automática obtiene el actor desde el request de frontera, sin depender del facade `Auth`.
- Todo módulo nuevo debe quedar documentado antes de abrir trabajo visual adicional.
