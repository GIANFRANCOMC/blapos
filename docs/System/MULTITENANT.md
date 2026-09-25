# System - Arquitectura multi-tenant por subdominio

> Para secuencias ejecutables de instalación, desarrollo, producción y referencia completa de comandos, consultar la [guía unificada](../1-Instalation.md). Este documento define la arquitectura y las reglas de tenancy.

## Resumen

Blapos atiende subdominios tenant registrados, por ejemplo `demo.blapos.test`, y reserva `app.blapos.test` para la administración central del SaaS. El dominio raíz pertenece a otro proyecto y este código no lo atiende.

La arquitectura y las convenciones del panel central están documentadas en `docs/System/PLATFORM_ADMINISTRATION.md`. Su interfaz autenticada funciona como un shell Vue con endpoints JSON y navegación sin recarga completa.

Cada tenant dispone de:

- un subdominio único de un solo nivel;
- una base de datos MySQL propia;
- usuarios, sesiones, caché lógica y datos operativos aislados;
- una única empresa raíz, resuelta desde la propia BD tenant;
- almacenamiento con prefijo `tenants/{slug}` para impedir colisiones entre clientes.

No se aceptan IPs, `localhost`, el dominio raíz, hosts desconocidos ni subdominios reservados. La solicitud se rechaza antes de iniciar sesión o consultar modelos tenant.

## Conexiones

### Landlord

La conexión `landlord` contiene únicamente el registro central:

- `tenant_databases`: slug, empresa raíz esperada, nombre de BD, estado y última resolución;
- `tenant_domains`: host exacto y activo asociado al tenant;
- `tenant_audit_logs`: altas, cambios de estado, verificaciones, rechazos de host y resultados operativos.
- `platform_users`: administradores exclusivos del panel `app`; no reutiliza usuarios tenant.
- `tenant_announcements`: avisos programables y activables por cliente.

Landlord no almacena host, usuario ni contraseña de MySQL. Las credenciales pertenecen al entorno seguro del servidor y nunca deben aparecer en Git, tablas, logs o respuestas HTTP.

### Tenant

La conexión `tenant` reutiliza host, puerto y credenciales del entorno y cambia únicamente el nombre de base. `TenantConnectionManager` valida el formato del nombre y exige `TENANT_DB_PREFIX` cuando `TENANT_ENFORCE_DB_PREFIX=true`.

## Resolución HTTP

Orden de defensas:

1. `TrustProxies` interpreta proxy y HTTPS solo desde proxies permitidos.
2. `TrustHosts` acepta exclusivamente `<slug>.<TENANCY_BASE_DOMAIN>`.
3. `ResolveTenant` deriva `app.<TENANCY_BASE_DOMAIN>` hacia landlord; para cualquier otro subdominio exige un tenant registrado y activo.
4. Se activa la conexión tenant antes de sesión, autenticación y rutas.
5. `EnsureTenantSession` comprueba que la sesión pertenezca al mismo `tenant_database_id`.
6. `EnsureAuthenticatedSession` valida estado del usuario y `session_version`.
7. `SecurityHeaders` protege navegador y evita cachear HTML autenticado.

El resolver usa una clave única `tenancy:resolver:<sha256(host)>` durante `TENANCY_RESOLVER_CACHE_SECONDS`. Los comandos de alta, suspensión, reactivación y limpieza invalidan esa misma clave; no existen claves versionadas paralelas.

Los intentos contra hosts desconocidos se registran en landlord con deduplicación breve por host e IP. El registro de auditoría nunca puede convertir un rechazo seguro en un error 500.

## Configuración

```env
LANDLORD_DB_CONNECTION=landlord
LANDLORD_DB_HOST=127.0.0.1
LANDLORD_DB_PORT=3306
LANDLORD_DB_DATABASE=blapos_landlord
LANDLORD_DB_USERNAME=usuario_landlord
LANDLORD_DB_PASSWORD=secreto_externo

TENANT_DB_CONNECTION=tenant
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=usuario_runtime_tenant
TENANT_DB_PASSWORD=secreto_externo
TENANT_DB_PREFIX=blapos_tenant_
TENANT_ENFORCE_DB_PREFIX=true

TENANCY_BASE_DOMAIN=blapos.test
TENANCY_PLATFORM_SUBDOMAIN=app
TENANCY_ENFORCE_SUBDOMAINS=true
TENANCY_RESERVED_SUBDOMAINS=www,api,admin,app,mail,static,assets
TENANCY_RESOLVER_CACHE_SECONDS=60
TENANT_SESSION_COOKIE_PREFIX=blapos_tenant
PLATFORM_SESSION_COOKIE=blapos_platform_session
```

`SESSION_DOMAIN` debe permanecer vacío. Un dominio compartido, como `.blapos.test`, compartiría cookies entre clientes y está prohibido.

## Provisionamiento

Preparar landlord:

```bash
php artisan platform:install
```

En desarrollo, si no se sobrescriben las variables `PLATFORM_ADMIN_*`, el acceso inicial es:

```text
URL: http://app.blapos.test/login
Usuario: admin@app.blapos.test
Contraseña: Admin12345!
```

En producción `PLATFORM_ADMIN_PASSWORD` es obligatoria y no puede conservar la clave local predeterminada. `platform:install` se detiene si detecta esa credencial insegura. Para rotar la contraseña y revocar sesiones existentes, usar `platform:admin`.

Crear un tenant local:

```bash
php artisan tenant:create demo \
  --commercial-name="Demo Gym" \
  --legal-name="Demo Gym S.A.C." \
  --document-number=20600000001 \
  --admin-name="Administrador" \
  --admin-email=admin@demo.test \
  --admin-password="UnaClaveSegura123"
```

En producción, infraestructura debe crear previamente la BD y conceder al usuario runtime solo permisos operativos:

```bash
php artisan tenant:create cliente \
  --database=blapos_tenant_cliente \
  --skip-create-database \
  --commercial-name="Cliente" \
  --legal-name="Cliente S.A.C." \
  --document-number=20600000010 \
  --admin-email=admin@cliente.test \
  --admin-password="UnaClaveSegura123"
```

`tenant:create` ejecuta las migraciones, crea explícitamente la fila `companies` y reutiliza `SystemCatalogSyncService` y `CompanyProvisioningService`. No depende de datos insertados por una migración. Si no se usa `--skip-migrate`, la contraseña administrativa es obligatoria y debe tener al menos ocho caracteres.

## Operación

El panel `app.<TENANCY_BASE_DOMAIN>` permite crear clientes, activar, inactivar o suspender tenants, administrar sus módulos y publicar avisos. La activación de módulos actualiza `companies_sub_sections` dentro de la base aislada del cliente e invalida su caché de navegación.

Las rutas del panel son exclusivamente inglesas y están aisladas por dominio: `/login`, `/tenants`, `/tenants/{tenant}/status`, `/tenants/{tenant}/modules` y `/tenants/{tenant}/announcements`. El fallback del dominio `app` impide que una URL del panel termine resolviendo una ruta tenant.

La autenticación usa `platform_users`, cookie propia, regeneración de sesión, versión revocable, limitación por correo e IP y auditoría de accesos correctos y fallidos. No consulta ni comparte usuarios de ninguna base tenant.

```bash
php artisan tenant:list
php artisan tenant:list --status=active
php artisan tenant:health
php artisan tenant:health demo
php artisan tenant:suspend demo --force
php artisan tenant:suspend demo --activate --force
php artisan tenant:cache-clear demo
php artisan tenant:cache-clear
```

- `tenant:list` consulta landlord sin abrir las bases tenant.
- `tenant:health` prueba conexión, consulta básica, tabla `companies` y latencia.
- `tenant:suspend` y `--activate` actualizan tenant y dominio en una transacción, invalidan caché y auditan la acción.
- `tenant:cache-clear` invalida una clave o todas las claves conocidas sin limpiar la caché completa de la aplicación.

El scheduler ejecuta comandos tenant-aware que iteran tenants activos, conectan y desconectan cada BD en `try/finally`, y auditan el resultado. Actualmente cubre notificaciones de membresías, vencimiento de membresías, cierre técnico de asistencias de clientes y retención de historial.

En servidor debe configurarse un solo cron de Laravel:

```bash
* * * * * cd /ruta/al/blapos && php artisan schedule:run >> /dev/null 2>&1
```

Comandos programados:

- `notifications:send-subscriptions --limit=100`: procesa correos pendientes cada cinco minutos.
- `subscriptions:cancel-expired`: inactiva membresías vencidas cada hora.
- `attendances:close-stale-customers --limit=500`: cierra asistencias antiguas sin salida cada hora.
- `attendances:prune-customers --limit=1000`: depura historial elegible diariamente a las 03:20.

Los jobs futuros deben declarar `UseTenantConnection`; un job sin contexto tenant no debe consultar modelos operativos.

## Demostración

```bash
php artisan db:seed --class=LandlordTenantDemoSeeder --force
```

| Cliente | Subdominio | Base de datos |
| --- | --- | --- |
| Demo Gym | `demo.blapos.test` | `blapos_tenant_demo` |
| Andina Fitness | `andina.blapos.test` | `blapos_tenant_andina` |
| Fit Center | `fitcenter.blapos.test` | `blapos_tenant_fitcenter` |

## Reglas obligatorias

- Nunca aceptar `company_id` como selector de contexto desde el frontend.
- Nunca resolver un tenant solo por slug; host, dominio y estados deben coincidir.
- Nunca guardar credenciales en `tenant_databases`.
- Nunca habilitar `SESSION_DOMAIN` compartido.
- Comandos, scheduler y jobs deben activar explícitamente el tenant.
- Archivos deben resolverse mediante `TenantStoragePath` o un disco equivalente por tenant.
- Backups, restauraciones y eliminación de datos se ejecutan por BD tenant.

## Evolución de infraestructura

Estas tareas no se resuelven dentro del repositorio y deben formar parte del plan operativo del proveedor cloud:

- rotación automática de credenciales y secretos;
- backups cifrados por tenant, retención y prueba periódica de restauración;
- alertas centralizadas sobre fallos de salud, hosts rechazados y acciones landlord;
- consola landlord desplegada en un dominio administrativo separado si se necesita operación visual.

## Documentación relacionada

- [Seguridad y autenticación](SECURITY_AND_AUTH.md)
- [Instalación y aprovisionamiento](DATABASE_INSTALLATION.md)
- [Laragon y XAMPP](deployment/LOCAL_SUBDOMAINS.md)
- [AWS y DigitalOcean](deployment/PRODUCTION_SUBDOMAINS.md)
