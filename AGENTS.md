# Blapos

Consulta [la documentación del proyecto](docs/backend/proyecto/README.md) y la guía del módulo afectado. Conserva los límites entre System, Guest y Platform, así como el contexto de tenant y los contratos vigentes.

## Cambios de backend y producto

- Usa la skill [blapos-backend](.agents/skills/blapos-backend/SKILL.md) para analizar procesos de negocio, diseñar mejoras de producto o implementar lógica de backend. Consulta [las guías de desarrollo, datos y seguridad](docs/backend/README.md) y el módulo afectado.
- Explica el cambio en términos de la operación, con reglas verificables y mensajes claros para quien usa Blapos.

## Cambios de interfaz

- Usa la skill del repositorio [blapos-frontend](.agents/skills/blapos-frontend/SKILL.md) al diseñar, revisar o modificar interfaces. Su punto de partida es el [sistema de diseño de Blapos](docs/frontend/README.md).
- Inspecciona la pantalla, sus equivalentes y los componentes compartidos antes de implementar. Conserva la identidad visual, la densidad y la arquitectura del área. Reutiliza componentes y tokens por función. Explica la necesidad técnica y solicita aprobación antes de incorporar dependencias, fuentes o bibliotecas de iconos.
- Conserva las reglas funcionales, los permisos y los contratos de API fuera del alcance del cambio. Para cálculos y precisión, consulta los contratos de Backend. Para opciones remotas, consulta los patrones de interacción de Frontend.
- Verifica los estados de interfaz aplicables, la accesibilidad, los anchos representativos y los flujos afectados. Ejecuta las validaciones pertinentes y registra qué quedó comprobado y qué sigue pendiente. Usa la [lista de verificación](docs/frontend/calidad/DESIGN_CHECKLIST.md).
