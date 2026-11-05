import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las celdas bloqueadas de control de stock y traslados no conservan cursor de escritura", () => {
    const productSelect = read("resources/js/System/Components/Generics/ProductSelect.vue");
    const numberInput = read("resources/js/System/Components/InputNumber.vue");
    const styles = read("resources/css/Shared/disabled-controls.css");

    assert.equal(parse(productSelect).errors.length, 0);
    assert.equal(parse(numberInput).errors.length, 0);
    assert.match(productSelect, /:searchable="!disabled"/);
    assert.match(productSelect, /:disabled="disabled"/);
    assert.equal((numberInput.match(/:disabled="disabled"/g) ?? []).length, 2);
    assert.match(styles, /\.v-select\.vs--disabled \.vs__search,[\s\S]*?caret-color: transparent !important;/);

    for(const [page, condition] of [["stock", "canConfigureMovement"], ["transfers", "canConfigureTransfer"]]) {
        const source = read(`resources/js/System/Pages/Warehouses/inventory/${page}.vue`);
        assert.match(source, new RegExp(`<ProductSelect[\\s\\S]*?:disabled="!${condition}"`));
        assert.match(source, new RegExp(`<InputNumber[^>]*:disabled="!${condition}"`));
    }
});
