import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {inventoryFilters} from "../../resources/js/System/Pages/Warehouses/inventory/inventoryPresentation.js";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("los filtros adicionales de stock comparten consulta, paginación y exportación", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const config = read("app/Services/System/Warehouses/StockManagement/StockManagementConfigService.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const report = read("app/Exports/System/Warehouses/InventoryReportExport.php");
    const filters = inventoryFilters.stock;
    assert.deepEqual(filters.map(field => field.label), ["Almacén de trabajo", "Producto", "Estado del stock", "Stock mínimo", "Último movimiento", "Categoría", "Marca"]);
    assert.ok(filters.slice(2, 5).every(field => field.compact));
    assert.doesNotMatch(page, /Ordenar por|Más filtros|stockAdvancedOpen/);
    assert.match(config, /"stock_categories"[\s\S]*?Category::query\(\)->active\(\)/);
    assert.match(config, /"stock_brands"[\s\S]*?Brand::query\(\)->active\(\)/);
    for(const field of ["category_id", "brand_id", "minimum_status", "inactive_days"]) {
        assert.match(state, new RegExp(`${field}(?::| =) selected\\.`));
        assert.match(controller, new RegExp(`"${field}" =>`));
    }
    assert.match(state, /\{code: "configured", label: "Con mínimo configurado"\}/);
    assert.match(state, /\{code: "unconfigured", label: "Sin mínimo configurado"\}/);
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

test("ambos Kardex conservan los mismos filtros de producto y movimiento", () => {
    assert.deepEqual(inventoryFilters.kardex, inventoryFilters.valued);
    assert.deepEqual(inventoryFilters.kardex.map(field => field.label), ["Almacén de trabajo", "Producto", "Movimiento", "Desde", "Hasta", "Realizado por", "Categoría", "Marca"]);
    assert.ok(inventoryFilters.kardex.slice(2,5).every(field => field.compact));
});
