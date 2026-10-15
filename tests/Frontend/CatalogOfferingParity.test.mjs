import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const modules = [
    {name: "servicio", path: "resources/js/System/Pages/Catalogs/services/main.vue", ownFields: ["estimated_duration_minutes", "capacity_limit"]},
    {name: "membresía", path: "resources/js/System/Pages/Catalogs/subscriptions/main.vue", ownFields: ["duration_value", "attendance_limit_per_day", "benefits_text", "restrictions_text"]}
];

test("precio principal e inventario comparten la escala tipográfica de tablas", () => {

    const tokens = readFileSync(new URL("../../resources/css/System/br-branding/00-tokens.css", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");

    assert.match(tokens, /--br-table-value-font-size: 0\.82rem/);
    assert.match(branding, /\.br-entity-price-row\.is-sale strong \{[\s\S]*?font-size: var\(--br-table-value-font-size\)/);
    assert.match(branding, /\.br-entity-stock \{[\s\S]*?font-size: var\(--br-table-value-font-size\)/);

});

test("productos muestra códigos sin recuadro y copia con icono discreto", () => {

    const product = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");

    assert.equal(parse(product, {filename: "products/main.vue"}).errors.length, 0);
    assert.match(product, /br-entity-identifiers br-entity-identifiers--plain/);
    assert.match(product, /<CopyButton[\s\S]*?:value="record\.internal_code"/);
    assert.match(product, /<CopyButton[\s\S]*?:value="record\.barcode"/);
    assert.match(branding, /\.br-entity-identifiers--plain :is\(\.br-entity-code, \.br-entity-barcode\)/);
    assert.match(product, /br-entity-identifier__label colon-at-end">Cód\. interno/);
    assert.match(product, /br-entity-identifier__label colon-at-end">Cód\. barras/);
    assert.match(branding, /\.br-entity-identifiers--plain \{\s*grid-template-columns: max-content minmax\(0, 1fr\)/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-entity-identifier \{\s*display: contents/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-entity-identifier__label \{[\s\S]*?overflow: visible/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-entity-identifier__label \{[\s\S]*?font-size: 0\.67rem/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-entity-identifier__value \{\s*display: flex;\s*align-items: baseline/);
    assert.match(branding, /\.br-entity-identifiers--plain :is\(\.br-entity-code, \.br-entity-barcode\) \{[\s\S]*?font-size: 0\.76rem/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-copy-button/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-copy-button i \{\s*transform: translateY\(0\.12em\)/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-copy-button \{[\s\S]*?color: var\(--br-primary-active\)/);
    assert.match(branding, /\.br-entity-identifiers--plain \.br-copy-button:is\(:hover, :focus-visible\) i \{[\s\S]*?filter: brightness\(0\.78\)/);

});

test("los tres editores comparten pestañas accesibles y navegables en móvil", () => {

    const editorPaths = [
        "resources/js/System/Components/Catalogs/Products/ProductEditor.vue",
        ...modules.map(module => module.path)
    ];
    const tabs = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogFormTabs.vue", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/99-system-wide-visual-polish.css", import.meta.url), "utf8");

    for(const path of editorPaths) {

        const source = readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

        assert.match(source, /<CatalogFormTabs\s+v-model="activeFormTab"/);

    }

    assert.equal(parse(tabs, {filename: "CatalogFormTabs.vue"}).errors.length, 0);
    assert.match(tabs, /role="tablist"/);
    assert.match(tabs, /@keydown="handleKeydown"/);
    assert.match(tabs, /tab-trigger-\$\{tab\.id\}/);
    assert.match(tabs, /:aria-controls="controlsPrefix/);
    assert.match(branding, /\.br-catalog-form-tabs[\s\S]*?overflow-x: auto/);

});

test("producto usa pestañas descriptivas con iconos y conserva la variante compacta en otros editores", () => {

    const product = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const config = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/productFormConfig.js", import.meta.url), "utf8");
    const tabConfig = config.split("export const FORM_FIELDS")[0];
    const tabs = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogFormTabs.vue", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/99-system-wide-visual-polish.css", import.meta.url), "utf8");

    assert.match(product, /<CatalogFormTabs[\s\S]*?variant="descriptive"/);
    assert.match(tabs, /variant: \{type: String, default: "compact"\}/);
    assert.match(tabs, /<small v-if="variant === 'descriptive' && tab\.description">/);
    assert.match(tabs, /class="br-entity-tab__icon" aria-hidden="true"/);
    assert.match(tabs, /<strong>[\s\S]*?class="br-entity-tab__icon"[\s\S]*?\{\{ tab\.label \}\}[\s\S]*?<\/strong>/);
    assert.match(tabs, /aria-label="Pestaña anterior"/);
    assert.match(tabs, /aria-label="Pestaña siguiente"/);
    assert.match(tabs, /selectAdjacentTab\(direction\)/);
    assert.equal([...tabConfig.matchAll(/description: "/g)].length, 5);
    assert.equal([...tabConfig.matchAll(/icon: "fa-solid/g)].length, 5);
    assert.match(branding, /\.br-catalog-form-tabs--descriptive[\s\S]*?scroll-snap-type: x proximity/);

    for(const module of modules) {

        const source = readFileSync(new URL(`../../${module.path}`, import.meta.url), "utf8");

        assert.doesNotMatch(source, /variant="descriptive"/);

    }

});

function assertFieldOrder(source, modelPrefix, fields) {

    const positions = fields.map(field => source.indexOf(`v-model="${modelPrefix}.${field}"`));

    assert.ok(positions.every(position => position >= 0));
    assert.ok(positions.every((position, index) => index === 0 || position > positions[index - 1]));

}

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
    assert.match(source, /\{id: "configuration", label: "CONFIGURACIÓN", fields: \["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit"\]\}/);
    assertFieldOrder(source, "forms[entity].createUpdate.data", ["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit"]);
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
    assert.match(source, /\{id: "configuration", label: "CONFIGURACIÓN", fields: \["expires_at", "commission_type", "commission_value", "attendance_limit_per_day", "capacity_control_enabled", "capacity_limit"\]\}/);
    assertFieldOrder(source, "forms[entity].createUpdate.data", ["expires_at", "commission_type", "commission_value", "attendance_limit_per_day", "capacity_control_enabled", "capacity_limit"]);

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
    assert.match(component, /<CatalogFormTabs[\s\S]*?:tabs="formTabs"/);
    assert.match(readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogFormTabs.vue", import.meta.url), "utf8"), /'br-entity-tabs--five': tabs\.length === 5/);
    assert.match(config, /id: "attributes",\s+label: "DETALLE"/);
    assert.match(config, /id: "configuration",\s+label: "CONFIGURACIÓN",[\s\S]*?fields: \["expires_at", "commission_type", "commission_value"\]/);
    assert.match(config, /expiresAt: "Disponible hasta"/);
    assert.deepEqual([...config.matchAll(/id: "(general|attributes|inventory|taxes|configuration)"/g)].map(match => match[1]), ["general", "attributes", "inventory", "taxes", "configuration"]);

    const configurationPanel = component.match(/<section\s+v-show="activeFormTab === 'configuration'"[\s\S]*?<\/section>/)?.[0];

    assert.ok(configurationPanel);
    assert.match(configurationPanel, /v-model="productForm\.data\.expires_at"/);
    assert.match(configurationPanel, /v-model="productForm\.data\.commission_type"/);
    assert.match(configurationPanel, /v-model="productForm\.data\.commission_value"/);
    assertFieldOrder(configurationPanel, "productForm.data", ["expires_at", "commission_type", "commission_value"]);

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
