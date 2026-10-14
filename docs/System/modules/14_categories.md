# 14 - Categorías

## Qué hace

Agrupa productos, servicios y membresías por empresa.

## Backend

- Campos: `internal_code`, `name`, `description` y `status`.
- El listado y el catálogo público ordenan las categorías alfabéticamente. Una categoría activa puede aparecer en el catálogo público; la publicación de cada ítem sigue controlándose en el propio ítem.
- Solo categorías activas se ofrecen en nuevas asociaciones.
- Inactivar o eliminar queda bloqueado cuando existen ítems activos asociados; el backend responde `422` con un mensaje directo para que el usuario entienda que primero debe retirar o desactivar esos productos.
- Crear, editar o eliminar invalida las cachés dependientes de Productos, Servicios y Membresías.
- La alta rápida conserva generación de código y validación backend.

## Interfaz

- El listado muestra identificación, nombre, cantidad de ítems activos asociados y estado.
- El formulario conserva código interno, nombre, descripción y estado. La descripción ocupa ocho columnas y el estado cuatro en escritorio.

## Carga y descarga

- `GET /categories/import` descarga la plantilla y `POST /categories/import` procesa el archivo. `GET /categories/export` descarga el listado filtrado; ambos métodos de `/import` requieren permiso `import`.
- El listado y la modal usan los componentes compartidos `FiltersSection`, `TableActionsDropdown` y `FormModal`.
- **Acciones → Descargar Excel** exporta todos los registros que coinciden con los filtros actuales, no solo la página visible. Requiere permiso `export`.
- **Acciones → Cargar masivamente** abre la modal compartida `CatalogReferenceImportModal`; la descarga de plantilla y la carga requieren permiso `import`.
- Plantilla: `Nombre` obligatorio; `Código interno`, `Descripción` y `Estado` opcionales. Valores predeterminados: código generado con el prefijo configurado y estado `Activo`.
- Se aceptan Excel y CSV con hasta 500 filas y el límite de tamaño configurado para archivos. El backend valida cada fila, rechaza nombres/códigos duplicados y revierte el lote completo ante un error.
- La importación usa `CategoryService` y luego invalida las cachés dependientes; no crea un segundo catálogo ni omite el aislamiento de la base tenant.
