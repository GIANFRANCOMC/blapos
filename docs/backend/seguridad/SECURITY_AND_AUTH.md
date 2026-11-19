# System - Seguridad y autenticación

## Dónde vive cada dato

- Las cuentas y el historial de autenticación viven en la BD tenant.
- La contraseña se almacena mediante hash y nunca se recupera en texto plano.
- El guard `web` usa `App\Models\System\Organizations\User` sobre la conexión tenant activa.
- El navegador conserva cookies host-only; el contenido de sesión vive en `SESSION_DRIVER`.
- En producción se recomienda Redis para sesión y caché.
- El token CSRF está ligado a la sesión y protege las rutas web mutables.

## Inicio y cierre de sesión

1. Se valida el host y se conecta el tenant antes de abrir la sesión.
2. Usuario y compañía se consultan únicamente dentro de la BD tenant.
3. El rate limit combina tenant, email normalizado e IP.
4. Al autenticar se regenera el ID de sesión para impedir session fixation.
5. La sesión guarda `_tenant_database_id` y `_user_session_version`.
6. `EnsureTenantSession` bloquea el cruce entre tenants.
7. `EnsureAuthenticatedSession` invalida al usuario inactivo o cuya versión cambió.

Al cerrar sesión se registra el evento, se ejecuta logout, se invalida la sesión y se regenera CSRF.

## Revocación

`users.session_version` es el contador de revocación. Cambiar la contraseña o inactivar un usuario:

- incrementa `session_version`;
- borra `remember_token`;
- revoca tokens Sanctum;
- invalida las sesiones web del usuario en su siguiente solicitud.

La primera solicitud de una sesión anterior al despliegue inicializa su versión actual para evitar cierres masivos accidentales. Después de eso, cualquier cambio es estricto.

## Historial de autenticación

`authentication_events` registra tenant, empresa, usuario, evento, resultado, email, IP, user agent, motivo y fecha. El ID de sesión jamás se guarda: solo se conserva su hash SHA-256 cuando existe.

Eventos cubiertos:

- login correcto;
- credenciales inválidas;
- empresa o usuario inactivo;
- rate limit y CAPTCHA rechazado;
- cierre de sesión;
- sesión revocada por estado o versión.

El endpoint `GET /users/{id}/authentication-events` exige permiso de visualización de usuarios, limita por empresa y permite filtrar evento, resultado y fechas.

## Cookies recomendadas

```env
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=false
```

`Secure` es obligatorio en producción. La cookie se nombra con prefijo, slug y hash, pero la defensa principal es que sea host-only.

## Encabezados

`SecurityHeaders` aplica `nosniff`, `SAMEORIGIN`, política de referencia, permisos restringidos, CSP mínima, HSTS en HTTPS y `no-store` para HTML autenticado.

La CSP actual es compatible con vistas heredadas. Una CSP estricta con nonce requiere retirar scripts inline y validar recursos externos; por eso se trata como modernización de frontend, no como un cambio aislado de backend.

## Secretos y red

- `.env` no se versiona.
- El API de `company-settings` rechaza claves de secretos, contraseñas, tokens y credenciales.
- `CAPTCHA_ENABLED`, claves Turnstile y timeout se configuran por entorno; si no existe secreto, el verificador permanece desactivado.
- `APP_KEY`, credenciales DB/SMTP, tokens cloud y CAPTCHA no se guardan en tablas, logs ni documentación.
- `APP_DEBUG=false` y `LOG_LEVEL=warning` en producción.
- Landlord guarda nombres de BD, nunca credenciales.
- MySQL no debe exponerse públicamente.
- El usuario runtime no debe crear ni eliminar bases; usar `tenant:create --skip-create-database`.
- `TRUSTED_PROXIES` debe contener solo los proxies reales.
- CORS debe permitir únicamente orígenes necesarios.

## Segundo factor

El segundo factor no forma parte del alcance vigente. Si se aprueba como proyecto independiente, debe usar una librería TOTP/WebAuthn mantenida, recuperación segura y cifrado de secretos; nunca se implementará criptografía propia ni una simulación exclusivamente visual.

## Lista de salida a producción

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS y certificado wildcard vigente.
- `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=` y Redis protegido.
- Dominio raíz dirigido al proyecto independiente; wildcard dirigido a Blapos.
- Origen no accesible directamente cuando exista balanceador.
- Backups cifrados, restauración probada y retención definida.
- Dependencias auditadas, logs sin datos sensibles y alertas de autenticación.
- Workers reiniciados después de despliegue o rotación de secretos.

La rotación automática de secretos, backups y alertas centralizadas pertenecen a infraestructura y se describen en la documentación de despliegue; no deben presentarse como funcionalidad incompleta del backend.
