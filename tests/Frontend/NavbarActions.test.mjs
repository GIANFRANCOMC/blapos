import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el navbar presenta favoritos, avisos y usuario autenticado sin alterar sus controles", () => {

    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/60-dashboard-sales.css", import.meta.url), "utf8");
    const typography = readFileSync(new URL("../../resources/css/System/br-branding/70-visual-density-brand-refresh.css", import.meta.url), "utf8");
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
    assert.match(styles, /\.br-navbar-announcements__head\s*\{[^}]*width: 100%;[^}]*background: var\(--br-white\);/);
    assert.match(layout, /<i class="fa-solid fa-bars" aria-hidden="true"><\/i>/);
    assert.match(layout, /<span>Mi cuenta<\/span>[\s\S]*?<span>Cerrar sesión<\/span>/);
    assert.doesNotMatch(layout, /<span>Configuración de empresa<\/span>/);
    assert.equal((layout.match(/<span class="br-navbar-user__avatar" aria-hidden="true">\{\{ \$userInitials \}\}<\/span>/g) ?? []).length, 2);
    assert.match(layout, /@if\(\$favoritesEnabled\)[\s\S]*?<span>Preferencias<\/span>/);
    assert.match(styles, /\.br-navbar-user__menu \.dropdown-item\s*\{[^}]*justify-content: flex-start;[^}]*padding: 0\.35rem 0\.72rem !important;[^}]*background: transparent !important;/);
    assert.match(styles, /\.br-navbar-user__menu \.dropdown-item > span\s*\{[^}]*text-align: left;/);
    assert.match(typography, /body\.br-layout :where\([\s\S]*?span, small, strong[\s\S]*?font-size: var\(--br-ui-font-size\) !important;/);
    assert.match(styles, /body\.br-layout \.br-navbar-user__summary-copy strong\s*\{[^}]*font-size: 0\.78rem !important;/);
    assert.match(styles, /body\.br-layout \.br-navbar-user__summary-copy small\s*\{[^}]*font-size: 0\.68rem !important;/);
    assert.match(styles, /body\.br-layout \.br-navbar-user__menu \.dropdown-item > span\s*\{[^}]*font-size: 0\.74rem !important;/);
    assert.match(styles, /body\.br-layout \.br-navbar-user__toggle:is\([^)]*\)\s*\{[^}]*box-shadow: none !important;/);
    assert.match(styles, /\.br-navbar-user__toggle:focus-visible \.br-navbar-user__avatar\s*\{[^}]*box-shadow: 0 0 0 2px/);
    assert.match(styles, /\.br-navbar-user__menu\s*\{[^}]*overflow: hidden;/);
    assert.match(styles, /\.br-navbar-user__menu \.dropdown-item\s*\{[^}]*width: 100% !important;[^}]*max-width: 100%;[^}]*box-sizing: border-box;/);
    assert.match(styles, /\.br-navbar-user__logout:hover,[\s\S]*?color: var\(--br-danger-strong\) !important;[^}]*var\(--br-danger-soft\)/);
    assert.match(layout, /<strong>Notificaciones<\/strong>[\s\S]*?br-navbar-announcements__total/);
    assert.match(behavior, /count\.hidden = totalItems === 0;/);

});
