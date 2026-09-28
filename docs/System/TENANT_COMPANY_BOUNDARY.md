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

El proyecto todavía no está en producción, por lo que la estructura se corrigió en las migraciones de origen. No existe una migración incremental que copie o elimine columnas históricas. Una instalación limpia crea directamente el modelo vigente.

Las migraciones tenant son exclusivamente estructurales: crean, modifican o revierten el esquema, pero no siembran configuraciones, catálogos ni permisos operativos. `CompanyProvisioningService` es la única fuente de los datos base del tenant y los sincroniza mediante operaciones idempotentes por lotes. Por esta razón, un rollback de esquema tampoco elimina configuraciones administradas por el aprovisionador.

La prueba `TenantCompanyBoundaryTest` impide que una migración vuelva a declarar `company_id` fuera de las cuatro tablas permitidas o escriba datos base con `DB::table()`. También limita los archivos backend autorizados, comprueba que frontend no reciba selectores de empresa, rechaza `$companyId` en cualquier método del backend y evita que regresen primitivas de alcance anteriores. La inspección abarca servicios, helpers y capa HTTP, incluyendo aprovisionamiento, métodos privados y protegidos. Todos resuelven la empresa raíz desde `TenantCompanyContext`; el ID solamente se materializa como variable local al consultar o persistir una relación estructural.

Las APIs `CompanySectionService` y `RolePermissionService` reciben únicamente el rol o los datos funcionales. Sus claves se aíslan con `TenantContext::cacheNamespace()` y la invalidación opera sobre el tenant conectado, evitando propagar un ID local que se repite entre bases.

La precisión decimal también se resuelve directamente desde `CompanySettingService`; `Utilities::round()`, `Utilities::formatDecimal()` y `Utilities::decimalPrecision()` no aceptan un selector de empresa.

`TenantCompanyDatabaseBoundaryTest` inspecciona `INFORMATION_SCHEMA` después de migrar MySQL y verifica que las cuatro columnas sean obligatorias y tengan clave foránea hacia `companies`.

La operación completa de contextos, sucursales, aprovisionamiento, jobs, almacenamiento, observabilidad y respaldos está definida en [TENANT_OPERATIONS.md](TENANT_OPERATIONS.md).

## Verificación de base de datos

La reconstrucción de pruebas debe apuntar explícitamente a `blapos_testing`. Nunca ejecutar `migrate:fresh` sobre la base de desarrollo.

```bash
php artisan migrate:fresh --database=mysql --seed
composer check:tenant-boundary
php artisan test
```

`phpunit.xml` fija `DB_DATABASE=blapos_testing`. Para comandos manuales, verificar antes la conexión resuelta y definir `DB_DATABASE=blapos_testing` en el entorno del proceso.
