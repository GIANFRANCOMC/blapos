import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {catalogColumns} from "../../resources/js/System/Components/Catalogs/catalogColumns.js";

const read = path => readFileSync(new URL("../../" + path, import.meta.url), "utf8");

test("FG alinea encabezado e importes a la derecha en el Catálogo comercial", () => {
    const shared = read("resources/js/System/Components/Catalogs/FgCatalogList.vue");
    assert.match(shared, /<th\b[^>]*v-for="column in columns"[^>]*:class="column\.align"/);
    for(const kind of ["products", "services", "subscriptions"]) {
        const columns = catalogColumns[kind];
        assert.equal(columns.find(column => column.title === "Precio").align, "fg-align-end");
        assert.equal(columns.reduce((sum, column) => sum + parseInt(column.width, 10), 0), 100);
        const source = read("resources/js/System/Pages/Catalogs/" + kind + "/main.vue");
        assert.match(source, /<td\b[^>]*class="fg-align-end">(?:(?!<\/td>)[\s\S])*?<CatalogPriceCell/);
    }
});

test("ventas y cotizaciones conservan sus encabezados de precio centrados", () => {
    for(const [page, label] of [["sales", "PRECIO UNITARIO"], ["quotations", "Precio"]]) {
        const source = read("resources/js/System/Pages/Sales/" + page + "/main.vue");
        const price = [...source.matchAll(/<th\b([^>]*)>([^<]*)<\/th>/g)].find(([, , text]) => text.trim() === label);
        assert.ok(price, page);
        assert.match(price[1], /\btext-center\b/, page);
    }
});
