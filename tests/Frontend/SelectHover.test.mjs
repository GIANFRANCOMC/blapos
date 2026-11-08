import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import postcss from "postcss";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("select2 y vue-select comparten placeholder recto y hover suave con los inputs", () => {

    const styles = read("resources/css/System/br-branding/99-system-wide-visual-polish.css");
    const controls = read("resources/css/System/br-branding/03-form-controls.css");
    const legacyStyles = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const hover = styles.match(/\.br-layout \.form-control:not\(:disabled\):not\(\[readonly\]\):not\(:focus\):hover,[\s\S]*?\{[^}]*\}/)?.[0] || "";

    assert.doesNotMatch(legacyStyles, /\.vs__search::placeholder\s*\{[^}]*font-style:\s*italic/);
    assert.match(controls, /::placeholder,\s*body \.select2-container\.select2-container--default \.select2-selection__placeholder \{[^}]*font: inherit;[^}]*font-style: normal/);
    assert.doesNotMatch(styles, /--br-placeholder-font-size/);
    assert.match(hover, /\.v-select:not\(\.vs--disabled\):not\(\.vs--open\):not\(:focus-within\) \.vs__dropdown-toggle:hover/);
    assert.match(hover, /\.select2-selection--single:hover/);
    assert.match(hover, /background-color: var\(--br-surface-subtle\) !important/);
    assert.match(styles, /\.select2-results__option--highlighted\[aria-selected\]\s*\{[^}]*background: var\(--br-primary-soft\)/);

});

test("los campos de una línea comparten tamaño y los selectores múltiples pueden crecer", () => {
    const controls = postcss.parse(read("resources/css/System/br-branding/03-form-controls.css"));
    const value = (selector, property) => controls.nodes
        .filter(rule => rule.selectors?.includes(selector))
        .flatMap(rule => rule.nodes)
        .find(node => node.prop === property)?.value;
    for(const selector of [
        "body :is(input.form-control, select.form-control, .form-select, .input-group-text)",
        "body .v-select.vs--single .vs__dropdown-toggle",
        "body .select2-container.select2-container--default .select2-selection--single"
    ]) {
        assert.equal(value(selector, "height"), "var(--br-control-height)", selector);
    }
    assert.equal(value("input", "font-size"), "var(--br-font-size-ui)");
    assert.equal(value("body .v-select", "--vs-font-size"), "var(--br-font-size-ui)");
    const multiple = "body .select2-container.select2-container--default .select2-selection--multiple .select2-selection__rendered";
    assert.equal(value(multiple, "flex-wrap"), "wrap");
    assert.equal(value(multiple, "height"), undefined);
    assert.match(read("resources/css/Platform/app.css"), /@import "\.\.\/System\/br-branding\/03-form-controls\.css"/);
    assert.match(read("public/System/assets/css/br-branding.css"), /Source: resources\/css\/System\/br-branding\/03-form-controls\.css/);
});
