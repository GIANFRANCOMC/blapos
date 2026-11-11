import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {compile} from "@vue/compiler-dom";
import {parse} from "@vue/compiler-sfc";
import * as Vue from "vue";
import {renderToString} from "vue/server-renderer";
import {tooltipOptionsFor} from "../../resources/js/System/Helpers/TooltipOptions.js";

const file = new URL("../../resources/js/Shared/SearchButton.vue", import.meta.url);
const {descriptor} = parse(readFileSync(file, "utf8"));

function searchButton(window = {}) {
    const script = descriptor.script.content.replace(/^import .*;\s*$/m, "").replace("export default", "return");
    const component = new Function("tooltipOptionsFor", "window", script)(
        element => tooltipOptionsFor(element, {}), window
    );
    component.render = new Function("Vue", compile(descriptor.template.content, {mode: "function"}).code)(Vue);
    return component;
}

test("Buscar muestra solo la lupa, conserva un nombre accesible y respeta el estado deshabilitado", async () => {
    const html = await renderToString(Vue.h(searchButton(), {disabled: true}));
    assert.match(html, /<button[^>]*type="button"[^>]*title="Buscar"[^>]*aria-label="Buscar"[^>]*data-bs-toggle="tooltip"[^>]*\bdisabled\b[^>]*>/);
    assert.match(html, />\s*<i class="fa-solid fa-magnifying-glass" aria-hidden="true"><\/i>\s*<\/button>$/);
});

test("Buscar oculta su tooltip antes de ejecutar la acción y lo retira al desmontarse", () => {
    const button = {};
    const events = [];
    const tooltip = {
        hide: () => events.push("hide"),
        dispose: () => events.push("dispose")
    };
    const component = searchButton({bootstrap: {Tooltip: {
        getOrCreateInstance(element, options) {
            assert.equal(element, button);
            assert.equal(options.trigger, "hover focus");
            events.push("mount");
            return tooltip;
        },
        getInstance(element) {
            assert.equal(element, button);
            return tooltip;
        }
    }}});
    const click = {currentTarget: button};
    const instance = {
        $refs: {button},
        $emit(name, event) {
            assert.equal(event, click);
            events.push(name);
        }
    };

    component.mounted.call(instance);
    component.methods.search.call(instance, click);
    component.beforeUnmount.call(instance);
    assert.deepEqual(events, ["mount", "hide", "click", "dispose"]);
});
