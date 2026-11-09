import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, createSSRApp, h} from "vue";
import {renderToString} from "vue/server-renderer";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const directory = "resources/js/System/Pages/Warehouses/inventory";

test("las tablas de inventario centran las cabeceras de texto y Kardex alinea a la derecha las numéricas", () => {
    const styles = read("resources/css/System/br-branding/82-inventory.css");
    assert.match(styles, /body\.br-layout \.br-inventory-table-headings > thead > tr > th \{\s*text-align: center !important;/);

    for(const file of ["stock", "transfers", "guides", "kardex", "valued-kardex", "StockAlertsModal", "InventorySkeleton"]) {
        const source = read(`${directory}/${file}.vue`);
        const tables = [...source.matchAll(/<table\b[\s\S]*?>/g)].map(match => match[0]);

        assert.ok(tables.length > 0, `${file} debe contener una tabla`);
        assert.ok(tables.every(table => table.includes("br-inventory-table-headings")), `${file} debe compartir la regla de cabeceras`);

        for(const [, head] of source.matchAll(/<thead\b[^>]*>([\s\S]*?)<\/thead>/g)) {
            assert.doesNotMatch(head, /<th\b[^>]*\btext-end\b/, `${file} debe usar la regla compartida de alineación`);
        }
    }
    assert.doesNotMatch(styles, /\.br-inventory__kardex-table > thead > tr > th:nth-child\(-n\+2\)/);
    assert.match(styles, /\.br-inventory__kardex-table > thead > tr > th:nth-child\(n\+3\) \{\s*text-align: end !important;/);
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
    assert.match(styles, /body\.br-layout \.br-inventory__difference \{[^}]*white-space: nowrap;/);
    assert.match(styles, /\.br-inventory__kardex-table--valued tbody td:nth-child\(n\+3\) \{\s*white-space: nowrap;\s*overflow-wrap: normal;/);
    assert.doesNotMatch(styles, /\.br-inventory__kardex-table \.br-inventory__difference \{[^}]*white-space: normal;/);
    assert.match(styles, /\.br-inventory__table--single-warehouse \.br-inventory__col-product,\s*\.br-inventory__kardex-table th:first-child \{ width: 37%; \}/);
});

test("el detalle de movimiento conserva la autoría y los datos disponibles", async () => {
    const {descriptor, errors} = parse(read(directory + "/KardexMovementDetails.vue"));
    assert.deepEqual(errors, []);
    const moduleUrl = "data:text/javascript;base64," + Buffer.from(descriptor.script.content).toString("base64");
    const {default: options} = await import(moduleUrl);
    const component = {...options, render: compile(descriptor.template.content)};
    const renderMovement = (movement, originLabel = "Compra") => renderToString(createSSRApp({
        render: () => h(component, {
            movement, dateLabel: "08/11/26 09:20", typeLabel: "Entrada", originLabel
        })
    }));
    const manual = await renderMovement({
        created_at: "2026-11-08T14:20:00Z", movement_type: "entry", user: {name: "Ana Pérez"},
        user_id: 1, reason: "Recepción de mercadería", reference: "OC-001"
    });
    assert.match(manual, /08\/11\/26 09:20[\s\S]*?Entrada[\s\S]*?Compra[\s\S]*?Por Ana Pérez[\s\S]*?Recepción de mercadería[\s\S]*?OC-001/);
    const opening = await renderMovement({reason: "Stock inicial de demostración.", reference: "REF-001"}, "Stock inicial de demostración");
    assert.equal((opening.match(/Stock inicial de demostración/g) || []).length, 1);
    assert.doesNotMatch(opening, />Motivo</);
    assert.match(opening, /REF-001/);
    assert.match(await renderMovement({reason: "Conteo manual"}, null), /Motivo[\s\S]*?Conteo manual/);
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
