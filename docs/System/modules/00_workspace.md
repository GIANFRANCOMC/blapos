# Mi espacio de trabajo

## Propósito

`Mi espacio de trabajo` consolida en un solo mundo las páginas Mi espacio, Menú y favoritos, Mi cuenta, Dashboard y Reportes. Su página inicial, `Mi espacio`, permite retomar rápidamente las rutas recientes y acceder a las funciones más utilizadas por cada usuario.

La autenticación tenant utiliza `/workspace` como destino predeterminado.

## Persistencia escalable

La tabla `user_navigation_metrics` utiliza una fila agregada por empresa, usuario y `sub_section`:

| Campo | Uso |
| --- | --- |
| `user_id` | Propietario de la preferencia. |
| `sub_section_id` | Página del catálogo, sin almacenar URLs libres. |
| `visit_count` | Total acumulado de visitas. |
| `recent_rank` | Posición reciente entre 1 y 10; `NULL` fuera del límite. |

La clave única `user_id + sub_section_id` impide duplicar registros por visita. No se almacenan timestamps porque esta funcionalidad no requiere auditoría temporal.

## Flujo

1. Una petición `GET` HTML supera autenticación, permisos y alcance operativo.
2. `TrackUserNavigation` espera una respuesta exitosa.
3. `UserNavigationService` resuelve la ruta contra el catálogo activo de `sub_sections`.
4. Dentro de una transacción incrementa `visit_count`, coloca la página en `recent_rank = 1`, desplaza las demás y deja en `NULL` cualquier posición superior a 10.
5. Mi espacio obtiene recientes y más usados exclusivamente entre las páginas permitidas por `CompanySectionService`.

La propia ruta `workspace.index`, endpoints AJAX, respuestas JSON y errores no se contabilizan.

## Archivos

- `app/Http/Controllers/System/Essentials/WorkspaceController.php`
- `app/Http/Middleware/TrackUserNavigation.php`
- `app/Models/System/Organizations/UserNavigationMetric.php`
- `app/Services/System/Essentials/UserNavigationService.php`
- `routes/System/Essentials/Workspace.php`
- `resources/views/System/general/Essentials/workspace/main.blade.php`
- `resources/css/System/br-branding/52-workspace.css`
- `database/migrations/2024_01_11_223124_create_init_masters_table.php`
- `database/seeders/SystemNavigationSeeder.php`

## Validaciones cubiertas

- Una visita repetida incrementa el contador sin crear otra fila.
- Solo diez páginas conservan `recent_rank`.
- Los contadores permanecen agregados aunque una página salga de recientes.
- Mi espacio es el destino autenticado predeterminado.
- Menú y favoritos, Mi cuenta, Dashboard y Reportes comparten el mismo mundo.
