<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Content -->
    <div class="row align-items-end g-3 mb-3 mb-md-2">
        <InputSlot
            hasDiv
            :isInputGroup="false"
            :divInputClass="['d-flex flex-wrap justify-content-center align-items-end gap-2 gap-md-3 mb-2']"
            xl="12"
            lg="12">
            <template v-slot:input>
                <template v-if="isDefined({value: customerCurrent?.customer})">
                    <button type="button" class="br-btn br-btn-sm br-btn-search waves-effect" @click="modalCreateUpdateEntity({})">
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="btn btn-info-1 btn-sm waves-effect" @click="getTrackingCustomers({refresh: true})">
                        <i class="fa fa-sync"></i>
                        <span class="ms-2">Refrescar expediente</span>
                    </button>
                </template>
                <template v-else>
                    <button type="button" class="btn btn-primary btn-sm waves-effect" @click="modalCreateUpdateEntity({})">
                        <i class="fa fa-hand-pointer"></i>
                        <span class="ms-2">Seleccione un cliente</span>
                    </button>
                </template>
            </template>
        </InputSlot>
    </div>
    <div class="row g-3">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 ps-1 ps-md-3 pe-0 pe-md-3" v-if="isDefined({value: customerCurrent?.customer})">
            <Timeline :data="customerCurrent ?? {}">
                <template v-slot:statisticsPrepend>
                    <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12">
                        <div class="br-customer-tracking-profile">
                            <span class="br-customer-tracking-profile__avatar">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                            </span>
                            <div class="flex-grow-1 flex-min-w-0">
                                <span class="br-customer-tracking-profile__name" v-text="customerCurrent?.customer?.name ?? ''"></span>
                                <span class="br-customer-tracking-profile__meta">
                                    <strong v-text="customerCurrent?.customer?.identity_document_type?.name ?? ''"></strong>
                                    <span v-text="customerCurrent?.customer?.document_number ?? ''"></span>
                                </span>
                                <span class="br-customer-tracking-profile__period" v-text="periodTypeCurrent"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-7 col-lg-7 col-md-12 col-sm-12">
                        <div class="br-customer-tracking-summary">
                            <article v-for="item in trackingSummaryCards" :key="item.key" class="br-customer-tracking-summary__item">
                                <span :class="['br-customer-tracking-summary__icon', item.tone]">
                                    <i :class="item.icon" aria-hidden="true"></i>
                                </span>
                                <span class="br-customer-tracking-summary__label" v-text="item.label"></span>
                                <strong class="br-customer-tracking-summary__value" v-text="item.value"></strong>
                            </article>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="br-customer-tracking-memberships" v-if="(customerCurrent?.extras?.options?.information ?? []).some(e => ['sales', 'subscriptions', 'attendances'].includes(e))">
                            <span class="br-customer-tracking-memberships__title">
                                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                                Membresía vigente hasta
                            </span>
                            <template v-if="(customerCurrent?.functions?.subscription_end_dates ?? []).length > 0">
                                <ul class="br-customer-tracking-memberships__list">
                                    <li v-for="(record, indexRecord) in (customerCurrent?.functions?.subscription_end_dates ?? [])" :key="indexRecord">
                                        <strong v-text="record?.branch?.name"></strong>
                                        <span v-text="legibleFormatDate({dateString: record?.max_end_date})"></span>
                                    </li>
                                </ul>
                            </template>
                            <template v-else>
                                <span class="br-customer-tracking-memberships__empty">Sin membresías vigentes en el periodo.</span>
                            </template>
                        </div>
                    </div>
                </template>
            </Timeline>
        </div>
        <div v-else class="text-center">
            <WithoutData type="image"/>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" :id="forms.entity.createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.createUpdate.extras.modals.default.titles.default"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <InputSlot
                            hasDiv
                            title="Cliente"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms.entity.createUpdate.data.customer"
                                    :options="customers"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    placeholder="Seleccione un cliente ..."/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            title="Filtrar por periodo"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms.entity.createUpdate.data.periodType"
                                    :options="periodTypes"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="false"
                                    placeholder="Seleccione un periodo ..."/>
                            </template>
                        </InputSlot>
                        <template v-if="isCustomPeriodSelected">
                            <InputDate
                                v-model="forms.entity.createUpdate.data.startDate"
                                hasDiv
                                title="Fecha inicial"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms.entity.createUpdate.errors?.start_date?.[0]"
                                xl="6"
                                lg="6"/>
                            <InputDate
                                v-model="forms.entity.createUpdate.data.endDate"
                                hasDiv
                                title="Fecha final"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms.entity.createUpdate.errors?.end_date?.[0]"
                                xl="6"
                                lg="6"/>
                        </template>
                        <InputSlot
                            hasDiv
                            title="¿Qué deseas visualizar?"
                            isRequired
                            :isInputGroup="false"
                            :divInputClass="['d-flex flex-wrap justify-content-center align-items-end gap-2 gap-md-3 pt-2 pt-md-2']"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <div class="form-check ms-2">
                                    <label class="cursor-pointer">
                                        <input class="form-check-input" type="checkbox" value="sales" v-model="forms.entity.createUpdate.data.options.information"/>
                                        <span class="fw-bold text-secondary">Ventas</span>
                                    </label>
                                </div>
                                <div class="form-check ms-2">
                                    <label class="cursor-pointer">
                                        <input class="form-check-input" type="checkbox" value="subscriptions" v-model="forms.entity.createUpdate.data.options.information"/>
                                        <span class="fw-bold text-secondary">Membresías</span>
                                    </label>
                                </div>
                                <div class="form-check ms-2">
                                    <label class="cursor-pointer">
                                        <input class="form-check-input" type="checkbox" value="attendances" v-model="forms.entity.createUpdate.data.options.information"/>
                                        <span class="fw-bold text-secondary">Asistencias</span>
                                    </label>
                                </div>
                            </template>
                        </InputSlot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="br-btn br-btn-search waves-effect" @click="getTrackingCustomers({refresh: false})">
                        <span>Buscar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

export default {
    components: {
        //
    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-customers", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        let initParams = await this.initParams({}),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {

            Alerts.swals({show: false});
            // this.listEntity({});

        }

    },
    data() {
        return {
            forms: {
                entity: {
                    createUpdate: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    titles: {
                                        default: "Búsqueda"
                                    }
                                }
                            }
                        },
                        data: {
                            customer: null,
                            periodType: null,
                            startDate: "",
                            endDate: "",
                            options: {
                                information: []
                            }
                        },
                        history: {
                            customers: {},
                            customerCurrent: null,
                            periodTypeCurrent: null,
                            startDateCurrent: null,
                            endDateCurrent: null,
                            optionsCurrent: null
                        },
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_customers"}),
                    page: {
                        title: "Expediente",
                        active: true,
                        menu: {
                            id: "menu-customers-history"
                        }
                    }
                }
            }
        };
    },
    methods: {
        // ============================================
        // Initialization Methods
        // ============================================
        async initParams({}) {

            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(initParams?.data?.config) {

                this.options.customers = initParams.data.config.customers;

            }

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                const formData = this.forms.entity.createUpdate.data;

                formData.periodType = this.periodTypes.length > 3 ? this.periodTypes[2] : null;
                formData.options.information = ["sales", "subscriptions"];

                this.modalCreateUpdateEntity({});

                resolve(true);

            });

        },

        // ============================================
        // Form Methods
        // ============================================
        modalCreateUpdateEntity({record = null}) {

            const functionName = "modalCreateUpdateEntity";

            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            Alerts.modals({
                type: "show",
                id: this.forms.entity.createUpdate.extras.modals.default.id
            });

        },
        async getTrackingCustomers({refresh = false}) {

            const functionName = "getTrackingCustomers";

            if(refresh) {

                await this.refreshTrackingData();

            }else {

                await this.fetchTrackingData(functionName);

            }

        },
        async refreshTrackingData() {

            const history = this.forms.entity.createUpdate.history;

            Alerts.swals({});

            try {

                const response = await Utils.getTrackingCustomers({
                    customer: {id: history.customerCurrent.code},
                    period_type: history.periodTypeCurrent.code,
                    start_date: history.startDateCurrent,
                    end_date: history.endDateCurrent,
                    options: history.optionsCurrent
                });

                const tracking = Requests.valid({result: response})
                    ? response?.data?.tracking
                    : {};

                this.forms.entity.createUpdate.history.customers[history.customerCurrent.code] = tracking;

            }finally {

                Alerts.swals({show: false});

            }

        },
        async fetchTrackingData(functionName) {

            const form = this.forms.entity.createUpdate.data;
            const validateForm = this.validateForm({functionName, form});

            if(!validateForm?.bool) {

                this.forms.entity.createUpdate.errors = validateForm;

                Alerts.generateAlert({
                    messages: Utils.getErrors({errors: validateForm}),
                    msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorSearchValidate}</div>`
                });

                return;

            }

            this.forms.entity.createUpdate.errors = {};

            Alerts.modals({
                type: "hide",
                id: this.forms.entity.createUpdate.extras.modals.default.id
            });

            Alerts.swals({});

            try {

                const response = await Utils.getTrackingCustomers({
                    customer: {id: form.customer.code},
                    period_type: form.periodType.code,
                    start_date: form.startDate,
                    end_date: form.endDate,
                    options: form.options
                });

                const history = this.forms.entity.createUpdate.history;

                if(Requests.valid({result: response})) {

                    history.customers[form.customer.code] = response?.data?.tracking;
                    history.customerCurrent   = form.customer;
                    history.periodTypeCurrent = form.periodType;
                    history.startDateCurrent  = form.startDate;
                    history.endDateCurrent    = form.endDate;
                    history.optionsCurrent    = form.options;

                }else {

                    history.customers[form.customer.code] = {};

                }

            }finally {

                Alerts.swals({show: false});

            }

        },

        // ============================================
        // Form Utility Methods
        // ============================================
        clearForm({functionName}) {

            // No form clearing needed for this module

        },
        formErrors({functionName, type = "clear", errors = []}) {

            if(functionName === "modalCreateUpdateEntity") {

                this.forms.entity.createUpdate.errors = type === "set" ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            const result = {
                bool: true,
                customer: [],
                period_type: [],
                start_date: [],
                end_date: [],
                options_information: []
            };

            if(functionName !== "getTrackingCustomers") {

                return result;

            }

            // Validate customer
            if(!this.isDefined({value: form?.customer?.code})) {

                result.customer.push("Debe seleccionar un cliente.");
                result.bool = false;

            }

            // Validate period type
            if(!this.isDefined({value: form?.periodType?.code})) {

                result.period_type.push("Debe seleccionar un periodo.");
                result.bool = false;

            }

            if(form?.periodType?.code === "custom") {

                if(!this.isDefined({value: form.startDate})) {

                    result.start_date.push("Debe seleccionar la fecha inicial.");
                    result.bool = false;

                }

                if(!this.isDefined({value: form.endDate})) {

                    result.end_date.push("Debe seleccionar la fecha final.");
                    result.bool = false;

                }

                if(this.isDefined({value: form.startDate}) && this.isDefined({value: form.endDate}) && form.startDate > form.endDate) {

                    result.end_date.push("La fecha final no puede ser menor que la fecha inicial.");
                    result.bool = false;

                }

            }

            // Validate options information
            if((form?.options?.information ?? []).length === 0) {

                result.options_information.push("Debe seleccionar al menos un tipo de información.");
                result.bool = false;

            }

            return result;

        },

        // ============================================
        // Utility Methods
        // ============================================
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value || 0);

        },
        formatCurrency(value) {

            return `S/ ${this.separatorNumber(value)}`;

        }
    },
    computed: {
        breadcrumbTitles: function() {

            return [{title: "Seguimiento"}, this.config.entity.page];

        },
        customers: function() {

            return this.options?.customers?.records.map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        periodTypes: function() {

            return [
                {code: "last_1_days", label: "Último día"},
                {code: "last_7_days", label: "Últimos 7 días"},
                {code: "last_14_days", label: "Últimos 14 días"},
                {code: "last_21_days", label: "Últimos 21 días"},
                {code: "last_1_months", label: "Último mes"},
                {code: "last_3_months", label: "Últimos 3 meses"},
                {code: "last_6_months", label: "Últimos 6 meses"},
                {code: "last_9_months", label: "Últimos 9 meses"},
                {code: "this_year", label: "Este año"},
                {code: "custom", label: "Rango personalizado"}
            ];

        },
        customerCurrent: function() {

            return this.forms.entity.createUpdate.history?.customers[this.forms.entity.createUpdate.history.customerCurrent?.code] ?? {};

        },
        periodTypeCurrent: function() {

            const code = this.customerCurrent?.extras?.period_type ?? "";
            const found = this.periodTypes.find(p => p.code === code);

            return found?.label ?? "No identificado";

        },
        isCustomPeriodSelected: function() {

            return this.forms.entity.createUpdate.data.periodType?.code === "custom";

        },
        trackingSummary: function() {

            return this.customerCurrent?.summary ?? {};

        },
        trackingSummaryCards: function() {

            const summary = this.trackingSummary;

            return [
                {
                    key: "active_sales_total",
                    label: "Ventas activas",
                    value: this.formatCurrency(summary.active_sales_total ?? 0),
                    icon: "fa-solid fa-receipt",
                    tone: "is-primary"
                },
                {
                    key: "canceled_sales_total",
                    label: "Ventas anuladas",
                    value: this.formatCurrency(summary.canceled_sales_total ?? 0),
                    icon: "fa-solid fa-ban",
                    tone: "is-danger"
                },
                {
                    key: "attendances",
                    label: "Asistencias",
                    value: summary.attendances ?? 0,
                    icon: "fa-solid fa-person-walking-arrow-right",
                    tone: "is-success"
                },
                {
                    key: "active_subscriptions",
                    label: "Membresías activas",
                    value: summary.active_subscriptions ?? 0,
                    icon: "fa-solid fa-id-card",
                    tone: "is-secondary"
                }
            ];

        }
    },
    watch: {
        "forms.entity.createUpdate.data.periodType": function(newValue) {

            if(newValue?.code !== "custom") {

                this.forms.entity.createUpdate.data.startDate = "";
                this.forms.entity.createUpdate.data.endDate = "";
                this.forms.entity.createUpdate.errors.start_date = [];
                this.forms.entity.createUpdate.errors.end_date = [];

            }

        },
        "forms.entity.createUpdate.data.customer": function(newValue, oldValue) {

            //

        }
    }
};
</script>
