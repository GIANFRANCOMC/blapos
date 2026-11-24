import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import postcss from "postcss";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

function declarations(css, selector) {
    const rule = postcss.parse(css).nodes.find(node => node.selectors?.includes(selector));
    assert.ok(rule, selector);
    return Object.fromEntries(rule.nodes.map(node => [node.prop, node.value]));
}

test("los selectores pintan el campo redondeado y comparten el hover suave", () => {
    const controls = read("resources/css/Shared/controls.css");
    const components = read("resources/css/Shared/components.css");
    const native = declarations(controls, ".fg-theme :is(.form-control, .form-select, .input-group-text, .select2-selection)");
    const vueSelect = declarations(components, ":is(.fg-select, .fg-theme .v-select) .vs__dropdown-toggle");
    assert.equal(native["background-color"], "var(--fg-surface)");
    assert.equal(native["border-radius"], "var(--fg-radius)");
    assert.equal(vueSelect.background, "var(--fg-surface)");
    assert.match(controls, /\.select2-container:not\(\.select2-container--open, \.select2-container--disabled\) \.select2-selection:hover,[\s\S]*?\.vs__dropdown-toggle:hover[^}]*background-color: var\(--fg-surface-subtle\)/);
    const placeholder = declarations(controls, ".fg-theme .select2-selection__placeholder");
    assert.equal(placeholder.font, "inherit");
    assert.equal(placeholder["font-style"], "normal");
});

test("los campos de una línea comparten altura y los selectores múltiples pueden crecer", () => {
    const controls = read("resources/css/Shared/controls.css");
    const components = read("resources/css/Shared/components.css");
    const native = declarations(controls, ".fg-theme :is(input.form-control, .form-select, .input-group-text, .select2-selection--single)");
    const vueSelect = declarations(components, ":is(.fg-select, .fg-theme .v-select) .vs__dropdown-toggle");
    const multiple = declarations(controls, ".fg-theme .select2-selection--multiple .select2-selection__rendered");
    assert.equal(native["min-height"], "var(--fg-control-height)");
    assert.equal(vueSelect["min-height"], native["min-height"]);
    assert.equal(multiple["flex-wrap"], "wrap");
    assert.equal(multiple.height, undefined);
});
