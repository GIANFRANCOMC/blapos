# CSS System

Esta carpeta es la fuente editable de los CSS de la plataforma.

Los archivos en `public/System/assets/css` son salida generada para mantener compatibilidad con los layouts actuales que todavía usan `<link rel="stylesheet">`.

## Estructura

- `br-branding/`: tokens `--br-*`, componentes reutilizables, layout, formularios, botones, navbar, sidebar, dashboard y SweetAlert.
- `custom/`: estilos heredados o específicos por dominio mientras se terminan de migrar a componentes reutilizables. La capa `90-system-wide-visual-polish.css` carga al final para armonizar pantallas antiguas que todavía pisan el branding.
- `br-login/`: pantalla de login y accesos invitados.
- `demo/`: ajustes mínimos de plantilla.
- `platform.css`: entry preparado para Vite con el mismo orden de parciales.

## Reglas

- Declarar colores nuevos únicamente en `br-branding/00-tokens.css`.
- Los tamaños tipográficos se definen en `../Shared/typography-tokens.css`; System, Guest y Platform consumen la misma escala. Los aliases por función se centralizan en `br-branding/00-tokens.css`. No escribir tamaños literales en `font-size`, componentes Vue ni estilos de página.
- Para ampliar toda la tipografía, cambiar `--br-font-scale` (por ejemplo, `1.1` aumenta un 10 %). Para cambiar solo un rol, ajustar su alias: `--br-ui-font-size`, `--br-table-heading-font-size`, `--br-table-value-font-size` o `--br-stock-quantity-font-size`. Stock actual y Diferencia comparten este último.
- Usar pocos tamaños estándar según el rol: texto operativo, ayuda, dato secundario, dato destacado, encabezado y cantidad de stock. Reutilizar sus aliases y los componentes de títulos existentes. La [política de tamaños por rol](../../../docs/GENERALIDADES.md#tamaños-estándar-según-el-rol) define la elección y las excepciones.
- Los pasos numéricos conservan tamaños heredados por compatibilidad; no son un menú de medidas para pantallas nuevas. Usar el alias semántico del rol, sin añadir variaciones mínimas ni tokens por preferencia estética. Un alias nuevo debe apuntar a un tamaño existente siempre que sea posible.
- Una medida nueva exige una necesidad funcional o de legibilidad que ningún rol cubra, con motivo y consumidores compartidos documentados. No crear tamaños por módulo ni diferencias mediante `calc()` para eludir el estándar. Al migrar componentes, consolidar excepciones en los roles existentes.
- Un token en `rem` escala una sola vez; los iconos relativos en `em` siguen el tamaño de su texto, sin otro factor. Los tamaños fluidos también se definen en el catálogo compartido.
- Aplicar el mismo criterio al resto del aspecto visual: reutilizar tokens de color, peso, espaciado, altura de control, radio, borde, sombra y transición antes de crear valores nuevos. Centralizar los valores nuevos y documentar su función; no crear variantes por pantalla para un mismo rol.
- Modificar la regla que pertenece al componente. No añadir otra clase, selector más específico o `!important` para corregir una regla propia duplicada; retirar la duplicación y mantener una sola responsabilidad por regla. Las adaptaciones responsive consumen los mismos tokens.
- En `InputBase`/`SearchInput`, el foco visual pertenece a `br-form-control-group` desde que el campo está vacío. Sus inputs y acciones no dibujan sombras independientes: insertar o retirar la X no debe trasladar el foco visual ni generar una división azul transitoria. La X solo limpia; Buscar o Enter ejecutan la consulta.
- Consumir colores con `var(--br-*)` o `color-mix()` basado en tokens.
- No editar directamente los CSS generados en `public/System/assets/css`.
- Si un CSS vendor pisa el branding, mapear únicamente sus colores conflictivos a tokens de compatibilidad `--br-vuexy-*`; no duplicar paletas ni agregar `!important` en capas de módulo.
- Mantener prefijos `br-*` para clases reutilizables.
- Todas las tablas paginadas, también en modales, usan el pie integrado de `Paginator`: pasar `pagination`, `links` y `loading`. La navegación queda centrada y el total filtrado a la derecha, visible al terminar la consulta, también con cero resultados. Durante la carga, el componente oculta juntos paginador y contador; esta es una regla funcional global, sin estilos o condiciones por módulo. No añadir contadores o wrappers propios; no ocultar el pie según la cantidad de registros. La separación común es `--br-list-pagination-gap`; retirar márgenes adicionales y mantener el pie fuera del scroll de la tabla.
- El skeleton cubre únicamente `initParams` (o la configuración inicial equivalente), y termina en su `finally` antes del primer listado. Todas las consultas de registros usan su loader habitual y vacían previamente la tabla mediante `Requests.emptyPagination()` o `[]`; si fallan, permanecen vacías. Editar un filtro pendiente de Buscar no borra registros. Ver las reglas funcionales en [Generalidades](../../../docs/GENERALIDADES.md#consultas-de-tablas-y-limpieza-de-registros).
- Todas las pantallas muestran skeleton + Swal durante `initParams`. Los skeletons conservan encabezados y el cuerpo en blanco mediante `br-list-initial-space`, sin spinner, filas ni paginación simuladas. Las filas de carga, vacío o error usan `br-table-state` y quedan fuera del hover. `PageSkeleton` e `InitialPageMixin` cubren las páginas sin un skeleton específico. `InitialLoadTransition` usa `02-loading-transitions.css` y los tokens `--br-initial-load-duration`/`--br-initial-load-easing`; las ramas comparten celda y respetan movimiento reducido.
- Los pesos de texto usan roles `--br-font-weight-*` de `Shared/typography-tokens.css`: regular (400), medio (500) y seminegrita (600). No escribir pesos literales por módulo; mantener separado el peso técnico de los iconos.
- Reutilizar `br-filter-clear` para «Limpiar filtros»: enlace sin subrayado y alineado abajo, definido en `40-buttons-quick-create.css` con los tokens existentes de acciones pequeñas. No combinarlo con `br-btn` ni crear versiones por módulo. Conservar el foco visible y los estados deshabilitados.
- Los controles deshabilitados no deben mostrar cursor de escritura ni conservar el foco al bloquearse. La regla común está en `resources/css/Shared/disabled-controls.css` y `resources/js/Shared/disabled-controls.js`, compartidas por System, Guest y Platform; no crear reglas por módulo. Los campos `readonly` siguen pudiendo recibir foco para seleccionar y copiar su contenido.
- Los estados compactos usan `br-badge` con `br-badge--success`, `--warning`, `--danger`, `--info`, etc. En Vue, preferir `StatusBadge` para elegir la variante desde `ModuleConstants.js`; muestra solo texto. Las clases antiguas `br-status-label` y `br-inventory-status` son alias visuales de transición.
- Si un bloque de `custom/` empieza a servir para varios módulos, moverlo a `br-branding/`.
- La capa final de `custom/` debe consumir solo tokens `--br-*`; no declarar hexadecimales, `rgba()` ni paletas locales.

## Compilación

`npm run build` regenera primero los CSS públicos desde los parciales de esta carpeta. `npm run dev` también los genera al arrancar y vuelve a generarlos cuando cambian los parciales.

Para regenerarlos manualmente sin compilar JavaScript:

```bash
npm run build:css:system
```

El entry `resources/css/System/platform.css` se actualiza junto con los CSS públicos y queda listo para una migración posterior a `@vite`.
