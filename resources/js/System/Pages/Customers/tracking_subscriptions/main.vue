<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Content -->
    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <FgPageHeader
                title="Membresías de clientes"
                description="Consulta las vigencias y administra las membresías asignadas.">
                <template #actions>
                    <button type="button" class="fg-button" @click="createManualEntity">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Registrar membresía
                    </button>
                </template>
            </FgPageHeader>
            <form class="fg-filters fg-filter-grid mb-3" aria-label="Filtros de membresías" @submit.prevent="listEntity({applyFilters: true})">
                <div class="fg-filter-grid__field fg-filter-grid__field--compact">
                    <FgField label="Sucursal" input-id="membership-list-branch">
                        <FgSelect
                            v-model="lists.entity.filters.branch"
                            input-id="membership-list-branch"
                            :options="branches"
                            :clearable="false"
                            :searchable="false"
                            placeholder="Selecciona una sucursal"/>
                    </FgField>
                </div>
                <div class="fg-filter-grid__field fg-filter-grid__field--compact">
                    <FgField label="Cliente" input-id="membership-list-customer">
                        <FgSelect
                            v-model="lists.entity.filters.customer"
                            input-id="membership-list-customer"
                            :options="customers"
                            placeholder="Todos los clientes"/>
                    </FgField>
                </div>
                <div class="fg-filter-grid__field fg-filter-grid__field--compact">
                    <InputDate v-model="lists.entity.filters.start_date" title="Desde"/>
                </div>
                <div class="fg-filter-grid__field fg-filter-grid__field--compact">
                    <InputDate v-model="lists.entity.filters.end_date" title="Hasta"/>
                </div>
                <div class="fg-filter-grid__field fg-filter-grid__field--compact">
                    <InputSelect
                        v-model="lists.entity.filters.status"
                        title="Estado"
                        :options="membershipListStatuses"
                        default-option-label="Todos los estados"/>
                </div>
                <div class="fg-filter-grid__actions">
                    <SearchButton type="submit" :disabled="lists.entity.extras.loading || !lists.entity.filters.branch?.code"/>
                    <ClearFiltersButton :disabled="lists.entity.extras.loading" @clear="clearMembershipFilters"/>
                </div>
            </form>

            <section aria-labelledby="memberships-list-title">
                <h2 id="memberships-list-title" class="fg-section-title mb-2">Membresías registradas</h2>
                <div class="fg-table-scroll" tabindex="0" role="region" aria-label="Membresías registradas">
                    <table class="table fg-entity-table fg-entity-table--directory mb-0">
                        <thead class="fg-table-header-surface">
                            <tr>
                                <th scope="col">Cliente</th>
                                <th scope="col">Duración</th>
                                <th scope="col">Sucursal</th>
                                <th scope="col">Inicio</th>
                                <th scope="col">Vence</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center"><span class="visually-hidden">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="lists.entity.extras.loading" class="fg-table__state">
                                <td colspan="7"><Loader label="Buscando membresías…"/></td>
                            </tr>
                            <tr v-else-if="lists.entity.extras.loadFailed" class="fg-table__state">
                                <td colspan="7">
                                    <FgEmptyState title="No se pudieron cargar las membresías" description="Reintenta con los mismos filtros." role="alert">
                                        <button type="button" class="fg-button fg-button--secondary" @click="listEntity({})">Reintentar</button>
                                    </FgEmptyState>
                                </td>
                            </tr>
                            <template v-else-if="lists.entity.records.total > 0">
                                <tr v-for="record in lists.entity.records.data" :key="record.id">
                                    <td>
                                        <span class="fg-entity-table__name">{{ record.customer?.name || '—' }}</span>
                                        <span v-if="record.customer?.document_number" class="fg-entity-table__meta">{{ record.customer.document_number }}</span>
                                    </td>
                                    <td>{{ record.formatted_duration || '—' }}</td>
                                    <td>{{ record.branch?.name || '—' }}</td>
                                    <td>{{ legibleFormatDate({dateString: record.start_date, type: 'datetime'}) || '—' }}</td>
                                    <td>
                                        {{ legibleFormatDate({dateString: record.end_date, type: 'datetime'}) || '—' }}
                                        <span
                                            v-if="record.remaining_time_label"
                                            :class="['fg-entity-table__meta d-block', remainingTimeClass(record)]">
                                            {{ record.remaining_time_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge :status="record.status" :formatted-status="record.formatted_status" :custom-variants="statusBadgeSubscriptionVariants"/>
                                    </td>
                                    <td class="text-center">
                                        <ActionsButton @click="modalActionsEntity({record})"/>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else class="fg-table__state">
                                <td colspan="7">
                                    <FgEmptyState
                                        v-if="!lists.entity.appliedFilters?.branch"
                                        title="Selecciona una sucursal"
                                        description="Elige una sucursal y pulsa Buscar para consultar las membresías."/>
                                    <FgEmptyState
                                        v-else-if="hasMembershipFilters"
                                        title="No hay coincidencias"
                                        description="Prueba con otros filtros."/>
                                    <FgEmptyState v-else title="La sucursal aún no tiene membresías registradas"/>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <Paginator :links="lists.entity.records.links" :pagination="lists.entity.records" :loading="lists.entity.extras.loading" @clickPage="listEntity"/>

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal fg-modal--compact" aria-labelledby="membership-detail-title" :id="forms.entity.createUpdate.extras.modals.actions.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="fg-modal__header">
                    <h2 id="membership-detail-title" class="fg-modal__title mb-0">Detalle</h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="fg-modal__body">
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
                        <div v-if="forms.entity.createUpdate.extras.modals.actions.data?.status === 'active'" class="col-12 d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="fg-button fg-button--secondary" @click="renewEntity({})">
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                Renovar membresía
                            </button>
                            <button type="button" class="fg-button fg-button--danger" @click="cancelEntity({})">
                                <i class="fa-solid fa-rectangle-xmark" aria-hidden="true"></i>
                                Anular membresía
                            </button>
                        </div>
                    </div>
                </div>
                <div class="fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ClearFiltersButton from "@System/Components/Generics/ClearFiltersButton.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import SearchButton from "@/Shared/SearchButton.vue";
import ActionsButton from "@System/Components/Generics/ActionsButton.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";
import {STATUS_BADGE_CUSTOM_SUBSCRIPTION} from "@System/Helpers/ModuleConstants.js";

export default {
    components: {ClearFiltersButton, FgEmptyState, FgSelect, FgField, FgPageHeader, ActionsButton, SearchButton},
    mixins: [InitialPageMixin],
    mounted: async function() {

        navbarItem("menu-parent-operations", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        const initialized = await this.loadInitialParams(() => this.initParams({}));
        if(initialized) {
            await this.initOthers({});
            await this.listEntity({});
        }

    },
    data() {
        return {
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        loadFailed: false,
                        route: Requests.config({entity: "tracking_subscriptions", type: "list"})
                    },
                    appliedFilters: null,
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
                        title: "Membresías de clientes",
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
        clearMembershipFilters() {
            const branch = this.lists.entity.appliedFilters?.branch ?? this.branches[0] ?? null;
            this.lists.entity.filters = {branch, customer: null, start_date: "", end_date: "", status: ""};
            Alerts.showFiltersCleared();
        },
        async listEntity({url = null, applyFilters = false}) {
            if(applyFilters || !this.lists.entity.appliedFilters) {
                this.lists.entity.appliedFilters = {...this.lists.entity.filters};
            }

            const filters = this.lists.entity.appliedFilters;
            if(!filters?.branch?.code) return;
            const filterJson = {
                branch_id:   filters?.branch?.code,
                customer_id: filters?.customer?.code,
                start_date:  filters?.start_date,
                end_date:    filters?.end_date,
                status:      filters?.status
            };

            this.lists.entity.extras.loading = true;
            this.lists.entity.extras.loadFailed = false;
            this.lists.entity.records = Requests.emptyPagination();

            try {

                const response = await Requests.get({
                    route: url || this.lists.entity.extras.route,
                    data: filterJson
                });

                this.lists.entity.extras.loadFailed = !response?.bool;
                this.lists.entity.records = response?.bool ? (response.data || Requests.emptyPagination()) : Requests.emptyPagination();

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
                allowEscapeKey: false,
                title: "¿Agregar membresía manual?",
                html: `<div class="text-start">
                           <p class="fg-notice mb-3">Registra una vigencia sin generar una venta. El sistema validará solapamientos según la política de la empresa.</p>
                           <div class="row g-2">
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="manualBranch">Sucursal</label>
                                   <select class="form-select" id="manualBranch"></select>
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="manualCustomer">Cliente</label>
                                   <select class="form-select" id="manualCustomer"></select>
                               </div>
                               <div class="col-12">
                                   <label class="form-label" for="manualItem">Membresía de catálogo</label>
                                   <select class="form-select" id="manualItem"></select>
                                   <small class="text-muted">Opcional. Si la seleccionas, se toma como referencia de duración.</small>
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="manualStartDate">Fecha inicial</label>
                                   <input type="datetime-local" class="form-control" id="manualStartDate">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="manualEndDate">Fecha final</label>
                                   <input type="datetime-local" class="form-control" id="manualEndDate">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="manualAttendanceLimit">Límite diario</label>
                                   <input type="number" min="1" max="999" class="form-control" id="manualAttendanceLimit" value="1">
                               </div>
                               <div class="col-12 col-md-6 d-flex align-items-end">
                                   <label class="form-check mb-2">
                                       <input class="form-check-input" type="checkbox" id="manualWelcomeEmail" checked>
                                       <span class="form-check-label">Enviar correo de agradecimiento</span>
                                   </label>
                               </div>
                               <div class="col-12">
                                   <label class="form-label" for="manualObservation">Observación</label>
                                   <textarea class="form-control no-resize" maxlength="500" rows="2" id="manualObservation"></textarea>
                               </div>
                           </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                confirmButtonText: "Agregar membresía",
                cancelButtonText: "Cerrar",
                customClass: {
                    confirmButton: "fg-button",
                    cancelButton: "fg-button fg-button--secondary ms-3"
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
                allowEscapeKey: false,
                title: "¿Renovar membresía?",
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
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                confirmButtonText: "Renovar membresía",
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
                allowEscapeKey: false,
                title: "¿Anular membresía?",
                html: `<span class="d-block my-1">La vigencia de <b>${form.customer?.name}</b> dejará de estar activa. Indica el motivo.</span>
                       <div class="form-group text-start mt-2">
                            <label class="form-label colon-at-end">Motivo</label>
                            <div class="input-group">
                                <textarea type="text" class="form-control no-resize" maxlength="999" id="motiveId"></textarea>
                            </div>
                       </div>`,
                icon: "warning",
                allowOutsideClick: false,
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                confirmButtonText: "Anular membresía",
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
        membershipListStatuses() {
            return [
                {code: "active", label: "Vigente"},
                {code: "inactive", label: "Vencida"},
                {code: "canceled", label: "Anulada"}
            ];
        },
        hasMembershipFilters() {
            const filters = this.lists.entity.appliedFilters;
            return Boolean(filters?.customer?.code || filters?.start_date || filters?.end_date || filters?.status);
        },
        breadcrumbTitles: function() {

            return [{title: "Operaciones y clientes"}, {title: "Membresías"}, this.config.entity.page];

        },
        branches: function() {

            return (this.options?.branches?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        customers: function() {

            return (this.options?.customers?.records ?? []).map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        subscriptionItems: function() {

            return this.options?.subscription_items?.records.map(e => ({code: e.id, label: e.name, data: e})) ?? [];

        },
        statusBadgeSubscriptionVariants() {
            return STATUS_BADGE_CUSTOM_SUBSCRIPTION;
        }
    }
};
</script>
