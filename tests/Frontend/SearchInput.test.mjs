import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {parse as parseTemplate} from "@vue/compiler-dom";
import postcss from "postcss";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");
const readInventory = () => read("resources/js/System/Pages/Warehouses/inventory/stock.vue")
    + read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");

test("el buscador compartido limpia el texto y conserva la búsqueda explícita", () => {

    const component = read("resources/js/System/Components/Generics/SearchInput.vue");
    const filters = read("resources/js/System/Components/Generics/FiltersSection.vue");
    const inventory = readInventory();

    assert.match(component, /<InputText[\s\S]*?@enterKeyPressed="\$emit\('search'\)"/);
    assert.match(component, /v-if="String\(modelValue \?\? ''\)\.length > 0"/);
    assert.match(component, /clear\(\) \{[\s\S]*?\$emit\("update:modelValue", ""\);[\s\S]*?\$emit\("clear"\);[\s\S]*?this\.focus\(\);/);
    assert.match(filters, /<SearchInput[\s\S]*?@search="\$emit\('search'\)"/);
    assert.match(inventory, /<SearchInput[\s\S]*?@clear="clearProductSearch"/);
    assert.doesNotMatch(component, /this\.\$emit\("search"\)/);

});

test("los buscadores propios reutilizan SearchInput y el panel central conserva limpiar", () => {

    for(const path of [
        "resources/js/System/Pages/Finance/accounts_payable/main.vue",
        "resources/js/System/Pages/Finance/accounts_receivable/main.vue",
        "resources/js/System/Pages/Finance/cash_registers/main.vue",
        "resources/js/System/Pages/Finance/misc_expenses/main.vue",
        "resources/js/System/Pages/General/master_data/main.vue",
        "resources/js/System/Pages/Organizations/roles/main.vue",
        "resources/js/System/Pages/Purchases/purchases/main.vue",
        "resources/js/System/Pages/Purchases/suppliers/main.vue",
        "resources/js/System/Pages/Sales/pos/main.vue",
        "resources/js/System/Pages/Sales/quotations/main.vue",
        "resources/js/System/Pages/Sales/sales/deliveries.vue",
        "resources/js/System/Pages/Sales/sales/list.vue"
    ]) {

        assert.match(read(path), /<SearchInput/);

    }

    const platform = read("resources/js/Platform/pages/TenantIndex.vue");
    assert.match(platform, /class="platform-search__clear"[\s\S]*?@click="filters\.search = ''; scheduleSearch\(\)"/);

});

test("los atributos del buscador llegan al input nativo en ambas variantes", () => {

    const search = read("resources/js/System/Components/Generics/SearchInput.vue");
    const text = read("resources/js/System/Components/InputText.vue");
    const base = read("resources/js/System/Components/InputBase.vue");
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");

    assert.match(search, /<InputText[\s\S]*?v-bind="\$attrs"/);
    assert.match(text, /<InputBase[\s\S]*?v-bind="\$attrs"/);
    assert.match(base, /name: "InputBase",\s*inheritAttrs: false/);
    assert.equal((base.match(/<input\s+ref="inputElement"\s+v-bind="\$attrs"/g) || []).length, 2);
    assert.match(modal, /<label for="inventoryAlertProduct"[\s\S]*?<SearchInput\s+id="inventoryAlertProduct"/);

});

test("limpiar Producto en alertas solo actualiza el texto y devuelve el foco", () => {
    const {descriptor} = parse(read("resources/js/System/Components/Generics/SearchInput.vue"));
    const component = new Function("InputText", descriptor.script.content
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))({});
    const events = [];
    const context = {
        $emit: (...args) => events.push(args),
        $refs: {input: {focus() { events.push(["focus"]); }}}
    };
    context.focus = component.methods.focus.bind(context);
    component.methods.clear.call(context);
    assert.deepEqual(events, [["update:modelValue", ""], ["clear"], ["focus"]]);

    const modal = parse(read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue")).descriptor;
    let productSearch;
    function walk(node) {
        if(node.tag === "SearchInput" && node.props.some(prop => prop.name === "id" && prop.value?.content === "inventoryAlertProduct")) productSearch = node;
        for(const child of node.children || []) walk(child);
    }
    walk(parseTemplate(modal.template.content));
    assert.ok(productSearch);
    const handlers = productSearch.props.filter(prop => prop.name === "on");
    assert.ok(!handlers.some(prop => ["clear", "input", "change"].includes(prop.arg?.content)));
    assert.equal(handlers.find(prop => prop.arg?.content === "update:model-value")?.exp.content, "$emit('update:productSearch', $event)");
    assert.equal(handlers.find(prop => prop.arg?.content === "search")?.exp.content, "$emit('search')");
});

test("el foco del buscador permanece en el grupo también antes de aparecer la X", () => {
    const css = postcss.parse(read("resources/css/System/br-branding/30-modals-fields-forms.css"));
    let focusRule;
    let childRule;
    css.walkRules(rule => {
        if(rule.selectors?.includes(".br-layout .br-form-control-group:focus-within")) focusRule = rule;
        if(rule.selectors?.includes(".br-layout .br-form-control-group > :is(.form-control, .form-select, .br-input-action)")) childRule = rule;
    });
    assert.equal(focusRule?.nodes.find(node => node.prop === "box-shadow")?.value, "var(--br-focus-ring)");
    const childShadow = childRule?.nodes.find(node => node.prop === "box-shadow");
    assert.equal(childShadow?.value, "none");
    assert.equal(childShadow?.important, true);
});
