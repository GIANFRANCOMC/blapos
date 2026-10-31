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
    const request = readFileSync(new URL("../../app/Http/Requests/System/Essentials/Account/UpdateAccountRequest.php", import.meta.url), "utf8");
    const service = readFileSync(new URL("../../app/Services/System/Essentials/AccountService.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/main.css", import.meta.url), "utf8");
    const userCard = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/AccountUserCard.vue", import.meta.url), "utf8");
    const companyCardData = readFileSync(new URL("../../app/Services/System/Organizations/Companies/CompanyCardDataService.php", import.meta.url), "utf8");

    assert.match(page, /<Breadcrumb :list=/);
    assert.match(styles, /\.br-account-page__breadcrumb\s*\{[^}]*justify-content: flex-end;/);
    assert.match(page, /<h1 id="account-title">Mi cuenta<\/h1>/);
    assert.match(page, /Actualiza tus datos personales y la información de tu cuenta\./);
    assert.doesNotMatch(page, /Administración · Ajustes/);
    assert.match(page, /br-account-page__layout[\s\S]*?br-account-page__main[\s\S]*?<form[\s\S]*?<aside class="br-account-page__aside"/);
    assert.match(page, /<CompanyCard :company="company" heading-id="account-company-title"\/>/);
    assert.match(page, /<AccountUserCard :account="account"\/>/);
    assert.match(styles, /\.br-account-page__layout\s*\{[^}]*grid-template-columns: minmax\(0, 1\.9fr\) minmax\(16rem, 0\.85fr\);/);
    assert.match(companyCardData, /"logomark_url" => \$this->logomarkService->url\(\$company->logomark\)/);
    assert.match(userCard, /br-workspace__panel br-workspace__company/);
    assert.match(userCard, /br-navbar-user__avatar[\s\S]*?initials/);
    assert.match(userCard, /Nombre completo[\s\S]*?Tipo de documento[\s\S]*?Número de documento/);
    assert.match(userCard, /CopyButton/);
    assert.doesNotMatch(page, /br-account-page__panel-icon/);
    assert.match(controller, /TenantCompanyContext::class[\s\S]*?companyCardDataService->forCompany\(\$company\)/);
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
    assert.match(styles, /body\.br-layout #app \.br-account-page__field \.input-group > \.input-group-text,[\s\S]*?border: 0 !important;[\s\S]*?box-shadow: none !important;/);
});

test("Mi cuenta comparte la escala compacta de Mi empresa y muestra un skeleton proporcional", () => {
    const page = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.vue", import.meta.url), "utf8");
    const skeleton = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/AccountSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/main.css", import.meta.url), "utf8");
    const skeletonStyles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/account/skeleton.css", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/account/main.js", import.meta.url), "utf8");

    assert.match(page, /createInitialLoad\(\{skeleton: true\}\)/);
    assert.match(page, /<AccountSkeleton v-if="loading && skeletonEnabled"\/>/);
    assert.match(page, /initialLoad\.start\(\)[\s\S]*?try \{[\s\S]*?Requests\.get[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\)/);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando tu cuenta"/);
    assert.match(skeleton, /br-account-page__panel-head[\s\S]*?br-account-page__fields[\s\S]*?v-for="index in 4"[\s\S]*?br-account-page__actions/);
    assert.match(skeleton, /br-account-page__aside[\s\S]*?<CompanyCardSkeleton\/>[\s\S]*?br-account-page__user-card/);
    assert.doesNotMatch(skeleton, /br-account-page__panel-icon/);
    assert.doesNotMatch(skeleton, /br-account-skeleton__footer-hint/);
    assert.match(styles, /height: var\(--br-control-height\);[\s\S]*?min-height: var\(--br-control-height\);/);
    assert.match(skeletonStyles, /height: var\(--br-control-height\)/);
    assert.match(skeletonStyles, /height: var\(--br-btn-height\)/);
    assert.match(skeletonStyles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /account\/skeleton\.css/);
    assert.doesNotMatch(page, /<Loader\/>/);
});
