# Entregas pendientes de ventas

## Propósito

Permite dar seguimiento a ventas cuya entrega física queda pendiente. La venta queda registrada comercialmente, pero el inventario se descuenta recién cuando se confirma una entrega parcial o total.

## Flujo operativo

1. Nueva venta guarda `delivery_mode = pending` cuando el usuario elige entrega pendiente.
2. El backend crea `sale_deliveries` y `sale_delivery_items` solo para productos contabilizables.
3. La pantalla `Ventas > Entregas pendientes` lista ventas con estado `pending` o `partial`.
4. El usuario registra una entrega indicando almacén, fecha, cantidades por producto y observación opcional.
5. Cada entrega crea `sale_delivery_events`, `sale_delivery_event_items` y movimientos de inventario con origen `sale_delivery`.
6. Si queda saldo, la entrega queda `partial`; si todo fue entregado, queda `delivered` y actualiza `sales_header.delivery_status`.

## Reglas de inventario

- `delivery_mode = immediate`: descuenta stock al crear la venta.
- `delivery_mode = pending`: no descuenta stock al crear la venta.
- La salida real de almacén se registra desde la entrega.
- No se permite entregar más cantidad que la pendiente.
- La anulación cancela entregas pendientes. Si ya hubo entregas parciales y la política de reposición está activa, la reposición considera movimientos con origen `sale_delivery`.

## Seguridad y alcance

- Las consultas se ejecutan sobre la conexión tenant activa.
- La lista y el registro respetan los almacenes permitidos para el colaborador autenticado.
- La entrega solo acepta almacenes activos de la empresa.

## UI/UX

- La pantalla usa filtros por sucursal, almacén, cliente, estado y búsqueda libre.
- La columna pendiente muestra progreso, cantidad pendiente y hasta tres productos pendientes como referencia rápida.
- La modal de entrega es estática y obliga cierre explícito, siguiendo `br-entity-modal`.
- La cantidad a entregar usa `InputNumber` y se limita al pendiente de cada línea.

## Pendientes sugeridos

- Exportar entregas pendientes y eventos de entrega a Excel.
- Agregar vista histórica de entregas completadas.
- Permitir impresión de guía o cargo de entrega por evento.
