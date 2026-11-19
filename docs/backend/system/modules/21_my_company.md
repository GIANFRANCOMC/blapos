# 21 - Mi empresa

## Qué hace

Permite configurar los datos de la empresa visibles interna y públicamente.

## Archivos

- Ruta: `routes/System/Organizations/Company.php`
- Controlador: `CompanyController`
- Servicios: `CompanyService`, `CompanyConfigService`, `CompanySectionService`, `TenantStoragePath`
- Tablas: `companies`, `company_socials_media`, `companies_sub_sections`, `company_settings`

## Campos necesarios

- `slug`
- `internal_code`
- `identity_document_type_id`
- `currency_id`
- `document_number`
- `legal_name`
- `commercial_name`
- `tagline`
- `description`
- `address`
- `telephone`
- `email`
- `token_api_misc`
- imágenes de marca
- `status`

## Separación de configuración

- Fiscal: documento, razón social, moneda y datos usados para comprobantes y reportes.
- Pública: marca, contacto, redes, logotipo y datos visibles para clientes.
- Operativa: parámetros, módulos, integraciones y reglas que impactan caja, ventas, inventario y accesos.

La pantalla muestra estos tres bloques como guía superior para que el usuario no mezcle configuración comercial con reglas internas.

## Reglas

- `slug` debe ser único y funciona como identificador estable del tenant.
- Datos públicos alimentan Guest.
- Redes sociales deben mantenerse por tipo.
- `CompanySectionService` administra el menú habilitado y `CompanySubSectionObserver` invalida su caché automáticamente.
- La configuración operativa vive tipada en `company_settings`; los datos fiscales y públicos permanecen en `companies`.

## Estado de mejoras

- Al reemplazar una imagen se elimina el archivo anterior después de guardar la nueva referencia.
- Los archivos de marca se guardan bajo `tenants/{tenant_slug}/...`.
- `GET|POST|PATCH /master-data/company-settings` administra claves tipadas bajo los permisos de Mi empresa; valida el valor, audita la mutación e invalida `initParams` dependientes.

## Pendientes sugeridos

- Separar más adelante la edición en subpantallas fiscal, pública y operativa si el formulario crece.
