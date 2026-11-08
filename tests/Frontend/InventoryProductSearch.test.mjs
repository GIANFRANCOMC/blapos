import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {nextTick, reactive, watch} from "vue";
import {emptyPagination} from "../../resources/js/System/Helpers/ListState.js";

const source = readFileSync(new URL("../../resources/js/System/Pages/Warehouses/inventory/inventoryState.js", import.meta.url), "utf8");

function inventoryComponent(requests = {}) {
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        const name = match[1].replace("* as ", "").replace(/[{}\s]/g, "");
        dependencies[name] = {};
    }
    dependencies.Requests = {emptyPagination, ...requests};
    return new Function(...Object.keys(dependencies), source
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))(...Object.values(dependencies));
}

test("escribir o limpiar Producto conserva registros y paginación sin consultar al servidor", async () => {
    const component = inventoryComponent();
    const context = reactive({
        filters: {productSearch: ""},
        requestVersion: 4,
        scannedProductId: 10,
        stockRecords: {total: 20, data: [{id: 10}], links: [{url: "?page=2"}]},
        kardexRecords: {total: 10, data: [{id: 20}]},
        stockAlerts: {total: 3, data: [{id: 30}]},
        invalidateListing() { assert.fail("Editar la búsqueda no debe invalidar listados"); },
        listCurrentView() { assert.fail("La búsqueda necesita Buscar o Enter"); }
    });
    const {stockRecords, kardexRecords, stockAlerts} = context;
    const stop = watch(() => context.filters.productSearch, component.watch["filters.productSearch"].bind(context));
    try {
        for(const value of ["p", "pr", "producto", ""]) {
            context.filters.productSearch = value;
            await nextTick();
            assert.equal(context.stockRecords, stockRecords);
            assert.equal(context.kardexRecords, kardexRecords);
            assert.equal(context.stockAlerts, stockAlerts);
            assert.equal(context.requestVersion, 4);
            assert.equal(context.scannedProductId, null);
        }
    } finally {
        stop();
    }
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
        filters: {warehouse: {code: 1}, productSearch: "producto", stockSituation: {code: ""}},
        isCurrentViewLoading: false,
        requestVersion: 0,
        stockRecords: previous,
        findExactProduct: () => null,
        currentStockAdvancedFilters: () => ({})
    };
    context.listCurrentView = component.methods.listStock.bind(context);
    context.searchCurrentView = component.methods.searchCurrentView.bind(context);
    const pending = component.methods.handleProductSearch.call(context);
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
});

test("Limpiar filtros restablece todos los campos juntos y conserva el almacén y los registros hasta Buscar", async () => {
    const component = inventoryComponent();
    const context = reactive({
        filters: {
            warehouse: {code: 2}, productSearch: "producto", stockSituation: {code: "low"},
            stockCategory: {code: 3}, stockBrand: {code: 4}, stockMinimumStatus: {code: "configured"},
            stockInactiveDays: {code: "30"}, movementType: {code: "entry"}, guideType: {code: "exit"},
            dateFrom: "2026-01-01", dateTo: "2026-02-01"
        },
        stockRecords: {data: [{id: 10}], total: 1}, requestVersion: 4,
        scannedProductId: 10, warehouseSearchEnabled: true, resettingFilters: false,
        isCurrentViewLoading: false, $nextTick: nextTick,
        listStock() { assert.fail("Limpiar no debe provocar consultas automáticas por cada filtro"); }
    });
    context.invalidateListing = component.methods.invalidateListing.bind(context);
    context.refreshStockAdvancedFilters = component.methods.refreshStockAdvancedFilters.bind(context);
    const stops = Object.keys(context.filters).map(key => watch(
        () => context.filters[key], component.watch[`filters.${key}`].bind(context)
    ));
    const {stockRecords} = context;
    const warehouse = context.filters.warehouse;
    try {
        await component.methods.clearFilters.call(context);
        assert.equal(context.filters.warehouse, warehouse);
        assert.equal(context.filters.productSearch, "");
        for(const field of ["stockSituation", "stockMinimumStatus", "stockInactiveDays", "movementType", "guideType"]) {
            assert.equal(context.filters[field].code, "", field);
        }
        assert.equal(context.filters.stockCategory, null);
        assert.equal(context.filters.stockBrand, null);
        assert.equal(context.filters.dateFrom, "");
        assert.equal(context.filters.dateTo, "");
        assert.equal(context.scannedProductId, null);
        assert.equal(context.stockRecords, stockRecords);
        assert.equal(context.requestVersion, 4);
        assert.equal(context.resettingFilters, false);
    } finally {
        stops.forEach(stop => stop());
    }
});
