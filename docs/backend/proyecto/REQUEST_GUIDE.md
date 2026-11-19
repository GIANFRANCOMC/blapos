# Guia para pedir cambios en Blapos

Este archivo describe como dar requerimientos para que Codex pueda modificar el proyecto con menos ambiguedad y menor riesgo.

## Formato recomendado

```md
Modulo:
Clientes / Ventas / Asistencias / etc.

Objetivo:
Que debe lograrse en lenguaje de negocio.

Alcance:
- Backend
- Frontend
- Base de datos
- Validaciones
- Reportes o PDF si aplica

Reglas:
- Que debe permitirse.
- Que debe bloquearse.
- Estados esperados.
- Relacion con empresa/sucursal/cliente.

Verificacion:
- Casos que debo probar.
- Resultado esperado.
```

## Ejemplo corto

```md
Modulo: Clientes
Objetivo: agregar campo fecha de nacimiento opcional.
Alcance: migracion, request, formulario crear/editar y detalle.
Reglas: si viene vacio guardar null.
Verificacion: crear y editar cliente siguen funcionando.
```

## Ejemplo completo

```md
Modulo: Asistencias
Objetivo: permitir registrar checkout automatico desde QR si el cliente ya tiene asistencia activa.
Alcance:
- TrackingAttendanceBusinessService
- controlador interno y publico si aplica
- pagina Vue de tracking_attendances si se muestra mensaje

Reglas:
- Si no tiene asistencia activa, hacer check-in normal.
- Si tiene asistencia activa, hacer checkout.
- El checkout debe ser al menos 2 minutos despues del check-in.
- Mantener validacion de membresia para check-in.

Verificacion:
- Cliente con membresia vigente hace check-in.
- El mismo cliente vuelve a escanear y hace checkout.
- Cliente sin membresia no registra check-in.
```

## Que contexto conviene dar

- Pantalla exacta donde ocurre el problema.
- Ruta o modulo visible en la URL.
- Usuario/rol usado para probar.
- Empresa y sucursal usadas si el bug depende de ellas.
- Datos de ejemplo: cliente, producto, membresia, venta.
- Mensaje de error exacto.
- Captura o descripcion del comportamiento actual.
- Comportamiento esperado.

## Como pedir mejoras sugeridas

Puedes referirte a los archivos por modulo:

```md
Revisa docs/backend/system/new_requirements/02_sales_stock_subscriptions.md y aplica la mejora de evitar stock negativo al vender productos.
```

O pedir una evaluacion antes de cambiar:

```md
Evalua la mejora "centralizar estados" de docs/backend/arquitectura/ARCHITECTURE.md y dime que archivos tocaria.
```

## Reglas para cambios grandes

Para cambios grandes, conviene pedir primero una fase de analisis:

```md
Analiza como implementar pagos parciales en ventas. No modifiques aun. Dame impacto por modulo y riesgos.
```

Cuando el alcance ya este claro:

```md
Implementa la opcion A. Mantener compatibilidad con ventas actuales y agregar tests si es viable.
```

## Buenas practicas al requerir

- Pedir un cambio por flujo de negocio, no por lista enorme de deseos mezclados.
- Indicar si se puede modificar base de datos.
- Indicar si se debe mantener compatibilidad con datos existentes.
- Indicar si quieres pruebas automatizadas, pruebas manuales o ambas.
- Decir si una mejora es urgente o si solo quieres evaluarla.
## Documentacion esperada

Cuando un cambio se implemente, tambien debe indicarse que documentacion se actualiza:

- `docs/backend/desarrollo/GENERALIDADES.md` si cambia una regla transversal.
- [Módulos de System](../system/modules) o [módulos de Guest](../guest/modules) si cambia un módulo.
- `docs/backend/datos/system/TABLES.md` o `docs/backend/datos/guest/TABLES.md` si cambian tablas, campos o relaciones.
- `docs/backend/system/new_requirements` o `docs/backend/guest/new_requirements` si queda algo pendiente.

Si el cambio afecta la interfaz, indica el módulo de referencia y los [estándares visuales](../../frontend/fundamentos/ESTANDARES_UI.md) pertinentes.

## Datos mínimos por dominio

- **Ventas/POS:** sucursal, almacén, serie, caja, cliente, tributos, pagos y política de stock.
- **Inventario:** almacén origen/destino, motivo, cantidades, costo y trazabilidad esperada.
- **Caja:** sucursal, caja, sesión, método de pago, responsable y tipo de movimiento.
- **Compras:** proveedor, almacén, entrega inmediata/pendiente, tributos, pagos y recepciones.
- **Catálogo:** tipo de ítem, publicación, precio, impuestos, inventario y relaciones comerciales.

Los ejemplos deben usar módulos y rutas vigentes; una propuesta visual o backend se documenta en el módulo afectado cuando queda implementada.
