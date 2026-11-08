import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, createSSRApp, h} from "vue";
import {renderToString} from "vue/server-renderer";

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

test("el skeleton reutiliza la distribución de las tablas de stock y kardex", () => {
    const skeleton = read(directory + "/InventorySkeleton.vue");
    const styles = read("resources/css/System/br-branding/82-inventory.css");

    assert.match(skeleton, /heading === 'Estado del stock'">Estado del<br>stock/);
    assert.match(skeleton, /'br-inventory__kardex-table': isKardex/);
    assert.match(skeleton, /'br-inventory__kardex-table--valued': page === 'valued'/);
    const commonHeadings = ["Producto", "Movimiento", "Variación"];
    assert.match(skeleton, /const kardexHeadings = \["Producto", "Movimiento", "Variación"\]/);
    for(const [view, remaining] of [
        ["kardex.vue", ["Saldo"]],
        ["valued-kardex.vue", ["Costo unitario", "Valor movimiento", "Valor resultante"]]
    ]) {
        const page = read(directory + "/" + view);
        const headers = [...page.matchAll(/<th scope="col">([^<]+)<\/th>/g)].map(match => match[1]);
        assert.deepEqual(headers, [...commonHeadings, ...remaining], view);
        assert.equal(page.split('colspan="' + headers.length + '"').length - 1, 2, view);
        assert.ok(skeleton.includes("[...kardexHeadings, " + remaining.map(label => JSON.stringify(label)).join(", ") + "]"), view);
        assert.match(page, /<td>\s*<ProductIdentity :product="movement\.item"\/>\s*<\/td>\s*<td>\s*<KardexMovementDetails\b[^>]*\/>\s*<\/td>\s*<td class="text-end">\s*<StockDifference\b/);
    }
    assert.match(styles, /\.br-inventory__kardex-table \{[^}]*table-layout: fixed;/);
});

test("el detalle de movimiento conserva la autoría y los datos disponibles", async () => {
    const {descriptor, errors} = parse(read(directory + "/KardexMovementDetails.vue"));
    assert.deepEqual(errors, []);
    const component = {
        props: ["movement", "dateLabel", "typeLabel", "originLabel"],
        render: compile(descriptor.template.content)
    };
    const renderMovement = movement => renderToString(createSSRApp({
        render: () => h(component, {
            movement, dateLabel: "08/11/26 09:20", typeLabel: "Entrada", originLabel: "Compra"
        })
    }));
    const manual = await renderMovement({
        created_at: "2026-11-08T14:20:00Z", movement_type: "entry", user: {name: "Ana Pérez"},
        user_id: 1, reason: "Recepción de mercadería", reference: "OC-001"
    });
    assert.match(manual, /08\/11\/26 09:20[\s\S]*?Entrada[\s\S]*?Ana Pérez[\s\S]*?Compra[\s\S]*?Recepción de mercadería[\s\S]*?OC-001/);
    assert.match(await renderMovement({}), /Proceso del sistema/);
    assert.match(await renderMovement({user_id: 2}), /Usuario no disponible/);
});

test("los listados de inventario reutilizan la identificación de Control de stock", async () => {
    const identity = read("resources/js/System/Components/Generics/ProductIdentity.vue");
    const {descriptor, errors} = parse(identity);
    assert.deepEqual(errors, []);
    const render = compile(descriptor.template.content);
    const component = {props: ["product"], render};
    const renderProduct = product => renderToString(createSSRApp({
        render: () => h(component, {product})
    }));
    const withCodes = await renderProduct({name: "Producto A", internal_code: "PR-001", barcode: "001234"});
    assert.match(withCodes, /Producto A[\s\S]*?Cód\. interno[\s\S]*?PR-001[\s\S]*?Cód\. barras[\s\S]*?001234/);
    const withoutProduct = await renderProduct(null);
    assert.match(withoutProduct, /Producto sin nombre[\s\S]*?Cód\. interno[\s\S]*?—/);
    assert.doesNotMatch(withoutProduct, /Cód\. barras/);

    for(const file of ["stock", "kardex", "valued-kardex", "transfers", "guides", "StockAlertsModal", "StockTraceabilityModal", "StockItemEditor"]) {
        assert.match(read(directory + "/" + file + ".vue"), /<ProductIdentity\b/);
    }
});
