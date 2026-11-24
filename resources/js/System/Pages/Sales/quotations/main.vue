<template>
    <FgPageHeader :title="isListPage ? 'Cotizaciones registradas' : 'Nueva cotización'" :description="isListPage ? 'Consulta la vigencia y el estado de tus cotizaciones.' : 'Prepara una propuesta con sus productos, precios y condiciones.'">
        <template #actions>
            <a v-if="isListPage" :href="createUrl" class="fg-button"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva cotización</a>
            <a v-else :href="listUrl" class="fg-button fg-button--secondary">Ver cotizaciones</a>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" :table="isListPage"/>
        <div v-else>
            <template v-if="isListPage">
                <FgResponsiveFilters
                    id="sales-filters-quotes"
                    class="fg-responsive-filters--quotes"
                    label="Filtros de cotizaciones"
                    :loading="loading.list"
                    @search="listQuotations({applyFilters: true})"
                    @clear="clearFilters">
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                        <SearchInput
                        v-model="filters.word"
                        hasDiv
                        title="Búsqueda"
                        :titleClass="[config.forms.classes.title]"
                        placeholder="Buscar por cotización o cliente"
                        xl="5"
                        lg="6"/>
                    </div>

                    <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                        <InputSlot
                        hasDiv
                        title="Estado"
                        :titleClass="[config.forms.classes.title]"
                        xl="3"
                        lg="6">
                        <template #input>
                            <FgSelect
                                v-model="filters.status"
                                :options="statusOptions"
                                :clearable="true"
                                :searchable="false"
                                placeholder="Todos"/>
                        </template>
                    </InputSlot>
                    </div>
                </FgResponsiveFilters>

                <ResponsiveTable fg class="fg-sales-table-scroll">
                    <table role="table" class="mb-0 fg-table fg-record-table fg-record-table--quotes">
                        <thead role="rowgroup">
                            <tr role="row">
                                <th scope="col" role="columnheader" style="width: 18%;">Cotización</th>
                                <th scope="col" role="columnheader" style="width: 26%;">Cliente</th>
                                <th scope="col" role="columnheader" style="width: 16%;">Sucursal</th>
                                <th scope="col" role="columnheader" style="width: 14%;">Vigencia</th>
                                <th scope="col" role="columnheader" class="text-end" style="width: 12%;">Total</th>
                                <th scope="col" role="columnheader" style="width: 8%;">Estado</th>
                                <th scope="col" role="columnheader" class="text-center" style="width: 6%;">
                                    <span class="visually-hidden">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody role="rowgroup">
                            <tr role="row" class="fg-table__state" v-if="loading.list">
                                <td role="cell" colspan="7" class="text-center py-4">
                                    <FgListLoading label="Buscando cotizaciones…"/>
                                </td>
                            </tr>
                            <template v-else-if="records.total > 0">
                                <tr role="row" v-for="record in records.data" :key="record.id">
                                    <td role="cell" class="fg-record-table__identity">
                                        <strong class="fg-row-title" v-text="record.reference"></strong>
                                        <span class="fg-table-note" v-text="legibleFormatDate(record.issue_date)"></span>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Cliente</span>
                                        <div class="fg-record-table__value">
                                            <strong class="fg-row-title" v-text="record.holder?.name || 'Cliente no identificado'"></strong>
                                            <span class="fg-table-note" v-text="record.holder?.document_number || 'Sin documento'"></span>
                                        </div>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Sucursal</span>
                                        <div class="fg-record-table__value">
                                            <span v-text="record.branch?.name || 'Sin sucursal'"></span>
                                        </div>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Vigencia</span>
                                        <div class="fg-record-table__value">
                                            <span v-text="record.valid_until ? legibleFormatDate(record.valid_until) : 'Sin vencimiento'"></span>
                                        </div>
                                    </td>
                                    <td role="cell" class="text-end">
                                        <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                        <div class="fg-record-table__value">
                                            <span class="fg-money">
                                                <span class="fg-money__unit" v-text="record.currency?.sign || 'S/'"></span>
                                                <span class="fg-money__value" v-text="separatorNumber(record.total)"></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td role="cell" class="fg-record-table__state">
                                        <span
                                            class="fg-badge"
                                            :class="statusClass(record.status)"
                                            v-text="statusLabel(record.status)">
                                        </span>
                                    </td>
                                    <td role="cell" class="fg-record-table__actions">
                                        <TableActionsDropdown :label="`Acciones de ${record.reference}`">
                                            <li><a :href="saleUrl" class="fg-menu__item" @click="persistQuotationForSale(record)">Usar en venta</a></li>
                                            <li v-if="canCancel(record)"><button type="button" class="fg-menu__item" @click="cancelQuotation(record)">Anular cotización</button></li>
                                        </TableActionsDropdown>
                                    </td>
                                </tr>
                            </template>
                            <tr role="row" class="fg-table__state" v-else>
                                <td role="cell" colspan="7" class="text-center py-4">
                                    <FgEmptyState title="No encontramos cotizaciones" description="Revisa la referencia o limpia los filtros para buscar de nuevo."/>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </ResponsiveTable>

                <Paginator :links="records.links" :pagination="records" :loading="loading.list" @clickPage="listQuotations"/>
            </template>

            <template v-else>
                <div class="fg-sale-create-grid">
                    <div class="fg-sale-create-main">
                        <div class="fg-sales-panel">
                            <div class="fg-sales-panel__body">
                                <h2 class="fg-section-title fg-panel-heading">Datos de la cotización</h2>
                                <div class="row g-2 mb-2">
                                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                                        <InputSlot
                                        hasDiv
                                        title="Sucursal"
                                        :titleClass="[config.forms.classes.title]"
                                        xl="4"
                                        lg="4">
                                        <template #input>
                                            <FgSelect
                                                v-model="form.branch"
                                                :options="branchOptions"
                                                :clearable="false"
                                                :searchable="branchOptions.length > 6"
                                                placeholder="Seleccione"/>
                                        </template>
                                    </InputSlot>
                                    </div>

                                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                                        <InputDate
                                        v-model="form.issue_date"
                                        hasDiv
                                        title="Fecha de emisión"
                                        :titleClass="[config.forms.classes.title]"
                                        isRequired
                                        xl="4"
                                        lg="4"/>
                                    </div>

                                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                                        <InputDate
                                        v-model="form.valid_until"
                                        hasDiv
                                        title="Válida hasta"
                                        :has-text-bottom="Boolean(validityError)"
                                        :text-bottom-info="validityError ? [validityError] : []"
                                        :titleClass="[config.forms.classes.title]"
                                        xl="4"
                                        lg="4"/>
                                    </div>

                                    <div class="fg-field-cell col-12">
                                        <InputSlot
                                        hasDiv
                                        title="Cliente"
                                        :titleClass="[config.forms.classes.title]"
                                        isRequired
                                        xl="12"
                                        lg="12">
                                        <template #default>
                                            <AddCustomer trigger-label="Agregar cliente" :options="customerQuickCreateOptions" @postAction="addCustomerPostAction"/>
                                        </template>
                                        <template #input>
                                            <FgSelect
                                                v-model="form.holder"
                                                :options="holderOptions"
                                                :clearable="false"
                                                :searchable="true"
                                                :filterable="false"
                                                :loading="remoteOptions.customers.loading"
                                                @open="openOptions('customers')"
                                                @search="searchOptions('customers', $event)"
                                                placeholder="Seleccione cliente">
                                                <template #list-footer>
                                                    <li v-if="remoteOptions.customers.hasMore" class="px-2 py-1">
                                                        <button type="button" class="w-100 text-start fg-button fg-button--link" @mousedown.prevent @click.stop="loadMoreOptions('customers')">Cargar más clientes</button>
                                                    </li>
                                                </template>
                                                <template #no-options>Sin clientes para esta búsqueda.</template>
                                            </FgSelect>
                                        </template>
                                    </InputSlot>
                                    </div>

                                    <div class="fg-field-cell col-12">
                                        <InputTextArea
                                        v-model="form.observation"
                                        hasDiv
                                        title="Observaciones"
                                        :titleClass="[config.forms.classes.title]"
                                        placeholder="Condiciones comerciales, alcance o notas para el cliente"
                                        xl="12"
                                        lg="12"/>
                                    </div>
                                </div>

                                <section class="fg-quotation-builder mt-2">
                                    <div class="fg-quotation-builder__head">
                                        <div>
                                            <h2 class="fg-section-title mb-0">Detalle de la cotización</h2>
                                            <span class="fg-help">{{ form.details.length }} {{ form.details.length === 1 ? "ítem" : "ítems" }}</span>
                                        </div>
                                        <button type="button" class="fg-button" @click="addDetail">
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                            <span>Agregar ítem</span>
                                        </button>
                                    </div>

                                    <ResponsiveTable fg class="fg-sales-table-scroll">
                                        <table role="table" class="fg-record-table fg-record-table--editor mb-0 fg-table">
                                            <thead role="rowgroup">
                                                <tr role="row">
                                                    <th scope="col" role="columnheader">Ítem</th>
                                                    <th scope="col" role="columnheader" class="text-end" style="width: 14%;">Cantidad</th>
                                                    <th scope="col" role="columnheader" class="text-center" style="width: 18%;">Precio</th>
                                                    <th scope="col" role="columnheader" class="text-end" style="width: 16%;">Total</th>
                                                    <th scope="col" role="columnheader" class="text-center" style="width: 7%;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody role="rowgroup">
                                                <tr role="row" v-for="(detail, index) in form.details" :key="detail.uid">
                                                    <td role="cell" class="fg-record-table__identity">
                                                        <FgSelect
                                                            v-model="detail.item"
                                                            :aria-label="`Ítem ${index + 1}`"
                                                            :options="itemOptions"
                                                            :clearable="false"
                                                            :searchable="true"
                                                            :filterable="false"
                                                            :loading="remoteOptions.items.loading"
                                                            @open="openOptions('items')"
                                                            @search="searchOptions('items', $event)"
                                                            placeholder="Seleccione ítem"
                                                            @option:selected="syncDetailItem(detail)">
                                                            <template #list-footer>
                                                                <li v-if="remoteOptions.items.hasMore" class="px-2 py-1">
                                                                    <button type="button" class="w-100 text-start fg-button fg-button--link" @mousedown.prevent @click.stop="loadMoreOptions('items')">Cargar más ítems</button>
                                                                </li>
                                                            </template>
                                                            <template #no-options>Sin ítems para esta búsqueda.</template>
                                                        </FgSelect>
                                                    </td>
                                                    <td role="cell">
                                                        <span class="fg-record-table__label" aria-hidden="true">Cantidad</span>
                                                        <div class="fg-record-table__value">
                                                            <InputNumber
                                                                v-model="detail.quantity"
                                                                :aria-label="`Cantidad del ítem ${index + 1}`"
                                                                :title="''"
                                                                :titleClass="[]"
                                                                :minValue="0"
                                                                @change="syncDetailTotal(detail)"/>
                                                        </div>
                                                    </td>
                                                    <td role="cell">
                                                        <span class="fg-record-table__label" aria-hidden="true">Precio</span>
                                                        <div class="fg-record-table__value">
                                                            <InputNumber
                                                                v-model="detail.price"
                                                                :aria-label="`Precio del ítem ${index + 1}`"
                                                                :title="''"
                                                                :titleClass="[]"
                                                                :minValue="0"
                                                                @change="syncDetailTotal(detail)">
                                                                <template #inputGroupPrepend>
                                                                    <span class="fg-field__prefix" v-text="currencySign"></span>
                                                                </template>
                                                            </InputNumber>
                                                        </div>
                                                    </td>
                                                    <td role="cell" class="text-end">
                                                        <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                                        <div class="fg-record-table__value">
                                                            <strong v-text="`${currencySign} ${separatorNumber(lineTotal(detail))}`"></strong>
                                                        </div>
                                                    </td>
                                                    <td role="cell" class="text-center fg-record-table__actions">
                                                        <button
                                                            type="button"
                                                            class="fg-button fg-button--icon fg-icon-danger"
                                                            title="Quitar"
                                                            aria-label="Quitar ítem"
                                                            @click="removeDetail(index)">
                                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr role="row" class="fg-table__state" v-if="form.details.length === 0">
                                                    <td role="cell" colspan="5" class="text-center py-4">
                                                        <FgEmptyState title="Aún no hay ítems en la cotización" description="Agrega un ítem para preparar la propuesta."/>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </ResponsiveTable>
                                </section>
                            </div>
                        </div>
                    </div>

                    <aside class="fg-sale-create-side" aria-label="Resumen de la cotización">
                        <div class="fg-document-settlement fg-sale-settlement">
                            <article class="fg-document-settlement__section">
                                <header class="fg-document-settlement__section-head">
                                    <h2 class="fg-section-title mb-0">Impuestos extras</h2>
                                </header>
                                <div v-if="optionalTaxes.length" class="fg-document-settlement__taxes">
                                    <label
                                        v-for="tax in optionalTaxes"
                                        :key="tax.id"
                                        class="fg-check fg-document-settlement__tax-option">
                                        <input
                                            v-model="form.selected_taxes"

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
                                                <span class="fg-field__prefix fg-tax-quantity__label">Veces</span>
                                            </template>
                                        </InputNumber>
                                    </label>
                                </div>
                                <p v-else class="fg-document-settlement__empty mb-0">Sin impuestos extras configurados.</p>
                            </article>

                            <div class="fg-document-summary-card">
                            <h3 class="fg-document-summary-card__title">Resumen</h3>
                            <div class="fg-document-settlement__summary">
                                <span class="fg-document-settlement__summary-label fg-document-settlement__summary-label--primary">Subtotal</span>
                                <strong class="fg-document-settlement__summary-value fg-document-settlement__summary-value--primary" v-text="`${currencySign} ${separatorNumber(subtotal)}`"></strong>
                                <span class="fg-document-settlement__summary-label fg-document-settlement__summary-label--primary">IGV</span>
                                <strong class="fg-document-settlement__summary-value fg-document-settlement__summary-value--primary" v-text="`${currencySign} ${separatorNumber(taxTotal)}`"></strong>
                                <span class="fg-document-settlement__summary-label fg-document-settlement__summary-label--total">Total</span>
                                <strong class="fg-document-settlement__summary-value fg-document-settlement__summary-value--total" v-text="`${currencySign} ${separatorNumber(total)}`"></strong>
                            </div>
                        </div>

                            <div class="fg-sale-sidebar-actions fg-sale-sidebar-actions--inline">
                            <button
                                type="button"
                                class="fg-button fg-sale-sidebar-actions__cta"
                                :disabled="!canSubmit || loading.store"
                                @click="storeQuotation">
                                <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
                                <span v-text="loading.store ? 'Guardando cotización' : 'Guardar cotización'"></span>
                            </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </template>
        </div>
    </InitialLoadTransition>
</template>

<script>
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import { generalConfig } from "../../../Helpers/Constants.js";
import * as Alerts from "../../../Helpers/Alerts.js";
import * as Requests from "../../../Helpers/Requests.js";
import * as Utils from "../../../Helpers/Utils.js";
import {createOptionSearch} from "@/Shared/Options.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

const MODULE = {
    entity: "quotations",
    page: "Cotizaciones"
};

export default {
    components: {TableActionsDropdown, SearchInput},
    mixins: [InitialPageMixin, createOptionSearch("customers", "items")],
    name: "SalesQuotationsMain",
    data() {
        return {
            config: {
                ...Requests.config({entity: MODULE.entity}),
                forms: generalConfig.forms,
                messages: generalConfig.messages
            },
            options: {},
            optionOrigin: "quotations-create",
            loading: {
                init: false,
                list: false,
                store: false
            },
            appliedFilters: null,
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
                const quantity = this.selectedTaxQuantity(tax);
                const isIgvTax = this.isIgvTax(tax);
                const lines = this.form.details.map(detail => ({
                    total: this.lineTotal(detail),
                    includesTax: this.detailIncludesTax(detail),
                    exempt: isIgvTax && this.detailIsIgvExempt(detail)
                }));
                const {amount, totalImpact} = Utils.documentTax(tax, lines, quantity);

                return {...tax, amount, impact: totalImpact, quantity};
            });
        },
        subtotal() {
            const grossSubtotal = Utils.sum(this.form.details.map(detail => this.lineTotal(detail)));
            const includedTaxTotal = Utils.sum(this.taxBreakdown.map(tax => Utils.subtract(tax.amount, tax.impact)));

            return Utils.subtract(grossSubtotal, includedTaxTotal);
        },
        taxTotal() {
            return Utils.sum(this.taxBreakdown.map(tax => tax.amount));
        },
        total() {
            const grossSubtotal = Utils.sum(this.form.details.map(detail => this.lineTotal(detail)));
            const taxImpact = Utils.sum(this.taxBreakdown.map(tax => tax.impact));

            return Utils.sum([grossSubtotal, taxImpact]);
        },
        validityError() {
            const {issue_date, valid_until} = this.form;
            return issue_date && valid_until && valid_until < issue_date
                ? "Usa una fecha igual o posterior a la emisión."
                : "";
        },
        canSubmit() {
            return !this.validityError && Boolean(this.form.holder && this.form.currency && this.validDetails.length && this.total > 0);
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
    async mounted() {
        navbarItem("menu-parent-sales", {addClass: "open"});
        navbarItem(this.isCreatePage ? "menu-quotations-create" : "menu-quotations-list", {});
        const initialized = await this.loadInitialParams(() => this.initParams());
        if(initialized) {
            if(this.isListPage) await this.listQuotations({});
            else if(this.form.details.length === 0) this.addDetail();
        }
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

            const response = await Requests.get({
                route: this.config.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(Requests.valid({result: response})) {
                this.options = response.data?.config || {};
                this.form.branch = this.branchOptions[0] || null;
                this.form.currency = this.currencyOptions[0] || null;

            }

            this.loading.init = false;
            return Requests.valid({result: response});
        },
        clearFilters() {
            this.filters = {word: "", status: null};
            Alerts.showFiltersCleared();
        },
        async listQuotations({url = null, applyFilters = false} = {}) {
            if(applyFilters || !this.appliedFilters) this.appliedFilters = {...this.filters};
            this.loading.list = true;

            try {
                this.records = Requests.emptyPagination();

                const response = await Requests.get({
                    route: url || this.config.routes.list,
                    data: {
                        word: this.appliedFilters.word,
                        status: this.appliedFilters.status?.code
                    },
                    showAlert: true
                });

                if(response.bool) {
                    this.records = response.data;
                }
            } finally {
                this.loading.list = false;
            }
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
            return Utils.lineTotal(detail.quantity, detail.price);
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
                title: "¿Anular cotización?",
                text: `La cotización ${record.reference} dejará de estar disponible para nuevas operaciones.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                buttonsStyling: false,
                confirmButtonText: "Anular cotización",
                customClass: {
                    container: "fg-swal-layer",
                    popup: "fg-swal",
                    confirmButton: "fg-button fg-button--danger",
                    cancelButton: "fg-button fg-button--secondary ms-2"
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
                draft: "fg-badge",
                sent: "fg-badge",
                accepted: "fg-badge--success",
                converted: "fg-badge--success",
                canceled: "fg-badge--danger",
                expired: "fg-badge--warning"
            }[status] || "fg-badge";
        },
        legibleFormatDate(value) {
            return value ? Utils.legibleFormatDate({dateString: value, type: "date", separator: "/"}) : "";
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value);
        },
        fixedNumber(value) {
            return Utils.roundNumber(value);
        },
        uuid() {
            return Utils.uuid ? Utils.uuid() : `${Date.now()}-${Math.random()}`;
        }
    }
};
</script>
