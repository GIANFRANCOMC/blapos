# Páginas de error

Las respuestas HTML de error usan una vista compartida (`resources/views/errors/page.blade.php`) y los estilos de `98-error-pages.css`. Mantienen la tipografía Inter, los colores de marca y una presentación ligera sin depender del layout autenticado ni de datos del tenant.

Hay páginas para `400`, `401`, `403`, `404`, `405`, `419`, `422`, `429`, `500` y `503`. Cada archivo numérico define el título y la explicación; la vista compartida resuelve el logo, el mensaje opcional `msg` y el enlace de salida. `401` y `419` llevan al inicio de sesión; `503` permite reintentar; los demás llevan al inicio.

Los controladores siguen determinando el código HTTP. Estas vistas no modifican autorizaciones ni reemplazan las respuestas JSON: una petición que espera JSON conserva el contrato de error correspondiente. Los mensajes opcionales se imprimen escapados.
