import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const panel=read("resources/views/System/components/navbar-panel.blade.php");
const css=read("resources/css/System/fg/navigation.css");
const behavior=read("resources/js/System/Layout/Menu.js");

test("el navbar conserva favoritos, avisos, cuenta, empresa y cierre de sesión",()=>{
 for(const marker of ['@if($favoritesEnabled)', 'id="brFabFavoritesToggle"', 'fa-regular fa-star', 'fa-regular fa-bell', '$tenantAnnouncements', '$navbarAnnouncementCount', '$navbarLogomarkUrl', '$user->name', '$navbarCompanyName', "route('account.index')", 'fg-user-logout']) assert.ok(navbar.includes(marker),marker);
 assert.match(navbar,/fg-topbar__user-summary[\s\S]*?\$user->name[\s\S]*?\$navbarCompanyName[\s\S]*?\$user->email[\s\S]*?\$role->name/);
 assert.doesNotMatch(navbar,/fg-user__identity/);
 assert.match(navbar,/fg-user-logout[\s\S]*?<form method="POST" action="\{\{ route\('logout'\) \}\}" id="logout" hidden>@csrf/);
 assert.match(css,/\.fg-topbar__user-summary \{[^}]*overflow-wrap: anywhere/);
 assert.match(css,/\.fg-topbar__panel/);
 assert.equal((navbar.match(/@component\("System\.components\.navbar-panel"/g) || []).length, 3);
 assert.match(panel,/aria-labelledby="\{\{ \$id \}\}-title"/);
 assert.match(behavior,/count.hidden = totalItems === 0;/);
 assert.match(behavior,/empty.hidden = totalItems > 0;/);
 assert.match(behavior,/link.className = "dropdown-item fg-menu__item"/);
});
