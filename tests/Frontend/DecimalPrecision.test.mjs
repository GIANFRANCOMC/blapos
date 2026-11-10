import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
globalThis.window = {location: {protocol: "http:", hostname: "localhost"}, numericInputConfig: {round: 0, maxDecimals: 3, minValue: 0, maxValue: 2000}};

const numbers = await import("../../resources/js/Shared/Numbers.js");
const system = await import("../../resources/js/System/Helpers/Utils.js");
const guest = await import("../../resources/js/Guest/Helpers/Utils.js");
const {applyGeneralConfig} = await import("../../resources/js/System/Helpers/Constants.js");

test("System y Guest toman la precisión de la empresa, incluido cero", () => {
    assert.equal(system.fixedNumber(12.5), "13");
    assert.equal(guest.fixedNumber(-12.5), "-13");
    assert.equal(system.decimalStep(), 1);
    assert.equal(numbers.normalizePrecision(undefined), 2);
    assert.equal(numbers.normalizePrecision(4), 3);

    for(const [precision, expected] of [[0, "1,235"], [1, "1,234.6"], [2, "1,234.57"], [3, "1,234.568"]]) {
        applyGeneralConfig({forms: {inputs: {round: precision}}});
        assert.equal(system.separatorNumber(1234.5675), expected);
        assert.equal(guest.separatorNumber(1234.5675), expected);
        assert.equal(system.decimalStep(), 10 ** -precision);
    }
});

test("el análisis y los límites de los campos usan la configuración compartida", () => {
    const bounds = numbers.numericBounds();
    assert.deepEqual(bounds, {min: 0, max: 2000});
    assert.equal(numbers.commitNumberInput("1,234.565", {...bounds, decimals: 2}), 1234.57);
    assert.equal(numbers.commitNumberInput("2,001", {...bounds, decimals: 0}), 2000);
    assert.equal(numbers.commitNumberInput("1.005", {min: 0, max: 1.005, decimals: 2}), 1);
    assert.equal(numbers.commitNumberInput("1", {min: 1.004, max: 2, decimals: 2}), 1.01);
    assert.equal(numbers.commitNumberInput("12,34", bounds), null);
    assert.equal(numbers.isNumber({value: "0", maxValue: 0}), true);
    assert.equal(numbers.isNumber({value: "12,34"}), false);
    assert.equal(numbers.isNumberInputKey(".", {decimals: 0}), false);
    assert.equal(numbers.isNumberInputKey(".", {decimals: 3}), true);
});

test("los empates se redondean igual en inputs, cantidades y montos", () => {
    assert.equal(system.fixedNumber("1.005", 2), "1.01");
    assert.equal(system.quantityWithUnit(-1.005, {abbreviation: "UND"}, 2), "-1.01 UND");
    assert.equal(guest.fixedNumber(-1.005, 2), "-1.01");
    assert.equal(guest.separatorNumber(-0.004, 2), "0.00");
});

test("el contrato decimal compartido coincide con los cálculos del backend", () => {
    const cases = JSON.parse(readFileSync(new URL("../Fixtures/decimal-cases.json", import.meta.url), "utf8"));

    for(const [value, precision, expected] of cases.format) {
        assert.equal(numbers.fixedNumber(value, precision), expected);
    }

    for(const [operation, precision, args, expected] of cases.arithmetic) {
        numbers.applyNumericInputConfig({round: precision});
        assert.equal(numbers.fixedNumber(numbers[operation](...args)), expected);
    }
});

test("el cálculo de venta aplica el impuesto por línea después de redondear su importe", () => {
    numbers.applyNumericInputConfig({round: 2});
    const total = numbers.lineTotal("0.30", "0.35");

    assert.equal(total, 0.11);
    assert.deepEqual(numbers.documentTax({rate: "18"}, [
        {total, includesTax: true},
        {total, includesTax: false}
    ]), {baseAmount: 0.20, amount: 0.04, totalImpact: 0.02});
    assert.deepEqual(numbers.documentTax({rate: "18", operation_type: "subtraction"}, [
        {total, includesTax: true}
    ]), {baseAmount: 0.11, amount: -0.02, totalImpact: -0.02});
});
