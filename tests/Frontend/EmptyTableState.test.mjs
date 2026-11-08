import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las filas de carga y vacío no reaccionan al hover ni bloquean sus acciones", () => {

    const styles = read("resources/css/System/br-branding/99-system-wide-visual-polish.css");

    const states = read("resources/css/System/br-branding/10-tables-breadcrumb-filters.css");
    assert.match(styles, /tr:not\(\.br-table-state\):not\(:has\(\.br-feedback-state, \.br-loader\)\):hover/);
    assert.match(states, /tr\.br-table-state:hover\s*\{[\s\S]*?background: transparent;[\s\S]*?cursor: default/);
    assert.match(states, /tr\.br-table-state > td\s*\{[\s\S]*?--bs-table-hover-bg: transparent/);
    assert.doesNotMatch(states.match(/tr\.br-table-state[\s\S]*$/)?.[0], /pointer-events: none/);
    assert.match(styles, /tr:has\(\.br-empty-state\) \.br-empty-state__custom-image\s*\{[\s\S]*?opacity: 0\.86/);

});
