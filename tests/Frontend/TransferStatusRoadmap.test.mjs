import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, h} from "vue";
import {renderToString} from "vue/server-renderer";
import {compareNumbers, normalizeNumber, isNumber, quantityParts} from "../../resources/js/Shared/Numbers.js";

function component(path, dependencies = {}) {
    const {descriptor, errors} = parse(readFileSync(path, "utf8"));
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const options = new Function(...Object.keys(dependencies), script)(...Object.values(dependencies));
    return {...options, render: compile(descriptor.template.content)};
}

const roadmap = component("resources/js/System/Components/Generics/StatusRoadmap.vue");
const catalogTabs = component("resources/js/Shared/AppTabs.vue");
const quantityValue = component("resources/js/System/Components/Generics/QuantityValue.vue", {compareNumbers, quantityParts});
const events = [
    {id: 10, label: "Por enviar", tone: "warning", occurred_at: "2026-11-17T09:00:00Z", user_name: "Ana"},
    {id: 11, label: "En tránsito", tone: "info", occurred_at: "2026-11-17T09:05:00Z", user_name: "Luis"},
    {id: 12, label: "Recepción completa registrada", tone: "success", occurred_at: "2026-11-17T09:10:00Z", user_name: "María"}
];

test("el recorrido muestra fechas y responsables de cada evento y señala únicamente el último como actual", async () => {
    const html = await renderToString(h(roadmap, {events, formatDate: value => `Fecha ${value}`}));
    assert.match(html, /<th scope="col">Fecha de registro<\/th><th scope="col">Responsable<\/th>/);
    const entries = [...html.matchAll(/<tr\b[^>]*class="br-status-roadmap__item"[^>]*>[\s\S]*?<\/tr>/g)].map(([entry]) => entry);
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
    assert.doesNotMatch(empty, /<table/);
});

function detailComponent() {
    const placeholder = {render: () => h("span")};
    let key = 0;
    return component("resources/js/System/Pages/Warehouses/inventory/TransferDetailModal.vue", {
        FormModal: {render() { return h("div", this.$slots.body?.()); }},
        ProductIdentity: placeholder, QuantityValue: quantityValue, InputText: placeholder, InputNumber: placeholder,
        Loader: placeholder, StatusRoadmap: roadmap, AppTabs: catalogTabs, compareNumbers, normalizeNumber, isNumber,
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
        assert.match(html, /<dt>Enviado por:<\/dt><dd>Ana<\/dd>/);
        assert.match(html, /<dt>Registrado por:<\/dt><dd>Creador<\/dd>/);
        assert.ok(html.includes(`<dt>Recepciones registradas por:</dt><dd>${expected}</dd>`));
        assert.match(html, /Recorrido del traslado/);
        assert.match(html, /role="tablist"/);
        for(const tab of ["products", "receipts", "history"]) {
            assert.ok(html.includes(`id="inventory-transfer-detail-tab-trigger-${tab}"`));
        }
    }
});

test("los datos de cabecera siguen visibles al consultar el historial y Productos conserva sus cantidades", async () => {
    const detail = detailComponent();
    const html = await renderToString(h({...detail, data() { return {...detail.data(), activeTab: "history"}; }}, {
        transfer: {
            reference: "TRAS-1", status: "received", status_history: events,
            items: [{id: 1, sent_quantity: "2.00", received_quantity: "2.00", pending_quantity: "0.00",
                resolved_quantity: 0, item: {name: "Producto", measurement_unit: {abbreviation: "UND"}}}]
        },
        status: {tone: "success", label: "Recibido"}, formatDate: value => value || ""
    }));
    assert.doesNotMatch(html, /inventory-transfer-detail-tab-trigger-summary/);
    const products = html.match(/<section\b[^>]*aria-label="Productos del traslado"[^>]*>/)?.[0];
    assert.ok(products);
    assert.match(products, /style="display:none;"/);
    // Las utilidades display de Bootstrap usan !important y anularían v-show.
    assert.doesNotMatch(products, /class="[^"]*\bd-(?:grid|flex|block)\b/);
    const header = html.match(/<header\b[^>]*aria-label="Datos del traslado"[^>]*>[\s\S]*?<\/header>/)?.[0];
    assert.ok(header);
    assert.doesNotMatch(header, /display:none/);
    assert.doesNotMatch(header, /fa-right-long|Fecha de registro/);
    assert.ok(html.indexOf(header) < html.indexOf('role="tablist"'));
    for(const field of ["Referencia:", "TRAS-1", "Almacén de origen:", "Almacén de destino:", "Estado:", "Recibido", "Motivo del traslado:", "Registrado por:"]) {
        assert.ok(header.includes(field), field);
    }
    assert.match(html, /fg-tabs/);
    assert.equal((html.match(/<i class="fa-solid/g) || []).length, 3);
    assert.equal((html.match(/class="text-muted">—(?:<!--v-if-->)?<\/span>/g) || []).length, 2);
    assert.equal((html.match(/class="br-quantity-value__unit"> UND<\/small>/g) || []).length, 2);
    for(const description of ["Cantidades y estado", "Ingresos y diferencias", "Fechas y responsables"]) {
        assert.ok(!html.includes(description));
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
    assert.equal(vm.activeTab, "products");
    vm.fillPending(line, true);
    assert.equal(vm.quantities[1], 6);
    assert.equal(vm.isFull(line), true);
    vm.fillPending(line, false);
    assert.equal(vm.quantities[1], 0);
    vm.quantities[1] = 2;
    vm.activeTab = "history";
    assert.equal(vm.quantities[1], 2);
    vm.activeTab = "products";
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
