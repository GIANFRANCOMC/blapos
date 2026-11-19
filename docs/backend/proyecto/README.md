# Documentación de Blapos

## Guías por área

| Área | Qué encontrarás |
| --- | --- |
| [Frontend](../../frontend/README.md) | Diseño, estilos, navegación, formularios, tablas, accesibilidad y revisión visual. |
| [Backend](../README.md) | PHP y Laravel, reglas de negocio, bases de datos, seguridad, tenants y pruebas. |
| [System](../system/README.md) | Módulos de la plataforma interna, con sus rutas, permisos y comportamiento. |
| [Guest](../guest/README.md) | Módulos del portal público y servicios que expone cada empresa. |

## Referencias del proyecto

- [Contexto del producto](PROJECT_CONTEXT.md) y [arquitectura general](../arquitectura/ARCHITECTURE.md).
- [Análisis de negocio y adopción](ANALISIS_DE_NEGOCIO.md) para priorizar mejoras de producto y digitalizar la operación por sucursal.
- [Generalidades](../desarrollo/GENERALIDADES.md) para contratos que comparten frontend y backend, como precisión numérica.
- [Instalación y operación](../despliegue/INSTALACION.md) para levantar el entorno, desplegar y operar tenants.
- [Guía para solicitar cambios](REQUEST_GUIDE.md) para especificar alcance y verificaciones.

## Dónde documentar un cambio

- Actualiza el módulo correspondiente en [System](../system/modules) o [Guest](../guest/modules) si cambian sus reglas, sus datos o sus rutas. Conserva la numeración de los módulos y registra las propuestas pendientes en `new_requirements` del dominio correspondiente.
- Si cambia el esquema o una relación, actualiza el inventario de tablas de [System](../datos/system/TABLES.md) o [Guest](../datos/guest/TABLES.md).
- Documenta las decisiones de interfaz en [Frontend](../../frontend/README.md). Enlaza desde la ficha del módulo cuando su flujo deba referir a la decisión visual.
- Documenta los cambios de contrato dentro de [Backend](../README.md) o [Generalidades](../desarrollo/GENERALIDADES.md), según su alcance.
