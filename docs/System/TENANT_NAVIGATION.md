# Navegación tenant

## Modelo visual

La navegación de cada tenant utiliza dos niveles permanentes en escritorio:

1. Barra primaria: muestra únicamente los módulos disponibles mediante iconos y tooltips.
2. Panel contextual: muestra las secciones y páginas del módulo activo.

La estructura visible se obtiene de los catálogos existentes de base de datos:

- `sections`: módulo principal.
- `menu_groups`: sección contextual.
- `sub_sections`: página o acción navegable.

`menu_categories` continúa disponible para ordenamiento y configuración interna, pero sus categorías no se muestran en la navegación.

El primer módulo se denomina `Mi espacio de trabajo` y agrupa las páginas estructurales:

- Mi espacio.
- Menú y favoritos.
- Mi cuenta.
- Dashboard.
- Reportes.

De esta forma ocupan un único acceso en la barra primaria y se seleccionan desde el panel contextual.

## Mi espacio y recomendaciones

Después de autenticarse, el usuario es dirigido a `/workspace`. Esta pantalla presenta:

- las últimas diez páginas visitadas;
- las diez páginas con mayor cantidad de visitas;
- accesos iniciales permitidos cuando todavía no existe actividad suficiente.

La navegación se registra mediante `UserNavigationService` únicamente para peticiones `GET` HTML exitosas cuya ruta corresponda exactamente a una `sub_section` activa. No se contabilizan solicitudes AJAX, respuestas JSON, errores ni la propia pantalla `workspace.index`.

Durante una recreación progresiva de bases tenant, `UserNavigationService` comprueba la disponibilidad de `user_navigation_metrics`. Si una base todavía no contiene la tabla, la navegación continúa sin registrar métricas y Mi espacio muestra accesos iniciales; no se generan excepciones ni se bloquea la carga de otros módulos.

`user_navigation_metrics` no es un historial de eventos. Conserva una sola fila por combinación `user_id` y `sub_section_id` con:

- `visit_count`: contador acumulado y atómico;
- `recent_rank`: posición de 1 a 10, o `NULL` cuando la página ya no pertenece a las últimas diez.

Por tanto, el número de filas por usuario está limitado naturalmente por la cantidad de páginas navegables del catálogo. No se almacenan fecha, hora ni una fila nueva por visita. Las consultas de Mi espacio vuelven a filtrar las páginas con `CompanySectionService`, por lo que nunca recomiendan módulos desactivados o no permitidos para el rol.

## Permisos y módulos habilitados

`CompanySectionService` sigue siendo la única fuente del menú. Antes de renderizar, filtra:

- módulos y páginas activos;
- módulos habilitados para la empresa;
- permisos del rol;
- preferencias de visibilidad del usuario.

La vista no replica consultas ni reglas de autorización. Si el servicio no entrega una sección o página, la navegación no la muestra.

La activación inicial se obtiene de `sub_sections.is_enabled_by_default`. Un rol con acceso total no omite esta licencia funcional de empresa: las rutas directas también se intersectan con `companies_sub_sections.status = active`. `CompanySectionService::clearCompanyCache()` invalida conjuntamente navegación y permisos cuando la plataforma modifica los módulos.

## Estado activo

La ruta actual es la fuente principal para determinar:

- módulo seleccionado;
- sección contextual;
- página actual.

La resolución se realiza en el servidor con el nombre almacenado en `sub_sections.dom_route`. Esto permite refrescar o abrir directamente cualquier URL sin depender del último clic.

Se conservan los `dom_id` y clases históricas para que los componentes Vue que todavía llaman a `Utils.navbarItem()` sigan funcionando. El estado activo principal se aplica únicamente a la página actual; los padres utilizan un tratamiento visual más tenue.

## Colapsado y responsive

- Escritorio expandido: barra primaria y panel contextual visibles con ancho fijo, sin cambios al pasar el cursor.
- Si el módulo activo solo ofrece una página, el icono navega directamente y el panel contextual no se renderiza.
- Escritorio colapsado: permanece únicamente la barra primaria; el hover no modifica su ancho.
- Tablet y móvil: la navegación completa funciona como drawer/offcanvas y conserva la jerarquía módulo, sección y página.

Las variables históricas `--br-sidebar-width` y `--br-sidebar-collapsed-width` se proyectan sobre las dimensiones de la navegación nueva. Navbar, panel contextual y contenido comparten así una sola fuente de ancho y nunca se superponen.

Las solicitudes de inicialización tienen un límite de 20 segundos. Ante error o timeout se cierra siempre el loader global y se muestra el mensaje correspondiente, evitando overlays permanentes cuando un endpoint no responde.

El botón existente del navbar continúa controlando el colapsado mediante la infraestructura del template y conserva su preferencia en `localStorage`.

## Acciones globales del navbar

- **Favoritos** conserva exclusivamente los accesos marcados por el usuario.
- **Anuncios** abre un panel independiente junto a Favoritos. Los avisos vigentes ya no se insertan dentro del contenido de cada página.
- El avatar con las iniciales abre **Mi perfil** y **Cerrar sesión**.
- Cerrar sesión, tanto desde el avatar como desde el riel, solicita confirmación antes de enviar el formulario `POST` protegido con CSRF.
- El acceso de salida usa una señal visual roja tenue en reposo y el tratamiento danger completo al pasar el cursor.

El seguimiento captura el nombre de ruta y la URL solicitada antes de ejecutar el controlador. Antes de incrementar el contador comprueba que ambas correspondan exactamente, evitando que una vista compartida —por ejemplo Nueva venta y Venta POS— registre otra página del catálogo.

## Archivos principales

- `resources/views/System/layouts/main.blade.php`: resolución y marcado accesible.
- `resources/css/System/br-branding/51-two-level-navigation.css`: diseño responsive de dos niveles.
- `app/Services/System/Organizations/Companies/CompanySectionService.php`: permisos y módulos disponibles.
- `database/seeders/SystemNavigationSeeder.php`: catálogo de módulos, secciones y páginas.
- `app/Services/System/Essentials/UserNavigationService.php`: contadores, recientes y recomendaciones.
- `app/Http/Middleware/TrackUserNavigation.php`: registro no intrusivo de navegación.
- `resources/views/System/general/Essentials/workspace/main.blade.php`: pantalla Mi espacio.
- `resources/css/System/br-branding/52-workspace.css`: presentación responsive de Mi espacio.
- `app/Http/Controllers/System/Essentials/AccountController.php`: edición segura de la cuenta autenticada.
- `resources/views/System/general/Essentials/account/main.blade.php`: formulario de datos personales.
- `resources/css/System/br-branding/53-account.css`: presentación responsive de Mi cuenta.

Después de modificar estilos ejecutar:

```bash
npm run build:css:system
```
