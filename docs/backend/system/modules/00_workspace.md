# Inicio

## Propósito

`Inicio` presenta las funciones más utilizadas por cada usuario y completa hasta seis accesos con otras opciones permitidas. No muestra un historial de páginas recientes.

La sección «¿Qué quiero hacer ahora?» muestra dos tarjetas por fila en escritorio. Cada tarjeta incluye el nombre, la descripción del submódulo almacenada en la base de datos y un icono de acceso. El contador de visitas ordena las primeras opciones, pero no se muestra en la interfaz.

Si faltan acciones habituales, se añaden funciones habilitadas para la empresa y el perfil sin repetir las seleccionadas. La pantalla saluda al usuario y mantiene visible un panel con el logo, la identificación y el contacto de la empresa. En escritorio el panel queda a la derecha; en pantallas estrechas aparece antes de los accesos.

Debajo de «¿Qué quiero hacer ahora?» se muestra una ayuda breve con enlaces de correo y teléfono de Blapos. Los datos no pertenecen al tenant: se leen de `landlord.platform_settings` mediante `PlatformSupportService`, al igual que en el inicio de sesión y las páginas de error.

La autenticación de System lleva a `/home` (`home.index`). Si Inicio es la única página visible y permitida de su sección, su enlace directo ocupa la parte superior del riel de mundos. Cuando la sección reúne otras páginas visibles y permitidas, el acceso se encuentra en el panel que abre ese mundo desde el riel. `/workspace` redirige a `/home` dentro de la sesión autenticada. [Reglas de navegación](../../../frontend/patrones/TENANT_NAVIGATION.md#modelo-visual).

## Persistencia

La tabla `user_navigation_metrics` utiliza una fila agregada por usuario y `sub_section`:

| Campo | Uso |
| --- | --- |
| `user_id` | Propietario de la preferencia. |
| `sub_section_id` | Página del catálogo, sin almacenar URLs libres. |
| `visit_count` | Total acumulado de visitas. |

La clave única `user_id + sub_section_id` impide duplicar registros por visita. No se almacenan timestamps porque esta funcionalidad no requiere auditoría temporal.

## Flujo

1. Una petición `GET` HTML supera autenticación, permisos y alcance operativo.
2. `TrackUserNavigation` espera una respuesta exitosa.
3. `UserNavigationService` resuelve la ruta contra el catálogo activo de `sub_sections`.
4. Dentro de una transacción incrementa `visit_count`.
5. Inicio obtiene los más usados exclusivamente entre las páginas permitidas por `CompanySectionService`.

La ficha de Inicio resuelve la empresa raíz dentro de `CompanyCardDataService` por medio de `TenantCompanyContext`. `GET /home/data` (`home.data`) entrega los accesos, la ficha, el isotipo y los canales de contacto. `GET /workspace/data` conserva el contrato JSON para clientes anteriores.

La propia ruta `home.index`, los endpoints AJAX, las respuestas JSON y los errores quedan fuera del contador. El acceso conserva el `sub_sections.id` 11.

## Archivos

- `app/Http/Controllers/System/Essentials/WorkspaceController.php`
- `app/Http/Middleware/TrackUserNavigation.php`
- `app/Models/System/Organizations/UserNavigationMetric.php`
- `app/Services/System/Essentials/UserNavigationService.php`
- `routes/System/Essentials/Workspace.php`
- `resources/views/System/general/Essentials/workspace/main.blade.php`
- `resources/css/System/fg/modules/workspace.css`
- `database/migrations/2024_01_11_223124_create_init_masters_table.php`
- `database/seeders/SystemNavigationSeeder.php`

## Validaciones cubiertas

- Una visita repetida incrementa el contador sin crear otra fila.
- La tabla y el modelo no contienen `recent_rank`.
- Los contadores permanecen agregados por usuario y página.
- `/home` es el destino autenticado predeterminado.
- [Mapa de las ubicaciones de Mi cuenta, Favoritos e Indicadores](../../../frontend/patrones/00_menu_order.md).
