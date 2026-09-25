# Instalación y operación de Blapos

Guía única para instalar, desarrollar y desplegar Blapos.

## 1. Qué administra cada base

| Base | Contenido | Comando principal |
|---|---|---|
| Landlord | Tenants, dominios, administradores de plataforma, avisos y auditoría central | `php artisan platform:install` |
| Tenant | Una empresa raíz y todos sus datos independientes | `php artisan tenant:create <slug>` |

Regla: nunca ejecutar migraciones tenant sobre landlord ni migraciones landlord sobre un tenant.

## 2. Requisitos

- PHP 8.1 o superior.
- Composer 2.
- Node.js LTS y npm.
- MySQL 8 recomendado.
- Apache o Nginx apuntando a `public`.

Validación rápida:

```bash
php --version
composer --version
composer check-platform-reqs
node --version
npm --version
mysql --version
```

## 3. Desarrollo y producción

| Tarea | Desarrollo | Producción |
|---|---|---|
| Dependencias PHP | `composer install` | `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` |
| Dependencias frontend | `npm ci` | `npm ci` durante la compilación |
| Assets | `npm run dev` | `npm run build` |
| Entorno | `.env` local; `APP_DEBUG=true` | Secretos externos; `APP_DEBUG=false` |
| Base landlord | `php artisan platform:install` | `php artisan platform:install --no-interaction` |
| Tenant | `php artisan tenant:create <slug>` | Panel central o el mismo comando |
| Caché | `php artisan optimize:clear` | `php artisan optimize` |
| Cola | `php artisan queue:work --tries=3` | Supervisor/systemd |
| Scheduler | `php artisan schedule:work` | Cron cada minuto |
| Pruebas | `php artisan test` | Ejecutar en CI antes de desplegar |

## 4. Primera instalación

### 4.1 Clonar

Linux/macOS:

```bash
git clone <URL_DEL_REPOSITORIO> blapos
cd blapos
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Windows PowerShell:

```powershell
git clone <URL_DEL_REPOSITORIO> blapos
Set-Location blapos
composer install
npm ci
Copy-Item .env.example .env
php artisan key:generate
```

No usar `composer update` ni `npm update` durante una instalación normal.

### 4.2 Configurar `.env`

Configuración local mínima:

```dotenv
APP_NAME=Blapos
APP_ENV=local
APP_DEBUG=true
APP_URL=http://demo.blapos.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blapos
DB_USERNAME=root
DB_PASSWORD=

LANDLORD_DB_CONNECTION=landlord
LANDLORD_DB_DATABASE=blapos_landlord
TENANT_DB_CONNECTION=tenant
TENANT_DB_PREFIX=blapos_tenant_
TENANT_ENFORCE_DB_PREFIX=true

TENANCY_BASE_DOMAIN=blapos.test
TENANCY_PLATFORM_SUBDOMAIN=app
TENANCY_ENFORCE_SUBDOMAINS=true
SESSION_DOMAIN=
```

Producción requiere además:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- HTTPS y `SESSION_SECURE_COOKIE=true`.
- credenciales MySQL exclusivas.
- `PLATFORM_ADMIN_NAME`, `PLATFORM_ADMIN_EMAIL` y `PLATFORM_ADMIN_PASSWORD` seguros.
- secretos fuera de Git, documentación, comandos y logs.

### 4.3 Instalar landlord

Ejecutar:

```bash
php artisan platform:install
```

Este único comando:

1. crea `LANDLORD_DB_DATABASE` cuando no existe;
2. ejecuta las migraciones de `database/migrations/landlord`;
3. ejecuta `LandlordPlatformSeeder`;
4. crea o conserva el administrador central configurado.

El usuario MySQL necesita permiso `CREATE DATABASE` únicamente para crear una base inexistente. Si un proveedor administrado no entrega ese permiso, la base debe crearse una sola vez desde su panel; después `platform:install` continúa normalmente.

Este comando alternativo solo migra las tablas y exige que la base ya exista:

```bash
php artisan migrate --database=landlord --path=database/migrations/landlord
```

Por tanto, usar `platform:install` en instalaciones nuevas y `migrate` únicamente para mantenimiento explícito del esquema.

Para crear o rotar otro administrador sin mostrar la contraseña en el historial:

```bash
php artisan platform:admin admin@app.blapos.test
```

### 4.4 Crear el primer tenant

```bash
php artisan tenant:create demo --commercial-name="Empresa demo" --legal-name="EMPRESA DEMO S.A.C." --document-number=20600000001 --admin-name="Administrador"  --admin-email=admin@demo.test
```

El comando solicita la contraseña de forma oculta y realiza el flujo completo:

1. crea la base tenant;
2. registra tenant y dominio en landlord;
3. ejecuta migraciones y seeders tenant;
4. crea la única empresa raíz, permisos, sede, almacén, caja y administrador.

También puede crearse el cliente desde `app.blapos.test`. No ejecutar ambos flujos para la misma alta.

### 4.5 Configurar dominios locales

El proyecto debe estar en:

```text
C:\laragon\www\blapos
```

Agregar al archivo `hosts`:

```text
127.0.0.1 app.blapos.test
127.0.0.1 demo.blapos.test
```

El VirtualHost debe apuntar a `C:/laragon/www/blapos/public` y aceptar `*.blapos.test`.

## 5. Uso diario en desarrollo

Terminal 1:

```bash
npm run dev
```

Terminal 2:

```bash
php artisan queue:work --tries=3
```

Terminal 3:

```bash
php artisan schedule:work
```

Limpiar cachés después de cambiar configuración, rutas o vistas:

```bash
php artisan optimize:clear
```

## 6. Despliegue

### Primera instalación

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan key:generate --force
php artisan platform:install --no-interaction
php artisan storage:link
php artisan optimize
```

Antes de ejecutar `platform:install`, definir credenciales seguras para el administrador central.

### Actualización posterior

```bash
php artisan down --retry=60
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --database=landlord --path=database/migrations/landlord --force
php artisan queue:restart
php artisan optimize
php artisan up
```

Después, migrar cada base tenant de forma explícita. El proyecto no tiene un comando global `tenant:migrate-all`.

Linux/macOS:

```bash
TENANT_DB_DATABASE=blapos_tenant_demo \
php artisan migrate --database=tenant --path=database/migrations --force
```

Windows PowerShell:

```powershell
$env:TENANT_DB_DATABASE = "blapos_tenant_demo"
php artisan migrate --database=tenant --path=database/migrations --force
Remove-Item Env:TENANT_DB_DATABASE
```

Si la configuración estaba cacheada, ejecutar antes `php artisan optimize:clear` para que Laravel lea la variable temporal.

## 7. Comandos del proyecto

### Plataforma central

| Comando | Uso |
|---|---|
| `php artisan platform:install` | Crea la base landlord si falta, migra y crea el administrador inicial. |
| `php artisan platform:admin <email>` | Crea o actualiza un administrador central. |

### Tenants

| Comando | Uso |
|---|---|
| `php artisan tenant:create <slug>` | Aprovisiona un tenant completo. |
| `php artisan tenant:list` | Lista tenants registrados. |
| `php artisan tenant:health` | Revisa conexión y estado. |
| `php artisan tenant:suspend <slug> --force` | Suspende un tenant. |
| `php artisan tenant:suspend <slug> --activate --force` | Reactiva un tenant. |
| `php artisan tenant:cache-clear <slug>` | Limpia la caché del tenant. |

Consultar opciones sin adivinar parámetros:

```bash
php artisan help tenant:create
php artisan help tenant:suspend
```

### Esquema y organización

| Comando | Uso |
|---|---|
| `php artisan system:install` | Instala una base no tenant vacía. |
| `php artisan system:sync` | Sincroniza catálogos y estructura System. |
| `php artisan system:doctor` | Diagnostica la instalación. |
| `php artisan company:enable <company>` | Habilita una empresa según las opciones del comando. |

### Procesos programados

| Comando | Uso |
|---|---|
| `php artisan notifications:send-subscriptions` | Envía notificaciones de suscripciones. |
| `php artisan subscriptions:cancel-expired` | Cancela suscripciones vencidas. |
| `php artisan attendances:close-stale-customers` | Cierra asistencias antiguas. |
| `php artisan attendances:prune-customers` | Depura asistencias según retención. |

No ejecutar manualmente estos procesos de manera habitual; el scheduler ya los coordina.

### Calidad y frontend

| Comando | Uso |
|---|---|
| `composer format:php` | Formatea PHP según la convención del proyecto. |
| `composer format:php-check` | Verifica formato sin corregir. |
| `composer check:php-syntax` | Comprueba sintaxis PHP. |
| `npm run dev` | Inicia Vite. |
| `npm run build` | Genera assets de producción. |
| `npm run build:css:system` | Compila el CSS de System. |

### Laravel operativo

| Comando | Uso |
|---|---|
| `php artisan about` | Resume entorno y versiones. |
| `php artisan route:list` | Lista rutas. |
| `php artisan migrate:status --database=<conexión>` | Muestra migraciones aplicadas. |
| `php artisan optimize:clear` | Limpia cachés. |
| `php artisan optimize` | Construye cachés de producción. |
| `php artisan queue:work` | Procesa colas. |
| `php artisan queue:restart` | Reinicia workers después de desplegar. |
| `php artisan schedule:list` | Lista tareas programadas. |
| `php artisan schedule:run` | Ejecuta el ciclo actual del scheduler. |
| `php artisan storage:link` | Crea el enlace público de storage. |
| `php artisan test` | Ejecuta pruebas. |

## 8. Shell, Tinker y MySQL

### Artisan y shell

```bash
php artisan list
php artisan help <comando>
php artisan tinker
```

No existe un shell Laravel independiente: Artisan se ejecuta desde la terminal y `tinker` permite inspeccionar la aplicación.

### Verificar landlord en Tinker

```php
DB::connection("landlord")->getDatabaseName();
DB::connection("landlord")->table("tenant_databases")->count();
```

### Verificar un tenant en Tinker

```php
config(["database.connections.tenant.database" => "blapos_tenant_demo"]);
DB::purge("tenant");
DB::connection("tenant")->getDatabaseName();
```

### Cliente MySQL

El cliente es útil para diagnóstico, no para la instalación normal:

```bash
mysql -u <usuario> -p -h <host>
```

```sql
SHOW DATABASES;
USE blapos_landlord;
SHOW TABLES;
```

## 9. Colas y scheduler en producción

Cron:

```cron
* * * * * cd /var/www/blapos && php artisan schedule:run >> /dev/null 2>&1
```

Supervisor:

```ini
[program:blapos-worker]
command=php /var/www/blapos/artisan queue:work --sleep=3 --tries=3 --timeout=120
directory=/var/www/blapos
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/blapos-worker.log
```

## 10. Validación final

```bash
php artisan about
php artisan migrate:status --database=landlord
php artisan tenant:list
php artisan tenant:health
php artisan schedule:list
composer format:php-check
composer check:php-syntax
php artisan test
npm run build
```

Verificar además:

- `app.<dominio>` abre la administración central.
- cada tenant abre solo su subdominio.
- una sesión no se comparte entre tenants.
- `storage` y `bootstrap/cache` tienen permisos de escritura.
- cola, scheduler, HTTPS, logs y respaldos están activos en producción.

## 11. Operaciones destructivas

No ejecutar en producción sin respaldo y autorización:

```bash
php artisan migrate:fresh
php artisan migrate:reset
php artisan db:wipe
php artisan tenant:create <slug> --force
```

## Documentación relacionada

- [Arquitectura multi-tenant](System/MULTITENANT.md)
- [Reglas internas de base de datos](System/DATABASE_INSTALLATION.md)
- [Subdominios locales](System/deployment/LOCAL_SUBDOMAINS.md)
- [Producción por subdominios](System/deployment/PRODUCTION_SUBDOMAINS.md)
- [Pruebas](System/TESTING.md)
