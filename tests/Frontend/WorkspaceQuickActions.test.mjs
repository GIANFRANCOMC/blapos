import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("las acciones rápidas conservan sus descripciones y usan el icono del submódulo", () => {

    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/main.blade.php", import.meta.url), "utf8");
    const link = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/partials/link.blade.php", import.meta.url), "utf8");
    const service = readFileSync(new URL("../../app/Services/System/Essentials/UserNavigationService.php", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/WorkspaceController.php", import.meta.url), "utf8");
    const seeder = readFileSync(new URL("../../database/seeders/SystemNavigationSeeder.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/52-workspace.css", import.meta.url), "utf8");

    assert.match(view, /br-workspace__section-eyebrow">Accesos rápidos<\/span>/);
    assert.match(view, /¿Qué deseas hacer hoy, \{\{ \$firstName \}\}\?/);
    assert.match(view, /Encuentra aquí las acciones principales para gestionar tu empresa de forma rápida y sencilla/);
    assert.doesNotMatch(view, /br-workspace__hero|Buenos días/);
    assert.match(controller, /\$firstName = Str::before\(trim\(\$user->name\), " "\);/);
    assert.match(link, /br-workspace__link-icon[^>]*><i class="\{\{ \$item\["icon"\] \}\}"/);
    assert.match(link, /\$item\["description"\]/);
    assert.match(link, /fa-solid fa-chevron-right/);
    assert.match(service, /"icon" => \$subSection->dom_icon/);
    assert.doesNotMatch(service, /WORKSPACE_ACTION_ICONS/);
    const items = seeder.match(/"items" => \[([\s\S]*?)\n            \],/)?.[1];
    assert.ok(items);
    const records = items.split("\n").filter(line => line.includes('"dom_route" =>'));
    assert.ok(records.length > 0);
    assert.ok(records.every(line => /"dom_icon" => "fa-(?:solid|regular) fa-[a-z-]+"/.test(line)));
    assert.match(styles, /\.br-workspace__link-icon\s*\{[^}]*border-radius: 50%;/);
    assert.match(styles, /\.br-workspace__link-icon i\s*\{[^}]*font-size: 1\.55rem;/);
    assert.match(styles, /\.br-workspace__support-icon\s*\{[^}]*font-size: 2\.3rem;/);
    assert.match(styles, /#workspace-popular-title\s*\{[^}]*font-size: clamp\(1\.45rem, 1\.08rem \+ 1\.3vw, 2\.05rem\) !important;/);
    assert.match(styles, /\.br-workspace__section-eyebrow\s*\{[^}]*text-transform: uppercase;/);
    assert.match(styles, /\.br-workspace__section-eyebrow\s*\{[^}]*background: var\(--br-workspace-support-bg\);[^}]*color: var\(--br-workspace-support-link\);/);

});
