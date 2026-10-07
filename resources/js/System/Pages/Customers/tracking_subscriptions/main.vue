<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Content -->
    <div class="row align-items-start g-3 mb-3 mb-md-4">
        <div class="col-lg-9 col-12">
            <div class="row g-3">
                <InputSlot
                    hasDiv
                    title="Sucursal"
                    :titleClass="[config.forms.classes.title]"
                    xl="12"
                    lg="12">
                    <template v-slot:input>
                        <v-select
                            v-model="lists.entity.filters.branch"
                            :options="branches"
                            :class="config.forms.classes.select2"
                            :clearable="false"
                            :searchable="false"
                            placeholder="Seleccione una sucursal ..."/>
                    </template>
                </InputSlot>
                <InputSlot
                    hasDiv
                    title="Cliente"
                    :titleClass="[config.forms.classes.title]"
                    xl="12"
                    lg="12">
                    <template v-slot:input>
                        <v-select
                            v-model="lists.entity.filters.customer"
                            :options="customers"
                            :class="config.forms.classes.select2"
                            :clearable="true"
                            placeholder="Seleccione un cliente ..."/>
                    </template>
                </InputSlot>
                <InputDate
                    v-model="lists.entity.filters.start_date"
                    @change="listEntity({})"
                    hasDiv
                    title="Desde"
                    :titleClass="[config.forms.classes.title]"
                    xl="6"
                    lg="6"/>
                <InputDate
                    v-model="lists.entity.filters.end_date"
                    @change="listEntity({})"
                    hasDiv
                    title="Hasta"
                    :titleClass="[config.forms.classes.title]"
                    xl="6"
                    lg="6"/>
            </div>
        </div>
        <div class="col-lg-3 col-12">
            <div class="row g-1">
                <div class="col-xl-12">
                    <label :class="[config.forms.classes.title]">Estado</label>
                </div>
                <div class="col-lg-12 col-xl-12">
                    <div class="form-check">
                        <label class="py-1 cursor-pointer">
                            <input :class="['form-check-input', lists.entity.filters.status == '' ? 'bg-secondary border-secondary' : '']" type="radio" value="" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                            <span class="fw-bold text-secondary">Todos los estados</span>
                        </label>
                    </div>
                </div>
                <div class="col-lg-12 col-xl-12">
                    <div class="form-check">
                        <label class="py-1 cursor-pointer">
                            <input :class="['form-check-input', lists.entity.filters.status == 'active' ? 'bg-success border-success' : '']" type="radio" value="active" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                            <span class="fw-bold text-success">Vigente</span>
                        </label>
                    </div>
                </div>
                <div class="col-lg-12 col-xl-12">
                    <div class="form-check">
                        <label class="py-1 cursor-pointer">
                            <input :class="['form-check-input', lists.entity.filters.status == 'inactive' ? 'bg-primary border-primary' : '']" type="radio" value="inactive" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                            <span class="fw-bold text-primary">Vencida</span>
                        </label>
                    </div>
                </div>
                <div class="col-lg-12 col-xl-12">
                    <div class="form-check">
                        <label class="py-1 cursor-pointer">
                            <input :class="['form-check-input', lists.entity.filters.status == 'canceled' ? 'bg-danger border-danger' : '']" type="radio" value="canceled" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                            <span class="fw-bold text-danger">Anulada</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <InputSlot
            hasDiv
            :isInputGroup="false"
            :divInputClass="['d-flex flex-wrap justify-content-start gap-2 gap-md-3']"
            xl="12"
            lg="12">
            <template v-slot:input>
                <button type="button" class="br-btn br-btn-search waves-effect" @click="listEntity({})" :disabled="lists.entity.extras.loading">
                    <span>Filtrar</span>
                </button>
                <button type="button" class="br-btn br-btn-action-create waves-effect" @click="createManualEntity">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Agregar membresía</span>
                </button>
            </template>
        </InputSlot>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="text-center align-middle">
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 10%;"></th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 20%;">SUCURSAL</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;">CLIENTE</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 15%;">FECHA DE INICIO</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 15%;">FECHA DE FINALIZACIÓN</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 15%;">ACCIONES</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <template v-if="lists.entity.extras.loading">
                    <tr class="text-center">
                        <td colspan="99" class="py-4">
                            <Loader/>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <template v-if="lists.entity.records.total > 0">
                        <tr v-for="record in lists.entity.records.data" :key="record.id" class="text-center">
                            <td>
                                <StatusBadge
                                    :status="record.status"
                                    :formatted-status="record.formatted_status"
                                    :custom-variants="statusBadgeSubscriptionVariants"/>
                            </td>
                            <td class="text-start">
                                <span v-text="record.branch?.name" class="fw-bold d-block"></span>
                            </td>
                            <td class="text-start">
                                <span v-text="record.customer?.name" class="fw-bold d-block"></span>
                                <small v-text="record.customer?.document_number" class="d-block"></small>
                            </td>
                            <td>
                                <span v-text="legibleFormatDate({dateString: record.start_date, type: 'date'})" class="d-block fw-semibold"></span>
                                <span v-text="legibleFormatDate({dateString: record.start_date, type: 'time'})" class="d-block fw-semibold"></span>
                            </td>
                            <td>
                                <span v-text="legibleFormatDate({dateString: record.end_date, type: 'date'})" class="d-block fw-semibold"></span>
                                <span v-text="legibleFormatDate({dateString: record.end_date, type: 'time'})" class="d-block fw-semibold"></span>
                                <small
                                    v-if="record.remaining_time_label"
                                    :class="['d-block fw-semibold mt-1', remainingTimeClass(record)]"
                                    v-text="record.remaining_time_label"></small>
                            </td>
                            <td>
                                <InputSlot
                                    hasDiv
                                    :isInputGroup="false"
                                    :divInputClass="['d-flex flex-wrap justify-content-center gap-2 gap-md-1']"
                                    xl="12"
                                    lg="12">
                                    <template v-slot:input>
                                        <button type="button" class="btn btn-sm btn-primary waves-effect" @click="modalActionsEntity({record})">
                                            <i class="fa fa-gear"></i>
                                            <span class="ms-2">Acciones</span>
                                        </button>
                                    </template>
                                </InputSlot>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="text-center" colspan="99">
                                <WithoutData type="image"/>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center" v-if="!lists.entity.extras.loading && lists.entity.records?.total > 0">
        <Paginator :links="lists.entity.records.links" @clickPage="listEntity"/>
    </div>

    <div class="modal fade" :id="forms.entity.createUpdate.extras.modals.actions.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold">Detalle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center g-1">
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <span class="fw-semibold">• Cliente:</span>
                            <span class="ms-2" v-text="forms.entity.createUpdate.extras.modals.actions.data?.customer?.name"></span>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <span class="fw-semibold">• Fecha de inicio:</span>
                            <span class="ms-2" v-text="legibleFormatDate({dateString: forms.entity.createUpdate.extras.modals.actions.data?.start_date, type: 'datetime'})"></span>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <span class="fw-semibold">• Fecha de finalización:</span>
                            <span class="ms-2" v-text="legibleFormatDate({dateString: forms.entity.createUpdate.extras.modals.actions.data?.end_date, type: 'datetime'})"></span>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12" v-if="forms.entity.createUpdate.extras.modals.actions.data?.remaining_time_label">
                            <span class="fw-semibold">Tiempo restante:</span>
                            <span
                                :class="['ms-2 fw-semibold', remainingTimeClass(forms.entity.createUpdate.extras.modals.actions.data)]"
                                v-text="forms.entity.createUpdate.extras.modals.actions.data?.remaining_time_label"></span>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <span class="fw-semibold">• Origen:</span>
                            <span class="ms-2" v-text="forms.entity.createUpdate.extras.modals.actions.data?.formatted_type"></span>
                            <span v-if="isDefined({value: forms.entity.createUpdate.extras.modals.actions.data?.sale_header?.serie_sequential})" class="ms-2 fw-semibold" v-text="forms.entity.createUpdate.extras.modals.actions.data?.sale_header?.serie_sequential"></span>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 d-flex align-items-center flex-wrap gap-2">
                            <span class="fw-semibold">• Estado:</span>
                            <StatusBadge
                                :status="forms.entity.createUpdate.extras.modals.actions.data?.status"
                                :formatted-status="forms.entity.createUpdate.extras.modals.actions.data?.formatted_status"
                                :custom-variants="statusBadgeSubscriptionVariants"/>
                        </div>
                        <div v-if="['canceled'].includes(forms.entity.createUpdate.extras.modals.actions.data?.status)" class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <span class="fw-semibold">• Motivo:</span>
                            <span class="ms-2" v-text="forms.entity.createUpdate.extras.modals.actions.data?.motive"></span>
                        </div>
                        <div v-if="['active'].includes(forms.entity.createUpdate.extras.modals.actions.data?.status)" class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mt-4">
                            <div class="text-center cursor-pointer p-1" @click="renewEntity({})">
                                <div class="badge bg-primary p-3 rounded mb-1">
                                    <i class="fa-solid fa-rotate-right fs-3"></i>
                                </div>
                                <span class="d-block fw-semibold text-primary">Renovar membresía</span>
                            </div>
                        </div>
                        <div v-if="['active'].includes(forms.entity.createUpdate.extras.modals.actions.data?.status)" class="col-xl-3 col-lg-3 col-md-3 col-sm-3 mt-4">
                            <div class="text-center cursor-pointer p-1" @click="cancelEntity({})">
                                <div class="badge bg-danger p-3 rounded mb-1">
                                    <i class="fa-solid fa-rectangle-xmark fs-3"></i>
                                </div>
                                <span class="d-block fw-semibold text-danger">Anular membresía</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
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
import {STATUS_BADGE_CUSTOM_SUBSCRIPTION} from "@System/Helpers/ModuleConstants.js";

export default {
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
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "tracking_subscriptions", type: "list"})
                    },
                    filters: {
                        branch: null,
                        customer: null,
                        start_date: "",
                        end_date: "",
                        status: ""
                    },
                    records: {
                        total: 0
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
                                        id: null
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            status: null
                        },
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_subscriptions"}),
                    page: {
                        title: "Membresías",
                        active: true,
                        menu: {
                            id: "menu-customers-subscriptions"
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

                this.options.branches  = initParams.data.config.branches;
                this.options.customers = initParams.data.config.customers;
                this.options.subscription_items = initParams.data.config.subscription_items;

            }

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                this.lists.entity.filters.branch = this.branches[0];
                resolve(true);

            });

        },

        // ============================================
        // Entity List Methods
        // ============================================
        async listEntity({url = null}) {

            const filters = Utils.cloneJson(this.lists.entity.filters);
            const filterJson = {
                branch_id:   filters?.branch?.code,
                customer_id: filters?.customer?.code,
                start_date:  filters?.start_date,
                end_date:    filters?.end_date,
                status:      filters?.status
            };

            this.lists.entity.extras.loading = true;

            try {

                const response = await Requests.get({
                    route: url || this.lists.entity.extras.route,
                    data: filterJson
                });

                this.lists.entity.records = response?.data;

            }finally {

                this.lists.entity.extras.loading = false;

            }

        },

        // ============================================
        // Form Methods
        // ============================================
        modalActionsEntity({record = null}) {

            this.forms.entity.createUpdate.extras.modals.actions.data = record;

            Alerts.modals({
                type: "show",
                id: this.forms.entity.createUpdate.extras.modals.actions.id
            });

        },
        createManualEntity() {

            const self = this;

            Swal.fire({
                title: "Agregar membresía manual",
                html: `<div class="text-start">
                           <p class="small text-muted mb-3">Registra una vigencia sin generar una venta. El sistema validará solapamientos según la política de la empresa.</p>
                           <div class="row g-2">
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Sucursal</label>
                                   <select class="form-select" id="manualBranch"></select>
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Cliente</label>
                                   <select class="form-select" id="manualCustomer"></select>
                               </div>
                               <div class="col-12">
                                   <label class="form-label colon-at-end">Membresía de catálogo</label>
                                   <select class="form-select" id="manualItem"></select>
                                   <small class="text-muted">Opcional. Si la seleccionas, se toma como referencia de duración.</small>
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Fecha inicial</label>
                                   <input type="datetime-local" class="form-control" id="manualStartDate">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Fecha final</label>
                                   <input type="datetime-local" class="form-control" id="manualEndDate">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Límite diario</label>
                                   <input type="number" min="1" max="999" class="form-control" id="manualAttendanceLimit" value="1">
                               </div>
                               <div class="col-12 col-md-6 d-flex align-items-end">
                                   <label class="form-check mb-2">
                                       <input class="form-check-input" type="checkbox" id="manualWelcomeEmail" checked>
                                       <span class="form-check-label">Enviar correo de agradecimiento</span>
                                   </label>
                               </div>
                               <div class="col-12">
                                   <label class="form-label colon-at-end">Observación</label>
                                   <textarea class="form-control no-resize" maxlength="500" rows="2" id="manualObservation"></textarea>
                               </div>
                           </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Agregar membresía",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "br-btn br-btn-action-create",
                    cancelButton: "br-btn br-btn-cancel ms-3"
                },
                didOpen() {
                    self.fillSwalSelect("manualBranch", self.branches, self.lists.entity.filters.branch?.code);
                    self.fillSwalSelect("manualCustomer", self.customers, self.lists.entity.filters.customer?.code);
                    self.fillSwalSelect("manualItem", [{code: "", label: "Sin plan de catálogo"}].concat(self.subscriptionItems), "");
                },
                preConfirm() {
                    const branchId = document.getElementById("manualBranch")?.value;
                    const customerId = document.getElementById("manualCustomer")?.value;
                    const itemId = document.getElementById("manualItem")?.value;
                    const startDate = document.getElementById("manualStartDate")?.value;
                    const endDate = document.getElementById("manualEndDate")?.value;
                    const attendanceLimit = document.getElementById("manualAttendanceLimit")?.value;
                    const observation = document.getElementById("manualObservation")?.value;
                    const sendWelcomeEmail = document.getElementById("manualWelcomeEmail")?.checked;

                    if(!branchId || !customerId || !startDate || !endDate) {
                        Swal.showValidationMessage("Selecciona sucursal, cliente y rango de vigencia.");
                        return false;
                    }

                    if(startDate >= endDate) {
                        Swal.showValidationMessage("La fecha final debe ser mayor o igual a la fecha inicial.");
                        return false;
                    }

                    return {
                        branch_id: branchId,
                        customer_id: customerId,
                        item_id: itemId || null,
                        start_date: startDate.replace("T", " "),
                        end_date: endDate.replace("T", " "),
                        attendance_limit_per_day: attendanceLimit || 1,
                        observation,
                        send_welcome_email: sendWelcomeEmail
                    };
                }
            }).then(async function(result) {
                if(result.isConfirmed) await self.processManualCreation(result.value);
                Alerts.tooltips({show: false});
            });

        },
        fillSwalSelect(id, options, selectedCode = null) {

            const select = document.getElementById(id);
            if(!select) return;

            select.innerHTML = "";
            options.forEach(option => {
                const element = document.createElement("option");
                element.value = option.code;
                element.textContent = option.label;
                if(String(option.code) === String(selectedCode ?? "")) element.selected = true;
                select.appendChild(element);
            });

        },
        async processManualCreation(payload) {

            Alerts.swals({type: "loading", message: "Agregando membresía"});

            try {

                const response = await Requests.post({
                    route: this.config.entity.routes.manual,
                    data: payload
                });

                Alerts.swals({show: false});

                if(Requests.valid({result: response})) {
                    Alerts.generateAlert({type: "success", msgContent: response?.data?.msg || "Membresía agregada correctamente."});
                    this.listEntity({});
                    return;
                }

                Alerts.generateAlert({type: "warning", msgContent: response?.data?.msg || "No fue posible agregar la membresía."});

            }finally {

                Alerts.swals({show: false});

            }

        },
        renewEntity({}) {

            const form = Utils.cloneJson(this.forms.entity.createUpdate.extras.modals.actions.data);

            Alerts.modals({
                type: "hide",
                id: this.forms.entity.createUpdate.extras.modals.actions.id
            });

            const self = this;

            Swal.fire({
                title: "Renovar membresía",
                html: `<div class="text-start">
                           <p class="small text-muted mb-3">Registra el nuevo rango de vigencia. Si se superpone con otra membresía activa, el sistema lo bloqueará.</p>
                           <div class="row g-2">
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Fecha inicial</label>
                                   <input type="datetime-local" class="form-control" id="renewStartDate">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Fecha final</label>
                                   <input type="datetime-local" class="form-control" id="renewEndDate">
                               </div>
                               <div class="col-12">
                                   <label class="form-label colon-at-end">Límite diario de asistencias</label>
                                   <input type="number" min="1" max="100" class="form-control" id="renewAttendanceLimit" placeholder="Usar límite actual">
                               </div>
                               <div class="col-12">
                                   <label class="form-label colon-at-end">Observación</label>
                                   <textarea class="form-control no-resize" maxlength="500" rows="2" id="renewObservation"></textarea>
                               </div>
                           </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Renovar",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "btn btn-primary waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                },
                preConfirm() {

                    const startDate = document.getElementById("renewStartDate")?.value;
                    const endDate = document.getElementById("renewEndDate")?.value;
                    const attendanceLimit = document.getElementById("renewAttendanceLimit")?.value;
                    const observation = document.getElementById("renewObservation")?.value;

                    if(!startDate || !endDate) {

                        Swal.showValidationMessage("Selecciona la fecha inicial y final.");
                        return false;

                    }

                    if(startDate >= endDate) {

                        Swal.showValidationMessage("La fecha final debe ser mayor a la fecha inicial.");
                        return false;

                    }

                    return {
                        start_date: startDate.replace("T", " "),
                        end_date: endDate.replace("T", " "),
                        attendance_limit_per_day: attendanceLimit || null,
                        observation
                    };

                }
            }).then(async function(result) {

                if(result.isConfirmed) await self.processRenewal(form, result.value);

                Alerts.tooltips({show: false});

            });

        },
        async processRenewal(form, payload) {

            Alerts.swals({type: "loading", message: "Renovando membresía"});

            try {

                const route = `${this.config.entity.routes.consult}/${form.id}/renew`;
                const response = await Requests.post({route, data: payload});

                if(Requests.valid({result: response})) {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "success", msgContent: response?.data?.msg || "Membresía renovada correctamente."});
                    this.listEntity({});

                }else {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "warning", msgContent: response?.data?.msg || "No fue posible renovar la membresía."});

                }

            }finally {

                Alerts.swals({show: false});

            }

        },
        cancelEntity({}) {

            const functionName = "cancelEntity";

            this.formErrors({functionName, type: "clear"});

            const form = Utils.cloneJson(this.forms.entity.createUpdate.extras.modals.actions.data);
            const validateForm = this.validateForm({functionName, form});

            Alerts.modals({
                type: "hide",
                id: this.forms.entity.createUpdate.extras.modals.actions.id
            });

            if(!validateForm?.bool) {

                Alerts.generateAlert({
                    messages: Utils.getErrors({errors: validateForm}),
                    msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`
                });

                Alerts.tooltips({show: false});

                return;

            }

            this.showCancelConfirmation(form);

        },
        showCancelConfirmation(form) {

            const self = this;

            Swal.fire({
                html: `<span class="d-block my-1">¿Desea anular la membresía del cliente <b>${form.customer?.name}</b>?</span>
                       <div class="form-group text-start mt-2">
                            <label class="form-label colon-at-end">Motivo</label>
                            <div class="input-group">
                                <textarea type="text" class="form-control no-resize" maxlength="999" id="motiveId"></textarea>
                            </div>
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

                    await self.processCancelation(form);

                }

                Alerts.tooltips({show: false});

            });

        },
        async processCancelation(form) {

            const motive = Swal.getHtmlContainer().querySelector("#motiveId").value;

            Alerts.swals({});

            try {

                const response = await Requests.patch({
                    route: this.config.entity.routes.cancel,
                    data: {motive},
                    id: form.id
                });

                if(Requests.valid({result: response})) {

                    Alerts.toastrs({
                        type: "success",
                        subtitle: response?.data?.msg
                    });

                    this.listEntity({});

                }else {

                    Alerts.toastrs({
                        type: "error",
                        subtitle: response?.data?.msg
                    });

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

            if(functionName === "cancelEntity") {

                this.forms.entity.createUpdate.errors = type === "set" ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            const result = {
                bool: true,
                msg: []
            };

            if(functionName === "cancelEntity") {

                if(!this.isDefined({value: form?.id})) {

                    result.msg.push(`Registro: ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

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
        remainingTimeClass(record = {}) {

            const days = Number(record?.remaining_days ?? 0);

            if(days < 0) return "text-danger";
            if(days <= 3) return "text-warning";

            return "text-success";

        }
    },
    computed: {
        breadcrumbTitles: function() {

            return [{title: "Seguimiento"}, this.config.entity.page];

        },
        branches: function() {

            return this.options?.branches?.records.map(e => ({code: e.id, label: e.name, data: e}));

        },
        customers: function() {

            return this.options?.customers?.records.map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        subscriptionItems: function() {

            return this.options?.subscription_items?.records.map(e => ({code: e.id, label: e.name, data: e})) ?? [];

        },
        statusBadgeSubscriptionVariants() {
            return STATUS_BADGE_CUSTOM_SUBSCRIPTION;
        }
    },
    watch: {
        "lists.entity.filters.branch": function(newValue, oldValue) {

            this.listEntity({});

        },
        "lists.entity.filters.customer": function(newValue, oldValue) {

            this.listEntity({});

        }
    }
};
</script>
