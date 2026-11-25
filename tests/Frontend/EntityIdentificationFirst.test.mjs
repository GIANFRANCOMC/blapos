import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {catalogColumns} from "../../resources/js/System/Components/Catalogs/catalogColumns.js";

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
            ? source.slice(source.indexOf('<section class="fg-entity-list fg-branches-list"'))
            : source;

        assert.equal(parse(source, {filename: path}).errors.length, 0);
        if(page === "Catalogs/products") {
            assert.match(list, /<thead\b[^>]*><tr\b[^>]*><th\b[^>]*scope="col">Identificación<\/th>/);
            assert.match(list, /<tr\b[^>]*v-for="record in entityList\.records\.data"[^>]*>\s*<td\b[^>]*>(?:(?!<\/td>)[\s\S])*?<div class="fg-product-codes"><span>\{\{ record\.internal_code \}\}/);
            return;
        }
        if(page.startsWith("Catalogs/") && page !== "Catalogs/products") {
            assert.equal(catalogColumns[page.split("/").at(-1)][0].title, "Código interno");
            assert.match(list, /<template #row="\{record\}">\s*<td\b[^>]*class="fg-align-center">(?:(?!<\/td>)[\s\S])*?<InternalCodeValue :value="record\.internal_code"\/>/);
            return;
        }

        assert.match(list, /<thead\b[^>]*>\s*<tr\b[^>]*>\s*<th\b[^>]*>Identificación<\/th>/);
        assert.match(list, /<tr\b[^>]*v-for="record in entityList\.records\.data"[^>]*>\s*<td\b[^>]*class="fg-entity-table__identification-cell"[^>]*>/);

    });

}

test("proveedores: el documento de identificación precede al proveedor", () => {

    const path = "../../resources/js/System/Pages/Purchases/suppliers/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.equal(parse(source, {filename: path}).errors.length, 0);
    assert.match(source, /<thead\b[^>]*class="fg-table-header-surface">\s*<tr\b[^>]*>\s*<th\b[^>]*>Identificación<\/th>\s*<th\b[^>]*>Proveedor<\/th>/);
    assert.match(source, /<tr\b[^>]*v-for="supplier in records\.data"[^>]*>\s*<td\b[^>]*class="[^"]*\bfg-entity-table__identification-cell\b[^"]*">/);

});

test("recetas: código interno centrado y código de barras junto al platillo", () => {

    const path = "../../resources/js/System/Pages/Catalogs/recipes/main.vue";
    const source = readFileSync(new URL(path, import.meta.url), "utf8");

    assert.equal(parse(source, {filename: path}).errors.length, 0);
    assert.match(source, /<FgCatalogList kind="recipes"/);
    assert.deepEqual(catalogColumns.recipes[0], {title: "Código interno", align: "fg-align-center", width: "18%"});
    assert.match(source, /<template #row="\{record\}">[\s\S]*?<InternalCodeValue :value="record\.item\?\.internal_code"\/>[\s\S]*?<span class="fg-entity-table__name">\{\{ record\.item\?\.name \}\}<\/span>[\s\S]*?<span class="fg-entity-identifier__value" v-text="record\.item\.barcode"/);


});
