import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {emptyPagination} from "../../resources/js/System/Helpers/ListState.js";

function traceability() {
    const source = readFileSync(new URL("../../resources/js/System/Pages/Warehouses/inventory/inventoryState.js", import.meta.url), "utf8");
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) dependencies[match[1].replace("* as ", "").replace(/[{}\s]/g, "")] = {};
    const requests = [], events = [];
    let respond, reject;
    dependencies.Requests = {
        emptyPagination, config: () => "/traceability",
        get(options) { requests.push(options); events.push("request"); return new Promise((resolve, fail) => { respond = resolve; reject = fail; }); }
    };
    dependencies.Alerts = {
        loading() { events.push("loading"); }, close() { events.push("close"); },
        modals({id, type = "show"}) { events.push(`${type}:${id}`); }
    };
    const component = new Function(...Object.keys(dependencies), source.replace(/^import .*;\s*$/gm, "").replace("export default", "return"))(...Object.values(dependencies));
    const context = {
        filters: {warehouse: {code: 3, label: "Pendiente"}},
        appliedFilters: {warehouse: {code: 2, label: "Central"}}, canUpdateStock: false,
        loadingTraceability: false, traceabilityRequest: 0, requestVersion: 0,
        traceabilityRecords: {data: [{id: 99}], total: 1, links: [{url: "?page=2"}]},
        async $nextTick() { events.push("render"); }
    };
    for(const [name, method] of Object.entries(component.methods)) context[name] = method.bind(context);
    return {context, requests, events, respond: result => respond(result), reject: () => reject(new Error("red"))};
}

test("Ver trazabilidad consulta el producto y almacén exactos y abre la modal después de recibir la respuesta", async () => {
    const {context, requests, events, respond} = traceability();
    const pending = context.openTraceability({id: 7, name: "Perno"});
    assert.deepEqual(context.traceabilityRecords.data, []);
    assert.equal(context.loadingTraceability, true);
    assert.deepEqual(events, ["loading", "request"]);
    assert.deepEqual(requests[0], {route: "/traceability", data: {warehouse_id: 2, item_id: 7, per_page: 10}});
    const records = {data: [{id: 1, quantity_after: 5}], total: 1, links: []};
    respond({bool: true, data: records});
    await pending;
    assert.equal(context.traceabilityRecords, records);
    assert.equal(context.loadingTraceability, false);
    assert.deepEqual(events, ["loading", "request", "close", "render", "show:inventoryStockTraceabilityModal"]);
});

test("paginar mantiene el producto, vacía datos y no vuelve a abrir la modal", async () => {
    const {context, requests, events, respond} = traceability();
    context.traceabilityProduct = {id: 7};
    context.traceabilityWarehouse = {code: 2};
    const pending = context.listTraceability({url: "/traceability?page=2"});
    assert.deepEqual(context.traceabilityRecords.data, []);
    assert.equal(context.traceabilityRecords.total, 0);
    assert.deepEqual(requests[0].data, {warehouse_id: 2, item_id: 7, per_page: 10});
    respond({bool: true, data: {data: [], total: 0, links: []}});
    await pending;
    assert.deepEqual(events, ["loading", "request", "close"]);
});

test("si falla la consulta queda vacía, libera la carga y ofrece el estado de error", async () => {
    for(const failedRequest of [false, true]) {
        const {context, respond, reject, events} = traceability();
        const pending = context.openTraceability({id: 7});
        if(failedRequest) reject();
        else respond({bool: false, data: {msg: "No disponible"}});
        await pending;
        assert.deepEqual(context.traceabilityRecords.data, []);
        assert.ok(context.traceabilityError);
        assert.equal(context.loadingTraceability, false);
        assert.deepEqual(events.slice(-3), ["close", "render", "show:inventoryStockTraceabilityModal"]);
    }
});

test("cambiar de almacén descarta una respuesta pendiente y evita abrir información del contexto anterior", async () => {
    const {context, respond, events} = traceability();
    const pending = context.openTraceability({id: 7});
    context.appliedFilters.warehouse = {code: 3};
    context.invalidateListing();
    respond({bool: true, data: {data: [{id: 1}], total: 1}});
    await pending;
    assert.deepEqual(context.traceabilityRecords.data, []);
    assert.equal(context.loadingTraceability, false);
    assert.equal(context.traceabilityProduct, null);
    assert.equal(events.includes("show:inventoryStockTraceabilityModal"), false);
});
