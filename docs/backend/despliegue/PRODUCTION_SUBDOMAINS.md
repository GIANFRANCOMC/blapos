# Producción en AWS o DigitalOcean

> La secuencia canónica de despliegue y la matriz Desarrollo/Producción están en [docs/backend/despliegue/INSTALACION.md](INSTALACION.md). Este archivo conserva únicamente la topología e infraestructura por subdominios.

## Topología recomendada

```text
Internet
  -> DNS wildcard *.app.ejemplo.com
  -> TLS / Load Balancer
  -> Nginx + PHP-FPM (red privada)
  -> Redis privado (sesiones y caché)
  -> MySQL privado
       - blapos_landlord
       - blapos_tenant_cliente_a
       - blapos_tenant_cliente_b
```

El registro del dominio raíz `ejemplo.com` apunta al proyecto principal. Solo `*.app.ejemplo.com` apunta a Blapos. El certificado wildcard cubre un nivel: `*.app.ejemplo.com` cubre `cliente.app.ejemplo.com`, no niveles adicionales.

## Nginx

Usar [nginx-subdomains.conf.example](nginx-subdomains.conf.example) y reemplazar dominio, ruta y socket PHP. El bloque default debe rechazar hosts no reconocidos. `/up` responde desde Nginx para que el balanceador no llegue a Laravel con un Host basado en IP.

## Variables productivas mínimas

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cliente.app.ejemplo.com
LOG_LEVEL=warning

TENANCY_BASE_DOMAIN=app.ejemplo.com
TENANCY_PLATFORM_SUBDOMAIN=app
TENANCY_ENFORCE_SUBDOMAINS=true
TENANCY_RESERVED_SUBDOMAINS=www,api,admin,app,mail,static,assets
TENANCY_RESOLVER_CACHE_SECONDS=60
TENANT_ENFORCE_DB_PREFIX=true
PLATFORM_ADMIN_NAME="Administrador SaaS"
PLATFORM_ADMIN_EMAIL=admin@ejemplo.com
PLATFORM_ADMIN_PASSWORD=una-clave-unica-desde-el-gestor-de-secretos

SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
SESSION_SAME_SITE=lax

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
TRUSTED_PROXIES=10.0.0.0/8
```

No copiar contraseñas en scripts, imágenes, AMIs, repositorios o documentación. Inyectarlas durante el despliegue.

En PHP establecer `expose_php=Off`; en Nginx usar `server_tokens off`. Esto reduce información de versión expuesta, aunque no reemplaza actualizaciones de seguridad.

## AWS

1. Crear VPC con ALB público y servidores/RDS/Redis en subredes privadas.
2. Emitir en ACM un certificado para `*.app.ejemplo.com` y asociarlo al listener HTTPS del ALB.
3. En Route 53 crear un alias wildcard hacia el ALB. El dominio raíz conserva su destino independiente.
4. Permitir al security group de los servidores solo tráfico desde el security group del ALB.
5. Permitir a RDS y Redis solo tráfico desde los servidores de aplicación.
6. Guardar secretos en AWS Secrets Manager y entregarlos al proceso con el mínimo permiso IAM.
7. Configurar el health check del target group en `/up`.
8. Usar un proceso administrativo para crear la BD; ejecutar `tenant:create --skip-create-database` con credenciales temporales de migración.

Referencias oficiales: [Route 53 hacia ELB](https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/routing-to-elb-load-balancer.html), [seguridad de RDS](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/CHAP_BestPractices.Security.html) y [Secrets Manager](https://docs.aws.amazon.com/secretsmanager/latest/userguide/best-practices.html).

## DigitalOcean

1. Crear un Load Balancer HTTPS y Droplets privados o App Platform según la operación elegida.
2. Crear el registro wildcard `*.app` hacia el balanceador; el dominio raíz conserva su proyecto.
3. Asociar certificado wildcard y health check `/up`.
4. Aplicar Cloud Firewall: el Droplet acepta HTTP solo desde el Load Balancer y SSH solo desde IP administrativa/VPN.
5. En Managed MySQL agregar únicamente Droplets o tags de aplicación como trusted sources y exigir SSL.
6. Mantener Redis y MySQL fuera de exposición pública.
7. Crear bases con una identidad administrativa separada; PHP-FPM utiliza un usuario runtime sin `CREATE/DROP DATABASE`.

Referencias oficiales: [DNS wildcard](https://docs.digitalocean.com/products/networking/dns/how-to/manage-records/), [Load Balancers](https://docs.digitalocean.com/products/networking/load-balancers/how-to/), [Cloud Firewalls](https://docs.digitalocean.com/products/networking/firewalls/how-to/configure-rules/) y [seguridad de MySQL](https://docs.digitalocean.com/products/databases/mysql/how-to/secure/).

## Despliegue

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan platform:install
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

No ejecutar migraciones tenant indiscriminadamente desde una petición web. Debe existir un despliegue controlado que recorra tenants, registre resultado y permita reintento.

La creación controlada de cada tenant debe proporcionar `--admin-email` y `--admin-password`, además de `--skip-create-database` cuando infraestructura haya preparado la base. El flujo completo está en [Instalación y aprovisionamiento](../datos/DATABASE_INSTALLATION.md).

## Operación

- Backups: landlord frecuente y cada tenant por separado, cifrados y con restauración probada.
- Observabilidad: etiquetar logs y métricas con tenant ID, nunca con contraseñas o tokens.
- Escalado: Redis central para sesiones/caché y workers tenant-aware.
- Suspensión: estado inactive/suspended, invalidación inmediata de caché y revocación de sesiones.
- Incidentes: rotar secretos, reiniciar workers, invalidar sesiones y revisar auditoría por tenant.
