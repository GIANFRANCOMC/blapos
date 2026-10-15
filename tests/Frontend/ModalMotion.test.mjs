import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("las modales usan la misma transición al abrir y cerrar sin perder el centrado", () => {

    const branding = readFileSync(new URL("../../resources/css/System/br-branding/40-buttons-quick-create.css", import.meta.url), "utf8");
    const formModal = readFileSync(new URL("../../resources/js/System/Components/Generics/FormModal.vue", import.meta.url), "utf8");

    assert.match(branding, /--br-modal-transition-duration: 0\.16s/);
    assert.match(branding, /\.br-layout \.modal\.fade,\s*\.br-layout \.modal-backdrop\.fade\s*\{\s*transition: opacity var\(--br-modal-transition-duration\) ease-in-out !important/);
    assert.match(branding, /\.br-layout \.modal\.fade \.modal-dialog\s*\{[^}]*transition: transform var\(--br-modal-transition-duration\) ease-in-out !important/);
    assert.match(branding, /@media \(prefers-reduced-motion: reduce\)[\s\S]*?transition: none !important/);
    assert.match(formModal, /modal-dialog modal-dialog-centered/);

});
