# 12 - Recetas y platillos

## Proposito

`recipes.index` agrega una capa operativa para restaurantes y negocios de comida dentro de Catalogo comercial. Un platillo sigue siendo un `item` vendible; la receta agrega la formula, toppings, extras, sabores e insumos que permiten proyectar consumo de inventario y preparar trazabilidad de cocina.

## Flujo actual

- El usuario selecciona el item vendible que representa el platillo.
- Define rendimiento y merma esperada.
- Registra insumos base del platillo, por ejemplo pollo, masa, queso o condimentos.
- Registra toppings/extras con precio adicional y, si corresponde, insumos propios.
- Registra sabores o variantes flexibles. Esto cubre casos como pizzas de varios sabores, donde el platillo tiene insumos base y cada sabor agrega su formula.

## Tablas asociadas

- `recipe_dishes`: cabecera de la receta asociada a `items`.
- `recipe_dish_components`: insumos base de la receta.
- `recipe_toppings`: catalogo operativo de toppings o extras.
- `recipe_dish_toppings`: relacion entre platillo y topping, con limites y defaults.
- `recipe_topping_components`: insumos consumidos por un topping.
- `recipe_dish_options`: sabores o variantes de un platillo.
- `recipe_dish_option_components`: insumos consumidos por cada sabor o variante.
- `recipe_waste_records`: mermas reales vinculadas a receta, insumo, almacén, costo y movimiento de inventario.

## Reglas de negocio

- No se crea un nuevo `items.type`; la receta se apoya en un item existente para no romper ventas, POS, compras ni reportes.
- Los insumos deben pertenecer a la misma empresa.
- El item principal, los insumos base, los insumos de toppings, los insumos de opciones y las monedas se validan mediante `CompanyFormRequest` antes de entrar al servicio.
- La cantidad de insumo debe ser mayor a cero.
- La merma esperada permite proyectar consumo real con tolerancia operativa.
- La merma global de la receta y la merma particular de cada componente se aplican de forma acumulativa al consumo.
- Una merma real genera una salida `recipe_waste`; no modifica silenciosamente el saldo ni se confunde con la merma esperada.
- Los toppings pueden tener precio y consumo de insumos propio.
- Si un topping no declara moneda, hereda la moneda del item principal de la receta; nunca usa un ID global fijo.
- Los sabores permiten distribuir componentes adicionales segun eleccion del cliente.

## Impacto en POS y ventas

La base de datos ya soporta formulas y opciones. El descuento automatico de insumos debe activarse cuando POS permita seleccionar receta, toppings y sabores vendidos. No se debe descontar una receta completa sin conocer las opciones elegidas, porque generaria kardex incorrecto.

## KDS y operación

- Los platillos vendidos con receta pueden alimentar KDS mediante el detalle de venta, conservando toppings, extras, sabores y combinaciones parciales seleccionadas.
- KDS debe mostrar la receta vendida como pedido operativo, no como movimiento de inventario. El consumo de insumos se registra cuando la venta queda confirmada.
- El costo teórico se calcula desde insumos base, toppings, sabores y merma esperada; sirve para margen operativo, no reemplaza el costo real registrado en kardex.
- La merma real se registra aparte en `recipe_waste_records` y genera movimiento de inventario para conservar trazabilidad.

## Cierre de caja principal e inventario fisico

Se agrega `cash_session_inventory_counts` para registrar conteos fisicos al cierre de caja principal. La caja principal no debe cerrar si existen cajas secundarias abiertas en la misma sucursal. Cuando el conteo real difiere del sistema, la diferencia debe generar un movimiento de inventario con origen `physical_count`, observacion y responsable.

## Estado de mejoras

- Al confirmar una venta, `RecipeConsumptionService` consume los insumos base, opciones y toppings elegidos, incluyendo merma configurada.
- Los consumos se agrupan por insumo y generan movimientos `recipe_sale` vinculados al detalle de venta y receta.
- La anulación con reposición automática revierte tanto productos directos como insumos de receta en el almacén original.
- `GET /recipes/{id}/theoretical-cost?warehouse_id=...` calcula costo base por porción, opciones y toppings usando el costo promedio del almacén; también informa insumos sin costo disponible.
- `GET /recipes/waste-records` consulta mermas reales por receta, almacén, insumo y fecha.
- `POST /recipes/{id}/waste-records` registra la merma, su costo histórico y el movimiento de inventario en una sola transacción.
- Actualizar, eliminar, calcular costo y registrar merma resuelven la receta y el almacén dentro de la conexión tenant; un ID de otra base no puede atravesar el contexto.
