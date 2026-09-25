# 04 - Ventas / Nuevo

## Que hace

Permite crear una venta con productos, servicios o membresias. Una venta puede generar efectos secundarios: descuento de stock y creacion de membresias reales.

## Archivos

- Ruta: `routes/System/Sales/Sale.php`
- Controlador: `SaleController`
- Request: `StoreSaleRequest`
- Servicio: `SaleService`
- Vista: `resources/views/System/general/Sales/sales/main.blade.php`
- Vue: `resources/js/System/Pages/Sales/sales/main.vue`
- Tablas: `sales_header`, `sales_body`, `items`, `warehouse_items`, `warehouses`, `subscriptions`

## Reglas

- Debe existir almacen para la sucursal.
- El correlativo se genera por serie.
- El total se calcula desde detalles.
- Si el detalle es `product`, se descuenta stock.
- Si el detalle es `subscription`, se crea membresia real para el cliente.
- Todo se ejecuta dentro de transaccion.
- `SaleConfigService` obtiene sucursales, clientes e ítems mediante `CompanyReferenceDataService`.
- La creación de una venta no elimina la caché de configuración: los registros operativos se consultan por endpoints separados.

## Campos necesarios

- `branch_id`
- `serie_id`
- `holder_id`
- `currency_id`
- `issue_date`
- `delivery_mode` opcional: `immediate` o `pending`.
- `delivery_status` opcional: `delivered`, `pending` o `partial`.
- `delivery_observation` opcional.
- `details[]`
- Por detalle: `item_id`, `currency_id`, `name`, `quantity`, `price`, `type`, `extras`
- Por detalle, opcional: `commission_type`, `commission_value`. Si no se envian, el backend toma la configuracion vigente del item.
- Por detalle de tipo `subscription`, opcional: `customer_id` para indicar el cliente beneficiario de la membresía. Debe pertenecer a la empresa y estar activo.

## Estado de mejoras

- La venta normal y Venta POS/Caja consultan `company_settings.inventory.allow_negative_stock_on_sale`; por defecto no permiten confirmar si algún producto supera el stock disponible del almacén seleccionado.
- La anulación aplica `company_settings.inventory.restore_stock_on_sale_cancellation`; por defecto no repone productos automáticamente. Si se requiere devolver mercadería, debe registrarse una devolución o reposición desde Inventario.
- `serie_id` debe pertenecer a la sucursal seleccionada y estar activo; si no coincide, la venta se rechaza antes de generar correlativo.
- El correlativo está protegido contra concurrencia mediante `lockForUpdate()` y la unicidad `serie_id + sequential`.
- Cada emisión y anulación queda registrada en `series_correlative_movements`; una anulación nunca libera el correlativo.
- Venta y POS bloquean la acción cuando la sucursal no tiene serie o almacén activo y muestran la configuración que debe corregirse.
- `details.*.extras` valida duración, fechas, observación, opciones de receta y toppings con una estructura explícita; los identificadores no se aceptan como datos libres.
- `StoreSaleRequest` normaliza antes de validar cantidades, precios, totales, tributos y pagos. Los máximos usan el mismo criterio transversal de backend y frontend (`999999999999.999`) para evitar diferencias entre Vue y PHP.
- El envío de correo de venta usa `HelperController::sendEmail`, valida correo y mensaje, construye el asunto con correlativo, sucursal y empresa cuando existe `sale_header.id`, y reutiliza la plantilla `emails.saleMail`.
- La plantilla de correo saluda al cliente cuando está disponible, muestra la sucursal de la venta y cierra con un footer de la empresa usando BLAPOS como referencia de plataforma.
- Los enlaces para imprimir o compartir comprobantes de venta usan una URL firmada temporal generada por `reports/sale/share-link`. Si el enlace vence o se altera, Laravel lo rechaza por firma antes de renderizar el PDF.
- `company_settings.reports.sale_share_ttl_minutes` define por empresa la vigencia de esos enlaces; el valor por defecto es 4320 minutos.
- Las consultas externas de DNI/RUC se registran en `external_api_request_logs`; el backend devuelve el consumo mensual y una advertencia al superar `company_settings.external_api.document_lookup_monthly_warning_threshold`.
- El sistema de puntos se ejecuta después de crear correctamente la venta. `company_settings.loyalty.enabled` activa la acumulación y las reglas vigentes definen si se otorgan puntos por monto total, cantidad de ítems, membresías o ítems seleccionados.
- Si se anula una venta, `company_settings.loyalty.reverse_points_on_sale_cancellation` determina si los puntos ganados por esa venta se reversan con un movimiento negativo.

## Actualizacion: impuestos y pagos configurables

- La venta ahora aplica automaticamente todos los impuestos activos desde `taxes`, filtrados por alcance `sale` o `both`.
- La venta ahora puede recibir multiples metodos de pago desde `payment_methods`, filtrados por alcance `sale` o `both`, indicando el monto pagado por cada metodo.
- El backend recalcula subtotal, impuestos, total y pagos para mantener la consistencia del documento.
- Los impuestos aplicados se guardan como foto del documento en `sale_taxes`.
- Los pagos aplicados se guardan como foto del documento en `sale_payments`.
- Cada pago conserva `payment_method_id`, nombre historico, monto, referencia y nota. Esto mantiene trazabilidad aunque despues se cambie o inactive el metodo de pago.
- La vista `resources/js/System/Pages/Sales/sales/main.vue` muestra un bloque lateral de liquidacion con impuestos aplicados automaticamente, metodos de pago, subtotal, impuestos, total, pagado y diferencia.
- Si solo hay un metodo de pago, el importe se sincroniza con el total para facilitar el registro.

## Actualizacion: comisiones de venta

- Toda venta puede guardar comision por detalle, sin importar si el item es producto, servicio o membresia.
- `items.commission_type` define la regla comercial por defecto: `none`, `percentage` o `fixed`.
- `items.commission_value` guarda el valor de la regla. En `percentage` representa porcentaje sobre el total de linea; en `fixed` representa monto fijo por unidad vendida.
- `items.commission_rate` se conserva por compatibilidad con servicios existentes; si existe un porcentaje antiguo y no se envia la regla nueva, `SaleService` lo interpreta como `percentage`.
- `sales_body.commission_type`, `sales_body.commission_value` y `sales_body.commission_amount` guardan la foto de la comision aplicada a cada detalle.
- `sales_header.commission_total` guarda la suma de comisiones de toda la venta para reportes, liquidaciones y comisiones por colaborador.
- La comision es informacion interna de la empresa: no modifica subtotal, impuestos ni total cobrado al cliente.
- La venta normal permite ajustar la comision por detalle antes de agregarlo. Venta POS toma la regla configurada en el item para mantener rapidez operativa.

## Actualizacion: IGV incluido, almacen de venta y caja

- `items.price_includes_tax` define si el precio de venta del producto, servicio o membresia ya contiene IGV.
- `items.igv_exempt` define si el producto, servicio o membresia está exonerado de IGV. Este valor tiene prioridad sobre `price_includes_tax`.
- La venta envia `details.*.price_includes_tax` y `details.*.igv_exempt`, y guarda la foto de ambos valores en `sales_body`.
- Si el precio incluye IGV, los impuestos configurados para venta no incrementan el total de ese detalle.
- Si el precio no incluye IGV, todos los impuestos activos de alcance `sale` o `both` se calculan sobre ese detalle y aumentan el total.
- Si el item está exonerado de IGV, el IGV no se calcula para ese detalle y el precio completo queda como precio del item. Esta exoneración solo aplica al IGV, no a tributos fijos opcionales como ICBP.
- El detalle de venta muestra una columna `IGV` por item: importe calculado cuando aplica, `S/ 0.000` cuando `igv_exempt = true` y `No aplica` cuando no existe un IGV activo o no corresponde cálculo.
- Cuando el item incluye IGV, la UI muestra `Precio de venta` como precio final unitario, extrae el `Subtotal` sin IGV con la tasa configurada en `taxes` y muestra el `IGV` contenido.
- Cuando el item no incluye IGV, `Precio de venta` se interpreta como base sin IGV, el `IGV` configurado se suma al detalle y `Total` muestra el importe final de la línea.
- Los tributos iniciales de venta para Peru son `IGV` obligatorio e `ICBP` fijo opcional, pero la tasa, monto y obligatoriedad salen de `taxes` por empresa; ambos se muestran por su nombre en el frontend y el resumen evita usar una fila generica llamada `Impuestos`.
- `taxes.calculation_type` permite porcentaje o monto fijo, y `taxes.operation_type` permite sumar o restar el monto calculado.
- `taxes.is_required` define si el tributo es obligatorio. El IGV de venta es obligatorio; el ICBP de venta es opcional y el usuario lo marca solo cuando corresponde.
- Si el precio incluye IGV, el resumen muestra el IGV contenido sin aumentar el total. Si el precio no incluye IGV, el IGV se suma al total.
- Los tributos fijos de venta, como ICBP, son cargos de documento: no dependen de la base porcentual y se calculan al estar obligatorios o seleccionados.
- Los tributos fijos opcionales de venta permiten indicar cantidad entera. Ejemplo: si la venta usa 2 bolsas, el usuario marca `ICBP` y coloca cantidad 2. Al quitar el check, el campo se oculta y no se envia el tributo.
- La venta normal y Venta POS respetan `taxes.min_apply_quantity` y `taxes.max_apply_quantity` para tributos fijos opcionales. El frontend normaliza la cantidad con `InputNumber` y el backend recalcula el importe final para evitar diferencias.
- `sales_header.warehouse_id` guarda el almacen afectado por la venta.
- El frontend muestra el selector de almacen junto a Sucursal y Tipo de comprobante. Si la sucursal solo tiene un almacen, lo selecciona automaticamente.
- Nueva venta muestra `br-operational-scope` con sucursal, almacen y serie activos para que el usuario identifique el alcance antes de confirmar.
- `SaleService::resolveWarehouse()` valida que el almacen pertenezca a la sucursal y a la empresa autenticada. Si hay varios almacenes y no se envia uno, el backend rechaza la venta con un mensaje accionable.
- `sales_header.cash_session_id` permite vincular la venta con una caja abierta cuando el modulo de caja este activo.
- Si la venta incluye `cash_session_id`, cada pago genera un registro en `cash_movements`, manteniendo trazabilidad por metodo de pago para apertura, cierre, arqueo y resumen de caja.
- Los métodos de pago iniciales incluyen `Efectivo`, depósitos, giros, transferencias, tarjetas, cheque, remesa, carta de crédito y `Billetera digital`; todos son configurables por empresa y por alcance (`sale`, `purchase`, `both`).
- Las billeteras específicas, como `Yape`, `Plin`, `Agora PAY`, `Bim` o `IzipayYA`, se registran como variantes de `Billetera digital` en `payment_method_variants`.
- La venta soporta `payment_modality`: `paid_now` exige pago completo, `cash_on_delivery` permite saldo pendiente y `installments` aplica el recargo configurado en `company_settings.sales.installment_extra_percentage`.
- Cuando una venta queda con saldo por modalidad `cash_on_delivery` o `installments`, el backend crea `sale_accounts_receivable`, sus cuotas en `sale_receivable_installments` y la trazabilidad de pagos iniciales en `sale_receivable_payments`.

## Visualización del detalle

- La tabla de detalle usa columnas rectas y compactas: `#`, `Ítem`, `Cantidad`, `Precio de venta`, `Subtotal`, `IGV`, `Total` y acciones. La fila final muestra `Totales` y suma `Subtotal`, `IGV` y `Total` bajo sus columnas respectivas para que el usuario valide los importes visibles sin depender del resumen lateral. No muestra unidad porque el catálogo comercial actual no expone una unidad estándar para todos los tipos vendibles.
- La cantidad se edita directamente con `InputNumber`; no se muestran botones de sumar o restar unidades para evitar duplicar acciones y mantener una lectura limpia de la línea.
- `Precio de venta` representa el precio unitario configurado en el catálogo o digitado por el vendedor. En la misma celda se muestra cómo debe interpretarse el impuesto con etiquetas compactas: `Inc. IGV` cuando el IGV ya está contenido, `Más IGV` cuando el IGV se agrega al precio y `Exo. IGV` para ítems exonerados o inafectos. Cada etiqueta mantiene tooltip con el texto completo.
- `Subtotal` representa la base contable total del item antes de impuestos.
- `IGV` representa el impuesto calculado para toda la cantidad. Sale del impuesto activo configurado en `taxes` para ventas; la tasa inicial peruana es solo dato semilla y no una constante fija en frontend ni backend.
- `Total` es el importe final que pagará el cliente por el item.
- Cuando `Incluye IGV = Sí`: `Total = Cantidad x Precio de venta`, `Subtotal = Total / (1 + tasa IGV configurable)` e `IGV = Total - Subtotal`. La UI usa los decimales configurados por empresa y redondea los importes visibles por línea para evitar desfases como `S/ 200.001`.
- Cuando `Incluye IGV = No`: `Subtotal = Cantidad x Precio de venta`, `IGV = Subtotal redondeado x tasa IGV configurable`, `Total = Subtotal + IGV`.
- Cuando `Incluye IGV = No aplica`: `Subtotal = Cantidad x Precio de venta`, `IGV = S/ 0.000`, `Total = Subtotal`.
- El importe total de la venta suma los totales de línea ya redondeados a la precisión configurada por empresa. Esta regla hace que el total siempre coincida con los importes visibles en la tabla.
- El panel lateral agrupa la información en bloques: `Información` para observaciones, cotización y vendedor; `Entrega` para tipo de entrega y almacén; y `Pago` para impuestos extras y métodos de pago.
- El resumen lateral queda enfocado en el cobro: `Total a pagar`, `Pagado` y el estado final solo cuando existe detalle. Si el pago cuadra muestra `Pago completo`; si falta dinero muestra `Pendiente` con el importe; si se excede muestra `Pago excedido`. El desglose de subtotal e IGV vive en la tabla de detalle.
- Ejemplo con IGV configurado en 18% y 3 decimales, gravado con IGV incluido: cantidad `1`, precio de venta `S/ 100.000`, incluye IGV `Sí`, subtotal `S/ 84.746`, IGV `S/ 15.254`, total `S/ 100.000`.
- Ejemplo con IGV configurado en 18% y 3 decimales, gravado con IGV incluido y cantidad `2`: precio de venta `S/ 100.000`, subtotal `S/ 169.492`, IGV `S/ 30.508`, total `S/ 200.000`.
- Ejemplo con IGV configurado en 18% y 3 decimales, gravado sin IGV incluido: cantidad `1`, precio de venta `S/ 100.000`, incluye IGV `No`, subtotal `S/ 100.000`, IGV `S/ 18.000`, total `S/ 118.000`.
- Ejemplo exonerado: cantidad `1`, precio de venta `S/ 100.000`, incluye IGV `No aplica`, subtotal `S/ 100.000`, IGV `S/ 0.000`, total `S/ 100.000`.
- La modal de agregar o rectificar item se mantiene operativa y simple: catálogo comercial, cantidad y precio de venta. El desglose tributario completo vive en la tabla de detalle para que el usuario vea cómo se llega al total en cada línea.
- La fila de membresía muestra la vigencia como una línea `Inicio -> Fin`, usando `extras.start_date` y `extras.end_date`, para que el usuario lea el periodo como una continuidad y no como dos campos sueltos.
- El importe total conserva una franja de cierre alineada con el encabezado de tabla para reforzar el monto final sin competir con el resumen lateral.

## Actualización: seguimiento de entrega

- `sales_header.delivery_mode` indica si la venta nace con entrega inmediata o queda pendiente.
- `sales_header.delivery_status` permite consultar si la venta está `delivered`, `pending`, `partial` o `canceled`.
- En venta normal y POS, si no se envía seguimiento, el backend asume `delivery_mode = immediate` y `delivery_status = delivered`.
- Cuando el estado es `delivered`, se guarda `delivered_at` y `delivered_by`.
- Si `delivery_mode = immediate`, la venta descuenta stock al confirmarse, como una entrega ya realizada.
- Si `delivery_mode = pending`, el backend crea `sale_deliveries` y `sale_delivery_items`, no descuenta stock al confirmar la venta y deja el despacho para `Ventas > Entregas pendientes`.
- Cada entrega registrada crea `sale_delivery_events`, `sale_delivery_event_items` y movimientos de inventario con origen `sale_delivery`. La venta puede quedar `partial` hasta completar todas las cantidades.
- Al anular una venta, las entregas pendientes se cancelan. Si la empresa tiene activa la reposición automática por anulación, también se consideran los movimientos originados por entregas parciales ya realizadas.
# Venta POS

- `sales.pos` se muestra bajo la cabecera `Ventas`, antes de `Nuevo` y `Listado`, porque genera una venta real y comparte validaciones, caja, pagos, tributos e inventario con el flujo principal.
- `sales.pos` usa una vista propia tipo mostrador: categorias superiores, buscador de productos, cards de productos y ticket lateral.
- Las categorias son botones compactos sin iconos, alternando tres colores solidos: naranja operativo, gris secundario y verde de accion positiva. El contador flota sobre la esquina superior derecha y la categoria seleccionada muestra un check con fondo visible para reforzar la seleccion.
- Las cards de productos, servicios y membresias no agregan al tocar el contenido; solo agregan mediante el boton `+`.
- Cada card diferencia marca, codigo interno y codigo de barras en lineas separadas para mejorar lectura en pantallas reducidas.
- Cada card muestra una accion de detalle para abrir una modal con nombre, tipo, descripcion, marca, categorias, codigos, precio y configuracion de IGV cuando el espacio del card no alcance.
- Si no existen cajas abiertas, POS muestra una alerta roja dentro del panel de detalle y oculta cliente, pagos, limpiar venta y generar venta.
- Si existe una sola caja abierta, POS muestra caja y sucursal como texto de contexto; si existen varias, permite seleccionar la caja.
- El buscador se ubica debajo de categorias para mantener primero el contexto visual de navegacion.
- El panel derecho trabaja como ticket fijo: caja/sucursal arriba, productos agregados al centro y el bloque de subtotal, impuestos, total y revision de venta anclado al final para mantener siempre visible el cierre de la operacion.
- En escritorio, el POS divide la vista en dos columnas: catalogo a la izquierda con scroll propio y ticket a la derecha fijo. El scroll del ticket solo afecta el detalle de productos agregados, no el resumen ni el boton de revision.
- La caja activa se muestra como contexto superior del ticket con fondo anaranjado para que el cajero identifique rapidamente caja y sucursal.
- POS muestra además `br-operational-scope` con caja, sucursal y almacen activos, usando el mismo patrón visual transversal de Inventario y Caja.
- La pantalla POS reutiliza `sales.store` para generar la venta, por lo que conserva validaciones y trazabilidad del modulo de ventas.
- El ticket lateral prioriza trazabilidad de caja, detalle de productos y cierre de venta, manteniendo el boton de revision junto al resumen final.
- La caja abierta es el selector principal del POS y muestra a que sucursal pertenece. La sucursal se deriva desde la caja.
- El almacen solo se muestra cuando la sucursal tiene mas de un almacen disponible; si solo existe uno, se selecciona automaticamente.
- Los metodos de pago son multiples. Por defecto se agrega efectivo por el total de la venta; el editor vive en la modal de confirmacion y se muestra con `Cambiar metodo de pago`.
- Cuando el editor de pagos esta abierto se muestran solo inputs editables; cuando se oculta, se muestra solo el resumen por metodo de pago.
- El selector de metodo de pago es buscable para agilizar caja cuando hay varios metodos configurados.
- La venta se confirma en dos pasos: el boton `Revisar venta` abre una modal con subtotal, impuestos, total y metodos de pago editables; luego `Confirmar venta` registra la venta.
- La modal de confirmacion muestra el comprobante de pago disponible para la sucursal. Si existe mas de una serie, el usuario debe seleccionar el comprobante que corresponde, por ejemplo boleta o factura.
- POS no crea una venta simplificada ni paralela: reutiliza `sales.store`, por lo que registra `sales_header`, `sales_body`, impuestos, metodos de pago, movimientos de caja e inventario igual que una venta normal.
- POS permite agregar un cliente desde el campo Cliente reutilizando `AddCustomer`; al crear el cliente se agrega a la lista y queda disponible para seleccion.
- La vista POS registra localmente sus componentes (`Breadcrumb`, `Loader`, `WithoutData`, `InputNumber`, `AddCustomer`) para evitar dependencias implícitas globales al reutilizar este patrón en nuevos módulos.
- El boton `Generar venta` se oculta hasta que la caja, cliente, pagos y detalle esten completos, evitando una accion visualmente disponible cuando todavia falta informacion.
- Al abrir o cerrar una caja, `CashRegisterService` invalida la cache de `CashRegisterConfigService` y `SaleConfigService` para que POS muestre solo cajas abiertas vigentes al volver a ingresar.
- Los impuestos configurados para ventas se aplican automaticamente sobre productos cuyo precio no incluye IGV (`price_includes_tax = false`) y no estén exonerados (`igv_exempt = false`).
- El POS calcula subtotal, base imponible, impuestos y total con la misma regla de crear venta: los precios con IGV incluido no suman impuesto adicional; los precios sin IGV incluido reciben IGV; los exonerados no generan IGV.
- El POS muestra cada tributo por nombre, por ejemplo `IGV`, y soporta tributos porcentuales, fijos, de suma o de resta usando la misma regla de backend.
- En venta normal y POS, el bloque `Impuestos extras` muestra solo tributos opcionales, como `ICBP`. Los obligatorios, como `IGV`, no se seleccionan; se calculan automáticamente y aparecen en el resumen.
- Si existe una sesion de caja abierta, POS puede asociar la venta a `cash_session_id` para alimentar movimientos de caja.
## Venta POS - pagos y confirmación

- La vista principal del POS se enfoca en seleccionar catálogo, caja y revisar el detalle de la venta.
- Los métodos de pago se revisan y editan únicamente en la modal **Confirmar venta**. Por defecto se muestra un resumen legible; si el pago será mixto, el usuario puede usar **Cambiar método de pago** y ajustar importes antes de confirmar.
- Los pagos mixtos usan el mismo contrato en venta normal y POS: cada fila conserva `payment_method_id`, nombre histórico, referencia opcional y monto aplicado; el total pagado debe cuadrar con el total del documento.
- El cliente también se selecciona dentro de **Confirmar venta**, junto al resumen y los pagos, para que la pantalla principal quede enfocada en armar el detalle.
- El botón **Revisar venta** queda visible cuando hay caja abierta y solo se bloquea si el detalle está vacío.
- El botón **Confirmar venta** dentro de la modal se mantiene visible y solo se bloquea si el detalle está vacío; las validaciones de cliente, caja y pagos se comunican al confirmar.
- El botón principal de la vista se mantiene como **Revisar venta** para reforzar que la venta se confirma en un segundo paso.
- Las cajas disponibles se limitan por las sucursales permitidas del colaborador. Si el usuario no tiene sucursales configuradas, mantiene acceso a todas las sucursales de la empresa.
- Los ítems con estado `inactive` no se cargan para Venta POS ni para el flujo transaccional de ventas.

## Integración con atenciones de servicio

- Venta POS admite `service_session_id` para cobrar una mesa o servicio previamente iniciado.
- Al abrir POS desde Restaurante POS o Servicios en curso, se precargan sucursal, cliente y detalles vigentes de la atención.
- La sesión debe pertenecer a la misma empresa, estar pendiente o en curso y ser accesible para el usuario por sucursal.
- La venta y el cierre de la sesión ocurren dentro de la misma transacción. Si falla comprobante, pago, stock, impuesto o caja, la atención permanece abierta.
- Una sesión cobrada guarda `sale_header_id`, fecha de fin, duración total y usuario que realizó el cierre.
- Los detalles conservan su tiempo individual y se consolidan al cerrar para evitar cronómetros abiertos.
