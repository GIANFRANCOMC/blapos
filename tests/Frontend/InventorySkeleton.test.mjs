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
        assert.match(view, /<InventorySkeleton v-if="initialLoading && skeletonEnabled" :page="page"\/>/);
        assert.match(view, /<section v-else class="br-inventory">/);
        assert.match(view, /import inventoryPage from "\.\/inventoryState\.js"/);
    }
});

test("Control de stock combina skeleton y Swal solo durante initParams", () => {
    const state = read(`${inventory}inventoryState.js`);
    const skeleton = read(`${inventory}InventorySkeleton.vue`);
    const css = read("resources/css/System/Pages/Warehouses/inventory/skeleton.css");
    const helper = read("resources/js/System/Helpers/InitialLoad.js");

    assert.match(helper, /createInitialLoad\(\{skeleton = false, swal = !skeleton\} = \{\}\)/);
    assert.match(state, /createInitialLoad\(\{skeleton: true, swal: this\.page === "stock"\}\)/);
    assert.match(state, /this\.initialLoad\.start\(\);[\s\S]*?await this\.initParams\(\);[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false;[\s\S]*?this\.initialLoad\.finish\(\);[\s\S]*?await this\.listCurrentView\(\{\}\);/);
    assert.doesNotMatch(state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts(")), /listStockAlerts/);
    assert.match(skeleton, /col-lg-5 col-xl-5/);
    assert.match(skeleton, /br-inventory-skeleton__actions[\s\S]*?br-inventory-skeleton__button--create[\s\S]*?v-if="page === 'stock'" class="br-inventory-skeleton__line br-inventory-skeleton__button"/);
    assert.doesNotMatch(skeleton, /br-inventory__alerts-action/);
    assert.match(skeleton, /role="status" aria-live="polite"/);
    assert.match(skeleton, /headingsByPage = \{[\s\S]*?stock:[\s\S]*?transfers:[\s\S]*?guides:[\s\S]*?kardex:[\s\S]*?valued:/);
    assert.match(css, /prefers-reduced-motion: reduce/);
});
