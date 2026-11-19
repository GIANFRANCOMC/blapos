# 00 - Alcance público Guest

## Qué hace

Guest expone experiencias para visitantes y clientes finales sin permitir acceso al panel System.

## Rutas

1. `{company_slug}/home`
2. `{company_slug}/book_complaints`
3. `{company_slug}/tracking_attendances`
4. `{company_slug}/biometric_devices`

## Reglas

- La empresa se deriva de `company_slug` y debe estar activa.
- El visitante nunca envía ni controla `company_id`.
- Toda sucursal se valida contra la empresa resuelta.
- Los modelos Guest seleccionan únicamente campos públicos; secretos, tokens y auditoría permanecen ocultos.
- Reclamos, consulta de estado, asistencia y biometría tienen límites configurables por IP, empresa y recurso en `config/public_access.php`.
- La asistencia pública exige entrada por URL firmada y una capacidad temporal guardada en sesión antes de aceptar el POST.
- Los dispositivos biométricos usan credenciales propias, firma e idempotencia; no heredan una sesión de usuario.
