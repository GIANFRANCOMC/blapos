# Blapos · Publicación 1.0.9

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

## Contrato de reutilización

Primero modificar y documentar el maestro en Componentes. Publicar, avisar y obtener aceptación para el alcance consumidor; esta entrega ya está autorizada. Luego aplicar la actualización y usar instancias remotas en Productos. Mantener las composiciones específicas, rutas, permisos y reglas de negocio en el submódulo.

Comisión: Dispositivo=Escritorio/Móvil, Tipo=Sin comisión/Porcentaje/Monto fijo por unidad y Desplegable=Cerrado/Abierto. El menú debe participar en auto-layout. Evitar posicionarlo encima del siguiente campo. Tanto la instancia como su contenedor deben tener altura adaptable. Los valores 10 y 5.00 son ejemplos; conservar reinicio al cambiar tipo, máximo 100 en porcentaje y cálculo fijo por unidad. No concatenar la unidad al valor de API.

Solo el icono de Seleccionar archivo es amarillo; la etiqueta conserva el texto oscuro. Es la excepción autorizada a icono = color del texto.

Verificación ampliada: 2.374 acciones de navegación y cambio de variante revisadas en las páginas vigentes; cero destinos inválidos, siete instancias de comisión y cero grupos antiguos. La revisión es del diseño Figma; la aplicación Vue no se modificó.
