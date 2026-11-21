<template>
    <FormModal modal-id="inventoryTransferDetailModal" modal-size="modal-xl" dialog-class="modal-dialog-scrollable"
        modal-class="br-entity-modal--tabbed" body-class="br-inventory-transfer__detail"
        title="DETALLE DEL TRASLADO" :show-submit="false" :keyboard="!savingStatus" :close-disabled="savingStatus">
        <template #body>
            <div v-if="loading" class="br-entity-form-section" aria-busy="true"><Loader/></div>
            <div v-else-if="error" class="br-entity-form-section text-center">
                <p class="text-danger" role="alert">{{ error }}</p>
                <button type="button" class="br-btn br-btn-cancel waves-effect" @click="$emit('retry')">Reintentar</button>
            </div>
            <template v-else-if="transfer">
                <header class="br-entity-form-section br-inventory-transfer__detail-header" aria-label="Datos del traslado">
                    <dl class="br-inventory-transfer__detail-fields br-inventory-transfer__detail-route">
                        <div>
                            <dt>Referencia:</dt>
                            <dd class="br-inventory-transfer__reference">{{ transfer.reference }}</dd>
                        </div>
                        <div v-for="[warehouse, label] in warehouses" :key="label">
                            <dt>{{ label }}:</dt>
                            <dd>
                                <template v-if="warehouse?.branch?.name && warehouse?.name">
                                    {{ warehouse.branch.name }} <span class="text-muted">· {{ warehouse.name }}</span>
                                </template>
                                <template v-else>{{ warehouse?.name || warehouse?.branch?.name || 'Almacén no visible' }}</template>
                            </dd>
                        </div>
                        <div>
                            <dt>Estado:</dt>
                            <dd><span :class="['br-badge', `br-badge--${status.tone}`]">{{ status.label }}</span></dd>
                        </div>
                    </dl>
                    <dl class="br-inventory-transfer__detail-fields br-inventory-transfer__detail-context">
                        <div><dt>Registrado por:</dt><dd>{{ transfer.user?.name || 'Usuario no disponible' }}</dd></div>
                        <div><dt>Enviado por:</dt><dd>{{ senderName }}</dd></div>
                        <div class="br-inventory-transfer__detail-receivers"><dt>Recepciones registradas por:</dt><dd>{{ receiverName }}</dd></div>
                        <div class="br-inventory-transfer__detail-reason">
                            <dt>Motivo del traslado:</dt>
                            <dd>{{ transfer.reason || 'Sin motivo registrado' }}</dd>
                        </div>
                        <div v-if="transfer.canceled_at" class="br-inventory-transfer__detail-cancellation">
                            <dt>Cancelación del traslado:</dt>
                            <dd>
                                {{ formatDate(transfer.canceled_at) }} · {{ transfer.canceled_by_user?.name || 'Usuario no disponible' }}
                                <button v-if="transfer.cancellation_reason" type="button" class="br-link ms-2 p-0 border-0 bg-transparent waves-effect" @click="$emit('view-cancellation-reason')">Ver motivo</button>
                            </dd>
                        </div>
                    </dl>
                </header>
                <AppTabs v-model="activeTab" :tabs="detailTabs"
                    :has-errors="tab => tab === 'products' && Boolean(statusError || quantityError)"
                    id-prefix="inventory-transfer-detail" shared-controls-id="inventory-transfer-detail-panel"
                    variant="descriptive" layout="content"
                    label="Secciones del traslado"/>
                <section id="inventory-transfer-detail-panel" class="br-entity-form-section" role="tabpanel"
                    :class="{'br-inventory-transfer__products-panel': activeTab === 'products'}"
                    :aria-labelledby="'inventory-transfer-detail-tab-trigger-' + activeTab">
                    <div v-if="statusError" class="mb-3" role="alert">
                        <p class="text-danger mb-1">{{ statusError }}</p>
                        <button type="button" class="br-link p-0 border-0 bg-transparent waves-effect" :disabled="savingStatus" @click="$emit('retry')">Actualizar detalle</button>
                    </div>
                    <section v-show="activeTab === 'products'" class="br-inventory-transfer__detail-products" aria-label="Productos del traslado">
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
                        <div class="br-entity-list table-responsive br-inventory-transfer__table-scroll" role="region" aria-label="Registros de productos" tabindex="0">
                            <table class="table br-entity-table br-inventory-transfer__detail-table mb-0" :class="{'br-inventory-transfer__detail-table--entry': mode}">
                                <caption class="visually-hidden">Productos del traslado ({{ transfer.items_count }})</caption>
                                <thead class="br-table-header-surface"><tr>
                                    <th scope="col" class="text-start">Producto</th>
                                    <th scope="col" class="text-end">{{ transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? 'Por enviar' : 'Enviados' }}</th>
                                    <th scope="col" class="text-end">Recibidos</th><th scope="col" class="text-end">Pendientes</th>
                                    <th scope="col" class="text-end">Resueltos</th><th scope="col" class="text-center">Estado</th>
                                    <th v-if="mode" scope="col">{{ mode === 'receipt' ? 'Recibir ahora' : 'Resolver ahora' }}</th>
                                </tr></thead>
                                <tbody><tr v-for="line in transfer.items" :key="line.id">
                                    <td><ProductIdentity variant="expandable" :product="line.item"/></td>
                                    <td class="text-end"><QuantityValue zero-as-dash :value="transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? line.quantity : line.sent_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="text-end"><QuantityValue zero-as-dash :value="line.received_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="text-end"><QuantityValue zero-as-dash :value="line.pending_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="text-end"><QuantityValue zero-as-dash :value="line.resolved_quantity" :unit="line.item?.measurement_unit"/></td>
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
                        <p v-if="mode && quantityError" class="text-danger mt-2 mb-0" role="alert">{{ quantityError }}</p>
                    </section>
                    <section v-show="activeTab === 'receipts'" aria-label="Recepciones y diferencias registradas">
                        <p v-if="!transfer.operations?.length" class="text-muted mb-0">Aún no hay recepciones ni diferencias registradas.</p>
                        <details v-for="(operation, index) in transfer.operations" :key="operation.id" :open="index === 0" class="fg-operation">
                            <summary>
                                <span class="fg-operation__summary">
                                    <strong>{{ operation.label }} #{{ operation.id }}</strong>
                                    <span class="text-muted">{{ formatDate(operation.occurred_at) }} · {{ operation.user_name }}</span>
                                </span>
                                <span class="fg-chevron" aria-hidden="true"></span>
                            </summary>
                            <dl v-if="operation.reason" class="br-inventory-transfer__detail-reason">
                                <dt>{{ operation.kind === 'receipt' ? 'Observaciones' : 'Motivo' }}</dt>
                                <dd>{{ operation.reason }}</dd>
                            </dl>
                            <div class="table-responsive">
                                <table class="table br-entity-table mb-0">
                                    <caption class="visually-hidden">Productos de la operación {{ operation.id }}</caption>
                                    <thead class="br-table-header-surface">
                                        <tr>
                                            <th scope="col" class="text-start">Producto</th>
                                            <th scope="col" class="text-end">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in operation.items" :key="line.id">
                                            <td><ProductIdentity variant="expandable" :product="line.transfer_item?.item"/></td>
                                            <td class="text-end"><QuantityValue :value="line.quantity" :unit="line.transfer_item?.item?.measurement_unit"/></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </section>
                    <section v-show="activeTab === 'history'" aria-label="Recorrido del traslado">
                        <StatusRoadmap :events="transfer.status_history || []" :format-date="formatDate" label="Estados y operaciones del traslado"/>
                    </section>
                </section>
            </template>
        </template>
        <template #footerClose><button type="button" class="br-btn br-btn-cancel waves-effect" data-bs-dismiss="modal" :disabled="savingStatus">Cerrar</button></template>
        <template #footer>
            <div v-if="transfer" class="br-entity-modal__footer-actions">
                <template v-if="mode">
                    <button type="button" class="br-btn br-btn-outline-secondary waves-effect" :disabled="savingStatus" @click="mode = ''">Volver al detalle</button>
                    <button type="button" class="br-btn br-btn-primary waves-effect" :disabled="savingStatus || !canSubmit" @click="submitOperation">{{ savingStatus ? 'Guardando…' : mode === 'receipt' ? 'Confirmar recepción' : 'Confirmar resolución' }}</button>
                </template>
                <template v-else>
                    <button v-if="canCancel" type="button" class="br-btn br-btn-outline-secondary text-danger waves-effect" :disabled="savingStatus" @click="$emit('cancel')">Cancelar traslado</button>
                    <a v-if="pdfUrl && !savingStatus" :href="pdfUrl" class="br-btn br-btn-danger waves-effect"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span>Descargar PDF</span></a>
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
import StatusRoadmap from "@System/Components/Generics/StatusRoadmap.vue";
import AppTabs from "@/Shared/AppTabs.vue";
import {compareNumbers, normalizeNumber, isNumber} from "../../../../Shared/Numbers.js";
import {uuid} from "@System/Helpers/CommonUtils.js";

const DETAIL_TABS = Object.freeze([
    {id: "products", label: "Productos", description: "Cantidades y estado", icon: "fa-solid fa-boxes-stacked"},
    {id: "receipts", label: "Recepciones", description: "Ingresos y diferencias", icon: "fa-solid fa-truck-ramp-box"},
    {id: "history", label: "Historial", description: "Fechas y responsables", icon: "fa-solid fa-clock-rotate-left"}
]);

export default {
    components: {FormModal, ProductIdentity, QuantityValue, InputNumber, InputText, Loader, StatusRoadmap, AppTabs},
    emits: ["retry", "advance", "cancel", "view-cancellation-reason", "operate"],
    props: {
        transfer: {type: Object, default: null}, loading: {type: Boolean, default: false}, error: {type: String, default: ""},
        status: {type: Object, required: true}, nextStatus: {type: String, default: null}, canCancel: {type: Boolean, default: false},
        savingStatus: {type: Boolean, default: false}, statusError: {type: String, default: ""},
        pdfUrl: {type: String, default: null}, formatDate: {type: Function, required: true}
    },
    data() { return {activeTab: "products", detailTabs: DETAIL_TABS, mode: "", quantities: {}, reason: "", lastSubmission: null}; },
    watch: {
        transfer() { this.activeTab = "products"; this.mode = ""; this.quantities = {}; this.reason = ""; this.lastSubmission = null; },
        statusError(value) { if(value && this.mode) this.activeTab = "products"; }
    },
    computed: {
        senderName() {
            if(this.transfer?.sender_name) return this.transfer.sender_name;
            return ["awaiting_dispatch", "canceled"].includes(this.transfer?.status)
                ? "Sin envío confirmado" : "Responsable de envío no registrado";
        },
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
