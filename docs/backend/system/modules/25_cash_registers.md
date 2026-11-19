# 25 - Caja

## Que hace

Gestiona apertura, cierre, arqueo, resumen y trazabilidad de caja por sucursal. Caja no reemplaza ventas ni inventario: agrupa el dinero registrado por metodo de pago y permite cuadrar lo esperado contra lo contado.

Cada función de caja tiene una ruta de página independiente:

- `cash_registers.index` (`/cash_registers`)
- `cash_sessions.index` (`/cash-sessions`)
- `cash_movements.index` (`/cash-movements`)
- `cash_summary.index` (`/cash-summary`)

La vista se monta desde:

- `resources/views/System/general/Finance/{cash_registers,cash_sessions,cash_movements,cash_summary}/main.blade.php`
- `resources/js/System/Pages/Finance/{cash_registers,cash_sessions,cash_movements,cash_summary}/main.js`
- `resources/js/System/Pages/Finance/cash_registers/main.vue`, compartido como componente operativo.
- `resources/js/System/Pages/Finance/cash_registers/mount.js`, responsable de montar cada vista con su contexto inicial.

## Tablas

- `cash_registers`: cajas configuradas por empresa y sucursal. Ejemplo: Caja principal.
- `cash_sessions`: apertura y cierre de una caja. Guarda responsable de apertura/cierre, montos esperados, contados y diferencia.
- `cash_session_payments`: resumen del arqueo por metodo de pago.
- `cash_movements`: movimientos financieros asociados a una sesion de caja. Puede registrar ventas, compras, ingresos, retiros, ajustes, apertura y cierre.
- `sale_payments`: foto de los pagos aplicados a una venta.
- `payment_methods`: catalogo configurable de metodos de pago por empresa y alcance (`sale`, `purchase`, `both`).

## Reglas

- Una sucursal puede tener una o varias cajas.
- Una caja puede tener una sesion abierta o cerrada.
- La apertura crea una sesion `open` y registra un movimiento `opening`.
- El cierre calcula el esperado desde `cash_movements`, registra el contado por metodo de pago y crea un movimiento `closing` con importe cero para trazabilidad.
- Si la caja es principal y la sucursal tiene productos inventariables, el cierre exige conteo físico de inventario antes de confirmar.
- El conteo físico compara saldo del sistema contra conteo real. Cada diferencia genera un movimiento de inventario de tipo corrección con origen `physical_count` y deja una foto en `cash_session_inventory_counts`.
- Las ventas pueden asociarse a `cash_session_id` para que sus pagos alimenten `cash_movements`.
- Los métodos de pago conservan nombre, referencia y variante en la venta; caja usa esos datos para cuadrar efectivo, tarjetas, transferencias y billeteras digitales como Yape, Plin, Agora PAY u otros medios configurados.
- Caja registra dinero; Kardex e inventario registran unidades fisicas.

## Menu

- Cajas pertenece a la cabecera `Cajas`, separada de Ventas y de Restaurante para que el usuario encuentre rápido apertura, arqueo y movimientos de dinero.
- La cabecera usa `menu-parent-cash` y se ordena por empresa con `companies_sub_sections.section_order`.
- La cabecera `Cajas` expone accesos independientes para perfiles: `Cajas`, `Aperturas y cierres`, `Movimientos`, `Resumen` y `Gastos varios`.

## UI implementada

- Menú `Cajas > Cajas` con acceso `cash_registers.index`.
- Menú `Cajas > Aperturas y cierres` con acceso `cash_sessions.index`.
- Menú `Cajas > Movimientos` con acceso `cash_movements.index`.
- Menú `Cajas > Resumen` con acceso `cash_summary.index`.
- Acción `Agregar caja`: permite crear varias cajas por sucursal desde el módulo Caja. El código interno puede dejarse vacío y se genera automáticamente con prefijo `CAJ-`.
- Las acciones `Abrir caja` y `Cerrar caja` se muestran por cada caja del listado. No existen botones globales para evitar abrir o cerrar una caja equivocada.
- Entrada `POS venta` dentro de ventas usando el flujo de nueva venta, pero con título y menú propios.
- Vistas independientes de Caja:
  - `Cajas`: estado de cada caja, sesion activa y monto esperado.
  - `Aperturas y cierres`: historial de sesiones, esperado, contado y diferencia.
  - `Resumen`: totales y desglose por metodo de pago.
  - `Movimientos`: trazabilidad de apertura, ventas, ajustes y cierre.
- Acción `Registrar movimiento`: permite ingresos, salidas y ajustes manuales sobre una caja abierta, y solo se muestra dentro de la sección **Movimientos**.
- Acción `Descargar`: exporta movimientos filtrados en CSV compatible con Excel.
- Modales con `data-bs-backdrop="static"` y `data-bs-keyboard="false"` para evitar cierre accidental.
- Al cerrar una caja principal, la modal muestra un bloque de conteo físico con producto, almacén, saldo del sistema, conteo real, diferencia y observación por línea. El botón **Usar saldo sistema** precarga el conteo cuando no hay diferencias.
- Ya no existen rutas `/cash_registers/page/...`, pestañas que muten la URL ni `history.pushState`. Cada acceso resuelve su controlador, Blade y entrada Vite; la lógica operativa común se reutiliza sin duplicarla.
- La cabecera de Caja es compacta: muestra la sección activa y prioriza el selector de caja de trabajo, manteniendo el contexto operativo visible.

## Backend implementado

- `routes/System/Finance/CashRegister.php`
- `routes/System/Finance/CashSession.php`
- `routes/System/Finance/CashMovement.php`
- `routes/System/Finance/CashSummary.php`
- `app/Http/Controllers/System/Finance/CashRegisterController.php`
- `app/Http/Controllers/System/Finance/{CashSessionController,CashMovementController,CashSummaryController}.php`
- `app/Services/System/Finance/CashRegisterConfigService.php`
- `app/Services/System/Finance/CashRegisterService.php`
- `StoreCashRegisterRequest`, `OpenCashSessionRequest`, `CloseCashSessionRequest` y `StoreCashMovementRequest` encapsulan validación y normalización.
- `resources/js/System/Helpers/Requests.js` expone únicamente las rutas especiales `cash_sessions.open`, `cash_sessions.close` y `cash_summary.data`; listado, registro y exportación usan el contrato REST estándar de cada recurso.
- `CashRegisterController` conserva únicamente configuración y mantenimiento de cajas.
- `CashSessionController` atiende listado, apertura y cierre mediante `/cash-sessions`.
- `CashMovementController` atiende listado, registro y exportación mediante `/cash-movements`.
- `CashSummaryController` entrega el consolidado mediante `/cash-summary/data`.
- Los cuatro frontends tienen entradas Vite independientes y reutilizan un único componente operativo; no duplican reglas ni consultas.
- `movement`: registra ingresos, salidas y ajustes manuales.
- `export`: descarga movimientos de caja.
- `CashRegisterConfigService` entrega productos inventariables por almacén y sucursal permitida para preparar el conteo físico del cierre principal.
- `CashRegisterService::closeSession()` valida que una caja principal con inventario contable no cierre sin recibir conteos y sincroniza `cash_session_inventory_counts`.
- Los errores `422` usan el contrato común de `CompanyFormRequest` y no duplican validadores dentro del controlador.

## Alta de cajas por sucursal

- `cash_registers.store` crea una caja asociada a una sucursal activa.
- Si el codigo interno se deja vacio, `CashRegisterService` genera un codigo unico con prefijo `CAJ-`.
- Al crear una caja se invalidan las caches operativas de Caja y POS venta para que la nueva caja quede disponible sin esperar al TTL.

## Estado de mejoras

- `company_settings.cash.require_open_session_on_sale` permite exigir una caja abierta de la misma sucursal.
- Abrir, cerrar y registrar movimientos valida el alcance de caja del colaborador.
- Sesiones, movimientos y resumen aceptan fecha, sucursal, caja, responsable y método de pago; el CSV conserva el mismo filtro.
- El filtro de responsable considera apertura o cierre en sesiones y responsable directo en movimientos. El filtro de método de pago restringe sesiones con arqueo/movimiento asociado y el desglose del resumen.
- El CSV usa nombre versionado `blapos-caja-movimientos-{Ymd-His}.csv` para evitar sobrescrituras ambiguas.
- Una sesión identifica turno, caja y usuario de apertura/cierre. No se permiten dos sesiones abiertas simultáneas para la misma caja física.
## Alcance operativo

- Caja aplica el alcance efectivo de perfil y colaborador para sucursales y cajas.
- La cabecera muestra `br-operational-scope` con caja y sucursal activas para que aperturas, cierres, movimientos y resúmenes no parezcan globales.
- Un colaborador sin selección propia hereda el perfil; no obtiene acceso total automáticamente.
- Solo puede listar, abrir, cerrar o usar cajas incluidas en `role_cash_registers`/`user_cash_registers` y en sus sucursales permitidas.
- POS venta reutiliza esta misma restricción para evitar que un usuario cobre en una caja de una sucursal que no le corresponde.
- Los accesos `Cajas`, `Aperturas y cierres`, `Movimientos` y `Resumen` conservan permisos por acción independientes aunque compartan controlador.
