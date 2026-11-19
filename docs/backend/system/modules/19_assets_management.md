# 19 - Gestion de activos

## Que hace

Gestiona asignaciones de activos a sucursales y usuarios.

## Archivos

- Ruta: `routes/System/Assets/AssetManagement.php`
- Controlador: `AssetManagementController`
- Servicio: `AssetManagementService`
- Tablas: `branch_assets`, `asset_assignments`, `asset_assignment_logs`, `assets`, `branches`, `users`

## Campos necesarios

- Para sucursal: `branch_id`, `asset_id`, `quantity`, `acquisition_value`, `acquisition_date`, `note`, `status`.
- Para usuario: `user_id`, `branch_id`, `asset_id`, `quantity`, `status`.

## Reglas

- No asignar a usuarios mas cantidad que la disponible en sucursal.
- Retirar marca como `retired`, no borra fisicamente.
- Estados: `active`, `maintenance`, `retired`.
- `AssetManagementConfigService` carga activos, sucursales activas, usuarios y estados de referencia.
- Las sucursales no precargan series comerciales porque este módulo no las consume.
- Asignar o retirar activos no invalida `initParams`; los cambios en activos, usuarios o sucursales sí lo hacen mediante la matriz de dependencias.

## Estado de mejoras

- Altas, reactivaciones, retiros, asignaciones y devoluciones escriben `asset_assignment_logs`.
- La bitácora conserva origen/destino de usuario y sucursal para soportar transferencias sin perder contexto.
- Inactivar una sucursal se bloquea mientras existan activos asignados a colaboradores.
