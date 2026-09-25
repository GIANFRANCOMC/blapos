# 10 - Libro de reclamaciones y sugerencias

## Que hace

Permite administrar reclamos, quejas y sugerencias recibidas desde System o Guest.

## Archivos

- Ruta: `routes/System/Organizations/BookComplaint.php`
- Controlador: `System/Organizations/BookComplaintController`
- Servicios: `BookComplaintService`, `BookComplaintConfigService`
- Requests: `StoreBookComplaintRequest`, `UpdateBookComplaintRequest`
- Tabla: `book_complaints`

## Campos necesarios

- `branch_id`
- `identity_document_type_id`
- `document_number`
- `name`
- `email`
- `phone_number`
- `type`
- `description`
- `request`
- `evidence`
- `admin_response`
- `status`

## Reglas

- Estados: `pending`, `in_progress`, `resolved`.
- Debe poder responderse desde System.
- Guest puede crear, System administra.
- Todo reclamo debe asociarse a una sucursal activa de la empresa.
- `BookComplaintConfigService` contiene tipos, estados y documentos de identidad.
- Actualizar un reclamo no invalida `initParams`, porque esos maestros no cambian.

## Estado de mejoras

- `admin_response` y `public_response` tienen responsabilidades separadas.
- Resolver exige ambas respuestas y registra `responded_at`/`responded_by`.
- `book_complaint_attachments` admite evidencia múltiple sin sobrecargar la cabecera.
- Cada transición genera un registro inmutable en `book_complaint_status_histories`.
- La consulta interna incluye adjuntos, historial, autor de cambios y responsable de respuesta.
- `book_complaints.branch_id` es obligatorio en nuevas migraciones; el formulario público carga las sucursales activas para que el cliente indique dónde ocurrió el caso.
- La configuración interna usa caché por usuario porque las sucursales disponibles dependen del alcance operativo del colaborador.

## Estado UI Implementado

- La gestion interna separa respuesta publica y respuesta interna.
- La modal muestra adjuntos descargables y el historial de estados sin mezclarlos con la respuesta administrativa.
- La nota de cambio de estado se envia como `status_note` para mantener trazabilidad de la atencion.
- El listado interno carga adjuntos e historial para que la modal de gestion tenga informacion completa sin una segunda consulta.
