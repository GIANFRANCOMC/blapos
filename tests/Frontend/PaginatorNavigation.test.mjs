import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const source = read("resources/js/System/Components/Paginator.vue");
const {descriptor} = parse(source);
const paginator = new Function("ListResultCount", descriptor.script.content
    .replace(/^import .*;\s*$/gm, "")
    .replace("export default", "return"))({});

const context = () => {
    const instance = {align: "start"};
    for(const [name, method] of Object.entries(paginator.methods)) instance[name] = method.bind(instance);
    return instance;
};

test("el paginador compartido muestra primera y última página y bloquea extremos", () => {
    globalThis.window = {location: {origin: "http://demo.blapos.test"}};
    const instance = context();
    const links = instance.buildWindowedLinks([
        {url: null, label: "&laquo; Previous", active: false},
        {url: "/items?page=1", label: "1", active: true},
        {url: "/items?page=2", label: "2", active: false},
        {url: null, label: "...", active: false},
        {url: "/items?page=100", label: "100", active: false},
        {url: "/items?page=2", label: "Next &raquo;", active: false}
    ]);

    assert.equal(instance.displayLabel(links[0], 0), "Primera");
    assert.equal(instance.displayLabel(links.at(-1), links.length - 1), "Última");
    assert.equal(instance.navigable(links[0]), false);
    assert.equal(instance.navigable(links.at(-1)), true);
    assert.equal(links.at(-1).url, "/items?page=100");
    const emitted = [];
    instance.$emit = (event, payload) => emitted.push({event, payload});
    instance.onPageClick(links[0]);
    instance.onPageClick(links.find(link => link.active));
    assert.equal(emitted.length, 0);
    instance.onPageClick(links.at(-1));
    assert.equal(emitted.length, 1);

    const onlyPage = instance.buildWindowedLinks([
        {url: null, label: "&laquo; Previous", active: false},
        {url: "/items?page=1", label: "1", active: true},
        {url: null, label: "Next &raquo;", active: false}
    ]);
    assert.equal(onlyPage[0].url, null);
    assert.equal(onlyPage.at(-1).lastPage, 1);
    assert.equal(onlyPage.at(-1).url, null);
    assert.equal(onlyPage.filter(link => link.active).length, 1);

    const finalPage = instance.buildWindowedLinks([
        {url: "/items?page=99", label: "&laquo; Previous", active: false},
        {url: "/items?page=1", label: "1", active: false},
        {url: null, label: "...", active: false},
        {url: "/items?page=100", label: "100", active: true},
        {url: null, label: "Next &raquo;", active: false}
    ]);
    assert.equal(finalPage[0].url, "/items?page=1");
    assert.equal(finalPage.at(-1).url, null);
    assert.equal(instance.navigable(finalPage.at(-1)), false);

    assert.match(source, /<button[\s\S]*?:disabled="!navigable\(link\)"/);
    assert.doesNotMatch(source, /href="javascript:void\(0\);"/);
});

test("el paginador es compacto sin alterar globalmente los botones deshabilitados", () => {
    const styles = read("resources/css/Shared/components.css");
    assert.match(styles, /\.fg-pagination__links \{[^}]*flex-wrap: wrap;[^}]*gap: 4px;/);
    assert.match(styles, /\.fg-pagination__button:disabled:not\(\[aria-current\]\)/);
    assert.match(styles, /\.fg-pagination__button\[aria-current="page"\] \{[^}]*background: var\(--fg-primary\)/);

});
