import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const readSharedSkeleton = () => readFileSync(new URL("../../resources/css/Shared/skeleton.css", import.meta.url), "utf8");

test("Mi cuenta monta Vue y mantiene el Blade sin presentación", () => {
    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/account/main.blade.php", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.js", import.meta.url), "utf8");
    const vite = readFileSync(new URL("../../vite.config.js", import.meta.url), "utf8");

    assert.match(view, /<div id="app" data-endpoint="\{\{ route\('account\.data'\) \}\}"><\/div>/);
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
    const request = readFileSync(new URL("../../app/Http/Requests/System/Essentials/Account/UpdateAccountRequest.php", import.meta.url), "utf8");
    const service = readFileSync(new URL("../../app/Services/System/Essentials/AccountService.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/main.css", import.meta.url), "utf8");
    const userCard = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/AccountUserCard.vue", import.meta.url), "utf8");
    const companyCard = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/AccountCompanyCard.vue", import.meta.url), "utf8");

    assert.match(page, /<Breadcrumb :list=/);
    assert.match(styles, /\.fg-account-page__breadcrumb\s*\{[^}]*justify-content: flex-end;/);
    assert.match(page, /<h1 id="account-title" class="fg-title">Mi cuenta<\/h1>/);
    assert.match(page, /Actualiza tus datos de contacto y consulta tu perfil de acceso\./);
    assert.doesNotMatch(page, /Administración · Ajustes/);
    assert.match(page, /<header class="fg-page-header fg-account-page__hero"[\s\S]*?<FgButton type="submit" form="fg-account-data-form"[^>]*>[\s\S]*?<\/header>[\s\S]*?fg-account-page__layout[\s\S]*?<form id="fg-account-data-form"[^>]*@submit.prevent="saveAccount"[\s\S]*?<aside class="fg-account-page__aside"/);
    assert.match(page, /<AccountUserCard :account="account"\/>\s*<AccountCompanyCard :company="company"\/>/);
    assert.match(styles, /\.fg-account-page__layout\s*\{[^}]*grid-template-columns: minmax\(0, 2\.7fr\) minmax\(16rem, 1fr\);/);
    assert.match(styles, /\.fg-account-page__company-card,\s*\.fg-account-page__company-card \.fg-workspace__company-head,\s*\.fg-account-page__company-card \.fg-workspace__company-logo\s*\{\s*background: var\(--fg-surface\);/);
    assert.match(userCard, /fg-workspace__panel fg-workspace__company/);
    assert.match(userCard, /fg-navbar-user__avatar[\s\S]*?initials/);
    assert.match(userCard, /Nombre completo[\s\S]*?Identificación[\s\S]*?Perfil/);
    assert.match(userCard, /identification\(\)[\s\S]*?document_type\?\.toUpperCase\(\), this\.account\.document_number/);
    assert.doesNotMatch(userCard, /label: "Tipo de documento"|label: "Número de documento"/);
    assert.match(companyCard, /fg-workspace__company-logo[\s\S]*?company\.logomark_url[\s\S]*?company\.name[\s\S]*?company\.document_number/);
    assert.doesNotMatch(userCard, /company\.|Empresa vinculada/);
    assert.match(userCard, /fg-account-page__user-head[\s\S]*?<CopyButton v-if="account\.document_number" :value="account\.document_number"[^>]*\/>[\s\S]*?<\/p>/);
    assert.equal((userCard.match(/<CopyButton\b/g) || []).length, 1);
    assert.match(companyCard, /<CopyButton v-if="company\.document_number" :value="company\.document_number"/);
    assert.doesNotMatch(page, /br-account-page__panel-icon/);
    assert.match(controller, /TenantCompanyContext::class[\s\S]*?"company" => \[[\s\S]*?"name" =>[\s\S]*?"document_number" =>/);
    assert.match(controller, /"logomark_url" => \$logomarkService->url\(\$company->logomark\)/);
    assert.match(controller, /"account" => \$this->accountData\(\$account\)/);
    assert.match(page, /<InputBase[\s\S]*?<InputText[\s\S]*?<InputSelect[\s\S]*?<InputDate/);
    assert.match(controller, /loadMissing\("role", "identityDocumentType"\)/);
    assert.match(controller, /"document_type" => \$account->identityDocumentType\?->code/);
    assert.match(controller, /"document_number" => \$account->document_number/);
    assert.doesNotMatch(page, /id="account-(?:document-type|document-number|name)"/);
    assert.match(page, /title="Celular"/);
    assert.doesNotMatch(page, /v-model="form.name"/);
    assert.doesNotMatch(service, /"name",/);
    assert.doesNotMatch(request, /"name" =>/);
    assert.match(request, /"phone_number" => "celular"/);
    assert.match(page, /Requests\.patch\(\{route: window\.location\.pathname, data: this\.form\}\)/);
    assert.match(page, /errors: \{\}/);
    assert.match(page, /Guardar cambios/);
    assert.doesNotMatch(page, /Los cambios se aplicarán a tu cuenta/);
    assert.match(page, /const confirmed = await Alerts\.confirmAction|confirmed = await Alerts\.confirmAction/);
    assert.match(page, /title: "¿Guardar cambios de cuenta\?"[\s\S]*?confirmButtonText: "Guardar cambios"/);
    assert.match(page, /if\(!confirmed\) return;[\s\S]*?Requests\.patch\(/);
    assert.match(page, /await Alerts\.showMessage\(\{\s*type: "success",\s*title: result\.data\.msg \|\| "Cambios guardados"/);
    assert.doesNotMatch(page, /br-account-page__notice|Alerts\.toastrs\(\{type: "success"/);
    assert.doesNotMatch(styles, /\.br-account-page__notice/);
    assert.doesNotMatch(page, /fa-check/);
    assert.equal((page.match(/type="submit"/g) || []).length, 1);
    assert.match(page, /Alcance de la edición[\s\S]*?El nombre, documento y perfil de acceso se gestionan desde Colaboradores/);
});

test("Mi cuenta comparte la escala compacta de Mi empresa y muestra un skeleton proporcional", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.vue", import.meta.url), "utf8");
    const skeleton = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/AccountSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/main.css", import.meta.url), "utf8");
    const skeletonStyles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/skeleton.css", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.js", import.meta.url), "utf8");

    assert.match(page, /<AccountSkeleton v-if="loading"\/>/);
    assert.match(page, /try \{[\s\S]*?Requests\.get[\s\S]*?finally \{[\s\S]*?this\.loading = false/);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando tu cuenta"/);
    assert.match(skeleton, /fg-account-page__panel[\s\S]*?fg-account-page__fields[\s\S]*?v-for="index in 4"[\s\S]*?fg-account-page__scope/);
    assert.match(skeleton, /fg-account-page__aside[\s\S]*?fg-account-page__user-card/);
    assert.match(skeleton, /fg-account-page__user-card[\s\S]*?fg-account-page__user-head[\s\S]*?fg-account-skeleton__avatar[\s\S]*?fg-account-page__company-card[\s\S]*?fg-account-skeleton__company-logo/);
    assert.match(skeleton, /v-for="index in 3"[^>]*fg-workspace__company-detail--full/);
    assert.doesNotMatch(skeleton, /CompanyCardSkeleton|br-account-page__company-summary/);
    assert.doesNotMatch(skeleton, /br-account-page__panel-icon/);
    assert.doesNotMatch(skeleton, /br-account-skeleton__footer-hint/);
    assert.match(styles, /\.fg-account-page__hero \{ flex-direction: column; align-items: flex-start; \}/);
    assert.doesNotMatch(skeleton, /account-title|Guardar cambios/);
    assert.match(skeletonStyles, /height: var\(--fg-control-height\)/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /account\/skeleton\.css/);
    assert.doesNotMatch(page, /<Loader\/>/);
});
