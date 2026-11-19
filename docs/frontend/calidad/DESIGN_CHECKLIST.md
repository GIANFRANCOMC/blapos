# Lista de verificación

Usa los puntos que correspondan al cambio y registra las excepciones explícitas.

- [ ] La acción principal es reconocible y los datos necesarios aparecen en su orden de uso. El espacio disponible favorece la tarea y la lectura.
- [ ] La densidad, tipografía, color, márgenes, iconos y estados reutilizan patrones del módulo o del [sistema de diseño](../fundamentos/DESIGN_SYSTEM.md).
- [ ] Los filtros, fechas, unidades y cantidades se entienden y mantienen su contexto. Los importes y estados respetan la lógica del dominio.
- [ ] Se probaron los estados que corresponden: inicial, carga, vacío, sin coincidencias, éxito, error, advertencia, deshabilitado, sin permiso o desconexión.
- [ ] Se verificaron las operaciones afectadas, incluida la recuperación ante error o reintento. Los permisos, la precisión y los contratos se conservan fuera del cambio autorizado.
- [ ] Teclado, foco, contraste, áreas interactivas y nombres accesibles cumplen los criterios aplicables de [Accesibilidad](ACCESSIBILITY.md).
- [ ] El contenido funciona al ancho habitual, en la vista más estrecha aplicable y con nombres o datos largos. Revisa el scroll de tablas y el comportamiento del pie.
- [ ] Al tocar estilos o componentes compartidos se revisó también otro consumidor. Se actualizaron el contrato o la guía del módulo si cambió un patrón.
- [ ] Se ejecutaron las comprobaciones pertinentes. Para cambios de Vue o JS usa las pruebas pertinentes de `tests/Frontend`. Para cambios visuales verifica la compilación de CSS o Vite y, cuando esté disponible, el navegador. Si el cambio alcanza backend, aplica la [guía de pruebas del proyecto](../../backend/desarrollo/TESTING.md).

En la entrega, indica qué escenarios se probaron y cuándo la revisión se hizo con datos de prueba. Registra los casos que quedaron sin verificar.

