# 08 - Asistencias de clientes

## Qué hace

Registra el ingreso y la salida de clientes. Valida cliente, membresía vigente, asistencia activa y límite diario permitido por la membresía.

En el menú pertenece a `Clientes > Membresías`, inmediatamente después de `Membresías`, porque su operación depende de una membresía vigente. No forma parte del grupo general de comunicaciones de Atención al cliente.

Esta lógica es independiente de `user_attendances`, que controla jornadas laborales de colaboradores.

## Archivos

- Ruta: `routes/System/Customers/TrackingAttendance.php`.
- Controlador: `TrackingAttendanceController`.
- Servicios: `TrackingAttendanceService`, `TrackingAttendanceBusinessService`.
- Requests: `StoreTrackingAttendanceRequest`, `CheckoutTrackingAttendanceRequest`, `ProcessTrackingAttendanceBatchRequest`, `CancelTrackingAttendanceRequest`, `StoreAttendanceCorrectionRequest` y `ReviewAttendanceCorrectionRequest`.
- Vue: `resources/js/System/Pages/Customers/tracking_attendances`.
- Tablas: `attendances`, `customers`, `subscriptions`, `branches`.

## Campos necesarios

- `branch_id`.
- `customer_id` o `customer_document_number`.
- `start_date`.
- `end_date`.
- `type`.
- `action`.
- `status`.

## Reglas

- No se permite check-in sin membresía vigente en la sucursal.
- La asistencia activa de un cliente es única por empresa, sucursal y cliente.
- El mismo cliente puede tener historial en varias sucursales, pero no dos asistencias activas en la misma sucursal.
- No se permite un segundo check-in mientras exista una asistencia activa en esa sucursal.
- Checkout requiere una asistencia activa en la sucursal solicitada.
- El ID de la ruta identifica la asistencia exacta que se cerrará; sucursal y cliente se recuperan del registro y no se confía en valores alternativos enviados por el cliente HTTP.
- La asistencia activa se bloquea dentro de la transacción antes de registrar la salida para impedir cierres concurrentes.
- Checkout debe ser posterior al ingreso y al menos dos minutos después.
- El límite diario se obtiene de `subscriptions.attendance_limit_per_day`.
- `exceedsLimit` bloquea un nuevo check-in cuando las asistencias finalizadas del día alcanzan dicho límite.
- El conteo diario considera estados `active` y `finalized`; ignora registros cancelados o inactivos.
- Tipos: `manual_form`, `qr_camera`, `qr_scanner`, `qr_public`, `biometric`.
- Para búsqueda por documento el tipo oficial es `document_number`. Los valores antiguos `dni` y `dnie` se siguen aceptando como alias internos para no romper lecturas previas.
- `TrackingAttendanceConfigService` carga únicamente sucursales y clientes activos.
- Altas manuales, lotes QR, cancelaciones y correcciones validan empresa y alcance efectivo de sucursal antes de mutar información.
- Cancelar una asistencia no invalida `initParams`, porque no modifica esas opciones.
- `company_settings.customer_attendance.max_active_hours` define cuántas horas puede permanecer abierta una asistencia. Por defecto son 20 horas.
- Si un nuevo ingreso encuentra una asistencia activa que supera `max_active_hours`, el backend finaliza técnicamente la asistencia vencida con observación auditada y permite crear una nueva.
- Si el checkout manual o automático intenta cerrar una asistencia superando `max_active_hours`, se bloquea y se solicita corrección o nuevo registro.
- `company_settings.customer_attendance.auto_close_stale_enabled` habilita el cierre automático de asistencias abiertas que quedaron sin salida.
- `company_settings.customer_attendance.auto_close_after_time` define desde qué hora se puede cerrar técnicamente pendientes del día anterior; el valor inicial es `01:00`.
- `company_settings.customer_attendance.auto_close_end_time` define la hora técnica de salida usada para el cierre; el valor inicial es `23:50`, aproximando el fin operativo del día.
- El cierre automático marca la asistencia como `absent`, conserva observación auditada y evita que un registro abierto indefinidamente bloquee nuevos ingresos.
- `company_settings.customer_attendance.retention_months` limita cuánto tiempo se conserva historial eliminable de asistencias finalizadas/canceladas/inactivas/ausentes. El backend fuerza un mínimo de 4 meses aunque la configuración sea menor.
- Los filtros de fecha usan rangos (`startOfDay/endOfDay`) sobre `start_date` para evitar `whereDate` en columnas `datetime`.

## Automatización

El scheduler ejecuta:

- `attendances:close-stale-customers --limit=500` cada hora.
- `attendances:prune-customers --limit=1000` todos los días a las 03:20.

En servidor debe existir un único cron del scheduler Laravel:

```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Los comandos son tenant-aware: iteran tenants activos, activan la conexión correspondiente y auditan el resultado sin limpiar caché global.

## Biometría

`TrackingAttendanceBusinessService` usa el namespace correcto `App\Services\System\Devices\BiometricDevices\BiometricDeviceService`.

Para entradas biométricas, el dispositivo y `device_user_id` deben resolver una huella activa del cliente dentro de la misma empresa.

## Pruebas

`tests/Feature/AttendanceFlowsTest.php` verifica:

- Check-in y check-out de cliente.
- Cambio de estado `active` a `finalized`.
- Bloqueo al superar `attendance_limit_per_day`.

## Estado de mejoras

- `daily_limit_scope` define si el límite diario se cuenta por sucursal o por toda la empresa.
- `biometric_device_id` y `source_reference` identifican la lectura exacta.
- `biometric_duplicate_tolerance_seconds` evita duplicados en la ventana configurada.
- `attendance_corrections` conserva valor anterior, valor solicitado, motivo, solicitante, revisor y decisión.
- `GET /tracking_attendances/export` reutiliza filtros y alcance de sucursal del listado, descarga CSV y exige reducir el rango si supera 10 000 registros.

## Estado UI Implementado

- El listado muestra el origen del registro: dispositivo biometrico, fuente informada o registro manual.
- Se agrego exportacion desde la vista con los filtros activos del listado.
- Cada asistencia activa o finalizada permite solicitar una correccion auditada con fechas opcionales y motivo obligatorio.
- Cuando una asistencia tiene correcciones, el listado muestra el estado mas reciente de esa solicitud.
- La relacion `corrections` se ordena descendente para que el estado visible corresponda a la ultima correccion registrada.
