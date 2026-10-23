import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

for(const entity of ["brands", "categories"]) {

    test(`${entity} usa la tabla, las modales y las acciones masivas del catálogo`, () => {

        const path = `../../resources/js/System/Pages/Catalogs/${entity}/main.vue`;
        const source = readFileSync(new URL(path, import.meta.url), "utf8");
        const parsed = parse(source, {filename: path});

        assert.equal(parsed.errors.length, 0);

        for(const component of ["FiltersSection", "FormModal", "CatalogReferenceImportModal", "TableActionsDropdown", "StatusBadge"]) {

            assert.match(source, new RegExp(`<${component}\\b`));

        }

        assert.match(source, /<thead class="br-table-header-surface">/);
        assert.match(source, /:show-download-button="true"/);
        assert.match(source, /:show-import-button="true"/);
        assert.match(source, /@download="downloadRecords"/);
        assert.match(source, /@import="openImportModal"/);
        assert.match(source, /:import-route="routeActions\.import"/);
        assert.doesNotMatch(source, /template-route|importTemplate|import-template/);
        assert.match(source, /class="br-entity-empty-state"/);
        assert.doesNotMatch(source, /btn-header-modal|table table-hover|btn btn-xs btn-warning/);
        assert.match(source, /<thead class="br-table-header-surface">\s*<tr>\s*<th>Identificación<\/th>/);
        assert.match(source, /<tr v-for="record in entityList\.records\.data" :key="record\.id">\s*<td class="br-entity-table__identification-cell">/);
        assert.match(source, /br-entity-identifiers br-entity-identifiers--plain/);
        assert.match(source, /br-entity-identifier__label colon-at-end">Cód\. interno<\/span>/);
        assert.match(source, /br-entity-identifier__actions[\s\S]*?<CopyButton :value="record\.internal_code" label="Código interno"\/>/);

    });

}

test("categories omite orden y publicación y distribuye descripción y estado en 8/4", () => {

    const path = "../../resources/js/System/Pages/Catalogs/categories/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.doesNotMatch(source, /sort_order|is_public|Visible públicamente|<th class="text-center">Orden</);
    assert.match(source, /data\.description"[\s\S]*?xl="8"\s+lg="8"/);
    assert.match(source, /<InputSlot[\s\S]*?:title="MODULE\.texts\.form\.status"[\s\S]*?xl="4"\s+lg="4"/);

});

test("brands conserva solo sus cinco campos comerciales", () => {

    const path = "../../resources/js/System/Pages/Catalogs/brands/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.doesNotMatch(source, /logo_path|origin_country_code|products_count|País de origen|Logo configurado/);
    assert.match(source, /<th class="text-center">Uso actual<\/th>/);
    assert.match(source, /record\.active_items_count/);
    assert.match(source, /producto activo/);

    for(const field of ["internal_code", "name", "description", "status", "website_url"]) {

        assert.match(source, new RegExp("brandForm\\.data\\." + field));

    }

    assert.match(source, /<CopyButton\b/);
    assert.match(source, /components: \{[^}]*CopyButton/);

});
