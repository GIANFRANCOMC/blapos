import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const routes = [
    ["services", "servicios"],
    ["subscriptions", "membresías"],
    ["categories", "categorías"],
    ["brands", "marcas"]
];

test("los cuatro catálogos usan el skeleton durante parámetros y primer listado", () => {
    for(const [route, label] of routes) {
        const page = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${route}/main.vue`, import.meta.url), "utf8");
        const entry = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${route}/main.js`, import.meta.url), "utf8");

        assert.equal(parse(page, {filename: `${route}/main.vue`}).errors.length, 0, route);
        assert.ok(page.includes(`<CatalogListSkeleton v-if="initialLoading && skeletonEnabled" kind="${route}" label="${label}"/>`), route);
        assert.match(page, /<main v-else class="br-entity">/, route);
        assert.match(page, /createInitialLoad\(\{skeleton: true\}\)/, route);
        assert.match(page, /initialLoad\.start\(\)[\s\S]*?await this\.initParams\(\)[\s\S]*?await this\.listEntity\(\{\}\)[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\);[\s\S]*?this\.initialLoading = false/, route);
        assert.match(page, /<tr v-if="entityList\.extras\.loading">[\s\S]*?<Loader\/>/, route);
        assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/, route);
        assert.match(entry, /Components\/Catalogs\/catalog-list-skeleton\.css/, route);
    }
});

test("el skeleton compartido representa las columnas y filas de cada catálogo", () => {
    const component = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Components/Catalogs/catalog-list-skeleton.css", import.meta.url), "utf8");

    assert.equal(parse(component, {filename: "CatalogListSkeleton.vue"}).errors.length, 0);
    assert.match(component, /role="status" aria-live="polite"/);
    assert.match(component, /br-filter-bar[\s\S]*?br-entity-list[\s\S]*?br-entity-table/);
    for(const route of routes.map(([name]) => name)) assert.match(component, new RegExp(`${route}: \\[`, "m"));
    assert.match(component, /kind === 'subscriptions' \? 4 : 3/);
    assert.match(component, /v-for="index in 8"/);
    assert.match(styles, /height: var\(--br-control-height\)/);
    assert.match(styles, /@media \(max-width: 991\.98px\)/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
});
