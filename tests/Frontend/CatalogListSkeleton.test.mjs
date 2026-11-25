import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const readSharedSkeleton = () => readFileSync(new URL("../../resources/css/Shared/skeleton.css", import.meta.url), "utf8");
import {parse} from "@vue/compiler-sfc";
import {catalogColumns} from "../../resources/js/System/Components/Catalogs/catalogColumns.js";

const routes = [
    ["services", "servicios"],
    ["subscriptions", "membresías"],
    ["categories", "categorías"],
    ["brands", "marcas"]
];

test("los cuatro catálogos usan el skeleton solo durante parámetros", () => {
    const list = readFileSync(new URL("../../resources/js/System/Components/Catalogs/FgCatalogList.vue", import.meta.url), "utf8");
    assert.match(list, /<CatalogListSkeleton v-if="initialLoading"/);
    assert.match(list, /<tr\b[^>]*\bv-if="loading"[^>]*\bclass="fg-table__state"[^>]*>\s*<td\b[^>]*>\s*<FgListLoading\b/);
    for(const [route, label] of routes) {
        const page = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${route}/main.vue`, import.meta.url), "utf8");
        const entry = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${route}/main.js`, import.meta.url), "utf8");

        assert.equal(parse(page, {filename: `${route}/main.vue`}).errors.length, 0, route);
        assert.match(page, /<FgCatalogList\b/);
        assert.ok(page.includes('plural="' + label + '"'));
        assert.match(page, /:initial-loading="initialLoading"/);
        assert.match(page, /await this\.initParams\(\)[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false[\s\S]*?await this\.listEntity\(\{\}\)/, route);
        assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/, route);
        assert.doesNotMatch(entry, /catalog-list-skeleton\.css/, route);
    }
});

test("el skeleton FG conserva las columnas de cada catálogo sin estilos antiguos", () => {
    const source = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    const {descriptor, errors} = parse(source, {filename: "CatalogListSkeleton.vue"});
    assert.deepEqual(errors, []);
    assert.doesNotMatch(source, /br-|className/);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const component = new Function("FgListLoading", "FgSkeletonFilters", "catalogColumns", script)({}, {}, catalogColumns);
    for(const kind of ["products", "services", "subscriptions", "categories", "brands", "recipes", "branches", "users"]) {
        const columns = component.computed.columns.call({kind});
        assert.ok(columns.length >= 5, kind);
        assert.equal(columns.at(-1).title, "Acciones", kind);
        assert.ok(columns.some(column => column.title === "Estado"), kind);
    }
    assert.match(source, /:colspan="columns.length"><FgListLoading/);
    assert.doesNotMatch(source, /rowCount|skeleton__pagination/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
});
