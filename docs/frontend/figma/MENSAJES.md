# Blapos · Mensajes, SweetAlert y tooltips

## Elegir el canal

| **Necesidad** | **Presentación** |
| --- | --- |
| Dato inválido | Error junto al campo y foco en el primer dato a corregir. |
| Consulta del listado | Skeleton o indicador dentro de la tabla, con texto accesible. |
| Guardado o importación en curso | Un SweetAlert de carga con spinner y nombre de la operación. |
| Decisión previa | Confirmación con acción, consecuencia, alcance y dos botones claros. |
| Resultado | Un único aviso de éxito, error o atención según respuesta real. |
| Contexto persistente | Mensaje en línea cerca del elemento al que se refiere. |
| Botón de icono | Nombre accesible y tooltip en hover y foco. |

Agregar abre el formulario directamente. No confirmar cada guardado otra vez. No apilar modal, Swal y toast para el mismo resultado.

## Anatomía y comportamiento

Swal base de 420 px; móvil de 358 px o el ancho disponible con margen. Confirmación con alcance: 480 px en escritorio. Fondo blanco, radio 12 px, título 16 px y cuerpo 13 px. La altura se adapta al contenido.

Carga: Font Awesome circle-notch y texto “Espera un momento, por favor.”. Reutilizar Alerts y su animación actual, sin dos giros superpuestos. Bloquear envíos duplicados. Cerrar el loader en éxito y error. Sin porcentaje ficticio ni cierre accidental.

Éxito se muestra solo tras respuesta válida. En error, conservar datos y contexto. Si la comunicación no confirma una escritura, pedir revisar el listado antes de repetir. Restaurar foco al contexto apropiado. Respetar movimiento reducido y anunciar estados con role=status, aria-live y aria-busy al implementar.

## Plantillas generales

| **Clave** | **Título** | **Cuerpo / acción** |
| --- | --- | --- |
| action.loading | {acción en gerundio}… | Espera un momento, por favor. |
| action.success | {objeto} {resultado confirmado} | Explica dónde se refleja el resultado. Entendido. |
| action.error | No se pudo {acción} | Indica qué se conserva y cómo continuar. |
| action.uncertain | No pudimos confirmar {operación} | Revisa el listado antes de repetir la operación para evitar duplicados. |
| confirm.action | ¿{Acción y objeto}? | Consecuencia y alcance. Secundario claro + nombre de la acción. |
| list.empty | Aún no hay {objetos} registrados | Agrega el primero para comenzar, si tiene permiso. Usar el símbolo genérico compartido de colección vacía. |
| list.no_results | No encontramos {objetos} con estos filtros | Prueba con otro término o limpia los filtros. |
| list.error | No pudimos cargar {objetos} | Conserva filtros y ofrece Reintentar. |
| select.loading | Buscando opciones… | No borrar la selección existente. |
| select.empty | No hay opciones disponibles | Agregar solo si tiene permiso. |
| select.error | No pudimos cargar las opciones | Reintentar, conservando los valores elegidos. |
| field.required | Ingresa {dato} | Adaptar al dato real. |
| field.invalid | Revisa {dato} | Explicar el formato o límite concreto. |
| download.success | {Archivo} preparado | La descarga comenzó. Revisa las descargas del navegador. |
| tooltip.world | {Nombre del mundo} | Azul activo con texto blanco. |
| tooltip.logout | Cerrar sesión | Rojo con texto blanco. |

Usar placeholders con datos reales de respuesta y pluralización apropiada. No afirmar que el navegador guardó un archivo en una carpeta determinada.

## Consulta

“Consultando {dato}…” con contexto del registro. No incluye un botón de confirmar. Si el dato ya está cargado, abrirlo directamente. No agregar una espera artificial para mostrar el loader.

## Confirmación

- Título que nombre la acción: “¿Exportar productos?”.
- Explicación: “Se descargará un Excel con todos los resultados de la búsqueda”.
- Alcance: “{n} productos · Búsqueda aplicada”.
- Botones: “Cerrar” y “Exportar productos”.

El alcance incluye todas las páginas del conjunto aplicado, no los filtros que aún no se han buscado.

Cerrar sesión: “¿Cerrar sesión?” / “Tendrás que iniciar sesión nuevamente para continuar.” / “Seguir en Blapos” y “Cerrar sesión” rojo.

## Tooltips

Una etiqueta breve, sin párrafos ni acciones dentro. Mostrar al pasar el mouse o recibir foco; cerrar con Escape. Mantener visible sin recortarlo por el panel vecino. El botón conserva nombre accesible aunque no se muestre tooltip. La salida usa cuadrado rojo suave e icono rojo de apagado centrado.

## Ejemplos de Productos

| **Acción** | **Carga** | **Éxito** | **Texto** |
| --- | --- | --- | --- |
| Agregar | Agregando producto… | Producto agregado | El producto ya está disponible en el listado. |
| Editar | Guardando cambios… | Producto actualizado | Los cambios ya aparecen en el listado. |
| Importar | Importando productos… | Importación finalizada | {n} productos importados. |
| Exportar | Preparando Excel… | Excel preparado | La descarga comenzó. Revisa las descargas del navegador. |
| Plantilla | Preparando plantilla… | Plantilla Excel preparada | La descarga comenzó. Completa la plantilla y selecciónala aquí para importar. |

Solo mencionar stock inicial registrado si la respuesta lo confirma. Para error de validación del archivo, conservar el detalle por fila devuelto por backend. Descargar código de barras es una descarga directa de PNG, sin flujo adicional de interfaz.

Los textos específicos y las condiciones de Productos continúan en su guía de módulo, vinculada desde su archivo Figma.
