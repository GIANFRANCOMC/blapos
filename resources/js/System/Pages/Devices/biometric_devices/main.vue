<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Filters -->
    <FiltersSection
        :filter-by-value="filterByValue"
        @update:filterByValue="filterByValue = $event"
        :filter-word-value="filterWordValue"
        @update:filterWordValue="filterWordValue = $event"
        :filter-by-options="filterByOptions"
        :search-placeholder="searchPlaceholder"
        :loading="entityList.extras.loading"
        :filter-by-title="MODULE.texts.filters.filterBy"
        :search-title="MODULE.texts.filters.search"
        :search-button-text="MODULE.texts.actions.search"
        :add-button-text="MODULE.texts.actions.add"
        :show-add-button="true"
        :title-class="[config.forms.classes.title]"
        :select-class="config.forms.classes.select2"
        @search="handleSearch"
        @add="openModal()"/>

    <!-- Records -->
    <div class="list-section mb-1 mb-md-1 table-responsive">
        <table class="table table-hover">
            <thead class="align-middle bg-secondary text-center">
                <tr>
                    <th class="text-white" style="width: 20%;">NOMBRE</th>
                    <th class="text-white" style="width: 20%;">MARCA / MODELO</th>
                    <th class="text-white" style="width: 15%;">IP / PUERTO</th>
                    <th class="text-white" style="width: 15%;">SUCURSAL</th>
                    <th class="text-white" style="width: 15%;">ÚLTIMO CONTACTO</th>
                    <th class="text-white" style="width: 10%;">ESTADO</th>
                    <th class="text-white" style="width: 15%;">ACCIONES</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <tr v-if="entityList.extras.loading">
                    <td colspan="99">
                        <Loader/>
                    </td>
                </tr>
                <template v-else-if="entityList.records.total > 0">
                    <tr v-for="record in entityList.records.data" :key="record.id">
                        <td class="text-center">
                            <span v-text="record.name" class="fw-semibold d-block"></span>
                            <small v-if="record.serial_number" v-text="`Serie: ${record.serial_number}`" class="text-muted"></small>
                        </td>
                        <td class="text-center">
                            <span v-text="record.brand_name" class="fw-semibold d-block"></span>
                            <small v-text="record.model_name" class="text-muted"></small>
                        </td>
                        <td class="text-center">
                            <span v-text="record.ip_address" class="fw-semibold d-block"></span>
                            <small v-text="`Puerto: ${record.port}`" class="text-muted"></small>
                        </td>
                        <td class="text-center">
                            <span v-text="record.branch?.name"></span>
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold d-block">{{ formatDateTime(record.last_seen_at) }}</span>
                            <small v-if="record.failed_events_count || record.pending_events_count" class="text-danger">
                                {{ record.failed_events_count || 0 }} fallidos / {{ record.pending_events_count || 0 }} pendientes
                            </small>
                            <small v-else class="text-muted">Sin alertas</small>
                        </td>
                        <td class="text-center">
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <div class="br-table-actions justify-content-center">
                                <button
                                    type="button"
                                    class="br-icon-action br-icon-action-info"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Ver eventos"
                                    @click="openEvents(record)">
                                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="br-icon-action br-icon-action-primary"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Rotar credenciales"
                                    @click="rotateCredentials(record)">
                                    <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="br-icon-action br-icon-action-edit" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.actions.edit" @click="openModal(record)">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="99">
                        <WithoutData type="image"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>

    <!-- Modal: Create/Update -->
    <div class="modal fade" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.branch"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.branch_id"
                                xl="12"
                                lg="12">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.branch"
                                        :options="branches"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputText
                                v-model="forms[entity].createUpdate.data.name"
                                hasDiv
                                :title="MODULE.texts.form.name"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="50"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.name"
                                xl="4"
                                lg="4"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.description"
                                hasDiv
                                :title="MODULE.texts.form.description"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.description"
                                xl="8"
                                lg="8"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.brand"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.brand"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.brand"
                                        :options="brands"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.model"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.model"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.model"
                                        :options="modelsByBrand"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputText
                                v-model="forms[entity].createUpdate.data.serial_number"
                                hasDiv
                                :title="MODULE.texts.form.serialNumber"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="50"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.serial_number"
                                xl="4"
                                lg="4"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.ip_address"
                                hasDiv
                                :title="MODULE.texts.form.ipAddress"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="15"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.ip_address"
                                xl="4"
                                lg="4"/>
                            <InputNumber
                                v-model="forms[entity].createUpdate.data.port"
                                hasDiv
                                :title="MODULE.texts.form.port"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                :minValue="1"
                                :maxValue="65535"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.port"
                                xl="4"
                                lg="4"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.device_id"
                                hasDiv
                                :title="MODULE.texts.form.deviceId"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="50"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.device_id"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.status"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.status"
                                        :options="statuses"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <i class="fa fa-save"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade br-entity-modal br-modal-standard" :id="secretModal.id" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Credenciales</p>
                        <h2 class="modal-title br-entity-modal__title">Secreto visible una sola vez</h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <p class="br-field-help mb-3">Guarda estas credenciales ahora. Por seguridad, el secreto no podrá consultarse nuevamente.</p>
                    <div class="br-credential-box">
                        <small>Access key</small>
                        <strong>{{ secretModal.data.access_key }}</strong>
                    </div>
                    <div class="br-credential-box mt-2">
                        <small>Secreto</small>
                        <strong>{{ secretModal.data.secret }}</strong>
                    </div>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade br-entity-modal br-modal-standard" :id="eventsModal.id" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Biométricos</p>
                        <h2 class="modal-title br-entity-modal__title">Eventos del dispositivo</h2>
                        <p v-if="eventsModal.device" class="br-entity-table__meta mb-0">{{ eventsModal.device.name }} - {{ eventsModal.device.branch?.name }}</p>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="table-responsive br-entity-table-wrap">
                        <table class="table br-entity-table mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Evento</th>
                                    <th>Sujeto</th>
                                    <th>Intentos</th>
                                    <th>Estado</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="eventsModal.loading">
                                    <td colspan="6" class="py-4"><Loader/></td>
                                </tr>
                                <template v-else-if="eventsModal.records.total > 0">
                                    <tr v-for="event in eventsModal.records.data" :key="event.id">
                                        <td>{{ formatDateTime(event.occurred_at) }}</td>
                                        <td>{{ eventTypeLabel(event.event_type) }}</td>
                                        <td>{{ event.subject_type }} #{{ event.device_user_id }}</td>
                                        <td>{{ event.attempts }}</td>
                                        <td><span :class="['br-status-label', eventStatusClass(event.processing_status)]">{{ eventStatusLabel(event.processing_status) }}</span></td>
                                        <td>{{ event.last_error || "-" }}</td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6"><WithoutData type="image"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Paginator v-if="eventsModal.records.total > 0" :links="eventsModal.records.links" @clickPage="listEvents"/>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

const MODULE_CONFIG = {
    entity: "biometric_devices",
    menuId: "menu-configuration-biometric-devices",
    pageTitle: "Dispositivos biométricos",
    pageTitleSingular: "Dispositivo biométrico",
    breadcrumbParent: "Infraestructura",
    perPage: 10
};

const FORM_FIELDS = {
    branch: null,
    name: "",
    description: "",
    brand: null,
    model: null,
    serial_number: "",
    ip_address: "",
    port: null,
    device_id: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    branch: {getCode: true},
    name: {trim: true},
    description: {normalize: true},
    brand: {getCode: true},
    model: {getCode: true},
    serial_number: {trim: true},
    ip_address: {trim: true},
    port: {toNumber: true, minValue: 1, maxValue: 65535},
    device_id: {trim: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    branch: {required: true},
    name: {required: true},
    description: {required: false},
    brand: {required: true},
    model: {required: true},
    serial_number: {required: false},
    ip_address: {required: true, ip: true},
    port: {required: true, number: true, min: 1, max: 65535},
    device_id: {required: false},
    status: {required: true}
};

const ERROR_LABELS = {
    branch: "Sucursal",
    name: "Nombre",
    description: "Descripción",
    brand: "Marca",
    model: "Modelo",
    serial_number: "Número de serie",
    ip_address: "Dirección IP",
    port: "Puerto",
    device_id: "ID del dispositivo",
    status: "Estado"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "name", label: "Nombre"},
    {code: "serial_number", label: "Serie"},
    {code: "brand", label: "Marca"},
    {code: "model", label: "Modelo"},
    {code: "ip_address", label: "IP"},
    {code: "port", label: "Puerto"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar",
        edit: "Editar"
    },
    form: {
        branch: "Sucursal",
        name: "Nombre",
        description: "Descripción",
        brand: "Marca",
        model: "Modelo",
        serialNumber: "Número de serie",
        ipAddress: "Dirección IP",
        port: "Puerto",
        deviceId: "ID del dispositivo",
        status: "Estado"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar dispositivo"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "BiometricDevicesMain",
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];
        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            secretModal: {
                id: Utils.uuid(),
                data: {}
            },
            eventsModal: {
                id: Utils.uuid(),
                device: null,
                loading: false,
                records: {total: 0, data: [], links: []}
            }
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-configuration", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams();

        this.isInitialized = true;

        if(initParams) {

            Alerts.swals({show: false});
            this.listEntity({});

        }

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.branches = response.data.config.branches;
                this.options.brands   = response.data.config.brands;
                this.options.models   = response.data.config.models;
                this.options.statuses = response.data.config.statuses;

            }

            return Requests.valid({result: response});

        },
        // List
        async listEntity(params = null) {

            const entityList   = this.lists[this.entity];
            const emptyRecords = {total: 0, data: [], links: []};
            const filters      = Utils.cloneJson(entityList.filters);
            const filterData   = {per_page: this.MODULE.config.perPage, filter_by: filters.filter_by?.code, word: filters.word};

            entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;

                let requestUrl  = url || entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObj = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);

                    });

                    requestUrl = `${urlObj.pathname}${urlObj.search}`;

                }else {

                    requestData = filterData;

                }

                const response = await Requests.get({route: requestUrl, data: requestData, showAlert: true});

                entityList.records = response?.data ?? emptyRecords;

            }catch(error) {

                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {

            this.listEntity({});

        },
        async rotateCredentials(record) {

            const confirmation = await Swal.fire({
                title: "Rotar credenciales",
                text: "El dispositivo dejará de aceptar las credenciales anteriores. El nuevo secreto se mostrará una sola vez.",
                icon: "warning",
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                buttonsStyling: false,
                confirmButtonText: "Rotar credenciales",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--warning",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--warning",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                }
            });

            if(!confirmation.isConfirmed) return;

            Alerts.swals({type: "loading", message: "Rotando credenciales"});
            const result = await Requests.patch({route: `${this.routeActions.store}/${record.id}/credentials`});
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.secretModal.data = result.data.data || {};
                Alerts.modals({type: "show", id: this.secretModal.id});
                await this.listEntity({});
                return;
            }

            Alerts.generateAlert({type: "error", msgContent: result?.data?.msg || "No se pudieron rotar las credenciales."});

        },
        async openEvents(record) {

            this.eventsModal.device = record;
            this.eventsModal.records = {total: 0, data: [], links: []};
            Alerts.modals({type: "show", id: this.eventsModal.id});
            await this.listEvents();

        },
        async listEvents(url = null) {

            if(!this.eventsModal.device?.id) return;

            const requestUrl = typeof url === "object" ? url.url : url;
            this.eventsModal.loading = true;
            const result = await Requests.get({
                route: requestUrl || `${this.routeActions.store}/${this.eventsModal.device.id}/events`,
                data: {per_page: 10},
                showAlert: true
            });
            this.eventsModal.loading = false;

            if(Requests.valid({result})) this.eventsModal.records = result.data.data;

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const branchOption = this.branches.find(b => b.code === record?.branch?.id),
                      brandOption  = this.brands.find(b => b.code === record.brand),
                      modelOption  = this.modelsByBrand.find(m => m.code === record.model),
                      statusOption = this.statuses.find(s => s.code === record.status);

                entityForms.data.id            = record.id;
                entityForms.data.branch        = branchOption;
                entityForms.data.name          = record.name;
                entityForms.data.description   = record.description;
                entityForms.data.brand         = brandOption;
                entityForms.data.model         = modelOption;
                entityForms.data.serial_number = record.serial_number;
                entityForms.data.ip_address    = record.ip_address;
                entityForms.data.port          = record.port;
                entityForms.data.device_id     = record.device_id;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.branch = this.branches.length > 0 ? this.branches[0] : null;
                entityForms.data.brand  = this.brands.length > 0 ? this.brands[0] : null;
                entityForms.data.model  = this.modelsByBrand.length > 0 ? this.modelsByBrand[0] : null;
                entityForms.data.status = this.statuses.length > 0 ? this.statuses[0] : null;
                entityForms.data.port   = 4370;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = Forms.validateFormData(formData, this.MODULE.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSaving = false;
                    return;

                }

                const preparedData = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);

                // Map input data to request data
                preparedData.branch_id = preparedData.branch;
                delete preparedData.branch;

                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                const result        = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: entityForms.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);

                    const entityList  = this.entityList;
                    const currentPage = entityList?.records?.current_page ?? 1;

                    this.listEntity({url: `${entityList?.extras?.route || ""}?page=${currentPage}`});

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        formatDateTime(value) {

            return value ? Utils.legibleFormatDate({dateString: value, type: "datetime"}) : "Sin contacto";

        },
        eventStatusLabel(status) {

            return {
                pending: "Pendiente",
                processed: "Procesado",
                failed: "Fallido",
                ignored: "Ignorado"
            }[status] || status || "-";

        },
        eventStatusClass(status) {

            return {
                pending: "br-status-label--warning",
                processed: "br-status-active",
                failed: "br-status-inactive",
                ignored: "br-status-inactive"
            }[status] || "br-status-inactive";

        },
        eventTypeLabel(type) {

            return {
                check_in: "Ingreso",
                check_out: "Salida",
                fingerprint_sync: "Sincronizacion de huella",
                heartbeat: "Contacto",
                error: "Error"
            }[type] || type || "-";

        },
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        routeActions() {

            return this.config.entity.routes;

        },
        entityList() {

            return this.lists[this.entity];

        },
        breadcrumbTitles() {

            return [
                {title: this.MODULE.config.breadcrumbParent},
                this.config.entity.page
            ];

        },
        branches() {

            return (this.options?.branches?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        brands() {

            return (this.options?.brands ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        modelsByBrand() {

            const brand  = this.forms[this.entity].createUpdate.data.brand?.code || "ZKTeco";
            const models = this.options.models?.[brand] ?? [];

            return models.map(m => ({code: m.code, label: m.label}));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label}));

        },
        isUpdate() {

            return this.isDefined(this.forms[this.entity].createUpdate.data.id);

        },
        modalTitles() {

            return {
                createUpdate: this.forms[this.entity].createUpdate.extras.modals.default.titles
            };

        },
        filterByOptions() {

            return this.MODULE.filterOptions;

        },
        filterByValue: {
            get() {

                return this.entityList.filters?.filter_by || this.MODULE.filterOptions[0];

            },
            set(value) {

                this.entityList.filters.filter_by = value;

            }
        },
        filterWordValue: {
            get() {

                return this.entityList.filters.word || "";

            },
            set(value) {

                this.entityList.filters.word = value;

            }
        },
        searchPlaceholder() {

            const filterBy = this.entityList.filters.filter_by;

            if(!filterBy) return "Buscar...";

            return `Buscar por ${(filterBy.label || "...").toLowerCase()}`;

        }
    },
    watch: {
        "forms.biometric_devices.createUpdate.data.brand": {
            handler(newBrand) {

                // Reset model when brand changes
                // this.forms.biometric_devices.createUpdate.data.model = null;

                // Future implementation

            },
            immediate: false
        }
    }
};
</script>

<style scoped>
</style>
