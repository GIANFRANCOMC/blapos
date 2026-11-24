# 00 - Navegación y orden del menú System

## Fuente canónica

La definición vigente se encuentra en `menu_categories`, `sections`, `menu_groups` y `sub_sections`. Contiene:

- categorías principales;
- secciones desplegables;
- grupos visuales dentro de una sección;
- opciones, rutas, etiquetas y descripciones.

`SystemNavigationSeeder` carga el diseño inicial y `SystemCatalogSyncService` completa las proyecciones de la empresa y los perfiles con acceso total. El sidebar, el buscador y Favoritos consumen las rutas y etiquetas de la base de datos.

Para una instalación ya poblada, `php artisan tenant:navigation-sync demo --apply-layout` aplica el diseño al tenant indicado. La operación recalcula el orden empresarial de los accesos reorganizados y conserva los IDs, los permisos, la habilitación, los favoritos y las métricas. [Navegación tenant](TENANT_NAVIGATION.md).

## Resolución y renderizado

`CompanySectionService` obtiene solamente las opciones:

1. activas en el catálogo;
2. habilitadas en `companies_sub_sections`;
3. permitidas para el rol cuando no tiene acceso total.

El servicio carga categoría y grupo, aplica el orden empresarial y genera la URL únicamente si la ruta existe. El layout Blade agrupa el resultado; no reconstruye el catálogo y no muestra encabezados vacíos cuando las preferencias ocultan todas sus opciones.

## Mapa de mundos

La distribución base tiene diez destinos, con Inicio en el isotipo del navbar y nueve mundos en el riel lateral. El isotipo lleva a `/home`, donde se muestran los accesos frecuentes y la ficha de la empresa. Los mundos agrupan las tareas por propósito. [Ubicación de Inicio y controles del panel](TENANT_NAVIGATION.md#modelo-visual).

| Mundo | Grupo | Páginas |
| --- | --- | --- |
| Inicio | — | Inicio |
| Operaciones y clientes | Servicios y asistencias | Servicios en curso, Asistencias de clientes |
| | Gestión de clientes | Clientes, Membresías de clientes, Historial |
| | Atención al cliente | Notificaciones, Libro de reclamaciones |
| Ventas | Registro de ventas | Nueva venta, Listado de ventas |
| | Punto de venta | POS venta, POS restaurante |
| | Cotizaciones | Nueva cotización, Listado de cotizaciones |
| | Entregas y cobros | Entregas pendientes, Cuentas por cobrar |
| Compras | Registro de compras | Nueva compra, Compras registradas |
| | Recepción y pagos | Ingresos pendientes, Cuentas por pagar |
| | Proveedores | Proveedores |
| Caja | Operación de caja | Cajas, Aperturas y cierres, Movimientos |
| | Resumen y gastos | Resumen de caja, Gastos varios |
| Inventario | Existencias | Control de stock |
| | Traslados y guías | Traslados, Guías |
| | Movimientos y costos | Kardex, Kardex valorizado |
| Catálogo comercial | Cocina | Recetas y platillos |
| | Oferta comercial | Productos, Servicios, Planes de membresía |
| | Clasificación | Categorías, Marcas |
| Equipo y activos | Personal | Colaboradores, Asistencia del personal |
| | Activos | Activos, Gestión de activos |
| | Control de acceso | Dispositivos biométricos |
| Analítica | Indicadores | Resumen del negocio, Rendimiento comercial, Rotación y reposición, Reportes |
| Configuración | Preferencias | Mi cuenta, Favoritos |
| | Empresa y sedes | Mi empresa, Sucursales |
| | Seguridad y acceso | Perfiles de acceso |
| | Configuración del negocio | Maestros internos |

**Operaciones y clientes** pone primero las tareas del día en **Servicios y asistencias**. **Gestión de clientes** reúne las fichas, las membresías y el historial. Las notificaciones y el Libro de reclamaciones siguen en **Atención al cliente**. **Equipo y activos** agrupa el trabajo con personal, bienes y dispositivos biométricos. Los perfiles y permisos se consultan en **Configuración → Seguridad y acceso**.

Se aplicó el cambio de grupos de Operaciones y clientes en la demo local. Se comprobó el orden del menú en escritorio y a 320 px y se abrieron Asistencias de clientes y Membresías de clientes. La comparación del catálogo y su proyección confirmó que los IDs, rutas, permisos y estados de habilitación de estas opciones se conservaron.

POS restaurante se encuentra en **Ventas → Punto de venta**. **Catálogo comercial** define la oferta, incluidos los planes de membresía. Las vigencias asignadas se consultan en **Operaciones y clientes → Gestión de clientes → Membresías de clientes**. **Inventario** registra existencias, traslados y costos. **Analítica** reúne indicadores y reportes del negocio.

El riel utiliza iconos y etiquetas de la base de datos. El panel contextual muestra grupos compactos y resalta la página actual. El breadcrumb utiliza mundo, grupo y página cuando aportan contexto distinto. Los submódulos sin grupo aparecen primero. El riel se centra cuando cabe y usa desplazamiento vertical cuando debe mostrar más mundos.

## Indicadores de Analítica

El grupo **Indicadores** ubica **Rendimiento comercial** en el orden 2 y **Rotación y reposición** en el 3. La visibilidad y el acceso dependen de la habilitación de la empresa y del permiso del perfil para cada informe. [Rendimiento comercial](../../backend/system/modules/28_commercial_performance.md) · [Rotación y reposición](../../backend/system/modules/29_stock_turnover.md).

## Orden por organización

- La categoría define el primer nivel de orden.
- `sections.order` ordena secciones dentro de la categoría.
- `menu_groups.order` ordena grupos dentro de la sección.
- `sections.description` contiene el resumen breve que acompaña al nombre e icono del mundo.
- `sections.dom_icon` define el icono del mundo. Configuración usa `fa-solid fa-gear`.
- `menu_groups.dom_icon` define el icono de cada grupo; la vista no asigna iconos por nombre o ruta.
- `sub_sections.dom_icon` define el icono propio del submódulo que se muestra junto al rótulo. [Estándar visual del panel](TENANT_NAVIGATION.md#colapsado-y-responsive).
- `sub_sections.order` ordena opciones dentro del grupo.
- `companies_sub_sections.section_order` y `sub_section_order` almacenan la proyección ordenada por organización; el segundo combina el orden del grupo con el de la opción para mantener juntos sus accesos.
- `SystemCatalogSyncService` y la administración central usan el mismo cálculo para componer categoría, sección, grupo y opción; así los grupos no se intercalan al sincronizar o guardar módulos.
- `tenant:navigation-sync` completa faltantes. La opción `--apply-layout` exige un único slug y actualiza la composición del menú y su orden dentro de ese tenant en una transacción.

## Permisos y preferencias

- `companies_sub_sections` habilita una opción para una organización.
- `role_sub_sections` restringe opciones y acciones para perfiles sin acceso total.
- `user_preferences`, mediante `config_companies_sub_sections`, conserva favoritos y visibilidad individual.
- Una preferencia nunca puede habilitar un módulo que la organización o el rol no tengan permitido.
- Durante la etapa reiniciable los módulos operativos se habilitan por defecto para cada organización.

## Incorporación de una opción

1. Crear y nombrar la ruta.
2. Incorporar la opción con ID estable en `SystemNavigationSeeder`. Para adaptar un tenant existente, usar la aplicación explícita del diseño de navegación.
3. Asociarla a una sección y, cuando corresponda, a un grupo.
4. Mapear permisos compartidos en `config/permissions.php` si la ruta no tiene correspondencia directa.
5. Ejecutar `php artisan system:sync`.
6. Ejecutar `php artisan system:doctor` para verificar la ruta.
7. Actualizar este documento y el archivo del módulo.

## Caché

La clave general usa el namespace físico del tenant y el rol `tenant:{namespace}:company_sections:v2:role:{roleId|all}`. La versión renueva el menú y el mapa de permisos tras el cambio de rutas. La sincronización invalida las claves del tenant al confirmar la transacción.

## Verificación del riel y la barra móvil

A 320 px en la demo autenticada, los controles de la barra superior se alinearon en una fila y el contenido se mantuvo dentro del ancho disponible. Inicio y Cerrar sesión quedaron en extremos opuestos del riel con sus colores distintivos. El grupo Configuración del negocio mostró Maestros internos como único acceso.

Pasaron 75 pruebas dirigidas de Laravel, 300 pruebas de frontend y la compilación de Vite.

## Verificación de la navegación por tamaño de pantalla

En la demo autenticada se comprobó que a 1280 y 820 px la hamburguesa cierra solo los submódulos. A 320 px se verificó que abre y cierra el riel y su panel, la página usa todo el ancho y Escape devuelve el foco a la hamburguesa. Inicio encabeza el riel y el isotipo carga junto al nombre del usuario.

Pasaron 68 pruebas dirigidas de Laravel, 300 pruebas de frontend y la compilación de Vite.

## Verificación de la configuración anterior del riel

En la demo autenticada se comprobó a 1280 px que el riel conserva 64 px y nueve mundos al abrir, cerrar y volver a abrir los submódulos. El contenido comienza a 64 px con el panel cerrado y a 280 px con el panel abierto. El acceso Inicio aparece activo entre la hamburguesa y el buscador. Configuración usa el engranaje de la base de datos.

A 320 px, el navbar queda por encima del panel y la hamburguesa permanece disponible durante la apertura. Se comprobó el cierre desde el botón, la restauración de foco con Escape y el ancho de Inicio durante la carga y después de mostrar el contenido. Laravel verificó el caso de Inicio con una página adicional y el caso del perfil sin entrada de Inicio en el catálogo.

Pasaron 68 pruebas dirigidas de Laravel con 6379 aserciones, 300 pruebas de frontend y la compilación de Vite. Se actualizó la muestra de Componentes y se verificaron sus estados de apertura y cierre. [Prototipo en móvil](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=152-227).

## Verificación inicial de las reservas de Analítica

En la verificación previa al lanzamiento, a 1280 y 320 px, las dos reservas mostraban «Próximamente» y quedaban fuera del recorrido por teclado y del buscador. Las cuatro filas medían 44 px en móvil. [Verificación de Rendimiento comercial publicado](../../backend/system/modules/28_commercial_performance.md#verificación).

Pasaron 67 pruebas dirigidas de Laravel con 6365 aserciones, 299 pruebas de frontend y la compilación de Vite. Las pruebas cubren el panel con un solo acceso operativo de Analítica, el orden de las filas y la ocultación de las reservas para un perfil sin acceso a ese mundo.

## Verificación de las transiciones y las reservas de Analítica

En la demo autenticada se comprobó a 1280 px la apertura del contexto de Analítica y el cierre con Escape. El panel saliente permanece visible mientras termina el fundido. Desde que comienza el cierre, `inert` y `aria-hidden` desactivan sus enlaces y el foco vuelve al control que abrió el panel. A 320 px se verificaron el desplazamiento del menú, el fondo atenuado, el bloqueo de la página mientras el panel está abierto y la restauración de foco al cerrarlo.

Antes de publicar Rendimiento comercial se verificó que los IDs 110 y 111 estaban inactivos y que Resumen del negocio y Reportes conservaban sus accesos. [Estado actual de Rotación y reposición](../../backend/system/modules/29_stock_turnover.md).

Pasaron 75 pruebas dirigidas de Laravel con 6411 aserciones y 298 pruebas de frontend. Se comprobaron la compilación de Vite y Blade, el movimiento reducido, la reapertura durante el cierre y el cambio de ancho entre móvil y escritorio. En Figma se verificó el prototipo y se publicó el maestro Submódulo. [Prototipo en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=152-118).

## Verificación de la distribución de diez accesos

Se aplicó `php artisan tenant:navigation-sync demo --apply-layout` al tenant local `demo`. La base resultante tiene diez mundos activos, 28 grupos y 51 submódulos. La comparación antes y después confirmó que se conservaron los IDs y rutas de los submódulos, la habilitación por empresa, los permisos de perfiles y las preferencias, incluidos los favoritos.

En la sesión autenticada a 1280 px se verificaron el orden de los diez accesos y los grupos de ambos mundos fusionados. El panel conservó su ancho al cambiar de mundo. Gestión de activos abrió su ruta original y quedó como opción activa en Equipo y activos. El buscador encontró Historial bajo Operaciones y clientes.

A 320 × 568 px se verificaron las filas táctiles de 44 px, la cabecera de 64 px y el desplazamiento propio del panel y el riel. El menú se mantuvo dentro del ancho disponible. Escape cerró el panel y devolvió el foco a su disparador. Libro de reclamaciones abrió dentro de Atención al cliente e Inicio llevó directamente a `/home`.

Pasaron 74 pruebas dirigidas de Laravel, con 6398 aserciones, y las 297 pruebas de frontend. Laravel comprobó que un perfil restringido conserva su página autorizada y recibe 403 al abrir otra página del mundo fusionado. También comprobó la aplicación repetida de la distribución y la reversión de la transacción si un mundo que debe retirarse conserva dependencias locales. Vite compiló los estilos y scripts.

## Verificación de la distribución de doce mundos

El 24 de noviembre de 2026 se aplicó la distribución al tenant local `demo`. En una sesión autenticada a 1280 px se verificaron Operaciones, Equipo, Equipos y activos, Seguridad y acceso y el orden Cocina → Oferta comercial → Clasificación. El panel mantuvo su ancho al cambiar de mundo. Escape devolvió el foco al disparador y restauró el panel de la página activa.

A 390 px se comprobaron la búsqueda de membresías, la apertura de Asistencias de clientes desde los resultados, el cambio de mundo dentro del menú lateral y el acceso a Servicios en curso. El buscador y el documento quedaron dentro del ancho disponible.

Pasaron 69 pruebas dirigidas de Laravel con 6365 aserciones. Cubren la carga inicial del catálogo, la conciliación del diseño, el orden de los módulos, la conservación de las preferencias y la visibilidad por organización y por perfil. Pasaron las 296 pruebas de frontend y compiló Vite. Con una muestra del menú se verificó que a 320 px el atajo cierra el menú móvil y abre la búsqueda sin desbordar los resultados. También se comprobó que la preferencia de movimiento reducido desactiva la transición.

### Iconos de submódulos y densidad del menú

En la demo autenticada se comprobó Ventas a 1280 px y Catálogo comercial a 1280 y 320 px. Los iconos tienen el mismo tamaño y las etiquetas se alinean dentro del ancho disponible. Se abrió Productos desde el menú móvil y se verificó el estado activo en el enlace y el icono. Pasaron las 39 pruebas de aprovisionamiento y navegación de Laravel, incluidas las aserciones de renderizado del icono, y las 297 pruebas de frontend. Vite compiló los estilos y scripts.

## Verificación de la reorganización inicial

Se aplicó la distribución al tenant `demo`. A 1280 px se verificaron los grupos de Analítica, Inventario y Configuración. A 390 px se abrió el menú y se navegó desde Favoritos hasta Inicio, con el documento dentro del ancho disponible. Se comprobó que `/workspace` redirige a `/home` y que Inicio se muestra como enlace directo.

Pasaron 71 pruebas dirigidas de Laravel y las 294 pruebas de frontend. También se verificó la compilación de Vite y la sintaxis de las vistas Blade modificadas.
