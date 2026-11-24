import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el inicio ubica su breadcrumb dentro del contenido", () => {
    const workspace = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.vue", import.meta.url), "utf8");
    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/main.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/fg/modules/workspace.css", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/WorkspaceController.php", import.meta.url), "utf8");

    assert.match(workspace, /<main class="fg-workspace"[\s\S]*?<nav [^>]*class="fg-breadcrumb fg-workspace__breadcrumb"[\s\S]*?<div class="fg-workspace__layout">/);
    assert.match(workspace, /breadcrumb\.section[\s\S]*?breadcrumb\.page/);
    assert.match(view, /<div id="app" data-endpoint="\{\{ route\('home\.data'\) \}\}"><\/div>[\s\S]*?Pages\/Essentials\/workspace\/main\.js/);
    assert.match(workspace, /v-if="breadcrumb\.section !== breadcrumb\.page"/);
    assert.doesNotMatch(view, /br-workspace__layout/);
    assert.match(styles, /\.fg-workspace__breadcrumb\s*\{[^}]*justify-content: flex-end;/);
    assert.match(controller, /"breadcrumb" => \[[\s\S]*?"section" => \$breadcrumbSection\?->dom_label[\s\S]*?"page" => \$breadcrumbPage\?->dom_label/);
});

test("el seeder demo prepara 15 avisos sin alterar avisos existentes", () => {
    const seeder = readFileSync(new URL("../../database/seeders/LandlordDemoAnnouncementsSeeder.php", import.meta.url), "utf8");
    const demo = readFileSync(new URL("../../database/seeders/LandlordTenantDemoSeeder.php", import.meta.url), "utf8");

    assert.equal((seeder.match(/\["title" => /g) ?? []).length, 15);
    assert.match(seeder, /where\("slug", "demo"\)/);
    assert.match(seeder, /firstOrCreate\(/);
    assert.match(demo, /\$this->call\(LandlordDemoAnnouncementsSeeder::class\)/);
});
