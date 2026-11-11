import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {forProduct, fromLevels, labels} from "../../resources/js/Shared/StockStatus.js";

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
    assert.match(state, /Object\.entries\(StockStatus\.labels\)/);
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
    assert.match(exportClass, /StockStatus::fromLevels\(\$stock, \$minimum\)/);
    assert.match(skeleton, /v-if="hasStockFilters" class="col-12 col-md-6 col-lg-2 col-xl-2"/);
});

test("los estados comparten etiquetas y priorizan el stock agotado", () => {
    assert.deepEqual(labels, {empty: "Agotado", low: "Stock bajo", healthy: "Stock saludable"});
    assert.equal(fromLevels(0, 0), "empty");
    assert.equal(fromLevels("-0.001", "5"), "empty");
    assert.equal(fromLevels("1.001", "1.001"), "low");
    assert.equal(fromLevels("1.002", "1.001"), "healthy");
    assert.equal(forProduct(0, false), "empty");
    assert.equal(forProduct(12, true), "low");
    assert.equal(forProduct(12, false), "healthy");
});
