# Interfaz de administración SaaS

La interfaz autenticada utiliza un único shell Vue. Cambiar entre el listado y el detalle actualiza `history.pushState` y consulta JSON; no vuelve a descargar el layout, las fuentes, Bootstrap ni el bundle de la aplicación.

### Capa de presentación

- `resources/views/Platform/layouts/app.blade.php`: documento HTML compartido por login y shell.
- `resources/views/Platform/shell.blade.php`: único punto de montaje autenticado y configuración segura serializada con `Js::from`.
- `resources/js/Platform/App.vue`: cabecera compacta, menú único de usuario, edición segura del perfil, notificaciones y cierre de sesión.
- `resources/js/Platform/pages/TenantIndex.vue`: filtros, paginación, indicadores y aprovisionamiento.
- `resources/js/Platform/pages/TenantDetail.vue`: estado, módulos y avisos.
- `resources/js/Platform/api.js`: cliente Axios con CSRF, cabeceras JSON y mensajes normalizados.

Las vistas Blade anteriores de listado y detalle se eliminaron. Ninguna página autenticada debe agregar un nuevo `@vite` propio: las funcionalidades deben incorporarse al entry `resources/js/Platform/app.js` y dividirse en componentes.
