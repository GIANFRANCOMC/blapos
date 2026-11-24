import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las filas de carga y vacío no reaccionan al hover ni bloquean sus acciones", () => {

    const styles = read("resources/css/Shared/responsive-tables.css");
    assert.match(styles, /tr:not\(\.fg-table__state, :has\(\.fg-empty, \.fg-loader\)\):hover/);
    assert.match(styles, /tr:is\(\.fg-table__state, :has\(\.fg-empty, \.fg-loader\)\) > td \{ --bs-table-hover-bg: transparent;/);
    assert.doesNotMatch(styles, /\.fg-table__state[^{}]*\{[^}]*pointer-events: none/);

});
