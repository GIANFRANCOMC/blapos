# Blapos · Diccionario visual y de contenido

## Fundamentos vigentes

| **Rol** | **Valor** | **Uso** |
| --- | --- | --- |
| Acción | #176fae | Botones con texto blanco y enlaces de acción. |
| Marca base | #2899e5 | Identidad; no asumir contraste suficiente para todo texto pequeño blanco. |
| Azul suave | #eaf6fd | Selección sin competir con la tarea principal. |
| Etiqueta de selección | #eaf6fd / #1a1a35 | Fondo azul suave / texto y × oscuros; contraste 15,36:1. |
| Marino | #1a1a35 | Mundos, énfasis y texto de todos los badges: Success, Warning y Danger. |
| Texto | #1e293b | Contenido operativo. |
| Ayuda | #607086 | Metadatos y ayuda secundaria. |
| Texto de mensajes en línea | #1a1a35 | Información, Éxito, Atención y Error usan color/navy; conservar el fondo semántico de cada tono. |
| Área de trabajo | #f4f7fd | Fondo general. |
| Superficie | #ffffff | Paneles, controles y modales. |
| Cabecera de tabla | #f1f5f9 | Diferenciar columnas sin saturar. |
| Borde | #d9e3ef | Divisiones suaves. |
| Borde de control | #8494a7 | Límite legible de controles. |
| Success | #dcf8ea / #1a1a35 | Activo y Disponible. |
| Warning | #fff3d4 / #1a1a35 | Inactivo y Stock bajo. |
| Danger | #fee6e7 / #1a1a35 | Badge Agotado: fondo rojo suave y el mismo texto oscuro que Success y Warning. |
| Obligatorio | #c92d35 | Asterisco y error; no cambiar requisitos de negocio. |
| Plantilla Excel | #dcf8ea / #066647 / #b6e6cd | Fondo / texto e icono / borde. |
| Tooltip de mundo | #176fae / #ffffff | Mismo azul del mundo activo. |
| Tooltip de salida | #c92d35 / #ffffff | Cerrar sesión. |

Tres colecciones: Primitivos (valores), Color (roles con aliases) y Geometría (espacios, radios y tamaños). Usar roles semánticos, no copiar valores por pantalla.

### Tipografía

Una familia: **Source Sans 3**, pesos Regular 400, Medium 500 y SemiBold 600. Seis estilos: Cuerpo 13/400; Etiqueta 13/500; Ayuda 12/400; Sección 14/600; Título de modal 16/600; Título de página 24/600. Interlineado 135 %, excepto página 125 %. Alinear cantidades a la derecha y mantener unidad y número juntos. La fuente de la aplicación sigue siendo Inter hasta implementar la adopción compartida.

### Geometría

Espacios: 4, 6, 8, 10, 12, 16, 20, 24, 32 y 48 px. Radios: 6 px controles y badges; 12 px paneles y modales. Control: 36 px escritorio, 44 px táctil. Icono habitual: 14–18 px. Badge: 26 px de alto y 10 px de padding horizontal. Separar el tamaño visual de iconos y enlaces de su área táctil mínima de 44 px.

Elevación Panel: 0/8/24, marino con opacidad 14 %. Menú: 0/6/16, marino 12 %. No repetir sombras en cada fila o control.

## Estructura común

1. Menú de mundos, divisor y panel de submódulos.
2. Barra superior de la plataforma.
3. Cabecera: título, descripción opcional de una línea y acción principal.
4. Búsqueda y filtros.
5. Contexto del conjunto y orden.
6. Tabla, carga, vacío o error.
7. Cantidad de resultados y paginación.

No agregar breadcrumb por ahora. Los submódulos usan solo texto, sin logo ni iconos repetidos. El título del mundo tiene jerarquía propia y los grupos mantienen el mismo eje de sus enlaces.

En tablas: centro vertical en cabeceras; mismas anchuras e insets que las filas. Texto a la izquierda, importes a la derecha y estados/acciones centrados. Badge de ancho natural dentro de una celda que conserva su ancho.

Modales: cabecera y pie estables; desplazar el contenido. El ancho se ajusta a la tarea y al viewport; no crear una geometría distinta por conveniencia de cada pantalla.

## Terminología

| **Término** | **Significado / regla** |
| --- | --- |
| Mundo | Área funcional principal: Catálogo e inventario, Ventas, etc. El menú no muestra el rótulo MUNDOS. |
| Submódulo | Tarea dentro de un mundo: Productos, Servicios, Control de stock. |
| Componente | Maestro compartido con API, estados y guía. |
| Instancia | Uso vinculado del maestro con su contenido y variante. |
| Composición | Agrupación de componentes para una tarea del módulo. |
| Token | Valor de diseño identificado por función; una fuente común. |
| Variante | Estado o configuración soportada; no una copia independiente. |
| Selección única | Un valor, con limpiar si el contrato lo permite. |
| Selección múltiple | Varios valores expresados como etiquetas removibles. |
| Filtros pendientes | Lo que se ha editado antes de Buscar. |
| Filtros aplicados | El conjunto vigente al paginar y exportar. |
| Nombre A–Z | Ascendente. |
| Nombre Z–A | Descendente. |
| Activo | Registro habilitado según sus reglas operativas. No equivale a tener stock. |
| Inactivo | Registro deshabilitado; badge Warning, igual estilo que Stock bajo. |
| Disponible | Cantidad positiva mayor al mínimo según el contrato del módulo. |
| Stock bajo | Cantidad positiva menor o igual al mínimo. |
| Agotado | Cantidad menor o igual a cero. |
| Consulta | Lee datos, no pide una decisión si no hay acción que confirmar. |
| Confirmación | Explica acción, consecuencia y alcance antes de ejecutarla. |
| Resultado incierto | No se pudo confirmar si el servidor guardó; revisar antes de repetir. |

## Vocabulario de acciones

Usar Agregar, Guardar cambios, Buscar, Limpiar, Importar productos, Exportar productos, Plantilla Excel, Generar, Cerrar y Reintentar. Nombrar el objeto si evita confusión. Mantener la misma acción entre botón, carga y resultado. Evitar Descargar listado en Excel, Alta rápida y un Aceptar/Sí sin contexto.

Los mensajes no prometen persistencia o descarga terminada antes de confirmarla. No mostrar trazas, IDs técnicos ni porcentajes inventados. Los datos operativos y límites pertenecen al contrato del submódulo.


## Registro completo de tokens

Los 71 tokens están centralizados en tres colecciones. La sintaxis WEB identifica el contrato de adopción; los alias nuevos de Figma no instalan variables CSS en producción.

| **Colección** | **Token** | **Valor** | **Alias** | **Sintaxis WEB** |
| --- | --- | --- | --- | --- |
| Primitivos | primary | #2899e5 | — | `var(--br-ref-primary)` |
| Primitivos | action | #176fae | — | `var(--br-ref-action)` |
| Primitivos | soft | #eaf6fd | — | `var(--br-ref-soft)` |
| Primitivos | navy | #1a1a35 | — | `var(--br-ref-navy)` |
| Primitivos | text | #1e293b | — | `var(--br-ref-text)` |
| Primitivos | muted | #607086 | — | `var(--br-ref-muted)` |
| Primitivos | white | #ffffff | — | `var(--br-ref-white)` |
| Primitivos | surface | #f4f7fd | — | `var(--br-ref-surface)` |
| Primitivos | subtle | #f9fbfd | — | `var(--br-ref-subtle)` |
| Primitivos | chrome | #f1f5f9 | — | `var(--br-ref-chrome)` |
| Primitivos | border | #d9e3ef | — | `var(--br-ref-border)` |
| Primitivos | control | #c5d0dc | — | `var(--br-ref-control)` |
| Primitivos | danger | #c92d35 | — | `var(--br-ref-danger)` |
| Primitivos | dangerSoft | #fee6e7 | — | `var(--br-ref-dangersoft)` |
| Primitivos | success | #07875f | — | `var(--br-ref-success)` |
| Primitivos | successSoft | #dcf8ea | — | `var(--br-ref-successsoft)` |
| Primitivos | warning | #fff3d4 | — | `var(--br-ref-warning)` |
| Primitivos | stock-saludable/texto | #066647 | — | `var(--br-ref-stock-saludable-texto)` |
| Primitivos | control/borde-activo | #8494a7 | — | `var(--br-ref-control-borde-activo)` |
| Primitivos | plantilla/borde-suave | #b6e6cd | — | `var(--br-ref-plantilla-borde-suave)` |
| Primitivos | rojo/oscuro | #9f2533 | — | `var(--br-ref-rojo-oscuro)` |
| Color | color/primary | #2899e5 | primary | `var(--br-primary)` |
| Color | color/action | #176fae | action | `var(--br-primary-active)` |
| Color | color/soft | #eaf6fd | soft | `var(--br-primary-soft)` |
| Color | color/navy | #1a1a35 | navy | `var(--br-secondary)` |
| Color | color/text | #1e293b | text | `var(--br-text)` |
| Color | color/muted | #607086 | muted | `var(--br-text-muted)` |
| Color | color/white | #ffffff | white | `var(--br-surface-elevated)` |
| Color | color/surface | #f4f7fd | surface | `var(--br-surface)` |
| Color | color/subtle | #f9fbfd | subtle | `var(--br-modal-surface)` |
| Color | color/chrome | #f1f5f9 | chrome | `var(--br-table-thead-bg)` |
| Color | color/border | #d9e3ef | border | `var(--br-border)` |
| Color | color/control | #c5d0dc | control | `var(--br-border-control)` |
| Color | color/danger | #c92d35 | danger | `var(--br-danger)` |
| Color | color/dangerSoft | #fee6e7 | dangerSoft | `var(--br-danger-soft)` |
| Color | color/success | #07875f | success | `var(--br-success)` |
| Color | color/successSoft | #dcf8ea | successSoft | `var(--br-success-soft)` |
| Color | color/warning | #fff3d4 | warning | `var(--br-warning-soft)` |
| Color | stock-saludable/texto | #066647 | stock-saludable/texto | `var(--br-stock-saludable-texto)` |
| Color | control/borde-activo | #8494a7 | control/borde-activo | `var(--br-control-borde-activo)` |
| Color | plantilla/borde-suave | #b6e6cd | plantilla/borde-suave | `var(--br-plantilla-borde-suave)` |
| Color | badge/success/background | #dcf8ea | successSoft | `var(--br-badge-success-background)` |
| Color | badge/success/text | #1a1a35 | navy | `var(--br-text)` |
| Color | badge/warning/background | #fff3d4 | warning | `var(--br-badge-warning-background)` |
| Color | badge/warning/text | #1a1a35 | navy | `var(--br-text)` |
| Color | badge/danger/background | #fee6e7 | dangerSoft | `var(--br-badge-danger-background)` |
| Color | tooltip/world/background | #176fae | action | `var(--br-tooltip-world-background)` |
| Color | tooltip/logout/background | #c92d35 | danger | `var(--br-tooltip-logout-background)` |
| Color | logout/background | #fee6e7 | dangerSoft | `var(--br-logout-background)` |
| Color | required/text | #c92d35 | danger | `var(--br-text)` |
| Color | badge/danger/text | #1a1a35 | navy | `var(--br-text)` |
| Color | template/text | #066647 | stock-saludable/texto | `var(--br-template-text)` |
| Color | template/background | #dcf8ea | successSoft | `var(--br-template-background)` |
| Color | template/border | #b6e6cd | plantilla/borde-suave | `var(--br-template-border)` |
| Geometría | radius/sm | 6 px | — | `var(--br-radius-sm)` |
| Geometría | radius/lg | 12 px | — | `var(--br-radius-lg)` |
| Geometría | control/compact | 32 px | — | `var(--br-control-compact)` |
| Geometría | control/escritorio | 36 px | — | `var(--br-control-escritorio)` |
| Geometría | control/tactil | 44 px | — | `var(--br-control-tactil)` |
| Geometría | badge/height | 26 px | — | `var(--br-badge-height)` |
| Geometría | badge/padding-x | 10 px | — | `var(--br-badge-padding-x)` |
| Geometría | space/4 | 4 px | — | `var(--br-space-4)` |
| Geometría | space/6 | 6 px | — | `var(--br-space-6)` |
| Geometría | space/8 | 8 px | — | `var(--br-space-8)` |
| Geometría | space/10 | 10 px | — | `var(--br-space-10)` |
| Geometría | space/12 | 12 px | — | `var(--br-space-12)` |
| Geometría | space/16 | 16 px | — | `var(--br-space-16)` |
| Geometría | space/20 | 20 px | — | `var(--br-space-20)` |
| Geometría | space/24 | 24 px | — | `var(--br-space-24)` |
| Geometría | space/32 | 32 px | — | `var(--br-space-32)` |
| Geometría | space/48 | 48 px | — | `var(--br-space-48)` |

