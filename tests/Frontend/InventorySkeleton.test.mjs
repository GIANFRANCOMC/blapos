
import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {inventoryFilters, inventoryViews} from "../../resources/js/System/Pages/Warehouses/inventory/inventoryPresentation.js";

const read = path => readFileSync(new URL("../../" + path, import.meta.url), "utf8");
const inventory = "resources/js/System/Pages/Warehouses/inventory/";

test("las cinco secciones tienen entrada propia y comparten la vista base", () => {
    for(const [name, page] of [["stock","stock"],["transfers","transfers"],["guides","guides"],["kardex","kardex"],["valued-kardex","valued"]]) {
        const entry = read(inventory + name + ".js");
        const view = read(inventory + name + ".vue");
        assert.ok(entry.includes('import App from "./' + name + '.vue"'));
        assert.ok(entry.includes('mountInventoryPage(App, "' + page + '")'));
        assert.match(view, /<InventoryListing[\s\S]*?:initial-loading="initialLoading"/);
        assert.deepEqual(parse(view).errors, []);
    }
});

test("Inventario cambia del skeleton al listado tras initParams", () => {
    const state = read(inventory + "inventoryState.js");
    const listing = read(inventory + "InventoryListing.vue");
    assert.match(state, /try \{[\s\S]*?await this\.initParams\(\);[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false;[\s\S]*?await this\.listCurrentView\(\{\}\);/);
    assert.match(listing, /<InitialLoadTransition>[\s\S]*?<InventorySkeleton v-if="initialLoading"[\s\S]*?<div v-else>/);
    assert.match(read("resources/css/Shared/skeleton.css"), /prefers-reduced-motion: reduce/);
});

test("el skeleton comparte la misma cuadrícula, tabla y columnas del listado", () => {
    const listing = read(inventory + "InventoryListing.vue");
    const skeleton = read(inventory + "InventorySkeleton.vue");
    for(const view of [listing, skeleton]) {
        assert.deepEqual(parse(view).errors, []);
        assert.match(view, /fg-filters fg-filter-grid[\s\S]*?v-for="field in fields"/);
        assert.match(view, /<InventoryTable :columns="view\.columns" :min-width="view\.minWidth"/);
    }
    assert.match(skeleton, /fg-skeleton-control/);
    assert.match(skeleton, /<FgSkeletonFilterActions\/>/);
    assert.match(read("resources/js/System/Components/Figma/FgSkeletonFilters.vue"), /<FgSkeletonFilterActions\/>/);
    assert.doesNotMatch(skeleton, /<button|<input|<select|<Paginator/);
    assert.equal(inventoryViews.stock.columns.length, 6);
    assert.equal(inventoryViews.transfers.columns.length, 7);
    assert.deepEqual(inventoryFilters.transfers.map(field => field.label), [
        "Almacén de origen", "Almacén de destino", "Referencia", "Desde", "Hasta", "Registrado por", "Producto", "Estado del traslado"
    ]);
});
