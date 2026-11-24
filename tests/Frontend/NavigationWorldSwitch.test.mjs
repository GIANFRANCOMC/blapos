import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {canFavoritePage} from "../../resources/js/System/Layout/Menu.js";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const layout=read("resources/views/System/layouts/main.blade.php");
const nav=read("resources/views/System/layouts/partials/fg-navigation.blade.php");
const world=read("resources/views/System/layouts/partials/fg-navigation-world.blade.php");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const css=read("resources/css/System/fg/navigation.css");
const behavior=read("resources/js/System/Layout/Menu.js");

test("el menú global conserva las rutas autorizadas y los mundos de una o varias páginas",()=>{
 for(const marker of ['@if($singlePage)', '$singlePage->dom_route_url', 'data-navigation-panel=', '$subSection->dom_route_url', 'NavigationMenuGroups::ordered($section->subSections->where("dom_route", "!=", "home.index"))']) assert.ok((nav+world).includes(marker),marker);
 assert.match(behavior,/panel.hidden = !visible/);
 assert.match(behavior,/if\(moduleButton.tagName === "A"\) return;/);
 assert.match(behavior,/const currentPanel = panels\.find\(panel => panel\.dataset\.navigationPanel === currentSectionId\)/);
 assert.match(world,/\$hasNavigationContext\(\$section\) \? null : \$section->subSections->first\(\)/);
 assert.match(nav,/\$worldNavigationSections->filter\(\$hasNavigationContext\)/);
 assert.match(layout,/\$hasNavigationContext = /);
 assert.match(behavior,/if\(document.documentElement.dataset.navigationPage\) return closeMobilePanel;/);
 assert.match(layout,/@include\("System.layouts.partials.fg-navigation"\)/);
 assert.doesNotMatch(layout,/fgDesign|fg-design/);
});
test("al cambiar de mundo se anima el contenido y el marco permanece estable", () => {
 assert.match(nav, /class="fg-context"[^>]*>[\s\S]*?class="fg-context__panel"/);
 assert.match(world, /aria-expanded="\{\{ \$isActiveSection && \$showNavigationContext/);
 assert.match(behavior, /lastPanel !== panel && context\.contains\(document\.activeElement\)/);
 assert.match(behavior, /panel === renderedPanel[\s\S]*?panel\.hidden = !visible/);
 assert.match(behavior, /if\(panel\) openPanel\(panel, moduleButton, mobile\.matches \|\| event\.detail === 0\)/);
 assert.match(behavior, /if\(moveFocus \|\| moveFocusFromPanel\) panel\.querySelector\("\.fg-context__page"\)\?\.focus\(\)/);
 assert.match(behavior, /event\.key === "Escape"[^\n]*\n\s*event\.preventDefault\(\);\s*closePanel\(\);/);
 assert.match(css, /@media \(prefers-reduced-motion: no-preference\)\s*\{\s*\.fg-context__panel\.is-entering\s*\{\s*animation: fg-world-panel-in/);
 assert.doesNotMatch(css, /\.fg-(?:navigation|rail|layout-page)\s*\{[^}]*\banimation:/);
});
test("el control de panel abre los submódulos en escritorio y todo el menú en móvil", () => {
 assert.match(nav, /class="fg-context" id="fg-navigation-context" @if\(!\$showNavigationContext\) inert aria-hidden="true"/);
 assert.match(behavior, /matchMedia\("\(max-width: 767px\)"\)/);
 assert.match(behavior, /navigation\.inert = mobile\.matches && !drawerOpen/);
 assert.match(behavior, /trigger\.disabled = !lastPanel && \(!mobile\.matches \|\| modules.length === 0\)/);
 assert.match(behavior, /trigger\.setAttribute\("aria-controls", mobile\.matches \? navigation\.id : context\.id\)/);
 assert.match(behavior, /setVisiblePanel\(lastPanel, \{drawerOpen: true\}\)/);
 assert.match(css, /@media \(max-width: 767px\) \{\s*\.fg-layout-page,[^\n]*margin-left: 0/);
 assert.match(css, /\.fg-navigation--open \{ transform: none; visibility: visible; \}/);
 assert.match(css, /\.fg-menu-motion \.fg-layout-page \{ transition: margin-left var\(--fg-load-duration\)/);
 assert.match(css, /@media \(prefers-reduced-motion: reduce\) \{\s*\.fg-menu-motion [^{}]+\{ transition: none; \}/);
 assert.match(navbar, /data-fg-menu-toggle[^>]*>[\s\S]*?<span class="fg-topbar__menu-icon" aria-hidden="true"><\/span>/);
 assert.match(css, /\[data-fg-menu-toggle\]\[aria-expanded="true"\] \.fg-topbar__menu-icon::after/);
});
test("la barra centra el buscador y el isotipo enlaza a Inicio cuando está autorizado", () => {
 assert.match(navbar, /fg-topbar__start[\s\S]*?@if\(\$workspaceHome\)\s*<a href="\{\{ \$workspaceHome->dom_route_url \}\}"[\s\S]*?class="fg-topbar__brand"[\s\S]*?aria-label="Inicio">[\s\S]*?data-fg-menu-toggle[\s\S]*?fg-global-search/);
 assert.match(navbar, /\$subSection->dom_route !== 'home.index'/);
 assert.doesNotMatch(navbar, /fg-topbar__home|fg-navigation-world|fa-house/);
 assert.match(layout, /@include\("System.layouts.partials.fg-navbar"\)\s*@include\("System.layouts.partials.fg-navigation"\)\s*<div class="fg-layout-page">/);
 assert.match(css, /\.fg-topbar \{[\s\S]*?position: sticky;\s*inset-block-start: 0;[\s\S]*?grid-template-columns: minmax\(var\(--fg-topbar-side-min\), 1fr\) minmax\(0, 340px\) minmax\(var\(--fg-topbar-side-min\), 1fr\)/);
 assert.match(css, /\.fg-nav-backdrop \{[^}]*inset: var\(--fg-topbar-height\) 0 0/);
 assert.match(css, /\.fg-layout \{[^}]*--fg-topbar-height: 44px/);
 assert.match(css, /@media \(max-width: 767px\) \{[\s\S]*?\.fg-layout \{ --fg-topbar-height: 48px; \}/);
});
test("el riel usa indicador activo, tooltips e iconos compactos",()=>{
 assert.match(nav,/class="fg-rail"/);
 assert.match(world,/data-bs-custom-class="fg-tooltip"/);
 assert.doesNotMatch(nav,/fg-world--logout|fa-power-off|route\('logout'\)/);
 assert.match(css,/\.fg-world\.is-active::before/);
 assert.match(css,/\.fg-world:focus-visible \{ outline: 2px solid var\(--fg-primary-muted\)/);
 assert.match(css,/\.fg-world i \{ font-size: var\(--fg-control-icon-size\);/);
 assert.match(css,/justify-content: safe center; gap: calc\(\(var\(--fg-space-1\) \+ var\(--fg-space-2\)\) \/ 2\); flex: 1/);
 assert.match(css,/\.fg-world \{[^}]*width: 40px; height: 40px/);
 assert.doesNotMatch(css, /@media\s*\(max-height:/);
 assert.match(css, /min-height: 0; overflow-y: auto/);
});
test("los submódulos muestran el icono del catálogo y conservan acceso y alineación", () => {
 assert.match(nav, /aria-current="[^"]*\$isActivePage[^"]*">\s*<i class="fg-context__page-icon \{\{ \$subSection->dom_icon \?: 'fa-regular fa-file' \}\}" aria-hidden="true"><\/i>\s*<span>\{\{ \$subSection->dom_label \}\}<\/span>/);
 assert.match(css, /\.fg-context__page \{[^}]*align-items: center; gap: var\(--fg-space-2\); min-height: var\(--fg-control-height-small\)[^}]*font-size: var\(--fg-small-size\)/);
 assert.match(css, /@media \(max-width: 767px\) \{[\s\S]*?\.fg-context__page \{ min-height: var\(--fg-control-height\); \}/);
 assert.match(css, /\.fg-context__page-icon \{[^}]*flex: none; inline-size: var\(--fg-control-icon-size\)/);
 assert.match(css, /\.fg-context__page-icon \{[^}]*color: var\(--fg-muted\); font-size: var\(--fg-micro-size\)/);
 assert.match(css, /\.fg-context__page\.active \.fg-context__page-icon \{ color: inherit; \}/);
 assert.match(css, /\.fg-context__header \{[^}]*min-height: var\(--fg-topbar-height\);/);
 assert.doesNotMatch(css, /\.fg-context__page::?before/);
});
test("los tooltips del riel se retiran en móvil y conservan los nombres accesibles", () => {
 const alerts = read("resources/js/System/Helpers/Alerts.js");
 assert.match(world, /aria-label="\{\{ \$worldLabel \}\}"[^>]*data-fg-desktop-tooltip/);
 assert.doesNotMatch(world, /\btitle="/);
 assert.match(behavior, /mobile.addEventListener\("change", \(\) => \{\s*syncRailTooltips\(\)/);
 assert.match(alerts, /existing\?\.dispose\(\);[\s\S]*?desktopOnly && window\.matchMedia\("\(max-width: 767px\)"\)\.matches\) \{\s*element.removeAttribute\("title"\);\s*return null/);
});
test("Mi cuenta y Favoritos se abren en Configuración con sus rutas independientes",()=>{
 const catalog=read("database/seeders/SystemNavigationSeeder.php");
 assert.match(catalog,/"section_id" => 7[^\n]*"dom_route" => "account.index"/);
 assert.match(layout,/\$favoritesEnabled =/);
 assert.match(nav,/NavigationMenuGroups::ordered/);
 assert.match(navbar,/@if\(\$favoritesEnabled\)/);
 assert.match(navbar,/route\('favorites\.index'\)/);
 assert.match(behavior,/if \(!root\) return;/);
});
test("Inicio y Favoritos no se pueden añadir como favoritos y Analítica sigue disponible",()=>{
 assert.equal(canFavoritePage({dom_route: "home.index"}), false);
 assert.equal(canFavoritePage({dom_route: "favorites.index"}), false);
 assert.equal(canFavoritePage({dom_route: "dashboard.index"}), true);
});
test("el CSS global se carga en head sin la apariencia ni el inicializador de Vuexy",()=>{
 assert.match(layout,/@vite\(\["resources\/css\/System\/fg\/index.css"/);
 const up=read("resources/views/System/layouts/partials/fg-up.blade.php");
 const down=read("resources/views/System/layouts/partials/fg-down.blade.php");
 assert.doesNotMatch(up,/core.css|theme-default.css|demo.css|br-branding.css|template-customizer/);
 assert.doesNotMatch(down,/assets\/js\/main.js|perfect-scrollbar|vendor\/js\/menu.js/);
 assert.match(read("resources/css/Shared/ui.css"), /bootstrap\.min\.css" layer\(fg-vendor\)/);
 assert.doesNotMatch(up,/bootstrap\.min\.css/);
 assert.match(down,/vendor\/js\/bootstrap.js/);
});
