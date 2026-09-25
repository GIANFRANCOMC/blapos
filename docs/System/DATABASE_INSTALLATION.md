# Instalación y aprovisionamiento de base de datos

> La secuencia completa de clonación, instalación, desarrollo, producción y referencia de comandos vive en [docs/1-Instalation.md](../1-Instalation.md). Este archivo conserva únicamente las reglas internas del esquema y aprovisionamiento.

## Objetivo

La creación de tablas, el catálogo global del sistema y los datos de cada organización tienen responsabilidades separadas:

1. Las migraciones crean únicamente el esquema y sus restricciones.
2. `SystemNavigationSeeder` inicializa el catálogo global de navegación en una base vacía.
3. `CompanyProvisioningService` crea los datos operativos de una organización.
4. Los comandos Artisan orquestan estos servicios sin duplicar definiciones.

Las migraciones no insertan empresas. El aprovisionador reserva el ID local `1` para la única empresa raíz del tenant y rechaza la existencia de una segunda raíz.

## Fuentes canónicas

- Esquema: `database/migrations`.
- Navegación en ejecución: tablas `menu_categories`, `sections`, `menu_groups` y `sub_sections`.
- Catálogo inicial para una base vacía: `database/seeders/SystemNavigationSeeder.php`.
- Proyección hacia organizaciones y permisos: `app/Services/System/Database/SystemCatalogSyncService.php`.
- Aprovisionamiento: `app/Services/System/Organizations/Companies/CompanyProvisioningService.php`.
- Instalación: `app/Console/Commands/InstallSystemDatabase.php`.
- Diagnóstico: `app/Console/Commands/DoctorSystemDatabase.php`.

Las migraciones antiguas que insertaban empresas, menús, tributos, pagos o configuraciones fueron retiradas. Estos datos no deben volver a distribuirse entre migraciones correctivas.

## Instalación desde una base vacía

Después de crear una base vacía y configurar `.env`, ejecutar:

```bash
php artisan system:install \
  --slug=mi-empresa \
  --commercial-name="Mi empresa" \
  --legal-name="MI EMPRESA S.A.C." \
  --document-number=20123456789 \
  --admin-name="Administrador" \
  --admin-email=admin@miempresa.com \
  --admin-password="UnaClaveSegura123" \
  --no-interaction
```

El comando ejecuta migraciones pendientes y crea de forma idempotente:

- organización y referencias maestras;
- administrador y perfil de acceso total;
- todas las opciones del catálogo activas en `companies_sub_sections` y `role_sub_sections`;
- tipos de identidad, comprobantes y moneda;
- configuraciones empresariales;
- tributos, métodos y variantes de pago;
- modalidades de entrega;
- sede principal, almacén y caja;
- series de comprobantes;
- cliente genérico;
- categorías, secciones, grupos, opciones de menú y permisos.

La contraseña es obligatoria en modo no interactivo y debe tener al menos ocho caracteres.

### Reconstrucción explícita

`--fresh` elimina todas las tablas de la conexión activa antes de instalarlas. Requiere confirmación interactiva:

```bash
php artisan system:install --fresh [opciones de organización]
```

No utilizar `--fresh` en una base con información que deba conservarse.

## Comandos operativos

### Sincronizar catálogo

```bash
php artisan system:sync
```

Sincroniza categorías, secciones, grupos, opciones, asignaciones empresariales y permisos de perfiles con acceso total. Es seguro ejecutarlo más de una vez.

### Aprovisionar una organización existente

```bash
php artisan company:enable
php artisan company:enable --skip-modules
```

Completa datos maestros y operativos faltantes sin duplicar registros. La variante `--skip-modules` evita crear o actualizar el perfil administrativo.

### Diagnosticar integridad

```bash
php artisan system:doctor
```

Comprueba tablas obligatorias, rutas del menú, referencias de empresa, perfil, sede, almacén y caja. Debe ejecutarse después de una instalación o despliegue con cambios de catálogo.

## Creación de tenants

`tenant:create` utiliza el mismo aprovisionador. Ya no espera que una migración haya creado previamente una empresa:

```bash
php artisan tenant:create demo \
  --commercial-name="Demo Gym" \
  --legal-name="Demo Gym S.A.C." \
  --document-number=20600000001 \
  --admin-name="Administrador" \
  --admin-email=admin@demo.test \
  --admin-password="UnaClaveSegura123"
```

Si se ejecutan migraciones, el administrador es obligatorio. `--skip-migrate` conserva su significado y no aprovisiona datos tenant.

## Idempotencia

Los servicios usan claves naturales y `updateOrInsert`. Una segunda ejecución debe mantener una sola organización, sede principal, almacén, caja, usuario administrativo y asignación de cada opción.

No usar identificadores aleatorios como criterio de búsqueda de datos base. Pueden generarse códigos internos, pero la coincidencia idempotente debe basarse en empresa y una clave estable.

## Política de migraciones

- Una migración define esquema, claves foráneas, índices y transformación histórica estrictamente necesaria.
- Los catálogos vigentes y defaults organizacionales pertenecen a servicios de sincronización o aprovisionamiento.
- Durante la etapa reiniciable se modifica la migración base propietaria de la tabla.
- No crear una migración para añadir una opción de menú, un medio de pago o una configuración por empresa.
- Cuando existan datos persistentes, las transformaciones históricas deben ser explícitas, reversibles cuando sea razonable y no deben inventar información.

## Validación automatizada

Flujo mínimo antes de entregar cambios:

```bash
php artisan system:install [opciones] --no-interaction
php artisan system:doctor
php artisan system:sync
php artisan company:enable
php artisan system:doctor
php artisan test --testsuite=Unit
```

Para comprobar una instalación real debe utilizarse una base temporal vacía y eliminarse al finalizar.
