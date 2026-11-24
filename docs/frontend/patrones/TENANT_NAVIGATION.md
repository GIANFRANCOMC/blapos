# Navegación tenant

> Estándar vigente de System: [FG global](../figma/IMPLEMENTACION_FG.md). Sus tokens, componentes y layout sustituyen la apariencia anterior. Las reglas funcionales de esta guía siguen vigentes. Guest y Platform conservan sus límites.


## Modelo visual

La navegación de System utiliza un riel de mundos y un panel contextual que se abre bajo demanda.

1. El riel muestra los mundos disponibles mediante iconos compactos con tooltips y permanece visible en escritorio y tablet.
2. El panel contextual muestra los grupos y páginas disponibles del mundo seleccionado.

La barra superior de System abarca todo el ancho disponible. El isotipo de Blapos se alinea con el riel de mundos y el control del panel lateral queda a su derecha. El buscador se centra respecto de la pantalla. Favoritos, avisos y cuenta se agrupan a la derecha con 4 px entre sus botones.

El riel usa azul pizarra (`--fg-navigation-rail`, `#23354a`) para distinguir los mundos del panel contextual y la barra superior, ambos blancos. Una línea de 1 px suavizada con `--fg-surface` recorre el borde inferior de la barra. El contraste de superficies delimita el canto derecho de la navegación. Los iconos del riel en reposo usan `--fg-border-strong`. El mundo activo usa `--fg-primary`, icono blanco e indicador de 2 × 24 px en `--fg-primary-muted`. El hover del submódulo usa gris claro y la página activa usa azul suave y peso medio. [Biblioteca Componentes](../figma/README.md).

La estructura visible se obtiene de los catálogos existentes de base de datos:

- `sections`: módulo principal.
- `menu_groups`: sección contextual.
- `sub_sections`: página o acción navegable.

`menu_categories` continúa disponible para ordenamiento y configuración interna, pero sus categorías no se muestran en la navegación.

El isotipo abre **Inicio** directamente en `/home` cuando el rol tiene acceso. Para las cuentas sin ese permiso se muestra como marca estática. Si `sc_workspace` contiene otras páginas visibles y permitidas, el riel muestra **Espacio de trabajo** para acceder a ellas. Los mundos se centran en el riel y cuentan con desplazamiento cuando falta espacio.

Los tooltips del riel se muestran desde 768 px. En móvil se retiran al abrir o cambiar el ancho para dejar visible el panel de submódulos. El nombre accesible de cada control permanece disponible.

**Operaciones y clientes** abre el riel en el catálogo estándar. **Servicios y asistencias** reúne Servicios en curso y Asistencias de clientes. **Gestión de clientes** reúne Clientes, Membresías de clientes e Historial. **Atención al cliente** contiene Notificaciones y Libro de reclamaciones.

**Equipo y activos** sigue a Catálogo comercial y contiene Personal, Activos y Control de acceso. **Analítica** reúne **Resumen del negocio**, **Rendimiento comercial**, **Rotación y reposición** y **Reportes**. El engranaje identifica **Configuración**, donde se gestionan los perfiles en Seguridad y acceso y se encuentran Mi cuenta y Favoritos en Preferencias. [Mapa de mundos, grupos y páginas](00_menu_order.md).

En instalaciones nuevas, `SystemNavigationSeeder` da de alta el catálogo y el aprovisionamiento lo proyecta a la empresa y a los perfiles según sus reglas de acceso. El diseño nuevo se aplica de forma explícita a cada instalación existente con `php artisan tenant:navigation-sync demo --apply-layout`, usando el slug del tenant correspondiente. Se conservan los IDs de las opciones, sus permisos y las preferencias vinculadas. Los grupos reemplazados pasan a inactivos cuando quedan vacíos. Los grupos con páginas propias del tenant se conservan. La aplicación de la topología ocurre en el tenant indicado, sin una migración adicional.

## Inicio y accesos frecuentes

Después de autenticarse, el usuario llega a `/home`. Esta pantalla muestra hasta seis acciones. Prioriza las páginas más visitadas y completa el espacio con otros accesos permitidos. `/workspace` sigue disponible como enlace anterior a Inicio.

`UserNavigationService` contabiliza peticiones `GET` HTML exitosas que coincidan exactamente con una opción activa. La propia pantalla `home.index`, las consultas AJAX, las respuestas JSON y los errores quedan fuera del contador.

Durante una recreación progresiva de bases tenant, `UserNavigationService` comprueba la disponibilidad de `user_navigation_metrics`. Si la tabla aún no existe, Inicio muestra los accesos iniciales y los demás módulos siguen disponibles.

`user_navigation_metrics` no es un historial de eventos. Conserva una sola fila por combinación `user_id` y `sub_section_id` con:

- `visit_count`: contador acumulado y atómico;

El número de filas por usuario está limitado por la cantidad de páginas navegables del catálogo. El contador acumula visitas sobre la misma fila. Inicio utiliza `CompanySectionService` para mostrar solo los módulos activos y permitidos para el rol.

## Permisos y módulos habilitados

`CompanySectionService` sigue siendo la fuente de los accesos navegables. Antes de renderizar, filtra:

- módulos y páginas activos;
- módulos habilitados para la empresa;
- permisos del rol;
- preferencias de visibilidad del usuario.

El panel lateral de **Analítica → Indicadores** muestra los informes habilitados y permitidos para la cuenta. Si el mundo solo tiene una página disponible, el riel enlaza directamente a esa página. [Rotación y reposición](../../backend/system/modules/29_stock_turnover.md).

La activación inicial se obtiene de `sub_sections.is_enabled_by_default`. Las rutas directas comprueban `companies_sub_sections.status = active`, también para roles con acceso total. `CompanySectionService::clearTenantCache()` invalida navegación y permisos dentro del tenant activo.

## Estado activo

La ruta actual es la fuente principal para determinar:

- módulo seleccionado;
- sección contextual;
- página actual.

La resolución se realiza en el servidor con el nombre almacenado en `sub_sections.dom_route`. Esto permite refrescar o abrir directamente cualquier URL sin depender del último clic.

Los componentes Vue marcan el mundo y la página con `navbarItem()` de `Menu.js`. El estado activo principal se aplica a la página actual y los padres utilizan un tratamiento visual más tenue.

## Colapsado y responsive

- Desde 768 px, el riel conserva sus 60 px. El control abre y cierra el panel del mundo y la página reserva el ancho correspondiente. La página abre con su panel cuando tiene submódulos.
- Hasta 767 px, el control abre y cierra toda la navegación lateral. El menú empieza cerrado y la página ocupa todo el ancho. Si todavía no hay un mundo seleccionado, se abre el riel para elegirlo.
- Al cerrar el panel, la página recuerda el último mundo que lo tenía abierto. El mismo control vuelve a mostrarlo. En escritorio y tablet, el control permanece deshabilitado hasta elegir un mundo con panel cuando no hay uno disponible.

Los submódulos sin `menu_group_id` aparecen juntos al inicio del panel contextual, sin encabezado. Luego aparecen los grupos por `menu_groups.order`; dentro de cada grupo se conserva el orden de las opciones habilitadas para la empresa.

El navbar mide 44 px en escritorio y tablet. En móvil mide 48 px para acomodar los controles de 44 px de alto. Permanece arriba al desplazar la página y su altura determina dónde empiezan el riel, el panel contextual y el fondo del menú móvil. El buscador mide hasta 340 px de ancho, con 28 px de alto en escritorio y tablet y 32 px en móvil. El isotipo conserva 32 px. La cabecera del panel puede crecer si el título ocupa más de una línea. Los accesos del riel mantienen áreas de interacción de 40 × 40 px con 6 px de separación.

El panel contextual tiene 192 px reservados. El contraste con el riel delimita el panel durante la apertura y el cierre. Usa `--fg-body-size` (13 px) para el título, `--fg-micro-size` (10 px) para los grupos y `--fg-small-size` (11 px) para las opciones. Los grupos se separan con `--fg-space-4` (16 px). Las filas usan `--fg-control-height-small` en escritorio y tablet (32 px) y `--fg-control-height` en móvil (44 px). Cada opción usa su icono de Font Awesome desde `sub_sections.dom_icon`, con un icono de archivo para los registros antiguos que carecen de valor. La columna de 16 px y el espaciado FG de 8 px mantienen alineados los rótulos que ocupan más de una línea.

El icono decorativo de submódulo usa `--fg-micro-size` (10 px) para dar prioridad al rótulo. Los glifos del riel usan `--fg-control-icon-size` (16 px) y mantienen un área de interacción de 40 px. El icono del submódulo usa un tono neutro en reposo y adopta el azul FG en la opción activa. El rótulo da nombre accesible al enlace y la fila completa conserva su área de interacción y foco. [Reglas de navegación](../../../resources/css/System/fg/navigation.css).

### Transición y foco

- La apertura y el cierre usan `--fg-load-duration` (180 ms) y `--fg-load-easing` (`cubic-bezier(0.2, 0, 0, 1)`). En escritorio, el marco pasa de 60 px a 252 px al abrir el contexto y el espacio reservado a la página acompaña el cambio. El panel conserva su ancho durante el recorte y la transición de 8 px. Al pasar entre mundos con panel contextual, el marco y la página conservan su posición y se anima la entrada del nuevo contenido.
- En móvil, la navegación completa se desliza y el fondo se desvanece. Ambos comienzan debajo de la barra superior y comparten su referencia `--fg-topbar-height`. El control permanece accesible durante la transición.
- Al comenzar el cierre se desactiva la interacción con el panel y se oculta su contenido a lectores de pantalla. Su representación visual permanece hasta completar la salida. Con movimiento reducido, el componente aplica el estado final de inmediato.
- El icono de panel indica la acción con una flecha que cambia según `aria-expanded`. En móvil, al abrir la navegación, el foco pasa al riel. Al elegir un mundo, pasa al primer enlace disponible del panel. En móvil, Escape, la X y el fondo cierran la navegación y devuelven el foco al control. En escritorio y tablet, Escape cierra el panel y devuelve el foco al control que lo abrió.

Verifica que el riel quede visible al cerrar en escritorio y tablet y que se cierre junto al panel en móvil. Comprueba el enlace Inicio en el navbar y el centrado del buscador con el panel abierto y cerrado. Prueba además un cambio de mundo con el panel abierto, cierre y reapertura rápida, `Escape`, `Ctrl+K` o `Cmd+K`, foco al salir y movimiento reducido. Repite en escritorio y móvil con un título largo. [Patrón reutilizable de paneles](INTERACTION_PATTERNS.md#apertura-y-cierre-de-paneles).

Las solicitudes de inicialización tienen un límite de 20 segundos. Ante error o timeout se cierra siempre el loader global y se muestra el mensaje correspondiente, evitando overlays permanentes cuando un endpoint no responde.

## Acciones globales del navbar

Los botones del navbar tienen fondo transparente e iconos neutros en reposo. Favoritos y avisos usan glifos de 12 px. La inicial de 11 px se centra en un círculo gris claro de 24 px. Con puntero, un círculo de 28 px en `--fg-table-head` señala el botón bajo el cursor. El foco de teclado conserva su señal visible. El panel lateral indica si está abierto mediante el sentido de su flecha.

- Favoritos, Notificaciones y Cuenta reutilizan un panel de 264 px con cabecera, contenido y pie opcional. Los tres se alinean al borde derecho de las acciones, adaptan su ancho a la pantalla y permiten desplazamiento interno para listas largas. Las filas interactivas miden al menos 32 px en escritorio y 44 px en móvil. Las flechas recorren los enlaces y Escape devuelve el foco al botón que abrió el panel.
- **Buscar módulos** encuentra páginas accesibles por nombre, mundo o descripción y prioriza las coincidencias en el nombre. El acceso a Inicio se concentra en el isotipo. En móvil la barra sigue en una fila, con resultados que aprovechan el ancho disponible. Hasta 480 px se muestra «Buscar…» y se conserva el nombre accesible completo.
- El campo tiene forma de cápsula, usa el fondo neutro `--fg-table-head` y carece de borde. El valor y el placeholder usan `--fg-help-size` (12 px), y la lupa usa `--fg-small-size` (11 px). El foco se indica con el cambio de fondo, más visible al navegar con Tab, y conserva su contorno en el modo de alto contraste.
- El buscador muestra hasta seis filas completas en el espacio habitual. El resto de las coincidencias sigue disponible mediante desplazamiento dentro de la lista. Cada fila muestra el icono de `sub_sections.dom_icon`, el nombre de 12 px y el mundo de 11 px. Al cambiar el texto o reabrir, la lista vuelve al inicio.
- En el buscador vacío, el módulo abierto aparece primero con «Estás aquí» y `aria-current="page"`. Al escribir, el orden sigue la relevancia de las coincidencias y el indicador permanece en el módulo abierto cuando coincide con la consulta.
- `Ctrl+K` o `Cmd+K` abre el buscador y, en móvil, cierra primero el panel de submódulos si está abierto. Las flechas recorren los resultados, `Enter` abre la opción activa y `Escape` cierra los resultados y devuelve el foco al campo. La cantidad de resultados o el estado vacío se anuncia a lectores de pantalla.
- **Favoritos** agrupa los accesos guardados por mundo, muestra el icono de cada módulo y separa **Configurar favoritos** en el pie. La cantidad, los accesos y el estado vacío se actualizan al guardar preferencias.
- **Notificaciones** muestra la cantidad de avisos vigentes. Cada aviso presenta título, mensaje y fecha, con un icono que identifica su nivel. El estado vacío indica «No hay avisos vigentes».
- **Cuenta** presenta el avatar, el nombre, la empresa, el correo y el perfil en una tarjeta con degradado de `--fg-primary-soft` a `--fg-surface-soft`. Los enlaces **Mi cuenta** y **Favoritos** están debajo, y **Cerrar sesión** ocupa el pie.
- **Cerrar sesión** está en el menú del avatar. Solicita confirmación antes de enviar el formulario `POST` protegido con CSRF.

El seguimiento captura el nombre de ruta y la URL solicitada antes de ejecutar el controlador. Antes de incrementar el contador comprueba que ambas correspondan exactamente, evitando que una vista compartida —por ejemplo Nueva venta y POS venta— registre otra página del catálogo.

## Archivos principales

- `resources/views/System/layouts/main.blade.php`: resolución y marcado accesible.
- `resources/views/System/components/navbar-panel.blade.php`: estructura compartida de los tres desplegables del navbar.
- `resources/js/System/Layout/Menu.js` reúne el cambio de mundo, la búsqueda del navbar, los favoritos, el marcado de la página activa y la salida de sesión.
- `resources/css/System/fg/navigation.css`: diseño responsive de dos niveles.
- `app/Services/System/Organizations/Companies/CompanySectionService.php`: permisos y módulos disponibles.
- `database/seeders/SystemNavigationSeeder.php`: catálogo de módulos, secciones y páginas.
- `app/Services/System/Essentials/UserNavigationService.php`: contadores y accesos frecuentes.
- `app/Http/Middleware/TrackUserNavigation.php`: registro no intrusivo de navegación.
- `resources/views/System/general/Essentials/workspace/main.blade.php`: página de Inicio.
- `resources/css/System/fg/modules/workspace.css`: presentación responsive de Inicio.
- `app/Http/Controllers/System/Essentials/AccountController.php`: edición segura de la cuenta autenticada.
- `resources/views/System/general/Essentials/account/main.blade.php`: formulario de datos personales.
- `resources/css/System/fg/modules/index.css`: presentación responsive de Mi cuenta.

Después de modificar estilos ejecutar:

```bash
npm run build
```
