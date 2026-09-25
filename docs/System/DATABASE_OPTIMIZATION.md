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
