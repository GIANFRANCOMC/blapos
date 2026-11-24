# Rotación y reposición

En **Analítica → Indicadores → Rotación y reposición** se puede revisar el inventario disponible y priorizar la reposición por producto y almacén. La tabla presenta la ubicación, el saldo actual, el mínimo configurado, las salidas comerciales netas y el estado de stock.

## Consulta

- **Periodo.** Desde y Hasta incluyen ambos días. La consulta inicial abarca los últimos 30 días, con un máximo de 366 días por consulta. El filtro de fechas se aplica a las salidas del Kardex. El saldo, el mínimo y la prioridad corresponden al inventario actual.
- **Ubicación.** Sucursal y Almacén seleccionan ubicaciones activas dentro del alcance del perfil. Elegir una sucursal acota las opciones de almacén y limpia la selección si pertenecía a otra sucursal. Con ambas opciones en «Todos», el informe muestra una fila por producto y almacén permitido que tenga una posición de inventario activa.
- **Reposición.** Agotado indica saldo igual o inferior a cero, también si falta un mínimo. Stock bajo indica un saldo positivo igual o inferior al mínimo. Stock saludable indica saldo por encima del mínimo. Agotados y bajos aparecen primero. **Solo productos por reponer** limita la consulta a esos estados. Un mínimo de cero aparece como **Sin mínimo**.
- **Búsqueda y navegación.** Buscar aplica los filtros y recarga el listado. Limpiar restablece las opciones iniciales y confirma la acción. El resultado refleja la última búsqueda aplicada y la paginación usa sus mismos filtros. La tabla muestra hasta diez filas por página y presenta el total fuera del estado de carga.

## Datos y cálculo

- **Saldo y mínimo.** Se leen `warehouse_items.quantity` y `warehouse_items.minimum_stock`, asociados a `items`, `warehouses`, `branches` y `measurement_units`. El informe usa productos y posiciones de inventario activos. Un producto en dos almacenes puede requerir reposición en uno de ellos.
- **Salidas netas.** Se agrupa `inventory_movements` por almacén y producto según `created_at`, siguiendo los cortes diarios del Kardex. Las cantidades negativas de venta, despacho de venta y consumo de recetas por venta se suman como salidas. Las cantidades positivas de devoluciones de clientes y reposiciones por anulación se descuentan en la fecha en que se registraron. Las cantidades se mantienen con su signo si en el periodo hubo más devoluciones que salidas. Traslados, ajustes, merma, compras y devoluciones a proveedores quedan fuera de la demanda comercial.
- **Precisión.** Se agregan decimales en SQL y se formatean mediante Numbers. El stock y el mínimo se comparan con la precisión configurada, siguiendo las reglas de Control de stock. La unidad abreviada proviene del catálogo. [Generalidades](../../desarrollo/GENERALIDADES.md#precisión-numérica) · [Control de stock y Kardex](16_stock_management.md).

## Rutas, permisos y activación

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/analytics/stock-turnover` | Abre el informe. |
| GET | `/analytics/stock-turnover/initParams` | Devuelve el periodo inicial y las ubicaciones permitidas. |
| GET | `/analytics/stock-turnover/list` | Devuelve cantidades y estado por producto y almacén. |

Las tres rutas requieren el módulo habilitado y permiso `view` para `analytics.stock_turnover_replenishment.index`. La consulta usa la conexión activa del tenant y aplica la intersección de los permisos de sucursal y almacén, incluso cuando el filtro dice «Todos». Una ubicación fuera del alcance devuelve 403. Una combinación de sucursal y almacén que no corresponde devuelve 422 si ambas ubicaciones son accesibles.

Se reutiliza el submódulo de catálogo **111**. Las instalaciones nuevas lo habilitan por defecto. Las existentes aplican la distribución con `php artisan tenant:navigation-sync <slug> --apply-layout`, conservando la selección de la empresa y los permisos de los perfiles restringidos. El informe solo consulta las tablas operativas existentes.

## Interfaz

La pantalla reutiliza el patrón de filtros FG de Analítica y la tabla de Inventario, con los mismos componentes de identificación de producto, cantidades y estados. En pantallas estrechas los filtros se apilan y el desplazamiento horizontal queda dentro de la tabla. El nombre de cada mundo sigue disponible para tecnologías de asistencia cuando se desactivan los tooltips del riel en móvil. [Estilo FG](../../../frontend/figma/IMPLEMENTACION_FG.md) · [Navegación tenant](../../../frontend/patrones/TENANT_NAVIGATION.md).

## Verificación

- En la demo autenticada se comprobó la búsqueda de un día, por sucursal y almacén, con **Solo productos por reponer**. La segunda página conservó los filtros aplicados y el cambio de sucursal en el formulario limpió el almacén incompatible sin alterar ese resultado.
- A 1280 y 320 px se revisaron la alineación de filtros, el desplazamiento de la tabla y la navegación de Analítica. En móvil se comprobó la recuperación al corregir el periodo, el cambio de mundo sin tooltips y el retorno del foco a la hamburguesa con Escape. Al regresar a escritorio se restauraron los tooltips y se verificó Rendimiento comercial como otro consumidor del patrón FG.
- Pasaron 76 pruebas dirigidas de Laravel con 7006 aserciones, 300 pruebas de frontend y la compilación de Vite. Las pruebas cubren los límites del periodo, las salidas netas por origen, los estados de reposición y el alcance combinado de sucursal y almacén. [Pruebas del informe](../../../../tests/Feature/StockTurnoverTest.php).
