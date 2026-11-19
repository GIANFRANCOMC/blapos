# Accesibilidad

El objetivo de Blapos es WCAG 2.2 nivel AA. Esta guía orienta la revisión de un cambio. La conformidad requiere evaluar sus páginas y estados reales. [WCAG 2.2, W3C](https://www.w3.org/TR/WCAG22/).

| Aspecto | Comprobación |
| --- | --- |
| Contraste | Compara los colores calculados de las combinaciones reales. Exige 4.5:1 para texto normal, 3:1 para texto grande según WCAG y 3:1 para componentes e indicadores visuales aplicables. Verifica variantes activas y enfocadas y acompaña el color de estado con texto o indicación equivalente. |
| Teclado | Recorre la pantalla con Tab y Shift+Tab. Acciona botones con Enter o Espacio, usa flechas en pestañas y selectores según su patrón y verifica el acceso a menús y a la tabla desplazable. |
| Foco y capas | Mantén un indicador de foco visible y libre de superposición. Dentro de una modal, el foco llega a sus controles, permanece contenido y vuelve a un punto lógico al cerrar. El diálogo apilado y el tooltip respetan la capa activa. |
| Nombres y relaciones | Usa elementos semánticos y labels asociados. Un botón con solo icono requiere nombre accesible además del tooltip. Vincula la ayuda y el mensaje de error al campo, e indica estado inválido y obligatoriedad cuando corresponda. |
| Actualizaciones | Comprueba que un lector pueda percibir carga, resultados, errores y avisos relevantes sin perder su posición. Evita anunciar repetidamente cambios irrelevantes. |
| Objetivos táctiles | Comprueba un mínimo de 24 × 24 píxeles CSS para objetivos de puntero, o una excepción válida de tamaño o separación según WCAG 2.2. El tamaño del icono puede ser menor que su área interactiva. |
| Reflujo y ampliación | Revisa 200 % de texto y el equivalente a 320 píxeles CSS de ancho para reflujo. Prueba campos, botones y pie de tablas. Una región realmente bidimensional puede desplazarse dentro de su contenedor. |
| Movimiento | Respeta `prefers-reduced-motion`. La transición nunca debe impedir leer, accionar o reconocer qué cambió. |

Prueba con teclado y navegador cuando puedas. Una captura permite revisar composición y legibilidad visual. Para foco, anuncios y uso con lectores de pantalla, verifica la interacción. Registra cualquier verificación pendiente junto a su alcance.

