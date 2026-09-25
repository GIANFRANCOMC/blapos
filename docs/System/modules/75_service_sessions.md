# 75 - Servicios En Curso

## Qué Hace

Controla atenciones con inicio y fin reales para barberías, salones, clínicas, talleres, canchas, alquileres, restaurantes y otros rubros. Una sesión puede tener agenda, cola, tolerancia, estación física, cliente, responsable y varios detalles independientes.

## Backend

- Tablas principales: `service_sessions` y `service_session_items`.
- Trazabilidad: `service_session_events`.
- Pausas: `service_session_pauses`.
- Recursos físicos reutilizables: `service_floors` y `service_stations`.
- Reportes agregados: `GET /service_operations/reports`.
- Edición operativa: `PATCH /service_operations/floors/{id}` y `PATCH /service_operations/stations/{id}`.
- Tablero compuesto: `GET /service_operations/board` devuelve pisos y estaciones en una sola llamada.
- Catálogos remotos: `GET /service_operations/options` busca clientes e ítems con alcance de empresa y un máximo de 30 resultados.

## Reglas

- `scheduled_at`, `expected_end_at`, `tolerance_minutes` y `queue_code` soportan agenda, cola y medición SLA sin confundirse con el inicio real.
- Cada pausa conserva responsable, motivo, duración y detalle afectado; su tiempo se separa del tiempo efectivo.
- Reasignar operador registra usuario anterior, nuevo usuario, actor y motivo.
- Cancelar exige motivo y genera un evento inmutable.
- Cada detalle conserva nombre, tipo, precio histórico, duración y comisión estimada.
- Los detalles KDS disponen de estados pendiente, preparando, listo y entregado.
- Todo acceso se limita por empresa y alcance de sucursal.
- Pausas y detalles se consultan y actualizan con `service_session_id + id` dentro de la conexión tenant y una transacción para impedir cruces entre sesiones.
- La configuración inicial no incluye colecciones crecientes de clientes ni ítems; se consultan bajo demanda desde los selectores.
- Los índices compuestos siguen los filtros reales del tablero, sesiones, detalles y eventos para evitar degradación al crecer el tenant.

## Rendimiento

- La primera vista prioriza el tablero; la precarga de opciones ocurre después y no bloquea su renderizado.
- Cambiar de piso consulta únicamente sus estaciones.
- Clientes e ítems devuelven solo los campos usados por el selector.
- Los catálogos de alto crecimiento no deben agregarse nuevamente a `initParams`; deben exponerse mediante búsqueda limitada o paginada.

## Reportes

El endpoint de reportes devuelve:

- Resumen: total de atenciones, abiertas, finalizadas, canceladas, duración promedio, sesiones fuera de SLA y comisión estimada.
- Agrupación por sucursal.
- Agrupación por estación.
- Agrupación por responsable.
- Agrupación por servicio o producto atendido.

## UX Implementada

- Cards de KPIs para atenciones, SLA, tiempo promedio y comisiones.
- Panel de detalle con timeline visible y acciones operativas.
- Formulario de nueva atención con agenda, fin esperado, tolerancia y turno/cola.
- Reasignación, pausa, reanudación y cancelación desde la misma atención.

## Mejoras Sugeridas

- Exportar reportes de servicios a Excel.
- Definir SLA por tipo de servicio para no depender solo del fin esperado manual.
- Agregar tablero de cola por responsable o estación.
