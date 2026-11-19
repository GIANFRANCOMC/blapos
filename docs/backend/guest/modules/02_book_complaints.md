# 02 - Libro de Reclamaciones Público

## Qué Hace

Permite que un visitante registre quejas, reclamos o sugerencias sin iniciar sesión, y consulte el estado con un código de seguimiento.

## Backend

- Valida documento, contacto, tipo, descripción y pedido mediante `StoreBookComplaintRequest`.
- Valida `branch_id` contra sucursales activas de la empresa para que cada solicitud quede asociada al local donde ocurrió el caso.
- Limita solicitudes por minuto, día, empresa e IP.
- Usa honeypot `website` y Turnstile cuando `CAPTCHA_ENABLED=true`.
- Genera `tracking_code` aleatorio y único por empresa.
- Separa `admin_response` de `public_response`.
- Registra estado inicial en `book_complaint_status_histories`.
- Admite hasta 5 adjuntos PDF, JPG o PNG de máximo 5 MB.
- Guarda adjuntos en disco privado dentro del directorio tenant; si falla la transacción, elimina archivos escritos.
- `GET /{company_slug}/book_complaints/status/{trackingCode}` expone únicamente código, tipo, estado, respuesta pública y fechas.

## UI/UX Implementado

- La pantalla separa dos modos: **Registrar solicitud** y **Consultar código**.
- El formulario muestra tipos como opciones claras: queja, reclamo o sugerencia.
- El formulario muestra un selector de sucursal antes de registrar datos personales.
- El CAPTCHA se renderiza solo cuando existe `CAPTCHA_KEY_FRONTEND`.
- Si Turnstile carga tarde, la pantalla reintenta el render por unos segundos sin bloquear el formulario.
- La carga múltiple muestra los nombres de archivos seleccionados antes de enviar.
- Al registrar correctamente, el formulario limpia campos, adjuntos y CAPTCHA para evitar reenvíos accidentales.
- Al registrar correctamente, el usuario ve el código de seguimiento en una alerta clara.
- La consulta normaliza el código de seguimiento antes de llamar al backend.
- La consulta por código no expone datos personales ni información interna de gestión.
