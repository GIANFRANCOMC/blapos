import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("un mundo de página única navega y los demás muestran su menú lateral", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/51-two-level-navigation.css", import.meta.url), "utf8");

    assert.match(layout, /@vite\("resources\/js\/System\/Layout\/main\.js"\)/);
    assert.match(layout, /data-navigation-section-id="\{\{ \$activeNavigationSection\?->id \}\}"/);
    assert.match(layout, /<button\s+type="button"\s+id="\{\{ \$section->dom_id \}\}"\s+class="br-navigation__module/);
    assert.match(layout, /@if\(\$singlePage\)[\s\S]*?<a\s+href="\{\{ \$singlePage->dom_route_url \}\}"/);
    assert.match(layout, /data-navigation-count="\{\{ \$section->subSections->count\(\) \}\}"/);
    assert.doesNotMatch(layout, /href="\{\{ \$reference->dom_route_url \}\}"/);
    assert.match(layout, /@foreach\(\$navigationSections as \$section\)[\s\S]*data-navigation-panel="\{\{ \$section->id \}\}"/);
    assert.match(behavior, /panel\.hidden = !visible/);
    assert.match(behavior, /panel\.classList\.toggle\("is-entering", visible && !alreadyVisible\)/);
    assert.match(behavior, /var hasSubmenu = Number\(selectedModule\.dataset\.navigationCount\) > 1/);
    assert.match(behavior, /if\(moduleButton\.tagName === "A"\) return;/);
    assert.doesNotMatch(behavior, /content\.hidden|preview\.hidden|br-navigation-previewing/);
    assert.match(behavior, /selectWorld\(currentSectionId\)/);
    assert.match(behavior, /if\(!showingOtherWorldMenu\) return;/);
    assert.match(behavior, /event\.target\.closest\("\.br-navigation__context"\)/);
    assert.match(behavior, /event\.target\.closest\("\.br-navigation__module"\)/);
    assert.doesNotMatch(behavior, /moduleButton\.dataset\.navigationSection === currentSectionId;\s*\}\)\?\.focus\(\)/);
    assert.match(layout, /id="brNavigationContent"/);
    assert.doesNotMatch(layout, /id="brNavigationPreview"|id="brNavigationReturn"/);
    assert.match(behavior, /document\.documentElement\.dataset\.navigationSectionId/);
    assert.match(behavior, /root\.dataset\.homeUrl/);
    assert.match(behavior, /addEventListener\("click", confirmLogout\)/);
    assert.doesNotMatch(layout, /onclick="confirmLogout\(\);"/);
    assert.match(layout, /\$section->slug === "sc_workspace"\) \$section->dom_icon = "fa fa-home"/);
    assert.doesNotMatch(layout, /br-navigation__brand/);
    assert.doesNotMatch(styles, /\.br-navigation__brand/);
    assert.match(styles, /\.br-navigation__context\[hidden\]/);
    assert.match(styles, /@keyframes br-navigation-world-enter/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(layout, /<div class="br-navigation__world-card">\s*<h2 class="br-navigation__world-title">\{\{ \$section->dom_label \}\}<\/h2>/);
    assert.doesNotMatch(layout, /br-navigation__world-icon/);
    assert.doesNotMatch(layout, /br-navigation__world-type/);
    assert.doesNotMatch(styles, /\.br-navigation__world-type\s*\{/);
    assert.doesNotMatch(layout, /br-navigation__world-description|navigation_summary/);
    assert.doesNotMatch(styles, /\.br-navigation__context-head\s*\{[^}]*border-bottom:/);
    assert.match(styles, /\.br-navigation__context-head\s*\{\s*flex: 0 0 auto;\s*padding: 0 0 0\.5rem;/);
    assert.match(styles, /--br-navigation-worlds-bg: color-mix\(in srgb, var\(--br-secondary\) 96%, var\(--br-white\)\);/);
    assert.match(styles, /\.br-navigation__rail\s*\{[^}]*background: var\(--br-navigation-worlds-bg\);/);
    assert.match(styles, /\.br-navigation__world-card\s*\{[\s\S]*?width: 100%;[\s\S]*?padding: 0\.4rem 0\.68rem;/);
    assert.match(styles, /\.br-navigation__world-card\s*\{[^}]*background: var\(--br-navigation-worlds-bg\);/);
    assert.doesNotMatch(styles, /\.br-navigation__world-icon\s*\{/);
    assert.match(styles, /\.br-navigation__world-title\s*\{[^}]*color: var\(--br-white\);/);
    assert.match(styles, /\.br-navigation__world-title\s*\{[^}]*font-weight: 400;[^}]*letter-spacing: 0\.08em;[^}]*text-transform: uppercase;/);
    assert.doesNotMatch(styles, /\.br-navigation__world-card\s*\{[^}]*border:/);
    assert.match(styles, /\.br-navigation__context-body\s*\{[\s\S]*?padding: 0\.8rem 0\.68rem 1rem;/);
    assert.match(styles, /\.br-navigation__context\.is-entering \.br-navigation__context-body\s*\{\s*animation: br-navigation-world-enter 0\.28s/);
    assert.doesNotMatch(styles, /\.br-navigation__context\.is-entering \.br-navigation__world-card/);
    assert.doesNotMatch(layout, /@if\(\$isActivePage\)\s*<i class="fa-solid fa-chevron-right"/);
    assert.doesNotMatch(layout, /br-navigation__page-icon/);
    assert.match(styles, /\.br-navigation__page\s*\{[\s\S]*?gap: 0\.66rem;[\s\S]*?min-height: 1\.65rem;[\s\S]*?padding: 0\.24rem 0\.52rem;/);
    assert.match(styles, /\.br-navigation__page::before\s*\{[\s\S]*?width: 0\.22rem;[\s\S]*?opacity: 0\.5;/);
    assert.match(styles, /\.br-navigation__page\.active\s*\{[\s\S]*?color: var\(--br-secondary\);/);
    assert.doesNotMatch(styles, /\.br-navigation__group\.is-current/);
    assert.doesNotMatch(layout, /menuGroupIsActive|br-navigation__group \{\{/);
    assert.match(styles, /\.br-navigation__page\.active::before\s*\{\s*background: var\(--br-primary\);/);
    assert.match(layout, /@if\(\$menuGroup\)\s*<h3 class="br-navigation__group-title">\{\{ \$menuGroup->name \}\}<\/h3>/);
    assert.doesNotMatch(layout, /\$menuGroupItems->count\(\) > 1/);
    assert.match(styles, /\.br-navigation__group\s*\{\s*padding-bottom: 0\.66rem;\s*\}/);
    assert.doesNotMatch(styles, /\.br-navigation__group\s*\{[^}]*border-bottom:/);
    assert.match(styles, /\.br-navigation__group \+ \.br-navigation__group\s*\{\s*margin-top: 0\.66rem;/);
    assert.match(styles, /@media \(min-width: 1200px\)\s*\{\s*html\.br-navigation-without-context body\.br-layout \.br-navbar-menu-toggle\s*\{\s*display: none !important;/);
    assert.match(styles, /@media \(max-width: 1199\.98px\)\s*\{\s*\.br-layout\s*\{\s*--br-navigation-rail-width: 3\.9rem;/);
    assert.match(styles, /@media \(max-width: 575\.98px\)\s*\{\s*\.br-layout\s*\{\s*--br-navigation-rail-width: 3\.65rem;/);
    assert.match(styles, /\.br-navigation\.layout-menu\.without-context,[\s\S]*?width: var\(--br-navigation-rail-width\) !important;/);
    assert.match(styles, /\.br-layout \.tooltip\.br-navigation-tooltip\s*\{\s*--bs-tooltip-bg: var\(--br-primary\);/);
    assert.match(styles, /\.br-layout \.tooltip\.br-navigation-logout-tooltip\s*\{\s*--bs-tooltip-bg: var\(--br-danger\);/);
    assert.match(styles, /\.br-layout \.tooltip\.br-navigation-tooltip \.tooltip-inner,\s*\.br-layout \.tooltip\.br-navigation-logout-tooltip \.tooltip-inner\s*\{/);
    assert.match(styles, /\.br-layout \.tooltip\.br-navigation-logout-tooltip \.tooltip-inner\s*\{[\s\S]*?background: var\(--bs-tooltip-bg\);/);
    assert.match(styles, /\.tooltip-arrow::before\s*\{\s*border-right-color: var\(--bs-tooltip-bg\);/);
    assert.doesNotMatch(styles, /\.br-navigation-preview|#brNavigationContent\[hidden\]/);

});

test("el riel de mundos permanece compacto con tooltips", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/51-two-level-navigation.css", import.meta.url), "utf8");

    assert.match(layout, /id="brNavigationWorlds" class="br-navigation__modules"/);
    assert.match(layout, /<nav id="brNavigationWorlds"[^>]*>\s*@foreach\(\$navigationSections as \$section\)/);
    assert.match(layout, /data-bs-custom-class="br-navigation-tooltip"\s+title="\{\{ \$section->dom_label \}\}"/);
    assert.match(styles, /\.br-navigation__module,\s*\.br-navigation__logout\s*\{[^}]*border: 0;[^}]*background: transparent;/);
    assert.doesNotMatch(layout, /brNavigationWorldLabelsToggle|br-navigation__module-label/);
    assert.doesNotMatch(behavior, /setWorldLabelsExpanded|labelsStorageKey|br-navigation-worlds-expanded/);
    assert.doesNotMatch(styles, /br-navigation-worlds-expanded|br-navigation__labels-toggle/);

});

test("Mi cuenta comparte Inicio con Mi espacio y los accesos sin grupo van primero", () => {

    const catalog = readFileSync(new URL("../../database/seeders/SystemNavigationSeeder.php", import.meta.url), "utf8");
    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");

    assert.match(catalog, /\["id" => 11, "section_id" => 1, "menu_group_id" => 23, "order" => 1[^\n]*"dom_route" => "workspace\.index"/);
    assert.match(catalog, /\["id" => 12, "section_id" => 1, "menu_group_id" => 23, "order" => 2[^\n]*"dom_route" => "account\.index"/);
    assert.match(layout, /NavigationMenuGroups::ordered\(\$section->subSections\)/);

});

test("favoritos depende automáticamente del módulo Menú y favoritos", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");
    const automatic = readFileSync(new URL("../../app/Services/System/General/AutomaticMasterDataService.php", import.meta.url), "utf8");
    const profile = readFileSync(new URL("../../resources/js/System/Pages/Organizations/business_profile/main.vue", import.meta.url), "utf8");

    assert.match(automatic, /public const MENU_FAVORITES = "menu_favorites"/);
    assert.match(automatic, /"module_route" => "home\.index"/);
    assert.match(layout, /\$favoritesEnabled = \\App\\Services\\System\\General\\AutomaticMasterDataService::enabled/);
    assert.match(layout, /@if\(\$favoritesEnabled\)[\s\S]*?id="brFabFavoritesToggle"[\s\S]*?@endif/);
    assert.match(behavior, /if \(!root\) return;/);
    assert.doesNotMatch(profile, /const PROTECTED_ROUTES = \[[^\]]*"home\.index"/);

});
