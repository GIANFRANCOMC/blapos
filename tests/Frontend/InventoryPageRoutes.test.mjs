import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");
const inventoryPath = "resources/js/System/Pages/Warehouses/inventory/";
const readInventory = () => ["stock", "transfers", "guides", "kardex", "valued-kardex"]
    .map(name => read(`${inventoryPath}${name}.vue`))
    .concat(read(`${inventoryPath}inventoryState.js`))
    .join("\n");

import {inventoryFilters, inventoryViews, inventoryBadgeTones} from "../../resources/js/System/Pages/Warehouses/inventory/inventoryPresentation.js";

test("inventory applies work warehouse changes only on search", () => {

    const component = readInventory();

    assert.match(component, /this\.filters\.warehouse = this\.realWarehouses\[0\];\s*this\.appliedFilters = \{\.\.\.this\.filters\};\s*await this\.listCurrentView\(\{\}\);/);
    assert.match(component, /searchCurrentView\(\) \{[\s\S]*?this\.invalidateListing\(\);\s*this\.appliedFilters = \{\.\.\.this\.filters\};/);
    assert.doesNotMatch(component, /"filters\.warehouse"\(/);
    assert.match(component, /listCurrentView\(\{url = null\} = \{\}\) \{[\s\S]*?this\.listStock\(\{url\}\)[\s\S]*?this\.listKardex\(\{url\}\)[\s\S]*?this\.listGuides\(\{url\}\)/);

});

test("inventory submodules have independent pages without internal tabs", () => {

    const routes = read("routes/System/Warehouses/InventoryPages.php");
    const legacyRoutes = read("routes/System/Warehouses/StockManagement.php");
    const component = readInventory();
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
        const entry = read(`resources/js/System/Pages/Warehouses/inventory/${file}.js`);
        assert.match(entry, new RegExp(`import App from "\\./${file}\\.vue"`));
        assert.match(entry, new RegExp(`mountInventoryPage\\(App, "${page}"\\)`));
        assert.ok(vite.includes(`inventory/${file}.js`));

    }

    assert.doesNotMatch(legacyRoutes, /\/page\//);
    assert.doesNotMatch(component, /role="tablist"|changeView\(/);
    assert.match(component, /activeView: this\.page/);

});


test("inventario usa los mismos estados y tonos FG que Catálogo", () => {
    const stock = read(inventoryPath + "stock.vue");
    assert.match(stock, /<td class="fg-align-center"><span :class="\['fg-badge', 'fg-badge--' \+ stockStatus\(record\)\.tone\]"/);
    assert.doesNotMatch(stock, /stockStatus\(record\)\.(icon|badgeClass)/);
    assert.match(readInventory(), /StockStatus\.fromLevels\(stock, minimum\)[\s\S]*?tone: inventoryBadgeTones\[code\]/);
    assert.deepEqual([inventoryBadgeTones.healthy, inventoryBadgeTones.empty, inventoryBadgeTones.low], ["success", "danger", "warning"]);
});

test("guías conserva la fecha, el número y los contratos paginados", () => {
    const page = read(inventoryPath + "guides.vue");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    assert.equal(inventoryViews.guides.columns[0].label, "Fecha y número");
    assert.match(page, /formatSimpleDate\(guide\.issue_date\)[\s\S]*?guide\.number/);
    assert.match(controller, /private const MAX_LIST_PER_PAGE = 30;/);
    assert.match(controller, /return min\(self::MAX_LIST_PER_PAGE, max\(1, \$this->getPerPage\(\$request, Utilities::\$per_page_default\)\)\);/);
    assert.equal((controller.match(/this->inventoryPerPage\(\$request\)/g) || []).length, 7);
    assert.match(service, /getPaginatedConsolidatedStock\([\s\S]*?->paginate\(\$perPage\)/);
    assert.match(service, /getConsolidatedStock\([\s\S]*?->get\(\)/);
});

test("Registrar traslado comparte la modal y mantiene motivos, filas y validación", () => {
    const page = read(inventoryPath + "transfers.vue");
    const state = read(inventoryPath + "inventoryState.js");
    const request = read("app/Http/Requests/System/Warehouses/StoreInventoryTransferRequest.php");
    assert.match(page, /data-bs-target="#inventoryTransferModal"/);
    assert.match(page, /<FormModal modal-id="inventoryTransferModal" title="Registrar traslado"/);
    assert.match(page, /class="fg-transfer-route"[\s\S]*?transferSource[\s\S]*?Selecciona el almacén que envía[\s\S]*?fa-right-long[\s\S]*?transferDestination[\s\S]*?Selecciona el almacén que recibe[\s\S]*?transferFormStatus/);
    assert.match(page, /v-model\.trim="transferForm\.reason"[\s\S]*?maxlength="255"[\s\S]*?show-char-counter/);
    assert.match(page, /<QuickCreateTrigger[^>]*title="Motivos frecuentes"[^>]*aria-controls="transferReasonsDialog"/);
    assert.match(page, /modal-id="transferReasonsDialog"[\s\S]*?<InventoryTable[^>]*:columns="transferReasonColumns"[^>]*scroll-records/);
    assert.match(page, /<a href="#transferReason"[^>]*@click\.prevent="applyTransferReason\(option\.text\)"/);
    assert.match(page, /transferReasonColumns: \(\) => \[\{label: "Motivo"\}, \{label: "Acción"/);
    assert.match(request, /"reason" => \["required", "string", "max:255"\]/);
    assert.match(page, /Productos incluidos[\s\S]*?@click="addTransferItem"[\s\S]*?@click="clearTransferItems"[\s\S]*?ref="transferItemsScroll" class="fg-table-scroll fg-table-scroll--records"/);
    assert.match(page, /Producto<\/th>[\s\S]*?Stock disponible<\/th>[\s\S]*?Cantidad a trasladar<\/th>/);
    assert.match(page, /v-model="item\.product"[\s\S]*?summarize-selection[\s\S]*?:value="transferAvailability\[item\.product\.code\]"/);
    assert.match(page, /v-model="item\.quantity"[^>]*:text-bottom-info="transferQuantityErrors\(item, index\)"/);
    assert.match(page, /icon="fa-solid fa-trash"[^>]*@click="removeTransferItem\(index\)"/);
    assert.doesNotMatch(page, /transferForm\.items\.length === 1/);
    assert.match(page, /:disabled="!transferForm\.items\.length \|\| hasTransferQuantityOverStock" @click="saveTransfer"/);
    assert.match(state, /addTransferItem\(\)[\s\S]*?transferForm\.items\.push[\s\S]*?refreshInventoryModalTooltips/);
    assert.match(state, /removeTransferItem\(index\)[\s\S]*?transferForm\.items\.splice\(index, 1\)[\s\S]*?refreshInventoryModalTooltips/);
    assert.match(state, /Alerts\.modals\(\{type: "hide", id: "inventoryTransferModal"\}\)/);
});

test("la operación conserva tipo, motivo, plantilla, productos y costo opcional", () => {
    const stock = read(inventoryPath + "stock.vue");
    const state = read(inventoryPath + "inventoryState.js");
    const request = read("app/Http/Requests/System/Warehouses/StoreInventoryOperationRequest.php");
    assert.match(stock, /<FormModal modal-id="inventoryMovementModal" title="Registrar operación"/);
    assert.match(stock, /Almacén de la operación[\s\S]*?Tipo de operación[\s\S]*?:selectable="option => !option\.isGroup"[\s\S]*?inventoryOperationEffect[\s\S]*?title="Motivo"/);
    assert.match(stock, /v-model\.trim="movementForm\.reason"[^>]*maxlength="100"/);
    assert.match(request, /"reason" => \["required", "string", "max:100"\]/);
    assert.match(stock, /Productos incluidos[\s\S]*?<TemplateFilePicker[\s\S]*?class="fg-table fg-inventory-items"/);
    assert.match(stock, /Costo unitario[\s\S]*?Opcional[\s\S]*?title="Si se omite, se conserva el costo promedio actual\."/);
    assert.match(stock, /v-for="\(item, index\) in movementForm\.items"[\s\S]*?v-model="item\.product"[\s\S]*?v-model="item\.amount"/);
    assert.match(stock, /<template #footer>[\s\S]*?@click="addMovementItem">Agregar producto[\s\S]*?@click="saveMovement">Guardar operación/);
    assert.match(state, /operation: null,[\s\S]*?warehouse: null/);
    assert.match(state, /if\(!operation\?\.code\) \{\s*this\.movementErrors\.operation = \["Selecciona un tipo de operación\."\]/);
    assert.match(state, /INVENTORY_OPERATION_GROUPS\.flatMap\(group => \[[\s\S]*?INVENTORY_OPERATIONS\.filter\(operation => operation\.movementType === group\.code\)/);
    assert.match(state, /removeMovementItem\(index\)[\s\S]*?movementForm\.items\.splice\(index, 1\)[\s\S]*?refreshInventoryModalTooltips/);
    assert.match(state, /Alerts\.modals\(\{type: "hide", id: "inventoryMovementModal"\}\)/);
});

test("los filtros y las acciones se muestran juntos con la tabla y paginación fuera de la botonera", () => {
    const listing = read(inventoryPath + "InventoryListing.vue");
    const table = read(inventoryPath + "InventoryTable.vue");
    const css = read("resources/css/System/fg/products.css");
    for(const name of ["stock", "transfers", "guides", "kardex", "valued-kardex"]) {
        const page = read(inventoryPath + name + ".vue");
        assert.match(page, /<InventoryListing[^>]*@search="searchCurrentView"[^>]*@page="listCurrentView"/);
        assert.doesNotMatch(page, /br-inventory|<Breadcrumb|<WithoutData/);
    }
    assert.match(listing, /fg-page-header__actions[\s\S]*?<ActionsDropdown type="button"[^>]*>[\s\S]*?Descargar Excel[\s\S]*?<\/header>/);
    assert.match(listing, /fg-filters fg-filter-grid[\s\S]*?<FgFilterActions[^>]*@search="\$emit\('search'\)" @clear="\$emit\('clear'\)"[\s\S]*?<InventoryTable[\s\S]*?<\/InventoryTable>\s*<Paginator/);
    assert.doesNotMatch(listing, /(?:brt?-|<SearchButton\b|<ClearFiltersButton\b)/);
    assert.match(read("resources/js/System/Pages/Catalogs/products/main.vue"), /<FgFilterActions submit[^>]*@clear="clearProductFilters"/);
    const filterActions = read("resources/js/System/Components/Figma/FgFilterActions.vue");
    assert.match(filterActions, /icon="fa-solid fa-magnifying-glass"[^>]*:loading="loading"[^>]*>Buscar<\/FgButton>/);
    assert.match(filterActions, /<FgButton v-if="showClear" :tone="clearTone"[^>]*>\{\{ clearLabel \}\}<\/FgButton>/);
    assert.match(filterActions, /clearLabel: \{type: String, default: "Limpiar"\}/);
    assert.match(filterActions, /clearTone: \{type: String, default: "link"\}/);
    assert.match(table, /<ResponsiveTable fg[\s\S]*?<table class="fg-table fg-inventory-table"[\s\S]*?<FgListLoading[\s\S]*?<FgEmptyState/);
    assert.match(css, /\.fg-table th \{[^}]*vertical-align: middle/);
    assert.match(css, /\.fg-table td \{[^}]*vertical-align: middle/);
    assert.match(css, /tbody tr:not\(\.fg-table__state\):hover/);
    assert.match(readInventory(), /Alerts\.confirmDownload/);
});

test("Traslados conserva los alcances de almacén y los selectores separados", () => {
    const component = readInventory();
    const page = read(inventoryPath + "transfers.vue");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const service = read("app/Services/System/Warehouses/Inventory/InventoryMovementService.php");
    assert.deepEqual(inventoryFilters.transfers.slice(0, 2).map(field => field.options), ["transferWarehouseOptions", "transferWarehouseOptions"]);
    assert.match(component, /sourceWarehouse: ALL_TRANSFER_WAREHOUSES,\s*destinationWarehouse: ALL_TRANSFER_WAREHOUSES/);
    assert.match(component, /prepareTransfer\(\)[\s\S]*?source: null,[\s\S]*?destination: null/);
    assert.match(component, /destinationWarehouses\(\)[\s\S]*?return this\.realWarehouses\.filter\(warehouse => Number\(warehouse\.code\) !== Number\(this\.transferForm\.source\?\.code\)\)/);
    assert.match(component, /source_warehouse_id: this\.transferForm\.source\.code/);
    assert.match(page, /:options="realWarehouses"[\s\S]*?:options="destinationWarehouses"/);
    assert.equal((page.match(/:summarize-selection="false"/g) || []).length, 2);
    assert.doesNotMatch(page, /placeholder="Todos los almacenes"/);
    assert.match(service, /\$warehouseIds = \$page->getCollection\(\)[\s\S]*?source_warehouse_id[\s\S]*?destination_warehouse_id/);
    assert.match(service, /\$warehouseQuery->whereIn\("id", \$filters\["allowed_warehouse_ids"\]\);/);
    assert.match(service, /"transfer_source_warehouse" => \$sourceId,[\s\S]*?"transfer_destination_warehouse" => \$destinationId/);
    assert.match(controller, /RolePermissionService::canAccessRoute\(\$this->getAuthUser\(\), "stocks_management\.transfers\.index"\)/);
    assert.match(controller, /\$filters\["allowed_warehouse_ids"\] = AccessScopeService::allowedIds/);
});

test("las operaciones conservan su almacén y control de carga independientes de los filtros", () => {
    const listing = read(inventoryPath + "InventoryListing.vue");
    const component = readInventory();
    assert.match(listing, /fieldDisabled\(field\) \{ return props\.loading \|\| \(field\.kind !== "warehouse" && !warehouseAvailable\.value\);/);
    assert.match(listing, /const searchDisabled = computed\(\(\) => props\.loading \|\| !warehouseAvailable\.value\)/);
    assert.match(component, /if\(requestVersion !== this\.requestVersion\) return;/);
    assert.match(component, /warehouse_id: this\.movementForm\.warehouse\.code/);
    assert.match(component, /prepareMovement\(record = null\)[\s\S]*?warehouse: null/);
    assert.match(component, /canConfigureTransfer\(\)[\s\S]*?Boolean\(source\?\.code && destination\?\.code && Number\(source\.code\) !== Number\(destination\.code\)\)/);
    assert.match(component, /searchCurrentView\(\)[\s\S]*?if\(\(this\.activeView !== "transfers" && !warehouse\) \|\| this\.isCurrentViewLoading\) return Promise\.resolve\(\);/);
    assert.match(component, /exportCurrentView\(\)[\s\S]*?this\.appliedFilters\.warehouse\?\.code/);
    assert.match(component, /focusProductSearch\(\)[\s\S]*?this\.\$refs\.inventoryListing\?\.focusProductSearch\(\);/);
});

test("el selector de almacén conserva sede y nombre en inventario", () => {
    const select = read("resources/js/System/Components/Generics/WarehouseSelect.vue");
    const listing = read(inventoryPath + "InventoryListing.vue");
    assert.match(select, /<FgSelect[\s\S]*?#selected-option="warehouse"[\s\S]*?fg-location__branch[\s\S]*?fg-location__name[\s\S]*?#option="warehouse"/);
    assert.match(listing, /<WarehouseSelect v-if="field\.kind === 'warehouse'"[\s\S]*?:model-value="filters\[field\.key\]"/);
    assert.match(readInventory(), /branchName: record\.branch\?\.name \|\| ""[\s\S]*?warehouseName: record\.name[\s\S]*?displayLabel:/);
});

test("ingresos manuales solo admiten entrada manual y devolución de cliente", () => {

    const state = read(`${inventoryPath}inventoryState.js`);
    const operations = state.match(/const INVENTORY_OPERATIONS = \[[\s\S]*?\n\];/)?.[0] ?? "";
    const service = read("app/Services/System/Warehouses/Inventory/InventoryMovementService.php");
    const stockService = read("app/Services/System/Warehouses/StockManagement/StockManagementService.php");
    const operationRequest = read("app/Http/Requests/System/Warehouses/StoreInventoryOperationRequest.php");
    const movementRequest = read("app/Http/Requests/System/Warehouses/StoreInventoryMovementRequest.php");

    assert.match(operations, /\{code: "manual_entry", label: "Entrada manual", amountLabel: "Cantidad de entrada", movementType: "entry"/);
    assert.match(operations, /\{code: "customer_return", label: "Devolución de cliente", amountLabel: "Cantidad devuelta", movementType: "entry"/);
    assert.doesNotMatch(operations, /replenishment|Reposición/);
    assert.match(service, /self::TYPE_ENTRY => \[self::ORIGIN_MANUAL, self::ORIGIN_CUSTOMER_RETURN\]/);
    assert.match(stockService, /if\(!in_array\(\$originType, InventoryMovementService::manualOperationOrigins\(\$movementType\), true\)\)/);

    for(const request of [operationRequest, movementRequest]) {
        assert.match(request, /Rule::in\(InventoryMovementService::manualOperationOrigins/);
        assert.doesNotMatch(request, /replenishment/);
    }

});
