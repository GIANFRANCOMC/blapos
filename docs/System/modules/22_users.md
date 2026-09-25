# 22 - Colaboradores

## Qué hace

Administra usuarios internos, perfiles, alcance operativo y seguridad de acceso de cada empresa.

## Archivos

- Ruta: `routes/System/Organizations/User.php`
- Controlador: `UserController`
- Servicios: `UserService`, `UserConfigService`, `AuthenticationAuditService`
- Requests: `StoreUserRequest`, `UpdateUserRequest`, `ChangeUserPasswordRequest`, `RegisterUserFingerprintRequest`
- Tablas: `users`, `authentication_events`, `roles`, `role_sub_sections`, `identity_document_types`, `user_preferences`, `user_branches`, `user_cash_registers`, `user_warehouses`

## Campos necesarios

- `role_id`
- `branch_scope_mode`
- `cash_register_scope_mode`
- `warehouse_scope_mode`
- `identity_document_type_id`
- `document_number`
- `name`
- `email`
- `password`
- `session_version`
- `phone_number`
- `gender`
- `birthdate`
- `status`

## Reglas

- El usuario pertenece a una empresa y los servicios de escritura reciben `companyId` y `userId` explícitos.
- El email debe ser único según la regla empresarial vigente.
- La contraseña se guarda únicamente mediante hash.
- `role_id` controla módulos y acciones disponibles.
- Un rol con `is_full_access = true` puede ingresar a todos los módulos habilitados.
- Un rol sin acceso total queda restringido por `role_sub_sections.actions`.
- `module.permission` aplica la restricción en menú, rutas web y JSON.
- El colaborador puede heredar el alcance del perfil o reducirlo a sucursales, cajas y almacenes específicos.
- `resource.scope` y las consultas filtradas aplican el alcance operativo.
- No se puede asignar un perfil con permisos superiores a los del usuario gestor.

## Seguridad de sesión

- `PATCH /users/{id}/password` separa el cambio de contraseña y exige confirmación.
- En la vista, la contraseña solo se captura al crear colaborador; la edición usa una modal independiente para evitar cambios accidentales.
- El estado admite `active`, `inactive` y `blocked`; bloquear conserva la trazabilidad sin eliminar el colaborador.
- Cambiar contraseña o inactivar una cuenta incrementa `session_version`, elimina `remember_token` y revoca tokens Sanctum.
- `EnsureAuthenticatedSession` invalida una sesión cuya versión ya no coincide o cuyo usuario está inactivo.
- `GET /users/{id}/authentication-events` lista eventos de acceso de ese usuario, siempre limitado por empresa.
- El historial conserva evento, resultado, tenant, IP, agente, motivo y hash de sesión; nunca guarda contraseña ni ID de sesión reutilizable.
- La vista de Colaboradores expone un botón compacto de historial por usuario. El modal permite filtrar por evento, resultado, fecha desde y fecha hasta, y pagina sin perder filtros.
- El historial se presenta como lectura de seguridad; no permite modificar eventos ni mostrar secretos.
- Cambios de perfil, alcance operativo, estado y contraseña generan auditoría de negocio sin almacenar secretos.
- El alta de huella valida que el dispositivo esté activo y pertenezca a la empresa antes de reservar el identificador biométrico.

## Alcance operativo

- Un selector vacío significa **heredar del perfil**, no acceso total.
- Las selecciones se guardan en `user_branches`, `user_cash_registers` y `user_warehouses`.
- El alcance efectivo es la intersección entre perfil y colaborador.
- Crear o editar sucursales, cajas o almacenes invalida las configuraciones dependientes para no conservar opciones antiguas.
