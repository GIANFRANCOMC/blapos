import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");
const inventory = "resources/js/System/Pages/Warehouses/inventory/";

test("las cinco secciones de inventario tienen una vista y entrada JS propias", () => {
    const mount = read(`${inventory}mount.js`);

    assert.doesNotMatch(mount, /InventoryPage\.vue/);
    for(const [name, page] of [
        ["stock", "stock"],
        ["transfers", "transfers"],
        ["guides", "guides"],
        ["kardex", "kardex"],
        ["valued-kardex", "valued"]
    ]) {
        const entry = read(`${inventory}${name}.js`);
        const view = read(`${inventory}${name}.vue`);

        assert.match(entry, new RegExp(`import App from "\\./${name}\\.vue"`));
        assert.match(entry, new RegExp(`mountInventoryPage\\(App, "${page}"\\)`));
        assert.match(view, /<InventorySkeleton v-if="initialLoading" :page="page"\/>/);
        assert.match(view, /<section v-else class="br-inventory">/);
        assert.match(view, /import inventoryPage from "\.\/inventoryState\.js"/);
    }
});

test("Control de stock usa el loader dentro del skeleton durante initParams", () => {
    const state = read(`${inventory}inventoryState.js`);
    const skeleton = read(`${inventory}InventorySkeleton.vue`);
    const css = read("resources/css/System/Pages/Warehouses/inventory/skeleton.css");

    assert.match(state, /try \{[\s\S]*?await this\.initParams\(\);[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false;[\s\S]*?await this\.listCurrentView\(\{\}\);/);
    assert.doesNotMatch(state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts(")), /listStockAlerts/);
    assert.match(skeleton, /br-table-state[\s\S]*?<Loader\/>/);
    assert.match(skeleton, /class="col-12 col-md-6 col-lg-3 col-xl-3"/);
    assert.match(skeleton, /page === 'guides' \? 'col-lg-2' : 'col-lg-3'/);
    assert.match(skeleton, /v-if="hasStockFilters" class="col-12 col-md-6 col-lg-2 col-xl-2"/);
    assert.match(skeleton, /<template v-if="page === 'stock'">\s*<div v-for="field in 2" :key="field" class="col-12 col-sm-6 col-lg-2"[\s\S]*?<template v-if="hasStockFilters">\s*<div v-for="field in 2" :key="field" class="col-12 col-sm-6 col-lg-3"/);
    assert.match(skeleton, /v-if="page === 'stock'" class="col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2">\s*<div class="br-inventory-skeleton__actions gap-2">\s*<span class="br-inventory-skeleton__line br-inventory-skeleton__button"><\/span>\s*<span class="br-inventory-skeleton__line br-inventory-skeleton__clear"><\/span>\s*<span class="br-inventory-skeleton__line br-inventory-skeleton__button"><\/span>\s*<\/div>\s*<div class="br-inventory-skeleton__actions gap-2 ms-auto">\s*<span class="vr align-self-center"><\/span>\s*<span class="br-inventory-skeleton__line br-inventory-skeleton__button br-inventory-skeleton__button--create"><\/span>\s*<span class="br-inventory-skeleton__line br-inventory-skeleton__button"/);
    assert.doesNotMatch(skeleton, /br-inventory__alerts-action/);
    assert.match(skeleton, /role="status" aria-live="polite"/);
    assert.match(skeleton, /headingsByPage = \{[\s\S]*?stock:[\s\S]*?transfers:[\s\S]*?guides:[\s\S]*?kardex:[\s\S]*?valued:/);
    assert.match(css, /prefers-reduced-motion: reduce/);
});
