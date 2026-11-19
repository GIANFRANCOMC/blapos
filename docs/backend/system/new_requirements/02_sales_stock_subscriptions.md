# 02 - Ventas, stock y membresias

## Riesgo evaluado

La venta crea membresías y descuenta stock. Se evaluó el riesgo de generar saldos negativos sin política explícita, usar series ajenas a la sucursal o perder trazabilidad al anular.

## Requerimientos evaluados

- Política de stock negativo definida por empresa.
- Reposición al anular gobernada por configuración empresarial.
- Serie validada contra sucursal y empresa.
- Correlativo protegido contra concurrencia y duplicados.
- `attendance_limit_per_day` heredado desde membresía de catálogo.

## Impacto cerrado

El contrato quedó centralizado en `SaleService`, inventario, membresías y reportes.

## Estado backend

- Venta normal y POS reutilizan `SaleService`, `CommercialDocumentSettlementService` e `InventoryMovementService`.
- La política de stock negativo, reposición por anulación y caja obligatoria se resuelve desde `company_settings`.
- Serie, almacén y caja se validan contra empresa, sucursal, estado y alcance del colaborador.
- Las membresías heredan límite diario y aplican una política de solapamiento configurable.
- La renovación crea un registro nuevo enlazado mediante `renewed_from_id`; no reescribe el histórico.

Las pruebas funcionales se incorporarán cuando sean solicitadas expresamente. No constituyen un pendiente de implementación productiva en esta fase.
