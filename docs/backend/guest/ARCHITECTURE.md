# Guest - Arquitectura

## Propósito

Guest expone funciones públicas de una empresa sin requerir login interno. La empresa se deriva de `company_slug` dentro de la BD tenant ya resuelta por subdominio.

## Flujo base

1. `ResolveTenant` activa la BD correspondiente al host.
2. La ruta recibe `{company_slug}`.
3. `company.exists` exige una empresa activa y la adjunta al request.
4. El controlador usa la empresa resuelta; nunca acepta `company_id` del visitante.
5. Modelos o servicios Guest seleccionan solo datos públicos.
6. La acción aplica validación, limitación y credencial adicional cuando corresponde.

## Reutilización de System

Guest reutiliza servicios de negocio solo cuando la regla es idéntica y el servicio no depende de una sesión administrativa. La asistencia pública usa `TrackingAttendanceBusinessService`; el catálogo utiliza `GuestCatalogService` porque su contrato de exposición es distinto al catálogo interno.

## Seguridad

- Empresa desde slug y tenant, no desde payload.
- Sucursal validada contra la empresa.
- FormRequest para formularios públicos.
- Límites centralizados en `config/public_access.php`.
- URL firmada y capacidad temporal para asistencia.
- HMAC e idempotencia para dispositivos biométricos.
- Respuestas sin trazas, tokens ni columnas internas.
- Precio público únicamente con `see_my_web_price`.

Cada endpoint nuevo debe documentar propósito, autenticación pública, límite antiabuso, datos visibles y datos deliberadamente excluidos.
