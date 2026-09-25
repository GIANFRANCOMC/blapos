# 91 - Menú, módulos y permisos

## Contrato

- `sections` y `sub_sections` definen catálogo y rutas.
- `companies_sub_sections` habilita y ordena módulos por empresa.
- `role_sub_sections` concede acciones por perfil.
- Los alcances operativos restringen sucursales, cajas y almacenes.
- El orden del menú y el árbol de perfiles provienen de `CompanySectionService`, por lo que conservan la misma estructura.
- `config/permissions.php` mapea endpoints compartidos con el módulo visible correcto.

## Seguridad

El menú es una representación, no una autorización. Toda ruta System exige `module.permission`; los recursos operativos exigen además `resource.scope`. Los perfiles de acceso total siguen sujetos al aislamiento de la conexión tenant.
