# 01 - Inicio

## Qué hace

Pantalla interna para descubrir módulos y administrar accesos favoritos. Lista las secciones y subsecciones habilitadas para la empresa, permite buscar accesos, abrirlos y marcarlos como favoritos por usuario.

Home no decide qué módulos tiene contratados o habilitados una empresa. Esa disponibilidad viene de `companies_sub_sections`. Home solamente permite que el usuario organice sus favoritos dentro de ese conjunto autorizado.

Los favoritos también se muestran en la barra superior de todas las pantallas de System. De esta manera, Home funciona como configurador y la navbar como acceso rápido global.

Inicio y Dashboard se excluyen del directorio de Home porque son accesos estructurales de la plataforma, no módulos configurables por el usuario. No se listan en esta pantalla ni se permite marcarlos como favoritos desde el endpoint de Home.

## Archivos

- Ruta: `routes/System/Essentials/Home.php`
- Controlador: `HomeController`
- Request: `app/Http/Requests/System/Essentials/Home/UpdateHomePreferenceRequest.php`
- Modelo de preferencias: `UserPreference`
- Vista: `resources/views/System/general/Essentials/home/main.blade.php`
- Vue: `resources/js/System/Pages/Essentials/home`
- Estilos: `public/System/assets/css/br-branding.css`, parcial `br-branding/81-core-roles-entities.css`
- Layout global: `resources/views/System/layouts/main.blade.php`
- Branding global de favoritos: `public/System/assets/css/br-branding.css`
- Tablas: `users`, `user_preferences`, `companies_sub_sections`, `sections`, `sub_sections`

## Flujo de lectura

1. El layout solicita las secciones activas a `CompanySectionService`.
2. El servicio resuelve la caché `company_sections:company:{id}` o ejecuta una consulta optimizada.
3. `window.sections` expone las secciones y subsecciones habilitadas a Vue.
4. `window.preferences` expone las preferencias activas del usuario.
5. Vue construye un directorio compacto por sección y accesos por subsección.
6. Cada subsección muestra `dom_label` como título y `description` como contexto breve.
7. La búsqueda filtra localmente por sección, subsección o descripción y normaliza tildes.
8. Si no existe preferencia para una subsección, se considera no favorita.
9. El layout obtiene las mismas preferencias para construir el menú global de favoritos en la navbar.
10. Después de guardar, Home emite el evento `br:preferences-updated`.
11. El layout escucha el evento y actualiza la lista y el contador sin recargar la página.

La lectura de preferencias debe cargar siempre la relación `preferences` del usuario antes de exponer `window.preferences`. Si la relación no llega precargada, el accesor `formatted_preferences` consulta sus preferencias activas y evita que Home vuelva a valores por defecto después de recargar la pantalla.

La caché del menú se invalida automáticamente mediante `CompanySubSectionObserver` cuando cambia la habilitación de módulos para una empresa. Ya no depende de un listener ejecutado al autenticar al usuario.

## Configuración de interfaz

El contenido visible de Home se centraliza en `config.entity.page` dentro de `main.vue`. El template no debe contener textos funcionales repetidos.

La configuración incluye:

- `eyebrow`, `title`, `subtitle` y `modulesAriaLabel`.
- `filters.search`: placeholder, etiqueta accesible y tooltip para limpiar.
- `filters.favorites`: texto y tooltip del filtro.
- `filters.descriptions`: texto y tooltip para mostrar u ocultar la descripción breve de cada acceso.
- `empty`: mensajes para favoritos vacíos, búsquedas sin resultados y ausencia de módulos.
- `favorites`: tooltips para agregar o quitar.
- `confirmations`: título, mensaje y botones de cada confirmación.
- `menu.id`: identificador usado para activar el elemento correspondiente del sidebar.

Este patrón permite cambiar textos y comportamiento desde un único objeto, facilita futuras traducciones y debe repetirse en módulos con interfaces similares.

## Organización del componente Vue

`main.vue` conserva la Options API utilizada por System, pero organiza su implementación por responsabilidades para facilitar mantenimiento y crecimiento sin fragmentar prematuramente el módulo:

- `PAGE_CONFIG` contiene la configuración estática de la interfaz y se registra con `markRaw` para evitar observación reactiva innecesaria.
- `createForms()` crea una instancia independiente del formulario por cada montaje del componente.
- `normalizeSearchValue()` y `createConfirmationContent()` son funciones puras o aisladas del estado Vue.
- `data()` contiene únicamente estado mutable de la pantalla.
- `methods` concentra carga, persistencia, tooltips y acciones del usuario.
- `computed` deriva la configuración visible, preferencias, índices de ids, secciones filtradas y mensajes vacíos.
- `watch` reacciona solamente a cambios que requieren sincronizar integraciones externas, como Bootstrap Tooltip.
- `beforeUnmount` destruye los tooltips antes de retirar el componente.

Los ids disponibles y favoritos se indexan mediante propiedades computadas de tipo `Set`. Esto evita búsquedas repetidas con `Array.includes()` durante el renderizado y el filtrado, y mantiene la consulta de favoritos en tiempo constante.

La persistencia utiliza `try/catch/finally`: restaura el filtro cuando la solicitud falla, libera siempre el estado `isSaving` y reinicializa los tooltips aunque ocurra una excepción. El evento global de actualización se identifica mediante la constante `PREFERENCES_UPDATED_EVENT`.

El template consume el alias computado `page` y las subsecciones ya filtradas de cada sección. No debe duplicar filtros, normalizaciones ni rutas profundas como `config.entity.page` dentro del marcado.

## Preferencia guardada

Slug: `config_companies_sub_sections`.

Estructura:

```json
{
  "show_actions": false,
  "show_only_favorites": false,
  "show_descriptions": true,
  "sub_sections": [
    {
      "sub_section_id": 30,
      "visible_in_menu": true,
      "is_favorite": false
    }
  ]
}
```

- `show_actions`: campo de compatibilidad. La interfaz actual lo envía siempre como `false`.
- `show_only_favorites`: filtra Home para mostrar únicamente favoritos.
- `show_descriptions`: muestra u oculta la descripción breve de cada acceso sin afectar la búsqueda ni la disponibilidad del módulo.
- `visible_in_menu`: dato heredado que puede ser consumido por el menú lateral, pero ya no se modifica desde Home.
- `is_favorite`: única preferencia de subsección editable desde Home.

## Reglas implementadas

- Requiere usuario autenticado.
- Solo se puede marcar como favorita una subsección activa de la empresa del usuario.
- El id `0` se reserva para guardar el filtro global de favoritos sin modificar subsecciones.
- El frontend llama explícitamente a `/home/0` para guardar preferencias globales; el helper genérico de `PATCH` considera `0` como vacío y por eso Home construye esa URL directamente.
- Las subsecciones con slug `sc_home` y `sc_dashboard` no se pueden marcar como favoritas desde Home.
- Las actualizaciones parciales conservan el valor no enviado.
- El endpoint de Home no acepta cambios de `visible_in_menu`.
- Las subsecciones duplicadas dentro del JSON se normalizan por `sub_section_id`.
- Si existen varias preferencias activas con el mismo usuario/slug, se conserva la más reciente y las anteriores pasan a `inactive`.
- El backend devuelve las preferencias actualizadas y Vue refleja el cambio sin recargar Home.
- La respuesta del backend usa `formatted_preferences` con carga real de la relación; no debe devolver `[]` solo porque `preferences` no estaba precargada.
- Si la respuesta llega con una estructura incompleta o la memoria del navegador conserva preferencias antiguas, Vue reconstruye localmente la preferencia modificada y emite `br:preferences-updated` para mantener sincronizada la navbar.
- El filtro de favoritos también filtra las subsecciones dentro de cada sección.
- Home comunica las preferencias actualizadas al layout mediante un evento del navegador.
- El layout crea los elementos dinámicos con APIs del DOM y `textContent`, evitando insertar etiquetas provenientes de datos.
- Las confirmaciones construyen su contenido mediante nodos DOM y `textContent`; esto permite resaltar módulo y acceso sin aceptar HTML proveniente de datos.
- El botón de favorito cambia su `key` según el estado para reemplazar el nodo y descartar tooltips obsoletos.
- `Alerts.tooltips()` destruye directamente la instancia anterior, elimina nodos `.tooltip` residuales, conserva el título vigente y crea una nueva instancia sin animación.
- No se encadenan `hide()` y `dispose()`: esa combinación generaba una carrera asíncrona en Bootstrap al completar la transición.
- Antes de abrir una confirmación se elimina el foco del botón y se destruyen los tooltips; al cancelar se reinicializan con retraso para evitar residuos visibles.

## Interfaz

- Breadcrumb compacto al inicio de la pantalla con la ubicación `Inicio > Favoritos`, reutilizando el componente global `Breadcrumb`.
- Encabezado configurable, sin fondo decorativo, separado del contenido por un borde gris suave.
- En escritorio, título y controles comparten una franja compacta; en pantallas menores los controles pasan a una segunda fila.
- Búsqueda local por módulo o acceso, tolerante a mayúsculas y tildes.
- El buscador oculta el control de limpieza nativo del navegador y conserva un único botón configurable.
- Filtro para mostrar únicamente favoritos.
- Filtro para mostrar u ocultar descripciones, útil cuando la empresa tiene muchos módulos activos.
- Al ocultar descripciones se activa un modo compacto: filas, encabezados, iconos y botón de favorito reducen su altura para mostrar más módulos por pantalla.
- Directorio responsive en dos columnas de escritorio y una columna móvil.
- Filas compactas con título y descripción breve del acceso.
- Accesos directos por subsección mediante toda la fila clicable; no se muestra una flecha adicional para evitar ruido visual.
- Un único control de estrella amarilla para agregar o quitar favoritos.
- El botón de favorito queda separado de la zona de navegación para evitar acciones visualmente amontonadas.
- Tooltips descriptivos en controles con iconos.
- Navegación por teclado y etiquetas `aria-label` en acciones.
- Responsive en dos o una columna.
- Colores basados en tokens `--br-*` del branding.
- Contraste mediante azul de marca `#2899E5`, navy `#1A1A35` y sus superficies suaves.
- Confirmaciones diferenciadas: pregunta al agregar y advertencia al quitar.
- El título de las confirmaciones se mantiene centrado.
- El título ocupa las tres columnas internas de la rejilla de SweetAlert para conservar el centrado y el ancho completo.
- El mensaje y las acciones ocupan filas completas e independientes para evitar que texto y botones compartan una misma línea.
- El diálogo usa un ancho máximo de lectura, independiente del ancho total de la ventana.
- Icono, título, mensaje y acciones ocupan filas propias; el contenido se limita a `23rem` para mantener una composición centrada.
- La referencia muestra `Módulo` o `Módulo y acceso` en una superficie neutra y resalta el nombre exacto antes de explicar la consecuencia.
- Las confirmaciones establecen `allowEnterKey: false`; presionar Enter no agrega ni quita favoritos accidentalmente.
- El borde superior y el icono usan el color semántico correspondiente: información, advertencia, éxito o error.
- El botón cancelar usa un hover gris suave y conserva contraste accesible.

## Favoritos globales

El acceso global se encuentra dentro de la navbar de `resources/views/System/layouts/main.blade.php`.

- El botón se ubica en el lado izquierdo de la navbar y no utiliza tooltip.
- Muestra un icono azul discreto, texto y contador neutro en una escala compacta.
- En móvil oculta el texto y conserva icono y contador.
- La navbar se mantiene compacta, ocupa todo el ancho superior y elimina el velo celeste sobrante para liberar espacio vertical al contenido.
- La hamburguesa queda disponible como acción global para alternar el menú lateral, no solo como control móvil.
- El bloque de usuario se ubica a la derecha con iniciales, nombre, rol y acción de cierre de sesión dentro de un menú desplegable.
- El panel lista únicamente subsecciones activas y autorizadas para la empresa.
- Cada grupo muestra la sección como encabezado y cada subsección favorita como cuerpo, con descripción opcional.
- La jerarquía evita etiquetas repetidas o ambiguas como dos entradas llamadas únicamente `Gestión de clientes`.
- Si no hay favoritos, enlaza a Home para configurarlos.
- Se cierra mediante botón, clic exterior, fondo o tecla `Escape`.
- Usa `aria-expanded`, `aria-controls`, `aria-hidden` y foco visible.
- Al abrirse aplica desenfoque transparente al resto de la interfaz, sin velo gris; botón y panel permanecen nítidos.
- Reutiliza los tokens de `br-branding.css`; no introduce paletas paralelas.
- Reemplaza el anterior botón flotante y elimina bloques ocultos duplicados del sidebar.

## Mejoras aplicadas

- Preferencias documentadas.
- Validación por empresa y subsección mediante FormRequest.
- Consolidación de preferencias activas duplicadas.
- Normalización de registros duplicados en JSON.
- Interfaz minimalista orientada únicamente a favoritos.
- Eliminación del control de visibilidad del menú en Home.
- Tooltips y textos accesibles con ortografía corregida.
- Actualización visual alineada al branding.
- Configuración declarativa de todos los textos de Home.
- Búsqueda local de módulos y accesos.
- Confirmaciones seguras, claras y alineadas al branding.
- Menú global de favoritos en la navbar.
- Actualización inmediata del menú global después de guardar preferencias.
- Limpieza de la navegación duplicada y oculta del sidebar.
- Campo `sub_sections.description` incorporado al maestro y al modelo.
- Descripciones iniciales para todos los módulos registrados por la migración base.
- Grilla compacta 2/1 y búsqueda sobre descripciones.
- Tooltips de favoritos sincronizados después de cada cambio.
- Estrellas amarillas en Home sin cambios a negro durante hover; la navbar usa un icono azul más discreto.
- Panel global jerárquico con sección, acceso y descripción.
- Blur de atención al abrir favoritos.
- Alertas semánticas por tipo, títulos centrados y cancelación con hover suave.
- Componente Vue organizado por configuración, utilidades, estado, acciones y datos derivados.
- Configuración estática excluida de la reactividad mediante `markRaw`.
- Índices computados con `Set` para validar módulos disponibles y favoritos.
- Persistencia protegida con restauración y limpieza garantizada mediante `try/catch/finally`.
- Limpieza de tooltips durante el desmontaje del componente.
- Breadcrumb integrado como patrón base para módulos de entrada similares.
- Eliminación de la flecha visual en cada acceso de Home.
- Actualización local robusta de preferencias cuando se agrega o quita un favorito, con sincronización inmediata del panel global de favoritos.
- Preferencia `show_descriptions` incorporada a frontend, backend y JSON de usuario.
- Corrección del guardado de filtros globales usando `/home/0`, evitando que `Solo favoritos` o `Mostrar descripción` se reviertan después de guardar.
- Corrección de permanencia al recargar: `formatted_preferences` ya no depende de que la relación venga precargada y el layout fuerza la carga por empresa antes de construir `window.preferences`.
- Modo compacto cuando se ocultan descripciones.
- Exclusión de Inicio y Dashboard del directorio configurable de Home.

## Decisiones de evolución

- Las pruebas se añadirán cuando sean solicitadas expresamente.
- No se agrega una restricción única sobre preferencias porque el historial se conserva y la consolidación activa ocurre transaccionalmente.

## Datos del módulo

La tabla `sub_sections` contiene la información funcional de cada acceso:

- `dom_label`: nombre corto mostrado en menús y accesos.
- `description`: explicación breve de lo que permite realizar el módulo.
- `dom_route`: nombre de la ruta Laravel.
- `dom_id`: identificador usado para marcar navegación activa.
- `dom_icon`: icono opcional del acceso.

Las descripciones se crean en `2024_01_11_223124_create_init_masters_table.php`. Como el proyecto está en una etapa que permite reiniciar migraciones, el cambio se incorporó directamente a la migración base. En una base de datos persistente futura deberá utilizarse una migración incremental.
