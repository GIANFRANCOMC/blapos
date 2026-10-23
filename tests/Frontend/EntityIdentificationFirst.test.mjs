import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const pages = [
    "Catalogs/products",
    "Catalogs/services",
    "Catalogs/subscriptions",
    "Catalogs/brands",
    "Catalogs/categories",
    "Organizations/branches",
    "Organizations/users",
    "Customers/customers"
];

for(const page of pages) {

    test(`${page}: identificación es la primera columna del listado`, () => {

        const path = `../../resources/js/System/Pages/${page}/main.vue`;
        const source = readFileSync(new URL(path, import.meta.url), "utf8");
        const list = page === "Organizations/branches"
            ? source.slice(source.indexOf('<section class="br-entity-list br-branches-list"'))
            : source;

        assert.equal(parse(source, {filename: path}).errors.length, 0);
        assert.match(list, /<thead class="br-table-header-surface">\s*<tr>\s*<th>Identificación<\/th>/);
        assert.match(list, /<tr v-for="record in entityList\.records\.data" :key="record\.id">\s*<td class="br-entity-table__identification-cell">/);

    });

}

test("proveedores: el documento de identificación precede al proveedor", () => {

    const path = "../../resources/js/System/Pages/Purchases/suppliers/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.equal(parse(source, {filename: path}).errors.length, 0);
    assert.match(source, /<thead class="br-table-header-surface">\s*<tr>\s*<th>Identificación<\/th>\s*<th>Proveedor<\/th>/);
    assert.match(source, /<tr v-for="supplier in records\.data" :key="supplier\.id">\s*<td class="br-entity-table__identification-cell">/);

});

test("recetas: códigos de producto preceden al platillo", () => {

    const path = "../../resources/js/System/Pages/Catalogs/recipes/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.equal(parse(source, {filename: path}).errors.length, 0);
    assert.match(source, /<thead class="br-table-header-surface">\s*<tr>\s*<th[^>]*>Identificación<\/th>/);
    assert.match(source, /<tr v-for="record in records\.data" :key="record\.id">\s*<td class="br-entity-table__identification-cell">/);

});
