import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {catalogColumns} from "../../resources/js/System/Components/Catalogs/catalogColumns.js";

for(const entity of ["brands", "categories"]) {

    test(`${entity} usa la tabla, las modales y las acciones masivas del catálogo`, () => {

        const path = `../../resources/js/System/Pages/Catalogs/${entity}/main.vue`;
        const source = readFileSync(new URL(path, import.meta.url), "utf8");
        const parsed = parse(source, {filename: path});

        assert.equal(parsed.errors.length, 0);

        for(const component of ["FgCatalogList", "FormModal", "CatalogReferenceImportModal", "TableActionsDropdown", "StatusBadge", "InternalCodeValue"]) {

            assert.match(source, new RegExp(`<${component}\\b`));

        }

        assert.match(source, /@export="downloadRecords"/);
        assert.match(source, /@import="openImportModal"/);
        assert.match(source, /:import-route="routeActions\.import"/);
        assert.match(parsed.descriptor.template.content, /<InternalCodeInput v-model=[^\n]+\.internal_code"[\s\S]*?<\/InternalCodeInput>\s*<InputText v-model=[^\n]+\.name"/);
        assert.match(source, /:placeholder="placeholders\.names\.(?:brand|category)"/);
        assert.match(source, /:placeholder="placeholders\.optional"/);
        assert.doesNotMatch(source, /template-route|importTemplate|import-template/);
        assert.doesNotMatch(source, /\bbr[t]?-[a-z]/);

    });

}

test("categorías conserva descripción y estado en su formulario", () => {

    const path = "../../resources/js/System/Pages/Catalogs/categories/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.doesNotMatch(source, /sort_order|is_public|Visible públicamente|<th class="text-center">Orden</);

});

test("brands conserva solo sus cinco campos comerciales", () => {

    const path = "../../resources/js/System/Pages/Catalogs/brands/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.doesNotMatch(source, /logo_path|origin_country_code|products_count|País de origen|Logo configurado/);
    assert.match(source, /<CatalogUsageBreakdown :record="record" :enabled-types="options\.usage_types"\/>/);
    assert.match(source, /<a v-if="record\.website_url" class="fg-link" :href="record\.website_url" target="_blank" rel="noopener noreferrer"[^>]*>Visitar sitio<\/a>/);
    assert.match(source, /<span v-else class="fg-help">Sin sitio oficial/);

    for(const field of ["internal_code", "name", "description", "status", "website_url"]) {

        assert.match(source, new RegExp("brandForm\\.data\\." + field));

    }

});

test("categorías y marcas presentan el uso por tipo y el skeleton mantiene sus proporciones", () => {

    for(const entity of ["brands", "categories"]) {

        const source = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${entity}/main.vue`, import.meta.url), "utf8");
        assert.ok(catalogColumns[entity].some(column => column.title === "En uso" && column.align === "fg-align-center"));
        assert.match(source, /<CatalogUsageBreakdown :record="record" :enabled-types="options\.usage_types"\/>/);
        assert.doesNotMatch(source, /Uso actual|ítems activos|productos activos/);

    }

    const breakdown = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogUsageBreakdown.vue", import.meta.url), "utf8");
    assert.match(breakdown, /this\.enabledTypes\.includes\(type\)/);
    assert.match(breakdown, /<details v-if="total"/);
    assert.match(breakdown, /Sin uso/);
    assert.match(breakdown, /active_products_count/);
    assert.match(breakdown, /active_services_count/);
    assert.match(breakdown, /active_subscriptions_count/);

    const skeleton = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    assert.deepEqual(catalogColumns.brands.find(column => column.title === "Sitio oficial"), {title: "Sitio oficial", width: "14%", align: "fg-align-center"});
    assert.match(skeleton, /:colspan="columns.length"><FgListLoading/);

});

test("el desglose conserva los ceros y suma solo los módulos visibles", () => {

    const source = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogUsageBreakdown.vue", import.meta.url), "utf8");
    const descriptor = parse(source).descriptor;
    const component = new Function(descriptor.script.content.replace("export default", "return"))();
    const context = {
        enabledTypes: ["product", "subscription"],
        record: {active_products_count: 3, active_services_count: 99, active_subscriptions_count: 1}
    };

    context.entries = component.computed.entries.call(context);
    context.total = component.computed.total.call(context);

    assert.deepEqual(context.entries.map(({type, count}) => [type, count]), [["product", 3], ["subscription", 1]]);
    assert.equal(context.total, 4);

    context.record = {active_products_count: 0, active_subscriptions_count: 0};
    context.entries = component.computed.entries.call(context);
    assert.equal(component.computed.total.call(context), 0);

});
