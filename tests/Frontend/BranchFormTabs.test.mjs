import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("sucursales reutiliza la modal y separa alta y edición en tres pestañas", () => {
    const source = read("resources/js/System/Pages/Organizations/branches/main.vue");
    const parsed = parse(source);

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /<FormModal[\s\S]*?:title="isUpdate \? 'EDITAR SUCURSAL' : 'AGREGAR SUCURSAL'"/);
    assert.match(source, /<CatalogFormTabs[\s\S]*?v-model="activeFormTab"[\s\S]*?variant="descriptive"/);

    for(const [id, fields] of [
        ["general", ["internal_code", "name", "capacity", "status"]],
        ["location", ["address", "reference", "map_url"]],
        ["contact", ["telephone", "email"]]
    ]) {
        assert.match(source, new RegExp(`\\{id: "${id}"[^\\n]*fields: \\[${fields.map(field => `"${field}"`).join(", ")}\\]\\}`));
        assert.match(source, new RegExp(`v-show="activeFormTab === '${id}'"`));
    }

    assert.match(source, /this\.activeFormTab = FORM_TABS\[0\]\.id/);
    assert.match(source, /this\.focusFirstErrorTab\(validation\.errors\)/);
    assert.match(source, /this\.focusFirstErrorTab\(entityForms\.errors\)/);
});

test("el aforo conserva el campo capacity y aparece también en el reporte", () => {
    const page = read("resources/js/System/Pages/Organizations/branches/main.vue");
    const skeleton = read("resources/js/System/Components/Catalogs/CatalogListSkeleton.vue");
    const report = read("app/Http/Controllers/System/Essentials/ReportController.php");
    const exportFile = read("app/Exports/BranchExport.php");
    const storeRequest = read("app/Http/Requests/System/Organizations/Branches/StoreBranchRequest.php");
    const updateRequest = read("app/Http/Requests/System/Organizations/Branches/UpdateBranchRequest.php");

    assert.match(page, /<th class="text-center">Aforo<\/th>/);
    assert.match(page, /capacity: "Aforo"/);
    assert.match(page, /v-model="forms\[entity\]\.createUpdate\.data\.capacity"/);
    assert.match(page, /errors\?\.capacity"\s+xl="4"\s+lg="4"/);
    assert.match(page, /errors\?\.status"\s+xl="4"\s+lg="4"/);
    assert.doesNotMatch(page, />Capacidad<|capacity: "Capacidad"/);
    assert.match(skeleton, /title: "Aforo"/);
    assert.match(report, /\$record->aforo = \$branch->capacity;/);
    assert.match(exportFile, /"AFORO"/);
    assert.match(storeRequest, /"capacity" => "aforo"/);
    assert.match(updateRequest, /"capacity" => "aforo"/);
});
