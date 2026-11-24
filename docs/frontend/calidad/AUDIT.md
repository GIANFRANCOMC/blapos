# Auditoría inicial y plan de estandarización

Revisión estática de la arquitectura frontend, activos, tokens, componentes y consumidores seleccionados de System. Se examinaron el marco Blade, Control de stock, Traslados, Productos y puntos de entrada de Venta, Compras y Finanzas. Guest y Platform se revisaron en sus puntos de integración compartidos. La muestra permite identificar prioridades. Quedan pendientes recorridos autenticados con datos y permisos representativos, mediciones de usabilidad y una evaluación de accesibilidad por pantalla.

## Base comprobada

| Área | Hallazgo |
| --- | --- |
| Dependencias | El [lock PHP](../../../composer.lock) fija Laravel 10.39.0 y el [manifiesto](../../../composer.json) declara PHP ^8.1. El [lock frontend](../../../package-lock.json) fija Vue 3.5.6, Vite 5.4.6, Tailwind 3.4.12 y Vue Select 4.0.0-beta.6. La plantilla System incluye Bootstrap 5.3.2 y Font Awesome Free 6.4.2 como activos vendor. |
| Datos y superficies | [Configuración de datos](../../../config/database.php) con MySQL por defecto y conexiones landlord y tenant. Hay entradas diferenciadas para System, Guest y Platform en [Vite](../../../vite.config.js). |
| CSS y fuente | System carga el core de la plantilla y luego el branding. Hay [configuración Tailwind](../../../tailwind.config.js) para los templates Blade y reglas compartidas independientes. Inter se incorpora desde [el parcial de fuentes](../../../resources/views/shared/font-inter.blade.php). |
| Patrones estabilizados | Control de stock aporta la escala y los patrones recientes de consulta. Productos aporta controles y pestañas descriptivas. Traslados conserva su detalle con campos alineados, pestañas compactas y tabla desplazable. |

## Hallazgos y decisiones

| Hallazgo observable | Decisión de diseño y adopción |
| --- | --- |
| Coexisten Vuexy, branding, parciales de módulo y una capa final de ajustes. El [README CSS](../../../resources/css/System/README.md) aún menciona `custom/` y un nombre de parcial antiguo. | El [sistema de diseño](../fundamentos/DESIGN_SYSTEM.md) dirige al árbol actual. En cada migración, corrige la regla en el parcial propietario y revisa el orden de carga antes de retirar overrides. |
| Inventario y Productos ensamblan tablas con celdas específicas. También existe `DataTable` con un contrato más general. | Comparte superficies, estados y pie. Generaliza celdas y acciones que ya se repitan después de verificar filtros, exportación y navegación en el consumidor. |
| Conviven `FormModal` y markup local de modal en páginas como Compras y Cuentas por cobrar. | Adopta el shell compartido por flujo al intervenirlo. Conserva foco, stack de modales, validación y persistencia durante la migración. |
| Los colores base de botones requieren revisar contraste. Blanco sobre `--fg-primary` da 3.10:1, sobre `--fg-success` 3.02:1 y sobre `--fg-danger` 3.91:1. Son pares de tokens, calculados para superficies sólidas, por debajo de 4.5:1 para texto normal. | Verifica el estilo calculado en los controles y estados reales. Ajusta variantes semánticas compartidas donde corresponda. La paleta actual queda como base de identidad para esa revisión. [Contraste mínimo, W3C](https://www.w3.org/TR/WCAG22/#contrast-minimum). |
| Ventas y POS tienen entradas propias. Los listados administrativos y las operaciones dependen de distintos contextos y permisos. | Usa los mismos fundamentos con composición específica por tarea. Obtén una línea base de teclado, clics y recuperación de errores antes de cambiar un flujo transaccional. |

## Adopción incremental

| Orden | Trabajo posterior | Comprobación para cerrar |
| --- | --- | --- |
| 1 | Componentes compartidos. Revisar controles, iconos, foco, colores de texto y estados. | Consumidores de muestra y contraste en estados aplicables. |
| 2 | Navegación por mundos, menú contextual y navbar. | Ruta activa, permisos, búsqueda y menú reducido con teclado y en distintos anchos. |
| 3 | Tablas y listados. Partir de Control de stock y Productos. | Consultas, exportaciones y paginación conservan el contexto, con scroll contenido en la tabla. |
| 4 | Formularios y modales. Migrar por flujo con el editor de Productos como referencia. | Alta, edición, errores, foco, cierre y controles compuestos sin regresiones. |
| 5 | Flujos transaccionales. Revisar Nueva venta, POS, inventario, entregas, cuentas por cobrar y asistencias. | Registro repetido, teclado, importes y recuperación con datos representativos. |
| 6 | Dashboards y reportes. Ordenar información y verificar coherencia de periodos, unidades y totales. | Lectura comparativa y exportaciones verificadas. |
| 7 | Revisión visual y accesibilidad. Ampliar la muestra a Guest y Platform, roles y anchos clave. | Hallazgos y excepciones trazables por pantalla, con las evidencias disponibles. |

Cada etapa se ejecuta cuando corresponda al alcance solicitado y se valida con la [lista de verificación](DESIGN_CHECKLIST.md).
