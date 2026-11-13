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
- El editor muestra el prefijo vigente de `internal_code_prefixes.subscription` (`MEMB` por defecto). El FormRequest valida y guarda el código completo en `items.internal_code`. El listado y el botón de copiar muestran ese valor guardado.

## Interfaz

- El listado muestra el Código interno centrado en la primera columna con un botón para copiarlo. En Detalles, Duración, Cupos y Comisión se muestran en tres etiquetas compactas. Si el límite diario de asistencias es mayor que cero, aparece un icono a la izquierda de Duración con su mismo color y tamaño de letra. El límite se muestra al pasar el cursor o enfocar el icono.
- La modal compartida usa pestañas descriptivas `MEMBRESÍA`, `DETALLE`, `IMPUESTOS` y `CONFIGURACIÓN`, con navegación por teclado y desplazamiento en móvil. No muestra Inventario físico.
- Los errores locales y del backend se muestran en el campo y señalan la pestaña correspondiente.
- El formulario permite definir límite diario de asistencias, comisión y valor de comisión.
- `benefits` y `restrictions` se editan como textos separados por coma para mantener una captura rápida; el frontend los normaliza a arreglos antes de enviarlos al backend.
- Los beneficios describen lo que incluye el plan; las restricciones deben usarse para reglas comerciales claras como horarios, sedes o condiciones de uso.

## Acciones masivas

`Cargar masivamente` y `Descargar Excel` están agrupadas en `Acciones`. La descarga confirma la operación y aplica los filtros actuales a todos los registros, no solo a la página visible. Incluye duración, asistencias, cupos, beneficios, restricciones, impuestos, publicación, comisión y estado; no incluye stock físico.

`GET /subscriptions/import` entrega la plantilla básica. `POST /subscriptions/import` requiere nombre, precio, valor y tipo de duración (`hora`, `día`, `rutina`, `mes` o `año`); código interno y descripción son opcionales. Un código vacío se genera con el prefijo configurado. La moneda es la de la empresa, la membresía se crea activa y no publicada; beneficios, restricciones y cupos pueden completarse luego en el editor. Máximo 500 filas y transacción única: cualquier error revierte toda la carga. La modal impide cerrarse durante el procesamiento. El tamaño del archivo se valida según la configuración de la empresa.

`GET /subscriptions/export` comparte la consulta filtrada con el listado. Estas rutas están bajo el tenant autenticado, no en Guest.

## Actualizacion funcional: cupos y vencimiento

- Las membresias de catalogo no usan `barcode`, `brand_id` ni `warehouse_items`; su disponibilidad no depende de inventario fisico.
- `capacity_control_enabled`, `capacity_limit` y `capacity_used` permiten vender membresias con cupos limitados o tratarlas como ilimitadas.
- `expires_at` es opcional. Cuando vence, el backend inactiva la membresia al listar catalogo o referencias comerciales y la bloquea en ventas.
- En Detalles, Cupos muestra `Ilimitados` si el control está desactivado o el límite entero configurado en la membresía. Comparte el formato del listado de Servicios.
- Al vender una membresia limitada se consume cupo segun la cantidad vendida. Al anular la venta se repone el cupo consumido.
