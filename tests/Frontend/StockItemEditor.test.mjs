import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

function editor({result, confirmed = true} = {}) {
    const {descriptor} = parse(read("resources/js/System/Pages/Warehouses/inventory/StockItemEditor.vue"));
    const events = [];
    const requests = [];
    let hidden;
    const Alerts = {
        async confirmAction() { events.push("confirm"); return confirmed; },
        loading() { events.push("loading"); }, close() { events.push("close"); }, toastrs() {},
        modals({type = "show", id}) { events.push(`${type}:${id}`); if(type === "hide" && id === "inventoryStockAlertsModal") hidden?.(); }
    };
    const Requests = {
        config: () => "/levels", valid: ({result}) => Boolean(result?.bool && result?.data?.bool),
        async post(request) { requests.push(request); return result ?? {bool: true, data: {bool: true, data: {quantity: 9, minimum_stock: 2}}}; }
    };
    const document = {getElementById: () => ({classList: {contains: () => true}, addEventListener: (event, handler) => { hidden = handler; }})};
    const component = new Function("Alerts", "Requests", "FormModal", "InputNumber", "InputText", "ProductIdentity", "document", descriptor.script.content
        .replace(/^import .*;\s*$/gm, "").replace("export default", "return"))(Alerts, Requests, {}, {}, {}, {}, document);
    const context = {
        ...component.data(),
        record: {id: 7, name: "Perno"}, warehouse: {code: 2, label: "Almacén central"},
        original: {quantity: 4, minimum_stock: 6}, form: {quantity: 9, minimum_stock: 2, reason: "Conteo físico"},
        async $nextTick() {}, async refresh(data) { events.push(["refresh", data]); return true; }
    };
    Object.defineProperty(context, "changed", {get: () => component.computed.changed.call(context)});
    for(const [name, method] of Object.entries(component.methods)) context[name] = method.bind(context);
    return {context, events, requests};
}

test("el editor guarda ambos niveles con el saldo esperado y refresca antes de cerrar", async () => {
    const {context, events, requests} = editor();
    context.returnModalId = "inventoryStockAlertsModal";
    await context.save();
    assert.deepEqual(requests[0].data, {
        warehouse_id: 2, item_id: 7, quantity: 9, minimum_stock: 2,
        expected_quantity: 4, expected_minimum_stock: 6, reason: "Conteo físico"
    });
    assert.deepEqual(events, ["confirm", "loading", ["refresh", {warehouseId: 2, fromAlerts: true}], "hide:inventoryStockEditorModal", "close"]);
    assert.equal(context.busy, false);
});

test("cancelar o enviar valores inválidos no guarda ni cambia los listados", async () => {
    const cancelled = editor({confirmed: false});
    await cancelled.context.save();
    assert.equal(cancelled.requests.length, 0);
    assert.deepEqual(cancelled.events, ["confirm"]);
    assert.equal(cancelled.context.busy, false);
    for(const value of ["", -1, "incorrecto"]) {
        const invalid = editor();
        invalid.context.form.quantity = value;
        await invalid.context.save();
        assert.ok(invalid.context.errors.quantity);
        assert.equal(invalid.requests.length, 0);
        assert.deepEqual(invalid.events, []);
    }
});

test("un conflicto carga los valores actuales para revisarlos sin reintentar ni ocultar el editor", async () => {
    const {context, requests, events} = editor({result: {bool: false, code: 409, data: {msg: "El inventario cambió", data: {quantity: 5, minimum_stock: 3}}}});
    await context.save();
    assert.deepEqual(context.original, {quantity: 5, minimum_stock: 3});
    assert.deepEqual(context.form, {quantity: 5, minimum_stock: 3, reason: "Conteo físico"});
    assert.equal(context.error, "El inventario cambió");
    assert.equal(context.changed, false);
    assert.equal(context.busy, false);
    assert.equal(requests.length, 1);
    assert.deepEqual(events, ["confirm", "loading", "close"]);
});

test("editar solo el mínimo conserva un saldo negativo existente", async () => {
    const {context, requests} = editor({result: {bool: true, data: {bool: true, data: {quantity: -2, minimum_stock: 1}}}});
    context.original = {quantity: -2, minimum_stock: 6};
    context.form = {quantity: -2, minimum_stock: 1, reason: "Actualizar mínimo"};
    await context.save();
    assert.equal(requests[0].data.quantity, -2);
    assert.equal(requests[0].data.minimum_stock, 1);
    assert.deepEqual(context.errors, {});
});

test("desde alertas se intercambian las modales sin apilarlas y al cancelar se vuelve una sola vez", async () => {
    const {context, events, requests} = editor();
    await context.open({record: {id: 8, name: "Tuerca", stock_quantity: 3, minimum_stock: 4}, warehouse: {code: 2}, fromAlerts: true});
    assert.deepEqual(events, ["hide:inventoryStockAlertsModal", "show:inventoryStockEditorModal"]);
    assert.deepEqual(context.original, {quantity: 3, minimum_stock: 4});
    context.onHidden();
    context.onHidden();
    assert.deepEqual(events.slice(2), ["show:inventoryStockAlertsModal"]);
    assert.equal(requests.length, 0);
});

function inventoryMethods() {
    const source = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        dependencies[match[1].replace("* as ", "").replace(/[{}\s]/g, "")] = {};
    }
    return new Function(...Object.keys(dependencies), source.replace(/^import .*;\s*$/gm, "").replace("export default", "return"))(...Object.values(dependencies)).methods;
}

test("editar desde alertas identifica el producto y el almacén, y respeta el permiso de operar", async () => {
    const methods = inventoryMethods();
    const calls = [];
    const context = {canUpdateStock: false, filters: {warehouse: {code: 2}}, $refs: {stockEditor: {open: data => calls.push(data)}}};
    const alert = {id: 100, quantity: 3, minimum_stock: 4, warehouse_item: {item: {id: 7, name: "Perno"}}};
    methods.editStock.call(context, alert, true);
    assert.equal(calls.length, 0);
    context.canUpdateStock = true;
    methods.editStock.call(context, alert, true);
    assert.deepEqual(calls[0], {record: {id: 7, name: "Perno", stock_quantity: 3, minimum_stock: 4}, warehouse: {code: 2}, fromAlerts: true});
});

test("el refresco consulta primero stock y luego alertas, y descarta el regreso si cambió el almacén", async () => {
    const methods = inventoryMethods();
    const events = [];
    const context = {filters: {warehouse: {code: 2}}, async listStock() { events.push("stock"); }, async listStockAlerts() { events.push("alerts"); }};
    assert.equal(await methods.refreshEditedStock.call(context, {warehouseId: 2, fromAlerts: true}), true);
    assert.deepEqual(events, ["stock", "alerts"]);
    context.listStock = async () => { context.filters.warehouse = {code: 3}; };
    assert.equal(await methods.refreshEditedStock.call(context, {warehouseId: 2, fromAlerts: true}), false);
    assert.deepEqual(events, ["stock", "alerts"]);
});
