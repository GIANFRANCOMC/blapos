# 20 - Dispositivos biométricos

Se encuentra en **Equipo y activos → Control de acceso**.

## Backend

- Marca y modelo son catalogos separados por empresa.
- Cada dispositivo pertenece a empresa, sucursal y modelo.
- Al crear un dispositivo sin estado explícito, el backend lo registra como `active` para que pueda usarse inmediatamente.
- Al crearlo se generan `access_key` y secreto; el secreto se devuelve una sola vez y se almacena cifrado.
- `rotateCredentials` invalida las credenciales anteriores y conserva fecha de rotacion.
- `PATCH /biometric_devices/{id}/credentials` ejecuta la rotacion, audita el actor y devuelve el nuevo secreto una sola vez.
- `last_seen_at` registra el ultimo evento procesado.
- `biometric_device_events` conserva eventos idempotentes, intentos, error y estado.
- `GET /biometric_devices/{id}/events` lista contactos, errores y eventos procesados por dispositivo dentro de la empresa actual.
- Clientes y colaboradores usan tablas de huellas separadas, pero comparten la reserva de `device_user_id`.
- Clientes usan `RegisterCustomerFingerprintRequest` y colaboradores usan `RegisterUserFingerprintRequest`; ambos validan pertenencia empresarial del dispositivo antes de persistir.

## UI/UX Implementado

- El listado muestra ultimo contacto y conteo de eventos fallidos o pendientes.
- La accion de rotar credenciales solicita confirmacion y muestra `access_key` y secreto nuevo en una modal de lectura unica.
- La accion de eventos abre un historial paginado con fecha, tipo, sujeto, intentos, estado y error.
- Los secretos se presentan en bloques monoespaciados reutilizables para evitar confundirlos con texto editable.

## Endpoint

El contrato firmado se documenta en `docs/backend/guest/modules/04_biometric_devices.md`.

## Criterio Operativo

El dispositivo no se crea automaticamente como activo patrimonial porque puede ser alquilado, estar en comodato o ser administrado por un tercero. La duda queda documentada: si el negocio decide tratar todo dispositivo biometrico como activo fijo, se debe crear una regla configurable para generar el registro en `assets` al guardar el dispositivo. Por ahora, el estado operativo por defecto es `active`, pero no crea activo patrimonial.
