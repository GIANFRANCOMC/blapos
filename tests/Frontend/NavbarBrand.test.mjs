import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el nombre del mundo ocupa el encabezado del menú lateral sin logo", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const company = readFileSync(new URL("../../resources/js/System/Pages/Organizations/companies/main.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/51-two-level-navigation.css", import.meta.url), "utf8");
    const navbarStyles = readFileSync(new URL("../../resources/css/System/br-branding/60-dashboard-sales.css", import.meta.url), "utf8");

    assert.match(layout, /<div class="br-navigation__context-head">\s*<div class="br-navigation__world-card">\s*<span class="br-navigation__world-icon"/);
    assert.match(layout, /<h2 class="br-navigation__world-title">\{\{ \$section->dom_label \}\}<\/h2>/);
    assert.doesNotMatch(layout, /navigationBrandUrl|br-navigation__context-brand/);
    assert.doesNotMatch(layout, /br-navbar-brand/);
    assert.match(layout, /class="br-navigation__logomark"/);
    assert.match(company, /formField: "combinationmark"[^\n]*identidad de la empresa/);
    assert.doesNotMatch(styles, /\.br-navigation__context-brand/);
    assert.match(styles, /\.br-navigation__context-head\s*\{[^}]*height: 3\.95rem;[^}]*padding: 1\.25rem 0 0;/);
    assert.match(styles, /\.br-navigation__world-card\s*\{[^}]*height: 100%;/);
    assert.match(navbarStyles, /\.br-layout \.br-layout-navbar\.navbar-detached\s*\{[^}]*height: 2\.95rem !important;/);
    assert.match(styles, /\.br-navigation__world-title\s*\{[^}]*letter-spacing: -0\.015em;/);
    assert.doesNotMatch(styles.match(/\.br-navigation__rail\s*\{[^}]*\}/)?.[0] ?? "", /border-right:/);

});
