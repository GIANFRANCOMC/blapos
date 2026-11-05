import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const directory = "resources/js/System/Pages/Warehouses/inventory";

test("todas las tablas de inventario centran sus cabeceras sin cambiar las celdas", () => {
    const styles = read("resources/css/System/br-branding/82-inventory.css");
    assert.match(styles, /body\.br-layout \.br-inventory-table-headings > thead > tr > th \{\s*text-align: center !important;/);

    for(const file of ["stock", "transfers", "guides", "kardex", "valued-kardex", "StockAlertsModal", "InventorySkeleton"]) {
        const source = read(`${directory}/${file}.vue`);
        const tables = [...source.matchAll(/<table\b[\s\S]*?>/g)].map(match => match[0]);

        assert.ok(tables.length > 0, `${file} debe contener una tabla`);
        assert.ok(tables.every(table => table.includes("br-inventory-table-headings")), `${file} debe centrar todas sus cabeceras`);

        for(const [, head] of source.matchAll(/<thead\b[^>]*>([\s\S]*?)<\/thead>/g)) {
            assert.doesNotMatch(head, /<th\b[^>]*\btext-end\b/, `${file} no debe alinear cabeceras a la derecha`);
        }
    }
});
