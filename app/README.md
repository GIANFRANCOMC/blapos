# Capa de aplicación

`app` contiene el backend de Blapos. La referencia funcional vive en `docs`; este archivo resume únicamente las reglas estructurales del código PHP.

## Flujo recomendado

```text
Route -> Middleware -> FormRequest -> Controller -> Service -> Model
```

- **Middleware:** resuelve tenant, sesión, permiso por acción y alcance operativo.
- **FormRequest:** normaliza y valida payloads con mensajes legibles.
- **Controller:** coordina HTTP; no contiene reglas de negocio ni consultas extensas.
- **Service:** recibe `companyId` y `userId` explícitos para toda escritura.
- **Model:** define relaciones, casts y accessors tolerantes a selecciones parciales.

## Aislamiento

- La conexión tenant se activa antes de sesión y autenticación.
- Nunca se acepta `company_id` del frontend como fuente de autoridad.
- Toda consulta operativa limita por empresa y, cuando corresponde, por sucursal, caja o almacén.
- Los jobs usan `UseTenantConnection`; los comandos iteran tenants de forma explícita.
- Archivos privados se resuelven mediante `TenantStoragePath` o un disco equivalente por tenant.

## Convenciones

- PHP 8.1+, `declare(strict_types=1)` en archivos nuevos o intervenidos.
- Tipos de retorno y parámetros explícitos cuando el contrato lo permita.
- `BaseController` concentra respuestas, usuario, empresa, paginación y filtros.
- `BaseConfigService` concentra caché de `initParams`.
- `CompanyReferenceDataService` expone consultas con nombres de intención; no usar variantes genéricas `getAll($type, ...)`.
- `InitParamsCacheInvalidationService` invalida dependencias; no crear claves versionadas paralelas.
- Un endpoint no implementado no debe publicarse ni conservar métodos REST vacíos.
- Los accessors de `$appends` leen atributos con valor alternativo para soportar `select(...)` parciales.

## Seguridad

- `module.permission` valida módulo y acción.
- `resource.scope` aplica alcance de sucursal, caja y almacén.
- Cambiar contraseña o inactivar un usuario revoca tokens y sesiones mediante `session_version`.
- Los eventos de autenticación no almacenan contraseñas ni IDs de sesión reutilizables.
- Guest usa modelos y servicios públicos con selección explícita de columnas.

## Documentación

- Arquitectura: `docs/backend/arquitectura/ARCHITECTURE.md`
- Reglas transversales: `docs/backend/desarrollo/GENERALIDADES.md`
- Guía System: `docs/backend/system/DEVELOPMENT_GUIDE.md`
- Seguridad: `docs/backend/seguridad/SECURITY_AND_AUTH.md`
- Multi-tenant: `docs/backend/tenancy/MULTITENANT.md`
- Tablas: `docs/backend/datos/system/TABLES.md`
- Criterios visuales: `docs/frontend/fundamentos/ESTANDARES_UI.md` y documentación del módulo afectado.

Las pruebas PHP se crean o ejecutan cuando el usuario las solicita expresamente. Las validaciones rutinarias de esta fase son sintaxis, carga de rutas y consistencia documental.
