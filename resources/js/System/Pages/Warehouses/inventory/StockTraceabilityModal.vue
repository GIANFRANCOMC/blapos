<template>
    <FormModal modal-id="inventoryStockTraceabilityModal" title="Trazabilidad del producto" modal-class="fg-inventory-dialog" modal-size="modal-xl"
        :show-submit="false" :close-disabled="loading" :keyboard="false">
        <template #body>
            <div class="fg-stack">
                <div class="fg-field">
                    <ProductIdentity v-if="product" variant="expandable" :product="product"/>
                    <p class="fg-help"><i class="fa-solid fa-warehouse" aria-hidden="true"></i> {{ warehouse?.displayLabel || warehouse?.label }}</p>
                </div>
                <section aria-label="Movimientos del producto" :aria-busy="loading">
                    <InventoryTable :columns="columns" min-width="740px" scroll-records :loading="loading" loading-label="Buscando movimientos…"
                        :error="error" :empty="!records.data?.length" empty-title="Este producto no tiene movimientos en el almacén seleccionado"
                        @retry="$emit('retry')">
                        <tr v-for="movement in records.data" :key="movement.id">
                            <td>
                                <time :datetime="movement.created_at">{{ formatters.formatDate(movement.created_at) }}</time>
                                <span class="fg-table-note">{{ movement.user?.name || 'Proceso del sistema' }}</span>
                            </td>
                            <td><span :class="['fg-badge', 'fg-badge--' + inventoryBadgeTones[movement.movement_type]]">{{ formatters.movementTypeLabel(movement.movement_type) }}</span></td>
                            <td class="fg-align-end"><StockDifference :current="movement.quantity_change" :unit="product?.measurement_unit || product"/></td>
                            <td class="fg-align-end text-nowrap">
                                <QuantityValue class="fg-inventory-quantity" :value="movement.quantity_after" :unit="product?.measurement_unit || product"/>
                                <span class="fg-table-note">Antes: <QuantityValue :value="movement.quantity_before" :unit="product?.measurement_unit || product"/></span>
                            </td>
                            <td>
                                <span>{{ movement.reason }}</span>
                                <span class="fg-table-note">{{ formatters.originLabel(movement.origin_type) }}<template v-if="movement.reference"> · {{ movement.reference }}</template></span>
                            </td>
                        </tr>
                    </InventoryTable>
                    <Paginator aria-label="Paginación de movimientos del producto" :links="records.links || []" :pagination="records" :loading="loading" @click-page="$emit('page', $event)"/>
                </section>
            </div>
        </template>
    </FormModal>
</template>

<script setup>
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import Paginator from "@System/Components/Paginator.vue";
import InventoryTable from "./InventoryTable.vue";
import StockDifference from "./StockDifference.vue";
import {inventoryBadgeTones} from "./inventoryPresentation.js";

const columns = [
    {label: "Fecha y responsable", width: "22%"}, {label: "Movimiento", width: "16%"},
    {label: "Variación", align: "end"}, {label: "Saldo", align: "end"}, {label: "Motivo y origen", width: "28%"}
];
defineEmits(["page", "retry"]);
defineProps({
    product: Object, warehouse: Object,
    records: {type: Object, required: true}, formatters: {type: Object, required: true},
    loading: Boolean, error: String
});
</script>
