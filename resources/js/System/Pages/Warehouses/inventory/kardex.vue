<template>
    <InventoryListing ref="inventoryListing" :page="page" v-model:filters="filters" :options="listingOptions"
        :initial-loading="initialLoading" :loading="isCurrentViewLoading" :records="currentRecords"
        :exporting="exporting" :applied-filters="appliedFilters" :applied-warehouse="Boolean(appliedFilters.warehouse?.code)" :error="listError"
        @search="searchCurrentView" @clear="clearFilters" @export="exportCurrentView" @page="listCurrentView" @retry="retryInventoryList">
        <tr role="row" v-for="movement in kardexRecords.data" :key="movement.id">
            <td role="cell" class="fg-record-table__identity">
                <ProductIdentity variant="expandable" :product="movement.item"/>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Movimiento</span>
                <div class="fg-record-table__value">
                    <KardexMovementDetails
                        :movement="movement"
                        :date-label="formatDate(movement.created_at)"
                        :type-label="movementTypeLabel(movement.movement_type)"
                        :origin-label="originLabel(movement.origin_type)"/>
                </div>
            </td>
            <td role="cell" class="fg-align-end">
                <span class="fg-record-table__label" aria-hidden="true">Variación</span>
                <div class="fg-record-table__value">
                    <StockDifference :current="movement.quantity_change" :unit="movement.item?.measurement_unit || movement.item"/>
                </div>
            </td>
            <td role="cell" class="fg-align-end">
                <span class="fg-record-table__label" aria-hidden="true">Saldo</span>
                <div class="fg-record-table__value">
                    <QuantityValue class="fg-inventory-quantity" :value="movement.quantity_after" :unit="movement.item?.measurement_unit || movement.item"/>
                    <span class="fg-table-note">Antes: <QuantityValue :value="movement.quantity_before" :unit="movement.item?.measurement_unit || movement.item"/></span>
                </div>
            </td>
        </tr>
    </InventoryListing>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
