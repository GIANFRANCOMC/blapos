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

test("los límites, importes grandes y formatos regionales conservan la fracción exacta", () => {
    numbers.applyNumericInputConfig({round: 3});
    const maxValue = "999999999999.999";
    assert.equal(numbers.isNumber({value: maxValue, maxValue}), true);
    assert.equal(numbers.isNumber({value: "999999999999.99901", maxValue}), false);
    assert.equal(numbers.isNumber({value: "-999999999999.99901", minValue: "-999999999999.999"}), false);
    assert.equal(numbers.commitNumberInput("-2", {min: "-1.005", max: 0, hasNegative: true, decimals: 2}), -1);
    assert.equal(numbers.commitNumberInput("0", {min: "-2", max: "-1.005", hasNegative: true, decimals: 2}), -1.01);
    assert.equal(numbers.commitNumberInput("1.001", {min: "1.001", max: "1.005", decimals: 2}), null);
    assert.equal(numbers.numberInputDraft(".5"), ".5");
    assert.equal(numbers.numberInputDraft("12x"), null);
    assert.equal(numbers.numberInputDraft("-"), null);
    const total = numbers.sumText(Array(11).fill(maxValue));
    assert.equal(total, "10999999999999.989");
    assert.equal(numbers.separatorNumber(total, 3, "de-DE"), "10.999.999.999.999,989");
    assert.equal(numbers.separatorNumber("-0.123", 3, "de-DE"), "-0,123");
    assert.equal(numbers.formatCurrencyTotals([{sign: "S/", total}, {code: "USD", total: "5.012"}]), "S/ 10,999,999,999,999.989 · USD 5.012");
    assert.equal(numbers.formatSignedCurrencyAmount("-10999999999999.989", {sign: "S/"}), "-S/ 10,999,999,999,999.989");
    assert.equal(numbers.formatSignedCurrencyAmount("0.0004", {sign: "S/"}), "S/ 0.000");
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

    for(const [precision, tax, lines, expected] of cases.documentTax) {
        numbers.applyNumericInputConfig({round: precision});
        assert.deepEqual(numbers.documentTax(tax, lines), expected);
    }

    numbers.applyNumericInputConfig({round: 2});
    assert.equal(numbers.calculateTotal({item: {quantity: "1.005", price: "2"}}), "2.02");
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
