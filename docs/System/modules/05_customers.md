# 05 - Clientes

## Que Hace

Administra los clientes de una empresa. Un cliente puede comprar, recibir membresias, registrar asistencias y asociarse con huellas biometricas.

## Archivos

- Ruta: `routes/System/Customers/Customer.php`
- Controlador: `CustomerController`
- Requests: `StoreCustomerRequest`, `UpdateCustomerRequest`, `RegisterCustomerFingerprintRequest`
- Servicios: `CustomerService`, `CustomerConfigService`, `BiometricDeviceService`
- Modelo: `Customer`
- Vista/Vue: `resources/views/System/general/Customers/customers`, `resources/js/System/Pages/Customers/customers`
- Tablas: `customers`, `identity_document_types`, `subscriptions`, `customer_biometric_fingerprints`

## Campos Necesarios

- `identity_document_type_id`
- `document_number`
- `name`
- `email`
- `phone_number`
- `gender`
- `birthdate`
- `status`

## Reglas

- El cliente pertenece a una empresa.
- Documento y tipo de documento deben ser consistentes con longitudes del maestro.
- El registro de huella se hace contra un dispositivo biometrico activo de la misma empresa.
- No debe mezclarse con usuarios internos.

## Estado Backend Implementado

- La unicidad se valida y refuerza por empresa, tipo de documento y numero.
- La busqueda cubre documento, nombre, correo y telefono.
- Se incorporaron `emergency_contact_name`, `emergency_contact_phone` y `medical_notes` como datos opcionales.
- `RegisterCustomerFingerprintRequest` valida `biometric_device_id`, `device_user_id` y `finger_index` antes de registrar una huella.
- Si no se envia `device_user_id`, el backend reserva el siguiente disponible para el dispositivo.
- La combinacion `device_user_id + finger_index` no puede repetirse dentro del mismo dispositivo.

## Estado UI Implementado

- El formulario de cliente muestra un bloque opcional de contacto de emergencia y salud.
- El componente reutilizable `AddCustomer` usa el mismo bloque opcional para que clientes creados desde POS u otros modulos guarden la misma informacion.
- El bloque registra contacto, celular de emergencia y observaciones medicas sin mezclar estos datos con informacion comercial.
- Las tarjetas de clientes muestran contacto de emergencia y observaciones medicas solo cuando existen, manteniendo placeholders discretos cuando no hay informacion.
