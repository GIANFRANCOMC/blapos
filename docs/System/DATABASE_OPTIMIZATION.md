# Base de datos: catálogo, inventario y ventas

## Organización del esquema

Las tablas conservan los nombres técnicos existentes para evitar un refactor cosmético de alto impacto:

- `items` representa el catálogo comercial compartido por productos, servicios y membresías.
- `warehouse_items` materializa el saldo de un ítem por almacén.
- `inventory_movements` es el Kardex inmutable.
- `sales_header` contiene la cabecera comercial, entrega, pago y totales.
- `sales_body` contiene la foto histórica de cada ítem vendido.

Mientras el proyecto continúe en fase reiniciable, una columna final debe vivir en la migración que crea su tabla. Por esta razón `igv_exempt` quedó consolidado en `items`, `sales_body` y `quotation_items`; la migración correctiva independiente fue eliminada. Los campos de pago y modalidad de entrega propios de la venta quedaron consolidados en `sales_header` y `sale_delivery_methods` nace desde la migración base de ventas.

Las migraciones posteriores solo deben alterar una tabla existente cuando exista una dependencia real con otro dominio creado después, por ejemplo cotizaciones o tablas de biometría que referencian estructuras creadas por un dominio anterior.

Los campos de acciones y alcances operativos de roles y usuarios viven directamente en la migración maestra. Sus tablas de relación con sucursales, cajas y almacenes se crean en la migración base de empresas, después de existir todos los recursos referenciados.

## Integridad dentro del tenant

La base tenant es el límite de empresa. La base de datos refuerza las reglas que también valida el backend:

- marca: `internal_code` único;
- ítem: `type + internal_code` único;
- código de barras: `barcode` único cuando exista;
- categoría: `internal_code` único;
- asignación de categoría: `category_id + item_id` única;
- almacén: `branch_id + name` único;
- saldo: `warehouse_id + item_id` único;
- correlativo de venta: `serie_id + sequential` único.

MySQL permite múltiples valores `NULL` en un índice único, por lo que los ítems no físicos pueden conservar `barcode = NULL`.

## Índices operativos

No se agregan índices por intuición. Los índices compuestos reflejan filtros, relaciones y ordenamientos existentes; el aislamiento ya está resuelto por la conexión tenant:

- catálogo: estado, tipo, nombre, marca y vencimiento;
- existencias: ítem, estado y almacén;
- Kardex: fecha general, almacén/fecha, ítem/fecha y origen;
- alertas: estado/fecha y saldo/estado;
- ventas: estado/fecha, cliente, vendedor, almacén, estado de entrega y estado de pago;
- detalle de venta: cabecera, ítem y cliente;
- cuentas por cobrar: cliente/estado/fecha, vencimiento, cuotas y pagos;
- compras y cuentas por pagar: proveedor/estado/fecha, almacén/recepción, vencimiento, cuotas y pagos;
- entregas: estado, almacén, detalles y eventos.

Al añadir un filtro nuevo de alta frecuencia, primero se debe revisar la consulta y su plan con `EXPLAIN`; no se debe duplicar un índice cuyo prefijo ya cubre la consulta.

## Proyecciones y escrituras por lote

Las proyecciones derivadas no deben ejecutar una escritura por registro:

- `SystemCatalogSyncService` sincroniza módulos de empresa y permisos de roles con `upsert` por lote. Al actualizar ordenamientos conserva el estado que la empresa eligió para cada módulo.
- `BusinessProfileService` desactiva el conjunto anterior con una sola actualización y activa el conjunto seleccionado mediante un único `upsert`.
- `WarehouseItemService` consulta una vez los saldos existentes y sincroniza todos los almacenes mediante `upsert`. Al crear un almacén procesa productos en bloques de 500 e inserta únicamente las relaciones faltantes.
- `CategoryItemService` normaliza y elimina categorías duplicadas antes de actualizar todas las relaciones mediante un único `upsert`.
- El aprovisionamiento crea las series documentarias por lote y la restricción `series_branch_document_type_uq` garantiza una sola serie base por tipo de documento y sucursal.

El catálogo de navegación, los mínimos de inventario y otras preferencias existentes no deben reiniciarse durante una resincronización técnica. Las pruebas de arquitectura rechazan el regreso de `updateOrInsert`, `firstOrNew` o `firstOrCreate` dentro de estas proyecciones.

El aprovisionamiento de datos iniciales usa inserciones idempotentes para configuraciones, impuestos, métodos de pago, rubros, recursos operativos y series. Al reintentarlo, agrega los registros faltantes sin restablecer tasas, preferencias, estados o correlativos existentes. La sucursal inicial se identifica por `SUC-PRINCIPAL`, aunque se cambie su nombre visible.

El informe de saltos de correlativo lee movimientos emitidos ordenados con el índice `serie_id, action, sequential`; el CSV de auditoría se transmite con cursor. Los comandos de asistencia, membresías y notificaciones recorren el registro landlord en bloques de 100 tenants y emiten tablas en lotes de 50 filas, sin acumular toda la salida en memoria. Los avisos visibles usan una caché de 30 segundos por UUID de tenant, se invalidan al publicarse o cambiar de estado y se filtran por fecha en cada solicitud.

La asignación y el retiro de activos precargan los registros de la sucursal en una consulta por lote; las escrituras y los eventos de auditoría permanecen individuales para conservar su trazabilidad. Una prueba comprueba el límite de consultas de lectura.

En `blapos_testing`, `EXPLAIN` seleccionó los índices de estado y fecha de ventas y compras, y los índices de estado y vencimiento de cuentas por cobrar y pagar. El conjunto de prueba es pequeño, por lo que estos planes confirman la estructura disponible, pero no sustituyen una medición con datos de volumen real.

Nueva venta y Nueva cotización ya no envían el catálogo completo en `initParams`: solo conservan el cliente genérico inicial y los metadatos del formulario. `CommercialSelectionService` sirve clientes e ítems en páginas de 25 con búsqueda; ambos componentes Vue solicitan páginas al abrir y buscar, ofrecen «Cargar más» y descartan respuestas obsoletas. Al aplicar una cotización, el borrador incluye el cliente y los ítems seleccionados para mantenerlos visibles aunque no formen parte de la página remota actual. `sales.options` y `quotations.options` requieren el permiso del respectivo módulo de creación.

El POS usa la página de configuración `pos` y conserva su catálogo completo y su interfaz actual; no se le aplica la búsqueda remota de Nueva venta.

La prueba `ServiceOperationPerformanceTest` construye 1000 clientes, 1000 servicios, 1000 productos, 1000 ventas y 1000 saldos de almacén en `blapos_testing`. En una ejecución local, la configuración de Nueva venta entregó 0 ítems y 1 cliente (16 923 bytes, 25 consultas, 151 ms); la primera página de ítems entregó 25 (40 296 bytes, 5 consultas, 108 ms); el listado de ventas entregó 25 de 1000 (67 816 bytes, 12 consultas, 149 ms); el inventario entregó 25 de 1000 (23 702 bytes, 2 consultas, 53 ms). Los tiempos son orientativos y varían por máquina; la prueba protege tamaños de página y presupuestos de consultas, no tiempos de pared.

`ConcurrentOperationalWritesTest` lanza procesos PHP independientes contra la base de pruebas. Comprueba que un segundo aprovisionamiento no atraviesa el bloqueo, que dos ventas obtienen correlativos distintos y que dos salidas de una única unidad de stock producen exactamente una salida válida. Ninguna de estas pruebas opera sobre una base de producción.

## Conservación del historial

Las referencias desde ventas y Kardex hacia catálogos usan `RESTRICT`. No se puede borrar una serie, cliente, vendedor, moneda, ítem, almacén o saldo si existen documentos o movimientos históricos que lo referencian. La inactivación mediante `status` es el flujo operativo esperado.

`CASCADE` se reserva para hijos exclusivos de su cabecera —por ejemplo, detalles de una venta al eliminar el tenant completo— y para la eliminación integral de una empresa.

## Convención de modelos

Los modelos operativos no declaran relación `company()` ni scopes redundantes. Las relaciones con empresa se limitan a `Branch`, `CompanySetting`, `CompanySocialMedia` y `CompanySubSection`.

Los modelos de catálogo, inventario y ventas declaran casts numéricos, relaciones tipadas y scopes con intención de dominio como `active`, `ofType`, `forStock`, `pendingDelivery`, `issuedBetween` y `outstanding`. Los métodos públicos anteriores se conservan para mantener compatibilidad.

## Validación obligatoria

Después de modificar este esquema se debe ejecutar sobre una base de pruebas descartable:

```bash
php artisan migrate:fresh --force --no-interaction
composer format:php-check
composer check:php-syntax
php artisan test
```

Nunca se debe usar `migrate:fresh` contra una base que contenga información que deba conservarse.
