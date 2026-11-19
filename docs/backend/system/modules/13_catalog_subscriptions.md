# 13 - Membresías de catálogo

## Qué hace

Administra ítems `subscription` que, al venderse, generan una membresía real para cliente y sucursal.

## Backend

- Duración y tipo de duración son obligatorios.
- `attendance_limit_per_day` define cuántas asistencias pueden registrarse por día. La duración del plan determina su vigencia.
- Al crear, importar o guardar un plan con el límite vacío, se usa el valor activo de `subscriptions.default_attendance_limit_per_day` en Maestros internos. Empieza en `1` y puede guardarse vacío como `null`. Un límite ingresado en el plan tiene prioridad.
- Al vender, se copia `items.attendance_limit_per_day` a `subscriptions.attendance_limit_per_day`, incluido `null`. Asistencias consulta la copia de la membresía del cliente. Un valor `null` bloquea el ingreso.
- `benefits` y `restrictions` son arreglos JSON validados, con hasta 50 textos; la captura de cada campo en el editor está limitada a 100 caracteres.
- `price_includes_tax` determina si el IGV ya está incluido.
- `igv_exempt` determina si la membresía está exonerada de IGV. Si está activo, ventas y POS no calculan IGV para ese detalle.
- `commission_type` y `commission_value` permiten configurar comision por membresia vendida; la venta guarda la foto en `sales_body` y suma el total interno en `sales_header.commission_total`.
- Código, moneda, categorías y pertenencia se validan por empresa.
- El editor muestra y envía el código completo. El prefijo vigente de `internal_code_prefixes.subscription` (`MEMB` por defecto) puede quitarse o restaurarse desde el campo. El FormRequest valida el código recibido y se guarda en `items.internal_code`. El listado y el botón de copiar muestran ese mismo valor.

## Interfaz

- El listado muestra el Código interno centrado en la primera columna con un botón para copiarlo. La columna **Detalle** muestra una etiqueta por línea y Duración presenta el plazo de la membresía. El límite diario de asistencias se consulta en la pestaña Configuración del formulario. La etiqueta de Comisión aparece cuando se configura un porcentaje o un monto mayor que cero.
- El editor de Duración agrupa el valor numérico y el selector de unidad a la derecha. Un solo borde y anillo de foco rodean el conjunto. La flecha del selector gira al abrir y cerrar.
- La modal compartida usa pestañas descriptivas `MEMBRESÍA`, `DETALLE`, `IMPUESTOS` y `CONFIGURACIÓN`, con navegación por teclado y desplazamiento en móvil. No muestra Inventario físico.
- Los errores locales y del backend se muestran en el campo y señalan la pestaña correspondiente.
- El formulario permite definir límite diario de asistencias, comisión y valor de comisión.
- El campo Valor de comisión comparte `CommissionValueInput` con Productos y Servicios. Muestra `%` o el símbolo de la moneda al inicio. Para porcentaje o monto fijo muestra un asterisco y exige un valor mayor que cero. El porcentaje admite hasta 100 %.
- `benefits` y `restrictions` se editan como textos separados por coma para mantener una captura rápida; el frontend los normaliza a arreglos antes de enviarlos al backend.
- Los beneficios describen lo que incluye el plan; las restricciones deben usarse para reglas comerciales claras como horarios, sedes o condiciones de uso.

## Acciones masivas

`Cargar masivamente` y `Descargar Excel` están agrupadas en `Acciones`. La descarga confirma la operación y aplica los filtros actuales a todos los registros, no solo a la página visible. Incluye duración, asistencias, cupos, beneficios, restricciones, impuestos, publicación, comisión y estado; no incluye stock físico.

`GET /subscriptions/import` entrega la plantilla básica. `POST /subscriptions/import` requiere nombre, precio, código interno completo, valor y tipo de duración (`hora`, `día`, `rutina`, `mes` o `año`). El código incluye el prefijo vigente cuando la empresa lo configura. La descripción es opcional. La moneda es la de la empresa y la membresía se crea activa con publicación desactivada. El editor permite completar beneficios, restricciones y cupos. Se aceptan hasta 500 filas. Un error revierte toda la carga. La modal impide cerrarse durante el procesamiento. El tamaño del archivo se valida según la configuración de la empresa.

`GET /subscriptions/export` comparte la consulta filtrada con el listado. Estas rutas están bajo el tenant autenticado, no en Guest.

## Actualizacion funcional: cupos y vencimiento

- Las membresias de catalogo no usan `barcode`, `brand_id` ni `warehouse_items`; su disponibilidad no depende de inventario fisico.
- `capacity_control_enabled`, `capacity_limit` y `capacity_used` permiten vender membresias con cupos limitados o tratarlas como ilimitadas.
- `expires_at` es opcional. Cuando vence, el backend inactiva la membresia al listar catalogo o referencias comerciales y la bloquea en ventas.
- En Detalle, la etiqueta Cupos aparece cuando el control de cupos está activado y muestra el límite entero configurado en la membresía. Comparte el formato del listado de Servicios.
- Al activar el control, Cantidad de cupos muestra el asterisco y exige un entero entre 1 y 1 000 000.
- Al vender una membresia limitada se consume cupo segun la cantidad vendida. Al anular la venta se repone el cupo consumido.
