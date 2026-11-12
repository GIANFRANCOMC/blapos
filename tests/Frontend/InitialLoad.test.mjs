import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = file => readFileSync(new URL(`../../${file}`, import.meta.url), "utf8");

test("el skeleton compartido usa el loader habitual en una fila de tabla", () => {
    const skeleton = read("resources/js/System/Components/Generics/PageSkeleton.vue");
    assert.match(skeleton, /import Loader from "@System\/Components\/Loader\.vue"/);
    assert.match(skeleton, /v-if="table"[\s\S]*?<table[\s\S]*?<tr class="br-table-state">[\s\S]*?<Loader\/>/);
});

function inventory(page, events) {
    const source = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        const name = match[1].replace("* as ", "").replace(/[{}\s]/g, "");
        dependencies[name] = {};
    }
    dependencies.Alerts = {swals: options => events.push(options)};
    dependencies.Requests = {config: () => ({})};
    dependencies.navbarItem = () => {};
    const component = new Function(...Object.keys(dependencies), source
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return"))(...Object.values(dependencies));
    const context = {
        page,
        ...component.data.call({page}),
        realWarehouses: [{code: 1}],
        async $nextTick() {},
        async initParams() { return true; }
    };
    context.activeMenuId = component.methods.activeMenuId.bind(context);
    return {context, mount: () => component.mounted.call(context)};
}

test("Control de stock termina el skeleton al finalizar parámetros sin abrir Swal", async () => {
    const events = [];
    const {context, mount} = inventory("stock", events);
    let resolveParams;
    context.initParams = () => new Promise(resolve => { resolveParams = resolve; });
    let resolveList;
    let notifyStarted;
    const started = new Promise(resolve => { notifyStarted = resolve; });
    context.listCurrentView = () => {
        notifyStarted();
        return new Promise(resolve => { resolveList = resolve; });
    };
    const pending = mount();
    assert.equal(context.initialLoading, true);
    assert.deepEqual(events, []);
    resolveParams(true);
    await started;
    assert.equal(context.initialLoading, false);
    resolveList();
    await pending;
    assert.equal(context.initialLoading, false);
    assert.equal(context.appliedFilters.warehouse, context.realWarehouses[0]);
    assert.deepEqual(events, []);
});

test("la carga inicial de inventario libera el skeleton al fallar sin abrir Swal", async () => {
    for(const stage of ["initParams", "listCurrentView"]) {
        const events = [];
        const {context, mount} = inventory("stock", events);
        context.listCurrentView = async () => {};
        context[stage] = async () => { throw new Error("consulta fallida"); };
        await assert.rejects(mount(), /consulta fallida/);
        assert.equal(context.initialLoading, false);
        assert.deepEqual(events, []);
    }
    const events = [];
    const {context, mount} = inventory("kardex", events);
    context.listCurrentView = async () => {};
    await mount();
    assert.equal(context.initialLoading, false);
    assert.deepEqual(events, []);
});

test("el inicializador compartido retira el skeleton y monta el contenido antes de continuar", async () => {
    for(const reject of [false, true]) {
        const events = [];
        const source = read("resources/js/System/Helpers/InitialPageMixin.js");
        const component = new Function("InitialLoadTransition", "PageSkeleton", "Alerts", source
            .replace(/^import .*;\s*$/gm, "").replace("export default", "return"))({}, {}, {swals: options => events.push(options)});
        let finish;
        const params = new Promise((resolve, fail) => { finish = reject ? fail : resolve; });
        const context = {initialLoading: true, async $nextTick() { events.push("mounted"); }};
        const pending = component.methods.loadInitialParams.call(context, () => params);
        assert.equal(context.initialLoading, true);
        assert.deepEqual(events, []);
        if(reject) {
            finish(new Error("fallo de parámetros"));
            await assert.rejects(pending, /fallo de parámetros/);
        } else {
            finish("configurada");
            assert.equal(await pending, "configurada");
        }
        assert.equal(context.initialLoading, false);
        assert.deepEqual(events, ["mounted"]);
    }
});
