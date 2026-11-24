import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import axios from "axios";
import {createOptionSearch, fetchOptions, select2Ajax} from "../../resources/js/Shared/Options.js";

test("Vue Select y Select2 marcan la selección persistente con un indicador distinto del foco", () => {
    const components = readFileSync(new URL("../../resources/css/Shared/components.css", import.meta.url), "utf8");
    const overlay = readFileSync(new URL("../../resources/css/Shared/select-overlay.css", import.meta.url), "utf8");
    assert.match(components, /\.select2-results__option\[aria-selected="true"\]::after[^}]*content: "✓"/);
    assert.match(components, /\.select2-results__option\[aria-selected="true"\][^{}]*\{ border-inline-start-color: var\(--fg-primary\)/);
    assert.match(components, /\.select2-results__option--highlighted\[aria-selected\][^{}]*\{ border-inline-start-color: var\(--fg-border-strong\)/);
    assert.match(components, /\.vs__dropdown-option--selected::after[^}]*content: "✓"/);
    assert.match(overlay, /\.vs__dropdown-option--selected::after[^}]*content: "✓"/);

});

test("las opciones solicitan la entidad en su origen y Select2 conserva los datos de cada opción", async ({mock}) => {
    const request = mock.method(axios, "get", async () => ({data: {
        bool: true, data: {records: [{id: 7, name: "Cliente", document_number: "123"}], page: 2, has_more: true}
    }}));
    const result = await fetchOptions({origin: "sales-list", entity: "customers", search: " 123 ", page: 2});
    const [url, config] = request.mock.calls[0].arguments;
    assert.equal(url, "/options/sales-list/customers");
    assert.deepEqual(config.params, {search: "123", page: 2});
    const ajax = select2Ajax({origin: "sales-list", entity: "customers"});
    assert.deepEqual(ajax.processResults(result), {
        results: [{id: 7, name: "Cliente", document_number: "123", text: "Cliente"}],
        pagination: {more: true}
    });
});

test("una búsqueda nueva cancela la anterior y la paginación conserva el término vigente", async ({mock}) => {
    const requests = [];
    mock.method(axios, "get", (url, config) => new Promise(resolve => {
        requests.push({config, resolve});
    }));
    mock.timers.enable({apis: ["setTimeout"]});
    const mixin = createOptionSearch("customers");
    const page = {optionOrigin: "sales-create", options: {customers: {records: [], statuses: ["active"]}}, ...mixin.data()};
    for(const [name, method] of Object.entries(mixin.methods)) page[name] = method.bind(page);

    const finish = (request, records, page, hasMore) => request.resolve({
        data: {bool: true, data: {records, page, has_more: hasMore}}
    });
    const oldRequest = page.openOptions("customers");
    page.searchOptions("customers", "Cliente nuevo");
    assert.equal(requests[0].config.signal.aborted, true);
    assert.equal(page.loadMoreOptions("customers"), undefined);
    mock.timers.tick(250);

    finish(requests[1], [{id: 2}], 1, true);
    finish(requests[0], [{id: 1}], 1, false);
    await oldRequest;
    assert.deepEqual(page.options.customers, {records: [{id: 2}], statuses: ["active"]});

    const nextPage = page.loadMoreOptions("customers");
    assert.deepEqual(requests[2].config.params, {search: "Cliente nuevo", page: 2});
    finish(requests[2], [{id: 3}], 2, false);
    await nextPage;
    assert.deepEqual(page.options.customers.records, [{id: 2}, {id: 3}]);
    assert.equal(page.remoteOptions.customers.loading, false);
    assert.equal(page.remoteOptions.customers.hasMore, false);
});

test("cerrar un selector remoto cancela la consulta y permite volver a abrirlo", async ({mock}) => {
    const requests = [];
    mock.method(axios, "get", (url, config) => new Promise((resolve, reject) => {
        requests.push({url, config, resolve, reject});
    }));
    const mixin = createOptionSearch("items");
    const page = {optionOrigin: "inventory-transfers", options: {}, ...mixin.data()};
    for(const [name, method] of Object.entries(mixin.methods)) page[name] = method.bind(page);

    const pending = page.openOptions("items");
    page.resetOptions("items");
    assert.equal(requests[0].config.signal.aborted, true);
    const response = {data: {bool: true, data: {records: [{code: 7, label: "Perno"}], page: 1, has_more: false}}};
    requests[0].resolve(response);
    await pending;
    assert.deepEqual(page.options.items.records, []);

    const failed = page.openOptions("items");
    assert.equal(requests[1].url, "/options/inventory-transfers/items");
    requests[1].reject(new Error("Temporal"));
    await failed;
    assert.equal(page.remoteOptions.items.error, true);
    const retry = page.openOptions("items");
    requests[2].resolve(response);
    await retry;
    assert.equal(page.remoteOptions.items.error, false);
    assert.deepEqual(page.options.items.records, response.data.data.records);
});
