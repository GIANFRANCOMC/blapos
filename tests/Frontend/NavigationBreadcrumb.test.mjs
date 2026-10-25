import assert from "node:assert/strict";
import test from "node:test";
import {withNavigationGroup} from "../../resources/js/System/Components/breadcrumbItems.js";

test("el breadcrumb agrega el subgrupo activo del mundo", () => {

    const items = [{title: "Catálogo e inventario"}, {title: "Productos", active: true}];

    assert.deepEqual(
        withNavigationGroup(items, {world: "Catálogo e inventario", group: "Catálogo comercial"}),
        [{title: "Catálogo e inventario"}, {title: "Catálogo comercial"}, {title: "Productos", active: true}]
    );

});

test("el breadcrumb no duplica subgrupos ni altera otros mundos", () => {

    const items = [{title: "Catálogo e inventario"}, {title: "Clasificación"}, {title: "Marcas"}];

    assert.deepEqual(withNavigationGroup(items, {world: "Catálogo e inventario", group: "Clasificación"}), items);
    assert.deepEqual(withNavigationGroup(items, {world: "Ventas", group: "Punto de venta"}), items);
    assert.deepEqual(
        withNavigationGroup([{title: "Ventas"}, {title: "Nueva venta"}], {world: "Ventas", group: "Ventas"}),
        [{title: "Ventas"}, {title: "Nueva venta"}]
    );

});

test("el breadcrumb usa la navegación activa como fuente para todos los módulos", () => {

    assert.deepEqual(
        withNavigationGroup(
            [{title: "Restaurante y servicios"}, {title: "Servicios en curso", active: true}],
            {world: "Clientes", group: "Servicios", page: "Servicios en curso"}
        ),
        [
            {title: "Clientes", active: false},
            {title: "Servicios", active: false},
            {title: "Servicios en curso", active: true}
        ]
    );

    assert.deepEqual(
        withNavigationGroup(
            [{title: "Clientes", active: true}],
            {world: "Clientes", group: "Datos", page: "Clientes"}
        ),
        [
            {title: "Clientes", active: false},
            {title: "Datos", active: false},
            {title: "Clientes", active: true}
        ]
    );

    assert.deepEqual(
        withNavigationGroup(
            [{title: "Administración"}],
            {world: "Administración", group: "Organización", page: "Mi empresa"}
        ),
        [
            {title: "Administración", active: false},
            {title: "Organización", active: false},
            {title: "Mi empresa", active: true}
        ]
    );

});
