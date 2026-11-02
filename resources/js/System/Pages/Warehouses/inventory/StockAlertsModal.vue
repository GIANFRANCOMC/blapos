<template>
    <div
        id="inventoryStockAlertsModal"
        class="modal fade br-entity-modal br-modal-standard"
        tabindex="-1"
        aria-labelledby="inventoryStockAlertsModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <h2 id="inventoryStockAlertsModalTitle" class="modal-title br-entity-modal__title">ALERTAS DE STOCK</h2>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body">
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
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "StockAlertsModal",
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
