import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const priceTables = [
    ["Catalogs/products/main.vue", "Precio"],
    ["Catalogs/products/ProductsSkeleton.vue", "Precio"],
    ["Catalogs/services/main.vue", "Precio"],
    ["Catalogs/subscriptions/main.vue", "Precio"],
    ["Sales/sales/main.vue", "PRECIO UNITARIO"],
    ["Sales/quotations/main.vue", "Precio"],
];

test("los encabezados de precio quedan centrados sin cambiar las celdas de importes", () => {
    for(const [page, heading] of priceTables) {
        const source = readFileSync(new URL(`../../resources/js/System/Pages/${page}`, import.meta.url), "utf8");
        const headers = [...source.matchAll(/<th\b([^>]*)>([^<]*)<\/th>/g)];
        const priceHeader = headers.find(([, , label]) => label.trim() === heading);

        assert.ok(priceHeader, `Falta el encabezado ${heading} en ${page}`);
        assert.match(priceHeader[1], /\btext-center\b/, page);
        assert.doesNotMatch(priceHeader[1], /\btext-end\b/, page);
    }
});

test("la columna de precio ocupa el mismo ancho en productos, servicios y membresías", () => {
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");
    const sharedPrice = styles.match(/\.br-entity-table__col-price,\s*\.br-entity-table__col-offering-price\s*\{\s*width:\s*(\d+)%;/);

    assert.ok(sharedPrice, "El ancho de precio debe definirse en una sola regla para los tres listados");
    assert.equal(Number(sharedPrice[1]), 20);

    const widthOf = name => {
        const match = styles.match(new RegExp(`\\.br-entity-table__col-${name}\\s*\\{\\s*width:\\s*(\\d+)%`));
        assert.ok(match, `Falta el ancho de ${name}`);
        return Number(match[1]);
    };

    assert.equal(widthOf("identification") + widthOf("product") + 20 + widthOf("inventory") + widthOf("status") + widthOf("actions"), 100);
    assert.equal(widthOf("product"), 27);
    assert.equal(widthOf("code") + widthOf("offering") + widthOf("detail") + 20 + widthOf("status") + widthOf("actions"), 100);
});

test("todas las cabeceras del catálogo comercial se centran sin modificar las celdas", () => {
    const pages = ["products", "services", "subscriptions", "brands", "categories"];

    for(const page of pages) {
        const source = readFileSync(new URL(`../../resources/js/System/Pages/Catalogs/${page}/main.vue`, import.meta.url), "utf8");
        assert.match(source, /<table class="[^"]*br-entity-table--commercial-headings[^"]*"/, page);
    }

    const productSkeleton = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/ProductsSkeleton.vue", import.meta.url), "utf8");
    const sharedSkeleton = readFileSync(new URL("../../resources/js/System/Components/Catalogs/CatalogListSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");

    assert.match(productSkeleton, /br-entity-table--commercial-headings/);
    assert.match(sharedSkeleton, /'br-entity-table--commercial-headings': isOffering \|\| isCatalogUsage/);
    assert.match(styles, /body\.br-layout \.br-entity-table--commercial-headings > thead > tr > th\s*\{\s*text-align: center !important;/);
});
