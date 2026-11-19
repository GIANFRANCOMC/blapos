# 26 - Gastos varios

## Propósito

Registra egresos operativos que no corresponden a compras de inventario. Cada gasto conserva empresa, sucursal opcional, responsable, categoría, moneda, medio de pago, referencia y observación.

## Caja y trazabilidad

- El gasto puede vincularse a una sesión de caja abierta.
- Cuando existe sesión, `MiscExpenseService` crea un `cash_movement` negativo con origen `misc_expense`.
- Al anular el gasto se anula también el movimiento asociado; no se elimina información histórica.
- Sucursales, cajas, monedas, responsables, categorías y medios de pago se validan dentro de la empresa autenticada.
- Los alcances del colaborador restringen sucursales y cajas visibles y utilizables.
- Las cinco categorías iniciales se crean de forma idempotente desde `CompanyProvisioningService::enable()`; no dependen de que ya existan empresas al ejecutar la migración.

## Interfaz y rutas

- Página: `misc_expenses.index` (`/misc_expenses`).
- Inicialización: `misc_expenses.initParams`.
- Listado paginado: `misc_expenses.list`.
- Registro: `misc_expenses.store`.
- Anulación: `misc_expenses.cancel`.

La pantalla permite filtrar, registrar y anular gastos desde `resources/js/System/Pages/Finance/misc_expenses/main.vue`.

## Archivos principales

- `app/Http/Controllers/System/Finance/MiscExpenseController.php`
- `app/Services/System/Finance/MiscExpenseService.php`
- `app/Models/System/Finance/MiscExpense.php`
- `routes/System/Finance/MiscExpense.php`
- `resources/views/System/general/Finance/misc_expenses/main.blade.php`
