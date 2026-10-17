# 16 - Inventario

## Propósito

Controla las existencias de productos por almacén y conserva la trazabilidad de cada variación. El nombre visible cambia de **Gestión de stock** a **Inventario**; el identificador técnico `stocks_management` se mantiene para no romper rutas, permisos ni favoritos existentes.

El módulo separa dos conceptos:

- `warehouse_items` conserva el saldo actual y el stock mínimo.
- `inventory_movements` conserva el kardex inmutable: saldo anterior, variación, saldo resultante, motivo, origen y usuario.

## Archivos principales

- Ruta: `routes/System/Warehouses/StockManagement.php`
- Controlador: `StockManagementController`
- Configuración: `StockManagementConfigService`
- Consulta y compatibilidad: `StockManagementService`
- Núcleo transaccional: `InventoryMovementService`
- Modelo de kardex: `InventoryMovement`
- Vista: `resources/js/System/Pages/Warehouses/stocks_management/main.vue`
- Estilos reutilizables: bloque `br-inventory` de `resources/css/System/br-branding/82-inventory.css`

## Tipos de movimiento

### Entrada

Suma una cantidad positiva al saldo. Orígenes implementados:

- `product_opening`: stock inicial al crear un producto.
- `manual`: entrada registrada desde Inventario.
- `sale_cancellation`: reposición automática al anular una venta.
- `replenishment`: reposición operativa de existencias.
- `customer_return`: devolución física recibida de un cliente.

- `purchase`: recepción parcial o total de una compra.
- `purchase_cancellation`: reversa automática de recepción cuando una compra se anula y la política empresarial lo permite.

### Salida

Resta una cantidad positiva del saldo. Orígenes implementados:

- `sale`: salida automática al vender un producto.
- `manual`: salida justificada desde Inventario.
- `supplier_return`: devolución física enviada a un proveedor.

### Corrección

El usuario registra el saldo físico contado. El backend calcula la diferencia contra el saldo actual y guarda esa variación. No debe usarse una corrección para representar una compra, venta o traslado.

La toma física usa el origen `physical_count`, separado de entradas y salidas manuales.

## Reglas de negocio

- Todo cambio de `warehouse_items.quantity` debe pasar por `InventoryMovementService`.
- Producto y almacén deben pertenecer a la misma empresa.
- Solo los items de tipo `product` generan movimientos.
- Entradas y salidas requieren una cantidad mayor que cero.
- Correcciones requieren el saldo físico resultante.
- Los movimientos manuales exigen motivo.
- Los movimientos son inmutables; un error se compensa con otro movimiento.
- Una venta genera una salida por cada detalle de tipo producto.
- La anulación consulta `company_settings.inventory.restore_stock_on_sale_cancellation`.
- La política es `false` por defecto: anular no modifica stock y una devolución recibida debe registrarse como `customer_return`.
- Con la política activa, la anulación repone cantidades mediante entradas `sale_cancellation`; nunca elimina la salida original.
- La anulación de compras consulta `company_settings.inventory.restore_stock_on_purchase_cancellation`.
- Por defecto, una compra con recepción se bloquea al anular para que el usuario registre una devolución a proveedor. Si la política está activa, se generan salidas `purchase_cancellation` por cada recepción y las recepciones quedan canceladas.
- El stock inicial de Productos se registra como entrada `product_opening`.
- Editar precio, descripción, marca, categorías o stock mínimo no genera kardex.
- Las salidas manuales no permiten saldo negativo.
- Ventas y Venta POS/Caja consultan `company_settings.inventory.allow_negative_stock_on_sale`. Por defecto es `false`, por lo que no se confirma una venta si algún producto queda por debajo de cero en el almacén seleccionado.
- Cada movimiento sincroniza `inventory_stock_alerts`: si el saldo queda menor o igual al stock mínimo se abre o actualiza una alerta; si vuelve a estar por encima del mínimo, la alerta se resuelve.
- Si `company_settings.inventory.stock_alert_email_enabled` está activo, al abrir una nueva alerta se envía un correo al destinatario configurado en `stock_alert_email_to` o al correo de la empresa. Las actualizaciones de una alerta abierta no reenvían correo.

## Interfaz

- El selector de **Almacén de trabajo** se presenta en una barra compacta e independiente; define el contexto de todas las pestañas sin ocupar una cabecera descriptiva adicional.
- Las pestañas muestran título y descripción breve para explicar la tarea antes de ingresar:
  - **Control de stock**: existencias actuales, mínimos y alertas.
  - **Kardex**: historial completo y trazabilidad.
  - **Traslados**: movimientos multiproducto entre almacenes.
  - **Guías**: entradas y salidas numeradas, con estado y detalle de productos.
  - **Kardex valorizado**: costo unitario, valor del movimiento y valor resultante por almacén.
- Las pestañas reutilizan la estructura visual de Productos: título, descripción breve y estado activo discreto, sin tarjeta exterior.
- No se repite un encabezado dentro de cada pestaña. El título y la descripción viven únicamente en la navegación.
- `Catálogo comercial` expone estos accesos independientes para perfiles: `Control de stock` en Existencias; `Traslados`, `Guías`, `Kardex` y `Kardex valorizado` en Movimientos. Todos reutilizan el mismo componente, pero cada ruta abre su vista inicial correspondiente.
- La barra de búsqueda y acciones reutiliza la estructura `br-filter-bar` usada por Productos: mismas alturas, etiquetas, espaciados y botones compactos.
- El buscador principal y **Registrar operación** solo aparecen en **Control de stock**. Kardex y Kardex valorizado conservan sus filtros propios; Traslados usa su formulario especializado.
- Las tablas usan una sola superficie delimitada, sin contenedores exteriores adicionales.
- **Control de stock** muestra saldo actual, stock mínimo y situación por producto. También permite seleccionar **Todos los almacenes** para una vista consolidada por empresa, con desglose por almacén y contador de almacenes que requieren atención.
- **Registrar operación** es una acción general del control de stock; no se repite por fila para evitar duplicidad visual. Abre un formulario para uno o hasta 100 productos e incluye entrada, salida, toma física, reposición, devolución de cliente y devolución a proveedor.
- **Kardex** muestra fecha, usuario, producto, tipo, variación, saldo anterior/resultante, motivo y origen.
- **Traslados** mueve uno o varios productos entre almacenes de la misma empresa mediante salidas y entradas atómicas.
- **Guías** lista documentos de entrada/salida con número, fecha, almacén, productos, referencia, motivo y estado.
- **Kardex valorizado** reutiliza filtros, búsqueda y paginación del Kardex operativo.
- El almacén seleccionado limita tanto existencias como kardex.
- Los selectores muestran sucursal y almacén porque una sucursal puede administrar varios almacenes.

### Búsqueda y lectura de códigos

- Todas las pestañas comparten un buscador por nombre, código interno o código de barras.
- El buscador reutiliza `InputText`; el icono de código de barras forma parte del grupo del campo y comparte borde y foco.
- El campo admite lectores físicos que escriben el código y envían `Enter`; no exige que el usuario conozca una sintaxis especial.
- En **Control de stock**, una coincidencia exacta filtra y resalta el producto.
- En **Kardex**, una coincidencia exacta consulta directamente su trazabilidad.
- En **Traslados**, una coincidencia exacta agrega el producto a la operación. Si ya está incluido, se informa sin duplicarlo.
- Al abrir **Registrar operación** después de escanear una coincidencia exacta, el producto queda preseleccionado.
- Las coincidencias parciales siguen funcionando como búsqueda convencional.

## Operaciones multiproducto

- Una operación manual admite entre 1 y 100 productos sin duplicados.
- Tipo de operación, origen, almacén y motivo son comunes al lote.
- Cada producto conserva su propia cantidad o saldo contado.
- El backend valida todos los registros y los procesa dentro de una sola transacción.
- `StoreInventoryOperationRequest`, `StoreInventoryMovementRequest` y `StoreInventoryTransferRequest` encapsulan reglas y campos condicionales.
- Si cualquier producto falla, no se registra ningún movimiento del lote.
- La operación continúa generando un registro independiente en `inventory_movements` por producto; no se pierde granularidad ni trazabilidad.

## Exportación

- Todas las pestañas permiten descargar un Excel con los filtros visibles.
- **Control de stock** exporta código interno, código de barras, producto, stock actual, stock mínimo, almacenes, cantidad de almacenes con alerta y situación.
- **Kardex** exporta fecha, almacén, identificación, producto, movimiento, origen, saldos, motivo, referencia y responsable.
- **Traslados** exporta los movimientos `transfer_in` y `transfer_out` del almacén seleccionado.
- **Guías** exporta número, fecha, almacén, sucursal, tipo, productos, referencia, motivo, estado y responsable.
- La exportación usa las mismas consultas de la pantalla sin paginación, evitando diferencias entre listado y reporte.
- Códigos internos y códigos de barras se escriben como texto para impedir notación científica o pérdida de ceros.
- Las existencias bajo el mínimo se resaltan suavemente en el Excel.
- En escritorio, Descargar se muestra como icono con tooltip y se mantiene al extremo derecho. En móvil muestra icono y texto.

### Kardex valorizado

- Usa el método `weighted_average`, configurado por empresa en `company_settings`.
- `warehouse_items` materializa `average_cost` e `inventory_value`.
- Cada movimiento conserva `unit_cost`, `value_before`, `value_change` y `value_after`.
- `unit_cost` no identifica ni duplica un producto: representa el costo usado para valorizar ese movimiento. Permite que dos entradas del mismo producto tengan costos distintos y que el promedio ponderado del almacén se actualice con trazabilidad.
- Las entradas de compra usan el costo unitario recibido.
- Las salidas usan el costo promedio vigente del almacén.
- Los traslados conservan el costo del almacén de origen para la entrada del destino.
- Las entradas manuales y reposiciones aceptan costo unitario opcional; si se omite, conservan el promedio actual.
- Nunca usa el precio de venta como costo.
- La pestaña y su Excel están activas y usan los mismos filtros del Kardex.
- La vista muestra una ayuda compacta: `unit_cost` es el costo usado para valorizar el movimiento, y si una entrada manual no lo informa se conserva el promedio ponderado vigente del almacén.

## Guías de entrada y salida

- `stocks_management.guides.index` es una página independiente dentro de Inventario y puede habilitarse por perfil.
- Permite filtrar por almacén, tipo de guía, rango de fechas y producto.
- Las guías se consultan con su numeración, estado, detalle de productos, referencia, motivo y responsable.
- Su Excel usa el mismo alcance operativo que la vista; si el usuario solo tiene acceso a ciertos almacenes, no exporta información fuera de ese alcance.

## Traslados entre almacenes

- El almacén seleccionado en la cabecera funciona como origen.
- La vista de traslados muestra un campo **Almacén de origen** precargado con el almacén de trabajo para que el usuario confirme desde dónde saldrán los productos.
- El destino debe ser distinto y pertenecer a la misma empresa.
- Cada operación admite entre 1 y 100 productos sin duplicados.
- Cada producto debe estar activo y ser de tipo `product`.
- Cada cantidad debe ser mayor que cero y no puede superar el saldo disponible del producto en el origen.
- El motivo es obligatorio.
- El traslado usa una referencia común y genera, por cada producto, dos movimientos:
  - `transfer_out`: salida del almacén de origen.
  - `transfer_in`: entrada al almacén de destino.
- Todos los productos se procesan dentro de una sola transacción. Si falla cualquier movimiento, ningún saldo del traslado se modifica.
- El traslado no altera el stock mínimo configurado en ninguno de los almacenes.
- La interfaz permite agregar y quitar filas, evita seleccionar el mismo producto dos veces y conserva errores asociados a cada fila.

## Modales y alertas

- Las modales System aplican globalmente backdrop estático y teclado deshabilitado. El usuario solo puede cerrarlas mediante la `X`, el botón de cierre del footer o una acción programática posterior a un resultado válido.
- El preparador interno de `Alerts.js` agrega la estructura reutilizable `br-modal-standard`; los bodies sin navegación por pestañas reciben espaciado horizontal uniforme mediante `.br-entity-modal__body`.
- La modal Registrar movimiento reutiliza el mismo header, body y footer de `br-entity-modal`, sin crear medidas locales.
- Los SweetAlert compactos conservan el color semántico en borde e icono. Sus acciones usan primary para error/success, danger para warning y secondary para question.
- El kardex puede cargar relaciones parciales de sucursal sin exigir `status`; el accessor de `Branch` tolera atributos no seleccionados.
- La relación de usuario del kardex solicita únicamente `id` y `name`. Los accessors de `User` toleran que `gender` o `status` no hayan sido seleccionados y `formatted_preferences` no ejecuta lazy loading: solo transforma preferencias cuando la relación fue cargada explícitamente.
- `Branch`, `Warehouse`, `Item` y `User`, modelos relacionados con el kardex, soportan selecciones parciales sin acceder directamente a claves ausentes.

## Integración

### Productos

- Cada almacén se inicializa con saldo cero.
- Si se informó stock inicial, se genera una entrada por almacén.
- En edición solo puede cambiarse `minimum_stock`; la existencia se modifica desde Inventario.

### Ventas

- Cada producto vendido genera una salida con referencia al detalle de venta.
- Anular una venta solo genera la entrada inversa cuando la política empresarial está activa.
- Con la política desactivada, la respuesta de Ventas recuerda registrar la devolución física cuando corresponda.
- No se descuenta stock adicional al rechazar o anular una venta: la salida ya ocurrió al confirmar la venta. Si el cliente devuelve mercadería, se registra una entrada `customer_return`; si la empresa decide automatizar la devolución al anular, debe activar `company_settings.inventory.restore_stock_on_sale_cancellation`.
- La reposición automática busca la salida original de cada detalle y devuelve el producto al mismo almacén. Las ventas históricas sin movimiento trazable usan el almacén principal como compatibilidad.
- La ruta frontend `stocks_management.movements` está declarada como ruta especial. Sin este mapeo el movimiento existía en base de datos, pero el Kardex no podía consultarlo.

### Compras

El módulo Compras está conectado. Crear el documento no cambia existencias; cada recepción genera entradas `purchase` con costo unitario y actualiza el promedio ponderado. Una compra con recepción no se anula: la mercadería que sale se registra como `supplier_return`.

## Estado de mejoras

- `inventory_stock_alerts` abre o actualiza una alerta al alcanzar el mínimo y la resuelve automáticamente al recuperar stock.
- `GET /stocks_management/alerts` permite consultar alertas abiertas/resueltas por almacén.
- Control de stock consume `GET /stocks_management/alerts` para mostrar una franja compacta de alertas abiertas del almacén seleccionado.
- La alerta visual no reemplaza el estado por fila; funciona como resumen rápido para orientar al usuario antes de revisar la tabla.
- `inventory_guides` e `inventory_guide_items` respaldan guías confirmadas de entrada/salida; cada detalle genera su movimiento inmutable.
- Cantidades, saldos y movimientos conservan cuatro decimales. Esto evita perder consumos pequeños de recetas, insumos fraccionados o traslados medidos por peso/volumen.

## Evolución del módulo

La navegación funcional evita nombres duplicados:

- **Control de stock / Existencias / Reporte Existencias** comparten el saldo materializado de `warehouse_items`.
- **Kardex / Reporte Kardex / Movimientos** comparten `inventory_movements`, con filtros y distintas estrategias de presentación o exportación.
- **Traslados / Transferencia de almacenes** son una sola operación multiproducto.
- **Toma física** es una corrección con origen `physical_count`.
- **Reposiciones y devoluciones** usan `replenishment`, `customer_return` y `supplier_return`.

## Actualizacion: ventas por almacen y caja

- Las ventas ahora guardan `sales_header.warehouse_id`, de modo que el descuento de stock ocurre en el almacen seleccionado por el usuario y no en el primero disponible.
- Si una sucursal tiene varios almacenes, el backend exige indicar el almacen afectado para evitar salidas ambiguas.
- Los movimientos por venta siguen registrandose en `inventory_movements` con origen `sale`, usando el almacen de la cabecera de venta.
- La caja se modela por separado con `cash_registers`, `cash_sessions`, `cash_session_payments` y `cash_movements`.
- Cuando una venta se asocia a `cash_session_id`, sus pagos generan movimientos de caja por metodo de pago. Esto prepara apertura, cierre, arqueo y resumen por metodo sin mezclar trazabilidad financiera con Kardex fisico.
## Alcance operativo

- Inventario carga almacenes desde `CompanyReferenceDataService::stockWarehouses()`, respetando perfil, colaborador y sucursales efectivas.
- La cabecera muestra el alcance activo con `br-operational-scope`, indicando el almacén o vista consolidada sobre la que se aplican consultas, exportaciones y operaciones.
- Un selector vacío en Colaboradores hereda el perfil; no elimina sus restricciones.
- `resource.scope` bloquea consultas, exportaciones, movimientos y traslados con almacenes no permitidos.
- Control de stock, Kardex, Traslados y Kardex valorizado conservan permisos por acción independientes aunque reutilicen controlador y componente Vue.
- Los productos con estado `inactive` no se ofrecen para movimientos, compras ni ventas; permanecen visibles en catálogo para edición y auditoría, pero no deben participar en operaciones nuevas.
