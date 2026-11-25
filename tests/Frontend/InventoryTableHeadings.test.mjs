import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {compile, createRenderer, createSSRApp, h, nextTick, ref, useId} from "vue";
import {renderToString} from "vue/server-renderer";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const directory = "resources/js/System/Pages/Warehouses/inventory";

import {inventoryViews, inventoryBadgeTones} from "../../resources/js/System/Pages/Warehouses/inventory/inventoryPresentation.js";

test("los encabezados de Kardex y Traslados conservan su alineación", () => {
    assert.deepEqual(inventoryViews.kardex.columns.map(column => [column.label, column.align]), [
        ["Producto","center"], ["Movimiento","center"], ["Variación","end"], ["Saldo","end"]
    ]);
    assert.ok(inventoryViews.valued.columns.slice(2).every(column => column.align === "end"));
    assert.deepEqual(inventoryViews.transfers.columns.slice(0,4).map(column => [column.label,column.align]), [
        ["Referencia","center"], ["Almacén de origen","end"], ["Dirección del traslado",undefined], ["Almacén de destino",undefined]
    ]);
    const table=read(directory + "/InventoryTable.vue");
    assert.match(table, /<th[\s\S]*?scope="col" :class="column\.align/);
    assert.match(table, /'fg-sr-only': column\.hidden/);
    for(const file of ["stock", "kardex", "valued"]) assert.equal(inventoryViews[file].columns[0].width, "37%");
});

test("el Kardex valorizado mantiene variaciones y montos en una sola línea", () => {
    const page=read(directory + "/valued-kardex.vue");
    const styles=read("resources/css/System/fg/inventory.css");
    assert.match(page, /class="fg-align-end text-nowrap">(?:(?!<\/td>)[\s\S])*?\{\{ currencyNumber\(movement\.unit_cost\) \}\}/);
    assert.match(page, /fg-inventory-quantity[^>]*>[\s\S]*?signedCurrency\(movement\.value_change\)/);
    assert.match(page, /class="fg-inventory-quantity">\{\{ currencyNumber\(movement\.value_after\) \}\}/);
    assert.match(styles, /\.fg-inventory-quantity \{[^}]*white-space: nowrap/);
});

test("el detalle de movimiento conserva la autoría y los datos disponibles", async () => {
    const {descriptor, errors} = parse(read(directory + "/KardexMovementDetails.vue"));
    assert.deepEqual(errors, []);
    const script = descriptor.script.content.replace(/^import .*;$/gm, "").replace("export default", "return");
    const options = new Function("inventoryBadgeTones", script)(inventoryBadgeTones);
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

    for(const file of ["stock", "StockAlertsModal", "StockTraceabilityModal", "StockItemEditor", "kardex", "valued-kardex", "TransferDetailModal", "guides"]) {
        assert.match(read(directory + "/" + file + ".vue"), /<ProductIdentity\b[^>]*variant="expandable"/);
    }
    for(const file of ["Catalogs/products/main.vue", "Catalogs/recipes/main.vue", "Sales/sales/main.vue", "Sales/sales/deliveries.vue", "Sales/pos/main.vue", "Purchases/purchases/main.vue"]) {
        assert.doesNotMatch(read("resources/js/System/Pages/" + file), /<ProductIdentity\b[^>]*variant="expandable"/);
    }
    const withoutCodes = await renderProduct({name: "Producto sin códigos"}, "expandable");
    assert.match(withoutCodes, /Producto sin códigos/);
    assert.doesNotMatch(withoutCodes, /<button|fg-product-identity__codes/);
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
    assert.deepEqual(identification.map(child => child.children[0].value), ["Cód. interno:", "Cód. barras:"]);
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
