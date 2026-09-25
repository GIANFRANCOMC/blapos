# Configuración financiera: tributos y métodos de pago

## Objetivo

Permitir que cada empresa configure tributos, métodos de pago, variantes de pago y reglas de crédito para ventas y compras, sin dejar porcentajes, cargos o medios de pago fijos dentro de los módulos operativos.

## Alcance funcional

- Una venta aplica automáticamente todos los tributos activos configurados para `sale` o `both`.
- Una compra aplica automáticamente todos los tributos activos configurados para `purchase` o `both`.
- Los tributos de venta y compra se registran como filas independientes. Si un concepto aplica a ambos ámbitos, debe existir una fila `sale` y otra `purchase`.
- Los tributos iniciales de venta son `SALE-IGV` / `IGV` al 18%, cálculo porcentual, operación de suma y obligatorio; y `SALE-ICBP` / `ICBP` con monto fijo de 0.50, operación de suma y opcional.
- Los tributos iniciales de compra son `PURCHASE-IGV` / `IGV` al 18%, cálculo porcentual, operación de suma y obligatorio; y `PURCHASE-ICBP` / `ICBP` con monto fijo de 0.50, operación de suma y opcional.
- En ventas y POS, un producto, servicio o membresía con `price_includes_tax = true` ya contiene IGV en su precio y no recibe IGV adicional.
- En ventas y POS, un producto, servicio o membresía con `price_includes_tax = false` forma parte de la base gravable y recibe todos los tributos activos del alcance correspondiente.
- En ventas, POS y cotizaciones, un producto, servicio o membresía con `igv_exempt = true` queda fuera del cálculo de `IGV`: no aporta base gravada ni importe de IGV. Este campo solo exonera IGV; otros tributos configurados, como cargos fijos opcionales, mantienen su propia regla.
- Cuando `igv_exempt = true`, el backend fuerza `price_includes_tax = false` para evitar estados contradictorios. En la UI, `Incluye IGV` queda desmarcado y deshabilitado.
- `payment_methods` representa el método general, por ejemplo `Billetera digital`.
- `payment_method_variants` representa la opción específica, por ejemplo `Yape`, `Plin`, `Agora PAY`, `Bim` o `IzipayYA`.
- `YAPE`, `PLIN` y cualquier billetera concreta no deben registrarse como filas principales de `payment_methods`; siempre pertenecen a `payment_method_variants` bajo `DIGITAL_WALLET`.
- Una venta puede registrar uno o más métodos de pago configurados para `sale` o `both`.
- Una compra puede registrar uno o más métodos de pago configurados para `purchase` o `both`.
- En modalidad `paid_now`, la suma de pagos debe coincidir con el total del documento.
- En modalidad `cash_on_delivery` o `installments`, el backend permite pago inicial cero o parcial y registra cuenta por cobrar o por pagar.

## Tablas

### taxes

Catálogo configurable de tributos por empresa.

Campos principales:

- `code`: código interno legible del tributo.
- `name`: nombre mostrado al usuario. En la interfaz se debe mostrar este nombre, por ejemplo `IGV`, no una etiqueta genérica como `Impuestos`.
- `description`: explica el ámbito del tributo, a quién aplica, de dónde proviene y cómo debe interpretarse.
- `rate`: valor usado para calcular el tributo. Si `calculation_type = percentage`, representa porcentaje; si `calculation_type = fixed`, representa monto fijo.
- `calculation_type`: define si el valor se calcula como `percentage` o como `fixed`.
- `operation_type`: define si el resultado suma (`addition`) o resta (`subtraction`) al total del documento.
- `min_apply_quantity` / `max_apply_quantity`: límites de cantidad para tributos fijos no porcentuales, por ejemplo ICBP. En porcentajes normalmente quedan nulos.
- `scope`: define si aplica a `sale`, `purchase` o `both`.
- `is_required`: define si el tributo se aplica siempre en su alcance. Si es `false`, el usuario puede marcarlo o no desde el frontend del documento.
- `is_default`: ordena primero el tributo preferente en configuraciones, reportes y consumidores administrativos. No limita el cálculo.
- `status`: controla si el tributo está disponible.

### payment_methods

Catálogo configurable de métodos de pago generales por empresa.

Campos principales:

- `code`: código interno del método.
- `name`: nombre mostrado al usuario.
- `category`: naturaleza operativa del método: `cash`, `bank`, `card`, `digital_wallet`, `credit` u `other`.
- `sunat_code`: código de referencia SUNAT cuando el método lo tenga.
- `description`: explica cuándo usar el método y su impacto operativo.
- `image_path`: ruta generada por backend para la imagen del método; el API recibe `image`, valida JPG/PNG/WebP y almacena el archivo en el directorio público del tenant.
- `scope`: define si aplica a `sale`, `purchase` o `both`.
- `requires_reference`: obliga a registrar referencia cuando corresponda.
- `supports_variants`: indica si el método tiene opciones específicas asociadas.
- `allows_partial_payment`: indica si puede usarse como parte de un pago mixto o parcial.
- `is_default`: método sugerido por defecto.
- `status`: controla si el método está disponible.

### payment_method_variants

Catálogo configurable de variantes por empresa y método de pago.

Ejemplos base:

- `DIGITAL_WALLET` -> `Yape`, `Plin`, `Agora PAY`, `Bim`, `IzipayYA`.
- `DEBIT_CARD` -> `Visa débito`, `Mastercard débito`.
- `CREDIT_CARD` -> `Visa crédito`, `Mastercard crédito`, `American Express`, `Diners Club`.

Campos principales:

- `payment_method_id`: método general al que pertenece.
- `code`: código interno de la variante.
- `name`: nombre mostrado al usuario.
- `sunat_code`: código de referencia SUNAT si existiera para la variante.
- `image_path`: imagen o logo visible en caja/reportes.
- `description`: explica cuándo usar la variante.
- `requires_reference`: permite exigir número de operación aunque el método general no lo exija.
- `is_default`: orden sugerido dentro del método.
- `status`: controla si la variante está disponible.

### sale_taxes / purchase_taxes

Guardan la foto del tributo aplicado al documento.

Se conserva `name`, `description`, `rate`, `calculation_type`, `operation_type`, `base_amount`, `quantity` y `amount` para mantener trazabilidad aunque luego cambie la configuración del tributo.

### sale_payments / purchase_payments

Guardan la foto de los pagos aplicados al documento.

Se conserva `payment_method_id`, `payment_method_variant_id`, `name`, `amount`, `reference` y `note` para mantener trazabilidad aunque luego cambie la configuración del método o de la variante.

### sale_accounts_receivable / purchase_accounts_payable

Registran saldos pendientes cuando una venta o compra no queda pagada al momento.

- `payment_modality`: `cash_on_delivery` o `installments`.
- `original_amount`: total antes del recargo por cuotas.
- `extra_percentage` y `extra_amount`: recargo aplicado cuando la modalidad es por cuotas.
- `total_amount`, `paid_amount`, `pending_amount`: control financiero del documento.
- `status`: `pending`, `partial`, `paid`, `overdue` o `canceled`.

### sale_receivable_installments / purchase_payable_installments

Dividen el saldo pendiente en cuotas, con número, fecha de vencimiento, monto, pagado, pendiente y estado.

### sale_receivable_payments / purchase_payable_payments

Guardan abonos posteriores contra una cuenta por cobrar o por pagar. Conservan método general, variante, referencia, importe y responsable.

## Reglas de negocio

- Los tributos obligatorios no se seleccionan desde el documento: siempre se aplican.
- Los tributos opcionales se muestran en el frontend como `Impuestos extras` y solo se aplican cuando el usuario los marca.
- Los tributos obligatorios no aparecen en `Impuestos extras`; se calculan de forma silenciosa y se muestran por nombre en el resumen del documento.
- Los tributos porcentuales se calculan sobre la base imponible.
- Los tributos fijos aplican su valor como monto del documento. No dependen de una base porcentual.
- Los tributos fijos opcionales permiten indicar cantidad de aplicación como número entero. Ejemplo: `ICBP` con valor 0.50 y cantidad 2 suma 1.00 al total.
- `operation_type = addition` suma al total.
- `operation_type = subtraction` descuenta del total y guarda el monto con signo negativo.
- Los tributos múltiples se calculan de forma independiente sobre la misma base. No hay cálculo en cascada por ahora.
- En ventas y POS, si el item incluye IGV, el IGV se calcula como contenido dentro del precio y no incrementa el total. Si el item no incluye IGV, el IGV incrementa el total. Si el item está exonerado de IGV, el IGV no se calcula para ese detalle.
- En la UI de venta, el precio configurado con IGV incluido se descompone visualmente: `Precio unitario` muestra la base sin IGV, la columna `IGV` muestra el importe contenido y `Total` conserva el precio final. Esta separación es visual y de edición guiada; la persistencia mantiene la foto tributaria del detalle.
- Los métodos de pago seleccionados deben pertenecer a la empresa y estar activos.
- Si se envía `payment_method_variant_id`, la variante debe pertenecer al método general indicado y a la misma empresa.
- Cada método de pago debe indicar su importe.
- Si un método requiere referencia, el backend debe rechazar el documento si no se envía.
- Si la variante requiere referencia, el backend debe rechazar el documento aunque el método general no la requiera.
- Si `payment_modality = paid_now`, los pagos manuales deben coincidir con el total del documento.
- Si `payment_modality = cash_on_delivery`, los pagos pueden ser cero o parciales y el saldo genera cuenta por cobrar o por pagar.
- Si `payment_modality = installments`, el backend aplica `company_settings.sales.installment_extra_percentage` o `company_settings.purchases.installment_extra_percentage` antes de validar pagos y generar cuotas.
- Si no se envían pagos y existe un método por defecto, el backend registra un pago automático por el total solo en modalidad `paid_now`.

## Servicios

### CompanyReferenceDataService

Expone catálogos filtrados por empresa y alcance:

- `taxesFor("sale")`
- `taxesFor("purchase")`
- `paymentMethodsFor("sale")`, incluyendo `variants`.
- `paymentMethodsFor("purchase")`, incluyendo `variants`.

### CommercialDocumentSettlementService

Centraliza el cálculo y validación de:

- tributos del documento.
- pagos del documento.
- variante de método de pago.
- cuadratura exacta o parcial según modalidad.

### CommercialCreditAccountService

Centraliza:

- normalización de modalidad de pago.
- cálculo de estado `unpaid`, `partial`, `paid` u `overpaid`.
- creación de cuentas por cobrar y por pagar.
- generación de cuotas.
- trazabilidad de pagos iniciales asociados a esas cuentas.

## Configuración por empresa

- `company_settings.sales.default_payment_modality`: modalidad sugerida para ventas. Valor por defecto: `paid_now`.
- `company_settings.sales.installment_extra_percentage`: porcentaje adicional aplicado al total de venta cuando la modalidad es por cuotas.
- `company_settings.purchases.default_payment_modality`: modalidad sugerida para compras. Valor por defecto: `paid_now`.
- `company_settings.purchases.installment_extra_percentage`: porcentaje adicional aplicado al total de compra cuando la modalidad es por cuotas.

## Fuentes de referencia

- SUNAT y gob.pe documentan medios de pago bancarizados como depósitos en cuenta, giros, transferencias, órdenes de pago, tarjetas, cheques, remesas y cartas de crédito.
- gob.pe identifica billeteras digitales de uso en Perú como Yape, Plin, Tunki, Agora PAY y Bim. Para la plataforma, Tunki se registra operativamente como `IzipayYA` cuando aplique por su evolución comercial.

## Módulos impactados

- Ventas: subtotal, tributos por nombre, total, pagos, modalidad, saldo y cuenta por cobrar.
- Venta POS: usa el mismo contrato de venta, por lo que hereda modalidad y variantes.
- Compras: subtotal, tributos por nombre, total, pagos, modalidad, saldo y cuenta por pagar.
- Caja: los pagos iniciales siguen alimentando movimientos de caja cuando existe sesión abierta.
- Configuración: el backend expone `taxes`, `payment-methods`, `payment-method-variants` y `company-settings` mediante `/master-data/{resource}` con validación, auditoría e invalidación de caché.

## Evolución

- Crear pantallas dedicadas para cuentas por cobrar y cuentas por pagar con calendario, abonos, vencimientos y exportación.
- Permitir reglas avanzadas de cuota: frecuencia semanal/quincenal/mensual, fecha fija de pago y mora.
- Si se requiere cálculo en cascada de tributos, definir primero una decisión contable explícita antes de cambiar este contrato.
