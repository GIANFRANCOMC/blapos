import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("sucursales usa tabla y agrupa auditoría en acciones", () => {
    const source = read("resources/js/System/Pages/Organizations/branches/main.vue");

    assert.equal(parse(source, {filename: "branches/main.vue"}).errors.length, 0);
    assert.match(source, /class="table br-entity-table br-entity-table--directory mb-0"/);
    assert.match(source, /<CopyButton :value="record.internal_code" label="Código interno"\/>/);
    assert.match(source, /<TableActionsDropdown/);
    assert.match(source, /#secondaryActions/);
    assert.match(source, /Auditoría de series/);
    assert.match(source, /br-entity-modal__header/);
    assert.match(source, /br-entity-modal__footer/);
    assert.doesNotMatch(source, /br-config-scope-grid|card-list-custom|br-branch-audit-toolbar/);
});

test("empresa, colaboradores y perfiles mantienen acciones y estilos compartidos", () => {
    const company = read("resources/js/System/Pages/Organizations/companies/main.vue");
    const users = read("resources/js/System/Pages/Organizations/users/main.vue");
    const roles = read("resources/js/System/Pages/Organizations/roles/main.vue");

    assert.match(company, /save: "Guardar información"/);
    assert.doesNotMatch(company, /fa-solid fa-floppy-disk/);
    assert.match(users, /<CopyButton :value="record.email"/);
    assert.match(users, /<CopyButton :value="record.phone_number"/);
    assert.match(users, /variant="descriptive"/);
    assert.match(roles, /<TableActionsDropdown/);
    assert.match(roles, /<StatusBadge/);
    assert.doesNotMatch(roles, /br-roles__modal-help/);
});

test("modales y botones distinguen guardar una entidad de crear y editar", () => {
    const modal = read("resources/js/System/Components/Generics/FormModal.vue");
    const buttons = read("resources/css/System/br-branding/40-buttons-quick-create.css");
    const roles = read("resources/js/System/Pages/Organizations/roles/main.vue");
    const product = read("resources/js/System/Components/Catalogs/Products/productFormConfig.js");

    assert.match(modal, /resolvedSubmitButtonText/);
    assert.match(modal, /(?:AGREGAR\|EDITAR\|CREAR\|REGISTRAR)/);
    assert.match(buttons, /\.br-btn\.br-btn-action-update \{[^}]*background: var\(--br-warning\);[^}]*color: var\(--br-white\)/);
    assert.match(buttons, /\.br-btn-action-create \{[^}]*background: var\(--br-primary\)/);
    assert.match(roles, /Guardar perfil de acceso/);
    assert.match(product, /save: "Guardar producto"/);
});
