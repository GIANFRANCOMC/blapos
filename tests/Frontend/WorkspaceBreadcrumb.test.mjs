import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el inicio presenta su encabezado como página raíz", () => {
    const workspace = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.vue", import.meta.url), "utf8");
    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/main.blade.php", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/WorkspaceController.php", import.meta.url), "utf8");

    assert.match(workspace, /<main class="fg-workspace" aria-label="Inicio">[\s\S]*?<header class="fg-workspace__welcome">\s*<h1>[\s\S]*?<div class="fg-workspace__layout">/);
    assert.doesNotMatch(workspace, /<Breadcrumb\b|fg-workspace__breadcrumb/);
    assert.match(view, /<div id="app" data-endpoint="\{\{ route\('home\.data'\) \}\}"><\/div>[\s\S]*?Pages\/Essentials\/workspace\/main\.js/);
    assert.doesNotMatch(view, /br-workspace__layout/);
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
