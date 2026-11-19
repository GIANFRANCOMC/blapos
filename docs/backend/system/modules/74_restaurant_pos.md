# 74 - Restaurante POS

## Qué Hace

Administra pisos, mesas, pedidos y tiempos antes del cobro. La atención operativa no reemplaza la venta: al cobrar se genera una venta normal con comprobante, tributos, pagos, caja e inventario.

## Estructura

- `service_floors`: pisos o zonas por sucursal, con nombre, código, orden y color de fondo del plano.
- `service_stations`: mesas o estaciones con capacidad, posición porcentual, color, forma y tipo.
- `service_sessions`: atención activa, cola, cita, responsable, cliente y vínculo final con la venta.
- `service_session_items`: productos, servicios o membresías agregadas a la atención.
- `service_session_events`: línea de tiempo visible con actor, estado anterior, estado nuevo y metadatos.
- `service_session_pauses`: pausas justificadas y duración acumulada.

## Reglas

- Una mesa solo puede tener una atención pendiente o en curso.
- Un piso y sus mesas siempre pertenecen a la misma sucursal, empresa y alcance operativo del usuario.
- Las coordenadas se guardan como porcentajes para conservar el plano entre resoluciones.
- El editor visual permite modificar nombre, código, orden, fondo, color, forma y disposición de mesas.
- La disponibilidad de mesa se deriva de sesiones abiertas; no se duplica como estado manual.
- KDS aplica la transición secuencial `pending -> preparing -> ready -> delivered`.
- Cada cambio KDS guarda fechas (`preparation_started_at`, `ready_at`, `delivered_at`) y evento de trazabilidad.
- Cancelaciones, pausas, reanudaciones y reasignaciones conservan actor, motivo y metadatos.
- La sesión se vincula a `sales_header` únicamente al confirmarse el cobro.

## UX Implementada

- Plano por piso con fondo configurable, arrastre de mesas y edición directa.
- Panel lateral de atención con KDS por detalle, timeline visible, responsable, pausa, reanudación y cancelación.
- Botones compactos y colores alineados al branding `br-*`.

## Mejoras Sugeridas

- Imprimir comandas por estación de cocina/bar.
- Configurar estaciones KDS por categoría de producto.
- Permitir múltiples planos por turno si el negocio opera ambientes temporales.
