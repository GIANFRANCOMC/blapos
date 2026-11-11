import assert from "node:assert/strict";
import test from "node:test";
import axios from "axios";
import {createOptionSearch, fetchOptions, select2Ajax} from "../../resources/js/Shared/Options.js";

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
