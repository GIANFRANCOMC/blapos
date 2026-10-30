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

test("el skeleton de inventario cubre la primera consulta sin Swal y conserva el modo optativo", () => {
    const state = read(`${inventory}inventoryState.js`);
    const skeleton = read(`${inventory}InventorySkeleton.vue`);
    const css = read("resources/css/System/Pages/Warehouses/inventory/skeleton.css");
    const helper = read("resources/js/System/Helpers/InitialLoad.js");

    assert.match(helper, /createInitialLoad\(\{skeleton = false\} = \{\}\)/);
    assert.match(state, /createInitialLoad\(\{skeleton: true\}\)/);
    assert.match(state, /initialLoad\.start\(\);[\s\S]*?await this\.initParams\(\);[\s\S]*?await this\.listCurrentView\(\{\}\);[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false;[\s\S]*?initialLoad\.finish\(\);/);
    assert.match(state, /if\(this\.initialLoading\) await alertsRequest;/);
    assert.match(skeleton, /role="status" aria-live="polite"/);
    assert.match(skeleton, /headingsByPage = \{[\s\S]*?stock:[\s\S]*?transfers:[\s\S]*?guides:[\s\S]*?kardex:[\s\S]*?valued:/);
    assert.match(css, /prefers-reduced-motion: reduce/);
});
