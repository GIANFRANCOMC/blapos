import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("alertas muestra contexto, códigos y cantidades con paginación fuera del scroll", () => {
    const base = "resources/js/System/Pages/Warehouses/inventory/";
    const modal=read(base + "StockAlertsModal.vue");
    const page=read(base + "stock.vue");
    const state=read(base + "inventoryState.js");
    const table=read(base + "InventoryTable.vue");
    const css=read("resources/css/Shared/responsive-tables.css");
    assert.deepEqual(parse(modal).errors, []);
    assert.match(modal, /Almacén de trabajo<\/span>[\s\S]*?class="fg-inventory-quantity"[\s\S]*?warehouse\?\.displayLabel/);
    assert.match(modal, /<SearchInput id="inventoryAlertProduct" title="Producto"[\s\S]*?:placeholder="productSearchPlaceholder"/);
    assert.match(modal, /<FgField label="Stock mínimo" input-id="inventoryAlertMinimumStatus">[\s\S]*?:model-value="minimumStatus"/);
    assert.match(page, /v-model:minimum-status="alertMinimumStatus"[\s\S]*?:minimum-options="stockMinimumOptions"/);
    assert.doesNotMatch(modal, /ClearFiltersButton|fa-bell|<template #footer>/);
    assert.match(modal, /<FgFilterActions :show-clear="false"[^>]*@search="\$emit\('search'\)"/);
    assert.match(modal, /<ActionsDropdown type="button" label="Acciones de alertas de stock"/);
    assert.match(modal, /<ProductIdentity variant="expandable" :product="alert\.warehouse_item\?\.item"/);
    assert.match(modal, /<QuantityValue class="fg-inventory-quantity" :value="alert\.quantity" :unit="alert\.warehouse_item\?\.item\?\.measurement_unit"/);
    assert.match(modal, /<QuantityValue :value="alert\.minimum_stock"/);
    assert.match(modal, /<\/InventoryTable>\s*<Paginator[^>]*:pagination="alerts"[^>]*:loading="loading"/);
    assert.match(table, /:class="\{'fg-table-scroll--records': scrollRecords\}"/);
    assert.match(css, /\.fg-table-scroll--records thead th \{ position: sticky;/);
    assert.equal((modal.match(/:disabled="loading \|\| downloading \|\| !alerts\.total"/g) || []).length, 3);
    assert.match(modal, /@click="\$emit\('download', 'pdf'\)"[\s\S]*?@click="\$emit\('download', 'excel'\)"/);
    assert.match(state, /this\.stockAlerts = Requests\.emptyPagination\(\);/);
    assert.match(state, /searchStockAlerts\(value = this\.alertProductSearch\)[\s\S]*?listStockAlerts\(\{\}\)/);
    assert.match(state, /async listStockAlerts[\s\S]*?Alerts\.loading\(\{message: "Consultando alertas de stock"\}\)[\s\S]*?Requests\.get[\s\S]*?Alerts\.close\(\)/);
});

test("las etiquetas compartidas añaden dos puntos sin duplicarlos", () => {
    const styles = read("resources/css/Shared/controls.css");
    const guide = read("docs/frontend/desarrollo/GUIA_SYSTEM.md");

    assert.match(styles, /\.colon-at-end::after \{\s*content: ":";/);
    assert.match(guide, /Toda etiqueta visible de filtro, campo o dato informativo debe terminar en dos puntos/);
});

test("PDF y Excel usan una consulta plana, permisos y descargas con Swal sin navegar", () => {
    const route = read("routes/System/Warehouses/StockManagement.php");
    const permissions = read("config/permissions.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const pdf = read("app/Services/System/Warehouses/StockManagement/StockAlertsPdfRenderer.php");
    const excel = read("app/Exports/System/Warehouses/StockAlertsExport.php");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");

    assert.match(route, /Route::get\("\/alerts\/export"[\s\S]*?->name\("\$entity\.alerts\.export"\)/);
    assert.doesNotMatch(route, /Route::get\("\/alerts\/excel"/);
    assert.match(permissions, /"stocks_management\.alerts\.export" => \["stocks_management\.stock\.index"\]/);
    assert.match(controller, /function exportAlerts\([\s\S]*?"export" => \["required", "in:excel,pdf"\][\s\S]*?new StockAlertsExport[\s\S]*?stockAlertsReportQuery[\s\S]*?StockAlertsPdfRenderer/);
    assert.match(controller, /function authorizedAlertWarehouse\([\s\S]*?AccessScopeService::canAccess/);
    assert.match(service, /function getStockAlerts\([\s\S]*?stockAlertsQuery\([\s\S]*?\)->paginate\(\$perPage\)/);
    assert.match(service, /function stockAlertsQuery\([\s\S]*?join\("warehouse_items as alert_warehouse_items"[\s\S]*?warehouseItem\.item\.measurementUnit:id,abbreviation/);
    assert.match(service, /function stockAlertsReportQuery\([\s\S]*?DB::table\("items as products"\)[\s\S]*?leftJoinSub\(\$stock[\s\S]*?COALESCE\(stock\.quantity, 0\) <= 0[\s\S]*?products\.barcode[\s\S]*?units\.abbreviation as unit_abbreviation/);
    assert.match(excel, /implements FromQuery[\s\S]*?stockAlertsReportQuery[\s\S]*?function map\(/);
    assert.match(pdf, /new CPDF\("a4", "landscape"\)[\s\S]*?foreach\(\$alerts as \$alert\)[\s\S]*?drawRow[\s\S]*?page_text/);
    assert.match(state, /async downloadStockAlerts\(format\)[\s\S]*?Alerts\.loading[\s\S]*?Requests\.download[\s\S]*?Alerts\.close\(\)/);
    assert.match(state, /type: "alertsExport"[\s\S]*?data: \{\s*warehouse_id: warehouseId,\s*export: format,\s*product_search: this\.appliedAlertProductSearch,\s*minimum_status: this\.appliedAlertMinimumStatus\s*\}/);
    assert.match(page, /@download="downloadStockAlerts"/);
    assert.match(page, /v-model:product-search="alertProductSearch"[\s\S]*?@search="searchStockAlerts"/);
});
