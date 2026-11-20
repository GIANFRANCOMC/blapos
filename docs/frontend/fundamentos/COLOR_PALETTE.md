# Color y superficies

> Estándar vigente de System: [FG global](../figma/IMPLEMENTACION_FG.md). Sus tokens, componentes y layout sustituyen la apariencia anterior. Las reglas funcionales de esta guía siguen vigentes. Guest y Platform conservan sus límites.


El branding vigente se define en [00-tokens.css](../../../resources/css/System/br-branding/00-tokens.css). La propuesta inicial del encargo se usó como contraste conceptual. A continuación se documenta la identidad efectiva de Blapos.

| Función | Tokens de referencia | Uso |
| --- | --- | --- |
| Marca | `--br-primary`, `--br-primary-active`, `--br-primary-soft` | Acción primaria, foco o selección según el componente. El azul base vigente es `#2899e5`. |
| Secundario oscuro | `--br-secondary`, `--br-on-secondary` | Texto de énfasis y superficies oscuras. El secundario base es `#1a1a35`. |
| Superficies | `--br-surface`, `--br-surface-elevated`, `--br-modal-surface`, `--br-modal-chrome-surface` | Área de trabajo, superficies elevadas y regiones de modales. |
| Tablas | `--br-table-thead-bg`, `--br-table-thead-text`, `--br-table-thead-edge` | Cabecera mate, texto y acento inferior. |
| Texto y límites | `--br-text`, `--br-text-muted`, `--br-border`, `--br-border-control` | Lectura, contexto secundario y límites de controles. |
| Estado | Familias `--br-success*`, `--br-warning*`, `--br-danger*`, `--br-info*` con su texto asociado | Resultado, atención, peligro e información. Reutiliza la variante del componente. |
| Geometría y profundidad | `--br-radius*`, `--br-shadow*`, `--br-focus-ring*`, `--br-control-height` | Esquinas, elevación y foco según el patrón existente. |

Selecciona el par de superficie y texto según su contraste real. El color de marca o de estado puede requerir una variante más oscura según el tamaño y el contexto. Reutiliza roles de color en lugar de hexadecimales por pantalla. Para bordes, radios, alturas, sombras y transiciones, sigue las reglas de [CSS System](../../../resources/css/System/README.md). Los casos observados de contraste están en la [auditoría](../calidad/AUDIT.md).

