import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {inventoryStockStatus, summarizeProductStock} from "../../resources/js/System/Components/Catalogs/Products/productInventory.js";

test("el resumen mantiene las alertas y el total del listado", () => {

    const record = {
        warehouse_items: [
            {quantity: "8.000", minimum_stock: "3.000"},
            {quantity: "2.000", minimum_stock: "2.000"}
        ]
    };

    assert.deepEqual(summarizeProductStock(record), {total: 10, alerts: 1});
    assert.deepEqual(summarizeProductStock({warehouse_items: []}), {total: 0, alerts: 0});
    assert.equal(inventoryStockStatus({initial_stock: 2, minimum_stock: 2}).className, "is-alert");
    assert.equal(inventoryStockStatus({initial_stock: 8, minimum_stock: 3}).className, "is-healthy");

});

test("el acceso al inventario abre una modal de consulta para alertas y estados saludables", () => {

    const page = readFileSync(new URL("../../resources/js/System/Pages/Catalogs/products/main.vue", import.meta.url), "utf8");
    const modal = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductInventoryModal.vue", import.meta.url), "utf8");
    const editor = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductEditor.vue", import.meta.url), "utf8");
    const inventory = readFileSync(new URL("../../resources/js/System/Components/Catalogs/Products/ProductWarehouseInventory.vue", import.meta.url), "utf8");
    const branding = readFileSync(new URL("../../resources/css/System/br-branding/81-core-roles-entities.css", import.meta.url), "utf8");

    assert.equal(parse(page, {filename: "products/main.vue"}).errors.length, 0);
    assert.equal(parse(modal, {filename: "ProductInventoryModal.vue"}).errors.length, 0);
    assert.equal(parse(inventory, {filename: "ProductWarehouseInventory.vue"}).errors.length, 0);
    assert.match(page, /<ProductInventoryModal ref="productInventoryModal"\/>/);
    assert.match(page, /@click="openInventoryModal\(record\)"/);
    assert.match(page, /fa-solid fa-triangle-exclamation' : 'fa-solid fa-box-open'/);
    assert.match(modal, /:show-submit="false"/);
    assert.match(modal, /close-button-text="Cerrar"/);
    assert.match(modal, /record\?\.warehouse_items/);
    assert.match(modal, /<ProductWarehouseInventory v-if="inventoryRows\.length" :rows="inventoryRows"\/>/);
    assert.match(editor, /<ProductWarehouseInventory[\s\S]*?v-model:rows="productForm\.data\.inventory"[\s\S]*?mode="edit"/);
    assert.match(modal, /record\.internal_code/);
    assert.match(modal, /record\.barcode/);
    assert.match(modal, /record\.brand\?\.name/);
    assert.match(inventory, /Stock actual/);
    assert.match(inventory, /Stock mínimo/);
    assert.match(inventory, /v-if="mode === 'edit' && !isUpdate"/);
    assert.match(inventory, /v-if="mode === 'edit'"/);
    assert.match(inventory, /v-if="item\.status\.className === 'is-alert'"/);
    assert.match(branding, /\.br-entity-stock-trigger\.is-alert/);
    assert.match(branding, /\.br-entity-stock-row \{[\s\S]*?gap: 0\.12rem/);
    assert.match(branding, /\.br-entity-stock-trigger:is\(:hover, :focus-visible\) \{[\s\S]*?background: transparent/);
    assert.match(branding, /\.br-entity-prices \{[\s\S]*?gap: 0\.24rem/);

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
