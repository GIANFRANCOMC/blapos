import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@babel/parser";
import {emptyPagination, currentQueryRoute} from "../../resources/js/System/Helpers/ListState.js";

const root = "resources/js/System/Pages/";
const read = file => readFileSync(new URL(`../../${file}`, import.meta.url), "utf8");
const noop = () => {};

function method(file, name, dependencies) {
    const source = read(file);
    const script = file.endsWith(".vue") ? source.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1] : source;
    const body = parse(script, {sourceType: "module"}).program.body;
    const component = body.find(node => node.type === "ExportDefaultDeclaration")?.declaration
        || body.find(node => node.type === "ExportNamedDeclaration").declaration.declarations[0].init;
    const methods = component.properties.find(node => node.key?.name === "methods")?.value.properties || [];
    const property = [...component.properties, ...methods].find(node => node.key?.name === name);
    const node = property?.value || property;
    assert.ok(node, `${file}: ${name}`);
    const params = node.params.map(param => script.slice(param.start, param.end)).join(",");
    return new Function(...Object.keys(dependencies), `return async function(${params}) ${script.slice(node.body.start, node.body.end)}`)(...Object.values(dependencies));
}

function deferred() {
    let resolve, reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return {promise, resolve, reject};
}

const lists = [
    ...["Assets/assets", "Catalogs/brands", "Catalogs/categories", "Catalogs/products", "Catalogs/services", "Catalogs/subscriptions", "Customers/customers", "Devices/biometric_devices", "Organizations/book_complaints", "Organizations/branches", "Organizations/users"]
        .map(path => [path + "/main.vue", "listEntity", "entityList.records", "entityList.extras.loading", "object"]),
    ...["Assets/assets_management", "Customers/tracking_attendances", "Customers/tracking_notifications", "Customers/tracking_subscriptions"]
        .map(path => [path + "/main.vue", "listEntity", "lists.entity.records", "lists.entity.extras.loading", "object"]),
    ["Sales/sales/list.vue", "listEntity", "lists.entity.records", "lists.entity.extras.loading", "object"],
    ["Catalogs/recipes/main.vue", "listRecipes", "records", "loading"],
    ["Devices/biometric_devices/main.vue", "listEvents", "eventsModal.records", "eventsModal.loading"],
    ...["accounts_payable", "accounts_receivable"].map(path => [`Finance/${path}/main.vue`, "loadAccounts", "accounts", "loading.list"]),
    ["Finance/cash_registers/main.vue", "getSessions", "sessions", "loading.sessions"],
    ["Finance/cash_registers/main.vue", "getMovements", "movements", "loading.movements"],
    ["Finance/cash_registers/main.vue", "getRegisters", "registers", "loading.registers", "array"],
    ["Finance/cash_registers/main.vue", "getSummary", "summary.payments", "loading.summary", "array"],
    ["Finance/misc_expenses/main.vue", "loadExpenses", "expenses", "loading"],
    ["Operations/service_operations/main.vue", "getSessions", "sessions", "loading"],
    ["Operations/service_operations/main.vue", "getBoard", "stations", "loading", "array"],
    ["Operations/service_operations/main.vue", "getStations", "stations", "loading", "array"],
    ["Essentials/reports/main.vue", "consultSettlements", "settlements.records", "settlements.loading", "array"],
    ["Organizations/branches/main.vue", "loadSeriesAudit", "seriesAudit.records", "seriesAudit.loading", "object"],
    ["Organizations/roles/main.vue", "listRoles", "records", "loading", "object"],
    ["Organizations/users/main.vue", "listAuthenticationEvents", "authenticationEvents.records", "authenticationEvents.loading", "object"],
    ["Organizations/user_attendances/main.vue", "getRecords", "records", "loading"],
    ["Purchases/purchases/main.vue", "listPurchases", "records", "loading", "object"],
    ["Purchases/suppliers/main.vue", "listSuppliers", "records", "loading", "object"],
    ["Sales/quotations/main.vue", "listQuotations", "records", "loading.list", "object"],
    ["Sales/sales/deliveries.vue", "listDeliveries", "records", "loading", "object"],
    ...[["listStock", "stockRecords", "loadingStock"], ["listKardex", "kardexRecords", "loadingKardex"], ["listGuides", "guideRecords", "loadingGuides"], ["listStockAlerts", "stockAlerts", "loadingAlerts"]]
        .map(([fn, records, loading]) => ["Warehouses/inventory/inventoryState.js", fn, records, loading, "object"]),
    ["General/master_data/main.vue", "loadRecords", "records", "loading", "array"],
    ["../Helpers/CrudMixin.js", "listEntity", "lists.entity.records", "lists.entity.extras.loading", "object"]
];

const valueAt = (context, path) => path.split(".").reduce((value, key) => value[key], context);
function setAt(context, path, value) {
    const keys = path.split(".");
    const last = keys.pop();
    keys.reduce((target, key) => target[key], context)[last] = value;
}

function contextFor(loadingPath) {
    const routes = {list: "/list", sessions: "/list", deliveries: "/list", data: "/list", board: "/list", stations: "/list", consult: "/list"};
    const entityList = {filters: {filter_by: {code: "all"}, word: "producto"}, extras: {loading: false, route: "/list"}};
    const context = {
        entity: "entity", entityList, lists: {entity: entityList},
        MODULE: {config: {perPage: 10}}, config: {routes, entity: {routes}}, routes,
        sessionConfig: {routes}, movementConfig: {routes}, summaryConfig: {routes}, summary: {}, routeActions: {store: "/list", consult: "/list", seriesAudit: "/list"},
        filters: {word: "producto", warehouse: {code: 1}, productSearch: "producto"},
        eventsModal: {device: {id: 1}}, authenticationEvents: {user: {id: 1}, filters: {}}, seriesAudit: {gaps: [{id: 1}]},
        selectedBranch: {id: 1}, selectedFloor: {id: 1}, selectedStatus: {code: ""}, sessionsRequestSequence: 0, requestVersion: 0, settlements: {},
        loadingStock: false, loadingAlerts: false,
        baseFilters: () => ({}), requestFilters: () => ({}), getListFilters: () => ({}), currentStockAdvancedFilters: () => ({}), currentReportFilters: () => ({}), resourceRoute: () => "/list",
        isDefined: value => value != null && (typeof value !== "object" || !("value" in value) || value.value != null),
        $nextTick: async () => {}, initChart: noop, notify: noop
    };
    if(loadingPath.startsWith("loading.")) context.loading = {};
    return context;
}

test("el estado vacío no comparte filas ni enlaces entre tablas", () => {
    const first = emptyPagination();
    const second = emptyPagination();
    first.data.push({id: 1});
    first.links.push({url: "?page=2"});
    assert.deepEqual(second, {data: [], total: 0, links: [], current_page: 1, last_page: 1, from: null, to: null});
});

test("los filtros actuales sustituyen los incluidos en un enlace anterior, incluso al vaciarlos", () => {
    const route = currentQueryRoute("/list?page=3&product_search=anterior&minimum_status=configured&filter[status]=active#table", {
        product_search: "", minimum_status: null, filter: {status: ""}
    });
    assert.equal(route, "/list?page=3#table");
    assert.equal(currentQueryRoute("/list?page=2&word=viejo", {word: "nuevo"}), "/list?page=2");
    assert.equal(currentQueryRoute("/list", {word: "nuevo"}), "/list");
});

for(const [path, name, recordsPath, loadingPath, argType] of lists) {
    test(`${path}: ${name} limpia antes de buscar o paginar y permanece vacío si falla`, async () => {
        let request;
        let calls = 0;
        const Requests = {
            emptyPagination, config: () => "/list", valid: ({result}) => Boolean(result?.bool && result?.data?.bool),
            get: ({route}) => { calls++; assert.equal(typeof route, "string", "la ruta debe ser texto, incluso al paginar"); return request.promise; }
        };
        const run = method(root + path, name, {
            Requests, Utils: {cloneJson: structuredClone}, Alerts: {tooltips: noop, loading: noop, close: noop, swals: noop, generateAlert: noop},
            MODULE: {config: {perPage: 10}}, CONFIG: {routes: {list: "/list"}}, STOCK_PAGE_SIZE: 10, window: {location: {origin: "https://example.test"}}
        });
        for(const [index, url] of [null, "/list?page=2"].entries()) {
            const context = contextFor(loadingPath);
            const previous = {data: [{id: 1}], total: 25, links: [{url: "?page=2"}]};
            setAt(context, recordsPath, previous);
            request = deferred();
            const pagedEvent = index && !["loadExpenses", "loadAccounts"].includes(name) ? {url} : url;
            const pending = run.call(context, argType === "object" ? {url} : pagedEvent);
            const outcome = pending.then(() => null, error => error);
            assert.equal(calls, index + 1, "debe iniciar la petición");
            assert.equal(valueAt(context, loadingPath), true);
            const cleared = valueAt(context, recordsPath);
            assert.notEqual(cleared, previous);
            assert.deepEqual(argType === "array" ? cleared : cleared.data, []);
            if(argType !== "array") {
                assert.equal(cleared.total, 0);
                assert.deepEqual(cleared.links, []);
            }
            assert.equal(context.filters.word, "producto", "conserva los filtros");
            if(index) request.reject(new Error("fallo de red"));
            else request.resolve({bool: false, data: {msg: "fallo de búsqueda"}});
            const error = await outcome;
            if(error) assert.equal(error.message, "fallo de red");
            const result = valueAt(context, recordsPath);
            assert.deepEqual(argType === "array" ? result : result.data, []);
            if(argType !== "array") assert.equal(result.total, 0);
            assert.equal(valueAt(context, loadingPath), false);
        }
    });
}

test("Platform vacía clientes y metadatos antes de consultar y también ante una excepción", async () => {
    const request = deferred();
    const run = method("resources/js/Platform/pages/TenantIndex.vue", "loadTenants", {
        api: {get: () => request.promise}, errorMessage: () => "fallo"
    });
    const context = {tenants: [{id: 1}], meta: {total: 25, last_page: 3}, filters: {search: "cliente"}, $emit: noop};
    const pending = run.call(context, 2);
    assert.deepEqual(context.tenants, []);
    assert.deepEqual(context.meta, {current_page: 1, last_page: 1, total: 0});
    request.reject(new Error("fallo de red"));
    await pending;
    assert.deepEqual(context.tenants, []);
    assert.equal(context.loading, false);
});

test("el expediente de clientes elimina el resultado anterior al buscar y refrescar, incluso ante errores", async () => {
    for(const name of ["fetchTrackingData", "refreshTrackingData"]) {
        for(const rejected of [false, true]) {
            const request = deferred();
            const run = method(root + "Customers/tracking_customers/main.vue", name, {
                Utils: {getTrackingCustomers: () => request.promise},
                Requests: {valid: ({result}) => Boolean(result?.bool && result?.data?.bool)},
                Alerts: {swals: noop, modals: noop}
            });
            const history = {customers: {1: {customer: {id: 1}, sales: [{id: 7}]}}, customerCurrent: {code: 1}, periodTypeCurrent: {code: "last_7_days"}};
            const context = {
                forms: {entity: {createUpdate: {
                    history, data: {customer: {code: 1}, periodType: {code: "last_7_days"}}, extras: {modals: {default: {id: "filterModal"}}}
                }}},
                validateForm: () => ({bool: true})
            };
            const pending = run.call(context, "getTrackingCustomers");
            assert.deepEqual(history.customers, {});
            if(rejected) {
                request.reject(new Error("fallo de red"));
                await assert.rejects(pending, /fallo de red/);
            } else {
                request.resolve({bool: false});
                await pending;
            }
            assert.deepEqual(history.customers[history.customerCurrent.code] || {}, {});
        }
    }
});

for(const path of ["Catalogs/brands", "Catalogs/categories", "Catalogs/products", "Catalogs/services", "Catalogs/subscriptions", "Organizations/branches", "Organizations/users", "Organizations/roles", "Organizations/companies"]) {
    test(`${path}: el skeleton termina con initParams mientras el primer listado sigue pendiente`, async () => {
        const params = deferred();
        const list = deferred();
        const started = deferred();
        const events = [];
        const run = method(root + path + "/main.vue", "mounted", {
            Utils: {navbarItem: noop}, Alerts: {tooltips: noop, swals: options => events.push(options)},
            document: {getElementById: () => null}
        });
        const load = () => { started.resolve(); return list.promise; };
        const context = {
            config: {entity: {page: {menu: {id: "menu"}}}}, initialLoading: true,
            initParams: () => params.promise, listEntity: load, listRoles: load, initOthers: load,
            $nextTick: async () => {}, initModalListeners: noop, brandForm: {extras: {modals: {default: {id: "brand"}}}}
        };
        const pending = run.call(context);
        assert.equal(context.initialLoading, true);
        assert.deepEqual(events, []);
        params.resolve(true);
        await started.promise;
        assert.equal(context.initialLoading, false);
        assert.deepEqual(events, []);
        list.resolve();
        await pending;
    });
}
