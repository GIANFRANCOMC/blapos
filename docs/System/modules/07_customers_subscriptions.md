# 07 - Membresias de clientes

## Que hace

Lista y administra membresias reales asignadas a clientes. Estas pueden originarse por venta o por registro manual.

## Archivos

- Ruta: `routes/System/Customers/TrackingSubscription.php`
- Controlador: `TrackingSubscriptionController`
- Servicio: `TrackingSubscriptionService`
- Requests: `CancelTrackingSubscriptionRequest`, `RenewTrackingSubscriptionRequest`, `StoreManualTrackingSubscriptionRequest`
- Vue: `resources/js/System/Pages/Customers/tracking_subscriptions`
- Tablas: `subscriptions`, `customers`, `branches`, `sales_header`, `sales_body`

## Campos necesarios

- `branch_id`
- `customer_id`
- `start_date`
- `end_date`
- `duration_type`
- `duration_value`
- `attendance_limit_per_day`
- `type`
- `status`

## Reglas

- Una membresia vigente permite registrar asistencia.
- La membresia debe pertenecer a empresa y sucursal.
- Cancelar requiere motivo.
- Si viene de venta, la anulacion de venta tambien la cancela.
- Cuando una venta crea una membresía, el detalle puede enviar `customer_id` para indicar el cliente beneficiario. Si no se envía, se usa el titular de la venta.
- `TrackingSubscriptionConfigService` carga únicamente sucursales y clientes activos.
- `TrackingSubscriptionConfigService` también carga membresías activas del catálogo para altas manuales.
- Cancelar una membresía no invalida `initParams`, porque no modifica esas opciones.

## Estado de mejoras

- `company_settings.subscriptions.overlap_policy` define `block` o `allow` por empresa.
- `force=true` es una excepción explícita enviada por una operación autorizada; nunca es el valor por defecto.
- `POST /tracking_subscriptions/{id}/renew` crea una nueva membresía manual y conserva `renewed_from_id`.
- `POST /tracking_subscriptions/manual` crea una membresía manual inicial sin generar venta.
- El alta manual puede tomar una membresía del catálogo como referencia de duración, pero siempre guarda una membresía real en `subscriptions`.
- Si el cliente tiene correo y se solicita, el alta manual registra un `subscription_emails` de tipo `SubscriptionWelcome` para enviar agradecimiento por suscripción mediante el proceso existente de notificaciones.
- `company_settings.subscriptions.send_welcome_email_on_sale` permite registrar el mismo correo de agradecimiento cuando la membresía nace desde una venta.
- La renovación respeta empresa, sucursal, alcance, fechas y política de solapamiento.
- El modelo expone `remaining_days` y `remaining_time_label` para mostrar cuántos días faltan hasta el vencimiento o cuántos días pasaron desde que venció.
- El comando `subscriptions:cancel-expired` inactiva membresías vencidas por tenant, registra motivo y dispara `SubscriptionExpired` para conservar el flujo de notificaciones.
- Las pruebas se añadirán cuando sean solicitadas expresamente.

## Estado UI Implementado

- La modal de detalle permite renovar una membresia activa con fecha inicial, fecha final, limite diario opcional y observacion.
- El listado permite agregar una membresía manual con sucursal, cliente, plan opcional, fechas, límite diario y opción de correo de agradecimiento.
- El listado y el historial del cliente muestran el tiempo restante junto a la fecha de finalización: verde si está holgado, amarillo si vence pronto y rojo si ya venció.
- La renovacion usa `POST /tracking_subscriptions/{id}/renew` y deja que el backend bloquee solapamientos segun `company_settings.subscriptions.overlap_policy`.
- Los mensajes del flujo explican que una membresia superpuesta sera bloqueada para evitar vigencias ambiguas.
