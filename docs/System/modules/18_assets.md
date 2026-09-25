# 18 - Activos

## Que Hace

Administra bienes fisicos por empresa, con clasificacion, identificacion interna, control patrimonial y serie del fabricante.

## Backend

- `asset_categories`: nombre, descripcion y estado por empresa.
- `assets.asset_category_id`: categoria opcional.
- `assets.internal_code`: identificador del sistema.
- `assets.patrimonial_code`: codigo patrimonial opcional y unico por empresa.
- `assets.serial_number`: serie fisica opcional y unica por empresa.
- Modelo `AssetCategory`, relaciones en `Asset` y validaciones de pertenencia/unicidad.
- Endpoints protegidos para listar, crear y editar categorias bajo `/assets/categories`.
- `StoreAssetCategoryRequest` y `UpdateAssetCategoryRequest` reutilizan `CompanyFormRequest`, normalizan textos y aplican unicidad empresarial antes de persistir.
- Las operaciones de asignacion y retiro usan requests especificos: `AssignAssetToBranchRequest`, `UnassignAssetFromBranchRequest`, `UpdateAssetInBranchRequest`, `AssignAssetToUserRequest` y `UnassignAssetFromUserRequest`.
- Los requests de gestion de activos validan arrays completos antes de llegar al servicio; el servicio conserva la validacion de pertenencia a sucursal y activo como segunda barrera.
- Busqueda de activos por codigo interno, codigo patrimonial, serie, nombre o descripcion.

## Interfaz

- El formulario de Activos permite seleccionar categoría de activo junto al nombre y descripción.
- La acción contextual `Agregar` abre una modal rápida para crear una categoría sin abandonar el alta o edición del activo.
- La categoría rápida solicita nombre y descripción, se crea activa y queda seleccionada automáticamente en el formulario actual.
- La modal reutiliza `br-entity-modal`, botones `br-btn-*` y validación inline para mantener consistencia con Catálogo comercial.

## Seguridad

- Ninguna asignación acepta un selector de empresa desde frontend.
- La sucursal se resuelve dentro del tenant y se valida el alcance del usuario.
- Las asignaciones de colaboradores validan que la cantidad total no exceda la cantidad disponible en la sucursal.
- Cada asignacion, retiro o devolucion registra un evento en `asset_assignment_logs`.

## Criterio Pendiente

La individualizacion de unidades de un bien administrado como stock sigue siendo una decision de negocio: solo debe crearse una tabla de unidades fisicas cuando cada unidad necesite serie, mantenimiento o ciclo de vida propio.
