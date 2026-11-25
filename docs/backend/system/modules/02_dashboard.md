# 02 - Resumen del negocio

## Qué hace

Este acceso se encuentra en **Analítica → Indicadores**, junto a Reportes. La página conserva la ruta interna `dashboard.index`.

Entrega indicadores operativos agregados para una fecha empresarial y, opcionalmente, una sucursal permitida. No carga ventas, asistencias ni membresías completas: toda suma y conteo se resuelve en SQL.

## Backend

- Controlador: `DashboardController`.
- Servicio: `DashboardService`.
- Configuración: `DashboardConfigService`.
- Fuentes: `company_settings`, `sales_header`, `series`, `attendances`, `subscriptions`, `branches` y `users`.

## KPIs oficiales

- **Ventas netas:** cantidad y suma de `sales_header.total` con estado `active` e `issue_date` igual a la fecha consultada.
- **Ventas canceladas:** cantidad e importe de ventas cuyo `canceled_at` ocurrió dentro del día consultado; no usa la fecha de emisión.
- **Asistencias del día:** cantidad de ingresos `active` o `finalized` cuyo `start_date` está dentro del día.
- **Membresías por vencer:** membresías activas cuyo `end_date` cae dentro de la ventana configurada.
- **Sucursales y usuarios activos:** conteos escalares complementarios.

## Configuración y alcance

- `company_settings.localization.timezone` define la zona horaria; el fallback es `America/Lima`.
- `company_settings.dashboard.membership_expiration_window_days` define la ventana; el fallback es 7 días.
- `branch_id` es opcional. Cuando se envía, `resource.scope` valida que el usuario tenga acceso a esa sucursal.
- Sin una sucursal seleccionada, ventas, asistencias, membresías y el conteo de sucursales activas se limitan a las sucursales autorizadas por el perfil. El indicador de sucursales activas abarca todo ese alcance y el de usuarios activos conserva el conteo general de la empresa.
- `initParams` incluye las sucursales permitidas con identificador, nombre y estado. Las opciones se consultan fuera de la caché compartida de configuración. Se incluyen sucursales inactivas para consultar historial.
- Todas las consultas usan la conexión tenant y ejecutan `COUNT`/`SUM` en base de datos.

## Contrato

`GET /dashboard/initData?date=YYYY-MM-DD&branch_id=ID` devuelve fecha, zona horaria, alcance y los agregados. No contiene colecciones de registros.

## Interfaz

- Fecha y sucursal usan los campos FG. Buscar aplica ambos filtros y Limpiar restaura el día actual y todas las sucursales permitidas para la próxima búsqueda. La consulta muestra el alcance aplicado y permite reintentar ante un error.

- La vista consume directamente los agregados `sales.net`, `sales.canceled`, `attendances`, `expiring_subscriptions` y `branches.active_count`.
- Los KPIs visibles son ventas netas, ventas anuladas, asistencias del día, membresías por vencer y sucursales activas.
- El gráfico horario queda defensivo: solo usa registros si el backend los envía explícitamente; no solicita colecciones completas desde Vue.
