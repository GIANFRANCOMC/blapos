# Estilos de Blapos

[Shared/ui.css](../Shared/ui.css) es la entrada visual común. Importa [tokens.css](../Shared/tokens.css) y las reglas compartidas de tipografía, controles, tablas, carga, foco y SweetAlert. System, acceso, Guest y Platform la consumen desde sus propias entradas.

| Superficie | Entrada Vite |
| --- | --- |
| System autenticado | [fg/index.css](fg/index.css) |
| Acceso System | [fg/auth.css](fg/auth.css) |
| Guest | [Guest/app.css](../Guest/app.css) |
| Platform | [Platform/app.css](../Platform/app.css) |
| Errores HTML | [Shared/error-page.css](../Shared/error-page.css) |

Pon en Shared toda regla con un uso transversal. Reserva [fg/modules/](fg/modules/) para composiciones de módulos de System. Reutiliza componentes y tokens por función, y usa el prefijo FG en clases y atributos visuales propios. Respeta los permisos, datos y contratos de cada superficie.

`npm run build` genera las hojas y fuentes con hash. Blade consume el manifiesto mediante `@vite`. `npm run dev` actualiza las entradas durante el desarrollo. Consulta los [estándares de interfaz](../../../docs/frontend/fundamentos/ESTANDARES_UI.md) para añadir o revisar estilos.

## Verificación

- La compilación de producción emitió las cinco entradas. Se compilaron las vistas Blade y se revisaron las hojas referenciadas por el manifiesto.
- Pasaron 292 pruebas de frontend y seis pruebas Laravel de errores, Mi espacio y navegación con sesión de prueba. `ThemeNamespace.test.mjs` revisa las fuentes, la configuración de Vite y las personalizaciones de plantilla para impedir que vuelva el prefijo retirado.
- En la demo se revisaron los accesos de System y Platform, el error 404 y el Libro de reclamaciones. En Guest se comprobó el menú a 390 px y el cambio entre registro y consulta, sin enviar formularios. Las pantallas autenticadas se verificaron mediante las pruebas Laravel.
