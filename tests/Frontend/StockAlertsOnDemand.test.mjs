import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las alertas de stock se consultan solo al abrirlas y se muestran paginadas", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const listStock = state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts("));

    assert.doesNotMatch(listStock, /listStockAlerts|type: "alerts"/);
    assert.doesNotMatch(page, /openStockAlerts|br-inventory-alerts__list|Productos en mínimo o sin existencias/);
    assert.match(page, /<span>Ver alertas<\/span>[\s\S]*?<StockAlertsModal/);
    assert.match(page, /@click="listStockAlerts\(\{\}\)"/);
    assert.match(state, /async listStockAlerts\(\{url = null\} = \{\}\)[\s\S]*?type: "alerts"[\s\S]*?status: "open"/);
    assert.match(modal, /v-for="alert in alerts\.data"[\s\S]*?alert\.warehouse_item\?\.item\?\.name/);
    assert.match(modal, /<Paginator[\s\S]*?:links="alerts\.links"[\s\S]*?@clickPage=/);
});
