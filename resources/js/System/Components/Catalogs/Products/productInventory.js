import {compareNumbers, sumText} from "../../../../Shared/Numbers.js";

export function summarizeProductStock(record) {

    const warehouseItems = record?.warehouse_items ?? [];

    return {
        total: record?.total_stock_quantity ?? sumText(warehouseItems.map(item => item.quantity)),
        alerts: warehouseItems.filter(item => compareNumbers(item.quantity, item.minimum_stock) <= 0).length
    };

}

export function inventoryStockStatus(inventory) {

    const hasCurrentStock = inventory?.initial_stock !== "" && inventory?.initial_stock != null;
    const hasMinimumStock = inventory?.minimum_stock !== "" && inventory?.minimum_stock != null;

    if(!hasCurrentStock && !hasMinimumStock) {

        return {
            className: "is-alert",
            icon: "fa-solid fa-triangle-exclamation",
            text: "Pendiente de registrar"
        };

    }

    const currentStock = inventory?.initial_stock ?? 0;
    const minimumStock = inventory?.minimum_stock ?? 0;

    if(compareNumbers(currentStock, minimumStock) <= 0) {

        return {
            className: "is-alert",
            icon: "fa-solid fa-triangle-exclamation",
            text: "Stock bajo o en el mínimo"
        };

    }

    return {
        className: "is-healthy",
        icon: "fa-solid fa-circle-check",
        text: "Inventario saludable"
    };

}
