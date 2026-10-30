import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("la ficha de empresa usa el isotipo y organiza datos y contactos", () => {

    const view = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.vue", import.meta.url), "utf8");
    const card = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/CompanyCard.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/52-workspace.css", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/WorkspaceController.php", import.meta.url), "utf8");
    const resolver = readFileSync(new URL("../../app/Services/System/Organizations/Companies/CompanyLogomarkService.php", import.meta.url), "utf8");

    assert.match(view, /<CompanyCard :company="company"\/>/);
    assert.match(card, /<img :src="company\.logomark_url" :alt="`Isotipo de \$\{company\.commercial_name \|\| company\.name\}`">/);
    assert.match(controller, /\$this->companyLogomarkService->url\(\$company->logomark\)/);
    assert.match(resolver, /assets->img->logomark/);
    assert.match(card, /label: "Correo electrónico"[^\n]*divider: true/);
    assert.match(card, /label: "WhatsApp"[^\n]*icon: "fa-brands fa-whatsapp"/);
    assert.match(card, /<dt class="colon-at-end">\{\{ detail\.label \}\}<\/dt>/);
    assert.match(card, /<CopyButton v-if="detail\.copyable && cleanValue\(detail\.value\)"/);
    assert.match(styles, /\.br-workspace__company-head\s*\{[^}]*background: color-mix\(in srgb, var\(--br-primary-soft\) 62%, var\(--br-white\)\);/);
    assert.match(styles, /\.br-workspace__company-detail--divider\s*\{[^}]*border-bottom: 1px solid var\(--br-workspace-border\);/);
    assert.match(styles, /\.br-workspace__company-details\s*\{[^}]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\);/);
    assert.match(styles, /\.br-workspace__company-detail-icon\s*\{[^}]*background: color-mix\(in srgb, var\(--br-primary-soft\) 46%, var\(--br-white\)\);/);

});
