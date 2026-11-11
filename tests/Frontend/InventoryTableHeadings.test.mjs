import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, createRenderer, createSSRApp, h, nextTick, ref, useId} from "vue";
import {renderToString} from "vue/server-renderer";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const directory = "resources/js/System/Pages/Warehouses/inventory";

test("las tablas de inventario centran las cabeceras de texto y Kardex alinea a la derecha las numéricas", () => {
    const styles = read("resources/css/System/br-branding/82-inventory.css");
    assert.match(styles, /body\.br-layout \.br-inventory-table-headings > thead > tr > th \{\s*text-align: center !important;/);
    assert.match(styles, /\.br-inventory__table--single-warehouse > thead > tr > th:last-child \{\s*position: relative;/);

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

test("Kardex conserva su distribución de columnas y los importes en una línea", () => {
    const styles = read("resources/css/System/br-branding/82-inventory.css");

    const commonHeadings = ["Producto", "Movimiento", "Variación"];
    for(const [view, remaining] of [
        ["kardex.vue", ["Saldo"]],
        ["valued-kardex.vue", ["Costo unitario", "Valor movimiento", "Valor resultante"]]
    ]) {
        const page = read(directory + "/" + view);
        const headers = [...page.matchAll(/<th scope="col">([^<]+)<\/th>/g)].map(match => match[1]);
        assert.deepEqual(headers, [...commonHeadings, ...remaining], view);
        assert.equal(page.split('colspan="' + headers.length + '"').length - 1, 2, view);
        assert.match(page, /<td>\s*<ProductIdentity variant="expandable" :product="movement\.item"\/>\s*<\/td>\s*<td>\s*<KardexMovementDetails\b[^>]*\/>\s*<\/td>\s*<td class="text-end">\s*<StockDifference\b/);
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

function productIdentityComponent() {
    const identity = read("resources/js/System/Components/Generics/ProductIdentity.vue");
    const {descriptor, errors} = parse(identity);
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    return {...new Function("useId", script)(useId), render: compile(descriptor.template.content)};
}

test("Control de stock e Inventario usan la variante desplegable de producto", async () => {
    const component = productIdentityComponent();
    const renderProduct = (product, variant) => renderToString(createSSRApp({
        render: () => h(component, {product, variant})
    }));
    const withCodes = await renderProduct({name: "Producto A", internal_code: "PR-001", barcode: "001234"});
    assert.match(withCodes, /Producto A[\s\S]*?Cód\. interno[\s\S]*?PR-001[\s\S]*?Cód\. barras[\s\S]*?001234/);
    assert.doesNotMatch(withCodes, /<button/);
    const withoutProduct = await renderProduct(null);
    assert.match(withoutProduct, /Producto sin nombre[\s\S]*?Cód\. interno[\s\S]*?—/);
    assert.doesNotMatch(withoutProduct, /Cód\. barras/);

    for(const file of ["stock", "StockAlertsModal", "StockTraceabilityModal", "StockItemEditor", "kardex", "valued-kardex", "transfers", "guides"]) {
        assert.match(read(directory + "/" + file + ".vue"), /<ProductIdentity\b[^>]*variant="expandable"/);
    }
    for(const file of ["Catalogs/products/main.vue", "Catalogs/recipes/main.vue", "Sales/sales/main.vue", "Sales/sales/deliveries.vue", "Sales/pos/main.vue", "Purchases/purchases/main.vue"]) {
        assert.doesNotMatch(read("resources/js/System/Pages/" + file), /<ProductIdentity\b[^>]*variant="expandable"/);
    }
    const withoutCodes = await renderProduct({name: "Producto sin códigos"}, "expandable");
    assert.match(withoutCodes, /Producto sin códigos/);
    assert.doesNotMatch(withoutCodes, /<button|br-product-identity__codes/);
});

test("el botón de producto alterna los códigos por instancia y se cierra al cambiar de producto", async () => {
    const component = productIdentityComponent();
    const node = tag => ({tag, props: {}, children: [], style: {display: ""}});
    const renderer = createRenderer({
        createElement: node,
        createText: value => ({value}),
        createComment: node,
        patchProp: (element, name, previous, value) => { element.props[name] = value; },
        setText: (element, value) => { element.value = value; },
        setElementText: (element, value) => { element.value = value; },
        parentNode: element => element.parent,
        nextSibling: element => element.parent?.children[element.parent.children.indexOf(element) + 1],
        insert(element, parent, anchor) {
            element.parent = parent;
            parent.children.splice(anchor ? parent.children.indexOf(anchor) : parent.children.length, 0, element);
        },
        remove(element) {
            const siblings = element.parent.children;
            siblings.splice(siblings.indexOf(element), 1);
        }
    });
    const product = ref({id: 1, name: "Producto A", internal_code: "PR-001", barcode: "001234"});
    const container = node("root");
    renderer.createApp({
        render: () => h("div", [
            h(component, {product: product.value, variant: "expandable"}),
            h(component, {product: product.value, variant: "expandable"})
        ])
    }).mount(container);
    const [first, second] = container.children[0].children;
    const [button, codes] = first.children;
    const [otherButton, otherCodes] = second.children;
    assert.equal(button.props["aria-controls"], codes.props.id);
    assert.notEqual(codes.props.id, otherCodes.props.id);
    assert.equal(button.props["aria-expanded"], false);
    assert.equal(codes.style.display, "none");
    assert.equal(codes.children.some(child => child.tag === "span"), false);
    let blurCount = 0;
    button.blur = () => blurCount++;
    const click = (detail = 0) => button.props.onClick({detail, currentTarget: button, stopPropagation() {}});
    click(1);
    await nextTick();
    assert.equal(button.props["aria-expanded"], true);
    assert.equal(blurCount, 1);
    assert.equal(codes.style.display, "");
    const identification = codes.children.filter(child => child.tag === "span");
    assert.deepEqual(identification.map(child => child.children[0].value), ["Cód. interno", "Cód. barras"]);
    assert.equal(identification[1].children[1].value, "001234");
    assert.equal(otherButton.props["aria-expanded"], false);
    click();
    await nextTick();
    assert.equal(codes.style.display, "none");
    assert.equal(blurCount, 1);
    click();
    await nextTick();
    product.value = {id: 2, name: "Producto B", internal_code: "PR-002"};
    await nextTick();
    assert.equal(button.props["aria-expanded"], false);
    assert.equal(codes.style.display, "none");
});
