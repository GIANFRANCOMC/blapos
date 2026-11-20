import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const readSharedSkeleton = () => readFileSync(new URL("../../resources/css/Shared/skeleton.css", import.meta.url), "utf8");
import {parse} from "@vue/compiler-sfc";

test("Productos usa skeleton solo durante initParams", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.js", import.meta.url), "utf8");

    assert.equal(parse(page, {filename: "products/main.vue"}).errors.length, 0);
    assert.match(page, /<CatalogListSkeleton v-if="initialLoading" kind="products" label="productos"\/>[\s\S]*?<main v-else class="br-entity">/);
    assert.match(page, /await this\.initParams\(\)[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false[\s\S]*?await this\.listEntity\(\{\}\)/);
    assert.match(page, /<tr class="br-table-state" v-if="entityList\.extras\.loading">[\s\S]*?<Loader label="Buscando productos…"\/>/);
    assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/);
    assert.match(entry, /Components\/Catalogs\/catalog-list-skeleton\.css/);
});

test("el skeleton de Productos conserva filtros y columnas con el loader real de la tabla", () => {
    const skeleton = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Components/Catalogs/catalog-list-skeleton.css", import.meta.url), "utf8");

    const {descriptor, errors} = parse(skeleton, {filename: "CatalogListSkeleton.vue"});
    assert.deepEqual(errors, []);
    assert.match(descriptor.template.content, /role="status" aria-live="polite"/);
    assert.match(skeleton, /br-filter-bar[\s\S]*?br-entity-list[\s\S]*?br-entity-table/);
    const script = descriptor.script.content.replace(/^import .*;\s*$/m, "").replace("export default", "return");
    const component = new Function("Loader", script)({});
    const columns = component.computed.columns.call({kind: "products"});
    assert.deepEqual(columns.map(column => column.title), ["Identificación", "Producto", "Precio", "Estado", ""]);
    assert.match(skeleton, /:colspan="columns.length" class="py-4"><Loader\/>/);
    assert.match(styles, /height: var\(--br-control-height\)/);
    assert.match(styles, /@media \(max-width: 991\.98px\)/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
});
