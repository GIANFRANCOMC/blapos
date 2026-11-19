# 03 - Ventas / Listado

## Que hace

Lista ventas realizadas, permite filtrar y acceder a detalle, anulacion o impresion.

## Archivos

- Ruta: `routes/System/Sales/Sale.php`
- Controlador: `SaleController`
- Servicios: `SaleService`, `SaleConfigService`
- Modelos: `SaleHeader`, `SaleBody`
- Vista: `resources/views/System/general/Sales/sales/list.blade.php`
- Vue: `resources/js/System/Pages/Sales/sales/list.vue`
- Tablas: `sales_header`, `sales_body`, `series`, `branches`, `customers`, `currencies`

## Reglas

- Listar solo ventas de series pertenecientes a sucursales de la empresa.
- Filtrar por serie, correlativo, fecha, cliente y estado.
- Permitir anulacion solo si la venta esta `active`.
- La acción de anular se muestra de forma directa en la fila cuando la venta está activa y se mantiene también dentro del modal de acciones.
- El modal de acciones permite reenviar el resumen por WhatsApp o correo electrónico validado.
- Anular una venta no implica necesariamente recibir productos de vuelta.
- `company_settings.inventory.restore_stock_on_sale_cancellation` controla la reposición automática y es `false` por defecto.
- Con la política desactivada, la respuesta recuerda registrar una devolución desde Inventario si la mercancía fue recibida.
- Con la política activa, cada producto genera una entrada `sale_cancellation` en el almacén asociado y la respuesta confirma la reposición.
- Las membresías vinculadas se anulan independientemente de la política de inventario.
- `SaleConfigService` mantiene cachés separadas para `main` y `list`.
- Crear o anular ventas no invalida `initParams`, porque esta configuración contiene filtros, maestros y estados, no registros de venta.

## Campos relevantes

- `sales_header.serie_id`
- `sales_header.sequential`
- `sales_header.holder_id`
- `sales_header.issue_date`
- `sales_header.total`
- `sales_header.status`

## Estado de mejoras

- El backend valida empresa y alcance de sucursal antes de anular.
- La venta se obtiene por `sales_header.id` dentro de la conexión tenant y luego se valida su alcance operativo.
- El listado acepta `start_date`, `end_date` y `branch_id`, además de los filtros existentes.
- La vista del listado expone filtros de sucursal, serie, secuencia, rango de emisión, cliente y estado usando `br-filter-bar`.
- La paginación conserva los filtros activos para no perder contexto al cambiar de página.
- La tabla muestra la sucursal emisora junto a la serie para identificar rápidamente el origen operativo de la venta.
- Cada venta carga la sucursal emisora mediante `serie.branch` sin consultas adicionales por fila.
- Las pruebas multiempresa se añadirán únicamente cuando sean solicitadas expresamente.
