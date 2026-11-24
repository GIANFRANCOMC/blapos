import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("las modales usan la misma transición al abrir y cerrar sin perder el centrado", () => {

    const branding = readFileSync(new URL("../../resources/css/Shared/controls.css", import.meta.url), "utf8");
    const formModal = readFileSync(new URL("../../resources/js/System/Components/Generics/FormModal.vue", import.meta.url), "utf8");

    assert.match(branding, /:is\(\.modal\.fade, \.modal-backdrop\.fade\) \{ transition: opacity var\(--fg-control-motion\)/);
    assert.match(branding, /\.modal\.fade \.modal-dialog \{ transition: transform var\(--fg-control-motion\)/);
    assert.match(branding, /@media \(prefers-reduced-motion: reduce\)[\s\S]*?transition: none/);
    assert.match(formModal, /modal-dialog modal-dialog-centered/);

});
