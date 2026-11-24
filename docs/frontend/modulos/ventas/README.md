# Ventas en Figma

La carpeta [Blapos → Ventas](https://www.figma.com/files/folder/697422898) contiene un archivo editable por submódulo. Los archivos usan instancias de la [biblioteca Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=19-2). Las composiciones de venta viven en sus archivos.

| Submódulo | Escritorio | Móvil |
| --- | --- | --- |
| Nueva venta | [Venta con ítems](https://www.figma.com/design/xCLogv2psYFzfohvnfz6sZ/Nueva-venta?node-id=2-532) · [Registro desde cero](https://www.figma.com/design/xCLogv2psYFzfohvnfz6sZ/Nueva-venta?node-id=2-850) | [Nueva venta en 390 px](https://www.figma.com/design/xCLogv2psYFzfohvnfz6sZ/Nueva-venta?node-id=2-1097) |
| Listado de ventas | [Consulta y resultados](https://www.figma.com/design/LDR3nMDpy7xM9uT5apWUhU/Listado-de-ventas?node-id=1-511) · [Sin coincidencias](https://www.figma.com/design/LDR3nMDpy7xM9uT5apWUhU/Listado-de-ventas?node-id=1-1208) | [Listado en 390 px](https://www.figma.com/design/LDR3nMDpy7xM9uT5apWUhU/Listado-de-ventas?node-id=1-1644) |
| POS venta | [Mostrador y ticket](https://www.figma.com/design/9zuzlJYJtAd7bPR7Cn5X3T/POS-venta?node-id=2-544) · [Pago mixto](https://www.figma.com/design/9zuzlJYJtAd7bPR7Cn5X3T/POS-venta?node-id=2-2479) | [POS venta en 390 px](https://www.figma.com/design/9zuzlJYJtAd7bPR7Cn5X3T/POS-venta?node-id=2-2081) |
| POS restaurante | [Plano de mesas](https://www.figma.com/design/cLAHUHSqvmzXr7skH40lsw/POS-restaurante?node-id=1-468) · [Detalle de Mesa 02](https://www.figma.com/design/cLAHUHSqvmzXr7skH40lsw/POS-restaurante?node-id=2-1098) | [Plano en 390 px](https://www.figma.com/design/cLAHUHSqvmzXr7skH40lsw/POS-restaurante?node-id=1-1056) |
| Nueva cotización | [Propuesta con ítems](https://www.figma.com/design/jDlvKPAtaJPFewNCvlWdwm/Nueva-cotizacion?node-id=1-505) · [Desde cero](https://www.figma.com/design/jDlvKPAtaJPFewNCvlWdwm/Nueva-cotizacion?node-id=1-820) | [Nueva cotización en 390 px](https://www.figma.com/design/jDlvKPAtaJPFewNCvlWdwm/Nueva-cotizacion?node-id=1-2372) |
| Cotizaciones registradas | [Consulta y resultados](https://www.figma.com/design/3mSFcwnPRqliAC822LwjaI/Cotizaciones-registradas?node-id=1-528) · [Cotización aplicada a una venta](https://www.figma.com/design/3mSFcwnPRqliAC822LwjaI/Cotizaciones-registradas?node-id=1-3887) | [Cotizaciones en 390 px](https://www.figma.com/design/3mSFcwnPRqliAC822LwjaI/Cotizaciones-registradas?node-id=1-2853) |
| Entregas pendientes | [Listado](https://www.figma.com/design/ETx5JEI510m04nRkr72IIG/Entregas-pendientes?node-id=1-524) · [Registrar entrega](https://www.figma.com/design/ETx5JEI510m04nRkr72IIG/Entregas-pendientes?node-id=1-3541) | [Listado en 390 px](https://www.figma.com/design/ETx5JEI510m04nRkr72IIG/Entregas-pendientes?node-id=1-2862) |
| Cuentas por cobrar | [Indicadores y cuentas](https://www.figma.com/design/DL1H8CBnsyAUFbz1KszKx7/Cuentas-por-cobrar?node-id=1-1131) · [Cronograma y pagos aplicados](https://www.figma.com/design/DL1H8CBnsyAUFbz1KszKx7/Cuentas-por-cobrar?node-id=1-3359) | [Cuentas en 390 px](https://www.figma.com/design/DL1H8CBnsyAUFbz1KszKx7/Cuentas-por-cobrar?node-id=1-2949) |

En **Nueva venta**, los datos del comprobante y los ítems tienen prioridad. El resumen mantiene visibles el impuesto, el total y el pago. El prototipo permite abrir el selector de ítems, revisar la edición de información, entrega y pago en escritorio, y ver el resultado del guardado.

En **Listado de ventas**, los filtros van antes de los resultados. La paginación y el conteo quedan debajo de la tabla cuando termina la consulta. En móvil, los comprobantes se presentan como tarjetas y el filtro se abre en un diálogo. El detalle del comprobante de muestra y la anulación simulada de escritorio tienen sus propios recorridos.

En **POS venta**, el catálogo y el ticket se mantienen lado a lado en escritorio. En móvil, el catálogo tiene prioridad cuando el ticket está vacío. La confirmación reúne comprobante, cliente, desglose y pago. Hay ejemplos de ticket vacío, caja cerrada, efectivo y pago mixto.

En **POS restaurante**, el plano distingue disponibilidad y atención mediante texto y color. El detalle muestra la evolución del pedido, el responsable y la línea de tiempo. Desde Mesa 02, «Cobrar en POS» abre el ticket precargado por S/ 72.00. Al confirmar el cobro de muestra, «Volver al plano» abre el estado con la mesa disponible. Los productos y las mesas se componen con el Panel publicado y contenido editable de cada módulo. [Recorrido de Mesa 02](https://www.figma.com/proto/cLAHUHSqvmzXr7skH40lsw/POS-restaurante?node-id=1-468&hide-ui=1&scaling=scale-down).

En **Nueva cotización**, los datos de cliente y vigencia preceden a la edición de ítems. El resumen reúne impuestos extras, subtotal, IGV y total. El prototipo incluye el detalle vacío, la selección de ítems y el error de vigencia anterior a la emisión. El guardado de muestra abre el listado con el registro en estado Borrador. [Prototipo de guardado](https://www.figma.com/proto/jDlvKPAtaJPFewNCvlWdwm/Nueva-cotizacion?node-id=1-505&hide-ui=1&scaling=scale-down).

En **Cotizaciones registradas**, la consulta conserva los filtros por referencia o cliente y estado. El prototipo contempla carga, resultados, paginación, ausencia de coincidencias y confirmación de anulación. «Usar en venta» abre una vista de muestra de Nueva venta con cliente, ítems y referencia precargados.

En **Entregas pendientes**, el listado muestra el avance y las cantidades por producto. El registro reúne almacén de salida, fecha, cantidades de la entrega, observación e historial. El ejemplo parte de 8 unidades pendientes. Al registrar 4, quedan 4 por entregar. El segundo registro completa la entrega y la venta sale de pendientes. [Prototipo de entregas](https://www.figma.com/proto/ETx5JEI510m04nRkr72IIG/Entregas-pendientes?node-id=1-524&hide-ui=1&scaling=scale-down) · [Reglas de entrega](../../../backend/system/modules/05_sales_deliveries.md).

En **Cuentas por cobrar**, los indicadores distinguen total financiado, cobrado, pendiente y vencido. Desde la fila se consulta el cronograma, el recargo y los pagos aplicados. Los filtros se aplican con «Buscar». El caso de muestra tiene S/ 840.00 pendientes, de los cuales S/ 420.00 corresponden a la cuota vencida. [Prototipo de cobranza](https://www.figma.com/proto/DL1H8CBnsyAUFbz1KszKx7/Cuentas-por-cobrar?node-id=1-1131&hide-ui=1&scaling=scale-down) · [Contrato de crédito comercial](../../../backend/system/modules/finance_configurable_taxes_payments.md#sale_accounts_receivable--purchase_accounts_payable).

Se revisó en Figma la composición de escritorio a 1440 px, la vista móvil a 390 px, la visibilidad del pie de resultados y los enlaces principales. Los datos son de muestra. La ejecución real de búsquedas, pagos, guardado, impresión y anulación, así como el teclado y el foco en navegador, requieren validación en la aplicación. Consulta los contratos de [Nueva venta](../../../backend/system/modules/04_sales_create.md) y [Listado de ventas](../../../backend/system/modules/03_sales_list.md).

Para los POS se hizo una revisión visual a 1440 y 390 px y se verificaron los destinos del prototipo. En Presentar se probó la secuencia del pedido hasta «Entregado» y el salto a caja, además del pago mixto de muestra y el regreso al plano. La validación de caja, stock, pagos y persistencia corresponde a la aplicación. Consulta [POS venta](../../../backend/system/modules/04_sales_create.md#pos-venta) y [Restaurante POS](../../../backend/system/modules/74_restaurant_pos.md).

Para Cotizaciones se revisaron las composiciones a 1440 y 390 px y se verificaron los destinos de prototipo. En Presentar se probó el guardado de la cotización COT-8K2M4R7Q9T y su aplicación a una venta por S/ 62.70. Las interacciones usan datos de muestra y los permisos, recálculo y persistencia se validan en la aplicación. [Flujo de Nueva venta](../../../backend/system/modules/04_sales_create.md).

En Despacho y cobranza se revisaron las vistas a 1440 y 390 px. En Presentar se probó el registro parcial y completo, el filtro de cuentas vencidas y la apertura del cronograma. Los cambios de inventario, permisos, saldos y persistencia corresponden a pruebas de la aplicación.

## Implementación en la aplicación

- Las ocho pantallas comparten los componentes FG de cabecera, campos, acciones, tablas y estados. `resources/css/System/fg/sales.css` define la composición de los formularios, listados y puntos de venta a partir de los tokens compartidos.
- En espacios menores de 768 px, los listados presentan los mismos registros como tarjetas y los filtros se abren en un diálogo. Los campos conservan su borrador al cerrar el diálogo. Buscar aplica los filtros y Limpiar filtros restablece los campos y muestra la confirmación de Toastr.
- POS venta da prioridad al catálogo en móvil mientras el ticket está vacío. POS restaurante muestra las mesas como tarjetas y conserva «Ver plano» para consultar y editar la distribución en pantallas pequeñas.
- El resumen de una entrega calcula las cantidades con el formato numérico de la empresa y avisa si una línea supera su saldo pendiente. Cuentas por cobrar conserva el cronograma y los pagos aplicados desde la acción de cada fila.
- Se retiraron los estilos `br-` específicos de Ventas. Los estilos compartidos por pantallas de Compras y Cuentas por pagar se mantienen en sus hojas de origen.

Se verificaron las ocho pantallas en navegador con respuestas simuladas a 1440 y 390 px. Se comprobó el borrador de filtros al cambiar de ancho, la vigencia de cotización, el retorno del foco al cerrar un detalle de mesa o el alta rápida de cliente, el saldo de una entrega parcial y la consulta de cuotas.

## Validación autenticada en la demo

El 24 de noviembre de 2026 se recorrieron los ocho submódulos en `demo.blapos.test` con la sesión de Administrador. Las escrituras corresponden a la prueba `QA-VENTAS-20261124-01`.

| Submódulo | Resultado comprobado |
| --- | --- |
| Nueva cotización | Bloqueó la fecha de vigencia anterior a la emisión. Guardó `COT-AK05YZREAA` por S/ 88.00 con dos unidades. También guardó el borrador de prueba `COT-QL4NNCGCPX`. |
| Cotizaciones registradas | La cotización siguió visible después de recargar. «Usar en venta» precargó cliente, ítem, precio y referencia. El listado mostró la primera como Convertida y la segunda como Anulada después de confirmar su anulación y recargar. |
| Nueva venta | Registró `BV1-00000001` por S/ 88.00 desde la cotización, con entrega pendiente, crédito en dos cuotas y S/ 0.00 de inicial. |
| Entregas pendientes | Bloqueó una cantidad mayor a la pendiente. Registró una unidad y conservó el 50 % de avance y la observación después de recargar. Registró la segunda unidad, confirmó el cierre y retiró la venta de pendientes. |
| Cuentas por cobrar | La venta generó una cuenta por S/ 88.00. Se abrió el cronograma con dos cuotas de S/ 44.00. Este submódulo consulta saldos, cuotas y pagos aplicados. |
| POS venta | El catálogo cargó 25 de 20 102 ítems disponibles y pasó a 50 con «Cargar más productos». Se encontró el ítem por código y se guardó `BV1-00000002` por S/ 44.00 con S/ 20.00 en efectivo y S/ 24.00 registrados como Agora PAY. Se revalidó el bloqueo al quedar S/ 24.00 por completar. |
| POS restaurante | Creó el piso QA y la mesa de prueba. La atención `SRV-4CUPL6PAVP` avanzó de Por preparar a En preparación, Listo y Entregado. «Cobrar en POS» cargó el pedido y generó `BV1-00000003` por S/ 44.00. La atención quedó finalizada y la mesa volvió a Disponible. |
| Listado de ventas | Mostró los comprobantes guardados. Editar la secuencia conservó el listado hasta Buscar. Desde el detalle abrió los PDF firmados A4 y 80 mm de `BV1-00000003`. Anuló únicamente la venta mixta de prueba y verificó el estado Anulado. |

Se contrastaron en la base de la demo la venta vinculada a la cotización, los dos eventos de entrega con una salida de inventario por unidad, las cuotas, los pagos de POS, los movimientos de caja y el comprobante asociado a la atención. La caja secundaria de prueba `QA-VENTAS-2411-01` se preparó con los servicios de Caja y quedó cerrada con S/ 88.00 esperados y diferencia de S/ 0.00. POS mostró el aviso de caja cerrada y rechazó volver a cobrar la atención finalizada. También se verificó el catálogo de POS y el filtro por categoría a 390 px.

La anulación conservó los S/ 44.00 registrados en caja para la venta mixta. Queda por definir si y cómo la anulación debe registrar un reembolso.

La validación usó importes simulados y no incluyó envíos por WhatsApp o correo.
