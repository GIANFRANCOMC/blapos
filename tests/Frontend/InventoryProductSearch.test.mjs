import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {reactive} from "vue";
import {emptyPagination} from "../../resources/js/System/Helpers/ListState.js";

const source = readFileSync(new URL("../../resources/js/System/Pages/Warehouses/inventory/inventoryState.js", import.meta.url), "utf8");

function inventoryComponent(requests = {}, alerts = {}) {
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        const name = match[1].replace("* as ", "").replace(/[{}\s]/g, "");
        dependencies[name] = {};
    }
    dependencies.Requests = {emptyPagination, config: () => ({}), ...requests};
    dependencies.Alerts = alerts;
    return new Function(...Object.keys(dependencies), source
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))(...Object.values(dependencies));
}

test("editar cualquier filtro de inventario conserva filas y paginación hasta Buscar", () => {
    const component = inventoryComponent();
    for(const page of ["stock", "kardex", "valued", "transfers", "guides"]) {
        const context = reactive(component.data.call({page}));
        context.appliedFilters = {...context.filters, warehouse: {code: 1}, productSearch: "anterior"};
        context.kardexRecords = {total: 20, data: [{id: 10}], links: [{url: "?page=2"}]};
        const records = context.kardexRecords;
        Object.assign(context.filters, {
            warehouse: {code: 2}, productSearch: "nuevo", movementType: {code: "entry"},
            stockSituation: {code: "low"}, stockCategory: {code: 4}, dateFrom: "2026-01-01"
        });
        assert.equal(context.kardexRecords, records);
        assert.equal(context.appliedFilters.warehouse.code, 1);
        assert.equal(context.appliedFilters.productSearch, "anterior");
    }
    assert.ok(Object.keys(component.watch).every(key => !key.startsWith("filters.") && key !== "alertMinimumStatus"));
});

test("Buscar aplica el texto actual y reemplaza los registros al recibir la respuesta", async () => {
    const requests = [];
    let respond;
    const component = inventoryComponent({
        config: () => "/stock",
        get(options) {
            requests.push(options);
            return new Promise(resolve => { respond = resolve; });
        }
    });
    const previous = {total: 20, data: [{id: 10}], links: [{url: "?page=2"}]};
    const context = {
        activeView: "stock",
        appliedFilters: {warehouse: null},
        filters: {warehouse: {code: 1}, productSearch: "producto", stockSituation: {code: ""}},
        isCurrentViewLoading: false,
        requestVersion: 0,
        stockRecords: previous,
        findExactProduct: () => null,
        currentStockAdvancedFilters: () => ({})
    };
    context.listCurrentView = component.methods.listStock.bind(context);
    const pending = component.methods.searchCurrentView.call(context);
    assert.deepEqual(context.stockRecords.data, []);
    assert.equal(context.stockRecords.total, 0);
    assert.notEqual(context.stockRecords, previous);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].data.product_search, "producto");
    assert.equal(requests[0].data.per_page, 10);
    const result = {total: 1, data: [{id: 22}], links: []};
    respond({bool: true, data: result});
    await pending;
    assert.equal(context.stockRecords, result);
    assert.equal(context.loadingStock, false);

    context.filters.productSearch = "pendiente";
    const page = component.methods.listStock.call(context, {url: "/stock?page=2"});
    assert.equal(requests[1].data.product_search, "producto");
    assert.equal(requests[1].route, "/stock?page=2");
    respond({bool: true, data: result});
    await page;
});

test("Limpiar filtros restablece todos los campos juntos y conserva el almacén y los registros hasta Buscar", async () => {
    let confirmations = 0;
    const component = inventoryComponent({}, {showFiltersCleared: () => confirmations++});
    const context = reactive({
        activeView: "stock",
        filters: {
            warehouse: {code: 2}, productSearch: "producto", stockSituation: {code: "low"},
            stockCategory: {code: 3}, stockBrand: {code: 4}, stockMinimumStatus: {code: "configured"},
            stockInactiveDays: {code: "30"}, movementType: {code: "entry"}, movementUser: {code: 5}, guideType: {code: "exit"},
            dateFrom: "2026-01-01", dateTo: "2026-02-01"
        },
        stockRecords: {data: [{id: 10}], total: 1}, requestVersion: 4,
        appliedFilters: {warehouse: {code: 2}, productSearch: "producto"},
        scannedProductId: 10,
        isCurrentViewLoading: false,
        listStock() { assert.fail("Limpiar no debe provocar consultas automáticas por cada filtro"); }
    });
    const {stockRecords} = context;
    const warehouse = context.filters.warehouse;
    await component.methods.clearFilters.call(context);
    assert.equal(context.filters.warehouse, warehouse);
    assert.equal(context.filters.productSearch, "");
    for(const field of ["stockSituation", "stockMinimumStatus", "stockInactiveDays", "movementType", "movementUser", "guideType"]) {
        assert.equal(context.filters[field].code, "", field);
    }
    assert.equal(context.filters.stockCategory, null);
    assert.equal(context.filters.stockBrand, null);
    assert.equal(context.filters.dateFrom, "");
    assert.equal(context.filters.dateTo, "");
    assert.equal(context.appliedFilters.productSearch, "producto");
    assert.equal(context.scannedProductId, 10);
    assert.equal(context.stockRecords, stockRecords);
    assert.equal(context.requestVersion, 4);
    assert.equal(confirmations, 1);
});

test("ambos Kardex envían el responsable seleccionado y Control de stock conserva el filtro de estado", () => {
    const {methods} = inventoryComponent();
    const context = {
        appliedFilters: {
            warehouse: {code: 2}, movementUser: {code: 5}, stockSituation: {code: "low"},
            stockCategory: {code: 3}, stockMinimumStatus: {code: "configured"}, stockInactiveDays: {code: "30"}
        },
        currentStockAdvancedFilters: methods.currentStockAdvancedFilters
    };
    for(const activeView of ["kardex", "valued"]) {
        const filters = methods.currentReportFilters.call({...context, activeView});
        assert.equal(filters.user_id, 5);
        assert.equal(filters.category_id, 3);
        assert.equal(Object.hasOwn(filters, "situation"), false);
        assert.equal(Object.hasOwn(filters, "minimum_status"), false);
        assert.equal(Object.hasOwn(filters, "inactive_days"), false);
    }
    const stock = methods.currentReportFilters.call({...context, activeView: "stock"});
    assert.equal(stock.situation, "low");
    assert.equal(stock.minimum_status, "configured");
    assert.equal(stock.inactive_days, "30");
    assert.equal(Object.hasOwn(stock, "user_id"), false);
});
