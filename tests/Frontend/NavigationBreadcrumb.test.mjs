import assert from "node:assert/strict";
import test from "node:test";
import {withNavigationGroup} from "../../resources/js/System/Components/breadcrumbItems.js";

test("el breadcrumb agrega el subgrupo activo del mundo", () => {

    const items = [{title: "Catálogo comercial"}, {title: "Productos", active: true}];

    assert.deepEqual(
        withNavigationGroup(items, {world: "Catálogo comercial", group: "Maestras"}),
        [{title: "Catálogo comercial"}, {title: "Maestras"}, {title: "Productos", active: true}]
    );

});

test("el breadcrumb no duplica subgrupos ni altera otros mundos", () => {

    const items = [{title: "Catálogo comercial"}, {title: "Clasificación"}, {title: "Marcas"}];

    assert.deepEqual(withNavigationGroup(items, {world: "Catálogo comercial", group: "Clasificación"}), items);
    assert.deepEqual(withNavigationGroup(items, {world: "Ventas", group: "Punto de venta"}), items);
    assert.deepEqual(
        withNavigationGroup([{title: "Ventas"}, {title: "Nueva venta"}], {world: "Ventas", group: "Ventas"}),
        [{title: "Ventas"}, {title: "Nueva venta"}]
    );

});
