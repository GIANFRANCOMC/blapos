# 14 - Categorías

## Qué hace

Agrupa productos, servicios y membresías por empresa.

## Backend

- Campos: `internal_code`, `name`, `description`, `sort_order`, `is_public` y `status`.
- `sort_order` controla orden estable; `is_public` separa uso interno de exposición pública.
- Solo categorías activas se ofrecen en nuevas asociaciones.
- Inactivar o eliminar queda bloqueado cuando existen ítems activos asociados; el backend responde `422` con un mensaje directo para que el usuario entienda que primero debe retirar o desactivar esos productos.
- Crear, editar o eliminar invalida las cachés dependientes de Productos, Servicios y Membresías.
- La alta rápida conserva generación de código y validación backend.

## Interfaz

- El listado muestra orden, publicación pública/interna y cantidad de productos activos asociados.
- El formulario permite administrar `sort_order` y `is_public` sin salir del flujo de categorías.
- La visibilidad pública está pensada para catálogos visibles al cliente; no reemplaza el estado `active/inactive`, que controla disponibilidad operativa.
