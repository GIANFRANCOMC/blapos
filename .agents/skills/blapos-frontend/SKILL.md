---
name: blapos-frontend
description: Diseña, implementa o audita interfaces del ERP Blapos con criterio visual, accesibilidad y una identidad de marca coherente. Úsala para módulos, componentes Vue, tablas, filtros, formularios, dashboards, navegación, accesibilidad, refactorización visual y flujos transaccionales del repositorio, incluidas las pantallas públicas de Guest.
---

# Diseño de interfaces en Blapos

1. Define la tarea del usuario, el área del producto (System, Guest o Platform), el contexto operativo y el alcance autorizado. Abre la documentación del módulo y localiza la ruta real en Blade y Vue. Compara con una pantalla ya trabajada del mismo tipo.
2. Consulta el [índice de diseño](../../../docs/frontend/README.md) y elige solo las guías que necesites.
   - Estructura de archivos Vue y JavaScript → [Arquitectura frontend](../../../docs/frontend/desarrollo/ARQUITECTURA_FRONTEND.md).
   - Tablas, formularios y Vue → [Componentes](../../../docs/frontend/patrones/COMPONENTS.md) y el apartado aplicable de [Interacciones](../../../docs/frontend/patrones/INTERACTION_PATTERNS.md).
   - Navegación, dashboards y pantallas transaccionales → [Estructuras](../../../docs/frontend/patrones/LAYOUTS.md) y [Criterios UX](../../../docs/frontend/patrones/UX_GUIDELINES.md).
   - Cambios visuales → [Paleta](../../../docs/frontend/fundamentos/COLOR_PALETTE.md) o [Tipografía](../../../docs/frontend/fundamentos/TYPOGRAPHY.md).
   - Revisión de teclado, contraste o adaptación → [Accesibilidad](../../../docs/frontend/calidad/ACCESSIBILITY.md).
3. Elige el componente existente y el token adecuado al rol. Ajusta en el origen de la regla si el comportamiento es compartido. Si cambias un componente común, revisa al menos un consumidor adicional. El alcance de una auditoría se limita a hallazgos y recomendaciones salvo que se autoricen cambios.
4. Contrasta diferencias en pantallas reales o en una muestra representativa. Aplica la [lista de verificación](../../../docs/frontend/calidad/DESIGN_CHECKLIST.md), ejecuta las pruebas pertinentes y distingue las comprobaciones de navegador, las pruebas automatizadas y los pendientes. Registra cambios visuales específicos en la guía del módulo y las reglas transversales en su fuente enlazada.

## Reutilización y revisión responsive

- Antes de agregar una clase, busca por función en los componentes, parciales CSS y tokens existentes. Corrige en el origen las propiedades que compiten y retira los estilos locales duplicados. Usa una variante compartida para cambios de composición.
- Verifica los estilos calculados en el navegador con los componentes reales y sus clases combinadas. Prueba una descripción larga cerrada y abierta, una botonera estrecha con sus iconos y una tabla ancha dentro de su contenedor. Revisa la transición del skeleton al listado.
- Al cambiar pestañas, verifica que conserven su tamaño y posición con paneles de alturas distintas. En pantallas estrechas, comprueba que el desplazamiento ocurra solo para revelar una pestaña recortada. Reutiliza las clases de `resources/css/Shared` para resolver comportamiento común.
- Para códigos y acciones de fila, aplica el [patrón de Catálogo comercial](../../../docs/frontend/patrones/CATALOGO_COMERCIAL.md).

## Consulta antes de cambios drásticos

- Consulta al usuario antes de cambiar la navegación, reorganizar los pasos de una operación, retirar datos o acciones que el personal usa para trabajar, cambiar valores predeterminados o modificar el alcance de un flujo. Describe el cambio, el motivo, su impacto y qué opción conservaría el flujo actual. Espera su decisión para la parte afectada y continúa con los ajustes independientes autorizados.
- Resuelve directamente los ajustes reversibles de alineación, legibilidad, accesibilidad y estilo cuando respetan el flujo y sus contratos.

## Criterio visual y de marca

- **Decide qué mostrar y cuándo.** Identifica quién usa la pantalla, qué necesita decidir y cuál es su siguiente acción. Pon a la vista los datos que cambian esa decisión y la acción principal. Lleva el detalle secundario a un menú o un desplegable cuando ayude a escanear la pantalla. Mantén visibles las alertas y los requisitos que condicionan la operación.
- **Compón con intención.** Alinea bordes, alturas y líneas base de etiquetas, campos, iconos y botones según su función. Usa los espaciados y tamaños compartidos. Agrupa por tarea, alinea los importes a la derecha para compararlos y comprueba la composición con textos largos, errores y anchos estrechos.
- **Haz que el DOM exprese la interfaz.** Usa enlaces para navegar y botones nativos para ejecutar acciones. Mantén el orden del DOM coherente con el orden de lectura y de foco. Asocia etiquetas y errores con sus campos. Da un nombre accesible a cada botón de icono y verifica que se pueda llegar a él y activarlo con teclado y tacto. Comprueba el foco al abrir y cerrar modales, menús y paneles.
- **Aplica criterio de marketing al producto.** Expresa el valor de Blapos con beneficios concretos y lenguaje del negocio. En System y Platform, deja que la claridad y la rapidez del flujo sostengan la marca. En Guest, haz visible qué ofrece la empresa y cuál es el siguiente paso. Mantén una voz clara, sobria y cercana. Usa los tokens y la iconografía existentes, y respeta la identidad de cada empresa en sus espacios asignados. Evita adornos, mensajes promocionales que interrumpan el trabajo y promesas que el producto no pueda demostrar.

## Mensajes de interfaz

- **Durante la carga**, nombra la acción y el dato que se está procesando. Usa «Buscando productos…», «Guardando la recepción…» o «Generando el reporte…» según el flujo. Si el indicador es solo visual, anuncia la operación con el patrón accesible existente.
- **Al terminar**, comunica el resultado confirmado y menciona qué se guardó, actualizó o quedó pendiente si ese dato cambia el siguiente paso. Por ejemplo, «Recepción registrada. Quedan 4 unidades pendientes». En búsquedas, distingue «Aún no hay productos registrados» de «No encontramos productos con estos filtros».
- **Si hay un error**, indica dónde ocurrió, qué dato debe corregirse o qué acción permite continuar. Por ejemplo, «Hay 6 unidades disponibles. Ingresa 6 o menos». Si el resultado es incierto, indica qué puede comprobar el usuario antes de repetir la operación.
- Escribe mensajes breves, con verbos concretos y vocabulario del negocio. Conserva la misma acción entre el botón, la carga y el resultado. Muestra cada aviso cerca del flujo que lo originó y reutiliza los [estados y patrones de recuperación](../../../docs/frontend/patrones/INTERACTION_PATTERNS.md#estados-y-recuperación) para elegir cuándo usar feedback en línea, Toastr o SweetAlert.

