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
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const request = read("app/Http/Requests/System/Warehouses/StoreInventoryTransferRequest.php");
    const modal = component.slice(
        component.indexOf('id="inventoryTransferModal"'),
        component.indexOf('</Teleport>', component.indexOf('id="inventoryTransferModal"'))
    );

    assert.match(component, /data-bs-target="#inventoryTransferModal"[\s\S]*?Registrar traslado/);
    assert.match(modal, /class="modal fade br-entity-modal br-modal-standard"/);
    assert.match(modal, /id="inventoryTransferModalTitle"[\s\S]*?REGISTRAR TRASLADO/);
    assert.match(modal, /class="br-inventory-operation__config"[\s\S]*?transferSource[\s\S]*?transferDestination[\s\S]*?title="Motivo del traslado"/);
    assert.match(modal, /v-model\.trim="transferForm\.reason"[\s\S]*?maxlength="255"[\s\S]*?showCharCounter/);
    assert.match(request, /"reason" => \["required", "string", "max:255"\]/);
    assert.match(modal, /class="br-inventory-operation__items"[\s\S]*?Productos incluidos[\s\S]*?<table class="table br-entity-table br-inventory-operation__table mb-0">/);
    assert.match(modal, /<tr v-for="\(item, index\) in transferForm\.items"[\s\S]*?v-model="item\.product"[\s\S]*?<SelectNoOptions\/>[\s\S]*?v-model="item\.quantity"/);
    assert.match(modal, /class="br-inventory-operation__remove-help"[\s\S]*?data-bs-toggle="tooltip"[\s\S]*?class="br-icon-danger-plain"[\s\S]*?fa-trash/);
    assert.match(modal, /ref="closeTransferModal"[\s\S]*?Cancelar[\s\S]*?class="br-entity-modal__footer-actions"[\s\S]*?class="br-btn br-btn-add-row"[\s\S]*?Agregar producto[\s\S]*?class="br-btn br-btn-action-create"[\s\S]*?@click="saveTransfer"[\s\S]*?Guardar/);
    assert.doesNotMatch(modal, /br-inventory-transfer__item-row|br-btn-outline-secondary|<textarea/);
    assert.doesNotMatch(inventoryCss, /\.br-inventory-transfer__item-row/);
    assert.match(component, /addTransferItem\(\) \{[\s\S]*?this\.disposeInventoryModalTooltips\("inventoryTransferModal"\);[\s\S]*?this\.transferForm\.items\.push\([\s\S]*?this\.refreshInventoryModalTooltips\("inventoryTransferModal"\)/);
    assert.match(component, /removeTransferItem\(index\) \{[\s\S]*?this\.disposeInventoryModalTooltips\("inventoryTransferModal"\);[\s\S]*?this\.transferForm\.items\.splice\(index, 1\);[\s\S]*?this\.refreshInventoryModalTooltips\("inventoryTransferModal"\)/);
    assert.match(component, /this\.\$refs\.closeTransferModal\?\.click\(\)/);

});

test("stock operation modal uses the standard primary action and a compact product table", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const buttonsCss = read("resources/css/System/br-branding/40-buttons-quick-create.css");
    const modalCss = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const selectCss = read("resources/css/System/br-branding/10-tables-breadcrumb-filters.css");
    const mount = read("resources/js/System/Pages/Warehouses/inventory/mount.js");
    const request = read("app/Http/Requests/System/Warehouses/StoreInventoryOperationRequest.php");
    const modal = component.slice(component.indexOf('id="inventoryMovementModal"'));

    assert.match(modal, /inventoryOperation[\s\S]*?:selectable="option => !option\.isGroup"[\s\S]*?#option="option"[\s\S]*?br-inventory-operation__option-group/);
    assert.match(component, /\{code: "correction", label: "Actualización"[\s\S]*?\{code: "entry", label: "Ingresos"[\s\S]*?\{code: "exit", label: "Salidas"/);
    assert.match(component, /INVENTORY_OPERATION_GROUPS\.flatMap\(group => \[[\s\S]*?INVENTORY_OPERATIONS\.filter\(operation => operation\.movementType === group\.code\)/);
    assert.match(modal, /br-inventory-operation__description[\s\S]*?inventoryOperationEffect[\s\S]*?movementForm\.operation\?\.description[\s\S]*?title="Motivo"/);
    assert.match(inventoryCss, /\.br-inventory-operation__effect \{[\s\S]*?border-left: 2px solid var\(--br-primary\)/);
    assert.match(modal, /v-model\.trim="movementForm\.reason"[\s\S]*?maxlength="100"[\s\S]*?showCharCounter/);
    assert.match(request, /"reason" => \["required", "string", "max:100"\]/);
    assert.match(modal, /<span class="form-label colon-at-end">Productos incluidos<\/span>\s*<span class="text-danger fw-bold"[^>]*>\*<\/span>/);
    assert.match(modal, /class="br-inventory-operation__items-header"[\s\S]*?Productos incluidos[\s\S]*?class="table-responsive br-inventory-operation__table-wrap"/);
    assert.doesNotMatch(modal.slice(modal.indexOf('class="br-inventory-operation__items-header"'), modal.indexOf('class="table-responsive br-inventory-operation__table-wrap"')), /Agregar producto/);
    assert.match(buttonsCss, /\.br-btn\.br-btn-add-row \{[\s\S]*?background: var\(--br-success\);/);
    assert.doesNotMatch(buttonsCss.match(/\.br-btn\.br-btn-add-row \{[^}]*\}/)?.[0], /min-height|padding|font-size/);
    assert.match(modal, /class="modal-footer br-entity-modal__footer"[\s\S]*?Cancelar[\s\S]*?class="br-entity-modal__footer-actions"[\s\S]*?class="br-btn br-btn-add-row"[\s\S]*?Agregar producto[\s\S]*?class="br-btn br-btn-action-create"[\s\S]*?Guardar/);
    assert.match(modalCss, /\.modal-footer\.br-entity-modal__footer > \.br-entity-modal__footer-actions \{[\s\S]*?margin-left: auto;/);
    assert.match(inventoryCss, /\.br-inventory-operation__effect \{[\s\S]*?display: flex;[\s\S]*?align-items: center;[\s\S]*?min-height: var\(--br-control-height\);[\s\S]*?white-space: nowrap;/);
    assert.match(inventoryCss, /\.br-inventory-operation__items-header \{[\s\S]*?align-items: flex-end;/);
    assert.match(inventoryCss, /\.br-inventory-operation__table tbody td \{[\s\S]*?vertical-align: middle;/);
    assert.match(modal, /<table[\s\S]*?br-inventory-operation__table[\s\S]*?<thead class="br-table-header-surface">[\s\S]*?Producto[\s\S]*?Cantidad[\s\S]*?Costo unitario/);
    assert.match(modal, /Costo unitario\s*<small>Opcional<\/small>[\s\S]*?data-bs-toggle="tooltip"[\s\S]*?title="Si se omite, se conserva el costo promedio actual\."/);
    assert.match(modal, /<tr v-for="\(item, index\) in movementForm\.items"[\s\S]*?v-model="item\.product"[\s\S]*?v-model="item\.amount"/);
    assert.match(modal, /v-model="item\.product"[\s\S]*?<template #no-options>\s*<SelectNoOptions\/>/);
    assert.match(mount, /\.component\("SelectNoOptions", SelectNoOptions\)/);
    assert.match(selectCss, /\.br-layout \.vs__no-options,[\s\S]*?min-height: 2\.65rem;[\s\S]*?text-align: center;/);
    assert.match(modal, /class="br-inventory-operation__remove-help"[\s\S]*?title="Quitar producto"[\s\S]*?data-bs-toggle="tooltip"[\s\S]*?class="br-icon-danger-plain"[\s\S]*?fa-trash/);
    assert.match(component, /addMovementItem\(\) \{[\s\S]*?this\.disposeInventoryModalTooltips\("inventoryMovementModal"\);[\s\S]*?this\.movementForm\.items\.push\([\s\S]*?this\.refreshInventoryModalTooltips\("inventoryMovementModal"\)/);
    assert.match(component, /removeMovementItem\(index\) \{[\s\S]*?this\.disposeInventoryModalTooltips\("inventoryMovementModal"\);[\s\S]*?this\.movementForm\.items\.splice\(index, 1\);[\s\S]*?this\.refreshInventoryModalTooltips\("inventoryMovementModal"\)/);
    assert.match(component, /refreshInventoryModalTooltips\(modalId\) \{[\s\S]*?this\.\$nextTick\(\(\) => Alerts\.tooltips\(\{show: true, time: 0, selector:/);
    assert.match(component, /"movementForm\.operation"\(\) \{\s*this\.refreshInventoryModalTooltips\("inventoryMovementModal"\);/);
    assert.match(inventoryCss, /\.br-inventory-operation__remove-help > button:disabled \{\s*pointer-events: none;/);
    assert.match(modal, /class="br-btn br-btn-action-create"[\s\S]*?@click="saveMovement"/);
    assert.doesNotMatch(modal, /br-inventory-operation__item"|<textarea/);

});

test("stock places warehouse, search and actions in order", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const context = component.slice(
        component.indexOf('<div class="row align-items-end g-2 br-inventory__context">'),
        component.indexOf('<div class="br-inventory__workspace">')
    );

    assert.doesNotMatch(component, /br-inventory__page-heading|br-operational-scope/);
    assert.match(context, /inventoryWarehouse[\s\S]*?ref="productSearch"[\s\S]*?<span>Buscar<\/span>[\s\S]*?Registrar operación[\s\S]*?Registrar traslado[\s\S]*?class="br-btn br-btn-sm br-btn-actions"[\s\S]*?Acciones[\s\S]*?br-btn-actions-menu[\s\S]*?Descargar Excel/);
    assert.doesNotMatch(context, /inputGroupPrepend|br-inventory-search__prefix|fa-barcode/);
    assert.match(inventoryCss, /\.br-layout \.br-inventory__context > \.form-group \{\s*margin-bottom: 0 !important;/);
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
    assert.match(transfers, /<WithoutData\s+class="br-entity-empty-state"\s+type="image"\/>/);

});

test("transfers list all accessible warehouses without allowing a virtual transfer source", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const movementService = read("app/Services/System/Warehouses/Inventory/InventoryMovementService.php");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const transfers = component.slice(
        component.indexOf('<div v-else-if="activeView === \'transfers\'" class="br-inventory-transfer">'),
        component.indexOf('<div v-else-if="activeView === \'guides\'"')
    );
    const movements = controller.slice(
        controller.indexOf("public function movements(Request $request)"),
        controller.indexOf("public function alerts(Request $request)")
    );

    assert.match(component, /return \["stock", "transfers"\]\.includes\(this\.activeView\)\s*\? \[\{code: "all", label: "Todos los almacenes"\}, \.\.\.warehouses\]/);
    assert.match(component, /this\.filters\.warehouse = \["stock", "transfers"\]\.includes\(this\.activeView\)/);
    const registerTransferButton = component.slice(
        component.indexOf('v-if="activeView === \'transfers\'"'),
        component.indexOf('data-bs-target="#inventoryTransferModal"')
    );

    assert.doesNotMatch(registerTransferButton, /:disabled=/);
    assert.match(transfers, /id="transferSource"[\s\S]*?v-model="transferForm\.source"[\s\S]*?:options="realWarehouses"/);
    assert.match(transfers, /id="transferDestination"[\s\S]*?v-model="transferForm\.destination"[\s\S]*?:options="destinationWarehouses"/);
    assert.match(component, /prepareTransfer\(\) \{[\s\S]*?const source = this\.defaultFormWarehouse\(\);[\s\S]*?source,[\s\S]*?destination: null/);
    assert.match(component, /source_warehouse_id: this\.transferForm\.source\.code/);
    assert.match(component, /destinationWarehouses\(\) \{\s*if\(!this\.transferForm\.source\?\.code\) return \[\];\s*return this\.realWarehouses\.filter\(warehouse => Number\(warehouse\.code\) !== Number\(this\.transferForm\.source\?\.code\)\);/);
    assert.match(transfers, /<th>Almacén origen<\/th>\s*<th>Almacén destino<\/th>/);
    assert.match(transfers, /movement\.transfer_source_warehouse\?\.name[\s\S]*?movement\.transfer_destination_warehouse\?\.name/);
    assert.equal((transfers.match(/colspan="7"/g) || []).length, 2);
    assert.match(inventoryCss, /\.br-inventory-transfer__table \{\s*min-width: 64rem;/);
    assert.match(movementService, /\$warehouseIds = \$page->getCollection\(\)[\s\S]*?source_warehouse_id[\s\S]*?destination_warehouse_id/);
    assert.match(movementService, /\$warehouseQuery->whereIn\("id", \$filters\["allowed_warehouse_ids"\]\);/);
    assert.match(movementService, /"transfer_source_warehouse" => \$sourceId,[\s\S]*?"transfer_destination_warehouse" => \$destinationId/);
    assert.match(movements, /\$allWarehouses = \(\$filters\["warehouse_id"\] \?\? null\) === "all";/);
    assert.match(movements, /RolePermissionService::canAccessRoute\(\$this->getAuthUser\(\), "stocks_management\.transfers\.index"\)/);
    assert.match(movements, /\$filters\["origin_types"\] = \["transfer_out", "transfer_in"\];\s*unset\(\$filters\["warehouse_id"\]\);/);
    assert.match(movements, /\$filters\["allowed_warehouse_ids"\] = AccessScopeService::allowedIds/);

});

test("operation and transfer dialogs choose their own warehouses without changing the list filter", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const registerOperationButton = component.slice(
        component.indexOf('v-if="activeView === \'stock\'"', component.indexOf('class="br-btn br-btn-sm br-btn-search"')),
        component.indexOf('data-bs-target="#inventoryMovementModal"')
    );
    const operationModal = component.slice(component.indexOf('id="inventoryMovementModal"'));

    assert.doesNotMatch(registerOperationButton, /:disabled=/);
    assert.match(operationModal, /id="operationWarehouse"[\s\S]*?v-model="movementForm\.warehouse"[\s\S]*?:options="realWarehouses"/);
    assert.match(component, /warehouse: this\.defaultFormWarehouse\(\)/);
    assert.match(component, /warehouse_id: this\.movementForm\.warehouse\.code/);
    assert.match(component, /defaultFormWarehouse\(\) \{\s*return this\.realWarehouses\.find\([\s\S]*?\|\| null;/);
    assert.match(component, /:disabled="savingMovement \|\| !movementForm\.warehouse\?\.code"/);
    assert.match(component, /:disabled="savingTransfer \|\| !transferForm\.source\?\.code \|\| !transferForm\.destination\?\.code"/);
    assert.match(inventoryCss, /\.br-layout \.br-inventory-operation__reason\.form-group \{\s*grid-column: 1 \/ -1;/);
    assert.doesNotMatch(inventoryCss, /br-inventory-operation__config--movement/);

});

test("stock keeps the warehouses column for both consolidated and selected-warehouse views", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const stock = component.slice(
        component.indexOf('<section v-if="activeView === \'stock\'" class="br-entity-list"'),
        component.indexOf('<div v-else-if="[\'kardex\', \'valued\'].includes(activeView)"')
    );

    assert.match(stock, /<col class="br-inventory__col-warehouses">/);
    assert.match(stock, /<th>Almacenes<\/th>/);
    assert.match(stock, /v-for="warehouse in stockWarehouseBreakdown\(record\)\.slice\(0, 3\)"/);
    assert.equal((stock.match(/colspan="5"/g) || []).length, 2);
    assert.doesNotMatch(stock, /<th v-if="isConsolidatedStock">Almacenes<\/th>|<td v-if="isConsolidatedStock">/);
    assert.match(component, /stockWarehouseBreakdown\(record\) \{[\s\S]*?if\(this\.isConsolidatedStock\) return record\.warehouse_breakdown \|\| \[\];[\s\S]*?warehouse_name: warehouse\.warehouseName/);
    assert.match(inventoryCss, /\.br-inventory__col-warehouses \{ width: 28%; \}/);

});

test("all inventory listings reuse the product loader, empty illustration and table surface", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const products = read("resources/js/System/Pages/Catalogs/products/main.vue");
    const withoutData = read("resources/js/System/Components/WithoutData.vue");
    const listing = component
        .slice(0, component.indexOf('id="inventoryMovementModal"'))
        .replace(/<Teleport to="body">[\s\S]*?<\/Teleport>/, "");
    const emptyStates = listing.match(/<WithoutData[\s\S]*?\/>/g) || [];

    assert.equal((listing.match(/class="br-entity-list"/g) || []).length, 4);
    assert.equal((listing.match(/class="br-table-header-surface"/g) || []).length, 4);
    assert.equal((listing.match(/class="py-4"><Loader\/>/g) || []).length, 4);
    assert.equal(emptyStates.length, 4);
    assert.equal((listing.match(/image-src=/g) || []).length, 0);
    assert.match(products, /<WithoutData\s+class="br-entity-empty-state"\s+type="image"\/>/);
    assert.match(withoutData, /default: "\/System\/assets\/img\/utils\/without_data\/empty_records\.png"/);
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
    assert.match(stockFilters, /v-if="\['stock', 'transfers'\]\.includes\(activeView\)"[\s\S]*?ref="productSearch"[\s\S]*?xl="3"\s+lg="3"/);
    assert.match(stockFilters, /inventoryWarehouse[\s\S]*?movementType[\s\S]*?guideType[\s\S]*?inventoryDateFrom[\s\S]*?inventoryDateTo[\s\S]*?contextActionColumns[\s\S]*?<span>Buscar<\/span>[\s\S]*?<span>Acciones<\/span>/);
    assert.match(component, /isReportView\(\) \{[\s\S]*?"kardex", "valued", "guides"/);
    assert.match(component, /if\(this\.isReportView\) return "col-12 col-md-6 col-lg-3 col-xl-3";/);
    assert.match(component, /if\(\["stock", "transfers"\]\.includes\(this\.activeView\)\) return "col-12 col-lg-6 col-xl-6";/);
    assert.equal((stockFilters.match(/class="col-12 col-md-6 col-lg-2 col-xl-2"/g) || []).length, 4);

});

test("changing inventory filters does not request records until Buscar is clicked", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const movementService = read("app/Services/System/Warehouses/Inventory/InventoryMovementService.php");
    const watcher = component.slice(component.indexOf('watch: {'));
    const mounted = component.slice(component.indexOf('async mounted() {'), component.indexOf('    data() {'));

    assert.match(component, /@click="activeView === 'stock' \? handleProductSearch\(\) : searchCurrentView\(\)"/);
    assert.equal((component.match(/<span>Buscar<\/span>/g) || []).length, 1);
    assert.doesNotMatch(mounted, /listCurrentView\(|listStock\(|listKardex\(|listGuides\(/);
    assert.match(watcher, /"filters\.warehouse"\(\) \{\s*this\.invalidateListing\(\);/);
    assert.doesNotMatch(watcher, /listCurrentView\(|listStock\(|listKardex\(|listGuides\(/);
    assert.match(component, /clearProductSearch\(\) \{[\s\S]*?this\.focusProductSearch\(\);/);
    assert.match(component, /focusProductSearch\(\) \{[\s\S]*?this\.\$refs\.productSearch\?\.\$el\?\.querySelector\("input"\)\?\.focus\(\);/);
    assert.match(component, /product_search: this\.filters\.productSearch[\s\S]*?if\(this\.activeView === "transfers"\) \{\s*filters\.origin_types = \["transfer_out", "transfer_in"\];/);
    assert.match(movementService, /\$productSearch = trim\(\(string\) \(\$filters\["product_search"\] \?\? ""\)\);[\s\S]*?whereHas\("item"/);
    assert.match(component, /if\(requestVersion !== this\.requestVersion\) return;/);

});

test("the shared work-warehouse selector shows branch and warehouse on every page", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const warehouseSelect = read("resources/js/System/Components/Generics/WarehouseSelect.vue");
    const warehouseCss = read("resources/css/System/br-branding/12-warehouse-select.css");
    const references = read("app/Services/System/Base/CompanyReferenceDataService.php");

    assert.match(component, /v-model="filters\.warehouse"\s+:options="warehouses"/);
    assert.equal((component.match(/<WarehouseSelect\s/g) || []).length, 4);
    assert.match(component, /import WarehouseSelect from "@System\/Components\/Generics\/WarehouseSelect\.vue";/);
    assert.match(warehouseSelect, /<VueSelect[\s\S]*?:model-value="modelValue"[\s\S]*?:options="options"[\s\S]*?@update:modelValue="\$emit\('update:modelValue', \$event\)"/);
    assert.match(warehouseSelect, /br-warehouse-select[\s\S]*?#selected-option="warehouse"[\s\S]*?#option="warehouse"/);
    assert.match(warehouseSelect, /<strong v-if="warehouse\.branchName" class="br-warehouse-select__branch">[\s\S]*?warehouse\.branchName[\s\S]*?<\/strong>/);
    assert.match(warehouseSelect, /br-warehouse-select__separator" aria-hidden="true">-<\/span>[\s\S]*?br-warehouse-select__name/);
    assert.match(component, /branchName: record\.branch\?\.name \|\| ""[\s\S]*?warehouseName: record\.name[\s\S]*?displayLabel: record\.branch\?\.name \? `\$\{record\.branch\.name\} - \$\{record\.name\}` : record\.name/);
    assert.match(component, /label: record\.branch\?\.name \? `\$\{record\.branch\.name\} \/ \$\{record\.name\}` : record\.name/);
    assert.match(warehouseCss, /\.br-warehouse-select__branch \{[\s\S]*?font-weight: 700;/);
    assert.match(warehouseCss, /\.br-warehouse-select__name \{[\s\S]*?font-weight: 400;/);
    assert.match(references, /function stockWarehouses\(\): Collection \{[\s\S]*?->with\("branch"\)/);

});

test("operation form orders warehouse context, type and effect before the full-width reason", () => {

    const component = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const inventoryCss = read("resources/css/System/br-branding/82-inventory.css");
    const modal = component.slice(component.indexOf('id="inventoryMovementModal"'));

    assert.match(modal, /Almacén de la operación[\s\S]*?<WarehouseSelect[\s\S]*?<strong>El stock cambia solo en el almacén elegido\.<\/strong>[\s\S]*?Tipo de operación[\s\S]*?br-inventory-operation__description[\s\S]*?title="Motivo"/);
    assert.doesNotMatch(modal, /Alcance de la operación|Efecto en el almacén/);
    assert.match(inventoryCss, /\.br-layout \.br-inventory-operation__reason\.form-group \{\s*grid-column: 1 \/ -1;/);
    assert.match(inventoryCss, /\.br-inventory-operation__warehouse-info \.br-inventory-operation__effect/);
    assert.match(component, /code: "physical_count"[\s\S]*?description: "Usa el saldo contado\."/);

});
