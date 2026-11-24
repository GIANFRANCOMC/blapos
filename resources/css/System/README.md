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

Se comprobaron escritorio a 1280 px y móvil a 390 px. Las pruebas usaron registros existentes de la demo y datos simulados de solo lectura en la vista local. La sesión de Administrador devolvió 403 al entrar en Compras y Caja, cuyos módulos están deshabilitados de forma predeterminada en la demo.

| Módulo | Entorno | Comprobación |
| --- | --- | --- |
| Cuentas por cobrar | Demo autenticada | Buscar y Limpiar quedan juntos a 1280 px y también a 1024 px, donde los filtros usan dos columnas. En móvil se comprobó el filtro Pagadas sin resultados, el contador en cero y el aviso de limpieza. Buscar tras limpiar recuperó la cuenta. El cronograma mostró dos cuotas y mantuvo disponible el botón Cerrar, sin desbordamiento del documento ni tooltip superpuesto. |
| Compras registradas | Vista local con datos simulados | Se alinearon Búsqueda, Estado y las acciones en escritorio. En móvil la tabla desplazó su contenido dentro del contenedor. Se verificaron la referencia y fecha, la conservación de la fila al escribir, el estado vacío al pulsar Buscar y el formulario Recibir mercadería con cantidades pendientes y campos etiquetados. |
| Caja y Movimientos | Vista local con datos simulados | La búsqueda de cajas aplica nombre, código o sucursal al pulsar Buscar. Se verificaron una consulta sin coincidencias y la recuperación del listado al limpiar el campo y buscar. En móvil se comprobó el scroll local de Movimientos, los diálogos Agregar caja, Cerrar caja y Registrar operación, y los desplegables Sucursal y Tipo de movimiento con nombres accesibles. |
| Traslados | Demo autenticada | Se comprobaron el listado con paginador y el detalle en escritorio. En móvil se abrieron Historial y Recepciones, con el botón Cerrar disponible y sin desbordamiento horizontal del documento. |

Al abrir un diálogo de Bootstrap en System, se ocultan los tooltips que quedaron visibles fuera del diálogo. Pendientes en la demo autenticada: Compras y Caja cuando tengan acceso, y el cronograma de una cuenta con pagos aplicados. La revisión abarcó consultas y apertura de formularios sin confirmar operaciones.

Pasaron la compilación de Vite, las 293 pruebas de frontend y dos pruebas Laravel sobre la habilitación predeterminada y las rutas de recepción y cuentas por pagar.
