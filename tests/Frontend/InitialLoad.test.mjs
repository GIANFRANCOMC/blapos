import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = file => readFileSync(new URL(`../../${file}`, import.meta.url), "utf8");

function factory(events) {
    const source = read("resources/js/System/Helpers/InitialLoad.js")
        .replace(/^import .*;\s*$/gm, "")
        .replace("export function", "function");
    return new Function("Alerts", `${source}\nreturn createInitialLoad;`)({swals: options => events.push(options)});
}

test("la carga inicial admite solo skeleton, solo Swal o ambos y conserva los valores por defecto", () => {
    for(const [options, skeleton, swal] of [
        [undefined, true, true],
        [{skeleton: true}, true, true],
        [{skeleton: true, swal: false}, true, false],
        [{skeleton: false, swal: true}, false, true],
        [{skeleton: true, swal: true}, true, true]
    ]) {
        const events = [];
        const load = factory(events)(options);
        assert.equal(load.skeleton, skeleton);
        assert.equal(load.swal, swal);
        load.start();
        assert.deepEqual(events, swal ? [{type: "initParams"}] : []);
        load.finish();
        assert.deepEqual(events, swal ? [{type: "initParams"}, {show: false}] : []);
    }
});

function inventory(page, events) {
    const source = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const dependencies = {};
    for(const match of source.matchAll(/^import (.+) from ".*";$/gm)) {
        const name = match[1].replace("* as ", "").replace(/[{}\s]/g, "");
        dependencies[name] = {};
    }
    dependencies.createInitialLoad = factory(events);
    dependencies.Requests = {config: () => ({})};
    dependencies.Utils = {navbarItem() {}};
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

test("Control de stock termina skeleton y Swal al finalizar parámetros, antes del listado", async () => {
    const events = [];
    const {context, mount} = inventory("stock", events);
    let resolveList;
    let notifyStarted;
    const started = new Promise(resolve => { notifyStarted = resolve; });
    context.listCurrentView = () => {
        notifyStarted();
        return new Promise(resolve => { resolveList = resolve; });
    };
    const pending = mount();
    await started;
    assert.equal(context.initialLoading, false);
    assert.equal(context.skeletonEnabled, true);
    assert.deepEqual(events, [{type: "initParams"}, {show: false}]);
    resolveList();
    await pending;
    assert.equal(context.initialLoading, false);
    assert.equal(context.warehouseSearchEnabled, true);
    assert.deepEqual(events, [{type: "initParams"}, {show: false}]);
});

test("la carga inicial libera ambos indicadores al fallar y usa ambos en todos los submódulos", async () => {
    for(const stage of ["initParams", "listCurrentView"]) {
        const events = [];
        const {context, mount} = inventory("stock", events);
        context.listCurrentView = async () => {};
        context[stage] = async () => { throw new Error("consulta fallida"); };
        await assert.rejects(mount(), /consulta fallida/);
        assert.equal(context.initialLoading, false);
        assert.deepEqual(events, [{type: "initParams"}, {show: false}]);
    }
    const events = [];
    const {context, mount} = inventory("kardex", events);
    context.listCurrentView = async () => {};
    await mount();
    assert.equal(context.skeletonEnabled, true);
    assert.deepEqual(events, [{type: "initParams"}, {show: false}]);
});

test("el inicializador compartido cierra skeleton y Swal y monta el contenido antes de continuar", async () => {
    for(const reject of [false, true]) {
        const events = [];
        const source = read("resources/js/System/Helpers/InitialPageMixin.js");
        const component = new Function("InitialLoadTransition", "PageSkeleton", "createInitialLoad", source
            .replace(/^import .*;\s*$/gm, "").replace("export default", "return"))({}, {}, factory(events));
        let finish;
        const params = new Promise((resolve, fail) => { finish = reject ? fail : resolve; });
        const context = {initialLoading: true, async $nextTick() { events.push("mounted"); }};
        const pending = component.methods.loadInitialParams.call(context, () => params);
        assert.equal(context.initialLoading, true);
        assert.deepEqual(events, [{type: "initParams"}]);
        if(reject) {
            finish(new Error("fallo de parámetros"));
            await assert.rejects(pending, /fallo de parámetros/);
        } else {
            finish("configurada");
            assert.equal(await pending, "configurada");
        }
        assert.equal(context.initialLoading, false);
        assert.deepEqual(events, [{type: "initParams"}, {show: false}, "mounted"]);
    }
});
