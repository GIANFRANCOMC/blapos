import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("las páginas comparten el fondo azul grisáceo y conservan superficies blancas distinguibles", () => {

    const read = path => readFileSync(new URL("../../" + path, import.meta.url), "utf8");
    const tokens = read("resources/css/Shared/tokens.css");
    const styles = read("resources/css/Shared/components.css");
    assert.match(tokens, /--fg-canvas: #f4f7fd;/);
    assert.match(tokens, /--fg-surface: #fff;/);
    assert.match(tokens, /--fg-border: #d9e3ef;/);
    assert.match(styles, /\.fg-theme\s*\{[^}]*background: var\(--fg-canvas\);/);
    assert.match(read("resources/css/Shared/modals.css"), /\.fg-modal__content[^{}]*\{[^}]*background: var\(--fg-surface\);/);

});
