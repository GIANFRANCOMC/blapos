# 73 - Asistencias del personal

## Qué hace

Registra jornadas laborales de colaboradores, separadas de las visitas de clientes. Consolida ingreso, salida, pausas, minutos ordinarios, tardanzas y horas extra, con solicitudes de corrección auditables.

## Tablas

- `user_attendances`: jornada y métricas consolidadas.
- `user_work_schedules`: horario por empresa, sucursal o colaborador; admite turnos nocturnos, tolerancia y redondeo.
- `user_attendance_breaks`: descansos múltiples con inicio, fin y duración.
- `user_attendance_corrections`: solicitud, revisión, aprobación o rechazo sin perder el dato solicitado.
- `user_biometric_fingerprints`: identidad del colaborador en un dispositivo.
- `biometric_device_events`: entrada firmada e idempotente desde equipos.

## Reglas

- Una jornada activa es única por empresa y colaborador, no por sucursal.
- Ingreso, salida, pausas y correcciones se ejecutan dentro de transacciones con bloqueos.
- La salida resta pausas finalizadas y calcula horas ordinarias, tardanza y horas extra según el horario más específico.
- Un horario de usuario prevalece sobre uno general; uno de sucursal prevalece sobre uno empresarial.
- Los turnos pueden cruzar medianoche.
- Una corrección aprobada recalcula las métricas y conserva la solicitud original y su revisor.
- El dispositivo biométrico no omite validaciones de empresa, sucursal, estado ni jornada activa.
- Check-in biométrico, resumen semanal, pausas y correcciones usan requests empresariales dedicados; comparten autorización, mensajes y formato de errores.
- Las pausas y correcciones se localizan por ID dentro de la conexión tenant y conservan al actor responsable.

## Endpoints

- Listado y resumen: `list`, `weekly-summary`.
- Exportación para nómina: `export`, con el mismo rango, colaborador, sucursal y estado del listado.
- Jornada: `check-in`, `check-out`, `biometric/check-in`.
- Pausas: `POST /{attendanceId}/breaks` y `PATCH /{attendanceId}/breaks/end`.
- Correcciones: `POST /{attendanceId}/corrections` y `PATCH /corrections/{correctionId}`.

El listado, resumen y exportación respetan las sucursales efectivas del colaborador autenticado. La exportación aplica `company_settings.reports.export_max_rows`, usa CSV UTF-8 y conserva minutos ordinarios, tardanza, horas extra y pausas.

## UI/UX Implementado

- El listado muestra horas trabajadas, ordinarias, extra, tardanzas, pausas acumuladas, pausa en curso y correcciones pendientes.
- Desde cada jornada se puede iniciar/finalizar pausa, solicitar corrección y aprobar o rechazar solicitudes pendientes.
- La exportación de nómina usa el mismo filtro visible para evitar reportes distintos a lo consultado.
- Los mensajes de corrección obligan a justificar rechazos y mantienen al usuario dentro del flujo sin salir de la pantalla.
