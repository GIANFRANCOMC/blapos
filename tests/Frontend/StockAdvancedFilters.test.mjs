import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("los filtros adicionales de stock comparten consulta, paginación y exportación", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const config = read("app/Services/System/Warehouses/StockManagement/StockManagementConfigService.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const report = read("app/Exports/System/Warehouses/InventoryReportExport.php");
    const styles = read("resources/css/System/br-branding/82-inventory.css");
    const skeleton = read("resources/js/System/Pages/Warehouses/inventory/InventorySkeleton.vue");
    const skeletonStyles = read("resources/css/System/Pages/Warehouses/inventory/skeleton.css");

    assert.match(page, /row align-items-end g-3 br-inventory__context[\s\S]*?stockAdvancedFilters[\s\S]*?<div class="row g-3">[\s\S]*?Categoría[\s\S]*?Marca[\s\S]*?Stock mínimo[\s\S]*?Último movimiento/);
    assert.match(styles, /\.br-inventory__context-actions \{[\s\S]*?margin-top: var\(--br-grid-gap-2\);/);
    assert.doesNotMatch(page, /Ordenar por|stockSort/);
    assert.match(page, /:aria-expanded="stockAdvancedOpen"/);
    assert.ok(page.indexOf('for="stockSituation"') < page.indexOf('id="stockAdvancedFilters"'));
    assert.ok(page.indexOf('id="stockAdvancedFilters"') < page.indexOf('class="br-btn br-btn-sm br-btn-search"'));
    assert.match(page, /class="br-btn br-btn-sm br-btn-search"[\s\S]*?<span>Buscar<\/span>[\s\S]*?<\/button>\s*<button[\s\S]*?class="br-btn br-btn-sm br-btn-outline-secondary br-inventory__advanced-toggle"[\s\S]*?<span>Más filtros<\/span>/);
    assert.doesNotMatch(styles, /\.br-inventory__advanced-filters \{[^}]*\b(?:border|background|padding):/);
    assert.match(styles, /\.br-inventory__context-actions--stock \{\s*justify-content: flex-start;/);
    assert.match(styles, /\.br-inventory__operation-button \{\s*margin-left: auto;/);
    assert.match(skeleton, /br-inventory-skeleton__actions--stock/);
    assert.match(skeletonStyles, /\.br-inventory-skeleton__actions--stock \{ justify-content: flex-start; \}/);
    assert.match(skeletonStyles, /\.br-inventory-skeleton__button--stock-create \{ margin-left: auto; \}/);
    assert.match(config, /"stock_categories"[\s\S]*?Category::query\(\)->active\(\)/);
    assert.match(config, /"stock_brands"[\s\S]*?Brand::query\(\)->active\(\)/);
    for(const field of ["category_id", "brand_id", "without_minimum", "inactive_days"]) {
        assert.match(state, new RegExp(`${field}: this\\.filters\\.`));
        assert.match(controller, new RegExp(`"${field}" =>`));
    }
    assert.match(state, /product_search: productSearch,\s*situation,\s*\.\.\.this\.currentStockAdvancedFilters\(\)/);
    assert.match(state, /if\(this\.activeView === "stock"\)[\s\S]*?Object\.assign\(filters, this\.currentStockAdvancedFilters\(\)\)/);
    assert.match(controller, /getPaginatedList\([\s\S]*?\$this->stockFilters\(\$request\)/);
    assert.match(controller, /getPaginatedConsolidatedStock\([\s\S]*?\$this->stockFilters\(\$request\)/);
    assert.match(controller.slice(controller.indexOf("public function export(")), /if\(\$view === "stock"\) \{\s*\$filters = array_merge\(\$filters, \$this->stockFilters\(\$request\)\);/);
    assert.doesNotMatch(controller.slice(controller.indexOf("public function movements("), controller.indexOf("public function alerts(")), /\$this->stockFilters|\$view === "stock"/);
    assert.match(report, /getConsolidatedStock\([\s\S]*?\$this->filters[\s\S]*?getStockReport\([\s\S]*?\$this->filters/);
    assert.match(service, /whereHas\("categoryItems"/);
    assert.match(service, /whereDoesntHave\("inventoryMovements"/);
    assert.match(service, /without_minimum[\s\S]*?\$query->whereRaw\("\{\$minimum\} <= 0"\)/);
    assert.doesNotMatch(service, /stock_asc|stock_desc|"urgency"/);
    assert.match(service, /\$allowedWarehouseIds[\s\S]*?whereIn\("warehouse_id", \$allowedWarehouseIds\)/);
});
