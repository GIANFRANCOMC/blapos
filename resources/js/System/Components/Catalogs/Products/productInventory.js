export function summarizeProductStock(record) {

    const warehouseItems = record?.warehouse_items ?? [];

    return {
        total: warehouseItems.reduce((total, item) => total + Number(item.quantity ?? 0), 0),
        alerts: warehouseItems.filter(item => Number(item.quantity ?? 0) <= Number(item.minimum_stock ?? 0)).length
    };

}

export function inventoryStockStatus(inventory) {

    const hasCurrentStock = inventory?.initial_stock !== "" && inventory?.initial_stock !== null;
    const hasMinimumStock = inventory?.minimum_stock !== "" && inventory?.minimum_stock !== null;

    if(!hasCurrentStock && !hasMinimumStock) {

        return {
            className: "is-alert",
            icon: "fa-solid fa-triangle-exclamation",
            text: "Pendiente de registrar"
        };

    }

    const currentStock = Number(inventory?.initial_stock ?? 0);
    const minimumStock = Number(inventory?.minimum_stock ?? 0);

    if(currentStock <= minimumStock) {

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
