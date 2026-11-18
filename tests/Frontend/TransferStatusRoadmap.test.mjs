import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, h} from "vue";
import {renderToString} from "vue/server-renderer";
import {compareNumbers, normalizeNumber, isNumber} from "../../resources/js/Shared/Numbers.js";

function component(path, dependencies = {}) {
    const {descriptor, errors} = parse(readFileSync(path, "utf8"));
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const options = new Function(...Object.keys(dependencies), script)(...Object.values(dependencies));
    return {...options, render: compile(descriptor.template.content)};
}

const roadmap = component("resources/js/System/Components/Generics/StatusRoadmap.vue");
const events = [
    {id: 10, label: "Por enviar", tone: "warning", occurred_at: "2026-11-17T09:00:00Z", user_name: "Ana"},
    {id: 11, label: "En tránsito", tone: "info", occurred_at: "2026-11-17T09:05:00Z", user_name: "Luis"},
    {id: 12, label: "Recepción completa registrada", tone: "success", occurred_at: "2026-11-17T09:10:00Z", user_name: "María"}
];

test("el recorrido muestra fechas y responsables de cada evento y señala únicamente el último como actual", async () => {
    const html = await renderToString(h(roadmap, {events, formatDate: value => `Fecha ${value}`}));
    const entries = [...html.matchAll(/<li\b[^>]*>[\s\S]*?<\/li>/g)].map(([entry]) => entry);
    assert.equal(entries.length, 3);
    events.forEach((event, index) => {
        assert.ok(entries[index].includes(event.label));
        assert.ok(entries[index].includes(event.user_name));
        assert.ok(entries[index].includes(`<time datetime="${event.occurred_at}">Fecha ${event.occurred_at}</time>`));
        assert.equal(entries[index].includes('aria-current="step"'), index === 2);
    });
});

test("el recorrido no inventa eventos previos y la ausencia de historial es explícita", async () => {
    const accepted = await renderToString(h(roadmap, {events: [events[2]], formatDate: value => value}));
    assert.match(accepted, /Recepción completa registrada/);
    assert.doesNotMatch(accepted, /Por enviar|En tránsito/);
    const empty = await renderToString(h(roadmap, {events: [], formatDate: value => value}));
    assert.match(empty, /No hay cambios de estado registrados/);
    assert.doesNotMatch(empty, /<li/);
});

function detailComponent() {
    const placeholder = {render: () => h("span")};
    let key = 0;
    return component("resources/js/System/Pages/Warehouses/inventory/TransferDetailModal.vue", {
        FormModal: {render() { return h("div", this.$slots.body?.()); }},
        ProductIdentity: placeholder, QuantityValue: placeholder, InputText: placeholder, InputNumber: placeholder,
        Loader: placeholder, StatusRoadmap: roadmap, compareNumbers, normalizeNumber, isNumber,
        uuid: () => `00000000-0000-4000-8000-${String(++key).padStart(12, "0")}`
    });
}

test("el detalle distingue creador, responsable de envío y responsables de recepción", async () => {
    const detail = detailComponent();
    for(const [status, receiver, expected] of [
        ["received", "María", "María"],
        ["awaiting_dispatch", null, "Sin recepción registrada"],
        ["canceled", null, "No recibido"],
        ["partially_received", "María, Luis", "María, Luis"]
    ]) {
        const html = await renderToString(h(detail, {
            transfer: {reference: "TRAS-1", status, user: {name: "Creador"}, sender_name: "Ana", receiver_name: receiver, items: [], status_history: []},
            status: {tone: "info", label: status}, formatDate: value => value || ""
        }));
        assert.match(html, /<dt>Enviado por<\/dt><dd>Ana<\/dd>/);
        assert.match(html, /<dt>Registrado por<\/dt><dd>Creador<\/dd>/);
        assert.ok(html.includes(`<dt>Recepciones registradas por</dt><dd>${expected}</dd>`));
        assert.match(html, /Recorrido del traslado/);
    }
});

test("el checkbox completa solo lo pendiente y los reintentos conservan la clave de la recepción", () => {
    const options = detailComponent();
    const emitted = [];
    const line = {id: 1, quantity: 10, received_quantity: 4, pending_quantity: 6};
    const vm = {...options.data(), transfer: {version: 3, items: [line]}, savingStatus: false, $emit: (...args) => emitted.push(args)};
    for(const [name, method] of Object.entries(options.methods)) vm[name] = method.bind(vm);
    for(const [name, getter] of Object.entries(options.computed)) Object.defineProperty(vm, name, {get: () => getter.call(vm)});
    vm.startMode("receipt");
    vm.fillPending(line, true);
    assert.equal(vm.quantities[1], 6);
    assert.equal(vm.isFull(line), true);
    vm.fillPending(line, false);
    assert.equal(vm.quantities[1], 0);
    vm.quantities[1] = 2;
    vm.submitOperation();
    vm.submitOperation();
    assert.equal(emitted[0][1].data.request_key, emitted[1][1].data.request_key);
    assert.equal(emitted[0][1].data.expected_version, 3);
    assert.deepEqual(emitted[0][1].data.items, [{id: 1, quantity: 2}]);
    vm.quantities[1] = 3;
    vm.submitOperation();
    assert.notEqual(emitted[2][1].data.request_key, emitted[0][1].data.request_key);
    assert.equal(line.received_quantity, 4);
    assert.equal(line.quantity, 10);
    vm.quantities[1] = 7;
    assert.ok(vm.quantityError);
    assert.equal(vm.canSubmit, false);
    vm.startMode("loss");
    vm.quantities[1] = 1;
    assert.equal(vm.canSubmit, false);
    vm.reason = "Faltante verificado";
    assert.equal(vm.canSubmit, true);
});
