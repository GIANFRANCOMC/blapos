# 00 - Navegación y orden del menú System

## Fuente canónica

La definición vigente se encuentra en `menu_categories`, `sections`, `menu_groups` y `sub_sections`. Contiene:

- categorías principales;
- secciones desplegables;
- grupos visuales dentro de una sección;
- opciones, rutas, etiquetas y descripciones.

`SystemNavigationSeeder` carga este catálogo cuando la base está vacía; no modifica los nombres de una instalación ya poblada. `SystemCatalogSyncService` lee esas tablas y actualiza `companies_sub_sections` y los permisos de perfiles con acceso total, sin sobrescribir la estructura del menú.

No definir opciones de menú en archivos de configuración ni componentes Vue. Los cambios sobre instalaciones existentes deben usar una migración de datos concreta; el seeder conserva el estado inicial de instalaciones nuevas.

## Resolución y renderizado

`CompanySectionService` obtiene solamente las opciones:

1. activas en el catálogo;
2. habilitadas en `companies_sub_sections`;
3. permitidas para el rol cuando no tiene acceso total.

El servicio carga categoría y grupo, aplica el orden empresarial y genera la URL únicamente si la ruta existe. El layout Blade agrupa el resultado; no reconstruye el catálogo y no muestra encabezados vacíos cuando las preferencias ocultan todas sus opciones.

## Categorías principales

1. **Principal**
   - Inicio.
2. **Operaciones**
   - Ventas.
   - Compras.
   - Caja.
3. **Gestión**
   - Clientes.
   - Catálogo e inventario.
4. **Administración**
   - Administración.

## Grupos y opciones

Cuando una sección contiene más de un grupo, cada uno muestra un encabezado y sus opciones en una lista compacta. La opción actual se distingue visualmente sin expandir paneles adicionales.

`Principal` contiene el mundo Inicio. Mi espacio y Mi cuenta comparten el grupo Mi espacio; Dashboard y Reportes aparecen solo si están habilitados. Menú y favoritos permanece en Administración. El riel de mundos mantiene disponibles los iconos aunque un mundo tenga una sola página visible.

Los submódulos sin grupo se reúnen primero, sin encabezado; luego aparecen los grupos según su orden. Los grupos internos usan encabezados compactos en mayúsculas, separados por líneas tenues. Se muestran incluso cuando el perfil solo puede ver una opción del grupo; así, el acceso no queda aislado. Cada submódulo lleva una viñeta circular discreta como marcador de lista, no un icono de Font Awesome. El estado activo recibe un fondo suave y resalta ligeramente la viñeta, sin flechas adicionales.

En el riel principal, los iconos de los mundos se centran verticalmente entre la marca superior y la cuenta del usuario, con separación suficiente entre accesos. Si la cantidad de mundos supera la altura disponible, el contenedor mantiene desplazamiento vertical. Sus tooltips usan el fondo, contraste, borde y sombra del branding System.

La cabecera contextual reúne únicamente el icono ya definido para el mundo y su nombre en una superficie compacta, sin badge, descripción ni línea divisoria debajo. El cambio entre mundos atenúa brevemente la cabecera y el contenido, sin animar el marco del panel y respetando la preferencia de movimiento reducido.

- **Clientes** agrupa Datos, Seguimiento, Vigencias y asistencia y Atención al cliente, en ese orden.
- **Administración** agrupa Organización, Equipos y bienes y Ajustes; el mundo Mi organización ya no aparece por separado.

### Ventas

- **Registro y consulta**: Nueva venta, Listado de ventas.
- **Punto de venta**: Venta POS, POS restaurante.
- **Cotizaciones**: Nueva cotización, Cotizaciones registradas.
- **Despacho y cobranza**: Entregas pendientes, Cuentas por cobrar.

### Compras

- **Registro y consulta**: Nueva compra, Compras registradas.
- **Recepción y pagos**: Ingresos pendientes, Cuentas por pagar.
- **Proveedores**: Proveedores.

### Caja

- **Operaciones**: Cajas, Aperturas y cierres, Movimientos.
- **Resumen y gastos**: Resumen de caja, Gastos varios.

### Clientes

- **Datos**: Clientes, Historial.
- **Seguimiento**: Servicios en curso.
- **Vigencias y asistencia**: Membresías, Asistencias de clientes.
- **Atención al cliente**: Notificaciones, Libro de reclamaciones.

### Catálogo e inventario

- **Inventario**: Control de stock, Traslados, Guías, Kardex, Kardex valorizado.
- **Catálogo comercial**: Productos, Servicios, Membresías, Categorías, Marcas, Recetas y platillos.

El breadcrumb muestra mundo, subgrupo activo y página cuando los tres niveles son distintos. `SystemNavigationSeeder` conserva los identificadores y rutas para no romper permisos ni favoritos. Los nombres nuevos se aplican al recrear la base; las instalaciones ya pobladas requieren una actualización de datos explícita, porque el seeder no sobrescribe su navegación.

Inventario es el primer grupo de Catálogo e inventario. Sus cinco rutas, permisos y favoritos conservan sus identificadores.

### Administración

- **Organización**: Mi empresa, Sucursales, Colaboradores, Asistencia del personal.
- **Equipos y bienes**: Activos, Gestión de activos, Dispositivos biométricos.
- **Ajustes**: Perfiles de acceso, Maestros internos, Rubro y módulos, Menú y favoritos.

## Orden por organización

- La categoría define el primer nivel de orden.
- `sections.order` ordena secciones dentro de la categoría.
- `menu_groups.order` ordena grupos dentro de la sección.
- `sections.description` contiene el resumen breve que acompaña al nombre e icono del mundo.
- `menu_groups.dom_icon` define el icono de cada grupo; la vista no asigna iconos por nombre o ruta.
- `sub_sections.order` ordena opciones dentro del grupo.
- `companies_sub_sections.section_order` y `sub_section_order` almacenan la proyección ordenada por organización; el segundo combina el orden del grupo con el de la opción para mantener juntos sus accesos.
- `SystemCatalogSyncService` y la administración central usan el mismo cálculo para componer categoría, sección, grupo y opción; así los grupos no se intercalan al sincronizar o guardar módulos.

## Permisos y preferencias

- `companies_sub_sections` habilita una opción para una organización.
- `role_sub_sections` restringe opciones y acciones para perfiles sin acceso total.
- `user_preferences`, mediante `config_companies_sub_sections`, conserva favoritos y visibilidad individual.
- Una preferencia nunca puede habilitar un módulo que la organización o el rol no tengan permitido.
- Durante la etapa reiniciable todos los módulos se habilitan por defecto para cada organización y para todos los perfiles de rubro. El rubro conserva valor descriptivo, pero no oculta accesos.

## Incorporación de una opción

1. Crear y nombrar la ruta.
2. Incorporar la opción con ID estable en `SystemNavigationSeeder` para instalaciones nuevas y, si existen bases persistentes, mediante una migración de datos idempotente.
3. Asociarla a una sección y, cuando corresponda, a un grupo.
4. Mapear permisos compartidos en `config/permissions.php` si la ruta no tiene correspondencia directa.
5. Ejecutar `php artisan system:sync`.
6. Ejecutar `php artisan system:doctor` para verificar la ruta.
7. Actualizar este documento y el archivo del módulo.

No dispersar el catálogo en varias migraciones cuando la base todavía es reiniciable. En instalaciones persistentes, usar una única migración de datos enfocada y mantener el seeder consolidado.

## Caché

La clave general usa el namespace físico del tenant y el rol: `tenant:{namespace}:company_sections:role:{roleId|all}`. Las asignaciones empresariales y los permisos invalidan las variantes afectadas mediante sus observers. `system:sync` limpia la caché de la empresa raíz.
