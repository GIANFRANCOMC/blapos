<template>
    <FormModal modal-id="inventoryTransferDetailModal" modal-size="modal-xl" dialog-class="modal-dialog-scrollable"
        modal-class="fg-inventory-dialog"
        title="Detalle del traslado" :show-submit="false" :keyboard="!savingStatus" :close-disabled="savingStatus">
        <template #body>
            <FgListLoading v-if="loading" label="Cargando el traslado…"/>
            <FgEmptyState v-else-if="error" title="No pudimos cargar el traslado" :description="error">
                <FgButton tone="secondary" @click="$emit('retry')">Volver a intentar</FgButton>
            </FgEmptyState>
            <div v-else-if="transfer" class="fg-stack">
                <header class="fg-stack" aria-label="Datos del traslado">
                    <dl class="fg-transfer-detail__fields">
                        <div>
                            <dt>Referencia:</dt>
                            <dd class="fg-inventory-reference">{{ transfer.reference }}</dd>
                        </div>
                        <div v-for="[warehouse, label] in warehouses" :key="label">
                            <dt>{{ label }}:</dt>
                            <dd>
                                <template v-if="warehouse?.branch?.name && warehouse?.name">
                                    {{ warehouse.branch.name }} <span class="fg-help">· {{ warehouse.name }}</span>
                                </template>
                                <template v-else>{{ warehouse?.name || warehouse?.branch?.name || 'Almacén no visible' }}</template>
                            </dd>
                        </div>
                        <div>
                            <dt>Estado:</dt>
                            <dd><span :class="['fg-badge', `fg-badge--${status.tone}`]">{{ status.label }}</span></dd>
                        </div>
                    </dl>
                    <dl class="fg-transfer-detail__fields">
                        <div><dt>Registrado por:</dt><dd>{{ transfer.user?.name || 'Usuario no disponible' }}</dd></div>
                        <div><dt>Enviado por:</dt><dd>{{ senderName }}</dd></div>
                        <div><dt>Recepciones registradas por:</dt><dd>{{ receiverName }}</dd></div>
                        <div class="fg-transfer-detail__reason">
                            <dt>Motivo del traslado:</dt>
                            <dd>{{ transfer.reason || 'Sin motivo registrado' }}</dd>
                        </div>
                        <div v-if="transfer.canceled_at">
                            <dt>Cancelación del traslado:</dt>
                            <dd>
                                {{ formatDate(transfer.canceled_at) }} · {{ transfer.canceled_by_user?.name || 'Usuario no disponible' }}
                                <button v-if="transfer.cancellation_reason" type="button" class="fg-link ms-2" @click="$emit('view-cancellation-reason')">Ver motivo</button>
                            </dd>
                        </div>
                    </dl>
                </header>
                <div class="fg-editor fg-transfer-detail__editor">
                <AppTabs v-model="activeTab" :tabs="detailTabs"
                    :has-errors="tab => tab === 'products' && Boolean(statusError || quantityError)"
                    id-prefix="inventory-transfer-detail" shared-controls-id="inventory-transfer-detail-panel"
                    variant="descriptive" layout="content"
                    label="Secciones del traslado"/>
                <section id="inventory-transfer-detail-panel" class="fg-editor__panel" role="tabpanel"
                    :aria-labelledby="'inventory-transfer-detail-tab-trigger-' + activeTab">
                    <div v-if="statusError" class="mb-3" role="alert">
                        <p class="fg-error mb-1">{{ statusError }}</p>
                        <button type="button" class="fg-link" :disabled="savingStatus" @click="$emit('retry')">Actualizar detalle</button>
                    </div>
                    <section v-show="activeTab === 'products'" class="fg-stack" aria-label="Productos del traslado">
                        <div v-if="mode" class="fg-stack">
                            <div :class="mode === 'receipt' ? 'fg-stack' : 'fg-grid'">
                                <FgField v-if="mode !== 'receipt'" label="Resolver mediante" input-id="transferResolutionKind">
                                    <FgSelect v-model="mode" :options="resolutionOptions" :reduce="option => option.code"
                                        :disabled="savingStatus" :clearable="false" :searchable="false"/>
                                </FgField>
                                <InputText id="transferOperationReason" v-model="reason" :title="mode === 'receipt' ? 'Observaciones' : 'Motivo de la diferencia'"
                                    :is-required="mode !== 'receipt'" maxlength="255" :disabled="savingStatus"/>
                            </div>
                            <p class="fg-help">{{ mode === 'receipt' ? 'Confirma únicamente lo que llegó. Puedes recibir todo lo pendiente o indicar una cantidad menor por producto.' : 'Se procesarán solo las cantidades indicadas. La devolución ingresa al origen; la pérdida se registra con su motivo y valor.' }}</p>
                        </div>
                        <div class="fg-table-scroll fg-table-scroll--records" role="region" aria-label="Registros de productos" tabindex="0">
                            <table class="fg-table fg-transfer-detail__items" :class="{'fg-transfer-detail__items--entry': mode}">
                                <caption class="visually-hidden">Productos del traslado ({{ transfer.items_count }})</caption>
                                <thead><tr>
                                    <th scope="col" class="text-start">Producto</th>
                                    <th scope="col" class="fg-align-end">{{ transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? 'Por enviar' : 'Enviados' }}</th>
                                    <th scope="col" class="fg-align-end">Recibidos</th><th scope="col" class="fg-align-end">Pendientes</th>
                                    <th scope="col" class="fg-align-end">Resueltos</th><th scope="col" class="fg-align-center">Estado</th>
                                    <th v-if="mode" scope="col">{{ mode === 'receipt' ? 'Recibir ahora' : 'Resolver ahora' }}</th>
                                </tr></thead>
                                <tbody><tr v-for="line in transfer.items" :key="line.id">
                                    <td><ProductIdentity variant="expandable" :product="line.item"/></td>
                                    <td class="fg-align-end"><QuantityValue zero-as-dash :value="transfer.status === 'awaiting_dispatch' || transfer.status === 'canceled' ? line.quantity : line.sent_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="fg-align-end"><QuantityValue zero-as-dash :value="line.received_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="fg-align-end"><QuantityValue zero-as-dash :value="line.pending_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="fg-align-end"><QuantityValue zero-as-dash :value="line.resolved_quantity" :unit="line.item?.measurement_unit"/></td>
                                    <td class="fg-align-center"><span :class="['fg-badge', `fg-badge--${line.status_tone}`]">{{ line.status_label }}</span></td>
                                    <td v-if="mode">
                                        <div v-if="line.pending_quantity > 0" class="fg-field">
                                            <label class="fg-check">
                                                <input type="checkbox"  :disabled="savingStatus" :checked="isFull(line)" @change="fillPending(line, $event.target.checked)">
                                                <span class="fg-help">{{ mode === 'receipt' ? 'Recibir todo lo pendiente' : 'Resolver todo lo pendiente' }}</span>
                                            </label>
                                            <InputNumber v-model="quantities[line.id]" :hasNegative="false" :disabled="savingStatus" :aria-label="`Cantidad de ${line.item?.name}`">
                                                <template #inputGroupAppend><span class="fg-field__prefix fg-quantity-unit">{{ line.item?.measurement_unit?.abbreviation }}</span></template>
                                            </InputNumber>
                                        </div>
                                        <span v-else class="fg-help">—</span>
                                    </td>
                                </tr></tbody>
                            </table>
                        </div>
                        <p v-if="mode && quantityError" class="fg-error mt-2 mb-0" role="alert">{{ quantityError }}</p>
                    </section>
                    <section v-show="activeTab === 'receipts'" aria-label="Recepciones y diferencias registradas">
                        <p v-if="!transfer.operations?.length" class="fg-help mb-0">Aún no hay recepciones ni diferencias registradas.</p>
                        <details v-for="(operation, index) in transfer.operations" :key="operation.id" :open="index === 0" class="fg-operation">
                            <summary>
                                <span class="fg-operation__summary">
                                    <strong>{{ operation.label }} #{{ operation.id }}</strong>
                                    <span class="fg-help">{{ formatDate(operation.occurred_at) }} · {{ operation.user_name }}</span>
                                </span>
                                <span class="fg-chevron" aria-hidden="true"></span>
                            </summary>
                            <dl v-if="operation.reason" class="fg-transfer-detail__reason">
                                <dt>{{ operation.kind === 'receipt' ? 'Observaciones' : 'Motivo' }}</dt>
                                <dd>{{ operation.reason }}</dd>
                            </dl>
                            <div class="fg-table-scroll">
                                <table class="fg-table">
                                    <caption class="visually-hidden">Productos de la operación {{ operation.id }}</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-start">Producto</th>
                                            <th scope="col" class="fg-align-end">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in operation.items" :key="line.id">
                                            <td><ProductIdentity variant="expandable" :product="line.transfer_item?.item"/></td>
                                            <td class="fg-align-end"><QuantityValue :value="line.quantity" :unit="line.transfer_item?.item?.measurement_unit"/></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </section>
                    <section v-show="activeTab === 'history'" aria-label="Recorrido del traslado">
                        <ResponsiveTable v-if="transfer.status_history?.length">
                            <StatusRoadmap :events="transfer.status_history" :format-date="formatDate" label="Estados y operaciones del traslado"/>
                        </ResponsiveTable>
                        <p v-else class="fg-help">No hay cambios de estado registrados.</p>
                    </section>
                </section>
                </div>
            </div>
        </template>
        <template #footer>
            <div v-if="transfer" class="fg-inventory-dialog-actions">
                <template v-if="mode">
                    <button type="button" class="fg-button fg-button--secondary" :disabled="savingStatus" @click="mode = ''">Volver al detalle</button>
                    <button type="button" class="fg-button" :disabled="savingStatus || !canSubmit" @click="submitOperation">{{ savingStatus ? 'Guardando…' : mode === 'receipt' ? 'Confirmar recepción' : 'Confirmar resolución' }}</button>
                </template>
                <template v-else>
                    <button v-if="canCancel" type="button" class="fg-button fg-button--secondary fg-icon-danger" :disabled="savingStatus" @click="$emit('cancel')">Cancelar traslado</button>
                    <a v-if="pdfUrl && !savingStatus" :href="pdfUrl" class="fg-button fg-button--danger"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span>Descargar PDF</span></a>
                    <button v-if="transfer.can_resolve" type="button" class="fg-button fg-button--secondary" :disabled="savingStatus" @click="startMode('return')">Resolver diferencia</button>
                    <button v-if="transfer.can_receive" type="button" class="fg-button" :disabled="savingStatus" @click="startMode('receipt')">Registrar recepción</button>
                    <button v-if="nextStatus" type="button" class="fg-button" :disabled="savingStatus" @click="$emit('advance')">{{ savingStatus ? 'Enviando…' : 'Confirmar envío' }}</button>
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
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
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
    components: {FormModal, ProductIdentity, QuantityValue, InputNumber, InputText, FgListLoading, FgEmptyState, FgButton, FgField, FgSelect, ResponsiveTable, StatusRoadmap, AppTabs},
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
        resolutionOptions: () => [{code: "return", label: "Devolución al origen"}, {code: "loss", label: "Pérdida justificada"}],
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
