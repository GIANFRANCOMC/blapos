import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("control de stock muestra el total filtrado a la derecha del paginador", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const count = read("resources/js/System/Components/Generics/ListResultCount.vue");
    const skeleton = read("resources/js/System/Pages/Warehouses/inventory/InventorySkeleton.vue");
    const styles = read("resources/css/System/br-branding/81-core-roles-entities.css");

    assert.match(page, /<section class="br-entity-list"[\s\S]*?<div v-if="filters\.warehouse\?\.code" class="br-inventory__list-footer">[\s\S]*?<Paginator[\s\S]*?<ListResultCount[\s\S]*?:pagination="stockRecords"[\s\S]*?:loading="loadingStock"\/>/);
    assert.match(state, /import ListResultCount from "@System\/Components\/Generics\/ListResultCount\.vue"/);
    assert.match(count, /Resultado:[\s\S]*?formatNumber\(total\)[\s\S]*?total === 1 \? singular : plural/);
    assert.doesNotMatch(count, /Mostrando|pagination\.from|pagination\.to/);
    assert.match(count, /total\(\) \{[\s\S]*?this\.pagination\?\.total/);
    assert.match(count, /aria-live="polite"[\s\S]*?:aria-hidden="loading"/);
    assert.match(skeleton, /v-if="page === 'stock'" class="br-inventory-skeleton__list-footer"[\s\S]*?br-inventory-skeleton__result-count/);
    assert.match(styles, /\.br-list-result-count--loading \{\s*visibility: hidden;/);
    const footerCss = read("resources/css/System/br-branding/82-inventory.css");
    assert.match(footerCss, /\.br-inventory__list-footer \{\s*display: grid;\s*grid-template-columns: minmax\(0, 1fr\) auto minmax\(0, 1fr\);/);
    assert.match(footerCss, /\.br-inventory__list-footer \.br-pagination \{\s*grid-column: 2;\s*justify-self: center;/);
    assert.match(footerCss, /\.br-inventory__list-footer \.br-list-result-count \{\s*grid-column: 3;\s*justify-self: end;/);
});

test("el contador usa registro en singular y registros en plural", async () => {
    const source = read("resources/js/System/Components/Generics/ListResultCount.vue");
    const {descriptor} = parse(source);
    const {default: component} = await import(`data:text/javascript,${encodeURIComponent(descriptor.script.content)}`);

    assert.equal(component.props.singular.default, "registro");
    assert.equal(component.props.plural.default, "registros");
    assert.equal(component.computed.total.call({pagination: {total: 1}}), 1);
    assert.equal(component.computed.total.call({pagination: {total: 50}}), 50);
    assert.equal(component.computed.total.call({pagination: {total: 0}}), 0);
});
