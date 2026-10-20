import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las filas sin registros conservan un fondo tenue y no reaccionan al hover", () => {

    const styles = read("resources/css/System/br-branding/99-system-wide-visual-polish.css");

    assert.match(styles, /tr:has\(\.br-empty-state\):hover\s*\{[\s\S]*?background-color: var\(--br-surface-soft\)/);
    assert.match(styles, /tr:has\(\.br-empty-state\) > td\s*\{[\s\S]*?--bs-table-hover-bg: transparent/);
    assert.match(styles, /tr:has\(\.br-empty-state\):hover > td:first-child\s*\{[\s\S]*?border-left-color: transparent/);
    assert.match(styles, /tr:has\(\.br-empty-state\) \.br-empty-state__custom-image\s*\{[\s\S]*?opacity: 0\.86/);

});
