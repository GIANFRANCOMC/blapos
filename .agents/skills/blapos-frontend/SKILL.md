---
name: blapos-frontend
description: Diseña, implementa o audita interfaces del ERP Blapos. Úsala para módulos, componentes Vue, tablas, filtros, formularios, dashboards, navegación, accesibilidad, refactorización visual y flujos transaccionales del repositorio.
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

