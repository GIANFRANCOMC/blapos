<template>
    <FormModal modal-id="inventoryTransferDetailModal" modal-size="modal-xl" dialog-class="modal-dialog-scrollable"
        modal-class="br-entity-modal--tabbed"
        title="DETALLE DEL TRASLADO" :show-submit="false" :keyboard="!savingStatus" :close-disabled="savingStatus">
        <template #body>
            <div :aria-busy="loading">
                <div v-if="loading" class="br-entity-form-section"><Loader/></div>
                <div v-else-if="error" class="br-entity-form-section text-center">
                    <p class="text-danger" role="alert">{{ error }}</p>
                    <button type="button" class="br-btn br-btn-cancel waves-effect" @click="$emit('retry')">Reintentar</button>
                </div>
                <div v-else-if="transfer" class="br-inventory-transfer__detail">
                    <section class="br-entity-form-section br-inventory-transfer__detail-header" aria-label="Datos del traslado">
                        <div class="br-field-summary">
                            <span class="form-label">Referencia</span>
                            <span class="br-inventory-transfer__reference">{{ transfer.reference }}</span>
                        </div>
                        <div class="br-inventory-transfer__detail-route">
                            <template v-for="([warehouse, label], index) in warehouses" :key="label">
                                <span v-if="index" class="br-inventory-transfer__direction br-inventory-transfer__detail-arrow" aria-hidden="true"><i class="fa-solid fa-right-long"></i></span>
                                <dl class="mb-0"><dt>{{ label }}</dt><dd>
                                    <span class="br-inventory-transfer__detail-warehouse">{{ warehouse?.branch?.name || warehouse?.name || 'Almacén no visible' }}</span>
                                    <span v-if="warehouse?.branch?.name" class="br-inventory__meta">{{ warehouse.name }}</span>
                                </dd></dl>
                            </template>
                        </div>
                        <div class="br-field-summary">
                            <span class="form-label">Estado</span>
                            <span :class="['br-badge', `br-badge--${status.tone}`]">{{ status.label }}</span>
                        </div>
                    </section>
                    <CatalogFormTabs v-model="activeTab" :tabs="detailTabs"
                        :has-errors="tab => tab === 'products' && Boolean(statusError || quantityError)"
                        id-prefix="inventory-transfer-detail" shared-controls-id="inventory-transfer-detail-panel"
                        variant="descriptive"
                        label="Secciones del traslado"/>
                    <div class="br-entity-form-section d-grid gap-3">
                        <div v-if="statusError" role="alert">
                            <p class="text-danger mb-1">{{ statusError }}</p>
                            <button type="button" class="br-link p-0 border-0 bg-transparent waves-effect" :disabled="savingStatus" @click="$emit('retry')">Actualizar detalle</button>
                        </div>
                        <div id="inventory-transfer-detail-panel" role="tabpanel" :aria-labelledby="'inventory-transfer-detail-tab-trigger-' + activeTab">
                            <section v-show="activeTab === 'summary'" aria-label="Resumen del traslado">
                                <div class="d-grid gap-3">
                                    <div class="br-inventory-transfer__detail-overview">
                                        <div>
                                            <h3 class="br-inventory-transfer__detail-heading mb-3">Registro y responsables</h3>
                                            <dl class="br-inventory-transfer__detail-summary mb-0">
                                                <div><dt>Fecha de registro</dt><dd>{{ formatDate(transfer.created_at) }}</dd></div>
                                                <div><dt>Registrado por</dt><dd>{{ transfer.user?.name || 'Usuario no disponible' }}</dd></div>
                                                <div><dt>Enviado por</dt><dd>{{ transfer.sender_name || (transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? 'Sin envío confirmado' : 'Responsable de envío no registrado') }}</dd></div>
                                                <div><dt>Recepciones registradas por</dt><dd>{{ receiverName }}</dd></div>
                                            </dl>
                                        </div>
                                        <dl class="br-inventory-transfer__detail-reason mb-0"><dt>Motivo del traslado</dt><dd>{{ transfer.reason || 'Sin motivo registrado' }}</dd></dl>
                                    </div>
                                    <section v-if="transfer.canceled_at" class="br-inventory-transfer__cancellation">
                                        <h3 class="br-inventory-transfer__detail-heading">Cancelación del traslado</h3>
                                        <p class="br-inventory__meta mb-0">{{ formatDate(transfer.canceled_at) }} · {{ transfer.canceled_by_user?.name || 'Usuario no disponible' }}</p>
                                        <button v-if="transfer.cancellation_reason" type="button" class="br-link p-0 border-0 bg-transparent waves-effect" @click="$emit('view-cancellation-reason')">Ver motivo</button>
                                    </section>
                                </div>
                            </section>
                            <section v-show="activeTab === 'products'" aria-labelledby="transferProductsHeading">
                                <h3 id="transferProductsHeading" class="br-inventory-transfer__detail-heading mb-3">Productos del traslado ({{ transfer.items_count }})</h3>
                                <div v-if="mode" class="row g-3 mb-3">
                                    <div v-if="mode !== 'receipt'" class="col-12 col-md-4">
                                        <label for="transferResolutionKind" class="form-label">Resolver mediante</label>
                                        <select id="transferResolutionKind" v-model="mode" class="form-select" :disabled="savingStatus">
                                            <option value="return">Devolución al origen</option><option value="loss">Pérdida justificada</option>
                                        </select>
                                    </div>
                                    <div :class="mode === 'receipt' ? 'col-12' : 'col-12 col-md-8'">
                                        <label for="transferOperationReason" class="form-label">{{ mode === 'receipt' ? 'Observaciones' : 'Motivo de la diferencia' }}</label>
                                        <span v-if="mode !== 'receipt'" class="text-danger ms-1" aria-hidden="true">*</span>
                                        <InputText id="transferOperationReason" v-model="reason" maxlength="255" :disabled="savingStatus" :aria-required="mode !== 'receipt'"/>
                                    </div>
                                    <p class="br-inventory__meta mb-0">{{ mode === 'receipt' ? 'Confirma únicamente lo que llegó. Puedes recibir todo lo pendiente o indicar una cantidad menor por producto.' : 'Se procesarán solo las cantidades indicadas. La devolución ingresa al origen; la pérdida se registra con su motivo y valor.' }}</p>
                                </div>
                                <div class="br-entity-list">
                                    <div class="table-responsive br-inventory-transfer__table-scroll">
                                        <table class="table br-entity-table br-inventory-transfer__detail-table mb-0">
                                            <thead class="br-table-header-surface"><tr>
                                                <th scope="col" class="text-start">Producto</th>
                                                <th scope="col" class="text-end">{{ transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? 'Por enviar' : 'Enviada' }}</th>
                                                <th scope="col" class="text-end">Recibida</th><th scope="col" class="text-end">Pendiente</th>
                                                <th scope="col" class="text-end">Resuelta</th><th scope="col">Estado</th>
                                                <th v-if="mode" scope="col">{{ mode === 'receipt' ? 'Recibir ahora' : 'Resolver ahora' }}</th>
                                            </tr></thead>
                                            <tbody><tr v-for="line in transfer.items" :key="line.id">
                                                <td><ProductIdentity variant="expandable" :product="line.item"/></td>
                                                <td class="text-end"><QuantityValue :value="transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? line.quantity : line.sent_quantity" :unit="line.item?.measurement_unit"/></td>
                                                <td class="text-end"><QuantityValue :value="line.received_quantity" :unit="line.item?.measurement_unit"/></td>
                                                <td class="text-end"><QuantityValue :value="line.pending_quantity" :unit="line.item?.measurement_unit"/></td>
                                                <td class="text-end"><QuantityValue :value="line.resolved_quantity" :unit="line.item?.measurement_unit"/></td>
                                                <td class="text-center"><span :class="['br-badge', `br-badge--${line.status_tone}`]">{{ line.status_label }}</span></td>
                                                <td v-if="mode">
                                                    <div v-if="line.pending_quantity > 0" class="br-inventory-transfer__receipt-input">
                                                        <label class="d-flex align-items-center gap-1 mb-1">
                                                            <input type="checkbox" class="form-check-input m-0" :disabled="savingStatus" :checked="isFull(line)" @change="fillPending(line, $event.target.checked)">
                                                            <span class="br-inventory__meta">{{ mode === 'receipt' ? 'Recibir todo lo pendiente' : 'Resolver todo lo pendiente' }}</span>
                                                        </label>
                                                        <InputNumber v-model="quantities[line.id]" :hasNegative="false" :disabled="savingStatus" :aria-label="`Cantidad de ${line.item?.name}`">
                                                            <template #inputGroupAppend><span class="input-group-text br-input-unit-suffix">{{ line.item?.measurement_unit?.abbreviation }}</span></template>
                                                        </InputNumber>
                                                    </div>
                                                    <span v-else class="text-muted">—</span>
                                                </td>
                                            </tr></tbody>
                                        </table>
                                    </div>
                                </div>
                                <p v-if="mode && quantityError" class="text-danger mt-2 mb-0" role="alert">{{ quantityError }}</p>
                            </section>
                            <section v-show="activeTab === 'receipts'" aria-labelledby="transferOperationsHeading">
                                <h3 id="transferOperationsHeading" class="br-inventory-transfer__detail-heading mb-3">Recepciones y diferencias registradas</h3>
                                <WithoutData v-if="!transfer.operations?.length" text="Sin recepciones registradas" description="Aquí se mostrarán las cantidades recibidas y las diferencias cuando se registren."/>
                                <details v-for="(operation, index) in transfer.operations" :key="operation.id" :open="index === 0" class="br-inventory-transfer__operation">
                                    <summary><span>{{ operation.label }} #{{ operation.id }}</span><span class="br-inventory__meta">{{ formatDate(operation.occurred_at) }} · {{ operation.user_name }}</span></summary>
                                    <p v-if="operation.reason" class="br-inventory__meta my-1">{{ operation.reason }}</p>
                                    <div class="table-responsive"><table class="table br-entity-table mb-0">
                                        <thead class="br-table-header-surface"><tr><th scope="col">Producto</th><th scope="col" class="text-end">Cantidad</th></tr></thead>
                                        <tbody><tr v-for="line in operation.items" :key="line.id">
                                            <td><ProductIdentity variant="expandable" :product="line.transfer_item?.item"/></td><td class="text-end"><QuantityValue :value="line.quantity" :unit="line.transfer_item?.item?.measurement_unit"/></td>
                                        </tr></tbody>
                                    </table></div>
                                </details>
                            </section>
                            <section v-show="activeTab === 'history'" aria-labelledby="transferRoadmapHeading">
                                <h3 id="transferRoadmapHeading" class="br-inventory-transfer__detail-heading mb-3">Recorrido del traslado</h3>
                                <div class="br-inventory-transfer__history">
                                    <StatusRoadmap :events="transfer.status_history || []" :format-date="formatDate" label="Estados y operaciones del traslado"/>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footerClose><button type="button" class="br-btn br-btn-cancel waves-effect" data-bs-dismiss="modal" :disabled="savingStatus">Cerrar</button></template>
        <template #footer>
            <div v-if="transfer" class="d-flex flex-wrap justify-content-end gap-2">
                <template v-if="mode">
                    <button type="button" class="br-btn br-btn-outline-secondary waves-effect" :disabled="savingStatus" @click="mode = ''">Volver al detalle</button>
                    <button type="button" class="br-btn br-btn-primary waves-effect" :disabled="savingStatus || !canSubmit" @click="submitOperation">{{ savingStatus ? 'Guardando…' : mode === 'receipt' ? 'Confirmar recepción' : 'Confirmar resolución' }}</button>
                </template>
                <template v-else>
                    <button v-if="canCancel" type="button" class="br-btn br-btn-outline-secondary text-danger waves-effect" :disabled="savingStatus" @click="$emit('cancel')">Cancelar traslado</button>
                    <a v-if="printUrl && !savingStatus" :href="printUrl" target="_blank" rel="noopener" class="br-btn br-btn-outline-secondary waves-effect"><i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir</span></a>
                    <button v-if="transfer.can_resolve" type="button" class="br-btn br-btn-outline-secondary waves-effect" :disabled="savingStatus" @click="startMode('return')">Resolver diferencia</button>
                    <button v-if="transfer.can_receive" type="button" class="br-btn br-btn-primary waves-effect" :disabled="savingStatus" @click="startMode('receipt')">Registrar recepción</button>
                    <button v-if="nextStatus" type="button" class="br-btn br-btn-primary waves-effect" :disabled="savingStatus" @click="$emit('advance')">{{ savingStatus ? 'Enviando…' : 'Confirmar envío' }}</button>
                </template>
            </div>
        </template>
    </FormModal>
</template>

<script>
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import StatusRoadmap from "@System/Components/Generics/StatusRoadmap.vue";
import CatalogFormTabs from "@System/Components/Catalogs/CatalogFormTabs.vue";
import {compareNumbers, normalizeNumber, isNumber} from "../../../../Shared/Numbers.js";
import {uuid} from "@System/Helpers/CommonUtils.js";

const DETAIL_TABS = Object.freeze([
    {id: "summary", label: "Resumen", description: "Responsables y motivo", icon: "fa-solid fa-file-lines"},
    {id: "products", label: "Productos", description: "Cantidades y estado", icon: "fa-solid fa-boxes-stacked"},
    {id: "receipts", label: "Recepciones", description: "Ingresos y diferencias", icon: "fa-solid fa-truck-ramp-box"},
    {id: "history", label: "Historial", description: "Fechas y responsables", icon: "fa-solid fa-clock-rotate-left"}
]);

export default {
    components: {FormModal, ProductIdentity, QuantityValue, InputNumber, InputText, Loader, WithoutData, StatusRoadmap, CatalogFormTabs},
    emits: ["retry", "advance", "cancel", "view-cancellation-reason", "operate"],
    props: {
        transfer: {type: Object, default: null}, loading: {type: Boolean, default: false}, error: {type: String, default: ""},
        status: {type: Object, required: true}, nextStatus: {type: String, default: null}, canCancel: {type: Boolean, default: false},
        savingStatus: {type: Boolean, default: false}, statusError: {type: String, default: ""},
        printUrl: {type: String, default: null}, formatDate: {type: Function, required: true}
    },
    data() { return {activeTab: "summary", detailTabs: DETAIL_TABS, mode: "", quantities: {}, reason: "", lastSubmission: null}; },
    watch: {
        transfer() { this.activeTab = "summary"; this.mode = ""; this.quantities = {}; this.reason = ""; this.lastSubmission = null; },
        statusError(value) { if(value && this.mode) this.activeTab = "products"; }
    },
    computed: {
        receiverName() {
            if(this.transfer?.receiver_name) return this.transfer.receiver_name;
            return this.transfer?.status === "canceled" ? "No recibido" : "Sin recepción registrada";
        },
        warehouses() { return [[this.transfer?.source_warehouse, "Almacén de origen"], [this.transfer?.destination_warehouse, "Almacén de destino"]]; },
        quantityError() {
            for(const line of this.transfer?.items || []) {
                const value = this.quantities[line.id];
                if(value === "" || value == null) continue;
                if(!isNumber({value, minValue: 0, maxValue: line.pending_quantity, allowEmpty: true})) return "La cantidad debe estar entre cero y lo pendiente de recibir.";
            }
            return "";
        },
        canSubmit() {
            return ["receipt", "return", "loss"].includes(this.mode) && !this.quantityError && (this.mode === "receipt" || Boolean(this.reason.trim()))
                && (this.transfer?.items || []).some(line => compareNumbers(this.quantities[line.id] || 0, 0) > 0);
        }
    },
    methods: {
        startMode(mode) { this.activeTab = "products"; this.mode = mode; this.quantities = {}; this.reason = ""; this.lastSubmission = null; },
        isFull(line) { return compareNumbers(this.quantities[line.id] || 0, line.pending_quantity) === 0; },
        fillPending(line, checked) { this.quantities[line.id] = checked ? line.pending_quantity : 0; },
        submitOperation() {
            if(!this.canSubmit || this.savingStatus) return;
            const payload = {expected_version: this.transfer.version, reason: this.reason.trim(),
                items: this.transfer.items.filter(line => compareNumbers(this.quantities[line.id] || 0, 0) > 0)
                    .map(line => ({id: line.id, quantity: normalizeNumber(this.quantities[line.id])}))};
            if(this.mode !== "receipt") payload.kind = this.mode;
            const fingerprint = JSON.stringify([this.mode, payload]);
            if(this.lastSubmission?.fingerprint !== fingerprint) this.lastSubmission = {fingerprint, key: uuid()};
            this.$emit("operate", {kind: this.mode, data: {...payload, request_key: this.lastSubmission.key}});
        }
    }
};
</script>
