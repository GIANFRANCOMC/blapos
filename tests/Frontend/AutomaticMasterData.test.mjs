import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("maestros automáticos es de solo lectura y clientes condiciona Registrar huella", () => {

    const masters = readFileSync(new URL("../../resources/js/System/Pages/General/master_data/main.vue", import.meta.url), "utf8");
    const customers = readFileSync(new URL("../../resources/js/System/Pages/Customers/customers/main.vue", import.meta.url), "utf8");

    assert.equal(parse(masters, {filename: "master_data/main.vue"}).errors.length, 0);
    assert.equal(parse(customers, {filename: "customers/main.vue"}).errors.length, 0);
    assert.match(masters, /code: "automatic"/);
    assert.match(masters, /v-if="selectedResourceCode === 'automatic'"/);
    assert.match(masters, /v-else class="br-master-data__grid"/);
    assert.match(customers, /<li v-if="canRegisterCustomerFingerprint">/);
    assert.match(customers, /this\.automaticSettings\.customer_fingerprint_registration === true/);

});
