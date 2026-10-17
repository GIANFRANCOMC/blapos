# 12 - Servicios

## Qué hace

Administra ítems `service`: se venden, no descuentan stock y pueden medirse dentro de una sesión de servicio.

## Backend

- Usa `items` con `type = service` y categorías mediante `category_items`.
- `estimated_duration_minutes` conserva la duración prevista para agenda, SLA y comparación con tiempo real.
- `commission_type` y `commission_value` permiten configurar comision por servicio como porcentaje o monto fijo por unidad vendida.
- `commission_rate` se conserva como compatibilidad historica para servicios que ya tenian comision porcentual.
- En ventas, la comision se guarda como foto en `sales_body` y se suma en `sales_header.commission_total`; no modifica el total cobrado al cliente.
- `price_includes_tax` define si el precio ya contiene IGV.
- `igv_exempt` define si el servicio está exonerado de IGV. Cuando está activo, no se calcula IGV para ese detalle y `price_includes_tax` queda desactivado.
- Moneda, categoría y código interno se validan por empresa.
- Crear o editar invalida las configuraciones dependientes de ventas y catálogo.

## Interfaz

- El listado usa la misma escala de tabla, identificación sin recuadro, copia discreta, precios y estado de Productos. Muestra duración estimada, cupos y comisión sin abrir la modal.
- La modal compartida conserva cabecera, cuerpo y pie del catálogo. Sus pestañas descriptivas son `SERVICIO`, `DETALLE`, `IMPUESTOS` y `CONFIGURACIÓN`; admiten teclado y desplazamiento en móvil. No aparece Inventario porque no aplica a servicios.
- Los errores locales y del backend se muestran en el campo y señalan la pestaña correspondiente.
- El formulario permite definir duración estimada en minutos, tipo de comisión (`Sin comisión`, `Porcentaje` o `Monto fijo por unidad`) y valor.
- Si la comisión está en `Sin comisión`, el valor queda deshabilitado para evitar montos huérfanos.
- La duración sirve como base para agenda, servicios en curso, SLA y medición del tiempo real del responsable.

## Acciones masivas

`Cargar masivamente` y `Descargar Excel` están en el menú `Acciones`, como en Productos. La descarga confirma la operación y exporta todos los servicios que coinciden con el filtro actual, sin limitarse a la página visible. Incluye precios, duración, cupos, impuestos, publicación, comisión y estado; nunca columnas de stock.

`GET /services/import` descarga la plantilla básica. `POST /services/import` acepta Excel o CSV con nombre y precio obligatorios, código interno y descripción opcionales, y duración estimada en minutos opcional. Si el código está vacío, se genera con el prefijo configurado. La moneda es la de la empresa, el servicio se crea activo y no publicado; el resto se configura desde el editor. Máximo 500 filas, validación por fila y transacción única: un error revierte toda la carga. La modal bloquea cerrar/cancelar mientras se procesa. El tamaño permitido del archivo depende de la configuración de la empresa.

`GET /services/export` reutiliza la consulta filtrada del listado. Los archivos se generan dentro del tenant autenticado; ninguna ruta masiva existe en Guest.

## Actualizacion funcional: cupos y vencimiento

- Servicios no usa `barcode`, `brand_id` ni `warehouse_items`: no tiene codigo de barras, marca comercial ni inventario fisico por unidades.
- `capacity_control_enabled`, `capacity_limit` y `capacity_used` controlan cupos opcionales. Si el control esta apagado, el servicio es ilimitado.
- `expires_at` es opcional. Al vencer, el backend inactiva el servicio al listar catalogo o referencias comerciales y bloquea ventas con datos obsoletos.
- El listado muestra cupos disponibles como dato compacto: ilimitado o disponibles contra limite.
- El formulario permite activar cupos y definir fecha de vencimiento. Si cupos esta apagado, el limite queda deshabilitado para evitar datos que no aplican.
- Ventas y Venta POS solo reciben servicios `active`, no vencidos y con cupos disponibles cuando el control esta activo.
- Cada venta consume cupos segun la cantidad vendida. La anulacion de una venta repone esos cupos para servicios y membresias.
