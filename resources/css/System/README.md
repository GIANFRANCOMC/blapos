# CSS System

Esta carpeta es la fuente editable de los CSS de la plataforma.

Los archivos en `public/System/assets/css` son salida generada para mantener compatibilidad con los layouts actuales que todavía usan `<link rel="stylesheet">`.

## Estructura

- `br-branding/`: tokens `--br-*`, componentes reutilizables, layout, formularios, botones, navbar, sidebar, dashboard y SweetAlert.
- `custom/`: estilos heredados o específicos por dominio mientras se terminan de migrar a componentes reutilizables. La capa `90-system-wide-visual-polish.css` carga al final para armonizar pantallas antiguas que todavía pisan el branding.
- `br-login/`: pantalla de login y accesos invitados.
- `demo/`: ajustes mínimos de plantilla.
- `platform.css`: entry preparado para Vite con el mismo orden de parciales.

## Reglas

- Declarar colores nuevos únicamente en `br-branding/00-tokens.css`.
- Consumir colores con `var(--br-*)` o `color-mix()` basado en tokens.
- No editar directamente los CSS generados en `public/System/assets/css`.
- Si un CSS vendor pisa el branding, mapear únicamente sus colores conflictivos a tokens de compatibilidad `--br-vuexy-*`; no duplicar paletas ni agregar `!important` en capas de módulo.
- Mantener prefijos `br-*` para clases reutilizables.
- Si un bloque de `custom/` empieza a servir para varios módulos, moverlo a `br-branding/`.
- La capa final de `custom/` debe consumir solo tokens `--br-*`; no declarar hexadecimales, `rgba()` ni paletas locales.

## Compilación

`npm run build` regenera primero los CSS públicos desde los parciales de esta carpeta. `npm run dev` también los genera al arrancar y vuelve a generarlos cuando cambian los parciales.

Para regenerarlos manualmente sin compilar JavaScript:

```bash
npm run build:css:system
```

El entry `resources/css/System/platform.css` se actualiza junto con los CSS públicos y queda listo para una migración posterior a `@vite`.
