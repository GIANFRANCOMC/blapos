# Límite tenant–empresa

## Regla vigente

Cada base de datos tenant representa exactamente una empresa. La conexión tenant es el límite de aislamiento de datos; `company_id` ya no se replica en tablas operativas, catálogos, transacciones, usuarios ni auditorías.

La empresa raíz se obtiene con `TenantCompanyContext`. Ningún formulario, endpoint o comando tenant debe aceptar un identificador de empresa enviado por el cliente como fuente de autoridad.

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

La prueba `TenantCompanyBoundaryTest` impide que una migración vuelva a declarar `company_id` fuera de las cuatro tablas permitidas, limita los archivos backend autorizados, comprueba que frontend no reciba selectores de empresa y evita que regresen primitivas de alcance anteriores.

`TenantCompanyDatabaseBoundaryTest` inspecciona `INFORMATION_SCHEMA` después de migrar MySQL y verifica que las cuatro columnas sean obligatorias y tengan clave foránea hacia `companies`.

## Verificación de base de datos

La reconstrucción de pruebas debe apuntar explícitamente a `blapos_testing`. Nunca ejecutar `migrate:fresh` sobre la base de desarrollo.

```bash
php artisan migrate:fresh --database=mysql --seed
composer check:tenant-boundary
php artisan test
```

`phpunit.xml` fija `DB_DATABASE=blapos_testing`. Para comandos manuales, verificar antes la conexión resuelta y definir `DB_DATABASE=blapos_testing` en el entorno del proceso.
