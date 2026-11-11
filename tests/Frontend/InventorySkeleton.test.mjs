import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, h} from "vue";
import {renderToString} from "vue/server-renderer";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");
const inventory = "resources/js/System/Pages/Warehouses/inventory/";

test("las cinco secciones de inventario tienen una vista y entrada JS propias", () => {
    const mount = read(`${inventory}mount.js`);

    assert.doesNotMatch(mount, /InventoryPage\.vue/);
    for(const [name, page] of [
        ["stock", "stock"],
        ["transfers", "transfers"],
        ["guides", "guides"],
        ["kardex", "kardex"],
        ["valued-kardex", "valued"]
    ]) {
        const entry = read(`${inventory}${name}.js`);
        const view = read(`${inventory}${name}.vue`);

        assert.match(entry, new RegExp(`import App from "\\./${name}\\.vue"`));
        assert.match(entry, new RegExp(`mountInventoryPage\\(App, "${page}"\\)`));
        assert.match(view, /<InventorySkeleton v-if="initialLoading" :page="page"\/>/);
        assert.match(view, /<section v-else class="br-inventory">/);
        assert.match(view, /import inventoryPage from "\.\/inventoryState\.js"/);
    }
});

test("Inventario cambia del skeleton al listado al terminar initParams", () => {
    const state = read(`${inventory}inventoryState.js`);
    const css = read("resources/css/System/Pages/Warehouses/inventory/skeleton.css");

    assert.match(state, /try \{[\s\S]*?await this\.initParams\(\);[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false;[\s\S]*?await this\.listCurrentView\(\{\}\);/);
    assert.doesNotMatch(state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts(")), /listStockAlerts/);
    assert.match(css, /prefers-reduced-motion: reduce/);
});

test("cada skeleton conserva el orden de filtros, acciones y columnas de su listado", async () => {
    const {descriptor, errors} = parse(read(`${inventory}InventorySkeleton.vue`));
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;\s*$/m, "").replace("export default", "return");
    const options = new Function("Loader", script)({render: () => h("div", {class: "br-loader"})});
    const skeleton = {...options, render: compile(descriptor.template.content)};
    const warehouse = "Almacén de trabajo";
    const kardexFilters = [warehouse, "Producto", "Movimiento", "Desde", "Hasta", "Realizado por", "Categoría", "Marca"];

    for(const [page, filters, actions] of [
        ["stock", [warehouse, "Producto", "Estado del stock", "Stock mínimo", "Último movimiento", "Categoría", "Marca"], ["Acciones", "Registrar operación"]],
        ["transfers", [warehouse, "Producto"], ["Registrar traslado", "Acciones"]],
        ["guides", [warehouse, "Tipo de guía", "Desde", "Hasta"], ["Acciones"]],
        ["kardex", kardexFilters, ["Acciones"]],
        ["valued", kardexFilters, ["Acciones"]]
    ]) {
        const html = await renderToString(h(skeleton, {page}));
        const view = read(`${inventory}${page === "valued" ? "valued-kardex" : page}.vue`);
        const tableClass = options.computed.tableClass.call({page}).split(" ").at(-1);
        const table = [...view.matchAll(/<table\b[^>]*>[\s\S]*?<\/table>/g)].map(([source]) => source)
            .find(source => source.match(/^<table class="([^"]+)"/)?.[1].split(" ").includes(tableClass));
        assert.ok(table, page);

        const headings = source => [...source.matchAll(/<th\b[^>]*>([\s\S]*?)<\/th>/g)]
            .map(([, heading]) => heading.replace(/<[^>]*>/g, "").trim());
        const columns = source => [...source.matchAll(/<col class="([^"]+)"/g)].map(([, column]) => column);
        assert.deepEqual(headings(html), headings(table), page);
        assert.deepEqual(columns(html), columns(table), page);
        assert.match(html, new RegExp(`<table class="[^"]*\\b${tableClass}\\b`), page);
        assert.match(html, new RegExp(`<tr class="br-table-state"><td colspan="${headings(table).length}" class="py-4"><div class="br-loader">`), page);
        assert.deepEqual([...html.matchAll(/br-inventory-skeleton__label">([^<]+)<\/span>/g)].map(([, label]) => label), filters, page);
        assert.deepEqual([...html.matchAll(/<span>(Acciones|Registrar operación|Registrar traslado)<\/span>/g)].map(([, label]) => label), actions, page);
        assert.equal([...html.matchAll(/br-inventory-skeleton__button--icon/g)].length, page === "stock" ? 3 : 2, page);
        assert.match(html, /role="status" aria-live="polite"/, page);
        assert.doesNotMatch(html, /<button|<input|<select|br-list-footer/, page);
    }
});
