import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("las alertas de stock se consultan solo al abrirlas y se muestran paginadas", () => {
    const page = read("resources/js/System/Pages/Warehouses/inventory/stock.vue");
    const state = read("resources/js/System/Pages/Warehouses/inventory/inventoryState.js");
    const modal = read("resources/js/System/Pages/Warehouses/inventory/StockAlertsModal.vue");
    const sharedModal = read("resources/js/System/Components/Generics/FormModal.vue");
    const modalStyles = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const listStock = state.slice(state.indexOf("async listStock("), state.indexOf("async listStockAlerts("));

    assert.doesNotMatch(listStock, /listStockAlerts|type: "alerts"/);
    assert.doesNotMatch(page, /openStockAlerts|br-inventory-alerts__list|Productos en mínimo o sin existencias/);
    assert.match(page, /<span>Ver alertas<\/span>[\s\S]*?<StockAlertsModal/);
    assert.match(page, /<button[\s\S]*?class="br-btn br-btn-sm br-btn-warning br-inventory__alerts-button"[\s\S]*?fa-bell fa-shake[\s\S]*?<span>Ver alertas<\/span>[\s\S]*?<\/button>/);
    const alertsButton = page.slice(page.indexOf('class="br-btn br-btn-sm br-btn-warning br-inventory__alerts-button"'), page.indexOf("</button>", page.indexOf('class="br-btn br-btn-sm br-btn-warning br-inventory__alerts-button"')));
    assert.match(alertsButton, /:disabled="!canViewStockAlerts"/);
    assert.match(alertsButton, /data-bs-toggle="modal"[\s\S]*?data-bs-target="#inventoryStockAlertsModal"/);
    assert.match(page, /@click="listStockAlerts\(\{\}\)"/);
    assert.match(state, /async listStockAlerts\(\{url = null\} = \{\}\)[\s\S]*?type: "alerts"[\s\S]*?status: "open"/);
    assert.match(modal, /v-for="alert in alerts\.data"[\s\S]*?alert\.warehouse_item\?\.item\?\.name/);
    assert.match(modal, /<Paginator[\s\S]*?:links="alerts\.links"[\s\S]*?@clickPage=/);
    assert.match(modal, /<FormModal[\s\S]*?modal-id="inventoryStockAlertsModal"[\s\S]*?title="ALERTAS DE STOCK"[\s\S]*?dialog-class="modal-dialog-scrollable"[\s\S]*?:show-submit="false"/);
    assert.match(page, /<FormModal[\s\S]*?modal-id="inventoryMovementModal"[\s\S]*?title="REGISTRAR OPERACIÓN"[\s\S]*?dialog-class="modal-dialog-scrollable"/);
    assert.match(page, /<template #footerClose>[\s\S]*?ref="closeMovementModal"[\s\S]*?<template #footer>[\s\S]*?Guardar operación/);
    assert.match(sharedModal, /br-entity-modal__title--form/);
    assert.match(modalStyles, /\.br-entity-modal \.modal-dialog-scrollable \.modal-content \{\s*overflow: hidden;/);
    assert.match(modalStyles, /\.br-entity-modal \.modal-dialog-scrollable \.modal-body\.br-entity-modal__body \{\s*overflow-x: hidden !important;\s*overflow-y: auto !important;/);
});
