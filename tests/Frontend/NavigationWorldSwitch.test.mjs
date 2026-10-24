import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("cambiar de mundo muestra su menú sin navegar al primer submódulo", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/51-two-level-navigation.css", import.meta.url), "utf8");

    assert.match(layout, /@vite\("resources\/js\/System\/Layout\/main\.js"\)/);
    assert.match(layout, /data-navigation-section-id="\{\{ \$activeNavigationSection\?->id \}\}"/);
    assert.match(layout, /<button\s+type="button"\s+id="\{\{ \$section->dom_id \}\}"\s+class="br-navigation__module/);
    assert.doesNotMatch(layout, /href="\{\{ \$reference->dom_route_url \}\}"/);
    assert.match(layout, /@foreach\(\$navigationSections as \$section\)[\s\S]*data-navigation-panel="\{\{ \$section->id \}\}"/);
    assert.match(behavior, /panel\.hidden = panel\.dataset\.navigationPanel !== sectionId/);
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
    assert.doesNotMatch(styles, /\.br-navigation-preview|#brNavigationContent\[hidden\]/);

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
