import assert from "node:assert/strict";
import test from "node:test";
import {normalizePrecision} from "../../resources/js/Shared/DecimalPrecision.js";

globalThis.window = {location: {protocol: "http:", hostname: "localhost"}, decimalPrecision: 0};

const system = await import("../../resources/js/System/Helpers/NumberUtils.js");
const guest = await import("../../resources/js/Guest/Helpers/Utils.js");
const {applyGeneralConfig} = await import("../../resources/js/System/Helpers/Constants.js");

test("System y Guest toman la precisión de la empresa, incluido cero", () => {
    assert.equal(system.fixedNumber(12.5), "13");
    assert.equal(guest.fixedNumber(-12.5), "-13");
    assert.equal(system.decimalStep(), 1);
    assert.equal(normalizePrecision(undefined), 2);
    assert.equal(normalizePrecision(4), 3);

    for(const [precision, expected] of [[0, "1,235"], [1, "1,234.6"], [2, "1,234.57"], [3, "1,234.568"]]) {
        applyGeneralConfig({forms: {inputs: {round: precision}}});
        assert.equal(system.separatorNumber(1234.5675), expected);
        assert.equal(system.decimalStep(), 10 ** -precision);
    }
});

test("los empates se redondean igual en inputs, cantidades y montos", () => {
    assert.equal(system.fixedNumber("1.005", 2), "1.01");
    assert.equal(system.quantityWithUnit(-1.005, {abbreviation: "UND"}, 2), "-1.01 UND");
    assert.equal(guest.fixedNumber(-1.005, 2), "-1.01");
    assert.equal(guest.separatorNumber(-0.004, 2), "0.00");
});
