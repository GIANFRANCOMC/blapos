# CSS System

FG es el único sistema visual del ERP System. La fuente editable es [fg/](fg/); se carga globalmente desde el layout. Consultar la [guía de implementación, componentes y verificación](../../../docs/frontend/figma/IMPLEMENTACION_FG.md).

## Editar estilos

- Declarar colores, tipografía y medidas compartidas en fg/00-tokens.css.
- Usar clases fg-* y variables --fg-* en código nuevo.
- Cambiar la fuente compartida por función y revisar sus consumidores.
- Iconos y loaders heredan el color del texto del botón.
- No agregar banderas por página, duplicar la biblioteca ni usar !important para competir con plantillas.
- Conservar contratos, modelos, opciones remotas, estados disabled y validaciones.
- Enlazar el CSS desde el head mediante Vite, antes de montar Vue.

## Compilación

npm run build genera assets FG, fuentes y manifest de Vite. npm run dev mantiene su recarga durante el desarrollo.

## Archivos anteriores todavía referenciados

br-branding/, br-login/, custom/, demo/ y platform.css son fuentes previas que siguen alimentando Guest y autenticación. System autenticado ya no carga la apariencia de Vuexy ni esos bundles.

npm run build:css:system continúa generando archivos públicos para esas referencias. No editar la salida de public/System/assets/css ni eliminarla mientras tenga consumidores. Las siguientes migraciones deben adoptar FG y retirar la referencia anterior cuando corresponda.

La paginación, el contador, los loaders y los controles conservan sus contratos funcionales. La presentación nueva se resuelve en FG.
