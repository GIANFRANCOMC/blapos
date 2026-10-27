import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("la ficha de empresa usa el isotipo y organiza datos y contactos", () => {

    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/main.blade.php", import.meta.url), "utf8");
    const detail = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/partials/company-detail.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/52-workspace.css", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/WorkspaceController.php", import.meta.url), "utf8");
    const resolver = readFileSync(new URL("../../app/Services/System/Organizations/Companies/CompanyLogomarkService.php", import.meta.url), "utf8");

    assert.match(view, /<img src="\{\{ \$logomarkUrl \}\}" alt="Isotipo de \{\{ \$companyName \}\}" \/>/);
    assert.match(controller, /\$this->companyLogomarkService->url\(\$company->logomark\)/);
    assert.match(resolver, /assets->img->logomark/);
    assert.match(view, /"label" => "Correo electrónico"[^\n]*"divider" => true/);
    assert.match(view, /"label" => "WhatsApp"[^\n]*"icon" => "fa-brands fa-whatsapp"/);
    assert.match(detail, /<dt class="colon-at-end">\{\{ \$label \}\}<\/dt>/);
    assert.match(detail, /@include\("System\.components\.copy-button"/);
    assert.match(styles, /\.br-workspace__company-head\s*\{[^}]*background: color-mix\(in srgb, var\(--br-primary-soft\) 62%, var\(--br-white\)\);/);
    assert.match(styles, /\.br-workspace__company-detail--divider\s*\{[^}]*border-bottom: 1px solid var\(--br-workspace-border\);/);
    assert.match(styles, /\.br-workspace__company-details\s*\{[^}]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\);/);

});
