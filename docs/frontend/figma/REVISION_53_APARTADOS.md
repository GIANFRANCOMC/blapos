# Revisión FG de los 53 apartados

Se cotejó el [mapa de Blapos](https://www.figma.com/design/uAC4aZ5e5a8M9YKLt8mg4U?node-id=4-83) con las 52 rutas habilitadas de System y el acceso de cada empresa. Los 53 archivos de Figma tienen una vista de escritorio y una de móvil. La matriz enlaza cada diseño con su implementación, incluida la rama correspondiente cuando varias rutas comparten vista.

## Ajustes aplicados

- **Inicio.** El saludo, los accesos frecuentes y la ayuda conservan la misma separación durante y después de la carga. La bienvenida usa un degradado con azul pizarra y azul de acción. Los accesos se redistribuyen según el ancho disponible. [Bienvenida en Figma](https://www.figma.com/design/ELAhZWYweVgpjlYk9pA6dl?node-id=1-328).
- **Servicios en curso.** Gestionar atención mantiene el cierre y las acciones principales a la vista. El cuerpo contiene el resumen, la asignación, las acciones de atención y los servicios incluidos. Los nombres largos fluyen dentro de su columna. El panel se verificó con datos simulados y las acciones de POS restaurante conservan su flujo. [Ficha de escritorio](https://www.figma.com/design/dm6HXZDC3R9qggpjPQZvdk?node-id=5-795) · [Ficha móvil](https://www.figma.com/design/dm6HXZDC3R9qggpjPQZvdk?node-id=5-890).
- **Asistencias de clientes.** Una acción principal para registrar, filtros de fecha visibles, listado y exportación CSV con los filtros aplicados. El resumen desplegable describe la actividad de la página visible. El registro manual, público y por escáner conserva sus opciones. [Escritorio](https://www.figma.com/design/2ceKgscx8nuls2K5a32v4k?node-id=1-445) · [Móvil](https://www.figma.com/design/2ceKgscx8nuls2K5a32v4k?node-id=1-601).
- **Patrones compartidos.** Los encabezados mantienen un espacio definido y sus botones pueden pasar a otra línea. Los filtros compactos conservan sus columnas, las botoneras se montan fuera de los componentes de campo y los pies de modal se adaptan a pantallas estrechas. Los filtros desplegables de Ventas y Cobros comparten su composición. Estas reglas se aplican en todos sus consumidores.
- **Otros listados y formularios.** Clientes y Libro de reclamaciones presentan tarjetas en móvil y conservan las acciones de cada registro. Clientes conserva Identificación como primera columna en escritorio. En móvil, el nombre encabeza la tarjeta. Se corrigieron las botoneras de Notificaciones, Historial y Colaboradores. Activos, Gestión de activos, Biométricos y Membresías de clientes comparten el marco de modal FG y tienen campos alineados.
- **Mi cuenta y Reportes.** Mi cuenta alinea la cabecera con ambas columnas y conserva una sola acción para guardar. Reportes usa filtros etiquetados, mantiene sus seis variantes y presenta los datos reales de la consulta financiera en tarjetas móviles. [Mi cuenta](https://www.figma.com/design/BwAQxSi06Yh0SJ8n4IJMUF?node-id=1-432) · [Reportes](https://www.figma.com/design/OjwADbn8Zxkoj020uCm2pp?node-id=1-295).

## Verificación

- Los 53 archivos de Figma respondieron con la vista móvil y tienen composición de escritorio. Los diseños de los módulos ajustados se cotejaron con sus componentes, estados y datos disponibles.
- Compilaron las 45 vistas raíz distintas de Vue y la compilación de Vite. La suite de frontend completó 303 pruebas.
- Laravel aprobó 34 pruebas y 323 aserciones. Incluyen la apertura de las 52 rutas con perfil autorizado, autenticación, Inicio, asistencias y operaciones de servicios en `blapos_testing`.
- Las pruebas dirigidas comprobaron consultas fallidas y recuperación, filtros aplicados, paginación, exportación, selección y respuestas simultáneas del detalle.

La demo de Chrome tenía una sesión activa. El control se interrumpió porque la herramienta no pudo verificar la URL y no se completó el recorrido visual autenticado posterior a los cambios. Las pruebas de rutas y componentes verifican acceso, estructura y estados funcionales. La comparación visual detallada se hizo en las vistas de Figma indicadas y en ejemplos representativos de los mundos. Quedan pendientes las pruebas de satisfacción con usuarios y la comprobación de cámara física.

## Matriz de apartados

### Inicio

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Inicio](https://www.figma.com/design/ELAhZWYweVgpjlYk9pA6dl?node-id=1-211) | `home.index` | [main.vue](../../../resources/js/System/Pages/Essentials/workspace/main.vue) | Espaciado saludo/contenido y paridad del skeleton. Accesos, tarjeta y soporte |

### Operaciones y clientes

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Servicios en curso](https://www.figma.com/design/dm6HXZDC3R9qggpjPQZvdk?node-id=1-316) | `service_sessions.index` | [main.vue](../../../resources/js/System/Pages/Operations/service_operations/main.vue) | Gestionar atención: título largo, resumen, formularios y pie con acciones |
| [Asistencias de clientes](https://www.figma.com/design/2ceKgscx8nuls2K5a32v4k?node-id=1-316) | `tracking_attendances.index` | [main.vue](../../../resources/js/System/Pages/Customers/tracking_attendances/main.vue) | Un solo Registrar, filtros, actividad con/sin datos, registro manual y modo público |
| [Clientes](https://www.figma.com/design/8mD18Oa7ob2Mp2oh9w5FqY?node-id=1-316) | `customers.index` | [main.vue](../../../resources/js/System/Pages/Customers/customers/main.vue) | Directorio, altas, edición, contactos y textos largos |
| [Membresías de clientes](https://www.figma.com/design/wwziHyNnlqMxCtYERkPEfW?node-id=1-316) | `tracking_subscriptions.index` | [main.vue](../../../resources/js/System/Pages/Customers/tracking_subscriptions/main.vue) | Membresías del cliente, vigencia, filtro y acciones de fila |
| [Historial](https://www.figma.com/design/Y3HkQo9nz32rxpsRVF9vdO?node-id=1-262) | `tracking_customers.index` | [main.vue](../../../resources/js/System/Pages/Customers/tracking_customers/main.vue) | Modal de periodos y selección nativa de Ventas, Membresías y Asistencias |
| [Notificaciones](https://www.figma.com/design/hi9zwAe6AouPlhXrE2onqz?node-id=1-316) | `tracking_notifications.index` | [main.vue](../../../resources/js/System/Pages/Customers/tracking_notifications/main.vue) | Filtro/aplicación, detalle de entrega y reintento disponible |
| [Libro de reclamaciones](https://www.figma.com/design/X6DuBC6fwDRdBV5Z0KpVvm?node-id=1-316) | `book_complaints.index` | [main.vue](../../../resources/js/System/Pages/Organizations/book_complaints/main.vue) | Lista y modal de detalle con mensajes extensos |

### Ventas

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Nueva venta](https://www.figma.com/design/x7TF8nmSwjASHkZykTkda4?node-id=1-490) | `sales.create` | [main.vue](../../../resources/js/System/Pages/Sales/sales/main.vue) | Ítem, tarjeta de códigos/precios y duración calculada de membresía |
| [Listado de ventas](https://www.figma.com/design/2fUXBIs35uFG7yegjArlHN?node-id=1-516) | `sales.index` | [list.vue](../../../resources/js/System/Pages/Sales/sales/list.vue) | Filtro, estado, paginación y detalle de la venta |
| [POS venta](https://www.figma.com/design/lu0xp4BJcpK03GYsg5EqOa?node-id=1-479) | `sales.pos` | [main.vue](../../../resources/js/System/Pages/Sales/pos/main.vue) | Catálogo, ticket largo, caja, cobro y recuperación |
| [POS restaurante](https://www.figma.com/design/15Vh6dp26HoBSY4n0FVa8N?node-id=1-344) | `restaurant_pos.index` | [main.vue](../../../resources/js/System/Pages/Operations/service_operations/main.vue) | Misma raíz de Servicios, modalidad restaurante: mesas, detalle de pedidos y KDS |
| [Nueva cotización](https://www.figma.com/design/KRobswu6NORRXR4JjnghqZ?node-id=1-490) | `quotations.create` | [main.vue](../../../resources/js/System/Pages/Sales/quotations/main.vue) | Rama de alta: cotización, ítems, totales y vigencia |
| [Listado de cotizaciones](https://www.figma.com/design/FLZn13kJikdIXizITnmAwy?node-id=1-516) | `quotations.index` | [main.vue](../../../resources/js/System/Pages/Sales/quotations/main.vue) | Rama de consulta: filtros, vigencia, paginación y conversión a venta |
| [Entregas pendientes](https://www.figma.com/design/864ncBzuXES9Miyq8UVAr4?node-id=1-516) | `sales.deliveries.index` | [deliveries.vue](../../../resources/js/System/Pages/Sales/sales/deliveries.vue) | Entrega parcial, cantidades por línea y progreso |
| [Cuentas por cobrar](https://www.figma.com/design/l6kuFke3v3RPWnJeZV6CvK?node-id=1-516) | `accounts_receivable.index` | [main.vue](../../../resources/js/System/Pages/Finance/accounts_receivable/main.vue) | Saldo, vencimiento y cronograma con cuotas/pagos |

### Compras

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Nueva compra](https://www.figma.com/design/iC0L2yDj4w7eLRXHLNORTJ?node-id=1-971) | `purchases.create` | [main.vue](../../../resources/js/System/Pages/Purchases/purchases/main.vue) | Rama de alta: proveedor, impuestos, ítems, pago y recepción |
| [Compras registradas](https://www.figma.com/design/1fhJ6t6i1b2EqMlgMYbgJY?node-id=1-512) | `purchases.index` | [main.vue](../../../resources/js/System/Pages/Purchases/purchases/main.vue) | Listado: proveedor, filtros, detalle y estado |
| [Ingresos pendientes](https://www.figma.com/design/mz0yJbhstwaNf1N2xA6Lpk?node-id=1-512) | `purchases.receipts.index` | [main.vue](../../../resources/js/System/Pages/Purchases/purchases/main.vue) | Rama de recepción: almacén, cantidades y cambio de compra activa |
| [Cuentas por pagar](https://www.figma.com/design/xqWGGucIHABDFJZC2hgG5q?node-id=1-512) | `accounts_payable.index` | [main.vue](../../../resources/js/System/Pages/Finance/accounts_payable/main.vue) | Filtros de pagos, saldo y cronograma |
| [Proveedores](https://www.figma.com/design/XwXZ1Zs3NQQghyHobG9D9m?node-id=1-512) | `suppliers.index` | [main.vue](../../../resources/js/System/Pages/Purchases/suppliers/main.vue) | Listado, alta/edición y contacto largo |

### Caja

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Cajas](https://www.figma.com/design/MnBerCeOx38ZKrpF467Eo5?node-id=1-510) | `cash_registers.index` | [main.vue](../../../resources/js/System/Pages/Finance/cash_registers/main.vue) | Rama de cajas: alta, edición y asignación |
| [Aperturas y cierres](https://www.figma.com/design/llsEKeV0GJtiRbskQ3I1ly?node-id=1-510) | `cash_sessions.index` | [main.vue](../../../resources/js/System/Pages/Finance/cash_registers/main.vue) | Rama de sesiones: apertura, arqueo y cierre |
| [Movimientos](https://www.figma.com/design/kGssWObIM0Oob72skORiz5?node-id=1-493) | `cash_movements.index` | [main.vue](../../../resources/js/System/Pages/Finance/cash_registers/main.vue) | Rama de movimientos: filtros y estados |
| [Resumen de caja](https://www.figma.com/design/qFKd2hh9ab4ysGKrziLh0y?node-id=1-493) | `cash_summary.index` | [main.vue](../../../resources/js/System/Pages/Finance/cash_registers/main.vue) | Rama de resumen: tarjetas, filtro y valores largos |
| [Gastos varios](https://www.figma.com/design/tBEN6oWPlpVRuKHRe3RJI0?node-id=1-510) | `misc_expenses.index` | [main.vue](../../../resources/js/System/Pages/Finance/misc_expenses/main.vue) | Listar egresos, registro y confirmaciones |

### Inventario

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Control de stock](https://www.figma.com/design/XpWtutcWsf5x3rHwo0QCNE?node-id=1-519) | `stocks_management.stock.index` | [stock.vue](../../../resources/js/System/Pages/Warehouses/inventory/stock.vue) | Tarjetas, alerta, modificación de stock y trazabilidad |
| [Traslados](https://www.figma.com/design/lS3WIh4AwYpcoayOM2tAqI?node-id=1-517) | `stocks_management.transfers.index` | [transfers.vue](../../../resources/js/System/Pages/Warehouses/inventory/transfers.vue) | Origen/destino, estados, detalle y recepción |
| [Guías](https://www.figma.com/design/fjz8Wzp78Vn19vGhnh4xjZ?node-id=1-462) | `stocks_management.guides.index` | [guides.vue](../../../resources/js/System/Pages/Warehouses/inventory/guides.vue) | Datos, filtros, detalle y estado de la guía |
| [Kardex](https://www.figma.com/design/Fwsy7KNxJLKKuhaqebmkYk?node-id=1-447) | `stocks_management.kardex.index` | [kardex.vue](../../../resources/js/System/Pages/Warehouses/inventory/kardex.vue) | Producto, movimiento, variación y saldo con unidad |
| [Kardex valorizado](https://www.figma.com/design/TKKExHNTcy2gcvng1xAvil?node-id=1-447) | `stocks_management.valued.index` | [valued-kardex.vue](../../../resources/js/System/Pages/Warehouses/inventory/valued-kardex.vue) | Costos y saldos, contención de anchos y montos |

### Catálogo comercial

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Productos](https://www.figma.com/design/fjrUPPpr11dZ7hD7Em9rsE?node-id=1-517) | `products.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/products/main.vue) | Listado, detalle largo, modal, inventario e importación |
| [Servicios](https://www.figma.com/design/DvekdHSpdA1EkduJguN4tX?node-id=1-517) | `services.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/services/main.vue) | Listado, cupos, comisión y alta/edición |
| [Planes de membresía](https://www.figma.com/design/mpvCPYm6yxHOSj3k6PPaIC?node-id=1-517) | `subscriptions.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/subscriptions/main.vue) | Listado, duración y alta de planes de membresía |
| [Categorías](https://www.figma.com/design/ycK6MKYcTwutIPbMc22KKN?node-id=1-517) | `categories.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/categories/main.vue) | Código y filtros, modal y nombre largo |
| [Marcas](https://www.figma.com/design/0lp6Dtm2HZs1UwAkaOvusi?node-id=1-517) | `brands.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/brands/main.vue) | Sitio oficial, nombre largo, importación y estado |
| [Recetas y platillos](https://www.figma.com/design/lawlo9Qe7a0590c88GSnIy?node-id=1-517) | `recipes.index` | [main.vue](../../../resources/js/System/Pages/Catalogs/recipes/main.vue) | Editar composición, insumos y textos del plato |

### Equipo y activos

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Colaboradores](https://www.figma.com/design/IRXrziag9ZYkuk7jJhjyak?node-id=1-489) | `users.index` | [main.vue](../../../resources/js/System/Pages/Organizations/users/main.vue) | Grid de filtros del historial de acceso. alta, importación y permisos |
| [Asistencia del personal](https://www.figma.com/design/kBWJk9b3ol4ijoF4ZLWkIh?node-id=1-489) | `user_attendances.index` | [main.vue](../../../resources/js/System/Pages/Organizations/user_attendances/main.vue) | Personal, filtros, manual y correcciones de jornada |
| [Activos](https://www.figma.com/design/m0EMXm8IQUaPj8GiKImJ4p?node-id=1-489) | `assets.index` | [main.vue](../../../resources/js/System/Pages/Assets/assets/main.vue) | Catálogo de bienes, estados, formulario y tabla ancha |
| [Gestión de activos](https://www.figma.com/design/oUi8J0Cr3qlJyCeO4aIT8j?node-id=1-489) | `assets_management.index` | [main.vue](../../../resources/js/System/Pages/Assets/assets_management/main.vue) | Asignación, traslados e historial del activo |
| [Dispositivos biométricos](https://www.figma.com/design/lM4efXcAtfIcx9Oa8HvwTz?node-id=1-489) | `biometric_devices.index` | [main.vue](../../../resources/js/System/Pages/Devices/biometric_devices/main.vue) | Configuración, sincronización y estados de dispositivo |

### Analítica

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Resumen del negocio](https://www.figma.com/design/LrzQNm2mT1AfiDFoHoSd7W?node-id=1-241) | `dashboard.index` | [main.vue](../../../resources/js/System/Pages/Essentials/dashboard/main.vue) | Periodo/sucursal, vacío, indicadores y evolución de operación |
| [Rendimiento comercial](https://www.figma.com/design/ZaxUocydc2wBdGMKmIYMV4?node-id=1-241) | `analytics.commercial_performance.index` | [main.vue](../../../resources/js/System/Pages/Essentials/commercial_performance/main.vue) | Serie por día, valores exactos y alternativo de teclado |
| [Rotación y reposición](https://www.figma.com/design/8yTIXfIQ7vnhtLLw9hvuAt?node-id=1-258) | `analytics.stock_turnover_replenishment.index` | [main.vue](../../../resources/js/System/Pages/Essentials/stock_turnover/main.vue) | Filtros, etiquetas móviles y ausencia de mínimo |
| [Reportes](https://www.figma.com/design/OjwADbn8Zxkoj020uCm2pp?node-id=1-295) | `reports.index` | [main.vue](../../../resources/js/System/Pages/Essentials/reports/main.vue) | Variantes de reporte, error recuperable y exportaciones |

### Configuración

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Mi cuenta](https://www.figma.com/design/BwAQxSi06Yh0SJ8n4IJMUF?node-id=1-432) | `account.index` | [main.vue](../../../resources/js/System/Pages/Essentials/account/main.vue) | Ficha de cuenta, campos de lectura/edición y estados de credenciales |
| [Favoritos](https://www.figma.com/design/SXv7IVAJCaewNLzpH1wehK?node-id=1-375) | `favorites.index` | [main.vue](../../../resources/js/System/Pages/Essentials/home/main.vue) | Seleccionar, ordenar o quitar accesos según el permiso |
| [Mi empresa](https://www.figma.com/design/NP4pz3VujYJbNs35oUd8A2?node-id=2-430) | `companies.index` | [main.vue](../../../resources/js/System/Pages/Organizations/companies/main.vue) | Secciones, datos, sedes e imagen de marca, sin activar pestañas ocultas |
| [Sucursales](https://www.figma.com/design/QCMiWKsZ1RgbenoWBII8fA?node-id=1-489) | `branches.index` | [main.vue](../../../resources/js/System/Pages/Organizations/branches/main.vue) | Sedes, alcance y formularios de contacto |
| [Perfiles de acceso](https://www.figma.com/design/IHTIGpBWJmOo2gyuUDcfcS?node-id=1-489) | `roles.index` | [main.vue](../../../resources/js/System/Pages/Organizations/roles/main.vue) | Permisos, grupos y controles accesibles |
| [Maestros internos](https://www.figma.com/design/gUrdtnLagqhCiTRVnWNd5i?node-id=1-472) | `master_data.index` | [main.vue](../../../resources/js/System/Pages/General/master_data/main.vue) | Panel de parámetros y visibilidad de validación |

### Acceso

| Diseño en Figma | Ruta | Vista raíz | Aspecto revisado |
| --- | --- | --- | --- |
| [Login](https://www.figma.com/design/kbo6DMIJ5l2dz9eL1YEr46?node-id=2-365) | `/login` | [login.blade.php](../../../resources/views/System/auth/login.blade.php) | Composición de escritorio/móvil, captcha, mensajes de error, teclado, soporte y estado de envío. |
