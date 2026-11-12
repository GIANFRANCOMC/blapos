import {sumText} from "../../../../Shared/Numbers.js";
import {fromLevels, labels} from "../../../../Shared/StockStatus.js";

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
    return {code, text: labels[code]};

}
