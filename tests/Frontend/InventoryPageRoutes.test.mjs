import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");

test("inventory submodules have independent pages without internal tabs", () => {

    const routes = read("routes/System/Warehouses/InventoryPages.php");
    const legacyRoutes = read("routes/System/Warehouses/StockManagement.php");
    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const vite = read("vite.config.js");

    for(const [path, file, page] of [
        ["stock", "stock", "stock"],
        ["transfers", "transfers", "transfers"],
        ["guides", "guides", "guides"],
        ["kardex", "kardex", "kardex"],
        ["valued-kardex", "valued-kardex", "valued"]
    ]) {

        assert.match(routes, new RegExp(`Route::get\\("/${path}"`));
        assert.match(read(`resources/views/System/general/Warehouses/inventory/${file}.blade.php`), new RegExp(`inventory/${file}\\.js`));
        assert.match(read(`resources/js/System/Pages/Warehouses/inventory/${file}.js`), new RegExp(`mountInventoryPage\\("${page}"\\)`));
        assert.ok(vite.includes(`inventory/${file}.js`));

    }

    assert.doesNotMatch(legacyRoutes, /\/page\//);
    assert.doesNotMatch(component, /role="tablist"|changeView\(/);
    assert.match(component, /activeView: this\.page/);

});

test("transfers open the standard modal and save without leaving the list", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");

    assert.match(component, /data-bs-target="#inventoryTransferModal"[\s\S]*?Registrar traslado/);
    assert.match(component, /id="inventoryTransferModal"[\s\S]*?class="modal fade br-entity-modal br-modal-standard"/);
    assert.match(component, /id="inventoryTransferModalTitle"[\s\S]*?REGISTRAR TRASLADO/);
    assert.match(component, /ref="closeTransferModal"[\s\S]*?Cancelar/);
    assert.match(component, /@click="saveTransfer"[\s\S]*?Guardar/);
    assert.match(component, /this\.\$refs\.closeTransferModal\?\.click\(\)/);

});

test("stock places warehouse, search and actions in order", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const context = component.slice(
        component.indexOf('<div class="row align-items-end g-2 br-inventory__context">'),
        component.indexOf('<div class="br-inventory__workspace">')
    );

    assert.doesNotMatch(component, /br-inventory__page-heading|br-operational-scope/);
    assert.match(context, /inventoryWarehouse[\s\S]*?ref="productSearch"[\s\S]*?<span>Buscar<\/span>[\s\S]*?Registrar operación[\s\S]*?Registrar traslado[\s\S]*?class="br-btn br-btn-sm br-btn-actions"[\s\S]*?Acciones[\s\S]*?br-btn-actions-menu[\s\S]*?Descargar Excel/);
    assert.doesNotMatch(component, /br-inventory__toolbar/);
    assert.match(component, /Alerts\.confirmDownload/);

});

test("transfers show only the product-style table below the controls", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const transfers = component.slice(
        component.indexOf('<div v-else-if="activeView === \'transfers\'" class="br-inventory-transfer">'),
        component.indexOf('<div v-else-if="activeView === \'guides\'"')
    );

    assert.doesNotMatch(transfers, /Para registrar un traslado, configura al menos dos almacenes/);
    assert.match(transfers, /<section class="br-entity-list" aria-label="Traslados registrados">\s*<div class="table-responsive">/);
    assert.match(transfers, /<table class="table br-entity-table br-inventory-transfer__table mb-0">/);
    assert.match(transfers, /<thead class="br-table-header-surface">/);
    assert.match(transfers, /<WithoutData\s+class="br-entity-empty-state"\s+type="image"\s+image-src="\/System\/assets\/img\/utils\/without_data\/empty_products\.png\?v=2"/);

});

test("all inventory listings reuse the product loader, empty illustration and table surface", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const products = read("resources/js/System/Pages/Catalogs/products/main.vue");
    const withoutData = read("resources/js/System/Components/WithoutData.vue");
    const listing = component.slice(0, component.indexOf('id="inventoryMovementModal"'));
    const emptyStates = listing.match(/<WithoutData[\s\S]*?\/>/g) || [];

    assert.equal((listing.match(/class="br-entity-list"/g) || []).length, 4);
    assert.equal((listing.match(/class="br-table-header-surface"/g) || []).length, 4);
    assert.equal((listing.match(/class="py-4"><Loader\/>/g) || []).length, 4);
    assert.equal(emptyStates.length, 4);
    assert.equal((listing.match(/image-src="\/System\/assets\/img\/utils\/without_data\/empty_products\.png\?v=2"/g) || []).length, 4);
    assert.match(products, /<WithoutData\s+class="br-entity-empty-state"\s+type="image"\s+image-src="\/System\/assets\/img\/utils\/without_data\/empty_products\.png\?v=2"\/>/);
    assert.match(withoutData, /default: Constants\.generalConfig\.messages\.withoutResults/);
    assert.match(withoutData, /default: "Aún no hay información para mostrar\."/);
    assert.doesNotMatch(component, /hasSearched|emptyStateText|emptyStateDescription|Pulsa Buscar para consultar/);

    for(const state of emptyStates) {
        assert.match(state, /class="br-entity-empty-state"/);
        assert.doesNotMatch(state, /(?:^|\s):?(?:text|description)=/);
    }

});

test("inventory filters remain outside the white table surface", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const cardCss = read("resources/css/System/br-branding/70-visual-density-brand-refresh.css");
    const polishCss = read("resources/css/System/br-branding/99-system-wide-visual-polish.css");

    assert.match(component, /<section class="br-inventory__content">/);
    assert.doesNotMatch(component, /br-inventory__panel/);
    assert.doesNotMatch(cardCss + polishCss, /\.br-inventory__content|\.br-inventory__panel/);
    assert.match(inventoryCss, /\.br-inventory__context \{[\s\S]*?padding: 0\.15rem 0 0\.25rem;/);
    assert.doesNotMatch(component, /br-inventory__kardex-filters/);
    assert.match(component, /class="br-filter-bar__actions br-inventory__context-actions"/);
    assert.doesNotMatch(inventoryCss, /br-inventory__context-actions--report|justify-content: flex-end/);
    assert.match(inventoryCss, /\.br-inventory-transfer \{[\s\S]*?background: transparent;/);

});

test("stock and inventory reports keep filters and actions in a single desktop row", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const stockFilters = component.slice(
        component.indexOf('<div class="row align-items-end g-2 br-inventory__context">'),
        component.indexOf('<div class="br-inventory__workspace">')
    );
    assert.match(stockFilters, /ref="productSearch"[\s\S]*?xl="3"\s+lg="3"/);
    assert.match(stockFilters, /inventoryWarehouse[\s\S]*?movementType[\s\S]*?guideType[\s\S]*?inventoryDateFrom[\s\S]*?inventoryDateTo[\s\S]*?contextActionColumns[\s\S]*?<span>Buscar<\/span>[\s\S]*?<span>Acciones<\/span>/);
    assert.match(component, /isReportView\(\) \{[\s\S]*?"kardex", "valued", "guides"/);
    assert.match(component, /if\(this\.isReportView\) return "col-12 col-md-6 col-lg-3 col-xl-3";/);
    assert.equal((stockFilters.match(/class="col-12 col-md-6 col-lg-2 col-xl-2"/g) || []).length, 4);

});

test("changing inventory filters does not request records until Buscar is clicked", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const watcher = component.slice(component.indexOf('watch: {'));
    const mounted = component.slice(component.indexOf('async mounted() {'), component.indexOf('    data() {'));

    assert.match(component, /@click="activeView === 'stock' \? handleProductSearch\(\) : searchCurrentView\(\)"/);
    assert.equal((component.match(/<span>Buscar<\/span>/g) || []).length, 1);
    assert.doesNotMatch(mounted, /listCurrentView\(|listStock\(|listKardex\(|listGuides\(/);
    assert.match(watcher, /"filters\.warehouse"\(\) \{\s*this\.invalidateListing\(\);/);
    assert.doesNotMatch(watcher, /listCurrentView\(|listStock\(|listKardex\(|listGuides\(/);
    assert.match(component, /clearProductSearch\(\) \{[\s\S]*?this\.focusProductSearch\(\);/);
    assert.match(component, /if\(requestVersion !== this\.requestVersion\) return;/);

});

test("the shared work-warehouse selector shows branch and warehouse on every page", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const references = read("app/Services/System/Base/CompanyReferenceDataService.php");

    assert.match(component, /v-model="filters\.warehouse"\s+:options="warehouses"/);
    assert.match(component, /label: record\.branch\?\.name \? `\$\{record\.branch\.name\} \/ \$\{record\.name\}` : record\.name/);
    assert.match(references, /function stockWarehouses\(\): Collection \{[\s\S]*?->with\("branch"\)/);

});
