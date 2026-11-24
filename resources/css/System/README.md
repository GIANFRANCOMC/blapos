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

Bootstrap y SweetAlert2 entran desde `Shared/ui.css` en la capa `fg-vendor`. Vue Select se importa en la hoja de System y en la de Guest. System también carga Select2 desde su propia hoja. Los componentes Vue reutilizan esas cargas. El scroll, el foco y el skeleton llegan mediante `Shared/ui.css`.

El layout autenticado de System usa `fg-up.blade.php` y `fg-down.blade.php`. Guest mantiene sus propios parciales de metadatos y scripts. La presentación de su menú vive en la hoja de Guest y el desplegable usa Bootstrap. Se retiraron las hojas de plantilla Vuexy sin consumidores y la entrada sin uso de Tailwind.

`npm run build` genera las hojas y fuentes con hash. Blade consume el manifiesto mediante `@vite`. `npm run dev` actualiza las entradas durante el desarrollo. Consulta los [estándares de interfaz](../../../docs/frontend/fundamentos/ESTANDARES_UI.md) para añadir o revisar estilos.

## Verificación

- La compilación de producción emitió las cinco entradas. Se compilaron las vistas Blade y se revisaron las hojas referenciadas por el manifiesto.
- Pasaron 293 pruebas de frontend y seis pruebas Laravel de navegación autenticada, Mi espacio y páginas de error. `ThemeNamespace.test.mjs` comprueba los parciales de System, las referencias a temas retirados y la carga duplicada de Vue Select y del skeleton.
- En la demo se revisaron los accesos de System y Platform, el Libro de reclamaciones a 1280 y 390 px y el cierre del menú con Escape y retorno de foco. El catálogo público mostró sus tarjetas en escritorio sin desbordamiento horizontal. Las verificaciones en navegador se hicieron sin enviar formularios.
