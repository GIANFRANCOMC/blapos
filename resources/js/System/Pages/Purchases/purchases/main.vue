<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <main class="br-entity br-purchases">
        <section v-if="activeMode === 'new'" class="br-purchases__workspace">
            <div class="row g-4">
                <div class="col-lg-9 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <InputSlot hasDiv title="Tipo de comprobante" :titleClass="[config.forms.classes.title]" isRequired xl="3" lg="6">
                                    <template #input>
                                        <v-select v-model="purchaseForm.documentType" :options="documentTypes" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                    </template>
                                </InputSlot>
                                <InputText v-model="purchaseForm.documentSeries" hasDiv title="Serie" :titleClass="[config.forms.classes.title]" maxlength="20" showCharCounter xl="3" lg="6"/>
                                <InputText v-model="purchaseForm.documentNumber" hasDiv title="Nro. de comprobante" :titleClass="[config.forms.classes.title]" maxlength="50" showCharCounter xl="3" lg="6"/>
                                <InputDate v-model="purchaseForm.issueDate" hasDiv title="Fecha de emisión" :titleClass="[config.forms.classes.title]" isRequired xl="3" lg="6"/>
                                <InputSlot hasDiv title="Sucursal" :titleClass="[config.forms.classes.title]" isRequired xl="4" lg="4">
                                    <template #input>
                                        <v-select v-model="purchaseForm.branch" :options="branches" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                    </template>
                                </InputSlot>
                                <InputSlot hasDiv title="Almacén" :titleClass="[config.forms.classes.title]" isRequired xl="4" lg="4">
                                    <template #input>
                                        <v-select v-model="purchaseForm.warehouse" :options="warehousesForBranch(purchaseForm.branch?.code)" :clearable="false" searchable append-to-body placeholder="Seleccione"/>
                                    </template>
                                </InputSlot>
                                <InputSlot hasDiv title="Proveedor" :titleClass="[config.forms.classes.title]" isRequired xl="8" lg="8">
                                    <template #default>
                                        <AddSupplier triggerLabel="Agregar" @postAction="addSupplierPostAction"/>
                                    </template>
                                    <template #input>
                                        <v-select v-model="purchaseForm.supplier" :options="suppliers" :clearable="false" searchable append-to-body placeholder="Seleccione"/>
                                    </template>
                                </InputSlot>
                                <InputSlot hasDiv title="Recepción" :titleClass="[config.forms.classes.title]" isRequired xl="4" lg="4">
                                    <template #input>
                                        <v-select v-model="purchaseForm.deliveryMode" :options="deliveryModes" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                    </template>
                                </InputSlot>
                                <InputDate v-if="purchaseForm.deliveryMode?.code === 'pending'" v-model="purchaseForm.expectedDate" hasDiv title="Fecha esperada" :titleClass="[config.forms.classes.title]" xl="3" lg="6"/>
                            </div>

                            <div class="row g-3">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="text-center">
                                            <tr>
                                                <th style="width: 8%;">#</th>
                                                <th class="min-w-150px" style="width: 32%;">PRODUCTO</th>
                                                <th class="min-w-150px" style="width: 20%;">CANTIDAD</th>
                                                <th class="min-w-150px" style="width: 20%;">COSTO UNITARIO</th>
                                                <th class="min-w-150px text-end pe-3" style="width: 15%;">TOTAL</th>
                                                <th style="width: 5%;">ACCIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0 bg-white">
                                            <template v-if="purchaseForm.items?.length">
                                                <tr v-for="(detail, index) in purchaseForm.items" :key="detail.key">
                                                    <td class="text-center fw-bold">
                                                        <span>{{ index + 1 }}</span>
                                                    </td>
                                                    <td class="text-start">
                                                        <v-select v-model="detail.product" :options="purchaseProductOptions(index)" :clearable="false" searchable append-to-body placeholder="Seleccione"/>
                                                    </td>
                                                    <td class="text-center">
                                                        <InputNumber v-model="detail.quantity" :hasNegative="false"/>
                                                    </td>
                                                    <td class="text-center">
                                                        <InputNumber v-model="detail.unitCost" :hasNegative="false">
                                                            <template #inputGroupPrepend>
                                                                <span class="input-group-text br-currency-prefix">{{ purchaseForm.currency?.sign || "S/" }}</span>
                                                            </template>
                                                        </InputNumber>
                                                    </td>
                                                    <td class="text-end align-middle pe-3">
                                                        <span class="br-amount-inline">
                                                            <span class="br-amount-inline__sign">{{ purchaseForm.currency?.sign || "" }}</span>
                                                            <span class="br-amount-inline__amount">{{ separatorNumber(lineTotal(detail)) }}</span>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-xs waves-effect" :disabled="purchaseForm.items.length === 1" title="Quitar producto" @click="removePurchaseItem(index)">
                                                            <i class="fa fa-times" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="br-table-footer-stripe">
                                                    <td colspan="4" class="text-end align-middle br-table-footer-stripe__label">
                                                        <span class="text-uppercase">Importe total:</span>
                                                    </td>
                                                    <td class="text-end align-middle pe-3">
                                                        <span class="br-amount-inline br-amount-inline--emphasis">
                                                            <span class="br-amount-inline__sign">{{ purchaseForm.currency?.sign || "" }}</span>
                                                            <span class="br-amount-inline__amount">{{ separatorNumber(purchaseSubtotal) }}</span>
                                                        </span>
                                                    </td>
                                                    <td class="align-middle"></td>
                                                </tr>
                                            </template>
                                            <tr v-else>
                                                <td class="pt-1 pb-0 border-0" colspan="99">
                                                    <div class="br-table-detail-empty">
                                                        <div class="br-table-detail-empty__top">
                                                            <div class="br-table-detail-empty__body">
                                                                <img class="br-table-detail-empty__img" :src="purchaseDetailEmptyImageUrl" alt="" width="100" height="84" loading="lazy" decoding="async"/>
                                                                <p class="br-table-detail-empty__text">
                                                                    <span>No hay productos en el detalle. Agréguelos con la acción </span>
                                                                    <a href="javascript:void(0)" class="br-link br-table-detail-empty__link" @click.prevent="addPurchaseItem">Agregar producto</a>
                                                                    <span>.</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-12">
                    <aside class="br-purchases__settlement">
                        <div class="br-document-settlement br-sale-settlement mb-2 mb-md-3">
                            <div>
                                <div class="br-document-settlement__header">
                                    <label class="form-label">Observaciones</label>
                                    <button
                                        type="button"
                                        class="br-btn br-btn-xs br-btn-action-import br-document-payment-config waves-effect"
                                        @click="openObservationsModal">
                                        <span>Cambiar</span>
                                    </button>
                                </div>
                                <div class="br-document-observation-summary">
                                    <span
                                        v-if="observationHasContent"
                                        class="br-document-observation-summary__value"
                                        :title="observationPreviewTooltip"
                                        v-text="observationDisplayPreview">
                                    </span>
                                    <p v-else class="br-document-settlement__empty mb-0">
                                        Sin observaciones para esta compra.
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Impuestos extras</label>
                                <div class="br-document-settlement__taxes" v-if="optionalPurchaseTaxes.length">
                                    <template v-for="tax in optionalPurchaseTaxes" :key="`optional-purchase-tax-page-${tax.code}`">
                                        <label class="br-entity-switch br-document-settlement__tax-option">
                                            <input v-model="purchaseForm.selectedTaxes" class="form-check-input" type="checkbox" :value="tax.code" @change="syncSelectedTaxQuantity(tax.data)">
                                            <span>
                                                <strong>{{ tax.data.name }}</strong>
                                                <small>{{ taxLabel(tax.data) }}</small>
                                            </span>
                                        </label>
                                        <InputNumber v-if="isFixedTax(tax.data) && (purchaseForm.selectedTaxes || []).includes(tax.code)" v-model="purchaseForm.selectedTaxQuantities[tax.code]" title="" :inputClass="['form-control', 'br-tax-quantity']" :decimals="0" :minValue="taxQuantityMinimum(tax.data)" :maxValue="taxQuantityMaximum(tax.data)" :hasNegative="false" @change="normalizeSelectedTaxQuantity(tax.code)">
                                            <template #inputGroupPrepend>
                                                <span class="input-group-text br-tax-quantity__label">Veces</span>
                                            </template>
                                        </InputNumber>
                                    </template>
                                </div>
                                <p v-else class="br-document-settlement__empty mb-0">Sin impuestos extras para esta compra.</p>
                            </div>
                            <div>
                                <label class="form-label">Métodos de pago</label>
                                <div class="br-document-payments">
                                    <div v-for="(payment, index) in purchaseForm.payments" :key="payment.key" class="br-document-payment-row">
                                        <v-select
                                            v-model="payment.method"
                                            :options="purchasePaymentMethods"
                                            :clearable="false"
                                            :searchable="true"
                                            append-to-body
                                            @update:modelValue="syncPurchasePaymentVariant(payment)">
                                            <template #selected-option="{ label, data }">
                                                <span class="br-payment-select-option">
                                                    <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                                    <span>{{ label }}</span>
                                                </span>
                                            </template>
                                            <template #option="{ label, data }">
                                                <span class="br-payment-select-option">
                                                    <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                                    <span>{{ label }}</span>
                                                </span>
                                            </template>
                                        </v-select>
                                        <v-select
                                            v-if="purchasePaymentVariantOptions(payment).length"
                                            v-model="payment.variant"
                                            :options="purchasePaymentVariantOptions(payment)"
                                            :clearable="false"
                                            :searchable="true"
                                            append-to-body
                                            placeholder="Variante">
                                            <template #selected-option="{ label, data }">
                                                <span class="br-payment-select-option">
                                                    <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                                    <span>{{ label }}</span>
                                                </span>
                                            </template>
                                            <template #option="{ label, data }">
                                                <span class="br-payment-select-option">
                                                    <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                                    <span>{{ label }}</span>
                                                </span>
                                            </template>
                                        </v-select>
                                        <InputNumber v-model="payment.amount" title="" :titleClass="[]" :inputClass="['form-control', 'br-document-payment-amount']" :minValue="0" :placeholder="separatorNumber(purchaseTotal)">
                                            <template #inputGroupPrepend>
                                                <span class="input-group-text br-currency-prefix">{{ purchaseForm.currency?.sign }}</span>
                                            </template>
                                        </InputNumber>
                                        <input v-if="paymentRequiresReference(payment)" v-model.trim="payment.reference" type="text" class="form-control" maxlength="100" placeholder="Referencia">
                                        <button type="button" class="br-icon-action" :disabled="purchaseForm.payments.length <= 1" title="Quitar método" @click="removePurchasePayment(index)">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="br-document-payment-add" @click="addPurchasePayment">
                                        <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                        <span>Agregar método</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="br-document-summary-card mb-2 mb-md-3">
                            <h3 class="br-document-summary-card__title">Resumen</h3>
                            <div class="br-document-settlement__summary">
                                <span>Subtotal</span>
                                <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchaseSubtotal) }}</strong>
                                <template v-if="purchaseTaxBreakdown.length">
                                    <template v-for="tax in purchaseTaxBreakdown" :key="`purchase-summary-tax-page-${tax.id}`">
                                        <span>{{ tax.name }}</span>
                                        <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(tax.amount) }}</strong>
                                    </template>
                                </template>
                                <span>Total</span>
                                <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchaseTotal) }}</strong>
                                <span>Pagado</span>
                                <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchasePaidTotal) }}</strong>
                                <span>Diferencia</span>
                                <strong :class="Number(purchasePaymentDifference) === 0 ? 'text-success' : 'text-danger'">
                                    {{ purchaseForm.currency?.sign }} {{ separatorNumber(purchasePaymentDifference) }}
                                </strong>
                            </div>
                        </div>
                        <div class="br-sale-sidebar-actions">
                            <div class="br-sale-sidebar-actions__pair">
                                <button type="button" class="br-btn br-btn-sm br-btn-primary waves-effect" @click="addPurchaseItem">
                                    <span>Agregar producto</span>
                                </button>
                                <button type="button" class="br-btn br-btn-sm br-btn-secondary waves-effect" @click="preparePurchase">
                                    <span>Limpiar</span>
                                </button>
                            </div>
                            <div class="br-sale-sidebar-actions__pair">
                                <button type="button" class="br-btn br-btn-action-create br-sale-sidebar-actions__cta" :disabled="saving" @click="savePurchase">
                                    <span v-text="saving ? 'Registrando' : 'Registrar compra'"></span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <div id="purchaseObservationsModal" class="modal fade br-modal-standard" data-bs-backdrop="static" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header br-modal-header">
                        <h5 class="modal-title text-uppercase fw-bold">Cambiar observación</h5>
                        <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                            <i class="fa fa-times icon-close-modal" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <InputTextArea
                            v-model="observationDraft"
                            hasDiv
                            title="Observaciones"
                            :titleClass="[config.forms.classes.title]"
                            rows="5"
                            maxlength="1000"
                            showCharCounter
                            placeholder="Escribe aquí la observación de la compra..."/>
                    </div>
                    <div class="modal-footer br-entity-modal__footer">
                        <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="br-btn br-btn-primary" @click="saveObservationsModal">Cambiar observación</button>
                    </div>
                </div>
            </div>
        </div>

        <section v-if="['list', 'receipts'].includes(activeMode)" class="br-filter-bar">
            <div class="row align-items-end g-2">
                <InputText
                    v-model="filters.word"
                    hasDiv
                    title="Búsqueda"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Proveedor, documento o producto"
                    xl="5"
                    lg="5"
                    @enterKeyPressed="listPurchases({})"/>
                <div class="form-group col-xl-3 col-lg-3 col-md-12">
                    <label class="form-label">{{ activeMode === "receipts" ? "Estado del ingreso" : "Estado" }}</label>
                    <v-select
                        v-model="filters.status"
                        :options="statuses"
                        :clearable="false"
                        :searchable="false"/>
                </div>
                <div class="form-group col-xl-4 col-lg-4 col-md-12">
                    <div class="br-filter-bar__actions">
                        <button type="button" class="br-btn br-btn-sm br-btn-search" @click="listPurchases({})">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <span>Buscar</span>
                        </button>
                        <button
                            v-if="activeMode === 'list'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create"
                            @click="changeMode('new')">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Nuevo</span>
                        </button>
                        <button
                            v-if="activeMode === 'list'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-export br-btn-action-export--desktop-icon"
                            title="Descargar compras"
                            data-bs-toggle="tooltip"
                            @click="exportPurchases">
                            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                            <span class="br-btn-action-export__label">Descargar</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="['list', 'receipts'].includes(activeMode)" class="br-entity-list">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Documento</th>
                            <th>Proveedor</th>
                            <th>Almacén</th>
                            <th class="text-end">Total</th>
                            <th>Recepción</th>
                            <th>Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="py-4"><Loader/></td>
                        </tr>
                        <template v-else-if="records.total > 0">
                            <tr v-for="purchase in records.data" :key="purchase.id">
                                <td>
                                    <strong>{{ purchase.formatted_document_type }}</strong>
                                    <span class="br-purchases__meta">
                                        {{ purchaseDocumentReference(purchase) }}
                                        ? {{ formatDate(purchase.issue_date) }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ purchase.supplier?.name }}</strong>
                                    <span class="br-purchases__meta">{{ purchase.supplier?.document_number }}</span>
                                </td>
                                <td>{{ purchase.warehouse?.name }}</td>
                                <td class="text-end">
                                    <strong class="br-amount-inline__amount">{{ purchase.currency?.sign }} {{ separatorNumber(purchase.total) }}</strong>
                                </td>
                                <td>
                                    <span class="br-purchases__progress-label">{{ purchase.receipt_progress }}%</span>
                                    <div class="br-purchases__progress">
                                        <span :style="{width: `${purchase.receipt_progress}%`}"></span>
                                    </div>
                                </td>
                                <td>
                                    <span :class="['br-purchase-status', `is-${purchase.status}`]">
                                        {{ purchase.formatted_status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="br-purchases__actions">
                                        <button
                                            v-if="['confirmed', 'partial'].includes(purchase.status)"
                                            type="button"
                                            class="br-icon-action br-icon-action-edit"
                                            title="Registrar recepción"
                                            data-bs-toggle="modal"
                                            data-bs-target="#purchaseReceiptModal"
                                            @click="prepareReceipt(purchase.id)">
                                            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                        </button>
                                        <button
                                            v-if="purchase.status === 'confirmed'"
                                            type="button"
                                            class="br-icon-action br-purchases__cancel"
                                            title="Anular compra"
                                            @click="cancelPurchase(purchase)">
                                            <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="7"><WithoutData type="image"/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="['list', 'receipts'].includes(activeMode) && records.links" class="d-flex justify-content-center mt-3">
            <Paginator :links="records.links" @clickPage="listPurchases"/>
        </div>
    </main>

    <div id="purchaseReceiptModal" class="modal fade br-entity-modal br-modal-standard" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Compras</p>
                        <h2 class="modal-title br-entity-modal__title">Recibir mercadería</h2>
                        <p class="br-purchases__modal-help">Solo las cantidades recibidas ingresarán al inventario.</p>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body br-modal-standard__body">
                    <div v-if="loadingReceipt" class="py-4"><Loader/></div>
                    <template v-else>
                        <div class="br-purchases__receipt-summary">
                            <strong>{{ receiptForm.purchase?.supplier?.name }}</strong>
                            <span>{{ receiptForm.purchase?.warehouse?.name }}</span>
                        </div>
                        <div class="br-purchases__receipt-items">
                            <div v-for="detail in receiptForm.items" :key="detail.purchaseItemId" class="br-purchases__receipt-row">
                                <div>
                                    <strong>{{ detail.name }}</strong>
                                    <span>Pendiente: {{ separatorNumber(detail.remaining) }}</span>
                                </div>
                                <InputNumber v-model="detail.quantity" title="Cantidad recibida" :titleClass="[config.forms.classes.title]" :hasNegative="false"/>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-5">
                                <label class="form-label">Fecha y hora de recepción</label>
                                <input v-model="receiptForm.receivedAt" type="datetime-local" class="form-control">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Observación</label>
                                <textarea v-model.trim="receiptForm.observation" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button ref="closeReceiptModal" type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-primary" :disabled="savingReceipt || loadingReceipt" @click="saveReceipt">Confirmar recepción</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

export default {
    async mounted() {
        this.activeMode = this.initialModeFromPath();
        Utils.navbarItem("menu-parent-purchases", {addClass: "open"});
        Utils.navbarItem({
            new: "menu-purchases-new",
            receipts: "menu-purchases-receipts",
            list: "menu-purchases-list"
        }[this.activeMode], {});
        Alerts.swals({type: "initParams"});
        await this.initParams();
        Alerts.swals({show: false});
        await this.listPurchases({});
        if(this.activeMode === "new") {
            this.preparePurchase();
        }
    },
    data() {
        return {
            activeMode: "list",
            loading: false,
            saving: false,
            loadingReceipt: false,
            savingReceipt: false,
            exporting: false,
            records: {total: 0, data: []},
            filters: {
                word: "",
                status: {code: "", label: "Todos los estados"}
            },
            options: {
                branches: [],
                suppliers: [],
                warehouses: [],
                currencies: [],
                products: [],
                taxes: [],
                paymentMethods: [],
                purchaseDocumentTypes: [],
                purchaseDeliveryModes: []
            },
            deliveryModes: [
                {
                    code: "immediate",
                    label: "Recepción inmediata"
                },
                {
                    code: "pending",
                    label: "Recepción pendiente"
                }
            ],
            purchaseForm: {},
            observationDraft: "",
            observationPreviewExpanded: false,
            receiptForm: {purchase: null, items: [], receivedAt: "", observation: ""},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "purchases"}),
                    page: {title: "Compras", active: true, menu: {id: "menu-purchases-list"}}
                }
            }
        };
    },
    methods: {
        initialModeFromPath() {
            const segment = window.location.pathname.split("?")[0].split("/").filter(Boolean).pop();

            if(["new", "create"].includes(segment)) return "new";
            if(segment === "pending-receipts") return "receipts";

            return "list";
        },
        changeMode(mode) {
            this.activeMode = mode;
            this.updateUrlMode(mode);
            if(mode === "new") this.preparePurchase();
        },
        taxLabel(tax = {}) {

            const name = tax?.name || "IGV";
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const sign = operationType === "subtraction" ? "-" : "+";

            if(calculationType === "fixed") {

                return `${name} ${sign} ${this.separatorNumber(rate)}`;

            }

            return `${name} ${sign}${this.separatorNumber(rate)}%`;

        },
        calculateTaxAmount(tax = {}, baseAmount = 0) {

            const base = Number(baseAmount || 0);
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const quantity = this.isFixedTax(tax) ? this.selectedTaxQuantity(tax.id) : 1;
            const amount = calculationType === "fixed"
                ? rate * quantity
                : base * (rate / 100);

            return Number(Utils.fixedNumber(operationType === "subtraction" ? amount * -1 : amount));

        },
        taxIsRequired(tax = {}) {

            return [true, 1, "1", "true"].includes(tax?.is_required);

        },
        isFixedTax(tax = {}) {

            return (tax?.calculation_type || "percentage") === "fixed";

        },
        selectedTaxQuantity(taxId) {

            return Math.max(1, parseInt(Number(this.purchaseForm.selectedTaxQuantities?.[taxId] || 1), 10));

        },
        normalizeSelectedTaxQuantity(taxId) {

            this.purchaseForm.selectedTaxQuantities[taxId] = Math.max(1, parseInt(Number(this.purchaseForm.selectedTaxQuantities[taxId] || 1), 10));

        },
        syncSelectedTaxQuantity(tax = {}) {

            if(!this.isFixedTax(tax)) return;

            this.purchaseForm.selectedTaxQuantities[tax.id] = (this.purchaseForm.selectedTaxes || []).includes(tax.id) ? 1 : 0;

        },
        async initParams() {
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });
            const config = result.data?.config || {};
            this.options.branches = config.branches?.records || [];
            this.options.suppliers = config.suppliers?.records || [];
            this.options.warehouses = config.warehouses?.records || [];
            this.options.currencies = config.currencies?.records || [];
            this.options.products = config.products?.records || [];
            this.options.taxes = config.taxes || {records: []};
            this.options.paymentMethods = config.paymentMethods || {records: []};
            this.options.purchaseDocumentTypes = config.purchaseDocumentTypes?.records || [];
            this.options.purchaseDeliveryModes = config.purchaseDeliveryModes?.records || [];
            if(this.options.purchaseDeliveryModes.length) {
                this.deliveryModes = this.options.purchaseDeliveryModes;
            }
        },
        async listPurchases({url = null} = {}) {
            this.loading = true;
            const result = await Requests.get({
                route: url || this.config.entity.routes.list,
                data: {
                    word: this.filters.word,
                    status: this.activeMode === "receipts"
                        ? (this.filters.status?.code || "pending_receipt")
                        : (this.filters.status?.code || "")
                }
            });
            this.records = result.data || {total: 0, data: []};
            this.loading = false;
        },
        newPurchaseItem() {
            return {key: `${Date.now()}-${Math.random()}`, product: null, quantity: "", unitCost: ""};
        },
        newPurchasePayment({amount = ""} = {}) {

            const payment = {
                key: `${Date.now()}-${Math.random()}`,
                method: this.purchasePaymentMethods.find(method => method.data?.is_default) || this.purchasePaymentMethods[0] || null,
                variant: null,
                amount,
                reference: "",
                note: ""
            };

            this.syncPurchasePaymentVariant(payment);

            return payment;

        },
        paymentAssetUrl(record) {

            const path = record?.image_path || record?.data?.image_path;

            if(!path) return "";

            if(/^https?:\/\//i.test(path) || path.startsWith("/")) return path;

            return `/${path}`;

        },
        purchasePaymentVariantOptions(payment) {

            const method = payment?.method?.data || {};
            const variants = method.supports_variants ? (method.variants || []) : [];

            return variants
                .filter(variant => variant.status !== "inactive")
                .map(variant => ({
                    code: variant.id,
                    label: variant.name,
                    data: variant
                }));

        },
        syncPurchasePaymentVariant(payment) {

            if(!payment) return;

            const options = this.purchasePaymentVariantOptions(payment);

            if(options.length === 0) {
                payment.variant = null;
                return;
            }

            const current = options.find(option => Number(option.code) === Number(payment.variant?.code));
            payment.variant = current || options.find(option => option.data?.is_default) || options[0];

        },
        paymentRequiresReference(payment) {

            return Boolean(payment?.variant?.data?.requires_reference ?? payment?.method?.data?.requires_reference);

        },
        addPurchasePayment() {
            const pending = this.purchasePaymentDifference > 0 ? this.purchasePaymentDifference : "";
            this.purchaseForm.payments.push(this.newPurchasePayment({amount: pending}));
        },
        removePurchasePayment(index) {
            if(this.purchaseForm.payments.length <= 1) return;
            this.purchaseForm.payments.splice(index, 1);
        },
        openObservationsModal() {
            this.observationDraft = this.purchaseForm.observation == null ? "" : String(this.purchaseForm.observation);
            Alerts.modals({type: "show", id: "purchaseObservationsModal"});
        },
        toggleObservationPreviewExpand() {
            this.observationPreviewExpanded = !this.observationPreviewExpanded;
        },
        saveObservationsModal() {
            this.purchaseForm.observation = this.observationDraft == null ? "" : String(this.observationDraft);
            Alerts.modals({type: "hide", id: "purchaseObservationsModal"});
        },
        preparePurchase() {
            const defaultBranch = this.branches[0] || null;
            const defaultWarehouse = this.warehousesForBranch(defaultBranch?.code)[0] || this.warehouses[0] || null;

            this.purchaseForm = {
                supplier: this.suppliers[0] || null,
                branch: defaultBranch,
                warehouse: defaultWarehouse,
                currency: this.currencies[0] || null,
                documentType: this.documentTypes[0],
                documentSeries: "",
                documentNumber: "",
                issueDate: new Date().toISOString().slice(0, 10),
                expectedDate: "",
                deliveryMode: this.deliveryModes[0],
                selectedTaxes: [],
                selectedTaxQuantities: {},
                payments: [this.newPurchasePayment({amount: 0})],
                observation: "",
                items: [this.newPurchaseItem()]
            };
        },
        addPurchaseItem() {
            if(this.purchaseForm.items.length < 100) this.purchaseForm.items.push(this.newPurchaseItem());
        },
        removePurchaseItem(index) {
            if(this.purchaseForm.items.length > 1) this.purchaseForm.items.splice(index, 1);
        },
        purchaseProductOptions(index) {
            const selected = this.purchaseForm.items
                .filter((_, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        warehousesForBranch(branchId) {
            if(!branchId) return this.warehouses;

            return this.warehouses.filter(warehouse => Number(warehouse.branchId) === Number(branchId));
        },
        lineTotal(detail) {
            return Number(detail.quantity || 0) * Number(detail.unitCost || 0);
        },
        taxQuantityMinimum(tax = {}) {
            return Math.max(1, Number(tax?.min_apply_quantity ?? 1));
        },
        taxQuantityMaximum(tax = {}) {
            return tax?.max_apply_quantity == null
                ? undefined
                : Math.max(this.taxQuantityMinimum(tax), Number(tax.max_apply_quantity));
        },
        addSupplierPostAction({response}) {
            if(!Requests.valid({result: response})) return;

            const supplier = response?.data?.data;
            if(!supplier?.id) return;

            const exists = this.options.suppliers.some(record => Number(record.id) === Number(supplier.id));
            if(!exists) {
                this.options.suppliers.push(supplier);
                this.options.suppliers.sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
            }

            this.purchaseForm.supplier = this.suppliers.find(record => Number(record.code) === Number(supplier.id)) || this.purchaseForm.supplier;
        },
        async savePurchase() {
            this.saving = true;
            Alerts.swals({type: "loading", message: "Registrando compra"});
            const result = await Requests.post({
                route: this.config.entity.routes.store,
                data: {
                    supplier_id: this.purchaseForm.supplier?.code,
                    warehouse_id: this.purchaseForm.warehouse?.code,
                    currency_id: this.purchaseForm.currency?.code,
                    document_type: this.purchaseForm.documentType?.code,
                    document_series: this.purchaseForm.documentSeries || null,
                    document_number: this.purchaseForm.documentNumber,
                    issue_date: this.purchaseForm.issueDate,
                    expected_date: this.purchaseForm.expectedDate || null,
                    delivery_mode: this.purchaseForm.deliveryMode?.code || "immediate",
                    tax: this.purchaseTaxTotal,
                    taxes: this.purchaseTaxBreakdown.map(tax => ({
                        tax_id: tax.id,
                        quantity: tax.quantity,
                        amount: tax.amount,
                        base_amount: this.purchaseSubtotal,
                        is_required: tax.isRequired
                    })),
                    payments: this.purchasePaymentPayload,
                    observation: this.purchaseForm.observation,
                    items: this.purchaseForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: item.quantity,
                        unit_cost: item.unitCost
                    }))
                }
            });
            this.saving = false;
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                this.activeMode = "list";
                this.updateUrlMode("list");
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", messages: this.responseErrors(result)});
        },
        async prepareReceipt(id) {
            this.loadingReceipt = true;
            const result = await Requests.get({route: `${this.config.entity.routes.consult}/${id}`});
            this.loadingReceipt = false;

            if(!result.bool) {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: result.data?.msg || "No se pudo consultar la compra."
                });
                return;
            }

            const purchase = result.data;
            this.receiptForm = {
                purchase,
                receivedAt: new Date().toISOString().slice(0, 16),
                observation: "",
                items: (purchase.items || [])
                    .filter(item => Number(item.remaining_quantity) > 0)
                    .map(item => ({
                        purchaseItemId: item.id,
                        name: item.name,
                        remaining: Number(item.remaining_quantity),
                        quantity: Number(item.remaining_quantity)
                    }))
            };
        },
        async saveReceipt() {
            this.savingReceipt = true;
            Alerts.swals({type: "loading", message: "Registrando recepción"});
            const result = await Requests.post({
                route: `${this.config.entity.routes.consult}/${this.receiptForm.purchase.id}/receive`,
                data: {
                    received_at: this.receiptForm.receivedAt,
                    observation: this.receiptForm.observation,
                    items: this.receiptForm.items
                        .filter(item => Number(item.quantity) > 0)
                        .map(item => ({
                            purchase_item_id: item.purchaseItemId,
                            quantity: item.quantity
                        }))
                }
            });
            this.savingReceipt = false;
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                this.$refs.closeReceiptModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", messages: this.responseErrors(result)});
        },
        async cancelPurchase(purchase) {
            const accepted = await Alerts.generateAlert({
                type: "question",
                msgContent: `?Deseas anular ${purchase.formatted_document_type.toLowerCase()}?`
            });
            if(!accepted?.isConfirmed) return;
            Alerts.swals({type: "loading", message: "Anulando compra"});
            const result = await Requests.patch({
                route: this.config.entity.routes.consult,
                id: `${purchase.id}/cancel`,
                data: {}
            });
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", msgContent: result.data?.msg});
        },
        async exportPurchases() {
            if(this.exporting) return;
            this.exporting = true;
            Alerts.swals({type: "loading", message: "Preparando reporte"});
            const result = await Requests.download({
                route: this.config.entity.routes.export,
                data: {word: this.filters.word, status: this.filters.status?.code || ""},
                fileName: "compras.xlsx"
            });
            Alerts.swals({show: false});
            this.exporting = false;

            if(!result.bool) {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: result.data?.msg || "No se pudo descargar el reporte."
                });
            }
        },
        responseErrors(result) {
            const errors = Object.values(result?.errors || result?.data?.errors || {}).flat();
            return errors.length ? errors : [result?.data?.msg || "No se pudo completar la operación."];
        },
        formatDate(value) {
            if(!value) return "";
            return new Intl.DateTimeFormat("es-PE").format(new Date(`${value}T00:00:00`));
        },
        purchaseDocumentReference(purchase) {
            const number = purchase?.document_number || "";
            const series = purchase?.document_series || "";

            if(series && number) return `${series}-${number}`;
            if(number) return number;

            return `Registro #${purchase?.id}`;
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        },
        updateUrlMode(mode) {
            const path = mode === "new" ? "/purchases/create" : "/purchases";
            window.history.replaceState({}, "", path);
        }
    },
    computed: {
        breadcrumbTitles() {
            return [
                {title: "Compras"},
                {
                    title: {
                        new: "Nueva compra",
                        receipts: "Ingresos pendientes",
                        list: "Compras registradas"
                    }[this.activeMode],
                    active: true
                }
            ];
        },
        statuses() {
            if(this.activeMode === "receipts") {
                return [
                    {code: "", label: "Todos los ingresos pendientes"},
                    {code: "confirmed", label: "Pendiente de recepción"},
                    {code: "partial", label: "Recepción parcial"}
                ];
            }

            return [
                {code: "", label: "Todos los estados"},
                {code: "confirmed", label: "Pendiente de recepción"},
                {code: "partial", label: "Recepción parcial"},
                {code: "received", label: "Recibida"},
                {code: "canceled", label: "Anulada"}
            ];
        },
        documentTypes() {
            return this.options.purchaseDocumentTypes.length ? this.options.purchaseDocumentTypes : [
                {code: "order", label: "BOLETA"},
                {code: "invoice", label: "FACTURA"}
            ];
        },
        deliveryModeDescription() {
            return this.purchaseForm.deliveryMode?.code === "immediate"
                ? "La mercadería ingresa al inventario al registrar la compra."
                : "La compra queda pendiente hasta registrar la recepción.";
        },
        branches() {
            return this.options.branches.map(record => ({
                code: record.id,
                label: record.name,
                data: record
            }));
        },
        suppliers() {
            return this.options.suppliers.map(record => ({code: record.id, label: record.name}));
        },
        warehouses() {
            return this.options.warehouses.map(record => ({
                code: record.id,
                branchId: record.branch?.id || record.branch_id || null,
                label: record.name,
                data: record
            }));
        },
        currencies() {
            return this.options.currencies.map(record => ({
                code: record.id,
                sign: record.sign,
                label: `${record.code} - ${record.sign}`
            }));
        },
        products() {
            return this.options.products.map(record => ({
                code: record.id,
                label: [record.name, record.internal_code, record.barcode].filter(Boolean).join(" ? "),
                data: record
            }));
        },
        purchaseDetailEmptyImageUrl() {
            return "/System/assets/img/utils/without_data/empty_sale_detail.svg";
        },
        observationFullText() {
            const raw = this.purchaseForm.observation;

            if(raw == null) return "";

            return String(raw).trim();
        },
        observationHasContent() {
            return this.observationFullText.length > 0;
        },
        observationPreviewCharLimit() {
            return 400;
        },
        observationIsTruncatable() {
            return this.observationFullText.length > this.observationPreviewCharLimit;
        },
        observationDisplayPreview() {
            const full = this.observationFullText;

            if(!full) return "";
            if(this.observationPreviewExpanded || !this.observationIsTruncatable) return full;

            return `${full.slice(0, this.observationPreviewCharLimit)}...`;
        },
        observationPreviewTooltip() {
            if(!this.observationHasContent || this.observationPreviewExpanded || !this.observationIsTruncatable) return "";

            return this.observationFullText;
        },
        observationsFieldAriaLabel() {
            return `Observaciones. ${this.observationHasContent ? "Cambiar observación" : "Agregar observación"}`;
        },
        purchaseSubtotal() {
            return (this.purchaseForm.items || []).reduce((total, detail) => total + this.lineTotal(detail), 0);
        },
        purchaseTaxTotal() {
            return (this.purchaseTaxBreakdown || []).reduce((total, tax) => {
                return total + Number(tax.amount || 0);
            }, 0);
        },
        purchaseTaxBreakdown() {
            const subtotal = Number(this.purchaseSubtotal || 0);

            return (this.appliedPurchaseTaxes || []).map(tax => {
                const data = tax.data || {};

                return {
                    id: data.id || tax.code,
                    name: data.name || "IGV",
                    isRequired: this.taxIsRequired(data),
                    quantity: this.isFixedTax(data) ? this.selectedTaxQuantity(data.id || tax.code) : 1,
                    amount: this.calculateTaxAmount(data, subtotal)
                };
            });
        },
        purchaseTotal() {
            return this.purchaseSubtotal + this.purchaseTaxTotal;
        },
        purchaseTaxes() {
            return (this.options.taxes?.records || []).map(tax => ({
                code: tax.id,
                label: this.taxLabel(tax),
                data: tax
            }));
        },
        requiredPurchaseTaxes() {
            return this.purchaseTaxes.filter(tax => this.taxIsRequired(tax.data));
        },
        optionalPurchaseTaxes() {
            return this.purchaseTaxes.filter(tax => !this.taxIsRequired(tax.data));
        },
        appliedPurchaseTaxes() {
            const selected = this.purchaseForm.selectedTaxes || [];

            return this.purchaseTaxes.filter(tax => this.taxIsRequired(tax.data) || selected.includes(tax.code));
        },
        purchasePaymentMethods() {
            return (this.options.paymentMethods?.records || []).map(method => ({
                code: method.id,
                label: method.name,
                data: method
            }));
        },
        purchasePaymentPayload() {
            const selected = this.purchaseForm.payments || [];

            if(selected.length === 0) return [];

            return selected
                .filter(payment => payment.method?.code)
                .map(payment => ({
                    payment_method_id: payment.method.code,
                    payment_method_variant_id: payment.variant?.code || null,
                    name: payment.variant?.label || payment.method?.label || null,
                    amount: Number(payment.amount || 0),
                    reference: payment.reference || null,
                    note: payment.note || null
                }));
        },
        purchasePaidTotal() {
            return (this.purchaseForm.payments || []).reduce((total, payment) => {
                return total + Number(payment.amount || 0);
            }, 0);
        },
        purchasePaymentDifference() {
            return Number(Utils.fixedNumber(this.purchaseTotal - this.purchasePaidTotal));
        }
    },
    watch: {
        "purchaseForm.branch": function(branch) {
            const warehouses = this.warehousesForBranch(branch?.code);
            const currentWarehouseIsValid = warehouses.some(warehouse => Number(warehouse.code) === Number(this.purchaseForm.warehouse?.code));

            if(!currentWarehouseIsValid) {
                this.purchaseForm.warehouse = warehouses[0] || null;
            }
        },
        "purchaseForm.observation": function() {
            this.observationPreviewExpanded = false;
        },
        purchaseTotal(value) {
            const payments = this.purchaseForm.payments || [];

            if(payments.length === 1) {
                payments[0].amount = Number(value || 0);
            }
        }
    }
};
</script>
