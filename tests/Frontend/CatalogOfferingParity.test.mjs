import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const modules = [
    {name: "servicio", path: "resources/js/System/Pages/Catalogs/services/main.vue", ownFields: ["estimated_duration_minutes", "capacity_limit"]},
    {name: "membresía", path: "resources/js/System/Pages/Catalogs/subscriptions/main.vue", ownFields: ["duration_value", "attendance_limit_per_day", "benefits_text", "restrictions_text"]}
];

for(const module of modules) {

    test(`el formulario de ${module.name} conserva el patrón visual y sus campos propios`, () => {

        const source = readFileSync(new URL(`../../${module.path}`, import.meta.url), "utf8");
        const parsed = parse(source, {filename: module.path});

        assert.equal(parsed.errors.length, 0);
        assert.match(source, new RegExp(`add: "Agregar ${module.name}"`));
        assert.match(source, new RegExp(`store: "Agregar ${module.name}"`));
        assert.match(source, new RegExp(`update: "Editar ${module.name}"`));
        assert.match(source, /<FormModal/);
        assert.match(source, /<TableActionsDropdown/);
        assert.match(source, /<AddCategory/);

        for(const tab of ["general", "attributes", "taxes"]) {

            assert.match(source, new RegExp(`activeFormTab === '${tab}'`));

        }

        for(const field of module.ownFields) {

            assert.match(source, new RegExp(`v-model="forms\\[entity\\]\\.createUpdate\\.data\\.${field}"`));

        }

        assert.doesNotMatch(source, /activeFormTab === 'inventory'/);

    });

}

test("producto conserva Inventario dentro de la modal compartida", () => {

    const source = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "ProductEditor.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /modal-class="br-entity-modal--tabbed"/);
    assert.match(source, /activeFormTab === 'inventory'/);

});
