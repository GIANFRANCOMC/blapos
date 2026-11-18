import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, h} from "vue";
import {renderToString} from "vue/server-renderer";

const directory = "resources/js/System/Components/Generics/";
function load(name, dependencies) {
    const {descriptor, errors} = parse(readFileSync(`${directory}${name}.vue`, "utf8"));
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const options = new Function(...Object.keys(dependencies), script)(...Object.values(dependencies));
    return {...options, render: compile(descriptor.template.content)};
}
const ActionsButton = load("ActionsButton", {createTooltip() {}});
const ActionsDropdown = load("ActionsDropdown", {ActionsButton});
const TableActionsDropdown = load("TableActionsDropdown", {ActionsDropdown});

test("acciones muestra por defecto tres puntos verticales con tooltip y área de botón", async () => {
    for(const component of [ActionsButton, ActionsDropdown, TableActionsDropdown]) {
        const html = await renderToString(h(component));
        assert.match(html, /title="Acciones" data-bs-toggle="tooltip"/);
        assert.match(html, /<button[^>]*type="button"/);
        assert.match(html, component === TableActionsDropdown ? /br-table-actions-trigger/ : /br-btn br-btn-sm br-btn-actions br-btn-actions--icon/);
        assert.match(html, /fa-ellipsis-vertical/);
        assert.doesNotMatch(html, /<span>Acciones<\/span>|fa-chevron-down/);
    }
});

test("type button habilita explícitamente la variante con label y flecha conservando el menú", async () => {
    const html = await renderToString(h(TableActionsDropdown, {
        type: "button", text: "Acciones", label: "Acciones del producto", disabled: true, menuClass: "custom-menu"
    }, {default: () => h("li", "Descargar Excel")}));
    assert.match(html, /<span>Acciones<\/span>/);
    assert.match(html, /fa-chevron-down/);
    assert.doesNotMatch(html, /fa-ellipsis-vertical/);
    assert.match(html, /data-bs-toggle="dropdown"/);
    assert.match(html, /aria-label="Acciones del producto" disabled/);
    assert.match(html, /dropdown-menu dropdown-menu-end custom-menu/);
    assert.match(html, /<li>Descargar Excel<\/li>/);
});
