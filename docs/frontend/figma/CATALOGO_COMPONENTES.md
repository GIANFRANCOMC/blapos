# Blapos · Catálogo de componentes

Cada enlace abre el maestro central. Cambiar textos, iconos y variantes mediante propiedades; mantener las instancias vinculadas. Los valores predeterminados son ejemplos, no reglas de negocio.

## Acciones y carga de listados

[Opción de menú](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-118) expone solo Label, sin Icon. El disparador de menú conserva su chevron o tres puntos. [Listado / Carga](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=123-78) reutiliza el indicador de 32 px, altura mínima 200 y Label contextual. En código corresponde a `Figma/FgListLoading.vue`; no ampliar el spinner de todos los botones para resolver una carga de tabla.

[Ordenar listado](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-859) representa selección única A–Z/Z–A respaldada por la API. Reiniciar página conservando filtros aplicados y usar el mismo orden al exportar.

## Scroll y foco

- [Scroll](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-94): Dirección Vertical/Horizontal × Estado Reposo/Hover/Arrastre. Thumb 6 px dentro de canal 10 px. Reutilizar como instancia para representar scroll nativo, sin añadir una segunda barra a un panel que ya la contiene.
- [Foco de sección](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-107): Reposo/Teclado; Título y Ayuda editables. Shift aislado conserva Reposo; Tab y Shift+Tab usan Teclado. No se elimina la navegación accesible.
- [Guía visual](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=115-119) · [Consumidor en Productos](https://www.figma.com/design/TxqVm9uFF7V8yZe64Po6SQ/Productos?node-id=253-36372). Publicados en Componentes; no crear variantes locales de estas familias en cada submódulo.


## Personalización de contenido

Barra superior expone **User**, **Company** e **Initials**. Cabecera y pie de modal exponen sus botones anidados para cambiar Label y el icono sin separar la instancia. La confirmación con alcance también expone las dos decisiones.

En Vue Select único, al usar **Selección=Con valor**, configurar **Value** con la opción real; al vaciar, usar el texto de selección correspondiente. No dejar el placeholder como si fuera un valor seleccionado. En Swal, configurar **Title** y **Body** para la operación, además de elegir su estado. En Badge, configurar **Tone** y **Label** explícitamente.


## Botón con icono

El icono y el indicador de carga deben usar exactamente el mismo token de color y opacidad que la etiqueta del botón en cada estado. Primario: blanco; secundario: color/text; enlace: color/action; plantilla Excel: su texto verde. Al intercambiar Icon, conservar esta regla; no elegir un color independiente por símbolo. En implementación, heredar `currentColor` para relleno o trazo según el icono.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-81) · 02 · Botones

Correspondencia de implementación: clases y controles comunes del sistema de diseño. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tono=Primario, Estado=Disponible; Tono=Secundario, Estado=Disponible; Tono=Enlace, Estado=Disponible; Tono=Primario, Estado=Hover; Tono=Primario, Estado=Foco; Tono=Primario, Estado=Cargando; Tono=Secundario, Estado=Hover; Tono=Secundario, Estado=Foco; Tono=Secundario, Estado=Cargando.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Icon | INSTANCE_SWAP | 1:337 |
| Label | TEXT | Agregar producto |
| Tono | VARIANT | Primario, Secundario, Enlace |
| Estado | VARIANT | Hover, Foco, Cargando, Disponible |

## Botón

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-82) · 02 · Botones

Correspondencia de implementación: clases y controles comunes del sistema de diseño. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tono=Primario, Estado=Disponible; Tono=Secundario, Estado=Disponible; Tono=Enlace, Estado=Disponible; Tono=Primario, Estado=Deshabilitado; Tono=Primario, Estado=Hover; Tono=Primario, Estado=Foco; Tono=Primario, Estado=Cargando; Tono=Secundario, Estado=Hover; Tono=Secundario, Estado=Foco; Tono=Secundario, Estado=Cargando.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Guardar producto |
| Tono | VARIANT | Primario, Secundario, Enlace |
| Estado | VARIANT | Disponible, Deshabilitado, Hover, Foco, Cargando |

## Botón de icono

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-116) · 02 · Botones

Correspondencia de implementación: clases y controles comunes del sistema de diseño. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Icon | INSTANCE_SWAP | 1:91 |

## Botón de plantilla Excel

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-609) · 02 · Botones

Correspondencia de implementación: clases y controles comunes del sistema de diseño. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Plantilla Excel |

## Campo

Desde la versión 1.0.9 publicada y aplicada en Productos, Campo expone Prefix (TEXT, ejemplo S/), usado solo en Tipo=Importe. Permite % o el signo de moneda sin concatenarlos al valor. Formulario / Comisión reutiliza esta propiedad y reserva espacio para las opciones abiertas; su altura y la del contenedor deben ser adaptables. Consulta el registro de calidad.

En Tipo=Texto, mostrar el contador dentro del control, alineado a la derecha y centrado verticalmente: usados / límite. Reservar su espacio con auto-layout; el valor puede truncarse visualmente sin cambiar su contenido. Counter es texto configurable y Show counter permite ocultarlo; ambos se aplican en las diez variantes de texto de escritorio y móvil. No se añaden contadores a Selección ni Importe. Conservar el mismo estilo del componente Campo con contador existente y no mostrar dos contadores.

En Figma configurar Value y Counter juntos: no hay cálculo automático al editar el texto. El ejemplo vacío muestra un placeholder y cuenta cero. Llegar al límite es válido; usar el estado Error solo ante un dato inválido, no por alcanzar el máximo. Si el campo no tiene un límite contractual identificado, ocultar el contador hasta confirmarlo. No inventar un límite universal de 100.

En implementación reutilizar InputText y showCharCounter existentes, calcular desde el valor según la regla de conteo del campo y asociar el contador con aria-describedby. No anunciar cada pulsación ni reemplazar los errores de validación. Conservar maxlength y el contrato de backend. Estos cambios son de Figma y documentación.

| **Dato verificado** | **Límite** |
| --- | --- |
| Producto: nombre / descripción comercial | 100 / 100 |
| Producto: código interno completo | 50 |
| Producto: código de barras EAN-13 | 13 dígitos |
| Categoría: nombre / descripción | 50 / 100 |
| Marca: nombre / descripción | 100 / 250 |

Fuentes: ProductRequest.php, CategoryRequest.php, BrandRequest.php y ProductEditor.vue. El código interno se valida completo, incluido su prefijo. [Ejemplos de Campo](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=64-87): vacío, contenido, foco, límites distintos, límite alcanzado y error; revisión adicional a 240 px de ancho.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-83) · 03 · Campos y selección

Correspondencia de implementación: controles compartidos y formularios del módulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Tipo=Texto, Estado=Lleno; Dispositivo=Escritorio, Tipo=Texto, Estado=Vacío; Dispositivo=Escritorio, Tipo=Texto, Estado=Foco; Dispositivo=Escritorio, Tipo=Texto, Estado=Error; Dispositivo=Escritorio, Tipo=Texto, Estado=Lectura; Dispositivo=Escritorio, Tipo=Selección, Estado=Lleno; Dispositivo=Escritorio, Tipo=Importe, Estado=Lleno; Dispositivo=Escritorio, Tipo=Importe, Estado=Vacío; Dispositivo=Móvil, Tipo=Texto, Estado=Lleno; Dispositivo=Móvil, Tipo=Texto, Estado=Vacío; Dispositivo=Móvil, Tipo=Texto, Estado=Foco; Dispositivo=Móvil, Tipo=Texto, Estado=Error; Dispositivo=Móvil, Tipo=Texto, Estado=Lectura; Dispositivo=Móvil, Tipo=Selección, Estado=Lleno; Dispositivo=Móvil, Tipo=Importe, Estado=Lleno; Dispositivo=Móvil, Tipo=Importe, Estado=Vacío.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Etiqueta |
| Action | TEXT | Agregar |
| Show action | BOOLEAN | true |
| Value | TEXT | Valor |
| Help | TEXT | Ayuda |
| Show help | BOOLEAN | true |
| Counter | TEXT | 17 / 100; usar el límite real de cada dato |
| Show counter | BOOLEAN | true; solo aparece en Tipo=Texto |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Tipo | VARIANT | Texto, Selección, Importe |
| Estado | VARIANT | Lleno, Vacío, Foco, Error, Lectura |

## Checkbox

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-84) · 03 · Campos y selección

Correspondencia de implementación: controles compartidos y formularios del módulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Estado=Sin seleccionar; Estado=Seleccionado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Precio incluye IGV |
| Estado | VARIANT | Sin seleccionar, Seleccionado |

## Skeleton / Línea, Control y Catálogo

[Catálogo](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=142-118) · [Ejemplo y guía](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=142-119).

Primitivas Línea y Control con tokens compartidos. Catálogo ofrece Dispositivo=Escritorio/Móvil y reutiliza las cabeceras y el loader existentes. Código: `CatalogListSkeleton`, `FgSkeletonFilters`, `InitialLoadTransition` y `Shared/skeleton.css`. Mantener el encabezado real, filtros, orden y anchos de columna. Solo durante los parámetros iniciales; las consultas posteriores usan el loader de tabla. Transición de 180 ms y entrada de 3 px, sin pulso ni desplazamiento con movimiento reducido.

## Vue Select / Múltiple

Regla transversal de campos no editables: Campo en Estado=Lectura y selects en Estado=Deshabilitado comparten los tokens `disabled/*`. Usar el mismo fondo, borde y texto gris en texto, número, importe, fecha y descripción, incluidos contador y prefijos; la implementación común está en `Shared/unavailable-controls.css`.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-86) · 03 · Campos y selección

Correspondencia de implementación: vue-select existente; adaptador y patrones remotos de Shared. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Selección=Vacía; Dispositivo=Escritorio, Selección=Con valores; Dispositivo=Móvil, Selección=Vacía; Dispositivo=Móvil, Selección=Con valores.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Categorías |
| Action | TEXT | Agregar |
| Show action | BOOLEAN | true |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Selección | VARIANT | Vacía, Con valores |
| Estado | VARIANT | Disponible, Deshabilitado |

Las combinaciones anteriores existen en ambos estados. Deshabilitado reutiliza los tokens `disabled/bg`, `disabled/text`, `disabled/border` y `disabled/tag-bg`; toda la superficie del control es gris, incluidas las etiquetas.

## Vue Select / Único

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-87) · 03 · Campos y selección

Correspondencia de implementación: vue-select existente; adaptador y patrones remotos de Shared. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Selección=Vacía; Dispositivo=Escritorio, Selección=Con valor; Dispositivo=Móvil, Selección=Vacía; Dispositivo=Móvil, Selección=Con valor.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Marca |
| Action | TEXT | Agregar |
| Show action | BOOLEAN | true |
| Value | TEXT | Selecciona una marca |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Selección | VARIANT | Vacía, Con valor |
| Estado | VARIANT | Disponible, Deshabilitado |

Las combinaciones anteriores existen en ambos estados. Deshabilitado comparte el tratamiento gris del múltiple y conserva las dimensiones del control disponible.

## Vue Select / Opción

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-88) · 03 · Campos y selección

Correspondencia de implementación: vue-select existente; adaptador y patrones remotos de Shared. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Estado=Reposo; Estado=Seleccionada; Estado=Resaltada.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Opción |
| Selected | BOOLEAN | false |
| Estado | VARIANT | Reposo, Seleccionada, Resaltada |

## Campo con contador

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-90) · 03 · Campos y selección

Correspondencia de implementación: controles compartidos y formularios del módulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Tipo=Texto, Estado=Reposo; Dispositivo=Escritorio, Tipo=Texto, Estado=Foco; Dispositivo=Escritorio, Tipo=Texto, Estado=Error; Dispositivo=Escritorio, Tipo=Descripción, Estado=Reposo; Dispositivo=Escritorio, Tipo=Descripción, Estado=Foco; Dispositivo=Escritorio, Tipo=Descripción, Estado=Error; Dispositivo=Móvil, Tipo=Texto, Estado=Reposo; Dispositivo=Móvil, Tipo=Texto, Estado=Foco; Dispositivo=Móvil, Tipo=Texto, Estado=Error; Dispositivo=Móvil, Tipo=Descripción, Estado=Reposo; Dispositivo=Móvil, Tipo=Descripción, Estado=Foco; Dispositivo=Móvil, Tipo=Descripción, Estado=Error.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Value | TEXT | Valor |
| Counter | TEXT | 0 / 100 |
| Label | TEXT | Nombre del producto * |
| Error | TEXT | Revisa este dato. |
| Show error | BOOLEAN | false |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Tipo | VARIANT | Texto, Descripción |
| Estado | VARIANT | Reposo, Foco, Error |

## Desplegable

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-295) · 03 · Campos y selección

Correspondencia de implementación: controles compartidos y formularios del módulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Códigos del producto |
| Detail | TEXT | Generados automáticamente |

## Campo / Importe con error

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-303) · 03 · Campos y selección

Correspondencia de implementación: controles compartidos y formularios del módulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Precio de venta * |
| Value | TEXT | 0.00 |
| Help | TEXT | El precio debe ser mayor que cero. |

## Vue Select / Indicador abierto

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-717) · 03 · Campos y selección

Correspondencia de implementación: vue-select existente; adaptador y patrones remotos de Shared. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Selección / Etiqueta

Fondo azul suave #eaf6fd (color/soft), texto oscuro #1a1a35 (color/navy) y × del mismo color del texto. Contraste texto/fondo: 15,36:1. Mantener Label y Removable editables; no cambiar el color por categoría ni crear estilos locales. La acción de quitar conserva su comportamiento y, en implementación, necesita nombre accesible y área de activación suficiente.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=13-22) · 03 · Campos y selección

Correspondencia de implementación: vue-select existente; adaptador y patrones remotos de Shared. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Opción |
| Removable | BOOLEAN | true |

## Pestaña

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-85) · 04 · Navegación

Correspondencia de implementación: Shared/AppTabs.vue. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Estado=Reposo; Dispositivo=Escritorio, Estado=Activa; Dispositivo=Móvil, Estado=Reposo; Dispositivo=Móvil, Estado=Activa.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Datos del producto |
| Icon | INSTANCE_SWAP | 1:325 |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Estado | VARIANT | Reposo, Activa |

## Navegación / Submódulo

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-89) · 04 · Navegación

Correspondencia de implementación: shell y navegación System; implementar foco y tooltips compartidos. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Estado=Reposo; Estado=Activo.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Productos |
| Estado | VARIANT | Reposo, Activo |

## Marca Blapos

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-103) · 04 · Navegación

Correspondencia de implementación: marca e iconografía compartida. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Isotipo

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-105) · 04 · Navegación

Correspondencia de implementación: marca e iconografía compartida. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Opción de menú

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-118) · 04 · Navegación

Correspondencia de implementación: marca e iconografía compartida. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Icon | INSTANCE_SWAP | 1:75 |
| Label | TEXT | Editar producto |

## Barra superior

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-121) · 04 · Navegación

Correspondencia de implementación: shell y navegación System; implementar foco y tooltips compartidos. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Navegación / Riel de mundos

[Maestro](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=80-85). Riel de 64 px, marca arriba, siete mundos centrados verticalmente y salida abajo. Mundo y sus tooltips son instancias reutilizadas y expuestas. Ajustar el activo en la instancia del mundo correspondiente. Centrado comprobado a 600, 720 y 1120 px de alto. Conservar rutas, permisos y reacciones del consumidor; prever desplazamiento si no hay altura suficiente.

## Archivo / Seleccionar

[Maestro](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=80-30). Reutiliza el botón secundario con icono; altura de 36 px. Solo la carpeta de Seleccionar archivo es amarilla #b58a00 mediante color/file-select-icon; el texto conserva el tono oscuro. Es una excepción expresa a la regla icono = texto. No cambiar el maestro del icono ni colorear otros botones. El selector nativo de archivo y su validación pertenecen al consumidor.

## Formulario / Comisión

[Maestro](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=82-159). Doce variantes: Dispositivo=Escritorio/Móvil, Tipo=Sin comisión/Porcentaje/Monto fijo por unidad y Desplegable=Cerrado/Abierto. Reutiliza Vue Select único, sus opciones y Campo. Existen 30 conexiones de cambio de variante. El selector no permite limpiar su valor, como en el contrato de implementación.

Sin comisión muestra — y valor de lectura. Porcentaje muestra %; monto fijo muestra el signo de moneda, S/ en los ejemplos. Prefix es una nueva propiedad de Campo para Tipo=Importe y permite cambiar la unidad sin crear otro campo. El tipo se corresponde con none, percentage y fixed. Los valores 10 y 5.00 son datos de demostración, no valores predeterminados del negocio. Conservar el reinicio de valor al cambiar tipo, el máximo 100 para porcentaje y el cálculo por unidad de monto fijo. No concatenar la unidad al dato que se envía a la API.

El grupo se adapta al ancho: verificado a 732 px en escritorio y 350 px en móvil, con el desplegable del mismo ancho que su selector. La variante móvil base usa 320 px. Los campos y las acciones específicas de Productos se integran después de publicar; aún no se han reemplazado.

## Navegación / Mundo

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-721) · 04 · Navegación

Correspondencia de implementación: shell y navegación System; implementar foco y tooltips compartidos. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tipo=Normal, Estado=Reposo; Tipo=Normal, Estado=Tooltip; Tipo=Activo, Estado=Reposo; Tipo=Activo, Estado=Tooltip; Tipo=Salir, Estado=Reposo; Tipo=Salir, Estado=Tooltip.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Icon | INSTANCE_SWAP | 1:349 |
| Tooltip | TEXT | Inicio |
| Tipo | VARIANT | Normal, Activo, Salir |
| Estado | VARIANT | Reposo, Tooltip |

## Tooltip

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=11-22) · 04 · Navegación

Correspondencia de implementación: shell y navegación System; implementar foco y tooltips compartidos. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tono=Mundo; Tono=Salir.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Nombre del mundo |
| Tono | VARIANT | Mundo, Salir |

## Badge

Success, Warning y Danger usan el mismo texto oscuro #1a1a35, con sus respectivos tokens semánticos vinculados al primitivo navy. El fondo distingue cada tono; Danger conserva #fee6e7. Mantener Label explícito para no depender solo del color. El texto de los mensajes en línea sigue su propia regla compartida color/navy.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-850) · 05 · Datos y listados

Correspondencia de implementación: StatusBadge.vue. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tone=Success; Tone=Warning; Tone=Danger.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Estado |
| Tone | VARIANT | Danger, Success, Warning |

## Indicador obligatorio

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-857) · 05 · Datos y listados

Correspondencia de implementación: marca e iconografía compartida. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Ordenar listado

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-859) · 05 · Datos y listados

Correspondencia de implementación: composición del listado; dirección de orden pendiente en API. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Ordenar por |
| Value | TEXT | Nombre A–Z |

## Tabla / Celda de cabecera

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=10-3) · 05 · Datos y listados

Correspondencia de implementación: Shared/ResponsiveTable.vue y composición del submódulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Alineación=Izquierda; Alineación=Centro; Alineación=Derecha.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Encabezado |
| Alineación | VARIANT | Izquierda, Centro, Derecha |

## Listado / Resultados

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=10-10) · 05 · Datos y listados

Correspondencia de implementación: Shared/ResponsiveTable.vue y composición del submódulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Mostrando 1–10 de 124 registros |

## Listado / Estado

Símbolo universal de carpeta abierta para Inicial y Sin resultados, igual en Productos, Marcas y los demás submódulos. Error usa un aviso de carga, no el icono de la entidad. Configurar siempre Estado, Title y Body: los textos predeterminados de Figma son compartidos y no cambian automáticamente al elegir otra variante. Personalizar el Label de la acción anidada expuesta; Show action depende de los permisos. No cambiar el símbolo según el submódulo. En listados con dos acciones, ocultar la acción interna y componer los botones existentes junto a la instancia, conservando sus eventos.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=10-12) · 05 · Datos y listados

Correspondencia de implementación: Shared/ResponsiveTable.vue y composición del submódulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Estado=Inicial; Estado=Sin resultados; Estado=Error.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | No hay registros |
| Body | TEXT | Agrega el primer registro para comenzar. |
| Show action | BOOLEAN | true |
| Estado | VARIANT | Inicial, Sin resultados, Error |

## Listado / Símbolo

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=52-30) · 05 · Datos y listados

Variantes Tipo=Vacío y Tipo=Error. Contenedor de 56 px, radio y fondo vinculados a tokens; iconos Font Awesome existentes como instancias. Vacío usa folder-open; Error usa circle-exclamation. Es una dependencia de Listado / Estado, no una ilustración específica de Productos.

[Ejemplos Productos, Marcas, filtros y recuperación](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=53-37). Ejemplo móvil de 320 px disponible junto a la composición.

## Paginación / Botón

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=10-34) · 05 · Datos y listados

Correspondencia de implementación: Shared/ResponsiveTable.vue y composición del submódulo. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Estado=Disponible; Estado=Actual; Estado=Deshabilitado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | 1 |
| Estado | VARIANT | Disponible, Actual, Deshabilitado |

## Swal / Resultado

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=7-91) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Dispositivo=Escritorio, Estado=Cargando; Dispositivo=Escritorio, Estado=Éxito; Dispositivo=Escritorio, Estado=Error; Dispositivo=Escritorio, Estado=Atención; Dispositivo=Móvil, Estado=Cargando; Dispositivo=Móvil, Estado=Éxito; Dispositivo=Móvil, Estado=Error; Dispositivo=Móvil, Estado=Atención.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Guardando producto… |
| Body | TEXT | Espera un momento, por favor. |
| Dispositivo | VARIANT | Escritorio, Móvil |
| Estado | VARIANT | Cargando, Éxito, Error, Atención |

## Confirmación / Botón peligro

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-822) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Label | TEXT | Cerrar sesión |

## Swal / Confirmación con alcance

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-824) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | ¿Exportar productos? |
| Body | TEXT | Se descargará un archivo Excel con los productos del listado. |
| Scope | TEXT | 124 productos · Listado completo |

## Swal / Cerrar sesión

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-837) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Swal / Consulta en curso

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-844) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Consultando inventario… |
| Context | TEXT | Café molido 250 g |

## Mensaje en línea

Información, Éxito, Atención y Error usan el mismo texto oscuro #1a1a35, vinculado a color/navy. El fondo conserva su tono semántico. No cambiar el texto a azul o rojo por el tipo de mensaje; conservar contenido, propiedades y geometría.

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=14-90) · 06 · Mensajes y feedback

Correspondencia de implementación: System/Helpers/Alerts.js y SweetAlert2 existentes. Esta entrada no afirma que exista un mapeo Code Connect publicado.

Variantes: Tono=Información; Tono=Éxito; Tono=Atención; Tono=Error.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Message | TEXT | Explica qué pasó y cómo continuar. |
| Tono | VARIANT | Información, Éxito, Atención, Error |

## Título de página

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=8-2) · 07 · Estructura de submódulos

Correspondencia de implementación: estructura compartida y ProductEditor como consumidor. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Nombre del submódulo |
| Description | TEXT | Describe la tarea principal en una frase. |
| Show description | BOOLEAN | true |

## Cabecera de modal

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=8-5) · 07 · Estructura de submódulos

Correspondencia de implementación: estructura compartida y ProductEditor como consumidor. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Agregar registro |

## Pie de modal

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=8-11) · 07 · Estructura de submódulos

Correspondencia de implementación: estructura compartida y ProductEditor como consumidor. Esta entrada no afirma que exista un mapeo Code Connect publicado.

## Panel

[Maestro en Componentes](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=8-17) · 07 · Estructura de submódulos

Correspondencia de implementación: estructura compartida y ProductEditor como consumidor. Esta entrada no afirma que exista un mapeo Code Connect publicado.

| **Propiedad** | **Tipo** | **Valores o ejemplo** |
| --- | --- | --- |
| Title | TEXT | Título de sección |
| Content | SLOT | null |

## Iconografía

Font Awesome vectorial: 155 maestros de símbolos y roles. Iconos blancos, de texto, de acción y secundarios permiten conservar el color del label. Reutilizar por INSTANCE_SWAP; no agregar fuentes ni librerías de iconos por submódulo.

| **Icono / rol** | **Maestro** |
| --- | --- |
| Icono / file-arrow-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-61) |
| Icono / bell | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-63) |
| Icono / bars | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-65) |
| Icono / gear | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-67) |
| Icono / chevron-right | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-69) |
| Icono / chevron-left | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-71) |
| Icono / triangle-exclamation | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-73) |
| Icono / pen | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-75) |
| Icono / book | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-77) |
| Icono / cart-shopping | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-79) |
| Icono / folder-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-81) |
| Icono / users | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-83) |
| Icono / file-arrow-up | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-85) |
| Icono / barcode | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-87) |
| Icono / box-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-89) |
| Icono / ellipsis-vertical | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-91) |
| Icono / chart-simple | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-93) |
| Icono / magnifying-glass | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-95) |
| Icono / house | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-97) |
| Icono / star | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-99) |
| Icono / arrow-right-from-bracket | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-101) |
| Icono / Desplegado | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-300) |
| Icono / Fuente / circle-info | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-317) |
| Icono / Fuente / xmark | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-319) |
| Icono / Fuente / sliders | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-321) |
| Icono / Fuente / chevron-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-323) |
| Icono / Fuente / box | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-325) |
| Icono / Fuente / trash | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-327) |
| Icono / Fuente / check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-329) |
| Icono / Fuente / plus | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-331) |
| Icono / Fuente / warehouse | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-333) |
| Icono / Fuente / rotate | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-335) |
| Icono / white / plus | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-337) |
| Icono / white / magnifying-glass | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-339) |
| Icono / white / file-arrow-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-341) |
| Icono / white / file-arrow-up | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-343) |
| Icono / white / chevron-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-345) |
| Icono / white / folder-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-347) |
| Icono / white / house | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-349) |
| Icono / white / cart-shopping | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-351) |
| Icono / white / users | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-353) |
| Icono / white / book | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-355) |
| Icono / white / chart-simple | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-357) |
| Icono / white / gear | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-359) |
| Icono / white / arrow-right-from-bracket | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-361) |
| Icono / white / warehouse | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-363) |
| Icono / white / right-left | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-365) |
| Icono / white / list-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-367) |
| Icono / white / tags | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-369) |
| Icono / white / tag | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-371) |
| Icono / white / utensils | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-373) |
| Icono / white / id-card | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-375) |
| Icono / white / bell-concierge | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-377) |
| Icono / white / cube | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-379) |
| Icono / white / box | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-381) |
| Icono / white / circle-notch | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-383) |
| Icono / white / circle-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-385) |
| Icono / white / circle-exclamation | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-387) |
| Icono / white / check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-389) |
| Icono / white / xmark | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-391) |
| Icono / text / plus | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-393) |
| Icono / text / magnifying-glass | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-395) |
| Icono / text / file-arrow-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-397) |
| Icono / text / file-arrow-up | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-399) |
| Icono / text / chevron-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-401) |
| Icono / text / folder-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-403) |
| Icono / text / house | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-405) |
| Icono / text / cart-shopping | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-407) |
| Icono / text / users | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-409) |
| Icono / text / book | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-411) |
| Icono / text / chart-simple | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-413) |
| Icono / text / gear | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-415) |
| Icono / text / arrow-right-from-bracket | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-417) |
| Icono / text / warehouse | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-419) |
| Icono / text / right-left | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-421) |
| Icono / text / list-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-423) |
| Icono / text / tags | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-425) |
| Icono / text / tag | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-427) |
| Icono / text / utensils | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-429) |
| Icono / text / id-card | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-431) |
| Icono / text / bell-concierge | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-433) |
| Icono / text / cube | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-435) |
| Icono / text / box | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-437) |
| Icono / text / circle-notch | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-439) |
| Icono / text / circle-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-441) |
| Icono / text / circle-exclamation | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-443) |
| Icono / text / check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-445) |
| Icono / text / xmark | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-447) |
| Icono / action / plus | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-449) |
| Icono / action / magnifying-glass | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-451) |
| Icono / action / file-arrow-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-453) |
| Icono / action / file-arrow-up | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-455) |
| Icono / action / chevron-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-457) |
| Icono / action / folder-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-459) |
| Icono / action / house | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-461) |
| Icono / action / cart-shopping | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-463) |
| Icono / action / users | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-465) |
| Icono / action / book | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-467) |
| Icono / action / chart-simple | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-469) |
| Icono / action / gear | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-471) |
| Icono / action / arrow-right-from-bracket | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-473) |
| Icono / action / warehouse | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-475) |
| Icono / action / right-left | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-477) |
| Icono / action / list-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-479) |
| Icono / action / tags | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-481) |
| Icono / action / tag | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-483) |
| Icono / action / utensils | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-485) |
| Icono / action / id-card | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-487) |
| Icono / action / bell-concierge | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-489) |
| Icono / action / cube | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-491) |
| Icono / action / box | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-493) |
| Icono / action / circle-notch | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-495) |
| Icono / action / circle-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-497) |
| Icono / action / circle-exclamation | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-499) |
| Icono / action / check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-501) |
| Icono / action / xmark | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-503) |
| Icono / muted / plus | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-505) |
| Icono / muted / magnifying-glass | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-507) |
| Icono / muted / file-arrow-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-509) |
| Icono / muted / file-arrow-up | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-511) |
| Icono / muted / chevron-down | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-513) |
| Icono / muted / folder-open | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-515) |
| Icono / muted / house | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-517) |
| Icono / muted / cart-shopping | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-519) |
| Icono / muted / users | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-521) |
| Icono / muted / book | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-523) |
| Icono / muted / chart-simple | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-525) |
| Icono / muted / gear | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-527) |
| Icono / muted / arrow-right-from-bracket | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-529) |
| Icono / muted / warehouse | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-531) |
| Icono / muted / right-left | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-533) |
| Icono / muted / list-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-535) |
| Icono / muted / tags | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-537) |
| Icono / muted / tag | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-539) |
| Icono / muted / utensils | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-541) |
| Icono / muted / id-card | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-543) |
| Icono / muted / bell-concierge | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-545) |
| Icono / muted / cube | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-547) |
| Icono / muted / box | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-549) |
| Icono / muted / circle-notch | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-551) |
| Icono / muted / circle-check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-553) |
| Icono / muted / circle-exclamation | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-555) |
| Icono / muted / check | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-557) |
| Icono / muted / xmark | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-559) |
| Icono / Plantilla | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-607) |
| Swal / Icono loading | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-612) |
| Swal / Icono success | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-618) |
| Swal / Icono error | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-625) |
| Swal / Icono warning | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-632) |
| Swal / Icono loading | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-639) |
| Swal / Icono success | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-645) |
| Swal / Icono error | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-652) |
| Swal / Icono warning | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-659) |
| Icono / Caja con alerta | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-666) |
| Icono / Apagado | [Abrir](https://www.figma.com/design/1Iq6LBNSmds7kztfOdiDSD/Componentes?node-id=1-719) |
