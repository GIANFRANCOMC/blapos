<template>
    <FormModal
        modal-id="inventoryStockTraceabilityModal"
        title="TRAZABILIDAD DEL PRODUCTO"
        :subtitle="product?.name || ''"
        modal-size="modal-xl"
        :show-submit="false"
        :close-disabled="loading"
        :keyboard="false"
        close-button-text="Cerrar">
        <template #body>
            <p class="br-operational-scope mb-3">
                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                <span>{{ warehouse?.displayLabel || warehouse?.label }}</span>
            </p>
            <section class="br-entity-list" aria-label="Movimientos del producto" :aria-busy="loading">
                <div class="table-responsive br-inventory-traceability__scroll">
                    <table class="table br-entity-table br-inventory-table-headings mb-0">
                        <thead class="br-table-header-surface">
                            <tr>
                                <th>Fecha y responsable</th>
                                <th>Movimiento</th>
                                <th class="text-end">Variación</th>
                                <th class="text-end">Saldo</th>
                                <th>Motivo y origen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading" class="br-table-state"><td colspan="5" class="py-4"><Loader/></td></tr>
                            <tr v-else-if="error" class="br-table-state">
                                <td colspan="5" class="py-4 text-center">
                                    <p class="text-danger" role="alert">{{ error }}</p>
                                    <button type="button" class="br-btn br-btn-cancel" @click="$emit('retry')">Reintentar</button>
                                </td>
                            </tr>
                            <template v-else-if="records.data?.length">
                                <tr v-for="movement in records.data" :key="movement.id">
                                    <td class="text-nowrap">
                                        <span>{{ formatters.formatDate(movement.created_at) }}</span>
                                        <span class="br-inventory__meta">{{ movement.user?.name || 'Proceso del sistema' }}</span>
                                    </td>
                                    <td><span :class="['br-inventory-movement', `is-${movement.movement_type}`]">{{ formatters.movementTypeLabel(movement.movement_type) }}</span></td>
                                    <td :class="['text-end text-nowrap', Number(movement.quantity_change) > 0 ? 'text-success' : Number(movement.quantity_change) < 0 ? 'text-danger' : 'text-muted']">{{ formatters.signedQuantity(movement.quantity_change, product) }}</td>
                                    <td class="text-end text-nowrap">
                                        <span>{{ formatters.quantityWithUnit(movement.quantity_after, product) }}</span>
                                        <span class="br-inventory__meta">Antes: {{ formatters.quantityWithUnit(movement.quantity_before, product) }}</span>
                                    </td>
                                    <td>
                                        <span>{{ movement.reason }}</span>
                                        <span class="br-inventory__meta">{{ formatters.originLabel(movement.origin_type) }}<template v-if="movement.reference"> · {{ movement.reference }}</template></span>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else class="br-table-state"><td colspan="5" class="py-4"><WithoutData type="image" text="Este producto no tiene movimientos en el almacén seleccionado."/></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <Paginator :links="records.links || []" :pagination="records" :loading="loading" @clickPage="$emit('page', $event)"/>
        </template>
    </FormModal>
</template>

<script>
import FormModal from "@System/Components/Generics/FormModal.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import Paginator from "@System/Components/Paginator.vue";

export default {
    components: {FormModal, Loader, WithoutData, Paginator},
    emits: ["page", "retry"],
    props: {
        product: {type: Object, default: null}, warehouse: {type: Object, default: null},
        records: {type: Object, required: true}, formatters: {type: Object, required: true},
        loading: {type: Boolean, default: false}, error: {type: String, default: ""}
    }
};
</script>
