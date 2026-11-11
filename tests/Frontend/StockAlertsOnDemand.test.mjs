import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {emptyPagination} from "../../resources/js/System/Helpers/ListState.js";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

function alertsRequest() {
    const events = [];
    const requests = [];
    let resolveRequest;
    let rejectRequest;
    const source = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        const name = match[1].replace("* as ", "").replace(/[{}\s]/g, "");
        dependencies[name] = {};
    }
    const context = {
        appliedFilters: {warehouse: {code: 1}},
        requestVersion: 1,
        loadingStock: false,
        loadingAlerts: false,
        stockAlerts: {total: 0, data: []},
        appliedAlertProductSearch: "",
        alertProductSearch: "",
        alertMinimumStatus: {code: ""},
        appliedAlertMinimumStatus: "",
        async $nextTick() { events.push("render"); }
    };
    dependencies.Alerts = {
        loading() { events.push("loading"); },
        close() { events.push("close"); },
        modals({id}) {
            assert.equal(id, "inventoryStockAlertsModal");
            assert.equal(context.loadingAlerts, false);
            events.push("modal");
        }
    };
    dependencies.Requests = {
        emptyPagination,
        config: () => "/alerts",
        get(options) {
            events.push("request");
            requests.push(options);
            return new Promise((resolve, reject) => {
                resolveRequest = resolve;
                rejectRequest = reject;
            });
        },
        async download(options) {
            requests.push(options);
            return {bool: true};
        }
    };
    const component = new Function(...Object.keys(dependencies), source
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))(...Object.values(dependencies));
    let pending;
    context.listStockAlerts = options => pending = component.methods.listStockAlerts.call(context, options);
    return {
        context,
        events,
        requests,
        run: context.listStockAlerts,
        get pending() { return pending; },
        search: component.methods.searchStockAlerts.bind(context),
        download: component.methods.downloadStockAlerts.bind(context),
        respond: result => resolveRequest(result),
        reject: () => rejectRequest(new Error("network error"))
    };
}

test("Stock mínimo conserva el filtro aplicado en búsqueda, paginación y descargas", async () => {
    for(const minimum of ["", "configured", "unconfigured"]) {
        const request = alertsRequest();
        request.context.alertProductSearch = " tuerca ";
        request.context.alertMinimumStatus = {code: minimum};
        request.search();
        assert.equal(request.requests[0].data.product_search, "tuerca");
        assert.equal(request.requests[0].data.minimum_status, minimum);
        assert.equal(request.requests[0].data.per_page, 10);
        request.respond({bool: true, data: {total: 10, data: [{id: 1}]}});
        await request.pending;

        const page = request.run({url: "/alerts?page=2"});
        assert.equal(request.requests[1].data.minimum_status, minimum);
        assert.equal(request.requests[1].data.per_page, 10);
        assert.equal(request.requests[1].data.product_search, "tuerca");
        request.respond({bool: true, data: {total: 10, data: [{id: 2}]}});
        await page;

        request.context.alertMinimumStatus = {code: "draft-change"};
        for(const format of ["pdf", "excel"]) {
            await request.download(format);
            assert.equal(request.requests.at(-1).data.minimum_status, minimum);
            assert.equal(request.requests.at(-1).data.product_search, "tuerca");
            assert.equal(request.requests.at(-1).data.export, format);
        }
    }
});

test("Ver alertas mantiene cerrada la modal durante la consulta y la abre con los resultados", async () => {
    const request = alertsRequest();
    const pending = request.run({showModal: true});
    await Promise.resolve();
    assert.deepEqual(request.events, ["loading", "request"]);
    assert.equal(request.context.loadingAlerts, true);
    await request.run({showModal: true});
    assert.deepEqual(request.events, ["loading", "request"]);
    const data = {total: 1, data: [{id: 10}], links: []};
    request.respond({bool: true, data});
    await pending;
    assert.equal(request.context.stockAlerts, data);
    assert.deepEqual(request.events, ["loading", "request", "close", "render", "modal"]);
});

test("una respuesta vacía o fallida abre la modal después del loader con su estado correspondiente", async () => {
    for(const result of [{bool: true, data: {total: 0, data: []}}, {bool: false, data: {msg: "Error de consulta"}}, null]) {
        const request = alertsRequest();
        const pending = request.run({showModal: true});
        await Promise.resolve();
        assert.equal(request.events.includes("modal"), false);
        result ? request.respond(result) : request.reject();
        await pending;
        assert.deepEqual(request.events.slice(-3), ["close", "render", "modal"]);
        if(!result?.bool) assert.ok(request.context.stockAlertsError);
        else assert.equal(request.context.stockAlerts.total, 0);
    }
});

test("una respuesta de un contexto anterior no abre la modal de alertas", async () => {
    for(const change of [context => { context.requestVersion++; }, context => { context.appliedFilters.warehouse = {code: 2}; }]) {
        const request = alertsRequest();
        const pending = request.run({showModal: true});
        await Promise.resolve();
        change(request.context);
        request.respond({bool: true, data: {total: 1, data: [{id: 10}]}});
        await pending;
        assert.equal(request.events.includes("modal"), false);
        assert.equal(request.context.stockAlerts.total, 0);
    }
});

test("buscar y paginar alertas actualiza los datos sin solicitar otra apertura", async () => {
    for(const options of [{}, {url: "/alerts?page=2"}]) {
        const request = alertsRequest();
        const pending = request.run(options);
        request.respond({bool: true, data: {total: 0, data: []}});
        await pending;
        assert.deepEqual(request.events, ["loading", "request", "close"]);
    }
});

test("cada apertura de alertas restablece los filtros antes de consultar y limpia las filas anteriores", async () => {
    const request = alertsRequest();
    for(let opening = 0; opening < 2; opening++) {
        request.context.alertProductSearch = "filtro anterior";
        request.context.alertMinimumStatus = {code: "configured"};
        request.context.appliedAlertProductSearch = "búsqueda anterior";
        request.context.appliedAlertMinimumStatus = "unconfigured";
        request.context.stockAlerts = {data: [{id: 88}], total: 10};
        const pending = request.run({showModal: true});
        assert.deepEqual(request.context.stockAlerts.data, []);
        await Promise.resolve();
        assert.equal(request.context.alertProductSearch, "");
        assert.equal(request.context.alertMinimumStatus.code, "");
        assert.equal(request.requests.at(-1).data.product_search, "");
        assert.equal(request.requests.at(-1).data.minimum_status, "");
        request.respond({bool: true, data: {data: [{id: opening}], total: 1}});
        await pending;
    }
});

test("paginar alertas conserva los filtros aplicados hasta pulsar Buscar", async () => {
    const request = alertsRequest();
    request.context.appliedAlertProductSearch = "anterior";
    request.context.appliedAlertMinimumStatus = "configured";
    request.context.alertProductSearch = " actual ";
    request.context.alertMinimumStatus = {code: "unconfigured"};
    const pending = request.run({url: "/alerts?page=2&product_search=anterior"});
    assert.equal(request.requests[0].data.product_search, "anterior");
    assert.equal(request.requests[0].data.minimum_status, "configured");
    request.respond({bool: true, data: {data: [], total: 0}});
    await pending;
    request.search();
    assert.equal(request.requests[1].data.product_search, "actual");
    assert.equal(request.requests[1].data.minimum_status, "unconfigured");
    request.respond({bool: true, data: {data: [], total: 0}});
    await request.pending;
});

test("las alertas de stock se consultan solo al abrirlas y se muestran paginadas", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const sharedModal = read("resources/js/System/Components/Generics/FormModal.vue");
    const modalStyles = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const inventoryStyles = read("resources/css/System/br-branding/82-inventory.css");
    const listStock = state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts("));

    assert.doesNotMatch(listStock, /listStockAlerts|type: "alerts"/);
    assert.doesNotMatch(page, /openStockAlerts|br-inventory-alerts__list|Productos agotados o con stock bajo/);
    assert.match(page, /aria-label="Ver alertas"[\s\S]*?<StockAlertsModal/);
    const alertsButton = page.match(/<button\b[^>]*class="[^"]*\bbr-inventory__alerts-button\b[^"]*"[^>]*>[\s\S]*?<\/button>/)?.[0] ?? "";
    assert.match(alertsButton, /\bv-tooltip\b[^>]*aria-label="Ver alertas"[^>]*title="Ver alertas"[^>]*data-bs-toggle="tooltip"/);
    assert.match(alertsButton, /:disabled="!canViewStockAlerts"/);
    assert.doesNotMatch(alertsButton, /data-bs-target/);
    assert.match(alertsButton, /@click="viewStockAlerts">\s*<i class="fa-solid fa-bell fa-shake" aria-hidden="true"><\/i>\s*<\/button>/);
    assert.match(page, /viewStockAlerts\(event\) \{\s*dismissTooltip\(event\.currentTarget\);\s*return this\.listStockAlerts\(\{showModal: true\}\);/);
    assert.match(state, /async listStockAlerts\(\{url = null, showModal = false\} = \{\}\)[\s\S]*?type: "alerts"[\s\S]*?status: "open"/);
    assert.match(modal, /v-for="alert in alerts\.data"[\s\S]*?alert\.warehouse_item\?\.item\?\.name/);
    assert.match(modal, /<Paginator[\s\S]*?:links="alerts\.links"[\s\S]*?@clickPage=/);
    assert.match(modal, /<FormModal[\s\S]*?modal-id="inventoryStockAlertsModal"[\s\S]*?title="ALERTAS DE STOCK"[\s\S]*?body-class="br-inventory-alerts__body"[\s\S]*?:show-submit="false"/);
    assert.match(modal, /br-inventory-alerts__table-scroll[\s\S]*?<\/section>[\s\S]*?<Paginator[^>]*:pagination="alerts"/);
    assert.match(inventoryStyles, /\.br-inventory-alerts__table-head \{[\s\S]*?overflow-y: hidden;/);
    assert.match(inventoryStyles, /\.br-inventory-alerts__table-scroll \{[\s\S]*?overflow-y: auto;/);
    assert.match(page, /<FormModal[\s\S]*?modal-id="inventoryMovementModal"[\s\S]*?title="REGISTRAR OPERACIÓN"[\s\S]*?dialog-class="modal-dialog-scrollable"/);
    assert.match(page, /<template #footerClose>[\s\S]*?ref="closeMovementModal"[\s\S]*?<template #footer>[\s\S]*?Guardar operación/);
    assert.match(sharedModal, /br-entity-modal__title--form/);
    assert.match(modalStyles, /\.br-entity-modal \.modal-dialog-scrollable \.modal-content \{\s*overflow: hidden;/);
    assert.match(modalStyles, /\.br-entity-modal \.modal-dialog-scrollable \.modal-body\.br-entity-modal__body \{\s*overflow-x: hidden !important;\s*overflow-y: auto !important;/);
});
