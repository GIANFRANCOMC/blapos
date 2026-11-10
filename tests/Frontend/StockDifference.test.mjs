import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";
import {formatDecimalNumber} from "../../resources/js/Shared/DecimalPrecision.js";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("Diferencia conserva signo, unidad y tono en ambas tablas de stock", () => {
    const source = read("resources/js/System/Pages/Warehouses/inventory/StockDifference.vue");
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const skeleton = read("resources/js/System/Pages/Warehouses/inventory/InventorySkeleton.vue");
    const styles = read("resources/css/System/br-branding/82-inventory.css");
    const {descriptor, errors} = parse(source);

    assert.equal(errors.length, 0);
    assert.match(page, /<th>Diferencia<\/th>[\s\S]*?<StockDifference :current="record\.stock_quantity" :minimum="record\.minimum_stock"/);
    assert.match(modal, /<th>Diferencia<\/th>[\s\S]*?<th scope="col">Diferencia<\/th>[\s\S]*?<StockDifference :current="alert\.quantity" :minimum="alert\.minimum_stock"/);
    assert.match(skeleton, /stock: \["Producto", "Stock actual", "Stock mínimo", "Diferencia", "Estado del stock", ""\]/);
    assert.match(styles, /\.br-inventory__difference\.is-positive \{ color: var\(--br-success-hover\); \}/);
    assert.match(styles, /\.br-inventory__difference\.is-negative \{ color: var\(--br-danger-strong\); \}/);
    assert.match(styles, /body\.br-layout \.br-inventory__stock-current,\s*body\.br-layout \.br-inventory__difference \{\s*font-size: var\(--br-stock-quantity-font-size\);\s*\}/);
    assert.match(descriptor.template.content, /<QuantityValue[^>]*:value="difference" :unit="unit" signed/);

    const script = descriptor.script.content
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default", "return");
    const component = new Function("QuantityValue", "normalizeNumber", "fixedNumber", script)(
        {},
        value => Number(value || 0),
        value => formatDecimalNumber(value, 3)
    );
    const present = (current, minimum) => {
        const instance = {current, minimum, unit: {abbreviation: "UND"}};
        Object.defineProperty(instance, "difference", {get: () => component.computed.difference.call(instance)});
        return {
            tone: component.computed.tone.call(instance),
            value: instance.difference
        };
    };

    assert.deepEqual(present(19, 9), {tone: "is-positive", value: 10});
    assert.deepEqual(present(1, 9), {tone: "is-negative", value: -8});
    assert.deepEqual(present(9, 9), {tone: "is-neutral", value: 0});
    assert.deepEqual(present(0, 0.0004), {tone: "is-neutral", value: 0});
});
