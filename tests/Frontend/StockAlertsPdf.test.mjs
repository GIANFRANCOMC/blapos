import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("alertas muestra contexto, códigos y abreviaturas sin desplazar la paginación", () => {
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const styles = read("resources/css/System/br-branding/82-inventory.css");

    assert.equal(parse(modal).errors.length, 0);
    assert.match(modal, /<span class="form-label">Almacén de trabajo<\/span>[\s\S]*?warehouse\?\.displayLabel \|\| warehouse\?\.label/);
    assert.match(modal, /<header class="br-inventory-alerts__summary">[\s\S]*?br-inventory-alerts__location[\s\S]*?<\/header>[\s\S]*?<div class="br-inventory-alerts__toolbar">/);
    assert.doesNotMatch(modal, /fa-warehouse/);
    assert.match(modal, /<div class="br-inventory-alerts__toolbar">[\s\S]*?<form class="br-inventory-alerts__filters"[\s\S]*?br-inventory-alerts__product-filter[\s\S]*?type="submit"[\s\S]*?v-if="!loading && alerts\.total > 0"[\s\S]*?fa-bell fa-shake[\s\S]*?producto\{\{ alerts\.total === 1/);
    assert.match(modal, /<label for="inventoryAlertProduct"[^>]*>Producto<\/label>[\s\S]*?<SearchInput[\s\S]*?@search="\$emit\('search'\)"/);
    assert.match(modal, /placeholder="Buscar o escanear producto"/);
    assert.doesNotMatch(modal, /br-inventory-alerts__location-icon|br-inventory-alerts__eyebrow|br-btn br-btn-sm br-btn-danger/);
    assert.match(modal, /Cód\. interno[\s\S]*?internal_code[\s\S]*?Cód\. barras[\s\S]*?barcode/);
    assert.match(modal, /quantityWithUnit\(value, alert\.warehouse_item\?\.item\?\.measurement_unit\)/);
    assert.match(modal, /class="br-inventory__stock-current">\{\{ formatQuantity\(alert\.quantity, alert\) \}\}/);
    assert.match(page, /class="br-inventory__stock-current">\{\{ quantityWithUnit\(record\.stock_quantity, record\) \}\}/);
    assert.doesNotMatch(modal, /class="br-inventory__stock-current">\{\{ formatQuantity\(alert\.minimum_stock/);
    assert.match(styles, /body\.br-layout \.br-inventory__stock-current \{[\s\S]*?font-size: 0\.8rem !important;[\s\S]*?font-weight: 600;/);
    assert.match(modal, /br-inventory-alerts__table-head[\s\S]*?<\/table>[\s\S]*?br-inventory-alerts__table-scroll[\s\S]*?<WithoutData/);
    assert.match(modal, /<tr v-if="loading && !alerts\.data\?\.length">[\s\S]*?<WithoutData[\s\S]*?text="Preparando resultados"/);
    assert.doesNotMatch(modal, /<Loader\s*\/>/);
    assert.match(modal, /<thead class="visually-hidden">[\s\S]*?scope="col"/);
    assert.match(modal, /<\/section>[\s\S]*?<nav v-if="alerts\.total > 0 && alerts\.links"/);
    assert.match(modal, /type="submit"[\s\S]*?class="dropdown br-inventory-alerts__actions"[\s\S]*?class="br-btn br-btn-actions"[\s\S]*?Descargar PDF[\s\S]*?Descargar Excel/);
    assert.equal((modal.match(/:disabled="loading \|\| downloading \|\| !alerts\.total"/g) || []).length, 3);
    assert.doesNotMatch(modal, /<template #footer>/);
    assert.match(modal, /@click="\$emit\('download', 'pdf'\)"[\s\S]*?@click="\$emit\('download', 'excel'\)"/);
    assert.match(styles, /body\.br-layout \.br-inventory-alerts \.br-inventory-alerts__warehouse-name \{[\s\S]*?font-size: 0\.95rem !important;[\s\S]*?font-weight: 700;/);
    assert.match(styles, /\.br-inventory-alerts__table-head \{[\s\S]*?overflow-y: hidden;[\s\S]*?scrollbar-gutter: stable;[\s\S]*?background: var\(--br-table-thead-bg\);/);
    assert.match(styles, /\.br-inventory-alerts__table-scroll \{[\s\S]*?overflow-y: auto;[\s\S]*?scrollbar-gutter: stable;/);
    assert.match(styles, /\.br-inventory-alerts__toolbar \{[\s\S]*?display: flex;[\s\S]*?justify-content: space-between;/);
    assert.match(styles, /\.br-inventory-alerts__product-filter \{[\s\S]*?width: min\(100%, 24rem\);/);
    assert.match(styles, /\.br-inventory-alerts__table-head \.table > thead > tr > th \{[\s\S]*?background: var\(--br-table-thead-bg\) !important;/);
    assert.match(styles, /\.br-inventory-alerts__count \.fa-bell \{[\s\S]*?color: var\(--br-warning\);[\s\S]*?--fa-animation-duration: 3s;/);
    assert.doesNotMatch(styles.slice(styles.indexOf('.br-inventory-alerts__summary {'), styles.indexOf('.br-inventory-alerts__location {')), /border:|background:/);
    assert.match(state, /if\(!url\) this\.stockAlerts = \{total: 0, data: \[\]\};/);
    assert.match(state, /searchStockAlerts\(value = this\.alertProductSearch\)[\s\S]*?appliedAlertProductSearch[\s\S]*?listStockAlerts\(\{\}\)/);
    assert.match(state, /async listStockAlerts[\s\S]*?Alerts\.loading\(\{message: "Consultando alertas de stock"\}\)[\s\S]*?Requests\.get[\s\S]*?Alerts\.close\(\)/);
    assert.match(state, /async downloadStockAlerts\(format\)[\s\S]*?!this\.stockAlerts\.total[\s\S]*?product_search: this\.appliedAlertProductSearch/);
});

test("las etiquetas compartidas añaden dos puntos sin duplicarlos", () => {
    const styles = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const guide = read("docs/System/DEVELOPMENT_GUIDE.md");

    assert.match(styles, /:is\(\.form-label:not\(\.br-sale-detail-toolbar__counter\), \.colon-at-end\)::after \{\s*content: ":";/);
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
    assert.match(service, /function stockAlertsReportQuery\([\s\S]*?DB::table\("inventory_stock_alerts as alerts"\)[\s\S]*?products\.barcode[\s\S]*?units\.abbreviation as unit_abbreviation/);
    assert.match(excel, /implements FromQuery[\s\S]*?stockAlertsReportQuery[\s\S]*?function map\(/);
    assert.match(pdf, /new CPDF\("a4", "landscape"\)[\s\S]*?foreach\(\$alerts as \$alert\)[\s\S]*?drawRow[\s\S]*?page_text/);
    assert.match(state, /async downloadStockAlerts\(format\)[\s\S]*?Alerts\.loading[\s\S]*?Requests\.download[\s\S]*?Alerts\.close\(\)/);
    assert.match(state, /type: "alertsExport"[\s\S]*?data: \{warehouse_id: warehouseId, export: format, product_search: this\.appliedAlertProductSearch\}/);
    assert.match(page, /@download="downloadStockAlerts"/);
    assert.match(page, /v-model:product-search="alertProductSearch"[\s\S]*?@search="searchStockAlerts"/);
});
