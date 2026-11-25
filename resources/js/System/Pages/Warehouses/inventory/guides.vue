<template>
    <InventoryListing ref="inventoryListing" :page="page" v-model:filters="filters" :options="listingOptions"
        :initial-loading="initialLoading" :loading="isCurrentViewLoading" :records="currentRecords"
        :exporting="exporting" :applied-filters="appliedFilters" :applied-warehouse="Boolean(appliedFilters.warehouse?.code)" :error="listError"
        @search="searchCurrentView" @clear="clearFilters" @export="exportCurrentView" @page="listCurrentView" @retry="retryInventoryList">
        <tr role="row" v-for="guide in guideRecords.data" :key="`guide-${guide.id}`">
            <td role="cell" class="fg-record-table__identity">
                <strong>{{ formatSimpleDate(guide.issue_date) }}</strong>
                <span class="fg-table-note">{{ guide.number }}</span>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Almacén</span>
                <div class="fg-record-table__value">
                    <strong>{{ guide.warehouse?.name }}</strong>
                    <span class="fg-table-note">{{ guide.warehouse?.branch?.name }}</span>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Tipo</span>
                <div class="fg-record-table__value">
                    <span :class="['fg-badge', guide.guide_type === 'entry' ? 'fg-badge--success' : 'fg-badge--danger']">
                        {{ guide.guide_type === "entry" ? "Entrada" : "Salida" }}
                    </span>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Detalle</span>
                <div class="fg-record-table__value">
                    <strong>{{ (guide.items || []).length }} producto{{ (guide.items || []).length === 1 ? "" : "s" }}</strong>
                    <div v-for="line in (guide.items || []).slice(0, 2)" :key="line.id" class="mt-2">
                        <ProductIdentity variant="expandable" :product="line.item"/>
                        <span class="fg-table-note">Cantidad: <QuantityValue :value="line.quantity" :unit="line.item?.measurement_unit || line.item"/></span>
                    </div>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Referencia</span>
                <div class="fg-record-table__value">
                    <strong>{{ guide.reference || "-" }}</strong>
                    <span class="fg-table-note">{{ guide.reason }}</span>
                </div>
            </td>
            <td role="cell" class="fg-record-table__state">
                <span class="fg-badge fg-badge--success">
                    {{ guide.status === "confirmed" ? "Confirmada" : guide.status }}
                </span>
            </td>
        </tr>
    </InventoryListing>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
