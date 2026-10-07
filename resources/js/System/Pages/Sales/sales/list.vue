<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-filter-bar br-sales-list__filters">
        <div class="row align-items-end g-2">
            <InputSlot hasDiv title="Sucursal" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="lists.entity.filters.branch"
                        :options="branches"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="branches.length > 6"
                        append-to-body
                        placeholder="Todas las sucursales">
                        <template #selected-option="{ label }">
                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                        </template>
                    </v-select>
                </template>
            </InputSlot>

            <InputSlot hasDiv title="Serie" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="lists.entity.filters.serie"
                        :options="series"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="false"
                        append-to-body
                        placeholder="Todas las series">
                        <template #option="{ data }">
                            <span v-text="`${data?.legible_serie} - ${documentTypeLabel(data?.document_type)}`" class="d-block fw-bold"></span>
                            <small v-text="data?.branch?.name" class="d-block"></small>
                        </template>
                        <template #selected-option="{ label }">
                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                        </template>
                    </v-select>
                </template>
            </InputSlot>

            <InputText
                v-model="lists.entity.filters.sequential"
                @enterKeyPressed="listEntity({})"
                hasDiv
                title="Secuencia"
                :titleClass="[config.forms.classes.title]"
                xl="2"
                lg="4"/>

            <InputDate
                v-model="lists.entity.filters.start_date"
                @enterKeyPressed="listEntity({})"
                hasDiv
                title="Desde"
                :titleClass="[config.forms.classes.title]"
                :max="maxIssueDate"
                xl="2"
                lg="6"/>

            <InputDate
                v-model="lists.entity.filters.end_date"
                @enterKeyPressed="listEntity({})"
                hasDiv
                title="Hasta"
                :titleClass="[config.forms.classes.title]"
                :max="maxIssueDate"
                xl="2"
                lg="6"/>

            <InputSlot hasDiv title="Cliente" :titleClass="[config.forms.classes.title]" xl="4" lg="6">
                <template #input>
                    <v-select
                        v-model="lists.entity.filters.holder"
                        :options="holders"
                        :filterable="false"
                        :loading="commercialSelection.customers.loading"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        append-to-body
                        placeholder="Todos los clientes"
                        @open="openCommercialOptions('customers')"
                        @search="searchCommercialOptions('customers', $event)">
                        <template #option="{ label }">
                            <span v-text="truncate({value: label, length: 50})" class="d-block"></span>
                        </template>
                        <template #selected-option="{ label }">
                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                        </template>
                        <template #list-footer>
                            <li v-if="commercialSelection.customers.hasMore" class="px-2 py-1">
                                <button type="button" class="btn btn-link btn-sm w-100 text-start" @mousedown.prevent @click.stop="loadMoreCommercialOptions('customers')">Cargar más clientes</button>
                            </li>
                        </template>
                        <template #no-options>Sin clientes para esta búsqueda.</template>
                    </v-select>
                </template>
            </InputSlot>

            <InputSlot hasDiv title="Estado" :titleClass="[config.forms.classes.title]" xl="3" lg="6">
                <template #input>
                    <v-select
                        v-model="lists.entity.filters.status"
                        :options="statuses"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="false"
                        append-to-body
                        placeholder="Todos los estados"/>
                </template>
            </InputSlot>

            <InputSlot
                hasDiv
                :isInputGroup="false"
                :divInputClass="['br-filter-bar__actions']"
                xl="5"
                lg="6">
                <template #input>
                    <button type="button" class="br-btn br-btn-sm br-btn-search" @click="listEntity({})" :disabled="lists.entity.extras.loading">
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="br-btn br-btn-sm br-btn-outline-secondary" @click="clearFilters" :disabled="lists.entity.extras.loading">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                        <span>Limpiar</span>
                    </button>
                </template>
            </InputSlot>
        </div>
    </section>

    <div class="table-responsive br-entity-table-wrap">
        <table class="table br-entity-table mb-0">
            <thead>
                <tr>
                    <th style="width: 18%;">Documento</th>
                    <th style="width: 22%;">Cliente</th>
                    <th style="width: 16%;">Sucursal</th>
                    <th style="width: 14%;">Emisión</th>
                    <th class="text-end" style="width: 12%;">Total</th>
                    <th style="width: 10%;">Estado</th>
                    <th class="text-center" style="width: 8%;"><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="lists.entity.extras.loading" class="text-center">
                    <td colspan="7" class="py-4">
                        <Loader/>
                    </td>
                </tr>
                <template v-else-if="lists.entity.records.total > 0">
                    <tr v-for="record in lists.entity.records.data" :key="record.id" class="text-center">
                        <td class="text-start">
                            <strong class="br-entity-primary" v-text="record.serie_sequential"></strong>
                            <span class="br-entity-table__meta" v-text="record.serie?.document_type?.name"></span>
                        </td>
                        <td class="text-start">
                            <strong class="br-entity-primary" v-text="record.holder?.name || 'Cliente no identificado'"></strong>
                            <span class="br-entity-table__meta" v-text="record.holder?.document_number || 'Sin documento'"></span>
                        </td>
                        <td class="text-start">
                            <strong class="br-entity-primary" v-text="record.serie?.branch?.name || 'Sin sucursal'"></strong>
                            <span class="br-entity-table__meta" v-text="record.serie?.legible_serie || record.serie?.serie"></span>
                        </td>
                        <td>
                            <span v-text="legibleFormatDate({dateString: record.issue_date, type: 'weekday_date', separator: '/'})" class="d-block fw-semibold"></span>
                            <span
                                :class="['br-sales-relative-pill', { 'br-sales-relative-pill--today': record.diff_days_issue_date === 0 }]"
                                v-text="diffDaysLegible({diff: record.diff_days_issue_date})"></span>
                        </td>
                        <td class="text-end align-middle pe-3" title="Total">
                            <span class="br-amount-inline">
                                <span class="br-amount-inline__sign" v-text="record.currency?.sign ?? ''"></span>
                                <span class="br-amount-inline__amount" v-text="separatorNumber(record.total)"></span>
                            </span>
                        </td>
                        <td>
                            <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td>
                            <InputSlot hasDiv :isInputGroup="false" :divInputClass="['br-table-actions']" xl="12" lg="12">
                                <template #input>
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-info"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Ver acciones"
                                        :aria-label="`Ver acciones de ${record.serie_sequential}`"
                                        @click="modalActionsEntity({record})">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        v-if="record.status === 'active'"
                                        type="button"
                                        class="br-icon-action br-icon-action-danger"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Anular venta"
                                        :aria-label="`Anular venta ${record.serie_sequential}`"
                                        @click="cancelRecord({record})">
                                        <i class="fa-solid fa-rectangle-xmark" aria-hidden="true"></i>
                                    </button>
                                </template>
                            </InputSlot>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td class="text-center" colspan="7">
                        <WithoutData type="image"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center" v-if="!lists.entity.extras.loading && lists.entity.records?.total > 0">
        <Paginator :links="lists.entity.records.links" @clickPage="listEntity"/>
    </div>

    <PrintSale :modalId="forms.entity.createUpdate.extras.modals.actions.id" :data="forms.entity.createUpdate.extras.modals.actions.data">
        <template v-slot:extraGroupAppend>
            <div v-if="['active'].includes(forms.entity.createUpdate.extras.modals.actions.data?.status)" class="col-xl-3 col-lg-3 col-md-3 col-sm-3">
                <div class="text-center cursor-pointer p-1" @click="cancelEntity({})">
                    <div class="badge bg-danger p-3 rounded mb-1">
                        <i class="fa-solid fa-rectangle-xmark fs-3"></i>
                    </div>
                    <span class="d-block fw-semibold text-danger">Anular venta</span>
                </div>
            </div>
            <div class="row g-2 justify-content-center my-4 px-1 px-md-5">
                <InputText
                    hasDiv
                    :placeholder="MODULE.texts.form.whatsappPlaceholder"
                    v-model="forms.entity.createUpdate.extras.modals.actions.data.whatsapp">
                    <template v-slot:inputGroupAppend>
                        <button class="btn btn-success waves-effect" type="button" @click="sendWhatsapp({data: forms.entity.createUpdate.extras.modals.actions.data})" :disabled="!isDefined({value: forms.entity.createUpdate.extras.modals.actions.data.whatsapp})">
                            <i class="fa-brands fa-whatsapp fs-5" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline ms-sm-2" v-text="MODULE.texts.actions.send"></span>
                        </button>
                    </template>
                </InputText>
                <InputText
                    hasDiv
                    title="Correo electrónico"
                    v-model="forms.entity.createUpdate.extras.modals.actions.data.email">
                    <template v-slot:inputGroupAppend>
                        <button class="btn btn-info-1 waves-effect" type="button" @click="sendEmail({data: forms.entity.createUpdate.extras.modals.actions.data})" :disabled="!isDefined({value: forms.entity.createUpdate.extras.modals.actions.data.email})">
                            <i class="fa fa-envelope"></i>
                            <span class="ms-2">Enviar</span>
                        </button>
                    </template>
                </InputText>
            </div>
        </template>
    </PrintSale>
</template>

<script>
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";
import * as DateUtils from "@System/Helpers/DateUtils.js";
import commercialSelection from "../commercialSelection.js";

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
    mixins: [commercialSelection],
    mounted: async function() {

        Utils.navbarItem("menu-parent-sales", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        let initParams = await this.initParams({}),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {

            Alerts.swals({show: false});
            await this.listEntity({});

        }

    },
    data() {
        return {
            commercialSelectionRoute: `${Requests.config({entity: "sales", type: "consult"})}/customer-options`,
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "sales", type: "list"})
                    },
                    filters: this.defaultFilters(),
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
            this.options.holders     = this.options.customers;
            this.options.salesHeader = initParams.data?.config?.salesHeader;

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                resolve(true);

            });

        },
        async listEntity({url = null}) {

            const filterJson = this.requestFilters();
            let requestUrl = url || this.lists.entity.extras.route;
            let requestData = filterJson;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filterJson).forEach(([key, value]) => {
                    if(this.isDefined({value}) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);
                });
                requestUrl = `${urlObj.pathname}${urlObj.search}`;
                requestData = {};
            }

            this.lists.entity.extras.loading = true;
            this.lists.entity.records = (await Requests.get({route: requestUrl, data: requestData}))?.data || {total: 0, data: [], links: []};
            this.lists.entity.extras.loading = false;

        },
        requestFilters() {
            const filters = Utils.cloneJson(this.lists.entity.filters);

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
            this.listEntity({});
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
                    html: `<p class="mb-3">¿Desea anular la venta <span class="fw-bold">${form?.serie_sequential ?? ""}</span>?</p>
                            <div class="br-sale-cancel-hint">
                                <p class="br-sale-cancel-hint__body">
                                    <span class="br-sale-cancel-hint__label">Importante.</span>
                                    Si esta venta incluye <span class="br-sale-cancel-hint__term">membresías</span>, estas serán <span class="br-sale-cancel-hint__risk">anuladas</span> automáticamente.
                                    El inventario se actualizará según la política configurada para la empresa; revisa el mensaje final para confirmar si hubo devolución de stock.
                                </p>
                            </div>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: "Sí, anular",
                    cancelButtonText: "Cancelar",
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
            const message = Utils.getMessageWhatsapp({data, action, url});

            Utils.sendWhatsapp({phoneNumber, message});

        },
        async sendEmail({data = null, action = "reportSale"}) {

            let route = Requests.config({entity: "helpers", type: "sendEmail"});
            const url = await Requests.saleReportShareUrl({document: data?.id, type: "a4"});
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

            return (this.options?.holders?.records ?? []).map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

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
