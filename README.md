# Blapos

Plataforma multiempresa para ventas, POS, clientes, membresías, asistencias, catálogo, inventario, compras, caja, activos, biometría, restaurantes y servicios en curso.

## Arquitectura

- Laravel 10 y PHP 8.1+
- Vue 3 y Vite
- MySQL con una base landlord y una base independiente por tenant
- Acceso exclusivo mediante subdominios registrados
- `company_id` dentro de cada tenant para empresa raíz y subcompañías
- Permisos por módulo + acción y alcances de sucursal, caja y almacén

## Instalación y operación

La fuente canónica para clonar, configurar, desarrollar, desplegar y operar landlord y tenants es la [guía unificada de instalación](docs/backend/despliegue/INSTALACION.md). Incluye la matriz Desarrollo/Producción, comandos Artisan propios, shell, Tinker, MySQL, colas, scheduler, pruebas y operaciones destructivas.

## Documentación

Empieza en [docs/backend/proyecto/README.md](docs/backend/proyecto/README.md). Las referencias principales son:

- [Contexto del proyecto](docs/backend/proyecto/PROJECT_CONTEXT.md)
- [Frontend y sistema de diseño](docs/frontend/README.md)
- [Backend y reglas técnicas](docs/backend/README.md)
- [Generalidades](docs/backend/desarrollo/GENERALIDADES.md)
- [Arquitectura System](docs/backend/system/ARCHITECTURE.md)
- [Multi-tenant](docs/backend/tenancy/MULTITENANT.md)
- [Instalación y aprovisionamiento](docs/backend/datos/DATABASE_INSTALLATION.md)
- [Instalación y comandos unificados](docs/backend/despliegue/INSTALACION.md)
- [Pruebas automatizadas](docs/backend/desarrollo/TESTING.md)
- [Seguridad y autenticación](docs/backend/seguridad/SECURITY_AND_AUTH.md)
- [Tablas System](docs/backend/datos/system/TABLES.md)
- [Portal Guest](docs/backend/guest/README.md)
- [Auditoría y plan visual](docs/frontend/calidad/AUDIT.md)

## Desarrollo

- No ejecutes Blapos desde el dominio raíz; usa un subdominio registrado.
- No compartas `SESSION_DOMAIN` entre tenants.
- No almacenes credenciales tenant en la base landlord.
- No confíes en `company_id` enviado por frontend.
- Actualiza el módulo y `TABLES.md` cuando cambie una regla o estructura.
- Las pruebas PHP se crean o ejecutan cuando se solicitan expresamente para el flujo trabajado.
