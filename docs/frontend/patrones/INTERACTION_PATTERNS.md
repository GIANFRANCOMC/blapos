# Patrones de interacción

## Buscar, filtrar y paginar

La interfaz mantiene separados los filtros editables y los aplicados. **Buscar** consulta desde la primera página. Los cambios y la X del campo solo editan el filtro pendiente. La acción **Limpiar filtros** restablece los campos del contexto y confirma con el Toastr compartido. Paginar, refrescar o exportar usa los filtros efectivamente aplicados. Los detalles de inicialización, consultas y tamaño de página están en [Consultas de tablas](../fundamentos/ESTANDARES_UI.md#consultas-de-tablas-y-limpieza-de-registros).

Al empezar una consulta, limpia las filas y totales anteriores. Conserva el resultado vigente ante ediciones de filtros que aún no se hayan aplicado. Si la consulta falla, ofrece recuperación desde el contexto aplicado. En una acción masiva indica cuántos registros se seleccionaron y si el alcance es la página o el conjunto filtrado. La selección se actualiza de manera explícita al cambiar el conjunto.

## Alta, edición, detalle y confirmación

- Identifica la entidad y el contexto operativo. Agrupa campos por tarea y muestra los obligatorios y sus errores cerca del control. Usa valores predeterminados respaldados por el negocio y una acción principal con verbo específico.
- Bloquea envíos duplicados mientras se guarda. Conserva los datos editables ante un rechazo. Si el flujo puede perder cambios relevantes, confirma el descarte antes de cerrar o navegar.
- Consulta datos mediante página, panel o modal según su volumen y la necesidad de mantener el contexto. Usa confirmaciones para operaciones destructivas o difíciles de revertir, indicando el alcance y las consecuencias.
- Reutiliza la [convención de modales y resultados](../fundamentos/ESTANDARES_UI.md#resultado-de-operaciones-en-modales). **Cerrar** descarta la modal. En un éxito, el flujo de resultado gestiona el cierre y el aviso. Los errores que requieren corrección conservan el formulario y restauran su foco.

## Estados y recuperación

| Estado | Respuesta de la interfaz |
| --- | --- |
| Apertura o configuración inicial | Skeleton durante `initParams` o su equivalente. En listados, representa la estructura y el loader dentro de la tabla según [Carga inicial](../fundamentos/ESTANDARES_UI.md#carga-inicial-con-skeleton). |
| Consulta de registros | Loader de tabla dentro de la fila de estado. Paginador y total ocultos durante la consulta y visibles cuando termina, también con cero resultados. |
| Vacío y sin coincidencias | Diferencia un conjunto sin datos de una búsqueda sin coincidencias cuando la acción de recuperación cambie. Centra la retroalimentación en el área visible. |
| Aviso informativo | Toastr para mensajes breves como «Se limpiaron los filtros correctamente.». |
| Error, advertencia o confirmación global | Usa el patrón compartido de [SweetAlert](../fundamentos/ESTANDARES_UI.md#modales-y-sweetalert) según el nivel de atención. Para errores locales, vincula el mensaje al elemento que se debe corregir. |
| Deshabilitado, sin permiso o desconexión | Aplica un estado comprensible, conserva la información útil cuando corresponda y ofrece reintento o una acción recuperable si existe. Verifica permisos en backend y conserva la prevención de envíos duplicados al reintentar. |

Las filas de estado carecen de hover de registro. El resultado de una operación global debe dejar un único foco activo, con los tooltips descartados durante la alerta. Para búsquedas remotas, usa el [contrato de opciones en Generalidades](../fundamentos/ESTANDARES_UI.md#formularios).

