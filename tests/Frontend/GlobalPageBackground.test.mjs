import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("las páginas comparten el fondo azul grisáceo y conservan superficies blancas distinguibles", () => {

    const tokens = readFileSync(new URL("../../resources/css/System/br-branding/00-tokens.css", import.meta.url), "utf8");
    const layout = readFileSync(new URL("../../resources/css/System/br-branding/50-layout-navbar-sidebar.css", import.meta.url), "utf8");
    const content = readFileSync(new URL("../../resources/css/System/br-branding/70-visual-density-brand-refresh.css", import.meta.url), "utf8");
    const panels = readFileSync(new URL("../../resources/css/System/br-branding/99-system-wide-visual-polish.css", import.meta.url), "utf8");

    assert.match(tokens, /--br-surface: #f4f7fd;/);
    assert.match(tokens, /--br-neutral-page: var\(--br-surface\);/);
    assert.match(tokens, /--br-border: #d9e3ef;/);
    assert.match(layout, /body\.br-layout\s*\{[^}]*background-color: var\(--br-surface\);/);
    assert.match(content, /\.br-layout \.br-layout-content\s*\{[^}]*background-color: var\(--br-surface\);/);
    assert.match(panels, /\.br-layout \.card,[\s\S]*?background: var\(--br-white\);/);

});
