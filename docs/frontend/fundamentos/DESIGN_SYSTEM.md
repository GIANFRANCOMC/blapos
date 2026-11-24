# Sistema de diseño

> Estándar vigente de System: [FG global](../figma/IMPLEMENTACION_FG.md). Sus tokens, componentes y layout sustituyen la apariencia anterior. Las reglas funcionales de esta guía siguen vigentes. Guest y Platform conservan sus límites.


Blapos es un ERP SaaS multiempresa y multisectorial. Una persona que recibe mercadería, cobra una venta o registra asistencia necesita identificar el contexto, operar varios registros seguidos y detectar errores a tiempo. La identidad visual se expresa en el marco compacto, las superficies neutras, la escala de Control de stock y los acentos de marca y estado.

## Arquitectura visual

| Capa | Fuente editable y responsabilidad |
| --- | --- |
| Fundamentos | [Tokens compartidos](../../../resources/css/Shared/tokens.css). Definen la escala, los roles y la paleta FG. |
| Controles y composición | [CSS compartido](../../../resources/css/Shared) y [composición de System](../../../resources/css/System/fg). Cada regla pertenece al control, patrón o layout que la utiliza. |
| Componentes y flujos | [Componentes Vue de System](../../../resources/js/System/Components), [comunes](../../../resources/js/Shared) y guías por módulo de [System](../../backend/system/README.md) y [Guest](../../backend/guest/README.md). |
| Integración | Blade carga los assets por superficie. Vite integra las entradas; `npm run build` genera las hojas públicas desde los parciales. [CSS System](../../../resources/css/System/README.md). |

Blapos usa Vue 3 y Bootstrap. Bootstrap aporta la cuadrícula, los controles nativos y sus estados. FG define la presentación desde las entradas de Vite. La [auditoría](../calidad/AUDIT.md) registra las versiones y el alcance de la revisión.

## Extensión y mantenimiento

- Aplica primero el componente por propósito y el token por rol. Ajusta en su parcial de origen. Conserva los estilos de módulo para necesidades propias, como el ancho de una columna o la cuadrícula para una operación.
- Extrae una variante cuando resuelva una diferencia funcional identificable y pueda preservar el comportamiento de quienes ya consumen el componente. Documenta su caso de uso y verifica un consumidor existente.
- Prepara los cambios transversales por etapas según la [auditoría](../calidad/AUDIT.md). Conserva las políticas de tenencia, permisos, redondeo y selección remota descritas en [Generalidades](../../backend/desarrollo/GENERALIDADES.md).

