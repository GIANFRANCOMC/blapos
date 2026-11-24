# Tipografía de Blapos

Source Sans 3 se carga desde `resources/fonts/source-sans-3.ttf`. [Shared/tokens.css](../../../resources/css/Shared/tokens.css) define la familia y los tamaños que comparten System, Guest y Platform. Los gráficos de la plantilla leen `--fg-font` desde la configuración común.

| Función | Token | Base |
| --- | --- | --- |
| Texto, campos, etiquetas y celdas | `--fg-body-size` | 13 px |
| Ayudas y contexto | `--fg-help-size` | 12 px |
| Unidades, contadores y metadatos compactos | `--fg-small-size`, `--fg-micro-size` | 11 px, 10 px |
| Secciones y valores destacados | `--fg-section-size` | 14 px |
| Títulos de diálogo y mensajes | `--fg-modal-title-size`, `--fg-feedback-title-size` | 16 px |
| Títulos de carga y de página | `--fg-loading-title-size`, `--fg-title-size` | 18 px, 24 px |

Usa `--fg-regular-weight` (400) para lectura, `--fg-label-weight` (500) para etiquetas y acciones, y `--fg-title-weight` (600) para títulos y énfasis. Las cifras alineadas pueden usar `font-variant-numeric: tabular-nums`. Los iconos conservan el peso técnico de su biblioteca.
