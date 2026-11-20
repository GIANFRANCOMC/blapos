import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
const read=p=>readFileSync(new URL("../../"+p,import.meta.url),"utf8");
const layout=read("resources/views/System/layouts/main.blade.php");
const nav=read("resources/views/System/layouts/partials/fg-navigation.blade.php");
const navbar=read("resources/views/System/layouts/partials/fg-navbar.blade.php");
const css=read("resources/css/System/fg/navigation.css");
const behavior=read("resources/js/System/Layout/Menu.js");

test("el contexto identifica el mundo sin repetir el icono del riel",()=>{
 assert.match(nav,/<header class="fg-context__header"><h2>\{\{ \$section->dom_label \}\}<\/h2>/);
 assert.match(nav,/class="fg-rail__brand"/);
 assert.doesNotMatch(nav,/world-description|world-icon|br-navbar-brand/);
 assert.match(css,/\.fg-context__header h2/);
 assert.match(css,/align-items: center/);
});
