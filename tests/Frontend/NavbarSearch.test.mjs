import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {rankNavigationItems} from "../../resources/js/System/Layout/Menu.js";

test("el buscador prioriza nombres y reconoce acentos, mundos y descripciones", () => {
    const items = [
        {label: "Control de stock", world: "Inventario", description: "Consultar existencias"},
        {label: "Clientes", world: "Gestión", description: "Datos y estado"},
        {label: "Gestión de servicios", world: "Servicios", description: "Atención al cliente"},
    ];

    assert.deepEqual(rankNavigationItems(items, "gestion"), [items[2], items[1]]);
    assert.deepEqual(rankNavigationItems(items, "atencion cliente"), [items[2]]);
    assert.deepEqual(rankNavigationItems(items, "EXISTENCIAS"), [items[0]]);
    assert.deepEqual(rankNavigationItems(items, "sin coincidencias"), []);
    assert.deepEqual(rankNavigationItems(items, "", 2), items.slice(0, 2));
});

test("el navbar usa la navegación autorizada y conserva avisos y empresa", () => {

 const layout=readFileSync(new URL("../../resources/views/System/layouts/partials/fg-navbar.blade.php",import.meta.url),"utf8");
 const styles=readFileSync(new URL("../../resources/css/System/fg/navigation.css",import.meta.url),"utf8");
 const behavior=readFileSync(new URL("../../resources/js/System/Layout/Menu.js",import.meta.url),"utf8");
 assert.match(layout,/id="brNavbarSearchInput"[\s\S]*?aria-controls="brNavbarSearchPanel"/);
 assert.match(layout,/@foreach\(\$navigationSections as \$section\)[\s\S]*?@foreach\(\$section->subSections as \$subSection\)[\s\S]*?data-fg-search-item/);
 assert.match(styles,/\.fg-global-search__panel\[hidden\]/);
 assert.match(styles,/\.fg-user__identity/);
 assert.match(behavior,/event.ctrlKey \|\| event.metaKey/);
 assert.match(behavior,/event.key === "ArrowDown"/);
 assert.match(behavior,/visible.forEach\(item => results.appendChild\(item.element\)\)/);

});
