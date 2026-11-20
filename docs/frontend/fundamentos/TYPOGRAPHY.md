# Tipografía

> Estándar vigente de System: [FG global](../figma/IMPLEMENTACION_FG.md). Sus tokens, componentes y layout sustituyen la apariencia anterior. Las reglas funcionales de esta guía siguen vigentes. Guest y Platform conservan sus límites.


Inter es la fuente configurada de la interfaz. Control de stock es la referencia para tamaño y peso de System, Guest y Platform. Consulta la [política de tamaños por rol](ESTANDARES_UI.md#tamaños-estándar-según-el-rol) antes de introducir un rol tipográfico.

| Contenido | Punto de partida vigente |
| --- | --- |
| Texto operativo, menú, controles y tablas | `--br-ui-font-size` o su alias de componente, por ejemplo `--br-font-size-table`. |
| Etiqueta de formulario | `form-label` y `--br-font-size-label`. |
| Encabezado de tabla | `--br-table-heading-font-size`. |
| Dato destacado | `--br-table-value-font-size`; usar el título compartido para encabezados de página, sección o modal. |
| Cantidad de stock | `--br-stock-quantity-font-size`. Stock actual y Diferencia comparten el tamaño. |
| Código, sufijo o ayuda | El alias del componente, como `--br-product-code-font-size`, `--br-control-affix-font-size` o `--br-table-caption-font-size`. |

La escala vive en [typography-tokens.css](../../../resources/css/Shared/typography-tokens.css) y los alias en [00-tokens.css](../../../resources/css/System/br-branding/00-tokens.css). Ajustar `--br-font-scale` permite ampliar toda la escala. Un cambio de un solo rol corresponde a su alias. Reutiliza el tamaño existente más cercano y retira el override local cuando el valor ya se hereda.

Usa 400 para lectura, 500 para el énfasis operativo que ya asigna el componente y 600 para jerarquía. Usa los `--br-font-weight-*` por función y reserva el peso técnico de Font Awesome para los iconos.

Para comparar importes y cantidades, usa alineación a la derecha, cifras tabulares si el componente las admite y conserva número y unidad juntos. En las cantidades de inventario, la unidad tiene menor jerarquía que el valor. Para precisión y redondeo consulta [Generalidades](../../backend/desarrollo/GENERALIDADES.md#precisión-numérica).

