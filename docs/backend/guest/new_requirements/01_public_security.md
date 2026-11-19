# 01 - Seguridad pública

## Estado implementado

- Empresa activa derivada del slug y nunca del payload.
- Rate limiting por IP, empresa y recurso con límites centralizados en `config/public_access.php`.
- FormRequest, honeypot, CAPTCHA Turnstile configurable y límite diario para reclamos.
- Consulta pública por código seguro sin revelar datos internos.
- Asistencia mediante URL firmada y capacidad temporal de sesión.
- Credenciales por dispositivo biométrico, firma HMAC, idempotencia y bitácora.
- Contratos Guest con selección explícita de columnas y atributos sensibles ocultos.
