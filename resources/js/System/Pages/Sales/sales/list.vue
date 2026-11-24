<template>
    <FgPageHeader title="Listado de ventas" description="Consulta comprobantes y el estado de las ventas.">
        <template #actions>
            <a class="fg-button" href="/sales/create"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva venta</a>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else>
            <FgResponsiveFilters
                id="sales-filters-list"
                class="fg-responsive-filters--list"
                label="Filtros de ventas"
                :loading="lists.entity.extras.loading"
                @search="listEntity({applyFilters: true})"
                @clear="clearFilters">
                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Sucursal" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="lists.entity.filters.branch"
                            :options="branches"
                            :clearable="true"
                            :searchable="branches.length > 6"
                            placeholder="Todas las sucursales">
                            <template #selected-option="{ label }">
                                <span class="fg-selection-summary__value" :title="label">{{ label }}</span>
                            </template>
                        </FgSelect>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Serie" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="lists.entity.filters.serie"
                            :options="series"
                            :clearable="true"
                            :searchable="false"
                            placeholder="Todas las series">
                            <template #option="{ data }">
                                <span v-text="`${data?.legible_serie} - ${documentTypeLabel(data?.document_type)}`" class="d-block fw-bold"></span>
                                <small v-text="data?.branch?.name" class="d-block"></small>
                            </template>
                            <template #selected-option="{ label }">
                                <span class="fg-selection-summary__value" :title="label">{{ label }}</span>
                            </template>
                        </FgSelect>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <SearchInput
                    v-model="lists.entity.filters.sequential"
                    hasDiv
                    title="Secuencia"
                    :titleClass="[config.forms.classes.title]"
                    xl="2"
                    lg="4"/>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                    <InputDate
                    v-model="lists.entity.filters.start_date"
                    hasDiv
                    title="Desde"
                    :titleClass="[config.forms.classes.title]"
                    :max="maxIssueDate"
                    xl="2"
                    lg="6"/>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                    <InputDate
                    v-model="lists.entity.filters.end_date"
                    hasDiv
                    title="Hasta"
                    :titleClass="[config.forms.classes.title]"
                    :max="maxIssueDate"
                    xl="2"
                    lg="6"/>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                    <InputSlot hasDiv title="Cliente" :titleClass="[config.forms.classes.title]" xl="4" lg="6">
                    <template #input>
                        <FgSelect
                            v-model="lists.entity.filters.holder"
                            :options="holders"
                            :filterable="false"
                            :loading="remoteOptions.customers.loading"
                            :clearable="true"
                            placeholder="Todos los clientes"
                            @open="openOptions('customers')"
                            @search="searchOptions('customers', $event)">
                            <template #option="{ label }">
                                <span v-text="truncate({value: label, length: 50})" class="d-block"></span>
                            </template>
                            <template #selected-option="{ label }">
                                <span class="fg-selection-summary__value" :title="label">{{ label }}</span>
                            </template>
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

                <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                    <InputSlot hasDiv title="Estado" :titleClass="[config.forms.classes.title]" xl="3" lg="6">
                    <template #input>
                        <FgSelect
                            v-model="lists.entity.filters.status"
                            :options="statuses"
                            :clearable="true"
                            :searchable="false"
                            placeholder="Todos los estados"/>
                    </template>
                </InputSlot>
                </div>
            </FgResponsiveFilters>

            <ResponsiveTable fg class="fg-sales-table-scroll">
                <table role="table" class="mb-0 fg-table fg-record-table fg-record-table--list">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th scope="col" role="columnheader" style="width: 18%;">Documento</th>
                            <th scope="col" role="columnheader" style="width: 22%;">Cliente</th>
                            <th scope="col" role="columnheader" style="width: 16%;">Sucursal</th>
                            <th scope="col" role="columnheader" style="width: 14%;">Emisión</th>
                            <th scope="col" role="columnheader" class="text-end" style="width: 12%;">Total</th>
                            <th scope="col" role="columnheader" style="width: 10%;">Estado</th>
                            <th scope="col" role="columnheader" class="text-center" style="width: 8%;"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        <tr role="row" v-if="lists.entity.extras.loading" class="fg-table__state text-center">
                            <td role="cell" colspan="7" class="py-4">
                                <FgListLoading label="Buscando ventas…"/>
                            </td>
                        </tr>
                        <template v-else-if="lists.entity.records.total > 0">
                            <tr role="row" v-for="record in lists.entity.records.data" :key="record.id" class="text-center">
                                <td role="cell" class="text-start fg-record-table__identity">
                                    <strong class="fg-row-title" v-text="record.serie_sequential"></strong>
                                    <span class="fg-table-note" v-text="record.serie?.document_type?.name"></span>
                                </td>
                                <td role="cell" class="text-start">
                                    <span class="fg-record-table__label" aria-hidden="true">Cliente</span>
                                    <div class="fg-record-table__value">
                                        <strong class="fg-row-title" v-text="record.holder?.name || 'Cliente no identificado'"></strong>
                                        <span class="fg-table-note" v-text="record.holder?.document_number || 'Sin documento'"></span>
                                    </div>
                                </td>
                                <td role="cell" class="text-start">
                                    <span class="fg-record-table__label" aria-hidden="true">Sucursal</span>
                                    <div class="fg-record-table__value">
                                        <strong class="fg-row-title" v-text="record.serie?.branch?.name || 'Sin sucursal'"></strong>
                                        <span class="fg-table-note" v-text="record.serie?.legible_serie || record.serie?.serie"></span>
                                    </div>
                                </td>
                                <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Emisión</span>
                                    <div class="fg-record-table__value">
                                        <span v-text="legibleFormatDate({dateString: record.issue_date, type: 'weekday_date', separator: '/'})" class="d-block fw-medium"></span>
                                        <span
                                            :class="['fg-sales-relative-pill', { 'fg-sales-relative-pill--today': record.diff_days_issue_date === 0 }]"
                                            v-text="diffDaysLegible({diff: record.diff_days_issue_date})"></span>
                                    </div>
                                </td>
                                <td role="cell" class="text-end pe-3" title="Total">
                                    <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                    <div class="fg-record-table__value">
                                        <span class="fg-money">
                                            <span class="fg-money__unit" v-text="record.currency?.sign ?? ''"></span>
                                            <span class="fg-money__value" v-text="separatorNumber(record.total)"></span>
                                        </span>
                                    </div>
                                </td>
                                <td role="cell" class="fg-record-table__state">
                                    <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                                </td>
                                <td role="cell" class="fg-record-table__actions">
                                    <TableActionsDropdown :label="`Acciones de ${record.serie_sequential}`">
                                        <li><button type="button" class="fg-menu__item" @click="modalActionsEntity({record})">Ver detalle</button></li>
                                        <li v-if="record.status === 'active'"><button type="button" class="fg-menu__item" @click="cancelRecord({record})">Anular venta</button></li>
                                    </TableActionsDropdown>
                                </td>
                            </tr>
                        </template>
                        <tr role="row" class="fg-table__state" v-else>
                            <td role="cell" class="text-center" colspan="7">
                                <FgEmptyState title="No se encontraron registros"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </ResponsiveTable>

            <Paginator :links="lists.entity.records.links" :pagination="lists.entity.records" :loading="lists.entity.extras.loading" @clickPage="listEntity"/>

        </div>
    </InitialLoadTransition>
    <PrintSale :modalId="forms.entity.createUpdate.extras.modals.actions.id" :data="forms.entity.createUpdate.extras.modals.actions.data">
        <template v-slot:extraGroupAppend>
            <div v-if="['active'].includes(forms.entity.createUpdate.extras.modals.actions.data?.status)" class="col-xl-3 col-lg-3 col-md-3 col-sm-3">
                <button class="fg-button fg-button--link fg-icon-danger" type="button" @click="cancelEntity({})">
                    <div class="badge bg-danger p-3 rounded mb-1">
                        <i class="fa-solid fa-rectangle-xmark"></i>
                    </div>
                    <span class="d-block fw-semibold text-danger">Anular venta</span>
                </button>
            </div>
            <div class="row g-2 justify-content-center my-4 px-1 px-md-5">
                <div class="fg-field-cell col-12">
                    <InputText
                    hasDiv
                    :placeholder="MODULE.texts.form.whatsappPlaceholder"
                    v-model="forms.entity.createUpdate.extras.modals.actions.data.whatsapp">
                    <template v-slot:inputGroupAppend>
                        <button class="fg-button" type="button" @click="sendWhatsapp({data: forms.entity.createUpdate.extras.modals.actions.data})" :disabled="!isDefined({value: forms.entity.createUpdate.extras.modals.actions.data.whatsapp})">
                            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline ms-sm-2" v-text="MODULE.texts.actions.send"></span>
                        </button>
                    </template>
                </InputText>
                </div>
                <div class="fg-field-cell col-12">
                    <InputText
                    hasDiv
                    title="Correo electrónico"
                    v-model="forms.entity.createUpdate.extras.modals.actions.data.email">
                    <template v-slot:inputGroupAppend>
                        <button class="fg-button" type="button" @click="sendEmail({data: forms.entity.createUpdate.extras.modals.actions.data})" :disabled="!isDefined({value: forms.entity.createUpdate.extras.modals.actions.data.email})">
                            <i class="fa fa-envelope"></i>
                            <span class="ms-2">Enviar</span>
                        </button>
                    </template>
                </InputText>
                </div>
            </div>
        </template>
    </PrintSale>
</template>

<script>
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";
import * as DateUtils from "@System/Helpers/DateUtils.js";
import {createOptionSearch} from "@/Shared/Options.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

const MODULE = {
    texts: {
        form: {
            whatsappPlaceholder: "Número de celular (ej.: 51987654321)"
        },
        actions: {
            send: "Enviar"
        }
    }
};

export default {
    components: {TableActionsDropdown, SearchInput},
    mixins: [InitialPageMixin, createOptionSearch("customers")],
    mounted: async function() {

        navbarItem("menu-parent-sales", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        let initParams = await this.loadInitialParams(() => this.initParams({})),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {

            await this.listEntity({});

        }

    },
    data() {
        return {
            optionOrigin: "sales-list",
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "sales", type: "list"})
                    },
                    filters: this.defaultFilters(),
                    appliedFilters: null,
                    records: {
                        total: 0,
                        data: [],
                        links: []
                    }
                }
            },
            forms: {
                entity: {
                    createUpdate: {
                        extras: {
                            modals: {
                                actions: {
                                    id: Utils.uuid(),
                                    data: {
                                        id: null,
                                        extras: {},
                                        whatsapp: "",
                                        email: ""
                                    },
                                    errors: {}
                                }
                            }
                        },
                        data: {},
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "sales"}),
                    page: {
                        title: "Listado",
                        active: true,
                        menu: {
                            id: "menu-sales-list"
                        }
                    }
                }
            },
            MODULE
        };
    },
    methods: {
        defaultFilters() {
            return {
                branch: null,
                serie: null,
                sequential: "",
                start_date: "",
                end_date: "",
                holder: null,
                status: null
            };
        },
        async initParams({}) {

            let initParams = await Requests.get({route: this.config.entity.routes.initParams, data: {page: "list"}, showAlert: true});

            this.options.branches    = initParams.data?.config?.branches;
            this.options.customers   = {records: []};
            this.options.salesHeader = initParams.data?.config?.salesHeader;

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                resolve(true);

            });

        },
        async listEntity({url = null, applyFilters = false}) {

            if(applyFilters || !this.lists.entity.appliedFilters) {
                this.lists.entity.appliedFilters = {...this.lists.entity.filters};
            }

            const filterJson = this.requestFilters();
            let requestUrl = url || this.lists.entity.extras.route;
            let requestData = filterJson;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filterJson).forEach(([key, value]) => {
                    if(value != null) urlObj.searchParams.set(key, value);
                    else urlObj.searchParams.delete(key);
                });
                requestUrl = `${urlObj.pathname}${urlObj.search}`;
                requestData = {};
            }

            this.lists.entity.extras.loading = true;

            try {
                this.lists.entity.records = Requests.emptyPagination();
                const response = await Requests.get({route: requestUrl, data: requestData});
                this.lists.entity.records = response?.bool ? response.data : Requests.emptyPagination();
            } finally {
                this.lists.entity.extras.loading = false;
            }
        },
        requestFilters() {
            const filters = this.lists.entity.appliedFilters;

            return {
                branch_id: filters?.branch?.code,
                serie_id: filters?.serie?.code,
                sequential: filters?.sequential,
                start_date: filters?.start_date,
                end_date: filters?.end_date,
                holder_id: filters?.holder?.code,
                status: filters?.status?.code
            };
        },
        clearFilters() {
            this.lists.entity.filters = this.defaultFilters();
            return Alerts.showFiltersCleared();
        },
        modalActionsEntity({record = null}) {

            const whatsapp = record?.holder?.phone_number ?? "";
            const email    = record?.holder?.email ?? "";

            this.forms.entity.createUpdate.extras.modals.actions.data = {...record, extras: {}, whatsapp, email};

            Alerts.modals({type: "show", id: this.forms.entity.createUpdate.extras.modals.actions.id});

        },
        cancelRecord({record = null}) {

            const whatsapp = record?.holder?.phone_number ?? "";
            const email    = record?.holder?.email ?? "";

            this.forms.entity.createUpdate.extras.modals.actions.data = {...record, extras: {}, whatsapp, email};
            this.cancelEntity({});

        },
        cancelEntity({}) {

            const functionName = "cancelEntity";

            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.createUpdate.extras.modals.actions.data);

            const validateForm = this.validateForm({functionName, form});

            Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.actions.id});

            if(validateForm?.bool) {

                let el = this;

                Swal.fire({
                    allowEscapeKey: false,
                    title: "¿Anular venta?",
                    html: `<p class="mb-3">La venta <span class="fw-bold">${form?.serie_sequential ?? ""}</span> quedará anulada.</p>
                            <div class="fg-sale-cancel-hint">
                                <p class="fg-sale-cancel-hint__body">
                                    <span class="fg-sale-cancel-hint__label">Importante.</span>
                                    Si esta venta incluye <span class="fg-sale-cancel-hint__term">membresías</span>, estas serán <span class="fg-sale-cancel-hint__risk">anuladas</span> automáticamente.
                                    El inventario se actualizará según la política configurada para la empresa; revisa el mensaje final para confirmar si hubo devolución de stock.
                                </p>
                            </div>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    showCloseButton: true,
                    closeButtonAriaLabel: "Cerrar confirmación",
                    confirmButtonText: "Anular venta",
                    customClass: {
                        confirmButton: "btn btn-danger waves-effect",
                        cancelButton: "btn btn-secondary waves-effect ms-3"
                    }
                }).then(async function(result) {

                    if(result.isConfirmed) {

                        Alerts.swals({type: "update", entity: "venta"});

                        let cancel = await Requests.patch({route: el.config.entity.routes.cancel, id: form.id});

                        if(Requests.valid({result: cancel})) {

                            Alerts.toastrs({type: "success", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                            el.listEntity({});

                        }else {

                            Alerts.toastrs({type: "error", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                        }

                    }

                });

            }else {

                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            Alerts.tooltips({show: false});

        },
        clearForm({functionName}) {

            switch(functionName) {
                case "createUpdateEntity":
                    break;
            }

        },
        formErrors({functionName, type = "clear", errors = []}) {

            if(["cancelEntity"].includes(functionName)) {

                this.forms.entity.createUpdate.extras.modals.actions.errors = ["set"].includes(type) ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            let result = {
                bool: true
            };

            if(["cancelEntity"].includes(functionName)) {

                result.sale = [];

                if(!this.isDefined({value: form?.id})) {

                    result.sale.push(this.config.forms.errors.labels.required);
                    result.bool = false;

                }

            }

            return result;

        },
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        },
        truncate({value, length}) {

            return Utils.truncate({value, length});

        },
        diffDaysLegible({diff}) {

            return Utils.diffDaysLegible({diff});

        },
        legibleFormatDate({dateString = null, type = "datetime", separator = "/"}) {

            return Utils.legibleFormatDate({dateString, type, separator});

        },
        async sendWhatsapp({data = null, action = "reportSale"}) {

            const phoneNumber = this.forms.entity.createUpdate.extras.modals.actions.data.whatsapp;
            const url = await Requests.saleReportShareUrl({document: data?.id, type: "a4"});
            if(!url) return;
            const message = Utils.getMessageWhatsapp({data, action, url});

            Utils.sendWhatsapp({phoneNumber, message});

        },
        async sendEmail({data = null, action = "reportSale"}) {

            let route = Requests.config({entity: "helpers", type: "sendEmail"});
            const url = await Requests.saleReportShareUrl({document: data?.id, type: "a4"});
            if(!url) return;
            const formJson = {id: data?.id, serie_sequential: data?.serie_sequential, email: data?.email, message: Utils.getMessageWhatsapp({data, action, url})};

            Alerts.swals({});

            let sendEmail = await Requests.post({route: route, data: formJson, id: data?.id});

            if(Requests.valid({result: sendEmail})) {

                Alerts.toastrs({type: "success", subtitle: sendEmail?.data?.msg});
                Alerts.swals({show: false});

            }else {

                Alerts.toastrs({type: "error", subtitle: sendEmail?.data?.msg});
                Alerts.swals({show: false});

            }

            Alerts.tooltips({show: false});

        },
        documentTypeLabel(documentType) {

            const value = String(documentType?.name || documentType?.code || "Comprobante").trim().toUpperCase();

            if(value.includes("BOLETA") || value === "BV") return "Boleta";
            if(value === "FACTURA" || value === "FA") return "Factura";

            return value;

        }
    },
    computed: {
        breadcrumbTitles: function() {

            return [{title: "Ventas"}, this.config.entity.page];

        },
        maxIssueDate() {

            return DateUtils.getCurrentDate("date");

        },
        branches() {

            return (this.options?.branches?.records ?? []).map(branch => ({
                code: branch.id,
                label: branch.name,
                data: branch
            }));

        },
        series: function() {

            let series = [];
            const selectedBranchId = this.lists.entity.filters.branch?.code;
            let branches = (this.options?.branches?.records ?? []);

            for(let branch of branches) {

                if(selectedBranchId && Number(branch.id) !== Number(selectedBranchId)) continue;

                for(let branchSerie of branch.series) {

                    series.push({
                        code: branchSerie.id,
                        label: `(${branch?.name}) ${branchSerie.legible_serie} - ${this.documentTypeLabel(branchSerie?.document_type)}`,
                        data: {...branchSerie, branch}
                    });

                }

            }

            return series;

        },
        holders: function() {

            return (this.options?.customers?.records ?? []).map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        statuses: function() {

            return (this.options?.salesHeader?.statuses ?? []).map(e => ({code: e.code, label: e.label}));

        }
    },
    watch: {
        "lists.entity.filters.branch": function(value) {
            const serie = this.lists.entity.filters.serie;
            if(!value?.code || !serie?.data?.branch?.id) return;
            if(Number(serie.data.branch.id) !== Number(value.code)) this.lists.entity.filters.serie = null;
        }
    }
};
</script>
