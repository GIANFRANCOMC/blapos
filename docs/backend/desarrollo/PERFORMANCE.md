# Convenciones de rendimiento

## Carga inicial de módulos

`initParams` se reserva para configuración pequeña, acotada y reutilizable: sucursales permitidas, estados, tipos y valores de configuración. No debe transportar tablas cuyo volumen crezca con la operación, como clientes, ítems, ventas, sesiones, movimientos o eventos.

Las colecciones crecientes deben resolverse con endpoints de búsqueda o listados que cumplan estas reglas:

- conexión tenant obligatoria y, cuando corresponda, filtro por alcance de sucursal;
- selección exclusiva de las columnas consumidas por la interfaz;
- límite o paginación desde la base de datos, nunca después de cargar la colección;
- búsqueda con debounce en el frontend;
- índices compuestos alineados con los filtros y ordenamientos frecuentes.

## Composición de vistas

Cuando una pantalla necesita varias colecciones estrechamente relacionadas para su primer renderizado, el backend debe ofrecer un endpoint de composición. Esto evita cascadas de solicitudes secuenciales y permite validar el alcance una sola vez.

Las solicitudes independientes pueden ejecutarse en paralelo o después del primer renderizado. No deben bloquear el contenido principal si solo alimentan modales, filtros opcionales o acciones posteriores.

## Revisión de regresiones

Antes de cerrar un módulo nuevo o modificado se debe comprobar:

1. Tamaño del JSON de inicialización con datos representativos.
2. Número de consultas del primer renderizado y ausencia de N+1.
3. Límites, paginación y aislamiento por empresa en endpoints de catálogo.
4. Consultas que filtren por columnas respaldadas por índices.
5. Ausencia de llamadas secuenciales cuando los datos puedan componerse o cargarse en paralelo.
6. Estados de carga liberados también ante errores de red.

La prueba `ServiceOperationPerformanceTest` protege el caso de restaurante: impide volver a incluir clientes o ítems en la configuración inicial, valida el límite de opciones y comprueba la composición del tablero.

## Auditoría transversal realizada

Se revisaron todos los `*ConfigService` del sistema. Además de retirar clientes e ítems de la operación de restaurante, los módulos de gestión de activos y asistencia del personal ahora reciben opciones mínimas de usuario (`id` y `name`) en vez del modelo completo.

Los flujos de ventas, recetas, seguimiento de clientes, asistencias y membresías todavía utilizan catálogos operativos completos porque sus formularios consumen atributos y relaciones adicionales. Están identificados como migraciones progresivas al mismo patrón remoto; no deben agregarse nuevos usos equivalentes mientras se desacoplan esos formularios.

La prueba `InitParamsPerformanceConventionTest` mantiene un inventario explícito de esas dependencias heredadas y falla si un módulo agrega una nueva colección no acotada a su configuración inicial.
