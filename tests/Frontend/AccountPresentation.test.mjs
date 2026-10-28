import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("Mi cuenta monta Vue y mantiene el Blade sin presentación", () => {
    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/account/main.blade.php", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.js", import.meta.url), "utf8");
    const vite = readFileSync(new URL("../../vite.config.js", import.meta.url), "utf8");

    assert.match(view, /<div id="app"><\/div>/);
    assert.match(view, /@vite\("resources\/js\/System\/Pages\/Essentials\/account\/main\.js"\)/);
    assert.doesNotMatch(view, /br-account-page|Mi cuenta|Guardar cambios/);
    assert.match(entry, /import App from "\.\/main\.vue"/);
    assert.match(entry, /Components\/Breadcrumb\.vue/);
    assert.match(entry, /Components\/InputText\.vue/);
    assert.match(entry, /css\/System\/Pages\/Essentials\/account\/main\.css/);
    assert.match(vite, /Pages\/Essentials\/account\/main\.js/);
});

test("Mi cuenta usa datos del servidor y controles compartidos", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.vue", import.meta.url), "utf8");
    const controller = readFileSync(new URL("../../app/Http/Controllers/System/Essentials/AccountController.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/main.css", import.meta.url), "utf8");

    assert.match(page, /<Breadcrumb :list=/);
    assert.match(styles, /\.br-account-page__breadcrumb\s*\{[^}]*justify-content: flex-end;/);
    assert.match(page, /<h1 id="account-title">Mi cuenta<\/h1>/);
    assert.match(page, /Actualiza tus datos personales y la información de tu cuenta\./);
    assert.doesNotMatch(page, /Administración · Ajustes/);
    assert.match(page, /br-account-page__identity-person[\s\S]*?account\.name[\s\S]*?account\.role[\s\S]*?account\.email/);
    assert.match(page, /br-account-page__identity-company[\s\S]*?company\.name[\s\S]*?companyDocument/);
    assert.match(controller, /TenantCompanyContext::class[\s\S]*?identityDocumentType/);
    assert.match(controller, /"account" => \$this->accountData\(\$account\)/);
    assert.match(page, /<InputText[\s\S]*?<InputBase[\s\S]*?<InputSelect[\s\S]*?<InputDate/);
    assert.match(page, /Requests\.patch\(\{route: window\.location\.pathname, data: this\.form\}\)/);
    assert.match(page, /errors: \{\}/);
    assert.match(page, /Guardar cambios/);
    assert.doesNotMatch(page, /fa-check/);
});
