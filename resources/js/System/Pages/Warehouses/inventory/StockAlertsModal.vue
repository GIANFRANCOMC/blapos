<template>
    <FormModal
        modal-id="inventoryStockAlertsModal"
        title="ALERTAS DE STOCK"
        modal-class="br-modal-standard"
        modal-size="modal-lg"
        dialog-class="modal-dialog-scrollable"
        :show-submit="false"
        close-button-text="Cerrar">
        <template #body>
            <p class="mb-3 text-muted">
                {{ warehouseName }}<span v-if="!loading && !error"> · {{ alerts.total || 0 }} alerta{{ alerts.total === 1 ? '' : 's' }}</span>
            </p>
            <section class="br-entity-list" aria-label="Alertas abiertas del almacén">
                <div class="table-responsive">
                    <table class="table br-entity-table mb-0">
                        <thead class="br-table-header-surface">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col" class="text-end">Stock actual</th>
                                <th scope="col" class="text-end">Stock mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="3" class="py-4"><Loader/></td>
                            </tr>
                            <tr v-else-if="error">
                                <td colspan="3" class="py-4 text-center">
                                    <span class="text-danger">{{ error }}</span>
                                    <button type="button" class="br-btn br-btn-sm br-btn-cancel ms-2" @click="$emit('retry')">Reintentar</button>
                                </td>
                            </tr>
                            <template v-else-if="alerts.data?.length">
                                <tr v-for="alert in alerts.data" :key="alert.id">
                                    <td>
                                        <strong>{{ alert.warehouse_item?.item?.name || 'Producto sin nombre' }}</strong>
                                        <small v-if="alert.warehouse_item?.item?.internal_code" class="d-block text-muted">
                                            Ref. {{ alert.warehouse_item.item.internal_code }}
                                        </small>
                                    </td>
                                    <td class="text-end">{{ formatQuantity(alert.quantity) }}</td>
                                    <td class="text-end">{{ formatQuantity(alert.minimum_stock) }}</td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="3" class="py-4 text-center text-muted">No hay alertas de stock abiertas en este almacén.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <Paginator v-if="!loading && !error && alerts.total > 0" :links="alerts.links" @clickPage="$emit('page', $event)"/>
        </template>
    </FormModal>
</template>

<script>
import FormModal from "@System/Components/Generics/FormModal.vue";

export default {
    name: "StockAlertsModal",
    components: {FormModal},
    emits: ["page", "retry"],
    props: {
        alerts: {type: Object, required: true},
        warehouseName: {type: String, default: ""},
        loading: {type: Boolean, default: false},
        error: {type: String, default: ""}
    },
    methods: {
        formatQuantity(value) {
            const quantity = Number(value);
            return Number.isFinite(quantity) ? quantity.toLocaleString("es-PE", {maximumFractionDigits: 3}) : "—";
        }
    }
};
</script>
