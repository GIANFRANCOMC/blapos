---
name: blapos-backend
description: Analiza necesidades empresariales y diseña o implementa reglas de negocio, procesos, APIs, datos y mejoras de producto para Blapos. Úsala en tareas de backend Laravel y en el análisis operativo de empresas y sucursales.
---

# Backend y análisis de negocio en Blapos

Combina tres perspectivas al decidir: quien dirige el negocio necesita control y continuidad, quien opera necesita completar la tarea con pocos pasos y quien mantiene el sistema necesita datos confiables y soporte viable.

1. **Entiende la operación.** Define quién hace qué, en qué sucursal y con qué resultado. Identifica el problema actual, su frecuencia, el costo de un error y quién lo corrige. Basa las reglas en el código, la documentación y lo que el usuario confirmó. Presenta las inferencias como supuestos y aclara solo decisiones con impacto material.
2. **Convierte la necesidad en comportamiento.** Identifica actor, permisos, disparador, datos, estados, efectos y resultado. Describe el caso normal y las excepciones que puedan cambiar stock, caja, saldos, acceso o trazabilidad. Si propones una mejora, indica qué fricción elimina y qué señal permitiría verificarlo. Si el pedido es de análisis, entrega la decisión o los hallazgos.
3. **Ubica la regla en Blapos.** Lee la ficha del módulo y sigue la ruta, el request, el servicio y las tablas antes de cambiar el flujo. Usa el servicio responsable de los movimientos o cálculos existentes. Preserva los contratos con frontend, Guest, Platform, reportes y procesos en segundo plano que correspondan.
4. **Implementa y comprueba el alcance.** Aplica la autorización y la validación en el servidor. Para escrituras críticas, revisa concurrencia, reintentos, consistencia de saldos y trazabilidad de actor, sucursal y fecha. Verifica el comportamiento con datos existentes y considera cómo se activará y sostendrá el cambio en las sucursales afectadas. Actualiza la ficha del módulo con la regla final.
5. **Explica el resultado con lenguaje operativo.** Empieza por lo que el negocio puede hacer y las excepciones que debe conocer. Para avisos y errores usa frases breves con el hecho y, si corresponde, la acción para continuar. Indica qué validaste y qué depende de una decisión pendiente. Cuando pidan persistencia o trazabilidad, identifica tabla, columna y momento de escritura.

## Lectura según el cambio

- Para descubrir necesidades, priorizar, digitalizar un proceso o diseñar un flujo multisucursal, consulta [Análisis de negocio y adopción](../../../docs/backend/proyecto/ANALISIS_DE_NEGOCIO.md).
- Para implementar rutas, validaciones, servicios o transacciones, abre el [índice de Backend](../../../docs/backend/README.md) y las [convenciones de Laravel en Blapos](../../../docs/backend/desarrollo/BACKEND_CONVENTIONS.md).
- Para cambios que cruzan sucursal, almacén, caja o tenant, consulta el [límite tenant–empresa](../../../docs/backend/tenancy/TENANT_COMPANY_BOUNDARY.md) y el alcance del módulo.
- Para dinero, cantidades, movimientos, reportes o reglas parametrizables, consulta [Generalidades](../../../docs/backend/desarrollo/GENERALIDADES.md) y el contrato específico. Reutiliza los servicios y parámetros vigentes.

