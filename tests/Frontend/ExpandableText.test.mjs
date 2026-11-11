import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const {descriptor, errors} = parse(read("resources/js/System/Components/Generics/ExpandableText.vue"));

test("el catálogo usa el control compartido para las descripciones de sus tablas", () => {
    assert.deepEqual(errors, []);
    assert.match(descriptor.template.content, /v-if="hasOverflow"[\s\S]*?:aria-expanded="expanded"[\s\S]*?expanded \? "Ver menos" : "Ver más"/);
    assert.match(descriptor.styles[0].content, /text-overflow: ellipsis;\s*white-space: nowrap;/);
    assert.match(descriptor.styles[0].content, /overflow-wrap: anywhere;\s*white-space: pre-line;/);
    for(const page of ["products", "services", "subscriptions", "categories", "brands"]) {
        assert.match(read(`resources/js/System/Pages/Catalogs/${page}/main.vue`),
            /<ExpandableText v-if="record\.description" class="br-entity-table__description" :text="record\.description"\/>/);
    }
});

test("la descripción alterna y vuelve a medir al cambiar el ancho o el texto", () => {
    const container = {clientWidth: 160};
    const content = {scrollWidth: 160};
    let resize;
    let observed;
    let disconnected = false;
    class ResizeObserver {
        constructor(callback) { resize = callback; }
        observe(element) { observed = element; }
        disconnect() { disconnected = true; }
    }

    const script = descriptor.script.content.replace(/^import .*;\s*$/m, "").replace("export default", "return");
    const component = new Function("ResizeObserver", "document", script)(ResizeObserver, {});
    const instance = {...component.data(), $refs: {container, content}, $nextTick: callback => callback()};
    for(const [name, method] of Object.entries(component.methods)) instance[name] = method.bind(instance);

    component.mounted.call(instance);
    assert.equal(observed, container);
    assert.equal(instance.hasOverflow, false);
    container.clientWidth = 100;
    resize();
    assert.equal(instance.hasOverflow, true);
    instance.toggle();
    assert.equal(instance.expanded, true);
    container.clientWidth = 180;
    resize();
    assert.equal(instance.hasOverflow, true);
    instance.toggle();
    assert.equal(instance.expanded, false);
    assert.equal(instance.hasOverflow, false);

    content.scrollWidth = 300;
    component.watch.text.call(instance);
    assert.equal(instance.hasOverflow, true);
    instance.toggle();
    content.scrollWidth = 180;
    component.watch.text.call(instance);
    assert.equal(instance.expanded, false);
    assert.equal(instance.hasOverflow, false);
    component.beforeUnmount.call(instance);
    assert.equal(disconnected, true);
});
