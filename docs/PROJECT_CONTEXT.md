# Blapos - Contexto del proyecto

## Resumen

Blapos es una aplicacion web para gestion operativa de gimnasios, centros deportivos o negocios similares. El repositorio remoto actual es `GIANFRANCOMC/blapos` y el codigo local esta en `C:\laragon\www\blapos`.

El sistema combina administracion interna y portal publico por empresa. Internamente permite manejar empresas, sucursales, usuarios, clientes, catalogos de productos/servicios/membresias, ventas, asistencias, inventario, activos, reportes y dispositivos biometricos. Publicamente permite mostrar informacion comercial de la empresa, registrar reclamos y registrar asistencias mediante enlaces con `company_slug`.

## Stack principal

- Backend: Laravel 10, PHP 8.1.
- Frontend: Vue 3 con Vite.
- UI base: Blade para montar paginas y Vue para la interaccion.
- Estilos: Tailwind CSS.
- Autenticacion: Laravel Breeze / session auth.
- PDF: `barryvdh/laravel-dompdf`.
- Excel: `maatwebsite/excel`.
- API externa probable: consulta de documentos via helper `searchDocumentNumber` y token `token_api_misc`.
- Biometria: integracion con dispositivos ZKTeco K20 Pro.

## Usuarios esperados

- Administrador del sistema o propietario de empresa.
- Personal de recepcion o ventas.
- Personal que gestiona inventario, activos y sucursales.
- Clientes del gimnasio, principalmente mediante carnet, QR o asistencia publica.
- Visitantes publicos que consultan la web de una empresa o registran reclamos.

## Conceptos centrales

- Empresa (`companies`): tenant funcional del sistema. Casi todos los datos operativos pertenecen a una empresa.
- Sucursal (`branches`): unidad fisica dentro de una empresa. Se usa en ventas, asistencia, almacenes, activos y dispositivos.
- Usuario (`users`): operador interno autenticado. Tiene empresa, rol y datos personales.
- Cliente (`customers`): persona que compra, recibe membresias y registra asistencias.
- Item (`items`): entidad compartida para productos, servicios y membresias de catalogo.
- Membresia real (`subscriptions`): derecho de asistencia de un cliente, creado manualmente o a partir de una venta.
- Asistencia (`attendances`): check-in/check-out de un cliente en una sucursal.
- Venta (`sales_header`, `sales_body`): transaccion comercial con detalle de productos, servicios o membresias.
- Puntos (`customer_point_balances`, `customer_point_movements`): beneficio configurable por empresa que puede acumularse por monto vendido, cantidad de items, venta de membresias o items seleccionados.
- Almacen (`warehouses`, `warehouse_items`): stock por sucursal para productos.
- Activo (`assets`, `branch_assets`, `asset_assignments`): bienes administrados por sucursal y usuario.
- Portal publico: rutas por `company_slug` para home, reclamos y asistencia QR/publica.

## Reglas transversales entendidas

- Cada base tenant representa exactamente una empresa.
- Las rutas internas estan protegidas por `auth` y `verified`.
- Las rutas publicas usan `{company_slug}` y middleware `company.exists`.
- Los listados operan sobre la conexión tenant y aplican alcances de sucursal, almacén o caja cuando corresponde.
- Los estados se modelan con strings/enums: `active`, `inactive`, `canceled`, `finalized`, `pending`, `resolved`, etc.
- Los cambios importantes guardan auditoria basica: `created_by`, `updated_by`, `canceled_by`, `deleted_by` segun tabla.
- Los módulos internos publican únicamente las operaciones que implementan: normalmente `index`, `initParams`, `list`, `store` y `update`, más acciones de negocio explícitas.
- Las pantallas usan Blade como contenedor y Vue como modulo interactivo.
- Los servicios `*ConfigService` preparan datos iniciales y usan caché por empresa/página; agregan usuario cuando contienen referencias restringidas por alcance.
- Los servicios principales encapsulan reglas de negocio y acceso a modelos.

## Flujos importantes

### Venta de membresia

1. El usuario crea una venta en `sales`.
2. Si un detalle de venta tiene `type = subscription`, `SaleService` crea un registro en `subscriptions`.
3. La membresia queda asociada al cliente beneficiario, sucursal, venta y detalle de venta. Si el detalle no trae beneficiario, se usa el titular de la venta.
4. Si se anula la venta, `SaleService::cancel` cancela tambien las suscripciones activas creadas por esa venta.
5. Si la empresa lo permite, se registra un correo de agradecimiento por suscripcion para el beneficiario.

### Sistema de puntos

1. La empresa activa `company_settings.loyalty.enabled`.
2. Las reglas vigentes definen el criterio: monto total, cantidad de items, venta de membresias o items seleccionados.
3. Al confirmar una venta, `CustomerLoyaltyPointService` registra movimientos positivos y actualiza el saldo materializado del cliente.
4. Si se anula una venta, `company_settings.loyalty.reverse_points_on_sale_cancellation` define si se registra la reversa de puntos.

### Venta de producto

1. El usuario crea una venta con detalles `type = product`.
2. `SaleService` busca el almacen de la sucursal.
3. Descuenta stock en `warehouse_items`.
4. Si no existe registro de stock para ese producto, crea uno con cantidad negativa.

### Registro de asistencia

1. El cliente se identifica por carnet/id, documento, QR o biometria.
2. `TrackingAttendanceBusinessService` valida cliente, sucursal y membresia vigente.
3. Si hay asistencia activa, no permite otro check-in.
4. Si la accion es checkout, finaliza la asistencia activa.
5. Si el cliente tiene membresia vigente y no supera limites, crea asistencia `active`.

### Portal publico por empresa

1. La URL incluye `{company_slug}`.
2. El middleware carga la empresa correspondiente.
3. El home publico muestra datos comerciales, redes sociales e items visibles en la web.
4. La asistencia publica puede recibir registros QR por sucursal.
5. El libro de reclamaciones permite crear reclamos publicos.

## Convenciones importantes para futuras modificaciones

- Mantener nombres de clases y carpetas en ingles, como el proyecto actual.
- Mantener textos funcionales en espanol si ya aparecen en vistas o respuestas.
- Usar `FormRequest` cuando el modulo ya lo usa para crear/editar.
- Respetar el patron `Controller -> Service -> Model`.
- Para nuevos listados, seguir el patron `initParams` + `list` + pagina Vue.
- Para cambios multiarchivo, modificar backend, frontend, validaciones y traducciones/mensajes si aplica.
- Evitar refactors amplios salvo que el requerimiento lo pida.

## Estado transversal

- El README raíz describe Blapos, tenancy, instalación y documentación.
- Permisos combinan módulo + acción con alcances de sucursal, caja y almacén.
- Asistencia pública usa enlaces firmados; biometría usa credenciales de dispositivo, firma e idempotencia.
- Operaciones críticas conservan auditoría de negocio o trazabilidad propia.
- Los servicios de escritura principales reciben empresa y usuario explícitos.
- Los comandos tenant, instalación local y despliegue productivo están documentados.
- Los criterios visuales transversales viven en `docs/GENERALIDADES.md`; cada mejora visual implementada se documenta en su módulo.
- Las pruebas PHP se agregan o ejecutan únicamente cuando el usuario las solicita para el flujo correspondiente.
