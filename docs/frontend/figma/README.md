# Blapos · Guía del sistema de componentes

La biblioteca [Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes) es la fuente compartida de la propuesta visual de Blapos. Vive en **Blapos → Componentes**. [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) permanece en **Blapos → Catálogo e inventario → Productos** y consume sus instancias. Esta organización no cambia los permisos, tenant, contratos ni reglas de negocio.

## Mapa del archivo

| **Página** | **Contenido** |
| --- | --- |
| 00 · Inicio y uso | Cómo activar, usar y mantener la biblioteca. |
| 01 · Fundamentos | Color, tipografía, geometría, elevación y adopción. |
| 02 · Botones | Jerarquía, iconos, foco, hover, carga, deshabilitado y plantilla Excel. |
| 03 · Campos y selección | Texto, importe, contador, descripción, checkbox, acordeón y vue-select. |
| 04 · Navegación | Mundos, submódulos, pestañas, tooltips, marca e iconografía. |
| 05 · Datos y listados | Badge, celdas de cabecera, orden, resultados, paginación y vacíos. |
| 06 · Mensajes y feedback | SweetAlert de consulta, confirmación, carga, resultado y avisos en línea. |
| 07 · Estructura de submódulos | Cabecera de página, modal, pie y panel. |
| 08 · Guías y diccionario | Manual, registro, terminología, mensajes y controles de calidad. |

## Usar en otro submódulo

1. Crear o abrir el archivo dentro del mundo correspondiente en Blapos.
2. Abrir Recursos → Bibliotecas y agregar **Componentes**.
3. Insertar una instancia desde Recursos. No copiar el maestro al archivo del módulo.
4. Elegir variante y configurar propiedades de texto, icono, visibilidad y contenido. Los ejemplos Café, Marca o Producto no limitan el componente a esos datos.
5. Mantener estilos y tokens vinculados. Ajustar el ancho y la composición del módulo sin redefinir colores, fuentes o radios.
6. Probar con textos largos, errores, teclado y móvil. En implementación, comprobar un consumidor adicional si cambia un componente compartido.
7. Aceptar actualizaciones de la biblioteca tras revisar su registro de cambios.

No separar una instancia del maestro para resolver una variante ordinaria. No renombrar una copia local como v2. Primero revisar las propiedades y variantes disponibles.

## Qué pertenece a Componentes

Toda pieza reutilizable: fundamentos, botones, campos, selectores, menú, navegación, estados, contadores, celdas genéricas, títulos, modales base, tooltips, mensajes y patrones de composición. Los nombres usan **Blapos / Familia / Pieza**. Las variantes usan ejes claros: Dispositivo, Estado, Tono, Selección o Tipo. Badge conserva Tone y Label para mantener el contrato existente.

Las filas y columnas de Producto, sus acciones, consulta de stock, reglas de IGV, códigos, almacenes y validaciones son composiciones del submódulo. Permanecen en Productos, hechas con instancias compartidas. La navegación contextual de Catálogo e inventario también es una composición específica; sus enlaces y mundos proceden de la biblioteca.

## Registrar una pieza nueva

Antes de usarla en un submódulo:

1. Buscar por función en el catálogo y comprobar si una variante existente basta.
2. Definir nombre, propósito, consumidores y responsable. Una pieza compartida se crea aquí; una composición exclusiva permanece en su módulo.
3. Reutilizar tokens y los seis estilos tipográficos. Un token nuevo necesita función y valor documentados.
4. Crear maestro y variantes necesarias. Evitar matrices mayores a 30; separar subcomponentes. Los iconos se intercambian con INSTANCE_SWAP.
5. Definir propiedades y valores predeterminados; exponer instancias anidadas cuando deban personalizarse.
6. Documentar uso, límites, ejemplos, estados, teclado, responsive, mensajes y correspondencia con código.
7. Validar geometría, contraste, nombres largos, dependencias y consumidores.
8. Añadir la entrada al catálogo y al registro, publicar Componentes y usar una instancia vinculada en el módulo.
9. Revisar y aceptar la actualización en los consumidores.

## Actualizar sin fragmentar

Cambiar el maestro en su archivo de origen y publicar una versión con un resumen concreto. Cambios compatibles de estilo o corrección conservan nombre y propiedades. Si cambia la API, documentar equivalencias y migrar consumidores antes de retirar el contrato anterior. Una pieza obsoleta se marca como tal y se deja de insertar; retirarla solo cuando no tenga consumidores vigentes.

El archivo de exploración de Productos se conserva como histórico, fuera del sistema vigente. No es una biblioteca alternativa.

### Aviso y aceptación de actualizaciones

Por solicitud del usuario, cada vez que se publique un cambio en Componentes, Codex debe avisarle en el chat antes de aceptar la actualización en los archivos consumidores. El aviso debe incluir la versión, las piezas modificadas, el efecto esperado y los enlaces de los archivos afectados conocidos, indicando si quedan consumidores por identificar. Solicitar su aceptación para aplicar esa versión; no aceptar actualizaciones en su nombre sin autorización explícita para esa versión y alcance.

La publicación en Componentes y la aceptación en los consumidores son pasos distintos. En archivos separados, usar «Actualizar todo» en cada archivo autorizado aplica las actualizaciones disponibles a sus instancias y páginas; revisar el conjunto antes de usarlo para no incluir cambios fuera del alcance aprobado. Varios submódulos dentro de un mismo archivo comparten esa aceptación. Las instancias del maestro dentro de su propio archivo se actualizan directamente.

Registrar por archivo la versión y el estado: **pendiente de aceptación**, **autorizada**, **aplicada y verificada** o **aplazada**. No informar que todos los submódulos están actualizados sin comprobarlo. Si el usuario aplica el cambio, registrar su confirmación y distinguirla de una verificación realizada por Codex.

## Navegación Blapos

La versión publicada **Navegación Blapos · Azul pizarra** usa `#23354a` en el riel, blanco en la barra superior y el panel de submódulos, y el fondo claro de la aplicación en el área de trabajo. El azul FG identifica el mundo activo. Figma confirmó la publicación de cuatro maestros y cuatro variables de navegación. La aceptación en los archivos consumidores queda pendiente.

La versión publicada **Navegación Blapos · Compacta** reduce el riel a 60 px, con accesos de 40 × 40 px, y la barra de escritorio a 44 px. El panel de submódulos reserva 192 px, con rótulos de 11 px, iconos de 10 px y un título de 13 px. Las filas miden 32 px en escritorio y 44 px en móvil. El buscador centrado usa una cápsula sin borde con fondo neutro, texto de 12 px y lupa de 11 px. Mide hasta 340 × 28 px en escritorio. En móvil conserva el ancho flexible y 32 px de alto, con una barra de 48 px para alojar los controles táctiles. El isotipo de Inicio conserva 32 px. La aceptación en archivos consumidores queda pendiente.

La revisión inicial se probó a 1280, 820 y 320 px en la demo autenticada. Después de compactar el buscador y el panel de submódulos, se verificaron las vistas de escritorio y móvil en Figma y el foco, la búsqueda con teclado y los nombres largos del panel a 1280 y 320 px en la demo. El ajuste de botones de la barra superior se probó en Productos y Mi cuenta. Se comprobó la apertura de favoritos, avisos y cuenta, el indicador del panel y el centrado del buscador.

[Navegación Blapos en Figma](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=164-208) · [Navegación tenant](../patrones/TENANT_NAVIGATION.md).

### Paneles del navbar

La versión publicada **Navegación Blapos · Paneles del navbar** incorpora el componente compartido para Favoritos, Notificaciones y Cuenta. Incluye el borde inferior del navbar y los estados de hover circular. La aceptación en los archivos consumidores queda pendiente.

La revisión editable añade una tarjeta de Cuenta con degradado FG, iconos de módulo en Favoritos e iconos por nivel de aviso en Notificaciones. El buscador presenta hasta seis filas completas, con icono, nombre y mundo. Las demás coincidencias se recorren dentro de la lista. Se comprobaron las seis filas a 1280 y 320 px y la navegación con teclado en la demo autenticada. [Paneles del navbar en Figma](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=204-402) · [Buscador en Figma](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=221-1109).

## Publicación de Navegación FG · Gris neutro

Los maestros de **Barra superior**, **Riel de mundos**, **Mundo** y **Submódulo** se actualizaron en Componentes. Se agregó la barra compacta de móvil y se completaron los iconos reutilizables del riel y del control de panel. El navbar de 48 px y el riel de 64 px usan `color/chrome`. El panel contextual usa blanco, con iconos grises en reposo y azul para el destino seleccionado. El riel representa los nueve mundos vigentes, con botones de 40 × 40 px y una separación de 6 px.

Figma confirmó la publicación de los 14 cambios autorizados. La aceptación de esta versión en los archivos consumidores sigue pendiente. La demo del repositorio se verificó a 1280 y 320 px en Analítica y Catálogo comercial.

## Publicación de Navegación / Submódulo del 24 de noviembre de 2026

Componentes publicó únicamente el maestro **Navegación / Submódulo**. Esa versión conservó sus dos variantes y añadió el icono intercambiable de 11 px, con columna de 16 px y separación de 8 px. Se verificó el cambio en el maestro y en los estados abierto y cerrado del prototipo.

La versión está pendiente de aceptación en los consumidores. Los archivos documentados incluyen Productos y las composiciones de Ventas que usan Componentes. La actualización se revisa por archivo para confirmar qué instancias consumen el maestro y qué cambios propone Figma.

[Maestro publicado](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-89) · [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-54) · [Archivos de Ventas](../modulos/ventas/README.md).

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

## Relación con el frontend

La aplicación actual usa Inter; la propuesta Figma aprobada usa Source Sans 3. La biblioteca no instala fuentes, paquetes ni iconos. La adopción debe realizarse en tokens y componentes compartidos del repositorio.

Reutilizar Alerts/SweetAlert2, Font Awesome y vue-select existentes. Los nombres de código de tokens nuevos describen el contrato propuesto; no afirman que ya estén implementados. No hay cambios de API, permisos, precisión ni reglas operativas en esta entrega.

Los enlaces de prototipo usan datos de ejemplo. No ejecutan guardado, generación real, importación, consulta remota o salida de sesión.
