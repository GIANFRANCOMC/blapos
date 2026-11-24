# Estructuras de pantalla

## Marco de trabajo

El navbar ocupa todo el ancho sobre el riel oscuro y el panel de submódulos. Agrupa el isotipo de Inicio con el control del panel a la izquierda, el buscador queda centrado y las acciones de cuenta van a la derecha. El riel permanece visible en escritorio y tablet. El control de panel abre o cierra los submódulos desde 768 px y toda la navegación lateral en móvil. El breadcrumb ubica el contenido. [Navegación tenant](TENANT_NAVIGATION.md) · [Menu.js](../../../resources/js/System/Layout/Menu.js).

## Composición según la tarea

| Pantalla | Orden de lectura y superficie |
| --- | --- |
| Listado administrativo | Ubicación, filtros y acciones, tabla, paginador y total. Los controles comparten fila cuando hay espacio, en orden de lectura. |
| Alta o edición breve | Modal compartida, campos agrupados, validación junto al control y pie de acciones. Usa una página para formularios largos que requieran contexto persistente. |
| Detalle de traslado | Datos de cabecera sobre las pestañas compactas de Productos, Recepciones e Historial. Etiqueta y valor juntos, título en el header y PDF en el pie. Referencia en [Control de stock y traslados](../../backend/system/modules/16_stock_management.md). |
| Panel lateral o edición directa | Úsalo si hace falta consultar o editar un dato acotado conservando el contexto de la lista. Revisa primero las implementaciones existentes del área. |
| Transaccional | Conserva sede, almacén, productos y totales según la operación. Prioriza entrada repetida y acciones de avance. `Nueva venta` es el flujo de registro comercial y `POS venta` tiene su propia operación. [Nueva venta](../../backend/system/modules/04_sales_create.md) · [POS restaurante](../../backend/system/modules/74_restaurant_pos.md). |
| Dashboard o reporte | Da precedencia a filtros, periodo, unidades y cifras accionables. Reserva las tarjetas para métricas o accesos independientes. Usa tablas para comparar filas y gráficos para tendencias. |

## Reflujo y desplazamiento

En escritorio conserva el espacio útil entre el marco de navegación y la tabla. En tabletas y móviles permite que filtros y botones se redistribuyan sin cambiar su secuencia. El desplazamiento horizontal pertenece al contenedor de la tabla. Los filtros, el total y el paginador siguen disponibles fuera de ese desplazamiento. En modales con encabezados fijos, un área vertical acotada para los registros conserva el contexto de las columnas. Valida el comportamiento con nombres largos y con pocas filas para evitar recortes y espacios reservados para barras que no aparecen.
