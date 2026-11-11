import {sumText} from "../../../../Shared/Numbers.js";
import {fromLevels, labels} from "../../../../Shared/StockStatus.js";

const statusStyles = {
    empty: {className: "is-empty", icon: "fa-solid fa-circle-exclamation"},
    low: {className: "is-alert", icon: "fa-solid fa-triangle-exclamation"},
    healthy: {className: "is-healthy", icon: "fa-solid fa-circle-check"}
};

export function summarizeProductStock(record) {

    const warehouseItems = record?.warehouse_items ?? [];

    return {
        total: record?.total_stock_quantity ?? sumText(warehouseItems.map(item => item.quantity)),
        alerts: warehouseItems.filter(item => fromLevels(item.quantity, item.minimum_stock) !== "healthy").length
    };

}

export function inventoryStockStatus(inventory) {

    const quantity = inventory?.initial_stock;
    if(quantity === "" || quantity == null) return null;

    const code = fromLevels(quantity, inventory?.minimum_stock);
    return {...statusStyles[code], text: labels[code]};

}
