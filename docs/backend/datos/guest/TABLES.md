# Guest - Tablas usadas

Guest usa varias tablas administradas por System, pero desde una perspectiva publica y filtrada por empresa.

## companies

Sirve para resolver la empresa por `slug` y mostrar datos publicos: nombre comercial, descripcion, logos, telefono, email, direccion y redes.

Relaciones publicas: redes sociales, items visibles, sucursales.

## company_socials_media

Redes sociales visibles en el home publico: web, Facebook, Instagram, TikTok, WhatsApp u otros.

## items

Productos, servicios y membresias visibles en web si `see_my_web` esta activo. Si `see_my_web_price` no esta activo, Guest debe ocultar precio, minimo, maximo y moneda.

Relaciones publicas: moneda y categorias activas asociadas a items publicados.

## currencies

Se usa solo cuando el precio del item puede mostrarse publicamente.

## branches

Se usa para validar enlaces publicos de asistencia por sucursal. La sucursal debe pertenecer a la empresa resuelta por slug.

## customers

Se usa para identificar clientes en asistencia publica. No debe exponerse informacion completa del cliente sin necesidad.

## subscriptions

Se usa para validar si un cliente tiene membresia vigente en la sucursal antes de registrar asistencia.

## attendances

Se crea desde asistencia publica QR cuando el cliente cumple reglas de membresia y limite.

## book_complaints

Guarda quejas, reclamos y sugerencias publicas. Incluye sucursal, datos del reclamante, descripcion, solicitud, evidencia, IP, user agent, plataforma y navegador.

## identity_document_types

Tipos de documento usados en formularios publicos como libro de reclamaciones.

## biometric_devices

Puede usarse si se habilitan endpoints publicos o callbacks de dispositivos. Debe validarse con seguridad adicional.

## customer_biometric_fingerprints

Puede usarse indirectamente para resolver cliente por dispositivo y usuario del dispositivo.

## Criterios de exposición

- Toda tabla nueva de System requiere una justificación explícita antes de ser leída desde Guest.
- Los modelos Guest deben declarar campos visibles y ocultar tokens, auditoría y relaciones internas.
- Una relación disponible en Eloquent no implica que deba serializarse al visitante.
