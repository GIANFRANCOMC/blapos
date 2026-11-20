# Blapos · Registro y control de calidad

## Registro inicial · 1.0

19 de noviembre de 2026. Biblioteca Componentes creada dentro de Blapos. Maestros compartidos trasladados desde Productos conservando la identidad de publicación. Fundamentos centrales, familias de variantes, ejemplos, mensajes y guía de uso. Productos es el consumidor inicial.

No se cambió código de producción. El prototipo sigue usando datos de ejemplo y no ejecuta peticiones reales. La adopción de Source Sans 3, orden descendente y los patrones refinados debe implementarse en los componentes y contratos correspondientes.

## Ficha obligatoria de componente

- Nombre estable y enlace del maestro.
- Propósito y cuándo usarlo.
- Cuándo usar una alternativa y límites.
- Propiedades: tipo, valores y valor predeterminado.
- Variantes y estados soportados.
- Dependencias, tokens y estilos vinculados.
- Ejemplos válidos de contenido.
- Comportamiento de teclado, foco, carga, error y permisos.
- Reglas responsive y textos largos.
- Correspondencia con componente del repositorio.
- Responsable, versión, consumidores, validaciones y pendientes.

Buscar y completar esta ficha antes de publicar una pieza nueva. Los maestros pertenecen a Componentes; las composiciones específicas al submódulo.

## Registro de cambios

### 1.0.10 · Menú de mundos · 2026-11-20

Indicador azul izquierdo de 3 × 32 px en Activo, conservando el fondo. Tooltip con punta lateral de 6 × 12 px centrada en el icono; salida conserva rojo. Mundo, Riel y Tooltip existentes publicados juntos y actualización de Productos autorizada por el usuario. Publicada y aplicada en Productos. Verificados indicador, puntas y ocho eventos hover sin duplicados; revisión visual limitada al riel. La pila y el área flexible permiten desbordamiento y el riel se dibuja por delante del panel contextual para evitar recortes del indicador y los tooltips.


### 1.0.9 · Mundos, archivo y comisión · aplicada el 2026-11-20

El 20 de noviembre de 2026 el usuario autorizó: «Acepta todos los cambios y realizar las confirmaciones correspondientes de ser neceario». Se publicó Componentes y se aceptaron las actualizaciones en Productos con «Mostrar actualizaciones para todas las páginas» y «Actualizar todo». Figma confirmó «No hay actualizaciones disponibles». La autorización incluye los contadores 1.0.7 y las etiquetas 1.0.8 que antes estaban pendientes.

Recursos publicados: Navegación / Riel de mundos, Archivo / Seleccionar, Formulario / Comisión, Campo con Prefix, las colecciones Primitivos y Color con el amarillo de archivo, y el ajuste pendiente de Listado / Estado. Se conservan los maestros y vínculos remotos.

- Navegación: riel remoto dentro de la composición contextual de Productos; siete mundos centrados, salida abajo y ocho interacciones conservadas. Las vistas explícitas de tooltips se adaptaron al nuevo riel.
- Archivo: doce botones vinculados; solo su carpeta usa amarillo #b58a00. Se conservaron sus IDs, tamaños y reacciones.
- Comisión: cuatro formularios de agregar/editar y tres ejemplos adicionales vinculados. Las tres opciones muestran —, % o S/. El menú participa en auto-layout y reserva espacio; en móvil desplaza el valor hacia abajo. La instancia y su contenedor usan altura adaptable.
- Contadores: 134 campos configurados con límites contractuales; se ocultan en búsqueda sin límite definido, fechas, stock, selección e importes. Producto: nombre/descripción 100, código interno completo 50, EAN-13 13; categoría 50/100 y marca 100/250.
- Etiquetas: fondo azul suave y texto/símbolo oscuros, vinculados a la biblioteca.

Verificación: doce combinaciones de tipo y formulario sin solapamientos; menú contenido en el grupo, anchos 732/350 y alturas abiertas 197/288. Treinta conexiones de comisión válidas en la biblioteca. Revisión visual de biblioteca, listado, importación y configuración. El prototipo representa datos de ejemplo; teclado, API, guardado real, foco y cálculos deben comprobarse al implementar.

[Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=82-159) · [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=39-54). Productos es el consumidor identificado; no se afirma haber actualizado archivos desconocidos. No se modificó código de aplicación.

### 1.0.8 · Etiqueta de selección con contraste · 2026-11-20

Publicados Selección / Etiqueta y Vue Select / Múltiple, que reutiliza la etiqueta. Fondo azul suave #eaf6fd conservado; texto y símbolo × ahora usan color/navy #1a1a35. Contraste calculado: 15,36:1. Propiedades Label y Removable, tamaño, geometría y acción de quitar conservados. Comprobadas cuatro etiquetas dependientes en el maestro múltiple, sin diferencias de texto; ejemplos de etiqueta corta, larga y sin quitar.

En la revisión inicial se identificaron 40 etiquetas y 41 selectores múltiples. La aceptación quedó pendiente en ese momento. El 2026-11-20 el usuario autorizó todos los cambios; se verificó su adopción en Productos durante 1.0.9, con texto y símbolo oscuros. Sin cambios en Vue, CSS ni API.

### 1.0.7 · Contadores internos en Campo · 2026-11-20

Publicada únicamente la actualización de Campo. Añadidos Counter (texto usados / límite) y Show counter (booleano) a las diez variantes de Tipo=Texto, cinco estados y dos dispositivos. Las seis variantes de selección e importe conservan su composición sin contador. Se reutilizan tipografía Ayuda, color muted, estado danger y espacios existentes; no se crean tokens ni estilos paralelos. Se reserva espacio entre valor y contador y se trunca solo la presentación del texto largo.

Comprobadas las alturas originales de 36/44 px, siete ejemplos visibles sin superposición, contador oculto, límites 50/100/250 y EAN-13 de 13 dígitos, límite válido y error 101/100. En Figma el conteo es una propiedad de ejemplo; el cálculo dinámico queda para la implementación con InputText y su contrato. Campo con contador mantiene compatibilidad y su estilo existente.

La revisión inicial identificó 470 Campo, 209 Tipo=Texto. La aceptación quedó pendiente hasta la autorización global del 2026-11-20. Durante 1.0.9 se aceptó la biblioteca y se configuraron 134 contadores según contrato, incluidos Campo con contador existentes; se ocultaron contadores de ejemplo donde no corresponde un límite de texto. Ver el registro de adopción 1.0.9.

### 1.0.6 · Texto oscuro en todos los mensajes en línea · 2026-11-20

Información, Éxito, Atención y Error comparten ahora color/navy (#1a1a35), el mismo tono oscuro que tenían verde y amarillo. Se actualizaron cuatro vínculos de texto; azul y rojo cambiaron de color visible. Los cuatro fondos, contenido, propiedades y geometría permanecen iguales. Revisión visual y comprobación de colores y fondos completadas. Publicado únicamente Mensaje en línea; el cambio pendiente de Listado / Estado se excluyó de esta publicación.

Se inspeccionaron las cinco páginas vigentes de Productos: cero instancias vinculadas de Mensaje en línea. No se aplicaron actualizaciones en consumidores y no hay aceptación pendiente identificada para este componente en Productos. La descripción de publicación lo había indicado provisionalmente como pendiente; esta comprobación posterior registra el estado real. Cualquier consumidor nuevo o adicional requerirá aviso y aceptación antes de aplicar esta versión. La aplicación Vue y sus mensajes no se modificaron.

### 1.0.5 · Estado vacío universal · 2026-11-20

El usuario autorizó «proponer una mejora considerable y aceptar la nueva version». Publicados Listado / Estado y el nuevo Listado / Símbolo. Inicial y Sin resultados usan la misma carpeta abierta genérica, sin cajas ni iconos específicos del submódulo. Jerarquía de símbolo, título, ayuda y acción; ejemplos de Productos y Marcas, búsqueda y error, más revisión a 320 px. Mantener Title y Body explícitos por estado; los valores predeterminados de las propiedades de Figma son compartidos.

Productos tenía tres composiciones sueltas, no instancias del estado compartido. Se migraron a instancias remotas vinculadas, conservando los marcos, los cuatro botones originales y sus conexiones existentes. Las acciones se agrupan en una fila con ajuste de línea. Verificados los símbolos de las tres instancias y los destinos afectados. Importar conserva su estado anterior: el botón no tenía conexión de prototipo; no se añadió una operación nueva. Marcas queda como ejemplo reutilizable en Componentes; no se identificó un archivo de Marcas independiente. Sin cambios en código, API ni permisos.

### 1.0.4 · Texto uniforme de badges · 2026-11-20

El usuario autorizó «ajustar y aprobar la nueva version». Se publicó y aplicó en todas las páginas de Productos. badge/danger/text ahora apunta al mismo primitivo navy (#1a1a35) que Success y Warning; Danger conserva su fondo #fee6e7. El mensaje en línea Error mantiene el rojo oscuro #9f2533 mediante su primitivo existente, separado del token de badge. Publicación: colección Color, Badge y Mensaje en línea. Verificados 925 badges de las páginas vigentes, incluidos siete Danger, sin diferencias de texto ni cambios del fondo rojo. Revisión visual de las tres variantes; Figma terminó sin actualizaciones pendientes en Productos. No se modificó código de aplicación.

### 1.0.3 · Color de iconos de botones · 2026-11-20

Corregidas cuatro variantes de Botón con icono: secundario disponible, hover y foco, y enlace disponible. Los vectores conservan sus instancias y ahora comparten el token de la etiqueta. Verificados los once iconos y loaders de Botón con icono y Botón, la plantilla Excel y cuatro usos en ejemplos de Componentes, incluidos mensajes. Revisión visual de la matriz corregida. Sin cambios de API, geometría ni código de aplicación. La publicación y aceptación se registran por separado; Productos requiere aceptación explícita antes de actualizar.

Biblioteca publicada como 1.0.3; Figma incluyó Botón con icono y su consumidor Swal / Confirmación con alcance. El 20 de noviembre de 2026 el usuario autorizó «si, acepto aplica esta version en todo». Se aceptó la actualización en Productos con «Mostrar actualizaciones para todas las páginas» y «Actualizar todo». Figma completó la operación y dejó de mostrar actualizaciones pendientes. Se inspeccionaron 729 botones vinculados en las páginas vigentes y se compararon 344 pares de icono o loader y etiqueta: cero diferencias de color u opacidad. Las páginas históricas se excluyeron de esta auditoría; la aceptación de biblioteca abarcó todas las páginas. En la carpeta Blapos y su subcarpeta Catálogo e inventario solo se identificaron Componentes y Productos; Productos es el único consumidor identificado. No se modificó código de aplicación.

| **Fecha** | **Versión** | **Componente** | **Cambio** | **Compatibilidad** | **Consumidores revisados** |
| --- | --- | --- | --- | --- | --- |
| 2026-11-19 | 1.0 | Sistema inicial | Centralización y documentación | Conservar identidad de publicación; revisar variantes y propiedades | Productos, escritorio y móvil |
| {fecha} | {versión} | {familia} | {resultado concreto} | {compatible o migración requerida} | {módulos y estados} |

No dar por revisada la aplicación por una captura de Figma. Registrar por separado diseño, navegador y pruebas integradas.

## Antes de publicar

1. Nombres únicos, sin v2/v3 ni duplicados vigentes.
2. Dependencias como instancias; tokens y estilos centrales.
3. Matriz de variantes sin solapamientos; propiedades editables y defaults útiles.
4. Hover, foco, deshabilitado, carga, éxito y error aplicables.
5. Color con texto o señal adicional; contraste de texto normal objetivo 4,5:1.
6. Área táctil mínima de 44 px y foco visible.
7. Textos largos, contenido vacío y anchos representativos.
8. En tablas: cabeceras y celdas con la misma cuadrícula.
9. En formularios: etiquetas, asterisco, contador interno y error.
10. Guía, catálogo, mensaje y registro actualizados.
11. Publicar Componentes y avisar al usuario con versión, cambios y archivos afectados. Esperar su autorización explícita antes de aceptar actualizaciones en consumidores, según la [regla de aviso y aceptación](README.md#aviso-y-aceptación-de-actualizaciones).
12. Verificar que no existan copias locales de maestros compartidos en módulos.

## Seguimiento de aceptación de biblioteca

Por cada publicación, registrar los archivos afectados conocidos y completar la tabla sin asumir que publicar actualiza automáticamente los consumidores. Si faltan consumidores por identificar, declararlo en el aviso.

| **Versión publicada** | **Archivo consumidor y enlace** | **Estado** | **Autorización del usuario** | **Verificación o confirmación** |
| --- | --- | --- | --- | --- |
| 1.0.9 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «Acepta todos los cambios» | Riel, doce botones de archivo y siete comisiones vinculadas; doce estados sin solapamientos; 2.374 conexiones válidas |
| 1.0.8 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «Acepta todos los cambios» | Actualización para todas las páginas; ver adopción 1.0.9 y configuración por dato |
| 1.0.7 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «Acepta todos los cambios» | Actualización para todas las páginas; ver adopción 1.0.9 y configuración por dato |
| 1.0.5 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «proponer una mejora considerable y aceptar la nueva version» | Tres estados vinculados; cuatro botones conservados; símbolos y destinos verificados; ejemplos de escritorio y 320 px |
| 1.0.4 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «ajustar y aprobar la nueva version» | Todas las páginas actualizadas; 925 badges verificados, siete Danger, cero diferencias; fondo rojo conservado |
| 1.0.3 | [Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos) | aplicada y verificada | 2026-11-20: «si, acepto aplica esta version en todo»; todos los consumidores de 1.0.3 | Actualización en todas las páginas; 729 botones inspeccionados, 344 pares de icono/texto sin diferencias; sin avisos pendientes |
| {versión} | {archivo y enlace} | {pendiente de aceptación / autorizada / aplicada y verificada / aplazada} | {referencia a la respuesta y alcance aprobado} | {fecha y comprobación, o confirmación del usuario} |

Esta regla se registró el 19 de noviembre de 2026 por solicitud del usuario y rige las próximas actualizaciones. No añade una publicación nueva de componentes ni una aceptación pendiente por este cambio documental.

## Al implementar

Probar teclado y lector de pantalla; foco atrapado y restaurado en modal, Escape, navegación de opciones y zoom 200 %. Comprobar red lenta, error, resultado incierto, datos largos, permisos y tenant. Mantener precisión y validación de backend. No introducir otra biblioteca para resolver una variante visual.

El orden ascendente/descendente del prototipo no cambia el backend actual. Los loaders de ejemplo no crean peticiones ni tiempos contractuales. Publicar Figma no despliega cambios en Blapos.


## Verificación de la centralización

- Biblioteca Componentes publicada en Blapos; Productos dejó de publicarse como biblioteca para evitar estilos y maestros compartidos alternativos.
- Nueve páginas de biblioteca, 39 familias y piezas reutilizables, iconografía vectorial, 71 tokens, seis estilos Source Sans 3 y dos elevaciones. Componentes no depende de variables ni estilos de Productos.
- Productos mantiene cero estilos tipográficos locales, cero elevaciones locales y ningún maestro compartido local. Conserva sus composiciones específicas y las cinco anchuras de columna del listado; la exploración anterior queda en archivo y fuera de publicación.
- Se reutilizaron 83 títulos de página, 97 resúmenes de resultados, 657 controles de paginación y cinco celdas del encabezado general. Se conservaron contenido, tamaños y acciones de navegación. Las marcas seleccionadas y los mensajes SweetAlert se revisaron al consolidar variantes.
- Auditoría del prototipo: 122 vistas y 2,475 conexiones, sin destinos de navegación inválidos.
- Revisión visual: portada, fundamentos, botones, campos, navegación, datos, mensajes y estructura común; listado, formulario, móvil e inventario de Productos.
- El color de ayuda pasó a #607086: contraste de 4,70:1 sobre #f4f7fd y 4,59:1 sobre #eaf6fd. Badges y tooltips comprobados por encima de 4,5:1.
- Documentación revisada con git diff --check. No se ejecutó build porque esta entrega modifica Figma y documentación.
- Pendientes de implementación: teclado real, lector de pantalla, foco modal, zoom 200 %, red, API, permisos, validación y descargas. El prototipo usa datos de ejemplo; no sustituye estas pruebas.

### Versiones publicadas

1.0: biblioteca central y estructura inicial. 1.0.1: carga en botones, propiedades y acciones expuestas, obligatoriedad y eliminación de dependencias. 1.0.2: contraste del texto de ayuda.

