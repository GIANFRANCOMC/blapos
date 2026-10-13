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

        assert.match(source, /class="br-entity-switch br-entity-switch--field"/);
        assert.match(source, /v-model="forms\[entity\]\.createUpdate\.data\.capacity_control_enabled"/);
        assert.match(source, /capacityControl: "¿Controlar cupos\?"/);
        assert.match(source, /<span>Activar <small>· Límite de cupos<\/small><\/span>/);

        const configurationFields = [...source.matchAll(/<(?:InputNumber|InputDate|InputSlot)\s+v-show="activeFormTab === 'configuration'"[\s\S]*?>/g)];

        assert.ok(configurationFields.length > 0);

        for(const [field] of configurationFields) {

            assert.match(field, /xl="4"\s+lg="4"/);

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

test("servicio conserva duración en Servicio y agrupa sus límites y comisión en Configuración", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/services/main.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "services/main.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /\{id: "configuration", label: "CONFIGURACIÓN", fields: \["capacity_control_enabled", "capacity_limit", "expires_at", "commission_type", "commission_value"\]\}/);
    assert.match(source, /v-show="activeFormTab === 'general'"\s+v-model="forms\[entity\]\.createUpdate\.data\.estimated_duration_minutes"/);
    assert.match(source, /v-show="activeFormTab === 'configuration'"\s+v-model="forms\[entity\]\.createUpdate\.data\.capacity_limit"/);
    assert.match(source, /v-show="activeFormTab === 'configuration'"\s+v-model="forms\[entity\]\.createUpdate\.data\.expires_at"/);
    assert.match(source, /v-show="activeFormTab === 'configuration'"\s+v-model="forms\[entity\]\.createUpdate\.data\.commission_value"/);
    assert.match(source, /estimatedDuration: "Duración estimada"/);
    assert.match(source, /expiresAt: "Disponible hasta"/);

    const durationField = source.match(/<InputNumber\s+v-show="activeFormTab === 'general'"\s+v-model="forms\[entity\]\.createUpdate\.data\.estimated_duration_minutes"[\s\S]*?<\/InputNumber>/)?.[0];

    assert.ok(durationField);
    assert.doesNotMatch(durationField, /isRequired/);
    assert.match(durationField, /<span class="input-group-text br-input-unit-suffix">min<\/span>/);
    assert.ok(source.indexOf('v-model="forms[entity].createUpdate.data.name"') < source.indexOf(durationField));
    assert.match(source, /\{id: "attributes", label: "DETALLE"/);
    assert.match(source, /data\.description"[\s\S]*?xl="8"\s+lg="8"/);
    assert.match(source, /:title="MODULE\.texts\.form\.categories"[\s\S]*?xl="4"\s+lg="4"/);

});

test("membresía agrupa cupos, asistencia, disponibilidad y comisión en Configuración", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/subscriptions/main.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "subscriptions/main.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /\{id: "configuration", label: "CONFIGURACIÓN", fields: \["attendance_limit_per_day", "capacity_control_enabled", "capacity_limit", "expires_at", "commission_type", "commission_value"\]\}/);

    for(const field of ["attendance_limit_per_day", "capacity_limit", "expires_at", "commission_value"]) {

        assert.match(source, new RegExp(`v-show="activeFormTab === 'configuration'"\\s+v-model="forms\\[entity\\]\\.createUpdate\\.data\\.${field}"`));

    }

    assert.match(source, /expiresAt: "Disponible hasta"/);
    assert.match(source, /\{id: "attributes", label: "DETALLE"/);

    const firstTab = source.match(/<InputText\s+v-show="activeFormTab === 'general'"[\s\S]*?<InputText\s+v-show="activeFormTab === 'attributes'"/)?.[0];

    assert.ok(firstTab);

    assert.match(firstTab, /data\.name"[\s\S]*?xl="3"\s+lg="3"/);
    assert.match(firstTab, /data\.duration_value"[\s\S]*?xl="3"\s+lg="3"/);
    assert.match(firstTab, /:title="MODULE\.texts\.form\.durationType"[\s\S]*?xl="3"\s+lg="3"[\s\S]*?data\.duration_type"/);

    assert.ok(firstTab.indexOf('data.name"') < firstTab.indexOf('data.duration_value"'));
    assert.ok(firstTab.indexOf('data.duration_value"') < firstTab.indexOf('data.duration_type"'));
    assert.match(source, /data\.description"[\s\S]*?xl="6"\s+lg="6"/);
    assert.match(source, /data\.benefits_text"[\s\S]*?maxlength="100"\s+showCharCounter[\s\S]*?xl="6"\s+lg="6"/);
    assert.match(source, /data\.restrictions_text"[\s\S]*?maxlength="100"\s+showCharCounter[\s\S]*?xl="6"\s+lg="6"/);
    assert.match(source, /:title="MODULE\.texts\.form\.categories"[\s\S]*?xl="6"\s+lg="6"/);
    assert.ok(source.indexOf('data.description"') < source.indexOf('data.categories"'));
    assert.ok(source.indexOf('data.categories"') < source.indexOf('data.benefits_text"'));

});

test("producto ordena Detalle y separa Configuración, Impuestos e Inventario", () => {

    const component = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const config = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/productFormConfig.js", import.meta.url), "utf8");
    const parsed = parse(component, {filename: "ProductEditor.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(component, /br-entity-tabs--five/);
    assert.match(config, /id: "attributes",\s+label: "DETALLE"/);
    assert.match(config, /id: "configuration",\s+label: "CONFIGURACIÓN",\s+fields: \["expires_at", "commission_type", "commission_value"\]/);
    assert.match(config, /expiresAt: "Disponible hasta"/);
    assert.deepEqual([...config.matchAll(/id: "(general|attributes|inventory|taxes|configuration)"/g)].map(match => match[1]), ["general", "attributes", "inventory", "taxes", "configuration"]);

    const configurationPanel = component.match(/<section\s+v-show="activeFormTab === 'configuration'"[\s\S]*?<\/section>/)?.[0];

    assert.ok(configurationPanel);
    assert.match(configurationPanel, /v-model="productForm\.data\.expires_at"/);
    assert.match(configurationPanel, /v-model="productForm\.data\.commission_type"/);
    assert.match(configurationPanel, /v-model="productForm\.data\.commission_value"/);

    for(const [field] of configurationPanel.matchAll(/<(?:InputNumber|InputDate|InputSlot)\b[\s\S]*?>/g)) {

        assert.match(field, /xl="4"\s+lg="4"/);

    }

    const detailPanel = component.match(/<section\s+v-show="activeFormTab === 'attributes'"[\s\S]*?<\/section>/)?.[0];

    assert.ok(detailPanel);
    assert.ok(detailPanel.indexOf('data.description"') < detailPanel.indexOf('data.categories"'));
    assert.ok(detailPanel.indexOf('data.categories"') < detailPanel.indexOf('data.brand"'));
    assert.match(detailPanel, /data\.description"[\s\S]*?xl="4"\s+lg="4"/);
    assert.match(detailPanel, /:title="MODULE\.texts\.form\.brand"[\s\S]*?xl="4"\s+lg="4"/);
    assert.match(detailPanel, /:title="MODULE\.texts\.form\.categories"[\s\S]*?xl="4"\s+lg="4"/);

});
