import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const layout=read("resources/views/System/layouts/main.blade.php");
const nav=read("resources/views/System/layouts/partials/fg-navigation.blade.php");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const css=read("resources/css/System/fg/navigation.css");
const behavior=read("resources/js/System/Layout/Menu.js");

test("el navbar conserva favoritos, avisos, cuenta, empresa y cierre de sesión",()=>{
 for(const marker of ['@if($favoritesEnabled)', 'id="brFabFavoritesToggle"', 'fa-regular fa-star', 'fa-regular fa-bell', '$tenantAnnouncements', '$navbarAnnouncementCount', '$userInitials', '$user->name', '$navbarCompanyName', "route('account.index')", 'fg-user-logout']) assert.ok(navbar.includes(marker),marker);
 assert.match(css,/\.fg-user__identity/);
 assert.match(css,/text-overflow: ellipsis/);
 assert.match(css,/\.fg-topbar__panel/);
 assert.match(behavior,/count.hidden = totalItems === 0;/);
});
