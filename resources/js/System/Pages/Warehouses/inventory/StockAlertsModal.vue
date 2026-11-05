<template>
    <FormModal
        modal-id="inventoryStockAlertsModal"
        title="ALERTAS DE STOCK"
        modal-class="br-modal-standard br-inventory-alerts"
        modal-size="modal-lg"
        body-class="br-inventory-alerts__body"
        :show-submit="false"
        close-button-text="Cerrar">
        <template #body>
            <div class="br-inventory-alerts__summary">
                <span class="br-inventory-alerts__location-icon" aria-hidden="true">
                    <i class="fa-solid fa-warehouse"></i>
                </span>
                <div class="br-inventory-alerts__location">
                    <span class="br-inventory-alerts__eyebrow">Sede y almacén</span>
                    <strong>{{ warehouse?.branchName || 'Sin sede asignada' }}</strong>
                    <span>{{ warehouse?.warehouseName || warehouse?.label || 'Almacén no seleccionado' }}</span>
                </div>
                <div class="br-inventory-alerts__count" aria-live="polite">
                    <strong>{{ loading && !alerts.total ? '—' : alerts.total || 0 }}</strong>
                    <span>Alerta{{ alerts.total === 1 ? '' : 's' }} abierta{{ alerts.total === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <section class="br-entity-list br-inventory-alerts__list" aria-label="Alertas abiertas del almacén" :aria-busy="loading">
                <div class="table-responsive br-inventory-alerts__table-scroll">
                    <table class="table br-entity-table br-inventory-table-headings br-inventory-alerts__table mb-0">
                        <thead class="br-table-header-surface">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Stock actual</th>
                                <th scope="col">Stock mínimo</th>
                                <th scope="col">Detectada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading && !alerts.data?.length">
                                <td colspan="4" class="py-4"><Loader/></td>
                            </tr>
                            <tr v-else-if="error && !alerts.data?.length">
                                <td colspan="4" class="py-4 text-center">
                                    <span class="text-danger">{{ error }}</span>
                                    <button type="button" class="br-btn br-btn-sm br-btn-cancel ms-2" @click="$emit('retry')">Reintentar</button>
                                </td>
                            </tr>
                            <template v-else-if="alerts.data?.length">
                                <tr v-for="alert in alerts.data" :key="alert.id">
                                    <td>
                                        <strong class="br-inventory__product-name">{{ alert.warehouse_item?.item?.name || 'Producto sin nombre' }}</strong>
                                        <span class="br-inventory__product-identifiers">
                                            <span>
                                                <small>Cód. interno</small>
                                                <span>{{ alert.warehouse_item?.item?.internal_code || '—' }}</span>
                                            </span>
                                            <span v-if="alert.warehouse_item?.item?.barcode">
                                                <small>Cód. barras</small>
                                                <span>{{ alert.warehouse_item.item.barcode }}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="text-end">{{ formatQuantity(alert.quantity, alert) }}</td>
                                    <td class="text-end">{{ formatQuantity(alert.minimum_stock, alert) }}</td>
                                    <td class="text-center">{{ formatDate(alert.detected_at) }}</td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="4" class="py-4 text-center text-muted">No hay alertas de stock abiertas en este almacén.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <p v-if="error && alerts.data?.length" class="br-inventory-alerts__error" role="alert">{{ error }}</p>
            <nav v-if="alerts.total > 0 && alerts.links" class="br-inventory-alerts__pagination" aria-label="Paginación de alertas de stock">
                <Paginator :links="alerts.links" @clickPage="$emit('page', $event)"/>
            </nav>
        </template>
        <template #footer>
            <a v-if="downloadUrl" :href="downloadUrl" class="br-btn br-btn-action-export br-inventory-alerts__download" aria-label="Descargar todas las alertas de stock en PDF">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                <span>Descargar PDF</span>
            </a>
        </template>
    </FormModal>
</template>

<script>
import FormModal from "@System/Components/Generics/FormModal.vue";
import {quantityWithUnit} from "@System/Helpers/NumberUtils.js";

export default {
    name: "StockAlertsModal",
    components: {FormModal},
    emits: ["page", "retry"],
    props: {
        alerts: {type: Object, required: true},
        warehouse: {type: Object, default: null},
        downloadUrl: {type: String, default: ""},
        loading: {type: Boolean, default: false},
        error: {type: String, default: ""}
    },
    methods: {
        formatQuantity(value, alert) {
            return quantityWithUnit(value, alert.warehouse_item?.item?.measurement_unit);
        },
        formatDate(value) {
            if(!value) return "—";
            return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
        }
    }
};
</script>
