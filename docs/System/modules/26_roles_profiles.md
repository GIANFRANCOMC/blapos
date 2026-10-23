# 26 - Perfiles y accesos

## Qué hace

Define qué módulos, acciones, sucursales, cajas y almacenes puede utilizar cada colaborador.

## Backend

- `role_sub_sections.actions` concede `view`, `create`, `update`, `delete`, `export`, `import` y `operate` por módulo.
- Los alcances `all`/`restricted` se guardan en el perfil; el colaborador puede heredarlos o reducirlos.
- `module.permission` valida módulo/acción y `resource.scope` valida recursos operativos.
- Un usuario no puede delegar más permisos o alcance del que posee.
- No puede retirarse el último administrador activo de una empresa.
- Antes de guardar cambios que inactivan un perfil, retiran acceso total o reducen permisos, la UI advierte cuántos usuarios activos quedarán afectados.
- El backend mantiene la regla final: si el cambio deja a la empresa sin administrador activo, se bloquea aunque la UI haya mostrado confirmación.
- `POST /roles/{id}/duplicate` copia permisos y alcances a un perfil nuevo sin acceso total.
- `DuplicateRoleRequest` normaliza el nombre y valida unicidad dentro de la empresa antes de copiar el perfil.
- Cambios de perfil, permiso y recursos invalidan únicamente las cachés afectadas.
- `business_audit_logs` registra creación, cambios de permisos y alcances con el conteo de usuarios afectados, sin guardar secretos.

## Frontend

- El listado permite crear, editar y duplicar perfiles de acceso.
- La acción `Duplicar perfil` solicita un nombre nuevo, copia permisos y alcances mediante `POST /roles/{id}/duplicate` y refresca el listado al terminar.
- El nombre sugerido usa `Copia de {perfil}` y evita repetir nombres ya visibles en la página actual; la validación final queda en el backend por empresa.
- Las acciones de tabla se mantienen compactas con `br-table-actions` y botones `br-icon-action-*` para conservar la línea visual de Productos.
- El listado presenta cada perfil en una fila, con el tipo bajo el nombre, el alcance de módulos y el número de colaboradores como datos sin badges decorativos. La edición usa secciones de permisos de una sola columna con filas y separadores suaves; las acciones aparecen alineadas junto al módulo.

## Perfiles iniciales de datos demo

`AccessProfilesSeeder` crea cinco perfiles limitados al aprovisionar cada empresa, incluso si no se cargan datos demo. `DemoPeopleSeeder` reparte los colaboradores importados de forma pseudoaleatoria estable a partir de su correo. Las cuentas demo siguen inactivas, con contraseña aleatoria, hasta que un administrador decida habilitarlas. El perfil anterior `demo-colaborador` se elimina al quedar sin usuarios y sus usuarios de demo se reasignan al volver a ejecutar el seeder.

| Perfil | Acceso previsto |
| --- | --- |
| Vendedor | Consulta del catálogo; operación de clientes, ventas, cotizaciones, servicios y caja. |
| Compras | Compras, proveedores y movimientos de inventario; consulta del catálogo y kardex. |
| Gestión | Consulta y exportación transversal; mantenimiento de colaboradores, sedes y catálogo, sin administrar perfiles. |
| Auditoría | Solo consulta y exportación de información operativa y financiera, sin altas ni cambios. |
| Contador | Consulta y exportación financiera, ventas y compras; registro y seguimiento de cuentas, movimientos y gastos. |

Los permisos se asignan por ruta de `sub_sections` y acción (`view`, `create`, `update`, `operate`, `import`, `export`). El módulo debe estar también habilitado para la empresa; asignar un permiso al perfil no activa por sí solo un módulo deshabilitado. El administrador conserva `is_full_access` y no comparte ese atributo con los perfiles demo.
