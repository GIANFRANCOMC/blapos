# 90 - Maestros generales

## Qué hace

Agrupa entidades técnicas de soporte usadas por múltiples módulos.

## Entidades

- `identity_document_types`: tipos de documento de personas.
- `document_types`: tipos de comprobante/documento comercial.
- `currencies`: monedas.
- `taxes`: tributos separados por alcance de venta o compra.
- `payment_methods`: medios de pago configurables por alcance.
- `company_settings`: políticas tipadas por grupo y clave.
- `Automáticos`: valores calculados de solo lectura, sin tabla ni configuración manual duplicada.

## Reglas

- Deben mantenerse estables porque alimentan formularios y relaciones.
- No se eliminan cuando están referenciadas; la inactivación se bloquea en los maestros estructurales.
- Cambios afectan usuarios, clientes, empresas, ventas, compras, caja y reportes.
- `company_settings` no debe almacenar secretos, tokens ni credenciales; esos valores pertenecen al entorno seguro.
- Tributos y métodos de pago usan `code + scope` como identidad lógica para separar ventas y compras.

## Pantalla administrativa

- Ruta UI: `/master-data`.
- Entrada de menú: Administración -> Ajustes -> Maestros internos.
- Recursos administrables: configuraciones, tributos, métodos de pago, documentos de identidad, tipos de comprobante y monedas.
- El apartado **Automáticos** muestra el valor actual, su condición y su origen. No ofrece edición.
- Cada recurso muestra tipo, alcance e impacto antes de editar o inactivar.
- La inactivación usa confirmación accesible con descripción del alcance operativo y del impacto esperado.
- Los `vue-select` de esta pantalla muestran `Sin opciones disponibles` para evitar textos residuales de librería.
- Los métodos de pago permiten imagen referencial; el backend guarda el archivo bajo storage del tenant.

## Estado de mejoras

- `MasterDataService` y `/master-data/{resource}` permiten listar, crear y modificar los maestros soportados por empresa.
- Inactivar se bloquea cuando existen referencias operativas.
- Las mutaciones invalidan maestros cacheados y todos los `initParams` dependientes.
- `MasterDataCodes` centraliza DNI, RUC, CE, boleta, factura y PEN para evitar literales dispersos.
- Los datos iniciales permanecen versionados en la migración exclusiva de inserts.
- `AutomaticMasterDataService` declara los datos condicionados. Actualmente `customer_fingerprint_registration` vale `true` solo cuando el módulo `tracking_attendances.index` está activo para el tenant. `CompanySectionService::isModuleEnabled()` consulta la asignación vigente de la empresa sin depender de la caché del formulario.
- Clientes recibe este valor en `initParams` y solo muestra **Registrar huella** cuando está activo. Sin el módulo, tampoco expone la lista de dispositivos biométricos en esa respuesta. El `RegisterCustomerFingerprintRequest` vuelve a comprobarlo en el servidor antes de validar o guardar; una llamada directa con el módulo inactivo devuelve 403.
- `GET /master-data/automatic` resuelve los valores al consultar. `POST` y `PATCH` sobre ese recurso están prohibidos. Para añadir otra condición se define una nueva clave y su fuente en `AutomaticMasterDataService`; no se debe almacenar una copia manual de un valor calculable.

## Pendientes sugeridos

- Agregar auditoría visual por registro en la UI cuando se consolide el módulo de auditorías.
- Incorporar filtros por estado y alcance cuando crezca el volumen de maestros.
