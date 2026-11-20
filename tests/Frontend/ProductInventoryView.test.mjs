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
    for(const initial_stock of ["", null, undefined, 0, "0", "0.00"]) {
        assert.deepEqual(inventoryStockStatus({initial_stock, minimum_stock: 3}), {code: "empty", text: "Agotado"});
    }

});

test("el acceso al inventario abre una modal de consulta para alertas y estados saludables", () => {

 const read=p=>readFileSync(new URL("../../resources/js/System/"+p,import.meta.url),"utf8");
 const page=read("Pages/Catalogs/products/main.vue"),modal=read("Components/Catalogs/Products/ProductInventoryModal.vue"),editor=read("Components/Catalogs/Products/ProductEditor.vue"),inventory=read("Components/Catalogs/Products/ProductWarehouseInventory.vue");
 for(const source of [page,modal,editor,inventory]) assert.equal(parse(source).errors.length,0);
 assert.match(page,/@click="openInventoryModal\(record\)"/);
 assert.match(modal,/:show-submit="false"/);
 assert.match(modal,/<ProductWarehouseInventory/);
 for(const field of ["record.internal_code","record.barcode","record.brand?.name"]) assert.ok(modal.includes(field));
 assert.match(editor,/v-model:rows="productForm.data.inventory"/);
 assert.match(inventory,/Sucursal y almacén/);
 assert.match(inventory,/Stock actual/);
 assert.match(inventory,/Stock mínimo/);
 assert.match(inventory,/v-if="mode === 'edit' && !isUpdate"/);
 assert.match(inventory,/@update:modelValue="updateRow\(item.index, 'minimum_stock', \$event\)"/);
 assert.doesNotMatch(inventory,/remove-row|fa-trash|Restaurar/);
 assert.doesNotMatch(editor,/@remove-row|restoreInventoryRows/);

});

test("el campo numérico distingue cero de un campo vacío con placeholder", () => {
    const source = readFileSync(new URL("../../resources/js/System/Components/InputNumber.vue", import.meta.url), "utf8");
    const {descriptor} = parse(source);
    const script = descriptor.script.content.replace(/^import .*;\s*$/gm, "").replace("export default", "return");
    const generalConfig = {forms: {inputs: {required: "*"}, errors: {styles: {default: "text-danger"}}}};
    const options = new Function("generalConfig", "editableNumber", "separatorNumber", "FgField", script)(generalConfig, editableNumber, separatorNumber, {});
    const format = modelValue => options.computed.formattedValue.call({modelValue, decimalPlaces: 2});

    assert.equal(format(0), "0.00");
    assert.equal(format(""), "");
    assert.equal(format(null), "");
    assert.match(descriptor.template.content, /:placeholder="placeholder"[\s\S]*?:aria-label="ariaLabel \|\| undefined"/);
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
