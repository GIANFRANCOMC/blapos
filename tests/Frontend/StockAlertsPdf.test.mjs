import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("alertas muestra contexto, códigos y abreviaturas sin desplazar la paginación", () => {
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const styles = read("resources/css/System/br-branding/82-inventory.css");

    assert.equal(parse(modal).errors.length, 0);
    assert.match(modal, /warehouse\?\.branchName[\s\S]*?warehouse\?\.warehouseName/);
    assert.match(modal, /Cód\. interno[\s\S]*?internal_code[\s\S]*?Cód\. barras[\s\S]*?barcode/);
    assert.match(modal, /quantityWithUnit\(value, alert\.warehouse_item\?\.item\?\.measurement_unit\)/);
    assert.match(modal, /<\/section>[\s\S]*?<nav v-if="alerts\.total > 0 && alerts\.links"/);
    assert.match(modal, /Descargar PDF/);
    assert.match(styles, /\.br-inventory-alerts__table-scroll \{[\s\S]*?overflow: auto;/);
    assert.match(styles, /\.br-inventory-alerts__table thead th \{[\s\S]*?position: sticky;/);
    assert.match(state, /if\(!url\) this\.stockAlerts = \{total: 0, data: \[\]\};/);
});

test("el PDF usa una consulta propia y el permiso de exportar del control de stock", () => {
    const route = read("routes/System/Warehouses/StockManagement.php");
    const permissions = read("config/permissions.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const pdf = read("resources/views/System/pdf/inventory/stock_alerts.blade.php");

    assert.match(route, /Route::get\("\/alerts\/export"[\s\S]*?->name\("\$entity\.alerts\.export"\)/);
    assert.match(permissions, /"stocks_management\.alerts\.export" => \["stocks_management\.stock\.index"\]/);
    assert.match(controller, /function exportAlertsPdf\([\s\S]*?AccessScopeService::canAccess[\s\S]*?stockAlertsQuery\(\$warehouseId\)->get\(\)[\s\S]*?Pdf::loadView/);
    assert.match(service, /function getStockAlerts\([\s\S]*?stockAlertsQuery\([\s\S]*?\)->paginate\(\$perPage\)/);
    assert.match(service, /function stockAlertsQuery\([\s\S]*?join\("warehouse_items as alert_warehouse_items"[\s\S]*?warehouseItem\.item\.measurementUnit:id,abbreviation/);
    assert.match(pdf, /Stock actual[\s\S]*?@forelse\(\$alerts as \$alert\)[\s\S]*?Cód\. interno[\s\S]*?Cód\. barras/);
});
