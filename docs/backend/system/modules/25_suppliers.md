# 25 - Proveedores

## Propósito

Mantiene los terceros comerciales usados en Compras. Cada proveedor pertenece a una empresa y conserva información fiscal, comunicación principal, contactos, cuentas bancarias, condiciones comerciales e historial de desempeño.

## Archivos principales

- Rutas: `routes/System/Purchases/Supplier.php`
- Controlador: `SupplierController`
- Validación: `StoreSupplierRequest`
- Servicio: `SupplierService`
- Modelo: `Supplier`
- Vista: `resources/js/System/Pages/Purchases/suppliers/main.vue`

## Reglas

- El nombre es obligatorio.
- El número de documento es opcional, pero no puede repetirse dentro de la empresa.
- El correo debe tener formato válido.
- Un proveedor inactivo permanece en el historial, pero no puede seleccionarse en una compra nueva.
- Crear o modificar proveedores invalida la configuración dependiente de Compras.
- El listado permite editar datos e inactivar proveedores sin eliminar su historial.
- Los contactos y cuentas bancarias se guardan como colecciones hijas. El primer registro enviado se marca como principal para simplificar el uso operativo.
- Plazo de pago y límite de crédito son condiciones sugeridas para nuevas compras; no reemplazan validaciones financieras futuras.

## Interfaz

- El listado muestra condiciones y desempeño junto a identificación, contacto y estado.
- Desempeño expone cantidad de compras y monto comprado acumulado para ayudar a elegir proveedor sin abrir otra pantalla.
- La modal permite agregar contactos y cuentas bancarias en bloques compactos, con botones reutilizables y sin salir del flujo de proveedor.

## Estado backend implementado

- `supplier_contacts` admite múltiples contactos y uno principal.
- `supplier_bank_accounts` admite varias cuentas, moneda y cuenta principal.
- El proveedor incorpora plazo de pago predeterminado y límite de crédito opcional.
