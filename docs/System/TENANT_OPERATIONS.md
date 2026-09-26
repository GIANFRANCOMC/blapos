# Gestión operativa de tenants

## Regla principal

Cada tenant contiene exactamente una empresa raíz. La base de datos física es la frontera de aislamiento de los datos operativos; `company_id` no vuelve a dividir ventas, compras, inventario, clientes, caja u operaciones dentro de esa base.

Los únicos usos estructurales permitidos de `company_id` son `branches`, `companies_sub_sections`, `company_settings` y `company_socials_media`.

## Controles implementados

| # | Control | Implementación vigente |
|---:|---|---|
| 1 | API operativa sin selector de empresa | Controladores y servicios operativos no reciben `company_id` ni `$companyId`. La prueba de arquitectura rechaza cualquier reintroducción. |
| 2 | Contextos separados | `TenantContext`, `TenantCompanyContext` y `BranchContext` resuelven tenant, empresa raíz y sucursal sin mezclar responsabilidades. |
| 3 | Acceso centralizado por sucursal | `BranchAccessService` valida selección, disponibilidad y alcance del usuario antes de establecer `BranchContext`. |
| 4 | Una empresa raíz | El modelo y el aprovisionamiento impiden crear una segunda fila raíz dentro de la base tenant. |
| 5 | Aprovisionamiento recuperable | El proceso usa bloqueo, pasos idempotentes, diagnóstico final, registro del fallo y reintento seguro. |
| 6 | Estados operativos del tenant | Solo `active` admite tráfico. También existen `provisioning`, `inactive`, `suspended`, `provisioning_failed` y `maintenance`. |
| 7 | Pruebas de aislamiento | Se inspeccionan migraciones, esquema MySQL, claves foráneas, índices, contexto de sucursal, firmas de servicios y ausencia de selectores en HTTP/frontend. |
| 8 | Jobs conscientes del tenant | Todo job en cola implementa `TenantAwareJob`, transporta el tenant landlord y restablece/libera el contexto en cada ejecución. |
| 9 | Almacenamiento por UUID | `TenantStoragePath` guarda archivos bajo el UUID público, nunca bajo el ID local de empresa o un slug mutable. |
| 10 | Respaldo y restauración | Comandos dedicados aplican retención, evitan secretos en argumentos y usan `maintenance` durante una restauración. |
| 11 | Observabilidad correlacionada | Logs y auditorías comparten solicitud, tenant, base, usuario y sucursal autorizada. |
| 12 | Índices orientados al dominio | Los índices operativos comienzan por sucursal, almacén, estado, fecha o relación funcional; no por `company_id`. |

Los enlaces firmados para comprobantes tampoco incluyen el ID local de la empresa. El dominio resuelve el tenant y la empresa raíz se obtiene desde su conexión activa.

## Contextos

| Contexto | Responsabilidad |
|---|---|
| `TenantContext` | Tenant landlord, conexión, UUID público y namespace de caché. |
| `TenantCompanyContext` | Perfil y configuración de la única empresa raíz. |
| `BranchContext` | Usuario operativo, sucursales permitidas y sucursal seleccionada. |

Los controladores no reciben `company_id` ni `companyId`. Los catálogos maestros, parámetros iniciales, códigos internos y cachés se resuelven desde el contexto tenant.

## Ciclo de vida

- `provisioning`: creación en curso.
- `active`: único estado que admite tráfico tenant.
- `inactive`: desactivación administrativa.
- `suspended`: bloqueo comercial o de seguridad.
- `provisioning_failed`: creación incompleta y reintentable.
- `maintenance`: acceso detenido para restauración o mantenimiento.

El aprovisionamiento crea la base, migra, sincroniza catálogos, crea empresa raíz, sucursal, almacén, caja y administrador, ejecuta `system:doctor` y recién entonces activa el tenant. Un fallo conserva el motivo y permite reintentar el proceso idempotente.

## Sucursales

`BranchAccessService` y `BranchContext` son la entrada común para validar sucursales. Un registro existente no implica autorización para el usuario. Almacenes y cajas heredan primero la restricción de sucursal y después su restricción específica.

## Jobs y caché

Todo job tenant que implemente `ShouldQueue` debe implementar `TenantAwareJob`, usar `InteractsWithTenant` y transportar `tenantDatabaseId`, nunca `company_id`. `UseTenantConnection` abre y libera conexión, empresa, sucursal, caché en memoria y contexto de log.

Las claves de caché comienzan con el namespace del UUID público del tenant. Las configuraciones por usuario agregan únicamente el ID del usuario.

## Archivos

Todos los archivos se almacenan debajo de:

```text
tenants/{tenant_public_uuid}/
```

Las rutas se construyen con `TenantStoragePath`. No se utiliza slug ni `company_id` porque no son una frontera de almacenamiento estable.

## Observabilidad

Cada solicitud comparte con logs y auditoría `request_id`, `tenant_id`, `tenant_domain`, `database_name`, `user_id` y la `branch_id` autorizada. `X-Request-ID` se conserva si es válido o se genera como UUID y se devuelve en la respuesta.

## Respaldo y restauración

```bash
php artisan tenant:backup cliente
php artisan tenant:restore cliente cliente-20260925-120000.sql --force
```

Los respaldos viven en `tenants/{tenant_public_uuid}/backups`, aplican retención y no colocan la contraseña MySQL en los argumentos del proceso. Durante una restauración el tenant permanece en `maintenance`; un fallo conserva ese estado y registra el motivo.

```dotenv
TENANT_BACKUP_DISK=local
TENANT_BACKUP_RETENTION=10
TENANT_BACKUP_TIMEOUT=900
MYSQL_DUMP_BINARY=mysqldump
MYSQL_CLIENT_BINARY=mysql
```

## Índices

Los índices siguen el acceso real dentro del tenant: `branch_id`, `warehouse_id`, estado, fecha e identificadores relacionados. No se antepone `company_id` a índices operativos. Ventas, compras, inventario, auditoría, autenticación, proveedores y preferencias poseen índices para sus listados y trazabilidad principales.

## Verificación

```bash
composer check:tenant-boundary
php artisan test tests/Unit/Architecture/TenantRuntimeArchitectureTest.php
php artisan test tests/Feature/BranchContextTest.php
php artisan test
```
