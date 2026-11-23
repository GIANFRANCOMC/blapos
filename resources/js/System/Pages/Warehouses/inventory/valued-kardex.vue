<template>
    <InventoryListing ref="inventoryListing" :page="page" v-model:filters="filters" :options="listingOptions"
        :initial-loading="initialLoading" :loading="isCurrentViewLoading" :records="currentRecords"
        :exporting="exporting" :applied-warehouse="Boolean(appliedFilters.warehouse?.code)" :error="listError"
        @search="searchCurrentView" @clear="clearFilters" @export="exportCurrentView" @page="listCurrentView" @retry="retryInventoryList">
        <tr v-for="movement in kardexRecords.data" :key="`valued-${movement.id}`">
            <td>
                <ProductIdentity variant="expandable" :product="movement.item"/>
            </td>
            <td>
                <KardexMovementDetails
                    :movement="movement"
                    :date-label="formatDate(movement.created_at)"
                    :type-label="movementTypeLabel(movement.movement_type)"
                    :origin-label="originLabel(movement.origin_type)"/>
            </td>
            <td class="fg-align-end">
                <StockDifference :current="movement.quantity_change" :unit="movement.item?.measurement_unit || movement.item"/>
            </td>
            <td class="fg-align-end text-nowrap">{{ currencyNumber(movement.unit_cost) }}</td>
            <td :class="['fg-align-end fg-inventory-quantity', Number(movement.value_change) >= 0 ? 'fg-value-positive' : 'fg-value-negative']">
                {{ signedCurrency(movement.value_change) }}
            </td>
            <td class="fg-align-end">
                <span class="fg-inventory-quantity">{{ currencyNumber(movement.value_after) }}</span>
                <span class="fg-table-note text-nowrap">Antes: {{ currencyNumber(movement.value_before) }}</span>
            </td>
        </tr>
    </InventoryListing>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
