# 27 - Rubro y módulos

## Propósito

Permite aplicar un set base de funciones según el rubro y personalizar después los módulos habilitados para cada empresa. La fuente del catálogo sigue siendo `sub_sections`; la habilitación tenant se conserva en `companies_sub_sections`.

## Reglas

- Los rubros pertenecen a una empresa y sus sets usan `business_industry_module_sets`.
- Aplicar un rubro reemplaza la selección vigente por sus módulos predeterminados.
- La personalización manual desactiva primero el catálogo empresarial y activa la selección recibida.
- Los accesos esenciales `workspace.index`, `home.index`, `account.index` y `business_profile.index` permanecen activos para evitar que la empresa pierda navegación o administración.
- Las operaciones son transaccionales e invalidan la caché de `CompanySectionService`.
- Los registros faltantes en `companies_sub_sections` se crean mediante `updateOrInsert`, por lo que la personalización también funciona en tenants recién aprovisionados.
- Los rubros y sus sets se crean de forma idempotente desde `CompanyProvisioningService::enable()`, después de existir la empresa y el catálogo de navegación.

## Interfaz y rutas

- Página: `business_profile.index` (`/business_profile`).
- Catálogos y selección: `business_profile.initParams`.
- Aplicar rubro: `business_profile.apply`.
- Guardar personalización: `business_profile.modules.update`.

La pantalla agrupa las funciones por mundo, muestra el conteo activo y permite activar todas o limpiar solo las opcionales.

## Archivos principales

- `app/Http/Controllers/System/Organizations/BusinessProfileController.php`
- `app/Services/System/Organizations/BusinessProfileService.php`
- `routes/System/Organizations/BusinessProfile.php`
- `resources/js/System/Pages/Organizations/business_profile/main.vue`
- `database/migrations/2026_07_14_000001_create_expenses_industries_and_quotations_tables.php`
