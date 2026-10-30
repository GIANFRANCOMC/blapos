import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("Mi empresa conserva pestañas, formulario y botón durante la carga inicial", () => {
    const page = read("resources/js/System/Pages/Organizations/companies/main.vue");
    const skeleton = read("resources/js/System/Pages/Organizations/companies/CompanySettingsSkeleton.vue");
    const styles = read("resources/css/System/Pages/Organizations/companies/skeleton.css");
    const entry = read("resources/js/System/Pages/Organizations/companies/main.js");

    assert.equal(parse(page, {filename: "companies/main.vue"}).errors.length, 0);
    assert.equal(parse(skeleton, {filename: "CompanySettingsSkeleton.vue"}).errors.length, 0);
    assert.match(page, /<CompanySettingsSkeleton v-if="initialLoading && skeletonEnabled"\/>[\s\S]*?<main v-else class="br-company-settings br-entity-modal--tabbed">/);
    assert.match(page, /createInitialLoad\(\{skeleton: true\}\)/);
    assert.match(page, /initialLoad\.start\(\)[\s\S]*?await this\.initParams\(\)[\s\S]*?await this\.initOthers\(\)[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\);[\s\S]*?this\.initialLoading = false/);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando empresa"/);
    assert.match(skeleton, /v-for="index in 3"[\s\S]*?v-for="index in 6"[\s\S]*?br-company-settings__footer/);
    assert.match(styles, /height: var\(--br-control-height\)/);
    assert.match(styles, /height: var\(--br-btn-height\)/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /Organizations\/companies\/skeleton\.css/);
});

test("Sucursales y Usuarios muestran su tabla esqueleto hasta terminar el primer listado", () => {
    const skeleton = read("resources/js/System/Components/Catalogs/CatalogListSkeleton.vue");

    for(const [route, label] of [["branches", "sucursales"], ["users", "colaboradores"]]) {
        const page = read(`resources/js/System/Pages/Organizations/${route}/main.vue`);
        const entry = read(`resources/js/System/Pages/Organizations/${route}/main.js`);

        assert.equal(parse(page, {filename: `${route}/main.vue`}).errors.length, 0, route);
        assert.ok(page.includes(`<CatalogListSkeleton v-if="initialLoading && skeletonEnabled" kind="${route}" label="${label}" :row-count="12"/>`), route);
        assert.match(page, /createInitialLoad\(\{skeleton: true\}\)/, route);
        assert.match(page, /initialLoad\.start\(\)[\s\S]*?await this\.initParams\(\)[\s\S]*?await this\.listEntity\(\{\}\)[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\);[\s\S]*?this\.initialLoading = false/, route);
        assert.match(page, /this\.\$nextTick\(\(\) => Alerts\.tooltips\(\{\}\)\)/, route);
        assert.match(page, /<tr v-if="entityList\.extras\.loading">[\s\S]*?<Loader\/>/, route);
        assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/, route);
        assert.match(entry, /Components\/Catalogs\/catalog-list-skeleton\.css/, route);
        assert.match(skeleton, new RegExp(`${route}: \\[`, "m"));
    }

    assert.match(skeleton, /br-entity-table--directory/);
    assert.match(skeleton, /v-for="index in rowCount"/);
});
