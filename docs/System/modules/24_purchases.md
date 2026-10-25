# 24 - Compras

## Propósito

Registra órdenes y facturas de compra, controla lo pendiente por recibir y alimenta el inventario con el costo real de cada recepción. El documento comercial y la recepción física son eventos distintos, pero `delivery_mode` permite decidir si la entrada al almacén es inmediata o queda pendiente.

## Archivos principales

- Rutas: `routes/System/Purchases/Purchase.php` (`/purchases` lista compras y `/purchases/create` abre el flujo de nueva compra)
- Controlador: `PurchaseController`
- Validaciones: `StorePurchaseRequest`, `ReceivePurchaseRequest`
- Servicio: `PurchaseService`
- Configuración: `PurchaseConfigService`
- Exportación: `PurchaseListExport`
- Vista: `resources/js/System/Pages/Purchases/purchases/main.vue`

## Flujo

1. Se registra proveedor, almacén de recepción, moneda, documento, fechas y productos.
2. Cada detalle guarda cantidad solicitada y costo unitario.
3. La compra guarda `delivery_mode`: `immediate` para recibir todo al crear o `pending` para recepciones posteriores.
4. Si la entrega es inmediata, el backend genera la recepción total y actualiza inventario en la misma transacción.
5. Si la entrega queda pendiente, una recepción posterior puede incluir parte o todo lo pendiente.
6. Cada cantidad recibida genera una entrada `purchase` mediante `InventoryMovementService`.
7. El costo recibido recalcula el promedio ponderado del producto en ese almacén y la compra cambia a `partial` o `received` según el avance.

## Reglas de negocio

- Solo admite proveedores activos de la empresa.
- El almacén debe pertenecer a la empresa.
- Solo admite items activos de tipo `product`.
- Un producto no puede repetirse en el mismo documento.
- Cantidad y costo unitario deben ser válidos; la cantidad debe ser mayor que cero.
- Un documento no anulado no puede repetirse para el mismo proveedor, tipo y número.
- La recepción no puede superar la cantidad pendiente.
- La recepción y todos sus movimientos se procesan en una sola transacción.
- Una compra con mercadería recibida no puede anularse por defecto. La salida física se registra como devolución a proveedor desde Inventario.
- Si `company_settings.inventory.restore_stock_on_purchase_cancellation` está activo, la anulación registra salidas `purchase_cancellation` por cada recepción y cancela las recepciones asociadas sin borrar la trazabilidad original.
- No se actualiza `warehouse_items` directamente; los saldos cambian mediante movimientos de inventario.

## Valorización

El método inicial es promedio ponderado por producto y almacén. Los impuestos se registran en la cabecera, pero el costo de inventario usa el costo unitario del detalle; no utiliza el precio de venta.

## Interfaz

- Compras tiene accesos dedicados para **Listado** y **Nuevo**. Ambos reutilizan la misma página Vue, pero el modo inicial se resuelve por la ruta para reducir clics y separar mentalmente consulta vs registro.
- `/purchases` funciona como equivalente directo a `/sales`; `/purchases/create` funciona como equivalente directo a `/sales/create`. Las rutas `/purchases/page/list` y `/purchases/page/new` se conservan solo como compatibilidad.
- El listado permite buscar por proveedor, documento o producto y filtrar por estado.
- El modo **Nuevo** abre un formulario de página completa, alineado con `sales/create`, con sucursal, almacén, proveedor, comprobante, productos, tributos, pagos mixtos, observaciones y recepción.
- El proveedor puede crearse desde el mismo flujo mediante el componente rápido `AddSupplier`, de la misma forma que ventas permite crear cliente.
- La pantalla de nueva compra expone `Recepción`: **Recepción inmediata** envía `delivery_mode = immediate` y **Recepción parcial o pendiente** envía `delivery_mode = pending`.
- Las opciones de tipo de comprobante y recepción salen del backend (`PurchaseHeader`) para evitar constantes sueltas en el frontend.
- El detalle de productos usa la misma estructura visual de ventas: tabla central, importes alineados, estado vacío con acción clara, observaciones como tile lateral y resumen de tributos/pagos en el panel derecho.
- Diferencias legítimas frente a ventas: compras usa proveedor en lugar de cliente, recepción en lugar de despacho, costo unitario en lugar de precio de venta, cuentas por pagar en lugar de cuentas por cobrar y comprobantes de compra propios.
- La recepción muestra únicamente cantidades pendientes.
- El progreso diferencia pendiente, parcial, recibido y anulado.
- La exportación usa los mismos filtros del listado y no aplica paginación.
- Las modales usan backdrop estático y los estilos reutilizables `br-entity-modal` y `br-modal-standard`.

## Estado de mejoras

- Cada compra recibe una referencia interna única `COM-*`, separada del número del proveedor.
- `delivery_mode` queda persistido como `immediate` o `pending`.
- Flete, seguro y otros gastos se distribuyen por valor, cantidad o partes iguales.
- Cada detalle conserva costo original, gasto asignado y `inventory_unit_cost`; las recepciones valorizan inventario con este último.
- La cabecera conserva gasto total, pagado, saldo y estado de pago.
- La aprobación pendiente bloquea recepciones; aprobar no simula una recepción física.
- La anulación con recepción usa una política explícita por empresa: por defecto se bloquea y exige devolución a proveedor; si se habilita reversa automática, genera movimientos de inventario compensatorios.

## Estado backend implementado

- La cabecera incorpora vencimiento y estado/aprobador de aprobación.
- `purchase_expenses` registra flete, seguro u otros gastos con criterio de distribución.
- `purchase_returns` y `purchase_return_items` vinculan devoluciones con compra, recepción, almacén y movimiento de inventario.

## Productos disponibles

- Compras solo lista productos con estado `active`.
- Un producto `inactive` no puede seleccionarse para nuevas órdenes o facturas de compra; primero debe reactivarse desde Catálogo e inventario.
- Los almacenes disponibles respetan las sucursales permitidas del colaborador.

## Ingresos pendientes

- Ruta independiente: `/purchases/pending-receipts` (`purchases.receipts.index`).
- Reutiliza `PurchaseService::receive()`; no existe una segunda lógica de inventario.
- Solo consulta compras aprobadas con estado `confirmed` o `partial`.
- Cada recepción puede ser parcial o total y registra `purchase_receipts`, `purchase_receipt_items` y movimientos de Kardex dentro de la misma transacción.
- Una compra sale automáticamente de Ingresos pendientes cuando todos sus detalles alcanzan la cantidad solicitada.

## Cuentas por pagar

- Ruta independiente: `/accounts_payable`.
- Controlador: `AccountsPayableController`.
- Servicio: `AccountsPayableService`.
- Tablas reutilizadas: `purchase_accounts_payable`, `purchase_payable_installments` y `purchase_payable_payments`.
- La vista sigue la estructura de Cuentas por cobrar: indicadores, filtros, listado, vencimientos y cronograma de cuotas.
- Las consultas respetan empresa y almacenes permitidos para el colaborador.
- No se crea una cuenta adicional por cada consulta: existe una sola cuenta por compra y las cuotas/pagos se relacionan con ella.
