import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("Estado del stock filtra el control de stock antes de paginar en ambos ámbitos", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const exportClass = read("app/Exports/System/Warehouses/InventoryReportExport.php");
    const skeleton = read("resources/js/System/Pages/Warehouses/inventory/InventorySkeleton.vue");

    assert.match(page, /<label for="stockSituation"[^>]*>Estado del stock<\/label>[\s\S]*?v-model="filters\.stockSituation"/);
    assert.match(state, /\{code: "", label: "Todos los estados"\}/);
    assert.match(state, /STOCK_SITUATIONS = \[[\s\S]*?"empty"[\s\S]*?"low"[\s\S]*?"healthy"/);
    assert.match(state, /product_search: productSearch,\s*situation/);
    assert.doesNotMatch(state, /"filters\.stockSituation"\(\)|refreshStockAdvancedFilters/);
    assert.match(state, /if\(this\.activeView === "stock"\) \{\s*filters\.situation = applied\.stockSituation\?\.code \|\| "";/);
    assert.match(controller, /getPaginatedList\([\s\S]*?input\("situation", ""\)/);
    assert.match(controller, /getPaginatedConsolidatedStock\([\s\S]*?input\("situation", ""\)/);
    assert.match(service, /applySituationFilter\(self::stockQuery\([\s\S]*?->paginate\(\$perPage\)/);
    assert.match(service, /applySituationFilter\([\s\S]*?self::consolidatedStockQuery\([\s\S]*?->paginate\(\$perPage\)/);
    assert.match(service, /"empty" => \$query->whereRaw\("\{\$stock\} <= 0"\)/);
    assert.match(service, /"low" => \$query->whereRaw\("\{\$stock\} > 0 AND \{\$stock\} <= \{\$minimum\}"\)/);
    assert.match(service, /"healthy" => \$query->whereRaw\("\{\$stock\} > 0 AND \{\$stock\} > \{\$minimum\}"\)/);
    assert.match(exportClass, /getConsolidatedStock\([\s\S]*?"situation"[\s\S]*?getStockReport\([\s\S]*?"situation"/);
    assert.match(exportClass, /"Estado del stock"/);
    assert.match(skeleton, /v-if="hasStockFilters" class="col-12 col-md-6 col-lg-2 col-xl-2"/);
});
