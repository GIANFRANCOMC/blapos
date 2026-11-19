# 23 - Reportes

## Que hace

Genera reportes y exportes de clientes, items, sucursales, ventas, usuarios y comprobantes PDF.

## Archivos

- Ruta: `routes/System/Essentials/Report.php`
- Controlador: `ReportController`
- Servicio: `ReportConfigService`
- Exports: `app/Exports`
- PDF: `resources/views/System/pdf/sales`
- Tablas: `sales_header`, `sales_body`, `customers`, `items`, `branches`, `users`

## Reglas

- Todo reporte debe respetar empresa.
- PDF de venta puede generarse en A4 o ticket 80mm.
- Exportes deben filtrar por parametros enviados.

## Estado de mejoras

- Todos los queries usan la conexión tenant y, cuando corresponde, filtran por sucursales autorizadas.
- `reports.export_max_rows` rechaza exportaciones excesivas con un mensaje accionable.
- Los archivos usan `blapos-{recurso}-{Ymd-His}.{extensión}`.
- Ventas admite `by_month`, `range_months`, `by_date` y `range_dates`; clientes, usuarios, items y sucursales aceptan sus filtros documentados por endpoint.
- La vista usa `FiltersSection` como barra principal: selector de reporte, búsqueda rápida, acción de exportar/consultar y filtros avanzados mediante slot, manteniendo la misma estructura visual de Productos y demás módulos migrados.
- La exportación usa `Requests.download`, muestra Swal de carga y conserva el nombre de archivo entregado por el backend.
- Las validaciones frontend muestran mensajes directos por parámetro requerido antes de consultar el backend.
- Cada reporte muestra una ayuda breve para explicar qué se exportará y reducir errores de selección.
- `Resumen financiero` consume `GET /reports/settlements` desde la vista de Reportes para consultar tributos o métodos de pago por ventas, compras o ambos, con rango de fechas opcional.
- El resumen financiero se muestra en tabla dentro de la pantalla porque el endpoint entrega datos agregados JSON, no un archivo Excel.
- La búsqueda rápida alimenta el campo principal del recurso: nombre de cliente, colaborador, ítem o sucursal. Los filtros avanzados conservan documento, descripción, rango de ventas o alcance financiero según corresponda.
- El PDF obtiene la empresa actual, verifica alcance de sucursal, valida base64 estricto y admite la fecha de expiración completa.
- Las pruebas PDF se añadirán cuando sean solicitadas expresamente.
