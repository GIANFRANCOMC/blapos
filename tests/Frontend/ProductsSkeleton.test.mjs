import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const readSharedSkeleton = () => readFileSync(new URL("../../resources/css/Shared/skeleton.css", import.meta.url), "utf8");
import {parse} from "@vue/compiler-sfc";

test("Productos usa skeleton solo durante initParams", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.js", import.meta.url), "utf8");

    assert.equal(parse(page, {filename: "products/main.vue"}).errors.length, 0);
    assert.match(page, /<CatalogListSkeleton v-if="initialLoading" kind="products" label="productos"\/>/);
    assert.match(page, /await this\.initParams\(\)[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false[\s\S]*?await this\.listEntity\(\{\}\)/);
    assert.match(page, /<tr v-if="entityList\.extras\.loading" class="fg-table__state">[\s\S]*?Buscando productos…/);
    assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/);
    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    assert.match(layout, /@vite\(\["resources\/css\/System\/fg\/index\.css"/);
    assert.doesNotMatch(entry, /index\.css/);
});

test("el skeleton de Productos conserva filtros y columnas con el loader real de la tabla", () => {
    const skeleton = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/Shared/skeleton.css", import.meta.url), "utf8");

    const {descriptor, errors} = parse(skeleton, {filename: "CatalogListSkeleton.vue"});
    assert.deepEqual(errors, []);
    assert.match(descriptor.template.content, /role="status" aria-live="polite"/);
    assert.match(skeleton, /<FgSkeletonFilters[\s\S]*?fg-products-list[\s\S]*?fg-table/);
    assert.doesNotMatch(skeleton, /br-/);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const component = new Function("FgListLoading", "FgSkeletonFilters", script)({}, {});
    const columns = component.computed.columns.call({kind: "products"});
    assert.deepEqual(columns.map(column => column.title), ["Identificación", "Producto", "Precio", "Estado", "Acciones"]);
    assert.match(skeleton, /:colspan="columns.length"><FgListLoading/);
    assert.deepEqual(columns.map(column => column.width), ["22%", "40%", "16%", "13%", "9%"]);
    assert.equal(columns[2].align, "fg-align-end");
    assert.match(styles, /height: var\(--fg-control-height, 36px\)/);
    assert.match(styles, /@media \(max-width: 767px\)/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
});
