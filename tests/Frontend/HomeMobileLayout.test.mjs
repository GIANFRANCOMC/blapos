import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import postcss from "postcss";
import {parse} from "@vue/compiler-sfc";

test("Home inicia con búsqueda y filtros a la izquierda sin encabezado redundante", () => {

    const component = readFileSync(new URL("../../resources/js/System/Pages/Essentials/home/main.vue", import.meta.url), "utf8");
    const source = readFileSync(new URL("../../resources/css/System/fg/modules/organization.css", import.meta.url), "utf8");
    const stylesheet = postcss.parse(source);
    const filters = stylesheet.nodes.find(node => node.selector === ".fg-home__filters");
    const switchContainer = stylesheet.nodes.find(node => node.selector === ".fg-theme .fg-home__filter.form-check.form-switch");
    const switchInput = stylesheet.nodes.find(node => node.selector === ".fg-theme .fg-home__filter .form-check-input");

    assert.equal(parse(component, {filename: "home/main.vue"}).errors.length, 0);
    assert.doesNotMatch(component, /br-home__heading|Plataforma de trabajo|Accede a los módulos habilitados/);
    assert.equal(filters.nodes.find(declaration => declaration.prop === "justify-content")?.value, "flex-start");
    assert.equal(filters.nodes.find(declaration => declaration.prop === "gap")?.value, "0.625rem");
    assert.equal(switchContainer.nodes.find(declaration => declaration.prop === "padding")?.value, "0");
    assert.equal(switchInput.nodes.find(declaration => declaration.prop === "float")?.value, "none");
    assert.equal(switchInput.nodes.find(declaration => declaration.prop === "margin")?.value, "0");
    assert.match(component, /<header class="fg-home__header">\s*<div class="fg-home__filters">/);
    assert.match(component, /id: "menu-parent-configuration"/);

});

test("Home mantiene la búsqueda compacta y los filtros alineados en móvil", () => {

    const source = readFileSync(new URL("../../resources/css/System/fg/modules/organization.css", import.meta.url), "utf8");
    const stylesheet = postcss.parse(source);
    const mobile = stylesheet.nodes.find(node =>
        node.type === "atrule"
        && node.name === "media"
        && node.params === "(max-width: 767.98px)"
        && node.nodes.some(rule => rule.selector === ".fg-home-search")
    );

    assert.ok(mobile);

    const value = (selector, property) => mobile.nodes
        .find(rule => rule.selector === selector)
        ?.nodes.find(declaration => declaration.prop === property)
        ?.value;

    assert.equal(value(".fg-home__filters", "display"), "grid");
    assert.equal(value(".fg-home__filters", "grid-template-columns"), "repeat(2, minmax(0, 1fr))");
    assert.equal(value(".fg-home-search", "grid-column"), "1 / -1");
    assert.equal(value(".fg-home-search", "flex"), "none");
    assert.equal(value(".fg-theme .fg-home__filter .form-check-label", "min-width"), "0");
    assert.equal(value(".fg-theme .fg-home__filter .form-check-label", "font-size"), undefined);

});
