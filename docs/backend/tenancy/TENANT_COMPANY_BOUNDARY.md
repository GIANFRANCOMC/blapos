# Límite tenant–empresa

## Regla vigente

Cada base de datos tenant representa exactamente una empresa. La conexión tenant es el límite de aislamiento de datos; `company_id` ya no se replica en tablas operativas, catálogos, transacciones, usuarios ni auditorías.

La empresa raíz se obtiene con `TenantCompanyContext`. Ningún formulario, endpoint, comando, helper, migración ni método del backend —público o privado— debe aceptar un identificador de empresa como fuente de autoridad.

## Tablas que conservan `company_id`

Solo cuatro tablas tenant mantienen la relación porque describen estructura directamente dependiente de `companies`:

| Tabla | Motivo |
| --- | --- |
| `branches` | Una sucursal pertenece formalmente a la empresa raíz. |
| `company_settings` | Las configuraciones son propiedades extensibles de la empresa. |
| `company_socials_media` | Los enlaces públicos forman parte del perfil de la empresa. |
| `companies_sub_sections` | Es la proyección de módulos habilitados para la empresa. |

`companies` continúa siendo la raíz del tenant. El aprovisionamiento reserva el ID local `1` para esa única fila. La base landlord conserva el registro y dominio del tenant, pero no duplica su `company_id` local.

## Reglas de desarrollo

- Las tablas operativas se aíslan por conexión tenant y se relacionan mediante sus claves funcionales: sucursal, almacén, usuario, documento o cabecera.
- No agregar `company_id` a migraciones nuevas salvo que la tabla sea una extensión directa de `companies` y exista una justificación arquitectónica explícita.
- No recrear scopes o reglas como `BelongsToCompany`, `UniqueInCompany` o `forCompany()`.
- Usar `ExistsInTenant` y `UniqueInTenant` para validar registros y unicidad dentro de la base tenant actual.
- Cuando se opere sobre una de las cuatro tablas estructurales, filtrar y persistir su `company_id` resuelto por `TenantCompanyContext`.
- Las claves de caché compartidas deben incluir `TenantContext::cacheNamespace()`; el ID local de la empresa suele ser `1` en todos los tenants y no es un namespace global seguro.

## Resolución del contexto

`TenantContext` identifica la conexión/base activa y genera el namespace de caché. `TenantCompanyContext` obtiene la única empresa raíz válida de esa conexión. Los controladores, servicios, comandos y vistas reutilizan estos servicios en vez de leer `company_id` desde el usuario o desde la petición.

## Migraciones y compatibilidad

El proyecto todavía no está en producción y las correcciones estructurales anteriores se hicieron en las migraciones de origen. La unidad de medida se incorpora mediante una migración estructural incremental para actualizar sin borrar el tenant de desarrollo existente; una instalación limpia ejecuta la cadena completa de migraciones.

Las migraciones tenant se limitan al esquema. `SystemNavigationSeeder` define el catálogo inicial, incluido `POS venta`. `CompanyProvisioningService` registra los datos base, como la unidad `UNIT` y el límite diario de asistencias. El aprovisionamiento añade los valores faltantes y conserva los configurados. Las migraciones históricas que duplicaban esos datos mantienen su archivo y su posición en el historial.

Un rollback estructural puede consultar el estado de los datos cuando debe detectar una conversión destructiva. La reversión de `subscriptions.attendance_limit_per_day` comprueba en la conexión de escritura del tenant que no existan membresías con valor `NULL` antes de volver a exigir un entero.

La prueba `TenantCompanyBoundaryTest` verifica que `company_id` solo se declare en las cuatro tablas permitidas y detecta escrituras de datos en migraciones hechas con el constructor de consultas o sentencias SQL directas. También limita los archivos backend autorizados, comprueba que frontend no reciba selectores de empresa, rechaza `$companyId` en cualquier método del backend y evita que regresen primitivas de alcance anteriores. La inspección abarca servicios, helpers y capa HTTP. El código resuelve la empresa raíz desde `TenantCompanyContext` y usa su ID como variable local al consultar o persistir una relación estructural.

Las APIs `CompanySectionService` y `RolePermissionService` reciben únicamente el rol o los datos funcionales. Sus claves se aíslan con `TenantContext::cacheNamespace()` y la invalidación opera sobre el tenant conectado, evitando propagar un ID local que se repite entre bases.

`CompanySectionService` concentra la consulta de rutas habilitadas para la empresa. Marcas y Categorías usan esa consulta para presentar los tipos de catálogo. La ficha de Mi espacio resuelve la empresa raíz dentro de `CompanyCardDataService`.

La sincronización habitual de `SystemCatalogSyncService` agrega registros faltantes con `insertOrIgnore` en `companies_sub_sections` y `role_sub_sections`. Conserva el estado, el orden y los permisos guardados en el tenant. La aplicación explícita del diseño mediante `tenant:navigation-sync <slug> --apply-layout` puede cambiar la ubicación y el orden de las opciones del catálogo dentro del tenant indicado. Los IDs, el estado de habilitación, los permisos, los favoritos y las métricas permanecen vinculados a los mismos submódulos. [Mapa y reglas del menú](../../frontend/patrones/00_menu_order.md).

Las reglas de precisión se concentran en `app/Helpers/System/Numbers.php`. La configuración se lee con `CompanySettingService` desde la empresa activa del tenant.

`TenantCompanyDatabaseBoundaryTest` inspecciona `INFORMATION_SCHEMA` después de migrar MySQL y verifica que las cuatro columnas sean obligatorias y tengan clave foránea hacia `companies`.

La operación completa de contextos, sucursales, aprovisionamiento, jobs, almacenamiento, observabilidad y respaldos está definida en [TENANT_OPERATIONS.md](TENANT_OPERATIONS.md).

## Verificación de base de datos

La reconstrucción de pruebas debe apuntar explícitamente a `blapos_testing`. Nunca ejecutar `migrate:fresh` sobre la base de desarrollo.

```bash
composer check:tenant-boundary
php vendor/bin/phpunit
```

`phpunit.xml` fija `DB_DATABASE=blapos_testing`. Para comandos manuales, verificar antes la conexión resuelta y definir `DB_DATABASE=blapos_testing` en el entorno del proceso.

La revisión pasó las 26 pruebas de arquitectura, la suite completa de PHPUnit de 248 pruebas y las 293 pruebas de frontend. Se comprobaron las preferencias de navegación, el rechazo del rollback cuando existe un límite `NULL` y el bloqueo de acceso a módulos deshabilitados.
