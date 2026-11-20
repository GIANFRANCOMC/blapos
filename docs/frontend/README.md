# Frontend de Blapos

Guía de trabajo para construir y revisar interfaces del ERP conservando la identidad, densidad y estructura que ya usa Blapos.

| Necesidad | Guía |
| --- | --- |
| Entender la arquitectura visual y cómo extenderla | [Sistema de diseño](fundamentos/DESIGN_SYSTEM.md) |
| Consultar reglas de branding, tablas, controles y modales | [Estándares de interfaz](fundamentos/ESTANDARES_UI.md) |
| Organizar archivos Vue y JavaScript | [Arquitectura frontend](desarrollo/ARQUITECTURA_FRONTEND.md) · [Guía frontend de System](desarrollo/GUIA_SYSTEM.md) |
| Diseñar un flujo o priorizar información | [Criterios UX](patrones/UX_GUIDELINES.md) |
| Elegir color, superficie, borde o sombra | [Paleta](fundamentos/COLOR_PALETTE.md) |
| Elegir tamaño y peso por función | [Tipografía](fundamentos/TYPOGRAPHY.md) |
| Encontrar controles y variantes existentes | [Componentes](patrones/COMPONENTS.md) |
| Mantener listados y formularios del catálogo | [Catálogo comercial](patrones/CATALOGO_COMERCIAL.md) |
| Consultar la propuesta de Productos en Figma y sus flujos | [Productos: experiencia e interfaz](modulos/productos/README.md) · [Estados y mensajes](modulos/productos/ESTADOS_Y_MENSAJES.md) |
| Reutilizar el sistema propuesto de Figma en otros módulos | [Estilos y componentes Blapos](modulos/productos/README.md#componentes-y-adopción) |
| Componer navegación, listados, detalle o pantallas transaccionales | [Estructuras](patrones/LAYOUTS.md) |
| Cambiar el menú, los mundos o el navbar | [Navegación tenant](patrones/TENANT_NAVIGATION.md) |
| Consultar grupos, orden y opciones de menú | [Orden del menú de System](patrones/00_menu_order.md) |
| Cambiar el panel central o las vistas de error | [Interfaz de administración SaaS](desarrollo/PLATFORM_PRESENTACION.md) · [Páginas de error](patrones/ERROR_PAGES.md) |
| Resolver consultas, validación, guardado y retroalimentación | [Interacciones](patrones/INTERACTION_PATTERNS.md) |
| Revisar teclado, contraste, reflujo y foco | [Accesibilidad](calidad/ACCESSIBILITY.md) |
| Verificar un cambio antes de entregarlo | [Lista de verificación](calidad/DESIGN_CHECKLIST.md) |
| Ver la auditoría inicial y el orden propuesto de adopción | [Auditoría](calidad/AUDIT.md) |
| Consultar referencias externas por problema de diseño | [Referencias](references/README.md) |

Los documentos de diseño organizan el acceso a los estándares vigentes. [Generalidades](../backend/desarrollo/GENERALIDADES.md) conserva los contratos transversales de implementación y las reglas de negocio. Las guías de módulo describen su aplicación concreta. El código es la fuente de los valores de los tokens y las API de los componentes. Si hay una discrepancia, comprueba el comportamiento real y actualiza la fuente correspondiente según el alcance del cambio.

Para editar estilos, consulta los [parciales y comandos de CSS](../../resources/css/System/README.md). Para cambios de API, permisos o persistencia, consulta el índice de [Backend](../backend/README.md).

## Usar la skill con Codex

Abre el proyecto Blapos y escribe `$blapos-frontend` en el mensaje, seguido del módulo y del cambio. Por ejemplo, «Usa `$blapos-frontend` para mejorar la modal de detalle de Traslados, conservando el estilo de Productos». Para una revisión sin cambios, pide «Usa `$blapos-frontend` para auditar los filtros de Control de stock y entregar solo los hallazgos».

La skill vive en [`.agents/skills/blapos-frontend/SKILL.md`](../../.agents/skills/blapos-frontend/SKILL.md) y abre las guías de este índice según la tarea.
