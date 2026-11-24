# Rendimiento comercial

En **Analítica → Indicadores → Rendimiento comercial** se pueden consultar las ventas vigentes de productos, servicios y planes de membresía. El informe muestra la evolución diaria de los importes y una tabla por ítem.

## Consulta

- **Periodo.** Desde y Hasta incluyen ambos días. La primera consulta toma los últimos 30 días, incluido el día actual en la zona horaria de la empresa. Se admiten periodos de hasta 366 días.
- **Sucursal.** «Todas las sucursales permitidas» reúne el ámbito de la cuenta. Las sucursales inactivas autorizadas se identifican en el selector para permitir consultas históricas.
- **Tipo de ítem.** Se pueden consultar todos los tipos o filtrar por producto, servicio o plan de membresía.
- **Resultados.** Los cambios en los campos se aplican con **Buscar**. **Limpiar** restablece los filtros iniciales y confirma la acción. La tabla conserva su consulta hasta la siguiente búsqueda. Al paginar se usan los filtros de la búsqueda aplicada y el gráfico permanece visible. La tabla muestra hasta diez filas por página y el total de resultados una vez finalizada la carga.
- **Gráfico.** Cada moneda tiene su propio gráfico y escala. Los días sin ventas de esa moneda se muestran en cero. **Ver importes por fecha** ofrece los mismos valores en una tabla accesible con teclado. Los importes presentados siguen la precisión configurada para el tenant.

La interfaz reutiliza los campos, selectores, botones y estados de carga FG, así como la tabla adaptable y su paginador. Los filtros pasan a varias filas cuando falta espacio y el desplazamiento horizontal queda en la tabla. El gráfico se dibuja con los colores de FG y usa las cadenas decimales del servidor para los valores de cada día. [Patrones de interfaz FG](../../../frontend/figma/IMPLEMENTACION_FG.md).

## Datos y cálculo

Se consultan las cabeceras activas de `sales_header` por fecha de emisión (`issue_date`) y la sucursal asociada a su serie (`series.branch_id`). Se suman únicamente líneas activas de `sales_body`. Las cabeceras y líneas anuladas o inactivas se excluyen del periodo de emisión.

La tabla agrupa por `sales_body.item_id`, `type` y `currency_id`. El nombre proviene de la línea con el ID más alto del grupo y del periodo consultado, de modo que un cambio posterior en el catálogo no sustituye ese nombre. Si un ítem se vendió en dos monedas, aparece en dos filas con la cantidad y el importe de cada moneda. La cantidad es la suma registrada por ítem. Las líneas históricas carecen de un campo de unidad de medida.

El gráfico suma `sales_body.total` por día y moneda, usando los mismos filtros que la tabla. El importe representa el valor registrado en las líneas. Los pagos cobrados, los tributos y cargos de cabecera, el margen y la comparación con otro periodo se consultan o se modelan por separado. Se agregan columnas decimales en SQL y se presentan cadenas formateadas con el [contrato de precisión](../../desarrollo/GENERALIDADES.md#precisión-numérica).

## Rutas, permisos y activación

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/analytics/commercial-performance` | Abrir el informe. |
| GET | `/analytics/commercial-performance/initParams` | Obtener el periodo inicial, las sucursales autorizadas y los tipos. |
| GET | `/analytics/commercial-performance/list` | Obtener filas paginadas y, opcionalmente, la evolución. |

Las tres rutas requieren sesión de System, módulo habilitado y acción `view` para `analytics.commercial_performance.index`. El servidor aplica el alcance de sucursales también cuando la consulta se envía sin `branch_id`. Una sucursal fuera de alcance devuelve 403. Cada consulta usa la conexión del tenant activo.

El módulo conserva el ID **110** de su reserva en el catálogo. El aprovisionamiento de bases nuevas lo habilita por defecto. Para una instalación existente se aplica la distribución con `php artisan tenant:navigation-sync <slug> --apply-layout`. Se activa la reserva del catálogo si existe la ruta y se conserva el estado de cualquier proyección de empresa existente. Los perfiles restringidos requieren asignación explícita del módulo. La implementación reutiliza las tablas existentes.

## Verificación

- En la demo autenticada se comprobó el periodo inicial, la búsqueda de un solo día por sucursal y servicio, y el estado sin ventas para planes de membresía. Editar y limpiar los filtros conservó los resultados hasta pulsar Buscar.
- A 1280 px se verificaron el gráfico y el acceso por teclado a los 30 importes diarios. A 320 px se comprobó que el desplazamiento horizontal queda dentro de la tabla. En el riel, Inicio activo carece de barra lateral y Analítica activa la conserva.
- Las pruebas de Laravel cubren fechas inclusivas, líneas y ventas anuladas, nombres históricos, separación de monedas, paginación, validación del periodo, permiso del módulo, habilitación de empresa y selección de sucursales autorizadas. [Pruebas del informe](../../../../tests/Feature/CommercialPerformanceTest.php).

## Referencias

- [Registro de ventas](04_sales_create.md).
- [Navegación y permisos](91_menu_roles_permissions.md).
