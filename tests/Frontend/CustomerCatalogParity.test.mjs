import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("clientes comparte tabla, acciones y modales del catálogo sin perder carnet ni huella", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Customers/customers/main.vue", import.meta.url), "utf8");

    assert.equal(parse(source, {filename: "customers/main.vue"}).errors.length, 0);
    assert.match(source, /<ResponsiveTable>[\s\S]*?class="fg-table fg-customer-table fg-entity-table--directory fg-record-table" role="table"/);
    for (const label of ["Documento", "Correo", "Teléfono"]) {
        assert.match(source, new RegExp('class="fg-record-table__label" aria-hidden="true">' + label));
    }
    assert.match(source, /<TableActionsDropdown/);
    assert.match(source, /edit: "Editar cliente"/);
    assert.doesNotMatch(source, /br-entity-person__avatar/);
    assert.match(source, /class="fg-entity-table__name" v-text="record.name"/);
    assert.match(source, /<CopyButton :value="record.email"/);
    assert.match(source, /<CopyButton :value="record.phone_number"/);
    assert.match(source, /@click="openCarnetModal\(record\)"/);
    assert.match(source, /@click="openRegisterBiometricModal\(record\)"/);
    assert.match(source, /<FormModal/);
    assert.match(source, /<AppTabs/);
    assert.match(source, /variant="descriptive"/);
    assert.match(source, /v-show="activeFormTab === 'client'"/);
    assert.match(source, /v-show="activeFormTab === 'contact'"/);
    assert.match(source, /v-show="activeFormTab === 'emergency'"/);
    assert.match(source, /focusFirstTabWithErrors\(entityForms.errors\)/);
    assert.match(source, /<CatalogReferenceImportModal/);
    assert.match(source, /@download="downloadRecords"/);
    assert.match(source, /@import="openImportModal"/);

});
