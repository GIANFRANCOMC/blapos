import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const layout=read("resources/views/System/layouts/main.blade.php");
const nav=read("resources/views/System/layouts/partials/fg-navigation.blade.php");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const css=read("resources/css/System/fg/navigation.css");
const behavior=read("resources/js/System/Layout/Menu.js");

test("el menú global conserva las rutas autorizadas y los mundos de una o varias páginas",()=>{
 for(const marker of ['@if($singlePage)', '$singlePage->dom_route_url', 'data-navigation-count=', 'data-navigation-panel=', '$subSection->dom_route_url', 'NavigationMenuGroups::ordered($section->subSections)']) assert.ok(nav.includes(marker),marker);
 assert.match(behavior,/panel.hidden = !visible/);
 assert.match(behavior,/if\(moduleButton.tagName === "A"\) return;/);
 assert.match(behavior,/selectWorld\(currentSectionId\)/);
 assert.match(layout,/@include\("System.layouts.partials.fg-navigation"\)/);
 assert.doesNotMatch(layout,/fgDesign|fg-design/);
});
test("el riel usa indicador activo, tooltips y cierre de sesión con CSRF",()=>{
 assert.match(nav,/class="fg-rail"/);
 assert.match(nav,/\$navigationLogomarkUrl/);
 assert.match(nav,/data-bs-custom-class="fg-tooltip"/);
 assert.match(nav,/fg-tooltip fg-tooltip--danger/);
 assert.match(nav,/fa-power-off/);
 assert.match(nav,/@csrf/);
 assert.match(css,/\.fg-world.is-active::before/);
 assert.match(css,/justify-content: safe center; gap: 12px; flex: 1/);
 assert.doesNotMatch(css, /@media\s*\(max-height:/);
 assert.match(css, /min-height: 0; overflow-y: auto/);
});
test("Mi cuenta y favoritos mantienen la organización y activación existentes",()=>{
 const catalog=read("database/seeders/SystemNavigationSeeder.php");
 assert.match(catalog,/"menu_group_id" => 23[^\n]*"dom_route" => "account.index"/);
 assert.match(layout,/\$favoritesEnabled =/);
 assert.match(nav,/NavigationMenuGroups::ordered/);
 assert.match(navbar,/@if\(\$favoritesEnabled\)/);
 assert.match(behavior,/if \(!root\) return;/);
});
test("el CSS global se carga en head sin la apariencia ni el inicializador de Vuexy",()=>{
 assert.match(layout,/@vite\(\["resources\/css\/System\/fg\/index.css"/);
 const up=read("resources/views/System/layouts/partials/fg-up.blade.php");
 const down=read("resources/views/System/layouts/partials/fg-down.blade.php");
 assert.doesNotMatch(up,/core.css|theme-default.css|demo.css|br-branding.css|template-customizer/);
 assert.doesNotMatch(down,/assets\/js\/main.js|perfect-scrollbar|vendor\/js\/menu.js/);
 assert.match(up,/bootstrap.min.css/);
 assert.match(down,/vendor\/js\/bootstrap.js/);
});
