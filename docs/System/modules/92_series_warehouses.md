# 92 - Series y almacenes

## Qué hace

Documenta las entidades técnicas que habilitan la emisión de comprobantes y el control físico de productos por sucursal.

## Series

La tabla `series` define los correlativos disponibles por empresa, sucursal y tipo de documento.

Campos principales:

- `branch_id`: sucursal que emite el comprobante.
- `document_type_id`: tipo de comprobante.
- `code` y `number`: identificación visible de la serie.
- `init`: primer correlativo que se utilizará cuando la serie aún no tenga ventas.
- `status`: `active` o `inactive`.

## Historial de correlativos

`series_correlative_movements` es una bitácora inmutable de correlativos emitidos y anulados.

Campos principales:

- `serie_id` y `sale_header_id` identifican la serie y la venta.
- `user_id` identifica al responsable.
- `sequential` conserva el correlativo utilizado.
- `action` distingue `issued` y `canceled`.
- `source` distingue venta normal (`sale`) y POS (`pos`).
- `note`, `metadata` y `occurred_at` conservan contexto y fecha.

Anular una venta no libera ni reutiliza su correlativo. Se agrega un movimiento `canceled` y se conserva el movimiento `issued` original.

La combinación `serie_id + sequential` es única en `sales_header`. Antes de calcular el siguiente número, la serie se obtiene con `lockForUpdate()`, evitando que dos ventas concurrentes reciban el mismo correlativo.

## Almacenes

Las tablas `warehouses` y `warehouse_items` administran existencias por sucursal.

Campos principales:

- `warehouses.branch_id` delimita la sucursal dentro del tenant.
- `warehouses.name` identifica el almacén.
- `warehouse_items.warehouse_id` y `warehouse_items.item_id` identifican el producto almacenado.
- `warehouse_items.quantity` conserva la existencia actual.
- `warehouse_items.minimum_stock` define el umbral de alerta.

## Historial de inventario

`inventory_movements` es la única fuente de trazabilidad física. No se crea una segunda bitácora para almacenes.

Cada movimiento conserva empresa, almacén, producto, usuario, tipo, origen, motivo, cantidad anterior, variación, saldo resultante, costos y metadatos. Entradas, ventas, anulaciones con reposición, ajustes y traslados deben pasar por `InventoryMovementService`.

## Reglas de venta

- Toda venta requiere una serie activa perteneciente a la sucursal seleccionada.
- Toda venta requiere un almacén activo perteneciente a una sucursal activa de la empresa.
- Si la sucursal tiene un único almacén activo, el backend puede resolverlo automáticamente.
- Si tiene varios almacenes, el usuario debe seleccionar cuál será afectado.
- Si no existe una serie activa, backend bloquea la venta y las vistas Venta y POS indican que debe crearse o activarse una serie.
- Si no existe un almacén activo, backend bloquea la venta y las vistas indican que debe crearse o activarse un almacén.
- Venta y POS filtran opciones inactivas y deshabilitan la acción mientras exista un bloqueo de configuración.

## Creación y consistencia

- Al crear una sucursal pueden crearse almacén y series por defecto.
- Al crear un almacén predeterminado se generan relaciones con cantidad y mínimo cero para los productos existentes.
- Un producto sólo puede tener un registro por almacén.
- La asignación del correlativo, la venta, su bitácora y los movimientos de inventario se ejecutan dentro de la misma transacción.

## Implementado

- Bloqueo backend de ventas sin serie activa.
- Bloqueo backend de ventas sin almacén activo.
- Mensajes accionables en Venta y Venta POS.
- Protección transaccional y unicidad del correlativo.
- Historial de emisión y anulación mediante `series_correlative_movements`.
- Historial de movimientos físicos mediante `inventory_movements`.

## Estado de mejoras

- `GET /branches/series/audit` filtra por sucursal, serie, responsable, origen, acción y fecha.
- La respuesta incluye detección de correlativos faltantes entre el primero y el último emitido.
- `GET /branches/series/audit/export` descarga la bitácora sin modificarla.
