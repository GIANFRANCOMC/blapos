# 04 - Eventos biométricos públicos

## Contrato backend

`POST /api/{company_slug}/biometric/events` recibe eventos de clientes o colaboradores sin sesión web.

Cabeceras obligatorias:

- `X-Device-Key`: clave pública del dispositivo.
- `X-Device-Signature`: HMAC SHA-256 del cuerpo JSON usando el secreto entregado al registrar o rotar credenciales.

El payload exige `event_uuid`, `event_type`, `subject_type`, `device_user_id` y `occurred_at`.

## Seguridad y trazabilidad

- Empresa resuelta desde subdominio/slug y no desde el cuerpo.
- Dispositivo activo y perteneciente a la empresa.
- Firma comparada con `hash_equals`.
- `event_uuid` idempotente por empresa y dispositivo.
- Máximo tres intentos automáticos antes de requerir revisión.
- Estado, error, payload, intentos y fecha de proceso se guardan en `biometric_device_events`.
- El secreto se almacena cifrado y nunca se serializa en el modelo.
- El rate limiting se aplica por empresa y clave del dispositivo.
