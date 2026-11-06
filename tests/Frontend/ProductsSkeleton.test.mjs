import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("Productos usa skeleton solo durante initParams", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.js", import.meta.url), "utf8");

    assert.equal(parse(page, {filename: "products/main.vue"}).errors.length, 0);
    assert.match(page, /createInitialLoad\(\{skeleton: true\}\)/);
    assert.match(page, /<ProductsSkeleton v-if="initialLoading && skeletonEnabled"\/>[\s\S]*?<main v-else class="br-entity">/);
    assert.match(page, /initialLoad\.start\(\)[\s\S]*?await this\.initParams\(\)[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\);[\s\S]*?this\.initialLoading = false[\s\S]*?await this\.listEntity\(\{\}\)/);
    assert.match(page, /<tr v-if="entityList\.extras\.loading">[\s\S]*?<Loader\/>/);
    assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/);
    assert.match(entry, /Pages\/Catalogs\/products\/skeleton\.css/);
});

test("el skeleton de Productos conserva filtros y columnas con el loader real de la tabla", () => {
    const skeleton = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/ProductsSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Catalogs/products/skeleton.css", import.meta.url), "utf8");

    assert.equal(parse(skeleton, {filename: "ProductsSkeleton.vue"}).errors.length, 0);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando productos"/);
    assert.match(skeleton, /br-filter-bar[\s\S]*?br-entity-list[\s\S]*?br-entity-table/);
    assert.match(skeleton, /br-entity-table__col-identification[\s\S]*?br-entity-table__col-product[\s\S]*?br-entity-table__col-price[\s\S]*?br-entity-table__col-inventory[\s\S]*?br-entity-table__col-status[\s\S]*?br-entity-table__col-actions/);
    assert.match(skeleton, /colspan="6" class="py-4"><Loader\/>/);
    assert.match(styles, /height: var\(--br-control-height\)/);
    assert.match(styles, /@media \(max-width: 991\.98px\)/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
});
