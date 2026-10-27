import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el navbar presenta favoritos, avisos y usuario autenticado sin alterar sus controles", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/60-dashboard-sales.css", import.meta.url), "utf8");
    const behavior = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");

    assert.match(layout, /@if\(\$favoritesEnabled\)[\s\S]*?id="brFabFavoritesToggle"/);
    assert.match(layout, /id="brFabFavoritesToggle"[\s\S]*?<i class="fa-regular fa-star"/);
    assert.match(layout, /br-navbar-announcements__toggle[\s\S]*?<i class="fa-regular fa-bell"/);
    assert.match(layout, /br-navbar-announcements__count" @if\(\$navbarAnnouncementCount === 0\) hidden @endif/);
    assert.match(layout, /\$navbarCompanyName = \$company->legal_name \?: \$company->commercial_name \?: "Empresa"/);
    assert.match(layout, /<span class="br-navbar-user__avatar"[^>]*>\{\{ \$userInitials \}\}<\/span>/);
    assert.match(layout, /<span class="br-navbar-user__identity">[\s\S]*?<span class="br-navbar-user__name" title="\{\{ \$user->name \}\}">\{\{ \$user->name \}\}<\/span>/);
    assert.match(layout, /<span class="br-navbar-user__company" title="\{\{ \$navbarCompanyName \}\}">\{\{ \$navbarCompanyName \}\}<\/span>/);
    assert.match(layout, /br-navbar-user__chevron/);
    assert.match(styles, /\.br-navbar-user__identity\s*\{[^}]*display: grid;/);
    assert.match(styles, /\.br-navbar-user__name,\s*\.br-navbar-user__company\s*\{[^}]*text-overflow: ellipsis;/);
    assert.match(styles, /\.br-navbar-user__avatar\s*\{[^}]*width: 1\.9rem;[^}]*height: 1\.9rem;/);
    assert.match(styles, /\.br-navbar-announcements__toggle\s*\{[^}]*border: 0;/);
    assert.match(styles, /\.br-navbar-announcements__count\s*\{[^}]*background: var\(--br-danger\);/);
    assert.match(styles, /\.br-navbar-announcements__menu\s*\{[^}]*--bs-dropdown-padding-y: 0;[^}]*padding: 0 !important;/);
    assert.match(styles, /\.br-navbar-announcements__head\s*\{[^}]*width: 100%;[^}]*background: var\(--br-primary-soft\);/);
    assert.match(behavior, /count\.hidden = totalItems === 0;/);

});
