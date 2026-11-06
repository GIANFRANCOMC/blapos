import assert from "node:assert/strict";
import {readFileSync, readdirSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, parse as parseTemplate} from "@vue/compiler-dom";
import * as Vue from "vue";
import {renderToString} from "vue/server-renderer";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("control de stock muestra el total filtrado a la derecha del paginador", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const paginator = read("resources/js/System/Components/Paginator.vue");
    const count = read("resources/js/System/Components/Generics/ListResultCount.vue");
    const skeleton = read("resources/js/System/Pages/Warehouses/inventory/InventorySkeleton.vue");
    const styles = read("resources/css/System/br-branding/81-core-roles-entities.css");

    assert.match(page, /<Paginator\s+v-if="filters\.warehouse\?\.code"[\s\S]*?:pagination="currentRecords"[\s\S]*?:loading="isCurrentViewLoading"/);
    assert.match(paginator, /<ListResultCount :pagination="pagination" :loading="loading"\/>/);
    assert.match(count, /Resultado:[\s\S]*?formatNumber\(total\)[\s\S]*?total === 1 \? singular : plural/);
    assert.doesNotMatch(count, /Mostrando|pagination\.from|pagination\.to/);
    assert.match(count, /total\(\) \{[\s\S]*?this\.pagination\?\.total/);
    assert.match(count, /<p v-if="!loading"[^>]*aria-live="polite"/);
    assert.match(paginator, /<div v-if="!loading" class="br-list-footer"/);
    assert.doesNotMatch(skeleton, /br-list-footer|br-inventory-skeleton__(?:pagination|result-count)/);
    assert.doesNotMatch(styles, /\.br-list-result-count--loading/);
    assert.match(styles, /\.br-list-footer \{[^}]*display: grid;\s*grid-template-columns: minmax\(0, 1fr\) auto minmax\(0, 1fr\);[^}]*margin-top: var\(--br-list-pagination-gap\);/);
    assert.match(styles, /\.br-list-footer > \.br-pagination \{\s*grid-column: 2;\s*justify-self: center;/);
    assert.match(styles, /\.br-list-footer > \.br-list-result-count \{\s*grid-column: 3;\s*justify-self: end;/);
    assert.match(styles, /@container br-list-pagination \(max-width: 44rem\)[\s\S]*?grid-column: 1 \/ -1;/);
});

function component(file, dependencies = {}) {
    const {descriptor} = parse(read(file));
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const result = new Function(...Object.keys(dependencies), script)(...Object.values(dependencies));
    result.render = new Function("Vue", compile(descriptor.template.content, {mode: "function"}).code)(Vue);
    return result;
}

test("el pie se oculta al cargar y muestra el total al terminar, incluso con cero resultados", async () => {
    const ListResultCount = component("resources/js/System/Components/Generics/ListResultCount.vue");
    const Paginator = component("resources/js/System/Components/Paginator.vue", {ListResultCount});
    const links = [{url: null, label: "Anterior"}, {url: "/items?page=1", label: "1", active: true}, {url: null, label: "Siguiente"}];
    for(const [total, loading, pageLinks] of [[0, false, []], [1, false, links], [75, false, links], [75, true, links]]) {
        const html = await renderToString(Vue.createSSRApp(Paginator, {
            pagination: {total, data: [{id: 1}]}, loading, links: pageLinks
        }));
        if(loading) {
            assert.doesNotMatch(html, /br-list-footer|br-pagination|br-list-result-count|Resultado:|<button/);
            continue;
        }
        assert.match(html, new RegExp(`<strong>${total}</strong>`));
        assert.match(html, total === 1 ? /<span>registro<\/span>/ : /<span>registros<\/span>/);
        assert.doesNotMatch(html, /aria-hidden="true"|visibility:\s*hidden/);
    }
    const countWhileLoading = await renderToString(Vue.createSSRApp(ListResultCount, {pagination: {total: 75}, loading: true}));
    assert.doesNotMatch(countWhileLoading, /Resultado:|75|br-list-result-count/);
});

test("todos los listados paginados comparten el pie, total y estado de carga sin ocultarlo al quedar vacíos", () => {
    function files(directory) {
        return readdirSync(new URL(`../../${directory}/`, import.meta.url), {withFileTypes: true}).flatMap(entry => {
            const file = `${directory}/${entry.name}`;
            return entry.isDirectory() ? files(file) : [file];
        });
    }
    let usages = 0;
    for(const file of files("resources/js/System").filter(file => file.endsWith(".vue"))) {
        const {descriptor} = parse(read(file));
        if(!descriptor.template) continue;
        function walk(node, parents = []) {
            if(node.tag === "Paginator") {
                usages++;
                for(const name of ["pagination", "loading"]) {
                    assert.ok(node.props.some(p => p.name === "bind" && p.arg?.content === name), `${file}: falta ${name}`);
                }
                for(const parent of [...parents, node]) {
                    assert.ok(!parent.props?.some(p => ["if", "else-if"].includes(p.name) && /\.total\s*>|\.data\??\.length/.test(p.exp?.content)), `${file}: pie oculto sin resultados`);
                    assert.ok(!parent.props?.some(p => p.name === "class" && /table-responsive|\bmt-[1-5]\b|d-none/.test(p.value?.content)), `${file}: pie dentro de scroll o espaciado adicional`);
                }
            }
            for(const child of node.children || []) walk(child, node.type === 1 ? [...parents, node] : parents);
        }
        walk(parseTemplate(descriptor.template.content));
    }
    assert.ok(usages > 0);
});

test("el contador usa registro en singular y registros en plural", async () => {
    const source = read("resources/js/System/Components/Generics/ListResultCount.vue");
    const {descriptor} = parse(source);
    const {default: component} = await import(`data:text/javascript,${encodeURIComponent(descriptor.script.content)}`);

    assert.equal(component.props.singular.default, "registro");
    assert.equal(component.props.plural.default, "registros");
    assert.equal(component.computed.total.call({pagination: {total: 1}}), 1);
    assert.equal(component.computed.total.call({pagination: {total: 50}}), 50);
    assert.equal(component.computed.total.call({pagination: {total: 0}}), 0);
});

test("Guest y el listado central de Platform ocultan su navegación mientras consultan", async () => {
    const GuestPaginator = component("resources/js/Guest/Components/Paginator.vue");
    const hidden = await renderToString(Vue.createSSRApp(GuestPaginator, {loading: true, links: [{url: "/next", label: "2"}]}));
    assert.doesNotMatch(hidden, /<nav|pagination|2<\/a>/);
    const visible = await renderToString(Vue.createSSRApp(GuestPaginator, {loading: false, links: [{url: "/next", label: "2"}]}));
    assert.match(visible, /<nav/);

    const platform = parse(read("resources/js/Platform/pages/TenantIndex.vue")).descriptor;
    let footer;
    function walk(node) {
        if(node.props?.some(prop => prop.name === "class" && prop.value?.content === "platform-pagination")) footer = node;
        for(const child of node.children || []) walk(child);
    }
    walk(parseTemplate(platform.template.content));
    assert.match(footer.props.find(prop => prop.name === "if").exp.content, /!loading/);
});
