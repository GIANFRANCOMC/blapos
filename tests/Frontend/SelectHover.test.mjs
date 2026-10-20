import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("select2 y vue-select comparten placeholder recto y hover suave con los inputs", () => {

    const styles = read("resources/css/System/br-branding/99-system-wide-visual-polish.css");
    const legacyStyles = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const hover = styles.match(/\.br-layout \.form-control:not\(:focus\):hover,[\s\S]*?\{[^}]*\}/)?.[0] || "";

    assert.doesNotMatch(legacyStyles, /\.vs__search::placeholder\s*\{[^}]*font-style:\s*italic/);
    assert.match(styles, /body\.br-layout ::placeholder\s*\{[^}]*font-style: normal/);
    assert.match(styles, /body\.br-layout \.select2-selection__placeholder\s*\{[^}]*font-style: normal/);
    assert.match(hover, /\.v-select:not\(\.vs--open\):not\(:focus-within\) \.vs__dropdown-toggle:hover/);
    assert.match(hover, /\.select2-selection--single:hover/);
    assert.match(hover, /background-color: var\(--br-surface-subtle\) !important/);
    assert.match(styles, /\.select2-results__option--highlighted\[aria-selected\]\s*\{[^}]*background: var\(--br-primary-soft\)/);

});
