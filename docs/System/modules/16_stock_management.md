# 16 - Inventario

## Propósito

Controla las existencias de productos por almacén y conserva la trazabilidad de cada variación. Los cinco submódulos tienen páginas y URL propias bajo `/inventory`; los nombres de ruta `stocks_management.*` se conservan para respetar permisos y favoritos existentes. El prefijo `/stocks_management` queda reservado a las consultas y operaciones API.

El módulo separa dos conceptos:

- `warehouse_items` conserva el saldo actual y el stock mínimo.
- `inventory_movements` conserva el kardex inmutable: saldo anterior, variación, saldo resultante, motivo, origen y usuario.

## Archivos principales

- Rutas de página: `routes/System/Warehouses/InventoryPages.php`
- Rutas API: `routes/System/Warehouses/StockManagement.php`
- Controlador de páginas: `InventoryPageController`
- Controlador API: `StockManagementController`
- Configuración: `StockManagementConfigService`
- Consulta y compatibilidad: `StockManagementService`
- Núcleo transaccional: `InventoryMovementService`
- Modelo de kardex: `InventoryMovement`
- Vistas Blade: `resources/views/System/general/Warehouses/inventory/`
- Entradas frontend: `resources/js/System/Pages/Warehouses/inventory/`
- Presentación: `stock.vue`, `transfers.vue`, `guides.vue`, `kardex.vue` y `valued-kardex.vue`, cada una con su entrada `.js` y una página fija; no hay tabs ni cambio de submódulo dentro de la vista.
- Estado y operaciones reutilizadas: `inventoryState.js`; montaje y registro de componentes: `mount.js`.
- `InventorySkeleton.vue` conserva filtros, acciones y encabezados durante `initParams`. Su cuerpo usa el `Loader` habitual en una fila `br-table-state` con el `colspan` de la vista. Al terminar los parámetros, `InitialLoadTransition` funde el skeleton con el contenido. El primer listado usa su propio estado de carga y el fundido respeta la preferencia de movimiento reducido.
- Selector reutilizable de almacenes: `resources/js/System/Components/Generics/WarehouseSelect.vue` y `resources/css/System/br-branding/12-warehouse-select.css`.
- Estilos reutilizables: bloque `br-inventory` de `resources/css/System/br-branding/82-inventory.css`

## Búsqueda y presentación del listado

- Editar, seleccionar o limpiar cualquier filtro conserva los registros y la paginación ya cargados. Buscar aplica una copia de los filtros pendientes.
- Las cinco vistas principales de inventario reutilizan `ClearFiltersButton`, con la escoba `fa-broom`, efecto Waves, fondo de cabecera y tooltip «Limpiar filtros». El botón restablece los valores iniciales y conserva el almacén y los registros cargados. Al terminar, `Alerts.showFiltersCleared()` muestra el aviso informativo «Se limpiaron los filtros correctamente.» mediante Toastr. La modal de alertas restablece sus filtros al abrirse.
- Cada consulta de stock, kardex, guías o alertas asigna `Requests.emptyPagination()` antes de esperar la respuesta. Buscar, paginar y reintentar eliminan filas, totales y enlaces anteriores; solo una respuesta exitosa vigente repuebla la tabla. Ante un error queda vacía y libera su loader en `finally`.
- Cada apertura de Ver alertas reinicia Producto y Stock mínimo antes del GET. Buscar aplica los cambios de Producto y Stock mínimo. La paginación, los reintentos y las descargas reutilizan los valores aplicados guardados en `appliedAlert*`. Las filas de loader, vacío y error usan `br-table-state` y no reaccionan al hover.
- En Ver alertas, Producto ocupa `col-sm-6` y todo el ancho en móvil, mediante columnas Bootstrap. La X solo vacía el texto y devuelve el foco; conserva los resultados, el filtro aplicado y las descargas hasta pulsar Buscar. No conectar el evento `clear` a una búsqueda.
- El valor de Almacén de trabajo en la modal usa `--br-stock-quantity-font-size`, igual que los valores de Stock actual y Diferencia, conservando su color y peso.
- Cambiar de almacén sí invalida los datos del contexto anterior antes de cargar los del nuevo almacén.
- Stock actual y Diferencia comparten `--br-stock-quantity-font-size`, definido en `br-branding/00-tokens.css` a partir de la escala de `Shared/typography-tokens.css`. Ajustar ese alias cambia ambos tamaños y conserva sus colores y peso.
- Las etiquetas Cód. interno y Cód. barras usan `colon-at-end` tanto en el listado como en el modal de alertas.
- Todos los listados de inventario y el modal de alertas pasan el objeto paginado y su estado de carga al `Paginator` compartido. Su pie incorpora el total filtrado a la derecha, incluso con cero registros cuando termina la consulta, y permanece fuera del scroll. Durante búsquedas y cargas, el componente compartido oculta juntos el paginador y el contador; la distancia con la tabla usa `--br-list-pagination-gap`, sin ajustes locales.
- El contenido de Estado del stock se centra dentro de su celda. Ver alertas muestra primero el loader de SweetAlert; la modal se abre después de recibir la respuesta, cerrar el loader y actualizar su contenido. Una respuesta fallida muestra el error y la opción de reintentar en la modal; una respuesta de un almacén o contexto anterior no puede abrirla. La búsqueda y paginación dentro de la modal conservan la instancia abierta y no solicitan otra apertura.

## Tipos de movimiento

### Entrada

Suma una cantidad positiva al saldo. Orígenes implementados:

- `product_opening`: stock inicial al crear un producto.
- `manual`: entrada registrada desde Inventario.
- `sale_cancellation`: reposición automática al anular una venta.
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
- Ventas y POS venta/Caja consultan `company_settings.inventory.allow_negative_stock_on_sale`. Por defecto es `false`, por lo que no se confirma una venta si algún producto queda por debajo de cero en el almacén seleccionado.
- Cada movimiento sincroniza `inventory_stock_alerts`: si el saldo queda menor o igual al stock mínimo se abre o actualiza una alerta; si vuelve a estar por encima del mínimo, la alerta se resuelve.
- Si `company_settings.inventory.stock_alert_email_enabled` está activo, al abrir una nueva alerta se envía un correo al destinatario configurado en `stock_alert_email_to` o al correo de la empresa. Las actualizaciones de una alerta abierta no reenvían correo.

## Interfaz

- Cada submódulo conserva su página y breadcrumb propios. No hay títulos descriptivos duplicados ni tabs entre submódulos: se cambia de página desde el menú lateral.
- El selector de **Almacén de trabajo** define el contexto del listado, independientemente del almacén elegido al registrar una operación o un traslado. **Acciones** contiene **Descargar Excel**.
- Las cinco páginas tienen **Buscar**. Elegir otro almacén o cambiar un filtro conserva los resultados anteriores. **Buscar** aplica los cambios y consulta desde la primera página. Sin resultados se reutilizan el título y subtítulo predeterminados de `WithoutData`, igual que en Productos.
- Los selectores de almacén identifican `Sucursal - Almacén`. Traslados ofrece selectores independientes para origen y destino, y cada uno admite consultar todos los almacenes disponibles. Su tabla muestra primero la sede y debajo el almacén. Los nombres se resuelven también para almacenes inactivos, dentro del alcance autorizado. El origen y destino al registrar un traslado deben ser almacenes reales.
- `Catálogo e inventario` expone los cinco accesos bajo `Inventario`:
  - **Control de stock**: `/inventory/stock`, existencias actuales, mínimos y alertas.
  - **Traslados**: `/inventory/transfers`, movimientos multiproducto entre almacenes.
  - **Guías**: `/inventory/guides`, entradas y salidas numeradas.
  - **Kardex**: `/inventory/kardex`, historial y trazabilidad.
  - **Kardex valorizado**: `/inventory/valued-kardex`, costos y valores.
- Cada URL entrega su propio Blade y entrada Vite. El componente compartido recibe la página como propiedad fija, carga las opciones iniciales sin consultar el listado y conserva filtros/formularios específicos.
- **Producto** se puede buscar en Control de stock, ambos Kardex y Traslados. **Registrar operación** aparece en Control de stock y **Registrar traslado** abre su formulario en Traslados. Guías ofrece sus filtros de almacén, tipo y fechas.
- Los cinco listados comparten la superficie `br-entity-list`, cabecera `br-table-header-surface`, indicador de carga y estado vacío ilustrado de Productos. La tabla es la única superficie bajo los filtros; la paginación aparece solo si hay registros.
- Control de stock muestra la cabecera **Estado del stock** en una línea en la tabla y en su skeleton. **Producto** usa `ProductIdentity` y un ancho de referencia del 37 % en Control de stock y ambos Kardex. Control de stock, Kardex, Kardex valorizado, Guías y el detalle de Traslados usan la variante desplegable con «+» y «−» para consultar los códigos por producto. Las columnas de Kardex pueden redistribuir el espacio si la **Variación** requiere más ancho para mantenerse en una línea. Kardex conserva **Variación** y **Saldo**. Kardex valorizado conserva **Variación**, **Costo unitario**, **Valor movimiento** y **Valor resultante**. Cada skeleton usa las columnas de su vista.
- En ambos Kardex, **Movimiento** muestra primero la fecha y el tipo. Debajo agrupa el origen y el responsable, seguidos por el motivo y la referencia cuando aportan información. Si el motivo repite el origen, se muestra una sola vez. Las cabeceras de **Producto** y **Movimiento** van centradas y las numéricas, a la derecha. El skeleton conserva esa alineación.
- En **Kardex valorizado**, cada cantidad o importe de **Variación**, **Costo unitario**, **Valor movimiento** y **Valor resultante** se mantiene en una sola línea. La tabla permite desplazamiento horizontal cuando los valores requieren más espacio.
- Los filtros permanecen sobre el fondo de la página y separados de la tabla por un espacio visible. El contenedor de contenido no usa el estilo blanco de tarjeta; únicamente `br-entity-list` delimita el listado.
- Desde `lg`, ambas vistas de Kardex muestran **Almacén de trabajo**, **Producto**, **Movimiento**, **Desde** y **Hasta** en la primera fila, con 3, 3, 2, 2 y 2 columnas. La segunda fila reúne **Realizado por**, **Categoría**, **Marca** y la botonera, con 3 columnas por grupo. En pantallas menores los campos se distribuyen en más filas.
- Desde `lg`, la primera fila de Control de stock reúne **Almacén de trabajo**, **Producto**, **Estado del stock**, **Stock mínimo** y **Último movimiento**, con 3, 3, 2, 2 y 2 columnas. **Categoría** y **Marca** siguen en la misma cuadrícula, con 3 columnas cada una. **Buscar**, **Limpiar filtros** y **Acciones** van al inicio de la botonera. **Registrar operación** y **Ver alertas** se alinean a la derecha, precedidos por un separador gris que usa `--br-border-muted`. Los grupos se mantienen juntos al saltar de línea y el skeleton usa la misma distribución.
- **Traslados** muestra los filtros **Almacén de origen**, **Almacén de destino**, **Referencia**, **Desde**, **Hasta**, **Registrado por**, **Producto** y **Estado del traslado**, en ese orden. Registrado por usa el mismo ancho que Almacén de destino y lista los autores de cabeceras en almacenes accesibles, incluidos traslados pendientes sin movimientos de kardex. Ambos filtros de almacén ofrecen la opción real «Todos los almacenes», seleccionada por defecto y al limpiar filtros. No usan ese texto como placeholder ni permiten dejar la selección vacía. La opción general omite el ID correspondiente de la consulta y respeta los permisos; el formulario Registrar traslado sigue ofreciendo únicamente almacenes reales. Las fechas incluyen ambos extremos y **Referencia** admite coincidencias parciales. **Buscar** aplica la selección a la tabla y al Excel. Registrado por filtra `inventory_transfers.user_id`, sin alterar la consulta al seleccionar una opción.
- **Registrar operación** y **Registrar traslado** abren modales estándar con título breve, **Cerrar** y **Guardar**.
- **Control de stock** muestra siempre la columna **Almacenes**, además de saldo actual, stock mínimo y estado del stock por producto. Con un almacén seleccionado, la columna identifica ese almacén y su cantidad; con **Todos los almacenes**, presenta el desglose por almacén y el contador de almacenes que requieren atención.
- **Registrar operación** permanece disponible incluso con **Todos los almacenes** seleccionado. Su modal exige elegir expresamente un **Almacén de la operación** real y un **Tipo de operación**; ambos empiezan vacíos y no cambian el filtro del listado. El formulario admite uno o hasta 100 productos. En **Ingresos** solo ofrece entrada manual y devolución de cliente; también permite salida manual, toma física y devolución a proveedor según el grupo correspondiente.
- Al elegir cualquiera de los cinco tipos de operación, debajo de **Productos incluidos** aparece `TemplateFilePicker.vue` con **Descargar plantilla** y **Subir plantilla**. Cada tipo genera su propio encabezado: **Saldo contado** para toma física; **Cantidad de entrada** para entrada manual; **Cantidad devuelta** para devolución de cliente; **Cantidad de salida** para salida manual; **Cantidad al proveedor** para devolución a proveedor. Las entradas incluyen **Costo unitario opcional**. Todas identifican producto, unidad de medida y stock actual del almacén elegido.
- La vista previa valida tipo de plantilla, códigos, productos activos, duplicados, unidad, cantidades, precisión y límite de 100 filas. Ignora las filas sin cantidad; no cambia existencias. Un SweetAlert comunica el resultado o error. Cambiar de operación o almacén descarta la vista previa anterior para evitar registrar el archivo con otro contexto. Solo **Guardar**, después de la confirmación, crea los movimientos.
- Las cantidades de las cinco páginas y de la vista de inventario de Productos muestran la unidad singular o plural del producto. Los Excel de stock, kardex y traslados mantienen las cantidades como números y añaden **Unidad de medida** en una columna separada; las guías detallan cada producto con cantidad y unidad.
- La modal de operación presenta primero **Almacén de la operación**, siempre sin preselección, y una nota en negrita sobre el alcance del cambio, sin un rótulo adicional. Después aparecen **Tipo de operación** y su alerta de efecto, también sin rótulo; **Motivo** ocupa todo el ancho, no tiene placeholder y admite hasta 100 caracteres. Los productos se muestran en una tabla. El costo unitario solo aparece en entradas; el encabezado indica que es opcional y un tooltip explica que, al omitirlo, se conserva el costo promedio actual. Eliminar es un icono rojo sin recuadro con tooltip persistente y **Guardar** reutiliza el botón primario de Productos. Antes de enviar, se confirma por SweetAlert el almacén, tipo de operación y número de productos; cancelar no altera el stock.
- El selector de operación agrupa las opciones bajo **Actualización**, **Ingresos** y **Salidas** sin alterar los códigos enviados al backend. El efecto presenta título y explicación breve en una sola línea, con la altura del selector. **Productos incluidos** se marca como obligatorio. **Agregar producto** es un botón verde de tamaño normal, sin icono, en el pie de la modal y a la izquierda de **Guardar**. Operaciones y traslados comparten `ProductSelect.vue`, con los mismos estilos y estado vacío de `v-select`.
- En las tablas de operación y traslado, Producto, Cantidad y Costo unitario respetan el bloqueo del formulario: el selector no ofrece búsqueda si está deshabilitado, los controles pierden el foco al bloquearse y el cursor de escritura no aparece. Al habilitarse, recuperan la interacción normal.
- **Kardex** muestra fecha, usuario, producto, tipo, variación, saldo anterior/resultante, motivo y origen.
- **Traslados** mueve uno o varios productos entre almacenes de la misma empresa mediante salidas y entradas atómicas. **Registrar traslado** está disponible con permiso de operar. La modal pide seleccionar almacenes reales de origen y destino. Ambos empiezan vacíos y elegirlos conserva el filtro del listado. La tabla muestra **Producto**, **Stock disponible** en el origen y **Cantidad a trasladar**. El stock se vuelve a consultar al cambiar el origen o los productos y se presenta con la unidad de medida y la precisión de la empresa. El servidor comprueba nuevamente el saldo al confirmar el envío. El motivo es obligatorio y admite hasta 255 caracteres.
- En **Registrar operación** y **Registrar traslado**, la papelera también permite quitar la última fila. El formulario vacío ofrece **Agregar producto** y requiere al menos una fila antes de guardar.
- En **Registrar traslado**, **Eliminar productos** es un botón rojo a la derecha de **Agregar producto**. Quita todas las filas y sus cantidades del borrador y conserva los almacenes y el motivo.
- En **Registrar traslado**, el campo **Cantidad a trasladar** muestra a la derecha la abreviatura de la unidad del producto seleccionado. Cuando la cantidad supera el stock disponible, el grupo reutiliza el estado inválido de `InputNumber` y muestra en rojo el borde, el foco y la unidad. **Guardar traslado** queda deshabilitado mientras alguna fila supere su saldo. **Agregar producto** está en el encabezado de **Productos incluidos** y desplaza la tabla hasta la fila nueva.
- **Registrar traslado** muestra primero dos selectores del mismo ancho y una flecha con los colores de **Acciones** que apunta del origen al destino. En pantallas estrechas los selectores se ordenan en vertical cuando hay altura suficiente. En ventanas bajas van lado a lado para dejar espacio a las filas. El motivo, la tabla de productos y las acciones de agregar y guardar aparecen cuando se eligen dos almacenes distintos. Si falta una selección, el formulario oculta esos campos y conserva el borrador. El desplazamiento de la modal queda dentro de la tabla **Productos incluidos**, con la cabecera fija. El icono de acceso directo **Motivos frecuentes** abre una modal estándar del mismo ancho que el traslado. El fondo se oscurece detrás de la modal activa. La tabla presenta diez plantillas con las columnas **Motivo** y **Acción**. **Seleccionar** copia el texto, cierra la modal de motivos y enfoca **Motivo del traslado** para editarlo.
- `WarehouseSelectionField` permite conservar el selector o resumir su valor. Registrar traslado usa `summarizeSelection=false`: origen y destino permanecen como select2 con sede y almacén visibles, sin convertirse a texto. Su modo de resumen predeterminado sigue disponible para otros usos. Si el nuevo origen coincide con el destino, este último vuelve a quedar pendiente.
- El selector de productos de **Registrar traslado** consulta `/options/inventory-transfers/items` mediante `Options.js`. Carga la primera página al abrir, busca por nombre, código interno o código de barras y ofrece más resultados en grupos de 25. La respuesta incluye los datos necesarios para elegir el producto y mostrar su unidad. Al elegirlo, la fila muestra su nombre y la acción **Cambiar**, con el mismo estilo que **Ver más**. Al cambiarlo, la selección anterior se conserva hasta elegir otra. Las búsquedas omiten los productos elegidos en otras filas.
- **Guías** lista documentos de entrada/salida con número, fecha, almacén, productos, referencia, motivo y estado.
- **Kardex** y **Kardex valorizado** combinan los filtros de producto con el tipo, el responsable y el intervalo de movimientos. **Realizado por** permite buscar por nombre entre los usuarios con movimientos en almacenes disponibles para la cuenta, incluidos autores históricos inactivos. Ambos Kardex aplican los filtros al pulsar **Buscar**. La paginación y el Excel utilizan los filtros aplicados.
- El almacén seleccionado limita existencias y Kardex. En **Traslados**, origen y destino tienen sus propios filtros y se aplican a su extremo correspondiente. La tabla destaca la sede y debajo muestra el almacén. La consulta general se limita a operaciones con al menos un extremo autorizado. Si el perfil tiene acceso a un solo extremo, el otro se muestra como **Almacén no visible** también en el detalle, el PDF y el Excel.
- `WarehouseSelect` centraliza la presentación de almacenes en el filtro de trabajo y en las modales de operación y traslado. El desplegable y el valor seleccionado destacan la sucursal en negrita y muestran el almacén en peso normal, separados por un guion. Sus estilos viven en `12-warehouse-select.css` y se reutilizan sin duplicar el marcado.

### Búsqueda y lectura de códigos

- Las páginas que admiten búsqueda de producto conservan la consulta por nombre, código interno o código de barras.
- El buscador reutiliza `InputText` sin prefijos visuales y admite nombre, código interno y código de barras. Los filtros y selectores de productos de Inventario comparten el placeholder «Buscar producto o escanear código», también en las modales.
- El campo admite el código enviado por lectores físicos; la consulta se ejecuta al pulsar **Buscar**, no al cambiar el almacén ni al introducir el código.
- En **Control de stock**, una coincidencia exacta filtra y resalta el producto. **Traslados** busca por nombre, código interno o código de barras en las posiciones al pulsar **Buscar**. Si coinciden varios productos de un traslado, el listado y el contador lo muestran una sola vez. El detalle contiene todos los productos de la operación. Editar los filtros conserva el listado hasta la siguiente búsqueda.
- En ambos Kardex, las coincidencias exactas o parciales muestran los movimientos del producto que cumplen los demás filtros.
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
- La operación manual genera un movimiento por producto. Un traslado genera una salida al enviar y una entrada por cada cantidad recibida; las recepciones parciales pueden generar múltiples entradas.

### Cabecera, posiciones, envíos y recepciones de un traslado

El flujo se centraliza en InventoryTransferService. Crear una cabecera no implica haber recibido mercancía. Los estados de recepción se calculan con las cantidades de todas las posiciones; no se seleccionan manualmente.

| Código | Estado | Significado |
| --- | --- | --- |
| awaiting_dispatch | Por enviar | Registro inicial. Todavía no hay salida ni reserva de stock. |
| in_transit | En tránsito | Salida confirmada del origen; aún no se registró una recepción. |
| partially_received | Recibido parcialmente | Existe alguna cantidad recibida y quedan cantidades por recibir o resolver. |
| received | Recibido | Todas las posiciones fueron recibidas completamente, sin diferencias. |
| closed_with_differences | Cerrado con diferencias | No queda cantidad pendiente, pero hubo devolución o pérdida justificada. No equivale a recepción completa. |
| canceled | Cancelado | Anulado antes del envío, sin efectos en stock. |

- El valor inicial es **Por enviar**. También se puede elegir **En tránsito** al registrar: se crean el registro y la salida dentro de la misma transacción, con sus eventos reales. **Recibido** y **Recibido parcialmente** solo resultan de confirmar cantidades.
- Confirmar envío descuenta cada producto del origen y guarda el valor retirado como inventario en tránsito. El destino no aumenta hasta recibir. No se puede cancelar una vez enviado; corresponde recibir, devolver al origen o justificar una pérdida.
- Cada recepción ingresa únicamente la cantidad confirmada al destino, con fecha y responsable propios. Se pueden registrar múltiples recepciones, incluso del mismo producto. La cantidad registrada originalmente queda inmutable.
- Para una posición enviada: pendiente = enviada − recibida − resuelta. Antes de enviar, la cantidad enviada es cero. Las posiciones muestran Por enviar, Por recibir, Recibido parcialmente, Recibido, Resuelto con diferencias o Cancelado.
- La cabecera se determina revisando cada posición; no se suman cantidades de productos con unidades incompatibles. Una posición con cuatro unidades pendientes mantiene abierto el traslado aunque todas las demás estén completas.

| Tabla | Datos y responsabilidad |
| --- | --- |
| inventory_transfers | Referencia única, almacenes, creador, motivo, fecha, estado calculado y version para concurrencia. Conserva los datos de cancelación. |
| inventory_transfer_items | Una posición por producto: quantity original, outbound_movement_id, received_quantity, resolved_quantity, transit_value y estado de posición. sent_quantity y pending_quantity son valores derivados. |
| inventory_transfer_operations | Documento inmutable por recepción o resolución: kind (receipt, return, loss), request_key, payload_hash, user_id, copia user_name, occurred_at y reason. |
| inventory_transfer_operation_items | Cantidad y valor de cada posición procesada y vínculo movement_id. Una posición puede aparecer en múltiples documentos. Las pérdidas no generan un movimiento de almacén, porque la mercancía ya salió al enviar. |
| inventory_transfer_status_events | Cada alta, envío, cancelación, recepción o resolución con action, estado resultante, fecha, responsable y operation_id cuando corresponde. Admite estados repetidos. |
| inventory_movements | transfer_out al enviar, transfer_in por cada recepción y transfer_return al confirmar una devolución al origen. origin_id referencia la cabecera. |

#### Recepción y resolución de diferencias

- El detalle ofrece **Registrar recepción** cuando hay cantidades en tránsito. Por posición, **Recibir todo lo pendiente** completa el input; desmarcar solo vacía la cantidad de esta captura. Nunca revierte recepciones anteriores. Se puede digitar una cantidad menor.
- La captura compara **Enviada**, **Recibida**, **Pendiente**, **Resuelta** y el estado de cada producto. Antes del envío presenta la cantidad Por enviar. Las cantidades usan InputNumber, QuantityValue y los decimales configurados de la empresa.
- Confirmar registra solo las cantidades positivas. Se rechazan sobre-recepciones, cantidades negativas, claves de posición ajenas al traslado y productos duplicados. Una recepción inválida no guarda documentos, movimientos ni eventos parciales.
- **Resolver diferencia** permite **Devolución al origen** o **Pérdida justificada**, con motivo obligatorio. La devolución incrementa el stock del origen por lo efectivamente devuelto. La pérdida reduce el valor que sigue en tránsito y queda documentada; no incrementa ningún almacén. Ambas consumen únicamente lo pendiente, nunca lo recibido.
- El checkbox facilita la captura; no determina los estados. Los estados de posición y cabecera se calculan en backend. Después de una recepción parcial se pueden continuar recibiendo o resolver las diferencias.
- **Recepciones y diferencias registradas** presenta los documentos y sus posiciones, fechas, responsables y motivos. El roadmap registra cada operación, incluso si la cabecera continúa Recibido parcialmente. No se editan ni eliminan recepciones confirmadas desde esta pantalla.
- Registrar, enviar y recibir son acciones distintas. Se conserva el creador en inventory_transfers.user_id; Enviado por corresponde al responsable que confirma el envío. Recepciones registradas por lista los responsables de todas las recepciones. El nombre de cada operación y evento se conserva aunque posteriormente se renombre o elimine al usuario.

#### Stock, valorización e integridad

- Todas las altas, envíos, recepciones, devoluciones y pérdidas son transaccionales. El envío bloquea la cabecera y procesa los productos en orden estable. Si cualquier salida falla, no se modifica ningún saldo del lote. Una recepción fallida conserva lo pendiente y sus valores.
- El envío valida nuevamente el stock real. Por enviar no reserva mercancía: otros movimientos pueden cambiar el saldo antes de confirmar la salida.
- Cada posición mantiene transit_value. Una recepción o resolución recibe una proporción del valor restante según la cantidad pendiente; la última porción absorbe el residuo. La suma de lo recibido, devuelto, perdido y aún en tránsito conserva el valor retirado del origen. Se usan exclusivamente Numbers y la precisión configurada (0–3), también para exportación.
- El stock disponible de cada almacén excluye lo que salió y aún está en tránsito. No se crea un almacén ficticio ni se permite vender esa cantidad desde el destino antes de recibir. El valor en tránsito se consulta en las posiciones del traslado y en su Excel.
- Cada operación exige request_key UUID y expected_version. La misma clave con el mismo contenido devuelve el mismo documento, incluso después del cierre del traslado. La misma clave con contenido diferente y las versiones antiguas se rechazan con 409.
- El bloqueo de cabecera serializa recepciones concurrentes. Una versión se incrementa por envío, cancelación o documento confirmado. La unicidad se aplica a (inventory_transfer_id, request_key), no al estado; múltiples recepciones parciales tienen eventos independientes.
- El origen y el motivo de Kardex se consultan desde la cabecera. Los movimientos de recepción incluyen el identificador de operación; cada posición de operación conserva su vínculo al movimiento. Las pérdidas quedan en el documento de resolución y su valor, sin descontar dos veces del almacén de origen.

#### Endpoints y permisos

- GET /stocks_management/transfers pagina cabeceras (10 por defecto). Listado, detalle, PDF y Excel respetan los almacenes accesibles.
- POST /stocks_management/transfers registra Por enviar o registra y envía si se eligió En tránsito; requiere permiso de crear y acceso a ambos almacenes.
- PATCH /stocks_management/transfers/{id}/status solo admite confirmar envío o cancelar, con expected_version. Requiere permiso operate y acceso al origen. Solo se cancela Por enviar.
- POST /stocks_management/transfers/{id}/receipts confirma una recepción. Requiere operate y acceso al destino; el operador receptor no necesita acceso al origen.
- POST /stocks_management/transfers/{id}/resolutions resuelve cantidades pendientes por return o loss, con motivo. Requiere operate y acceso a ambos almacenes.
- El detalle devuelve can_dispatch, can_cancel, can_receive y can_resolve calculados en servidor. La autorización se verifica nuevamente al ejecutar cada acción y se mantiene al reintentar una operación ya confirmada.

#### Presentación y reportes

- Las columnas siguen el orden Referencia, Almacén de origen, flecha, Almacén de destino, Detalle, Estado y acciones. Origen se alinea a la derecha, Destino y Detalle a la izquierda. El contador circular representa productos distintos; no unidades recibidas. Se centra el conjunto flecha-contador con espacio equilibrado. El skeleton mantiene la distribución.
- Detalle agrupa fecha de registro y autor, con sus iconos a la izquierda y tooltips. Las fechas reutilizan DateUtils.legibleFormatDate, sin coma y con ceros iniciales, por ejemplo 02/11/2026 09:03 AM.
- Registrar traslado coloca ambos almacenes dentro de col-md-10 y el estado en col-md-2; el motivo ocupa col-12 después de seleccionarlos. Origen y destino permanecen como selectores, incluso después de elegir un valor: WarehouseSelectionField recibe summarizeSelection=false. No se convierten a texto ni muestran Cambiar. Otros usos del componente conservan su modo de resumen predeterminado. Los selectores mantienen append-to-body para no recortarse. La botonera de filtros sigue al último filtro cuando hay espacio y conserva su separador.
- Ver detalle es la única acción de fila. La modal usa modal-xl, br-entity-modal--tabbed y cabecera solo con título. En el cuerpo, una cabecera de datos permanece por encima de las pestañas y muestra referencia, almacén de origen, flecha, almacén de destino y estado; no depende del panel activo ni se repite en Resumen. En pantallas estrechas se reorganiza sin recortar nombres. Reutiliza CatalogFormTabs con variant=descriptive, igual que Productos: icono, título y descripción en cuatro pestañas, sin envolver la cuarta en otra fila. En pantallas estrechas usa la navegación horizontal compartida. Resumen agrupa registro y responsables junto al motivo, con un separador discreto, y muestra la cancelación cuando existe; Productos contiene cantidades, estados y captura; Recepciones muestra documentos y diferencias con el primero expandido, reutiliza ProductIdentity y el estado vacío compartido cuando no hay documentos; Historial contiene el roadmap en una superficie suave delimitada. El contenido y la cabecera de datos usan br-entity-form-section para respetar los mismos márgenes laterales de las pestañas y el pie. Los labels y valores reutilizan la tipografía existente. Conserva la transición compartida, navegación por teclado y panel accesible. Cada apertura comienza en Resumen; Registrar recepción o Resolver diferencia activa Productos. Cambiar de pestaña mantiene la captura. Los documentos se expanden individualmente y muestran fecha y responsable en una segunda línea. Imprimir se ofrece dentro del detalle. Los resultados de guardar siguen el estándar compartido de Swal y modales; los errores conservan la captura.
- PDF y Excel incluyen cantidades enviadas, recibidas, pendientes y resueltas, responsables de envío/recepción, historial y documentos. El Excel incluye el valor en tránsito y usa Numbers para cantidades y montos.
- internal_code_prefixes.inventory_transfer conserva el prefijo parametrizable (TRAS inicial, vacío o nulo sin prefijo). Cambiarlo solo afecta referencias nuevas.

#### Esquema inicial y datos anteriores

- El esquema está en el create inicial del tenant, 2024_02_11_223124_create_init_companies_table.php. No se agregan migraciones incrementales para este cambio de desarrollo.
- La demo fue convertida sin modificar warehouse_items ni inventory_movements. Los antiguos Aceptado pasan a Recibido y sus recepciones se respaldan con los movimientos de entrada existentes. Se preservan los vínculos de posiciones, autores y fechas de esa evidencia.
- Los antiguos Enviado sin salida en Kardex quedan Por enviar: el flujo anterior no había descontado stock. Su evento se identifica como envío anterior sin salida. Requieren confirmar el envío con la lógica nueva; no se fabrican salidas retroactivas.
- Una anulación histórica posterior a recepción conserva su recepción y reverso como documentos respaldados por Kardex. Es una excepción histórica de solo lectura; el nuevo flujo impide cancelar después de enviar. Los datos no evidenciados se indican como no registrados.
- El antiguo inbound_movement_id único se sustituye por los vínculos a movimientos de cada documento. Se verificó la conservación de los saldos y la cantidad de movimientos al convertir la demo.

## Exportación

- Las cinco páginas permiten descargar un Excel con los filtros aplicados. El backend comprueba el permiso de la página solicitada además del permiso general de exportación.
- **Control de stock** exporta código interno, código de barras, producto, unidad de medida abreviada (por ejemplo, `UND`), stock actual, stock mínimo, almacenes, cantidad de almacenes con alerta y estado del stock. La misma abreviatura se muestra junto a las cantidades en las vistas de inventario y en las demás exportaciones; siempre proviene del registro de `measurement_units`, sin valores fijos en frontend ni backend. La importación acepta tanto la abreviatura como los nombres de esa misma unidad.
- **Kardex** exporta fecha, almacén, identificación, producto, movimiento, origen, saldos, motivo, referencia y responsable.
- **Traslados** exporta una fila por cabecera con los mismos filtros que la tabla, incluyendo referencia, fecha, responsable, almacenes, estado, motivo y el detalle completo de productos y cantidades.
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
- Las entradas manuales y devoluciones de cliente aceptan costo unitario opcional; si se omite, conservan el promedio actual.
- Nunca usa el precio de venta como costo.
- La página y su Excel están activos y usan los mismos filtros del Kardex.
- La vista muestra una ayuda compacta: `unit_cost` es el costo usado para valorizar el movimiento, y si una entrada manual no lo informa se conserva el promedio ponderado vigente del almacén.

## Guías de entrada y salida

- `stocks_management.guides.index` es una página independiente dentro de Inventario y puede habilitarse por perfil.
- Permite filtrar por almacén, tipo de guía, rango de fechas y producto.
- Las guías se consultan con su numeración, estado, detalle de productos, referencia, motivo y responsable.
- Su Excel usa el mismo alcance operativo que la vista; si el usuario solo tiene acceso a ciertos almacenes, no exporta información fuera de ese alcance.

## Traslados entre almacenes

- Los almacenes de origen y destino se seleccionan explícitamente en el formulario.
- **Registrar traslado** abre una modal estándar con origen, destino, productos y motivo. La tabla de movimientos permanece visible en la página; el formulario no ocupa espacio permanente.
- En Traslados, **Almacén de origen**, **Almacén de destino** y **Producto** ocupan tres columnas desde `lg`. **Referencia**, **Desde**, **Hasta** y **Estado del traslado** ocupan dos. La botonera usa la distribución de Control de stock con **Buscar**, **Limpiar filtros** y **Acciones** a la izquierda, y **Registrar traslado** a la derecha tras el separador gris. En pantallas estrechas, los grupos saltan de fila según el espacio. El skeleton usa la misma distribución. La tabla muestra la flecha de **Registrar traslado** entre el almacén de origen y el de destino.
- Debajo de los controles solo aparece el listado: usa la superficie `br-entity-list` y la cabecera `br-table-header-surface` de Productos, sin mensajes auxiliares cuando no hay un segundo almacén.
- El formulario inicia sin almacenes seleccionados. El usuario confirma origen y destino. Cerrar descarta la captura; un error de guardado conserva sus datos mediante el estándar compartido de modales.
- El destino debe ser distinto y pertenecer a la misma empresa.
- Cada operación admite entre 1 y 100 productos sin duplicados.
- Cada producto debe estar activo y ser de tipo `product`.
- Cada cantidad debe ser mayor que cero y no puede superar el saldo disponible del producto en el origen.
- El motivo es obligatorio.
- El traslado usa una referencia común: `transfer_out` descuenta al enviar, `transfer_in` ingresa cada recepción y `transfer_return` registra una devolución al origen. Las pérdidas se justifican en su documento sin duplicar la salida.
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

- Una alerta actual requiere atención cuando el stock es menor o igual a cero, haya o no mínimo configurado, o cuando el mínimo es positivo y el stock lo alcanza o queda por debajo. Esta regla coincide con Agotado y Stock bajo del listado general. Los movimientos abren/actualizan `inventory_stock_alerts` con esa misma condición y resuelven la alerta al recuperar stock saludable.
- `GET /stocks_management/alerts` consulta las alertas actuales por almacén directamente desde productos y existencias activas, sin depender de que haya un registro histórico. Incluye productos sin fila de inventario (cantidad y mínimo cero, como en el listado general) y evita duplicados aunque existan varias alertas guardadas del mismo producto. `status=resolved` conserva la consulta del historial resuelto. Consultar las alertas actuales no crea filas, no modifica stock y no envía correos.
- Control de stock consulta las alertas solo al abrir **Ver alertas**. La modal usa `modal-xl` de Bootstrap, sin una clase de tamaño propia, y muestra **Almacén de trabajo:** con el mismo texto sede–almacén del selector general y mayor jerarquía visual. Debajo se muestran **Producto:**, **Stock mínimo:**, **Buscar** y el desplegable compartido **Acciones**. No hay campana ni contador junto a Acciones; el total aparece únicamente en el pie paginado compartido. **Stock mínimo** reutiliza las opciones del listado general: Todos los productos, Con mínimo configurado y Sin mínimo configurado. Cambiar el mínimo o escribir en Producto prepara los filtros de la próxima búsqueda. **Acciones** agrupa **Descargar PDF** y **Descargar Excel**, y se deshabilita sin resultados. En las tablas principal y de alertas, **Stock actual** tiene un énfasis tipográfico moderado para diferenciarlo de **Stock mínimo**. La apertura, búsqueda y paginación muestran el Swal de carga como único indicador animado; el listado usa `WithoutData` estático mientras espera y cuando no hay resultados. La paginación y ambas descargas conservan el almacén, el producto y el estado de mínimo aplicados al listado, independientemente de cambios aún no enviados en los campos. La cabecera queda fuera del panel de scroll vertical; solo se desplazan los registros. Al finalizar la consulta, la paginación y el contador quedan fuera de ese scroll; durante la carga se ocultan ambos. Detectada conserva la fecha de la alerta abierta registrada; si no existe historial, se muestra «—» tanto en pantalla como en PDF/Excel, sin inventar una fecha de detección.
- **Diferencia** en Control de stock y en la modal de alertas es `Stock actual − Stock mínimo`, con signo y abreviatura de unidad. Positiva se muestra en verde, negativa en rojo y cero en tono neutro. Se reutiliza el mismo componente y precisión visual en ambas tablas; no altera el estado del stock ni los datos persistidos.
- **Estado del stock** en la tabla principal colorea toda la celda según el estado existente: rojo para **Agotado**, amarillo para **Stock bajo** y verde para **Stock saludable**. El texto queda sin badge ni icono; el color no desaparece al pasar el cursor por la fila. La misma denominación se usa en el filtro y la exportación.
- Inventario usa 10 registros por página en sus listados, resumen, alertas y trazabilidad. El backend toma el valor de `Utilities::$per_page_default` y admite solicitudes explícitas de hasta 30 registros.
- Ambas tablas terminan en una columna visualmente vacía, con nombre accesible «Acciones», que reutiliza `TableActionsDropdown` de Productos. «Modificar stock» abre `StockItemEditor` para editar stock actual y mínimo del producto en el almacén seleccionado, con motivo opcional y confirmación antes de guardar. El skeleton de Control de stock conserva esa sexta columna.
- En la tabla principal, «Ver trazabilidad» abre `StockTraceabilityModal` sin salir de Control de stock. Consulta únicamente el producto de la fila y el almacén seleccionado, con movimientos recientes primero, fecha, responsable, tipo, variación, saldo anterior/final y motivo/origen. Reutiliza los formatos y unidades del kardex y el paginador compartido de 10 registros; mantiene encabezado visible y el pie fuera del área de scroll.
- `GET stocks_management/traceability` exige `warehouse_id` e `item_id`, permiso de lectura de Control de stock y acceso al almacén. Reutiliza la consulta del kardex, pero devuelve una proyección sin costos, valorización ni metadatos internos; no amplía permisos sobre el kardex general. La acción está disponible para lectores aunque no puedan modificar stock. Se consulta solo al pulsarla, vacía los resultados antes de cada petición y muestra Swal antes de abrir la modal. Cambiar el contexto descarta las respuestas anteriores; errores y vacíos conservan estados sin hover y reintento.
- El editor usa `POST stocks_management/stock-levels` (`stocks_management.levels.update`), `UpdateStockLevelsRequest` y el permiso **Operar** de Control de stock; valida además el alcance del almacén. `can_update_stock` se calcula en cada respuesta de `initParams`, fuera de la configuración cacheada de permisos. No se exponen acciones de edición a lectores sin permiso de operar.
- `InventoryMovementService.updateStockLevels` bloquea la fila de inventario y comprueba los saldos esperados antes de actualizar ambos valores en una transacción. Cambiar el stock actual registra una corrección de origen `physical_count`, conservando la valorización y el kardex. Cambiar solo el mínimo no inventa un movimiento; actualiza el responsable y sincroniza las alertas. Admite productos sin una fila previa de inventario.
- Los valores nuevos de stock y mínimo son no negativos. Un stock negativo ya existente puede conservarse al modificar solo el mínimo, sin forzar un ajuste de cantidad ni registrar un movimiento ficticio.
- Si otro proceso modificó el saldo o el mínimo, responde 409 sin sobrescribir ninguno: el formulario carga los valores actuales y exige revisar y volver a guardar. Al editar desde alertas se oculta esa modal antes de abrir el editor; al cancelar se recupera el listado anterior, y al guardar se refrescan primero la tabla principal y después las alertas, conservando sus filtros y 10 registros por página, sin resetearlos como en una apertura nueva de Ver alertas. Nunca se apilan las dos modales.
- Debajo de la tabla principal, el pie muestra el paginador centrado y «Resultado: N registros» a la derecha mediante `ListResultCount`. Durante la carga inicial y cualquier recarga se ocultan el paginador y el contador hasta recibir la respuesta; el skeleton tampoco dibuja un pie ficticio.
- El filtro **Stock mínimo** ofrece **Todos los productos**, **Con mínimo configurado** (`mínimo > 0`) y **Sin mínimo configurado** (`mínimo ≤ 0`). Se aplica en la consulta antes de paginar y también al exportar; no cambia el cálculo de **Estado del stock**.
- La consulta actual comparte una única consulta plana con PDF y Excel, con existencias agregadas por producto y almacén y filas activas; los filtros y el total se calculan antes de paginar o exportar. No depende de snapshots de cantidades o mínimos históricos ni recupera las alertas durante la carga inicial de Control de stock. PDF y Excel respetan todos los filtros; sin filtros, incluyen todos los productos del almacén que requieren atención.
- **Descargar PDF** y **Descargar Excel** comparten `GET /stocks_management/alerts/export?warehouse_id=...&export=pdf|excel`. La consulta y las descargas aceptan `minimum_status=configured|unconfigured` o vacío para todos: configurado implica el mínimo actual mayor que cero; sin configurar implica menor o igual a cero. El servidor valida el valor y aplica la misma condición antes de paginar, contar o exportar. La descarga requiere permisos de vista/exportación y acceso al almacén. Se solicita como archivo sin navegar fuera de la modal, muestra el Swal de carga y notifica errores en la misma pantalla.
- Los reportes consultan filas planas con los códigos, cantidades y abreviatura necesarios, sin hidratar todos los modelos ni ejecutar sus accessors. El PDF dibuja las filas directamente sobre el lienzo, con cabecera repetida y páginas numeradas, sin construir una tabla HTML gigante; recorre el resultado con cursor. El Excel usa `FromQuery`. Ninguno carga el reporte durante la carga inicial de Control de stock.
- `inventory_guides` e `inventory_guide_items` respaldan guías confirmadas de entrada/salida; cada detalle genera su movimiento inmutable.
- Cantidades, saldos y movimientos usan los decimales configurados para la empresa. Los filtros de estado y mínimo comparan los valores redondeados que muestra la tabla. Excel y PDF usan la misma precisión.

## Evolución del módulo

La navegación funcional evita nombres duplicados:

- **Control de stock / Existencias / Reporte Existencias** comparten el saldo materializado de `warehouse_items`.
- **Kardex / Reporte Kardex / Movimientos** comparten `inventory_movements`, con filtros y distintas estrategias de presentación o exportación.
- **Traslados / Transferencia de almacenes** son una sola operación multiproducto.
- **Toma física** es una corrección con origen `physical_count`.
- **Devoluciones** usan `customer_return` y `supplier_return`; el ingreso manual usa `manual`.

## Actualizacion: ventas por almacen y caja

- Las ventas ahora guardan `sales_header.warehouse_id`, de modo que el descuento de stock ocurre en el almacen seleccionado por el usuario y no en el primero disponible.
- Si una sucursal tiene varios almacenes, el backend exige indicar el almacen afectado para evitar salidas ambiguas.
- Los movimientos por venta siguen registrandose en `inventory_movements` con origen `sale`, usando el almacen de la cabecera de venta.
- La caja se modela por separado con `cash_registers`, `cash_sessions`, `cash_session_payments` y `cash_movements`.
- Cuando una venta se asocia a `cash_session_id`, sus pagos generan movimientos de caja por metodo de pago. Esto prepara apertura, cierre, arqueo y resumen por metodo sin mezclar trazabilidad financiera con Kardex fisico.
## Alcance operativo

- Inventario carga almacenes desde `CompanyReferenceDataService::stockWarehouses()`, respetando perfil, colaborador y sucursales efectivas.
- El selector **Almacén de trabajo** muestra el almacén o la vista consolidada sobre la que se aplican consultas, exportaciones y operaciones.
- Un selector vacío en Colaboradores hereda el perfil; no elimina sus restricciones.
- `resource.scope` bloquea consultas, exportaciones, movimientos y traslados con almacenes no permitidos.
- Control de stock, Kardex, Traslados y Kardex valorizado conservan permisos por acción independientes aunque reutilicen controlador y componente Vue.
- Los productos con estado `inactive` no se ofrecen para movimientos, compras ni ventas; permanecen visibles en catálogo para edición y auditoría, pero no deben participar en operaciones nuevas.
