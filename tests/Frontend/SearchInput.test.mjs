import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";

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
