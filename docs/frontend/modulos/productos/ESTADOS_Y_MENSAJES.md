# Productos · Estados y mensajes

Propuesta visual y de contenido vinculada a [la guía de Productos](README.md). Reutilizar Alerts y SweetAlert2 existentes. No introducir otra biblioteca ni otro loader.

## Secuencia de una operación

1. Validar los campos locales. Un error local se muestra junto al campo y recibe foco; no simular una petición.
2. Al enviar, bloquear envíos duplicados y mostrar un único SweetAlert de carga.
3. Cerrar el loader cuando termine la petición, incluso si falla.
4. En éxito, cerrar la modal operativa antes del resultado y refrescar el conjunto que corresponda. En error, conservar el formulario.
5. Al cerrar Entendido, restaurar el foco al listado o al campo/contexto que requiere corrección.

Agregar abre directamente el formulario. El Swal de operación empieza cuando se envían datos; no agregar una confirmación redundante para cada guardado.

## Diseño compartido

- Popup blanco de 420 px de referencia, 358 px en móvil, radio de 12 px y fondo oscurecido. Adaptar a contenido y viewport.
- Icono de estado, título de 16 px, explicación de 13 px y botón Entendido. Éxito verde, error rojo, carga azul.
- Carga con circle-notch giratorio y ayuda **Espera un momento, por favor.** Sin porcentaje ficticio, botón Entendido, cierre exterior ni interrupción accidental.
- Reutilizar fa-solid fa-circle-notch y br-loader__icon del helper Alerts; fa-spin representa el mismo giro solicitado, sin superponer dos animaciones.
- Mantener el texto cuando se reduzca o desactive el movimiento. El estado se anuncia mediante role=status, aria-live y aria-busy.
- Un solo nivel de foco activo. No acumular Swal, modal y toast para el mismo resultado.

## Carga y resultado confirmado

| Acción | Título durante la carga | Título de éxito | Texto |
| --- | --- | --- | --- |
| Agregar | Agregando producto… | Producto agregado | El producto ya está disponible en el listado. |
| Editar | Guardando cambios… | Producto actualizado | Los cambios ya aparecen en el listado. |
| Importar | Importando productos… | Importación finalizada | {n} productos importados. El stock inicial se registró en el almacén seleccionado. |
| Exportar | Preparando Excel… | Excel preparado | La descarga comenzó. Revisa la carpeta de descargas. |
| Plantilla | Preparando plantilla… | Plantilla Excel preparada | La descarga comenzó. Completa la plantilla y selecciónala aquí para importar. |

Todos los resultados usan **Entendido**. El contador procede de la respuesta, no de las filas del Excel. Si el stock inicial fue cero o la respuesta no confirma movimientos, usar únicamente “{n} productos importados.” para no afirmar entradas inexistentes. No afirmar que el archivo se guardó en una carpeta específica.

El helper actual ya ofrece Alerts.loading, Alerts.close, Alerts.showMessage y Alerts.generateAlert. Algunos textos y el tratamiento de la descarga difieren de esta propuesta; al implementar hay que adaptar los consumidores sin duplicar avisos.

## Rechazo confirmado

| Situación | Título | Texto o recuperación |
| --- | --- | --- |
| Fallo al agregar | No se pudo agregar el producto | Tus datos siguen en el formulario. Revisa el mensaje e inténtalo de nuevo. |
| Fallo al editar | No se pudieron guardar los cambios | Tus cambios siguen en el formulario. Revisa el mensaje e inténtalo de nuevo. |
| Archivo con validaciones | Revisa el archivo de importación | Hay datos que necesitan corrección. Revisa el detalle por fila antes de volver a importar. |
| Archivo rechazado sin detalle | No se pudo importar el archivo | Revisa el archivo y vuelve a intentarlo. |
| Exportación fallida | No se pudo preparar el Excel | Conservamos tus filtros. Inténtalo de nuevo. |
| Plantilla fallida | No se pudo preparar la plantilla | Inténtalo de nuevo. Si el problema continúa, vuelve a abrir la importación. |

Priorizar el mensaje seguro y útil del servidor cuando especifique la causa. No mostrar trazas ni HTML sin escapar. Para filas con errores, usar generateAlert con la tabla de mensajes existente y asociar errores de campos en la modal.

## Respuesta no confirmada

Cuando la comunicación no permite saber si el servidor confirmó una escritura, no tratarlo como un rechazo seguro ni ofrecer reintento automático.

| Operación | Título | Texto |
| --- | --- | --- |
| Agregar o editar | No pudimos confirmar el guardado | Revisa el listado antes de repetir la operación para evitar duplicados. |
| Importar | No pudimos confirmar la importación | Revisa el listado antes de volver a importar para evitar registros duplicados. |

Entendido devuelve al contexto desde el que se puede revisar el listado. Conservar los datos pendientes mientras se comprueba la operación.

## Mensajes junto al campo

| Caso | Mensaje propuesto |
| --- | --- |
| Nombre vacío | Ingresa el nombre del producto. |
| Precio vacío o no positivo | Ingresa un precio de venta mayor que cero. |
| EAN inválido | Ingresa un código EAN-13 válido de 13 dígitos. |
| Código duplicado | Usa otro código. Este ya pertenece a un registro de la empresa. |
| Mínimo mayor al precio | No puede ser mayor que el precio de venta. |
| Máximo menor al precio | No puede ser menor que el precio de venta. |
| Archivo sin seleccionar | Selecciona el archivo que completaste. |
| Almacén sin seleccionar | Selecciona el almacén para el stock inicial. |
| Categorías sin opciones | No hay categorías disponibles. |
| Marca sin opciones | No hay marcas disponibles. |

Los dos últimos son estados del selector, no fallos de guardado. Ofrecer Agregar solo si existe permiso. En fallo remoto: “No pudimos cargar las opciones.” con Reintentar, conservando las selecciones existentes.

## Consultas y confirmaciones

La consulta en curso y la decisión de ejecutar una acción tienen estructuras distintas.

### Consulta asíncrona

Swal compacto de referencia de 420 px, spinner de 28 px a la izquierda y contenido contextual a la derecha:

- **Consultando inventario…**
- **{nombre del producto}**
- **Espera un momento, por favor.**

Sin botón de confirmación, porcentaje ficticio ni delay añadido en la aplicación. Si los datos ya están disponibles, abrir directamente la consulta. ProductInventoryModal actualmente recibe el registro del listado y no hace una petición nueva; el prototipo muestra este estado solo como ejemplo de carga real.

Buscar productos conserva el loader de tabla, sin bloquear toda la pantalla con un Swal.

### Exportar productos

Swal de 480 px de referencia, adaptado a 358 px en móvil:

- Título: **¿Exportar productos?**
- Sin filtros: **Se descargará un archivo Excel con todos los productos del listado.**
- Con filtros: **Se descargará un archivo Excel con todos los resultados de la búsqueda «{término}».**
- Alcance: **{n} productos · Listado completo** o **{n} productos · Búsqueda aplicada**.
- Botones: **Cerrar** y **Exportar productos**.

Usar el total y los filtros aplicados, incluyendo todas las páginas. Después del clic, retirar la confirmación antes de mostrar el loader. No apilar dos avisos de la misma operación.

Descargar código de barras queda como descarga directa de PNG y no abre una pantalla adicional.

### Cerrar sesión

- Título: **¿Cerrar sesión?**
- Texto: **Tendrás que iniciar sesión nuevamente para continuar.**
- Botón secundario: **Seguir en Blapos**.
- Botón de confirmación rojo: **Cerrar sesión**.

El disparador es un cuadrado de 40 × 40 px con fondo rojo suave e icono de apagado rojo centrado. El tooltip Cerrar sesión usa fondo rojo y texto blanco; los tooltips de mundos usan azul activo. Reutilizar la operación POST y protección CSRF actuales. En Figma no se ejecuta una salida de sesión real.

Al implementar, ampliar el helper compartido para permitir el texto de cancelación cuando corresponda, conservando su valor predeterminado en otros consumidores. Las confirmaciones deben nombrar acción, consecuencia y alcance; no usar un “¿Estás seguro?” aislado.

## Estados del listado

| Estado | Presentación |
| --- | --- |
| Carga inicial | Skeleton con estructura de filtros y tabla. |
| Consulta | Indicador dentro de la tabla y anuncio “Buscando productos…”. |
| Sin registros | Aún no hay productos registrados. Acción Agregar producto si tiene permiso. |
| Sin coincidencias | No encontramos productos con estos filtros. Acción Limpiar filtros. |
| Error de consulta | No pudimos cargar los productos. Acción Reintentar. |
| Sin almacenes de consulta | Este producto aún no tiene inventario para mostrar. |
| Sin permiso | Conservar las reglas de visibilidad y acceso actuales; no simular acciones habilitadas. |

La carga de tabla y la carga global tienen funciones diferentes: buscar no debe abrir un Swal sobre toda la página. Los estados vacíos conservan el total correcto; durante consulta no mostrar el total anterior como resultado vigente.

