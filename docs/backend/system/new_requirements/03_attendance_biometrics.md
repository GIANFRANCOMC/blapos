# 03 - Asistencias y biometricos

## Riesgo evaluado

La asistencia es un flujo crítico compartido por System, Guest y biometría. Se revisaron límites diarios, duplicados, idempotencia y salidas involuntarias.

## Requerimientos evaluados

- Límite diario de membresía aplicado.
- Checkout automático desde QR/biometría gobernado por configuración empresarial y desactivado inicialmente.
- Namespace e imports del servicio biométrico normalizados.
- Logs idempotentes de eventos biométricos implementados con firma, intentos y errores.
- Las pruebas de check-in/check-out se añadirán únicamente cuando sean solicitadas expresamente.

## Decision biometrica vigente

- Al crear un dispositivo biometrico sin estado explicito, el backend lo registra como `active`.
- Queda documentada la duda de negocio: decidir si un dispositivo biometrico debe generar automaticamente un activo patrimonial. Por ahora no se hace, porque puede ser alquilado o administrado por terceros.

## Impacto cerrado

El contrato quedó integrado entre clientes, membresías, asistencias, dispositivos y portal público.

## Estado backend

- Marca, modelo y dispositivo están separados y aislados por empresa.
- Cada evento conserva dispositivo, UUID, sujeto, fecha, payload, intentos y último error.
- La asistencia de cliente registra el dispositivo y la referencia del evento que la originó.
- `customer_attendance.biometric_duplicate_tolerance_seconds` evita lecturas repetidas.
- `customer_attendance.daily_limit_scope` permite contar el límite diario por sucursal o por empresa.
- `customer_attendance.allow_automatic_checkout` controla si QR o biometría pueden finalizar una asistencia activa; el valor inicial es `false` para evitar salidas involuntarias.
- `customer_attendance.max_active_hours` limita asistencias abiertas demasiado antiguas. Si se supera durante un nuevo ingreso, el backend finaliza la asistencia previa con observación auditada y permite registrar una nueva; si se intenta cerrar superando ese límite, bloquea y pide corrección.
- El tipo visible para escaneo por documento es `document_number`; `dni` y `dnie` quedan como alias de compatibilidad interna.
