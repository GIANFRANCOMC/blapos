# 03 - Asistencia Pública

## Qué Hace

Permite registrar asistencia de clientes desde un enlace firmado y temporal asociado a una sucursal.

## Archivos

- Ruta: `routes/Guest/TrackingAttendance.php`
- Controlador: `Guest/TrackingAttendanceController`
- Middleware: `EnsurePublicAttendanceAccess`
- Servicio: `TrackingAttendanceBusinessService`
- Generador del enlace: `BranchController::publicAttendanceLink`
- Tablas: `branches`, `customers`, `subscriptions`, `attendances`

## Flujo Seguro

1. Un usuario System autorizado solicita `GET /branches/{id}/public-attendance-link`.
2. Backend valida empresa, sucursal activa y vencimiento entre 5 minutos y 7 días.
3. El visitante abre la URL firmada.
4. El GET validado guarda en sesión una capacidad temporal con empresa, sucursal y expiración.
5. El POST de lectura QR exige esa capacidad, coincidencia de empresa/sucursal y rate limit.

La ruta pública sin firma responde 404 y no concede acceso.

## Reglas

- Usa tipo `qr_public`.
- No expone datos completos del cliente.
- La sucursal debe pertenecer a la empresa activa.
- La membresía debe estar vigente y respetar el límite diario.
- `customer_attendance.auto_checkout_active` define si una lectura posterior cierra automáticamente una asistencia activa; por defecto permanece desactivado.
- Los límites antiabuso se obtienen desde `config/public_access.php`.

## UI/UX Implementado

- La pantalla muestra empresa, sucursal y vencimiento del enlace.
- El estado del scanner indica: listo, validando, aceptado, advertencia o error.
- El POST usa la sucursal del enlace firmado como prioridad sobre cualquier contexto visual.
- Los mensajes de error no revelan datos internos ni datos personales del cliente.
- Si el QR no corresponde, se pide reintentar sin describir reglas internas.
- El lector conserva controles simples para iniciar o detener cámara y muestra errores claros si el navegador no permite acceder a la cámara.
