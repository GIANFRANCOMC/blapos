# Estilos de Blapos

[Shared/ui.css](../Shared/ui.css) es la entrada visual común. Importa [tokens.css](../Shared/tokens.css) y las reglas compartidas de tipografía, controles, tablas, carga, foco y SweetAlert. System, acceso, Guest y Platform la consumen desde sus propias entradas.

| Superficie | Entrada Vite |
| --- | --- |
| System autenticado | [fg/index.css](fg/index.css) |
| Acceso System | [fg/auth.css](fg/auth.css) |
| Guest | [Guest/app.css](../Guest/app.css) |
| Platform | [Platform/app.css](../Platform/app.css) |
| Errores HTML | [Shared/error-page.css](../Shared/error-page.css) |

Pon en Shared toda regla con un uso transversal. Reserva [fg/modules/](fg/modules/) para composiciones de módulos de System. Reutiliza componentes y tokens por función, y usa el prefijo FG en clases y atributos visuales propios. Respeta los permisos, datos y contratos de cada superficie.

Bootstrap y SweetAlert2 entran desde `Shared/ui.css` en la capa `fg-vendor`. Vue Select se importa en la hoja de System y en la de Guest. System también carga Select2 desde su propia hoja. Los componentes Vue reutilizan esas cargas. El scroll, el foco y el skeleton llegan mediante `Shared/ui.css`.

El layout autenticado de System usa `fg-up.blade.php` y `fg-down.blade.php`. Guest mantiene sus propios parciales de metadatos y scripts. La presentación de su menú vive en la hoja de Guest y el desplegable usa Bootstrap. Se retiraron las hojas de plantilla Vuexy sin consumidores y la entrada sin uso de Tailwind.

### Responsabilidad de los estilos compartidos

| Patrón | Fuente |
| --- | --- |
| Tablas, encabezados, estados y desplazamiento local | [responsive-tables.css](../Shared/responsive-tables.css) y [table-values.css](../Shared/table-values.css) |
| Cabeceras de página, filtros, breadcrumbs y su cuadrícula | [list-layouts.css](../Shared/list-layouts.css) |
| Campos compuestos y estados de solo lectura, foco y error | [control-groups.css](../Shared/control-groups.css) y [unavailable-controls.css](../Shared/unavailable-controls.css) |
| Diálogos, tamaños, transiciones y altas rápidas | [modals.css](../Shared/modals.css) |
| Importación, acciones en línea, nombre y códigos del producto | [file-import.css](../Shared/file-import.css), [inline-actions.css](../Shared/inline-actions.css), [inline-text-action.css](../Shared/inline-text-action.css) y [product-identity.css](../Shared/product-identity.css) |
| Impuestos, pagos y totales de documentos | [document-settlement.css](../Shared/document-settlement.css) |

El contenedor responsive es dueño del scroll. También contiene los elementos posicionados de la tabla, incluso las etiquetas ocultas para lectores de pantalla. Usa la misma cuadrícula FG en el filtro y su skeleton. Las variantes de filtros tienen selectores separados para que el orden de las hojas no cambie su número de columnas. El selector simple conserva una sola línea y la selección múltiple conserva sus saltos.

`npm run build` genera las hojas y fuentes con hash. Blade consume el manifiesto mediante `@vite`. `npm run dev` actualiza las entradas durante el desarrollo. Consulta los [estándares de interfaz](../../../docs/frontend/fundamentos/ESTANDARES_UI.md) para añadir o revisar estilos.

## Verificación

- La compilación de producción emitió las cinco entradas. Se compilaron las vistas Blade y se revisaron las hojas referenciadas por el manifiesto.
- Pasaron 293 pruebas de frontend y seis pruebas Laravel de navegación autenticada, Mi espacio y páginas de error. `ThemeNamespace.test.mjs` comprueba los parciales de System, las referencias a temas retirados y la carga duplicada de Vue Select y del skeleton.
- En la demo se revisaron los accesos de System y Platform, el Libro de reclamaciones a 1280 y 390 px y el cierre del menú con Escape y retorno de foco. El catálogo público mostró sus tarjetas en escritorio sin desbordamiento horizontal. Las verificaciones en navegador se hicieron sin enviar formularios.

### Revisión de módulos con sesión autenticada

Prueba con la sesión de Administrador de la demo a 1280 y 390 px. Se abrieron formularios y se hicieron consultas de lectura.

| Módulo | Comprobación |
| --- | --- |
| Productos | Los registros siguieron visibles al escribir. Buscar mostró el estado vacío y su total. La descripción se pudo expandir y contraer. El editor y la importación mantuvieron sus secciones y el pie visible. La tabla de inventario del editor desplazó solo su propio contenido. |
| Control de stock | Los filtros ocuparon 12 columnas a 1280 px y dos a 390 px. Sus controles midieron 36 y 44 px respectivamente. Alertas conservó la cabecera fija durante el scroll, el paginador por fuera y el desplegable de stock mínimo sobre el diálogo, sin ampliar su cuerpo. |
| Perfiles de acceso | Cabecera y celdas centradas verticalmente y cabecera con texto oscuro sobre fondo claro. El filtro mantuvo un anillo de foco exterior. A 390 px el documento midió 390 px y el ancho de 1080 px de la tabla quedó dentro de su contenedor, incluso con la modal abierta. |
| Nueva venta | Se comprobó el panel de totales y la apertura y el cierre del selector de ítems. El documento conservó el ancho disponible en ambos tamaños. |

### Revisión de Compras, Caja y Cuentas por cobrar

Se validó `demo.blapos.test` con sesión de Administrador, en escritorio a 1280 px y móvil a 390 px. Se habilitaron los 51 submódulos del catálogo para el tenant demo. La compra, la venta contraentrega y la caja descritas abajo son registros de prueba de esa base.

| Módulo | Comprobación |
| --- | --- |
| Cuentas por cobrar | Se encontró `BV1-00000004` por serie y correlativo completos. Su cronograma mostró S/ 100.00 de total, una cuota pendiente de S/ 70.00 y un pago aplicado en efectivo de S/ 30.00 con fecha y referencia. A 390 px la fila de pago y el botón Cerrar conservaron su ancho disponible. También se comprobó la disposición de Buscar y Limpiar a 1024 px y el estado vacío al filtrar Pagadas. |
| Compras registradas e Ingresos pendientes | Se buscó por proveedor y por referencia `COM-M1FMGCEHVI`. El listado de ingresos pendientes mostró la compra y el diálogo Recibir mercadería indicó cuatro unidades por recibir, con fecha y observación etiquetadas. A 390 px, el ancho de 1080 px de la tabla quedó dentro del contenedor de 356 px. |
| Nueva compra | Se comprobó la selección del proveedor, el cambio a Recepción pendiente, la aparición de Fecha esperada y la apertura y cierre de Cambiar observación. Los selectores del documento, almacén, producto y métodos de pago exponen nombres accesibles. |
| Proveedores y Cuentas por pagar | Se alinearon búsqueda y acciones de Proveedores y se verificó Editar proveedor con Estado seleccionable. En Cuentas por pagar, el cronograma mostró dos cuotas de S/ 47.20. En móvil llevó el foco al detalle y lo devolvió a Ver cronograma al cerrar. |
| Cajas, Aperturas y cierres, Movimientos y Resumen | La búsqueda por código encontró `QA-FIN-20261124`. Se verificaron los diálogos Agregar caja, Cerrar caja y Registrar operación y el scroll local en móvil. Movimientos mostró fondo de S/ 100.00 e ingreso de S/ 50.00. Cajas, el historial, el resumen y el arqueo mostraron S/ 150.00 esperados. Se cerró la sesión QA por el servicio de caja y se confirmó en el historial S/ 150.00 contados y S/ 0.00 de diferencia. |
| Gastos varios | Se abrió Registrar gasto y se comprobó el nombre accesible de Concepto, Importe y Sesión de caja, incluida la caja QA antes de su cierre. |
| Traslados | Se revisaron el listado con paginador y el detalle en escritorio. En móvil se abrieron Historial y Recepciones y se comprobó el cierre del detalle. |

Al abrir un diálogo de Bootstrap en System, se ocultan los tooltips que quedaron visibles fuera del diálogo. Las consultas y los formularios se verificaron en navegador. Los registros QA se generaron con los servicios de la aplicación. La compra quedó pendiente de recepción y la caja de prueba quedó cerrada. Las verificaciones de Gastos varios y de recepción de mercadería se limitaron a sus formularios.

Pasaron la compilación de Vite, 293 pruebas de frontend y las 41 pruebas Laravel seleccionadas de aprovisionamiento, navegación y finanzas. Los cuatro fallos detectados en la revisión adicional de arquitectura se corrigieron en la revisión del límite tenant–empresa. [Comprobaciones de arquitectura](../../../docs/backend/tenancy/TENANT_COMPANY_BOUNDARY.md#verificación-de-base-de-datos).
