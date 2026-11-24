import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {compile} from "@vue/compiler-dom";
import {parse} from "@vue/compiler-sfc";
import * as Vue from "vue";
import {renderToString} from "vue/server-renderer";
import {capacityLabel} from "../../resources/js/System/Components/Catalogs/Offerings/capacityLabel.js";
import {commissionLabel} from "../../resources/js/System/Components/Catalogs/Offerings/commissionLabel.js";
import {commissionRequiresValue, validateCatalogCapacity, validateCatalogCommission} from "../../resources/js/System/Components/Catalogs/catalogFormValidation.js";
import InternalCodePrefixMixin from "../../resources/js/System/Mixins/InternalCodePrefixMixin.js";
import {internalCodeImportHelp, internalCodePrefixLabel} from "../../resources/js/System/Helpers/InternalCodes.js";

const modules = [
    {name: "servicio", path: "resources/js/System/Pages/Catalogs/services/main.vue", ownFields: ["estimated_duration_minutes", "capacity_limit"]},
    {name: "membresía", path: "resources/js/System/Pages/Catalogs/subscriptions/main.vue", ownFields: ["duration_value", "attendance_limit_per_day", "benefits_text", "restrictions_text"]}
];

test("el precio principal usa la escala tipográfica compartida de tablas", () => {

    const tokens = readFileSync(new URL("../../resources/css/Shared/tokens.css", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/fg/products.css", import.meta.url), "utf8");

    assert.match(tokens, /--fg-body-size: [\d.]+px/);
    assert.match(branding, /\.fg-price__sale strong \{[^}]*font-weight: var\(--fg-label-weight\);[^}]*font-variant-numeric: tabular-nums/);

});

test("el catálogo muestra códigos como texto y conserva la descarga del código de barras", () => {

    const product = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");

    assert.equal(parse(product, {filename: "products/main.vue"}).errors.length, 0);
    assert.match(product, /fg-product-codes/);
    assert.match(product, /record\.internal_code/);
    assert.match(product, /record\.barcode/);
    assert.match(product, /<BarcodeDownloadButton[\s\S]*?:value="record\.barcode"/);
    assert.doesNotMatch(product, /record\.brand\??\.name/);
    for(const page of ["products", "services", "subscriptions", "categories", "brands", "recipes"]) {
        const source = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${page}/main.vue`, import.meta.url), "utf8");
        assert.doesNotMatch(source, /<CopyButton|class="br-copy-value"/, page);
    }

});

test("el código interno compartido se presenta como texto", () => {
    const source = readFileSync(new URL("../../resources/js/System/Components/Catalogs/InternalCodeValue.vue", import.meta.url), "utf8");
    assert.equal(parse(source, {filename: "InternalCodeValue.vue"}).errors.length, 0);
    assert.match(source, /<span class="fg-product-codes" v-text="value \|\| '—'"><\/span>/);
    assert.doesNotMatch(source, /CopyButton|role="button"|tabindex/);
});

test("copiar usa un icono interactivo por defecto y permite un botón explícito", async () => {
    const source = readFileSync(new URL("../../resources/js/System/Components/CopyButton.vue", import.meta.url), "utf8");
    const {descriptor} = parse(source);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const component = new Function(script)();
    const {code} = compile(descriptor.template.content, {mode: "function", prefixIdentifiers: true});
    component.render = new Function("Vue", code)(Vue);

    const icon = await renderToString(Vue.h(component, {value: "PROD-1"}));
    assert.match(icon, /^<span[^>]*class="fg-copy-icon"[^>]*role="button"[^>]*tabindex="0"[^>]*title="Copiar"/);
    assert.match(icon, /fa-solid fa-clipboard/);
    const nativeButton = await renderToString(Vue.h(component, {type: "button", value: "PROD-1"}));
    assert.match(nativeButton, /^<button[^>]*class="fg-copy-button"[^>]*type="button"/);

    const disabledIcon = await renderToString(Vue.h(component, {disabled: true, value: "PROD-1"}));
    assert.match(disabledIcon, /aria-disabled="true"/);
    assert.doesNotMatch(disabledIcon, /tabindex/);

    let copies = 0;
    let prevented = 0;
    const control = {isButton: false, disabled: false, copy() { copies++; }};
    const event = {repeat: false, preventDefault() { prevented++; }};
    component.methods.activateIcon.call(control, event);
    component.methods.activateIcon.call(control, {...event, repeat: true});
    component.methods.activateIcon.call({...control, isButton: true}, event);
    component.methods.activateIcon.call({...control, disabled: true}, event);
    assert.equal(copies, 1);
    assert.equal(prevented, 2);
});

test("los tres editores comparten pestañas accesibles y navegables en móvil", () => {

 const paths = ["resources/js/System/Components/Catalogs/Products/ProductEditor.vue", ...modules.map(module => module.path)];
 for(const path of paths) assert.match(readFileSync(new URL("../../"+path,import.meta.url),"utf8"), /<AppTabs\s+v-model="activeFormTab"/);
 const tabs=readFileSync(new URL("../../resources/js/Shared/AppTabs.vue",import.meta.url),"utf8");
 assert.equal(parse(tabs).errors.length,0);
 for(const marker of ['role="tablist"', '@keydown="handleKeydown"', ':aria-controls="sharedControlsId ||', ':aria-selected="modelValue === tab.id"', 'ArrowUp:', 'ArrowDown:', 'Home:', 'End:']) assert.ok(tabs.includes(marker),marker);
 const css=readFileSync(new URL("../../resources/css/Shared/components.css",import.meta.url),"utf8");
 assert.match(css,/\.fg-tabs__list/);
 assert.match(css,/@media \(max-width: 767px\)/);

});

test("productos, servicios y membresías comparten pestañas descriptivas con iconos", () => {

 const tabs=readFileSync(new URL("../../resources/js/Shared/AppTabs.vue",import.meta.url),"utf8");
 assert.match(tabs, /v-if="tab.icon" :class="tab.icon" aria-hidden="true"/);
 assert.match(tabs, /tab.label/);
 assert.match(tabs, /hasErrors\(tab.id\)/);
 const product=readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue",import.meta.url),"utf8");
 assert.match(product,/variant="descriptive"/);
 assert.match(product,/fg-product-summary/);
 assert.match(product,/productName \|\| 'Producto sin nombre'/);
 assert.match(product,/productName\(\)\s*\{\s*return this\.productForm\.data\.name\?\.trim\(\)/);

});

test("la barra compartida solo se desplaza al revelar una pestaña recortada", () => {
    const source = readFileSync(new URL("../../resources/js/Shared/AppTabs.vue", import.meta.url), "utf8");
    const script = parse(source).descriptor.script.content.replace("export default", "return");
    const tabs = new Function(script)();
    const movements = [];
    let bounds = {left: 30, right: 120};
    const list = {
        clientWidth: 200,
        scrollWidth: 480,
        getBoundingClientRect: () => ({left: 20, right: 220}),
        scrollBy: movement => movements.push(movement)
    };
    const instance = {
        $refs: {tabList: list},
        selectedTab: () => ({getBoundingClientRect: () => bounds})
    };

    tabs.methods.revealSelected.call(instance);
    assert.deepEqual(movements, []);

    bounds = {left: 180, right: 260};
    tabs.methods.revealSelected.call(instance);
    bounds = {left: 10, right: 90};
    tabs.methods.revealSelected.call(instance);
    assert.deepEqual(movements, [
        {left: 40, behavior: "instant"},
        {left: -10, behavior: "instant"}
    ]);
});

test("servicios y membresías comparten acciones masivas y una modal de importación sin inventario", () => {

    const modalPath = "resources/js/System/Components/Catalogs/Offerings/OfferingImportModal.vue";
    const modal = readFileSync(new URL(`../../${modalPath}`, import.meta.url), "utf8");
    const sharedModal = readFileSync(new URL("../../resources/js/System/Components/Generics/FileImportModal.vue", import.meta.url), "utf8");
    const requests = readFileSync(new URL("../../resources/js/System/Helpers/Requests.js", import.meta.url), "utf8");

    assert.equal(parse(modal, {filename: modalPath}).errors.length, 0);
    assert.match(modal, /<FileImportModal\b/);
    assert.match(sharedModal, /:close-disabled="busy"/);
    assert.match(sharedModal, /if\(this\.busy\) event\.preventDefault\(\)/);
    assert.doesNotMatch(modal, /warehouse_id|Almacén para el stock inicial/);

    for(const module of modules) {

        const source = readFileSync(new URL(`../../${module.path}`, import.meta.url), "utf8");
        const route = module.name === "servicio" ? "Service" : "Subscription";
        const routes = readFileSync(new URL(`../../routes/System/Catalogs/${route}.php`, import.meta.url), "utf8");

        assert.match(source, /<OfferingImportModal\b/);
        assert.match(source, /@export="downloadRecords"/);
        assert.match(source, /@import="openImportModal"/);
        assert.match(source, /:import-route="routeActions\.import"/);
        assert.match(routes, /Route::get\("\/export"/);
        assert.match(routes, /Route::get\("\/import"/);
        assert.match(routes, /Route::post\("\/import"/);

    }

    assert.match(requests, /services: \{\s*import: "import"/);
    assert.match(requests, /subscriptions: \{\s*import: "import"/);

});

function formGroups(source, tab) {
    return [...source.matchAll(/<section\b([^>]*)>[\s\S]*?<\/section>/g)]
        .find(([, attributes]) => attributes.includes("activeFormTab === " + "'" + tab + "'"))?.[0] ?? "";
}

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

        for(const tab of ["general", "configuration"]) {

            assert.match(source, new RegExp(`activeFormTab === '${tab}'`));

        }

        assert.doesNotMatch(source, /id: "(?:attributes|taxes)"|activeFormTab === '(?:attributes|taxes)'/);
        assert.match(formGroups(source, "configuration"), /<CatalogVisibilityFields\b/);
        const general = formGroups(source, "general");
        assert.match(general, /<InputTextArea\s+v-model="forms\[entity\]\.createUpdate\.data\.description"/);
        assert.match(general, /<FgField label="Categorías"/);

        for(const field of module.ownFields) {

            assert.match(source, new RegExp(`v-model="forms\\[entity\\]\\.createUpdate\\.data\\.${field}"`));

        }

        assert.match(source, /class="fg-check"/);
        assert.match(source, /v-model="forms\[entity\]\.createUpdate\.data\.capacity_control_enabled"/);
        assert.match(source, /:is-required="forms\[entity\]\.createUpdate\.data\.capacity_control_enabled"/);
        assert.match(source, /capacityControl: "Control de cupos"/);
        assert.match(source, /<span>Limitar cupos<\/span>/);

        const configuration = formGroups(source, "configuration");
        assert.match(general, /<CatalogTaxFields\s+v-model:price-includes-tax="forms\[entity\]\.createUpdate\.data\.price_includes_tax"\s+v-model:igv-exempt="forms\[entity\]\.createUpdate\.data\.igv_exempt"/);
        assert.doesNotMatch(configuration, /<CatalogTaxFields\b/);
        assert.ok(configuration.indexOf("<CatalogVisibilityFields") < configuration.indexOf("Vigencia y comisión"));
        for(const placeholder of ["price", "optional", "minPrice", "maxPrice", "categories"]) {
            assert.ok(general.includes(`:placeholder="placeholders.${placeholder}"`));
        }
        const configurationFields = [...configuration.matchAll(/<(?:InputNumber|InputDate|InputSlot)\b[^>]*>/g)];

        assert.ok(configurationFields.length > 0);

        for(const [field] of configurationFields) {

            assert.match(field, /v-model=/);

        }

        assert.doesNotMatch(source, /activeFormTab === 'inventory'/);

    });

}

test("producto conserva Inventario dentro de la modal compartida", () => {

    const source = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "ProductEditor.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /modal-class="'fg-modal--editor'"/);
    assert.match(source, /activeFormTab === 'inventory'/);

});

test("servicio conserva duración en Servicio y agrupa sus límites y comisión en Configuración", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/services/main.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "services/main.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /\{id: "configuration", label: "Configuración",[^\n]*fields: \["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit", "see_my_web", "see_my_web_price"\]\}/);
    assertFieldOrder(source, "forms[entity].createUpdate.data", ["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit"]);
    const general = formGroups(source, "general");
    const configuration = formGroups(source, "configuration");
    assert.match(general, /v-model="forms\[entity\]\.createUpdate\.data\.estimated_duration_minutes"/);
    assert.match(configuration, /v-model="forms\[entity\]\.createUpdate\.data\.capacity_limit"/);
    assert.match(configuration, /v-model="forms\[entity\]\.createUpdate\.data\.expires_at"/);
    assert.match(configuration, /v-model="forms\[entity\]\.createUpdate\.data\.commission_value"/);
    assert.match(source, /estimatedDuration: "Duración estimada"/);
    assert.match(source, /expiresAt: "Disponible hasta"/);

    const durationField = general.match(/<InputNumber\s+v-model="forms\[entity\]\.createUpdate\.data\.estimated_duration_minutes"[\s\S]*?<\/InputNumber>/)?.[0];

    assert.ok(durationField);
    assert.doesNotMatch(durationField, /isRequired/);
    assert.match(durationField, /<span class="fg-field__prefix">min<\/span>/);
    assert.match(general, /data\.name/);
    assert.match(source, /id: "general",[^\n]*"description", "categories"\]/);
    assert.match(general, /<details class="fg-disclosure"[\s\S]*?data\.description"/);
    assert.match(general, /<FgField label="Categorías"/);

});

test("membresía agrupa cupos, asistencia, disponibilidad y comisión en Configuración", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/subscriptions/main.vue", import.meta.url), "utf8");
    const parsed = parse(source, {filename: "subscriptions/main.vue"});

    assert.equal(parsed.errors.length, 0);
    assert.match(source, /\{id: "configuration", label: "Configuración",[^\n]*fields: \["expires_at", "commission_type", "commission_value", "attendance_limit_per_day", "capacity_control_enabled", "capacity_limit", "see_my_web", "see_my_web_price"\]\}/);
    assertFieldOrder(source, "forms[entity].createUpdate.data", ["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit", "attendance_limit_per_day"]);

    for(const field of ["attendance_limit_per_day", "capacity_limit", "expires_at", "commission_value"]) {

        assert.match(formGroups(source, "configuration"), new RegExp(`v-model="forms\\[entity\\]\\.createUpdate\\.data\\.${field}"`));

    }

    assert.match(source, /expiresAt: "Disponible hasta"/);
    assert.match(source, /id: "general",[^\n]*"description", "categories", "benefits", "restrictions"\]/);

    const firstTab = formGroups(source, "general");

    assert.ok(firstTab);

    assert.match(firstTab, /data\.duration_value"[\s\S]*?<template #inputGroupAppend>\s*<FgSelect[^>]*class="fg-select--unit"[^>]*data\.duration_type"/);
    assert.match(firstTab, /<details class="fg-disclosure"[\s\S]*?data\.description"/);
    for(const field of ["benefits_text", "restrictions_text"]) {
        assert.match(firstTab, new RegExp("data\\."+field+'"[^>]*maxlength="100"[^>]*show-char-counter'));
    }

});

test("la comisión se muestra cuando hay tipo y valor aplicables", () => {
    assert.equal(commissionLabel({commission_type: "none", commission_value: 3}), null);
    assert.equal(commissionLabel({commission_type: "fixed", commission_value: 0}), null);
    assert.equal(commissionLabel({commission_type: "percentage", commission_value: "3"}), "3.00%");
    assert.equal(commissionLabel({commission_type: "fixed", commission_value: 5, currency: {sign: "S/"}}), "S/ 5.00");
});

test("comisión y cupos son obligatorios según la configuración de la oferta", () => {
    const validate = form => {
        const result = {bool: true, errors: {}};
        validateCatalogCommission(result, form);
        validateCatalogCapacity(result, form);
        return result.errors;
    };

    assert.equal(commissionRequiresValue("none"), false);
    assert.equal(commissionRequiresValue("percentage"), true);
    assert.equal(commissionRequiresValue("fixed"), true);
    assert.deepEqual(validate({commission_type: "none", capacity_control_enabled: false}), {});
    assert.deepEqual(validate({commission_type: {code: "percentage"}, commission_value: 100, capacity_control_enabled: true, capacity_limit: 8}), {});
    assert.deepEqual(validate({commission_type: "fixed", commission_value: 150}), {});
    assert.ok(validate({commission_type: "fixed", commission_value: 0}).commission_value);
    assert.ok(validate({commission_type: {code: "percentage"}, commission_value: ""}).commission_value);
    assert.ok(validate({commission_type: "percentage", commission_value: 101}).commission_value);
    for(const capacity_limit of ["", 0, 1.5, 1000001]) {
        assert.ok(validate({capacity_control_enabled: true, capacity_limit}).capacity_limit);
    }
});

test("el editor envía el mismo código interno que muestra", () => {
    const editor = {...InternalCodePrefixMixin.data(), ...InternalCodePrefixMixin.methods};
    const form = {internal_code: ""};
    const input = () => editor.internalCodeInputProps(form);
    const payload = () => editor.internalCodePayload(form.internal_code);
    const paste = value => {form.internal_code = input().transformValue.call(editor, value);};

    editor.internalCodePrefixLabel = internalCodePrefixLabel(" - memb - ");
    form.internal_code = editor.setEditableInternalCode("PLAN", true);
    paste("MEMB-PLAN");
    assert.equal(form.internal_code, "PLAN");
    assert.equal(input().usePrefix, true);
    assert.deepEqual(payload(), {internal_code: "MEMB-PLAN", internal_code_without_prefix: false});

    input().onTogglePrefix();
    assert.equal(input().usePrefix, false);
    assert.deepEqual(payload(), {internal_code: "PLAN", internal_code_without_prefix: true});
    input().onTogglePrefix();
    assert.equal(input().usePrefix, true);
    assert.equal(payload().internal_code, "MEMB-PLAN");
    input().onTogglePrefix();
    paste("MEMB-PLAN");
    assert.equal(input().usePrefix, true);
    assert.equal(form.internal_code, "PLAN");
    paste("MEMB-");
    assert.equal(payload().internal_code, "");

    editor.internalCodePrefixLabel = internalCodePrefixLabel("GYM");
    form.internal_code = editor.setEditableInternalCode("MEMB-PLAN");
    assert.equal(input().usePrefix, false);
    assert.equal(payload().internal_code, "MEMB-PLAN");
    paste("MEMB-NEW");
    assert.equal(form.internal_code, "MEMB-NEW");
    assert.equal(payload().internal_code, "GYM-MEMB-NEW");

    editor.internalCodePrefixLabel = internalCodePrefixLabel(null);
    assert.equal(input().prefix, "");
    assert.equal(input().usePrefix, false);
    assert.equal(payload().internal_code, "MEMB-NEW");
    assert.equal(internalCodeImportHelp("gym"), "Completa el código interno de cada fila con el prefijo GYM-.");
    assert.equal(internalCodeImportHelp(null), "Completa el código interno de cada fila.");
});

test("producto muestra la información comercial al inicio y la visibilidad en Configuración", () => {

 const source=readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue",import.meta.url),"utf8");
 const general=source.slice(source.indexOf("activeFormTab === 'general'"),source.indexOf("activeFormTab === 'inventory'"));
 const configuration=source.slice(source.indexOf("activeFormTab === 'configuration'"),source.indexOf("</form>"));
 assertFieldOrder(general,"productForm.data",["internal_code","barcode","name","price"]);
 assert.match(general,/data.categories"[\s\S]*?multiple/);
 assert.match(general,/data.brand"/);
 for(const field of ["igv_exempt","price_includes_tax"]) assert.ok(general.includes("productForm.data."+field));
 for(const field of ["see_my_web","see_my_web_price","expires_at","commission_type","commission_value"]) assert.ok(configuration.includes("productForm.data."+field));
 assert.match(general, /<CatalogTaxFields\b/);
 assert.match(configuration, /<CatalogVisibilityFields\b/);
 const visibility=readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogVisibilityFields.vue",import.meta.url),"utf8");
 assert.match(visibility,/:disabled="disabled \|\| !published"/);
 assert.match(visibility,/Publicación en el catálogo/);
 assert.match(visibility,/Precio a consultar/);
 assert.match(configuration,/<CommissionValueInput[\s\S]*?:currency-sign="currencySign"/);
 assert.match(configuration,/:searchable="false"/);

});
