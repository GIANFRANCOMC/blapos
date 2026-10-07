<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <template v-if="isListPage">
        <section class="br-filter-bar br-sales-list__filters">
            <div class="row align-items-end g-2">
                <InputText
                    v-model="filters.word"
                    hasDiv
                    title="Búsqueda"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Buscar por cotización o cliente"
                    xl="5"
                    lg="6"
                    @enterKeyPressed="listQuotations({})"/>

                <InputSlot
                    hasDiv
                    title="Estado"
                    :titleClass="[config.forms.classes.title]"
                    xl="3"
                    lg="6">
                    <template #input>
                        <v-select
                            v-model="filters.status"
                            :options="statusOptions"
                            :class="config.forms.classes.select2"
                            :clearable="true"
                            :searchable="false"
                            placeholder="Todos"/>
                    </template>
                </InputSlot>

                <InputSlot
                    hasDiv
                    :isInputGroup="false"
                    :divInputClass="['br-filter-bar__actions']"
                    xl="4"
                    lg="12">
                    <template #input>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-search"
                            :disabled="loading.list"
                            @click="listQuotations({})">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <span>Buscar</span>
                        </button>
                        <a :href="createUrl" class="br-btn br-btn-sm br-btn-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Nueva cotización</span>
                        </a>
                    </template>
                </InputSlot>
            </div>
        </section>

        <section class="table-responsive br-entity-table-wrap">
            <table class="table br-entity-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 18%;">Cotización</th>
                        <th style="width: 26%;">Cliente</th>
                        <th style="width: 16%;">Sucursal</th>
                        <th style="width: 14%;">Vigencia</th>
                        <th class="text-end" style="width: 12%;">Total</th>
                        <th style="width: 8%;">Estado</th>
                        <th class="text-center" style="width: 6%;">
                            <span class="visually-hidden">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading.list">
                        <td colspan="7" class="text-center py-4">
                            <Loader/>
                        </td>
                    </tr>
                    <template v-else-if="records.total > 0">
                        <tr v-for="record in records.data" :key="record.id">
                            <td>
                                <strong class="br-entity-primary" v-text="record.reference"></strong>
                                <span class="br-entity-table__meta" v-text="legibleFormatDate(record.issue_date)"></span>
                            </td>
                            <td>
                                <strong class="br-entity-primary" v-text="record.holder?.name || 'Cliente no identificado'"></strong>
                                <span class="br-entity-table__meta" v-text="record.holder?.document_number || 'Sin documento'"></span>
                            </td>
                            <td>
                                <span v-text="record.branch?.name || 'Sin sucursal'"></span>
                            </td>
                            <td>
                                <span v-text="record.valid_until ? legibleFormatDate(record.valid_until) : 'Sin vencimiento'"></span>
                            </td>
                            <td class="text-end">
                                <span class="br-amount-inline">
                                    <span class="br-amount-inline__sign" v-text="record.currency?.sign || 'S/'"></span>
                                    <span class="br-amount-inline__amount" v-text="separatorNumber(record.total)"></span>
                                </span>
                            </td>
                            <td>
                                <span
                                    class="br-status-label"
                                    :class="statusClass(record.status)"
                                    v-text="statusLabel(record.status)">
                                </span>
                            </td>
                            <td>
                                <div class="br-table-actions">
                                    <a
                                        :href="saleUrl"
                                        class="br-icon-action br-icon-action-edit"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Usar en venta"
                                        aria-label="Usar cotización en venta"
                                        @click="persistQuotationForSale(record)">
                                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                                    </a>
                                    <button
                                        v-if="canCancel(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-danger"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Anular cotización"
                                        aria-label="Anular cotización"
                                        @click="cancelQuotation(record)">
                                        <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="7" class="text-center py-4">
                            <WithoutData type="image"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <div v-if="records?.links?.length" class="d-flex justify-content-center">
            <Paginator :links="records.links" @clickPage="listQuotations"/>
        </div>
    </template>

    <template v-else>
        <div class="row g-4">
            <div class="col-lg-9 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-2 mb-2">
                            <InputSlot
                                hasDiv
                                title="Sucursal"
                                :titleClass="[config.forms.classes.title]"
                                xl="4"
                                lg="4">
                                <template #input>
                                    <v-select
                                        v-model="form.branch"
                                        :options="branchOptions"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="branchOptions.length > 6"
                                        placeholder="Seleccione"/>
                                </template>
                            </InputSlot>

                            <InputDate
                                v-model="form.issue_date"
                                hasDiv
                                title="Fecha de emisión"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                xl="4"
                                lg="4"/>

                            <InputDate
                                v-model="form.valid_until"
                                hasDiv
                                title="Válida hasta"
                                :titleClass="[config.forms.classes.title]"
                                xl="4"
                                lg="4"/>

                            <InputSlot
                                hasDiv
                                title="Cliente"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                xl="12"
                                lg="12">
                                <template #default>
                                    <AddCustomer :options="customerQuickCreateOptions" @postAction="addCustomerPostAction"/>
                                </template>
                                <template #input>
                                    <v-select
                                        v-model="form.holder"
                                        :options="holderOptions"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="true"
                                        :filterable="false"
                                        :loading="commercialSelection.customers.loading"
                                        @open="openCommercialOptions('customers')"
                                        @search="searchCommercialOptions('customers', $event)"
                                        placeholder="Seleccione cliente">
                                        <template #list-footer>
                                            <li v-if="commercialSelection.customers.hasMore" class="px-2 py-1">
                                                <button type="button" class="btn btn-link btn-sm w-100 text-start" @mousedown.prevent @click.stop="loadMoreCommercialOptions('customers')">Cargar más clientes</button>
                                            </li>
                                        </template>
                                        <template #no-options>Sin clientes para esta búsqueda.</template>
                                    </v-select>
                                </template>
                            </InputSlot>

                            <InputTextArea
                                v-model="form.observation"
                                hasDiv
                                title="Observaciones"
                                :titleClass="[config.forms.classes.title]"
                                placeholder="Condiciones comerciales, alcance o notas para el cliente"
                                xl="12"
                                lg="12"/>
                        </div>

                        <section class="br-quotation-builder mt-2">
                            <div class="br-quotation-builder__head">
                                <strong>Detalle</strong>
                                <button type="button" class="br-btn br-btn-sm br-btn-primary" @click="addDetail">
                                    <span>Agregar ítem</span>
                                </button>
                            </div>

                            <div class="table-responsive br-entity-table-wrap">
                                <table class="table br-entity-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ítem</th>
                                            <th class="text-end" style="width: 14%;">Cantidad</th>
                                            <th class="text-end" style="width: 18%;">Precio</th>
                                            <th class="text-end" style="width: 16%;">Total</th>
                                            <th class="text-center" style="width: 7%;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in form.details" :key="detail.uid">
                                            <td>
                                                <v-select
                                                    v-model="detail.item"
                                                    :options="itemOptions"
                                                    :class="config.forms.classes.select2"
                                                    :clearable="false"
                                                    :searchable="true"
                                                    :filterable="false"
                                                    :loading="commercialSelection.items.loading"
                                                    @open="openCommercialOptions('items')"
                                                    @search="searchCommercialOptions('items', $event)"
                                                    append-to-body
                                                    placeholder="Seleccione ítem"
                                                    @option:selected="syncDetailItem(detail)">
                                                    <template #list-footer>
                                                        <li v-if="commercialSelection.items.hasMore" class="px-2 py-1">
                                                            <button type="button" class="btn btn-link btn-sm w-100 text-start" @mousedown.prevent @click.stop="loadMoreCommercialOptions('items')">Cargar más ítems</button>
                                                        </li>
                                                    </template>
                                                    <template #no-options>Sin ítems para esta búsqueda.</template>
                                                </v-select>
                                            </td>
                                            <td>
                                                <InputNumber
                                                    v-model="detail.quantity"
                                                    :title="''"
                                                    :titleClass="[]"
                                                    :minValue="0"
                                                    @change="syncDetailTotal(detail)"/>
                                            </td>
                                            <td>
                                                <InputNumber
                                                    v-model="detail.price"
                                                    :title="''"
                                                    :titleClass="[]"
                                                    :minValue="0"
                                                    @change="syncDetailTotal(detail)">
                                                    <template #inputGroupPrepend>
                                                        <span class="input-group-text br-currency-prefix" v-text="currencySign"></span>
                                                    </template>
                                                </InputNumber>
                                            </td>
                                            <td class="text-end">
                                                <strong v-text="`${currencySign} ${separatorNumber(lineTotal(detail))}`"></strong>
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="br-icon-action br-icon-action-danger"
                                                    title="Quitar"
                                                    aria-label="Quitar ítem"
                                                    @click="removeDetail(index)">
                                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.details.length === 0">
                                            <td colspan="5" class="text-center py-4">
                                                <WithoutData type="image"/>
                                                <p class="br-document-settlement__empty mt-2 mb-0">
                                                    Aún no hay ítems en la cotización. Usa Agregar ítem para empezar.
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-12">
                <div class="br-document-settlement br-sale-settlement bg-white mb-2 mb-md-3">
                    <article class="br-document-settlement__section">
                        <header class="br-document-settlement__section-head">
                            <label class="form-label colon-at-end">Impuestos extras</label>
                        </header>
                        <div v-if="optionalTaxes.length" class="br-document-settlement__taxes">
                            <label
                                v-for="tax in optionalTaxes"
                                :key="tax.id"
                                class="br-entity-switch br-document-settlement__tax-option">
                                <input
                                    v-model="form.selected_taxes"
                                    class="form-check-input"
                                    type="checkbox"
                                    :value="tax.id"
                                    @change="syncSelectedTaxQuantity(tax)"/>
                                <span>
                                    <strong v-text="tax.name"></strong>
                                    <small v-text="taxLabel(tax)"></small>
                                </span>
                                <InputNumber
                                    v-if="isFixedTax(tax) && form.selected_taxes.includes(tax.id)"
                                    v-model="form.selected_tax_quantities[tax.id]"
                                    :title="''"
                                    :titleClass="[]"
                                    :decimals="0"
                                    :minValue="taxQuantityMinimum(tax)"
                                    :maxValue="taxQuantityMaximum(tax)"
                                    @change="normalizeSelectedTaxQuantity(tax.id)">
                                    <template #inputGroupPrepend>
                                        <span class="input-group-text br-tax-quantity__label">Veces</span>
                                    </template>
                                </InputNumber>
                            </label>
                        </div>
                        <p v-else class="br-document-settlement__empty mb-0">Sin impuestos extras configurados.</p>
                    </article>
                </div>

                <div class="br-document-summary-card mb-2 mb-md-3">
                    <h3 class="br-document-summary-card__title">Resumen</h3>
                    <div class="br-document-settlement__summary">
                        <span class="br-document-settlement__summary-label br-document-settlement__summary-label--primary">Subtotal</span>
                        <strong class="br-document-settlement__summary-value br-document-settlement__summary-value--primary" v-text="`${currencySign} ${separatorNumber(subtotal)}`"></strong>
                        <span class="br-document-settlement__summary-label br-document-settlement__summary-label--primary">IGV</span>
                        <strong class="br-document-settlement__summary-value br-document-settlement__summary-value--primary" v-text="`${currencySign} ${separatorNumber(taxTotal)}`"></strong>
                        <span class="br-document-settlement__summary-label br-document-settlement__summary-label--total">Total</span>
                        <strong class="br-document-settlement__summary-value br-document-settlement__summary-value--total" v-text="`${currencySign} ${separatorNumber(total)}`"></strong>
                    </div>
                </div>

                <div class="br-sale-sidebar-actions br-sale-sidebar-actions--inline">
                    <button type="button" class="br-btn br-btn-sm br-btn-primary waves-effect" @click="addDetail">
                        <span>Agregar ítem</span>
                    </button>
                    <button
                        type="button"
                        class="br-btn br-btn-success br-sale-sidebar-actions__cta waves-effect"
                        :disabled="!canSubmit || loading.store"
                        @click="storeQuotation">
                        <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
                        <span v-text="loading.store ? 'Guardando cotización' : 'Guardar cotización'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</template>

<script>
import { generalConfig } from "../../../Helpers/Constants.js";
import * as Alerts from "../../../Helpers/Alerts.js";
import * as Requests from "../../../Helpers/Requests.js";
import * as Utils from "../../../Helpers/Utils.js";
import commercialSelection from "../commercialSelection.js";

const MODULE = {
    entity: "quotations",
    page: "Cotizaciones"
};

export default {
    mixins: [commercialSelection],
    name: "SalesQuotationsMain",
    data() {
        return {
            config: {
                ...Requests.config({entity: MODULE.entity}),
                forms: generalConfig.forms,
                messages: generalConfig.messages
            },
            options: {},
            commercialSelectionEntity: "quotations",
            loading: {
                init: false,
                list: false,
                store: false
            },
            filters: {
                word: "",
                status: null
            },
            records: {
                data: [],
                links: [],
                total: 0
            },
            pageMode: window.__BR_QUOTATIONS_PAGE__ || "list",
            form: this.defaultForm()
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Cotizaciones"}, this.isCreatePage ? "Nuevo" : "Listado"];
        },
        isListPage() {
            return this.pageMode === "list";
        },
        isCreatePage() {
            return this.pageMode === "create";
        },
        createUrl() {
            return `${window.location.origin}/quotations/create`;
        },
        listUrl() {
            return `${window.location.origin}/quotations`;
        },
        statusOptions() {
            return [
                {code: "draft", label: "Borrador"},
                {code: "sent", label: "Enviada"},
                {code: "accepted", label: "Aceptada"},
                {code: "converted", label: "Convertida"},
                {code: "canceled", label: "Anulada"},
                {code: "expired", label: "Vencida"}
            ];
        },
        branchOptions() {
            return (this.options?.branches?.records || []).map(record => ({
                code: record.id,
                label: record.name,
                data: record
            }));
        },
        holderOptions() {
            return (this.options?.customers?.records || []).map(record => ({
                code: record.id,
                label: `${record.document_number || "S/D"} - ${record.name}`,
                data: record
            }));
        },
        currencyOptions() {
            return (this.options?.currencies?.records || []).map(record => ({
                code: record.id,
                label: record.name || record.code,
                data: record
            }));
        },
        itemOptions() {
            return (this.options?.items?.records || []).map(record => ({
                code: record.id,
                label: [record.name, record.internal_code, record.barcode].filter(Boolean).join(" - "),
                data: record
            }));
        },
        currencySign() {
            return this.form.currency?.data?.sign || "S/";
        },
        saleTaxes() {
            return this.options?.taxes?.records || [];
        },
        requiredTaxes() {
            return this.saleTaxes.filter(tax => Boolean(tax.is_required));
        },
        optionalTaxes() {
            return this.saleTaxes.filter(tax => !Boolean(tax.is_required));
        },
        selectedOptionalTaxes() {
            return this.optionalTaxes.filter(tax => this.form.selected_taxes.includes(tax.id));
        },
        taxBreakdown() {
            const taxes = [...this.requiredTaxes, ...this.selectedOptionalTaxes];

            return taxes.map(tax => {
                const amount = this.calculateTaxAmount(tax);
                const impactAmount = tax.calculation_type === "percentage"
                    ? this.calculateTaxImpact(tax)
                    : amount;
                const impact = tax.operation_type === "subtraction" ? -impactAmount : impactAmount;

                return {
                    ...tax,
                    amount: this.fixedNumber(amount),
                    impact: this.fixedNumber(impact),
                    quantity: this.selectedTaxQuantity(tax)
                };
            });
        },
        subtotal() {
            const grossSubtotal = this.form.details.reduce((sum, detail) => sum + this.lineTotal(detail), 0);
            const includedTaxTotal = this.taxBreakdown.reduce((sum, tax) => sum + (tax.amount - tax.impact), 0);

            return this.fixedNumber(grossSubtotal - includedTaxTotal);
        },
        taxTotal() {
            return this.fixedNumber(this.taxBreakdown.reduce((sum, tax) => sum + tax.amount, 0));
        },
        total() {
            const grossSubtotal = this.form.details.reduce((sum, detail) => sum + this.lineTotal(detail), 0);
            const taxImpact = this.taxBreakdown.reduce((sum, tax) => sum + tax.impact, 0);

            return this.fixedNumber(grossSubtotal + taxImpact);
        },
        canSubmit() {
            return Boolean(this.form.holder && this.form.currency && this.validDetails.length && this.total > 0);
        },
        validDetails() {
            return this.form.details.filter(detail => detail.item && Number(detail.quantity || 0) > 0);
        },
        saleUrl() {
            return `${window.location.origin}/sales/create`;
        },
        customerQuickCreateOptions() {
            const {records, ...customerOptions} = this.options.customers ?? {};

            return customerOptions;
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-sales", {addClass: "open"});
        Utils.navbarItem(this.isCreatePage ? "menu-quotations-create" : "menu-quotations-list", {});
        this.initParams();
    },
    methods: {
        defaultForm() {
            return {
                branch: null,
                holder: null,
                currency: null,
                issue_date: this.currentDate(),
                valid_until: "",
                observation: "",
                selected_taxes: [],
                selected_tax_quantities: {},
                details: []
            };
        },
        currentDate() {
            return new Date().toISOString().slice(0, 10);
        },
        async initParams() {
            this.loading.init = true;
            Alerts.swals({type: "loading", message: "Preparando cotizaciones"});

            const response = await Requests.get({
                route: this.config.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(Requests.valid({result: response})) {
                this.options = response.data?.config || {};
                this.form.branch = this.branchOptions[0] || null;
                this.form.currency = this.currencyOptions[0] || null;

                if(this.isListPage) {
                    await this.listQuotations({});
                }else if(this.form.details.length === 0) {
                    this.addDetail();
                }
            }

            Alerts.swals({show: false});
            this.loading.init = false;
        },
        async listQuotations({url = null} = {}) {
            this.loading.list = true;

            const response = await Requests.get({
                route: url || this.config.routes.list,
                data: {
                    word: this.filters.word,
                    status: this.filters.status?.code
                },
                showAlert: true
            });

            if(response.bool) {
                this.records = response.data;
            }

            this.loading.list = false;
        },
        addDetail() {
            this.form.details.push({
                uid: this.uuid(),
                item: null,
                quantity: 1,
                price: 0,
                igv_exempt: false,
                price_includes_tax: true,
                name: "",
                observation: ""
            });
        },
        removeDetail(index) {
            this.form.details.splice(index, 1);
        },
        syncDetailItem(detail) {
            const item = detail.item?.data || {};

            detail.name = item.name || "";
            detail.quantity = detail.quantity || 1;
            detail.price = Number(item.price || 0);
            detail.currency = item.currency || this.form.currency?.data || null;
            detail.igv_exempt = Boolean(item.igv_exempt ?? false);
            detail.price_includes_tax = detail.igv_exempt ? false : Boolean(item.price_includes_tax ?? true);
        },
        syncDetailTotal(detail) {
            detail.quantity = this.fixedNumber(detail.quantity || 0);
            detail.price = this.fixedNumber(detail.price || 0);
        },
        lineTotal(detail) {
            return this.fixedNumber(Number(detail.quantity || 0) * Number(detail.price || 0));
        },
        isIgvTax(tax = {}) {
            const code = String(tax?.code || "").toUpperCase();
            const name = String(tax?.name || "").toUpperCase();

            return code.includes("IGV") || name === "IGV";
        },
        detailIsIgvExempt(detail = {}) {
            const value = detail?.item?.data?.igv_exempt ?? detail?.igv_exempt ?? false;

            return [true, 1, "1", "true"].includes(value);
        },
        detailIncludesTax(detail = {}) {
            if(this.detailIsIgvExempt(detail)) return false;

            const value = detail?.item?.data?.price_includes_tax ?? detail?.price_includes_tax ?? true;

            return [true, 1, "1", "true"].includes(value);
        },
        calculateTaxAmount(tax) {
            if(tax.calculation_type === "fixed") {
                return this.fixedNumber(Number(tax.rate || 0) * this.selectedTaxQuantity(tax));
            }

            const taxableBase = this.form.details.reduce((sum, detail) => {
                const total = this.lineTotal(detail);
                const rate = Number(tax.rate || 0) / 100;
                if(this.isIgvTax(tax) && this.detailIsIgvExempt(detail)) return sum;

                return sum + (this.detailIncludesTax(detail) ? (total - (total / (1 + rate))) : (total * rate));
            }, 0);

            return this.fixedNumber(taxableBase);
        },
        calculateTaxImpact(tax) {
            if(tax.calculation_type !== "percentage") {
                return this.calculateTaxAmount(tax);
            }

            const rate = Number(tax.rate || 0) / 100;

            return this.fixedNumber(this.form.details.reduce((sum, detail) => {
                if(this.isIgvTax(tax) && this.detailIsIgvExempt(detail)) return sum;
                if(this.detailIncludesTax(detail)) return sum;

                return sum + (this.lineTotal(detail) * rate);
            }, 0));
        },
        selectedTaxQuantity(tax) {
            if(tax.calculation_type !== "fixed") return 1;

            return this.clampTaxQuantity(tax.id, this.form.selected_tax_quantities[tax.id]);
        },
        syncSelectedTaxQuantity(tax) {
            if(tax.calculation_type !== "fixed") return;

            this.form.selected_tax_quantities[tax.id] = this.form.selected_taxes.includes(tax.id)
                ? this.taxQuantityMinimum(tax)
                : 0;
        },
        normalizeSelectedTaxQuantity(taxId) {
            this.form.selected_tax_quantities[taxId] = this.clampTaxQuantity(taxId, this.form.selected_tax_quantities[taxId]);
        },
        clampTaxQuantity(taxId, value) {
            const tax = this.saleTaxes.find(record => Number(record.id) === Number(taxId)) || {};
            const min = this.taxQuantityMinimum(tax);
            const max = this.taxQuantityMaximum(tax);
            const quantity = parseInt(value || min, 10);

            return Math.min(Math.max(Number.isFinite(quantity) ? quantity : min, min), max);
        },
        taxQuantityMinimum(tax) {
            return Math.max(1, Number(tax?.min_quantity || 1));
        },
        taxQuantityMaximum(tax) {
            return Math.max(this.taxQuantityMinimum(tax), Number(tax?.max_quantity || 999));
        },
        isFixedTax(tax) {
            return tax?.calculation_type === "fixed";
        },
        taxLabel(tax) {
            const sign = tax.operation_type === "subtraction" ? "-" : "+";

            return tax.calculation_type === "fixed"
                ? `${sign}${this.currencySign} ${this.separatorNumber(tax.rate)} por vez`
                : `${sign}${this.separatorNumber(tax.rate)}%`;
        },
        async storeQuotation() {
            if(!this.canSubmit) {
                Alerts.toastrs({type: "warning", subtitle: "Agrega cliente y al menos un ítem con importe."});
                return;
            }

            this.loading.store = true;
            Alerts.swals({type: "loading", message: "Guardando cotización"});

            const response = await Requests.post({
                route: this.config.routes.store,
                data: this.payload()
            });

            Alerts.swals({show: false});
            this.loading.store = false;

            if(Requests.valid({result: response})) {
                Alerts.toastrs({type: "success", subtitle: response.data?.msg || "Cotización registrada correctamente."});
                window.location.href = this.listUrl;
            }else {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: response.data?.msg || "No fue posible registrar la cotización."
                });
            }
        },
        payload() {
            return {
                branch_id: this.form.branch?.code || null,
                holder_id: this.form.holder?.code,
                currency_id: this.form.currency?.code,
                issue_date: this.form.issue_date,
                valid_until: this.form.valid_until || null,
                observation: this.form.observation || null,
                details: this.validDetails.map(detail => ({
                    item_id: detail.item.code,
                    currency_id: detail.item?.data?.currency_id || this.form.currency?.code,
                    name: detail.name || detail.item.label,
                    quantity: detail.quantity,
                    price: detail.price,
                    igv_exempt: this.detailIsIgvExempt(detail),
                    price_includes_tax: this.detailIsIgvExempt(detail) ? false : this.detailIncludesTax(detail),
                    observation: detail.observation || null
                })),
                taxes: this.taxBreakdown.map(tax => ({
                    tax_id: tax.id,
                    quantity: tax.quantity
                }))
            };
        },
        async cancelQuotation(record) {
            const confirm = await window.Swal.fire({
                icon: "warning",
                html: `<div class="fw-semibold">¿Deseas anular la cotización ${record.reference}?</div>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Sí, anular",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--warning",
                    confirmButton: "br-btn br-btn-danger",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                }
            });

            if(!confirm?.isConfirmed) return;

            Alerts.swals({type: "loading", message: "Anulando cotización"});

            const response = await Requests.patch({
                route: `${this.config.routes.consult}/${record.id}/cancel`
            });

            Alerts.swals({show: false});

            if(Requests.valid({result: response})) {
                Alerts.toastrs({type: "success", subtitle: response.data?.msg || "Cotización anulada correctamente."});
                await this.listQuotations({});
            }else {
                Alerts.toastrs({type: "error", subtitle: response.data?.msg || "No fue posible anular la cotización."});
            }
        },
        canCancel(record) {
            return !["converted", "canceled"].includes(record.status);
        },
        persistQuotationForSale(record) {
            window.sessionStorage.setItem("br_sale_pending_quotation_id", String(record.id));
        },
        addCustomerPostAction({response = null}) {
            if(Requests.valid({result: response}) && response?.data?.customer) {
                this.options.customers = this.options.customers || {records: []};
                this.options.customers.records = this.options.customers.records || [];
                this.options.customers.records.push(response.data.customer);
                this.form.holder = {
                    code: response.data.customer.id,
                    label: `${response.data.customer.document_number || "S/D"} - ${response.data.customer.name}`,
                    data: response.data.customer
                };
            }
        },
        statusLabel(status) {
            return this.statusOptions.find(option => option.code === status)?.label || status;
        },
        statusClass(status) {
            return {
                draft: "br-status-label--primary",
                sent: "br-status-label--info",
                accepted: "br-status-label--success",
                converted: "br-status-label--success",
                canceled: "br-status-label--danger",
                expired: "br-status-label--warning"
            }[status] || "br-status-label--primary";
        },
        legibleFormatDate(value) {
            return value ? Utils.legibleFormatDate({dateString: value, type: "date", separator: "/"}) : "";
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value);
        },
        fixedNumber(value) {
            const decimals = Number(generalConfig.forms.inputs.round || 3);
            return Number(Number(value || 0).toFixed(decimals));
        },
        uuid() {
            return Utils.uuid ? Utils.uuid() : `${Date.now()}-${Math.random()}`;
        }
    }
};
</script>
