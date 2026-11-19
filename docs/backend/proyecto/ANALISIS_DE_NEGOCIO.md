# Análisis de negocio y adopción

Usa esta guía para decidir qué debe resolver Blapos y cómo encaja en la operación de una empresa con una o varias sucursales.

## Entender el trabajo cotidiano

Observa el proceso desde tres lugares. La persona propietaria necesita saber qué pasó, cuánto le costó y quién puede corregirlo. El personal necesita registrar la operación, entender las excepciones y continuar su jornada. El responsable de tecnología necesita configurar permisos, incorporar sucursales, dar soporte y recuperar el flujo cuando falla una integración o un equipo.

| Contexto | Preguntas para decidir |
| --- | --- |
| Tienda o ferretería | ¿Qué dificultades aparecen al vender, reponer o recibir? ¿Cómo se manejan unidades, devoluciones y diferencias de inventario, si aplican? |
| Farmacia | Si el alcance incluye lotes o vencimientos, ¿en qué movimiento se necesitan y quién resuelve una diferencia? ¿Qué datos deben seguir al producto cuando cambia de almacén? |
| Consultorio o negocio de servicios | ¿Quién reserva, presta y cobra el servicio? ¿Quién puede consultar cada dato y cómo se gestionan reprogramaciones, anulaciones o pagos pendientes? |
| Gimnasio u otro negocio de membresías | ¿Qué derecho obtiene el cliente al comprar? ¿Qué límites por fecha, turno o sede corresponden? ¿Qué se conserva si cambia el plan del catálogo? |
| Empresa con varias sucursales | ¿Qué define la empresa y qué puede decidir cada sucursal? ¿Quién puede operar, aprobar, consultar otras sedes o corregir un cierre? |

Estas preguntas ayudan a descubrir el flujo de cada cliente. Las funciones, los datos sensibles y las obligaciones sectoriales requieren evidencia y alcance explícito antes de incorporarse.

## Elegir qué digitalizar y cómo introducirlo

1. Parte de una operación frecuente o costosa que ya exista. Identifica de dónde llegan sus datos, dónde se recapturan y qué comprobante o registro se usa hoy para resolver reclamos o diferencias.
2. Define un primer flujo completo que el personal pueda usar en su siguiente jornada. Decide qué pasos se pueden automatizar y qué excepciones requieren revisión humana.
3. Distingue reglas comunes, decisiones de empresa y excepciones por sucursal. Parametriza cuando dos operaciones válidas realmente difieran. Define el valor inicial, quién puede modificarlo y desde cuándo tiene efecto.
4. Antes de extender un cambio relevante, considera datos de arranque, responsables, permisos y continuidad del trabajo. Un piloto en una sucursal puede comprobar el proceso con devoluciones, errores y cierres reales antes de ampliarlo.
5. Elige una medida útil para el cambio. Puede ser el tiempo de registro, las diferencias pendientes de conciliar, las correcciones por turno o la proporción de operaciones completadas en Blapos. Usa una línea base si está disponible.

## Especificar reglas que se puedan verificar

Para una decisión importante, registra la condición, el actor, el dato que usa el sistema, la acción que permite y qué persiste. Distingue los hechos registrados, el estado actual y los parámetros que pueden cambiar. Si una operación afecta inventario o dinero, precisa el momento en que cambia el saldo y quién resuelve una diferencia. Abre el contrato del módulo antes de proponer una transición, un ajuste o una nueva entidad.

Un ejemplo de análisis es una recepción parcial de mercadería. Identifica cómo se distingue cantidad enviada, recibida y pendiente, quién confirma en destino y qué sucede si el mismo envío se confirma dos veces. Revisa la regla vigente y la trazabilidad antes de plantear cambios. [Traslados de inventario](../system/modules/16_stock_management.md).

## Redactar para la persona que opera

- Usa los términos que la persona reconoce en su trabajo. Una acción debe decir qué va a ocurrir, como «Confirmar recepción».
- Explica el problema con el dato necesario y una salida concreta. Por ejemplo, «Hay 6 unidades disponibles. Ingresa 6 o menos».
- Entrega primero el efecto sobre la operación. Añade tablas, columnas o estados cuando sean necesarios para verificar el cambio, explicar una excepción o facilitar soporte.

