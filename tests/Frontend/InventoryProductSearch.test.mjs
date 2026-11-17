import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {reactive} from "vue";
import * as Numbers from "../../resources/js/Shared/Numbers.js";
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
    dependencies.Numbers = Numbers;
    return new Function(...Object.keys(dependencies), source
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))(...Object.values(dependencies));
}

test("las diez plantillas de traslado copian un motivo editable dentro del límite del campo", () => {
    const parent = {};
    let onHidden;
    let dialogOpen = false;
    const {computed, methods} = inventoryComponent({}, {
        showStackedModal({id, parent: transferModal, onHidden: callback}) {
            assert.equal(id, "transferReasonsDialog");
            assert.equal(transferModal, parent);
            dialogOpen = true;
            onHidden = callback;
        },
        modals({type, id}) {
            assert.equal(type, "hide");
            assert.equal(id, "transferReasonsDialog");
            dialogOpen = false;
            onHidden();
        }
    });
    const options = computed.transferReasonOptions();
    assert.equal(options.length, 10);
    assert.equal(new Set(options.map(option => option.label)).size, 10);
    assert.equal(new Set(options.map(option => option.text)).size, 10);
    assert.ok(options.every(option => option.text.trim().length > 0 && option.text.length <= 255));

    let focused = false;
    const trigger = {closest: () => parent, focus() { assert.fail("El foco debe volver al motivo seleccionado"); }};
    const context = {
        canConfigureTransfer: true,
        savingTransfer: false,
        transferForm: {reason: ""},
        transferErrors: {reason: ["Campo obligatorio"], items: ["Falta un producto"]},
        $refs: {transferReason: {focus() { focused = true; }}}
    };

    methods.openTransferReasons.call(context, {currentTarget: trigger});
    assert.equal(dialogOpen, true);
    methods.applyTransferReason.call(context, options[0].text);
    assert.equal(dialogOpen, false);
    assert.equal(context.transferForm.reason, options[0].text);
    assert.deepEqual(context.transferErrors, {items: ["Falta un producto"]});
    assert.equal(focused, true);

    context.transferForm.reason += " Para el despacho de mañana.";
    context.savingTransfer = true;
    methods.applyTransferReason.call(context, options[1].text);
    assert.equal(context.transferForm.reason, options[0].text + " Para el despacho de mañana.");
});

test("el traslado consulta los productos seleccionados y descarta saldos de un origen anterior", async () => {
    const requests = [];
    const component = inventoryComponent({
        get({data}) { return new Promise(resolve => requests.push({data, resolve})); },
        valid: ({result}) => Boolean(result?.data?.bool)
    });
    const context = component.data.call({page: "transfers"});
    Object.defineProperty(context, "canConfigureTransfer", {
        get: () => component.computed.canConfigureTransfer.call(context)
    });
    context.transferForm.source = {code: 1};
    context.transferForm.items[0].product = {code: 25};
    context.transferForm.reason = "Reposición de stock";
    const query = () => component.computed.transferAvailabilityQuery.call(context);
    const load = () => component.methods.loadTransferAvailability.call(context, query());

    await load();
    assert.equal(context.canConfigureTransfer, false);
    assert.equal(requests.length, 0);

    context.transferForm.destination = {code: 1};
    assert.equal(context.canConfigureTransfer, false);
    assert.equal(query(), null);

    context.transferForm.destination = {code: 3};
    assert.equal(context.canConfigureTransfer, true);
    const first = load();
    context.transferForm.source = {code: 2};
    const current = load();
    assert.deepEqual(requests.map(request => request.data), [
        {warehouse_id: 1, item_ids: [25]},
        {warehouse_id: 2, item_ids: [25]}
    ]);
    assert.equal(context.loadingTransferAvailability, true);
    requests[1].resolve({data: {bool: true, data: {25: 0}}});
    await current;
    requests[0].resolve({data: {bool: true, data: {25: 14}}});
    await first;
    assert.deepEqual(context.transferAvailability, {25: 0});
    assert.equal(context.loadingTransferAvailability, false);

    context.transferForm.destination = null;
    await load();
    assert.deepEqual(context.transferAvailability, {});
    assert.equal(context.transferForm.items[0].product.code, 25);
    assert.equal(context.transferForm.reason, "Reposición de stock");

    context.transferForm.destination = {code: 3};
    context.transferForm.items = [];
    await load();
    assert.deepEqual(context.transferAvailability, {});
    assert.equal(requests.length, 2);
});

test("el traslado marca cantidades que exceden el saldo conocido con la precisión de la empresa", async () => {
    const {computed, methods} = inventoryComponent({post() { assert.fail("No se debe enviar una cantidad superior al stock"); }});
    const item = {product: {code: 25}, quantity: "0.126"};
    const context = {
        canConfigureTransfer: true,
        transferForm: {items: [item]},
        transferAvailability: {},
        transferErrors: {},
        firstError: methods.firstError,
        transferItemError: methods.transferItemError,
        transferQuantityExceedsStock: candidate => methods.transferQuantityExceedsStock.call(context, candidate),
        get hasTransferQuantityOverStock() { return computed.hasTransferQuantityOverStock.call(context); }
    };
    const errors = () => methods.transferQuantityErrors.call(context, item, 0);
    assert.deepEqual(errors(), []);

    const originalPrecision = Numbers.numericInputConfig().round;
    try {
        Numbers.applyNumericInputConfig({round: 3});
        context.transferAvailability = {25: 0.125};
        assert.deepEqual(errors(), ["La cantidad supera el stock disponible."]);
        assert.equal(context.hasTransferQuantityOverStock, true);
        await methods.saveTransfer.call(context);

        item.quantity = "0.125";
        assert.deepEqual(errors(), []);
        assert.equal(context.hasTransferQuantityOverStock, false);
        context.transferAvailability[25] = 0;
        assert.equal(context.hasTransferQuantityOverStock, true);
        item.quantity = "";
        assert.equal(context.hasTransferQuantityOverStock, false);
        context.transferErrors = {"items.0.quantity": ["Ingresa una cantidad."], "items.0.item_id": ["Revisa el producto."]};
        assert.deepEqual(errors(), ["Ingresa una cantidad."]);
        methods.clearTransferQuantityError.call(context, 0);
        assert.deepEqual(errors(), []);
        assert.deepEqual(context.transferErrors, {"items.0.item_id": ["Revisa el producto."]});
    } finally {
        Numbers.applyNumericInputConfig({round: originalPrecision});
    }
});

test("una operación o traslado puede quitar la última fila sin enviar un formulario vacío", async () => {
    const {methods} = inventoryComponent({post() { assert.fail("No se pueden guardar filas vacías"); }});
    for(const [form, errors, remove, save] of [
        ["movementForm", "movementErrors", "removeMovementItem", "saveMovement"],
        ["transferForm", "transferErrors", "removeTransferItem", "saveTransfer"]
    ]) {
        const context = {
            [form]: {warehouse: {code: 1}, items: [{product: {code: 25}}]},
            [errors]: {"items.0.quantity": ["Dato inválido"]},
            canConfigureTransfer: true,
            disposeInventoryModalTooltips() {},
            refreshInventoryModalTooltips() {}
        };
        methods[remove].call(context, 0);
        assert.deepEqual(context[form].items, []);
        assert.deepEqual(context[errors], {});
        await methods[save].call(context);
    }
});

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
