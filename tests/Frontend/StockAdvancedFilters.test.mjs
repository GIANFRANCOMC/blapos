import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("los filtros adicionales de stock comparten consulta, paginación y exportación", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const panel = read("resources/js/System/Pages/Warehouses/inventory/StockAdvancedFilters.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const config = read("app/Services/System/Warehouses/StockManagement/StockManagementConfigService.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const report = read("app/Exports/System/Warehouses/InventoryReportExport.php");
    const styles = read("resources/css/System/br-branding/82-inventory.css");

    assert.match(page, /row align-items-end g-3 br-inventory__context[\s\S]*?<StockAdvancedFilters\b/);
    assert.match(panel, /Stock mínimo[\s\S]*?Último movimiento[\s\S]*?Categoría[\s\S]*?Marca/);
    assert.doesNotMatch(panel, /class="row\b|br-inventory__advanced-filters/);
    assert.match(panel, /v-if="minimumOptions" class="col-12 col-sm-6 col-lg-2"/);
    assert.match(panel, /v-if="inactivityOptions" class="col-12 col-sm-6 col-lg-2"/);
    assert.equal(panel.match(/class="col-12 col-sm-6 col-lg-3"/g)?.length, 2);
    assert.match(styles, /\.br-inventory__context-actions \{[\s\S]*?margin-top: var\(--br-grid-gap-2\);/);
    assert.doesNotMatch(page, /Ordenar por|stockSort/);
    assert.match(page, /<StockAdvancedFilters\s+v-if="activeView === 'stock'"/);
    assert.doesNotMatch(page, /<StockAdvancedFilters[^>]*\bv-show=|Más filtros|stockAdvancedOpen/);
    assert.ok(page.indexOf('for="stockSituation"') < page.indexOf('<StockAdvancedFilters'));
    assert.ok(page.indexOf('<StockAdvancedFilters') < page.indexOf('<SearchButton'));
    assert.match(page, /<div class="col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2">\s*<div class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2">\s*<SearchButton\b[^>]*@click="searchCurrentView"[^>]*\/>\s*<ClearFiltersButton\b[^>]*\/>\s*<button[^>]*data-bs-toggle="dropdown"[^>]*>\s*<span>Acciones<\/span>[\s\S]*?<ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">[\s\S]*?<\/ul>\s*<\/div>\s*<div[^>]*class="[^"]*\bms-auto">\s*<span[^>]*class="vr align-self-center" aria-hidden="true">/);
    assert.match(styles, /\.br-inventory__context \.vr \{\s*color: var\(--br-border-muted\);/);
    assert.match(config, /"stock_categories"[\s\S]*?Category::query\(\)->active\(\)/);
    assert.match(config, /"stock_brands"[\s\S]*?Brand::query\(\)->active\(\)/);
    for(const field of ["category_id", "brand_id", "minimum_status", "inactive_days"]) {
        assert.match(state, new RegExp(`${field}(?::| =) selected\\.`));
        assert.match(controller, new RegExp(`"${field}" =>`));
    }
    assert.match(state, /\{code: "configured", label: "Con mínimo configurado"\}/);
    assert.match(state, /\{code: "unconfigured", label: "Sin mínimo configurado"\}/);
    assert.match(panel, /id="stockMinimumStatus" v-model="minimumStatus"/);
    assert.match(page, /v-model:minimum-status="filters\.stockMinimumStatus"/);
    assert.match(controller, /"minimum_status" => \["nullable", "in:configured,unconfigured"\]/);
    assert.match(state, /product_search: productSearch,\s*situation,\s*\.\.\.this\.currentStockAdvancedFilters\(this\.appliedFilters\)/);
    assert.match(controller, /getPaginatedList\([\s\S]*?\$this->stockFilters\(\$request\)/);
    assert.match(controller, /getPaginatedConsolidatedStock\([\s\S]*?\$this->stockFilters\(\$request\)/);
    const exportMethod = controller.slice(controller.indexOf("public function export("));
    assert.match(exportMethod, /if\(\$view === "stock"\)[\s\S]*?\$this->stockFilters\(\$request\)/);
    assert.match(exportMethod, /in_array\(\$view, self::KARDEX_VIEWS, true\)[\s\S]*?\$this->kardexFilters\(\$request\)/);
    assert.match(report, /getConsolidatedStock\([\s\S]*?\$this->filters[\s\S]*?getStockReport\([\s\S]*?\$this->filters/);
    assert.match(service, /whereHas\("categoryItems"/);
    assert.match(service, /whereDoesntHave\("inventoryMovements"/);
    assert.match(service, /\$filters\["minimum_status"\][\s\S]*?=== "configured"[\s\S]*?\$query->whereRaw\("\{\$minimum\} > 0"\)/);
    assert.match(service, /\$filters\["minimum_status"\][\s\S]*?=== "unconfigured"[\s\S]*?\$query->whereRaw\("\{\$minimum\} <= 0"\)/);
    assert.doesNotMatch(service, /stock_asc|stock_desc|"urgency"/);
    assert.match(service, /\$allowedWarehouseIds[\s\S]*?whereIn\("warehouse_id", \$allowedWarehouseIds\)/);
});

test("ambos Kardex muestran los mismos filtros de producto y conservan los filtros de movimiento", () => {
    const panel = read("resources/js/System/Pages/Warehouses/inventory/StockAdvancedFilters.vue");
    assert.match(panel, /<slot name="before-filters"\/>[\s\S]*?<div class="col-12 col-sm-6 col-lg-3">\s*<label for="stockCategory"[\s\S]*?for="stockBrand"[\s\S]*?<slot\/>/);

    for(const view of ["kardex", "valued-kardex"]) {
        const page = read(`resources/js/System/Pages/Warehouses/inventory/${view}.vue`);
        assert.match(page, /col-12 col-md-6 col-lg-3 col-xl-3 br-inventory__warehouse">\s*<label for="inventoryWarehouse"/);
        assert.match(page, /v-model\.trim="filters\.productSearch"[\s\S]*?placeholder="Buscar o escanear producto"[\s\S]*?xl="3"\s*lg="3"/);
        assert.match(page, /@click="searchCurrentView"/);
        assert.doesNotMatch(page, /<SearchInput[^>]*@search=/);
        assert.doesNotMatch(page, /stockSituation|Estado del stock/);
        assert.match(page, /col-lg-2 col-xl-2">\s*<label for="movementType"[^>]*>Movimiento<\/label>/);
        assert.match(page, /col-lg-2 col-xl-2">\s*<label for="inventoryDateFrom"[\s\S]*?col-lg-2 col-xl-2">\s*<label for="inventoryDateTo"[\s\S]*?<StockAdvancedFilters/);
        assert.match(page, /<template #before-filters>\s*<div class="col-12 col-md-6 col-lg-3 col-xl-3">\s*<label for="movementUser"[^>]*>Realizado por<\/label>[\s\S]*?v-model="filters\.movementUser"/);
        assert.match(page, /<StockAdvancedFilters\s+v-model:category="filters\.stockCategory"\s+v-model:brand="filters\.stockBrand"[\s\S]*?<SearchButton\b[^>]*@click="searchCurrentView"[^>]*\/>[\s\S]*?<\/StockAdvancedFilters>/);
        assert.match(page, /<div class="col-12 col-lg-3">\s*<div class="br-filter-bar__actions br-inventory__context-actions gap-lg-1">/);
        assert.doesNotMatch(page, /stockMinimumStatus|stockInactiveDays/);
        assert.doesNotMatch(page, /Más filtros|stockAdvancedOpen/);
    }
});
