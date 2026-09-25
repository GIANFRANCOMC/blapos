# 00 - Navegación y orden del menú System

## Fuente canónica

La definición vigente se encuentra en `menu_categories`, `sections`, `menu_groups` y `sub_sections`. Contiene:

- categorías principales;
- secciones desplegables;
- grupos visuales dentro de una sección;
- opciones, rutas, etiquetas y descripciones.

`SystemNavigationSeeder` carga este catálogo solamente cuando la base está vacía. `SystemCatalogSyncService` lee esas tablas y actualiza `companies_sub_sections` y los permisos de perfiles con acceso total, sin sobrescribir la estructura del menú.

No definir opciones de menú en archivos de configuración ni componentes Vue. Los cambios sobre instalaciones existentes deben usar una migración de datos concreta; el seeder conserva el estado inicial de instalaciones nuevas.

## Resolución y renderizado

`CompanySectionService` obtiene solamente las opciones:

1. activas en el catálogo;
2. habilitadas en `companies_sub_sections`;
3. permitidas para el rol cuando no tiene acceso total.

El servicio carga categoría y grupo, aplica el orden empresarial y genera la URL únicamente si la ruta existe. El layout Blade agrupa el resultado; no reconstruye el catálogo y no muestra encabezados vacíos cuando las preferencias ocultan todas sus opciones.

## Categorías principales

1. **Principal**
   - Mi espacio de trabajo.
2. **Operaciones**
   - Ventas.
   - Compras.
   - Caja y finanzas.
3. **Gestión**
   - Clientes.
   - Catálogo comercial.
   - Inventario.
4. **Administración**
   - Mi organización.
   - Configuración.

## Grupos y opciones

Cuando una sección contiene más de un grupo, cada grupo se representa como un submenú desplegable real. No se muestra como un rótulo estático seguido de opciones planas. El grupo que contiene la ruta actual se abre automáticamente.

Los títulos de categoría tienen peso normal y tamaño reducido. `Principal` no dibuja encabezado. Su único mundo, `Mi espacio de trabajo`, agrupa Mi espacio, Menú y favoritos, Mi cuenta, Dashboard y Reportes dentro del panel contextual. En escritorio colapsado las demás categorías muestran únicamente sus tres primeros caracteres; al expandir o pasar el cursor recuperan el nombre completo.

Los grupos internos usan encabezados compactos en mayúsculas y espaciado tipográfico, sin líneas decorativas. El grupo actual recibe solamente una variación leve de color; así, el encabezado no se confunde con una página seleccionable ni agrega ruido visual. Sus opciones usan una segunda sangría y menor contraste, de modo que se distinguen mundo, grupo y acceso final.

En el riel principal, los iconos de los mundos se centran verticalmente entre la marca superior y la cuenta del usuario, con separación suficiente entre accesos. Si la cantidad de mundos supera la altura disponible, el contenedor mantiene desplazamiento vertical. Sus tooltips usan el fondo, contraste, borde y sombra del branding System.

La cabecera contextual muestra únicamente el nombre del mundo. No repite el grupo, la página, la ruta completa ni muestra un icono decorativo.

- **Clientes** agrupa Gestión, Membresías, Servicios y Atención al cliente.
- **Mi organización** agrupa Empresa, Sedes, Activos y Colaboradores.
- **Configuración** conserva Accesos y Datos maestros.

### Ventas

- **Ventas**: Nueva venta, Ventas registradas.
- **Punto de venta**: Venta POS, POS restaurante.
- **Cotizaciones**: Nueva cotización, Cotizaciones registradas.
- **Despacho y cobranza**: Entregas pendientes, Cuentas por cobrar.

### Compras

- **Compras**: Nueva compra, Compras registradas.
- **Proveedores**: Proveedores.

### Caja y finanzas

- **Operación de caja**: Cajas, Aperturas y cierres, Movimientos.
- **Control**: Resumen de caja, Gastos varios.

### Clientes

- **Gestión**: Clientes, Historial.
- **Membresías**: Membresías, Asistencias de clientes.
- **Servicios**: Servicios en curso.
- **Atención al cliente**: Notificaciones, Libro de reclamaciones.

### Catálogo comercial

- **Oferta comercial**: Productos, Servicios, Membresías, Recetas y platillos.
- **Organización**: Categorías, Marcas.

### Inventario

- **Control**: Control de stock.
- **Movimientos**: Kardex, Traslados, Guías, Kardex valorizado.

### Mi organización

- **Empresa**: Mi empresa.
- **Sedes**: Sucursales.
- **Activos**: Activos, Gestión de activos, Dispositivos biométricos.
- **Colaboradores**: Colaboradores, Asistencia del personal.

### Configuración

- **Accesos**: Perfiles de acceso.
- **Datos maestros**: Maestros internos, Rubro y módulos.

## Orden por organización

- La categoría define el primer nivel de orden.
- `sections.order` ordena secciones dentro de la categoría.
- `menu_groups.order` ordena grupos dentro de la sección.
- `sub_sections.order` ordena opciones dentro del grupo.
- `companies_sub_sections.section_order` y `sub_section_order` almacenan la proyección ordenada por organización.
- `SystemCatalogSyncService` compone el orden de categoría y sección para impedir que se intercalen categorías con órdenes locales iguales.

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
