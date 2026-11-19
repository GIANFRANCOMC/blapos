# 01 - Seguridad y permisos

## Estado

Implementado en backend.

System separa cuatro decisiones:

1. La empresa habilita módulos mediante `companies_sub_sections`.
2. El perfil concede módulos y acciones mediante `role_sub_sections.actions`.
3. Perfil y colaborador delimitan sucursales, cajas y almacenes.
4. Las mutaciones sensibles generan registros en `business_audit_logs`.

Ocultar una opción del menú no autoriza ni bloquea por sí solo. Las rutas protegidas usan `module.permission` y `resource.scope` y responden `403` cuando la operación no está permitida.

## Acciones

- `view`: consultar páginas, listados y detalles.
- `create`: registrar datos.
- `update`: modificar datos y revisar correcciones.
- `delete`: eliminar cuando el módulo lo admita.
- `export`: descargar archivos.
- `import`: ejecutar cargas masivas.
- `operate`: ventas, movimientos, cierres, recepciones, pausas o traslados.

`RolePermissionService` resuelve acción y módulo desde `config/permissions.php`. Los endpoints técnicos compartidos se mapean a sus módulos visibles para evitar permisos implícitos.

## Alcances operativos

- Perfil: `all` o `restricted` para sucursal, caja y almacén.
- Colaborador: `inherit` o `restricted`; solo puede reducir el perfil.
- Alcance efectivo: intersección entre perfil y colaborador.
- Cajas y almacenes se filtran además por las sucursales efectivas.
- `is_full_access = true` identifica al administrador.

El backend impide que un usuario delegue módulos, acciones o recursos que no posee. También impide retirar o inactivar el último acceso total si no existe otro usuario administrador activo.

## Perfiles

- Crear y editar sincroniza módulos, acciones y alcances dentro de una transacción.
- Duplicar crea un perfil independiente, sin acceso total, copiando permisos y alcances válidos.
- Los cambios invalidan cachés de permisos, menú e `initParams` afectados.
- Usuarios, perfiles, permisos, configuraciones, impuestos, métodos de pago, sucursales y cajas se auditan automáticamente.

## Auditoría

`business_audit_logs` conserva empresa, sucursal, actor, módulo, acción, modelo, registro, resumen, valores anteriores/nuevos, contexto, IP, agente y fecha. Secretos, contraseñas, tokens y plantillas biométricas se excluyen del payload.
