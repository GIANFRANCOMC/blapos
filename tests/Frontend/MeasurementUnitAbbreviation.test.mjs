import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

globalThis.window ??= {location: {protocol: "http:", hostname: "localhost"}};

const {quantityWithUnit} = await import("../../resources/js/System/Helpers/NumberUtils.js");

test("las cantidades muestran la abreviatura registrada sin inventar unidades", () => {
    assert.equal(quantityWithUnit(19, {abbreviation: "UND"}, 0), "19 UND");
    assert.equal(quantityWithUnit(1, {unitAbbreviation: "UND"}, 0), "1 UND");
    assert.equal(quantityWithUnit(2, {plural_name: "UNIDADES"}, 0), "2");
    assert.equal(quantityWithUnit(2, null, 0), "2");
});

test("inventario conserva la abreviatura desde la consulta hasta las vistas y exportaciones", () => {
    const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
    const config = read("app/Services/System/Warehouses/StockManagement/StockManagementConfigService.php");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const stock = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const transfers = read("resources/js/System/Pages/Warehouses/inventory/transfers.vue");
    const report = read("app/Exports/System/Warehouses/InventoryReportExport.php");

    assert.match(config, /measurementUnit:id,abbreviation/);
    assert.match(state, /unitAbbreviation: product\.measurement_unit\?\.abbreviation/);
    assert.match(stock, /Unidad: \{\{ item\.product\.unitAbbreviation \}\}/);
    assert.match(transfers, /Unidad: \{\{ item\.product\.unitAbbreviation \}\}/);
    assert.match(report, /measurementUnit\?->abbreviation/);
});

test("los prefijos de caché no usan versiones", () => {
    const config = readFileSync(new URL("../../app/Services/System/Warehouses/StockManagement/StockManagementConfigService.php", import.meta.url), "utf8");
    const cash = readFileSync(new URL("../../app/Services/System/Finance/CashRegisterConfigService.php", import.meta.url), "utf8");

    assert.match(config, /return "inventory";/);
    assert.match(cash, /return "cash_registers";/);
    assert.doesNotMatch(`${config}\n${cash}`, /(?:_v|:v)\d+/);
});
