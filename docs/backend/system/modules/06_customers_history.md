# 06 - Historial de cliente

## Que hace

Permite consultar ventas, membresias y asistencias de un cliente en un periodo.

## Archivos

- Ruta: `routes/System/Customers/TrackingCustomer.php`
- Controlador: `TrackingCustomerController`
- Servicio: `TrackingCustomerBusinessService`
- Vue: `resources/js/System/Pages/Customers/tracking_customers`
- Componentes: `TrackingCustomers/Timeline`, `Sales`, `Subscriptions`, `Attendances`
- Tablas: `customers`, `sales_header`, `subscriptions`, `attendances`

## Reglas

- Buscar cliente por id o numero de documento.
- Filtrar informacion por rango.
- Devolver solo informacion de la empresa actual.

## Campos relevantes

- `customer_id`
- `customer_document_number`
- `period_type`
- `options.information`

## Estado de mejoras

- Ventas, membresías y asistencias se limitan por empresa y sucursales efectivas del colaborador.
- `period_type=custom` acepta `start_date` y `end_date` y rechaza rangos invertidos.
- La respuesta incluye `summary` con ventas activas/canceladas, membresías activas y asistencias.

## Estado UI Implementado

- La modal de busqueda permite elegir rango personalizado con fecha inicial y fecha final.
- La vista valida rango invertido antes de consultar y envia `start_date`/`end_date` al servicio existente.
- El expediente muestra tarjetas compactas de ventas activas, ventas anuladas, asistencias y membresias activas.
