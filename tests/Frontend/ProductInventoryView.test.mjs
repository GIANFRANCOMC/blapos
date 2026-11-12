import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {inventoryStockStatus, summarizeProductStock} from "../../resources/js/System/Components/Catalogs/Products/productInventory.js";
import {editableNumber, separatorNumber} from "../../resources/js/Shared/Numbers.js";

test("el botón y la consulta comparten las alertas de stock", () => {

    const record = {
        warehouse_items: [
            {quantity: "8.000", minimum_stock: "3.000"},
            {quantity: "2.000", minimum_stock: "2.000"}
        ]
    };

    assert.deepEqual(summarizeProductStock(record), {total: "10.00", alerts: 1});
    assert.deepEqual(summarizeProductStock({warehouse_items: []}), {total: "0.00", alerts: 0});
    assert.deepEqual(inventoryStockStatus({initial_stock: "10", minimum_stock: "2"}), {code: "healthy", text: "Stock saludable"});
    assert.deepEqual(inventoryStockStatus({initial_stock: 2, minimum_stock: 2}), {code: "low", text: "Stock bajo"});
    assert.deepEqual(inventoryStockStatus({initial_stock: 0, minimum_stock: 3}), {code: "empty", text: "Agotado"});
    assert.equal(inventoryStockStatus({initial_stock: "", minimum_stock: 3}), null);

});

test("el acceso al inventario abre una modal de consulta para alertas y estados saludables", () => {

    const page = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const modal = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductInventoryModal.vue", import.meta.url), "utf8");
    const editor = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const inventory = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductWarehouseInventory.vue", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");
    const typography = readFileSync(new URL("../../resources/css/System/br-branding/01-typography.css", import.meta.url), "utf8");
    const stockStyles = readFileSync(new URL("../../resources/css/System/br-branding/82-inventory.css", import.meta.url), "utf8");

    assert.equal(parse(page, {filename: "products/main.vue"}).errors.length, 0);
    assert.equal(parse(modal, {filename: "ProductInventoryModal.vue"}).errors.length, 0);
    assert.equal(parse(inventory, {filename: "ProductWarehouseInventory.vue"}).errors.length, 0);
    assert.match(page, /<ProductInventoryModal ref="productInventoryModal"\/>/);
    assert.match(page, /<div class="br-entity-product-cell">\s*<button\b[\s\S]*?@click="openInventoryModal\(record\)"[\s\S]*?<\/button>\s*<div>\s*<span class="br-entity-table__name" v-text="record\.name"/);
    assert.match(page, /fa-solid fa-triangle-exclamation' : 'fa-solid fa-box-open'/);
    assert.doesNotMatch(page, /<th[^>]*>Inventario<\/th>|<QuantityValue\b/);
    assert.match(modal, /:show-submit="false"/);
    assert.match(modal, /close-button-text="Cerrar"/);
    assert.match(modal, /record\?\.warehouse_items/);
    assert.match(modal, /<ProductWarehouseInventory v-if="inventoryRows\.length" :rows="inventoryRows" :measurement-unit="record\.measurement_unit"\/>/);
    assert.match(editor, /<ProductWarehouseInventory[\s\S]*?v-model:rows="productForm\.data\.inventory"[\s\S]*?mode="edit"/);
    assert.match(modal, /record\.internal_code/);
    assert.match(modal, /record\.barcode/);
    assert.match(modal, /record\.brand\?\.name/);
    assert.match(inventory, /Stock actual/);
    assert.match(inventory, /Stock mínimo/);
    assert.match(inventory, /class="br-entity-inventory__head">\s*<span>Sede<\/span>\s*<span>Almacén<\/span>/);
    assert.match(inventory, /br-entity-inventory__branch[\s\S]*?<span v-text="item\.inventory\.branch_name"[\s\S]*?br-entity-inventory__warehouse[\s\S]*?<span v-text="item\.inventory\.warehouse_name"/);
    assert.match(inventory, /:model-value="item\.inventory\.initial_stock"[\s\S]*?:placeholder="currentLabel"/);
    assert.match(inventory, /:model-value="item\.inventory\.minimum_stock"[\s\S]*?placeholder="Stock mínimo"/);
    assert.match(inventory, /v-if="mode === 'edit' && !isUpdate"/);
    assert.match(inventory, /v-if="mode === 'edit'"/);
    assert.match(inventory, /class="br-entity-inventory__status" :class="item\.status && \['br-inventory__situation', `br-inventory__situation--\$\{item\.status\.code\}`\]"/);
    assert.match(inventory, /<QuantityValue class="br-inventory__stock-current" :value="item\.inventory\.initial_stock"/);
    assert.match(stockStyles, /\.br-entity-inventory__status\.br-inventory__situation,\s*body\.br-layout \.br-inventory__table > tbody > tr > td\.br-inventory__situation \{[^}]*background-color: var\(--br-situation-bg\)/);
    assert.match(inventory, /<span>Stock mínimo<\/span>\s*<span>Estado del stock<\/span>\s*<\/div>/);
    assert.match(inventory, /@update:modelValue="updateRow\(item\.index, 'minimum_stock', \$event\)"[\s\S]*?class="br-entity-inventory__status"\s/);
    assert.match(branding, /\.br-entity-inventory__field \.form-control:focus::placeholder\s*\{\s*opacity: 0;/);
    assert.match(branding, /\.br-entity-stock-trigger \{[^}]*color: var\(--br-success-hover\)/);
    assert.match(branding, /\.br-entity-stock-trigger\.is-alert \{[^}]*color: var\(--br-warning-hover\)/);
    assert.match(branding, /\.br-entity-product-cell \{[^}]*grid-template-columns: auto minmax\(0, 1fr\)/);
    assert.match(branding, /\.br-entity-stock-trigger:is\(:hover, :focus-visible\) \{[\s\S]*?background: transparent/);
    assert.match(branding, /\.br-entity-prices \{[\s\S]*?gap: 0\.24rem/);
    assert.match(branding, /\.br-entity-inventory__head \{[\s\S]*?background: var\(--br-table-thead-bg\);[\s\S]*?color: var\(--br-table-thead-text\);/);
    assert.match(branding, /\.br-entity-inventory__head > span \{[^}]*justify-content: center;/);
    assert.match(typography, /\.table,\s*\.br-entity-inventory \{[^}]*font-size: var\(--br-font-size-table\)/);
    assert.match(typography, /body \.table > thead > tr > th,\s*\.br-entity-inventory__head \{[^}]*font-size: var\(--br-table-heading-font-size\)/);

});

test("el campo numérico distingue cero de un campo vacío con placeholder", () => {
    const source = readFileSync(new URL("../../resources/js/System/Components/InputNumber.vue", import.meta.url), "utf8");
    const {descriptor} = parse(source);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const generalConfig = {forms: {inputs: {required: "*"}, errors: {styles: {default: "text-danger"}}}};
    const options = new Function("generalConfig", "editableNumber", "separatorNumber", script)(generalConfig, editableNumber, separatorNumber);
    const format = modelValue => options.computed.formattedValue.call({modelValue, decimalPlaces: 2});

    assert.equal(format(0), "0.00");
    assert.equal(format(""), "");
    assert.equal(format(null), "");
    assert.match(descriptor.template.content, /:aria-label="ariaLabel \|\| undefined"\s+:placeholder="placeholder"/);
});

test("los tooltips se restauran al cerrar cualquier modal", async () => {

    const previousWindow = globalThis.window;
    const previousDocument = globalThis.document;
    const listeners = new Map();
    const attributes = new Map([["title", "Ver inventario por almacén"]]);
    const trigger = {
        getAttribute: name => attributes.get(name) ?? null,
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name)
    };

    class Tooltip {
        static instances = new Map();

        constructor(element) {

            this.element = element;
            Tooltip.instances.set(element, this);

        }

        static getInstance(element) {

            return Tooltip.instances.get(element) ?? null;

        }

        dispose() {

            Tooltip.instances.delete(this.element);

        }
    }

    globalThis.window = {
        location: {protocol: "http:", hostname: "demo.blapos.test", href: "http://demo.blapos.test/products"},
        bootstrap: {Tooltip}
    };
    globalThis.document = {
        addEventListener: (name, listener) => listeners.set(name, listener),
        querySelectorAll: selector => selector === '[data-bs-toggle="tooltip"]' ? [trigger] : [],
        createElement: () => ({
            setAttribute(name, value) {

                if(name !== "href") return;

                const url = new URL(value, globalThis.window.location.href);

                Object.assign(this, {
                    href: url.href,
                    protocol: url.protocol,
                    host: url.host,
                    search: url.search,
                    hash: url.hash,
                    hostname: url.hostname,
                    port: url.port,
                    pathname: url.pathname
                });

            }
        })
    };

    try {

        const alerts = await import("../../resources/js/System/Helpers/Alerts.js");

        alerts.tooltips({time: 0});
        assert.ok(Tooltip.getInstance(trigger));

        alerts.tooltips({show: false});
        assert.equal(Tooltip.getInstance(trigger), null);

        listeners.get("hidden.bs.modal")();
        await new Promise(resolve => setTimeout(resolve, 20));
        assert.ok(Tooltip.getInstance(trigger));

    }finally {

        if(previousWindow === undefined) delete globalThis.window;
        else globalThis.window = previousWindow;

        if(previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;

    }

});
