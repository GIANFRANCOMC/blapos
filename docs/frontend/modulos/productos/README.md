# Productos · Experiencia e implementación FG

Estado: diseño aprobado integrado en el frontend el 20 de noviembre de 2026. Tokens, componentes, navegación y navbar FG se aplican globalmente en System. Consulta la [guía de implementación, etapas y verificaciones](../../figma/IMPLEMENTACION_FG.md). API, permisos y reglas de negocio se conservan. Las notas siguientes registran el historial de Figma.

Biblioteca Componentes 1.0.3 aceptada con autorización del usuario en todas las páginas de Productos: iconos de botones con el mismo color de su etiqueta. Verificados 344 pares de icono o loader y texto sin diferencias de color u opacidad. Consulta el [registro central de actualización](../../figma/REGISTRO_Y_CALIDAD.md).

Biblioteca Componentes 1.0.4 publicada y aceptada con autorización del usuario en todas las páginas: Success, Warning y Danger comparten el texto oscuro #1a1a35; Danger conserva #fee6e7. Verificados 925 badges, incluidos siete Danger, sin diferencias.

Biblioteca Componentes 1.0.5 publicada y aplicada con autorización del usuario: sin registros, sin coincidencias y error se componen con instancias vinculadas de Listado / Estado. El vacío usa una carpeta abierta genérica compartida con el ejemplo de Marcas. Se conservan los cuatro botones originales y sus conexiones; el botón Importar mantiene su ausencia previa de conexión de prototipo. La biblioteca incluye ejemplos de escritorio y de 320 px y documenta Title, Body, permisos y acciones.

## Ubicación y fuentes

- Organización: **Blapos → Catálogo e inventario → Productos**. Conservar un archivo por submódulo dentro de su mundo.
- [Archivo Productos en Figma](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-54).
- [Biblioteca Componentes de Blapos](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=15-2) · [Guía central](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=19-2).
- [Listado y prototipo](https://www.figma.com/proto/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-54).
- [Reglas operativas de Productos](../../../backend/system/modules/11_products.md).
- [Patrón vigente de catálogo](../../patrones/CATALOGO_COMERCIAL.md), [navegación](../../patrones/TENANT_NAVIGATION.md), [interacciones](../../patrones/INTERACTION_PATTERNS.md) y [calidad](../../calidad/DESIGN_CHECKLIST.md).
- [Mensajes y SweetAlert de la propuesta](ESTADOS_Y_MENSAJES.md).

El sistema visual FG es global. La composición de los otros módulos se migrará conservando sus datos, permisos y operaciones.

## Objetivo y alcance

Permitir a una persona con poca experiencia tecnológica localizar un producto, consultar su inventario y agregarlo o editarlo sin perder el contexto del listado. La jerarquía depende del espacio, la alineación y los títulos; el color destaca acciones y estados.

El área es **System**, dentro de la empresa y tenant autenticados. Guest solo recibe la publicación comercial que corresponda y Platform conserva su administración independiente. No incorporar información operativa del listado en rutas públicas.

Se conservan filtros, contratos de datos, precisión, validaciones, permisos y movimientos existentes. La nueva composición no habilita operaciones ni cambia el catálogo de navegación.

## Navegación y ubicación

| Elemento | Propuesta | Regla de implementación |
| --- | --- | --- |
| Mundos | Riel navy de 64 px con iconos blancos y acceso activo azul, sin rótulo MUNDOS. | Cada icono muestra el nombre del mundo al pasar el cursor y al recibir foco; conservar permisos y scroll. |
| División | Línea vertical explícita de 1 px entre riel y panel contextual. | La separación permanece al cambiar de mundo; no depende del hover. |
| Submódulos | Panel claro de 215 px con el nombre del mundo y los grupos, únicamente texto. Sin marca, icono del mundo, iconos por opción ni viñetas. | Mantener el origen en CompanySectionService, el orden de Inventario y Catálogo comercial y las preferencias del usuario. |
| Opciones | Texto centrado verticalmente en filas de 36 px, padding común de 12 px y opción activa con fondo azul suave. | No derivar rutas ni permisos a partir del texto o de los iconos de la maqueta. |
| Breadcrumb | Se retira de esta propuesta. | El mundo y submódulo activos ya dan contexto; una eventual incorporación se revisará por separado. |
| Móvil | Menú en drawer, conservando mundo → grupo → submódulo. | Reutilizar el offcanvas existente, devolver foco al disparador y evitar scroll en el documento bajo el drawer. |

Los recursos gráficos de navegación se concentran en el riel de mundos. El panel contextual reserva su jerarquía al nombre del mundo, grupos y opciones de texto. Las opciones y su orden siguen procediendo del catálogo de navegación. No migrar ni renombrar rutas para reproducir la maqueta.

Los tooltips de mundos usan el mismo azul del acceso activo (#176fae), con texto blanco. Aparecen a la derecha del icono, sin desplazar el panel ni quedar debajo de él. Usar nombre accesible además del tooltip; permitir su cierre al salir del control o con Escape y mostrarlo también mediante foco de teclado. En Figma existen variantes Reposo y Tooltip con transiciones de hover.

Cerrar sesión se representa como un botón cuadrado de 40 × 40 px, radio de 6 px, fondo rojo suave e icono de apagado rojo centrado. Su tooltip **Cerrar sesión** tiene fondo rojo (#c92d35) y texto blanco. Su confirmación ofrece **Seguir en Blapos** y **Cerrar sesión** en rojo. El prototipo presenta el diálogo; no ejecuta el cierre real de una cuenta.

## Listado completo

1. Título Productos y descripción breve.
2. **Agregar producto**, acción principal azul; **Acciones**, botón secundario con flecha.
3. Franja de filtros: tipo de búsqueda, término, Buscar y Limpiar.
4. **Productos registrados**, o el contexto de la búsqueda, y selector **Ordenar por** con Nombre A–Z o Nombre Z–A.
5. Tabla, estados del conjunto y recuperación.
6. Cantidad de resultados y paginador.

El menú Acciones ofrece **Importar productos** y **Exportar productos**. El menú por fila contiene Editar producto y Descargar código de barras. Esta última acción descarga directamente la imagen PNG: no abre modal, vista previa ni un flujo adicional. El prototipo no ejecuta la descarga real.

El icono junto al nombre consulta el inventario. Se usa el mismo símbolo de caja en todas las filas: neutro para consulta normal y ámbar sobre amarillo suave cuando existe una alerta. Su tooltip y nombre accesible deben indicar “Consultar inventario” o “Consultar inventario: requiere atención”, para no depender únicamente del color. No elimina, traslada ni ajusta existencias.

En Figma se diseñaron A–Z y Z–A. Por decisión del usuario, la implementación conserva **Nombre A–Z** como texto hasta que la API admita Z–A. No se ordenan solo los registros de la página actual.

### Orden por nombre: propuesta para implementar

El código actual de ProductService conserva orderBy(name, ASC). El prototipo muestra ambas direcciones con datos de ejemplo; esta entrega no cambia el endpoint.

Para implementarlo, proponer sort_direction con valores permitidos asc o desc y asc como predeterminado. La columna name debe ser fija en el servidor, sin aceptar columnas o SQL arbitrario del cliente. Mantener el contexto de empresa, tipo product, filtros y permisos. Al cambiar la dirección, consultar desde la primera página conservando los filtros efectivamente aplicados. Listado, paginación y Excel deben usar la misma dirección; agregar un desempate estable por id para nombres iguales.

Los filtros pendientes de edición no se aplican por cambiar el orden. Comprobar dirección inválida, nombres repetidos, búsquedas aplicadas y exportación antes de adoptar el cambio en producción.

### Tabla y alineación

La tabla de escritorio mide 1104 px dentro de un lienzo de 1440 px. Las cabeceras y celdas comparten el mismo contrato de columnas:

| Columna | Ancho de referencia | Alineación |
| --- | --- | --- |
| Identificación | 240 px | Texto a la izquierda, padding horizontal de 16 px. Código interno y EAN como texto seleccionable. |
| Producto | 460 px | Nombre y descripción a la izquierda. Icono de inventario de 28 px, separación de 10 px; cabecera alineada al inicio del nombre, con 54 px a la izquierda. |
| Precio | 180 px | Cabecera, importe y límites a la derecha, padding de 16 px. Conservar moneda y precisión del contrato. |
| Estado | 140 px | Cabecera y estado centrados. |
| Acciones | 84 px | Cabecera y botón de menú centrados. |

Cabecera gris azulada suave (referencia #f1f5f9), texto oscuro (#1a1a35), borde tenue y altura de 44 px. Todo el contenido de la cabecera se centra verticalmente desde su contenedor; las alineaciones horizontales siguen el tipo de dato. Filas blancas de altura base de 64 px, bordes tenues y mayor altura al desplegar detalle. Inventario usa la misma familia de superficies, con cabeceras de 40 px.

El navy se reserva para el riel de mundos y los textos de énfasis. El azul identifica acción o selección. Verde suave distingue Plantilla Excel; amarillo suave señala atención de inventario. Ninguna cabecera de tabla usa una superficie oscura dominante.

En inventario, el encabezado de una cantidad editable reserva los mismos 10 px a la derecha que el texto dentro del input. Las cantidades de solo lectura conservan el eje del valor sin ese inset. Así se alinean los números, además de los límites de las columnas.

Al implementar, definir las columnas una sola vez mediante colgroup o la estructura compartida equivalente; no calcular anchos independientes en thead y tbody. En móvil, conservar la alternativa de tabla con scroll interno accesible. El archivo también presenta tarjetas de consulta como alternativa de composición; la tabla conserva todas las columnas.

Los menús de selección y acciones usan superficie blanca, borde tenue, sombra suave y opciones centradas de 40 px. Cada menú se ancla al borde inferior de su disparador y no deja separaciones arbitrarias. El filtro tiene el mismo ancho que su control; Acciones y menú de fila usan 252 px de referencia.

En **Filtrar por**, todas las entradas son opciones simples: Todos los filtros, Código interno, Código de barras, Nombre, Marca, Descripción comercial y Precio de venta. Marca no contiene otro selector ni una acción Agregar. Mantener el mismo inset de texto y reservar la marca de selección a la derecha. La selección elegida se distingue de hover/foco.

La búsqueda mantiene filtros pendientes y aplicados separados. **Buscar** aplica y vuelve a la primera página. Editar un filtro no cambia el conjunto ni el alcance del Excel hasta buscar. Paginar y exportar usan los filtros aplicados. Limpiar conserva la retroalimentación compartida.

El total usa “Mostrando 1–10 de 124 productos”; los valores se calculan con la respuesta real. Diferenciar conjunto vacío de búsqueda sin coincidencias. Durante carga no presentar totales anteriores como vigentes.

## Agregar y editar

Modal de escritorio de 960 px, cabecera y pie fijos y contenido desplazable. En móvil ocupa la pantalla disponible; solo se desplaza el panel de campos. Cerrar mantiene su posición. Usar **Guardar producto** al crear y **Guardar cambios** al editar.

### Pestañas estables

Producto, Inventario y Configuración comparten tamaño, padding, icono, eje del texto y espacio para el indicador. La pestaña activa tiene superficie azul suave e indicador azul. Las inactivas reservan la misma línea en blanco, igual que el fondo: el texto no salta al seleccionar otra pestaña.

En escritorio, el indicador es lateral de 3 × 20 px; en móvil, inferior de 28 × 2 px. El estado se expresa también mediante aria-selected y asociación con el panel. Comprobar cambio de pestañas con alturas distintas sin desplazar automáticamente la vista.

### Producto

Orden confirmado: **código interno y código de barras → nombre → precio/moneda/estado → tratamiento del IGV → clasificación → detalle opcional**. Los códigos comparten fila en escritorio y se apilan en móvil.

- Se adapta el **v-select de vue-select** ya instalado (^4.0.0-beta.6). La clase de presentación select2 no implica cambiar a otra biblioteca.
- **Categorías** usa multiple=true, etiquetas removibles y búsqueda dentro del propio control. **Marca** muestra un único valor y la acción de limpiar cuando existe selección.
- Las opciones tienen texto con el mismo inset, una línea lateral reservada de 2 px y una marca a la derecha en la opción seleccionada. Selección y hover conservan estados distintos. Se retiran casillas, buscador separado y botón Listo del menú.
- Conservar searchable=true, clearable=true y append-to-body. Mantener closeOnSelect=true y deselectFromDropdown=false, valores actuales de la biblioteca: seleccionar cierra el menú; quitar una categoría se hace desde su etiqueta o mediante limpiar. No introducir un comportamiento nuevo de selección persistente.
- Ambos controles usan alturas de 36 px en escritorio y 44 px en móvil, padding horizontal de 10 px y caret de 14 px. Opciones de 40 px en escritorio y mínimo 44 px en táctil. El múltiple debe crecer si las etiquetas requieren otra línea; no fijar una altura que las recorte.
- **Agregar**, junto a Categorías y Marca, comparte estilo, tamaño y alineación con **Generar** en los dos códigos: enlace azul discreto en el extremo derecho de la etiqueta. Cada acción es independiente del control y respeta permisos.
- Los códigos están **desplegados por defecto**. Código interno y Código de barras muestran **Generar** junto a su etiqueta; se retiran los botones anchos y el acordeón de códigos.
- Generar cambia solo el código correspondiente y no guarda el producto. La validación de EAN-13 y unicidad sigue en backend; el prefijo se obtiene de la configuración existente.
- Descripción comercial y límites de precio conservan un desplegable con resumen del contenido configurado.
- El asterisco obligatorio usa el rojo compartido #c92d35 y conserva la etiqueta en su color normal. La leyenda también usa el asterisco rojo. Reflejar el requisito con required o aria-required, además del indicador visual; no modificar qué campos exige el backend.
- Los contadores de nombre y descripción se colocan **dentro del control**: a la derecha en texto de una línea y abajo a la derecha en descripciones. Formato compacto 0 / 100, con espacio reservado para no superponerse al contenido. Los errores siguen debajo del campo; el contador no sustituye al mensaje de validación.

El prototipo usa valores de ejemplo y estados enlazados. No ejecuta búsquedas remotas, generación aleatoria, validación ni persistencia.

### Agregar categoría y marca

Modales compactas de 480 px con título **Agregar categoría** o **Agregar marca**, sin rótulo Alta rápida ni mensaje informativo. Mostrar únicamente **Nombre** obligatorio y **Descripción** opcional. Pie con Cerrar y Guardar categoría o Guardar marca.

El código interno no se muestra ni se pide a la persona. Al implementar, conservar su generación automática con el prefijo configurado y su envío al endpoint requerido; ocultar el campo no elimina el contrato de backend. Mantener el producto abierto debajo, preservar sus datos y devolver el foco al control de origen.

### Inventario

| Modo | Stock | Mínimo |
| --- | --- | --- |
| Crear | Stock inicial editable, cero o positivo. Si es positivo, genera la entrada existente product_opening al confirmar. | Editable por almacén. |
| Editar | Stock actual de solo lectura, incluso si es negativo. No enviar initial_stock. | Editable por almacén. |
| Consultar desde listado | Solo lectura. | Solo lectura. |

Mostrar sucursal y almacén con su estado. Agotado: cantidad ≤ 0; Stock bajo: cantidad positiva ≤ mínimo; Disponible: cantidad positiva > mínimo.

La **consulta desde el listado** presenta primero el nombre del producto, después código interno, código de barras y marca mediante texto que pueda fluir y envolver. Se retiran los bloques estrechos de identidad y el recuadro fijo del total. Una franja sencilla reúne cantidad de almacenes, alertas y stock total con su unidad.

La tabla de consulta usa filas de 68 px, ubicación en dos líneas, importes alineados a la derecha y estados en etiquetas de ancho natural. En un área de 792 px, reservar 12 px por lado y columnas de 320/124/124/200 px. Encabezados y cantidades usan el mismo padding interior de 16 px. En móvil se mantiene la lectura por almacén mediante bloques, evitando comprimir las cuatro columnas.

ProductInventoryModal actualmente usa los datos del registro ya cargado. Abrir esta consulta no requiere un loader artificial. El Swal Consultando inventario se documenta para una consulta asíncrona real y como estado demostrativo del prototipo.

**No existen Quitar ni Restaurar filas**. Productos no permite desvincular almacenes ni ajustar saldos desde esta sección. Los movimientos se realizan mediante los flujos de Inventario.

### Configuración

Conservar reglas de publicación, visibilidad del precio, vigencia y comisión. Desactivar Publicar producto desactiva Mostrar precio. Exonerado de IGV tiene prioridad y apaga Precio incluye IGV. La fecha Disponible hasta es vigencia comercial, no vencimiento por lote.

No añadir cupos, duración ni campos propios de Servicios o Membresías.

## Importación y exportación

### Importar productos

1. **Prepara el archivo**: instrucciones breves y **Plantilla Excel**.
2. **Selecciona el archivo y su almacén**: selector único de almacén, archivo admitido y nombre visible del elegido.
3. **Importar productos**: validación, SweetAlert de carga y resultado confirmado.

Plantilla Excel usa verde suave (#dcf8ea), texto e icono verdes oscuros (#066647) y borde verde tenue (#b6e6cd). Seleccionar archivo es neutro y la acción que importa conserva azul sólido. La diferencia se apoya en texto, icono y ubicación, además del color.

Conservar extensiones y límite de 500 registros que admite la importación actual. Obtener el número importado de data.imported; “120” solo es un ejemplo. No prometer un alcance de importación parcial ni reintentar automáticamente. Si hay errores, conservar contexto y presentar el detalle por fila que devuelva el servidor.

### Exportar productos

**Exportar productos** nombra la acción del menú y del botón de confirmación. El Swal pregunta **¿Exportar productos?**, explica que se descargará un Excel y muestra el alcance: **{n} productos · Listado completo** o **{n} productos · Búsqueda aplicada**. El texto menciona el término de búsqueda cuando corresponde. Los botones son **Cerrar** y **Exportar productos**; la operación incluye todas las páginas que coincidan con los filtros aplicados.

Después: Preparando Excel… → Excel preparado o error. Confirmar únicamente que comenzó la descarga del navegador, no que el archivo fue guardado en disco. Plantilla y exportación son acciones distintas.

## Contratos que debe conservar la implementación

| Dato o acción | Contrato comprobado |
| --- | --- |
| Precio | ProductRequest exige precio > 0; límites opcionales ≥ 0 y coherentes entre sí. El min=0 de un control no reemplaza esa validación. |
| Categorías | Array de hasta 50 categorías activas de la empresa, sin duplicados. |
| Marca | brand_id nullable y único. Una marca inactiva ya asociada puede conservarse al editar; no asignarla como nueva opción activa. |
| Códigos | Interno requerido, prefijo configurado, hasta 50 caracteres permitidos. EAN-13 requerido, dígito de control válido y único entre items de la empresa. |
| Unidad | Se asigna automáticamente; no añadir un selector innecesario. |
| Inventario | Relación por almacén; creación con stock inicial y edición únicamente del mínimo. |
| Listado y Excel | Mismo criterio de empresa, tipo product, filtros y orden. El Excel no recibe page ni per_page. |
| Resultados | Mostrar éxito tras respuesta válida. Conservar formulario ante rechazo; resultado incierto exige comprobar el listado antes de repetir. |

## Componentes y adopción

Reutilizar ProductEditor, productFormConfig, AddProduct, ProductWarehouseInventory, ProductInventoryModal, FileImportModal, FiltersSection, CatalogPriceCell, ResponsiveTable, AppTabs y Alerts. Los cambios genéricos se realizan en Shared o el componente responsable; las composiciones de Productos permanecen en su módulo.

La propuesta usa una sola familia de estilos **Blapos / Tipografía**, con Source Sans 3:

| Rol | Tamaño y peso |
| --- | --- |
| Cuerpo | 13 px, Regular |
| Etiqueta | 13 px, Medium |
| Ayuda y badge | 12 px, Regular |
| Sección | 14 px, SemiBold |
| Título de modal | 16 px, SemiBold |
| Título de página | 24 px, SemiBold |

Se migraron las referencias de las pantallas, componentes y archivo antes de retirar los cuatro estilos Inter iniciales y el rótulo antiguo de mundos. Quedan seis estilos tipográficos y dos roles de elevación bajo Blapos. No crear familias v2, v3 o alternativas para nuevos módulos.

La aplicación vigente usa Inter. Esta limpieza no instala fuentes ni modifica los tokens de producción; la adopción debe centralizarse cuando se implemente.

### Componentes reutilizables

**Blapos / Badge** tiene propiedades **Tone** y **Label**, con tres variantes:

| Tone | Uso en Productos | Fondo | Texto |
| --- | --- | --- | --- |
| Success | Activo, Disponible, Stock saludable | #dcf8ea | #1a1a35 |
| Warning | Inactivo, Stock bajo | #fff3d4 | #1a1a35 |
| Danger | Agotado | #fee6e7 | #9f2533 |

Todos comparten altura de 26 px, padding horizontal de 10 px, radio de 6 px y tipografía Ayuda de 12 px. El ancho se adapta al texto. Conservar la celda de la tabla como contenedor independiente para no alterar sus columnas al usar un badge de ancho natural.

Se reutilizaron los componentes de Activo e Inactivo dentro de la familia para conservar sus referencias. La fila de producto expone el badge anidado; los nuevos consumidores deben configurar Tone y Label explícitamente. No crear otro componente por cada palabra de estado. En Vue, adaptar StatusBadge y su mapa de presentación cuando se implemente: el color de Inactivo cambia, pero su regla operativa no cambia.

La biblioteca publicada **Blapos → Componentes** es la fuente común de maestros, estilos y tokens. Productos consume sus instancias y conserva sus composiciones específicas en la página 91. Se retiraron los seis estilos tipográficos, dos elevaciones y nueve colecciones locales reemplazadas; aquí solo quedan las cinco anchuras de columna de Productos. La página 04 enlaza la guía, catálogo, diccionario, mensajes y registro centrales. Los títulos, cabeceras, resultados y paginación también reutilizan componentes de la biblioteca. Todo componente compartido nuevo debe registrarse, documentarse y publicarse en Componentes antes de usarse en un submódulo.

Los iconos de botones heredan el color del label mediante currentColor: blancos en primarios, color de acción en enlaces y tono de texto en secundarios. Comprobar también hover, foco y deshabilitado. En Figma se utilizaron símbolos con el color explícito correspondiente para que un intercambio de icono no recuperara el gris de origen.

Orden sugerido de implementación: estructura y tokens → navegación → tabla y filtros → pestañas y selectores → códigos → importación/exportación → SweetAlert → revisión integrada. Aplicar primero en Productos y comprobar un consumidor adicional antes de extender cambios compartidos.

## Verificación y límites

Comprobado en Figma:

La última auditoría del 19 de noviembre revisó 2.395 conexiones de prototipo sin destinos de navegación inválidos y 916 instancias de Badge con geometría consistente. Confirmó seis estilos tipográficos Source Sans 3, tokens de color y geometría vinculados y retiro de los cinco estilos iniciales sin referencias pendientes. Los contrastes calculados de badges y tooltips superan 4,5:1. Se revisaron orden ascendente y descendente, marcas obligatorias rojas y la alineación del inventario en escritorio y móvil. El contenido operativo usa nodos editables.

- Estructura editable con frames, texto, vectores e instancias. Las únicas imágenes de las vistas revisadas son la marca y el isotipo del repositorio.
- Fuente Source Sans 3 consistente en los wrappers revisados.
- Composición de escritorio de 1440 px, modal de 960 px, importación de 720 px y editor móvil de 390 px.
- Cabeceras suaves y centradas verticalmente; columnas compartidas, importes a la derecha, Estado y Acciones centrados.
- Vue Select múltiple para Categorías y único para Marca; códigos al inicio e indicadores blancos reservados en tabs inactivos.
- Sin breadcrumb, rótulo MUNDOS ni descripción del mundo. Submódulos solo de texto y centrados verticalmente.
- Tooltips de mundos en azul activo; salida cuadrada con icono rojo centrado y tooltip rojo.
- Selector A–Z y Z–A en escritorio y móvil, con estados de búsqueda aplicada.
- Badges Success, Warning y Danger con geometría y estilos compartidos; Inactivo amarillo y Agotado rojo oscuro.
- Asteriscos rojos y seis estilos tipográficos canónicos, sin las familias iniciales duplicadas.
- Menú Filtrar por con siete opciones uniformes y sin controles anidados.
- Contadores dentro de los controles, incluida la descripción multilínea.
- Agregar categoría y Agregar marca con Nombre y Descripción, sin código interno visible ni texto Alta rápida.
- Estados de carga, éxito y error para las cuatro operaciones principales; aviso de resultado incierto y descarga de plantilla.
- Ausencia de acciones para quitar almacenes y restaurar filas.
- Selectores flotantes anclados al campo; navegación entre pantallas de la misma página y hover mediante variantes de componentes. La revisión de hover se realizó con sus estados y captura; el teclado en navegador sigue pendiente de implementación.

Pendiente de la implementación: teclado real, lector de pantalla, foco atrapado y restaurado, contraste calculado con CSS, zoom al 200 %, nombres largos, opciones remotas, red lenta, errores de API, descarga real, validación, permisos y pruebas integradas. Los enlaces de Figma simulan flujos con datos de muestra; los 1,5 segundos de carga son una demostración, no un tiempo contractual.

Aplicar áreas táctiles de al menos 44 px, incluyendo Agregar y Generar; mantener el indicador de carga con texto accesible y respetar movimiento reducido. No se ejecutó build de la aplicación porque esta entrega no cambia su código.



## Publicación 1.0.9 · aplicada en Productos

El 20 de noviembre de 2026 el usuario autorizó: «Acepta todos los cambios y realizar las confirmaciones correspondientes de ser neceario». Se publicó Componentes y se aceptaron las actualizaciones en Productos con «Mostrar actualizaciones para todas las páginas» y «Actualizar todo». Figma confirmó «No hay actualizaciones disponibles». La autorización incluye los contadores 1.0.7 y las etiquetas 1.0.8 que antes estaban pendientes.

Recursos publicados: Navegación / Riel de mundos, Archivo / Seleccionar, Formulario / Comisión, Campo con Prefix, las colecciones Primitivos y Color con el amarillo de archivo, y el ajuste pendiente de Listado / Estado. Se conservan los maestros y vínculos remotos.

- Navegación: riel remoto dentro de la composición contextual de Productos; siete mundos centrados, salida abajo y ocho interacciones conservadas. Las vistas explícitas de tooltips se adaptaron al nuevo riel.
- Archivo: doce botones vinculados; solo su carpeta usa amarillo #b58a00. Se conservaron sus IDs, tamaños y reacciones.
- Comisión: cuatro formularios de agregar/editar y tres ejemplos adicionales vinculados. Las tres opciones muestran —, % o S/. El menú participa en auto-layout y reserva espacio; en móvil desplaza el valor hacia abajo. La instancia y su contenedor usan altura adaptable.
- Contadores: 134 campos configurados con límites contractuales; se ocultan en búsqueda sin límite definido, fechas, stock, selección e importes. Producto: nombre/descripción 100, código interno completo 50, EAN-13 13; categoría 50/100 y marca 100/250.
- Etiquetas: fondo azul suave y texto/símbolo oscuros, vinculados a la biblioteca.

Verificación: doce combinaciones de tipo y formulario sin solapamientos; menú contenido en el grupo, anchos 732/350 y alturas abiertas 197/288. Treinta conexiones de comisión válidas en la biblioteca. Revisión visual de biblioteca, listado, importación y configuración. El prototipo representa datos de ejemplo; teclado, API, guardado real, foco y cálculos deben comprobarse al implementar.

[Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=82-159) · [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-54). Productos es el consumidor identificado; no se afirma haber actualizado archivos desconocidos. No se modificó código de aplicación.


Actualización Figma 1.0.10 (2026-11-20): riel vinculado con indicador azul izquierdo en el mundo activo y tooltips con punta anclada al icono. Salida conserva rojo. Publicación conjunta aplicada con autorización del usuario; revisados únicamente los estados y eventos del menú.
