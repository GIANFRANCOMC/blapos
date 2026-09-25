# Tablas y relaciones

Este documento describe la organización vigente del esquema. Las migraciones son la fuente exacta de columnas, índices y claves foráneas.

## Límite de datos

Cada tenant tiene una base de datos propia y representa exactamente una empresa. Las tablas operativas no repiten `company_id`; su aislamiento proviene de la conexión tenant resuelta.

Solo conservan `company_id` estas relaciones directas con `companies`:

- `branches`;
- `company_settings`;
- `company_socials_media`;
- `companies_sub_sections`.

La justificación y las reglas para código nuevo están en `TENANT_COMPANY_BOUNDARY.md`.

## Base landlord

La conexión `landlord` contiene únicamente administración central:

| Tabla | Responsabilidad |
| --- | --- |
| `platform_users` | Usuarios exclusivos de `app.<TENANCY_BASE_DOMAIN>`. |
| `tenant_databases` | Registro técnico del tenant: UUID público, slug, base, estado y resolución. |
| `tenant_domains` | Dominios y subdominios asociados al tenant. |
| `tenant_audit_logs` | Bitácora de operaciones de plataforma. |
| `tenant_announcements` | Avisos dirigidos a los usuarios de un tenant. |

Landlord no guarda el ID local de `companies`. El tenant se identifica externamente por `public_id` y se conecta por `database_name` validado.

## Empresa, configuración y navegación

| Tabla | Responsabilidad |
| --- | --- |
| `companies` | Única empresa raíz de la base tenant. |
| `branches` | Sucursales relacionadas formalmente con la empresa. |
| `company_settings` | Configuración extensible por grupo y clave. |
| `company_socials_media` | Enlaces públicos del perfil empresarial. |
| `companies_sub_sections` | Módulos habilitados y ordenados para la empresa. |
| `menu_categories`, `sections`, `menu_groups`, `sub_sections` | Catálogo de navegación. |
| `roles`, `role_sub_sections` | Perfiles y permisos por módulo. |
| `role_branches`, `role_cash_registers`, `role_warehouses` | Alcances operativos de perfiles. |
| `users` | Colaboradores autenticables del tenant. |
| `user_branches`, `user_cash_registers`, `user_warehouses` | Restricciones adicionales del usuario. |
| `user_preferences`, `user_navigation_metrics` | Preferencias, rutas recientes y contador de uso. |

`company_settings` y `companies_sub_sections` usan una clave única compuesta con `company_id` porque son extensiones directas de la raíz. Las demás tablas son únicas dentro de su base tenant.

## Maestros y catálogo comercial

- `identity_document_types`, `document_types`, `currencies`;
- `brands`, `categories`, `category_items`, `items`;
- `taxes`, `payment_methods`, `payment_method_variants`;
- `business_industries`, `business_industry_module_sets`;
- `loyalty_point_rules`, `loyalty_point_rule_items`.

Estos catálogos pertenecen implícitamente al tenant actual. Las reglas HTTP usan `ExistsInTenant` y `UniqueInTenant`.

## Sucursales, almacenes e inventario

| Tabla | Responsabilidad |
| --- | --- |
| `series` | Series documentarias de una sucursal. |
| `series_correlative_movements` | Trazabilidad inmutable de correlativos. |
| `warehouses` | Almacenes relacionados con sucursales. |
| `warehouse_items` | Saldo materializado por almacén e ítem. |
| `inventory_movements` | Kardex inmutable. |
| `inventory_stock_alerts` | Alertas de existencias. |
| `inventory_guides`, `inventory_guide_items` | Guías y sus detalles. |

Las claves funcionales evitan duplicados, por ejemplo `warehouse_id + item_id` para saldos y `serie_id + sequential` para documentos.

## Ventas, entregas y cuentas por cobrar

- `sales_header`, `sales_body`;
- `sale_taxes`, `sale_payments`;
- `sale_delivery_methods`;
- `sale_deliveries`, `sale_delivery_items`, `sale_delivery_events`, `sale_delivery_event_items`;
- `sale_accounts_receivable`, `sale_receivable_installments`, `sale_receivable_payments`;
- `quotation_headers`, `quotation_items`, `quotation_taxes`.

La cabecera conserva la foto comercial y los totales. Sus hijos se relacionan por `sale_header_id` o por la cabecera especializada correspondiente. Las entregas pendientes se determinan por su estado, no por una modalidad de entrega.

## Compras, recepciones y cuentas por pagar

- `purchase_headers`, `purchase_items`, `purchase_taxes`, `purchase_payments`, `purchase_expenses`;
- `purchase_receipts`, `purchase_receipt_items`;
- `purchase_returns`, `purchase_return_items`;
- `purchase_accounts_payable`, `purchase_payable_installments`, `purchase_payable_payments`;
- `suppliers`, `supplier_contacts`, `supplier_bank_accounts`.

Compras replica el patrón de cabecera, detalle, recepción y obligación financiera de ventas sin duplicar el límite de empresa.

## Caja y gastos

- `cash_registers`, `cash_sessions`, `cash_movements`, `cash_session_payments`;
- `cash_session_inventory_counts`;
- `misc_expense_categories`, `misc_expenses`.

El alcance se establece por sucursal y caja. Los gastos conservan responsable, método, sesión y trazabilidad.

## Clientes, membresías y fidelización

- `customers`;
- `subscriptions`, `subscription_emails`;
- `attendances`;
- `customer_biometric_fingerprints`;
- `customer_point_balances`, `customer_point_movements`.

Los documentos de cliente son únicos dentro del tenant según su tipo y número. Las membresías y asistencias se relacionan con cliente y sucursal.

## Operación de servicios y restaurante

- `service_floors`, `service_stations`;
- `service_sessions`, `service_session_items`, `service_session_events`, `service_session_pauses`;
- `recipe_dishes`, `recipe_dish_components`;
- `recipe_dish_options`, `recipe_dish_option_components`;
- `recipe_dish_toppings`, `recipe_toppings`, `recipe_topping_components`;
- `recipe_waste_records`.

Las sesiones se acotan por sucursal/estación y sus detalles por cabecera. Las recetas enlazan componentes del catálogo sin repetir empresa.

## Organización, asistencia y activos

- `user_attendances`, `user_attendance_breaks`, `user_attendance_corrections`, `user_work_schedules`;
- `biometric_device_brands`, `biometric_device_models`, `biometric_devices`, `biometric_device_events`;
- `user_biometric_fingerprints`;
- `asset_categories`, `assets`, `asset_assignments`, `asset_assignment_logs`, `branch_assets`.

La pertenencia se deriva de usuario, sucursal o dispositivo. Los servicios deben aplicar los alcances autorizados antes de mutar datos.

## Auditoría, autenticación y atención

- `authentication_events`, `business_audit_logs`, `external_api_request_logs`;
- `book_complaints`, `book_complaint_attachments`, `book_complaint_status_histories`;
- tablas estándar Laravel: `password_reset_tokens`, `personal_access_tokens`, `failed_jobs`.

Las bitácoras operativas ya están físicamente aisladas por tenant. No almacenan un `company_id` redundante.

## Reglas para migraciones

- Corregir la migración de origen mientras el proyecto siga sin producción.
- Declarar claves foráneas hacia la relación funcional inmediata.
- Diseñar índices según filtros y ordenamientos reales; no anteponer una columna de empresa inexistente.
- Mantener unicidad tenant-wide para códigos y documentos que antes dependían de `company_id`.
- Ejecutar la reconstrucción únicamente sobre `blapos_testing` y verificar `TenantCompanyBoundaryTest`.
