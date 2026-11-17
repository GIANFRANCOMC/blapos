<template>
    <FormModal modal-id="inventoryTransferDetailModal" modal-size="modal-lg" dialog-class="modal-dialog-scrollable"
        title="DETALLE DEL TRASLADO" :show-submit="false" :keyboard="!savingStatus" :close-disabled="savingStatus">
        <template #body>
            <div :aria-busy="loading">
                <Loader v-if="loading"/>
                <div v-else-if="error" class="text-center py-3">
                    <p class="text-danger" role="alert">{{ error }}</p>
                    <button type="button" class="br-btn br-btn-cancel waves-effect" @click="$emit('retry')">Reintentar</button>
                </div>

                <template v-else-if="transfer">
                    <div class="br-inventory-transfer__detail">
                        <dl class="br-inventory-transfer__detail-summary mb-0">
                            <div>
                                <dt>Referencia</dt>
                                <dd class="br-inventory-transfer__reference">{{ transfer.reference }}</dd>
                            </div>
                            <div><dt>Registrado el</dt><dd>{{ formatDate(transfer.created_at) }}</dd></div>
                            <div><dt>Responsable</dt><dd>{{ transfer.user?.name || 'Proceso del sistema' }}</dd></div>
                            <div>
                                <dt>Estado</dt>
                                <dd><span :class="['br-badge', `br-badge--${status.tone}`]">{{ status.label }}</span></dd>
                            </div>
                        </dl>

                        <div class="br-inventory-transfer__detail-route">
                            <template v-for="([warehouse, label], index) in warehouses" :key="label">
                                <span v-if="index" class="br-inventory-transfer__direction br-inventory-transfer__detail-arrow" aria-hidden="true">
                                    <i class="fa-solid fa-right-long"></i>
                                </span>
                                <dl class="mb-0">
                                    <dt>{{ label }}</dt>
                                    <dd class="mb-0">
                                        <span class="br-inventory-transfer__detail-warehouse">{{ warehouse?.branch?.name || warehouse?.name || 'Almacén no visible' }}</span>
                                        <span v-if="warehouse?.branch?.name" class="br-inventory__meta">{{ warehouse.name }}</span>
                                    </dd>
                                </dl>
                            </template>
                        </div>
                        <dl class="br-inventory-transfer__detail-reason mb-0">
                            <dt>Motivo del traslado</dt>
                            <dd>{{ transfer.reason }}</dd>
                        </dl>

                        <section v-if="transfer.canceled_at" class="br-inventory-transfer__cancellation" aria-labelledby="transferCancellationHeading">
                            <h3 id="transferCancellationHeading" class="br-inventory-transfer__detail-heading">Cancelación del traslado</h3>
                            <p class="br-inventory__meta mb-0">{{ formatDate(transfer.canceled_at) }} · {{ transfer.canceled_by_user?.name || 'Usuario no disponible' }}</p>
                            <button v-if="transfer.cancellation_reason" type="button" class="btn btn-link p-0 mt-1"
                                @click="$emit('view-cancellation-reason')">Ver motivo</button>
                        </section>

                        <p v-if="statusError" class="text-danger mb-0" role="alert">{{ statusError }}</p>
                        <section aria-labelledby="transferProductsHeading">
                            <h3 id="transferProductsHeading" class="br-inventory-transfer__detail-heading">Productos del traslado ({{ transfer.items_count }})</h3>
                            <div class="br-entity-list">
                                <div class="table-responsive br-inventory-transfer__table-scroll">
                                    <table class="table br-entity-table br-inventory-transfer__detail-table mb-0">
                                        <thead class="br-table-header-surface">
                                            <tr><th scope="col" class="text-start">Producto</th><th scope="col" class="text-end">Cantidad</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="line in transfer.items" :key="line.id">
                                                <td><ProductIdentity variant="expandable" :product="line.item"/></td>
                                                <td class="text-end"><QuantityValue :value="line.quantity" :unit="line.item?.measurement_unit"/></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>
                </template>
            </div>
        </template>

        <template #footerClose>
            <button type="button" class="br-btn br-btn-cancel waves-effect" data-bs-dismiss="modal" :disabled="savingStatus">Cerrar</button>
        </template>
        <template #footer>
            <div v-if="transfer" class="d-flex flex-wrap justify-content-end gap-2">
                <button v-if="canCancel" type="button" class="br-btn br-btn-outline-secondary text-danger waves-effect" :disabled="savingStatus" @click="$emit('cancel')">
                    <i class="fa-solid fa-ban" aria-hidden="true"></i>
                    <span>Cancelar traslado</span>
                </button>
                <a v-if="printUrl && !savingStatus" :href="printUrl" target="_blank" rel="noopener" class="br-btn br-btn-outline-secondary waves-effect">
                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                    <span>Imprimir</span>
                </a>
                <button v-if="nextStatus" type="button" class="br-btn br-btn-primary waves-effect" :disabled="savingStatus" @click="$emit('advance')">
                    {{ savingStatus ? 'Actualizando…' : nextStatus === 'sent' ? 'Marcar como enviado' : 'Aceptar traslado' }}
                </button>
            </div>
        </template>
    </FormModal>
</template>

<script>
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import Loader from "@System/Components/Loader.vue";

export default {
    components: {FormModal, ProductIdentity, QuantityValue, Loader},
    emits: ["retry", "advance", "cancel", "view-cancellation-reason"],
    props: {
        transfer: {type: Object, default: null},
        loading: {type: Boolean, default: false},
        error: {type: String, default: ""},
        status: {type: Object, required: true},
        nextStatus: {type: String, default: null},
        canCancel: {type: Boolean, default: false},
        savingStatus: {type: Boolean, default: false},
        statusError: {type: String, default: ""},
        printUrl: {type: String, default: null},
        formatDate: {type: Function, required: true}
    },
    computed: {
        warehouses() {
            return [
                [this.transfer?.source_warehouse, "Almacén de origen"],
                [this.transfer?.destination_warehouse, "Almacén de destino"]
            ];
        }
    }
};
</script>
