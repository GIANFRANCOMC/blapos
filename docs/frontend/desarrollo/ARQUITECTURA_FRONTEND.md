# Arquitectura frontend

## Entradas y superficies

Cada módulo interno suele tener:

- Vista Blade: `resources/views/System/general/<Module>/<page>/main.blade.php`.
- Entrada JS: `resources/js/System/Pages/<Module>/<page>/main.js`.
- Componente Vue: `resources/js/System/Pages/<Module>/<page>/main.vue`.

Consulta [vite.config.js](../../../vite.config.js) para localizar la entrada real y los alias. System usa `@System`; Guest monta sus páginas desde `resources/js/Guest/Pages` y Platform usa su [shell de administración](PLATFORM_PRESENTACION.md). Conserva las entradas de cada área y usa `resources/js/Shared` para utilidades compartidas cuyo contrato sea el mismo.

## Responsabilidad de los archivos Vue y JavaScript

| Archivo | Responsabilidad |
| --- | --- |
| Entrada `main.js` o equivalente | Carga el CSS de la página y monta la app. Para el montaje de CRUD en System, consulta la [guía de System](GUIA_SYSTEM.md#montaje-y-estado-inicial-de-módulos). |
| Página `.vue` | Compone la pantalla, administra su estado y coordina eventos, filtros y solicitudes. |
| Componente `.vue` | Presenta una parte concreta de la interfaz. Comparte el comportamiento mediante props, eventos y slots. Parte del [catálogo de componentes](../patrones/COMPONENTS.md). |
| Helper o módulo de estado `.js` | Encapsula comportamiento reutilizable o separa una responsabilidad concreta, como formatear valores, cargar opciones o construir solicitudes. Busca el helper existente antes de extraer uno nuevo. |
| CSS | Usa [los parciales editables](../../../resources/css/System/README.md) y los tokens del rol. Deja en la página los estilos que solo resuelvan su distribución particular. |

## Utilidades compartidas

- Componentes de formulario: `InputText`, `InputDate`, `InputSelect`, `InputNumber`, etc.
- Componentes genericos: `DataTable`, `FiltersSection`, `FormModal`, `StatusBadge`.
- Helpers CRUD: `BaseCrudModule.js`, `CrudMixin.js`, `ModuleFactory.js`.
- Helpers de request, fechas, numeros, strings y validaciones.

Usa el contrato de [precisión numérica](../../backend/desarrollo/GENERALIDADES.md#precisión-numérica) para resolver formato, validación y redondeo. Para datos remotos y estados de consulta, aplica los [patrones de interacción](../patrones/INTERACTION_PATTERNS.md).
