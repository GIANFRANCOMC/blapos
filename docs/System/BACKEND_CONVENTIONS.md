# Convenciones Backend System

Este archivo concentra reglas backend aplicables a controladores, requests y servicios de `System`. Las reglas visuales transversales viven en `docs/GENERALIDADES.md` y las mejoras puntuales se documentan en el módulo afectado.

## Flujo Base

- Mantener el flujo `Route -> Controller -> FormRequest -> Service -> Model`.
- El controlador orquesta: obtiene `companyId`, `userId`, datos validados y delega la regla de negocio.
- El servicio decide negocio, transacciones, trazabilidad y consultas de escritura.
- El modelo conserva relaciones, casts, scopes simples y accessors tolerantes a columnas ausentes.
- No poner reglas de negocio en Vue ni aceptar `company_id` enviado desde frontend.

## Nombres

- Controladores: verbos HTTP visibles (`list`, `store`, `update`, `show`, `export`) y acciones de dominio explicitas (`openSession`, `closeSession`, `registerMovement`).
- FormRequests: `Store<Entity>Request`, `Update<Entity>Request`, `Cancel<Entity>Request`, `Open<Entity>Request`, `Close<Entity>Request`, `Assign<Entity>Request`.
- Servicios: metodos con intencion de negocio (`createStation`, `open`, `pause`, `assignAssetToUsers`) y parametros explicitos `int $companyId, int $userId`.
- Variables: usar `$companyId`, `$userId`, `$actorId`, `$branchId`, `$warehouseId`, `$cashRegisterId`; no usar nombres genericos como `$id2`, `$data2` o `$itemData` si puede ser mas preciso.

## Requests

- Toda mutación tenant debe usar el FormRequest correspondiente; puede extender `CompanyFormRequest` para compartir normalización y configuración.
- `CompanyFormRequest` autoriza usuarios autenticados en un tenant válido, normaliza strings declarados en `normalizedStringFields()` y devuelve errores JSON homogéneos.
- Validar IDs directos con `ExistsInTenant`.
- Si el scope depende de una relacion, validar pertenencia en el servicio dentro de la transaccion o antes de escribir.
- Los mensajes bajo campos deben ser cortos: `Campo obligatorio.`, `Ingrese un numero valido.`, `Seleccione una opcion valida.`

## Seguridad De Datos

- Resolver entidades mutables por ID dentro de la conexión tenant activa y aplicar después su alcance funcional.
- Si una ruta trae `{id}`, ese ID manda sobre cualquier ID recibido en el cuerpo.
- Aplicar alcance operativo por sucursal, almacen o caja antes de ejecutar escrituras.
- Registrar `created_by`, `updated_by`, `canceled_by`, `opened_by` o el campo equivalente cuando exista.
- Evitar `auth()->user()` en servicios; pasar el actor desde el controlador.
- Evitar `request()` en servicios salvo infraestructura transversal de auditoria que solo lea metadatos de frontera.

## Transacciones

- Toda operacion con cabecera + detalle, stock, caja, pagos, impuestos, asistencias o trazabilidad debe ejecutarse en una unica transaccion.
- Usar `lockForUpdate()` cuando se cambia estado, saldo, stock, sesion activa, caja abierta o asistencia abierta.
- No actualizar saldos fisicos directamente; usar el servicio dueno del movimiento.

## Consultas Y Cache

- No reintroducir `Model::getAll($type, $companyId)`.
- `initParams` debe usar `CompanyReferenceDataService` y `MasterReferenceDataService`.
- Invalidar cache mediante `InitParamsCacheInvalidationService`; no usar `Cache::flush()`.
- No cargar registros completos cuando solo se necesitan agregados.

## Documentacion

- Cada cambio backend debe actualizar el modulo en `docs/System/modules`.
- Cambios transversales se documentan aqui, en `DEVELOPMENT_GUIDE.md` o `SECURITY_AND_AUTH.md` segun corresponda.
- Las mejoras visuales implementadas se documentan en el módulo correspondiente; no mantener pendientes visuales globales fuera de `GENERALIDADES.md`.
