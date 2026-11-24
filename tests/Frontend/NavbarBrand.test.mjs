import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const nav=read("resources/views/System/layouts/partials/fg-navigation.blade.php");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const css=read("resources/css/System/fg/navigation.css");

test("el contexto identifica el mundo sin repetir el icono del riel",()=>{
 assert.match(nav,/<header class="fg-context__header"><h2>\{\{ \$worldLabel \}\}<\/h2>/);
 assert.doesNotMatch(nav,/\$workspaceSection|fg-rail__brand|navbarLogomark/);
 assert.match(navbar,/class="fg-topbar__brand"><img src="[^"]*\$navbarLogomarkUrl[^"]*" alt="Blapos" width="32" height="32"/);
 assert.match(navbar,/class="fg-user__avatar" aria-hidden="true">\{\{ \$navbarUserInitial \}\}/);
 assert.doesNotMatch(nav,/world-description|world-icon|br-navbar-brand/);
 assert.match(css,/\.fg-context__header h2/);
 assert.match(css,/align-items: center/);
 assert.match(css,/\.fg-rail \{[^}]*background: var\(--fg-navigation-rail\);/);
 assert.match(css,/\.fg-topbar \{[^}]*background: var\(--fg-surface\);/);
 assert.match(css,/\.fg-topbar \.fg-button--icon \{[^}]*background: transparent;[^}]*color: var\(--fg-muted\);/);
 assert.match(css,/\.fg-world\.is-active \{ background: var\(--fg-primary\); color: var\(--fg-surface\);/);
 assert.match(css,/\.fg-topbar__actions \{[^}]*gap: var\(--fg-topbar-group-gap\);/);
 assert.doesNotMatch(navbar,/fg-topbar__utilities|fg-topbar__account/);
 assert.match(css,/\.fg-user__avatar \{[^}]*inline-size: var\(--fg-space-6\);[^}]*font-size: var\(--fg-small-size\);/);
});
