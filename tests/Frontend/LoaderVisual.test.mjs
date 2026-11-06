import assert from "node:assert/strict";
import {existsSync, readFileSync} from "node:fs";
import test from "node:test";

const file = path => new URL(`../../${path}`, import.meta.url);
const read = path => readFileSync(file(path), "utf8");

test("Loader y Swal usan el mismo icono giratorio sin imágenes", () => {
    const component = read("resources/js/System/Components/Loader.vue");
    const alerts = read("resources/js/System/Helpers/Alerts.js");
    const css = read("resources/css/System/br-branding/40-buttons-quick-create.css");

    assert.match(component, /class="br-loader"[\s\S]*?fa-solid fa-circle-notch br-loader__icon[\s\S]*?class="visually-hidden" v-text="label"/);
    assert.match(alerts, /function buildSwalLoadingHtml\(\{message\}\)[\s\S]*?class="br-loader br-loader--swal"[\s\S]*?fa-solid fa-circle-notch br-loader__icon/);
    assert.match(css, /\.br-loader__icon \{[\s\S]*?font-size: 2rem;[\s\S]*?color: var\(--br-primary\);[\s\S]*?animation: br-loader-spin 0\.7s linear infinite;/);
    assert.match(css, /@media \(prefers-reduced-motion: reduce\) \{\s*\.br-loader__icon \{\s*animation: none;/);
    assert.doesNotMatch(component, /<img|LOADER_IMAGE_SRC|loaderImageSrc/);
    assert.doesNotMatch(alerts, /<img|LOADER_IMAGE_SRC|br-swal-load__logo/);
    assert.equal(existsSync(file("resources/js/System/Helpers/BrandAssets.js")), false);
});
