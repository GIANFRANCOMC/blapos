import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("colaboradores reutiliza tabla, menú y modal con pestañas sin perder acceso y seguridad", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Organizations/users/main.vue", import.meta.url), "utf8");

    assert.equal(parse(source, {filename: "users/main.vue"}).errors.length, 0);
    assert.match(source, /class="table br-entity-table mb-0"/);
    assert.match(source, /class="br-users-list__stack"/);
    assert.doesNotMatch(source, /br-users-profile-label|br-entity-code|<CopyButton/);
    assert.match(source, /edit: "Editar colaborador"/);
    assert.match(source, /changePassword: "Cambiar contraseña"/);
    assert.match(source, /accessHistory: "Historial de acceso"/);
    assert.match(source, /<TableActionsDropdown/);
    assert.match(source, /@click="openPasswordModal\(record\)"/);
    assert.match(source, /@click="openAuthenticationEvents\(record\)"/);
    assert.match(source, /<FormModal/);
    assert.match(source, /<CatalogFormTabs/);
    assert.match(source, /<CatalogReferenceImportModal/);
    assert.match(source, /@download="downloadRecords"/);
    assert.match(source, /@import="openImportModal"/);
    assert.match(source, /title="HISTORIAL DE ACCESO"/);
    assert.match(source, /title="CAMBIAR CONTRASEÑA"/);
    assert.doesNotMatch(source, /br-entity-modal__eyebrow|br-entity-modal__subtitle/);
    assert.match(source, /id: "collaborator", label: "COLABORADOR"[\s\S]*id: "contact", label: "CONTACTO"[\s\S]*id: "access", label: "ACCESO"/);
    assert.match(source, /v-show="activeFormTab === 'access'"/);
    assert.match(source, /v-show="activeFormTab === 'collaborator'"/);
    assert.match(source, /v-show="activeFormTab === 'contact'"/);
    assert.match(source, /focusFirstTabWithErrors\(entityForms.errors\)/);
    assert.match(source, /:textBottomInfo="forms\[entity\]\.createUpdate\.errors\?\.branch_ids"/);
    assert.match(source, /:textBottomInfo="forms\[entity\]\.createUpdate\.errors\?\.cash_register_ids"/);
    assert.match(source, /:textBottomInfo="forms\[entity\]\.createUpdate\.errors\?\.warehouse_ids"/);
    assert.doesNotMatch(source, /branchesHint|cashRegistersHint|warehousesHint/);

});
