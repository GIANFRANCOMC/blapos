# 04 - Calidad de datos y campos nuevos

## Campos incorporados

Clientes:

- Contacto de emergencia.
- Observaciones medicas.
- Fecha de inscripcion.
- Foto.

Productos:

- SKU/codigo de barras.
- Stock minimo.

Membresias:

- Limite diario configurable desde item.
- Beneficios del plan.

Activos:

- Codigo patrimonial.
- Numero de serie fisico.
- Categoria de activo.

## Impacto cerrado

Las migraciones, modelos, requests y servicios ya incorporan estos campos. Su representación visual permanece fuera de este documento.

## Estado backend

Los campos descritos ya forman parte de sus contratos de migración, modelos, requests y servicios. El criterio transversal de longitudes, `nullable`, claves foráneas y aislamiento tenant está documentado en `docs/GENERALIDADES.md` y `docs/System/TABLES.md`.

Los archivos backend y documentación usan UTF-8; cualquier dato persistido con codificación dañada debe corregirse mediante una operación de datos auditada, no mediante una reescritura silenciosa.
