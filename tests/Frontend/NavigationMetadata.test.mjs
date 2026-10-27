import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("descripciones de mundos e iconos de grupos provienen del catálogo de navegación", () => {

    const migration = readFileSync(new URL("../../database/migrations/2024_01_11_223124_create_init_masters_table.php", import.meta.url), "utf8");
    const seeder = readFileSync(new URL("../../database/seeders/SystemNavigationSeeder.php", import.meta.url), "utf8");
    const service = readFileSync(new URL("../../app/Services/System/Organizations/Companies/CompanySectionService.php", import.meta.url), "utf8");
    const layout = readFileSync(new URL("../../resources/views/System/layouts/main.blade.php", import.meta.url), "utf8");

    assert.match(migration, /Schema::create\("sections"[\s\S]*?\$table->string\("description", 120\)->nullable\(\)/);
    assert.match(migration, /Schema::create\("menu_groups"[\s\S]*?\$table->string\("dom_icon", 255\)->default\(""\)/);
    const worlds = seeder.match(/"sections" => \[([\s\S]*?)\n            \],/)?.[1]?.split("\n").filter(line => line.includes('"menu_category_id" =>')) ?? [];
    const groups = seeder.match(/"groups" => \[([\s\S]*?)\n            \],/)?.[1]?.split("\n").filter(line => line.includes('"section_id" =>')) ?? [];
    assert.equal(worlds.length, 7);
    assert.ok(worlds.every(line => /"description" => "[^"]+"/.test(line)));
    assert.ok(groups.length > 0);
    assert.ok(groups.every(line => /"dom_icon" => "fa-(?:solid|regular) fa-[a-z-]+"/.test(line)));
    assert.match(service, /"dom_icon",\s*"description",/);
    assert.match(service, /subSections\.menuGroup:id,section_id,slug,name,dom_icon,order,status/);
    assert.match(layout, /\{\{ \$section->description \}\}/);
    assert.match(layout, /\{\{ \$menuGroup->dom_icon \}\}/);
    assert.doesNotMatch(layout, /\$section->slug === "sc_workspace"\) \$section->dom_icon/);

});
