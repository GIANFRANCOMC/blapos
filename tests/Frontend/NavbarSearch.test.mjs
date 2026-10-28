import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {rankNavigationItems} from "../../resources/js/System/Layout/navbar-search.js";

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
    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/60-dashboard-sales.css", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/navbar-search.js", import.meta.url), "utf8");

    assert.match(layout, /id="brNavbarSearchInput"[\s\S]*?aria-controls="brNavbarSearchPanel"/);
    assert.match(layout, /@foreach\(\$navigationSections as \$section\)[\s\S]*?@foreach\(\$section->subSections as \$subSection\)[\s\S]*?data-br-search-item/);
    assert.match(layout, /br-navbar-announcements__count" @if\(\$navbarAnnouncementCount === 0\) hidden @endif/);
    assert.match(layout, /br-navbar-user__company" title="\{\{ \$navbarCompanyName \}\}"/);
    assert.match(styles, /\.br-navbar-search__panel\[hidden\][\s\S]*?display: none !important;/);
    assert.doesNotMatch(styles.match(/\.br-navbar-search__heading\s*\{[^}]*\}/)?.[0] ?? "", /border-bottom/);
    assert.match(styles, /\.br-navbar-search__item\.is-primary-result[\s\S]*?background: color-mix/);
    assert.match(styles, /@media \(max-width: 399\.98px\)[\s\S]*?\.br-navbar-user__identity\s*\{\s*display: none;/);
    assert.match(behavior, /event\.ctrlKey \|\| event\.metaKey/);
    assert.match(behavior, /event\.key === "ArrowDown"/);
    assert.match(behavior, /visible\.forEach\(item => results\.appendChild\(item\.element\)\)/);
});
